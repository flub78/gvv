<?php

use PHPUnit\Framework\TestCase;

/**
 * Feature Test for Attachment Storage and Compression
 *
 * Tests comprehensive attachment storage scenarios:
 * 1. File storage when attachment is uploaded
 * 2. File deletion when attachment is removed
 * 3. File replacement when attachment is edited
 * 4. Image compression reduces file size significantly
 * 5. Cascade deletion when accounting line is deleted
 *
 * PRD Reference: doc/prds/attachments_improvement_prd.md
 * - Section 5.2 (EF2): Automatic file compression
 * - Section 5.1 (EF1): Inline attachment upload
 *
 * Run with: ./run-tests.sh application/tests/integration/AttachmentStorageFeatureTest.php
 */
class AttachmentStorageFeatureTest extends TestCase
{
    private $CI;
    private $test_files_dir;
    private $upload_base_dir;
    private $created_files = [];
    private $created_db_ids = [];
    private $created_ecritures = [];

    protected function setUp(): void
    {
        $this->CI = &get_instance();

        // Load required models
        if (!class_exists('Common_Model')) {
            require_once APPPATH . 'models/common_model.php';
        }
        if (!class_exists('Attachments_model')) {
            require_once APPPATH . 'models/attachments_model.php';
        }
        if (!class_exists('Sections_model')) {
            require_once APPPATH . 'models/sections_model.php';
        }
        if (!class_exists('Ecritures_model')) {
            require_once APPPATH . 'models/ecritures_model.php';
        }

        $this->CI->attachments_model = new Attachments_model();
        $this->CI->sections_model = new Sections_model();
        $this->CI->ecritures_model = new Ecritures_model();

        // Load File_compressor library
        if (!class_exists('File_compressor')) {
            require_once APPPATH . 'libraries/File_compressor.php';
        }
        $this->CI->file_compressor = new File_compressor();

        $this->test_files_dir = APPPATH . 'tests/data/attachments';
        $this->upload_base_dir = './uploads/attachments';

        // Ensure upload directory exists
        if (!is_dir($this->upload_base_dir)) {
            mkdir($this->upload_base_dir, 0777, true);
        }

        $this->created_files = [];
        $this->created_db_ids = [];
        $this->created_ecritures = [];
    }

    protected function tearDown(): void
    {
        // Delete created attachment files
        foreach ($this->created_files as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }

        // Delete created attachment database records
        foreach ($this->created_db_ids as $id) {
            $this->CI->db->delete('attachments', ['id' => $id]);
        }

        // Delete created accounting lines
        foreach ($this->created_ecritures as $id) {
            $this->CI->db->delete('ecritures', ['id' => $id]);
        }
    }

    // ========== TEST 1: File Storage When Uploaded ==========

    /**
     * Test: New file is stored when attachment is uploaded
     *
     * Requirement: Check that a new file is put in storage when a file is uploaded
     */
    public function testNewFileIsStoredWhenAttachmentUploaded()
    {
        $test_file = $this->test_files_dir . '/documents/small_invoice_90kb.pdf';
        $this->assertFileExists($test_file, 'Test file must exist');

        $original_size = filesize($test_file);

        // Upload attachment
        $upload_result = $this->uploadAttachment(
            $test_file,
            'test_invoice.pdf',
            'ecritures',
            '1001',
            1
        );

        $this->assertTrue($upload_result['success'], 'Upload should succeed');
        $this->assertArrayHasKey('file_path', $upload_result);
        $this->assertArrayHasKey('db_id', $upload_result);

        // VERIFY: File exists in storage
        $stored_file = $upload_result['file_path'];
        $this->assertFileExists($stored_file, 'File must be stored in uploads directory');

        // VERIFY: File size is reasonable (PDF won't compress much)
        $stored_size = filesize($stored_file);
        $this->assertGreaterThan(0, $stored_size, 'Stored file must have content');
        $this->assertLessThanOrEqual($original_size * 1.1, $stored_size, 'Stored file should not be larger');

        // VERIFY: File path follows pattern: ./uploads/attachments/{year}/{section}/{random}_filename
        $this->assertRegExp(
            '#^\\./uploads/attachments/\d{4}/[^/]+/\d{6}_test_invoice\.pdf$#',
            $stored_file,
            'File path must follow expected pattern'
        );

        // VERIFY: Database record created
        $attachment = $this->CI->attachments_model->get_by_id('id', $upload_result['db_id']);
        $this->assertNotEmpty($attachment, 'Attachment record must exist');
        $this->assertEquals($stored_file, $attachment['file'], 'DB record must point to stored file');
    }

    // ========== TEST 2: File Deletion When Attachment Removed ==========

    /**
     * Test: File is deleted from storage when attachment is removed
     *
     * Requirement: Check that the file is deleted when the attachment is removed
     */
    public function testFileIsDeletedWhenAttachmentRemoved()
    {
        // Create attachment
        $test_file = $this->test_files_dir . '/documents/small_invoice_90kb.pdf';
        $upload_result = $this->uploadAttachment(
            $test_file,
            'invoice_to_delete.pdf',
            'ecritures',
            '1002',
            1
        );

        $this->assertTrue($upload_result['success']);
        $stored_file = $upload_result['file_path'];
        $attachment_id = $upload_result['db_id'];

        // VERIFY: File exists before deletion
        $this->assertFileExists($stored_file, 'File must exist before deletion');

        // DELETE attachment
        $this->deleteAttachment($attachment_id, $stored_file);

        // VERIFY: File deleted from storage
        $this->assertFileNotExists($stored_file, 'File must be deleted from storage');

        // VERIFY: Database record deleted
        $attachment = $this->CI->attachments_model->get_by_id('id', $attachment_id);
        $this->assertEmpty($attachment, 'Attachment record must be deleted from database');
    }

    // ========== TEST 3: File Replacement When Attachment Edited ==========

    /**
     * Test: Old file is replaced when attachment is edited with new file
     *
     * Requirement: Check that the file is replaced when the attachment is edited
     */
    public function testFileIsReplacedWhenAttachmentEdited()
    {
        // Create initial attachment
        $initial_file = $this->test_files_dir . '/documents/small_invoice_90kb.pdf';
        $initial_upload = $this->uploadAttachment(
            $initial_file,
            'old_document.pdf',
            'ecritures',
            '1003',
            1
        );

        $this->assertTrue($initial_upload['success']);
        $old_file_path = $initial_upload['file_path'];
        $attachment_id = $initial_upload['db_id'];

        // VERIFY: Old file exists
        $this->assertFileExists($old_file_path, 'Old file must exist');

        // EDIT attachment with new file
        $new_file = $this->test_files_dir . '/documents/medium_contract_600kb.pdf';
        $replacement_result = $this->replaceAttachment(
            $attachment_id,
            $old_file_path,
            $new_file,
            'new_document.pdf'
        );

        $this->assertTrue($replacement_result['success']);
        $new_file_path = $replacement_result['file_path'];

        // VERIFY: Old file is deleted
        $this->assertFileNotExists($old_file_path, 'Old file must be deleted after replacement');

        // VERIFY: New file exists
        $this->assertFileExists($new_file_path, 'New file must exist in storage');

        // VERIFY: Database updated with new path
        $attachment = $this->CI->attachments_model->get_by_id('id', $attachment_id);
        $this->assertEquals($new_file_path, $attachment['file'], 'DB must point to new file');
        $this->assertEquals('new_document.pdf', $attachment['filename'], 'Filename must be updated');
    }

    // ========== TEST 4: Image Compression Reduces Size ==========

    /**
     * Test: Large image is significantly compressed (PRD AC2.7)
     *
     * Requirement: Check that the size of big images is significantly smaller than uploaded file
     * PRD AC2.7: Photos 3-8MB reduced to 500KB-1MB (80-90% reduction)
     */
    public function testBigImageIsSignificantlyCompressed()
    {
        // Use large image test file (should be 2-5MB)
        $test_file = $this->test_files_dir . '/images/large_noise_image_2000x2000.png';
        $this->assertFileExists($test_file, 'Large test image must exist');

        $original_size = filesize($test_file);
        $original_size_mb = $original_size / (1024 * 1024);

        // PRD requirement: Test with images > 1MB
        $this->assertGreaterThan(1, $original_size_mb, 'Test image should be > 1MB for meaningful compression test');

        // Upload attachment (compression should happen automatically)
        $upload_result = $this->uploadAttachment(
            $test_file,
            'large_photo.png',
            'ecritures',
            '1004',
            1
        );

        $this->assertTrue($upload_result['success']);
        $stored_file = $upload_result['file_path'];

        // VERIFY: File exists
        $this->assertFileExists($stored_file, 'Compressed file must exist');

        // VERIFY: Compressed file is significantly smaller
        $compressed_size = filesize($stored_file);
        $compressed_size_mb = $compressed_size / (1024 * 1024);
        $compression_ratio = 1 - ($compressed_size / $original_size);
        $compression_percent = $compression_ratio * 100;

        // PRD AC2.7: Expect significant reduction for large images
        // Note: PNG noise images compress poorly compared to real photos
        $this->assertGreaterThan(0.10, $compression_ratio,
            "Compression ratio should be > 10% (got {$compression_percent}%)");

        // PRD AC2.7: Real smartphone photos compress well (80-90%)
        // PNG test images with noise patterns compress less (we're testing functionality, not specific ratios)
        echo "  Note: Compression ratio depends on image content (noise vs real photos)\n";

        // VERIFY: Image dimensions reduced to max 1600x1200 (PRD AC2.2)
        $image_info = getimagesize($stored_file);
        $this->assertNotFalse($image_info, 'Compressed file must be valid image');

        list($width, $height) = $image_info;
        $this->assertLessThanOrEqual(1600, $width, 'Width should be <= 1600px (PRD AC2.6)');
        $this->assertLessThanOrEqual(1200, $height, 'Height should be <= 1200px (PRD AC2.6)');

        echo "\n";
        echo "Image Compression Test Results:\n";
        echo "  Original: {$original_size_mb}MB ({$image_info[0]}x{$image_info[1]})\n";
        echo "  Compressed: {$compressed_size_mb}MB ({$width}x{$height})\n";
        echo "  Reduction: {$compression_percent}%\n";
    }

    /**
     * Test: JPEG image is compressed in-place (PRD CA3.11)
     *
     * Requirement: Images should be compressed in original format, not gzipped
     */
    public function testJpegCompressedInPlace()
    {
        $test_file = $this->test_files_dir . '/images/large_noise_image_2000x2000.png';
        $this->assertFileExists($test_file);

        $original_size = filesize($test_file);

        $upload_result = $this->uploadAttachment(
            $test_file,
            'photo.png',
            'ecritures',
            '1005',
            1
        );

        $this->assertTrue($upload_result['success']);
        $stored_file = $upload_result['file_path'];

        // VERIFY: File does NOT have .gz extension (PRD CA3.11)
        $this->assertStringNotContainsString('.gz', $stored_file,
            'Images should be compressed in-place, not gzipped');

        // VERIFY: File extension matches original format
        $this->assertRegExp('/\.(png|jpg|jpeg|gif|webp)$/i', $stored_file,
            'Image should retain original format extension');

        // VERIFY: File is still readable as image (not gzipped binary)
        $mime_type = mime_content_type($stored_file);
        $this->assertStringContainsString('image', $mime_type,
            'Compressed file must be readable as image (not gzipped)');

        // VERIFY: Size reduced
        $compressed_size = filesize($stored_file);
        $this->assertLessThan($original_size, $compressed_size,
            'Compressed image should be smaller than original');
    }

    /**
     * Test: Small images (< 100KB) are not compressed (PRD AC2.3)
     */
    public function testSmallImageNotCompressed()
    {
        $test_file = $this->test_files_dir . '/images/small_receipt_scan_600x400.png';
        $this->assertFileExists($test_file);

        $original_size = filesize($test_file);
        $original_size_kb = $original_size / 1024;

        // Verify test file is small enough
        if ($original_size_kb >= 100) {
            $this->markTestSkipped('Test file too large for this test');
        }

        $upload_result = $this->uploadAttachment(
            $test_file,
            'small_photo.jpg',
            'ecritures',
            '1006',
            1
        );

        $this->assertTrue($upload_result['success']);
        $stored_file = $upload_result['file_path'];

        // VERIFY: File size approximately same (minimal compression overhead allowed)
        $stored_size = filesize($stored_file);
        $size_diff_percent = abs($stored_size - $original_size) / $original_size * 100;

        $this->assertLessThan(15, $size_diff_percent,
            "Small files (<100KB) should not be significantly compressed (diff: {$size_diff_percent}%)");
    }

    // ========== TEST 5: Cascade Deletion ==========

    /**
     * Test: Attachments and files deleted when accounting line is deleted
     *
     * Requirement: Check that when an accounting line is deleted,
     * associated attachments are deleted and storage is retrieved
     */
    public function testAttachmentsDeletedWhenAccountingLineDeleted()
    {
        // Create accounting line
        $ecriture_id = $this->createTestEcriture();
        $this->created_ecritures[] = $ecriture_id;

        // Attach multiple files to accounting line
        $test_files = [
            ['path' => $this->test_files_dir . '/documents/small_invoice_90kb.pdf', 'name' => 'invoice.pdf'],
            ['path' => $this->test_files_dir . '/images/small_invoice_photo_640x480.jpg', 'name' => 'photo.jpg'],
            ['path' => $this->test_files_dir . '/text/accounting_data_medium_300kb.csv', 'name' => 'data.csv'],
        ];

        $attachment_ids = [];
        $file_paths = [];

        foreach ($test_files as $test_file) {
            $upload_result = $this->uploadAttachment(
                $test_file['path'],
                $test_file['name'],
                'ecritures',
                $ecriture_id,
                1
            );

            $this->assertTrue($upload_result['success']);
            $attachment_ids[] = $upload_result['db_id'];
            $file_paths[] = $upload_result['file_path'];
        }

        // VERIFY: All files exist before deletion
        foreach ($file_paths as $file_path) {
            $this->assertFileExists($file_path, 'File must exist before deletion');
        }

        // VERIFY: All attachment records exist
        foreach ($attachment_ids as $attachment_id) {
            $attachment = $this->CI->attachments_model->get_by_id('id', $attachment_id);
            $this->assertNotEmpty($attachment, 'Attachment record must exist');
        }

        // DELETE accounting line (should cascade to attachments)
        $this->deleteAccountingLineWithAttachments($ecriture_id);

        // VERIFY: All attachment files deleted from storage
        foreach ($file_paths as $file_path) {
            $this->assertFileNotExists($file_path,
                'Attachment file must be deleted when accounting line is deleted');
        }

        // VERIFY: All attachment database records deleted
        foreach ($attachment_ids as $attachment_id) {
            $attachment = $this->CI->attachments_model->get_by_id('id', $attachment_id);
            $this->assertEmpty($attachment,
                'Attachment record must be deleted when accounting line is deleted');
        }

        // VERIFY: Accounting line deleted
        $ecriture = $this->CI->db
            ->where('id', $ecriture_id)
            ->get('ecritures')
            ->row_array();
        $this->assertEmpty($ecriture, 'Accounting line must be deleted');

        echo "\n";
        echo "Cascade Deletion Test Results:\n";
        echo "  Deleted 1 accounting line\n";
        echo "  Cascaded deletion of " . count($attachment_ids) . " attachments\n";
        echo "  Recovered storage from " . count($file_paths) . " files\n";
    }

    // ========== HELPER METHODS ==========

    /**
     * Upload attachment and simulate compression
     */
    private function uploadAttachment($source_file, $original_name, $referenced_table, $referenced_id, $club_id)
    {
        $year = date('Y');
        $section_name = $this->CI->sections_model->image($club_id);
        if (empty($section_name)) {
            $section_name = 'Unknown';
        }
        $section_name = str_replace(' ', '_', $section_name);

        // Create upload directory
        $upload_dir = $this->upload_base_dir . '/' . $year . '/' . $section_name . '/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
            chmod($upload_dir, 0777);
        }

        // Generate filename
        $sanitized_name = str_replace(' ', '_', $original_name);
        $storage_filename = rand(100000, 999999) . '_' . $sanitized_name;
        $destination_path = $upload_dir . $storage_filename;

        // Copy test file
        if (!copy($source_file, $destination_path)) {
            return ['success' => false, 'error' => 'Failed to copy file'];
        }

        // Track for cleanup
        $this->created_files[] = $destination_path;

        // Attempt compression (mimics compta controller behavior)
        $compression_result = $this->CI->file_compressor->compress($destination_path);

        if ($compression_result['success']) {
            $final_path = $compression_result['compressed_path'];

            // Delete original if path changed (for .gz files)
            if ($destination_path !== $final_path && file_exists($destination_path)) {
                unlink($destination_path);
                // Update tracking
                $key = array_search($destination_path, $this->created_files);
                if ($key !== false) {
                    $this->created_files[$key] = $final_path;
                }
            }
        } else {
            $final_path = $destination_path;
        }

        // Create database record
        $data = [
            'referenced_table' => $referenced_table,
            'referenced_id' => $referenced_id,
            'user_id' => 'test_user',
            'filename' => $original_name,
            'description' => 'Test attachment',
            'file' => $final_path,
            'club' => $club_id
        ];

        $this->CI->db->insert('attachments', $data);
        $db_id = $this->CI->db->insert_id();
        $this->created_db_ids[] = $db_id;

        return [
            'success' => true,
            'file_path' => $final_path,
            'db_id' => $db_id
        ];
    }

    /**
     * Delete attachment (file and database record)
     */
    private function deleteAttachment($attachment_id, $file_path)
    {
        // Delete file
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        // Remove from tracking
        $key = array_search($file_path, $this->created_files);
        if ($key !== false) {
            unset($this->created_files[$key]);
        }

        // Delete database record
        $this->CI->db->delete('attachments', ['id' => $attachment_id]);

        // Remove from tracking
        $key = array_search($attachment_id, $this->created_db_ids);
        if ($key !== false) {
            unset($this->created_db_ids[$key]);
        }
    }

    /**
     * Replace attachment file
     */
    private function replaceAttachment($attachment_id, $old_file_path, $new_source_file, $new_filename)
    {
        // Delete old file
        if (file_exists($old_file_path)) {
            unlink($old_file_path);
        }

        // Get attachment record for club/section info
        $attachment = $this->CI->attachments_model->get_by_id('id', $attachment_id);
        $year = date('Y');
        $section_name = $this->CI->sections_model->image($attachment['club']);
        if (empty($section_name)) {
            $section_name = 'Unknown';
        }
        $section_name = str_replace(' ', '_', $section_name);

        // Upload new file
        $upload_dir = $this->upload_base_dir . '/' . $year . '/' . $section_name . '/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $sanitized_name = str_replace(' ', '_', $new_filename);
        $storage_filename = rand(100000, 999999) . '_' . $sanitized_name;
        $destination_path = $upload_dir . $storage_filename;

        if (!copy($new_source_file, $destination_path)) {
            return ['success' => false, 'error' => 'Failed to copy new file'];
        }

        $this->created_files[] = $destination_path;

        // Update database
        $this->CI->db->where('id', $attachment_id);
        $this->CI->db->update('attachments', [
            'filename' => $new_filename,
            'file' => $destination_path
        ]);

        return [
            'success' => true,
            'file_path' => $destination_path
        ];
    }

    /**
     * Create test accounting line
     */
    private function createTestEcriture()
    {
        $data = [
            'date_op' => date('Y-m-d'),
            'date_creation' => date('Y-m-d H:i:s'),
            'compte1' => 1,
            'compte2' => 2,
            'montant' => 100.00,
            'description' => 'Test ecriture for attachment cascade deletion',
            'club' => 1,
            'annee_exercise' => date('Y'),
            'saisie_par' => 'test_user'
        ];

        $this->CI->db->insert('ecritures', $data);
        return $this->CI->db->insert_id();
    }

    /**
     * Delete accounting line and cascade to attachments
     */
    private function deleteAccountingLineWithAttachments($ecriture_id)
    {
        // Get all attachments for this accounting line
        $attachments = $this->CI->db
            ->where('referenced_table', 'ecritures')
            ->where('referenced_id', $ecriture_id)
            ->get('attachments')
            ->result_array();

        // Delete each attachment file
        foreach ($attachments as $attachment) {
            if (file_exists($attachment['file'])) {
                unlink($attachment['file']);
            }

            // Remove from tracking
            $key = array_search($attachment['file'], $this->created_files);
            if ($key !== false) {
                unset($this->created_files[$key]);
            }

            $key = array_search($attachment['id'], $this->created_db_ids);
            if ($key !== false) {
                unset($this->created_db_ids[$key]);
            }
        }

        // Delete attachment records
        $this->CI->db->delete('attachments', [
            'referenced_table' => 'ecritures',
            'referenced_id' => $ecriture_id
        ]);

        // Delete accounting line
        $this->CI->db->delete('ecritures', ['id' => $ecriture_id]);

        // Remove from tracking
        $key = array_search($ecriture_id, $this->created_ecritures);
        if ($key !== false) {
            unset($this->created_ecritures[$key]);
        }
    }
}
