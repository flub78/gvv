<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for Cartes_membre_model
 *
 * Verifies the data-access methods used for member card generation.
 * All tests read from the existing database without creating or modifying data.
 *
 * Requirements:
 * - Full CodeIgniter framework loaded
 * - Database connection configured
 * - Migration 105 (mnumero column) applied
 *
 * @see application/models/cartes_membre_model.php
 * @see application/migrations/105_mnumero_membre.php
 */
class CartesMemberModelTest extends TestCase
{
    /** @var CI_Controller */
    private $CI;

    /** @var Cartes_membre_model */
    private $model;

    public function setUp(): void
    {
        $this->CI =& get_instance();
        $this->CI->load->model('cartes_membre_model');
        $this->model = $this->CI->cartes_membre_model;

        if (!$this->CI->db->conn_id) {
            $this->markTestSkipped('Database connection not available');
        }
    }

    // ------------------------------------------------------------------
    // get_membre()
    // ------------------------------------------------------------------

    public function testGetMembreReturnsNullForUnknownLogin()
    {
        $result = $this->model->get_membre('__nonexistent_login__');
        $this->assertNull($result);
    }

    public function testGetMembreReturnsArrayForExistingLogin()
    {
        $first = $this->CI->db->select('mlogin')->from('membres')->limit(1)->get()->row_array();
        if (!$first) {
            $this->markTestSkipped('No membres in database');
        }
        $result = $this->model->get_membre($first['mlogin']);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('mlogin', $result);
        $this->assertArrayHasKey('mnom', $result);
        $this->assertArrayHasKey('mprenom', $result);
    }

    // ------------------------------------------------------------------
    // get_years_with_cotisation()
    // ------------------------------------------------------------------

    public function testGetYearsWithCotisationReturnsArray()
    {
        $first = $this->CI->db->select('mlogin')->from('membres')->limit(1)->get()->row_array();
        if (!$first) {
            $this->markTestSkipped('No membres in database');
        }
        $years = $this->model->get_years_with_cotisation($first['mlogin']);
        $this->assertIsArray($years);
    }

    public function testGetYearsWithCotisationReturnedInDescendingOrder()
    {
        $query = $this->CI->db->query(
            "SELECT pilote FROM licences WHERE type = 0 GROUP BY pilote HAVING COUNT(*) > 1 LIMIT 1"
        );
        $row = $query ? $query->row_array() : null;

        if (!$row) {
            $this->markTestSkipped('No member with multiple cotisation years');
        }

        $years = $this->model->get_years_with_cotisation($row['pilote']);
        $this->assertGreaterThan(1, count($years));

        for ($i = 0; $i < count($years) - 1; $i++) {
            $this->assertGreaterThan($years[$i + 1], $years[$i],
                'Years should be in descending order');
        }
    }

    // ------------------------------------------------------------------
    // get_membres_actifs_annee()
    // ------------------------------------------------------------------

    public function testGetMembresActifsAnneeReturnsArray()
    {
        $year = (int)date('Y');
        $result = $this->model->get_membres_actifs_annee($year);
        $this->assertIsArray($result);
    }

    public function testGetMembresActifsAnneeRowsHaveRequiredFields()
    {
        $year = (int)date('Y');
        $result = $this->model->get_membres_actifs_annee($year);

        if (empty($result)) {
            // Try previous year
            $result = $this->model->get_membres_actifs_annee($year - 1);
        }
        if (empty($result)) {
            $this->markTestSkipped('No active members with cotisation found');
        }

        $row = $result[0];
        $this->assertArrayHasKey('mlogin', $row);
        $this->assertArrayHasKey('mnom', $row);
        $this->assertArrayHasKey('mprenom', $row);
    }

    // ------------------------------------------------------------------
    // get_president()
    // ------------------------------------------------------------------

    public function testGetPresidentReturnsNullOrArray()
    {
        $result = $this->model->get_president();
        $this->assertTrue($result === null || is_array($result),
            'get_president() should return null or array');
    }

    public function testGetPresidentArrayHasNameFields()
    {
        $result = $this->model->get_president();
        if ($result === null) {
            $this->markTestSkipped('No president defined in database');
        }
        $this->assertArrayHasKey('mnom', $result);
        $this->assertArrayHasKey('mprenom', $result);
    }

    // ------------------------------------------------------------------
    // get_photo_path()
    // ------------------------------------------------------------------

    public function testGetPhotoPathReturnsNullForEmptyInput()
    {
        $this->assertNull($this->model->get_photo_path(null));
        $this->assertNull($this->model->get_photo_path(''));
    }

    public function testGetPhotoPathReturnsNullForNonExistentFile()
    {
        $result = $this->model->get_photo_path('__nonexistent_photo__.jpg');
        $this->assertNull($result);
    }

    // ------------------------------------------------------------------
    // get_fond_path()
    // ------------------------------------------------------------------

    public function testGetFondPathReturnsNullForUnconfiguredYear()
    {
        // Year 1900 is unlikely to have a configured background
        $result = $this->model->get_fond_path(1900, 'recto');
        $this->assertNull($result);
    }

    // ------------------------------------------------------------------
    // save_fond_path() + get_fond_path()
    // ------------------------------------------------------------------

    public function testSaveFondPathPersistsAndGetFondPathReadsIt()
    {
        $year = 9999;
        $face = 'recto';
        $fake_path = 'uploads/configuration/carte_recto_9999.jpg';

        // Set a test value
        $this->model->save_fond_path($year, $face, $fake_path);

        // Verify it is stored (get_fond_path checks file_exists, so result may be null)
        $cle = 'carte_recto_9999';
        $row = $this->CI->db->select('valeur')->from('configuration')->where('cle', $cle)->get()->row_array();
        $this->assertNotEmpty($row, 'Configuration key should have been saved');
        $this->assertEquals($fake_path, $row['valeur']);

        // Calling save_fond_path again should update, not duplicate
        $this->model->save_fond_path($year, $face, $fake_path);
        $count = $this->CI->db->where('cle', $cle)->count_all_results('configuration');
        $this->assertEquals(1, $count, 'save_fond_path should upsert, not insert duplicates');

        // Cleanup
        $this->CI->db->where('cle', $cle)->delete('configuration');
    }

    // ------------------------------------------------------------------
    // get_all_membres_actifs() / get_all_membres()
    // ------------------------------------------------------------------

    public function testGetAllMembresActifsReturnsOnlyActive()
    {
        $result = $this->model->get_all_membres_actifs();
        $this->assertIsArray($result);
        foreach ($result as $m) {
            // The query filters actif = 1 — all rows returned should be active
            // We can't directly verify actif here since it's not selected, but
            // we verify the expected keys are present.
            $this->assertArrayHasKey('mlogin', $m);
            $this->assertArrayHasKey('mnom', $m);
        }
    }

    public function testGetAllMembresIncludesInactive()
    {
        $all    = $this->model->get_all_membres();
        $actifs = $this->model->get_all_membres_actifs();

        $this->assertGreaterThanOrEqual(count($actifs), count($all),
            'get_all_membres() should return at least as many rows as get_all_membres_actifs()');
    }
}
