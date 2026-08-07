<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Forms file storage library
 *
 * Reads/writes the on-disk representation of a form's content (HTML pages +
 * global CSS) under uploads/formulaires/{code}/. This directory is the
 * source of truth for form content — see doc/prds/remplissage_formulaires_prd.md
 * (EF2-bis) and doc/design_notes/formulaires_sync_fichiers_design.md.
 */
class Forms_file_storage {

    private $base_dir;

    function __construct() {
        $this->base_dir = rtrim(FCPATH, '/') . '/uploads/formulaires';
    }

    /**
     * Sanitize a form code into a safe directory name (mirrors the
     * sanitization already used by forms_admin::form_backup()).
     */
    public function safe_code($code) {
        $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) $code);
        return trim($safe, '-');
    }

    public function form_dir($code) {
        return $this->base_dir . '/' . $this->safe_code($code);
    }

    private function page_path($code, $page_number) {
        return $this->form_dir($code) . sprintf('/page%02d.html', (int) $page_number);
    }

    private function css_path($code) {
        return $this->form_dir($code) . '/style.css';
    }

    public function images_dir($code) {
        return $this->form_dir($code) . '/images';
    }

    /**
     * Creates a directory (recursively) at exactly mode 0770, bypassing the
     * web server process's umask — otherwise a umask of 022 (typical for
     * www-data) strips the group-write bit and leaves the CLI user unable
     * to touch files the web server created.
     */
    private function make_dir($dir) {
        if (is_dir($dir)) {
            return;
        }
        $old_umask = umask(0);
        mkdir($dir, 0770, true);
        umask($old_umask);
    }

    /** Writes a file then forces group-write, for the same umask reason as make_dir(). */
    private function write_file($path, $content) {
        file_put_contents($path, $content);
        chmod($path, 0660);
    }

    /** Copies a file then forces group-write, for the same umask reason as make_dir(). */
    private function copy_file($from, $to) {
        copy($from, $to);
        chmod($to, 0660);
    }

    /**
     * Sanitize an uploaded image's original filename into a safe basename
     * (no path separators, restricted charset) — used both for uploads and
     * for extracting an images/ folder from an imported archive.
     */
    public function safe_image_name($name) {
        $name = basename((string) $name);
        $name = preg_replace('/[^a-zA-Z0-9_.-]+/', '-', $name);
        return trim($name, '.-');
    }

    /**
     * Create the per-form directory (idempotent) with a deny-all .htaccess:
     * form content is never served as a static file, it always goes through
     * forms_public/forms_admin so widget injection still happens.
     */
    public function ensure_dir($code) {
        $dir = $this->form_dir($code);
        $this->make_dir($dir);
        $htaccess = $dir . '/.htaccess';
        if (!file_exists($htaccess)) {
            $this->write_file($htaccess, "Require all denied\n");
        }
        return $dir;
    }

    /**
     * Wraps a page's content fragment into a minimal standalone HTML5
     * document (doctype, head with a relative <link> to style.css, body).
     * This is what actually gets written to disk — see EF2-bis PRD #3:
     * a stored page must open directly in a plain browser (file://), CSS
     * resolved, without going through the application server.
     */
    private function wrap_page_html($code, $page_number, $content_html) {
        $title = htmlspecialchars($this->safe_code($code) . ' — page ' . (int) $page_number, ENT_QUOTES, 'UTF-8');
        return "<!DOCTYPE html>\n<html lang=\"fr\">\n<head>\n<meta charset=\"utf-8\">\n"
             . "<title>{$title}</title>\n<link rel=\"stylesheet\" href=\"style.css\">\n</head>\n"
             . "<body class=\"forms-public-root\">\n" . (string) $content_html . "\n</body>\n</html>\n";
    }

    /**
     * Reverses wrap_page_html(): extracts the <body> inner content back out,
     * so every caller of read_page() keeps receiving a bare fragment exactly
     * as before — only this class knows the file on disk is a full document.
     */
    private function unwrap_page_html($wrapped_html) {
        if (preg_match('/<body[^>]*>(.*)<\/body>/is', (string) $wrapped_html, $m)) {
            return trim($m[1]);
        }
        return trim((string) $wrapped_html);
    }

    public function write_page($code, $page_number, $content_html) {
        $this->ensure_dir($code);
        $wrapped = $this->wrap_page_html($code, $page_number, (string) $content_html);
        $this->write_file($this->page_path($code, $page_number), $wrapped);
    }

    public function read_page($code, $page_number) {
        $path = $this->page_path($code, $page_number);
        return file_exists($path) ? $this->unwrap_page_html(file_get_contents($path)) : null;
    }

    public function delete_page($code, $page_number) {
        $path = $this->page_path($code, $page_number);
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function page_exists($code, $page_number) {
        return file_exists($this->page_path($code, $page_number));
    }

    public function write_css($code, $css) {
        $this->ensure_dir($code);
        $this->write_file($this->css_path($code), (string) $css);
    }

    public function read_css($code) {
        $path = $this->css_path($code);
        return file_exists($path) ? file_get_contents($path) : null;
    }

    /**
     * Stores an uploaded image under images/, sanitizing its filename.
     * Returns the stored (sanitized) filename.
     */
    public function write_image($code, $filename, $content) {
        $dir = $this->images_dir($code);
        $this->make_dir($dir);
        $safe_name = $this->safe_image_name($filename);
        $this->write_file($dir . '/' . $safe_name, $content);
        return $safe_name;
    }

    public function image_path($code, $filename) {
        return $this->images_dir($code) . '/' . $this->safe_image_name($filename);
    }

    public function read_image($code, $filename) {
        $path = $this->image_path($code, $filename);
        return file_exists($path) ? file_get_contents($path) : null;
    }

    /** Sorted list of stored image filenames for a form. */
    public function list_images($code) {
        $dir = $this->images_dir($code);
        if (!is_dir($dir)) {
            return array();
        }
        $names = array();
        foreach (glob($dir . '/*') as $file) {
            if (is_file($file)) {
                $names[] = basename($file);
            }
        }
        sort($names);
        return $names;
    }

    public function delete_image($code, $filename) {
        $path = $this->image_path($code, $filename);
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * True if this form already has any content on disk (used by the
     * one-time base->file migration to skip already-migrated forms).
     */
    public function has_content($code) {
        $dir = $this->form_dir($code);
        if (!is_dir($dir)) {
            return false;
        }
        $html_files = glob($dir . '/page*.html');
        return !empty($html_files) || file_exists($this->css_path($code));
    }

    /**
     * Follows a form code rename (forms_admin::update() allows changing
     * `code`) so the directory keeps matching. No-op if there is nothing to
     * rename, or if the target already exists (avoids clobbering).
     */
    public function rename_form_dir($old_code, $new_code) {
        $old_dir = $this->form_dir($old_code);
        $new_dir = $this->form_dir($new_code);
        if ($old_dir === $new_dir || !is_dir($old_dir) || is_dir($new_dir)) {
            return;
        }
        rename($old_dir, $new_dir);
    }

    /**
     * Recursively copies a form's directory to another code (used by
     * forms_admin::duplicate()).
     */
    public function copy_form_dir($from_code, $to_code) {
        $from_dir = $this->form_dir($from_code);
        if (!is_dir($from_dir)) {
            return;
        }
        $this->ensure_dir($to_code);
        $to_dir = $this->form_dir($to_code);
        foreach (glob($from_dir . '/*') as $file) {
            $basename = basename($file);
            if ($basename === '.htaccess' || !is_file($file)) {
                continue;
            }
            $this->copy_file($file, $to_dir . '/' . $basename);
        }

        $from_images = $this->images_dir($from_code);
        if (is_dir($from_images)) {
            $to_images = $this->images_dir($to_code);
            $this->make_dir($to_images);
            foreach (glob($from_images . '/*') as $file) {
                if (is_file($file)) {
                    $this->copy_file($file, $to_images . '/' . basename($file));
                }
            }
        }
    }

    public function delete_form_dir($code) {
        $dir = $this->form_dir($code);
        if (!is_dir($dir)) {
            return;
        }
        $images_dir = $this->images_dir($code);
        if (is_dir($images_dir)) {
            foreach (glob($images_dir . '/*') as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($images_dir);
        }
        foreach (glob($dir . '/*') as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        // glob('/*') doesn't match dotfiles — remove .htaccess explicitly or rmdir() fails.
        $htaccess = $dir . '/.htaccess';
        if (file_exists($htaccess)) {
            unlink($htaccess);
        }
        rmdir($dir);
    }
}
