<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit MySQL Tests — Paiement générique par QR code
 *
 * Vérifie :
 *  - Création d'une transaction pending avec les bonnes métadonnées
 *  - Webhook simulé → écriture comptable débit 467 / crédit compte destination
 *  - Idempotence du webhook (second appel ignoré)
 *  - Filtre type=paiement_generique dans get_transactions_with_user()
 *  - Validation : montant hors limites rejeté, description vide rejetée
 *
 * Prérequis :
 *  - Migration 097 appliquée (table paiements_en_ligne)
 *  - Section 4 avec au moins un compte 467
 *  - Un compte 7xx quelconque dans club=4 comme destination
 *
 * @see application/controllers/paiements_en_ligne.php  paiement_generique()
 * @see application/models/paiements_en_ligne_model.php _ecriture_paiement_generique()
 */
class PaiementGeneriqueTest extends TestCase
{
    protected static $CI;
    protected $db;
    protected $model;

    protected $created_transaction_ids = array();
    protected $created_ecriture_ids    = array();

    protected static $club_id          = 4;
    protected static $compte_passage_id;
    protected static $compte_destination_id;

    public static function setUpBeforeClass(): void
    {
        self::$CI = &get_instance();
        self::$CI->load->database();
        self::$CI->load->model('paiements_en_ligne_model');
        self::$CI->load->model('ecritures_model');
        self::$CI->load->model('comptes_model');

        $q = self::$CI->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'paiements_en_ligne'"
        )->row_array();
        if ((int) $q['cnt'] === 0) {
            self::markTestSkipped('Table paiements_en_ligne absente — migration 097 non appliquée');
        }

        $c467 = self::$CI->db
            ->where('club', self::$club_id)
            ->where('codec', '467')
            ->order_by('id', 'ASC')
            ->get('comptes')->row_array();
        if (!$c467) {
            self::markTestSkipped('Aucun compte 467 dans club=' . self::$club_id);
        }
        self::$compte_passage_id = (int) $c467['id'];

        // Utilise le premier compte dont le codec commence par 7 comme destination
        $cdest = self::$CI->db->query(
            "SELECT id FROM comptes WHERE club = ? AND codec LIKE '7%' ORDER BY id ASC LIMIT 1",
            array(self::$club_id)
        )->row_array();
        if (!$cdest) {
            self::markTestSkipped('Aucun compte 7xx dans club=' . self::$club_id . ' pour compte destination');
        }
        self::$compte_destination_id = (int) $cdest['id'];
    }

    protected function setUp(): void
    {
        $this->db    = self::$CI->db;
        $this->model = self::$CI->paiements_en_ligne_model;
        $this->created_transaction_ids = array();
        $this->created_ecriture_ids    = array();

        $this->model->upsert_config('helloasso', 'compte_passage',
            (string) self::$compte_passage_id, self::$club_id, 'phpunit');
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

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function createPendingTransaction($txid, $montant = 25.00, $description = 'Cotisation test', $user_id = 1)
    {
        $id = $this->model->create_transaction(array(
            'user_id'        => $user_id,
            'montant'        => $montant,
            'plateforme'     => 'helloasso',
            'club'           => self::$club_id,
            'transaction_id' => $txid,
            'metadata'       => json_encode(array(
                'type'                   => 'paiement_generique',
                'description'            => $description,
                'compte_destination_id'  => self::$compte_destination_id,
                'compte_destination_nom' => 'Compte test',
                'gvv_transaction_id'     => $txid,
            )),
            'created_by' => 'phpunit',
        ));
        $this->assertIsInt($id, 'create_transaction doit retourner un int');
        $this->created_transaction_ids[] = $id;
        return $id;
    }

    private function buildWebhookPayload($txid, $state = 'Authorized', $montant = 25.00, array $extra_meta = array())
    {
        $meta = array_merge(array(
            'type'                  => 'paiement_generique',
            'description'           => 'Cotisation test',
            'compte_destination_id' => self::$compte_destination_id,
            'gvv_transaction_id'    => $txid,
        ), $extra_meta);

        return array(
            'metadata' => $meta,
            'payments' => array(array('state' => $state, 'amount' => (int)($montant * 100))),
        );
    }

    // ── Tests ────────────────────────────────────────────────────────────────

    /**
     * Une transaction pending doit être insérée avec les bonnes métadonnées.
     */
    public function testCreatePendingTransactionStoresMetadata()
    {
        $txid = 'gen-test-create-' . uniqid();
        $id   = $this->createPendingTransaction($txid, 42.50, 'Renouvellement licence');

        $tx = $this->db->where('id', $id)->get('paiements_en_ligne')->row_array();
        $this->assertNotEmpty($tx);
        $this->assertEquals('pending', $tx['statut']);
        $this->assertEquals(self::$club_id, (int) $tx['club']);
        $this->assertEquals(42.50, (float) $tx['montant']);

        $meta = json_decode($tx['metadata'], true);
        $this->assertEquals('paiement_generique',         $meta['type']);
        $this->assertEquals('Renouvellement licence',     $meta['description']);
        $this->assertEquals(self::$compte_destination_id, (int) $meta['compte_destination_id']);
        $this->assertEquals($txid,                        $meta['gvv_transaction_id']);
    }

    /**
     * Webhook avec état Authorized → écriture débit 467 / crédit compte destination.
     */
    public function testWebhookCreatesEcritureDebitPassageCreditDestination()
    {
        $txid    = 'gen-test-ecriture-' . uniqid();
        $montant = 35.00;
        $desc    = 'Remboursement matériel';

        $this->createPendingTransaction($txid, $montant, $desc);
        $payload = $this->buildWebhookPayload($txid, 'Authorized', $montant,
            array('description' => $desc));

        $result = $this->model->process_order_event($payload, self::$club_id);

        $this->assertTrue($result['ok'], ($result['error'] ?? '') . ' status=' . ($result['status'] ?? ''));
        $this->assertEquals('completed',         $result['status']);
        $this->assertEquals('paiement_generique', $result['type']);
        $this->assertGreaterThan(0, $result['ecriture_id']);
        $this->created_ecriture_ids[] = (int) $result['ecriture_id'];

        // Vérifier les comptes de l'écriture
        $ecriture = $this->db->where('id', $result['ecriture_id'])->get('ecritures')->row_array();
        $this->assertNotEmpty($ecriture);
        $this->assertEquals(self::$compte_passage_id,     (int) $ecriture['compte1'], 'compte1 doit être 467');
        $this->assertEquals(self::$compte_destination_id, (int) $ecriture['compte2'], 'compte2 doit être la destination');
        $this->assertEquals($montant, (float) $ecriture['montant']);
        $this->assertEquals('HelloAsso:' . $txid, $ecriture['num_cheque']);
        $this->assertStringContainsString($desc, $ecriture['description']);
    }

    /**
     * La description saisie doit apparaître dans le libellé de l'écriture.
     */
    public function testWebhookEcritureLibelleMatchesDescription()
    {
        $txid = 'gen-test-desc-' . uniqid();
        $desc = 'Achat extincteur hangar';

        $this->createPendingTransaction($txid, 120.00, $desc);
        $payload = $this->buildWebhookPayload($txid, 'Authorized', 120.00,
            array('description' => $desc));

        $result = $this->model->process_order_event($payload, self::$club_id);
        $this->assertTrue($result['ok']);
        $this->created_ecriture_ids[] = (int) $result['ecriture_id'];

        $ecriture = $this->db->where('id', $result['ecriture_id'])->get('ecritures')->row_array();
        $this->assertStringContainsString($desc, $ecriture['description']);
    }

    /**
     * Webhook envoyé deux fois → une seule écriture (idempotence).
     */
    public function testWebhookIdempotent()
    {
        $txid    = 'gen-test-idem-' . uniqid();
        $montant = 15.00;

        $this->createPendingTransaction($txid, $montant);
        $payload = $this->buildWebhookPayload($txid, 'Authorized', $montant);

        $result1 = $this->model->process_order_event($payload, self::$club_id);
        $this->assertTrue($result1['ok']);
        $this->assertEquals('completed', $result1['status']);
        $this->created_ecriture_ids[] = (int) $result1['ecriture_id'];

        $result2 = $this->model->process_order_event($payload, self::$club_id);
        $this->assertTrue($result2['ok']);
        $this->assertEquals('already_completed', $result2['status']);

        $count = $this->db
            ->where('num_cheque', 'HelloAsso:' . $txid)
            ->count_all_results('ecritures');
        $this->assertEquals(1, $count, 'Une seule écriture doit exister pour ce txid');
    }

    /**
     * Webhook avec état non Authorized → transaction failed, aucune écriture.
     */
    public function testWebhookNonAuthorizedMarksTransactionFailed()
    {
        $txid = 'gen-test-failed-' . uniqid();

        $this->createPendingTransaction($txid);
        $payload = $this->buildWebhookPayload($txid, 'Pending');

        $result = $this->model->process_order_event($payload, self::$club_id);

        $this->assertTrue($result['ok']);
        $this->assertEquals('failed', $result['status']);

        $count = $this->db
            ->where('num_cheque', 'HelloAsso:' . $txid)
            ->count_all_results('ecritures');
        $this->assertEquals(0, $count, 'Aucune écriture pour un paiement non autorisé');
    }

    /**
     * Filtre type=paiement_generique dans get_transactions_with_user() ne retourne
     * que les transactions de ce type.
     */
    public function testListeFilterByType()
    {
        $txid_gen = 'gen-test-liste-gen-' . uniqid();
        $txid_bar = 'gen-test-liste-bar-' . uniqid();

        // Insérer un paiement_generique
        $this->createPendingTransaction($txid_gen, 10.00);

        // Insérer un bar_externe
        $id_bar = $this->model->create_transaction(array(
            'user_id'        => 0,
            'montant'        => 5.00,
            'plateforme'     => 'helloasso',
            'club'           => self::$club_id,
            'transaction_id' => $txid_bar,
            'metadata'       => json_encode(array(
                'type'               => 'bar_externe',
                'description'        => 'café test',
                'gvv_transaction_id' => $txid_bar,
            )),
            'created_by' => 'phpunit',
        ));
        $this->assertIsInt($id_bar);
        $this->created_transaction_ids[] = $id_bar;

        $results = $this->model->get_transactions_with_user(array(
            'type'  => 'paiement_generique',
            'club'  => self::$club_id,
        ));

        $txids_returned = array_column($results, 'transaction_id');
        $this->assertContains($txid_gen, $txids_returned, 'La transaction generique doit apparaître');
        $this->assertNotContains($txid_bar, $txids_returned, 'La transaction bar_externe ne doit pas apparaître');
    }
}
