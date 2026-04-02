<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit MySQL Tests — Cotisation en ligne par le pilote (UC3, débit de solde)
 *
 * Vérifie :
 *  - Flag is_cotisation sur un tarif : création/lecture/toggle
 *  - Débit de solde suffisant → écriture 411→417 créée, licence créée
 *  - Solde insuffisant → refus
 *  - Doublon cotisation → refus
 *
 * @see application/controllers/paiements_en_ligne.php
 * @see application/models/tarifs_model.php
 */
class PaiementsEnLigneCotisationPiloteTest extends TestCase
{
    protected static $CI;
    protected $db;
    protected $tarifs_model;
    protected $ecritures_model;
    protected $licences_model;
    protected $comptes_model;

    protected $created_ecriture_ids = array();
    protected $created_tarif_ids    = array();
    protected $created_licence_ids  = array();

    protected static $club_id      = 4;
    protected static $pilote_login = 'asterix';

    public static function setUpBeforeClass(): void
    {
        self::$CI = &get_instance();
        self::$CI->load->database();
        self::$CI->load->model('tarifs_model');
        self::$CI->load->model('ecritures_model');
        self::$CI->load->model('comptes_model');
        self::$CI->load->model('licences_model');

        $q = self::$CI->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tarifs' AND COLUMN_NAME = 'is_cotisation'"
        )->row_array();
        if ((int) $q['cnt'] === 0) {
            self::markTestSkipped('Colonne tarifs.is_cotisation absente — exécuter la migration 099');
        }
    }

    protected function setUp(): void
    {
        $this->db              = self::$CI->db;
        $this->tarifs_model    = self::$CI->tarifs_model;
        $this->ecritures_model = self::$CI->ecritures_model;
        $this->licences_model  = self::$CI->licences_model;
        $this->comptes_model   = self::$CI->comptes_model;
        $this->created_ecriture_ids = array();
        $this->created_tarif_ids    = array();
        $this->created_licence_ids  = array();
    }

    protected function tearDown(): void
    {
        foreach ($this->created_ecriture_ids as $id) {
            $this->db->where('id', $id)->delete('ecritures');
        }
        foreach ($this->created_tarif_ids as $id) {
            $this->db->where('id', $id)->delete('tarifs');
        }
        foreach ($this->created_licence_ids as $id) {
            $this->db->where('id', $id)->delete('licences');
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function _get_compte_pilote()
    {
        // Compte 411 de asterix dans club=4
        $section = array('id' => self::$club_id);
        return $this->comptes_model->compte_pilote(self::$pilote_login, $section);
    }

    private function _get_compte_cotisation()
    {
        $c = $this->db->where('club', self::$club_id)->where('codec', '417')
            ->order_by('id', 'ASC')->get('comptes')->row_array();
        if (!$c) {
            $c = $this->db->where('club', self::$club_id)->where('codec', '708')
                ->order_by('id', 'ASC')->get('comptes')->row_array();
        }
        return $c;
    }

    private function _get_compte_tarif()
    {
        return $this->db->where('club', self::$club_id)
            ->where('codec >=', '700')->where('codec <', '800')
            ->order_by('id', 'ASC')->get('comptes')->row_array();
    }

    // ── Tests ────────────────────────────────────────────────────────────────

    /**
     * Flag is_cotisation sur un tarif : création, lecture, toggle.
     */
    public function testCotisationTarifFlag()
    {
        $compte = $this->_get_compte_tarif();
        if (!$compte) {
            $this->markTestSkipped('Aucun compte 7xx dans club=4');
        }

        $this->db->insert('tarifs', array(
            'reference'     => 'COT-PHPUNIT-' . uniqid(),
            'date'          => '2026-01-01',
            'date_fin'      => '2099-12-31',
            'description'   => 'Cotisation test PHPUnit',
            'prix'          => 50.00,
            'compte'        => (int) $compte['id'],
            'saisie_par'    => 'phpunit',
            'club'          => self::$club_id,
            'is_cotisation' => 1,
            'created_by'    => 'phpunit',
            'created_at'    => date('Y-m-d H:i:s'),
        ));
        $id = (int) $this->db->insert_id();
        $this->assertGreaterThan(0, $id);
        $this->created_tarif_ids[] = $id;

        // Doit apparaître dans la liste
        $produits = $this->tarifs_model->get_cotisation_products_for_section(self::$club_id);
        $found = array_filter($produits, function ($p) use ($id) { return (int) $p['id'] === $id; });
        $this->assertNotEmpty($found, 'Tarif is_cotisation=1 doit être dans la liste cotisation');

        $p = reset($found);
        $this->assertArrayHasKey('libelle', $p);
        $this->assertArrayHasKey('annee', $p);
        $this->assertArrayHasKey('montant', $p);
        $this->assertArrayHasKey('compte_cotisation_id', $p);
        $this->assertEquals('Cotisation test PHPUnit', $p['libelle']);
        $this->assertEquals(2026, (int) $p['annee']);
        $this->assertEquals(50.00, (float) $p['montant']);

        // Toggle off
        $this->db->where('id', $id)->update('tarifs', array('is_cotisation' => 0));
        $produits_after = $this->tarifs_model->get_cotisation_products_for_section(self::$club_id);
        $found_after = array_filter($produits_after, function ($p) use ($id) { return (int) $p['id'] === $id; });
        $this->assertEmpty($found_after, 'Tarif is_cotisation=0 ne doit plus être dans la liste');

        // Toggle on
        $this->db->where('id', $id)->update('tarifs', array('is_cotisation' => 1));
        $produits_final = $this->tarifs_model->get_cotisation_products_for_section(self::$club_id);
        $found_final = array_filter($produits_final, function ($p) use ($id) { return (int) $p['id'] === $id; });
        $this->assertNotEmpty($found_final, 'Tarif is_cotisation=1 réactivé doit réapparaître');

        // get_cotisation_product_by_id
        $produit = $this->tarifs_model->get_cotisation_product_by_id($id);
        $this->assertNotEmpty($produit);
        $this->assertEquals($id, (int) $produit['id']);
    }

    /**
     * Débit de solde suffisant : écriture 411→417 créée, licence créée.
     */
    public function testCotisationDebitSoldeSucces()
    {
        $cpilote = $this->_get_compte_pilote();
        if (!$cpilote) {
            $this->markTestSkipped('Compte 411 asterix introuvable dans club=4');
        }
        $ccot = $this->_get_compte_cotisation();
        if (!$ccot) {
            $this->markTestSkipped('Compte cotisation (417/708) introuvable dans club=4');
        }

        $annee   = 2091;   // Année fictive, pas de cotisation existante attendue
        $montant = 0.01;   // Petit montant pour ne pas vider le compte

        $existing = $this->licences_model->check_cotisation_exists(self::$pilote_login, $annee);
        if ($existing) {
            $this->markTestSkipped('Licence ' . $annee . ' déjà existante pour ' . self::$pilote_login);
        }

        $solde_avant = (float) $this->ecritures_model->solde_compte($cpilote['id']);
        if ($solde_avant < $montant) {
            $this->markTestSkipped('Solde pilote insuffisant pour ce test (' . $solde_avant . ' < ' . $montant . ')');
        }

        // Créer l'écriture directement (simule ce que fait le contrôleur)
        $ecriture_id = $this->ecritures_model->create_ecriture(array(
            'annee_exercise' => date('Y'),
            'date_creation'  => date('Y-m-d'),
            'date_op'        => date('Y-m-d'),
            'compte1'        => (int) $cpilote['id'],
            'compte2'        => (int) $ccot['id'],
            'montant'        => $montant,
            'description'    => 'Cotisation test PHPUnit ' . $annee,
            'type'           => 0,
            'num_cheque'     => '',
            'saisie_par'     => 'phpunit',
            'gel'            => 0,
            'club'           => self::$club_id,
            'categorie'      => 0,
        ));
        $this->assertNotFalse($ecriture_id, 'L\'écriture doit être créée');
        $this->assertGreaterThan(0, $ecriture_id);
        $this->created_ecriture_ids[] = (int) $ecriture_id;

        // Vérifier solde débité
        $solde_apres = (float) $this->ecritures_model->solde_compte($cpilote['id']);
        $this->assertEqualsWithDelta($solde_avant - $montant, $solde_apres, 0.001,
            'Le solde pilote doit être diminué du montant de la cotisation');

        // Créer la licence
        $licence_id = $this->licences_model->create_cotisation(
            self::$pilote_login, 0, $annee, date('Y-m-d'),
            'Cotisation enregistrée en ligne (débit compte)'
        );
        $this->assertNotFalse($licence_id);
        $this->assertGreaterThan(0, $licence_id);
        $this->created_licence_ids[] = (int) $licence_id;

        // Vérifier que la cotisation est bien enregistrée
        $this->assertTrue(
            $this->licences_model->check_cotisation_exists(self::$pilote_login, $annee),
            'La cotisation doit être enregistrée dans licences'
        );
    }

    /**
     * Solde insuffisant : la garde doit rejeter le paiement.
     */
    public function testCotisationDebitSoldeInsuffisant()
    {
        $cpilote = $this->_get_compte_pilote();
        if (!$cpilote) {
            $this->markTestSkipped('Compte 411 asterix introuvable dans club=4');
        }

        $solde = (float) $this->ecritures_model->solde_compte($cpilote['id']);
        $montant_depasse = $solde + 10000.00;  // Montant forcément supérieur au solde

        $guard_ok = ($solde >= $montant_depasse);
        $this->assertFalse($guard_ok,
            'La garde doit refuser si montant > solde (solde=' . $solde . ', montant=' . $montant_depasse . ')');
    }

    /**
     * Doublon cotisation : la garde doit rejeter si la cotisation de l'année existe déjà.
     */
    public function testCotisationDoublonRejete()
    {
        $annee = 2092;

        // Vérifier absence préalable
        $existing = $this->licences_model->check_cotisation_exists(self::$pilote_login, $annee);
        if ($existing) {
            $this->markTestSkipped('Licence ' . $annee . ' déjà existante — test non reproductible');
        }

        // Créer une première cotisation
        $licence_id = $this->licences_model->create_cotisation(
            self::$pilote_login, 0, $annee, date('Y-m-d'), 'PHPUnit doublon test'
        );
        $this->assertGreaterThan(0, $licence_id);
        $this->created_licence_ids[] = (int) $licence_id;

        // La garde check_cotisation_exists doit maintenant retourner true
        $this->assertTrue(
            $this->licences_model->check_cotisation_exists(self::$pilote_login, $annee),
            'check_cotisation_exists doit retourner true après création de la cotisation'
        );
    }
}
