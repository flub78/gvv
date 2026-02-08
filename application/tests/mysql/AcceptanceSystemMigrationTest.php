<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL integration tests for acceptance_system migration (068)
 *
 * Tests that the migration properly creates the tables with all constraints,
 * foreign keys, indexes, and correct column definitions.
 *
 * @package tests
 * @see application/migrations/068_acceptance_system.php
 */
class AcceptanceSystemMigrationTest extends TestCase
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

    /**
     * Helper method to get column info
     */
    protected function getColumnInfo($table_name, $column_name)
    {
        $query = $this->db->query("
            SELECT *
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = '" . $this->db->escape_str($table_name) . "'
            AND COLUMN_NAME = '" . $this->db->escape_str($column_name) . "'
        ");
        return $query->row_array();
    }

    /**
     * Helper to check FK exists
     */
    protected function foreignKeyExists($table_name, $column_name, $referenced_table)
    {
        $query = $this->db->query("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = '" . $this->db->escape_str($table_name) . "'
            AND COLUMN_NAME = '" . $this->db->escape_str($column_name) . "'
            AND REFERENCED_TABLE_NAME = '" . $this->db->escape_str($referenced_table) . "'
        ");
        return $query->num_rows() > 0;
    }

    /**
     * Helper to check index exists
     */
    protected function indexExists($table_name, $index_name)
    {
        $query = $this->db->query("
            SELECT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = '" . $this->db->escape_str($table_name) . "'
            AND INDEX_NAME = '" . $this->db->escape_str($index_name) . "'
        ");
        return $query->num_rows() > 0;
    }

    // ==================== acceptance_items table tests ====================

    public function testAcceptanceItemsTable_Exists()
    {
        $this->assertTrue(
            $this->tableExists('acceptance_items'),
            'acceptance_items table should exist after migration'
        );
    }

    public function testAcceptanceItemsTable_HasCorrectColumns()
    {
        if (!$this->tableExists('acceptance_items')) {
            $this->markTestSkipped('Table acceptance_items does not exist');
        }

        $columns = $this->getTableColumns('acceptance_items');
        $expected_columns = [
            'id', 'title', 'category', 'pdf_path', 'target_type',
            'version_date', 'mandatory', 'deadline', 'dual_validation',
            'role_1', 'role_2', 'target_roles', 'active',
            'created_by', 'created_at', 'updated_at'
        ];

        foreach ($expected_columns as $column) {
            $this->assertContains($column, $columns, "Column '$column' should exist in acceptance_items");
        }
    }

    public function testAcceptanceItemsTable_CategoryEnumValues()
    {
        if (!$this->tableExists('acceptance_items')) {
            $this->markTestSkipped('Table acceptance_items does not exist');
        }

        $info = $this->getColumnInfo('acceptance_items', 'category');
        $this->assertStringContainsString("'document'", $info['COLUMN_TYPE']);
        $this->assertStringContainsString("'formation'", $info['COLUMN_TYPE']);
        $this->assertStringContainsString("'controle'", $info['COLUMN_TYPE']);
        $this->assertStringContainsString("'briefing'", $info['COLUMN_TYPE']);
        $this->assertStringContainsString("'autorisation'", $info['COLUMN_TYPE']);
    }

    public function testAcceptanceItemsTable_TargetTypeEnumValues()
    {
        if (!$this->tableExists('acceptance_items')) {
            $this->markTestSkipped('Table acceptance_items does not exist');
        }

        $info = $this->getColumnInfo('acceptance_items', 'target_type');
        $this->assertStringContainsString("'internal'", $info['COLUMN_TYPE']);
        $this->assertStringContainsString("'external'", $info['COLUMN_TYPE']);
        $this->assertStringContainsString('internal', $info['COLUMN_DEFAULT']);
    }

    public function testAcceptanceItemsTable_MandatoryDefaultZero()
    {
        if (!$this->tableExists('acceptance_items')) {
            $this->markTestSkipped('Table acceptance_items does not exist');
        }

        $info = $this->getColumnInfo('acceptance_items', 'mandatory');
        $this->assertEquals('0', $info['COLUMN_DEFAULT']);
    }

    public function testAcceptanceItemsTable_Indexes()
    {
        if (!$this->tableExists('acceptance_items')) {
            $this->markTestSkipped('Table acceptance_items does not exist');
        }

        $this->assertTrue($this->indexExists('acceptance_items', 'idx_category'), 'Index idx_category should exist');
        $this->assertTrue($this->indexExists('acceptance_items', 'idx_active'), 'Index idx_active should exist');
        $this->assertTrue($this->indexExists('acceptance_items', 'idx_deadline'), 'Index idx_deadline should exist');
    }

    // ==================== acceptance_records table tests ====================

    public function testAcceptanceRecordsTable_Exists()
    {
        $this->assertTrue(
            $this->tableExists('acceptance_records'),
            'acceptance_records table should exist after migration'
        );
    }

    public function testAcceptanceRecordsTable_HasCorrectColumns()
    {
        if (!$this->tableExists('acceptance_records')) {
            $this->markTestSkipped('Table acceptance_records does not exist');
        }

        $columns = $this->getTableColumns('acceptance_records');
        $expected_columns = [
            'id', 'item_id', 'user_login', 'external_name', 'status',
            'validation_role', 'partner_record_id', 'formula_text',
            'acted_at', 'created_at', 'initiated_by', 'signature_mode',
            'linked_pilot_login', 'linked_by', 'linked_at'
        ];

        foreach ($expected_columns as $column) {
            $this->assertContains($column, $columns, "Column '$column' should exist in acceptance_records");
        }
    }

    public function testAcceptanceRecordsTable_StatusEnumValues()
    {
        if (!$this->tableExists('acceptance_records')) {
            $this->markTestSkipped('Table acceptance_records does not exist');
        }

        $info = $this->getColumnInfo('acceptance_records', 'status');
        $this->assertStringContainsString("'pending'", $info['COLUMN_TYPE']);
        $this->assertStringContainsString("'accepted'", $info['COLUMN_TYPE']);
        $this->assertStringContainsString("'refused'", $info['COLUMN_TYPE']);
        $this->assertStringContainsString('pending', $info['COLUMN_DEFAULT']);
    }

    public function testAcceptanceRecordsTable_SignatureModeEnum()
    {
        if (!$this->tableExists('acceptance_records')) {
            $this->markTestSkipped('Table acceptance_records does not exist');
        }

        $info = $this->getColumnInfo('acceptance_records', 'signature_mode');
        $this->assertStringContainsString("'direct'", $info['COLUMN_TYPE']);
        $this->assertStringContainsString("'link'", $info['COLUMN_TYPE']);
        $this->assertStringContainsString("'qrcode'", $info['COLUMN_TYPE']);
        $this->assertStringContainsString("'paper'", $info['COLUMN_TYPE']);
    }

    public function testAcceptanceRecordsTable_ForeignKeyItem()
    {
        if (!$this->tableExists('acceptance_records')) {
            $this->markTestSkipped('Table acceptance_records does not exist');
        }

        $this->assertTrue(
            $this->foreignKeyExists('acceptance_records', 'item_id', 'acceptance_items'),
            'FK on item_id -> acceptance_items should exist'
        );
    }

    public function testAcceptanceRecordsTable_ForeignKeyUser()
    {
        if (!$this->tableExists('acceptance_records')) {
            $this->markTestSkipped('Table acceptance_records does not exist');
        }

        $this->assertTrue(
            $this->foreignKeyExists('acceptance_records', 'user_login', 'membres'),
            'FK on user_login -> membres should exist'
        );
    }

    public function testAcceptanceRecordsTable_ForeignKeyPartner()
    {
        if (!$this->tableExists('acceptance_records')) {
            $this->markTestSkipped('Table acceptance_records does not exist');
        }

        $this->assertTrue(
            $this->foreignKeyExists('acceptance_records', 'partner_record_id', 'acceptance_records'),
            'FK on partner_record_id -> acceptance_records (self-reference) should exist'
        );
    }

    public function testAcceptanceRecordsTable_ForeignKeyLinkedPilot()
    {
        if (!$this->tableExists('acceptance_records')) {
            $this->markTestSkipped('Table acceptance_records does not exist');
        }

        $this->assertTrue(
            $this->foreignKeyExists('acceptance_records', 'linked_pilot_login', 'membres'),
            'FK on linked_pilot_login -> membres should exist'
        );
    }

    public function testAcceptanceRecordsTable_ForeignKeyLinkedBy()
    {
        if (!$this->tableExists('acceptance_records')) {
            $this->markTestSkipped('Table acceptance_records does not exist');
        }

        $this->assertTrue(
            $this->foreignKeyExists('acceptance_records', 'linked_by', 'membres'),
            'FK on linked_by -> membres should exist'
        );
    }

    public function testAcceptanceRecordsTable_LinkedColumnsNullable()
    {
        if (!$this->tableExists('acceptance_records')) {
            $this->markTestSkipped('Table acceptance_records does not exist');
        }

        $linked_pilot = $this->getColumnInfo('acceptance_records', 'linked_pilot_login');
        $this->assertEquals('YES', $linked_pilot['IS_NULLABLE'], 'linked_pilot_login should be nullable');

        $linked_by = $this->getColumnInfo('acceptance_records', 'linked_by');
        $this->assertEquals('YES', $linked_by['IS_NULLABLE'], 'linked_by should be nullable');

        $linked_at = $this->getColumnInfo('acceptance_records', 'linked_at');
        $this->assertEquals('YES', $linked_at['IS_NULLABLE'], 'linked_at should be nullable');
    }

    // ==================== acceptance_signatures table tests ====================

    public function testAcceptanceSignaturesTable_Exists()
    {
        $this->assertTrue(
            $this->tableExists('acceptance_signatures'),
            'acceptance_signatures table should exist after migration'
        );
    }

    public function testAcceptanceSignaturesTable_HasCorrectColumns()
    {
        if (!$this->tableExists('acceptance_signatures')) {
            $this->markTestSkipped('Table acceptance_signatures does not exist');
        }

        $columns = $this->getTableColumns('acceptance_signatures');
        $expected_columns = [
            'id', 'record_id', 'signer_first_name', 'signer_last_name',
            'signer_quality', 'beneficiary_first_name', 'beneficiary_last_name',
            'signature_type', 'signature_data', 'file_path',
            'original_filename', 'file_size', 'mime_type',
            'signed_at', 'pilot_attestation'
        ];

        foreach ($expected_columns as $column) {
            $this->assertContains($column, $columns, "Column '$column' should exist in acceptance_signatures");
        }
    }

    public function testAcceptanceSignaturesTable_SignatureTypeEnum()
    {
        if (!$this->tableExists('acceptance_signatures')) {
            $this->markTestSkipped('Table acceptance_signatures does not exist');
        }

        $info = $this->getColumnInfo('acceptance_signatures', 'signature_type');
        $this->assertStringContainsString("'tactile'", $info['COLUMN_TYPE']);
        $this->assertStringContainsString("'upload'", $info['COLUMN_TYPE']);
    }

    public function testAcceptanceSignaturesTable_ForeignKeyRecord()
    {
        if (!$this->tableExists('acceptance_signatures')) {
            $this->markTestSkipped('Table acceptance_signatures does not exist');
        }

        $this->assertTrue(
            $this->foreignKeyExists('acceptance_signatures', 'record_id', 'acceptance_records'),
            'FK on record_id -> acceptance_records should exist'
        );
    }

    public function testAcceptanceSignaturesTable_BeneficiaryColumnsNullable()
    {
        if (!$this->tableExists('acceptance_signatures')) {
            $this->markTestSkipped('Table acceptance_signatures does not exist');
        }

        $quality = $this->getColumnInfo('acceptance_signatures', 'signer_quality');
        $this->assertEquals('YES', $quality['IS_NULLABLE'], 'signer_quality should be nullable');

        $ben_first = $this->getColumnInfo('acceptance_signatures', 'beneficiary_first_name');
        $this->assertEquals('YES', $ben_first['IS_NULLABLE'], 'beneficiary_first_name should be nullable');

        $ben_last = $this->getColumnInfo('acceptance_signatures', 'beneficiary_last_name');
        $this->assertEquals('YES', $ben_last['IS_NULLABLE'], 'beneficiary_last_name should be nullable');
    }

    public function testAcceptanceSignaturesTable_PilotAttestationDefault()
    {
        if (!$this->tableExists('acceptance_signatures')) {
            $this->markTestSkipped('Table acceptance_signatures does not exist');
        }

        $info = $this->getColumnInfo('acceptance_signatures', 'pilot_attestation');
        $this->assertEquals('0', $info['COLUMN_DEFAULT']);
    }

    // ==================== acceptance_tokens table tests ====================

    public function testAcceptanceTokensTable_Exists()
    {
        $this->assertTrue(
            $this->tableExists('acceptance_tokens'),
            'acceptance_tokens table should exist after migration'
        );
    }

    public function testAcceptanceTokensTable_HasCorrectColumns()
    {
        if (!$this->tableExists('acceptance_tokens')) {
            $this->markTestSkipped('Table acceptance_tokens does not exist');
        }

        $columns = $this->getTableColumns('acceptance_tokens');
        $expected_columns = [
            'id', 'token', 'item_id', 'record_id', 'mode',
            'created_by', 'created_at', 'expires_at', 'used', 'used_at'
        ];

        foreach ($expected_columns as $column) {
            $this->assertContains($column, $columns, "Column '$column' should exist in acceptance_tokens");
        }
    }

    public function testAcceptanceTokensTable_TokenUniqueKey()
    {
        if (!$this->tableExists('acceptance_tokens')) {
            $this->markTestSkipped('Table acceptance_tokens does not exist');
        }

        $query = $this->db->query("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'acceptance_tokens'
            AND CONSTRAINT_TYPE = 'UNIQUE'
            AND CONSTRAINT_NAME = 'uk_token'
        ");

        $this->assertGreaterThan(0, $query->num_rows(), 'UNIQUE constraint uk_token should exist');
    }

    public function testAcceptanceTokensTable_ModeEnumValues()
    {
        if (!$this->tableExists('acceptance_tokens')) {
            $this->markTestSkipped('Table acceptance_tokens does not exist');
        }

        $info = $this->getColumnInfo('acceptance_tokens', 'mode');
        $this->assertStringContainsString("'direct'", $info['COLUMN_TYPE']);
        $this->assertStringContainsString("'link'", $info['COLUMN_TYPE']);
        $this->assertStringContainsString("'qrcode'", $info['COLUMN_TYPE']);
    }

    public function testAcceptanceTokensTable_ForeignKeyItem()
    {
        if (!$this->tableExists('acceptance_tokens')) {
            $this->markTestSkipped('Table acceptance_tokens does not exist');
        }

        $this->assertTrue(
            $this->foreignKeyExists('acceptance_tokens', 'item_id', 'acceptance_items'),
            'FK on item_id -> acceptance_items should exist'
        );
    }

    public function testAcceptanceTokensTable_UsedDefaultZero()
    {
        if (!$this->tableExists('acceptance_tokens')) {
            $this->markTestSkipped('Table acceptance_tokens does not exist');
        }

        $info = $this->getColumnInfo('acceptance_tokens', 'used');
        $this->assertEquals('0', $info['COLUMN_DEFAULT']);
    }

    // ==================== Cross-table dependency tests ====================

    public function testMigrationRollback_ForeignKeyDependencies()
    {
        // Verify the dependency chain: tokens/signatures -> records -> items
        if (!$this->tableExists('acceptance_items')) {
            $this->markTestSkipped('Tables do not exist');
        }

        $this->assertTrue(
            $this->foreignKeyExists('acceptance_records', 'item_id', 'acceptance_items'),
            'acceptance_records depends on acceptance_items'
        );

        $this->assertTrue(
            $this->foreignKeyExists('acceptance_signatures', 'record_id', 'acceptance_records'),
            'acceptance_signatures depends on acceptance_records'
        );

        $this->assertTrue(
            $this->foreignKeyExists('acceptance_tokens', 'item_id', 'acceptance_items'),
            'acceptance_tokens depends on acceptance_items'
        );
    }
}
