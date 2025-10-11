<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Attachments Configuration
 */

// === Temporary Upload Settings ===
$config['temp_upload_path'] = './uploads/attachments/temp/';
$config['temp_file_lifetime'] = 86400; // 24 hours
$config['max_pending_files_per_session'] = 20;
$config['max_temp_storage_mb'] = 500;

// === Upload Settings ===
$config['upload_max_size'] = 20480; // 20MB in KB
$config['allowed_file_types'] = 'pdf|jpg|jpeg|png|gif|doc|docx|xls|xlsx|csv|txt';

// === Compression Settings (Phase 2 - Images only) ===
// PRD Section 5.2 AC2.2: Three-track compression strategy
// - Compressible Images (JPEG, PNG, GIF, WebP): Resize + recompress in original format
// - PDFs: Not implemented yet (will use Ghostscript /ebook in future phase)
// - Other files: Not implemented yet (will use gzip in future phase)
$config['compression'] = [
    'enabled' => TRUE, // Enabled for Phase 2 (images only)
    'min_size' => 102400, // 100KB - don't compress smaller files (PRD AC2.3)
    'min_ratio' => 0.10, // Only keep compressed if >10% savings (PRD AC2.3)

    // Image compression (PRD AC2.2 & CA1.7: Resize + recompress in original format)
    'image_max_width' => 1600,  // PRD AC2.6 & AC2.7: 300 DPI at A4, optimize smartphone photos
    'image_max_height' => 1200,
    'image_quality' => 85, // Quality (0-100) for JPEG/WebP, converted to 0-9 for PNG

    // PDF compression - NOT IMPLEMENTED YET
    // 'ghostscript_path' => 'gs',
    // 'pdf_quality' => 'ebook',

    // Gzip compression - NOT IMPLEMENTED YET
    // 'gzip_level' => 9,

    // Safety
    'preserve_original_until_verified' => TRUE,
];

// === Batch Compression Settings ===
$config['batch_compression_chunk_size'] = 100;
$config['batch_compression_temp_backup'] = './uploads/attachments/_batch_backup/';
$config['batch_compression_log_detail'] = 'full'; // 'full' or 'summary'

/* End of file attachments.php */
/* Location: ./application/config/attachments.php */
