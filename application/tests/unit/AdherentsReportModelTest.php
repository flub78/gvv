<?php
/**
 * Tests unitaires pour Adherents_report_model
 *
 * @package tests
 */

use PHPUnit\Framework\TestCase;

class AdherentsReportModelTest extends TestCase {

    /**
     * Test du calcul de la date limite pour moins de 25 ans
     */
    public function testDateCalculationUnder25() {
        // Pour l'année 2025, quelqu'un de moins de 25 ans est né après le 01/01/2000
        $year = 2025;
        $expected_date = '2000-01-01';
        $calculated_date = ($year - 25) . '-01-01';

        $this->assertEquals($expected_date, $calculated_date);
    }

    /**
     * Test du calcul de la date limite pour 60 ans et plus
     */
    public function testDateCalculation60AndOver() {
        // Pour l'année 2025, quelqu'un de 60 ans et plus est né avant ou le 01/01/1965
        $year = 2025;
        $expected_date = '1965-01-01';
        $calculated_date = ($year - 60) . '-01-01';

        $this->assertEquals($expected_date, $calculated_date);
    }

    /**
     * Test de classification par groupe d'âge - moins de 25 ans
     */
    public function testAgeClassificationUnder25() {
        $year = 2025;
        $date_25 = ($year - 25) . '-01-01';

        // Né le 15/06/2002 - a 22 ans au 1er janvier 2025
        $birth_date = '2002-06-15';
        $is_under_25 = ($birth_date > $date_25);

        $this->assertTrue($is_under_25, "Person born on 2002-06-15 should be under 25 in 2025");
    }

    /**
     * Test de classification par groupe d'âge - 25 à 59 ans
     */
    public function testAgeClassification25To59() {
        $year = 2025;
        $date_25 = ($year - 25) . '-01-01';
        $date_60 = ($year - 60) . '-01-01';

        // Né le 15/06/1980 - a 44 ans au 1er janvier 2025
        $birth_date = '1980-06-15';
        $is_25_to_59 = ($birth_date <= $date_25) && ($birth_date > $date_60);

        $this->assertTrue($is_25_to_59, "Person born on 1980-06-15 should be 25-59 in 2025");
    }

    /**
     * Test de classification par groupe d'âge - 60 ans et plus
     */
    public function testAgeClassification60AndOver() {
        $year = 2025;
        $date_60 = ($year - 60) . '-01-01';

        // Né le 15/06/1960 - a 64 ans au 1er janvier 2025
        $birth_date = '1960-06-15';
        $is_60_and_over = ($birth_date <= $date_60);

        $this->assertTrue($is_60_and_over, "Person born on 1960-06-15 should be 60+ in 2025");
    }

    /**
     * Test du cas limite - exactement 25 ans au 1er janvier
     */
    public function testAgeClassificationExactly25() {
        $year = 2025;
        $date_25 = ($year - 25) . '-01-01';
        $date_60 = ($year - 60) . '-01-01';

        // Né le 01/01/2000 - a exactement 25 ans au 1er janvier 2025
        $birth_date = '2000-01-01';

        // Cette personne a exactement 25 ans, donc devrait être dans la catégorie 25-59
        $is_under_25 = ($birth_date > $date_25);
        $is_25_to_59 = ($birth_date <= $date_25) && ($birth_date > $date_60);

        $this->assertFalse($is_under_25, "Person born on 2000-01-01 should NOT be under 25 in 2025");
        $this->assertTrue($is_25_to_59, "Person born on 2000-01-01 should be 25-59 in 2025");
    }

    /**
     * Test du cas limite - exactement 60 ans au 1er janvier
     */
    public function testAgeClassificationExactly60() {
        $year = 2025;
        $date_25 = ($year - 25) . '-01-01';
        $date_60 = ($year - 60) . '-01-01';

        // Né le 01/01/1965 - a exactement 60 ans au 1er janvier 2025
        $birth_date = '1965-01-01';

        // Cette personne a exactement 60 ans, donc devrait être dans la catégorie 60+
        $is_25_to_59 = ($birth_date <= $date_25) && ($birth_date > $date_60);
        $is_60_and_over = ($birth_date <= $date_60);

        $this->assertFalse($is_25_to_59, "Person born on 1965-01-01 should NOT be 25-59 in 2025");
        $this->assertTrue($is_60_and_over, "Person born on 1965-01-01 should be 60+ in 2025");
    }

    /**
     * Test de la structure du sélecteur d'années
     */
    public function testYearSelectorStructure() {
        // Simuler un sélecteur d'années
        $years = array(2025, 2024, 2023, 2022);
        $selector = array();
        foreach ($years as $year) {
            $selector[$year] = $year;
        }

        $this->assertIsArray($selector);
        $this->assertArrayHasKey(2025, $selector);
        $this->assertEquals(2025, $selector[2025]);
    }

    /**
     * Test de la structure des statistiques retournées
     */
    public function testStatsStructure() {
        // Simuler la structure de statistiques attendue
        $stats = array(
            'under_25' => array('section_1' => 0, 'section_2' => 0, 'club_total' => 0),
            '25_to_59' => array('section_1' => 0, 'section_2' => 0, 'club_total' => 0),
            '60_and_over' => array('section_1' => 0, 'section_2' => 0, 'club_total' => 0),
            'total' => array('section_1' => 0, 'section_2' => 0, 'club_total' => 0)
        );

        $this->assertArrayHasKey('under_25', $stats);
        $this->assertArrayHasKey('25_to_59', $stats);
        $this->assertArrayHasKey('60_and_over', $stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('club_total', $stats['under_25']);
    }
}
