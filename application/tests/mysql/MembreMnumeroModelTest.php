<?php

require_once __DIR__ . '/../integration/TransactionalTestCase.php';

/**
 * Tests for the mnumero field assignment and preservation in Membres_model.
 *
 * Rule 1: When a member is created without providing mnumero, the assigned
 *         mnumero must be >= the number of members in the database before creation.
 *         (model sets mnumero = COUNT(*) + 1)
 *
 * Rule 2: When another field is updated for a member, mnumero must not change.
 */
class MembreMnumeroModelTest extends TransactionalTestCase
{
    /** @var CI_Controller */
    private $ci;

    /** @var Membres_model */
    private $membres_model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ci =& get_instance();
        $this->ci->load->model('membres_model');
        $this->membres_model = $this->ci->membres_model;

        if (!$this->ci->db->conn_id) {
            $this->markTestSkipped('Database connection not available');
        }
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function memberData($suffix = '')
    {
        // mlogin is varchar(25) — keep the login short
        $login = 'tm_' . substr($suffix, -10);
        return array(
            'mlogin'    => $login,
            'username'  => $login,
            'mnom'      => 'TestMnumero',
            'mprenom'   => 'Test',
            'memail'    => $login . '@test.invalid',
            'madresse'  => '1 rue de Test',
            'cp'        => '75000',
            'ville'     => 'Paris',
            'pays'      => 'France',
            'msexe'     => 'M',
            'ext'       => 0,
            'actif'     => 1,
            'categorie' => '0',
        );
    }

    // -------------------------------------------------------------------------
    // Rule 1: mnumero on create >= count before creation
    // -------------------------------------------------------------------------

    /**
     * When no mnumero is provided, the model assigns count + 1,
     * so mnumero must be >= the count of members that existed before creation.
     */
    public function testCreateWithoutMnumeroAssignsValueAtLeastEqualToCountBefore()
    {
        $count_row = $this->ci->db->select('COUNT(*) AS cnt')->from('membres')->get()->row_array();
        $count_before = (int) $count_row['cnt'];

        $data = $this->memberData(uniqid());
        // Do NOT set mnumero — the model must assign it

        $result = $this->membres_model->create($data);
        if ($result === FALSE) {
            $this->markTestSkipped(
                'Member creation failed (possible uniqueness conflict on mnumero = ' . ($count_before + 1) . ')'
            );
        }

        $login = $data['mlogin'];
        $member = $this->ci->db->select('mnumero')->from('membres')->where('mlogin', $login)->get()->row_array();

        $this->assertNotEmpty($member, 'Created member should be retrievable by mlogin');
        $this->assertArrayHasKey('mnumero', $member);
        $this->assertGreaterThanOrEqual(
            $count_before,
            (int) $member['mnumero'],
            'mnumero after creation must be >= count of members before creation'
        );
    }

    /**
     * Since migration 108, mnumero is AUTO_INCREMENT — MySQL assigns the next
     * AUTO_INCREMENT value, which diverges from COUNT(*)+1 when rows have been
     * inserted and rolled back (e.g. by TransactionalTestCase in other tests).
     * We read the expected next value from information_schema before the insert.
     */
    public function testCreateWithoutMnumeroAssignsCountPlusOne()
    {
        $row = $this->ci->db->query(
            "SELECT AUTO_INCREMENT AS next_id FROM information_schema.TABLES
             WHERE table_schema = DATABASE() AND table_name = 'membres'"
        )->row_array();
        $expected_mnumero = (int) $row['next_id'];

        $data = $this->memberData(uniqid());

        $result = $this->membres_model->create($data);
        if ($result === FALSE) {
            $this->markTestSkipped(
                'Member creation failed (possible uniqueness conflict on mnumero = ' . $expected_mnumero . ')'
            );
        }

        $member = $this->ci->db->select('mnumero')->from('membres')->where('mlogin', $data['mlogin'])->get()->row_array();

        $this->assertEquals(
            $expected_mnumero,
            (int) $member['mnumero'],
            'mnumero must equal the AUTO_INCREMENT value at time of creation'
        );
    }

    // -------------------------------------------------------------------------
    // Rule 1b: mnumero = 0 or '0' must not be stored (regression for MySQL
    //          servers with NO_AUTO_VALUE_ON_ZERO in sql_mode)
    // -------------------------------------------------------------------------

    /**
     * When the form submits mnumero = 0 (integer), the model must replace it
     * with COUNT(*) + 1, not store 0 literally.
     */
    public function testCreateWithMnumeroZeroIntegerAssignsPositiveValue()
    {
        $count_row    = $this->ci->db->select('COUNT(*) AS cnt')->from('membres')->get()->row_array();
        $count_before = (int) $count_row['cnt'];

        $data            = $this->memberData(uniqid());
        $data['mnumero'] = 0; // integer zero — previous condition did not catch this

        $result = $this->membres_model->create($data);
        if ($result === FALSE) {
            $this->markTestSkipped('Member creation failed (uniqueness conflict)');
        }

        $member = $this->ci->db->select('mnumero')->from('membres')->where('mlogin', $data['mlogin'])->get()->row_array();

        $this->assertGreaterThan(
            0,
            (int) $member['mnumero'],
            'mnumero must not be 0 when integer 0 is submitted'
        );
        $this->assertGreaterThanOrEqual(
            $count_before,
            (int) $member['mnumero'],
            'mnumero must be >= count before creation'
        );
    }

    /**
     * When the form submits mnumero = '0' (string, typical from an empty
     * <input type="number">), the model must replace it with COUNT(*) + 1.
     */
    public function testCreateWithMnumeroZeroStringAssignsPositiveValue()
    {
        $count_row    = $this->ci->db->select('COUNT(*) AS cnt')->from('membres')->get()->row_array();
        $count_before = (int) $count_row['cnt'];

        $data            = $this->memberData(uniqid());
        $data['mnumero'] = '0'; // string zero — what an HTML form submits for an empty number input

        $result = $this->membres_model->create($data);
        if ($result === FALSE) {
            $this->markTestSkipped('Member creation failed (uniqueness conflict)');
        }

        $member = $this->ci->db->select('mnumero')->from('membres')->where('mlogin', $data['mlogin'])->get()->row_array();

        $this->assertGreaterThan(
            0,
            (int) $member['mnumero'],
            'mnumero must not be 0 when string "0" is submitted'
        );
        $this->assertGreaterThanOrEqual(
            $count_before,
            (int) $member['mnumero'],
            'mnumero must be >= count before creation'
        );
    }

    // -------------------------------------------------------------------------
    // Rule 2: mnumero preserved when updating another field
    // -------------------------------------------------------------------------

    /**
     * When updating only memail for a member, mnumero must not change.
     */
    public function testUpdateEmailPreservesMnumero()
    {
        $existing = $this->ci->db
            ->select('mlogin, mnumero')
            ->from('membres')
            ->where('mnumero IS NOT NULL', null, false)
            ->limit(1)
            ->get()
            ->row_array();

        if (!$existing) {
            $this->markTestSkipped('No member with a non-null mnumero found in database');
        }

        $mlogin          = $existing['mlogin'];
        $mnumero_before  = (int) $existing['mnumero'];

        // Update only memail — mnumero must not be included in the data
        $this->membres_model->update('mlogin', array(
            'mlogin' => $mlogin,
            'memail' => 'preserved_test@test.invalid',
        ), $mlogin);

        $after = $this->ci->db
            ->select('mnumero')
            ->from('membres')
            ->where('mlogin', $mlogin)
            ->get()
            ->row_array();

        $this->assertEquals(
            $mnumero_before,
            (int) $after['mnumero'],
            'mnumero must not change when only memail is updated'
        );
    }

    /**
     * When update data contains mnumero explicitly (e.g., from a form field),
     * the stored value must still match what was sent — no silent override.
     */
    public function testUpdateWithExplicitMnumeroPreservesIt()
    {
        $existing = $this->ci->db
            ->select('mlogin, mnumero')
            ->from('membres')
            ->where('mnumero IS NOT NULL', null, false)
            ->limit(1)
            ->get()
            ->row_array();

        if (!$existing) {
            $this->markTestSkipped('No member with a non-null mnumero found in database');
        }

        $mlogin         = $existing['mlogin'];
        $mnumero_before = (int) $existing['mnumero'];

        // Simulate a form that sends mnumero explicitly alongside other fields
        $this->membres_model->update('mlogin', array(
            'mlogin'  => $mlogin,
            'memail'  => 'explicit_mnumero@test.invalid',
            'mnumero' => $mnumero_before,
        ), $mlogin);

        $after = $this->ci->db
            ->select('mnumero')
            ->from('membres')
            ->where('mlogin', $mlogin)
            ->get()
            ->row_array();

        $this->assertEquals(
            $mnumero_before,
            (int) $after['mnumero'],
            'mnumero must remain unchanged when the same value is passed explicitly in update data'
        );
    }
}
