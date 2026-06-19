<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests MySQL pour la validation de l'autonomie machine dans les séances de formation.
 *
 * Vérifie que l'enregistrement d'une séance dont la durée dépasse l'autonomie
 * de la machine (avion) est bien bloqué.
 *
 * @package tests/mysql
 */
class FormationSeancesAutonomieTest extends TestCase
{
    /** @var CI_Controller */
    private $CI;

    /** @var string Immatriculation d'un avion de test */
    private $test_machine;

    /** @var float Autonomie originale sauvegardée */
    private $original_autonomie;

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        $this->CI->load->model('avions_model');

        $aircraft = $this->CI->db
            ->select('macimmat, autonomie_en_heures')
            ->from('machinesa')
            ->where('actif', 1)
            ->limit(1)
            ->get()
            ->row_array();

        if (empty($aircraft)) {
            $this->markTestSkipped('Aucun avion actif trouvé en base');
        }

        $this->test_machine = $aircraft['macimmat'];
        $this->original_autonomie = isset($aircraft['autonomie_en_heures']) ? $aircraft['autonomie_en_heures'] : null;
    }

    protected function tearDown(): void
    {
        $this->CI->db->update('machinesa',
            array('autonomie_en_heures' => $this->original_autonomie),
            array('macimmat' => $this->test_machine)
        );
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Convertit une durée "HH:MM:SS" en heures décimales.
     */
    private function timeToHours($time)
    {
        $parts = explode(':', $time);
        $hours = intval($parts[0]) + intval($parts[1]) / 60.0;
        if (isset($parts[2])) {
            $hours += intval($parts[2]) / 3600.0;
        }
        return $hours;
    }

    /**
     * Simule la logique de validation de l'autonomie appliquée par le contrôleur.
     *
     * @param string $machine_id Immatriculation de la machine
     * @param string $duree      Durée au format HH:MM:SS
     * @return bool true si la durée dépasse l'autonomie, false sinon
     */
    private function autonomieDepassee($machine_id, $duree)
    {
        $avion = $this->CI->avions_model->get_by_id('macimmat', $machine_id);
        if (!$avion || !isset($avion['autonomie_en_heures']) || $avion['autonomie_en_heures'] === null || $avion['autonomie_en_heures'] === '') {
            return false;
        }

        $autonomie = floatval($avion['autonomie_en_heures']);
        $duree_heures = $this->timeToHours($duree);

        return $duree_heures > $autonomie;
    }

    // -----------------------------------------------------------------------
    // Tests
    // -----------------------------------------------------------------------

    /**
     * Pas d'autonomie définie → aucun blocage quelle que soit la durée.
     */
    public function testNoAutonomieNeverBlocks()
    {
        $this->CI->db->update('machinesa',
            array('autonomie_en_heures' => null),
            array('macimmat' => $this->test_machine)
        );

        $this->assertFalse($this->autonomieDepassee($this->test_machine, '06:00:00'),
            'Sans autonomie définie, une séance de 6h doit être acceptée');
    }

    /**
     * Durée inférieure à l'autonomie → valide.
     */
    public function testDureeInferieureAutonomie()
    {
        $this->CI->db->update('machinesa',
            array('autonomie_en_heures' => 3.0),
            array('macimmat' => $this->test_machine)
        );

        $this->assertFalse($this->autonomieDepassee($this->test_machine, '02:30:00'),
            'Séance 2h30 avec autonomie 3h → doit être acceptée');
    }

    /**
     * Durée exactement égale à l'autonomie → valide (borne incluse).
     */
    public function testDureeEgaleAutonomie()
    {
        $this->CI->db->update('machinesa',
            array('autonomie_en_heures' => 3.0),
            array('macimmat' => $this->test_machine)
        );

        $this->assertFalse($this->autonomieDepassee($this->test_machine, '03:00:00'),
            'Séance exactement 3h avec autonomie 3h → doit être acceptée');
    }

    /**
     * Durée supérieure à l'autonomie → bloquée.
     */
    public function testDureeSuperieureAutonomieEstBloquee()
    {
        $this->CI->db->update('machinesa',
            array('autonomie_en_heures' => 3.0),
            array('macimmat' => $this->test_machine)
        );

        $this->assertTrue($this->autonomieDepassee($this->test_machine, '03:30:00'),
            'Séance 3h30 avec autonomie 3h → doit être bloquée');
    }

    /**
     * Durée très longue dépasse l'autonomie → bloquée.
     */
    public function testSeanceTropLongueEstBloquee()
    {
        $this->CI->db->update('machinesa',
            array('autonomie_en_heures' => 2.0),
            array('macimmat' => $this->test_machine)
        );

        $this->assertTrue($this->autonomieDepassee($this->test_machine, '05:00:00'),
            'Séance 5h avec autonomie 2h → doit être bloquée');
    }

    /**
     * Conversion HH:MM:SS vers heures décimales.
     */
    public function testConversionDureeHeures()
    {
        $this->assertEquals(1.5,  $this->timeToHours('01:30:00'), '1h30 = 1.5h');
        $this->assertEquals(3.0,  $this->timeToHours('03:00:00'), '3h00 = 3.0h');
        $this->assertEquals(3.5,  $this->timeToHours('03:30:00'), '3h30 = 3.5h');
        $this->assertEquals(0.25, $this->timeToHours('00:15:00'), '15min = 0.25h');
    }

    /**
     * Autonomie minimale (1h) : une séance d'1h est acceptée.
     */
    public function testAutonomieMinimale()
    {
        $this->CI->db->update('machinesa',
            array('autonomie_en_heures' => 1.0),
            array('macimmat' => $this->test_machine)
        );

        $this->assertFalse($this->autonomieDepassee($this->test_machine, '01:00:00'),
            'Séance exactement 1h avec autonomie 1h → valide');

        $this->assertTrue($this->autonomieDepassee($this->test_machine, '01:01:00'),
            'Séance 1h01 avec autonomie 1h → bloquée');
    }

    /**
     * Autonomie maximale (8h) : une séance de 8h est acceptée.
     */
    public function testAutonomieMaximale()
    {
        $this->CI->db->update('machinesa',
            array('autonomie_en_heures' => 8.0),
            array('macimmat' => $this->test_machine)
        );

        $this->assertFalse($this->autonomieDepassee($this->test_machine, '08:00:00'),
            'Séance exactement 8h avec autonomie 8h → valide');

        $this->assertTrue($this->autonomieDepassee($this->test_machine, '08:01:00'),
            'Séance 8h01 avec autonomie 8h → bloquée');
    }
}

/* End of file FormationSeancesAutonomieTest.php */
/* Location: ./application/tests/mysql/FormationSeancesAutonomieTest.php */
