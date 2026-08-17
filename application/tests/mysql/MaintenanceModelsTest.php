<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests MySQL — Modeles CRUD du module Maintenance des Aeronefs (Phase 2)
 *
 * Un test par modele au minimum, plus la reproduction de la logique
 * Formation_programme_model::get_by_section() et le transfert d'un
 * equipement (PRD Parcours 5).
 *
 * @see doc/plans/maintenance_aeronefs_plan.md (Phase 2)
 */
class MaintenanceModelsTest extends TestCase
{
    /** @var RealDatabase */
    private $db;
    private $CI;
    private $membre_login;
    private $macimmat;
    private $section_ids = array();

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->db = $this->CI->db;

        $this->CI->load->model('maintenance_equipement_model');
        $this->CI->load->model('maintenance_programme_model');
        $this->CI->load->model('maintenance_programme_section_model');
        $this->CI->load->model('maintenance_tache_model');
        $this->CI->load->model('maintenance_dossier_model');
        $this->CI->load->model('maintenance_operation_model');
        $this->CI->load->model('maintenance_realisation_model');
        $this->CI->load->model('maintenance_bulletin_model');

        $membre = $this->db->limit(1)->get('membres')->row_array();
        $this->membre_login = $membre ? $membre['mlogin'] : null;

        $aircraft = $this->db->limit(1)->get('machinesa')->row_array();
        $this->macimmat = $aircraft ? $aircraft['macimmat'] : null;

        $sections = $this->db->query("SELECT id FROM sections ORDER BY id LIMIT 2")->result_array();
        $this->section_ids = array_column($sections, 'id');
    }

    // ---------------------------------------------------------------
    // maintenance_equipement_model
    // ---------------------------------------------------------------

    public function testEquipementCrudAndTransfer()
    {
        $id = $this->CI->maintenance_equipement_model->create(array(
            'aeronef_id' => 'F-MEQ01',
            'nom'        => 'Moteur test',
        ));
        $this->assertNotFalse($id);

        $equipement = $this->CI->maintenance_equipement_model->get($id);
        $this->assertSame('F-MEQ01', $equipement['aeronef_id']);

        $liste = $this->CI->maintenance_equipement_model->get_by_aeronef('F-MEQ01');
        $this->assertCount(1, $liste);

        $selector = $this->CI->maintenance_equipement_model->get_selector('F-MEQ01');
        $this->assertArrayHasKey($id, $selector);

        // Transfert (PRD Parcours 5)
        $this->assertTrue((bool) $this->CI->maintenance_equipement_model->transferer($id, 'F-MEQ02'));

        $this->assertCount(0, $this->CI->maintenance_equipement_model->get_by_aeronef('F-MEQ01'));
        $transfere = $this->CI->maintenance_equipement_model->get_by_aeronef('F-MEQ02');
        $this->assertCount(1, $transfere);
        $this->assertSame($id, (int) $transfere[0]['id']);

        $this->assertNotEmpty($this->CI->maintenance_equipement_model->image($id));

        $this->db->where('id', $id)->delete('maintenance_equipements');
    }

    public function testEquipementModelListingsAndDeactivation()
    {
        if (!$this->macimmat) {
            $this->markTestSkipped('Aucun aeronef disponible pour ce test');
        }

        $id = $this->CI->maintenance_equipement_model->create(array(
            'aeronef_id' => $this->macimmat, 'nom' => 'Equipement listing test',
        ));

        $tous = array_column($this->CI->maintenance_equipement_model->get_all(false), 'id');
        $this->assertContains($id, $tous);

        $actifs = array_column($this->CI->maintenance_equipement_model->get_all(true), 'id');
        $this->assertContains($id, $actifs);

        $selector = $this->CI->maintenance_equipement_model->get_all_selector();
        $this->assertArrayHasKey($id, $selector);
        $this->assertStringContainsString($this->macimmat, $selector[$id]);

        // get_aeronef_selector()/get_aeronefs_by_section() ne listent que les
        // aeronefs actifs (machinesa.actif=1) -- l'aeronef de fixture n'est
        // pas necessairement actif, on cherche donc un aeronef actif reel.
        $actif = $this->db->where('actif', 1)->limit(1)->get('machinesa')->row_array();
        if ($actif) {
            $aeronef_selector = $this->CI->maintenance_equipement_model->get_aeronef_selector();
            $this->assertArrayHasKey($actif['macimmat'], $aeronef_selector);

            $aeronefs = array_column($this->CI->maintenance_equipement_model->get_aeronefs_by_section(null), 'macimmat');
            $this->assertContains($actif['macimmat'], $aeronefs);
        } else {
            $this->assertArrayHasKey('', $this->CI->maintenance_equipement_model->get_aeronef_selector());
        }

        $this->assertTrue($this->CI->maintenance_equipement_model->desactiver($id));
        $actifs_apres = array_column($this->CI->maintenance_equipement_model->get_all(true), 'id');
        $this->assertNotContains($id, $actifs_apres);
        $tous_apres = array_column($this->CI->maintenance_equipement_model->get_all(false), 'id');
        $this->assertContains($id, $tous_apres);

        $this->assertTrue($this->CI->maintenance_equipement_model->reactiver($id));
        $actifs_reactive = array_column($this->CI->maintenance_equipement_model->get_all(true), 'id');
        $this->assertContains($id, $actifs_reactive);

        $this->db->where('id', $id)->delete('maintenance_equipements');
    }

    // ---------------------------------------------------------------
    // maintenance_programme_model
    // ---------------------------------------------------------------

    public function testProgrammeModelGetBySectionMirrorsFormationLogic()
    {
        if (count($this->section_ids) < 2) {
            $this->markTestSkipped('Au moins 2 sections sont necessaires pour ce test');
        }
        list($section1, $section2) = $this->section_ids;

        $global_id = $this->CI->maintenance_programme_model->create(array(
            'code' => 'MPROG_GLOBAL_' . time(), 'titre' => 'Programme global',
        ));
        $sec1_id = $this->CI->maintenance_programme_model->create(array(
            'code' => 'MPROG_SEC1_' . time(), 'titre' => 'Programme section 1', 'section_id' => $section1,
        ));
        $sec2_id = $this->CI->maintenance_programme_model->create(array(
            'code' => 'MPROG_SEC2_' . time(), 'titre' => 'Programme section 2', 'section_id' => $section2,
        ));

        $for_section1 = array_column($this->CI->maintenance_programme_model->get_by_section($section1), 'id');
        $this->assertContains($global_id, $for_section1);
        $this->assertContains($sec1_id, $for_section1);
        $this->assertNotContains($sec2_id, $for_section1);

        $for_section2 = array_column($this->CI->maintenance_programme_model->get_by_section($section2), 'id');
        $this->assertContains($global_id, $for_section2);
        $this->assertContains($sec2_id, $for_section2);
        $this->assertNotContains($sec1_id, $for_section2);

        $for_all = array_column($this->CI->maintenance_programme_model->get_by_section(null), 'id');
        $this->assertContains($global_id, $for_all);
        $this->assertContains($sec1_id, $for_all);
        $this->assertContains($sec2_id, $for_all);

        // get_visibles() delegue a get_by_section($this->section_id) -- section mockee = 1
        $visibles = array_column($this->CI->maintenance_programme_model->get_visibles(), 'id');
        $this->assertSame($visibles, array_column($this->CI->maintenance_programme_model->get_by_section(1), 'id'));

        $global_code = $this->CI->maintenance_programme_model->get($global_id)['code'];
        $by_code = $this->CI->maintenance_programme_model->get_by_code($global_code);
        $this->assertSame($global_id, (int) $by_code['id']);
        $this->assertNotEmpty($this->CI->maintenance_programme_model->image($global_id));

        $this->db->where('id', $global_id)->delete('maintenance_programmes');
        $this->db->where('id', $sec1_id)->delete('maintenance_programmes');
        $this->db->where('id', $sec2_id)->delete('maintenance_programmes');
    }

    // ---------------------------------------------------------------
    // maintenance_programme_section_model / maintenance_tache_model
    // ---------------------------------------------------------------

    public function testProgrammeSectionAndTacheModels()
    {
        $programme_id = $this->CI->maintenance_programme_model->create(array(
            'code' => 'MPROG_STRUCT_' . time(), 'titre' => 'Programme structure',
        ));

        $ordre1 = $this->CI->maintenance_programme_section_model->get_next_ordre($programme_id);
        $this->assertSame(1, $ordre1);

        $section_id = $this->CI->maintenance_programme_section_model->create(array(
            'programme_id' => $programme_id, 'ordre' => $ordre1, 'titre' => 'Cellule',
        ));

        $ordre2 = $this->CI->maintenance_programme_section_model->get_next_ordre($programme_id);
        $this->assertSame(2, $ordre2);

        $sections = $this->CI->maintenance_programme_section_model->get_by_programme($programme_id);
        $this->assertCount(1, $sections);
        $this->assertSame('Cellule', $this->CI->maintenance_programme_section_model->image($section_id));

        $tache_ordre1 = $this->CI->maintenance_tache_model->get_next_ordre($section_id);
        $this->assertSame(1, $tache_ordre1);

        $tache_id = $this->CI->maintenance_tache_model->create(array(
            'programme_section_id' => $section_id, 'ordre' => $tache_ordre1, 'titre' => 'Vidange',
        ));

        $taches = $this->CI->maintenance_tache_model->get_by_programme_section($section_id);
        $this->assertCount(1, $taches);

        $taches_programme = $this->CI->maintenance_tache_model->get_by_programme($programme_id);
        $this->assertCount(1, $taches_programme);
        $this->assertSame('Cellule', $taches_programme[0]['section_titre']);
        $this->assertSame('Vidange', $this->CI->maintenance_tache_model->image($tache_id));

        $this->db->where('id', $tache_id)->delete('maintenance_taches');
        $this->db->where('id', $section_id)->delete('maintenance_programme_sections');
        $this->db->where('id', $programme_id)->delete('maintenance_programmes');
    }

    // ---------------------------------------------------------------
    // maintenance_dossier_model
    // ---------------------------------------------------------------

    public function testDossierModelCrudEntiteExistsAndLifecycle()
    {
        if (!$this->macimmat) {
            $this->markTestSkipped('Aucun aeronef disponible pour tester un dossier');
        }

        $programme_id = $this->CI->maintenance_programme_model->create(array(
            'code' => 'MPROG_DOSSIER_' . time(), 'titre' => 'Programme dossier',
        ));

        $equipement_id = $this->CI->maintenance_equipement_model->create(array(
            'aeronef_id' => $this->macimmat, 'nom' => 'Equipement dossier test',
        ));

        // entite_exists()
        $this->assertTrue($this->CI->maintenance_dossier_model->entite_exists('aeronef', $this->macimmat));
        $this->assertFalse($this->CI->maintenance_dossier_model->entite_exists('aeronef', 'F-INCONNU'));
        $this->assertTrue($this->CI->maintenance_dossier_model->entite_exists('equipement', $equipement_id));
        $this->assertFalse($this->CI->maintenance_dossier_model->entite_exists('equipement', 999999));

        $dossier_id = $this->CI->maintenance_dossier_model->ouvrir(array(
            'entite_type' => 'aeronef', 'entite_id' => $this->macimmat, 'programme_id' => $programme_id,
        ));
        $this->assertNotFalse($dossier_id);

        $dossier = $this->CI->maintenance_dossier_model->get($dossier_id);
        $this->assertSame('ouvert', $dossier['statut']);

        $full = $this->CI->maintenance_dossier_model->get_full($dossier_id);
        $this->assertStringContainsString('MPROG_DOSSIER_', $full['programme_code']);

        $by_entite = $this->CI->maintenance_dossier_model->get_by_entite('aeronef', $this->macimmat);
        $this->assertGreaterThanOrEqual(1, count($by_entite));

        $ouverts = $this->CI->maintenance_dossier_model->get_ouverts('aeronef', $this->macimmat);
        $this->assertGreaterThanOrEqual(1, count($ouverts));

        $by_programme = $this->CI->maintenance_dossier_model->get_by_programme($programme_id);
        $this->assertCount(1, $by_programme);

        $this->CI->maintenance_dossier_model->suspendre($dossier_id);
        $dossier = $this->CI->maintenance_dossier_model->get($dossier_id);
        $this->assertSame('suspendu', $dossier['statut']);
        $this->assertNotNull($dossier['date_suspension']);

        $this->CI->maintenance_dossier_model->reactiver($dossier_id);
        $dossier = $this->CI->maintenance_dossier_model->get($dossier_id);
        $this->assertSame('ouvert', $dossier['statut']);
        $this->assertNull($dossier['date_suspension']);

        $this->CI->maintenance_dossier_model->cloturer($dossier_id, 'cloture');
        $dossier = $this->CI->maintenance_dossier_model->get($dossier_id);
        $this->assertSame('cloture', $dossier['statut']);
        $this->assertNotNull($dossier['date_cloture']);

        $this->assertNotEmpty($this->CI->maintenance_dossier_model->image($dossier_id));

        $this->db->where('id', $dossier_id)->delete('maintenance_dossiers');
        $this->db->where('id', $equipement_id)->delete('maintenance_equipements');
        $this->db->where('id', $programme_id)->delete('maintenance_programmes');
    }

    // ---------------------------------------------------------------
    // maintenance_operation_model / maintenance_realisation_model
    // ---------------------------------------------------------------

    public function testOperationAndRealisationModels()
    {
        if (!$this->macimmat || !$this->membre_login) {
            $this->markTestSkipped('Aeronef et membre necessaires pour tester une operation');
        }

        $programme_id = $this->CI->maintenance_programme_model->create(array(
            'code' => 'MPROG_OP_' . time(), 'titre' => 'Programme operation',
        ));
        $section_id = $this->CI->maintenance_programme_section_model->create(array(
            'programme_id' => $programme_id, 'ordre' => 1, 'titre' => 'Section op',
        ));
        $tache_id = $this->CI->maintenance_tache_model->create(array(
            'programme_section_id' => $section_id, 'ordre' => 1, 'titre' => 'Tache op',
        ));
        $dossier_id = $this->CI->maintenance_dossier_model->ouvrir(array(
            'entite_type' => 'aeronef', 'entite_id' => $this->macimmat, 'programme_id' => $programme_id,
        ));

        $operation_id = $this->CI->maintenance_operation_model->create(array(
            'dossier_id' => $dossier_id, 'date_operation' => date('Y-m-d'),
            'mecano_id' => $this->membre_login, 'mode_saisie' => 'directe',
        ));
        $this->assertNotFalse($operation_id);

        $operation = $this->CI->maintenance_operation_model->get($operation_id);
        $this->assertSame('directe', $operation['mode_saisie']);

        $full = $this->CI->maintenance_operation_model->get_full($operation_id);
        $this->assertSame('aeronef', $full['entite_type']);

        $by_dossier = $this->CI->maintenance_operation_model->get_by_dossier($dossier_id);
        $this->assertCount(1, $by_dossier);
        $this->assertNotEmpty($this->CI->maintenance_operation_model->image($operation_id));

        // get_all() -- liste toutes sections (point d'entree dashboard, Etape 5.7)
        $toutes_sections = array_column($this->CI->maintenance_operation_model->get_all(null), 'id');
        $this->assertContains($operation_id, $toutes_sections);

        // Realisations : batch vide -> no-op valide (operation compte_rendu sans tache cochee, PRD EF4)
        $this->assertTrue($this->CI->maintenance_realisation_model->save_batch($operation_id, array()));
        $this->assertCount(0, $this->CI->maintenance_realisation_model->get_by_operation($operation_id));

        $this->assertNotFalse($this->CI->maintenance_realisation_model->save_batch($operation_id, array(
            $tache_id => array('statut' => 'fait', 'commentaire' => 'RAS'),
        )));
        $realisations = $this->CI->maintenance_realisation_model->get_by_operation($operation_id);
        $this->assertCount(1, $realisations);
        $this->assertSame('Tache op', $realisations[0]['tache_titre']);
        $this->assertNotEmpty($this->CI->maintenance_realisation_model->image($realisations[0]['id']));

        $this->CI->maintenance_realisation_model->delete_by_operation($operation_id);
        $this->assertCount(0, $this->CI->maintenance_realisation_model->get_by_operation($operation_id));

        $this->db->where('id', $operation_id)->delete('maintenance_operations');
        $this->db->where('id', $dossier_id)->delete('maintenance_dossiers');
        $this->db->where('id', $tache_id)->delete('maintenance_taches');
        $this->db->where('id', $section_id)->delete('maintenance_programme_sections');
        $this->db->where('id', $programme_id)->delete('maintenance_programmes');
    }

    // ---------------------------------------------------------------
    // maintenance_bulletin_model
    // ---------------------------------------------------------------

    public function testBulletinModelStatutLifecycle()
    {
        if (!$this->membre_login) {
            $this->markTestSkipped('Aucun membre disponible pour tester un bulletin');
        }

        $this->db->insert('archived_documents', array(
            'file_path'         => '/tmp/mbulletin_test.pdf',
            'original_filename' => 'mbulletin_test.pdf',
            'uploaded_by'       => $this->membre_login,
            'uploaded_at'       => date('Y-m-d H:i:s'),
            'machine_immat'     => 'F-MBUL01',
        ));
        $document_id = $this->db->insert_id();

        // Pas de ligne de statut encore -> defaut 'a_traiter'
        $this->assertSame('a_traiter', $this->CI->maintenance_bulletin_model->get_statut($document_id));
        $this->assertSame(array(), $this->CI->maintenance_bulletin_model->get_by_document($document_id));

        $this->assertTrue($this->CI->maintenance_bulletin_model->set_statut($document_id, 'traite'));
        $this->assertSame('traite', $this->CI->maintenance_bulletin_model->get_statut($document_id));

        // Deuxieme appel : met a jour la ligne existante, pas de doublon
        $this->assertTrue($this->CI->maintenance_bulletin_model->set_statut($document_id, 'non_applicable'));
        $this->assertSame('non_applicable', $this->CI->maintenance_bulletin_model->get_statut($document_id));
        $count = $this->db->where('archived_document_id', $document_id)->get('maintenance_bulletin_statuts')->num_rows();
        $this->assertSame(1, $count);

        $bulletins = $this->CI->maintenance_bulletin_model->get_by_machine('F-MBUL01');
        $this->assertCount(1, $bulletins);
        $this->assertSame('non_applicable', $bulletins[0]['statut']);

        $row = $this->CI->maintenance_bulletin_model->get_by_document($document_id);
        $this->assertNotEmpty($this->CI->maintenance_bulletin_model->image($row['id']));

        $this->db->where('archived_document_id', $document_id)->delete('maintenance_bulletin_statuts');
        $this->db->where('id', $document_id)->delete('archived_documents');
    }

    // ---------------------------------------------------------------
    // Tableau des potentiels : get_ouverts_aeronefs() / get_dernier_horametre()
    // ---------------------------------------------------------------

    public function testTableauPotentielsQueries()
    {
        if (!$this->macimmat || !$this->membre_login) {
            $this->markTestSkipped('Aeronef et membre necessaires pour tester le tableau des potentiels');
        }

        $programme_id = $this->CI->maintenance_programme_model->create(array(
            'code' => 'MPROG_TAB_' . time(), 'titre' => 'Programme tableau',
            'regle_butee_heures' => 1, 'seuil_heures' => 100,
        ));
        $dossier_id = $this->CI->maintenance_dossier_model->ouvrir(array(
            'entite_type' => 'aeronef', 'entite_id' => $this->macimmat, 'programme_id' => $programme_id,
        ));
        $operation_id = $this->CI->maintenance_operation_model->create(array(
            'dossier_id' => $dossier_id, 'date_operation' => date('Y-m-d'),
            'mecano_id' => $this->membre_login, 'mode_saisie' => 'directe',
            'horametre_releve' => 1234.5,
        ));
        $this->CI->load->library('Maintenance_potentiel');
        $this->CI->maintenance_potentiel->appliquer_operation($operation_id);

        $ouverts = $this->CI->maintenance_dossier_model->get_ouverts_aeronefs();
        $found = null;
        foreach ($ouverts as $row) {
            if ($row['id'] == $dossier_id) {
                $found = $row;
            }
        }
        $this->assertNotNull($found, 'get_ouverts_aeronefs() doit retourner le dossier ouvert');
        $this->assertStringContainsString('MPROG_TAB_', $found['programme_code']);
        $this->assertEquals(100, $found['heures_restantes_courant']);
        $this->assertEquals(1, $found['programme_regle_butee_heures']);

        // Filtre section : programme sans section_id (global) reste visible
        if (!empty($this->section_ids)) {
            $ouverts_section = $this->CI->maintenance_dossier_model->get_ouverts_aeronefs($this->section_ids[0]);
            $ids = array_column($ouverts_section, 'id');
            $this->assertContains($dossier_id, $ids);
        }

        $horametre = $this->CI->maintenance_operation_model->get_dernier_horametre('aeronef', $this->macimmat);
        $this->assertEquals(1234.5, $horametre);

        $this->db->where('id', $operation_id)->delete('maintenance_operations');
        $this->db->where('id', $dossier_id)->delete('maintenance_dossiers');
        $this->db->where('id', $programme_id)->delete('maintenance_programmes');
    }
}
