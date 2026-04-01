<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit MySQL Tests — Liste paiements trésorier (EF4)
 *
 * Teste la méthode get_transactions_with_user() du modèle :
 *  - Filtre par statut
 *  - Filtre par période (date_from / date_to)
 *  - Filtre par plateforme
 *  - Filtre par club
 *  - Jointure membres : mprenom/mnom présents
 *
 * @see application/models/paiements_en_ligne_model.php
 */
class PaiementsEnLigneListeTest extends TestCase
{
    protected static $CI;
    protected $db;
    protected $model;

    protected $created_tx_ids = array();

    public static function setUpBeforeClass(): void
    {
        self::$CI = &get_instance();
        self::$CI->load->database();
        self::$CI->load->model('paiements_en_ligne_model');

        $q = self::$CI->db->query("SHOW TABLES LIKE 'paiements_en_ligne'");
        if ($q->num_rows() == 0) {
            self::markTestSkipped('Table paiements_en_ligne absente — migrations non appliquées');
        }
    }

    protected function setUp(): void
    {
        $this->db    = self::$CI->db;
        $this->model = self::$CI->paiements_en_ligne_model;
        $this->created_tx_ids = array();
    }

    protected function tearDown(): void
    {
        foreach ($this->created_tx_ids as $txid) {
            $this->db->where('transaction_id', $txid)->delete('paiements_en_ligne');
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function _insert_tx($txid, $user_id, $club_id, $statut, $date, $plateforme = 'helloasso')
    {
        $this->db->insert('paiements_en_ligne', array(
            'user_id'        => $user_id,
            'montant'        => 20.00,
            'commission'     => 0.50,
            'plateforme'     => $plateforme,
            'club'           => $club_id,
            'statut'         => $statut,
            'transaction_id' => $txid,
            'date_demande'   => $date,
            'metadata'       => json_encode(array('type' => 'provisionnement')),
            'created_at'     => $date,
            'updated_at'     => $date,
        ));
        $this->created_tx_ids[] = $txid;
    }

    // ── Tests filtres ────────────────────────────────────────────────────────

    public function testFiltreStatutCompleted()
    {
        $uid  = 999801;
        $club = 991;
        $date = date('Y-m-d H:i:s');
        $tx1  = 'test-liste-cmp-' . time() . '-' . substr(uniqid(), -4);
        $tx2  = 'test-liste-pnd-' . time() . '-' . substr(uniqid(), -4);

        $this->_insert_tx($tx1, $uid, $club, 'completed', $date);
        $this->_insert_tx($tx2, $uid, $club, 'pending',   $date);

        $rows = $this->model->get_transactions_with_user(array(
            'user_id' => $uid,
            'statut'  => 'completed',
        ));

        $txids = array_column($rows, 'transaction_id');
        $this->assertContains($tx1, $txids);
        $this->assertNotContains($tx2, $txids);
    }

    public function testFiltrePeriode()
    {
        $uid   = 999802;
        $club  = 992;
        $hier  = date('Y-m-d H:i:s', strtotime('-1 day'));
        $auj   = date('Y-m-d H:i:s');
        $tx_h  = 'test-liste-hier-' . time() . '-' . substr(uniqid(), -4);
        $tx_a  = 'test-liste-auj-'  . time() . '-' . substr(uniqid(), -4);

        $this->_insert_tx($tx_h, $uid, $club, 'completed', $hier);
        $this->_insert_tx($tx_a, $uid, $club, 'completed', $auj);

        $rows = $this->model->get_transactions_with_user(array(
            'user_id'   => $uid,
            'date_from' => date('Y-m-d'),
            'date_to'   => date('Y-m-d'),
        ));

        $txids = array_column($rows, 'transaction_id');
        $this->assertContains($tx_a,  $txids);
        $this->assertNotContains($tx_h, $txids);
    }

    public function testFiltrePlateforme()
    {
        $uid  = 999803;
        $club = 993;
        $date = date('Y-m-d H:i:s');
        $tx1  = 'test-liste-ha-'    . time() . '-' . substr(uniqid(), -4);
        $tx2  = 'test-liste-other-' . time() . '-' . substr(uniqid(), -4);

        $this->_insert_tx($tx1, $uid, $club, 'completed', $date, 'helloasso');
        $this->_insert_tx($tx2, $uid, $club, 'completed', $date, 'stripe');

        $rows = $this->model->get_transactions_with_user(array(
            'user_id'    => $uid,
            'plateforme' => 'helloasso',
        ));

        $txids = array_column($rows, 'transaction_id');
        $this->assertContains($tx1,    $txids);
        $this->assertNotContains($tx2, $txids);
    }

    public function testFiltreClub()
    {
        $uid   = 999804;
        $club1 = 994;
        $club2 = 995;
        $date  = date('Y-m-d H:i:s');
        $tx1   = 'test-liste-c1-' . time() . '-' . substr(uniqid(), -4);
        $tx2   = 'test-liste-c2-' . time() . '-' . substr(uniqid(), -4);

        $this->_insert_tx($tx1, $uid, $club1, 'completed', $date);
        $this->_insert_tx($tx2, $uid, $club2, 'completed', $date);

        $rows = $this->model->get_transactions_with_user(array(
            'user_id' => $uid,
            'club'    => $club1,
        ));

        $txids = array_column($rows, 'transaction_id');
        $this->assertContains($tx1,    $txids);
        $this->assertNotContains($tx2, $txids);
    }

    public function testJointureMembresChampsPresents()
    {
        // La jointure ne doit pas provoquer d'erreur même si user_id est inconnu
        $uid  = 999899;
        $club = 996;
        $date = date('Y-m-d H:i:s');
        $tx   = 'test-liste-join-' . time() . '-' . substr(uniqid(), -4);

        $this->_insert_tx($tx, $uid, $club, 'pending', $date);

        $rows = $this->model->get_transactions_with_user(array('user_id' => $uid));

        $this->assertCount(1, $rows);
        $this->assertArrayHasKey('mprenom', $rows[0]);
        $this->assertArrayHasKey('mnom',    $rows[0]);
    }
}
