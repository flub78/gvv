<?php

require_once __DIR__ . '/../integration/TransactionalTestCase.php';

/**
 * Test: Les sélecteurs de comptes dans le formulaire d'édition d'écriture
 * doivent inclure les comptes masqués quand l'écriture en cours pointe vers eux.
 *
 * Bug démontré : quand on édite une écriture dont compte1 ou compte2 est masqué,
 * le compte masqué n'apparaît pas dans le sélecteur → le champ semble vide.
 *
 * Correction attendue : comptes_model::selector_with_null_force_include() inclut
 * l'ID forcé même si le compte est masqué.
 *
 * Usage :
 *   phpunit --bootstrap application/tests/integration_bootstrap.php \
 *           --configuration phpunit_mysql.xml \
 *           application/tests/mysql/EcritureSelectorCompteMasqueTest.php
 */
class EcritureSelectorCompteMasqueTest extends TransactionalTestCase {

    /** @var Comptes_model */
    private $comptes_model;

    /** @var int ID de la section de test */
    private $section_id;

    /** @var array Deux comptes non masqués de la section */
    private $compte1;
    private $compte2;

    /** @var int ID de l'écriture de test créée */
    private $ecriture_id;

    /**
     * Initialise le modèle et prépare les données de test.
     */
    public function setUp(): void {
        parent::setUp(); // démarre la transaction

        $this->CI->load->model('comptes_model');
        $this->comptes_model = $this->CI->comptes_model;

        if (!$this->CI->db->conn_id) {
            $this->markTestSkipped('Connexion base de données indisponible');
        }

        // Récupère une section existante
        $section_row = $this->CI->db->select('id')
            ->from('sections')
            ->limit(1)
            ->get()
            ->row_array();

        if (!$section_row) {
            $this->markTestSkipped('Aucune section disponible en base');
        }
        $this->section_id = $section_row['id'];

        // Récupère deux comptes non masqués dans cette section
        $comptes = $this->CI->db->select('id, nom, codec, masked')
            ->from('comptes')
            ->where('club', $this->section_id)
            ->where('masked', 0)
            ->limit(2)
            ->get()
            ->result_array();

        if (count($comptes) < 2) {
            $this->markTestSkipped("Pas assez de comptes non masqués dans la section {$this->section_id}");
        }

        $this->compte1 = $comptes[0]; // sera masqué pendant le test
        $this->compte2 = $comptes[1];

        // Crée une écriture de test pointant vers ces deux comptes
        $ecriture_data = [
            'annee_exercise' => date('Y'),
            'date_creation'  => date('Y-m-d'),
            'date_op'        => date('Y-m-d'),
            'compte1'        => $this->compte1['id'],
            'compte2'        => $this->compte2['id'],
            'montant'        => '1.00',
            'description'    => 'Test EcritureSelectorCompteMasque',
            'saisie_par'     => 'phpunit',
            'gel'            => 0,
            'club'           => $this->section_id,
            'categorie'      => 0,
            'created_by'     => 'phpunit',
            'created_at'     => date('Y-m-d H:i:s'),
        ];
        $this->CI->db->insert('ecritures', $ecriture_data);
        $this->ecriture_id = $this->CI->db->insert_id();

        $this->assertGreaterThan(0, $this->ecriture_id,
            "La création de l'écriture de test doit réussir");
    }

    /**
     * Le tearDown est géré par TransactionalTestCase::tearDown() qui fait un rollback.
     * Cela supprime l'écriture créée ET restaure le statut masked du compte.
     */
    public function tearDown(): void {
        parent::tearDown(); // rollback de toute la transaction
    }

    /**
     * Masque un compte via une requête SQL directe (contournement d'un bug de CI 2.x
     * où $db->update($table, $data, ['id'=>$id]) n'ajoute pas la clause WHERE).
     */
    private function maskCompte(int $id): void {
        $this->CI->db->query("UPDATE comptes SET masked = 1 WHERE id = $id");
    }

    // -------------------------------------------------------------------------
    // Test 1 – Démonstration du bug
    // -------------------------------------------------------------------------

    /**
     * Démontre que le sélecteur standard exclut un compte masqué même si une écriture
     * pointe dessus.
     *
     * Ce test PASSE avant correction (confirme que le bug existe).
     */
    public function testBugSelectorExcludesAccountAfterMasking(): void {
        // Masque compte1 (comme le ferait un trésorier via l'interface)
        $this->maskCompte((int) $this->compte1['id']);

        // Construit le sélecteur tel que form_static_element() le fait sans correction
        $selector = $this->comptes_model->selector_with_null([], FALSE);

        // Le compte masqué ne doit PAS apparaître dans le sélecteur standard
        $this->assertArrayNotHasKey(
            $this->compte1['id'],
            $selector,
            "Bug confirmé : le compte masqué ({$this->compte1['id']}) n'est pas dans le sélecteur standard."
        );
    }

    // -------------------------------------------------------------------------
    // Test 2 – Vérification de la correction
    // -------------------------------------------------------------------------

    /**
     * Vérifie que selector_with_null_force_include() inclut le compte masqué
     * dont l'ID est fourni (comportement attendu après correction).
     *
     * Ce test ÉCHOUE avant correction et PASSE après.
     */
    public function testFixSelectorIncludesMaskedAccountWhenForced(): void {
        $compte1_id = (int) $this->compte1['id'];
        $compte2_id = (int) $this->compte2['id'];

        // Masque compte1
        $this->maskCompte($compte1_id);

        // Appelle la méthode corrigée avec l'ID à forcer
        $selector = $this->comptes_model->selector_with_null_force_include(
            [],       // $where – pas de filtre supplémentaire
            FALSE,    // $filter_section
            $compte1_id
        );

        // Le compte masqué DOIT maintenant apparaître dans le sélecteur
        $this->assertArrayHasKey(
            $compte1_id,
            $selector,
            "Correction : le compte masqué ($compte1_id) référencé par une écriture " .
            "doit apparaître dans le sélecteur du formulaire d'édition."
        );

        // Le compte non masqué doit toujours être présent
        $this->assertArrayHasKey(
            $compte2_id,
            $selector,
            "Le compte non masqué ($compte2_id) doit rester dans le sélecteur."
        );
    }

    /**
     * Vérifie que le compte forcé est visuellement marqué comme masqué dans le libellé.
     */
    public function testFixMaskedAccountHasMaskedLabelInSelector(): void {
        $compte1_id = (int) $this->compte1['id'];
        $this->maskCompte($compte1_id);

        $selector = $this->comptes_model->selector_with_null_force_include(
            [], FALSE, $compte1_id
        );

        $this->assertArrayHasKey($compte1_id, $selector);

        // Le libellé doit signaler visuellement que le compte est masqué
        $label = $selector[$compte1_id];
        $this->assertStringContainsString(
            '[masqué]',
            $label,
            "Le libellé du compte masqué doit contenir '[masqué]' pour alerter l'utilisateur."
        );
    }

    /**
     * Vérifie que sans ID forcé, selector_with_null_force_include() se comporte
     * exactement comme selector_with_null() (pas de régression).
     */
    public function testFixWithNullForceIdBehavesLikeNormalSelector(): void {
        // Pas de masquage ici – les deux comptes sont non masqués
        $selector_normal  = $this->comptes_model->selector_with_null([], FALSE);
        $selector_force   = $this->comptes_model->selector_with_null_force_include([], FALSE, null);

        $this->assertEquals(
            $selector_normal,
            $selector_force,
            "Sans ID forcé, selector_with_null_force_include() doit retourner " .
            "le même résultat que selector_with_null()."
        );
    }
}
