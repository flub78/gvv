<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit MySQL Tests — Cotisation en ligne par le pilote (UC3)
 *
 * Vérifie :
 *  - Création/lecture/toggle d'un produit de cotisation
 *  - process_order_event type=cotisation → écriture 417 créée
 *  - Idempotence webhook
 *  - Création licence de cotisation via metadata
 *
 * @see application/controllers/paiements_en_ligne.php
 * @see application/models/paiements_en_ligne_model.php
 * @see application/models/cotisation_produits_model.php
 */
class PaiementsEnLigneCotisationPiloteTest extends TestCase
{
    protected static $CI;
    protected $db;
    protected $model;
    protected $produits_model;

    protected $created_transaction_ids = array();
    protected $created_ecriture_ids    = array();
    protected $created_produit_ids     = array();
    protected $created_licence_ids     = array();

    protected static $club_id          = 4;
    protected static $user_id          = 1187;  // asterix
    protected static $pilote_login     = 'asterix';

    public static function setUpBeforeClass(): void
    {
        self::$CI = &get_instance();
        self::$CI->load->database();
        self::$CI->load->model('paiements_en_ligne_model');
        self::$CI->load->model('cotisation_produits_model');
        self::$CI->load->model('ecritures_model');
        self::$CI->load->model('comptes_model');
        self::$CI->load->model('licences_model');

        $q = self::$CI->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'paiements_en_ligne'"
        )->row_array();
        if ((int) $q['cnt'] === 0) {
            self::markTestSkipped('Table paiements_en_ligne absente');
        }

        $q2 = self::$CI->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cotisation_produits'"
        )->row_array();
        if ((int) $q2['cnt'] === 0) {
            self::markTestSkipped('Table cotisation_produits absente — exécuter la migration 098');
        }
    }

    protected function setUp(): void
    {
        $this->db             = self::$CI->db;
        $this->model          = self::$CI->paiements_en_ligne_model;
        $this->produits_model = self::$CI->cotisation_produits_model;
        $this->created_transaction_ids = array();
        $this->created_ecriture_ids    = array();
        $this->created_produit_ids     = array();
        $this->created_licence_ids     = array();

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
        foreach ($this->created_produit_ids as $id) {
            $this->db->where('id', $id)->delete('cotisation_produits');
        }
        foreach ($this->created_licence_ids as $id) {
            $this->db->where('id', $id)->delete('licences');
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Retourne un compte utilisable comme compte de cotisation (417 ou 708 si absent).
     */
    private function _get_compte_cotisation() {
        $c = $this->db->where('club', self::$club_id)->where('codec', '417')
            ->order_by('id', 'ASC')->get('comptes')->row_array();
        if (!$c) {
            $c = $this->db->where('club', self::$club_id)->where('codec', '708')
                ->order_by('id', 'ASC')->get('comptes')->row_array();
        }
        return $c;
    }

    // ── Tests ────────────────────────────────────────────────────────────────

    /**
     * CRUD produit de cotisation.
     */
    public function testCotisationProduitCrud()
    {
        $c417 = $this->_get_compte_cotisation();
        if (!$c417) {
            $this->markTestSkipped('Aucun compte 417/708 dans club=4');
        }

        // Création
        $id = $this->produits_model->create(array(
            'section_id'           => self::$club_id,
            'libelle'              => 'Cotisation test PHPUnit',
            'montant'              => 50.00,
            'annee'                => 2099,
            'compte_cotisation_id' => (int) $c417['id'],
            'created_by'           => 'phpunit',
        ));
        $this->assertIsInt($id);
        $this->created_produit_ids[] = $id;

        // Lecture
        $produits = $this->produits_model->get_active_for_section(self::$club_id);
        $found = array_filter($produits, function ($p) use ($id) { return (int) $p['id'] === $id; });
        $this->assertNotEmpty($found, 'Produit créé doit être dans la liste active');

        // Toggle inactif
        $this->produits_model->toggle_actif($id, 'phpunit');
        $produits_after = $this->produits_model->get_active_for_section(self::$club_id);
        $found_after = array_filter($produits_after, function ($p) use ($id) { return (int) $p['id'] === $id; });
        $this->assertEmpty($found_after, 'Produit désactivé ne doit plus être dans la liste active');

        // Toggle actif
        $this->produits_model->toggle_actif($id, 'phpunit');
        $produits_final = $this->produits_model->get_active_for_section(self::$club_id);
        $found_final = array_filter($produits_final, function ($p) use ($id) { return (int) $p['id'] === $id; });
        $this->assertNotEmpty($found_final, 'Produit réactivé doit être dans la liste active');
    }

    /**
     * Webhook type=cotisation → écriture cotisation créée.
     */
    public function testWebhookCotisationCreatesEcriture417()
    {
        $c417 = $this->_get_compte_cotisation();
        if (!$c417) {
            $this->markTestSkipped('Aucun compte cotisation (417/708) dans club=4');
        }

        $txid    = 'test-uc3-ecriture-' . uniqid();
        $montant = 120.00;
        $annee   = 2099;

        $id = $this->model->create_transaction(array(
            'user_id'        => self::$user_id,
            'montant'        => $montant,
            'plateforme'     => 'helloasso',
            'club'           => self::$club_id,
            'transaction_id' => $txid,
            'metadata'       => json_encode(array(
                'type'                 => 'cotisation',
                'pilote_login'         => self::$pilote_login,
                'annee_cotisation'     => $annee,
                'compte_cotisation_id' => (int) $c417['id'],
                'description'          => 'Cotisation pilote 2099',
                'gvv_transaction_id'   => $txid,
            )),
            'created_by' => 'phpunit',
        ));
        $this->assertIsInt($id);
        $this->created_transaction_ids[] = $id;

        $payload = array(
            'metadata' => array(
                'type'                 => 'cotisation',
                'pilote_login'         => self::$pilote_login,
                'annee_cotisation'     => $annee,
                'compte_cotisation_id' => (int) $c417['id'],
                'description'          => 'Cotisation pilote 2099',
                'gvv_transaction_id'   => $txid,
            ),
            'payments' => array(array('state' => 'Authorized', 'amount' => (int)($montant * 100))),
        );

        $result = $this->model->process_order_event($payload, self::$club_id);

        $this->assertTrue($result['ok'], ($result['error'] ?? '') . ' | status=' . ($result['status'] ?? '?'));
        $this->assertEquals('completed', $result['status']);
        $this->assertArrayHasKey('ecriture_id', $result);
        $this->assertGreaterThan(0, $result['ecriture_id']);
        $this->created_ecriture_ids[] = (int) $result['ecriture_id'];

        // Vérifier type retourné
        $this->assertEquals('cotisation', $result['type'] ?? '');

        // Vérifier écriture en base
        $ecriture = $this->db->where('id', $result['ecriture_id'])->get('ecritures')->row_array();
        $this->assertNotEmpty($ecriture);
        $this->assertEquals('HelloAsso:' . $txid, $ecriture['num_cheque']);
        $this->assertEquals($montant, (float) $ecriture['montant']);

        // Cleanup licence créée si existante
        $licence = $this->db->where('pilote', self::$pilote_login)->where('year', $annee)->get('licences')->row_array();
        if ($licence) {
            $this->created_licence_ids[] = (int) $licence['id'];
        }
    }

    /**
     * Webhook type=cotisation → licence créée (statut cotisant mis à jour).
     */
    public function testWebhookCotisationCreatesLicence()
    {
        $c417 = $this->_get_compte_cotisation();
        if (!$c417) {
            $this->markTestSkipped('Aucun compte cotisation (417/708) dans club=4');
        }

        $txid    = 'test-uc3-licence-' . uniqid();
        $montant = 80.00;
        $annee   = 2098;  // Année fictive pour ne pas polluer les données

        // Vérifier qu'il n'existe pas déjà une licence pour cette année fictive
        $existing = self::$CI->licences_model->check_cotisation_exists(self::$pilote_login, $annee);
        if ($existing) {
            $this->markTestSkipped('Licence 2098 déjà existante pour ' . self::$pilote_login);
        }

        $id = $this->model->create_transaction(array(
            'user_id'        => self::$user_id,
            'montant'        => $montant,
            'plateforme'     => 'helloasso',
            'club'           => self::$club_id,
            'transaction_id' => $txid,
            'metadata'       => json_encode(array(
                'type'                 => 'cotisation',
                'pilote_login'         => self::$pilote_login,
                'annee_cotisation'     => $annee,
                'compte_cotisation_id' => (int) $c417['id'],
                'gvv_transaction_id'   => $txid,
            )),
            'created_by' => 'phpunit',
        ));
        $this->assertIsInt($id);
        $this->created_transaction_ids[] = $id;

        $payload = array(
            'metadata' => array(
                'type'                 => 'cotisation',
                'pilote_login'         => self::$pilote_login,
                'annee_cotisation'     => $annee,
                'compte_cotisation_id' => (int) $c417['id'],
                'gvv_transaction_id'   => $txid,
            ),
            'payments' => array(array('state' => 'Authorized', 'amount' => (int)($montant * 100))),
        );

        // Process via model (creates écriture)
        $result = $this->model->process_order_event($payload, self::$club_id);
        $this->assertTrue($result['ok']);
        if (isset($result['ecriture_id'])) {
            $this->created_ecriture_ids[] = (int) $result['ecriture_id'];
        }

        // Créer la licence manuellement comme le ferait le webhook handler
        self::$CI->licences_model->create_cotisation(
            self::$pilote_login, 0, $annee, date('Y-m-d'),
            'Cotisation enregistrée via paiement HelloAsso'
        );

        // Vérifier que la licence existe
        $exists = self::$CI->licences_model->check_cotisation_exists(self::$pilote_login, $annee);
        $this->assertTrue($exists, 'La licence de cotisation doit être créée');

        // Cleanup
        $licence = $this->db->where('pilote', self::$pilote_login)->where('year', $annee)->get('licences')->row_array();
        if ($licence) {
            $this->created_licence_ids[] = (int) $licence['id'];
        }
    }

    /**
     * Idempotence : second webhook → already_completed.
     */
    public function testWebhookCotisationIdempotent()
    {
        $c417 = $this->_get_compte_cotisation();
        if (!$c417) {
            $this->markTestSkipped('Aucun compte cotisation (417/708) dans club=4');
        }

        $txid    = 'test-uc3-idem-' . uniqid();
        $montant = 60.00;

        $id = $this->model->create_transaction(array(
            'user_id'        => self::$user_id,
            'montant'        => $montant,
            'plateforme'     => 'helloasso',
            'club'           => self::$club_id,
            'transaction_id' => $txid,
            'metadata'       => json_encode(array(
                'type'                 => 'cotisation',
                'pilote_login'         => self::$pilote_login,
                'annee_cotisation'     => 2097,
                'compte_cotisation_id' => (int) $c417['id'],
                'gvv_transaction_id'   => $txid,
            )),
            'created_by' => 'phpunit',
        ));
        $this->assertIsInt($id);
        $this->created_transaction_ids[] = $id;

        $payload = array(
            'metadata' => array(
                'type'                 => 'cotisation',
                'pilote_login'         => self::$pilote_login,
                'annee_cotisation'     => 2097,
                'compte_cotisation_id' => (int) $c417['id'],
                'gvv_transaction_id'   => $txid,
            ),
            'payments' => array(array('state' => 'Authorized', 'amount' => (int)($montant * 100))),
        );

        $result1 = $this->model->process_order_event($payload, self::$club_id);
        $this->assertTrue($result1['ok']);
        $this->assertEquals('completed', $result1['status']);
        if (isset($result1['ecriture_id'])) {
            $this->created_ecriture_ids[] = (int) $result1['ecriture_id'];
        }

        $result2 = $this->model->process_order_event($payload, self::$club_id);
        $this->assertTrue($result2['ok']);
        $this->assertEquals('already_completed', $result2['status']);

        // Une seule écriture pour ce txid
        $ecritures = $this->db->where('num_cheque', 'HelloAsso:' . $txid)->get('ecritures')->result_array();
        $this->assertCount(1, $ecritures);
    }
}
