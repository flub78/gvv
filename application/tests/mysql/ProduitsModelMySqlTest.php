<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL integration tests for Produits_model
 *
 * Étape 6 de doc/plans/refactoring_produits_tarifs_plan.md.
 *
 * @package tests
 */
class ProduitsModelMySqlTest extends TestCase
{
    private $CI;
    private $model;

    /** @var array Ids créés par les tests, nettoyés dans tearDown() */
    private $createdIds = array();

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        $this->CI->load->model('produits_model');
        $this->model = $this->CI->produits_model;
    }

    protected function tearDown(): void
    {
        foreach ($this->createdIds as $id) {
            $this->CI->db->delete('produits', array('id' => $id));
        }
        $this->createdIds = array();
    }

    public function testModelInstantiation()
    {
        $this->assertNotNull($this->model, "Produits_model should be instantiated");
        $this->assertEquals('produits', $this->model->table, "Model should reference 'produits' table");
        $this->assertEquals('id', $this->model->primary_key(), "Primary key should be 'id'");
    }

    public function testCreateGetUpdateDeleteRoundtrip()
    {
        $reference = 'TEST_PRODUIT_' . uniqid();

        $id = $this->model->create(array(
            'reference' => $reference,
            'description' => 'Produit de test',
            'compte' => 107,
            'club' => 1,
            'is_cotisation' => 0,
            'nb_personnes_max' => 1,
            'public' => 1,
        ));
        $this->assertNotFalse($id, "create() should return a new id");
        $this->createdIds[] = $id;

        $row = $this->model->get_by_id('id', $id);
        $this->assertEquals($reference, $row['reference']);
        $this->assertEquals('Produit de test', $row['description']);
        $this->assertNotEmpty($row['created_at'], "created_at should be auto-injected");
        $this->assertNotEmpty($row['created_by'], "created_by should be auto-injected");
        $this->assertNotEmpty($row['updated_at'], "updated_at should be auto-injected");

        $this->model->update('id', array('id' => $id, 'description' => 'Produit modifié'));
        $updated = $this->model->get_by_id('id', $id);
        $this->assertEquals('Produit modifié', $updated['description']);
        $this->assertEquals($row['created_at'], $updated['created_at'], "created_at must not change on update");

        $this->model->delete(array('id' => $id));
        $gone = $this->model->get_by_id('id', $id);
        $this->assertEmpty($gone, "produit should no longer exist after delete()");
        $this->createdIds = array_diff($this->createdIds, array($id));
    }

    public function testImageReturnsDescriptionWhenPresent()
    {
        $id = $this->model->create(array(
            'reference' => 'TEST_IMG_DESC_' . uniqid(),
            'description' => 'Description visible',
            'compte' => 107,
            'club' => 1,
        ));
        $this->createdIds[] = $id;

        $this->assertEquals('Description visible', $this->model->image($id));
    }

    public function testImageFallsBackToReferenceWhenDescriptionEmpty()
    {
        $reference = 'TEST_IMG_REF_' . uniqid();
        $id = $this->model->create(array(
            'reference' => $reference,
            'description' => '',
            'compte' => 107,
            'club' => 1,
        ));
        $this->createdIds[] = $id;

        $this->assertEquals($reference, $this->model->image($id));
    }

    public function testSelectorReturnsReferenceKeyedArray()
    {
        // La session de test (MockSession) est positionnée sur la section 1 :
        // le selector doit donc être filtré sur club=1 et ne renvoyer que des
        // références existant réellement dans cette section (créées par la
        // migration 146 depuis les tarifs existants).
        $selector = $this->model->selector();

        $this->assertIsArray($selector);
        $this->assertNotEmpty($selector, "selector() should return known section-1 products");
        $this->assertArrayHasKey('Treuillé', $selector, "selector() keys should be the product reference");

        $produits_club1 = $this->CI->db->query(
            "SELECT COUNT(*) AS n FROM produits WHERE club = 1"
        )->row_array();
        $this->assertCount((int) $produits_club1['n'], $selector);
    }
}
