<?php

require_once(__DIR__ . '/TransactionalTestCase.php');

/**
 * Integration test for Licences_model::pilotes_vols_sans_licence()
 *
 * Licence exigée par la section du vol (sections.licence_type) pour l'année ;
 * vols planeur rattachés à la section qui gère les planeurs ; un remorquage
 * (vol volsa de la section planeur) relève de la licence planeur.
 * Les fixtures sont créées dans une année sans données réelles (2099)
 * et annulées par le rollback de TransactionalTestCase.
 */
class LicencesVolsSansLicenceTest extends TransactionalTestCase
{
    const YEAR = 2099;

    private $licences_model;
    private $sections = array();

    public function setUp(): void
    {
        parent::setUp();
        $this->CI->load->model('licences_model');
        $this->licences_model = $this->CI->licences_model;
        foreach (array('PLA', 'AVI', 'ULM') as $acronyme) {
            $row = $this->CI->db->get_where('sections', array('acronyme' => $acronyme))->row_array();
            $this->sections[$acronyme] = $row ? (int) $row['id'] : 0;
        }
        // Types de licence de la fixture, fixés dans la transaction du test
        $this->set_licence_type('PLA', 1);
        $this->set_licence_type('AVI', 2);
        $this->set_licence_type('ULM', 3);
    }

    private function set_licence_type($acronyme, $type)
    {
        if ($this->sections[$acronyme]) {
            $this->CI->db->where('id', $this->sections[$acronyme])->update('sections', array('licence_type' => $type));
        }
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

    private function add_licence($pilote, $type, $year = self::YEAR)
    {
        $this->CI->db->insert('licences', array(
            'pilote' => $pilote, 'type' => $type, 'year' => $year,
            'date' => $year . '-01-15', 'comment' => 'test',
        ));
    }

    /** Lignes du pilote, par type de licence manquant (une section par type dans la fixture) */
    private function rows($pilote)
    {
        $result = array();
        foreach ($this->licences_model->pilotes_vols_sans_licence(self::YEAR) as $row) {
            if ($row['pilote'] === $pilote) {
                $result[(int) $row['licence_type']] = $row;
            }
        }
        ksort($result);
        return $result;
    }

    private function requireSections()
    {
        if (!$this->sections['PLA'] || !$this->sections['AVI'] || !$this->sections['ULM']) {
            $this->markTestSkipped('Sections PLA, AVI et ULM requises');
        }
        $glider = $this->CI->db->get_where('sections', array('gestion_planeurs' => 1))->row_array();
        if (!$glider || (int) $glider['id'] !== $this->sections['PLA']) {
            $this->markTestSkipped('La section PLA doit être la section qui gère les planeurs');
        }
    }

    public function test_one_row_per_activity_without_licence()
    {
        $this->requireSections();
        $pilote = 'tst_vsl_' . uniqid();
        $this->add_vol_planeur($pilote, self::YEAR . '-03-01');
        $this->add_vol_planeur($pilote, self::YEAR . '-03-02');
        $this->add_vol_avion($pilote, self::YEAR . '-04-01', $this->sections['AVI']);
        $this->add_vol_avion($pilote, self::YEAR . '-05-10', $this->sections['ULM']);

        $rows = $this->rows($pilote);

        $this->assertSame(array(1, 2, 3), array_keys($rows));
        $this->assertEquals($this->sections['PLA'], $rows[1]['section_id'], 'Vol planeur : section qui gère les planeurs');
        $this->assertEquals(2, $rows[1]['vols']);
        $this->assertEquals($this->sections['AVI'], $rows[2]['section_id']);
        $this->assertEquals((string) $this->sections['AVI'], $rows[2]['sections']);
        $this->assertEquals($this->sections['ULM'], $rows[3]['section_id']);
        $this->assertSame(self::YEAR . '-05-10', $rows[3]['dernier_vol']);
    }

    public function test_licence_of_the_activity_removes_the_row()
    {
        $this->requireSections();
        $pilote = 'tst_vsl_' . uniqid();
        $this->add_vol_planeur($pilote, self::YEAR . '-03-01');
        $this->add_vol_avion($pilote, self::YEAR . '-04-01', $this->sections['AVI']);
        $this->add_licence($pilote, 1);

        $this->assertSame(array(2), array_keys($this->rows($pilote)), 'Licence planeur : reste la licence avion');
    }

    public function test_tow_flight_in_glider_section_requires_glider_licence()
    {
        $this->requireSections();
        $pilote = 'tst_vsl_' . uniqid();
        $this->add_vol_avion($pilote, self::YEAR . '-04-01', $this->sections['PLA']); // remorquage
        $this->add_licence($pilote, 2);

        $rows = $this->rows($pilote);
        $this->assertSame(array(1), array_keys($rows), 'Licence planeur exigée, licence avion inutile');
        $this->assertEquals($this->sections['PLA'], $rows[1]['section_id']);

        $this->add_licence($pilote, 1);
        $this->assertSame(array(), $this->rows($pilote));
    }

    public function test_section_without_required_licence_is_not_checked()
    {
        $this->requireSections();
        $pilote = 'tst_vsl_' . uniqid();
        $this->add_vol_avion($pilote, self::YEAR . '-04-01', $this->sections['ULM']);
        $this->set_licence_type('ULM', null);

        $this->assertSame(array(), $this->rows($pilote));
    }

    public function test_licence_of_another_activity_or_cotisation_does_not_count()
    {
        $pilote = 'tst_vsl_' . uniqid();
        $this->add_vol_planeur($pilote, self::YEAR . '-03-01');
        $this->add_licence($pilote, 0);
        $this->add_licence($pilote, 2);
        $this->add_licence($pilote, 3);

        $this->assertSame(array(1), array_keys($this->rows($pilote)));
    }

    public function test_licence_of_another_year_does_not_count()
    {
        $pilote = 'tst_vsl_' . uniqid();
        $this->add_vol_planeur($pilote, self::YEAR . '-03-01');
        $this->add_licence($pilote, 1, self::YEAR - 1);

        $this->assertSame(array(1), array_keys($this->rows($pilote)));
    }

    public function test_external_pilot_and_other_years_are_ignored()
    {
        $external = 'tst_vsl_' . uniqid();
        $this->CI->db->insert('membres', array('mlogin' => $external, 'mnom' => 'Test', 'mprenom' => 'VSL',
            'mnumero' => 0, 'ext' => 1));
        $this->add_vol_planeur($external, self::YEAR . '-06-01');
        $this->assertSame(array(), $this->rows($external), 'Pilote extérieur');

        $pilote = 'tst_vsl_' . uniqid();
        $this->add_vol_planeur($pilote, (self::YEAR - 1) . '-12-31');
        $this->assertSame(array(), $this->rows($pilote), 'Vol d\'une autre année');
    }
}
