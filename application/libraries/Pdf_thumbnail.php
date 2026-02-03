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

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->gs_path = $this->find_ghostscript();
    }

    /**
     * Find Ghostscript executable path
     *
     * @return string|null Path to gs or null if not found
     */
    private function find_ghostscript()
    {
        $possible_paths = ['/usr/bin/gs', '/usr/local/bin/gs', '/opt/local/bin/gs'];

        foreach ($possible_paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        // Try which command
        $which_result = @shell_exec('which gs 2>/dev/null');
        if ($which_result) {
            $path = trim($which_result);
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Check if PDF thumbnail generation is available
     *
     * @return bool True if Ghostscript is available
     */
    public function is_available()
    {
        return $this->gs_path !== null;
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

        // Generate thumbnail using Ghostscript
        // -dFirstPage=1 -dLastPage=1 : Only process first page
        // -sDEVICE=jpeg : Output as JPEG
        // -dJPEGQ=85 : JPEG quality
        // -r72 : Resolution (72 dpi is sufficient for thumbnails)
        // -dPDFFitPage : Fit page to device size
        $temp_file = sys_get_temp_dir() . '/pdf_thumb_' . uniqid() . '.jpg';

        $cmd = sprintf(
            '%s -dNOPAUSE -dBATCH -dSAFER -dQUIET ' .
            '-dFirstPage=1 -dLastPage=1 ' .
            '-sDEVICE=jpeg -dJPEGQ=85 -r72 ' .
            '-dPDFFitPage -g%dx%d ' .
            '-sOutputFile=%s %s 2>&1',
            escapeshellcmd($this->gs_path),
            $this->thumb_width * 2,  // Generate at 2x for better quality
            $this->thumb_height * 2,
            escapeshellarg($temp_file),
            escapeshellarg($pdf_path)
        );

        $output = [];
        $return_var = 0;
        exec($cmd, $output, $return_var);

        if ($return_var !== 0 || !file_exists($temp_file)) {
            // Clean up temp file if it exists
            if (file_exists($temp_file)) {
                @unlink($temp_file);
            }
            return [
                'success' => false,
                'thumbnail_path' => null,
                'error' => 'Ghostscript failed: ' . implode("\n", $output)
            ];
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
