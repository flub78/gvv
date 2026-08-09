<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests MySQL — Migrations 155 a 162 (Phases 1 et 4, module Maintenance des Aeronefs)
 *
 * Couvre les points de validation de la Phase 1 du plan :
 * - document_types.scope accepte 'machine' sans casser les valeurs existantes
 * - creation des tables maintenance_equipements, maintenance_programmes,
 *   maintenance_programme_sections, maintenance_taches, maintenance_dossiers,
 *   maintenance_operations, maintenance_realisations, maintenance_bulletin_statuts
 * - contraintes metier : programme sans section / section sans tache valides,
 *   dossiers multiples ouverts sur une meme entite, operation compte_rendu
 *   sans tache cochee, unicite du statut de bulletin par document
 * - roundtrip down()/up() de l'ensemble des 8 migrations
 *
 * Et de la Phase 4 (Etape 4.2) :
 * - colonne `actif` sur maintenance_programme_sections/maintenance_taches
 * - document_types seedes pour les programmes d'entretien et bulletins de service
 *
 * @see doc/plans/maintenance_aeronefs_plan.md
 */
class MaintenanceMigrationsTest extends TestCase
{
    /** @var RealDatabase */
    private $db;

    public static function setUpBeforeClass(): void
    {
        if (!class_exists('CI_Migration')) {
            require_once BASEPATH . 'libraries/Migration.php';
        }
        require_once APPPATH . 'migrations/155_document_types_scope_machine.php';
        require_once APPPATH . 'migrations/156_maintenance_equipements.php';
        require_once APPPATH . 'migrations/157_maintenance_programmes.php';
        require_once APPPATH . 'migrations/158_maintenance_dossiers.php';
        require_once APPPATH . 'migrations/159_maintenance_operations.php';
        require_once APPPATH . 'migrations/160_maintenance_bulletin_statuts.php';
        require_once APPPATH . 'migrations/161_maintenance_actif_column.php';
        require_once APPPATH . 'migrations/162_maintenance_document_types.php';
        require_once APPPATH . 'migrations/163_maintenance_compte_rendu_document_type.php';
    }

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;
        $this->applyAllUp();
    }

    private function applyAllUp()
    {
        (new Migration_Document_types_scope_machine())->up();
        (new Migration_Maintenance_equipements())->up();
        (new Migration_Maintenance_programmes())->up();
        (new Migration_Maintenance_actif_column())->up();
        (new Migration_Maintenance_dossiers())->up();
        (new Migration_Maintenance_operations())->up();
        (new Migration_Maintenance_bulletin_statuts())->up();
        (new Migration_Maintenance_document_types())->up();
        (new Migration_Maintenance_compte_rendu_document_type())->up();
    }

    private function tableExists($table)
    {
        $t = $this->db->escape_str($table);
        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t'"
        )->row_array();
        return isset($row['cnt']) && (int) $row['cnt'] > 0;
    }

    private function columnExists($table, $column)
    {
        $t = $this->db->escape_str($table);
        $c = $this->db->escape_str($column);
        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND COLUMN_NAME = '$c'"
        )->row_array();
        return isset($row['cnt']) && (int) $row['cnt'] > 0;
    }

    // ---------------------------------------------------------------
    // Migration 155 — document_types.scope + 'machine'
    // ---------------------------------------------------------------

    public function testScopeEnumIncludesMachineAndExistingValues()
    {
        $col = $this->db->query(
            "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'document_types' AND COLUMN_NAME = 'scope'"
        )->row_array();

        $this->assertStringContainsString("'machine'", $col['COLUMN_TYPE']);
        $this->assertStringContainsString("'pilot'", $col['COLUMN_TYPE']);
        $this->assertStringContainsString("'section'", $col['COLUMN_TYPE']);
        $this->assertStringContainsString("'club'", $col['COLUMN_TYPE']);
    }

    public function testExistingDocumentTypeUnaffectedByScopeMigration()
    {
        $row = $this->db->where('code', 'medical')->get('document_types')->row_array();
        if (!$row) {
            $this->markTestSkipped("Type de document 'medical' absent de ce schema");
        }
        $this->assertSame('pilot', $row['scope']);
    }

    public function testNewDocumentTypeWithMachineScopeCanBeCreated()
    {
        $code = 'mig155_test_' . time();
        $this->db->insert('document_types', array(
            'code'  => $code,
            'name'  => 'Test migration 155',
            'scope' => 'machine',
        ));
        $id = $this->db->insert_id();

        $row = $this->db->where('id', $id)->get('document_types')->row_array();
        $this->assertSame('machine', $row['scope']);

        $this->db->where('id', $id)->delete('document_types');
    }

    // ---------------------------------------------------------------
    // Migration 156 — maintenance_equipements
    // ---------------------------------------------------------------

    public function testMaintenanceEquipementsTableCreated()
    {
        $this->assertTrue($this->tableExists('maintenance_equipements'));
        foreach (array('aeronef_id', 'nom', 'description', 'actif', 'created_at', 'updated_at', 'created_by', 'updated_by') as $col) {
            $this->assertTrue($this->columnExists('maintenance_equipements', $col), "Colonne $col manquante");
        }
    }

    public function testEquipementCanBeTransferredToAnotherAeronef()
    {
        $this->db->insert('maintenance_equipements', array(
            'aeronef_id' => 'F-TEST01',
            'nom'        => 'Parachute test migration',
        ));
        $id = $this->db->insert_id();

        $this->db->where('id', $id)->update('maintenance_equipements', array('aeronef_id' => 'F-TEST02'));
        $row = $this->db->where('id', $id)->get('maintenance_equipements')->row_array();
        $this->assertSame('F-TEST02', $row['aeronef_id']);

        $this->db->where('id', $id)->delete('maintenance_equipements');
    }

    // ---------------------------------------------------------------
    // Migration 157 — maintenance_programmes / programme_sections / taches
    // ---------------------------------------------------------------

    public function testMaintenanceProgrammeTablesCreated()
    {
        $this->assertTrue($this->tableExists('maintenance_programmes'));
        $this->assertTrue($this->tableExists('maintenance_programme_sections'));
        $this->assertTrue($this->tableExists('maintenance_taches'));
        // Nommage sans ambiguite avec la table sections existante
        $this->assertFalse($this->tableExists('maintenance_sections'));
    }

    public function testProgrammeWithoutSectionsIsValid()
    {
        $code = 'mig157_test_' . time();
        $this->db->insert('maintenance_programmes', array(
            'code'  => $code,
            'titre' => 'Programme sans section (test migration)',
        ));
        $id = $this->db->insert_id();

        $sections = $this->db->where('programme_id', $id)->get('maintenance_programme_sections')->result_array();
        $this->assertCount(0, $sections);

        $this->db->where('id', $id)->delete('maintenance_programmes');
    }

    public function testSectionWithoutTachesIsValid()
    {
        $code = 'mig157_test2_' . time();
        $this->db->insert('maintenance_programmes', array(
            'code'  => $code,
            'titre' => 'Programme test migration 157',
        ));
        $programme_id = $this->db->insert_id();

        $this->db->insert('maintenance_programme_sections', array(
            'programme_id' => $programme_id,
            'ordre'        => 1,
            'titre'        => 'Section sans tache',
        ));
        $section_id = $this->db->insert_id();

        $taches = $this->db->where('programme_section_id', $section_id)->get('maintenance_taches')->result_array();
        $this->assertCount(0, $taches);

        $this->db->where('id', $section_id)->delete('maintenance_programme_sections');
        $this->db->where('id', $programme_id)->delete('maintenance_programmes');
    }

    // ---------------------------------------------------------------
    // Migration 158 — maintenance_dossiers
    // ---------------------------------------------------------------

    public function testMaintenanceDossiersTableCreated()
    {
        $this->assertTrue($this->tableExists('maintenance_dossiers'));
    }

    public function testEntiteCanHaveMultipleOpenDossiersOnDifferentProgrammes()
    {
        $this->db->insert('maintenance_programmes', array('code' => 'mig158_a_' . time(), 'titre' => 'Programme A'));
        $programme_a = $this->db->insert_id();
        $this->db->insert('maintenance_programmes', array('code' => 'mig158_b_' . time(), 'titre' => 'Programme B'));
        $programme_b = $this->db->insert_id();

        $this->db->insert('maintenance_dossiers', array(
            'entite_type'    => 'aeronef',
            'entite_id'      => 'F-TEST01',
            'programme_id'   => $programme_a,
            'statut'         => 'ouvert',
            'date_ouverture' => date('Y-m-d'),
        ));
        $dossier_a = $this->db->insert_id();

        $this->db->insert('maintenance_dossiers', array(
            'entite_type'    => 'aeronef',
            'entite_id'      => 'F-TEST01',
            'programme_id'   => $programme_b,
            'statut'         => 'ouvert',
            'date_ouverture' => date('Y-m-d'),
        ));
        $dossier_b = $this->db->insert_id();

        $ouverts = $this->db->where('entite_id', 'F-TEST01')->where('statut', 'ouvert')
            ->get('maintenance_dossiers')->result_array();
        $this->assertGreaterThanOrEqual(2, count($ouverts));

        $this->db->where('id', $dossier_a)->delete('maintenance_dossiers');
        $this->db->where('id', $dossier_b)->delete('maintenance_dossiers');
        $this->db->where('id', $programme_a)->delete('maintenance_programmes');
        $this->db->where('id', $programme_b)->delete('maintenance_programmes');
    }

    // ---------------------------------------------------------------
    // Migration 159 — maintenance_operations / maintenance_realisations
    // ---------------------------------------------------------------

    public function testMaintenanceOperationsTablesCreated()
    {
        $this->assertTrue($this->tableExists('maintenance_operations'));
        $this->assertTrue($this->tableExists('maintenance_realisations'));
    }

    public function testCompteRenduOperationWithoutRealisationIsValid()
    {
        $membre = $this->db->limit(1)->get('membres')->row_array();
        if (!$membre) {
            $this->markTestSkipped('Aucun membre disponible pour tester une operation');
        }

        $this->db->insert('maintenance_programmes', array('code' => 'mig159_' . time(), 'titre' => 'Programme test 159'));
        $programme_id = $this->db->insert_id();

        $this->db->insert('maintenance_dossiers', array(
            'entite_type'    => 'aeronef',
            'entite_id'      => 'F-TEST01',
            'programme_id'   => $programme_id,
            'statut'         => 'ouvert',
            'date_ouverture' => date('Y-m-d'),
        ));
        $dossier_id = $this->db->insert_id();

        $this->db->insert('maintenance_operations', array(
            'dossier_id'     => $dossier_id,
            'date_operation' => date('Y-m-d'),
            'mecano_id'      => $membre['mlogin'],
            'mode_saisie'    => 'compte_rendu',
        ));
        $operation_id = $this->db->insert_id();

        $realisations = $this->db->where('operation_id', $operation_id)->get('maintenance_realisations')->result_array();
        $this->assertCount(0, $realisations);

        $this->db->where('id', $operation_id)->delete('maintenance_operations');
        $this->db->where('id', $dossier_id)->delete('maintenance_dossiers');
        $this->db->where('id', $programme_id)->delete('maintenance_programmes');
    }

    public function testRealisationUniquePerTachePerOperation()
    {
        $membre = $this->db->limit(1)->get('membres')->row_array();
        if (!$membre) {
            $this->markTestSkipped('Aucun membre disponible pour tester une realisation');
        }

        $this->db->insert('maintenance_programmes', array('code' => 'mig159b_' . time(), 'titre' => 'Programme test 159b'));
        $programme_id = $this->db->insert_id();
        $this->db->insert('maintenance_programme_sections', array('programme_id' => $programme_id, 'ordre' => 1, 'titre' => 'Section'));
        $section_id = $this->db->insert_id();
        $this->db->insert('maintenance_taches', array('programme_section_id' => $section_id, 'ordre' => 1, 'titre' => 'Tache'));
        $tache_id = $this->db->insert_id();
        $this->db->insert('maintenance_dossiers', array(
            'entite_type' => 'aeronef', 'entite_id' => 'F-TEST01', 'programme_id' => $programme_id,
            'statut' => 'ouvert', 'date_ouverture' => date('Y-m-d'),
        ));
        $dossier_id = $this->db->insert_id();
        $this->db->insert('maintenance_operations', array(
            'dossier_id' => $dossier_id, 'date_operation' => date('Y-m-d'),
            'mecano_id' => $membre['mlogin'], 'mode_saisie' => 'directe',
        ));
        $operation_id = $this->db->insert_id();

        $this->db->insert('maintenance_realisations', array(
            'operation_id' => $operation_id, 'tache_id' => $tache_id, 'statut' => 'fait',
        ));
        $realisation_id = $this->db->insert_id();

        // Deuxieme insertion pour la meme (operation_id, tache_id) : doit echouer (UNIQUE)
        $duplicate_rejected = false;
        try {
            $this->db->insert('maintenance_realisations', array(
                'operation_id' => $operation_id, 'tache_id' => $tache_id, 'statut' => 'non_fait',
            ));
        } catch (Exception $e) {
            $duplicate_rejected = true;
        }
        $this->assertTrue($duplicate_rejected, 'Une deuxieme realisation pour la meme tache/operation doit etre rejetee');

        $this->db->where('id', $realisation_id)->delete('maintenance_realisations');
        $this->db->where('id', $operation_id)->delete('maintenance_operations');
        $this->db->where('id', $dossier_id)->delete('maintenance_dossiers');
        $this->db->where('id', $tache_id)->delete('maintenance_taches');
        $this->db->where('id', $section_id)->delete('maintenance_programme_sections');
        $this->db->where('id', $programme_id)->delete('maintenance_programmes');
    }

    // ---------------------------------------------------------------
    // Migration 160 — maintenance_bulletin_statuts
    // ---------------------------------------------------------------

    public function testMaintenanceBulletinStatutsTableCreated()
    {
        $this->assertTrue($this->tableExists('maintenance_bulletin_statuts'));
    }

    public function testBulletinStatutUniquePerDocument()
    {
        $membre = $this->db->limit(1)->get('membres')->row_array();
        if (!$membre) {
            $this->markTestSkipped('Aucun membre disponible pour tester un bulletin');
        }

        $this->db->insert('archived_documents', array(
            'file_path'         => '/tmp/mig160_test.pdf',
            'original_filename' => 'mig160_test.pdf',
            'uploaded_by'       => $membre['mlogin'],
            'uploaded_at'       => date('Y-m-d H:i:s'),
        ));
        $document_id = $this->db->insert_id();

        $this->db->insert('maintenance_bulletin_statuts', array(
            'archived_document_id' => $document_id,
            'statut'               => 'a_traiter',
        ));
        $statut_id = $this->db->insert_id();

        $duplicate_rejected = false;
        try {
            $this->db->insert('maintenance_bulletin_statuts', array(
                'archived_document_id' => $document_id,
                'statut'               => 'traite',
            ));
        } catch (Exception $e) {
            $duplicate_rejected = true;
        }
        $this->assertTrue($duplicate_rejected, 'Un deuxieme statut pour le meme document doit etre rejete');

        $this->db->where('id', $statut_id)->delete('maintenance_bulletin_statuts');
        $this->db->where('id', $document_id)->delete('archived_documents');
    }

    // ---------------------------------------------------------------
    // Migration 161 — actif sur maintenance_programme_sections/maintenance_taches
    // ---------------------------------------------------------------

    public function testActifColumnPresentOnSectionsAndTaches()
    {
        $this->assertTrue($this->columnExists('maintenance_programme_sections', 'actif'));
        $this->assertTrue($this->columnExists('maintenance_taches', 'actif'));
    }

    public function testUpIsIdempotentWhenActifColumnAlreadyExists()
    {
        // applyAllUp() (setUp) l'a deja applique une fois ; un second appel ne doit pas erreur.
        $this->assertTrue((new Migration_Maintenance_actif_column())->up());
    }

    // ---------------------------------------------------------------
    // Migration 162 — document_types pour la maintenance
    // ---------------------------------------------------------------

    public function testMaintenanceDocumentTypesSeeded()
    {
        $programme_type = $this->db->where('code', 'maintenance_programme')->get('document_types')->row_array();
        $this->assertNotEmpty($programme_type);
        $this->assertSame('machine', $programme_type['scope']);

        $bulletin_type = $this->db->where('code', 'maintenance_bulletin')->get('document_types')->row_array();
        $this->assertNotEmpty($bulletin_type);
        $this->assertSame('machine', $bulletin_type['scope']);
    }

    public function testUpIsIdempotentWhenDocumentTypesAlreadySeeded()
    {
        // applyAllUp() (setUp) l'a deja applique une fois ; un second appel ne doit pas dupliquer les lignes.
        $this->assertTrue((new Migration_Maintenance_document_types())->up());

        $count = $this->db->where('code', 'maintenance_programme')->count_all_results('document_types');
        $this->assertSame(1, $count);
    }

    // ---------------------------------------------------------------
    // Migration 163 — document_type compte rendu de maintenance
    // ---------------------------------------------------------------

    public function testMaintenanceCompteRenduDocumentTypeSeeded()
    {
        $type = $this->db->where('code', 'maintenance_compte_rendu')->get('document_types')->row_array();
        $this->assertNotEmpty($type);
        $this->assertSame('machine', $type['scope']);
    }

    public function testUpIsIdempotentWhenCompteRenduTypeAlreadySeeded()
    {
        $this->assertTrue((new Migration_Maintenance_compte_rendu_document_type())->up());

        $count = $this->db->where('code', 'maintenance_compte_rendu')->count_all_results('document_types');
        $this->assertSame(1, $count);
    }

    // ---------------------------------------------------------------
    // Roundtrip down()/up() de l'ensemble des 9 migrations
    // ---------------------------------------------------------------

    public function testDownUpRoundtripAllMigrations()
    {
        // Defensive: archived_documents rows left behind by other test suites
        // (e.g. the Playwright maintenance smoke tests, which upload fixtures
        // but never delete them) reference these document_types and would
        // block the DELETE in migration 162's down() via the RESTRICT delete
        // rule on fk_archived_documents_type. This test verifies migration
        // reversibility, not data left over by unrelated suites, so clear it
        // unconditionally first — maintenance_bulletin_statuts cascades and
        // maintenance_programmes/operations.document_id is set NULL automatically.
        $this->db->query(
            "DELETE ad FROM archived_documents ad
             JOIN document_types dt ON dt.id = ad.document_type_id
             WHERE dt.code IN ('maintenance_programme', 'maintenance_bulletin')"
        );

        $this->assertTrue((new Migration_Maintenance_compte_rendu_document_type())->down(), 'down() 163 doit reussir');
        $this->assertTrue((new Migration_Maintenance_document_types())->down(), 'down() 162 doit reussir');
        $this->assertTrue((new Migration_Maintenance_bulletin_statuts())->down(), 'down() 160 doit reussir');
        $this->assertTrue((new Migration_Maintenance_operations())->down(), 'down() 159 doit reussir');
        $this->assertTrue((new Migration_Maintenance_dossiers())->down(), 'down() 158 doit reussir');
        $this->assertTrue((new Migration_Maintenance_actif_column())->down(), 'down() 161 doit reussir');
        $this->assertTrue((new Migration_Maintenance_programmes())->down(), 'down() 157 doit reussir');
        $this->assertTrue((new Migration_Maintenance_equipements())->down(), 'down() 156 doit reussir');
        $this->assertTrue((new Migration_Document_types_scope_machine())->down(), 'down() 155 doit reussir');

        $this->assertFalse($this->tableExists('maintenance_bulletin_statuts'));
        $this->assertFalse($this->tableExists('maintenance_realisations'));
        $this->assertFalse($this->tableExists('maintenance_operations'));
        $this->assertFalse($this->tableExists('maintenance_dossiers'));
        $this->assertFalse($this->tableExists('maintenance_taches'));
        $this->assertFalse($this->tableExists('maintenance_programme_sections'));
        $this->assertFalse($this->tableExists('maintenance_programmes'));
        $this->assertFalse($this->tableExists('maintenance_equipements'));

        $col = $this->db->query(
            "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'document_types' AND COLUMN_NAME = 'scope'"
        )->row_array();
        $this->assertStringNotContainsString("'machine'", $col['COLUMN_TYPE']);

        $count = $this->db->where('code', 'maintenance_programme')->count_all_results('document_types');
        $this->assertSame(0, $count);

        // Restaure l'etat migre pour le reste de la suite / l'application
        $this->applyAllUp();
        $this->assertTrue($this->tableExists('maintenance_bulletin_statuts'));
        $this->assertTrue($this->columnExists('maintenance_taches', 'actif'));
    }
}

