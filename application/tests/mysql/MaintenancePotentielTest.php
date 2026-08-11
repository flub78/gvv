<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests MySQL — Maintenance_potentiel (Phase 3)
 *
 * Couvre les methodes qui touchent reellement la base :
 * appliquer_operation() pour les 3 combinaisons de regle de butee,
 * etat_pire_cas() sur aeronef + equipements, et mise_a_jour_manuelle()
 * avec verification du marqueur MAINTENANCE dans les logs.
 *
 * Les cas limites purement calculatoires de calculer_etat() sont couverts
 * par application/tests/unit/libraries/MaintenancePotentielTest.php.
 *
 * @see doc/plans/maintenance_aeronefs_plan.md (Etape 3.1)
 */
class MaintenancePotentielTest extends TestCase
{
    /** @var RealDatabase */
    private $db;
    private $CI;
    private $potentiel;
    private $membre_login;
    private $macimmat;

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->db = $this->CI->db;

        $this->CI->load->library('Maintenance_potentiel');
        $this->potentiel = $this->CI->maintenance_potentiel;

        $this->CI->load->model('maintenance_programme_model');
        $this->CI->load->model('maintenance_dossier_model');
        $this->CI->load->model('maintenance_operation_model');
        $this->CI->load->model('maintenance_equipement_model');

        $membre = $this->db->limit(1)->get('membres')->row_array();
        $this->membre_login = $membre ? $membre['mlogin'] : null;

        $aircraft = $this->db->limit(1)->get('machinesa')->row_array();
        $this->macimmat = $aircraft ? $aircraft['macimmat'] : null;

        if (!$this->membre_login || !$this->macimmat) {
            $this->markTestSkipped('Un membre et un aeronef sont necessaires pour ces tests');
        }
    }

    private function creerProgramme($regles)
    {
        return $this->CI->maintenance_programme_model->create(array_merge(array(
            'code' => 'MPOT_' . uniqid(),
            'titre' => 'Programme test potentiel',
        ), $regles));
    }

    private function creerDossier($programme_id, $entite_type = 'aeronef', $entite_id = null)
    {
        return $this->CI->maintenance_dossier_model->ouvrir(array(
            'entite_type' => $entite_type,
            'entite_id' => $entite_id ?: $this->macimmat,
            'programme_id' => $programme_id,
        ));
    }

    private function creerOperation($dossier_id, $extra = array())
    {
        return $this->CI->maintenance_operation_model->create(array_merge(array(
            'dossier_id' => $dossier_id,
            'date_operation' => date('Y-m-d'),
            'mecano_id' => $this->membre_login,
            'mode_saisie' => 'directe',
        ), $extra));
    }

    private function nettoyer($programme_id, $dossier_id, $operation_id = null)
    {
        if ($operation_id) {
            $this->db->where('id', $operation_id)->delete('maintenance_operations');
        }
        $this->db->where('id', $dossier_id)->delete('maintenance_dossiers');
        $this->db->where('id', $programme_id)->delete('maintenance_programmes');
    }

    // ---------------------------------------------------------------
    // appliquer_operation() — 3 combinaisons de regle de butee
    // ---------------------------------------------------------------

    public function testAppliquerOperationRegleDateSeule()
    {
        $programme_id = $this->creerProgramme(array(
            'regle_butee_date' => 1, 'regle_butee_heures' => 0, 'periodicite_mois' => 6,
        ));
        $dossier_id = $this->creerDossier($programme_id);
        $operation_id = $this->creerOperation($dossier_id);

        $this->assertTrue($this->potentiel->appliquer_operation($operation_id));

        $dossier = $this->CI->maintenance_dossier_model->get($dossier_id);
        $this->assertSame(date('Y-m-d', strtotime('+6 months')), $dossier['echeance_courante']);
        $this->assertNull($dossier['heures_restantes_courant']);

        $this->nettoyer($programme_id, $dossier_id, $operation_id);
    }

    public function testAppliquerOperationRegleDateAvecNouvelleEcheanceExplicite()
    {
        $programme_id = $this->creerProgramme(array(
            'regle_butee_date' => 1, 'regle_butee_heures' => 0, 'periodicite_mois' => 6,
        ));
        $dossier_id = $this->creerDossier($programme_id);
        $operation_id = $this->creerOperation($dossier_id, array('nouvelle_echeance' => '2030-01-15'));

        $this->assertTrue($this->potentiel->appliquer_operation($operation_id));

        $dossier = $this->CI->maintenance_dossier_model->get($dossier_id);
        $this->assertSame('2030-01-15', $dossier['echeance_courante']);

        $this->nettoyer($programme_id, $dossier_id, $operation_id);
    }

    public function testAppliquerOperationRegleHeuresSeule()
    {
        $programme_id = $this->creerProgramme(array(
            'regle_butee_date' => 0, 'regle_butee_heures' => 1, 'seuil_heures' => 100.00,
        ));
        $dossier_id = $this->creerDossier($programme_id);
        $operation_id = $this->creerOperation($dossier_id, array('horametre_releve' => 1234.50));

        $this->assertTrue($this->potentiel->appliquer_operation($operation_id));

        $dossier = $this->CI->maintenance_dossier_model->get($dossier_id);
        $this->assertNull($dossier['echeance_courante']);
        $this->assertEqualsWithDelta(100.00, (float) $dossier['heures_restantes_courant'], 0.001);

        $this->nettoyer($programme_id, $dossier_id, $operation_id);
    }

    public function testAppliquerOperationLesDeuxRegles()
    {
        $programme_id = $this->creerProgramme(array(
            'regle_butee_date' => 1, 'regle_butee_heures' => 1,
            'periodicite_mois' => 12, 'seuil_heures' => 50.00,
        ));
        $dossier_id = $this->creerDossier($programme_id);
        $operation_id = $this->creerOperation($dossier_id, array('horametre_releve' => 500.00));

        $this->assertTrue($this->potentiel->appliquer_operation($operation_id));

        $dossier = $this->CI->maintenance_dossier_model->get($dossier_id);
        $this->assertSame(date('Y-m-d', strtotime('+12 months')), $dossier['echeance_courante']);
        $this->assertEqualsWithDelta(50.00, (float) $dossier['heures_restantes_courant'], 0.001);

        $this->nettoyer($programme_id, $dossier_id, $operation_id);
    }

    public function testAppliquerOperationSansDonneesUtilesNeChangeRien()
    {
        // Regle horaire active mais aucun horametre_releve saisi sur l'operation : rien a mettre a jour.
        $programme_id = $this->creerProgramme(array(
            'regle_butee_date' => 0, 'regle_butee_heures' => 1, 'seuil_heures' => 100.00,
        ));
        $dossier_id = $this->creerDossier($programme_id);
        $operation_id = $this->creerOperation($dossier_id);

        $this->assertTrue($this->potentiel->appliquer_operation($operation_id));

        $dossier = $this->CI->maintenance_dossier_model->get($dossier_id);
        $this->assertNull($dossier['heures_restantes_courant']);

        $this->nettoyer($programme_id, $dossier_id, $operation_id);
    }

    // ---------------------------------------------------------------
    // etat_pire_cas()
    // ---------------------------------------------------------------

    public function testEtatPireCasSansDossierEstAJour()
    {
        $this->assertSame('a_jour', $this->potentiel->etat_pire_cas('F-INCONNU-POT'));
    }

    public function testEtatPireCasCombineAeronefEtEquipementsActifs()
    {
        $programme_id = $this->creerProgramme(array('regle_butee_date' => 1));

        // Dossier aeronef : echeance proche (dans 5 jours)
        $dossier_aeronef = $this->creerDossier($programme_id, 'aeronef', $this->macimmat);
        $this->db->where('id', $dossier_aeronef)->update('maintenance_dossiers', array(
            'echeance_courante' => date('Y-m-d', strtotime('+5 days')),
        ));

        // Equipement actif avec un dossier depasse -> doit degrader l'etat global
        $equipement_id = $this->CI->maintenance_equipement_model->create(array(
            'aeronef_id' => $this->macimmat, 'nom' => 'Equipement potentiel actif',
        ));
        $dossier_equipement = $this->creerDossier($programme_id, 'equipement', $equipement_id);
        $this->db->where('id', $dossier_equipement)->update('maintenance_dossiers', array(
            'echeance_courante' => date('Y-m-d', strtotime('-1 day')),
        ));

        // Equipement inactif : ne doit pas etre pris en compte
        $equipement_inactif_id = $this->CI->maintenance_equipement_model->create(array(
            'aeronef_id' => $this->macimmat, 'nom' => 'Equipement potentiel inactif', 'actif' => 0,
        ));
        $dossier_inactif = $this->creerDossier($programme_id, 'equipement', $equipement_inactif_id);
        $this->db->where('id', $dossier_inactif)->update('maintenance_dossiers', array(
            'echeance_courante' => date('Y-m-d', strtotime('-10 days')),
        ));

        $this->assertSame('depasse', $this->potentiel->etat_pire_cas($this->macimmat));

        $this->db->where('id', $dossier_inactif)->delete('maintenance_dossiers');
        $this->db->where('id', $dossier_equipement)->delete('maintenance_dossiers');
        $this->db->where('id', $dossier_aeronef)->delete('maintenance_dossiers');
        $this->db->where('id', $equipement_inactif_id)->delete('maintenance_equipements');
        $this->db->where('id', $equipement_id)->delete('maintenance_equipements');
        $this->db->where('id', $programme_id)->delete('maintenance_programmes');
    }

    // ---------------------------------------------------------------
    // mise_a_jour_manuelle()
    // ---------------------------------------------------------------

    public function testMiseAJourManuelleMetAJourEtJournaliseAvecMarqueurMaintenance()
    {
        $programme_id = $this->creerProgramme(array('regle_butee_date' => 1));
        $dossier_id = $this->creerDossier($programme_id);

        $user_marker = 'phase3_test_user_' . uniqid();
        $result = $this->potentiel->mise_a_jour_manuelle(
            $dossier_id,
            array('echeance_courante' => '2031-06-15', 'statut' => 'cloture'),
            $user_marker
        );
        $this->assertTrue($result);

        $dossier = $this->CI->maintenance_dossier_model->get($dossier_id);
        $this->assertSame('2031-06-15', $dossier['echeance_courante']);
        // 'statut' n'est pas un champ autorise pour une correction de potentiel : ignore.
        $this->assertSame('ouvert', $dossier['statut']);

        $logfile = APPPATH . 'logs/log-' . date('Y-m-d') . '.php';
        $this->assertFileExists($logfile);
        $contenu = file_get_contents($logfile);
        $this->assertStringContainsString('MAINTENANCE', $contenu);
        $this->assertStringContainsString($user_marker, $contenu);
        $this->assertStringContainsString((string) $dossier_id, $contenu);

        $this->nettoyer($programme_id, $dossier_id);
    }

    public function testMiseAJourManuelleSansChampAutoriseRetourneFalse()
    {
        $programme_id = $this->creerProgramme(array('regle_butee_date' => 1));
        $dossier_id = $this->creerDossier($programme_id);

        $result = $this->potentiel->mise_a_jour_manuelle($dossier_id, array('statut' => 'cloture'), 'test_user');
        $this->assertFalse($result);

        $this->nettoyer($programme_id, $dossier_id);
    }

    // ---------------------------------------------------------------
    // lister_echeances_actives() -- Phase 7, point d'ancrage alarmes (PRD EF10.1)
    // ---------------------------------------------------------------

    public function testListerEcheancesActivesNeRetientQueLesDossiersOuverts()
    {
        $programme_id = $this->creerProgramme(array('regle_butee_date' => 1, 'periodicite_mois' => 6));

        $dossier_ouvert = $this->creerDossier($programme_id);
        $this->db->where('id', $dossier_ouvert)->update('maintenance_dossiers', array(
            'echeance_courante' => date('Y-m-d', strtotime('+5 days')),
        ));

        $dossier_suspendu = $this->creerDossier($programme_id);
        $this->CI->maintenance_dossier_model->suspendre($dossier_suspendu);

        $dossier_cloture = $this->creerDossier($programme_id);
        $this->CI->maintenance_dossier_model->cloturer($dossier_cloture);

        $echeances = $this->potentiel->lister_echeances_actives();
        $dossier_ids = array_column($echeances, 'dossier_id');

        $this->assertContains($dossier_ouvert, $dossier_ids);
        $this->assertNotContains($dossier_suspendu, $dossier_ids);
        $this->assertNotContains($dossier_cloture, $dossier_ids);

        $this->db->where('id', $dossier_cloture)->delete('maintenance_dossiers');
        $this->db->where('id', $dossier_suspendu)->delete('maintenance_dossiers');
        $this->nettoyer($programme_id, $dossier_ouvert);
    }

    public function testListerEcheancesActivesStructureExploitableSansHtml()
    {
        $programme_id = $this->creerProgramme(array(
            'regle_butee_date' => 1, 'regle_butee_heures' => 1,
            'periodicite_mois' => 12, 'seuil_heures' => 50.00,
        ));
        $dossier_id = $this->creerDossier($programme_id);
        $operation_id = $this->creerOperation($dossier_id, array('horametre_releve' => 500.00));
        $this->potentiel->appliquer_operation($operation_id);

        $echeances = $this->potentiel->lister_echeances_actives();
        $entry = null;
        foreach ($echeances as $candidate) {
            if ($candidate['dossier_id'] == $dossier_id) {
                $entry = $candidate;
                break;
            }
        }
        $this->assertNotNull($entry);

        $this->assertSame('aeronef', $entry['entite_type']);
        $this->assertSame($this->macimmat, $entry['entite_id']);
        $this->assertStringContainsString($this->macimmat, $entry['entite_label']);
        $this->assertEquals($programme_id, $entry['programme_id']);
        $this->assertTrue($entry['regle_butee_date']);
        $this->assertTrue($entry['regle_butee_heures']);
        $this->assertSame(date('Y-m-d', strtotime('+12 months')), $entry['echeance_courante']);
        $this->assertEqualsWithDelta(50.00, (float) $entry['heures_restantes_courant'], 0.001);
        $this->assertContains($entry['etat'], array('a_jour', 'echeance_proche', 'depasse'));

        // Structure pure : aucune cle ne doit contenir de balisage HTML.
        foreach ($entry as $valeur) {
            if (is_string($valeur)) {
                $this->assertStringNotContainsString('<', $valeur);
            }
        }

        $this->nettoyer($programme_id, $dossier_id, $operation_id);
    }

    public function testListerEcheancesActivesFiltrageParSection()
    {
        $sections = $this->db->limit(2)->get('sections')->result_array();
        if (count($sections) < 2) {
            $this->markTestSkipped('Au moins 2 sections sont necessaires pour ce test');
        }
        list($section, $autre_section) = $sections;

        $programme_section = $this->creerProgramme(array(
            'regle_butee_date' => 1, 'periodicite_mois' => 6, 'section_id' => $section['id'],
        ));
        $dossier_section = $this->creerDossier($programme_section);

        $echeances_section = $this->potentiel->lister_echeances_actives($section['id']);
        $this->assertContains($dossier_section, array_column($echeances_section, 'dossier_id'));

        $echeances_autre_section = $this->potentiel->lister_echeances_actives($autre_section['id']);
        $this->assertNotContains($dossier_section, array_column($echeances_autre_section, 'dossier_id'));

        $this->nettoyer($programme_section, $dossier_section);
    }
}

/* End of file MaintenancePotentielTest.php */
/* Location: ./application/tests/mysql/MaintenancePotentielTest.php */
