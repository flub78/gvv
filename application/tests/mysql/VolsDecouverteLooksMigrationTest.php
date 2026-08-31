<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL tests for migrations 152/153/154 (Lot 1 —
 * doc/plans/configuration_bons_vols_decouverte_plan.md):
 * - 152: table vols_decouverte_looks
 * - 153: table vols_decouverte_look_sections
 * - 154: column vols_decouverte.pdf_path
 *
 * Règle de la suite : un test qui touche la base réelle doit la restaurer
 * exactement dans l'état où il l'a trouvée. Le test de réversibilité
 * (`testMigrations_DownRemovesTablesAndColumn`) fait un DROP TABLE : il
 * sauvegarde donc puis restaure les looks, associations et valeurs
 * `pdf_path` préexistants.
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

    /** Réinsère à l'identique (ids et timestamps compris) des lignes sauvegardées. */
    private function restoreRows($table, array $rows)
    {
        if (empty($rows)) {
            return;
        }
        $max_id = 0;
        foreach ($rows as $row) {
            $this->db->insert($table, $row);
            if (isset($row['id']) && (int) $row['id'] > $max_id) {
                $max_id = (int) $row['id'];
            }
        }
        if ($max_id > 0) {
            $this->db->query("ALTER TABLE `$table` AUTO_INCREMENT = " . ($max_id + 1));
        }
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
        // Une section sans association existante, pour ne pas modifier de données en place.
        $free = $this->db->query(
            "SELECT s.id FROM sections s
             LEFT JOIN vols_decouverte_look_sections vls ON vls.section_id = s.id
             WHERE vls.id IS NULL ORDER BY s.id LIMIT 1"
        )->row_array();
        if (empty($free)) {
            $this->markTestSkipped('Aucune section libre : impossible de tester la contrainte UNIQUE sans toucher aux données existantes.');
            return;
        }
        $section_id = (int) $free['id'];

        $look_id = $this->db->query(
            "INSERT INTO vols_decouverte_looks (nom, layout_json) VALUES ('Test unicité', '{}')"
        ) ? $this->db->insert_id() : null;
        $this->assertNotEmpty($look_id);

        try {
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
        } finally {
            $this->db->query("DELETE FROM vols_decouverte_look_sections WHERE section_id = $section_id AND look_id = $look_id");
            $this->db->query("DELETE FROM vols_decouverte_looks WHERE id = $look_id");
        }
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

        // Ce test DROP puis recrée les tables : sauvegarde de tout ce qui existe
        // pour le restaurer à l'identique ensuite.
        $looks_backup          = $this->db->query("SELECT * FROM vols_decouverte_looks")->result_array();
        $look_sections_backup  = $this->db->query("SELECT * FROM vols_decouverte_look_sections")->result_array();
        $pdf_paths_backup      = $this->db->query(
            "SELECT id, pdf_path, updated_at FROM vols_decouverte WHERE pdf_path IS NOT NULL"
        )->result_array();

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

            // Restauration des données préexistantes (looks d'abord : FK depuis look_sections).
            $this->restoreRows('vols_decouverte_looks', $looks_backup);
            $this->restoreRows('vols_decouverte_look_sections', $look_sections_backup);
            foreach ($pdf_paths_backup as $row) {
                $this->db->query(
                    "UPDATE vols_decouverte SET pdf_path = ?, updated_at = ? WHERE id = ?",
                    array($row['pdf_path'], $row['updated_at'], $row['id'])
                );
            }
        }
    }
}
