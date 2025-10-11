<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * File Compressor Library
 *
 * PRD Section 5.2 AC2.2: Three-track compression strategy (Phase 2 - Images only):
 * - Compressible Images (JPEG, PNG, GIF, WebP): Resize with GD + recompress in original format
 * - PDFs: Not implemented yet (will use Ghostscript /ebook in future phase)
 * - Other files: Not implemented yet (will use gzip in future phase)
 *
 * Requirements:
 * - gd: Image manipulation (resize, recompress in original format)
 */
class File_compressor {

    private $CI;
    private $config;

    // Compression statistics
    private $stats = [
        'original_size' => 0,
        'compressed_size' => 0,
        'compression_ratio' => 0,
        'method' => '',
        'original_dimensions' => '',
        'new_dimensions' => ''
    ];

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->config('attachments');
        $this->config = $this->CI->config->item('compression');
    }

    /**
     * Main compression entry point
     *
     * @param string $file_path Path to file to compress
     * @param array $options Compression options (override config)
     * @return array ['success' => bool, 'compressed_path' => string, 'stats' => array, 'error' => string]
     */
    public function compress($file_path, $options = []) {
        if (!file_exists($file_path)) {
            return ['success' => false, 'error' => 'File not found'];
        }

        // Check if compression is enabled
        if (!$this->get_config('enabled', true)) {
            return ['success' => false, 'error' => 'Compression disabled'];
        }

        // Get file info
        $original_size = filesize($file_path);
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

        // Check minimum size threshold
        $min_size = $this->get_config('min_size', 102400); // 100KB default
        if ($original_size < $min_size) {
            return ['success' => false, 'error' => 'File too small to compress'];
        }

        // Skip already compressed formats
        if ($this->is_already_compressed($extension)) {
            return ['success' => false, 'error' => 'File already compressed'];
        }

        // Initialize stats
        $this->stats['original_size'] = $original_size;

        // Route to appropriate compression method (images only for now)
        if ($this->is_image($extension)) {
            $result = $this->compress_image($file_path, $options);
        } else {
            // PDFs and other files not implemented yet
            return ['success' => false, 'error' => 'Compression not implemented for this file type yet'];
        }

        if (!$result['success']) {
            return $result;
        }

        // Check compression ratio
        if ($result['success']) {
            $compressed_size = filesize($result['compressed_path']);
            $this->stats['compressed_size'] = $compressed_size;
            $this->stats['compression_ratio'] = 1 - ($compressed_size / $original_size);

            $min_ratio = $this->get_config('min_ratio', 0.10);
            if ($this->stats['compression_ratio'] < $min_ratio) {
                // Not enough savings, use original
                if (file_exists($result['compressed_path']) && $result['compressed_path'] !== $file_path) {
                    unlink($result['compressed_path']);
                }
                return ['success' => false, 'error' => 'Compression ratio too low'];
            }

            // Log compression
            $this->log_compression($file_path, $result['compressed_path']);
        }

        $result['stats'] = $this->stats;
        return $result;
    }

    /**
     * Compress image file using GD
     * - Resize to max dimensions (1600x1200)
     * - Recompress in ORIGINAL format (JPEG stays JPEG, PNG stays PNG)
     * - NO additional gzip compression (images are already compressed)
     *
     * @param string $file_path Path to image file
     * @param array $options Compression options
     * @return array ['success' => bool, 'compressed_path' => string, 'error' => string]
     */
    private function compress_image($file_path, $options = []) {
        // Check if GD extension is available
        if (!extension_loaded('gd')) {
            return ['success' => false, 'error' => 'GD extension not available'];
        }

        // PRD AC2.2: Images are resized to max 1600x1200, recompressed in original format
        $max_width = $options['max_width'] ?? $this->get_config('image_max_width', 1600);
        $max_height = $options['max_height'] ?? $this->get_config('image_max_height', 1200);
        $quality = $options['quality'] ?? $this->get_config('image_quality', 85);

        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

        // Get image dimensions
        $info = getimagesize($file_path);
        if ($info === false) {
            return ['success' => false, 'error' => 'Invalid image file'];
        }

        list($width, $height, $type) = $info;
        $this->stats['original_dimensions'] = "{$width}x{$height}";

        // Calculate new dimensions
        if ($width > $max_width || $height > $max_height) {
            $ratio = min($max_width / $width, $max_height / $height);
            $new_width = (int)($width * $ratio);
            $new_height = (int)($height * $ratio);
        } else {
            $new_width = $width;
            $new_height = $height;
        }
        $this->stats['new_dimensions'] = "{$new_width}x{$new_height}";

        // Load source image
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($file_path);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($file_path);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($file_path);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    $source = imagecreatefromwebp($file_path);
                } else {
                    return ['success' => false, 'error' => 'WebP support not available'];
                }
                break;
            default:
                return ['success' => false, 'error' => 'Unsupported image type'];
        }

        if ($source === false) {
            return ['success' => false, 'error' => 'Failed to load image'];
        }

        // Create resized image
        $destination = imagecreatetruecolor($new_width, $new_height);

        // Preserve transparency for PNG
        if ($type == IMAGETYPE_PNG) {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
            $transparent = imagecolorallocatealpha($destination, 0, 0, 0, 127);
            imagefill($destination, 0, 0, $transparent);
        }

        // Preserve transparency for GIF
        if ($type == IMAGETYPE_GIF) {
            $transparent_index = imagecolortransparent($source);
            if ($transparent_index >= 0) {
                $transparent_color = imagecolorsforindex($source, $transparent_index);
                $transparent_new = imagecolorallocate($destination,
                    $transparent_color['red'],
                    $transparent_color['green'],
                    $transparent_color['blue']);
                imagefill($destination, 0, 0, $transparent_new);
                imagecolortransparent($destination, $transparent_new);
            }
        }

        imagecopyresampled($destination, $source, 0, 0, 0, 0,
                          $new_width, $new_height, $width, $height);

        // Create temporary output file
        $temp_output = $file_path . '.tmp';

        // Save in ORIGINAL format (PRD: keep images in original format)
        $success = false;
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $success = imagejpeg($destination, $temp_output, $quality);
                $this->stats['method'] = 'gd/resize+jpeg';
                break;
            case 'png':
                // PNG quality is 0-9 (compression level), convert from 0-100 scale
                $png_quality = 9 - round(($quality / 100) * 9);
                $success = imagepng($destination, $temp_output, $png_quality);
                $this->stats['method'] = 'gd/resize+png';
                break;
            case 'gif':
                $success = imagegif($destination, $temp_output);
                $this->stats['method'] = 'gd/resize+gif';
                break;
            case 'webp':
                if (function_exists('imagewebp')) {
                    $success = imagewebp($destination, $temp_output, $quality);
                    $this->stats['method'] = 'gd/resize+webp';
                } else {
                    imagedestroy($source);
                    imagedestroy($destination);
                    return ['success' => false, 'error' => 'WebP support not available'];
                }
                break;
            default:
                imagedestroy($source);
                imagedestroy($destination);
                return ['success' => false, 'error' => 'Unsupported image format for saving'];
        }

        imagedestroy($source);
        imagedestroy($destination);

        if (!$success) {
            if (file_exists($temp_output)) {
                unlink($temp_output);
            }
            return ['success' => false, 'error' => 'Failed to save compressed image'];
        }

        // Replace original with compressed
        if (!rename($temp_output, $file_path)) {
            unlink($temp_output);
            return ['success' => false, 'error' => 'Failed to replace original file'];
        }

        return ['success' => true, 'compressed_path' => $file_path];
    }

    /**
     * Check if file is an image
     *
     * @param string $extension File extension (lowercase)
     * @return bool True if image
     */
    private function is_image($extension) {
        $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        return in_array($extension, $image_extensions);
    }

    /**
     * Check if file extension indicates already compressed format
     *
     * @param string $extension File extension (lowercase)
     * @return bool True if already compressed
     */
    private function is_already_compressed($extension) {
        $compressed_formats = ['gz', 'zip', 'rar', '7z', 'bz2', 'xz', 'tar.gz', 'tgz'];
        return in_array($extension, $compressed_formats);
    }

    /**
     * Log compression results
     *
     * @param string $original_path Original file path
     * @param string $compressed_path Compressed file path
     */
    private function log_compression($original_path, $compressed_path) {
        $original_size_mb = round($this->stats['original_size'] / (1024 * 1024), 2);
        $compressed_size_mb = round($this->stats['compressed_size'] / (1024 * 1024), 2);
        $ratio_percent = round($this->stats['compression_ratio'] * 100, 1);

        // Image compression log with dimensions
        $message = sprintf(
            "Attachment compression: file=%s, original=%.2fMB (%s), compressed=%.2fMB (%s), ratio=%d%%, method=%s",
            basename($original_path),
            $original_size_mb,
            $this->stats['original_dimensions'],
            $compressed_size_mb,
            $this->stats['new_dimensions'],
            $ratio_percent,
            $this->stats['method']
        );

        log_message('info', $message);
    }

    /**
     * Get configuration value
     *
     * @param string $key Config key
     * @param mixed $default Default value
     * @return mixed Config value or default
     */
    private function get_config($key, $default = null) {
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }

    /**
     * Get compression statistics
     *
     * @return array Statistics array
     */
    public function get_stats() {
        return $this->stats;
    }
}

/* End of file File_compressor.php */
/* Location: ./application/libraries/File_compressor.php */
