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

    private function base_url()
    {
        return 'http://gvv.net/index.php/';
    }

    private function extract_session_cookie(array $headers)
    {
        $cookie = null;
        foreach ($headers as $h) {
            if (stripos($h, 'Set-Cookie:') === 0 && stripos($h, 'ci_session=') !== false) {
                $pair = trim(substr($h, strlen('Set-Cookie:')));
                $cookie = explode(';', $pair)[0];
            }
        }
        return $cookie;
    }

    private function login_as_admin()
    {
        $body = http_build_query(array('username' => 'testadmin', 'password' => 'password'));
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $body, 'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 20,
            ),
        ));
        @file_get_contents($this->base_url() . 'auth/login', false, $context);
        $headers = isset($http_response_header) ? $http_response_header : array();
        return $this->extract_session_cookie($headers);
    }

    private function http_post($url, array $post_fields, $cookie = null)
    {
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\nCookie: " . ($cookie ?: '') . "\r\n",
                'content' => http_build_query($post_fields), 'ignore_errors' => true,
                'follow_location' => 0, 'timeout' => 20,
            ),
        ));
        $body = @file_get_contents($url, false, $context);
        return array('body' => $body, 'headers' => isset($http_response_header) ? $http_response_header : array());
    }

    private function http_get($url, $cookie = null)
    {
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'GET', 'header' => "Cookie: " . ($cookie ?: '') . "\r\n",
                'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 20,
            ),
        ));
        $body = @file_get_contents($url, false, $context);
        return array('body' => $body, 'headers' => isset($http_response_header) ? $http_response_header : array());
    }

    public function testGeneratePageListsMembersOfEverySectionWhenNoSectionActive()
    {
        $cookie = $this->login_as_admin();
        $this->assertNotNull($cookie);

        // Switch to "Toutes" (section key 0 matches no real section).
        $this->http_post($this->base_url() . 'user_roles_per_section/set_section',
            array('section' => '0'), $cookie);

        $page = $this->http_get($this->base_url() . 'forms_admin/generate/' . $this->slug, $cookie);
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
