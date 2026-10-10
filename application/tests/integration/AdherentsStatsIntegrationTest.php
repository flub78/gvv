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
        $r = $this->stats->repartition_par_tranche_10_ans($adherents, $data['sections'], self::YEAR);
        $this->assertSame(4, $r['total']['club_total']);
        $this->assertSame(3, $r['total'][$col]);
        $this->assertSame(1, $r['counts']['lt_20']['club_total']);
        $this->assertSame(1, $r['counts']['30_39']['club_total']);
        $this->assertSame(0, $r['counts']['30_39'][$col]);
        $this->assertSame(1, $r['counts']['60_69'][$col]);
        $this->assertSame(1, $r['counts']['unknown'][$col]);

        $regl = $this->stats->repartition_par_classe_reglementaire($adherents, $data['sections'], self::YEAR);
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

    public function testIndicateursSexesEtEvolution()
    {
        $a = $this->create_member('1985-06-15', array(1994, self::YEAR));   // 8 ans en 1994, 9 ans en 1995
        $b = $this->create_member('1935-06-15', array(self::YEAR));         // 59 ans
        $this->create_member(null, array(self::YEAR));

        $data = $this->model->get_adherents_data(self::YEAR);
        $adherents = $this->stats->adherents_de_l_annee($data['members'], self::YEAR);
        $col = 'section_' . $this->section_id;

        $ind = $this->stats->indicateurs($adherents, $data['sections'], self::YEAR);
        $this->assertSame(3, $ind[$col]['effectif']);
        $this->assertSame(34.0, $ind[$col]['age_moyen']);
        $this->assertSame(34.0, $ind[$col]['age_median']);
        $this->assertSame(1, $ind[$col]['age_inconnu']);

        $sexes = $this->stats->repartition_par_sexe($adherents, $data['sections']);
        $this->assertSame(3, $sexes['counts']['sex_F']['club_total']);

        $evolution = $this->stats->evolution($data['members'], self::YEAR);
        $this->assertSame(array(1994, self::YEAR), array_keys($evolution));
        $this->assertSame(1, $evolution[1994]['effectif']);
        $this->assertSame(8.0, $evolution[1994]['age_moyen']);
        $this->assertSame(3, $evolution[self::YEAR]['effectif']);
        $this->assertSame(1, $evolution[self::YEAR]['unknown']);
    }

    public function testFidelisationEtAnciennete()
    {
        $renouvele = $this->create_member('1980-06-15', array(1994, self::YEAR));
        $nouveau = $this->create_member('1980-06-15', array(self::YEAR));
        $parti = $this->create_member('1980-06-15', array(1994));
        $sans_compte = $this->create_member('1980-06-15', array(1994), false);

        $data = $this->model->get_adherents_data(self::YEAR);
        $col = 'section_' . $this->section_id;
        $f = $this->stats->fidelisation($data['members'], $data['sections'], self::YEAR);

        $this->assertSame(1, $f['counts']['renouvellement'][$col]);
        $this->assertSame(1, $f['counts']['nouveau'][$col]);
        $this->assertSame(1, $f['counts']['depart'][$col]);
        $this->assertSame(2, $f['counts']['depart']['club_total']);
        $this->assertSame(array($nouveau), array_column($f['members']['nouveau'][$col], 'mlogin'));
        $this->assertContains($sans_compte, array_column($f['members']['depart']['club_total'], 'mlogin'));
        // Nouveaux de 1994 : renouvele, parti, sans_compte ; seul renouvele est resté
        $this->assertSame(array('nouveaux_n1' => 3, 'toujours_adherents' => 1, 'taux' => 33), $f['retention']['club_total']);

        $adherents = $this->stats->adherents_de_l_annee($data['members'], self::YEAR);
        $anc = $this->stats->repartition_par_anciennete($adherents, $data['sections'], self::YEAR);
        $this->assertSame(2, $anc['counts']['anc_lt_2']['club_total']);
        $this->assertNotContains($parti, array_column($adherents, 'mlogin'));
        $this->assertContains($renouvele, array_column($adherents, 'mlogin'));
    }
}
