<?php
/**
 * Tests unitaires pour le Mode RAN (Retrospective Adjustment Nullification)
 *
 * Ces tests vérifient le bon fonctionnement des fonctions de saisie rétrospective:
 * - Identification des comptes à compenser
 * - Création des écritures de compensation
 * - Cohérence des soldes après compensation
 */

require_once(__DIR__ . '/TransactionalTestCase.php');

class RanModeTest extends TransactionalTestCase {

    protected $CI;
    protected $test_section_id;
    protected $test_compte_102_id;
    protected $test_compte_init_id;
    protected $test_compte_no_init_id;

    protected function setUp(): void {
        parent::setUp();
        $this->CI =& get_instance();

        // Skip all RAN mode tests if RAN mode is not enabled
        $this->CI->load->config('program');
        if (!$this->CI->config->item('ran_mode_enabled')) {
            $this->markTestSkipped('RAN mode is not enabled in config/program.php');
            return;
        }

        // Start database transaction to prevent data loss
        $this->CI->db->trans_start();
        TestLogger::info("After trans_start in setUp: _trans_depth = " . $this->CI->db->_trans_depth);

        // Load required models
        $this->CI->load->model('ecritures_model');
        $this->CI->load->model('comptes_model');
        $this->CI->load->model('sections_model');

        // Use section 1 (or get from session)
        $this->test_section_id = 1;
        $this->CI->session->set_userdata('section', $this->test_section_id);
        
        // Set a test user for saisie_par field in ecritures
        $this->CI->session->set_userdata('user', 'phpunit_test_user');

        // Find compte 102 for this section
        $compte_102 = $this->CI->db->select('id')
            ->from('comptes')
            ->where('codec', '102')
            ->where('club', $this->test_section_id)
            ->get()
            ->row();

        if (!$compte_102) {
            $this->markTestSkipped("Compte 102 not found for section {$this->test_section_id}");
            return;
        }
        $this->test_compte_102_id = $compte_102->id;

        // Find a compte with initialization (has ecritures with compte 102)
        $compte_init = $this->CI->db->query("
            SELECT DISTINCT
                CASE
                    WHEN compte1 = {$this->test_compte_102_id} THEN compte2
                    ELSE compte1
                END as compte_id
            FROM ecritures
            WHERE club = {$this->test_section_id}
                AND (compte1 = {$this->test_compte_102_id} OR compte2 = {$this->test_compte_102_id})
                AND date_op < '2025-01-01'
            LIMIT 1
        ")->row();

        if (!$compte_init) {
            $this->markTestSkipped("No compte with 102 initialization found for section {$this->test_section_id}");
            return;
        }
        $this->test_compte_init_id = $compte_init->compte_id;

        // Find a compte without initialization (no ecritures with compte 102)
        $compte_no_init = $this->CI->db->query("
            SELECT c.id
            FROM comptes c
            WHERE c.club = {$this->test_section_id}
                AND c.id != {$this->test_compte_102_id}
                AND c.id NOT IN (
                    SELECT DISTINCT
                        CASE
                            WHEN compte1 = {$this->test_compte_102_id} THEN compte2
                            ELSE compte1
                        END
                    FROM ecritures
                    WHERE club = {$this->test_section_id}
                        AND (compte1 = {$this->test_compte_102_id} OR compte2 = {$this->test_compte_102_id})
                )
            LIMIT 1
        ")->row();

        if (!$compte_no_init) {
            $this->markTestSkipped("No compte without initialization found for section {$this->test_section_id}");
            return;
        }
        $this->test_compte_no_init_id = $compte_no_init->id;

        TestLogger::info("\nRAN Mode Test Setup:");
        TestLogger::info("Section: {$this->test_section_id}");
        TestLogger::info("Compte 102: {$this->test_compte_102_id}");
        TestLogger::info("Compte with init: {$this->test_compte_init_id}");
        TestLogger::info("Compte without init: {$this->test_compte_no_init_id}");
    }

    /**
     * Test 1: Identifier les comptes à compenser
     *
     * Cas testés:
     * - Compte avec initialisation 102 → doit être compensé
     * - Compte sans initialisation 102 → ne doit pas être compensé
     * - Montant correct (débit = impact négatif, crédit = impact positif)
     */
    public function test_identifier_comptes_a_compenser_avec_compte_initialise() {
        $montant = 100.00;

        // Cas 1: Écriture avec compte initialisé en compte1 (débit)
        $comptes = $this->CI->ecritures_model->identifier_comptes_a_compenser(
            $this->test_compte_init_id,      // compte1 (débité)
            $this->test_compte_no_init_id,   // compte2 (crédité)
            $montant,
            $this->test_section_id
        );

        TestLogger::info("\nTest: Compte initialisé débité");
        TestLogger::info("Comptes à compenser: " . print_r($comptes, true));

        // Le compte initialisé doit être dans la liste avec impact négatif (débit diminue solde)
        $this->assertArrayHasKey($this->test_compte_init_id, $comptes,
            "Le compte initialisé débité doit être identifié pour compensation");
        $this->assertEquals(-$montant, $comptes[$this->test_compte_init_id],
            "L'impact doit être négatif pour un débit");

        // Le compte non initialisé ne doit pas être dans la liste
        $this->assertArrayNotHasKey($this->test_compte_no_init_id, $comptes,
            "Le compte non initialisé ne doit pas être compensé");
    }

    public function test_identifier_comptes_a_compenser_avec_compte_initialise_credite() {
        $montant = 100.00;

        // Cas 2: Écriture avec compte initialisé en compte2 (crédit)
        $comptes = $this->CI->ecritures_model->identifier_comptes_a_compenser(
            $this->test_compte_no_init_id,   // compte1 (débité)
            $this->test_compte_init_id,      // compte2 (crédité)
            $montant,
            $this->test_section_id
        );

        TestLogger::info("\nTest: Compte initialisé crédité");
        TestLogger::info("Comptes à compenser: " . print_r($comptes, true));

        // Le compte initialisé doit être dans la liste avec impact positif (crédit augmente solde)
        $this->assertArrayHasKey($this->test_compte_init_id, $comptes,
            "Le compte initialisé crédité doit être identifié pour compensation");
        $this->assertEquals($montant, $comptes[$this->test_compte_init_id],
            "L'impact doit être positif pour un crédit");
    }

    public function test_identifier_comptes_a_compenser_deux_comptes_initialises() {
        // Setup: Trouver deux comptes avec initialisation
        $comptes_init_result = $this->CI->db->query("
            SELECT DISTINCT
                CASE
                    WHEN compte1 = {$this->test_compte_102_id} THEN compte2
                    ELSE compte1
                END as compte_id
            FROM ecritures
            WHERE club = {$this->test_section_id}
                AND (compte1 = {$this->test_compte_102_id} OR compte2 = {$this->test_compte_102_id})
                AND date_op < '2025-01-01'
            LIMIT 2
        ");
        $comptes_init = $comptes_init_result->result();

        if (count($comptes_init) < 2) {
            $this->markTestSkipped("Need at least 2 initialized comptes for this test");
            return;
        }

        $compte1_id = $comptes_init[0]->compte_id;
        $compte2_id = $comptes_init[1]->compte_id;
        $montant = 100.00;

        // Cas 3: Écriture entre deux comptes initialisés
        $comptes = $this->CI->ecritures_model->identifier_comptes_a_compenser(
            $compte1_id,
            $compte2_id,
            $montant,
            $this->test_section_id
        );

        TestLogger::info("\nTest: Deux comptes initialisés");
        TestLogger::info("Comptes à compenser: " . print_r($comptes, true));

        // Les deux comptes doivent être dans la liste
        $this->assertCount(2, $comptes, "Les deux comptes initialisés doivent être compensés");
        $this->assertArrayHasKey($compte1_id, $comptes);
        $this->assertArrayHasKey($compte2_id, $comptes);

        // Vérifier les impacts opposés
        $this->assertEquals(-$montant, $comptes[$compte1_id], "Compte1 débité = impact négatif");
        $this->assertEquals($montant, $comptes[$compte2_id], "Compte2 crédité = impact positif");
    }

    public function test_identifier_comptes_a_compenser_aucun_compte_initialise() {
        // Trouver deux comptes sans initialisation
        $comptes_no_init_result = $this->CI->db->query("
            SELECT c.id
            FROM comptes c
            WHERE c.club = {$this->test_section_id}
                AND c.id != {$this->test_compte_102_id}
                AND c.id NOT IN (
                    SELECT DISTINCT
                        CASE
                            WHEN compte1 = {$this->test_compte_102_id} THEN compte2
                            ELSE compte1
                        END
                    FROM ecritures
                    WHERE club = {$this->test_section_id}
                        AND (compte1 = {$this->test_compte_102_id} OR compte2 = {$this->test_compte_102_id})
                )
            LIMIT 2
        ");
        $comptes_no_init = $comptes_no_init_result->result();

        if (count($comptes_no_init) < 2) {
            $this->markTestSkipped("Need at least 2 non-initialized comptes for this test");
            return;
        }

        $montant = 100.00;

        // Cas 4: Aucun compte initialisé
        $comptes = $this->CI->ecritures_model->identifier_comptes_a_compenser(
            $comptes_no_init[0]->id,
            $comptes_no_init[1]->id,
            $montant,
            $this->test_section_id
        );

        TestLogger::info("\nTest: Aucun compte initialisé");
        TestLogger::info("Comptes à compenser: " . print_r($comptes, true));

        // Aucun compte ne doit être compensé
        $this->assertEmpty($comptes, "Aucune compensation nécessaire si aucun compte initialisé");
    }

    /**
     * Test 2: Passer une écriture de compensation
     *
     * Vérifie que:
     * - L'écriture de compensation est bien créée
     * - Le montant est correct
     * - La référence à l'écriture principale est stockée
     * - Les comptes 102 et compte cible sont corrects
     */
    public function test_passer_ecriture_compensation_impact_negatif() {
        $date = '2024-06-15';
        $impact = -100.00;  // Débit du compte → compensation par crédit
        $id_ecriture_ref = 99999;  // ID fictif pour référence

        // Récupérer le solde initial du compte
        $solde_avant = $this->CI->ecritures_model->solde_compte($this->test_compte_init_id, $date, '<=');

        TestLogger::info("\nTest: Compensation impact négatif");
        TestLogger::info("Solde avant: $solde_avant");

        // Passer l'écriture de compensation
        $id_compensation = $this->CI->ecritures_model->passer_ecriture_compensation(
            $date,
            $this->test_compte_init_id,
            $impact,
            $this->test_section_id,
            $id_ecriture_ref
        );

        $this->assertNotFalse($id_compensation, "L'écriture de compensation doit être créée");
        $this->assertGreaterThan(0, $id_compensation, "L'ID de compensation doit être positif");

        // Vérifier l'écriture créée
        $compensation = $this->CI->db->select('*')
            ->from('ecritures')
            ->where('id', $id_compensation)
            ->get()
            ->row();

        $this->assertNotNull($compensation, "L'écriture de compensation doit exister en base");
        $this->assertEquals($date, $compensation->date_op, "La date doit correspondre");
        $this->assertEquals(abs($impact), $compensation->montant, "Le montant doit être en valeur absolue");
        $this->assertEquals("REF:$id_ecriture_ref", $compensation->num_cheque, "La référence doit être stockée");

        // Vérifier que c'est une compensation: 102 débité, compte crédité (impact négatif)
        $this->assertEquals($this->test_compte_102_id, $compensation->compte1,
            "Compte1 doit être 102 (débit)");
        $this->assertEquals($this->test_compte_init_id, $compensation->compte2,
            "Compte2 doit être le compte cible (crédit)");

        TestLogger::info("Écriture compensation créée: ID=$id_compensation");
        TestLogger::info("Comptes: 102 (débit) → {$this->test_compte_init_id} (crédit)");

        // Cleanup handled by transaction rollback in tearDown()
    }

    public function test_passer_ecriture_compensation_impact_positif() {
        $date = '2024-06-15';
        $impact = 100.00;  // Crédit du compte → compensation par débit
        $id_ecriture_ref = 99999;

        TestLogger::info("\nTest: Compensation impact positif");

        // Passer l'écriture de compensation
        $id_compensation = $this->CI->ecritures_model->passer_ecriture_compensation(
            $date,
            $this->test_compte_init_id,
            $impact,
            $this->test_section_id,
            $id_ecriture_ref
        );

        $this->assertNotFalse($id_compensation, "L'écriture de compensation doit être créée");

        // Vérifier l'écriture créée
        $compensation = $this->CI->db->select('*')
            ->from('ecritures')
            ->where('id', $id_compensation)
            ->get()
            ->row();

        $this->assertNotNull($compensation);
        $this->assertEquals(abs($impact), $compensation->montant);

        // Vérifier que c'est une compensation: compte débité, 102 crédité (impact positif)
        $this->assertEquals($this->test_compte_init_id, $compensation->compte1,
            "Compte1 doit être le compte cible (débit)");
        $this->assertEquals($this->test_compte_102_id, $compensation->compte2,
            "Compte2 doit être 102 (crédit)");

        TestLogger::info("Écriture compensation créée: ID=$id_compensation");
        TestLogger::info("Comptes: {$this->test_compte_init_id} (débit) → 102 (crédit)");

        // Cleanup handled by transaction rollback in tearDown()
    }
}
