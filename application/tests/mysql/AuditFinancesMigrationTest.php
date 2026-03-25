<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL tests for migration 092 (Lot 1 audit fields on finances/flights).
 */
class AuditFinancesMigrationTest extends TestCase
{
    /** @var RealDatabase */
    private $db;

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;

        if (!class_exists('CI_Migration')) {
            require_once BASEPATH . 'libraries/Migration.php';
        }
        require_once APPPATH . 'migrations/092_audit_finances.php';
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

    private function runMigrationUp()
    {
        $migration = new Migration_Audit_finances();
        $result = $migration->up();
        $this->assertTrue($result, 'Migration 092 up() should succeed');
    }

    public function testMigration092AddsExpectedAuditColumns()
    {
        $this->runMigrationUp();

        $tables = array('achats', 'ecritures', 'comptes', 'tickets', 'tarifs', 'volsp', 'volsa');
        foreach ($tables as $table) {
            $this->assertTrue($this->columnExists($table, 'created_by'), "$table.created_by should exist");
            $this->assertTrue($this->columnExists($table, 'created_at'), "$table.created_at should exist");
            $this->assertTrue($this->columnExists($table, 'updated_by'), "$table.updated_by should exist");
            $this->assertTrue($this->columnExists($table, 'updated_at'), "$table.updated_at should exist");
        }

        $this->assertTrue($this->columnExists('vols_decouverte', 'created_by'), 'vols_decouverte.created_by should exist');
        $this->assertTrue($this->columnExists('vols_decouverte', 'updated_by'), 'vols_decouverte.updated_by should exist');
        $this->assertTrue($this->columnExists('vols_decouverte', 'created_at'), 'vols_decouverte.created_at should still exist');
        $this->assertTrue($this->columnExists('vols_decouverte', 'updated_at'), 'vols_decouverte.updated_at should still exist');
    }

    public function testMigration092BackfillsLegacyFields()
    {
        // Ensure columns exist before inserting fixtures with explicit NULL audit values.
        $this->runMigrationUp();

        $suffix = (string) time();

        $tarif = $this->db->query("SELECT reference FROM tarifs LIMIT 1")->row_array();
        if (empty($tarif['reference'])) {
            $this->markTestSkipped('No tariff reference available for achats fixture');
        }

        $compte = $this->db->query("SELECT id FROM comptes LIMIT 1")->row_array();
        if (empty($compte['id'])) {
            $this->markTestSkipped('No account available for ecritures fixture');
        }

        $this->db->query(
            "INSERT INTO achats (date, produit, quantite, prix, description, pilote, facture, saisie_par, club, machine, vol_planeur, vol_avion, mvt_pompe, num_cheque, created_by, created_at, updated_by, updated_at)
               VALUES ('2026-03-25', '" . $this->db->escape_str($tarif['reference']) . "', 1.00, 10.00, 'lot1 audit backfill achats $suffix', 'test_user', 0, 'legacy_creator', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL)"
        );

        $this->db->query(
            "INSERT INTO ecritures (annee_exercise, date_creation, date_op, compte1, compte2, montant, description, type, num_cheque, saisie_par, gel, club, achat, quantite, prix, categorie, created_by, created_at, updated_by, updated_at)
               VALUES (2026, '2026-03-24', '2026-03-25', " . (int) $compte['id'] . ", " . (int) $compte['id'] . ", 10.00, 'lot1 audit backfill ecritures $suffix', NULL, NULL, 'legacy_writer', 0, 1, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL)"
        );

        $migration = new Migration_Audit_finances();
        $this->assertTrue($migration->up(), 'Migration 092 should be idempotent and succeed when rerun');

        $achats = $this->db->query(
            "SELECT created_by, created_at, updated_by, updated_at
             FROM achats
               WHERE description = 'lot1 audit backfill achats $suffix'
             ORDER BY id DESC LIMIT 1"
        )->row_array();

        $this->assertEquals('legacy_creator', $achats['created_by']);
        $this->assertNotEmpty($achats['created_at']);
        $this->assertEquals('legacy_creator', $achats['updated_by']);
        $this->assertNotEmpty($achats['updated_at']);

        $ecritures = $this->db->query(
            "SELECT created_by, created_at, updated_by, updated_at
             FROM ecritures
               WHERE description = 'lot1 audit backfill ecritures $suffix'
             ORDER BY id DESC LIMIT 1"
        )->row_array();

        $this->assertEquals('legacy_writer', $ecritures['created_by']);
        $this->assertStringStartsWith('2026-03-24', $ecritures['created_at']);
        $this->assertEquals('legacy_writer', $ecritures['updated_by']);
        $this->assertNotEmpty($ecritures['updated_at']);

        // Cleanup explicit fixtures
        $this->db->query("DELETE FROM achats WHERE description = 'lot1 audit backfill achats $suffix'");
        $this->db->query("DELETE FROM ecritures WHERE description = 'lot1 audit backfill ecritures $suffix'");
    }
}
