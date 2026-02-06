<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL integration tests for archived_documents migration (067)
 *
 * Tests that the migration properly creates the tables with all constraints,
 * foreign keys, indexes, and initial data.
 *
 * @package tests
 * @see application/migrations/067_archived_documents.php
 */
class ArchivedDocumentsMigrationTest extends TestCase
{
    protected $CI;
    protected $db;

    protected function setUp(): void
    {
        $this->CI =& get_instance();
        $this->db = $this->CI->db;
    }

    /**
     * Helper method to check if a table exists
     */
    protected function tableExists($table_name)
    {
        $query = $this->db->query("
            SELECT COUNT(*) as count
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = '" . $this->db->escape_str($table_name) . "'
        ");
        $result = $query->row_array();
        return $result['count'] > 0;
    }

    /**
     * Helper method to get columns of a table
     */
    protected function getTableColumns($table_name)
    {
        $query = $this->db->query("
            SELECT COLUMN_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = '" . $this->db->escape_str($table_name) . "'
        ");

        $columns = [];
        foreach ($query->result_array() as $row) {
            $columns[] = $row['COLUMN_NAME'];
        }
        return $columns;
    }

    // ==================== document_types table tests ====================

    public function testDocumentTypesTable_Exists()
    {
        $this->assertTrue(
            $this->tableExists('document_types'),
            'document_types table should exist after migration'
        );
    }

    public function testDocumentTypesTable_HasCorrectColumns()
    {
        if (!$this->tableExists('document_types')) {
            $this->markTestSkipped('Table document_types does not exist');
        }

        $columns = $this->getTableColumns('document_types');
        $expected_columns = [
            'id', 'code', 'name', 'section_id', 'scope', 'required',
            'has_expiration', 'allow_versioning',
            'storage_by_year', 'alert_days_before', 'active', 'display_order'
        ];

        foreach ($expected_columns as $column) {
            $this->assertContains($column, $columns, "Column '$column' should exist in document_types");
        }
    }

    public function testDocumentTypesTable_IdIsPrimaryKey()
    {
        if (!$this->tableExists('document_types')) {
            $this->markTestSkipped('Table document_types does not exist');
        }

        $query = $this->db->query("
            SELECT COLUMN_KEY
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'document_types'
            AND COLUMN_NAME = 'id'
        ");

        $result = $query->row_array();
        $this->assertEquals('PRI', $result['COLUMN_KEY'], 'id should be primary key');
    }

    public function testDocumentTypesTable_UniqueConstraintCodeSection()
    {
        if (!$this->tableExists('document_types')) {
            $this->markTestSkipped('Table document_types does not exist');
        }

        $query = $this->db->query("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'document_types'
            AND CONSTRAINT_TYPE = 'UNIQUE'
            AND CONSTRAINT_NAME = 'uk_code_section'
        ");

        $this->assertGreaterThan(0, $query->num_rows(), 'UNIQUE constraint uk_code_section should exist');
    }

    public function testDocumentTypesTable_ScopEnumValues()
    {
        if (!$this->tableExists('document_types')) {
            $this->markTestSkipped('Table document_types does not exist');
        }

        $query = $this->db->query("
            SELECT COLUMN_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'document_types'
            AND COLUMN_NAME = 'scope'
        ");

        $result = $query->row_array();
        $this->assertStringContainsString("'pilot'", $result['COLUMN_TYPE']);
        $this->assertStringContainsString("'section'", $result['COLUMN_TYPE']);
        $this->assertStringContainsString("'club'", $result['COLUMN_TYPE']);
    }

    public function testDocumentTypesTable_HasInitialData()
    {
        if (!$this->tableExists('document_types')) {
            $this->markTestSkipped('Table document_types does not exist');
        }

        $query = $this->db->query("SELECT COUNT(*) as count FROM document_types");
        $result = $query->row_array();

        $this->assertGreaterThanOrEqual(8, $result['count'], 'document_types should have at least 8 initial records');
    }

    public function testDocumentTypesTable_MedicalTypeExists()
    {
        if (!$this->tableExists('document_types')) {
            $this->markTestSkipped('Table document_types does not exist');
        }

        $query = $this->db->query("
            SELECT * FROM document_types WHERE code = 'medical'
        ");

        $this->assertEquals(1, $query->num_rows(), 'medical document type should exist');

        $result = $query->row_array();
        $this->assertEquals('pilot', $result['scope']);
        $this->assertEquals(1, $result['required']);
        $this->assertEquals(1, $result['has_expiration']);
    }

    // ==================== archived_documents table tests ====================

    public function testArchivedDocumentsTable_Exists()
    {
        $this->assertTrue(
            $this->tableExists('archived_documents'),
            'archived_documents table should exist after migration'
        );
    }

    public function testArchivedDocumentsTable_HasCorrectColumns()
    {
        if (!$this->tableExists('archived_documents')) {
            $this->markTestSkipped('Table archived_documents does not exist');
        }

        $columns = $this->getTableColumns('archived_documents');
        $expected_columns = [
            'id', 'document_type_id', 'pilot_login', 'section_id',
            'file_path', 'original_filename', 'description',
            'uploaded_by', 'uploaded_at', 'valid_from', 'valid_until',
            'alarm_disabled', 'previous_version_id', 'is_current_version',
            'file_size', 'mime_type'
        ];

        foreach ($expected_columns as $column) {
            $this->assertContains($column, $columns, "Column '$column' should exist in archived_documents");
        }
    }

    public function testArchivedDocumentsTable_IdIsPrimaryKey()
    {
        if (!$this->tableExists('archived_documents')) {
            $this->markTestSkipped('Table archived_documents does not exist');
        }

        $query = $this->db->query("
            SELECT COLUMN_KEY
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'archived_documents'
            AND COLUMN_NAME = 'id'
        ");

        $result = $query->row_array();
        $this->assertEquals('PRI', $result['COLUMN_KEY'], 'id should be primary key');
    }

    public function testArchivedDocumentsTable_AlarmDisabledDefault()
    {
        if (!$this->tableExists('archived_documents')) {
            $this->markTestSkipped('Table archived_documents does not exist');
        }

        $query = $this->db->query("
            SELECT COLUMN_DEFAULT
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'archived_documents'
            AND COLUMN_NAME = 'alarm_disabled'
        ");

        $result = $query->row_array();
        $this->assertEquals('0', $result['COLUMN_DEFAULT'], 'alarm_disabled should default to 0');
    }

    public function testArchivedDocumentsTable_ForeignKeyDocumentType()
    {
        if (!$this->tableExists('archived_documents')) {
            $this->markTestSkipped('Table archived_documents does not exist');
        }

        $query = $this->db->query("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'archived_documents'
            AND COLUMN_NAME = 'document_type_id'
            AND REFERENCED_TABLE_NAME = 'document_types'
        ");

        $this->assertGreaterThan(0, $query->num_rows(), 'Foreign key on document_type_id should exist');
    }

    public function testArchivedDocumentsTable_ForeignKeyPilot()
    {
        if (!$this->tableExists('archived_documents')) {
            $this->markTestSkipped('Table archived_documents does not exist');
        }

        $query = $this->db->query("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'archived_documents'
            AND COLUMN_NAME = 'pilot_login'
            AND REFERENCED_TABLE_NAME = 'membres'
        ");

        $this->assertGreaterThan(0, $query->num_rows(), 'Foreign key on pilot_login should exist');
    }

    public function testArchivedDocumentsTable_ForeignKeySection()
    {
        if (!$this->tableExists('archived_documents')) {
            $this->markTestSkipped('Table archived_documents does not exist');
        }

        $query = $this->db->query("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'archived_documents'
            AND COLUMN_NAME = 'section_id'
            AND REFERENCED_TABLE_NAME = 'sections'
        ");

        $this->assertGreaterThan(0, $query->num_rows(), 'Foreign key on section_id should exist');
    }

    public function testArchivedDocumentsTable_ForeignKeyPreviousVersion()
    {
        if (!$this->tableExists('archived_documents')) {
            $this->markTestSkipped('Table archived_documents does not exist');
        }

        $query = $this->db->query("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'archived_documents'
            AND COLUMN_NAME = 'previous_version_id'
            AND REFERENCED_TABLE_NAME = 'archived_documents'
        ");

        $this->assertGreaterThan(0, $query->num_rows(), 'Foreign key on previous_version_id (self-reference) should exist');
    }

    public function testArchivedDocumentsTable_IndexExpiration()
    {
        if (!$this->tableExists('archived_documents')) {
            $this->markTestSkipped('Table archived_documents does not exist');
        }

        $query = $this->db->query("
            SELECT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'archived_documents'
            AND COLUMN_NAME = 'valid_until'
            AND INDEX_NAME = 'idx_expiration'
        ");

        $this->assertGreaterThan(0, $query->num_rows(), 'Index idx_expiration should exist');
    }

    public function testArchivedDocumentsTable_IndexAlarm()
    {
        if (!$this->tableExists('archived_documents')) {
            $this->markTestSkipped('Table archived_documents does not exist');
        }

        $query = $this->db->query("
            SELECT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'archived_documents'
            AND COLUMN_NAME = 'alarm_disabled'
            AND INDEX_NAME = 'idx_alarm'
        ");

        $this->assertGreaterThan(0, $query->num_rows(), 'Index idx_alarm should exist');
    }

    // ==================== Migration rollback test ====================

    public function testMigrationRollback_CanDropTables()
    {
        // This test verifies the schema is compatible with rollback
        // We don't actually run down() as it would break other tests
        // Just verify the tables can be dropped in correct order

        if (!$this->tableExists('archived_documents') || !$this->tableExists('document_types')) {
            $this->markTestSkipped('Tables do not exist');
        }

        // Verify archived_documents depends on document_types (FK constraint)
        $query = $this->db->query("
            SELECT CONSTRAINT_NAME
            FROM information_schema.REFERENTIAL_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND TABLE_NAME = 'archived_documents'
            AND REFERENCED_TABLE_NAME = 'document_types'
        ");

        $this->assertGreaterThan(0, $query->num_rows(),
            'archived_documents should have FK to document_types, confirming drop order matters');
    }
}
