<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL tests for migration 142 (Lot 6, doc/plans/journalisation_crud_plan.md):
 * backfill of created_by/created_at values corrupted by the isset(FALSE)
 * regression (Lot 0/1, see plan §2.3).
 *
 * Like AuditFinancesMigrationTest (migration 092), this runs the migration
 * directly against the real database rather than inside a rollback
 * transaction: the migration is idempotent (every UPDATE is scoped to rows
 * still carrying a bad placeholder value), so re-running it is harmless and
 * this test doubles as the actual backfill run for the dev/test database.
 */
class BackfillAuditHistoriqueMigrationTest extends TestCase
{
    /** @var RealDatabase */
    private $db;

    private $zero_date = '0000-00-00 00:00:00';

    private $tables = array('achats', 'comptes', 'ecritures', 'tarifs', 'tickets', 'volsa', 'volsp', 'vols_decouverte');

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;

        if (!class_exists('CI_Migration')) {
            require_once BASEPATH . 'libraries/Migration.php';
        }
        require_once APPPATH . 'migrations/142_backfill_audit_historique.php';
    }

    private function runMigrationUp()
    {
        $migration = new Migration_Backfill_audit_historique();
        $result = $migration->up();
        $this->assertTrue($result, 'Migration 142 up() should succeed');
    }

    private function countFixableCreatedBy($table)
    {
        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM `$table`
             WHERE (created_by IS NULL OR created_by = '' OR created_by = '0')
               AND saisie_par IS NOT NULL AND saisie_par <> '' AND saisie_par <> '0'"
        )->row_array();
        return (int) $row['cnt'];
    }

    public function testMigration142BackfillsCreatedByFromSaisieParOnAllTables()
    {
        $this->runMigrationUp();

        foreach ($this->tables as $table) {
            $this->assertEquals(
                0,
                $this->countFixableCreatedBy($table),
                "$table should have no row left where saisie_par is known but created_by is still a placeholder"
            );
        }
    }

    public function testMigration142BackfillsCreatedAtFromDateProxyColumns()
    {
        $date_proxy_by_table = array(
            'achats' => 'date',
            'ecritures' => 'date_creation',
            'tarifs' => 'date',
            'tickets' => 'date',
            'vols_decouverte' => 'date_vente',
            'volsp' => 'vpdate',
        );

        $this->runMigrationUp();

        foreach ($date_proxy_by_table as $table => $date_column) {
            // A zero-date ('0000-00-00') in the proxy column itself means "no
            // meaningful date" in this app (e.g. some tarifs rows) and is just as
            // unusable as NULL — those rows are correctly left unfixed, not a bug.
            $row = $this->db->query(
                "SELECT COUNT(*) AS cnt FROM `$table`
                 WHERE (created_at IS NULL OR created_at = '{$this->zero_date}')
                   AND $date_column IS NOT NULL AND $date_column <> '0000-00-00'"
            )->row_array();

            $this->assertEquals(
                0,
                (int) $row['cnt'],
                "$table should have no row left with a bad created_at when $date_column is a usable date"
            );
        }
    }

    public function testMigration142ApproximatesVolsaCreatedAtFromVadateAndVahfin()
    {
        // Use a dedicated synthetic row with known inputs rather than relying on
        // whichever real row happens to still be unfixed: once the migration has
        // run once against the dev database, there is nothing left to observe on
        // real data, and this check must remain meaningful on every future run.
        $pilot = $this->db->select('mlogin')->from('membres')->where('actif', 1)->limit(1)->get()->row_array();
        $machine = $this->db->select('macimmat')->from('machinesa')->where('actif', 1)->limit(1)->get()->row_array();
        $this->assertNotEmpty($pilot, 'Need an active pilot for the synthetic row');
        $this->assertNotEmpty($machine, 'Need an active machine for the synthetic row');

        $vadate = '2026-01-15';
        $vahfin = 10.54; // 10:32:24 in decimal-hours

        $this->db->query(
            "INSERT INTO volsa
                (vadate, vapilid, vamacid, vacdeb, vacfin, vaduree, vaobs, vadc, vacategorie,
                 saisie_par, vaatt, vahdeb, vahfin, created_by, created_at)
             VALUES (?, ?, ?, 1, 2, 1, 'Lot6 migration test', 0, 0, ?, 1, 10, ?, '0', '{$this->zero_date}')",
            array($vadate, $pilot['mlogin'], $machine['macimmat'], $pilot['mlogin'], $vahfin)
        );
        $vaid = $this->db->insert_id();

        try {
            $hours = floor($vahfin);
            $seconds_from_midnight = $hours * 3600 + round(($vahfin - $hours) * 3600);
            $expected = date('Y-m-d H:i:s', strtotime($vadate) + $seconds_from_midnight + 20 * 60);

            $this->runMigrationUp();

            $after = $this->db->query('SELECT created_at, created_by FROM volsa WHERE vaid = ' . (int) $vaid)->row_array();

            $this->assertEquals($expected, $after['created_at'], 'volsa.created_at should be vadate + vahfin (converted) + 20 minutes');
            $this->assertEquals($pilot['mlogin'], $after['created_by'], 'volsa.created_by should be backfilled from saisie_par');
        } finally {
            $this->db->query('DELETE FROM volsa WHERE vaid = ' . (int) $vaid);
        }
    }

    public function testMigration142DoesNotFabricateComptesCreatedAt()
    {
        // comptes has no usable business date column: rows without saisie_par
        // must be left untouched rather than given an invented timestamp.
        $before = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM comptes
             WHERE (created_at IS NULL OR created_at = '{$this->zero_date}')
               AND (saisie_par IS NULL OR saisie_par = '' OR saisie_par = '0')"
        )->row_array();

        if ((int) $before['cnt'] === 0) {
            $this->markTestSkipped('No comptes row without saisie_par to verify non-fabrication on');
        }

        $this->runMigrationUp();

        $after = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM comptes
             WHERE (created_at IS NULL OR created_at = '{$this->zero_date}')
               AND (saisie_par IS NULL OR saisie_par = '' OR saisie_par = '0')"
        )->row_array();

        $this->assertEquals(
            (int) $before['cnt'],
            (int) $after['cnt'],
            'comptes rows without saisie_par must be left with no fabricated created_at'
        );
    }

    public function testMigration142IsIdempotent()
    {
        $this->runMigrationUp();

        $snapshot = array();
        foreach ($this->tables as $table) {
            $snapshot[$table] = $this->db->query(
                "SELECT COUNT(*) AS cnt FROM `$table`
                 WHERE created_by = '0' OR created_at = '{$this->zero_date}'"
            )->row_array();
        }

        $migration = new Migration_Backfill_audit_historique();
        $this->assertTrue($migration->up(), 'Re-running migration 142 should still succeed');

        foreach ($this->tables as $table) {
            $after = $this->db->query(
                "SELECT COUNT(*) AS cnt FROM `$table`
                 WHERE created_by = '0' OR created_at = '{$this->zero_date}'"
            )->row_array();
            $this->assertEquals(
                $snapshot[$table]['cnt'],
                $after['cnt'],
                "$table placeholder count should be unchanged by a second run"
            );
        }
    }

    public function testMigration142DownIsNoOp()
    {
        $migration = new Migration_Backfill_audit_historique();
        $this->assertTrue($migration->down(), 'Migration 142 down() is a documented no-op for this data-only migration');
    }
}
