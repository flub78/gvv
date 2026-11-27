<?php

use PHPUnit\Framework\TestCase;

/**
 * HTTP test for Forms_admin::generate() when no section is active ("Toutes").
 *
 * In that case the pilot/instructor selectors must list members and
 * instructors of every section (get_selector_all()/inst_selector_all()),
 * so an admin can generate the same form — e.g. a training attestation —
 * for members belonging to different sections. Previously generate() called
 * get_selector(0)/inst_selector(0), which fell back to the non-existent
 * active section and produced empty <select> lists.
 *
 * Same HTTP harness as FormsAdminGenerateMachineTest (Forms_admin is only
 * reachable through a real HTTP round-trip against the dev server).
 * Relies on the Gaulois test users (bin/create_test_users.sh).
 */
class FormsAdminGenerateAllSectionsTest extends TestCase
{
    /** @var CI_Controller */
    private $db;
    private $form_id;
    private $slug;

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;

        $found = $this->db->select('mlogin')
            ->where_in('mlogin', array('asterix', 'abraracourcix'))
            ->get('membres')->result_array();
        if (count($found) < 2) {
            $this->markTestSkipped('Gaulois test users missing — run bin/create_test_users.sh');
        }

        $ts = time() . '_' . rand(1000, 9999);
        $this->slug = 'gen-allsections-test-' . $ts;

        $this->db->insert('forms', array(
            'code'            => 'gen_allsections_test_' . $ts,
            'title'           => 'Generate all-sections test',
            'public_slug'     => $this->slug,
            'status'          => 'published',
            'required_params' => 'pilot+instructor',
            'club'            => null,
        ));
        $this->form_id = $this->db->insert_id();

        $this->db->insert('form_pages', array(
            'form_id'      => $this->form_id,
            'page_number'  => 1,
            'title'        => 'Page 1',
            'content_html' => '<input type="text" name="x">',
        ));
    }

    protected function tearDown(): void
    {
        $this->db->where('form_id', $this->form_id)->delete('form_submissions');
        $this->db->where('form_id', $this->form_id)->delete('form_pages');
        $this->db->where('id', $this->form_id)->delete('forms');
    }

    public function testGeneratePageListsMembersOfEverySectionWhenNoSectionActive()
    {
        $cookie = GvvTestHttp::login();
        $this->assertNotNull($cookie);

        // Switch to "Toutes" (section key 0 matches no real section).
        GvvTestHttp::post(GvvTestHttp::base_url() . 'user_roles_per_section/set_section',
            array('section' => '0'), $cookie);

        $page = GvvTestHttp::get(GvvTestHttp::base_url() . 'forms_admin/generate/' . $this->slug, $cookie);
        $this->assertNotNull($page['body']);

        // Pilot selector: members from different sections must all be offered.
        $this->assertStringContainsString('value="asterix"', $page['body'],
            'asterix (sections 1/4) doit être proposé quand aucune section n\'est active');
        $this->assertStringContainsString('value="abraracourcix"', $page['body'],
            'abraracourcix (sections 1..4) doit être proposé');

        // Instructor selector present with the cross-section instructor.
        $this->assertStringContainsString('name="instructor_login"', $page['body']);
    }
}
