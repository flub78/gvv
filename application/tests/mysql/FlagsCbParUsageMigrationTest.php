<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit Tests — Migration 100 : flags CB par usage
 *
 * Teste :
 * - up() ajoute has_vd_par_cb et has_approvisio_par_cb dans sections
 * - up() initialise les flags à 1 pour les sections avec enabled = '1' dans paiements_en_ligne_config
 * - down() supprime les deux colonnes
 */
class FlagsCbParUsageMigrationTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;

        if (!class_exists('CI_Migration')) {
            require_once BASEPATH . 'libraries/Migration.php';
        }
        require_once APPPATH . 'migrations/100_flags_cb_par_usage.php';

        // État propre avant le test : supprimer les colonnes si elles existent déjà
        if ($this->columnExists('sections', 'has_vd_par_cb')) {
            $this->db->query("ALTER TABLE `sections` DROP COLUMN `has_vd_par_cb`");
        }
        if ($this->columnExists('sections', 'has_approvisio_par_cb')) {
            $this->db->query("ALTER TABLE `sections` DROP COLUMN `has_approvisio_par_cb`");
        }
    }

    protected function tearDown(): void
    {
        // Restaurer l'état : remettre les colonnes si elles ont été supprimées par down()
        if (!$this->columnExists('sections', 'has_vd_par_cb')) {
            $this->db->query(
                "ALTER TABLE `sections` ADD COLUMN `has_vd_par_cb` TINYINT(1) NOT NULL DEFAULT 0 AFTER `bar_account_id`"
            );
        }
        if (!$this->columnExists('sections', 'has_approvisio_par_cb')) {
            $this->db->query(
                "ALTER TABLE `sections` ADD COLUMN `has_approvisio_par_cb` TINYINT(1) NOT NULL DEFAULT 0 AFTER `has_vd_par_cb`"
            );
        }

        // Réappliquer la data migration pour laisser la base dans un état cohérent
        $this->db->query("
            UPDATE sections s
            INNER JOIN paiements_en_ligne_config c ON c.club = s.id
            SET s.has_vd_par_cb = 1, s.has_approvisio_par_cb = 1
            WHERE c.plateforme = 'helloasso'
              AND c.param_key = 'enabled'
              AND c.param_value = '1'
        ");
    }

    private function columnExists($table, $column)
    {
        $t = $this->db->escape_str($table);
        $c = $this->db->escape_str($column);
        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND COLUMN_NAME = '$c'"
        )->row_array();
        return isset($row['cnt']) && (int) $row['cnt'] > 0;
    }

    private function getColumnMeta($table, $column)
    {
        $t = $this->db->escape_str($table);
        $c = $this->db->escape_str($column);
        return $this->db->query(
            "SELECT COLUMN_DEFAULT, IS_NULLABLE, DATA_TYPE
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND COLUMN_NAME = '$c'"
        )->row_array();
    }

    // ── up() ─────────────────────────────────────────────────────────────────

    public function testUpAddsHasVdParCbColumn()
    {
        $this->assertFalse($this->columnExists('sections', 'has_vd_par_cb'),
            'La colonne ne doit pas exister avant up()');

        $migration = new Migration_Flags_Cb_Par_Usage();
        $result = $migration->up();

        $this->assertTrue($result, 'up() doit retourner true');
        $this->assertTrue($this->columnExists('sections', 'has_vd_par_cb'),
            'La colonne has_vd_par_cb doit exister après up()');
    }

    public function testUpAddsHasApprovisioParCbColumn()
    {
        $migration = new Migration_Flags_Cb_Par_Usage();
        $migration->up();

        $this->assertTrue($this->columnExists('sections', 'has_approvisio_par_cb'),
            'La colonne has_approvisio_par_cb doit exister après up()');
    }

    public function testHasVdParCbDefaultIsZero()
    {
        $migration = new Migration_Flags_Cb_Par_Usage();
        $migration->up();

        $col = $this->getColumnMeta('sections', 'has_vd_par_cb');
        $this->assertEquals('0', $col['COLUMN_DEFAULT'], 'DEFAULT has_vd_par_cb doit être 0');
        $this->assertEquals('tinyint', $col['DATA_TYPE']);
        $this->assertEquals('NO', $col['IS_NULLABLE']);
    }

    public function testHasApprovisioParCbDefaultIsZero()
    {
        $migration = new Migration_Flags_Cb_Par_Usage();
        $migration->up();

        $col = $this->getColumnMeta('sections', 'has_approvisio_par_cb');
        $this->assertEquals('0', $col['COLUMN_DEFAULT'], 'DEFAULT has_approvisio_par_cb doit être 0');
        $this->assertEquals('tinyint', $col['DATA_TYPE']);
        $this->assertEquals('NO', $col['IS_NULLABLE']);
    }

    public function testDataMigrationSetsFlags()
    {
        // Vérifier que la data migration a activé les flags pour les sections
        // qui avaient enabled = '1' dans paiements_en_ligne_config.
        // Si aucune section n'est configurée, le test vérifie simplement
        // que les colonnes ont été créées sans erreur.
        $migration = new Migration_Flags_Cb_Par_Usage();
        $migration->up();

        // Récupérer les sections attendues (celles avec enabled = '1')
        $expected = $this->db->query(
            "SELECT DISTINCT club FROM paiements_en_ligne_config
             WHERE plateforme = 'helloasso' AND param_key = 'enabled' AND param_value = '1'"
        )->result_array();

        foreach ($expected as $row) {
            $section = $this->db->query(
                "SELECT has_vd_par_cb, has_approvisio_par_cb FROM sections WHERE id = ?",
                array((int) $row['club'])
            )->row_array();

            $this->assertNotEmpty($section,
                "Section {$row['club']} doit exister dans sections");
            $this->assertEquals(1, (int) $section['has_vd_par_cb'],
                "has_vd_par_cb doit être 1 pour section {$row['club']} (avait enabled=1)");
            $this->assertEquals(1, (int) $section['has_approvisio_par_cb'],
                "has_approvisio_par_cb doit être 1 pour section {$row['club']} (avait enabled=1)");
        }
    }

    public function testDataMigrationDoesNotAffectDisabledSections()
    {
        // Les sections avec enabled = '0' (ou sans config) doivent garder les flags à 0
        $migration = new Migration_Flags_Cb_Par_Usage();
        $migration->up();

        // Sections explicitement désactivées (peut être vide sur certaines BDD de test)
        $disabled = $this->db->query(
            "SELECT DISTINCT club FROM paiements_en_ligne_config
             WHERE plateforme = 'helloasso' AND param_key = 'enabled' AND param_value = '0'"
        )->result_array();

        if (empty($disabled)) {
            $this->markTestSkipped('Aucune section avec enabled=0 dans paiements_en_ligne_config — test non applicable sur cette base.');
        }

        foreach ($disabled as $row) {
            // Vérifier que cette section n'était pas aussi dans enabled = '1'
            $is_enabled = $this->db->query(
                "SELECT COUNT(*) AS cnt FROM paiements_en_ligne_config
                 WHERE plateforme = 'helloasso' AND param_key = 'enabled' AND param_value = '1'
                 AND club = ?",
                array((int) $row['club'])
            )->row_array();

            if ((int) $is_enabled['cnt'] === 0) {
                $section = $this->db->query(
                    "SELECT has_vd_par_cb, has_approvisio_par_cb FROM sections WHERE id = ?",
                    array((int) $row['club'])
                )->row_array();

                if ($section) {
                    $this->assertEquals(0, (int) $section['has_vd_par_cb'],
                        "has_vd_par_cb doit rester 0 pour section {$row['club']} (avait enabled=0)");
                    $this->assertEquals(0, (int) $section['has_approvisio_par_cb'],
                        "has_approvisio_par_cb doit rester 0 pour section {$row['club']} (avait enabled=0)");
                }
            }
        }
    }

    // ── down() ───────────────────────────────────────────────────────────────

    public function testDownRemovesColumns()
    {
        $migration = new Migration_Flags_Cb_Par_Usage();
        $migration->up();
        $this->assertTrue($this->columnExists('sections', 'has_vd_par_cb'));
        $this->assertTrue($this->columnExists('sections', 'has_approvisio_par_cb'));

        $result = $migration->down();

        $this->assertTrue($result, 'down() doit retourner true');
        $this->assertFalse($this->columnExists('sections', 'has_vd_par_cb'),
            'has_vd_par_cb doit être supprimée après down()');
        $this->assertFalse($this->columnExists('sections', 'has_approvisio_par_cb'),
            'has_approvisio_par_cb doit être supprimée après down()');
    }
}
