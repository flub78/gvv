<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Pdf_thumbnail Library
 *
 * Generates thumbnail images from PDF files using Ghostscript.
 * Falls back gracefully if generation is not possible.
 *
 * @package     GVV
 * @subpackage  Libraries
 */
class Pdf_thumbnail
{
    private $CI;
    private $thumb_width = 150;
    private $thumb_height = 150;
    private $gs_path = null;
    private $convert_path = null;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->convert_path = $this->find_executable('convert', ['/usr/bin/convert', '/usr/local/bin/convert']);
        $this->gs_path = $this->find_executable('gs', ['/usr/bin/gs', '/usr/local/bin/gs', '/opt/local/bin/gs']);
    }

    /**
     * Find an executable by checking common paths then `which`
     */
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

    /**
     * @deprecated Use find_executable() instead
     */
    private function find_ghostscript()
    {
        return $this->find_executable('gs', ['/usr/bin/gs', '/usr/local/bin/gs', '/opt/local/bin/gs']);
    }

    /**
     * Check if PDF thumbnail generation is available
     *
     * @return bool True if convert or Ghostscript is available
     */
    public function is_available()
    {
        return $this->convert_path !== null || $this->gs_path !== null;
    }

    /**
     * Get thumbnail path for a PDF file
     *
     * @param string $pdf_path Path to the PDF file
     * @return string Path where thumbnail should be stored
     */
    public function get_thumbnail_path($pdf_path)
    {
        $dir = dirname($pdf_path);
        $filename = pathinfo($pdf_path, PATHINFO_FILENAME);
        return $dir . '/thumb_' . $filename . '.jpg';
    }

    /**
     * Check if thumbnail exists for a PDF
     *
     * @param string $pdf_path Path to the PDF file
     * @return bool True if thumbnail exists
     */
    public function thumbnail_exists($pdf_path)
    {
        $thumb_path = $this->get_thumbnail_path($pdf_path);
        return file_exists($thumb_path);
    }

    /**
     * Generate thumbnail for a PDF file
     *
     * @param string $pdf_path Path to the PDF file
     * @param bool $force Force regeneration even if thumbnail exists
     * @return array ['success' => bool, 'thumbnail_path' => string|null, 'error' => string|null]
     */
    public function generate($pdf_path, $force = false)
    {
        // Check if Ghostscript is available
        if (!$this->is_available()) {
            return [
                'success' => false,
                'thumbnail_path' => null,
                'error' => 'Ghostscript not available on this server'
            ];
        }

        // Check if PDF exists
        if (!file_exists($pdf_path)) {
            return [
                'success' => false,
                'thumbnail_path' => null,
                'error' => 'PDF file not found: ' . $pdf_path
            ];
        }

        // Check if it's actually a PDF
        $mime = mime_content_type($pdf_path);
        if ($mime !== 'application/pdf') {
            return [
                'success' => false,
                'thumbnail_path' => null,
                'error' => 'File is not a PDF: ' . $mime
            ];
        }

        $thumb_path = $this->get_thumbnail_path($pdf_path);

        // Check if thumbnail already exists and we're not forcing regeneration
        if (!$force && file_exists($thumb_path)) {
            return [
                'success' => true,
                'thumbnail_path' => $thumb_path,
                'error' => null
            ];
        }

        $temp_file = sys_get_temp_dir() . '/pdf_thumb_' . uniqid() . '.jpg';

        // Try ImageMagick convert first (preferred: respects /Rotate page attribute).
        // If it fails (e.g. due to ImageMagick PDF security policy on Debian/Ubuntu),
        // fall back to Ghostscript.
        $output = [];
        $return_var = 1;
        $used_gs = false;

        if ($this->convert_path) {
            $cmd = sprintf(
                '%s -density 72 -thumbnail %dx%d -background white -alpha remove -flatten %s[0] %s 2>&1',
                escapeshellcmd($this->convert_path),
                $this->thumb_width * 2,
                $this->thumb_height * 2,
                escapeshellarg($pdf_path),
                escapeshellarg($temp_file)
            );
            exec($cmd, $output, $return_var);
            if ($return_var !== 0 && file_exists($temp_file)) {
                @unlink($temp_file);
            }
        }

        // Fall back to Ghostscript if convert is unavailable or failed
        if (($return_var !== 0 || !file_exists($temp_file)) && $this->gs_path) {
            $used_gs = true;
            $output = [];
            $return_var = 0;
            $cmd = sprintf(
                '%s -dNOPAUSE -dBATCH -dSAFER -dQUIET ' .
                '-dFirstPage=1 -dLastPage=1 ' .
                '-sDEVICE=jpeg -dJPEGQ=85 -r72 ' .
                '-dPDFFitPage -g%dx%d ' .
                '-sOutputFile=%s %s 2>&1',
                escapeshellcmd($this->gs_path),
                $this->thumb_width * 2,
                $this->thumb_height * 2,
                escapeshellarg($temp_file),
                escapeshellarg($pdf_path)
            );
            exec($cmd, $output, $return_var);
        }

        if ($return_var !== 0 || !file_exists($temp_file)) {
            if (file_exists($temp_file)) {
                @unlink($temp_file);
            }
            return [
                'success' => false,
                'thumbnail_path' => null,
                'error' => 'thumbnail generation failed (convert+gs): ' . implode("\n", $output)
            ];
        }

        // Ghostscript ignores /Rotate — apply it manually via GD
        if ($used_gs) {
            $rotation = $this->get_pdf_rotation($pdf_path);
            if ($rotation !== 0) {
                $this->rotate_jpeg($temp_file, $rotation);
            }
        }

        // Resize to final thumbnail size using GD
        $resized = $this->resize_image($temp_file, $thumb_path);

        // Clean up temp file
        @unlink($temp_file);

        if (!$resized) {
            return [
                'success' => false,
                'thumbnail_path' => null,
                'error' => 'Failed to resize thumbnail'
            ];
        }

        return [
            'success' => true,
            'thumbnail_path' => $thumb_path,
            'error' => null
        ];
    }

    /**
     * Resize image to thumbnail size
     *
     * @param string $source_path Source image path
     * @param string $dest_path Destination path
     * @return bool Success
     */
    private function resize_image($source_path, $dest_path)
    {
        if (!function_exists('imagecreatefromjpeg')) {
            // GD not available, just copy the file
            return copy($source_path, $dest_path);
        }

        $source = @imagecreatefromjpeg($source_path);
        if (!$source) {
            return false;
        }

        $src_width = imagesx($source);
        $src_height = imagesy($source);

        // Calculate new dimensions maintaining aspect ratio
        $ratio = min($this->thumb_width / $src_width, $this->thumb_height / $src_height);
        $new_width = (int)($src_width * $ratio);
        $new_height = (int)($src_height * $ratio);

        // Create new image
        $thumb = imagecreatetruecolor($new_width, $new_height);

        // Preserve quality
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $new_width, $new_height, $src_width, $src_height);

        // Save
        $result = imagejpeg($thumb, $dest_path, 85);

        // Free memory
        imagedestroy($source);
        imagedestroy($thumb);

        return $result;
    }

    /**
     * Read /Rotate value from a PDF file (scans first 64 KB of the file)
     */
    private function get_pdf_rotation($pdf_path)
    {
        $handle = @fopen($pdf_path, 'rb');
        if (!$handle) return 0;
        $content = fread($handle, 65536);
        fclose($handle);
        if (preg_match('/\/Rotate\s+(\d+)/', $content, $m)) {
            return ((int)$m[1]) % 360;
        }
        return 0;
    }

    /**
     * Rotate a JPEG file in-place using GD.
     * $degrees is the clockwise rotation as stored in PDF /Rotate (90, 180, 270).
     * imagerotate() is counter-clockwise, so we invert.
     */
    private function rotate_jpeg($image_path, $degrees)
    {
        if (!function_exists('imagecreatefromjpeg') || $degrees === 0) return;
        $img = @imagecreatefromjpeg($image_path);
        if (!$img) return;
        $ccw = (360 - $degrees) % 360;
        $rotated = imagerotate($img, $ccw, 0);
        imagejpeg($rotated, $image_path, 85);
        imagedestroy($img);
        imagedestroy($rotated);
    }

    /**
     * Delete thumbnail for a PDF file
     *
     * @param string $pdf_path Path to the PDF file
     * @return bool True if deleted or didn't exist
     */
    public function delete_thumbnail($pdf_path)
    {
        $thumb_path = $this->get_thumbnail_path($pdf_path);
        if (file_exists($thumb_path)) {
            return @unlink($thumb_path);
        }
        return true;
    }

    /**
     * Generate thumbnail asynchronously (non-blocking)
     * This method returns immediately and generation happens in background
     *
     * @param string $pdf_path Path to the PDF file
     * @return bool True if background process was started
     */
    public function generate_async($pdf_path)
    {
        if (!$this->is_available()) {
            return false;
        }

        // Use a simple approach: generate synchronously but with timeout
        // For true async, we would need a job queue system
        // This is a pragmatic approach for the current architecture

        // Set a short time limit for this operation
        $old_limit = ini_get('max_execution_time');
        set_time_limit(10); // 10 seconds max for thumbnail generation

        $result = $this->generate($pdf_path);

        // Restore original limit
        set_time_limit($old_limit);

        return $result['success'];
    }
}
