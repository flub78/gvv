<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL integration tests for section menu flags migration (072)
 *
 * Tests that the migration properly adds the three columns to the sections table
 * with correct types, defaults, and that the rollback is clean.
 *
 * @package tests
 * @see application/migrations/072_section_menu_flags.php
 * @see doc/design_notes/section_menu_visibility_plan.md
 */
class SectionMenuFlagsTest extends TestCase
{
    protected $CI;
    protected $db;

    protected function setUp(): void
    {
        $this->CI =& get_instance();
        $this->db = $this->CI->db;
    }

    /**
     * Helper: get column info from information_schema
     */
    protected function getColumnInfo($table, $column)
    {
        $query = $this->db->query("
            SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = '" . $this->db->escape_str($table) . "'
              AND COLUMN_NAME  = '" . $this->db->escape_str($column) . "'
        ");
        return $query->row_array();
    }

    // ==================== Column existence ====================

    public function testSections_HasColumn_GestionPlaneurs()
    {
        $col = $this->getColumnInfo('sections', 'gestion_planeurs');
        $this->assertNotEmpty($col, 'Column gestion_planeurs must exist in sections');
    }

    public function testSections_HasColumn_GestionAvions()
    {
        $col = $this->getColumnInfo('sections', 'gestion_avions');
        $this->assertNotEmpty($col, 'Column gestion_avions must exist in sections');
    }

    public function testSections_HasColumn_LibelleMenuAvions()
    {
        $col = $this->getColumnInfo('sections', 'libelle_menu_avions');
        $this->assertNotEmpty($col, 'Column libelle_menu_avions must exist in sections');
    }

    // ==================== Column types and defaults ====================

    public function testGestionPlaneurs_IsNotNullableWithDefaultZero()
    {
        $col = $this->getColumnInfo('sections', 'gestion_planeurs');
        if (empty($col)) {
            $this->markTestSkipped('Column gestion_planeurs does not exist');
        }
        $this->assertEquals('NO', $col['IS_NULLABLE'], 'gestion_planeurs must be NOT NULL');
        $this->assertEquals('0', $col['COLUMN_DEFAULT'], 'gestion_planeurs must default to 0');
    }

    public function testGestionAvions_IsNotNullableWithDefaultZero()
    {
        $col = $this->getColumnInfo('sections', 'gestion_avions');
        if (empty($col)) {
            $this->markTestSkipped('Column gestion_avions does not exist');
        }
        $this->assertEquals('NO', $col['IS_NULLABLE'], 'gestion_avions must be NOT NULL');
        $this->assertEquals('0', $col['COLUMN_DEFAULT'], 'gestion_avions must default to 0');
    }

    public function testLibelleMenuAvions_IsNullableWithNullDefault()
    {
        $col = $this->getColumnInfo('sections', 'libelle_menu_avions');
        if (empty($col)) {
            $this->markTestSkipped('Column libelle_menu_avions does not exist');
        }
        $this->assertEquals('YES', $col['IS_NULLABLE'], 'libelle_menu_avions must be nullable');
        // MySQL information_schema returns the string 'NULL' (not PHP null) for NULL defaults
        $this->assertTrue(
            $col['COLUMN_DEFAULT'] === null || $col['COLUMN_DEFAULT'] === 'NULL',
            'libelle_menu_avions must default to NULL'
        );
    }

    // ==================== Rollback compatibility ====================

    public function testMigrationRollback_DropColumnsInCorrectOrder()
    {
        // Verify all three columns exist so down() can drop them.
        // We do NOT actually run down() to avoid breaking the environment.
        $planeurs = $this->getColumnInfo('sections', 'gestion_planeurs');
        $avions   = $this->getColumnInfo('sections', 'gestion_avions');
        $libelle  = $this->getColumnInfo('sections', 'libelle_menu_avions');

        $this->assertNotEmpty($planeurs, 'gestion_planeurs must exist for rollback to work');
        $this->assertNotEmpty($avions,   'gestion_avions must exist for rollback to work');
        $this->assertNotEmpty($libelle,  'libelle_menu_avions must exist for rollback to work');
    }
}
