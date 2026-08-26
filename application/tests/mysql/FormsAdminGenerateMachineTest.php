<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL/HTTP tests for the "machine" dimension of forms.required_params
 * (migration 174) and the corresponding wiring in Forms_admin::generate()/
 * generate_submit() + the machine.numero_identification GVV source in
 * Forms_public. Before this, a form could only declare pilot/instructor as
 * required context: machine_immat was never collected nor forwarded to the
 * public form URL, so machine.numero_identification never resolved even
 * when a field declared it as data-gvv-source.
 *
 * Same HTTP harness as FormsSubmissionEditTest / FormsMultiPageSubmitTest —
 * Forms_admin/Forms_public are only testable through a real HTTP round-trip
 * against the dev server (session-carried login state, no curl available in
 * this environment).
 */
class FormsAdminGenerateMachineTest extends TestCase
{
    /** @var RealDatabase */
    private $db;
    private $form_id;
    private $slug;
    private $macimmat;
    private $numero_identification;

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;

        $ts = time() . '_' . rand(1000, 9999);
        $this->macimmat = 'F-T' . rand(10000, 99999);
        $this->numero_identification = 'ID' . rand(10000, 99999);
        $this->slug = 'gen-machine-test-' . $ts;

        $this->db->insert('machinesa', array(
            'macimmat' => $this->macimmat,
            'macconstruc' => 'GvvTest', 'macmodele' => 'GvvTestModel',
            'numero_identification' => $this->numero_identification,
            'maprix' => '0', 'actif' => 1,
        ));

        $this->db->insert('forms', array(
            'code'            => 'gen_machine_test_' . $ts,
            'title'           => 'Generate machine test',
            'public_slug'     => $this->slug,
            'status'          => 'published',
            'required_params' => 'pilot+instructor+machine',
            'club'            => null,
        ));
        $this->form_id = $this->db->insert_id();

        $this->db->insert('form_pages', array(
            'form_id'      => $this->form_id,
            'page_number'  => 1,
            'title'        => 'Page 1',
            'content_html' => '<input type="text" id="immat_ulm" name="immat_ulm" data-gvv-source="machine.numero_identification">',
        ));
    }

    protected function tearDown(): void
    {
        $this->db->where('form_id', $this->form_id)->delete('form_submissions');
        $this->db->where('form_id', $this->form_id)->delete('form_pages');
        $this->db->where('id', $this->form_id)->delete('forms');
        $this->db->where('macimmat', $this->macimmat)->delete('machinesa');
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

    private function http_post($url, array $post_fields, $cookie = null, $follow = false)
    {
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\nCookie: " . ($cookie ?: '') . "\r\n",
                'content' => http_build_query($post_fields), 'ignore_errors' => true,
                'follow_location' => $follow ? 1 : 0, 'timeout' => 20,
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

    private function location_header(array $headers)
    {
        foreach ($headers as $h) {
            if (stripos($h, 'Location:') === 0) {
                return trim(substr($h, strlen('Location:')));
            }
        }
        return null;
    }

    public function testGenerateSubmitMissingMachineRedirectsBackWithError()
    {
        $cookie = $this->login_as_admin();
        $this->assertNotNull($cookie);

        $result = $this->http_post(
            $this->base_url() . 'forms_admin/generate_submit/' . $this->slug,
            array('pilot_login' => 'abraracourcix', 'instructor_login' => 'fpeignot'),
            $cookie
        );
        $location = $this->location_header($result['headers']);
        $this->assertNotNull($location);
        $this->assertStringContainsString('forms_admin/generate/' . $this->slug, $location);

        $page = $this->http_get($this->base_url() . 'forms_admin/generate/' . $this->slug, $cookie);
        $this->assertStringContainsString('Veuillez sélectionner une machine', $page['body']);
    }

    public function testGenerateSubmitWithMachineForwardsMachineImmatInUrl()
    {
        $cookie = $this->login_as_admin();
        $this->assertNotNull($cookie);

        $result = $this->http_post(
            $this->base_url() . 'forms_admin/generate_submit/' . $this->slug,
            array('pilot_login' => 'abraracourcix', 'instructor_login' => 'fpeignot', 'machine_immat' => $this->macimmat),
            $cookie
        );
        $location = $this->location_header($result['headers']);
        $this->assertNotNull($location);
        $this->assertStringContainsString('machine_immat=' . rawurlencode($this->macimmat), $location);
    }

    public function testPublicFormPrefillsMachineNumeroIdentification()
    {
        $cookie = $this->login_as_admin();
        $this->assertNotNull($cookie);

        $url = $this->base_url() . 'forms/' . $this->slug
            . '?pilot_login=abraracourcix&instructor_login=fpeignot&machine_immat=' . rawurlencode($this->macimmat);
        $result = $this->http_get($url, $cookie);

        $this->assertNotNull($result['body']);
        $this->assertStringContainsString('value="' . $this->numero_identification . '"', $result['body'], 'Le champ doit être pré-rempli avec machinesa.numero_identification.');
    }
}
