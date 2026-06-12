<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Pdf_thumbnail Library
 *
 * Generates thumbnail images from PDF files.
 * Tool priority:
 *   1. pdftoppm (poppler-utils) — respects /Rotate natively, no security policy issues
 *   2. convert (ImageMagick)    — respects /Rotate, but blocked by PDF policy on Debian/Ubuntu
 *   3. gs (Ghostscript)         — ignores /Rotate; rotation applied manually via pdfinfo + GD
 *
 * @package     GVV
 * @subpackage  Libraries
 */
class Pdf_thumbnail
{
    private $CI;
    private $thumb_width  = 150;
    private $thumb_height = 150;
    private $pdftoppm_path = null;
    private $pdfinfo_path  = null;
    private $convert_path  = null;
    private $gs_path       = null;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->pdftoppm_path = $this->find_executable('pdftoppm', ['/usr/bin/pdftoppm', '/usr/local/bin/pdftoppm']);
        $this->pdfinfo_path  = $this->find_executable('pdfinfo',  ['/usr/bin/pdfinfo',  '/usr/local/bin/pdfinfo']);
        $this->convert_path  = $this->find_executable('convert',  ['/usr/bin/convert',  '/usr/local/bin/convert']);
        $this->gs_path       = $this->find_executable('gs',       ['/usr/bin/gs', '/usr/local/bin/gs', '/opt/local/bin/gs']);
    }

    private function find_executable($name, $paths)
    {
        foreach ($paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }
        $result = @shell_exec('which ' . escapeshellarg($name) . ' 2>/dev/null');
        if ($result) {
            $path = trim($result);
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }
        return null;
    }

    public function is_available()
    {
        return $this->pdftoppm_path !== null
            || $this->convert_path  !== null
            || $this->gs_path       !== null;
    }

    public function get_thumbnail_path($pdf_path)
    {
        $dir      = dirname($pdf_path);
        $filename = pathinfo($pdf_path, PATHINFO_FILENAME);
        return $dir . '/thumb_' . $filename . '.jpg';
    }

    public function thumbnail_exists($pdf_path)
    {
        return file_exists($this->get_thumbnail_path($pdf_path));
    }

    /**
     * Generate thumbnail for a PDF file.
     *
     * @param string $pdf_path Absolute path to the PDF file
     * @param bool   $force    Regenerate even if thumbnail already exists
     * @return array ['success' => bool, 'thumbnail_path' => string|null, 'error' => string|null]
     */
    public function generate($pdf_path, $force = false)
    {
        if (!$this->is_available()) {
            return ['success' => false, 'thumbnail_path' => null, 'error' => 'No PDF rendering tool available'];
        }

        if (!file_exists($pdf_path)) {
            return ['success' => false, 'thumbnail_path' => null, 'error' => 'PDF not found: ' . $pdf_path];
        }

        $mime = mime_content_type($pdf_path);
        if ($mime !== 'application/pdf') {
            return ['success' => false, 'thumbnail_path' => null, 'error' => 'Not a PDF: ' . $mime];
        }

        $thumb_path = $this->get_thumbnail_path($pdf_path);

        if (!$force && file_exists($thumb_path)) {
            return ['success' => true, 'thumbnail_path' => $thumb_path, 'error' => null];
        }

        // --- Attempt 1: pdftoppm (respects /Rotate natively) ---
        $temp_file = $this->try_pdftoppm($pdf_path);

        // --- Attempt 2: convert / ImageMagick (respects /Rotate, may be blocked by policy) ---
        if (!$temp_file && $this->convert_path) {
            $temp_file = $this->try_convert($pdf_path);
        }

        // --- Attempt 3: Ghostscript + manual GD rotation via pdfinfo ---
        $used_gs = false;
        if (!$temp_file && $this->gs_path) {
            $temp_file = $this->try_ghostscript($pdf_path);
            $used_gs = ($temp_file !== null);
        }

        if (!$temp_file) {
            return ['success' => false, 'thumbnail_path' => null, 'error' => 'All PDF rendering tools failed'];
        }

        // Ghostscript ignores /Rotate — apply it manually using pdfinfo (reliable for
        // incrementally-updated PDFs where raw binary scan would find stale values)
        if ($used_gs) {
            $rotation = $this->get_pdf_rotation($pdf_path);
            if ($rotation !== 0) {
                $this->rotate_jpeg($temp_file, $rotation);
            }
        }

        $resized = $this->resize_image($temp_file, $thumb_path);
        @unlink($temp_file);

        if (!$resized) {
            return ['success' => false, 'thumbnail_path' => null, 'error' => 'Failed to resize thumbnail'];
        }

        return ['success' => true, 'thumbnail_path' => $thumb_path, 'error' => null];
    }

    // -------------------------------------------------------------------------
    // Private rendering helpers
    // -------------------------------------------------------------------------

    private function try_pdftoppm($pdf_path)
    {
        if (!$this->pdftoppm_path) return null;

        $prefix = sys_get_temp_dir() . '/pdf_thumb_' . uniqid();
        $cmd = sprintf(
            '%s -r 72 -jpeg -f 1 -l 1 %s %s 2>&1',
            escapeshellcmd($this->pdftoppm_path),
            escapeshellarg($pdf_path),
            escapeshellarg($prefix)
        );
        $output = [];
        $rc = 0;
        exec($cmd, $output, $rc);

        // pdftoppm names output as prefix-1.jpg or prefix-01.jpg etc.
        $files = glob($prefix . '*.jpg');
        if ($rc === 0 && $files) {
            return $files[0];
        }
        foreach ((array)$files as $f) { @unlink($f); }
        return null;
    }

    private function try_convert($pdf_path)
    {
        $temp_file = sys_get_temp_dir() . '/pdf_thumb_' . uniqid() . '.jpg';
        $cmd = sprintf(
            '%s -density 72 -thumbnail %dx%d -background white -alpha remove -flatten %s[0] %s 2>&1',
            escapeshellcmd($this->convert_path),
            $this->thumb_width * 2,
            $this->thumb_height * 2,
            escapeshellarg($pdf_path),
            escapeshellarg($temp_file)
        );
        $output = [];
        $rc = 0;
        exec($cmd, $output, $rc);
        if ($rc === 0 && file_exists($temp_file)) {
            return $temp_file;
        }
        @unlink($temp_file);
        return null;
    }

    private function try_ghostscript($pdf_path)
    {
        $temp_file = sys_get_temp_dir() . '/pdf_thumb_' . uniqid() . '.jpg';
        $cmd = sprintf(
            '%s -dNOPAUSE -dBATCH -dSAFER -dQUIET -dFirstPage=1 -dLastPage=1 ' .
            '-sDEVICE=jpeg -dJPEGQ=85 -r72 -dPDFFitPage -g%dx%d ' .
            '-sOutputFile=%s %s 2>&1',
            escapeshellcmd($this->gs_path),
            $this->thumb_width * 2,
            $this->thumb_height * 2,
            escapeshellarg($temp_file),
            escapeshellarg($pdf_path)
        );
        $output = [];
        $rc = 0;
        exec($cmd, $output, $rc);
        if ($rc === 0 && file_exists($temp_file)) {
            return $temp_file;
        }
        @unlink($temp_file);
        return null;
    }

    // -------------------------------------------------------------------------
    // Rotation helpers
    // -------------------------------------------------------------------------

    /**
     * Return the /Rotate value (degrees, clockwise) for the first page of a PDF.
     * Uses pdfinfo if available — reliable even for incrementally-updated PDFs
     * where multiple /Rotate entries coexist in the binary and a raw text scan
     * would return a stale value.
     */
    private function get_pdf_rotation($pdf_path)
    {
        // pdfinfo parses the PDF properly and always returns the current value
        if ($this->pdfinfo_path) {
            $out = @shell_exec(
                escapeshellcmd($this->pdfinfo_path) . ' ' . escapeshellarg($pdf_path) . ' 2>/dev/null'
            );
            if ($out && preg_match('/Page rot:\s*(\d+)/i', $out, $m)) {
                return ((int)$m[1]) % 360;
            }
        }

        // Fallback: read full file and take the LAST /Rotate occurrence.
        // Still imperfect for compressed object streams, but better than first-match.
        $content = @file_get_contents($pdf_path);
        if ($content !== false && preg_match_all('/\/Rotate\s+(\d+)/', $content, $matches)) {
            return ((int)end($matches[1])) % 360;
        }

        return 0;
    }

    /**
     * Rotate a JPEG in-place using GD.
     * $degrees is the clockwise rotation as stored in PDF /Rotate (90, 180, 270).
     */
    private function rotate_jpeg($image_path, $degrees)
    {
        if (!function_exists('imagecreatefromjpeg') || $degrees === 0) return;
        $img = @imagecreatefromjpeg($image_path);
        if (!$img) return;
        // imagerotate() is counter-clockwise; invert to get clockwise
        $rotated = imagerotate($img, (360 - $degrees) % 360, 0);
        imagejpeg($rotated, $image_path, 85);
        imagedestroy($img);
        imagedestroy($rotated);
    }

    // -------------------------------------------------------------------------
    // Image resize
    // -------------------------------------------------------------------------

    private function resize_image($source_path, $dest_path)
    {
        if (!function_exists('imagecreatefromjpeg')) {
            return copy($source_path, $dest_path);
        }

        $source = @imagecreatefromjpeg($source_path);
        if (!$source) return false;

        $src_w = imagesx($source);
        $src_h = imagesy($source);
        $ratio = min($this->thumb_width / $src_w, $this->thumb_height / $src_h);
        $new_w = (int)($src_w * $ratio);
        $new_h = (int)($src_h * $ratio);

        $thumb = imagecreatetruecolor($new_w, $new_h);
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $new_w, $new_h, $src_w, $src_h);
        $result = imagejpeg($thumb, $dest_path, 85);

        imagedestroy($source);
        imagedestroy($thumb);
        return $result;
    }

    // -------------------------------------------------------------------------
    // Public utilities
    // -------------------------------------------------------------------------

    public function delete_thumbnail($pdf_path)
    {
        $thumb_path = $this->get_thumbnail_path($pdf_path);
        if (file_exists($thumb_path)) {
            return @unlink($thumb_path);
        }
        return true;
    }

    public function generate_async($pdf_path)
    {
        if (!$this->is_available()) return false;
        $old_limit = ini_get('max_execution_time');
        set_time_limit(10);
        $result = $this->generate($pdf_path);
        set_time_limit($old_limit);
        return $result['success'];
    }
}
