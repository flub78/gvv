# Implementation Plan: Attachments Feature Improvement

**Related PRD:** `doc/prds/attachments_improvement_prd.md`
**Status:** Planning
**Created:** 2025-10-09
**Last Updated:** 2025-10-10 (updated per PRD changes - simplified image compression)
**Complexity:** High
**Estimated Total Effort:** 40-48 hours (reduced - simpler image handling)

---

## Executive Summary

This plan details the technical implementation of inline attachment upload during accounting line creation and automatic file compression for the GVV application. The implementation is divided into four phases with clear deliverables, testing requirements, and rollback procedures.

**Key Implementation Approach (Updated per latest PRD):**
- **Three-track pure PHP compression** - keeping files in original formats
  - **Compressible Images (JPEG, PNG, GIF, WebP)**: Resize with GD library + recompress in original format (JPEG stays JPEG, PNG stays PNG)
  - **PDFs**: Use Ghostscript to reduce resolution to /ebook (150 DPI) + keep as PDF
  - **Other files (DOCX, CSV, TXT, etc.)**: Standard gzip compression only (stored as .gz)
- **Required PHP extensions**: `gd` (for images) and `zlib` (for gzip - both already available on production)
- **External tool needed**: Ghostscript for PDF optimization (commonly available on Linux servers)
- **Smartphone photo optimization**: 3-8MB photos automatically resized to 500KB-1MB
- **Browser-friendly viewing**: Images and PDFs viewable directly in browser (PRD CA1.7) - no decompression needed
- **Transparent decompression**: Only applies to gzipped files (.gz); images and PDFs served directly

---

## Table of Contents

1. [Technical Approach Overview](#1-technical-approach-overview)
2. [Phase 1: Inline Attachment Upload](#2-phase-1-inline-attachment-upload)
3. [Phase 2: Automatic File Compression](#3-phase-2-automatic-file-compression)
4. [Phase 3: Transparent Decompression](#4-phase-3-transparent-decompression)
5. [Phase 4: Batch Compression Script](#5-phase-4-batch-compression-script)
6. [Testing Strategy](#6-testing-strategy)
7. [Migration and Deployment](#7-migration-and-deployment)
8. [Configuration Reference](#8-configuration-reference)
9. [Monitoring and Maintenance](#9-monitoring-and-maintenance)

---

## 1. Technical Approach Overview

### 1.1 Architecture Decisions

**Inline Attachment Upload:**
- **Approach:** Session-based temporary storage (Option A from PRD)
- **Rationale:**
  - Simple to implement in CodeIgniter 2.x
  - No database schema changes required
  - Natural integration with existing form validation/error handling
  - Session cleanup can handle abandoned uploads

**File Compression:**
- **Approach:** Three-track compression strategy keeping files in original formats
  - **Compressible Images (JPEG, PNG, GIF, WebP):** Resize with GD + recompress in original format (no format conversion)
  - **PDFs:** Use Ghostscript with /ebook settings (150 DPI) + keep as PDF
  - **Other files (DOCX, CSV, TXT, etc.):** Standard gzip compression only (stored as .ext.gz)
- **Rationale:**
  - Keep files in browser-viewable formats (images stay as images, PDFs stay as PDFs)
  - No decompression needed for images and PDFs - direct viewing in browser (PRD CA1.7)
  - Original formats maintain compatibility with all viewing tools
  - Ghostscript /ebook provides good compression while maintaining readability
  - Synchronous compression for immediate feedback
  - Simpler decompression logic (only for .gz files)
  - Can move to async later if performance issues arise

**Batch Compression:**
- **Approach:** CLI script with resume capability
- **Rationale:**
  - Administrator control over timing
  - Can monitor progress in real-time
  - Easy to test with dry-run mode
  - No complex job queue needed

### 1.2 File Flow Diagrams

**Inline Attachment Flow:**
```
User uploads file → Temp storage (session-based) → Form submission →
  ├─ Success: Move to permanent storage + create DB records
  └─ Failure: Keep in temp, redisplay form
```

**Compression Flow:**
```
File received → Analyze type →
  ├─ Should compress? → Compress → Validate →
  │                      ├─ Success: Store compressed, log ratio
  │                      └─ Failure: Store original, log error
  └─ Skip compression → Store original
```

---

## 2. Phase 1: Inline Attachment Upload

**Priority:** HIGH
**Estimated Effort:** 12-16 hours
**Dependencies:** None

### 2.1 Components to Modify

#### 2.1.1 Controller: `application/controllers/compta.php`

**Location:** Lines 46-96 (existing attachment integration)

**Changes:**

1. **Add file upload handler to `create()` method:**

```php
function create() {
    $table = $this->gvv_model->table();
    $this->data = $this->gvvmetadata->defaults_list($table);

    // Handle pending attachment uploads from session
    $session_id = $this->session->userdata('session_id');
    $pending_files = $this->session->userdata('pending_attachments_' . $session_id);
    if ($pending_files) {
        $this->data['pending_attachments'] = $pending_files;
    }

    $this->form_static_element(CREATION);
    return load_last_view($this->form_view, $this->data, $this->unit_test);
}
```

2. **Add AJAX upload handler method:**

```php
/**
 * Handle attachment upload during creation (AJAX)
 * Returns JSON response with temp file info
 */
public function upload_temp_attachment() {
    if (!$this->input->is_ajax_request()) {
        show_404();
        return;
    }

    $session_id = $this->session->userdata('session_id');
    $year = date('Y');
    $club_id = $this->session->userdata('section');
    $section_name = $this->sections_model->image($club_id);

    if (empty($section_name)) {
        $section_name = 'Unknown';
    }
    $section_name = str_replace(' ', '_', $section_name);

    // Create temp directory
    $temp_dir = './uploads/attachments/temp/' . $session_id . '/';
    if (!file_exists($temp_dir)) {
        mkdir($temp_dir, 0777, true);
        chmod($temp_dir, 0777);
    }

    // Generate unique filename
    $storage_file = rand(100000, 999999) . '_' . str_replace(' ', '_', $_FILES['file']['name']);

    // Upload file
    $config['upload_path'] = $temp_dir;
    $config['allowed_types'] = '*';
    $config['max_size'] = '20000';
    $config['file_name'] = $storage_file;

    $this->load->library('upload', $config);

    if (!$this->upload->do_upload('file')) {
        // Error
        $response = [
            'success' => false,
            'error' => $this->upload->display_errors('', '')
        ];
    } else {
        // Success - store in session
        $upload_data = $this->upload->data();

        $file_info = [
            'temp_id' => uniqid(),
            'temp_path' => $temp_dir . $storage_file,
            'original_name' => $_FILES['file']['name'],
            'storage_name' => $storage_file,
            'size' => $upload_data['file_size'] * 1024, // Convert KB to bytes
            'club' => $club_id,
            'section_name' => $section_name
        ];

        // Add to session
        $pending_key = 'pending_attachments_' . $session_id;
        $pending = $this->session->userdata($pending_key) ?: [];
        $pending[$file_info['temp_id']] = $file_info;
        $this->session->set_userdata($pending_key, $pending);

        $response = [
            'success' => true,
            'file' => $file_info
        ];
    }

    $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($response));
}
```

3. **Add attachment removal handler:**

```php
/**
 * Remove temp attachment (AJAX)
 */
public function remove_temp_attachment() {
    if (!$this->input->is_ajax_request()) {
        show_404();
        return;
    }

    $temp_id = $this->input->post('temp_id');
    $session_id = $this->session->userdata('session_id');
    $pending_key = 'pending_attachments_' . $session_id;
    $pending = $this->session->userdata($pending_key) ?: [];

    if (isset($pending[$temp_id])) {
        // Delete file
        $file_path = $pending[$temp_id]['temp_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        // Remove from session
        unset($pending[$temp_id]);
        $this->session->set_userdata($pending_key, $pending);

        $response = ['success' => true];
    } else {
        $response = ['success' => false, 'error' => 'File not found'];
    }

    $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($response));
}
```

4. **Modify `formValidation()` to process pending attachments:**

```php
public function formValidation($action, $return_on_success = false) {
    // ... existing validation code ...

    // If validation successful and record created
    if ($validation_passed && $new_id) {
        // Process pending attachments
        $session_id = $this->session->userdata('session_id');
        $this->process_pending_attachments('ecritures', $new_id, $session_id);
    }

    // ... rest of existing code ...
}
```

5. **Add helper function:**

```php
/**
 * Process pending attachments after successful record creation
 *
 * @param string $referenced_table Table name (e.g., 'ecritures')
 * @param int $referenced_id ID of the created record
 * @param string $session_id Current session ID
 * @return int Number of attachments processed
 */
private function process_pending_attachments($referenced_table, $referenced_id, $session_id) {
    $pending_key = 'pending_attachments_' . $session_id;
    $pending = $this->session->userdata($pending_key);

    if (empty($pending)) {
        return 0;
    }

    $processed = 0;
    $year = date('Y');

    foreach ($pending as $temp_id => $file_info) {
        $temp_path = $file_info['temp_path'];

        if (!file_exists($temp_path)) {
            gvv_error("Pending attachment file not found: $temp_path");
            continue;
        }

        // Build permanent path
        $section_name = $file_info['section_name'];
        $permanent_dir = './uploads/attachments/' . $year . '/' . $section_name . '/';

        if (!file_exists($permanent_dir)) {
            mkdir($permanent_dir, 0777, true);
            chmod($permanent_dir, 0777);
        }

        $storage_name = $file_info['storage_name'];
        $permanent_path = $permanent_dir . $storage_name;

        // Move file from temp to permanent
        if (rename($temp_path, $permanent_path)) {
            // Create attachment database record
            $attachment_data = [
                'referenced_table' => $referenced_table,
                'referenced_id' => $referenced_id,
                'user_id' => $this->dx_auth->get_username(),
                'filename' => $file_info['original_name'],
                'description' => '', // User can edit later
                'file' => $permanent_path,
                'club' => $file_info['club']
            ];

            $this->attachments_model->insert($attachment_data);
            $processed++;

            gvv_info("Processed pending attachment: $storage_name → $permanent_path");
        } else {
            gvv_error("Failed to move pending attachment: $temp_path → $permanent_path");
        }
    }

    // Clear session data
    $this->session->unset_userdata($pending_key);

    // Clean up temp directory
    $temp_dir = './uploads/attachments/temp/' . $session_id . '/';
    if (is_dir($temp_dir)) {
        rmdir($temp_dir);
    }

    return $processed;
}
```

#### 2.1.2 View: `application/views/compta/bs_formView.php`

**Location:** Near line 83 (after existing attachment section)

**Changes:**

1. **Add attachment upload widget in creation mode:**

```php
<?php if ($action == CREATION): ?>
    <!-- Inline Attachment Upload (for creation) -->
    <div class="form-group">
        <label><?= $this->lang->line("gvv_attachments_title") ?> (<?= $this->lang->line("gvv_optional") ?>)</label>
        <div class="attachment-upload-area" id="attachmentDropZone">
            <input type="file" name="attachment_files[]" id="fileInput" multiple
                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.csv,.txt"
                   style="display:none;">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('fileInput').click();">
                <i class="bi bi-paperclip"></i> <?= $this->lang->line("gvv_choose_files") ?>
            </button>
            <small class="form-text text-muted">
                <?= $this->lang->line("gvv_supported_formats") ?>: PDF, Images, Office, CSV (Max 20MB)
            </small>
        </div>
        <div id="fileList" class="file-list mt-2">
            <!-- JavaScript will populate uploaded files here -->
        </div>
    </div>
<?php endif; ?>

<?php if ($action == MODIFICATION || $action == VISUALISATION): ?>
    <!-- Existing attachment display for edit mode -->
    <h3><?= translation("gvv_attachments_title") ?></h3>
    <?php
    $attrs = array(
        'controller' => "attachments",
        'referenced_table' => 'ecritures',
        'referenced_id' => $id
    );
    echo $this->gvvmetadata->table("vue_attachments", $attrs, "");
    ?>
<?php endif; ?>
```

2. **Add JavaScript for file upload handling:**

```javascript
<script>
$(document).ready(function() {
    var uploadedFiles = {};

    // Handle file selection
    $('#fileInput').on('change', function(e) {
        var files = e.target.files;
        for (var i = 0; i < files.length; i++) {
            uploadFile(files[i]);
        }
        // Clear input so same file can be re-uploaded if needed
        $(this).val('');
    });

    // Upload file via AJAX
    function uploadFile(file) {
        var formData = new FormData();
        formData.append('file', file);

        // Add file to UI immediately with loading state
        var tempId = 'uploading_' + Date.now();
        addFileToList(tempId, file.name, 0, true);

        $.ajax({
            url: '<?= base_url() ?>index.php/compta/upload_temp_attachment',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update UI with actual temp_id
                    updateFileInList(tempId, response.file);
                    uploadedFiles[response.file.temp_id] = response.file;
                } else {
                    // Show error
                    removeFileFromList(tempId);
                    alert('Upload failed: ' + response.error);
                }
            },
            error: function(xhr, status, error) {
                removeFileFromList(tempId);
                alert('Upload failed: ' + error);
            }
        });
    }

    // Add file to list UI
    function addFileToList(tempId, filename, size, isLoading) {
        var sizeStr = size > 0 ? ' (' + formatBytes(size) + ')' : '';
        var loadingClass = isLoading ? 'file-item-loading' : '';
        var removeBtn = isLoading ? '' :
            '<button type="button" class="btn btn-sm btn-danger" onclick="removeFile(\'' + tempId + '\')">
                <i class="bi bi-trash"></i>
            </button>';

        var html = '<div class="file-item ' + loadingClass + '" id="file_' + tempId + '">' +
                   '<i class="bi bi-file-earmark"></i> ' +
                   '<span class="filename">' + filename + '</span>' +
                   '<span class="filesize">' + sizeStr + '</span> ' +
                   removeBtn +
                   '</div>';

        $('#fileList').append(html);
    }

    // Update file in list after upload completes
    function updateFileInList(oldId, fileInfo) {
        var $item = $('#file_' + oldId);
        $item.attr('id', 'file_' + fileInfo.temp_id);
        $item.removeClass('file-item-loading');
        $item.find('.filesize').text(' (' + formatBytes(fileInfo.size) + ')');
        $item.append(
            '<button type="button" class="btn btn-sm btn-danger" onclick="removeFile(\'' + fileInfo.temp_id + '\')">
                <i class="bi bi-trash"></i>
            </button>'
        );
    }

    // Remove file from list
    function removeFileFromList(tempId) {
        $('#file_' + tempId).remove();
    }

    // Format bytes for display
    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    // Global function for remove button
    window.removeFile = function(tempId) {
        if (!confirm('<?= $this->lang->line("gvv_confirm_remove_file") ?>')) {
            return;
        }

        $.ajax({
            url: '<?= base_url() ?>index.php/compta/remove_temp_attachment',
            type: 'POST',
            data: { temp_id: tempId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    removeFileFromList(tempId);
                    delete uploadedFiles[tempId];
                } else {
                    alert('Failed to remove file: ' + response.error);
                }
            },
            error: function() {
                alert('Failed to remove file');
            }
        });
    };
});
</script>

<style>
.file-list {
    margin-top: 10px;
}
.file-item {
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.file-item-loading {
    opacity: 0.6;
    background-color: #f8f9fa;
}
.file-item .filename {
    flex-grow: 1;
    font-weight: 500;
}
.file-item .filesize {
    color: #6c757d;
    font-size: 0.875rem;
}
</style>
```

#### 2.1.3 Cleanup Script: `scripts/cleanup_temp_attachments.php`

**Purpose:** Cron job to delete abandoned temporary files

```php
<?php
/**
 * Cleanup script for temporary attachment files
 *
 * Run daily via cron: 0 2 * * * php /path/to/scripts/cleanup_temp_attachments.php
 *
 * Deletes temporary attachment files older than 24 hours
 */

// Bootstrap CodeIgniter
define('BASEPATH', TRUE);
$_SERVER['REQUEST_URI'] = '/scripts/cleanup';
require_once __DIR__ . '/../index.php';

$temp_dir = './uploads/attachments/temp/';
$max_age = 86400; // 24 hours in seconds
$now = time();

$deleted_count = 0;
$deleted_size = 0;
$error_count = 0;

echo "=== Temporary Attachment Cleanup ===\n";
echo "Started: " . date('Y-m-d H:i:s') . "\n";
echo "Max age: " . ($max_age / 3600) . " hours\n\n";

if (!is_dir($temp_dir)) {
    echo "Temp directory not found: $temp_dir\n";
    exit(0);
}

// Iterate through session directories
$session_dirs = glob($temp_dir . '*', GLOB_ONLYDIR);

foreach ($session_dirs as $session_dir) {
    $session_id = basename($session_dir);

    // Check age of directory
    $dir_mtime = filemtime($session_dir);
    $age = $now - $dir_mtime;

    if ($age > $max_age) {
        echo "Processing session: $session_id (age: " . round($age / 3600, 1) . "h)\n";

        // Delete files in directory
        $files = glob($session_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                $size = filesize($file);
                if (unlink($file)) {
                    $deleted_count++;
                    $deleted_size += $size;
                    echo "  Deleted: " . basename($file) . " (" . round($size / 1024, 1) . " KB)\n";
                } else {
                    $error_count++;
                    echo "  ERROR: Could not delete " . basename($file) . "\n";
                }
            }
        }

        // Remove empty directory
        if (rmdir($session_dir)) {
            echo "  Removed session directory\n";
        } else {
            echo "  WARNING: Could not remove session directory\n";
        }
    }
}

echo "\n=== Summary ===\n";
echo "Files deleted: $deleted_count\n";
echo "Space freed: " . round($deleted_size / (1024 * 1024), 2) . " MB\n";
echo "Errors: $error_count\n";
echo "Completed: " . date('Y-m-d H:i:s') . "\n";

exit($error_count > 0 ? 1 : 0);
```

#### 2.1.4 Language Files

**Files to update:**
- `application/language/french/compta_lang.php`
- `application/language/english/compta_lang.php`
- `application/language/dutch/compta_lang.php`

**Add translations:**

```php
// French
$lang['gvv_choose_files'] = "Choisir des fichiers";
$lang['gvv_optional'] = "facultatif";
$lang['gvv_supported_formats'] = "Formats supportés";
$lang['gvv_confirm_remove_file'] = "Êtes-vous sûr de vouloir supprimer ce fichier ?";

// English
$lang['gvv_choose_files'] = "Choose Files";
$lang['gvv_optional'] = "optional";
$lang['gvv_supported_formats'] = "Supported formats";
$lang['gvv_confirm_remove_file'] = "Are you sure you want to remove this file?";

// Dutch
$lang['gvv_choose_files'] = "Bestanden kiezen";
$lang['gvv_optional'] = "optioneel";
$lang['gvv_supported_formats'] = "Ondersteunde formaten";
$lang['gvv_confirm_remove_file'] = "Weet u zeker dat u dit bestand wilt verwijderen?";
```

### 2.2 Configuration

**File:** `application/config/attachments.php` (create new file)

```php
<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Attachments Configuration
 */

// Temporary upload settings
$config['temp_upload_path'] = './uploads/attachments/temp/';
$config['temp_file_lifetime'] = 86400; // 24 hours
$config['max_pending_files_per_session'] = 20;
$config['max_temp_storage_mb'] = 500;

// Upload settings
$config['upload_max_size'] = 20480; // 20MB in KB
$config['allowed_file_types'] = 'pdf|jpg|jpeg|png|gif|doc|docx|xls|xlsx|csv|txt';

/* End of file attachments.php */
/* Location: ./application/config/attachments.php */
```

### 2.3 Testing Checklist

- [ ] Upload single file during accounting line creation
- [ ] Upload multiple files during accounting line creation
- [ ] Remove file before submission
- [ ] Submit form with attachments successfully
- [ ] Submit form with validation error (files retained)
- [ ] Submit form without attachments (no errors)
- [ ] Verify files moved from temp to permanent storage
- [ ] Verify attachment database records created correctly
- [ ] Test with large files (near 20MB limit)
- [ ] Test with unsupported file types (should be rejected)
- [ ] Test temp file cleanup script
- [ ] Test session expiry handling
- [ ] Test concurrent uploads from different users

---

## 3. Phase 2: Automatic File Compression

**Priority:** HIGH
**Estimated Effort:** 14-16 hours
**Dependencies:** None (can run parallel to Phase 1)

**Approach:** Three-track compression strategy keeping original formats:
- **Compressible Images** (JPEG, PNG, GIF, WebP): Resize with GD + recompress in original format
- **PDFs**: Ghostscript /ebook (150 DPI) + keep as PDF
- **Other files** (DOCX, CSV, TXT, etc.): Standard gzip compression only (stored as .gz)

**Required PHP Extensions:**
- `gd` - Image manipulation (already available on production)
- `zlib` - Gzip compression/decompression for non-image, non-PDF files (already available on production)

**Required External Tools:**
- `ghostscript` (gs command) - PDF optimization (commonly available on Linux servers)

### 3.1 Compression Library

**File:** `application/libraries/File_compressor.php`

```php
<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * File Compressor Library
 *
 * PRD Section 5.2 AC2.2: Three-track compression strategy:
 * - Compressible Images (JPEG, PNG, GIF, WebP): Resize with GD + recompress in original format
 * - PDFs: Ghostscript /ebook (150 DPI) + keep as PDF
 * - Other files (DOCX, CSV, TXT, etc.): Standard gzip compression only
 *
 * PRD Section 9.1: Requires PHP gd and zlib extensions + Ghostscript
 * - gd: Image manipulation (resize, recompress in original format)
 * - zlib: Gzip compression/decompression (for non-image, non-PDF files only)
 * - ghostscript (gs): PDF optimization
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

        // Route to appropriate compression method
        if ($this->is_image($extension)) {
            $result = $this->compress_image($file_path, $options);
        } elseif ($extension === 'pdf') {
            $result = $this->compress_pdf($file_path, $options);
        } else {
            $result = $this->compress_gzip($file_path, $options);
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
            case IMAGETYPE_BMP:
                $source = imagecreatefrombmp($file_path);
                break;
            default:
                return ['success' => false, 'error' => 'Unsupported image type'];
        }

        if ($source === false) {
            return ['success' => false, 'error' => 'Failed to load image'];
        }

        // Create resized image
        $destination = imagecreatetruecolor($new_width, $new_height);

        // Preserve transparency for PNG (though we'll save it back as PNG)
        if ($type == IMAGETYPE_PNG) {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
            $transparent = imagecolorallocatealpha($destination, 0, 0, 0, 127);
            imagefill($destination, 0, 0, $transparent);
        }

        imagecopyresampled($destination, $source, 0, 0, 0, 0,
                          $new_width, $new_height, $width, $height);

        // Save in ORIGINAL format (PRD: keep images in original format)
        $output_path = $file_path; // Overwrite original

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $success = imagejpeg($destination, $output_path, $quality);
                $this->stats['method'] = 'gd/resize+jpeg';
                break;
            case 'png':
                // PNG quality is 0-9 (compression level), convert from 0-100 scale
                $png_quality = 9 - round(($quality / 100) * 9);
                $success = imagepng($destination, $output_path, $png_quality);
                $this->stats['method'] = 'gd/resize+png';
                break;
            case 'gif':
                $success = imagegif($destination, $output_path);
                $this->stats['method'] = 'gd/resize+gif';
                break;
            case 'webp':
                $success = imagewebp($destination, $output_path, $quality);
                $this->stats['method'] = 'gd/resize+webp';
                break;
            default:
                imagedestroy($source);
                imagedestroy($destination);
                return ['success' => false, 'error' => 'Unsupported image format for saving'];
        }

        imagedestroy($source);
        imagedestroy($destination);

        if (!$success) {
            return ['success' => false, 'error' => 'Failed to save compressed image'];
        }

        return ['success' => true, 'compressed_path' => $output_path];
    }

    /**
     * Compress PDF file using Ghostscript
     * - Reduce resolution to /ebook (150 DPI)
     * - Keep as PDF format
     *
     * @param string $file_path Path to PDF file
     * @param array $options Compression options
     * @return array ['success' => bool, 'compressed_path' => string, 'error' => string]
     */
    private function compress_pdf($file_path, $options = []) {
        // Check if Ghostscript is available
        $gs_path = $this->get_config('ghostscript_path', 'gs');
        exec("which $gs_path 2>&1", $output, $return_var);
        if ($return_var !== 0) {
            return ['success' => false, 'error' => 'Ghostscript not available'];
        }

        // Create temporary output file
        $temp_output = $file_path . '.tmp.pdf';

        // PRD AC2.2: Use Ghostscript /ebook settings (150 DPI)
        $quality = $options['pdf_quality'] ?? $this->get_config('pdf_quality', 'ebook');

        // Build Ghostscript command
        $command = sprintf(
            '%s -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/%s ' .
            '-dNOPAUSE -dQUIET -dBATCH -sOutputFile=%s %s 2>&1',
            escapeshellcmd($gs_path),
            escapeshellarg($quality),
            escapeshellarg($temp_output),
            escapeshellarg($file_path)
        );

        // Execute Ghostscript
        exec($command, $exec_output, $return_var);

        if ($return_var !== 0 || !file_exists($temp_output)) {
            // Cleanup
            if (file_exists($temp_output)) {
                unlink($temp_output);
            }
            return ['success' => false, 'error' => 'Ghostscript compression failed: ' . implode("\n", $exec_output)];
        }

        // Replace original with compressed
        if (!rename($temp_output, $file_path)) {
            unlink($temp_output);
            return ['success' => false, 'error' => 'Failed to replace original PDF'];
        }

        $this->stats['method'] = 'ghostscript/ebook';
        return ['success' => true, 'compressed_path' => $file_path];
    }

    /**
     * Compress file using gzip only
     * PRD AC2.2: All non-image, non-PDF files compressed with gzip level 9
     *
     * @param string $file_path Path to file to compress
     * @param array $options Compression options
     * @return array ['success' => bool, 'compressed_path' => string, 'error' => string]
     */
    private function compress_gzip($file_path, $options = []) {
        $output_path = $file_path . '.gz';

        // PRD AC2.2: Use gzip compression level 9 (maximum)
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
     * Check if file is an image
     *
     * @param string $extension File extension (lowercase)
     * @return bool True if image
     */
    private function is_image($extension) {
        $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
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

        if ($this->stats['method'] === 'gd+jpeg') {
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
        } else {
            // Standard file compression log
            $message = sprintf(
                "Attachment compression: file=%s, original=%.2fMB, compressed=%.2fMB, ratio=%d%%, method=%s",
                basename($original_path),
                $original_size_mb,
                $compressed_size_mb,
                $ratio_percent,
                $this->stats['method']
            );
        }

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
```

### 3.2 Integration with Upload

**Modify:** `application/controllers/attachments.php` (line ~148)

```php
// After successful upload
if ($this->upload->do_upload("userfile")) {
    $upload_data = array('upload_data' => $this->upload->data());
    $file_path = $dirname . $storage_file;
    $_POST['file'] = $file_path;

    // Attempt compression
    $this->load->library('file_compressor');
    $compression_result = $this->file_compressor->compress($file_path);

    if ($compression_result['success']) {
        // Use compressed file
        $compressed_path = $compression_result['compressed_path'];
        $_POST['file'] = $compressed_path;

        // Delete original
        if (file_exists($file_path) && $file_path !== $compressed_path) {
            unlink($file_path);
        }

        gvv_info("File compressed successfully: " . basename($compressed_path));
    } else {
        // Use original file
        gvv_info("Compression skipped: " . $compression_result['error']);
    }

    // Continue with existing code...
    parent::formValidation($action);
}
```

### 3.3 Configuration

**Add to:** `application/config/attachments.php`

```php
// Compression settings (PRD Section 5.2 AC2.2)
$config['compression'] = [
    'enabled' => TRUE,
    'min_size' => 102400, // 100KB - don't compress smaller files (PRD AC2.3)
    'min_ratio' => 0.10, // Only keep compressed if >10% savings (PRD AC2.3)

    // Image compression (PRD AC2.2: Resize + JPEG + gzip)
    'image_max_width' => 1600,  // PRD AC2.6: suitable for printing (300 DPI at A4)
    'image_max_height' => 1200,
    'image_quality' => 85, // JPEG quality (0-100)

    // Gzip compression level for all files
    'gzip_level' => 9, // Maximum compression (PRD AC2.2)

    // Safety
    'preserve_original_until_verified' => TRUE,
];
```

### 3.4 Testing Checklist

- [ ] Test image compression (JPEG, PNG, GIF, WebP) - resize + recompress in original format
- [ ] Test smartphone photo optimization (PRD AC2.7: 3-8MB → 500KB-1MB)
- [ ] Verify images are stored in original formats (.jpg, .png, .gif, .webp - not .gz)
- [ ] Verify images can be viewed directly in browser (PRD CA1.7)
- [ ] Test PDF compression - Ghostscript /ebook (stored as .pdf, not .pdf.gz)
- [ ] Test CSV/TXT compression - gzip only
- [ ] Test DOC/DOCX compression - gzip only
- [ ] Verify compression ratios logged correctly (PRD AC2.4)
- [ ] Verify log format includes dimensions for images (method=gd/resize+jpeg or gd/resize+png, etc.)
- [ ] Test files below minimum size 100KB (not compressed per PRD AC2.3)
- [ ] Test files with low compression ratio <10% (original kept per PRD AC2.3)
- [ ] Test compression failure handling (fallback to original)
- [ ] Verify original file deleted after successful compression
- [ ] Test with various image sizes and resolutions
- [ ] Verify image quality suitable for printing (PRD AC2.6)
- [ ] Test already compressed formats (ZIP, RAR) - should skip
- [ ] **Critical:** Verify treasurers can view previously uploaded (uncompressed) attachments

---

## 4. Phase 3: Transparent Decompression

**Priority:** MEDIUM (lower priority since images and PDFs don't need decompression)
**Estimated Effort:** 3-4 hours (significantly reduced - only gzipped files need decompression)
**Dependencies:** Phase 2

**Note:** This phase handles decompression of gzipped files (.gz extensions - documents, text files, etc.). Images are stored in their original compressed formats (JPEG, PNG, GIF, WebP) and PDFs remain as PDF files, both viewable directly in the browser without decompression (PRD CA1.7), significantly simplifying this phase.

### 4.1 Download Handler

**Modify:** `application/controllers/attachments.php`

**Add new method:**

```php
/**
 * Download attachment with automatic decompression
 *
 * @param int $id Attachment ID
 */
public function download($id) {
    // Get attachment record
    $attachment = $this->gvv_model->get_by_id('id', $id);

    if (empty($attachment)) {
        show_404();
        return;
    }

    $file_path = $attachment['file'];
    $original_name = $attachment['filename'];

    if (!file_exists($file_path)) {
        show_error('File not found');
        return;
    }

    // PRD AC3.10 & CA1.7: Treasurers can view/download attachments seamlessly
    // - Images are stored in original formats (JPEG, PNG, GIF, WebP) and viewed directly in browser
    // - PDFs are stored as .pdf and viewed directly in browser
    // - Only documents/text files stored as .gz need decompression
    // - Old uncompressed files still work

    // Check if file is gzip compressed
    if (preg_match('/\.gz$/', $file_path)) {
        // Decompress to temp file (for documents, text files, etc.)
        $temp_file = $this->decompress_to_temp($file_path);

        if ($temp_file === false) {
            show_error('Failed to decompress file');
            return;
        }

        // Serve temp file (restore original filename without .gz extension)
        $original_name_no_gz = preg_replace('/\.gz$/', '', $original_name);
        $this->serve_file($temp_file, $original_name_no_gz, true);
    } else {
        // Serve directly - images (JPEG, PNG, GIF, WebP), PDFs, old uncompressed files
        $this->serve_file($file_path, $original_name, false);
    }
}

/**
 * Decompress gzipped file to temporary location
 *
 * @param string $gz_file_path Path to .gz file
 * @return string|false Path to temp file or false on failure
 */
private function decompress_to_temp($gz_file_path) {
    $temp_file = tempnam(sys_get_temp_dir(), 'gvv_attachment_');

    $gz_handle = gzopen($gz_file_path, 'rb');
    if ($gz_handle === false) {
        return false;
    }

    $out_handle = fopen($temp_file, 'wb');
    if ($out_handle === false) {
        gzclose($gz_handle);
        return false;
    }

    while (!gzeof($gz_handle)) {
        $chunk = gzread($gz_handle, 4096);
        if ($chunk === false) {
            gzclose($gz_handle);
            fclose($out_handle);
            unlink($temp_file);
            return false;
        }
        fwrite($out_handle, $chunk);
    }

    gzclose($gz_handle);
    fclose($out_handle);

    return $temp_file;
}

/**
 * Serve file to browser
 *
 * @param string $file_path Path to file
 * @param string $download_name Name for download
 * @param bool $is_temp Whether file is temporary (delete after serving)
 */
private function serve_file($file_path, $download_name, $is_temp = false) {
    // Determine MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file_path);
    finfo_close($finfo);

    if ($mime_type === false) {
        $mime_type = 'application/octet-stream';
    }

    // Set headers
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . $download_name . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: public');

    // Output file
    readfile($file_path);

    // Delete temp file if needed
    if ($is_temp && file_exists($file_path)) {
        unlink($file_path);
    }

    exit;
}
```

### 4.2 Update Attachment Links

**Modify:** `application/helpers/MY_html_helper.php`

**Update `attachment()` function:**

```php
function attachment($id, $filename, $url = "") {
    // ... existing code ...

    // Change direct file link to download handler
    $download_url = base_url() . 'index.php/attachments/download/' . $id;

    // Rest of function using $download_url instead of direct file path
    // ... existing code ...
}
```

### 4.3 Testing Checklist

- [ ] Download uncompressed file (legacy/old attachments)
- [ ] Download gzip compressed file (.csv.gz, .txt.gz, .docx.gz)
- [ ] View/download optimized PDFs directly in browser (.pdf) - no decompression needed (PRD CA1.7)
- [ ] View/download images directly in browser (.jpg, .png, .gif, .webp) - no decompression needed (PRD CA1.7)
- [ ] Verify images display correctly in browser without download
- [ ] Verify correct MIME types for all file types
- [ ] Verify correct filenames (original name, .gz extension removed for compressed files)
- [ ] Test temp file cleanup after serving decompressed files
- [ ] Test with missing files
- [ ] Test with corrupted compressed files
- [ ] Performance test (large files, especially decompression)
- [ ] **Critical:** Test treasurer workflow - ensure seamless access to both old (uncompressed) and new (compressed/optimized) attachments

---

## 5. Phase 4: Batch Compression Script

**Priority:** MEDIUM
**Estimated Effort:** 10-12 hours
**Dependencies:** Phase 3 (Transparent Decompression)

**Note:** This phase comes after transparent decompression to ensure that treasurers can access both old (uncompressed) and newly compressed attachments before running batch compression on historical files.

### 5.1 Script Implementation

**File:** `scripts/batch_compress_attachments.php`

```php
<?php
/**
 * Batch Compression Script for Existing Attachments
 *
 * Usage: php scripts/batch_compress_attachments.php [options]
 *
 * Options:
 *   --dry-run         Preview changes without actually compressing
 *   --verbose         Show detailed progress
 *   --year=YYYY       Only compress attachments from specific year
 *   --section=NAME    Only compress attachments from specific section
 *   --type=TYPE       Only compress specific file type (pdf, image, text)
 *   --min-size=SIZE   Only compress files larger than SIZE (e.g., 100KB, 1MB)
 *   --limit=N         Limit to first N files (for testing)
 *   --resume          Resume from last interruption
 *
 * Examples:
 *   php scripts/batch_compress_attachments.php --dry-run --verbose
 *   php scripts/batch_compress_attachments.php --year=2024 --min-size=100KB
 *   php scripts/batch_compress_attachments.php --type=pdf --limit=10
 */

// Parse command line arguments
$options = [
    'dry_run' => false,
    'verbose' => false,
    'year' => null,
    'section' => null,
    'type' => null,
    'min_size' => null,
    'limit' => null,
    'resume' => false
];

foreach ($argv as $arg) {
    if ($arg === '--dry-run') $options['dry_run'] = true;
    if ($arg === '--verbose') $options['verbose'] = true;
    if ($arg === '--resume') $options['resume'] = true;
    if (preg_match('/--year=(\d{4})/', $arg, $m)) $options['year'] = $m[1];
    if (preg_match('/--section=(.+)/', $arg, $m)) $options['section'] = $m[1];
    if (preg_match('/--type=(.+)/', $arg, $m)) $options['type'] = $m[1];
    if (preg_match('/--min-size=(.+)/', $arg, $m)) $options['min_size'] = parse_size($m[1]);
    if (preg_match('/--limit=(\d+)/', $arg, $m)) $options['limit'] = (int)$m[1];
}

// Bootstrap CodeIgniter
define('BASEPATH', TRUE);
$_SERVER['REQUEST_URI'] = '/scripts/batch_compress';
require_once __DIR__ . '/../index.php';

// Run batch compression
$compressor = new Batch_Attachment_Compressor($options);
$success = $compressor->run();

exit($success ? 0 : 1);

/**
 * Parse size string (e.g., "100KB", "1MB") to bytes
 */
function parse_size($size_str) {
    $size_str = strtoupper(trim($size_str));
    $multipliers = ['B' => 1, 'KB' => 1024, 'MB' => 1024 * 1024, 'GB' => 1024 * 1024 * 1024];

    foreach ($multipliers as $unit => $multiplier) {
        if (substr($size_str, -strlen($unit)) === $unit) {
            $value = (float)substr($size_str, 0, -strlen($unit));
            return (int)($value * $multiplier);
        }
    }

    return (int)$size_str; // Assume bytes
}

/**
 * Batch Attachment Compressor Class
 */
class Batch_Attachment_Compressor {

    private $CI;
    private $options;
    private $stats = [
        'total' => 0,
        'processed' => 0,
        'compressed' => 0,
        'skipped' => 0,
        'errors' => 0,
        'original_size' => 0,
        'compressed_size' => 0
    ];
    private $start_time;
    private $progress_file = './application/logs/batch_compression_progress.json';

    public function __construct($options) {
        $this->CI =& get_instance();
        $this->CI->load->model('attachments_model');
        $this->CI->load->library('file_compressor');
        $this->options = $options;
        $this->start_time = time();
    }

    public function run() {
        echo "=== Batch Attachment Compression ===\n";
        echo "Mode: " . ($this->options['dry_run'] ? "DRY RUN" : "LIVE") . "\n";
        echo "Started: " . date('Y-m-d H:i:s') . "\n";

        // Show filters
        $filters = [];
        if ($this->options['year']) $filters[] = "year=" . $this->options['year'];
        if ($this->options['section']) $filters[] = "section=" . $this->options['section'];
        if ($this->options['type']) $filters[] = "type=" . $this->options['type'];
        if ($this->options['min_size']) $filters[] = "min_size=" . $this->format_bytes($this->options['min_size']);
        if ($this->options['limit']) $filters[] = "limit=" . $this->options['limit'];

        if (!empty($filters)) {
            echo "Filters: " . implode(', ', $filters) . "\n";
        }
        echo "\n";

        // Load progress if resuming
        $processed_ids = [];
        if ($this->options['resume'] && file_exists($this->progress_file)) {
            $progress = json_decode(file_get_contents($this->progress_file), true);
            $processed_ids = $progress['processed_ids'] ?? [];
            echo "Resuming from previous run (" . count($processed_ids) . " files already processed)\n\n";
        }

        // Get attachments to compress
        echo "Analyzing attachments...\n";
        $attachments = $this->get_attachments($processed_ids);
        $this->stats['total'] = count($attachments);

        if ($this->stats['total'] === 0) {
            echo "No attachments found matching criteria.\n";
            return true;
        }

        // Calculate total size
        foreach ($attachments as $att) {
            if (file_exists($att['file'])) {
                $this->stats['original_size'] += filesize($att['file']);
            }
        }

        echo "Found {$this->stats['total']} attachments to process ";
        echo "(" . $this->format_bytes($this->stats['original_size']) . " total)\n\n";

        // Process each attachment
        foreach ($attachments as $index => $attachment) {
            $this->process_attachment($attachment, $index + 1);
            $processed_ids[] = $attachment['id'];

            // Save progress
            if (!$this->options['dry_run']) {
                $this->save_progress($processed_ids);
            }
        }

        // Print summary
        $this->print_summary();

        // Delete progress file on successful completion
        if (!$this->options['dry_run'] && file_exists($this->progress_file)) {
            unlink($this->progress_file);
        }

        return $this->stats['errors'] === 0;
    }

    private function get_attachments($exclude_ids = []) {
        $this->CI->db->select('id, file, club, referenced_table, referenced_id');
        $this->CI->db->from('attachments');
        $this->CI->db->where('file IS NOT NULL');
        $this->CI->db->where("file != ''");

        // Apply filters
        if ($this->options['year']) {
            $this->CI->db->like('file', '/attachments/' . $this->options['year'] . '/');
        }

        if ($this->options['section']) {
            $this->CI->db->like('file', '/' . $this->options['section'] . '/');
        }

        if ($this->options['type']) {
            switch ($this->options['type']) {
                case 'pdf':
                    $this->CI->db->like('file', '.pdf');
                    break;
                case 'image':
                    $this->CI->db->where("(file LIKE '%.jpg' OR file LIKE '%.jpeg' OR file LIKE '%.png' OR file LIKE '%.gif')");
                    break;
                case 'text':
                    $this->CI->db->where("(file LIKE '%.txt' OR file LIKE '%.csv')");
                    break;
            }
        }

        if (!empty($exclude_ids)) {
            $this->CI->db->where_not_in('id', $exclude_ids);
        }

        if ($this->options['limit']) {
            $this->CI->db->limit($this->options['limit']);
        }

        $query = $this->CI->db->get();
        $attachments = $query->result_array();

        // Filter by min size if specified
        if ($this->options['min_size']) {
            $attachments = array_filter($attachments, function($att) {
                return file_exists($att['file']) && filesize($att['file']) >= $this->options['min_size'];
            });
        }

        return $attachments;
    }

    private function process_attachment($attachment, $current_number) {
        $id = $attachment['id'];
        $file_path = $attachment['file'];

        // Check if file exists
        if (!file_exists($file_path)) {
            $this->stats['errors']++;
            echo "ERROR: File not found for ID $id: $file_path\n";
            return;
        }

        // Check if already compressed
        if (preg_match('/\.(gz|zip|rar|7z)$/', $file_path)) {
            $this->stats['skipped']++;
            if ($this->options['verbose']) {
                echo "SKIP: ID $id already compressed: " . basename($file_path) . "\n";
            }
            return;
        }

        $original_size = filesize($file_path);

        // Show progress
        if (!$this->options['verbose']) {
            $this->show_progress($current_number, $attachment);
        } else {
            echo "Processing ID $id: " . basename($file_path) . " (" . $this->format_bytes($original_size) . ")\n";
        }

        $this->stats['processed']++;

        // Skip compression in dry-run mode
        if ($this->options['dry_run']) {
            $this->stats['compressed']++;
            return;
        }

        // Attempt compression
        $result = $this->CI->file_compressor->compress($file_path);

        if ($result['success']) {
            $compressed_path = $result['compressed_path'];
            $compressed_size = filesize($compressed_path);
            $this->stats['compressed_size'] += $compressed_size;

            // Update database
            $this->CI->db->where('id', $id);
            $update_success = $this->CI->db->update('attachments', ['file' => $compressed_path]);

            if ($update_success) {
                // Delete original file
                if ($file_path !== $compressed_path && file_exists($file_path)) {
                    unlink($file_path);
                }

                $this->stats['compressed']++;

                if ($this->options['verbose']) {
                    $ratio = round($result['stats']['compression_ratio'] * 100, 1);
                    echo "  SUCCESS: $original_size → $compressed_size bytes ($ratio% saved)\n";
                }
            } else {
                $this->stats['errors']++;
                echo "ERROR: Failed to update database for ID $id\n";

                // Clean up compressed file
                if (file_exists($compressed_path)) {
                    unlink($compressed_path);
                }
            }
        } else {
            $this->stats['skipped']++;
            if ($this->options['verbose']) {
                echo "  SKIPPED: " . $result['error'] . "\n";
            }
        }
    }

    private function show_progress($current, $attachment) {
        $percent = round(($current / $this->stats['total']) * 100);
        $bar_width = 40;
        $filled = round(($percent / 100) * $bar_width);
        $empty = $bar_width - $filled;

        $bar = '[' . str_repeat('█', $filled) . str_repeat('░', $empty) . ']';

        // Calculate ETA
        $elapsed = time() - $this->start_time;
        $rate = $current / $elapsed;
        $remaining = ($this->stats['total'] - $current) / $rate;
        $eta = $this->format_time($remaining);

        // Calculate storage saved
        $saved = $this->stats['original_size'] - $this->stats['compressed_size'];
        $saved_percent = $this->stats['original_size'] > 0
            ? round(($saved / $this->stats['original_size']) * 100)
            : 0;

        echo "\rProcessing: $bar $percent% ($current/{$this->stats['total']}) | ";
        echo "ETA: $eta | Saved: " . $this->format_bytes($saved) . " ($saved_percent%)";

        if ($current === $this->stats['total']) {
            echo "\n\n";
        }
    }

    private function print_summary() {
        echo "=== Summary ===\n";
        echo "Total attachments: {$this->stats['total']}\n";
        echo "Processed: {$this->stats['processed']}\n";
        echo "Successfully compressed: {$this->stats['compressed']}\n";
        echo "Skipped: {$this->stats['skipped']}\n";
        echo "Errors: {$this->stats['errors']}\n";
        echo "---\n";
        echo "Storage before: " . $this->format_bytes($this->stats['original_size']) . "\n";
        echo "Storage after: " . $this->format_bytes($this->stats['compressed_size']) . "\n";

        $saved = $this->stats['original_size'] - $this->stats['compressed_size'];
        $saved_percent = $this->stats['original_size'] > 0
            ? round(($saved / $this->stats['original_size']) * 100)
            : 0;

        echo "Total saved: " . $this->format_bytes($saved) . " ($saved_percent%)\n";

        $elapsed = time() - $this->start_time;
        echo "---\n";
        echo "Elapsed time: " . $this->format_time($elapsed) . "\n";

        if ($this->options['dry_run']) {
            echo "\n** This was a DRY RUN - no changes were made **\n";
        }
    }

    private function save_progress($processed_ids) {
        $progress = [
            'timestamp' => time(),
            'processed_ids' => $processed_ids,
            'stats' => $this->stats
        ];

        file_put_contents($this->progress_file, json_encode($progress));
    }

    private function format_bytes($bytes) {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1024 * 1024 * 1024) return round($bytes / (1024 * 1024), 1) . ' MB';
        return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
    }

    private function format_time($seconds) {
        if ($seconds < 60) return round($seconds) . 's';
        if ($seconds < 3600) return round($seconds / 60) . 'm ' . ($seconds % 60) . 's';
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return $hours . 'h ' . $minutes . 'm';
    }
}
```

### 5.2 Testing Checklist

- [ ] Run dry-run on production data
- [ ] Test with various filters (year, section, type)
- [ ] Test resume functionality (interrupt and restart)
- [ ] Test limit option with small number
- [ ] Verify progress tracking
- [ ] Test with --verbose flag
- [ ] Verify database updates
- [ ] Verify original files deleted
- [ ] Check compression statistics accuracy
- [ ] Test error handling (missing files, corrupted files)
- [ ] **Critical:** Verify treasurers can still view/download previously uploaded (uncompressed) attachments after batch compression

---

## 6. Testing Strategy

### 6.1 Unit Tests

**File:** `application/tests/unit/libraries/FileCompressorTest.php`

```php
<?php

class FileCompressorTest extends PHPUnit\Framework\TestCase {

    private $CI;
    private $compressor;

    protected function setUp(): void {
        $this->CI =& get_instance();
        $this->CI->load->library('file_compressor');
        $this->compressor = $this->CI->file_compressor;
    }

    public function testCompressImage() {
        $test_image = $this->create_test_image();

        $result = $this->compressor->compress($test_image);

        $this->assertTrue($result['success']);
        $this->assertFileExists($result['compressed_path']);
        $this->assertLessThan(filesize($test_image), filesize($result['compressed_path']));

        // Cleanup
        unlink($test_image);
        unlink($result['compressed_path']);
    }

    public function testSkipSmallFiles() {
        $small_file = tempnam(sys_get_temp_dir(), 'test_') . '.txt';
        file_put_contents($small_file, 'Small content');

        $result = $this->compressor->compress($small_file);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('too small', $result['error']);

        unlink($small_file);
    }

    // ... more tests ...

    private function create_test_image() {
        $image = imagecreatetruecolor(800, 600);
        $temp_file = tempnam(sys_get_temp_dir(), 'test_') . '.png';
        imagepng($image, $temp_file);
        imagedestroy($image);
        return $temp_file;
    }
}
```

### 6.2 Integration Tests

**File:** `application/tests/integration/AttachmentWorkflowTest.php`

```php
<?php

class AttachmentWorkflowTest extends PHPUnit\Framework\TestCase {

    public function testInlineAttachmentWorkflow() {
        // 1. Create accounting line with attachments
        // 2. Verify files moved from temp to permanent
        // 3. Verify database records created
        // 4. Verify compression applied
        // 5. Download and verify file integrity
        // ... implementation ...
    }

    public function testBatchCompressionScript() {
        // 1. Create test attachments
        // 2. Run batch compression script
        // 3. Verify all files compressed
        // 4. Verify statistics
        // ... implementation ...
    }

    // ... more tests ...
}
```

### 6.3 Manual Testing

See Phase-specific testing checklists above.

---

## 7. Migration and Deployment

### 7.1 Pre-Deployment Checklist

- [ ] All unit tests passing
- [ ] All integration tests passing
- [ ] Code review completed
- [ ] Documentation updated
- [ ] Configuration files prepared
- [ ] Backup database and files
- [ ] Test deployment on staging environment

### 7.2 Deployment Steps

1. **Backup current system:**
   ```bash
   mysqldump -u gvv_user -p gvv2 > backup_pre_attachments_improvement.sql
   tar -czf backup_uploads.tar.gz uploads/attachments/
   ```

2. **Deploy code:**
   ```bash
   git pull origin feature/attachments-improvement
   ```

3. **Create temp directory:**
   ```bash
   mkdir -p uploads/attachments/temp/
   chmod 777 uploads/attachments/temp/
   ```

4. **Deploy configuration:**
   ```bash
   cp application/config/attachments.php.example application/config/attachments.php
   # Edit configuration as needed
   ```

5. **Test inline attachment upload:**
   - Create test accounting line with attachments
   - Verify files in correct location
   - Verify database records

6. **Test compression:**
   - Upload various file types
   - Check logs for compression ratios
   - Verify file quality

7. **Run batch compression (optional):**
   ```bash
   php scripts/batch_compress_attachments.php --dry-run --verbose
   # Review output
   php scripts/batch_compress_attachments.php --verbose
   ```

8. **Setup cron job for temp file cleanup:**
   ```bash
   crontab -e
   # Add line:
   0 2 * * * cd /path/to/gvv && php scripts/cleanup_temp_attachments.php >> application/logs/cron_cleanup.log 2>&1
   ```

### 7.3 Rollback Plan

**If Phase 1 (Inline Attachments) has issues:**
1. Disable file upload widget in `compta/bs_formView.php` (comment out HTML)
2. Clear temporary upload directory: `rm -rf uploads/attachments/temp/*`
3. Users revert to old workflow (create → edit → attach)

**If Phase 2 (Compression) has issues:**
1. Set `$config['compression']['enabled'] = FALSE;` in configuration
2. Files upload without compression
3. Investigate compression failures in logs

**If data corruption occurs:**
1. Stop web server
2. Restore database: `mysql -u gvv_user -p gvv2 < backup_pre_attachments_improvement.sql`
3. Restore files: `rm -rf uploads/attachments && tar -xzf backup_uploads.tar.gz`
4. Restart web server
5. Investigate root cause

---

## 8. Configuration Reference

### 8.1 Complete Configuration File

**File:** `application/config/attachments.php`

```php
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

// === Compression Settings ===
// PRD Section 5.2 AC2.2: Three-track compression strategy
// - Compressible Images (JPEG, PNG, GIF, WebP): Resize + recompress in original format
// - PDFs: Ghostscript /ebook (150 DPI) + keep as PDF
// - Other files: gzip only (stored as .gz)
$config['compression'] = [
    'enabled' => TRUE,
    'min_size' => 102400, // 100KB - don't compress smaller files (PRD AC2.3)
    'min_ratio' => 0.10, // Only keep compressed if >10% savings (PRD AC2.3)

    // Image compression (PRD AC2.2 & CA1.7: Resize + recompress in original format)
    'image_max_width' => 1600,  // PRD AC2.6 & AC2.7: 300 DPI at A4, optimize smartphone photos
    'image_max_height' => 1200,
    'image_quality' => 85, // Quality (0-100) for JPEG/WebP, converted to 0-9 for PNG

    // PDF compression (PRD AC2.2: Ghostscript /ebook)
    'ghostscript_path' => 'gs', // Path to Ghostscript binary
    'pdf_quality' => 'ebook', // /screen (72 DPI), /ebook (150 DPI), /printer (300 DPI), /prepress (300 DPI)

    // Gzip compression level for non-image, non-PDF files (PRD AC2.2)
    'gzip_level' => 9, // Maximum compression

    // Safety
    'preserve_original_until_verified' => TRUE,
];

// === Batch Compression Settings ===
$config['batch_compression_chunk_size'] = 100;
$config['batch_compression_temp_backup'] = './uploads/attachments/_batch_backup/';
$config['batch_compression_log_detail'] = 'full'; // 'full' or 'summary'

/* End of file attachments.php */
/* Location: ./application/config/attachments.php */
```

### 8.2 Configuration Profiles

**Development:**
```php
$config['compression']['enabled'] = FALSE; // Test without compression
$config['temp_file_lifetime'] = 3600; // 1 hour for faster testing
```

**Production:**
```php
$config['compression']['enabled'] = TRUE;
$config['compression']['image_quality'] = 85; // Balanced quality
$config['temp_file_lifetime'] = 86400; // 24 hours
```

**Aggressive Compression:**
```php
$config['compression']['enabled'] = TRUE;
$config['compression']['min_size'] = 51200; // 50KB
$config['compression']['min_ratio'] = 0.05; // Accept 5% savings
$config['compression']['image_quality'] = 75; // Lower quality
$config['compression']['image_max_width'] = 1200; // Smaller dimensions
```

---

## 9. Monitoring and Maintenance

### 9.1 Log Monitoring

**Key log files:**
- `application/logs/log-YYYY-MM-DD.php` - General application logs
- `application/logs/batch_compression_YYYY-MM-DD.log` - Batch compression logs
- `application/logs/cron_cleanup.log` - Temp file cleanup logs

**Key log messages to monitor:**
```
// Successful image compression (method=gd/resize+png, gd/resize+jpeg, etc.)
INFO - Attachment compression: file=photo.png, original=5.2MB (3000x2000), compressed=850KB (1600x1067), ratio=84%, method=gd/resize+png

// Successful PDF compression (method=ghostscript/ebook)
INFO - Attachment compression: file=invoice.pdf, original=2.5MB, compressed=450KB, ratio=82%, method=ghostscript/ebook

// Successful document compression (method=gzip)
INFO - Attachment compression: file=document.docx, original=1.2MB, compressed=380KB, ratio=68%, method=gzip

// Compression skipped
INFO - Compression skipped: File too small

// Compression failed
ERROR - Compression failed: Invalid image file

// Temp file cleanup
INFO - Temp file cleanup: deleted 5 files, freed 12.3MB
```

### 9.2 Storage Monitoring

**Daily check:**
```bash
# Check total attachment storage
du -sh uploads/attachments/

# Check temp storage
du -sh uploads/attachments/temp/

# Count pending temp files
find uploads/attachments/temp/ -type f | wc -l
```

**Weekly analysis:**
```bash
# Compression effectiveness
grep "Attachment compression" application/logs/log-*.php | \
  awk -F'ratio=' '{print $2}' | \
  awk -F'%' '{sum+=$1; count++} END {print "Avg compression: " sum/count "%"}'

# Storage by section
du -sh uploads/attachments/*/*/
```

### 9.3 Performance Monitoring

**Metrics to track:**
- Average upload time with compression
- Average download time with decompression
- Temp file cleanup duration
- Batch compression throughput (files/minute)

**Performance thresholds:**
- Upload + compression: < 5 seconds for <10MB files
- Download + decompression: < 2 seconds
- Temp cleanup: < 30 seconds
- Batch compression: > 15 files/minute

---

## 10. Troubleshooting Guide

### 10.1 Common Issues

**Issue:** Compression not working
- **Check:** `$config['compression']['enabled']` is TRUE
- **Check:** PHP extensions installed: `php -m | grep -E 'gd|zlib'`
- **Check:** File size above minimum threshold (100KB default)
- **Check:** Compression ratio meets minimum (10% default)
- **Solution:** Enable verbose logging, check application logs

**Issue:** Temp files accumulating
- **Check:** Cron job running: `crontab -l`
- **Check:** Cleanup script permissions: `ls -la scripts/cleanup_temp_attachments.php`
- **Check:** Disk space: `df -h`
- **Solution:** Run cleanup script manually, adjust `temp_file_lifetime`

**Issue:** Upload fails with large files
- **Check:** PHP settings: `upload_max_filesize`, `post_max_size`, `memory_limit`
- **Check:** Web server timeout settings
- **Check:** Disk space
- **Solution:** Increase PHP limits, optimize compression algorithm

**Issue:** Compressed files corrupt
- **Check:** Compression library versions
- **Check:** Disk space during compression
- **Check:** File permissions
- **Solution:** Disable compression temporarily, restore from backup

### 10.2 Debug Mode

**Enable detailed logging:**

```php
// In application/config/attachments.php
$config['compression']['debug'] = TRUE;

// In File_compressor.php
if ($this->get_config('debug', FALSE)) {
    log_message('debug', "Compression debug: " . print_r($debug_info, TRUE));
}
```

---

**End of Implementation Plan**

**Next Steps:**
1. Review and approve plan
2. Create feature branch: `git checkout -b feature/attachments-improvement`
3. Begin Phase 1 implementation
4. Test each phase before proceeding to next
5. Deploy to staging for user acceptance testing
6. Deploy to production

**Estimated Timeline:**
- Phase 1: 2-3 days (Inline attachment upload)
- Phase 2: 2-3 days (Automatic compression - simplified for images)
- Phase 3: 1 day (Transparent decompression - simpler since images don't need decompression)
- Phase 4: 2 days (Batch compression script - after Phase 3)
- Testing & Integration: 2-3 days
- **Total: 9-12 days**

**Critical Path Note:**
- Phases 2 and 3 should be developed and tested together since gzip-compressed files (PDFs, documents) need decompression
- Images (.jpg) can be served directly without decompression (PRD CA1.7), simplifying Phase 3
- Phase 4 (batch compression) should only be run after Phase 3 is complete to ensure treasurers can access all file types

---

## Implementation Task Checklist

### Phase 1: Inline Attachment Upload (10 tasks)
- [x] Modify compta.php controller for inline attachments
- [x] Create upload_temp_attachment() AJAX handler
- [x] Create remove_temp_attachment() handler
- [x] Update formValidation() to process pending attachments
- [x] Modify bs_formView.php for attachment widget
- [x] Add JavaScript for file upload handling
- [x] Create cleanup script for temp files
- [x] Add language file translations (French, English, Dutch)
- [x] Create attachments.php config file
- [x] Complete Phase 1 testing checklist

### Phase 2: Automatic File Compression (7 tasks)
- [ ] Create File_compressor.php library
- [ ] Implement compress_image() method (GD resize + recompress in original format)
- [ ] Implement compress_pdf() method (Ghostscript /ebook, keep as PDF)
- [ ] Implement compress_gzip() method (for non-image, non-PDF files)
- [ ] Integrate compression into attachments.php upload
- [ ] Add compression configuration to config file (including Ghostscript settings)
- [ ] Complete Phase 2 testing checklist

### Phase 3: Transparent Decompression (5 tasks)
- [ ] Add download() method to attachments.php
- [ ] Implement decompress_to_temp() method
- [ ] Implement serve_file() method
- [ ] Update attachment() helper in MY_html_helper.php
- [ ] Complete Phase 3 testing checklist

### Phase 4: Batch Compression Script (5 tasks)
- [ ] Create batch_compress_attachments.php script
- [ ] Implement command-line argument parsing
- [ ] Implement Batch_Attachment_Compressor class
- [ ] Implement progress tracking and resume functionality
- [ ] Complete Phase 4 testing checklist

### Testing (3 tasks)
- [ ] Create FileCompressorTest.php unit tests
- [ ] Create AttachmentWorkflowTest.php integration tests
- [ ] Complete all manual testing checklists

### Deployment (6 tasks)
- [ ] Complete pre-deployment checklist
- [ ] Deploy to staging environment
- [ ] User acceptance testing
- [ ] Deploy to production
- [ ] Setup cron job for temp file cleanup
- [ ] Monitor logs and storage usage for first week

**Total: 36 implementation tasks** (Phase 2 now has 7 tasks instead of 6)
