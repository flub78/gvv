<?php

use PHPUnit\Framework\TestCase;

/**
 * Smoke test d'intégration CRUD Produits + Tarifs (étape 11 du plan
 * refactoring_produits_tarifs_plan.md) : vérifie que Produits_model et la
 * façade Tarifs_model fonctionnent correctement ensemble de bout en bout
 * (création, lecture jointe, sélecteur, image, mise à jour, suppression).
 */
class ProduitsTarifsCrudIntegrationTest extends TestCase
{
    private $CI;
    private $produits_model;
    private $tarifs_model;
    private $produitId;

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        $this->CI->load->model('sections_model');
        $this->CI->load->model('produits_model');
        $this->CI->load->model('tarifs_model');
        $this->produits_model = $this->CI->produits_model;
        $this->tarifs_model = $this->CI->tarifs_model;
        // tarifs_model::get_tarif() appelle $this->gvv_model->section() — alias
        // requis (normalement fourni par Gvv_Controller, absent hors contrôleur).
        $this->CI->gvv_model = $this->tarifs_model;
        $this->produitId = null;
    }

    protected function tearDown(): void
    {
        if ($this->produitId) {
            $this->CI->db->where('produit_id', $this->produitId)->delete('tarifs');
            $this->CI->db->where('id', $this->produitId)->delete('produits');
        }
    }

    public function testCrudProduitPuisTarifs()
    {
        $reference = 'ZZ_ITEST_' . uniqid();

        // 1. Création du produit
        $this->produitId = $this->produits_model->create(array(
            'reference' => $reference,
            'description' => 'Produit intégration test',
            'compte' => 55,
            'club' => 1,
            'is_cotisation' => 0,
            'nb_personnes_max' => 1,
            'public' => 1,
        ));
        $this->assertNotFalse($this->produitId);

        // 2. Création d'un tarif pour ce produit via la façade
        $tarifId = $this->tarifs_model->create(array(
            'produit_id' => $this->produitId,
            'date' => '2020-01-01',
            'prix' => 42.50,
            'nb_tickets' => 0,
        ));
        $this->assertNotFalse($tarifId);

        // 3. get_by_id('reference', ...) doit joindre produits et tarifs
        $row = $this->tarifs_model->get_by_id('reference', $reference);
        $this->assertEquals($reference, $row['reference']);
        $this->assertEquals('Produit intégration test', $row['description']);
        $this->assertEquals(42.50, (float) $row['prix']);
        $this->assertEquals($this->produitId, (int) $row['produit_id']);

        // 4. get_tarif() doit retrouver le tarif applicable à une date donnée
        $tarif = $this->tarifs_model->get_tarif($reference, '2026-01-01');
        $this->assertNotEmpty($tarif);
        $this->assertEquals(42.50, (float) $tarif['prix']);

        // 5. selector() du produit et de la façade tarifs doivent tous deux
        //    exposer le produit avec la reference comme clé
        $produitSelector = $this->produits_model->selector(array('reference' => $reference));
        $this->assertArrayHasKey($reference, $produitSelector);

        $tarifsSelector = $this->tarifs_model->selector(array('reference' => $reference));
        $this->assertArrayHasKey($reference, $tarifsSelector);

        // 6. image() du tarif doit utiliser la description du produit joint
        $image = $this->tarifs_model->image($tarifId);
        $this->assertStringContainsString('Produit intégration test', $image);

        // 7. select_page() scopé au produit doit lister ce tarif
        $page = $this->tarifs_model->select_page($this->produitId);
        $ids = array_column($page, 'id');
        $this->assertContains((int) $tarifId, array_map('intval', $ids));

        // 8. update() du tarif : seules les colonnes de prix sont modifiées
        $this->tarifs_model->update('id', array('id' => $tarifId, 'prix' => 55.00), $tarifId);
        $updated = $this->tarifs_model->get_by_id('id', $tarifId);
        $this->assertEquals(55.00, (float) $updated['prix']);

        // 9. update() du produit
        $this->produits_model->update('id', array('id' => $this->produitId, 'description' => 'Produit modifié'), $this->produitId);
        $updatedProduit = $this->produits_model->get_by_id('id', $this->produitId);
        $this->assertEquals('Produit modifié', $updatedProduit['description']);

        // 10. Suppression du tarif puis du produit
        $this->tarifs_model->delete(array('id' => $tarifId));
        $this->assertEmpty($this->tarifs_model->get_by_id('id', $tarifId));

        $this->produits_model->delete(array('id' => $this->produitId));
        $this->assertEmpty($this->produits_model->get_by_id('id', $this->produitId));
        $this->produitId = null; // déjà supprimé, rien à nettoyer
    }
}
