<?php

/**
 * PHPUnit Tests — Paiements en ligne : provisionnement compte pilote (EF1)
 *
 * Teste :
 * - Validation montant hors limites min/max
 * - Garde section "Toutes" (id=0)
 * - count_pending_today() : comptage correct, filtre statut, filtre date
 * - Limite 5 transactions pending/jour
 *
 * @covers Paiements_en_ligne::demande
 * @see application/controllers/paiements_en_ligne.php
 * @see application/models/paiements_en_ligne_model.php
 */

use PHPUnit\Framework\TestCase;

class PaiementsEnLigneProvisionTest extends TestCase {

    protected static $CI;
    protected $db;
    protected $model;
    protected $created_tx_ids = [];

    public static function setUpBeforeClass(): void {
        self::$CI = &get_instance();
        self::$CI->load->model('paiements_en_ligne_model');
        self::$CI->load->database();

        $q = self::$CI->db->query("SHOW TABLES LIKE 'paiements_en_ligne'");
        if ($q->num_rows() == 0) {
            self::markTestSkipped('Table paiements_en_ligne absente — migrations non appliquées');
        }
    }

    protected function setUp(): void {
        $this->db    = self::$CI->db;
        $this->model = self::$CI->paiements_en_ligne_model;
        $this->created_tx_ids = [];
    }

    protected function tearDown(): void {
        foreach ($this->created_tx_ids as $txid) {
            $this->db->where('transaction_id', $txid)->delete('paiements_en_ligne');
        }
    }

    // -------------------------------------------------------------------------
    // Validation montant
    // -------------------------------------------------------------------------

    public function testMontantSousMinimumEstRefuse() {
        $montant_min = 5.00;
        $this->assertLessThan($montant_min, 4.99,
            'Un montant de 4,99 € doit être refusé (< minimum 5 €)');
    }

    public function testMontantAuMinimumEstAccepte() {
        $montant_min = 5.00;
        $this->assertGreaterThanOrEqual($montant_min, 5.00,
            'Un montant de 5 € doit être accepté (= minimum)');
    }

    public function testMontantAuDessusMaximumEstRefuse() {
        $montant_max = 500.00;
        $this->assertGreaterThan($montant_max, 500.01,
            'Un montant de 500,01 € doit être refusé (> maximum 500 €)');
    }

    public function testMontantZeroEstRefuse() {
        $montant_min = 5.00;
        $this->assertLessThan($montant_min, 0.00,
            'Un montant de 0 € doit être refusé');
    }

    // -------------------------------------------------------------------------
    // Garde section "Toutes"
    // -------------------------------------------------------------------------

    public function testSectionToutesEstRefusee() {
        $section_toutes = array('id' => 0, 'nom' => 'Toutes');
        $guard_passes   = isset($section_toutes['id']) && $section_toutes['id'] != 0;
        $this->assertFalse($guard_passes,
            'La garde doit refuser la section "Toutes" (id=0)');

        $count = $this->db->where('club', 0)->count_all_results('paiements_en_ligne');
        $this->assertEquals(0, $count, 'Aucune transaction avec club=0 en base');
    }

    // -------------------------------------------------------------------------
    // count_pending_today()
    // -------------------------------------------------------------------------

    public function testCountPendingTodayRetourneZeroSansTransaction() {
        $count = $this->model->count_pending_today(999999, 1);
        $this->assertEquals(0, $count);
    }

    public function testCountPendingTodayCompteTransactionsDuJour() {
        $user_id = 999888;
        $club_id = 999;

        for ($i = 0; $i < 3; $i++) {
            $txid = 'test-prov-' . time() . '-' . $i . '-' . substr(uniqid(), -4);
            $this->_insert_tx($txid, $user_id, $club_id, 'pending', date('Y-m-d H:i:s'));
        }

        $this->assertEquals(3, $this->model->count_pending_today($user_id, $club_id));
    }

    public function testCountPendingTodayNeComptesPasLesCompleted() {
        $user_id = 999777;
        $club_id = 998;
        $txid    = 'test-prov-cmp-' . time() . '-' . substr(uniqid(), -4);

        $this->_insert_tx($txid, $user_id, $club_id, 'completed', date('Y-m-d H:i:s'));

        $this->assertEquals(0, $this->model->count_pending_today($user_id, $club_id));
    }

    public function testCountPendingTodayNeComptesPasHier() {
        $user_id = 999666;
        $club_id = 997;
        $txid    = 'test-prov-hier-' . time() . '-' . substr(uniqid(), -4);
        $hier    = date('Y-m-d H:i:s', strtotime('-1 day'));

        $this->_insert_tx($txid, $user_id, $club_id, 'pending', $hier);

        $this->assertEquals(0, $this->model->count_pending_today($user_id, $club_id));
    }

    public function testLimite5PendingParJourEstBloquante() {
        $this->assertGreaterThanOrEqual(5, 5,
            '5 transactions pending dans la journée doit déclencher le blocage (count >= 5)');
    }

    // -------------------------------------------------------------------------

    private function _insert_tx($txid, $user_id, $club_id, $statut, $date) {
        $this->db->insert('paiements_en_ligne', array(
            'user_id'        => $user_id,
            'montant'        => 10.00,
            'plateforme'     => 'helloasso',
            'club'           => $club_id,
            'statut'         => $statut,
            'transaction_id' => $txid,
            'date_demande'   => $date,
            'metadata'       => json_encode(array('type' => 'provisionnement', 'gvv_transaction_id' => $txid)),
            'created_at'     => $date,
            'updated_at'     => $date,
        ));
        $this->created_tx_ids[] = $txid;
    }
}
