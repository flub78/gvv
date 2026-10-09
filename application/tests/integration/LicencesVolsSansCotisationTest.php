<?php

require_once(__DIR__ . '/TransactionalTestCase.php');

/**
 * Integration test for Licences_model::pilotes_vols_sans_cotisation()
 *
 * Les fixtures sont créées dans une année sans données réelles (2099)
 * et annulées par le rollback de TransactionalTestCase.
 */
class LicencesVolsSansCotisationTest extends TransactionalTestCase
{
    const YEAR = 2099;

    private $licences_model;
    private $ulm_section_id;
    private $avion_section_id;

    public function setUp(): void
    {
        parent::setUp();
        $this->CI->load->model('licences_model');
        $this->licences_model = $this->CI->licences_model;

        $ulm = $this->CI->db->get_where('sections', array('acronyme' => 'ULM'))->row_array();
        $this->ulm_section_id = $ulm ? (int) $ulm['id'] : 0;
        $other = $this->CI->db->where('acronyme !=', 'ULM')->get('sections')->row_array();
        $this->avion_section_id = $other ? (int) $other['id'] : 0;
    }

    private function add_vol_planeur($pilote, $date)
    {
        $this->CI->db->insert('volsp', array(
            'vpdate' => $date, 'vppilid' => $pilote, 'vpmacid' => 'TEST',
            'vpcdeb' => 10, 'vpcfin' => 11, 'vpduree' => 60,
            'vpdc' => 0, 'vpcategorie' => 0, 'vpticcolle' => 0,
        ));
    }

    private function add_vol_avion($pilote, $date, $section_id)
    {
        $this->CI->db->insert('volsa', array(
            'vadate' => $date, 'vapilid' => $pilote, 'vamacid' => 'TEST',
            'vacdeb' => 0, 'vacfin' => 1, 'vaduree' => 1,
            'vadc' => 0, 'vacategorie' => 0, 'vahdeb' => 10, 'vahfin' => 11,
            'club' => $section_id,
        ));
    }

    private function find($rows, $pilote)
    {
        foreach ($rows as $row) {
            if ($row['pilote'] === $pilote) {
                return $row;
            }
        }
        return null;
    }

    public function test_no_flight_returns_empty_list()
    {
        $this->assertSame(array(), $this->licences_model->pilotes_vols_sans_cotisation(self::YEAR));
    }

    public function test_pilot_without_cotisation_is_listed_with_counts_per_category()
    {
        if (!$this->ulm_section_id || !$this->avion_section_id) {
            $this->markTestSkipped('Sections ULM et non-ULM requises');
        }
        $pilote = 'tst_vsc_' . uniqid();
        $this->add_vol_planeur($pilote, self::YEAR . '-03-01');
        $this->add_vol_planeur($pilote, self::YEAR . '-03-02');
        $this->add_vol_avion($pilote, self::YEAR . '-04-01', $this->avion_section_id);
        $this->add_vol_avion($pilote, self::YEAR . '-05-10', $this->ulm_section_id);

        $row = $this->find($this->licences_model->pilotes_vols_sans_cotisation(self::YEAR), $pilote);

        $this->assertNotNull($row);
        $this->assertEquals(2, $row['planeur']);
        $this->assertEquals(1, $row['avion']);
        $this->assertEquals(1, $row['ulm']);
        $this->assertSame(self::YEAR . '-05-10', $row['dernier_vol']);
    }

    public function test_pilot_with_cotisation_is_not_listed()
    {
        $pilote = 'tst_vsc_' . uniqid();
        $this->add_vol_planeur($pilote, self::YEAR . '-06-01');
        $this->CI->db->insert('licences', array(
            'pilote' => $pilote, 'type' => 0, 'year' => self::YEAR,
            'date' => self::YEAR . '-01-15', 'comment' => 'test',
        ));

        $rows = $this->licences_model->pilotes_vols_sans_cotisation(self::YEAR);

        $this->assertNull($this->find($rows, $pilote));
    }

    public function test_section_licence_does_not_count_as_cotisation()
    {
        $pilote = 'tst_vsc_' . uniqid();
        $this->add_vol_planeur($pilote, self::YEAR . '-06-01');
        $this->CI->db->insert('licences', array(
            'pilote' => $pilote, 'type' => 1, 'year' => self::YEAR,
            'date' => self::YEAR . '-01-15', 'comment' => 'test',
        ));

        $rows = $this->licences_model->pilotes_vols_sans_cotisation(self::YEAR);

        $this->assertNotNull($this->find($rows, $pilote));
    }

    private function add_membre($pilote, $ext)
    {
        $this->CI->db->insert('membres', array(
            'mlogin' => $pilote, 'mnom' => 'Test', 'mprenom' => 'VSC',
            'mnumero' => 0, 'ext' => $ext,
        ));
    }

    public function test_external_pilot_is_not_listed()
    {
        $pilote = 'tst_vsc_' . uniqid();
        $this->add_membre($pilote, 1);
        $this->add_vol_planeur($pilote, self::YEAR . '-06-01');

        $rows = $this->licences_model->pilotes_vols_sans_cotisation(self::YEAR);

        $this->assertNull($this->find($rows, $pilote));
    }

    public function test_club_member_without_cotisation_is_listed()
    {
        $pilote = 'tst_vsc_' . uniqid();
        $this->add_membre($pilote, 0);
        $this->add_vol_planeur($pilote, self::YEAR . '-06-01');

        $row = $this->find($this->licences_model->pilotes_vols_sans_cotisation(self::YEAR), $pilote);

        $this->assertNotNull($row);
        $this->assertSame('Test', $row['nom']);
    }

    public function test_flights_of_other_years_are_ignored()
    {
        $pilote = 'tst_vsc_' . uniqid();
        $this->add_vol_planeur($pilote, (self::YEAR - 1) . '-12-31');

        $rows = $this->licences_model->pilotes_vols_sans_cotisation(self::YEAR);

        $this->assertNull($this->find($rows, $pilote));
    }
}
