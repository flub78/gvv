<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit MySQL Tests — Cotisation trésorier par carte (UC6)
 *
 * Vérifie :
 *  - process_order_event retourne type et metadata pour cotisation_tresorier
 *  - La création de la licence (via _create_licence_from_cotisation_meta) est testée
 *    indirectement via les données retournées
 *
 * @see application/controllers/paiements_en_ligne.php
 * @see application/models/paiements_en_ligne_model.php
 */
class PaiementsEnLigneCotisationTest extends TestCase
{
    protected static $CI;
    protected $db;
    protected $model;

    protected $created_transaction_ids = array();
    protected $created_ecriture_ids    = array();

    protected static $club_id          = 4;
    protected static $user_id          = 1187;  // asterix
    protected static $compte_pilote_id = 1477;  // 411 asterix club=4
    protected static $bar_account_id   = 763;   // 707 club=4 (utilisé comme compte cotisation)

    public static function setUpBeforeClass(): void
    {
        self::$CI = &get_instance();
        self::$CI->load->database();
        self::$CI->load->model('paiements_en_ligne_model');
        self::$CI->load->model('ecritures_model');
        self::$CI->load->model('comptes_model');
        self::$CI->load->model('membres_model');
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

    public function testCotisationTresorierResultContainsTypeAndMetadata()
    {
        $txid = 'test-uc6-type-' . uniqid();
        $id = $this->model->create_transaction(array(
            'user_id'        => self::$user_id,
            'montant'        => 150.00,
            'plateforme'     => 'helloasso',
            'club'           => self::$club_id,
            'transaction_id' => $txid,
            'metadata'       => json_encode(array(
                'type'                 => 'cotisation_tresorier',
                'description'          => 'Cotisation 2026',
                'gvv_transaction_id'   => $txid,
                'compte_cotisation_id' => self::$bar_account_id,
                'pilote_login'         => 'asterix',
                'annee_cotisation'     => 2026,
            )),
            'created_by' => 'phpunit',
        ));
        $this->assertIsInt($id);
        $this->created_transaction_ids[] = $id;

        $payload = array(
            'metadata' => array(
                'type'                 => 'cotisation_tresorier',
                'description'          => 'Cotisation 2026',
                'gvv_transaction_id'   => $txid,
                'compte_cotisation_id' => self::$bar_account_id,
                'pilote_login'         => 'asterix',
                'annee_cotisation'     => 2026,
            ),
            'payments' => array(array('state' => 'Authorized', 'amount' => 15000)),
        );

        $result = $this->model->process_order_event($payload, self::$club_id);

        $this->assertTrue($result['ok'], ($result['error'] ?? '') . ' | status=' . ($result['status'] ?? '?'));
        $this->assertEquals('completed', $result['status'], ($result['error'] ?? ''));

        // type et metadata sont retournés (pour la création de licence côté contrôleur)
        $this->assertArrayHasKey('type',     $result);
        $this->assertArrayHasKey('metadata', $result);
        $this->assertEquals('cotisation_tresorier', $result['type']);
        $this->assertEquals('asterix', $result['metadata']['pilote_login']);
        $this->assertEquals(2026,      $result['metadata']['annee_cotisation']);

        // Collecter les écritures pour le nettoyage
        $ecritures = $this->db->where('num_cheque', 'HelloAsso:' . $txid)->get('ecritures')->result_array();
        foreach ($ecritures as $e) {
            $this->created_ecriture_ids[] = (int) $e['id'];
        }
    }

    public function testCotisationTresorierSoldeNetPiloteInchange()
    {
        $txid = 'test-uc6-solde-' . uniqid();
        $id = $this->model->create_transaction(array(
            'user_id'        => self::$user_id,
            'montant'        => 80.00,
            'plateforme'     => 'helloasso',
            'club'           => self::$club_id,
            'transaction_id' => $txid,
            'metadata'       => json_encode(array(
                'type'                 => 'cotisation_tresorier',
                'description'          => 'Cotisation 2026',
                'gvv_transaction_id'   => $txid,
                'compte_cotisation_id' => self::$bar_account_id,
                'pilote_login'         => 'asterix',
                'annee_cotisation'     => 2026,
            )),
            'created_by' => 'phpunit',
        ));
        $this->assertIsInt($id);
        $this->created_transaction_ids[] = $id;

        $solde_avant = self::$CI->ecritures_model->solde_compte(self::$compte_pilote_id);

        $payload = array(
            'metadata' => array(
                'type'                 => 'cotisation_tresorier',
                'description'          => 'Cotisation 2026',
                'gvv_transaction_id'   => $txid,
                'compte_cotisation_id' => self::$bar_account_id,
                'pilote_login'         => 'asterix',
                'annee_cotisation'     => 2026,
            ),
            'payments' => array(array('state' => 'Authorized', 'amount' => 8000)),
        );

        $result = $this->model->process_order_event($payload, self::$club_id);

        $this->assertTrue($result['ok']);
        $this->assertEquals('completed', $result['status']);

        $solde_apres = self::$CI->ecritures_model->solde_compte(self::$compte_pilote_id);
        $this->assertEquals(
            round((float) $solde_avant, 2),
            round((float) $solde_apres, 2),
            'Le solde net du pilote doit être inchangé après cotisation_tresorier'
        );

        $ecritures = $this->db->where('num_cheque', 'HelloAsso:' . $txid)->get('ecritures')->result_array();
        foreach ($ecritures as $e) {
            $this->created_ecriture_ids[] = (int) $e['id'];
        }
    }
}
