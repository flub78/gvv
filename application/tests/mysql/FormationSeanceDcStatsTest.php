<?php

use PHPUnit\Framework\TestCase;

/**
 * Rapports de formation : statistiques d'instruction déduites directement de
 * volsa (vadc = 1), pour compenser le fait que beaucoup de vols en double
 * commande ne donnent pas lieu à la déclaration d'une formation_seances.
 *
 * @see application/models/formation_seance_model.php
 *      get_stats_dc_par_instructeur(), get_stats_dc_par_machine(),
 *      get_vols_dc_sans_seance()
 */
class FormationSeanceDcStatsTest extends TestCase
{
    /** @var CI_Controller */
    private $CI;

    /** @var Formation_seance_model */
    private $model;

    private $machine = 'F-TESTD';
    private $instructeur = 'zztestinst';
    private $pilote1 = 'zztestpil1';
    private $pilote2 = 'zztestpil2';
    private $test_year = 2099;
    private $test_date = '2099-05-15';

    private $created_vaid = array();
    private $created_seance_ids = array();

    /** Section active de la session en cours (peut varier selon les tests exécutés avant celui-ci). */
    private $section;

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        $this->CI->load->model('formation_seance_model');
        $this->model = $this->CI->formation_seance_model;

        if (!function_exists('centieme_to_hhmm')) {
            $this->CI->load->helper('validation');
        }

        // Les vols/machines de test doivent appartenir à la section active de
        // la session (get_stats_dc_* filtrent par club quand une section est
        // sélectionnée), sinon ils seraient invisibles pour les requêtes testées.
        $this->section = $this->model->section_id();

        foreach (array($this->instructeur, $this->pilote1, $this->pilote2) as $login) {
            $this->CI->db->delete('membres', array('mlogin' => $login));
            $this->CI->db->insert('membres', array(
                'mlogin'  => $login,
                'mnom'    => 'Test',
                'mprenom' => $login,
                'actif'   => 1,
            ));
        }

        $this->CI->db->delete('machinesa', array('macimmat' => $this->machine));
        $this->CI->db->insert('machinesa', array(
            'macimmat'       => $this->machine,
            'macconstruc'    => 'TestConstructeur',
            'macmodele'      => 'TestDcStats',
            'macplaces'      => 2,
            'maprix'         => '0',
            'maprive'        => 0,
            'horametre_mode' => 1,
            'actif'          => 1,
            'club'           => $this->section,
        ));
    }

    protected function tearDown(): void
    {
        foreach ($this->created_vaid as $vaid) {
            $this->CI->db->delete('volsa', array('vaid' => $vaid));
        }
        foreach ($this->created_seance_ids as $id) {
            $this->CI->db->delete('formation_seances', array('id' => $id));
        }
        $this->CI->db->delete('machinesa', array('macimmat' => $this->machine));
        foreach (array($this->instructeur, $this->pilote1, $this->pilote2) as $login) {
            $this->CI->db->delete('membres', array('mlogin' => $login));
        }
    }

    private function insertFlight($pilote, $vadc, $vaduree, $date = null)
    {
        $this->CI->db->insert('volsa', array(
            'vadate'      => $date ?: $this->test_date,
            'vapilid'     => $pilote,
            'vamacid'     => $this->machine,
            'vainst'      => $vadc ? $this->instructeur : null,
            'vahdeb'      => 9.00,
            'vahfin'      => 10.00,
            'vacdeb'      => 0.00,
            'vacfin'      => $vaduree,
            'vaduree'     => $vaduree,
            'vaobs'       => 'Test FormationSeanceDcStats',
            'vadc'        => $vadc,
            'vacategorie' => 0,
            'vaatt'       => 1,
            'club'        => $this->section,
        ));
        $id = $this->CI->db->insert_id();
        $this->assertGreaterThan(0, $id, 'insertFlight should succeed');
        $this->created_vaid[] = $id;
        return $id;
    }

    private function insertSeance($pilote, $date = null)
    {
        $this->CI->db->insert('formation_seances', array(
            'pilote_id'      => $pilote,
            'instructeur_id' => $this->instructeur,
            'machine_id'     => $this->machine,
            'date_seance'    => $date ?: $this->test_date,
            'seance_theorique' => 0,
        ));
        $id = $this->CI->db->insert_id();
        $this->created_seance_ids[] = $id;
        return $id;
    }

    public function test_stats_dc_par_instructeur_only_counts_dc_flights(): void
    {
        $this->insertFlight($this->pilote1, 1, 1.00);
        $this->insertFlight($this->pilote2, 1, 0.50);
        $this->insertFlight($this->pilote1, 0, 2.00); // vol non DC : ne doit pas être compté

        $rows = $this->model->get_stats_dc_par_instructeur($this->test_year);
        $row = $this->find_row($rows, 'instructeur_id', $this->instructeur);

        $this->assertNotNull($row, 'Un total doit exister pour l\'instructeur de test');
        $this->assertEquals(2, (int) $row['nb_vols']);
        $this->assertEquals('1:30', centieme_to_hhmm($row['heures']));
    }

    public function test_stats_dc_par_machine_only_counts_dc_flights(): void
    {
        $this->insertFlight($this->pilote1, 1, 0.75);
        $this->insertFlight($this->pilote2, 1, 0.25);
        $this->insertFlight($this->pilote1, 0, 5.00); // vol non DC : ne doit pas être compté

        $rows = $this->model->get_stats_dc_par_machine($this->test_year);
        $row = $this->find_row($rows, 'machine_id', $this->machine);

        $this->assertNotNull($row, 'Un total doit exister pour la machine de test');
        $this->assertEquals(2, (int) $row['nb_vols']);
        $this->assertEquals('1:00', centieme_to_hhmm($row['heures']));
    }

    public function test_vol_dc_sans_seance_correspondante_est_liste(): void
    {
        // Vol DC sans aucune formation_seances au même jour/pilote/instructeur.
        $this->insertFlight($this->pilote1, 1, 1.00);

        $rows = $this->model->get_vols_dc_sans_seance($this->test_year);
        $row = $this->find_row($rows, 'pilote_id', $this->pilote1);

        $this->assertNotNull($row, 'Le vol DC sans séance déclarée doit apparaître dans la liste');
        $this->assertEquals($this->instructeur, $row['instructeur_id']);
        $this->assertEquals(1, (int) $row['nb_vols']);
    }

    public function test_vol_dc_avec_seance_correspondante_est_exclu(): void
    {
        // Vol DC avec une formation_seances déclarée le même jour, pour le
        // même pilote et le même instructeur : ne doit pas être considéré
        // comme "sans séance".
        $this->insertFlight($this->pilote2, 1, 1.00);
        $this->insertSeance($this->pilote2);

        $rows = $this->model->get_vols_dc_sans_seance($this->test_year);
        $row = $this->find_row($rows, 'pilote_id', $this->pilote2);

        $this->assertNull($row, 'Un vol DC déjà couvert par une séance déclarée ne doit pas être listé comme orphelin');
    }

    private function find_row(array $rows, $key, $value)
    {
        foreach ($rows as $row) {
            if ($row[$key] === $value) {
                return $row;
            }
        }
        return null;
    }
}
