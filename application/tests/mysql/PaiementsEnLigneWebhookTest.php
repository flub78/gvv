<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit MySQL Tests — Webhook HelloAsso (EF2)
 *
 * Teste la méthode process_order_event() du modèle paiements_en_ligne_model :
 *  - type=provisionnement → écriture créée, transaction completed
 *  - type=bar             → débit compte pilote 411, crédit compte bar 7xx
 *  - payment state != 'Authorized' → transaction failed, aucune écriture
 *  - idempotence : même webhook deux fois → une seule écriture
 *  - type=cotisation_tresorier → deux écritures atomiques, solde net pilote inchangé
 *  - transaction introuvable → status=error, aucune écriture
 *
 * Prérequis :
 *  - migrations 096 (has_bar) et 097 (paiements_en_ligne) appliquées
 *  - section 4 avec has_bar=1, bar_account_id=763
 *  - utilisateur asterix (user_id=429) avec compte pilote 411 dans club=4 (id=1457)
 *  - au moins un compte 467 dans club=4
 *
 * @see application/models/paiements_en_ligne_model.php
 */
class PaiementsEnLigneWebhookTest extends TestCase
{
    protected static $CI;
    protected $db;
    protected $model;

    /** IDs d'écritures créées (nettoyage tearDown) */
    protected $created_ecriture_ids = array();
    /** IDs de transactions créées (nettoyage tearDown) */
    protected $created_transaction_ids = array();
    /** IDs de config insérés (nettoyage tearDown) */
    protected $created_config_ids = array();

    // Fixtures stables (section 4, asterix)
    protected static $club_id     = 4;
    protected static $user_id     = 429;   // asterix
    protected static $compte_pilote_id = 1457; // 411 asterix club=4
    protected static $bar_account_id   = 763;  // 707 "Recettes du bar" club=4
    protected static $compte_passage_id; // premier 467 dans club=4, déterminé en setUp

    public static function setUpBeforeClass(): void
    {
        self::$CI = &get_instance();
        self::$CI->load->database();
        self::$CI->load->model('paiements_en_ligne_model');
        self::$CI->load->model('ecritures_model');
        self::$CI->load->model('comptes_model');

        // Vérifier prérequis : tables et comptes
        $tables_ok = self::$CI->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME IN ('paiements_en_ligne', 'paiements_en_ligne_config')"
        )->row_array();
        if ((int) $tables_ok['cnt'] < 2) {
            self::markTestSkipped('Tables paiements_en_ligne manquantes — migration 097 non appliquée');
        }

        // Vérifier compte pilote d'asterix dans club=4
        $cp = self::$CI->db->where('id', self::$compte_pilote_id)->get('comptes')->row_array();
        if (!$cp || (int) $cp['club'] !== self::$club_id) {
            self::markTestSkipped('Compte pilote asterix (id=1457) introuvable dans club=4');
        }

        // Vérifier compte bar dans club=4
        $cb = self::$CI->db->where('id', self::$bar_account_id)->get('comptes')->row_array();
        if (!$cb || (int) $cb['club'] !== self::$club_id) {
            self::markTestSkipped('Compte bar (id=763) introuvable dans club=4');
        }

        // Premier compte 467 dans club=4 (ordre naturel par id)
        $c467 = self::$CI->db
            ->where('club', self::$club_id)
            ->where('codec', '467')
            ->order_by('id', 'ASC')
            ->get('comptes')->row_array();
        if (!$c467) {
            self::markTestSkipped('Aucun compte 467 dans club=4');
        }
        self::$compte_passage_id = (int) $c467['id'];
    }

    protected function setUp(): void
    {
        $this->db    = self::$CI->db;
        $this->model = self::$CI->paiements_en_ligne_model;
        $this->created_ecriture_ids   = array();
        $this->created_transaction_ids = array();
        $this->created_config_ids      = array();

        // Configurer compte_passage pour club=4 (codec '467')
        $ok = $this->model->upsert_config('helloasso', 'compte_passage', '467',
            self::$club_id, 'phpunit');
        $this->assertTrue($ok, 'Impossible d\'insérer la config compte_passage');

        $row = $this->db
            ->where('plateforme', 'helloasso')
            ->where('club', self::$club_id)
            ->where('param_key', 'compte_passage')
            ->get('paiements_en_ligne_config')->row_array();
        if ($row) {
            $this->created_config_ids[] = (int) $row['id'];
        }
    }

    protected function tearDown(): void
    {
        // 1. Nullifier ecriture_id dans paiements_en_ligne pour lever la FK avant suppression
        if (!empty($this->created_ecriture_ids)) {
            $this->db->where_in('ecriture_id', $this->created_ecriture_ids)
                     ->update('paiements_en_ligne', array('ecriture_id' => null));
        }
        // 2. Supprimer les transactions créées
        foreach ($this->created_transaction_ids as $id) {
            $this->db->where('id', $id)->delete('paiements_en_ligne');
        }
        // 3. Supprimer les écritures (FK levée, comptes mis à jour par delete inverse n'est pas requis ici)
        foreach ($this->created_ecriture_ids as $id) {
            $this->db->where('id', $id)->delete('ecritures');
        }
        // 4. Supprimer la config de test
        foreach ($this->created_config_ids as $id) {
            $this->db->where('id', $id)->delete('paiements_en_ligne_config');
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Crée une transaction pending et retourne son transaction_id (chaîne unique).
     */
    private function createPendingTransaction($type, $overrides = array())
    {
        $txid = 'test-wh-' . $type . '-' . uniqid();
        $defaults = array(
            'user_id'        => self::$user_id,
            'montant'        => 20.00,
            'plateforme'     => 'helloasso',
            'club'           => self::$club_id,
            'transaction_id' => $txid,
            'metadata'       => json_encode(array(
                'type'               => $type,
                'description'        => 'Test ' . $type,
                'gvv_transaction_id' => $txid,
            )),
            'created_by'     => 'phpunit',
        );
        $data = array_merge($defaults, $overrides);
        $id = $this->model->create_transaction($data);
        $this->assertIsInt($id);
        $this->created_transaction_ids[] = $id;
        return $txid;
    }

    /**
     * Construit un payload Order HelloAsso minimal.
     */
    private function buildOrderPayload($txid, $type, $payment_state = 'Authorized', $extra_meta = array())
    {
        $meta = array_merge(array(
            'type'               => $type,
            'description'        => 'Test ' . $type,
            'gvv_transaction_id' => $txid,
        ), $extra_meta);

        return array(
            'metadata' => $meta,
            'payments' => array(
                array(
                    'state'  => $payment_state,
                    'amount' => 2000,
                ),
            ),
        );
    }

    // ── Tests provisionnement ─────────────────────────────────────────────────

    public function testProvisionnementCreatesEcritureAndCompletesTransaction()
    {
        $txid = $this->createPendingTransaction('provisionnement');
        $payload = $this->buildOrderPayload($txid, 'provisionnement');

        $result = $this->model->process_order_event($payload, self::$club_id);

        $this->assertTrue($result['ok'], 'Résultat attendu ok=true : ' . ($result['error'] ?? ''));
        $this->assertEquals('completed', $result['status']);
        $this->assertGreaterThan(0, $result['ecriture_id']);
        $this->created_ecriture_ids[] = $result['ecriture_id'];

        // Transaction en base = completed
        $tx = $this->model->get_by_transaction_id($txid);
        $this->assertEquals('completed', $tx['statut']);
        $this->assertEquals($result['ecriture_id'], (int) $tx['ecriture_id']);
        $this->assertNotEmpty($tx['date_paiement']);

        // Écriture : débit compte_passage, crédit compte_pilote
        $ecriture = $this->db->where('id', $result['ecriture_id'])->get('ecritures')->row_array();
        $this->assertIsArray($ecriture);
        $this->assertEquals(self::$compte_passage_id, (int) $ecriture['compte1']);
        $this->assertEquals(self::$compte_pilote_id,  (int) $ecriture['compte2']);
        $this->assertEquals('20.00', number_format((float) $ecriture['montant'], 2, '.', ''));
        $this->assertStringStartsWith('HelloAsso:', $ecriture['num_cheque']);
    }

    public function testCreditTresorierSameAsProvisionnement()
    {
        // credit_tresorier utilise exactement la même logique comptable que provisionnement
        $txid = $this->createPendingTransaction('credit_tresorier');
        $payload = $this->buildOrderPayload($txid, 'credit_tresorier');

        $result = $this->model->process_order_event($payload, self::$club_id);

        $this->assertTrue($result['ok'], $result['error'] ?? '');
        $this->assertEquals('completed', $result['status']);
        $this->created_ecriture_ids[] = $result['ecriture_id'];

        $ecriture = $this->db->where('id', $result['ecriture_id'])->get('ecritures')->row_array();
        $this->assertEquals(self::$compte_passage_id, (int) $ecriture['compte1']);
        $this->assertEquals(self::$compte_pilote_id,  (int) $ecriture['compte2']);
    }

    // ── Tests bar ─────────────────────────────────────────────────────────────

    public function testBarDebitePiloteCreditBar()
    {
        $txid = $this->createPendingTransaction('bar');
        $payload = $this->buildOrderPayload($txid, 'bar');

        $result = $this->model->process_order_event($payload, self::$club_id);

        $this->assertTrue($result['ok'], $result['error'] ?? '');
        $this->assertEquals('completed', $result['status']);
        $this->created_ecriture_ids[] = $result['ecriture_id'];

        // Écriture : débit compte_pilote 411, crédit bar 7xx
        $ecriture = $this->db->where('id', $result['ecriture_id'])->get('ecritures')->row_array();
        $this->assertIsArray($ecriture);
        $this->assertEquals(self::$compte_pilote_id, (int) $ecriture['compte1']);
        $this->assertEquals(self::$bar_account_id,   (int) $ecriture['compte2']);
        $this->assertEquals('20.00', number_format((float) $ecriture['montant'], 2, '.', ''));
    }

    // ── Tests bar_externe ─────────────────────────────────────────────────────

    public function testBarExterneDebitPassageCreditBar()
    {
        // bar_externe : pas de user_id pilote, mais la transaction peut avoir user_id=0
        // Le user_id ne sert pas pour bar_externe (pas de compte pilote)
        // On crée une transaction avec user_id=429 mais le type bar_externe l'ignore
        $txid = $this->createPendingTransaction('bar_externe', array(
            'metadata' => json_encode(array(
                'type'               => 'bar_externe',
                'description'        => 'Test bar externe',
                'gvv_transaction_id' => 'test-wh-bar_externe-dummy', // sera remplacé
                'payer_name'         => 'Jean Dupont',
            )),
        ));
        // Reconstruire avec le bon txid dans metadata
        $this->db->where('transaction_id', $txid)->update('paiements_en_ligne', array(
            'metadata' => json_encode(array(
                'type'               => 'bar_externe',
                'description'        => 'Cafés bar',
                'gvv_transaction_id' => $txid,
                'payer_name'         => 'Jean Dupont',
            )),
        ));

        $payload = $this->buildOrderPayload($txid, 'bar_externe', 'Authorized', array(
            'payer_name' => 'Jean Dupont',
        ));

        $result = $this->model->process_order_event($payload, self::$club_id);

        $this->assertTrue($result['ok'], $result['error'] ?? '');
        $this->assertEquals('completed', $result['status']);
        $this->created_ecriture_ids[] = $result['ecriture_id'];

        $ecriture = $this->db->where('id', $result['ecriture_id'])->get('ecritures')->row_array();
        $this->assertEquals(self::$compte_passage_id, (int) $ecriture['compte1']);
        $this->assertEquals(self::$bar_account_id,    (int) $ecriture['compte2']);
        $this->assertStringContainsString('Jean Dupont', $ecriture['description']);
    }

    // ── Tests état de paiement non autorisé ──────────────────────────────────

    public function testPaymentStateNotAuthorizedMarksTransactionFailed()
    {
        $txid = $this->createPendingTransaction('provisionnement');
        $payload = $this->buildOrderPayload($txid, 'provisionnement', 'Pending');

        $result = $this->model->process_order_event($payload, self::$club_id);

        $this->assertTrue($result['ok']);
        $this->assertEquals('failed', $result['status']);

        // Aucune écriture créée
        $count = $this->db
            ->where('num_cheque', 'HelloAsso:' . $txid)
            ->count_all_results('ecritures');
        $this->assertEquals(0, $count);

        // Transaction marquée failed
        $tx = $this->model->get_by_transaction_id($txid);
        $this->assertEquals('failed', $tx['statut']);
    }

    public function testPaymentStateCancelledMarksTransactionFailed()
    {
        $txid = $this->createPendingTransaction('bar');
        $payload = $this->buildOrderPayload($txid, 'bar', 'Cancelled');

        $result = $this->model->process_order_event($payload, self::$club_id);

        $this->assertTrue($result['ok']);
        $this->assertEquals('failed', $result['status']);
        $tx = $this->model->get_by_transaction_id($txid);
        $this->assertEquals('failed', $tx['statut']);
    }

    // ── Test idempotence ──────────────────────────────────────────────────────

    public function testIdempotenceSecondCallSkipsProcessing()
    {
        $txid = $this->createPendingTransaction('provisionnement');
        $payload = $this->buildOrderPayload($txid, 'provisionnement');

        // Premier appel
        $r1 = $this->model->process_order_event($payload, self::$club_id);
        $this->assertEquals('completed', $r1['status']);
        $this->created_ecriture_ids[] = $r1['ecriture_id'];

        // Deuxième appel identique
        $r2 = $this->model->process_order_event($payload, self::$club_id);
        $this->assertEquals('already_completed', $r2['status']);

        // Une seule écriture dans la base
        $count = $this->db
            ->where('num_cheque', 'HelloAsso:' . $txid)
            ->count_all_results('ecritures');
        $this->assertEquals(1, $count, 'Idempotence : exactement une écriture attendue');
    }

    // ── Test cotisation_tresorier (double écriture atomique) ──────────────────

    public function testCotisationTresorierCreatesTwoEcrituresAtomically()
    {
        // Utiliser bar_account_id (763) comme compte de destination cotisation
        // pour éviter d'insérer un compte avec codec='417' (FK planc non satisfaite en test)
        $compte_cotisation_id = self::$bar_account_id;

        $txid = 'test-wh-cot-tres-' . uniqid();
        $id = $this->model->create_transaction(array(
            'user_id'        => self::$user_id,
            'montant'        => 120.00,
            'plateforme'     => 'helloasso',
            'club'           => self::$club_id,
            'transaction_id' => $txid,
            'metadata'       => json_encode(array(
                'type'                 => 'cotisation_tresorier',
                'description'          => 'Cotisation 2026',
                'gvv_transaction_id'   => $txid,
                'compte_cotisation_id' => $compte_cotisation_id,
            )),
            'created_by' => 'phpunit',
        ));
        $this->assertIsInt($id);
        $this->created_transaction_ids[] = $id;

        $payload = $this->buildOrderPayload($txid, 'cotisation_tresorier', 'Authorized', array(
            'compte_cotisation_id' => $compte_cotisation_id,
        ));

        // Solde pilote AVANT
        $solde_avant = self::$CI->ecritures_model->solde_compte(self::$compte_pilote_id);

        $result = $this->model->process_order_event($payload, self::$club_id);

        $this->assertTrue($result['ok'], $result['error'] ?? '');
        $this->assertEquals('completed', $result['status']);

        // Deux écritures doivent exister avec le num_cheque correspondant
        $ecritures = $this->db
            ->where('num_cheque', 'HelloAsso:' . $txid)
            ->get('ecritures')->result_array();
        $this->assertCount(2, $ecritures, 'Exactement deux écritures attendues pour cotisation_tresorier');

        foreach ($ecritures as $e) {
            $this->created_ecriture_ids[] = (int) $e['id'];
        }

        // Solde pilote APRÈS : inchangé (les deux écritures s'annulent)
        $solde_apres = self::$CI->ecritures_model->solde_compte(self::$compte_pilote_id);
        $this->assertEquals(
            round((float) $solde_avant, 2),
            round((float) $solde_apres, 2),
            'Le solde net du pilote doit être inchangé après cotisation_tresorier'
        );
    }

    // ── Test transaction introuvable ──────────────────────────────────────────

    public function testUnknownTransactionIdReturnsError()
    {
        $payload = $this->buildOrderPayload('txid-inexistant-' . uniqid(), 'provisionnement');

        $result = $this->model->process_order_event($payload, self::$club_id);

        $this->assertFalse($result['ok']);
        $this->assertEquals('error', $result['status']);
    }

    public function testMissingGvvTransactionIdReturnsError()
    {
        // Payload sans gvv_transaction_id dans les metadata
        $payload = array(
            'metadata' => array('type' => 'provisionnement'),
            'payments' => array(array('state' => 'Authorized', 'amount' => 1000)),
        );

        $result = $this->model->process_order_event($payload, self::$club_id);

        $this->assertFalse($result['ok']);
        $this->assertEquals('error', $result['status']);
    }
}
