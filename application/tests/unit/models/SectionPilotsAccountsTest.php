<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'models/membres_model.php';
require_once APPPATH . 'models/comptes_model.php';
require_once APPPATH . 'tests/unit/models/ComptesModelSectionTest.php';

/**
 * Test pour les méthodes section_pilots() et section_client_accounts()
 * qui retournent les pilotes et comptes de la section active
 */
class SectionPilotsAccountsTest extends TestCase
{
    /**
     * Test de la méthode section_pilots avec section active
     */
    public function test_section_pilots_returns_pilots_from_active_section()
    {
        // Arrange - Simuler la base de données et les résultats
        $rows = [
            ['mlogin' => 'pilot1'],
            ['mlogin' => 'pilot2'],
        ];

        $db = new FakeDb($rows);
        $sections = new StubSectionsModel(['id' => 2, 'nom' => 'Section 2']);
        $membres = new StubMembresModelForTest();

        $model = new TestableMembresModel($db, $sections, $membres);

        // Act - Appeler section_pilots() avec section active (0)
        $result = $model->section_pilots(0, true);

        // Assert - Vérifier que la requête filtre sur la section active
        $this->assertContains(['comptes.codec', '411'], $db->wheres, 'Should filter on 411 accounts');
        $this->assertContains(['comptes.club', 2], $db->wheres, 'Should filter on active section ID');
        $this->assertContains(['comptes.actif', 1], $db->wheres, 'Should filter on active accounts');
        $this->assertContains(['comptes.masked', 0], $db->wheres, 'Should exclude masked accounts');
        $this->assertContains(['membres.actif', 1], $db->wheres, 'Should filter on active members');

        // Vérifier que le sélecteur contient une entrée vide
        $this->assertArrayHasKey('', $result, 'Selector should have empty option');

        // Vérifier les jointures
        $this->assertCount(1, $db->joins, 'Should have one join');
        $this->assertEquals('comptes', $db->joins[0][0], 'Should join with comptes table');
    }

    /**
     * Test de la méthode section_pilots avec section spécifique
     */
    public function test_section_pilots_returns_pilots_from_specific_section()
    {
        // Arrange
        $rows = [
            ['mlogin' => 'pilot3'],
        ];

        $db = new FakeDb($rows);
        $sections = new StubSectionsModel(['id' => 2, 'nom' => 'Section 2']);
        $membres = new StubMembresModelForTest();

        $model = new TestableMembresModel($db, $sections, $membres);

        // Act - Appeler section_pilots() avec section spécifique (5)
        $result = $model->section_pilots(5, true);

        // Assert - Vérifier que la requête filtre sur la section spécifiée
        $this->assertContains(['comptes.club', 5], $db->wheres, 'Should filter on specified section ID');
    }

    /**
     * Test de la méthode section_pilots sans filtre sur membres actifs
     */
    public function test_section_pilots_returns_all_members_when_only_actif_false()
    {
        // Arrange
        $rows = [
            ['mlogin' => 'pilot1'],
            ['mlogin' => 'pilot_inactive'],
        ];

        $db = new FakeDb($rows);
        $sections = new StubSectionsModel(['id' => 2, 'nom' => 'Section 2']);
        $membres = new StubMembresModelForTest();

        $model = new TestableMembresModel($db, $sections, $membres);

        // Act - Appeler section_pilots() avec only_actif = false
        $result = $model->section_pilots(2, false);

        // Assert - Vérifier qu'il n'y a pas de filtre sur membres.actif
        $activeMemberFilter = array_filter($db->wheres, function($where) {
            return $where[0] === 'membres.actif';
        });
        $this->assertEmpty($activeMemberFilter, 'Should not filter on membres.actif when only_actif=false');
    }

    /**
     * Test de la méthode section_client_accounts avec section active
     */
    public function test_section_client_accounts_returns_accounts_from_active_section()
    {
        // Arrange
        $rows = [
            ['id' => 1, 'codec' => '411', 'nom' => 'Compte A', 'pilote' => 'pilot1'],
            ['id' => 2, 'codec' => '411', 'nom' => 'Compte B', 'pilote' => 'pilot2'],
        ];

        $db = new FakeDb($rows);
        $membres = new StubMembresModel([]);
        $sections = new StubSectionsModel(['id' => 3, 'nom' => 'Section 3']);

        $model = new TestableComptesModel($db, $membres, $sections);

        // Act - Appeler section_client_accounts() avec section active (0)
        $result = $model->section_client_accounts(0, true);

        // Assert - Vérifier que la requête filtre sur la section active
        $this->assertContains(['comptes.club', 3], $db->wheres, 'Should filter on active section ID');
        $this->assertContains(['comptes.actif', 1], $db->wheres, 'Should filter on active accounts');
        $this->assertContains(['comptes.masked', 0], $db->wheres, 'Should exclude masked accounts');

        // Vérifier que le sélecteur contient une entrée vide
        $this->assertArrayHasKey('', $result, 'Selector should have empty option');
        $this->assertEquals('-- Sélectionner --', $result[''], 'Empty option should have correct label');

        // Vérifier que les comptes sont dans le sélecteur
        $this->assertArrayHasKey(1, $result, 'Should contain account with id 1');
        $this->assertArrayHasKey(2, $result, 'Should contain account with id 2');

        // Vérifier le format des valeurs
        $this->assertEquals('(411) Compte A', $result[1], 'Account should have format (codec) nom');
    }

    /**
     * Test de la méthode section_client_accounts avec section spécifique
     */
    public function test_section_client_accounts_returns_accounts_from_specific_section()
    {
        // Arrange
        $rows = [
            ['id' => 5, 'codec' => '411', 'nom' => 'Compte C', 'pilote' => 'pilot5'],
        ];

        $db = new FakeDb($rows);
        $membres = new StubMembresModel([]);
        $sections = new StubSectionsModel(['id' => 3, 'nom' => 'Section 3']);

        $model = new TestableComptesModel($db, $membres, $sections);

        // Act - Appeler section_client_accounts() avec section spécifique (7)
        $result = $model->section_client_accounts(7, true);

        // Assert - Vérifier que la requête filtre sur la section spécifiée
        $this->assertContains(['comptes.club', 7], $db->wheres, 'Should filter on specified section ID');
    }

    /**
     * Test de la méthode section_client_accounts sans filtre sur membres actifs
     */
    public function test_section_client_accounts_includes_inactive_members_when_only_actif_false()
    {
        // Arrange
        $rows = [
            ['id' => 1, 'codec' => '411', 'nom' => 'Compte A', 'pilote' => 'pilot1'],
            ['id' => 2, 'codec' => '411', 'nom' => 'Compte B', 'pilote' => 'pilot_inactive'],
        ];

        $db = new FakeDb($rows);
        $membres = new StubMembresModel([]);
        $sections = new StubSectionsModel(['id' => 3, 'nom' => 'Section 3']);

        $model = new TestableComptesModel($db, $membres, $sections);

        // Act - Appeler section_client_accounts() avec only_actif = false
        $result = $model->section_client_accounts(3, false);

        // Assert - Vérifier qu'il n'y a pas de jointure avec membres
        $this->assertEmpty($db->joins, 'Should not join with membres when only_actif=false');
    }

    /**
     * Test de la méthode section_client_accounts filtre avec membres actifs
     */
    public function test_section_client_accounts_joins_with_membres_when_only_actif_true()
    {
        // Arrange
        $rows = [
            ['id' => 1, 'codec' => '411', 'nom' => 'Compte A', 'pilote' => 'pilot1'],
        ];

        $db = new FakeDb($rows);
        $membres = new StubMembresModel([]);
        $sections = new StubSectionsModel(['id' => 3, 'nom' => 'Section 3']);

        $model = new TestableComptesModel($db, $membres, $sections);

        // Act - Appeler section_client_accounts() avec only_actif = true
        $result = $model->section_client_accounts(3, true);

        // Assert - Vérifier qu'il y a une jointure avec membres et un filtre sur actif
        $this->assertCount(1, $db->joins, 'Should join with membres when only_actif=true');
        $this->assertEquals('membres', $db->joins[0][0], 'Should join with membres table');
        $this->assertContains(['membres.actif', 1], $db->wheres, 'Should filter on active membres');
    }
}

// Classes spécifiques pour les tests de Membres_model
// Les autres classes (FakeDbResult, FakeDb, etc.) sont réutilisées de ComptesModelSectionTest.php

class StubMembresModelForTest
{
    public function image($mlogin, $short = false)
    {
        // Simuler le comportement de image()
        return "Pilote $mlogin";
    }
}

class TestableMembresModel extends Membres_model
{
    private $stub_membres;

    public function __construct($db, $sections_model, $stub_membres)
    {
        // Avoid parent constructor to bypass CI loader wiring for unit tests
        $this->db = $db;
        $this->sections_model = $sections_model;
        $this->stub_membres = $stub_membres;
        $this->table = 'membres';
        $this->primary_key = 'mlogin';
    }

    public function section_id()
    {
        return $this->sections_model->section_id();
    }

    public function image($key, $short = false)
    {
        return $this->stub_membres->image($key, $short);
    }
}
