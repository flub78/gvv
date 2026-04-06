<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the public VD page feature.
 *
 * Covers:
 *  - Migration 101 : nb_personnes_max column on tarifs
 *  - Migration 102 : public_rate_limit table
 *  - rate_limit_helper : check_rate_limit()
 *  - vd_quota_helper   : get_vd_quota_status()
 *
 * @package tests
 * @see application/migrations/101_tarifs_nb_personnes_max.php
 * @see application/migrations/102_public_rate_limit.php
 * @see application/helpers/rate_limit_helper.php
 * @see application/helpers/vd_quota_helper.php
 */
class PaiementsEnLignePublicVdTest extends TestCase
{
    /** @var CI_DB_active_record */
    protected $db;

    // =========================================================================
    // Helpers
    // =========================================================================

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

    private function tableExists($table)
    {
        $t = $this->db->escape_str($table);
        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t'"
        )->row_array();
        return isset($row['cnt']) && (int) $row['cnt'] > 0;
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

    private function columnDefault($table, $column)
    {
        $t = $this->db->escape_str($table);
        $c = $this->db->escape_str($column);
        $row = $this->db->query(
            "SELECT COLUMN_DEFAULT FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND COLUMN_NAME = '$c'"
        )->row_array();
        return isset($row['COLUMN_DEFAULT']) ? $row['COLUMN_DEFAULT'] : null;
    }

    // =========================================================================
    // Migration 101 : nb_personnes_max
    // =========================================================================

    public function testMigration101_ColumnExists()
    {
        $this->assertTrue(
            $this->columnExists('tarifs', 'nb_personnes_max'),
            'Column nb_personnes_max should exist on tarifs after migration 101'
        );
    }

    public function testMigration101_DefaultIsOne()
    {
        $default = $this->columnDefault('tarifs', 'nb_personnes_max');
        $this->assertEquals('1', (string) $default,
            'nb_personnes_max should default to 1'
        );
    }

    public function testMigration101_Up_Down_Cycle()
    {
        if (!$this->columnExists('tarifs', 'nb_personnes_max')) {
            $this->markTestSkipped('Migration 101 not applied — skipping up/down cycle');
        }

        $migration = $this->loadMigration(
            '101_tarifs_nb_personnes_max.php',
            'Migration_Tarifs_nb_personnes_max'
        );

        // down() removes the column
        $migration->down();
        $this->assertFalse(
            $this->columnExists('tarifs', 'nb_personnes_max'),
            'Column nb_personnes_max should be absent after down()'
        );

        // up() re-adds it
        $migration->up();
        $this->assertTrue(
            $this->columnExists('tarifs', 'nb_personnes_max'),
            'Column nb_personnes_max should exist after up()'
        );
    }

    // =========================================================================
    // Migration 102 : public_rate_limit
    // =========================================================================

    public function testMigration102_TableExists()
    {
        $this->assertTrue(
            $this->tableExists('public_rate_limit'),
            'Table public_rate_limit should exist after migration 102'
        );
    }

    public function testMigration102_Columns()
    {
        if (!$this->tableExists('public_rate_limit')) {
            $this->markTestSkipped('Table public_rate_limit does not exist');
        }
        foreach (['ip', 'endpoint', 'attempts', 'window_start'] as $col) {
            $this->assertTrue(
                $this->columnExists('public_rate_limit', $col),
                "Column $col should exist on public_rate_limit"
            );
        }
    }

    public function testMigration102_Up_Down_Cycle()
    {
        if (!$this->tableExists('public_rate_limit')) {
            $this->markTestSkipped('Migration 102 not applied — skipping up/down cycle');
        }

        $migration = $this->loadMigration(
            '102_public_rate_limit.php',
            'Migration_Public_rate_limit'
        );

        $migration->down();
        $this->assertFalse(
            $this->tableExists('public_rate_limit'),
            'Table public_rate_limit should be absent after down()'
        );

        $migration->up();
        $this->assertTrue(
            $this->tableExists('public_rate_limit'),
            'Table public_rate_limit should exist after up()'
        );
    }

    // =========================================================================
    // Rate limiter : check_rate_limit()
    // =========================================================================

    /**
     * Remove test entries from public_rate_limit after each rate-limit test.
     */
    private function cleanRateLimit($endpoint)
    {
        $this->db->where('endpoint', $endpoint)->delete('public_rate_limit');
    }

    public function testRateLimit_FirstCallReturnsTrue()
    {
        if (!$this->tableExists('public_rate_limit')) {
            $this->markTestSkipped('Table public_rate_limit does not exist');
        }
        $ep = 'test_rl_first_' . uniqid();
        $result = check_rate_limit($ep, 3, 3600);
        $this->assertTrue($result, 'First call should be under the limit');
        $this->cleanRateLimit($ep);
    }

    public function testRateLimit_UnderLimitReturnsTrue()
    {
        if (!$this->tableExists('public_rate_limit')) {
            $this->markTestSkipped('Table public_rate_limit does not exist');
        }
        $ep = 'test_rl_under_' . uniqid();
        check_rate_limit($ep, 5, 3600); // 1
        check_rate_limit($ep, 5, 3600); // 2
        $result = check_rate_limit($ep, 5, 3600); // 3 — still under
        $this->assertTrue($result, 'Third call with max=5 should still be under the limit');
        $this->cleanRateLimit($ep);
    }

    public function testRateLimit_AtMaxReturnsFalse()
    {
        if (!$this->tableExists('public_rate_limit')) {
            $this->markTestSkipped('Table public_rate_limit does not exist');
        }
        $ep = 'test_rl_max_' . uniqid();
        for ($i = 0; $i < 3; $i++) {
            check_rate_limit($ep, 3, 3600);
        }
        $result = check_rate_limit($ep, 3, 3600); // 4th — exceeds max=3
        $this->assertFalse($result, 'Call beyond max should return FALSE');
        $this->cleanRateLimit($ep);
    }

    public function testRateLimit_ExpiredWindowResetsCounter()
    {
        if (!$this->tableExists('public_rate_limit')) {
            $this->markTestSkipped('Table public_rate_limit does not exist');
        }
        $ep = 'test_rl_expire_' . uniqid();

        // Insert an entry with an expired window (2 hours ago)
        $this->db->insert('public_rate_limit', [
            'ip'           => '127.0.0.1',
            'endpoint'     => $ep,
            'attempts'     => 99,
            'window_start' => date('Y-m-d H:i:s', time() - 7200),
        ]);

        $result = check_rate_limit($ep, 3, 3600);
        $this->assertTrue($result, 'Expired window should reset counter — first call of new window should pass');
        $this->cleanRateLimit($ep);
    }

    // =========================================================================
    // Quota helper : get_vd_quota_status()
    // =========================================================================

    /**
     * Section 4 (Général) — exists in all test environments.
     * We identify our test rows by a unique paiement prefix so we never
     * touch real data even if the section already has bons.
     */
    private $test_section = 4;
    private $test_paiement_prefix;

    protected function setUp(): void
    {
        $CI =& get_instance();
        $this->db = $CI->db;
        $CI->load->helper('vd_quota');
        $CI->load->helper('rate_limit');
        // Unique prefix per test run so parallel runs don't collide
        $this->test_paiement_prefix = 'phpunit-quota-' . uniqid() . '-';
    }

    /**
     * Insert a fake vols_decouverte row dated $days_ago days in the past.
     * The row is identified by a unique paiement value for cleanup.
     */
    private function insertFakeVd($days_ago = 0)
    {
        $date     = date('Y-m-d', strtotime("-{$days_ago} days"));
        $paiement = $this->test_paiement_prefix . uniqid();

        $this->db->insert('vols_decouverte', [
            'date_vente'   => $date,
            'club'         => $this->test_section,
            'product'      => 'PHPUNIT-TEST',
            'beneficiaire' => 'Test Quota PHPUnit',
            'paiement'     => $paiement,
            'participation'=> 'test',
            'saisie_par'   => 'phpunit',
            'cancelled'    => 0,
        ]);
    }

    private function cleanFakeVd()
    {
        $prefix = $this->db->escape_str($this->test_paiement_prefix);
        $this->db->query(
            "DELETE FROM vols_decouverte WHERE paiement LIKE '{$prefix}%'"
        );
    }

    /**
     * Override quota config for test_section, storing original to restore.
     */
    private $quota_original = null;

    private function setQuotaConfig($value)
    {
        $row = $this->db
            ->where('plateforme', 'helloasso')
            ->where('club', $this->test_section)
            ->where('param_key', 'vd_quota_mensuel')
            ->get('paiements_en_ligne_config')
            ->row_array();

        $this->quota_original = $row ? $row['param_value'] : null;

        if ($row) {
            $this->db
                ->where('plateforme', 'helloasso')
                ->where('club', $this->test_section)
                ->where('param_key', 'vd_quota_mensuel')
                ->update('paiements_en_ligne_config', ['param_value' => $value]);
        } else {
            $now = date('Y-m-d H:i:s');
            $this->db->insert('paiements_en_ligne_config', [
                'plateforme'  => 'helloasso',
                'club'        => $this->test_section,
                'param_key'   => 'vd_quota_mensuel',
                'param_value' => $value,
                'created_at'  => $now,
                'updated_at'  => $now,
                'created_by'  => 'phpunit',
                'updated_by'  => 'phpunit',
            ]);
        }
    }

    private function restoreQuotaConfig()
    {
        if ($this->quota_original === null) {
            // Was not present before — delete our insert
            $this->db
                ->where('plateforme', 'helloasso')
                ->where('club', $this->test_section)
                ->where('param_key', 'vd_quota_mensuel')
                ->delete('paiements_en_ligne_config');
        } else {
            $this->db
                ->where('plateforme', 'helloasso')
                ->where('club', $this->test_section)
                ->where('param_key', 'vd_quota_mensuel')
                ->update('paiements_en_ligne_config', ['param_value' => $this->quota_original]);
        }
        $this->quota_original = null;
    }

    public function testQuota_ZeroMeansUnlimited()
    {
        $this->setQuotaConfig('0');

        $status = get_vd_quota_status($this->test_section);

        $this->assertEquals(0, $status['quota'],       'quota should be 0');
        $this->assertEquals(0, $status['vendu'],       'vendu should be 0 (no query performed)');
        $this->assertFalse($status['atteint'],          'atteint should be false when quota = 0');
        $this->assertEquals(0, $status['jours_reset'], 'jours_reset should be 0');

        $this->restoreQuotaConfig();
    }

    public function testQuota_UnderLimitNotReached()
    {
        // Set quota high enough that existing real bons won't trigger it
        // We set quota = existing_count + 5 + 4 test bons — stays under
        $existing = (int) $this->db->query(
            'SELECT COUNT(*) AS cnt FROM vols_decouverte
             WHERE club = ? AND cancelled = 0
               AND date_vente >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)',
            [$this->test_section]
        )->row()->cnt;

        $quota = $existing + 10; // +4 test bons + 6 headroom
        $this->setQuotaConfig((string) $quota);

        for ($i = 0; $i < 4; $i++) {
            $this->insertFakeVd(1);
        }

        $status = get_vd_quota_status($this->test_section);

        $this->assertEquals($quota, $status['quota']);
        $this->assertEquals($existing + 4, $status['vendu']);
        $this->assertFalse($status['atteint'], 'quota not reached with ' . ($existing + 4) . '/' . $quota);
        $this->assertEquals(0, $status['jours_reset']);

        $this->cleanFakeVd();
        $this->restoreQuotaConfig();
    }

    public function testQuota_ExactlyAtLimit()
    {
        $existing = (int) $this->db->query(
            'SELECT COUNT(*) AS cnt FROM vols_decouverte
             WHERE club = ? AND cancelled = 0
               AND date_vente >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)',
            [$this->test_section]
        )->row()->cnt;

        $quota = $existing + 3;
        $this->setQuotaConfig((string) $quota);

        for ($i = 0; $i < 3; $i++) {
            $this->insertFakeVd(1);
        }

        $status = get_vd_quota_status($this->test_section);

        $this->assertEquals($quota, $status['quota']);
        $this->assertEquals($quota, $status['vendu']);
        $this->assertTrue($status['atteint'], 'quota reached with exactly ' . $quota . '/' . $quota . ' bons');
        $this->assertGreaterThan(0, $status['jours_reset']);

        $this->cleanFakeVd();
        $this->restoreQuotaConfig();
    }

    public function testQuota_JoursResetCalculation()
    {
        // Use quota = 1 and insert 1 bon that is 15 days old
        // We must ensure no real bons are in the window, so set quota high first,
        // then set quota = total_in_window to force atteint with our bon as oldest
        $this->insertFakeVd(15); // our test bon, 15 days ago

        $total = (int) $this->db->query(
            'SELECT COUNT(*) AS cnt FROM vols_decouverte
             WHERE club = ? AND cancelled = 0
               AND date_vente >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)',
            [$this->test_section]
        )->row()->cnt;

        // Set quota = total so atteint is true
        $this->setQuotaConfig((string) $total);

        $status = get_vd_quota_status($this->test_section);

        $this->assertTrue($status['atteint']);
        // jours_reset = 30 - DATEDIFF(today, oldest_in_window)
        // oldest_in_window is at most 15 days old (our test bon)
        $this->assertGreaterThan(0, $status['jours_reset']);
        $this->assertLessThanOrEqual(30, $status['jours_reset']);

        $this->cleanFakeVd();
        $this->restoreQuotaConfig();
    }

    public function testQuota_OldBonsOutsideWindowNotCounted()
    {
        $existing_in_window = (int) $this->db->query(
            'SELECT COUNT(*) AS cnt FROM vols_decouverte
             WHERE club = ? AND cancelled = 0
               AND date_vente >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)',
            [$this->test_section]
        )->row()->cnt;

        $quota = $existing_in_window + 5; // headroom — 1 test bon inside, 1 outside: still under
        $this->setQuotaConfig((string) $quota);

        $this->insertFakeVd(5);  // inside 30-day window
        $this->insertFakeVd(35); // outside window — must not be counted

        $status = get_vd_quota_status($this->test_section);

        $this->assertEquals($quota, $status['quota']);
        $this->assertEquals($existing_in_window + 1, $status['vendu'],
            'Only bons within 30-day window should be counted (1 of our 2 test bons)'
        );
        $this->assertFalse($status['atteint']);

        $this->cleanFakeVd();
        $this->restoreQuotaConfig();
    }
}
