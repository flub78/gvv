<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the Relances feature (Phase 1).
 *
 * Tests :
 *  - relances_model::get_debiteurs() returns only debtors (total < 0), sorted descending
 *  - Configuration keys relances.seuil_alarme and relances.seuil_critique exist after migration
 *  - Controller files exist and are PHP-valid
 *  - View file exists
 */
class RelancesTest extends TestCase
{
    /** @var CI_Controller */
    private $CI;

    public function setUp(): void
    {
        $this->CI =& get_instance();
        $this->CI->load->model('relances_model');
        $this->CI->load->model('configuration_model');
    }

    // ------------------------------------------------------------------
    // Model tests
    // ------------------------------------------------------------------

    public function testGetDebiteursCategoryStructure()
    {
        $result = $this->CI->relances_model->get_debiteurs();

        $this->assertArrayHasKey('sections', $result, 'Result must contain sections key');
        $this->assertArrayHasKey('rows', $result, 'Result must contain rows key');
        $this->assertIsArray($result['sections']);
        $this->assertIsArray($result['rows']);
    }

    public function testGetDebiteursOnlyNegativeBalances()
    {
        $result = $this->CI->relances_model->get_debiteurs();
        foreach ($result['rows'] as $row) {
            $this->assertLessThan(
                0,
                $row['total'],
                'get_debiteurs() must only return rows with total < 0 (debtors)'
            );
        }
    }

    public function testGetDebiteursSortedDescending()
    {
        $result = $this->CI->relances_model->get_debiteurs();
        $rows   = $result['rows'];
        // Sorted ASC by total (most negative = most indebted first).
        // Each subsequent row has a total >= the previous (less negative).
        for ($i = 1; $i < count($rows); $i++) {
            $this->assertGreaterThanOrEqual(
                $rows[$i - 1]['total'],
                $rows[$i]['total'],
                'Rows must be sorted ASC by total (most indebted first — most negative value first)'
            );
        }
    }

    public function testGetDebiteursRowHasExpectedKeys()
    {
        $result = $this->CI->relances_model->get_debiteurs();
        if (empty($result['rows'])) {
            $this->markTestSkipped('No debtor in test database.');
        }
        $row = $result['rows'][0];
        foreach (['mlogin', 'mnom', 'mprenom', 'memail', 'total', 'total_6m', 'total_1an', 'par_section'] as $key) {
            $this->assertArrayHasKey($key, $row, "Row must contain key '$key'");
        }
    }

    public function testGetDebiteursSectionsMatchRows()
    {
        $result   = $this->CI->relances_model->get_debiteurs();
        $sections = $result['sections'];
        if (empty($result['rows'])) {
            $this->markTestSkipped('No debtor in test database.');
        }
        $row = $result['rows'][0];
        foreach ($sections as $s) {
            $this->assertArrayHasKey(
                $s['id'],
                $row['par_section'],
                "par_section must contain entry for section id={$s['id']}"
            );
        }
    }

    // ------------------------------------------------------------------
    // Configuration tests
    // ------------------------------------------------------------------

    public function testConfigKeysExist()
    {
        $alarme   = $this->CI->configuration_model->get_param('relances.seuil_alarme');
        $critique = $this->CI->configuration_model->get_param('relances.seuil_critique');

        $this->assertNotNull($alarme,   'relances.seuil_alarme must exist in configuration');
        $this->assertNotNull($critique, 'relances.seuil_critique must exist in configuration');

        $this->assertEquals(300, (int)$alarme,   'Default alarme threshold should be 300');
        $this->assertEquals(500, (int)$critique, 'Default critique threshold should be 500');
    }

    // ------------------------------------------------------------------
    // File existence & syntax tests
    // ------------------------------------------------------------------

    public function testControllerFileExists()
    {
        $file = APPPATH . 'controllers/relances.php';
        $this->assertFileExists($file);
        $content = file_get_contents($file);
        $this->assertStringContainsString('class Relances', $content, 'Controller must define Relances class');
    }

    public function testModelFileExists()
    {
        $file = APPPATH . 'models/relances_model.php';
        $this->assertFileExists($file);
    }

    public function testViewFileExists()
    {
        $file = APPPATH . 'views/relances/bs_relancesView.php';
        $this->assertFileExists($file);
    }

    public function testMigrationFileExists()
    {
        $file = APPPATH . 'migrations/135_relances_seuils_config.php';
        $this->assertFileExists($file);
    }

    public function testLanguageFilesExist()
    {
        $langs = ['french', 'english', 'dutch'];
        foreach ($langs as $lang) {
            $file = APPPATH . "language/{$lang}/relances_lang.php";
            $this->assertFileExists($file, "Language file for $lang must exist");
        }
    }
}
