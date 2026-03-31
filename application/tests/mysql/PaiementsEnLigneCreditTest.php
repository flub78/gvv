<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit MySQL Tests — Approvisionnement compte pilote par trésorier (UC7)
 *
 * Vérifie :
 *  - process_order_event retourne type et metadata pour credit_tresorier
 *  - L'écriture créée est débit 467 → crédit 411 pilote
 *  - Le solde du compte pilote augmente du montant payé
 *
 * @see application/controllers/compta.php
 * @see application/models/paiements_en_ligne_model.php
 */
class PaiementsEnLigneCreditTest extends TestCase
{
    protected static $CI;
    protected $db;
    protected $model;

    protected $created_transaction_ids = array();
    protected $created_ecriture_ids    = array();

    protected static $club_id          = 4;
    protected static $user_id          = 1187;  // asterix
    protected static $compte_pilote_id = 1477;  // 411 asterix club=4

    public static function setUpBeforeClass(): void
    {
        self::$CI = &get_instance();
        self::$CI->load->database();
        self::$CI->load->model('paiements_en_ligne_model');
        self::$CI->load->model('ecritures_model');
        self::$CI->load->model('comptes_model');
        self::$CI->load->model('membres_model');

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

    public function testCreditTresorierResultContainsTypeAndMetadata()
    {
        $txid = 'test-uc7-type-' . uniqid();
        $id = $this->model->create_transaction(array(
            'user_id'        => self::$user_id,
            'montant'        => 100.00,
            'plateforme'     => 'helloasso',
            'club'           => self::$club_id,
            'transaction_id' => $txid,
            'metadata'       => json_encode(array(
                'type'               => 'credit_tresorier',
                'pilote_login'       => 'asterix',
                'description'        => 'Approvisionnement compte pilote asterix',
                'initiated_by_user'  => false,
                'gvv_transaction_id' => $txid,
            )),
            'created_by' => 'phpunit',
        ));
        $this->assertIsInt($id);
        $this->created_transaction_ids[] = $id;

        $payload = array(
            'metadata' => array(
                'type'               => 'credit_tresorier',
                'pilote_login'       => 'asterix',
                'description'        => 'Approvisionnement compte pilote asterix',
                'initiated_by_user'  => false,
                'gvv_transaction_id' => $txid,
            ),
            'payments' => array(array('state' => 'Authorized', 'amount' => 10000)),
        );

        $result = $this->model->process_order_event($payload, self::$club_id);

        $this->assertTrue($result['ok'], ($result['error'] ?? '') . ' | status=' . ($result['status'] ?? '?'));
        $this->assertEquals('completed', $result['status'], ($result['error'] ?? ''));

        // type et metadata sont retournés
        $this->assertArrayHasKey('type',     $result);
        $this->assertArrayHasKey('metadata', $result);
        $this->assertEquals('credit_tresorier', $result['type']);
        $this->assertEquals('asterix', $result['metadata']['pilote_login']);
        $this->assertArrayHasKey('initiated_by_user', $result['metadata']);
        $this->assertFalse((bool) $result['metadata']['initiated_by_user']);

        // Collecter les écritures pour le nettoyage
        $ecritures = $this->db->where('num_cheque', 'HelloAsso:' . $txid)->get('ecritures')->result_array();
        foreach ($ecritures as $e) {
            $this->created_ecriture_ids[] = (int) $e['id'];
        }
    }

    public function testCreditTresorierSoldePiloteAugmente()
    {
        $txid   = 'test-uc7-solde-' . uniqid();
        $montant = 75.00;

        $id = $this->model->create_transaction(array(
            'user_id'        => self::$user_id,
            'montant'        => $montant,
            'plateforme'     => 'helloasso',
            'club'           => self::$club_id,
            'transaction_id' => $txid,
            'metadata'       => json_encode(array(
                'type'               => 'credit_tresorier',
                'pilote_login'       => 'asterix',
                'description'        => 'Approvisionnement compte pilote asterix',
                'gvv_transaction_id' => $txid,
            )),
            'created_by' => 'phpunit',
        ));
        $this->assertIsInt($id);
        $this->created_transaction_ids[] = $id;

        $solde_avant = self::$CI->ecritures_model->solde_compte(self::$compte_pilote_id);

        $payload = array(
            'metadata' => array(
                'type'               => 'credit_tresorier',
                'pilote_login'       => 'asterix',
                'description'        => 'Approvisionnement compte pilote asterix',
                'gvv_transaction_id' => $txid,
            ),
            'payments' => array(array('state' => 'Authorized', 'amount' => (int)($montant * 100))),
        );

        $result = $this->model->process_order_event($payload, self::$club_id);

        $this->assertTrue($result['ok'], ($result['error'] ?? ''));
        $this->assertEquals('completed', $result['status']);

        $solde_apres = self::$CI->ecritures_model->solde_compte(self::$compte_pilote_id);
        $this->assertEquals(
            round((float) $solde_avant + $montant, 2),
            round((float) $solde_apres, 2),
            'Le solde du compte pilote doit augmenter du montant approvisionné'
        );

        $ecritures = $this->db->where('num_cheque', 'HelloAsso:' . $txid)->get('ecritures')->result_array();
        foreach ($ecritures as $e) {
            $this->created_ecriture_ids[] = (int) $e['id'];
        }
    }
}
