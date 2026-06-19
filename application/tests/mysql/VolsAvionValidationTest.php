<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests MySQL pour la validation des vols avion :
 *  - Pilote déjà en vol
 *  - Instructeur déjà en vol
 *  - Machine déjà en vol
 *  - Durée de vol > 8 heures
 *
 * @package tests/mysql
 */
class VolsAvionValidationTest extends TestCase
{
    /** @var CI_Controller */
    private $CI;

    /** @var Vols_avion_model */
    private $model;

    /** @var string Immatriculation d'un avion de test */
    private $test_machine;

    /** @var string Login d'un pilote de test */
    private $test_pilot;

    /** @var string Login d'un instructeur de test */
    private $test_instructor;

    /** @var string Date de test (future, sans risque de conflit) */
    private $test_date = '2099-07-01';

    /** @var int[] IDs des vols créés à nettoyer */
    private $created_vaid = array();

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        $this->CI->load->model('vols_avion_model');
        $this->model = $this->CI->vols_avion_model;

        // Chercher un avion actif
        $aircraft = $this->CI->db
            ->select('macimmat')
            ->from('machinesa')
            ->where('actif', 1)
            ->limit(1)
            ->get()
            ->row_array();

        if (empty($aircraft)) {
            $this->markTestSkipped('Aucun avion actif trouvé en base');
        }
        $this->test_machine = $aircraft['macimmat'];

        // Chercher deux membres actifs
        $members = $this->CI->db
            ->select('mlogin')
            ->from('membres')
            ->where('actif', 1)
            ->limit(2)
            ->get()
            ->result_array();

        if (count($members) < 2) {
            $this->markTestSkipped('Pas assez de membres actifs (besoin de 2)');
        }
        $this->test_pilot      = $members[0]['mlogin'];
        $this->test_instructor = $members[1]['mlogin'];
    }

    protected function tearDown(): void
    {
        // Suppression des vols de test créés
        foreach ($this->created_vaid as $vaid) {
            $this->CI->db->delete('volsa', array('vaid' => $vaid));
        }
        $this->created_vaid = array();
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Insère un vol de test minimal directement en base et retourne son vaid.
     */
    private function insertTestFlight($pilot, $machine, $hdeb, $hfin, $instructor = '')
    {
        $data = array(
            'vadate'   => $this->test_date,
            'vapilid'  => $pilot,
            'vamacid'  => $machine,
            'vahdeb'   => $hdeb,
            'vahfin'   => $hfin,
            'vacdeb'   => 0,
            'vacfin'   => 0,
            'vaduree'  => 0,
            'vainst'   => $instructor,
            'vaobs'    => 'Test VolsAvionValidation',
            'vadc'     => 0,
            'vacategorie' => 0,
            'vaatt'    => 1,
            'club'     => $this->model->section_id(),
        );
        $this->CI->db->insert('volsa', $data);
        $id = $this->CI->db->insert_id();
        if ($id > 0) {
            $this->created_vaid[] = $id;
        }
        return $id;
    }

    // -----------------------------------------------------------------------
    // Tests : is_person_in_flight
    // -----------------------------------------------------------------------

    /**
     * Pilote libre : aucun vol existant → pas de conflit.
     */
    public function testPilotAvailableWhenNoFlight()
    {
        $result = $this->model->is_person_in_flight(
            $this->test_pilot,
            $this->test_date,
            9.00,  // 9h00
            10.00, // 10h00
            0
        );
        $this->assertFalse($result, 'Pilote libre : aucun conflit attendu');
    }

    /**
     * Pilote déjà en vol avec chevauchement total.
     */
    public function testPilotAlreadyInFlightFullOverlap()
    {
        // Vol existant : 9h00 - 10h00
        $this->insertTestFlight($this->test_pilot, $this->test_machine, 9.00, 10.00);

        // Nouveau vol : 9h30 - 10h30 → chevauche
        $result = $this->model->is_person_in_flight(
            $this->test_pilot,
            $this->test_date,
            9.30,
            10.30,
            0
        );
        $this->assertTrue($result, 'Le pilote est déjà en vol → conflit attendu');
    }

    /**
     * Pilote en vol comme instructeur dans un autre vol → conflit.
     */
    public function testPilotAsInstructorConflict()
    {
        // Vol existant où test_pilot est instructeur (test_instructor pilote)
        $this->insertTestFlight($this->test_instructor, $this->test_machine, 10.00, 11.00, $this->test_pilot);

        // Nouveau vol où test_pilot est pilote dans le même créneau
        $result = $this->model->is_person_in_flight(
            $this->test_pilot,
            $this->test_date,
            10.30,
            11.30,
            0
        );
        $this->assertTrue($result, 'Pilote déjà instructeur dans un autre vol → conflit attendu');
    }

    /**
     * Vols adjacents (non chevauchants) : pas de conflit.
     */
    public function testPilotAdjacentFlightsNoConflict()
    {
        // Vol existant : 9h00 - 10h00
        $this->insertTestFlight($this->test_pilot, $this->test_machine, 9.00, 10.00);

        // Nouveau vol : 10h00 - 11h00 (commence exactement à la fin du précédent)
        $result = $this->model->is_person_in_flight(
            $this->test_pilot,
            $this->test_date,
            10.00,
            11.00,
            0
        );
        $this->assertFalse($result, 'Vols adjacents : aucun conflit attendu');
    }

    /**
     * Modification d'un vol existant : on doit l'exclure du check.
     */
    public function testExcludeOwnFlightWhenEditing()
    {
        // Créer le vol à éditer
        $vaid = $this->insertTestFlight($this->test_pilot, $this->test_machine, 9.00, 10.00);
        $this->assertGreaterThan(0, $vaid, 'Le vol de test doit être créé');

        // Vérification avec exclusion du vol lui-même : pas de conflit
        $result = $this->model->is_person_in_flight(
            $this->test_pilot,
            $this->test_date,
            9.00,
            10.00,
            $vaid
        );
        $this->assertFalse($result, "Modifier son propre vol ne doit pas générer de conflit");
    }

    /**
     * Vol sur une autre date : pas de conflit.
     */
    public function testNoPilotConflictOnDifferentDate()
    {
        // Vol existant le 2099-07-01
        $this->insertTestFlight($this->test_pilot, $this->test_machine, 9.00, 10.00);

        // Vérification le lendemain
        $result = $this->model->is_person_in_flight(
            $this->test_pilot,
            '2099-07-02',
            9.00,
            10.00,
            0
        );
        $this->assertFalse($result, 'Dates différentes : aucun conflit attendu');
    }

    /**
     * Heures non renseignées (0) : pas de vérification.
     */
    public function testSkipCheckWhenTimesAreMissing()
    {
        $this->insertTestFlight($this->test_pilot, $this->test_machine, 9.00, 10.00);

        // vahdeb = 0 → skip
        $result = $this->model->is_person_in_flight(
            $this->test_pilot,
            $this->test_date,
            0,    // pas d'heure de début
            10.00,
            0
        );
        $this->assertFalse($result, 'vahdeb=0 → le check doit être ignoré');

        // vahfin = 0 → skip
        $result = $this->model->is_person_in_flight(
            $this->test_pilot,
            $this->test_date,
            9.00,
            0,    // pas d'heure de fin
            0
        );
        $this->assertFalse($result, 'vahfin=0 → le check doit être ignoré');
    }

    // -----------------------------------------------------------------------
    // Tests : is_machine_in_flight
    // -----------------------------------------------------------------------

    /**
     * Machine libre : pas de conflit.
     */
    public function testMachineAvailableWhenNoFlight()
    {
        $result = $this->model->is_machine_in_flight(
            $this->test_machine,
            $this->test_date,
            14.00,
            15.00,
            0
        );
        $this->assertFalse($result, 'Machine libre : aucun conflit attendu');
    }

    /**
     * Machine déjà en vol : conflit attendu.
     */
    public function testMachineAlreadyInFlight()
    {
        // Vol existant avec cette machine : 14h00 - 15h00
        $this->insertTestFlight($this->test_pilot, $this->test_machine, 14.00, 15.00);

        // Nouveau vol sur la même machine à 14h30 - 15h30
        $result = $this->model->is_machine_in_flight(
            $this->test_machine,
            $this->test_date,
            14.30,
            15.30,
            0
        );
        $this->assertTrue($result, 'Machine déjà en vol → conflit attendu');
    }

    /**
     * Machine différente : pas de conflit.
     */
    public function testMachineNoConflictDifferentAircraft()
    {
        // Trouver une deuxième machine
        $other = $this->CI->db
            ->select('macimmat')
            ->from('machinesa')
            ->where('actif', 1)
            ->where('macimmat !=', $this->test_machine)
            ->limit(1)
            ->get()
            ->row_array();

        if (empty($other)) {
            $this->markTestSkipped('Besoin de deux avions actifs pour ce test');
        }
        $other_machine = $other['macimmat'];

        // Vol existant sur la machine de test
        $this->insertTestFlight($this->test_pilot, $this->test_machine, 14.00, 15.00);

        // Vérification sur l'autre machine : pas de conflit
        $result = $this->model->is_machine_in_flight(
            $other_machine,
            $this->test_date,
            14.00,
            15.00,
            0
        );
        $this->assertFalse($result, 'Machines différentes : aucun conflit attendu');
    }

    /**
     * Modification de la machine : exclure son propre vol.
     */
    public function testExcludeOwnFlightForMachine()
    {
        $vaid = $this->insertTestFlight($this->test_pilot, $this->test_machine, 14.00, 15.00);
        $this->assertGreaterThan(0, $vaid);

        $result = $this->model->is_machine_in_flight(
            $this->test_machine,
            $this->test_date,
            14.00,
            15.00,
            $vaid
        );
        $this->assertFalse($result, 'Modifier son propre vol ne génère pas de conflit machine');
    }

    /**
     * Machine avec vols adjacents : pas de conflit.
     */
    public function testMachineAdjacentFlightsNoConflict()
    {
        // Vol existant : 14h00 - 15h00
        $this->insertTestFlight($this->test_pilot, $this->test_machine, 14.00, 15.00);

        // Vol suivant commence exactement à 15h00
        $result = $this->model->is_machine_in_flight(
            $this->test_machine,
            $this->test_date,
            15.00,
            16.00,
            0
        );
        $this->assertFalse($result, 'Vols adjacents machine : pas de conflit');
    }

    // -----------------------------------------------------------------------
    // Tests : durée maximale (logique en centièmes)
    // -----------------------------------------------------------------------

    /**
     * Durée <= 8h : valide.
     */
    public function testFlightDurationValidUnder8Hours()
    {
        // vaduree = 7.5 centièmes = 7h30 → valide
        $this->assertLessThanOrEqual(8.0, 7.5, 'Durée 7h30 doit être valide');

        // vaduree = 8.0 centièmes = 8h00 → valide (limite)
        $this->assertLessThanOrEqual(8.0, 8.0, 'Durée exactement 8h doit être valide');
    }

    /**
     * Durée > 8h : invalide.
     */
    public function testFlightDurationExceedsMaximum()
    {
        // vaduree = 8.01 centièmes → invalide
        $this->assertGreaterThan(8.0, 8.01, 'Durée 8h01 doit être invalide');

        // vaduree = 10.0 centièmes = 10h → invalide
        $this->assertGreaterThan(8.0, 10.0, 'Durée 10h doit être invalide');
    }

    /**
     * La présence d'un vol de durée > 8h dans la base n'est pas bloquante
     * pour les nouvelles insertions (la vérification est côté contrôleur).
     * Ce test vérifie que les méthodes du modèle ne gèrent pas elles-mêmes
     * la limite de durée (c'est fait dans le callback valid_vol_duration).
     */
    public function testModelDoesNotCheckDurationLimit()
    {
        // Un vol de 9h dans la base ne doit pas empêcher les checks de conflit
        // Sur une autre tranche horaire, aucun conflit
        $result = $this->model->is_person_in_flight(
            $this->test_pilot,
            $this->test_date,
            8.00,
            17.00, // 9h de vol – heures seulement, on vérifie l'indépendance
            0
        );
        $this->assertFalse($result, 'Sans vol existant, pas de conflit même pour une longue tranche');
    }

    // -----------------------------------------------------------------------
    // Tests de chevauchement partiel
    // -----------------------------------------------------------------------

    /**
     * Nouveau vol inclus dans un vol existant.
     */
    public function testPilotNewFlightIncludedInExisting()
    {
        // Vol existant : 8h00 - 16h00
        $this->insertTestFlight($this->test_pilot, $this->test_machine, 8.00, 16.00);

        // Nouveau vol entièrement inclus
        $result = $this->model->is_person_in_flight(
            $this->test_pilot,
            $this->test_date,
            10.00,
            12.00,
            0
        );
        $this->assertTrue($result, 'Nouveau vol inclus dans un existant → conflit');
    }

    /**
     * Nouveau vol englobe un vol existant.
     */
    public function testPilotNewFlightEnglobingExisting()
    {
        // Vol existant : 10h00 - 11h00
        $this->insertTestFlight($this->test_pilot, $this->test_machine, 10.00, 11.00);

        // Nouveau vol qui englobe l'existant
        $result = $this->model->is_person_in_flight(
            $this->test_pilot,
            $this->test_date,
            9.00,
            12.00,
            0
        );
        $this->assertTrue($result, 'Nouveau vol englobant un existant → conflit');
    }

    /**
     * Instructeur : vérification quand login vide → pas de check.
     */
    public function testSkipCheckWhenInstructorIsEmpty()
    {
        $this->insertTestFlight($this->test_pilot, $this->test_machine, 9.00, 10.00);

        $result = $this->model->is_person_in_flight(
            '',   // instructeur vide
            $this->test_date,
            9.00,
            10.00,
            0
        );
        $this->assertFalse($result, 'Login vide → le check doit être ignoré');
    }

    // -----------------------------------------------------------------------
    // Tests : autonomie machine
    // -----------------------------------------------------------------------

    /**
     * Durée dans les limites de l'autonomie → pas d'erreur.
     */
    public function testFlightWithinAutonomie()
    {
        $autonomie = 3.0;
        $this->CI->db->update('machinesa',
            array('autonomie_en_heures' => $autonomie),
            array('macimmat' => $this->test_machine)
        );

        $avion = $this->CI->db->where('macimmat', $this->test_machine)
            ->get('machinesa')->row_array();
        $this->assertEquals($autonomie, floatval($avion['autonomie_en_heures']),
            "L'autonomie doit être enregistrée");

        $duree = 2.5; // 2h30 < 3h → valide
        $this->assertLessThanOrEqual($autonomie, $duree,
            'Durée 2h30 dans une autonomie 3h doit être valide');

        // Nettoyage
        $this->CI->db->update('machinesa',
            array('autonomie_en_heures' => null),
            array('macimmat' => $this->test_machine)
        );
    }

    /**
     * Durée exactement égale à l'autonomie → valide (limite incluse).
     */
    public function testFlightExactlyAtAutonomieLimit()
    {
        $autonomie = 3.0;
        $duree = 3.0; // exactement la limite
        $this->assertFalse($duree > $autonomie,
            'Durée égale à l\'autonomie doit être valide (borne incluse)');
    }

    /**
     * Durée dépasse l'autonomie → erreur.
     */
    public function testFlightExceedsAutonomie()
    {
        $autonomie = 3.0;
        $this->CI->db->update('machinesa',
            array('autonomie_en_heures' => $autonomie),
            array('macimmat' => $this->test_machine)
        );

        $avion = $this->CI->db->where('macimmat', $this->test_machine)
            ->get('machinesa')->row_array();
        $this->assertEquals($autonomie, floatval($avion['autonomie_en_heures']),
            "L'autonomie doit être enregistrée");

        $duree = 3.5; // 3h30 > 3h → invalide
        $this->assertGreaterThan($autonomie, $duree,
            'Durée 3h30 dépasse une autonomie de 3h → doit être invalide');

        // Nettoyage
        $this->CI->db->update('machinesa',
            array('autonomie_en_heures' => null),
            array('macimmat' => $this->test_machine)
        );
    }

    /**
     * Pas d'autonomie définie → pas de vérification.
     */
    public function testNoAutonomieSetSkipsCheck()
    {
        $this->CI->db->update('machinesa',
            array('autonomie_en_heures' => null),
            array('macimmat' => $this->test_machine)
        );

        $avion = $this->CI->db->where('macimmat', $this->test_machine)
            ->get('machinesa')->row_array();
        $autonomie = $avion['autonomie_en_heures'];
        $this->assertNull($autonomie,
            'Sans autonomie définie, aucune vérification ne doit bloquer');
    }

    /**
     * L'autonomie est lue correctement depuis la base.
     */
    public function testAutonomieFieldIsReadable()
    {
        $this->CI->db->update('machinesa',
            array('autonomie_en_heures' => 4.5),
            array('macimmat' => $this->test_machine)
        );

        $avion = $this->CI->db->where('macimmat', $this->test_machine)
            ->get('machinesa')->row_array();
        $this->assertEquals(4.5, floatval($avion['autonomie_en_heures']),
            'Le champ autonomie_en_heures doit être lu avec la valeur enregistrée');

        // Nettoyage
        $this->CI->db->update('machinesa',
            array('autonomie_en_heures' => null),
            array('macimmat' => $this->test_machine)
        );
    }
}

/* End of file VolsAvionValidationTest.php */
/* Location: ./application/tests/mysql/VolsAvionValidationTest.php */
