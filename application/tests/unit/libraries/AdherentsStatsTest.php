<?php
/**
 * Tests unitaires — Adherents_stats
 *
 * Calculs purs des statistiques adhérents, sans base de données.
 *
 * @see doc/plans/statistiques_adherents_plan.md
 */

use PHPUnit\Framework\TestCase;

class AdherentsStatsTest extends TestCase
{
    private $stats;

    protected function setUp(): void
    {
        parent::setUp();
        $CI = &get_instance();
        $CI->load->library('Adherents_stats');
        $this->stats = $CI->adherents_stats;
    }

    private function member($mlogin, $mdaten, $years, $sections = array(), $mnom = null, $msexe = 'M')
    {
        return array(
            'mlogin' => $mlogin,
            'mnom' => $mnom ?: strtoupper($mlogin),
            'mprenom' => 'Test',
            'mdaten' => $mdaten,
            'msexe' => $msexe,
            'inscription_date' => null,
            'years' => $years,
            'sections' => $sections,
        );
    }

    private function sections()
    {
        return array(array('id' => 1, 'nom' => 'Planeur'), array('id' => 2, 'nom' => 'ULM'));
    }

    // --- Âge au 1er janvier ---

    public function testAgeAvantAnniversaire()
    {
        $this->assertSame(22, $this->stats->age_au_1er_janvier('2002-06-15', 2025));
    }

    public function testAgeNeUn1erJanvierAAnniversaireAtteint()
    {
        $this->assertSame(25, $this->stats->age_au_1er_janvier('2000-01-01', 2025));
        $this->assertSame(24, $this->stats->age_au_1er_janvier('2000-01-02', 2025));
    }

    public function testAgeZeroPourNaissanceLe1erJanvierDeLAnnee()
    {
        $this->assertSame(0, $this->stats->age_au_1er_janvier('2025-01-01', 2025));
    }

    /**
     * @dataProvider datesInvalides
     */
    public function testAgeInconnu($mdaten)
    {
        $this->assertNull($this->stats->age_au_1er_janvier($mdaten, 2025));
    }

    public function datesInvalides()
    {
        return array(
            'null' => array(null),
            'vide' => array(''),
            'zero' => array('0000-00-00'),
            'avant 1900' => array('1899-12-31'),
            'apres le 1er janvier' => array('2025-01-02'),
            'date impossible' => array('2001-02-30'),
            'format inattendu' => array('15/06/2002'),
        );
    }

    // --- Classifications ---

    public function testClassesReglementairesAuxFrontieres()
    {
        $this->assertSame('under_25', $this->stats->classe_reglementaire(24));
        $this->assertSame('25_to_59', $this->stats->classe_reglementaire(25));
        $this->assertSame('25_to_59', $this->stats->classe_reglementaire(59));
        $this->assertSame('60_and_over', $this->stats->classe_reglementaire(60));
        $this->assertSame('unknown', $this->stats->classe_reglementaire(null));
    }

    public function testTranches10AnsAuxFrontieres()
    {
        $this->assertSame('lt_20', $this->stats->tranche_10_ans(0));
        $this->assertSame('lt_20', $this->stats->tranche_10_ans(19));
        $this->assertSame('20_29', $this->stats->tranche_10_ans(20));
        $this->assertSame('70_79', $this->stats->tranche_10_ans(79));
        $this->assertSame('80_plus', $this->stats->tranche_10_ans(80));
        $this->assertSame('80_plus', $this->stats->tranche_10_ans(102));
        $this->assertSame('unknown', $this->stats->tranche_10_ans(null));
    }

    public function testClesDansLOrdreAvecInconnuEnDernier()
    {
        $this->assertSame(
            array('lt_20', '20_29', '30_39', '40_49', '50_59', '60_69', '70_79', '80_plus', 'unknown'),
            $this->stats->cles_tranches_10_ans()
        );
        $this->assertSame(
            array('under_25', '25_to_59', '60_and_over', 'unknown'),
            $this->stats->cles_classes_reglementaires()
        );
    }

    // --- Adhérents de l'année ---

    public function testAdherentsDeLAnneeFiltreSurLesCotisations()
    {
        $members = array(
            'a' => $this->member('a', '1980-05-05', array(2024, 2025)),
            'b' => $this->member('b', '1980-05-05', array(2024)),
        );
        $this->assertSame(array('a'), array_keys($this->stats->adherents_de_l_annee($members, 2025)));
    }

    // --- Répartition ---

    public function testRepartitionTotauxCoherents()
    {
        $adherents = array(
            $this->member('a', '2010-03-03', array(2025), array(1)),        // 14 ans
            $this->member('b', '1980-03-03', array(2025), array(1, 2)),     // 44 ans, deux sections
            $this->member('c', null, array(2025), array(2)),                // inconnu
            $this->member('d', '1940-03-03', array(2025), array()),         // 84 ans, sans compte 411
            $this->member('e', '1990-03-03', array(2025), array(99)),       // section inexistante
        );
        $r = $this->stats->repartition_par_age($adherents, $this->sections(), 2025, 'tranche_10_ans');

        $this->assertSame(1, $r['counts']['lt_20']['section_1']);
        $this->assertSame(1, $r['counts']['40_49']['section_1']);
        $this->assertSame(1, $r['counts']['40_49']['section_2']);
        $this->assertSame(1, $r['counts']['40_49']['club_total']);
        $this->assertSame(1, $r['counts']['unknown']['section_2']);
        $this->assertSame(1, $r['counts']['80_plus']['club_total']);
        $this->assertSame(0, $r['counts']['60_69']['club_total']);

        $this->assertSame(array('section_1' => 2, 'section_2' => 2, 'club_total' => 5), $r['total']);

        // Somme des lignes (âge inconnu inclus) = total de chaque colonne
        foreach ($r['total'] as $column => $total) {
            $sum = 0;
            foreach ($r['counts'] as $row) {
                $sum += $row[$column];
            }
            $this->assertSame($total, $sum, "colonne $column");
        }
    }

    public function testRepartitionReglementaireToutesLesClesPresentes()
    {
        $r = $this->stats->repartition_par_age(array(), $this->sections(), 2025, 'classe_reglementaire');
        $this->assertSame(array('under_25', '25_to_59', '60_and_over', 'unknown'), array_keys($r['counts']));
        $this->assertSame(array('section_1' => 0, 'section_2' => 0, 'club_total' => 0), $r['total']);
    }

    // --- Âge inconnu ---

    public function testAdherentsAgeInconnuTriesParNom()
    {
        $adherents = array(
            $this->member('z', null, array(2025), array(), 'ZORRO'),
            $this->member('k', '1980-01-01', array(2025), array(), 'CONNU'),
            $this->member('a', '0000-00-00', array(2025), array(), 'ALPHA'),
        );
        $list = $this->stats->adherents_age_inconnu($adherents, 2025);
        $this->assertSame(array('a', 'z'), array_column($list, 'mlogin'));
    }

    // --- Pourcentage ---

    public function testPourcentage()
    {
        $this->assertSame(33, $this->stats->pourcentage(1, 3));
        $this->assertSame(100, $this->stats->pourcentage(4, 4));
        $this->assertSame(0, $this->stats->pourcentage(0, 0));
    }

    // --- Sexe ---

    public function testSexe()
    {
        $this->assertSame('sex_M', $this->stats->sexe('M'));
        $this->assertSame('sex_F', $this->stats->sexe('F'));
        $this->assertSame('sex_unknown', $this->stats->sexe(''));
        $this->assertSame('sex_unknown', $this->stats->sexe(null));
        $this->assertSame('sex_unknown', $this->stats->sexe('X'));
    }

    public function testRepartitionParSexe()
    {
        $adherents = array(
            $this->member('a', '1980-01-01', array(2025), array(1), null, 'F'),
            $this->member('b', '1980-01-01', array(2025), array(1, 2), null, 'M'),
            $this->member('c', '1980-01-01', array(2025), array(2), null, ''),
        );
        $r = $this->stats->repartition_par_sexe($adherents, $this->sections());
        $this->assertSame(array('sex_M', 'sex_F', 'sex_unknown'), array_keys($r['counts']));
        $this->assertSame(1, $r['counts']['sex_F']['section_1']);
        $this->assertSame(1, $r['counts']['sex_M']['section_2']);
        $this->assertSame(1, $r['counts']['sex_unknown']['club_total']);
        $this->assertSame(array('section_1' => 2, 'section_2' => 2, 'club_total' => 3), $r['total']);
    }

    public function testPyramideDesAges()
    {
        $adherents = array(
            $this->member('a', '1960-05-05', array(2025), array(), null, 'F'),   // 64 ans
            $this->member('b', '1962-05-05', array(2025), array(), null, 'M'),   // 62 ans
            $this->member('c', '1958-05-05', array(2025), array(), null, 'M'),   // 66 ans
            $this->member('d', null, array(2025), array(), null, 'F'),
        );
        $p = $this->stats->pyramide_des_ages($adherents, 2025);
        $this->assertSame($this->stats->cles_tranches_10_ans(), array_keys($p));
        $this->assertSame(array('sex_M' => 2, 'sex_F' => 1, 'sex_unknown' => 0), $p['60_69']);
        $this->assertSame(1, $p['unknown']['sex_F']);
        $this->assertSame(0, $p['20_29']['sex_M']);
    }

    // --- Moyenne et médiane ---

    public function testMoyenne()
    {
        $this->assertSame(20.0, $this->stats->moyenne(array(10, 30)));
        $this->assertSame(23.3, $this->stats->moyenne(array(10, 30, 30)));
        $this->assertNull($this->stats->moyenne(array()));
    }

    public function testMediane()
    {
        $this->assertSame(30.0, $this->stats->mediane(array(70, 10, 30)));
        $this->assertSame(25.0, $this->stats->mediane(array(40, 10, 20, 30)));
        $this->assertSame(42.5, $this->stats->mediane(array(42, 43)));
        $this->assertSame(17.0, $this->stats->mediane(array(17)));
        $this->assertNull($this->stats->mediane(array()));
    }

    // --- Indicateurs ---

    public function testIndicateurs()
    {
        $adherents = array(
            $this->member('a', '2005-03-03', array(2025), array(1)),     // 19 ans
            $this->member('b', '1965-03-03', array(2025), array(1, 2)),  // 59 ans
            $this->member('c', null, array(2025), array(2)),
            $this->member('d', '1975-03-03', array(2025), array()),      // 49 ans
        );
        $i = $this->stats->indicateurs($adherents, $this->sections(), 2025);

        $this->assertSame(array('effectif' => 2, 'age_moyen' => 39.0, 'age_median' => 39.0, 'age_inconnu' => 0), $i['section_1']);
        $this->assertSame(array('effectif' => 2, 'age_moyen' => 59.0, 'age_median' => 59.0, 'age_inconnu' => 1), $i['section_2']);
        $this->assertSame(array('effectif' => 4, 'age_moyen' => 42.3, 'age_median' => 49.0, 'age_inconnu' => 1), $i['club_total']);
    }

    public function testIndicateursSansAgeConnu()
    {
        $i = $this->stats->indicateurs(array($this->member('a', null, array(2025), array(1))), $this->sections(), 2025);
        $this->assertNull($i['section_1']['age_moyen']);
        $this->assertNull($i['section_1']['age_median']);
        $this->assertSame(1, $i['section_1']['age_inconnu']);
        $this->assertSame(0, $i['section_2']['effectif']);
    }

    // --- Évolution ---

    public function testEvolutionIgnoreLesAnneesSansCotisationEtLesAnneesFutures()
    {
        $members = array(
            'a' => $this->member('a', '2000-06-01', array(2012, 2025, 2026)),
            'b' => $this->member('b', '1950-06-01', array(2025)),
            'c' => $this->member('c', null, array(2026)),
        );
        $e = $this->stats->evolution($members, 2025);

        $this->assertSame(array(2012, 2025), array_keys($e));
        $this->assertSame(1, $e[2012]['effectif']);
        $this->assertSame(11.0, $e[2012]['age_moyen']);
        $this->assertSame(2, $e[2025]['effectif']);
        $this->assertSame(1, $e[2025]['under_25']);
        $this->assertSame(1, $e[2025]['60_and_over']);
        $this->assertSame(0, $e[2025]['unknown']);
        $this->assertSame(49.0, $e[2025]['age_moyen']);
    }

    public function testEvolutionLimiteeAuxDernieresAnnees()
    {
        $members = array('a' => $this->member('a', '1980-01-01', range(2000, 2025)));
        $e = $this->stats->evolution($members, 2025, 10);
        $this->assertSame(range(2016, 2025), array_keys($e));
    }

    // --- Fidélisation ---

    public function testCategorieFidelisation()
    {
        $this->assertSame('nouveau', $this->stats->categorie_fidelisation(array(2025), 2025));
        $this->assertSame('renouvellement', $this->stats->categorie_fidelisation(array(2024, 2025), 2025));
        $this->assertSame('retour', $this->stats->categorie_fidelisation(array(2020, 2025), 2025));
        $this->assertSame('depart', $this->stats->categorie_fidelisation(array(2023, 2024), 2025));
        $this->assertNull($this->stats->categorie_fidelisation(array(2020), 2025));
        $this->assertNull($this->stats->categorie_fidelisation(array(2026), 2025));
        // Une cotisation postérieure ne change pas la catégorie
        $this->assertSame('nouveau', $this->stats->categorie_fidelisation(array(2025, 2026), 2025));
    }

    public function testFidelisationParSectionEtClub()
    {
        $members = array(
            'n' => $this->member('n', '1980-01-01', array(2025), array(1), 'NOUVEAU'),
            'r' => $this->member('r', '1980-01-01', array(2020, 2025), array(1, 2), 'RETOUR'),
            'u' => $this->member('u', '1980-01-01', array(2024, 2025), array(2), 'RENOUV'),
            'd' => $this->member('d', '1980-01-01', array(2024), array(1), 'DEPART'),
            'x' => $this->member('x', '1980-01-01', array(2019), array(1), 'ANCIEN'),
        );
        $f = $this->stats->fidelisation($members, $this->sections(), 2025);

        $this->assertSame(array('section_1' => 1, 'section_2' => 0, 'club_total' => 1), $f['counts']['nouveau']);
        $this->assertSame(array('section_1' => 1, 'section_2' => 1, 'club_total' => 1), $f['counts']['retour']);
        $this->assertSame(array('section_1' => 0, 'section_2' => 1, 'club_total' => 1), $f['counts']['renouvellement']);
        $this->assertSame(array('section_1' => 1, 'section_2' => 0, 'club_total' => 1), $f['counts']['depart']);
        $this->assertSame(array('d'), array_column($f['members']['depart']['club_total'], 'mlogin'));

        // Effectif N = nouveaux + retours + renouvellements ; effectif N-1 = renouvellements + départs
        $adherents_n = $this->stats->adherents_de_l_annee($members, 2025);
        $adherents_n1 = $this->stats->adherents_de_l_annee($members, 2024);
        $total_n = $this->stats->repartition_par_age($adherents_n, $this->sections(), 2025, 'classe_reglementaire')['total'];
        $total_n1 = $this->stats->repartition_par_age($adherents_n1, $this->sections(), 2024, 'classe_reglementaire')['total'];
        foreach ($total_n as $column => $n) {
            $this->assertSame($n, $f['counts']['nouveau'][$column] + $f['counts']['retour'][$column] + $f['counts']['renouvellement'][$column], "N $column");
            $this->assertSame($total_n1[$column], $f['counts']['renouvellement'][$column] + $f['counts']['depart'][$column], "N-1 $column");
        }
    }

    public function testFidelisationListesTrieesParNom()
    {
        $members = array(
            'b' => $this->member('b', '1980-01-01', array(2025), array(), 'ZED'),
            'a' => $this->member('a', '1980-01-01', array(2025), array(), 'ALPHA'),
        );
        $f = $this->stats->fidelisation($members, $this->sections(), 2025);
        $this->assertSame(array('a', 'b'), array_column($f['members']['nouveau']['club_total'], 'mlogin'));
    }

    public function testRetentionDesNouveaux()
    {
        $members = array(
            'a' => $this->member('a', '1980-01-01', array(2024, 2025), array(1)),
            'b' => $this->member('b', '1980-01-01', array(2024), array(1)),
            'c' => $this->member('c', '1980-01-01', array(2024), array(1)),
            'd' => $this->member('d', '1980-01-01', array(2020, 2024, 2025), array(1)),  // retour en 2024, pas nouveau
        );
        $f = $this->stats->fidelisation($members, $this->sections(), 2025);
        $this->assertSame(array('nouveaux_n1' => 3, 'toujours_adherents' => 1, 'taux' => 33), $f['retention']['section_1']);
        $this->assertSame(array('nouveaux_n1' => 0, 'toujours_adherents' => 0, 'taux' => null), $f['retention']['section_2']);
    }

    // --- Ancienneté ---

    public function testAncienneteSurLaPlusAncienneDate()
    {
        $m = $this->member('a', '1980-01-01', array(2020, 2025));
        $m['inscription_date'] = '2015-06-01';
        $this->assertSame(9, $this->stats->anciennete($m, 2025));

        $m['inscription_date'] = '2022-06-01';
        $this->assertSame(5, $this->stats->anciennete($m, 2025));
    }

    public function testAncienneteCasLimites()
    {
        $m = $this->member('a', '1980-01-01', array(2025));
        $this->assertSame(0, $this->stats->anciennete($m, 2025));

        $m['inscription_date'] = '0000-00-00';
        $this->assertSame(0, $this->stats->anciennete($m, 2025));

        $m['years'] = array();
        $this->assertNull($this->stats->anciennete($m, 2025));

        $m['inscription_date'] = '2025-09-01';
        $this->assertSame(0, $this->stats->anciennete($m, 2025));
    }

    public function testTranchesAnciennete()
    {
        $this->assertSame('anc_lt_2', $this->stats->tranche_anciennete(0));
        $this->assertSame('anc_lt_2', $this->stats->tranche_anciennete(1));
        $this->assertSame('anc_2_5', $this->stats->tranche_anciennete(2));
        $this->assertSame('anc_2_5', $this->stats->tranche_anciennete(4));
        $this->assertSame('anc_5_10', $this->stats->tranche_anciennete(5));
        $this->assertSame('anc_5_10', $this->stats->tranche_anciennete(9));
        $this->assertSame('anc_10_plus', $this->stats->tranche_anciennete(10));
        $this->assertSame('anc_unknown', $this->stats->tranche_anciennete(null));
    }

    public function testRepartitionParAnciennete()
    {
        $old = $this->member('a', '1980-01-01', array(2010, 2025), array(1));
        $new = $this->member('b', '1980-01-01', array(2025), array(2));
        $r = $this->stats->repartition_par_anciennete(array($old, $new), $this->sections(), 2025);
        $this->assertSame($this->stats->cles_anciennete(), array_keys($r['counts']));
        $this->assertSame(1, $r['counts']['anc_10_plus']['section_1']);
        $this->assertSame(1, $r['counts']['anc_lt_2']['section_2']);
        $this->assertSame(2, $r['total']['club_total']);
    }
}
