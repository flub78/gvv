<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL tests for migrations 152/153/154 (Lot 1 —
 * doc/plans/configuration_bons_vols_decouverte_plan.md):
 * - 152: table vols_decouverte_looks
 * - 153: table vols_decouverte_look_sections
 * - 154: column vols_decouverte.pdf_path
 *
 * @see application/migrations/152_create_vols_decouverte_looks_table.php
 * @see application/migrations/153_create_vols_decouverte_look_sections_table.php
 * @see application/migrations/154_add_pdf_path_to_vols_decouverte.php
 */
class VolsDecouverteLooksMigrationTest extends TestCase
{
    protected $db;

    protected function setUp(): void
    {
        $CI =& get_instance();
        $this->db = $CI->db;

        // CREATE TABLE IF NOT EXISTS makes 152/153 safe to re-run, but the
        // ALTER TABLE ADD COLUMN in 154 is not: only apply it once.
        $this->loadMigration('152_create_vols_decouverte_looks_table.php', 'Migration_Create_vols_decouverte_looks_table')->up();
        $this->loadMigration('153_create_vols_decouverte_look_sections_table.php', 'Migration_Create_vols_decouverte_look_sections_table')->up();
        if (!$this->columnExists('vols_decouverte', 'pdf_path')) {
            $this->loadMigration('154_add_pdf_path_to_vols_decouverte.php', 'Migration_Add_pdf_path_to_vols_decouverte')->up();
        }
    }

    private function tableExists($table)
    {
        return (bool) $this->db->table_exists($table);
    }

    private function columnExists($table, $column)
    {
        $t = $this->db->escape_str($table);
        $c = $this->db->escape_str($column);
        $query = $this->db->query(
            "SELECT COUNT(*) as `count` FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND COLUMN_NAME = '$c'"
        );
        $row = $query ? $query->row_array() : null;
        return isset($row['count']) && (int) $row['count'] > 0;
    }

    private function loadMigration($file, $class)
    {
        if (!class_exists('CI_Migration')) {
            require_once BASEPATH . 'libraries/Migration.php';
        }
        if (!class_exists($class)) {
            require_once APPPATH . 'migrations/' . $file;
        }
        return new $class();
    }

    public function testMigration152_CreatesLooksTableWithExpectedColumns()
    {
        $this->assertTrue($this->tableExists('vols_decouverte_looks'));
        foreach (array('id', 'nom', 'layout_json', 'fond_recto_path', 'fond_verso_path', 'is_default',
                        'created_at', 'updated_at', 'created_by', 'updated_by') as $column) {
            $this->assertTrue(
                $this->columnExists('vols_decouverte_looks', $column),
                "Column $column should exist on vols_decouverte_looks"
            );
        }
    }

    public function testMigration153_CreatesLookSectionsTableWithExpectedColumns()
    {
        $this->assertTrue($this->tableExists('vols_decouverte_look_sections'));
        foreach (array('id', 'section_id', 'look_id', 'created_at', 'updated_at', 'created_by', 'updated_by') as $column) {
            $this->assertTrue(
                $this->columnExists('vols_decouverte_look_sections', $column),
                "Column $column should exist on vols_decouverte_look_sections"
            );
        }
    }

    public function testMigration153_SectionIdIsUnique()
    {
        $section_id = 1;
        $look_id = $this->db->query(
            "INSERT INTO vols_decouverte_looks (nom, layout_json) VALUES ('Test unicité', '{}')"
        ) ? $this->db->insert_id() : null;
        $this->assertNotEmpty($look_id);

        $this->db->query(
            "INSERT INTO vols_decouverte_look_sections (section_id, look_id) VALUES ($section_id, $look_id)"
        );

        $rejected = false;
        try {
            $duplicate_ok = $this->db->query(
                "INSERT INTO vols_decouverte_look_sections (section_id, look_id) VALUES ($section_id, $look_id)"
            );
            $rejected = ($duplicate_ok === false);
        } catch (\Throwable $e) {
            $rejected = true;
        }
        $this->assertTrue($rejected, 'A second association for the same section_id must be rejected (UNIQUE constraint)');

        $this->db->query("DELETE FROM vols_decouverte_look_sections WHERE section_id = $section_id");
        $this->db->query("DELETE FROM vols_decouverte_looks WHERE id = $look_id");
    }

    public function testMigration154_AddsPdfPathColumnNullableOnVolsDecouverte()
    {
        $this->assertTrue($this->columnExists('vols_decouverte', 'pdf_path'));

        $query = $this->db->query("
            SELECT IS_NULLABLE, DATA_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'vols_decouverte'
              AND COLUMN_NAME = 'pdf_path'
        ");
        $row = $query->row_array();

        $this->assertNotEmpty($row, 'Column pdf_path should be found in information_schema');
        $this->assertEquals('YES', $row['IS_NULLABLE'], 'pdf_path must be nullable (historical rows have none)');
        $this->assertEquals('varchar', strtolower($row['DATA_TYPE']), 'pdf_path must be of type VARCHAR');
    }

    public function testMigrations_DownRemovesTablesAndColumn()
    {
        $migration154 = $this->loadMigration('154_add_pdf_path_to_vols_decouverte.php', 'Migration_Add_pdf_path_to_vols_decouverte');
        $migration153 = $this->loadMigration('153_create_vols_decouverte_look_sections_table.php', 'Migration_Create_vols_decouverte_look_sections_table');
        $migration152 = $this->loadMigration('152_create_vols_decouverte_looks_table.php', 'Migration_Create_vols_decouverte_looks_table');

        try {
            // FK from 153 to 152 requires dropping 153 before 152.
            $migration154->down();
            $migration153->down();
            $migration152->down();

            $this->assertFalse($this->columnExists('vols_decouverte', 'pdf_path'));
            $this->assertFalse($this->tableExists('vols_decouverte_look_sections'));
            $this->assertFalse($this->tableExists('vols_decouverte_looks'));
        } finally {
            // Restore for any test running after this one in the suite.
            $migration152->up();
            $migration153->up();
            $migration154->up();
        }
    }
}
