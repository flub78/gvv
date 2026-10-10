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

    private function member($mlogin, $mdaten, $years, $sections = array(), $mnom = null)
    {
        return array(
            'mlogin' => $mlogin,
            'mnom' => $mnom ?: strtoupper($mlogin),
            'mprenom' => 'Test',
            'mdaten' => $mdaten,
            'msexe' => 'M',
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
}
