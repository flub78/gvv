<?php

require_once(__DIR__ . '/TransactionalTestCase.php');

/**
 * Tests d'intégration — statistiques adhérents
 *
 * Vérifie Adherents_report_model::get_adherents_data() combiné à la
 * bibliothèque Adherents_stats sur des données créées par le test.
 *
 * Les cotisations sont créées sur des années fictives (1994-1995), antérieures
 * à tout historique réel, pour que le résultat ne contienne que les données
 * du test. Chaque test s'exécute dans une transaction annulée en tearDown().
 *
 * @see doc/plans/statistiques_adherents_plan.md
 */
class AdherentsStatsIntegrationTest extends TransactionalTestCase
{
    const YEAR = 1995;

    private $model;
    private $stats;
    private $section_id;
    private static $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        if (!$this->CI->db->conn_id) {
            $this->markTestSkipped('Database connection not available');
        }
        $this->CI->load->model('adherents_report_model');
        $this->CI->load->library('adherents_stats');
        $this->model = $this->CI->adherents_report_model;
        $this->stats = $this->CI->adherents_stats;

        $existing = $this->CI->db->where('licences.year <=', self::YEAR)->where('type', 0)->count_all_results('licences');
        if ($existing > 0) {
            $this->markTestSkipped('Des cotisations réelles existent pour ' . self::YEAR . ' ou avant');
        }

        $sections = $this->CI->sections_model->section_list();
        if (empty($sections)) {
            $this->markTestSkipped('Aucune section configurée');
        }
        $this->section_id = (int) $sections[0]['id'];
    }

    private function create_member($mdaten, array $years, $with_account = true)
    {
        $login = 'tadh_' . getmypid() . '_' . (++self::$seq);
        $this->CI->db->insert('membres', array(
            'mlogin' => $login,
            'mnom' => 'Nom_' . $login,
            'mprenom' => 'Prenom_' . $login,
            'memail' => $login . '@test.invalid',
            'msexe' => 'F',
            'mdaten' => $mdaten,
            'actif' => 1,
            'ext' => 0,
            'm25ans' => 0,
        ));
        foreach ($years as $year) {
            $this->CI->db->insert('licences', array(
                'pilote' => $login,
                'type' => 0,
                'year' => $year,
                'date' => $year . '-03-01',
                'comment' => 'test statistiques adhérents',
            ));
        }
        if ($with_account) {
            $this->CI->db->insert('comptes', array(
                'nom' => '(411) ' . $login,
                'pilote' => $login,
                'codec' => '411',
                'club' => $this->section_id,
                'actif' => 1,
                'debit' => 0,
                'credit' => 0,
                'saisie_par' => 'test',
            ));
        }
        return $login;
    }

    public function testDonneesBrutesEtRepartition()
    {
        $jeune = $this->create_member('1980-06-15', array(self::YEAR, self::YEAR));   // 14 ans, cotisation en double
        $ancien = $this->create_member('1930-01-01', array(1994, self::YEAR));         // 65 ans
        $inconnu = $this->create_member(null, array(self::YEAR));
        $sans_compte = $this->create_member('1960-02-02', array(self::YEAR), false);   // 34 ans
        $parti = $this->create_member('1970-02-02', array(1994));

        $data = $this->model->get_adherents_data(self::YEAR);
        $members = $data['members'];

        $this->assertSame(array(self::YEAR), $members[$jeune]['years'], 'cotisations dédoublonnées');
        $this->assertSame(array($this->section_id), $members[$jeune]['sections']);
        $this->assertSame(array(), $members[$sans_compte]['sections']);
        $this->assertArrayHasKey($parti, $members);

        $adherents = $this->stats->adherents_de_l_annee($members, self::YEAR);
        $this->assertCount(4, $adherents);

        $col = 'section_' . $this->section_id;
        $r = $this->stats->repartition_par_age($adherents, $data['sections'], self::YEAR, 'tranche_10_ans');
        $this->assertSame(4, $r['total']['club_total']);
        $this->assertSame(3, $r['total'][$col]);
        $this->assertSame(1, $r['counts']['lt_20']['club_total']);
        $this->assertSame(1, $r['counts']['30_39']['club_total']);
        $this->assertSame(0, $r['counts']['30_39'][$col]);
        $this->assertSame(1, $r['counts']['60_69'][$col]);
        $this->assertSame(1, $r['counts']['unknown'][$col]);

        $regl = $this->stats->repartition_par_age($adherents, $data['sections'], self::YEAR, 'classe_reglementaire');
        $this->assertSame(1, $regl['counts']['under_25']['club_total']);
        $this->assertSame(1, $regl['counts']['25_to_59']['club_total']);
        $this->assertSame(1, $regl['counts']['60_and_over']['club_total']);
        $this->assertSame(1, $regl['counts']['unknown']['club_total']);

        $this->assertSame(array($inconnu), array_column($this->stats->adherents_age_inconnu($adherents, self::YEAR), 'mlogin'));
        $this->assertSame(array(1994, self::YEAR), $members[$ancien]['years']);
    }

    public function testCotisationsPosterieuresIgnorees()
    {
        $login = $this->create_member('1980-06-15', array(1994, self::YEAR));
        $data = $this->model->get_adherents_data(1994);
        $this->assertSame(array(1994), $data['members'][$login]['years']);
    }
}
