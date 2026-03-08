<?php

use PHPUnit\Framework\TestCase;

/**
 * Coherence test: verifies that Gaulois test users have the expected
 * user_roles_per_section entries, regardless of which script created them
 * (bin/create_test_users.sh or admin._create_test_gaulois_users()).
 *
 * This test defines the canonical role expectations. If it fails after
 * running either script, the script is out of sync with the specification.
 *
 * Run after creating test users:
 *   bash bin/create_test_users.sh
 *   phpunit application/tests/integration/TestUsersCoherenceTest.php
 *
 * @see bin/create_test_users.sh
 * @see application/controllers/admin.php -> _create_test_gaulois_users()
 * @see doc/plans/2025_authorization_refactoring_plan.md Phase 13.0
 */
class TestUsersCoherenceTest extends TestCase
{
    /** @var CI_Controller */
    private $CI;

    // Section IDs (standard test database layout)
    const PLANEUR_SECTION  = 1;
    const ULM_SECTION      = 2;
    const AVION_SECTION    = 3;
    const GENERAL_SECTION  = 4;

    // types_roles IDs
    const ROLE_USER            = 1;
    const ROLE_AUTO_PLANCHISTE = 2;
    const ROLE_PLANCHISTE      = 5;
    const ROLE_CA              = 6;
    const ROLE_TRESORIER       = 8;
    const ROLE_CLUB_ADMIN      = 10;
    const ROLE_INSTRUCTEUR     = 11;
    const ROLE_MECANO          = 12;

    /**
     * Canonical expected roles for each Gaulois test user.
     * Each entry: [section_id, types_roles_id]
     * This is the single source of truth — both creation scripts must match this.
     */
    private static function expected_roles(): array
    {
        return [
            'asterix' => [
                [self::PLANEUR_SECTION,  self::ROLE_USER],
                [self::GENERAL_SECTION,  self::ROLE_USER],
            ],
            'obelix' => [
                [self::PLANEUR_SECTION,  self::ROLE_USER],
                [self::PLANEUR_SECTION,  self::ROLE_PLANCHISTE],
                [self::PLANEUR_SECTION,  self::ROLE_MECANO],
                [self::ULM_SECTION,      self::ROLE_USER],
                [self::ULM_SECTION,      self::ROLE_AUTO_PLANCHISTE],
                [self::GENERAL_SECTION,  self::ROLE_USER],
            ],
            'abraracourcix' => [
                // CA is restricted to Avion section only
                [self::PLANEUR_SECTION,  self::ROLE_USER],
                [self::AVION_SECTION,    self::ROLE_USER],
                [self::AVION_SECTION,    self::ROLE_CA],
                [self::AVION_SECTION,    self::ROLE_INSTRUCTEUR],
                [self::ULM_SECTION,      self::ROLE_USER],
                [self::GENERAL_SECTION,  self::ROLE_USER],
            ],
            'goudurix' => [
                [self::AVION_SECTION,    self::ROLE_USER],
                [self::AVION_SECTION,    self::ROLE_TRESORIER],
                [self::AVION_SECTION,    self::ROLE_AUTO_PLANCHISTE],
                [self::GENERAL_SECTION,  self::ROLE_USER],
                [self::GENERAL_SECTION,  self::ROLE_TRESORIER],
            ],
        ];
    }

    public function setUp(): void
    {
        $this->CI =& get_instance();

        if (!$this->CI->db->conn_id) {
            $this->markTestSkipped('Database connection not available');
        }
    }

    // =========================================================================
    // Helper
    // =========================================================================

    /**
     * Returns the set of [section_id, types_roles_id] pairs for a username.
     */
    private function get_actual_roles(string $username): array
    {
        $user = $this->CI->db->where('username', $username)->get('users')->row();
        if (!$user) {
            return [];
        }

        $rows = $this->CI->db
            ->select('section_id, types_roles_id')
            ->where('user_id', $user->id)
            ->get('user_roles_per_section')
            ->result_array();

        $pairs = [];
        foreach ($rows as $row) {
            $pairs[] = [(int)$row['section_id'], (int)$row['types_roles_id']];
        }
        return $pairs;
    }

    /**
     * Sorts a roles array for deterministic comparison.
     */
    private function sort_roles(array $roles): array
    {
        usort($roles, function ($a, $b) {
            return $a[0] !== $b[0] ? $a[0] - $b[0] : $a[1] - $b[1];
        });
        return $roles;
    }

    // =========================================================================
    // Tests: existence
    // =========================================================================

    /**
     * @dataProvider gauloisUsernameProvider
     */
    public function testUserExists(string $username): void
    {
        $user = $this->CI->db->where('username', $username)->get('users')->row();
        $this->assertNotNull($user, "Test user '$username' does not exist in users table");
    }

    /**
     * @dataProvider gauloisUsernameProvider
     */
    public function testUserInNewAuthorization(string $username): void
    {
        $row = $this->CI->db
            ->where('username', $username)
            ->get('use_new_authorization')
            ->row();
        $this->assertNotNull(
            $row,
            "Test user '$username' is missing from use_new_authorization"
        );
    }

    // =========================================================================
    // Tests: role coherence
    // =========================================================================

    /**
     * @dataProvider gauloisUsernameProvider
     */
    public function testUserHasExpectedRoles(string $username): void
    {
        $expected = self::expected_roles();
        if (!isset($expected[$username])) {
            // panoramix roles are dynamic (all sections) — verified by testPanoramixIsClubAdminInAllSections()
            $this->markTestSkipped("'$username' uses dynamic role verification (see testPanoramixIsClubAdminInAllSections)");
        }

        $actual   = $this->sort_roles($this->get_actual_roles($username));
        $expected = $this->sort_roles($expected[$username]);

        $this->assertEquals(
            $expected,
            $actual,
            "user_roles_per_section mismatch for '$username'.\n" .
            "This means bin/create_test_users.sh and admin._create_test_gaulois_users() are out of sync.\n" .
            "Expected (canonical): " . json_encode($expected) . "\n" .
            "Actual (in DB):       " . json_encode($actual)
        );
    }

    /**
     * Panoramix: club-admin in ALL sections, no 411 accounts
     */
    public function testPanoramixIsClubAdminInAllSections(): void
    {
        $user = $this->CI->db->where('username', 'panoramix')->get('users')->row();
        if (!$user) {
            $this->markTestSkipped('panoramix not found in database');
        }

        $sections = $this->CI->db->select('id')->get('sections')->result_array();
        $this->assertNotEmpty($sections, 'No sections found in database');

        foreach ($sections as $section) {
            $section_id = (int)$section['id'];

            $user_role = $this->CI->db
                ->where('user_id', $user->id)
                ->where('section_id', $section_id)
                ->where('types_roles_id', self::ROLE_USER)
                ->get('user_roles_per_section')
                ->row();
            $this->assertNotNull(
                $user_role,
                "panoramix missing ROLE_USER in section $section_id"
            );

            $admin_role = $this->CI->db
                ->where('user_id', $user->id)
                ->where('section_id', $section_id)
                ->where('types_roles_id', self::ROLE_CLUB_ADMIN)
                ->get('user_roles_per_section')
                ->row();
            $this->assertNotNull(
                $admin_role,
                "panoramix missing ROLE_CLUB_ADMIN in section $section_id"
            );
        }
    }

    public function testPanoramixHasNoComptes(): void
    {
        $count = $this->CI->db
            ->where('pilote', 'panoramix')
            ->where('codec', 411)
            ->count_all_results('comptes');
        $this->assertEquals(0, $count, 'panoramix should have no 411 accounts');
    }

    /**
     * Verify abraracourcix does NOT have CA role in non-Avion sections.
     * This is the critical regression test for the divergence that existed
     * between bin/create_test_users.sh (CA all sections) and admin.php (CA Avion only).
     */
    public function testAbraracourcixCaRoleIsAvionOnly(): void
    {
        $user = $this->CI->db->where('username', 'abraracourcix')->get('users')->row();
        if (!$user) {
            $this->markTestSkipped('abraracourcix not found in database');
        }

        $non_avion_sections = [self::PLANEUR_SECTION, self::ULM_SECTION, self::GENERAL_SECTION];
        foreach ($non_avion_sections as $section_id) {
            $ca_role = $this->CI->db
                ->where('user_id', $user->id)
                ->where('section_id', $section_id)
                ->where('types_roles_id', self::ROLE_CA)
                ->get('user_roles_per_section')
                ->row();
            $this->assertNull(
                $ca_role,
                "abraracourcix must NOT have CA role in section $section_id " .
                "(CA is restricted to Avion section only)"
            );
        }

        // And confirm CA IS present in Avion section
        $ca_avion = $this->CI->db
            ->where('user_id', $user->id)
            ->where('section_id', self::AVION_SECTION)
            ->where('types_roles_id', self::ROLE_CA)
            ->get('user_roles_per_section')
            ->row();
        $this->assertNotNull(
            $ca_avion,
            'abraracourcix must have CA role in Avion section'
        );
    }

    // =========================================================================
    // Data providers
    // =========================================================================

    public function gauloisUsernameProvider(): array
    {
        return [
            'asterix'        => ['asterix'],
            'obelix'         => ['obelix'],
            'abraracourcix'  => ['abraracourcix'],
            'goudurix'       => ['goudurix'],
            'panoramix'      => ['panoramix'],
        ];
    }
}
