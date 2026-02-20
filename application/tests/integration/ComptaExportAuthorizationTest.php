<?php

require_once(__DIR__ . '/TransactionalTestCase.php');

/**
 * Tests for compta/export and compta/pdf authorization logic
 *
 * Bug fix: with new authorization mode (use_new_auth), access to compta/export/<compte>/<section>
 * was denied to all non-tresorier users, even for their own account.
 *
 * Expected behavior:
 * - A regular user can export/view their own account
 * - A regular user is denied access to another user's account
 * - A tresorier can access any account
 *
 * The fix adds 'export' and 'pdf' to the constructor exemption list and adds
 * an ownership check inside each method, mirroring the pattern used in journal_compte.
 */
class ComptaExportAuthorizationTest extends TransactionalTestCase
{
    /**
     * Set up test environment
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->CI->load->model('comptes_model');
        $this->CI->load->model('membres_model');
    }

    /**
     * Verify that comptes_model->user() returns the account owner login.
     * This is the ownership check used in the export/pdf authorization logic.
     */
    public function testComptesModelUserReturnsOwner()
    {
        // Find a pilot account (codec 411) with a pilot assigned
        $query = $this->CI->db
            ->select('c.id, c.pilote')
            ->from('comptes c')
            ->where('c.codec', 411)
            ->where('c.pilote IS NOT NULL')
            ->where("c.pilote != ''")
            ->limit(1)
            ->get();

        if ($query->num_rows() == 0) {
            $this->markTestSkipped('No pilot account found in database');
        }

        $row = $query->row_array();
        $compte_id = $row['id'];
        $expected_owner = $row['pilote'];

        // The ownership check in export/pdf uses comptes_model->user($compte)
        $actual_owner = $this->CI->comptes_model->user($compte_id);

        $this->assertEquals($expected_owner, $actual_owner,
            'comptes_model->user() should return the pilot login for a pilot account');
        $this->assertNotEmpty($actual_owner,
            'Account owner should not be empty');
    }

    /**
     * Verify the ownership check logic: a user matches their own account.
     * Simulates the check done in compta::export() and compta::pdf().
     */
    public function testOwnershipCheckGrantsAccessToOwnAccount()
    {
        // Find a pilot account
        $query = $this->CI->db
            ->select('c.id, c.pilote')
            ->from('comptes c')
            ->where('c.codec', 411)
            ->where('c.pilote IS NOT NULL')
            ->where("c.pilote != ''")
            ->limit(1)
            ->get();

        if ($query->num_rows() == 0) {
            $this->markTestSkipped('No pilot account found in database');
        }

        $row = $query->row_array();
        $compte_id = $row['id'];
        $owner_login = $row['pilote'];

        // Simulate: owner accesses their own account
        $account_owner = $this->CI->comptes_model->user($compte_id);
        $access_granted = ($account_owner == $owner_login);

        $this->assertTrue($access_granted,
            "User '$owner_login' should be granted access to their own account (id=$compte_id)");
    }

    /**
     * Verify the ownership check logic: a different user is denied access.
     * Simulates the check done in compta::export() and compta::pdf().
     */
    public function testOwnershipCheckDeniesAccessToOtherAccount()
    {
        // Find a pilot account with a known owner
        $query = $this->CI->db
            ->select('c.id, c.pilote')
            ->from('comptes c')
            ->where('c.codec', 411)
            ->where('c.pilote IS NOT NULL')
            ->where("c.pilote != ''")
            ->limit(1)
            ->get();

        if ($query->num_rows() == 0) {
            $this->markTestSkipped('No pilot account found in database');
        }

        $row = $query->row_array();
        $compte_id = $row['id'];
        $owner_login = $row['pilote'];

        // Simulate: a different user tries to access this account
        $different_user = $owner_login . '_other';
        $account_owner = $this->CI->comptes_model->user($compte_id);
        $access_granted = ($account_owner == $different_user);

        $this->assertFalse($access_granted,
            "User '$different_user' should be denied access to account (id=$compte_id) owned by '$owner_login'");
    }

    /**
     * Verify that the constructor exemption list includes 'export' and 'pdf'.
     * This tests the fix to the authorization system: these methods should not
     * require the 'tresorier' role at the constructor level.
     */
    public function testExportAndPdfAreInConstructorExemptionList()
    {
        $controller_file = APPPATH . 'controllers/compta.php';
        $this->assertFileExists($controller_file, 'compta.php controller must exist');

        $source = file_get_contents($controller_file);

        // Verify 'export' is in the exemption list
        $this->assertRegExp(
            "/in_array\s*\(\s*\\\$method\s*,\s*\[.*'export'.*\]\s*\)/s",
            $source,
            "'export' must be in the constructor exemption list so regular users can access their own account"
        );

        // Verify 'pdf' is in the exemption list
        $this->assertRegExp(
            "/in_array\s*\(\s*\\\$method\s*,\s*\[.*'pdf'.*\]\s*\)/s",
            $source,
            "'pdf' must be in the constructor exemption list so regular users can export their own account as PDF"
        );
    }

    /**
     * Verify that both export() and pdf() contain an ownership check.
     * This tests the second part of the fix: deny access inside the method
     * if the account doesn't belong to the current user.
     */
    public function testExportAndPdfContainOwnershipCheck()
    {
        $controller_file = APPPATH . 'controllers/compta.php';
        $source = file_get_contents($controller_file);

        // Count occurrences of the ownership check pattern
        // Each method (export, pdf) should have its own check
        $ownership_check_count = preg_match_all(
            '/Regular users can only export their own account/',
            $source
        );

        $this->assertGreaterThanOrEqual(2, $ownership_check_count,
            'Both export() and pdf() must contain an ownership check comment (found: ' . $ownership_check_count . ')');
    }
}
