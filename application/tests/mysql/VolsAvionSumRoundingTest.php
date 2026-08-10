<?php

use PHPUnit\Framework\TestCase;

/**
 * Régression : le total "vaduree" affiché en bas de la liste des vols avion
 * doit correspondre à la somme des lignes telles qu'affichées (chacune
 * arrondie à la minute via centieme_to_hhmm()), pas à l'arrondi de la somme
 * brute des centièmes d'heure stockés.
 *
 * Avant le correctif, Vols_avion_model::sum('vaduree', ...) faisait un
 * SUM(vaduree) SQL brut converti une seule fois en HH:MM ; l'erreur
 * d'arrondi de ±0,3 min par ligne s'accumulait et dérivait du total obtenu
 * en additionnant les lignes affichées à la main.
 */
class VolsAvionSumRoundingTest extends TestCase
{
    /** @var CI_Controller */
    private $CI;

    /** @var Vols_avion_model */
    private $model;

    private $machine = 'F-TESTR';
    private $test_pilot;
    private $test_date = '2099-06-24';

    private $created_vaid = array();

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        $this->CI->load->model('vols_avion_model');
        $this->model = $this->CI->vols_avion_model;

        if (!function_exists('centieme_to_hhmm')) {
            $this->CI->load->helper('validation');
        }

        $member = $this->CI->db
            ->select('mlogin')
            ->from('membres')
            ->where('actif', 1)
            ->limit(1)
            ->get()
            ->row_array();

        if (empty($member)) {
            $this->markTestSkipped('Aucun membre actif trouvé en base');
        }
        $this->test_pilot = $member['mlogin'];

        $this->CI->db->delete('machinesa', array('macimmat' => $this->machine));
        $section = $this->model->section_id();
        $this->CI->db->insert('machinesa', array(
            'macimmat'       => $this->machine,
            'macconstruc'    => 'TestConstructeur',
            'macmodele'      => 'TestSumRounding',
            'macplaces'      => 1,
            'maprix'         => '0',
            'maprive'        => 0,
            'horametre_mode' => 1,
            'actif'          => 1,
            'club'           => $section,
        ));
    }

    protected function tearDown(): void
    {
        foreach ($this->created_vaid as $vaid) {
            $this->CI->db->delete('volsa', array('vaid' => $vaid));
        }
        $this->CI->db->delete('machinesa', array('macimmat' => $this->machine));
    }

    /**
     * Insère un vol avec une durée (vaduree) donnée en centièmes d'heure.
     */
    private function insertFlight($vaduree, $vacategorie = 0)
    {
        $section = $this->model->section_id();
        $this->CI->db->insert('volsa', array(
            'vadate'      => $this->test_date,
            'vapilid'     => $this->test_pilot,
            'vamacid'     => $this->machine,
            'vahdeb'      => 9.00,
            'vahfin'      => 10.00,
            'vacdeb'      => 0.00,
            'vacfin'      => $vaduree,
            'vaduree'     => $vaduree,
            'vaobs'       => 'Test VolsAvionSumRounding',
            'vadc'        => 0,
            'vacategorie' => $vacategorie,
            'vaatt'       => 1,
            'club'        => $section,
        ));
        $id = $this->CI->db->insert_id();
        if ($id > 0) {
            $this->created_vaid[] = $id;
        }
        return $id;
    }

    /**
     * 3 vols de 0.01h (36s) chacun : affichés individuellement "0:01" (round(0.6)=1),
     * soit "0:03" au total en additionnant les lignes. La somme brute (0.03h) arrondie
     * une seule fois donne round(0.03*60)=round(1.8)="0:02" — c'est le bug rapporté.
     */
    public function test_sum_matches_addition_of_displayed_lines(): void
    {
        $this->insertFlight(0.01);
        $this->insertFlight(0.01);
        $this->insertFlight(0.01);

        $total = $this->model->sum('vaduree', array(
            'vapilid'  => $this->test_pilot,
            'vamacid'  => $this->machine,
        ));

        $this->assertEquals('0:03', centieme_to_hhmm($total),
            'Le total doit correspondre à la somme des lignes affichées (3x "0:01"), pas à l\'arrondi de la somme brute ("0:02")');
    }

    public function test_stats_by_category_hours_matches_addition_of_displayed_lines(): void
    {
        $this->insertFlight(0.01, 2);
        $this->insertFlight(0.01, 2);
        $this->insertFlight(0.01, 2);

        $stats = $this->model->stats_by_category(array(
            'vapilid' => $this->test_pilot,
            'vamacid' => $this->machine,
        ));

        $this->assertArrayHasKey(2, $stats);
        $this->assertEquals('0:03', centieme_to_hhmm($stats[2]['hours']),
            'Le sous-total par catégorie doit lui aussi sommer les minutes déjà arrondies par ligne');
    }

    public function test_sum_unaffected_when_no_rounding_drift(): void
    {
        // 1h30 pile : aucune dérive possible, le total doit rester exact.
        $this->insertFlight(1.50);
        $this->insertFlight(0.50);

        $total = $this->model->sum('vaduree', array(
            'vapilid' => $this->test_pilot,
            'vamacid' => $this->machine,
        ));

        $this->assertEquals('2:00', centieme_to_hhmm($total));
    }
}
