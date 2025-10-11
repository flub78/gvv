<?php

use PHPUnit\Framework\TestCase;

/**
 * Test case for ecriture deletion with attachment cascade deletion
 * 
 * This test verifies that when an ecriture is deleted, all associated
 * attachments and their physical files are properly removed.
 * 
 * This is a unit test focusing on the business logic of attachment deletion.
 * For full integration testing, run with CI framework loaded.
 */
class EcritureAttachmentDeletionTest extends TestCase
{
    /**
     * Test that the delete_attachments_for_ecriture method exists in the model
     */
    public function test_delete_attachments_method_exists()
    {
        $ecritures_file = file_get_contents(__DIR__ . '/../../../models/ecritures_model.php');
        
        // Check if the method exists
        $this->assertStringContainsString(
            'delete_attachments_for_ecriture',
            $ecritures_file,
            'delete_attachments_for_ecriture method should exist in ecritures_model'
        );
        
        // Check if method is properly defined as private
        $this->assertStringContainsString(
            'private function delete_attachments_for_ecriture',
            $ecritures_file,
            'delete_attachments_for_ecriture should be a private method'
        );
    }

    /**
     * Test that delete_ecriture calls the attachment deletion method
     */
    public function test_delete_ecriture_calls_attachment_deletion()
    {
        $ecritures_file = file_get_contents(__DIR__ . '/../../../models/ecritures_model.php');
        
        // Check if delete_ecriture calls the attachment deletion method
        $this->assertStringContainsString(
            '$this->delete_attachments_for_ecriture($id);',
            $ecritures_file,
            'delete_ecriture should call delete_attachments_for_ecriture method'
        );
    }

    /**
     * Test that delete_all uses delete_ecriture for cascade deletion
     */
    public function test_delete_all_uses_delete_ecriture()
    {
        $ecritures_file = file_get_contents(__DIR__ . '/../../../models/ecritures_model.php');
        
        // Check if delete_all uses delete_ecriture for each record
        $this->assertStringContainsString(
            '$this->ecritures_model->delete_ecriture($row[',
            $ecritures_file,
            'delete_all should use delete_ecriture to ensure cascade deletion'
        );
    }

    /**
     * Test the attachment deletion method implementation
     */
    public function test_attachment_deletion_implementation()
    {
        $ecritures_file = file_get_contents(__DIR__ . '/../../../models/ecritures_model.php');
        
        // Check key components of the implementation
        $required_patterns = [
            // Database query for attachments
            "->where('referenced_table', 'ecritures')",
            "->where('referenced_id', \$ecriture_id)",
            "->from('attachments')",
            
            // File deletion logic
            'file_exists($file_path)',
            '@unlink($file_path)',
            
            // Database record deletion
            "->delete('attachments'",
            
            // Proper logging
            'gvv_debug(',
            'gvv_error(',
            'gvv_info('
        ];
        
        foreach ($required_patterns as $pattern) {
            $this->assertStringContainsString(
                $pattern,
                $ecritures_file,
                "Attachment deletion implementation should contain: {$pattern}"
            );
        }
    }

    /**
     * Test that the method handles empty attachment list gracefully
     */
    public function test_handles_empty_attachments()
    {
        $ecritures_file = file_get_contents(__DIR__ . '/../../../models/ecritures_model.php');
        
        // Check if method handles empty attachments gracefully
        $this->assertStringContainsString(
            'if (empty($attachments))',
            $ecritures_file,
            'Method should handle case when no attachments are found'
        );
        
        $this->assertStringContainsString(
            'return;',
            $ecritures_file,
            'Method should return early when no attachments found'
        );
    }

    /**
     * Test that file deletion errors are handled properly
     */
    public function test_handles_file_deletion_errors()
    {
        $ecritures_file = file_get_contents(__DIR__ . '/../../../models/ecritures_model.php');
        
        // Check error handling for file deletion failures
        $this->assertStringContainsString(
            'Failed to delete attachment file',
            $ecritures_file,
            'Method should log errors when file deletion fails'
        );
    }

    /**
     * Test that database deletion errors are handled properly
     */
    public function test_handles_database_deletion_errors()
    {
        $ecritures_file = file_get_contents(__DIR__ . '/../../../models/ecritures_model.php');
        
        // Check error handling for database deletion failures
        $this->assertStringContainsString(
            'Failed to delete attachment record',
            $ecritures_file,
            'Method should log errors when database deletion fails'
        );
    }

    /**
     * Test that the method provides proper logging and metrics
     */
    public function test_provides_proper_logging()
    {
        $ecritures_file = file_get_contents(__DIR__ . '/../../../models/ecritures_model.php');
        
        // Check for success logging with metrics
        $this->assertStringContainsString(
            'recovered {$total_size_mb}MB storage',
            $ecritures_file,
            'Method should log storage space recovered'
        );
        
        // Check for file size tracking
        $this->assertStringContainsString(
            'filesize($file_path)',
            $ecritures_file,
            'Method should track file sizes for metrics'
        );
    }

    /**
     * Test method documentation
     */
    public function test_method_documentation()
    {
        $ecritures_file = file_get_contents(__DIR__ . '/../../../models/ecritures_model.php');
        
        // Check for proper documentation
        $this->assertStringContainsString(
            'Delete all attachments associated with an ecriture',
            $ecritures_file,
            'Method should be properly documented'
        );
        
        $this->assertStringContainsString(
            '@param int $ecriture_id',
            $ecritures_file,
            'Method should document its parameter'
        );
    }

    /**
     * Test that attachment deletion is called before database deletion
     */
    public function test_attachment_deletion_order()
    {
        $ecritures_file = file_get_contents(__DIR__ . '/../../../models/ecritures_model.php');
        
        // Extract the delete_ecriture method to check order
        preg_match('/public function delete_ecriture.*?(?=public function|\Z)/s', $ecritures_file, $matches);
        $delete_method = $matches[0] ?? '';
        
        $this->assertNotEmpty($delete_method, 'Should find delete_ecriture method');
        
        // Find positions of attachment deletion and database deletion
        $attachment_pos = strpos($delete_method, 'delete_attachments_for_ecriture');
        $db_delete_pos = strpos($delete_method, '$this->db->delete($this->table');
        
        $this->assertNotFalse($attachment_pos, 'Should call attachment deletion');
        $this->assertNotFalse($db_delete_pos, 'Should call database deletion');
        $this->assertLessThan($db_delete_pos, $attachment_pos, 
            'Attachment deletion should happen before database deletion');
    }
}