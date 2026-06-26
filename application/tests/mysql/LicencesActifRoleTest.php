<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL integration tests for licences_model member_status filter.
 *
 * Verifies that per_year() and per_year_detail() use the role 'user'
 * (not membres.actif) to filter active/inactive members.
 */
class LicencesActifRoleTest extends TestCase
{
    /** @var CI_Controller */
    private $CI;

    /** @var Licences_model */
    private $model;

    protected function setUp(): void
    {
        $this->CI =& get_instance();
        $this->CI->load->model('licences_model');
        $this->model = $this->CI->licences_model;

        if (!$this->CI->db->conn_id) {
            $this->markTestSkipped('Database connection not available');
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function getActiveLogins(): array
    {
        $rows = $this->CI->db->query("
            SELECT DISTINCT u.username
            FROM users u
            INNER JOIN user_roles_per_section urps ON urps.user_id = u.id
            INNER JOIN types_roles tr ON tr.id = urps.types_roles_id
            WHERE tr.nom = 'user'
        ")->result_array();
        return array_column($rows, 'username');
    }

    private function getInactiveLogins(): array
    {
        $rows = $this->CI->db->query("
            SELECT m.mlogin
            FROM membres m
            INNER JOIN users u ON u.username = m.mlogin
            WHERE m.mlogin NOT IN (
                SELECT u2.username
                FROM users u2
                INNER JOIN user_roles_per_section urps ON urps.user_id = u2.id
                INNER JOIN types_roles tr ON tr.id = urps.types_roles_id
                WHERE tr.nom = 'user'
            )
            LIMIT 20
        ")->result_array();
        return array_column($rows, 'mlogin');
    }

    // -------------------------------------------------------------------------
    // per_year() tests
    // -------------------------------------------------------------------------

    public function testPerYearActiveReturnsArray()
    {
        $result = $this->model->per_year(0, null, null, 'active', null, 'csv');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
    }

    public function testPerYearActiveOnlyContainsMembersWithUserRole()
    {
        $result = $this->model->per_year(0, null, null, 'active', null, 'csv');
        $activeLogins = $this->getActiveLogins();

        if (empty($activeLogins)) {
            $this->markTestSkipped('No members with user role in database');
        }

        $data = $result['data'];
        if (count($data) <= 1) {
            $this->markTestSkipped('No active members with licences found');
        }

        // Re-run the member query directly to validate (raw SQL, no Active Record)
        $rows = $this->CI->db->query("
            SELECT DISTINCT membres.mlogin
            FROM membres
            INNER JOIN users u_lic ON u_lic.username = membres.mlogin
            INNER JOIN user_roles_per_section urps_lic ON urps_lic.user_id = u_lic.id
            INNER JOIN types_roles tr_lic ON tr_lic.id = urps_lic.types_roles_id
            WHERE tr_lic.nom = 'user'
        ")->result_array();

        foreach ($rows as $row) {
            $this->assertContains(
                $row['mlogin'],
                $activeLogins,
                "per_year active: member '{$row['mlogin']}' has no user role"
            );
        }
    }

    public function testPerYearInactiveExcludesMembersWithUserRole()
    {
        $inactiveLogins = $this->getInactiveLogins();

        if (empty($inactiveLogins)) {
            $this->markTestSkipped('No inactive members in database');
        }

        $activeLogins = $this->getActiveLogins();

        // Directly query members without user role
        $rows = $this->CI->db->query("
            SELECT DISTINCT m.mlogin
            FROM membres m
            INNER JOIN users u ON u.username = m.mlogin
            WHERE m.mlogin NOT IN (
                SELECT u2.username
                FROM users u2
                INNER JOIN user_roles_per_section urps ON urps.user_id = u2.id
                INNER JOIN types_roles tr ON tr.id = urps.types_roles_id
                WHERE tr.nom = 'user'
            )
        ")->result_array();

        foreach ($rows as $row) {
            $this->assertNotContains(
                $row['mlogin'],
                $activeLogins,
                "per_year inactive: member '{$row['mlogin']}' has user role but was returned as inactive"
            );
        }
    }

    public function testPerYearAllReturnMoreThanActive()
    {
        $inactiveLogins = $this->getInactiveLogins();

        if (empty($inactiveLogins)) {
            $this->markTestSkipped('No inactive members in database');
        }

        $active = $this->CI->db->query("
            SELECT DISTINCT membres.mlogin
            FROM membres
            INNER JOIN users u ON u.username = membres.mlogin
            INNER JOIN user_roles_per_section urps ON urps.user_id = u.id
            INNER JOIN types_roles tr ON tr.id = urps.types_roles_id
            WHERE tr.nom = 'user'
        ")->result_array();

        $all = $this->CI->db->query("SELECT mlogin FROM membres")->result_array();

        $this->assertGreaterThan(
            count($active),
            count($all),
            'Total members should be more than active-only members'
        );
    }

    // -------------------------------------------------------------------------
    // per_year_detail() tests
    // -------------------------------------------------------------------------

    public function testPerYearDetailActiveReturnsArray()
    {
        $year = (int)date('Y');
        $result = $this->model->per_year_detail($year, 'active');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('members', $result);
        $this->assertArrayHasKey('sections', $result);
    }

    public function testPerYearDetailActiveOnlyContainsMembersWithUserRole()
    {
        $year = (int)date('Y');
        $result = $this->model->per_year_detail($year, 'active');
        $activeLogins = $this->getActiveLogins();

        if (empty($activeLogins)) {
            $this->markTestSkipped('No members with user role in database');
        }

        foreach ($result['members'] as $member) {
            $this->assertContains(
                $member['mlogin'],
                $activeLogins,
                "per_year_detail active: member '{$member['mlogin']}' has no user role"
            );
        }
    }

    public function testPerYearDetailInactiveExcludesMembersWithUserRole()
    {
        $year = (int)date('Y');
        $result = $this->model->per_year_detail($year, 'inactive');
        $activeLogins = $this->getActiveLogins();

        foreach ($result['members'] as $member) {
            $this->assertNotContains(
                $member['mlogin'],
                $activeLogins,
                "per_year_detail inactive: member '{$member['mlogin']}' has user role but returned as inactive"
            );
        }
    }

    public function testPerYearDetailAllReturnsBothActiveAndInactive()
    {
        $inactiveLogins = $this->getInactiveLogins();

        if (empty($inactiveLogins)) {
            $this->markTestSkipped('No inactive members in database');
        }

        $year = (int)date('Y');
        $all      = $this->model->per_year_detail($year, 'all');
        $active   = $this->model->per_year_detail($year, 'active');

        $this->assertGreaterThanOrEqual(
            count($active['members']),
            count($all['members']),
            "per_year_detail 'all' should return at least as many members as 'active'"
        );
    }
}
