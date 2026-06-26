<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL integration tests for membres_model::selector() override.
 *
 * Verifies that selector(array('actif'=>1)) intercepts the 'actif' key
 * and translates it into a user_roles_per_section filter (role 'user')
 * instead of filtering on the deprecated membres.actif column.
 */
class MembresSelectorActifTest extends TestCase
{
    /** @var CI_Controller */
    private $CI;

    /** @var Membres_model */
    private $model;

    protected function setUp(): void
    {
        $this->CI =& get_instance();
        $this->CI->load->model('membres_model');
        $this->model = $this->CI->membres_model;

        if (!$this->CI->db->conn_id) {
            $this->markTestSkipped('Database connection not available');
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Returns mlogins of members who have role 'user' in at least one section. */
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

    /** Returns mlogins of members who have NO role 'user' in any section (but have a user account). */
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
            LIMIT 10
        ")->result_array();
        return array_column($rows, 'mlogin');
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    public function testSelectorWithActifReturnsArray()
    {
        $result = $this->model->selector(array('actif' => 1));
        $this->assertIsArray($result);
    }

    public function testSelectorWithActifReturnsAtLeastOneMember()
    {
        $result = $this->model->selector(array('actif' => 1));
        $this->assertGreaterThan(1, count($result), 'Selector with actif=1 should return at least one member');
    }

    public function testSelectorWithActifReturnsOnlyMembersWithUserRole()
    {
        $result = $this->model->selector(array('actif' => 1));
        $activeLogins = $this->getActiveLogins();

        if (empty($activeLogins)) {
            $this->markTestSkipped('No members with user role in database');
        }

        foreach ($result as $mlogin => $label) {
            if ($mlogin === '') continue;
            $this->assertContains(
                $mlogin,
                $activeLogins,
                "Member '$mlogin' appears in selector but has no 'user' role"
            );
        }
    }

    public function testSelectorWithActifExcludesMembersWithoutUserRole()
    {
        $inactiveLogins = $this->getInactiveLogins();

        if (empty($inactiveLogins)) {
            $this->markTestSkipped('No inactive members (without user role) in database');
        }

        $result = $this->model->selector(array('actif' => 1));

        foreach ($inactiveLogins as $login) {
            $this->assertArrayNotHasKey(
                $login,
                $result,
                "Inactive member '$login' (no user role) should not appear in selector"
            );
        }
    }

    public function testSelectorWithoutActifReturnsMoreMembersThanWithActif()
    {
        $inactiveLogins = $this->getInactiveLogins();

        if (empty($inactiveLogins)) {
            $this->markTestSkipped('No inactive members in database — cannot compare counts');
        }

        $withActif    = $this->model->selector(array('actif' => 1));
        $withoutActif = $this->model->selector(array());

        $this->assertGreaterThan(
            count($withActif),
            count($withoutActif),
            'selector() without actif filter should return more members than with actif=1'
        );
    }

    public function testSelectorActifDoesNotFilterOnMembresActifColumn()
    {
        // A member with membres.actif=0 BUT with role 'user' should appear in the selector.
        $row = $this->CI->db
            ->select('m.mlogin')
            ->from('membres m')
            ->join('users u', 'u.username = m.mlogin', 'inner')
            ->join('user_roles_per_section urps', 'urps.user_id = u.id', 'inner')
            ->join('types_roles tr', 'tr.id = urps.types_roles_id', 'inner')
            ->where('tr.nom', 'user')
            ->where('m.actif', 0)
            ->limit(1)
            ->get()->row_array();

        if (!$row) {
            $this->markTestSkipped('No member with actif=0 AND user role found in database');
        }

        $result = $this->model->selector(array('actif' => 1));
        $this->assertArrayHasKey(
            $row['mlogin'],
            $result,
            "Member with membres.actif=0 but role 'user' should appear in selector"
        );
    }
}
