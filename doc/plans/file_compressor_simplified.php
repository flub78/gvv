<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Simplified File Compressor Library
 *
 * Uses only gzip compression (no external dependencies)
 * Compatible with production server (only requires PHP zlib extension)
 */
class File_compressor {

    private $CI;
    private $config;

    // Compression statistics
    private $stats = [
        'original_size' => 0,
        'compressed_size' => 0,
        'compression_ratio' => 0,
        'method' => 'gzip'
    ];

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->config('attachments');
        $this->config = $this->CI->config->item('compression');
    }

    /**
     * Main compression entry point - uses gzip for all files
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

        // Compress using gzip
        $result = $this->compress_gzip($file_path, $options);

        // Check compression ratio
        if ($result['success']) {
            $compressed_size = filesize($result['compressed_path']);
            $this->stats['compressed_size'] = $compressed_size;
            $this->stats['compression_ratio'] = 1 - ($compressed_size / $original_size);

            $min_ratio = $this->get_config('min_ratio', 0.10);
            if ($this->stats['compression_ratio'] < $min_ratio) {
                // Not enough savings, use original
                if (file_exists($result['compressed_path'])) {
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
     * Compress file using gzip
     *
     * @param string $file_path Path to file to compress
     * @param array $options Compression options
     * @return array ['success' => bool, 'compressed_path' => string, 'error' => string]
     */
    private function compress_gzip($file_path, $options = []) {
        $output_path = $file_path . '.gz';

        // Get compression level (default 9 = maximum)
        $level = $options['level'] ?? $this->get_config('gzip_level', 9);

        try {
            // Read original file
            $content = file_get_contents($file_path);
            if ($content === false) {
                return ['success' => false, 'error' => 'Failed to read file'];
            }

            // Compress using gzip
            $compressed = gzencode($content, $level);
            if ($compressed === false) {
                return ['success' => false, 'error' => 'gzip compression failed'];
            }

            // Write compressed file
            if (file_put_contents($output_path, $compressed) === false) {
                return ['success' => false, 'error' => 'Failed to write compressed file'];
            }

            $this->stats['method'] = 'gzip';
            return ['success' => true, 'compressed_path' => $output_path];

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Exception: ' . $e->getMessage()];
        }
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

        $message = sprintf(
            "Attachment compression: file=%s, original=%.2fMB, compressed=%.2fMB, ratio=%d%%, method=%s",
            basename($original_path),
            $original_size_mb,
            $compressed_size_mb,
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
