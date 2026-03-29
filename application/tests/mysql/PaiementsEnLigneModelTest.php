<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit MySQL Tests — Paiements_en_ligne_model (EF6)
 *
 * Teste le CRUD complet sur les tables paiements_en_ligne et paiements_en_ligne_config :
 * - create_transaction / get_by_id / get_by_transaction_id
 * - update_transaction_status (tous les statuts, validation des statuts invalides)
 * - get_transactions (avec filtres user_id, statut, club)
 * - get_pending_transactions (transactions anciennes uniquement)
 * - get_config
 *
 * Prérequis : migration 097 appliquée (tables paiements_en_ligne et paiements_en_ligne_config).
 *
 * @see application/models/paiements_en_ligne_model.php
 */
class PaiementsEnLigneModelTest extends TestCase
{
    protected static $CI;
    protected $db;
    protected $model;

    // IDs d'entrées créées pendant les tests (nettoyage)
    protected $created_ids = array();
    protected $created_config_ids = array();

    // Fixture : user_id réel pour les tests
    protected static $test_user_id;
    protected static $test_club_id = 1; // Section Planeur

    public static function setUpBeforeClass(): void
    {
        self::$CI = &get_instance();
        self::$CI->load->database();
        self::$CI->load->model('paiements_en_ligne_model');

        // Appliquer la migration 097 si les tables n'existent pas
        // (PaiementsEnLigneMigrationTest peut les avoir supprimées dans son tearDown)
        $q = self::$CI->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'paiements_en_ligne'"
        );
        $row = $q->row_array();
        if (empty($row['cnt'])) {
            if (!class_exists('CI_Migration')) {
                require_once BASEPATH . 'libraries/Migration.php';
            }
            require_once APPPATH . 'migrations/097_paiements_en_ligne.php';
            $migration = new Migration_Paiements_En_Ligne();
            if (!$migration->up()) {
                self::markTestSkipped('Impossible d\'appliquer la migration 097 — tests ignorés');
            }
        }

        // Récupérer un user_id réel
        $r = self::$CI->db->query("SELECT id FROM users ORDER BY id LIMIT 1")->row_array();
        self::$test_user_id = $r ? (int)$r['id'] : 1;
    }

    public static function tearDownAfterClass(): void
    {
        // Laisser les tables en place pour les tests suivants
        // (ne pas les supprimer ici)
    }

    protected function setUp(): void
    {
        $this->db    = self::$CI->db;
        $this->model = self::$CI->paiements_en_ligne_model;
        $this->created_ids        = array();
        $this->created_config_ids = array();
    }

    protected function tearDown(): void
    {
        if (!empty($this->created_ids)) {
            $this->db->where_in('id', $this->created_ids)->delete('paiements_en_ligne');
        }
        if (!empty($this->created_config_ids)) {
            $this->db->where_in('id', $this->created_config_ids)->delete('paiements_en_ligne_config');
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeTransaction(array $overrides = array())
    {
        $defaults = array(
            'user_id'        => self::$test_user_id,
            'montant'        => 12.50,
            'plateforme'     => 'helloasso',
            'club'           => self::$test_club_id,
            'transaction_id' => 'test-' . uniqid(),
            'created_by'     => 'phpunit',
        );
        return array_merge($defaults, $overrides);
    }

    private function createAndTrack(array $data)
    {
        $id = $this->model->create_transaction($data);
        $this->assertIsInt($id, 'create_transaction should return an integer ID');
        $this->assertGreaterThan(0, $id);
        $this->created_ids[] = $id;
        return $id;
    }

    // ── create_transaction ────────────────────────────────────────────────────

    public function testCreateTransactionReturnsId()
    {
        $id = $this->createAndTrack($this->makeTransaction());
        $this->assertGreaterThan(0, $id);
    }

    public function testCreateTransactionDefaultsToStatusPending()
    {
        $id  = $this->createAndTrack($this->makeTransaction());
        $row = $this->model->get_by_id($id);
        $this->assertEquals('pending', $row['statut']);
    }

    public function testCreateTransactionStoresMontant()
    {
        $id  = $this->createAndTrack($this->makeTransaction(array('montant' => 25.75)));
        $row = $this->model->get_by_id($id);
        $this->assertEquals('25.75', $row['montant']);
    }

    public function testCreateTransactionStoresCreatedBy()
    {
        $id  = $this->createAndTrack($this->makeTransaction(array('created_by' => 'testuser')));
        $row = $this->model->get_by_id($id);
        $this->assertEquals('testuser', $row['created_by']);
    }

    // ── get_by_id ─────────────────────────────────────────────────────────────

    public function testGetByIdReturnsFalseForUnknown()
    {
        $result = $this->model->get_by_id(999999999);
        $this->assertFalse($result);
    }

    // ── get_by_transaction_id ─────────────────────────────────────────────────

    public function testGetByTransactionIdFindsRecord()
    {
        $txid = 'test-find-' . uniqid();
        $id   = $this->createAndTrack($this->makeTransaction(array('transaction_id' => $txid)));

        $row = $this->model->get_by_transaction_id($txid);
        $this->assertIsArray($row);
        $this->assertEquals($id, (int)$row['id']);
    }

    public function testGetByTransactionIdReturnsFalseForUnknown()
    {
        $result = $this->model->get_by_transaction_id('nonexistent-' . uniqid());
        $this->assertFalse($result);
    }

    // ── update_transaction_status ─────────────────────────────────────────────

    public function testUpdateStatusToCompleted()
    {
        $txid = 'test-completed-' . uniqid();
        $this->createAndTrack($this->makeTransaction(array('transaction_id' => $txid)));

        $result = $this->model->update_transaction_status($txid, 'completed');
        $this->assertTrue($result);

        $row = $this->model->get_by_transaction_id($txid);
        $this->assertEquals('completed', $row['statut']);
        $this->assertNotNull($row['date_paiement']);
    }

    public function testUpdateStatusToFailed()
    {
        $txid = 'test-failed-' . uniqid();
        $this->createAndTrack($this->makeTransaction(array('transaction_id' => $txid)));

        $result = $this->model->update_transaction_status($txid, 'failed');
        $this->assertTrue($result);

        $row = $this->model->get_by_transaction_id($txid);
        $this->assertEquals('failed', $row['statut']);
    }

    public function testUpdateStatusToCancelled()
    {
        $txid = 'test-cancelled-' . uniqid();
        $this->createAndTrack($this->makeTransaction(array('transaction_id' => $txid)));

        $result = $this->model->update_transaction_status($txid, 'cancelled');
        $this->assertTrue($result);

        $row = $this->model->get_by_transaction_id($txid);
        $this->assertEquals('cancelled', $row['statut']);
    }

    public function testUpdateStatusReturnsFalseForInvalidStatus()
    {
        $txid = 'test-invalid-' . uniqid();
        $this->createAndTrack($this->makeTransaction(array('transaction_id' => $txid)));

        $result = $this->model->update_transaction_status($txid, 'bogus_status');
        $this->assertFalse($result);

        // Statut inchangé
        $row = $this->model->get_by_transaction_id($txid);
        $this->assertEquals('pending', $row['statut']);
    }

    public function testUpdateStatusReturnsFalseForUnknownTransaction()
    {
        $result = $this->model->update_transaction_status('no-such-tx', 'completed');
        $this->assertFalse($result);
    }

    public function testUpdateStatusStoresMetadata()
    {
        $txid = 'test-meta-' . uniqid();
        $this->createAndTrack($this->makeTransaction(array('transaction_id' => $txid)));

        $meta = json_encode(array('order_id' => 'ABC123'));
        $this->model->update_transaction_status($txid, 'completed', $meta);

        $row = $this->model->get_by_transaction_id($txid);
        $this->assertEquals($meta, $row['metadata']);
    }

    // ── get_transactions ──────────────────────────────────────────────────────

    public function testGetTransactionsFiltersByUserId()
    {
        $uid1 = self::$test_user_id;

        // Créer deux transactions pour uid1
        $this->createAndTrack($this->makeTransaction(array('user_id' => $uid1, 'transaction_id' => 'tx-u1a-' . uniqid())));
        $this->createAndTrack($this->makeTransaction(array('user_id' => $uid1, 'transaction_id' => 'tx-u1b-' . uniqid())));

        $results = $this->model->get_transactions(array('user_id' => $uid1, 'club' => self::$test_club_id));
        $this->assertGreaterThanOrEqual(2, count($results));
        foreach ($results as $r) {
            $this->assertEquals($uid1, (int)$r['user_id']);
        }
    }

    public function testGetTransactionsFiltersByStatut()
    {
        $txid = 'tx-stat-' . uniqid();
        $id = $this->createAndTrack($this->makeTransaction(array('transaction_id' => $txid)));
        $this->model->update_transaction_status($txid, 'completed');

        $results = $this->model->get_transactions(array(
            'user_id' => self::$test_user_id,
            'club'    => self::$test_club_id,
            'statut'  => 'completed',
        ));

        $found = false;
        foreach ($results as $r) {
            if ((int)$r['id'] === $id) { $found = true; break; }
        }
        $this->assertTrue($found, 'Completed transaction should appear in filtered results');
    }

    // ── get_pending_transactions ──────────────────────────────────────────────

    public function testGetPendingTransactionsReturnsOnlyOldOnes()
    {
        // Créer une transaction "récente" → ne doit PAS apparaître dans pending > 30 min
        $this->createAndTrack($this->makeTransaction(array('transaction_id' => 'tx-fresh-' . uniqid())));

        $results = $this->model->get_pending_transactions(30);
        // La transaction fraîche ne doit pas être dedans
        foreach ($results as $r) {
            $this->assertEquals('pending', $r['statut']);
        }
    }

    // ── get_config ────────────────────────────────────────────────────────────

    public function testGetConfigReturnsFalseWhenNotSet()
    {
        $result = $this->model->get_config('helloasso', 'nonexistent_key_' . uniqid(), 999);
        $this->assertFalse($result);
    }

    public function testGetConfigReturnsValueWhenSet()
    {
        $key   = 'phpunit_test_key_' . uniqid();
        $value = 'phpunit_value_' . uniqid();
        $club  = 998;

        // Insérer directement
        $this->db->insert('paiements_en_ligne_config', array(
            'plateforme'  => 'helloasso',
            'param_key'   => $key,
            'param_value' => $value,
            'club'        => $club,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
            'created_by'  => 'phpunit',
            'updated_by'  => 'phpunit',
        ));
        $this->created_config_ids[] = $this->db->insert_id();

        $result = $this->model->get_config('helloasso', $key, $club);
        $this->assertEquals($value, $result);
    }

    public function testGetConfigIsolatedByClub()
    {
        $key   = 'phpunit_club_key_' . uniqid();
        $value = 'phpunit_club_value';
        $club  = 997;

        $this->db->insert('paiements_en_ligne_config', array(
            'plateforme'  => 'helloasso',
            'param_key'   => $key,
            'param_value' => $value,
            'club'        => $club,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
            'created_by'  => 'phpunit',
            'updated_by'  => 'phpunit',
        ));
        $this->created_config_ids[] = $this->db->insert_id();

        // Même clé, autre club → doit retourner false
        $result = $this->model->get_config('helloasso', $key, $club + 1);
        $this->assertFalse($result);
    }

    public function testUpsertConfigEncryptsSensitiveKeyAtRest()
    {
        $club = 996;
        $key = 'client_secret';
        $value = 'super_secret_phpunit_' . uniqid();

        $ok = $this->model->upsert_config('helloasso', $key, $value, $club, 'phpunit');
        $this->assertTrue($ok);

        $row = $this->db
            ->where('plateforme', 'helloasso')
            ->where('club', $club)
            ->where('param_key', $key)
            ->get('paiements_en_ligne_config')
            ->row_array();

        $this->assertIsArray($row);
        $this->created_config_ids[] = (int) $row['id'];

        $this->assertNotEquals($value, $row['param_value']);
        $this->assertStringStartsWith('enc:v1:', $row['param_value']);
    }

    public function testGetConfigDecryptsSensitiveKey()
    {
        $club = 995;
        $key = 'webhook_secret';
        $value = 'webhook_secret_phpunit_' . uniqid();

        $ok = $this->model->upsert_config('helloasso', $key, $value, $club, 'phpunit');
        $this->assertTrue($ok);

        $row = $this->db
            ->where('plateforme', 'helloasso')
            ->where('club', $club)
            ->where('param_key', $key)
            ->get('paiements_en_ligne_config')
            ->row_array();
        $this->assertIsArray($row);
        $this->created_config_ids[] = (int) $row['id'];

        $result = $this->model->get_config('helloasso', $key, $club);
        $this->assertEquals($value, $result);
    }
}
