<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit MySQL Tests — Historique des transactions pilote (EF3)
 *
 * Vérifie :
 *  - get_transactions() filtre par user_id
 *  - get_transactions() filtre par club
 *  - get_transactions() retourne la bonne structure de données
 *  - Badge HelloAsso : num_cheque 'HelloAsso:xxx' identifiable
 *
 * @see application/controllers/compta.php
 * @see application/models/paiements_en_ligne_model.php
 */
class PaiementsEnLigneHistoriqueTest extends TestCase
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

    // -------------------------------------------------------------------------
    // get_transactions() — filtrage par user_id
    // -------------------------------------------------------------------------

    public function testGetTransactionsFiltreParUserId()
    {
        $user_id  = 888001;
        $other_id = 888002;
        $club_id  = 4;

        $txid1 = 'test-hist-u1-' . time() . '-' . substr(uniqid(), -4);
        $txid2 = 'test-hist-u2-' . time() . '-' . substr(uniqid(), -4);

        $this->_insert_tx($txid1, $user_id,  $club_id, 'completed');
        $this->_insert_tx($txid2, $other_id, $club_id, 'completed');

        $results = $this->model->get_transactions(array('user_id' => $user_id, 'club' => $club_id));

        $found_txids = array_column($results, 'transaction_id');
        $this->assertContains($txid1, $found_txids, 'La transaction de user_id doit être retournée');
        $this->assertNotContains($txid2, $found_txids, 'La transaction d\'un autre user ne doit pas apparaître');
    }

    public function testGetTransactionsFiltreParClub()
    {
        $user_id  = 888003;
        $club1    = 4;
        $club2    = 5;

        $txid1 = 'test-hist-c1-' . time() . '-' . substr(uniqid(), -4);
        $txid2 = 'test-hist-c2-' . time() . '-' . substr(uniqid(), -4);

        $this->_insert_tx($txid1, $user_id, $club1, 'completed');
        $this->_insert_tx($txid2, $user_id, $club2, 'completed');

        $results = $this->model->get_transactions(array('user_id' => $user_id, 'club' => $club1));

        $found_txids = array_column($results, 'transaction_id');
        $this->assertContains($txid1, $found_txids, 'La transaction du club1 doit être retournée');
        $this->assertNotContains($txid2, $found_txids, 'La transaction du club2 ne doit pas apparaître');
    }

    public function testGetTransactionsRetourneStructureCorrecte()
    {
        $user_id = 888004;
        $club_id = 4;
        $txid    = 'test-hist-str-' . time() . '-' . substr(uniqid(), -4);

        $this->_insert_tx($txid, $user_id, $club_id, 'completed');

        $results = $this->model->get_transactions(array('user_id' => $user_id, 'club' => $club_id));

        $this->assertNotEmpty($results);
        $row = $results[0];
        $this->assertArrayHasKey('transaction_id', $row);
        $this->assertArrayHasKey('montant', $row);
        $this->assertArrayHasKey('statut', $row);
        $this->assertArrayHasKey('date_demande', $row);
    }

    public function testGetTransactionsRetourneVideSiAucuneTransaction()
    {
        $results = $this->model->get_transactions(array('user_id' => 999999, 'club' => 4));
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    // -------------------------------------------------------------------------
    // Badge HelloAsso — identification via num_cheque
    // -------------------------------------------------------------------------

    public function testNumChequeHelloAssoEstIdentifiable()
    {
        $num_cheque = 'HelloAsso:abc123';
        $this->assertTrue(
            strpos($num_cheque, 'HelloAsso:') === 0,
            'num_cheque commençant par HelloAsso: doit être identifié comme paiement HelloAsso'
        );
    }

    public function testNumChequeNonHelloAssoNestPasIdentifie()
    {
        $num_cheque = 'CHQ-12345';
        $this->assertFalse(
            strpos($num_cheque, 'HelloAsso:') === 0,
            'num_cheque sans préfixe HelloAsso: ne doit pas être traité comme HelloAsso'
        );
    }

    // -------------------------------------------------------------------------

    private function _insert_tx($txid, $user_id, $club_id, $statut)
    {
        $now = date('Y-m-d H:i:s');
        $this->db->insert('paiements_en_ligne', array(
            'user_id'        => $user_id,
            'montant'        => 100.00,
            'plateforme'     => 'helloasso',
            'club'           => $club_id,
            'statut'         => $statut,
            'transaction_id' => $txid,
            'date_demande'   => $now,
            'metadata'       => json_encode(array('type' => 'provisionnement')),
            'created_at'     => $now,
            'updated_at'     => $now,
        ));
        $this->created_tx_ids[] = $txid;
    }
}
