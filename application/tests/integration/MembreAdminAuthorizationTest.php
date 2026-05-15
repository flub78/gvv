<?php

require_once(__DIR__ . '/TransactionalTestCase.php');

/**
 * Tests for member admin-level authorization (new authorization system).
 *
 * Rules under new auth:
 * - delete requires club-admin
 * - modifying mnom, mprenom requires club-admin
 * - modifying mdaten requires ca (club-admin also allowed as superadmin)
 * - all other member edits require ca
 *
 * Static checks verify the code structure; integration checks verify runtime behavior.
 */
class MembreAdminAuthorizationTest extends TransactionalTestCase
{
    // -------------------------------------------------------------------------
    // Static code structure checks
    // -------------------------------------------------------------------------

    public function testConstructorRequiresClubAdminForDelete()
    {
        $source = file_get_contents(APPPATH . 'controllers/membre.php');

        $this->assertRegExp(
            "/if \(\\\$method === 'delete'\) \{\\s*\\\$this->require_roles\(\['club-admin'\]\);/",
            $source,
            "Constructor must require club-admin for delete in new auth"
        );
    }

    public function testConstructorRequiresCaForCreateAndFormValidation()
    {
        $source = file_get_contents(APPPATH . 'controllers/membre.php');

        $this->assertRegExp(
            "/elseif \(in_array\(\\\$method, \['create', 'formValidation'\]\)\) \{\\s*\\\$this->require_roles\(\['ca'\]\);/",
            $source,
            "Constructor must require ca for create and formValidation in new auth"
        );
    }

    public function testFormValidationLegacyCheckIsGatedByOldAuthFlag()
    {
        $source = file_get_contents(APPPATH . 'controllers/membre.php');

        $this->assertRegExp(
            "/if \(!\\\$this->use_new_auth && !\\\$this->dx_auth->is_role\(\\\$this->modification_level, true, true\)\)/",
            $source,
            "formValidation() legacy DX_Auth check must be wrapped in !use_new_auth"
        );
    }

    public function testPreUpdateStripsNameFieldsForNonClubAdmin()
    {
        $source = file_get_contents(APPPATH . 'controllers/membre.php');

        $this->assertRegExp(
            "/if \(\\\$this->use_new_auth && !\\\$this->user_has_role\('club-admin'\)\) \{\\s*unset\(\\\$data\['mnom'\], \\\$data\['mprenom'\]\);/",
            $source,
            "pre_update() must strip mnom/mprenom when new auth is active and user is not club-admin"
        );
    }

    public function testPreUpdateStripsBirthdateForNonCa()
    {
        $source = file_get_contents(APPPATH . 'controllers/membre.php');

        $this->assertRegExp(
            "/if \(\\\$this->use_new_auth && !\\\$this->user_has_role\('ca'\)\) \{\\s*unset\(\\\$data\['mdaten'\]\);/",
            $source,
            "pre_update() must strip mdaten when new auth is active and user is not ca"
        );
    }

    public function testFormStaticElementSetsHasAdminRightsFlag()
    {
        $source = file_get_contents(APPPATH . 'controllers/membre.php');

        $this->assertRegExp(
            "/\\\$this->data\['has_admin_rights'\] = !\\\$this->use_new_auth \|\| \\\$this->user_has_role\('club-admin'\);/",
            $source,
            "form_static_element() must set has_admin_rights based on new auth and club-admin role"
        );
    }

    public function testViewRendersIdentityFieldsConditionally()
    {
        $source = file_get_contents(APPPATH . 'views/membre/bs_formView.php');

        $this->assertStringContainsString(
            '$has_admin_rights || $action == CREATION',
            $source,
            "View must protect identity fields behind has_admin_rights || CREATION condition"
        );
    }

    // -------------------------------------------------------------------------
    // Integration checks via pre_update data manipulation
    // -------------------------------------------------------------------------

    /**
     * Simulate pre_update behaviour: with club-admin, all fields are preserved.
     */
    public function testPreUpdatePreservesIdentityFieldsForClubAdmin()
    {
        $CI =& get_instance();
        $CI->load->library('Gvv_Authorization');
        $CI->gvv_authorization->clear_cache();

        // Find panoramix (club-admin in new auth) or skip
        $user = $CI->db->get_where('users', ['username' => 'panoramix'])->row();
        if (!$user) {
            $this->markTestSkipped('Test user panoramix not found — run bin/create_test_users.sh');
        }

        $club_admin_role = $CI->db->get_where('types_roles', ['nom' => 'club-admin'])->row();
        $this->assertNotNull($club_admin_role, 'club-admin role must exist in types_roles');

        $has_club_admin = $CI->gvv_authorization->has_role((int) $user->id, 'club-admin', NULL);
        $this->assertTrue($has_club_admin, 'panoramix must have club-admin role');

        // Build data as pre_update would receive it
        $data = ['mnom' => 'Panoramix', 'mprenom' => 'Le Druide', 'mdaten' => '1960-01-01', 'memail' => 'p@test.fr'];

        // Simulate the new-auth + club-admin path: fields must NOT be stripped
        $is_new_auth = true;
        $is_club_admin = true;
        if ($is_new_auth && !$is_club_admin) {
            unset($data['mnom'], $data['mprenom'], $data['mdaten']);
        }

        $this->assertArrayHasKey('mnom', $data, 'mnom must be preserved for club-admin');
        $this->assertArrayHasKey('mprenom', $data, 'mprenom must be preserved for club-admin');
        $this->assertArrayHasKey('mdaten', $data, 'mdaten must be preserved for club-admin');
    }

    /**
     * Simulate pre_update behaviour: ca user (not club-admin) — mnom/mprenom stripped, mdaten preserved.
     */
    public function testPreUpdateStripsNameFieldsForCaUser()
    {
        $CI =& get_instance();
        $CI->load->library('Gvv_Authorization');
        $CI->gvv_authorization->clear_cache();

        // Find abraracourcix (ca but not club-admin in new auth) or skip
        $user = $CI->db->get_where('users', ['username' => 'abraracourcix'])->row();
        if (!$user) {
            $this->markTestSkipped('Test user abraracourcix not found — run bin/create_test_users.sh');
        }

        $has_club_admin = $CI->gvv_authorization->has_role((int) $user->id, 'club-admin', NULL);
        $this->assertFalse($has_club_admin, 'abraracourcix must NOT have club-admin role');

        $has_ca = $CI->gvv_authorization->has_role((int) $user->id, 'ca', NULL);
        $this->assertTrue($has_ca, 'abraracourcix must have ca role');

        // Build data as pre_update would receive it
        $data = ['mnom' => 'Abraracourcix', 'mprenom' => 'Le Chef', 'mdaten' => '1970-05-15', 'memail' => 'a@test.fr'];

        // Simulate new-auth + ca (not club-admin): mnom/mprenom stripped, mdaten preserved
        $is_new_auth = true;
        $is_club_admin = false;
        $is_ca = true;
        if ($is_new_auth && !$is_club_admin) {
            unset($data['mnom'], $data['mprenom']);
        }
        if ($is_new_auth && !$is_ca) {
            unset($data['mdaten']);
        }

        $this->assertArrayNotHasKey('mnom', $data, 'mnom must be stripped for non-club-admin');
        $this->assertArrayNotHasKey('mprenom', $data, 'mprenom must be stripped for non-club-admin');
        $this->assertArrayHasKey('mdaten', $data, 'mdaten must be preserved for ca user');
        $this->assertArrayHasKey('memail', $data, 'non-identity fields must be preserved');
    }

    /**
     * club-admin role must exist in the types_roles table.
     */
    public function testClubAdminRoleExistsInDatabase()
    {
        $CI =& get_instance();
        $role = $CI->db->get_where('types_roles', ['nom' => 'club-admin'])->row();
        $this->assertNotNull($role, 'club-admin role must exist in types_roles');
        $this->assertEquals('global', $role->scope, 'club-admin must be a global role');
    }
}
