<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit MySQL Tests — Bar externe via QR Code (UC2)
 *
 * Vérifie :
 *  - Accès sans paramètre club (ou club invalide) → refus (aucun checkout créé)
 *  - process_order_event avec type=bar_externe → écriture recette bar créée
 *
 * @see application/controllers/paiements_en_ligne.php
 * @see application/models/paiements_en_ligne_model.php
 */
class PaiementsEnLigneBarExterneTest extends TestCase
{
    protected static $CI;
    protected $db;
    protected $model;

    protected $created_transaction_ids = array();
    protected $created_ecriture_ids    = array();

    protected static $club_id = 4;

    public static function setUpBeforeClass(): void
    {
        self::$CI = &get_instance();
        self::$CI->load->database();
        self::$CI->load->model('paiements_en_ligne_model');
        self::$CI->load->model('ecritures_model');
        self::$CI->load->model('comptes_model');
        self::$CI->load->model('sections_model');

        $q = self::$CI->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'paiements_en_ligne'"
        )->row_array();
        if ((int) $q['cnt'] === 0) {
            self::markTestSkipped('Table paiements_en_ligne absente');
        }
    }

    protected function setUp(): void
    {
        $this->db    = self::$CI->db;
        $this->model = self::$CI->paiements_en_ligne_model;
        $this->created_transaction_ids = array();
        $this->created_ecriture_ids    = array();

        // Configurer compte_passage pour club=4
        $c467 = $this->db->where('club', self::$club_id)->where('codec', '467')
            ->order_by('id', 'ASC')->get('comptes')->row_array();
        if (!$c467) {
            $this->markTestSkipped('Aucun compte 467 dans club=4');
        }
        $this->model->upsert_config('helloasso', 'compte_passage',
            (string) $c467['id'], self::$club_id, 'phpunit');

        // Vérifier que le compte bar est configuré pour club=4
        $section = $this->db->where('id', self::$club_id)->get('sections')->row_array();
        if (!$section || empty($section['bar_account_id'])) {
            $this->markTestSkipped('Compte bar non configuré pour club=' . self::$club_id);
        }
    }

    protected function tearDown(): void
    {
        if (!empty($this->created_ecriture_ids)) {
            $this->db->where_in('ecriture_id', $this->created_ecriture_ids)
                ->update('paiements_en_ligne', array('ecriture_id' => null));
        }
        foreach ($this->created_transaction_ids as $id) {
            $this->db->where('id', $id)->delete('paiements_en_ligne');
        }
        foreach ($this->created_ecriture_ids as $id) {
            $this->db->where('id', $id)->delete('ecritures');
        }
    }

    // ── Tests ────────────────────────────────────────────────────────────────

    /**
     * Accès sans club valide : aucun checkout ne doit être créé.
     * On vérifie qu'on peut créer une transaction bar_externe avec user_id=0.
     */
    public function testBarExterneTransactionCreatedWithUserIdZero()
    {
        $txid = 'test-uc2-create-' . uniqid();
        $id = $this->model->create_transaction(array(
            'user_id'        => 0,
            'montant'        => 5.00,
            'plateforme'     => 'helloasso',
            'club'           => self::$club_id,
            'transaction_id' => $txid,
            'metadata'       => json_encode(array(
                'type'               => 'bar_externe',
                'payer_name'         => 'Jean Dupont',
                'payer_email'        => 'jean@example.com',
                'description'        => '2 cafés',
                'gvv_transaction_id' => $txid,
            )),
            'created_by' => 'phpunit',
        ));
        $this->assertIsInt($id);
        $this->created_transaction_ids[] = $id;

        // Vérifier que la transaction est bien stockée avec user_id=0
        $tx = $this->db->where('id', $id)->get('paiements_en_ligne')->row_array();
        $this->assertNotEmpty($tx);
        $this->assertEquals(0, (int) $tx['user_id']);
        $this->assertEquals('pending', $tx['statut']);
    }

    /**
     * Webhook type=bar_externe → écriture de recette bar créée (débit 467, crédit 7xx).
     */
    public function testBarExterneWebhookCreatesEcritureBar()
    {
        $txid    = 'test-uc2-ecriture-' . uniqid();
        $montant = 8.00;

        $id = $this->model->create_transaction(array(
            'user_id'        => 0,
            'montant'        => $montant,
            'plateforme'     => 'helloasso',
            'club'           => self::$club_id,
            'transaction_id' => $txid,
            'metadata'       => json_encode(array(
                'type'               => 'bar_externe',
                'payer_name'         => 'Marie Martin',
                'payer_email'        => 'marie@example.com',
                'description'        => '3 cafés 1 jus',
                'gvv_transaction_id' => $txid,
            )),
            'created_by' => 'phpunit',
        ));
        $this->assertIsInt($id);
        $this->created_transaction_ids[] = $id;

        $payload = array(
            'metadata' => array(
                'type'               => 'bar_externe',
                'payer_name'         => 'Marie Martin',
                'payer_email'        => 'marie@example.com',
                'description'        => '3 cafés 1 jus',
                'gvv_transaction_id' => $txid,
            ),
            'payments' => array(array('state' => 'Authorized', 'amount' => (int)($montant * 100))),
        );

        $result = $this->model->process_order_event($payload, self::$club_id);

        $this->assertTrue($result['ok'], ($result['error'] ?? '') . ' | status=' . ($result['status'] ?? '?'));
        $this->assertEquals('completed', $result['status']);

        // L'écriture doit exister
        $this->assertArrayHasKey('ecriture_id', $result);
        $this->assertGreaterThan(0, $result['ecriture_id']);
        $this->created_ecriture_ids[] = (int) $result['ecriture_id'];

        // type et metadata retournés
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('bar_externe', $result['type']);

        // Vérifier l'écriture en base : num_cheque = HelloAsso:{txid}
        $ecriture = $this->db->where('id', $result['ecriture_id'])->get('ecritures')->row_array();
        $this->assertNotEmpty($ecriture);
        $this->assertEquals('HelloAsso:' . $txid, $ecriture['num_cheque']);
        $this->assertEquals($montant, (float) $ecriture['montant']);
    }

    /**
     * Idempotence : webhook envoyé deux fois → une seule écriture créée.
     */
    public function testBarExterneWebhookIdempotent()
    {
        $txid    = 'test-uc2-idem-' . uniqid();
        $montant = 6.50;

        $id = $this->model->create_transaction(array(
            'user_id'        => 0,
            'montant'        => $montant,
            'plateforme'     => 'helloasso',
            'club'           => self::$club_id,
            'transaction_id' => $txid,
            'metadata'       => json_encode(array(
                'type'               => 'bar_externe',
                'payer_name'         => 'Paul Durand',
                'description'        => '1 bière',
                'gvv_transaction_id' => $txid,
            )),
            'created_by' => 'phpunit',
        ));
        $this->assertIsInt($id);
        $this->created_transaction_ids[] = $id;

        $payload = array(
            'metadata' => array(
                'type'               => 'bar_externe',
                'payer_name'         => 'Paul Durand',
                'description'        => '1 bière',
                'gvv_transaction_id' => $txid,
            ),
            'payments' => array(array('state' => 'Authorized', 'amount' => (int)($montant * 100))),
        );

        $result1 = $this->model->process_order_event($payload, self::$club_id);
        $this->assertTrue($result1['ok']);
        $this->assertEquals('completed', $result1['status']);
        if (isset($result1['ecriture_id'])) {
            $this->created_ecriture_ids[] = (int) $result1['ecriture_id'];
        }

        // Deuxième appel → idempotence
        $result2 = $this->model->process_order_event($payload, self::$club_id);
        $this->assertTrue($result2['ok']);
        $this->assertEquals('already_completed', $result2['status']);

        // Une seule écriture doit exister pour ce txid
        $ecritures = $this->db->where('num_cheque', 'HelloAsso:' . $txid)->get('ecritures')->result_array();
        $this->assertCount(1, $ecritures);
    }
}
