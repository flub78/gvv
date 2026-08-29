<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL/HTTP test for the instructor-signature prefill guard in
 * Forms_public::_resolve_gvv_source() (case 'instructor').
 *
 * Use-case: an instructor pre-fills their own reference signature
 * (membres.signature_path) when generating an attestation/fiche de test for
 * their own students. The guard ensures `instructor.signature` only resolves
 * when the currently authenticated user is the instructor designated by the
 * `instructor_login` URL/session parameter — in every other case (nobody
 * logged in, or logged in as someone else) the signature widget must stay
 * blank. See doc/design_notes/remplissage_formulaires_design.md#9-signatures.
 *
 * Same HTTP harness as FormsSubmissionEditTest — Forms_public is only
 * testable through a real HTTP round-trip against the dev server (session-
 * carried login state, no curl available in this environment).
 */
class FormsInstructorSignaturePrefillGuardTest extends TestCase
{
    /** @var RealDatabase */
    private $db;
    private $form_id;
    private $slug;
    private $instructor_login = 'abraracourcix';
    private $signature_relpath;
    private $previous_signature_path;

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;

        $user = $this->db->select('username')->from('users')->where('username', $this->instructor_login)->get()->row_array();
        if (!$user) {
            $this->markTestSkipped('Test user "' . $this->instructor_login . '" absent (voir bin/create_test_users.sh).');
        }

        $membre = $this->db->select('signature_path')->from('membres')->where('mlogin', $this->instructor_login)->get()->row_array();
        $this->previous_signature_path = $membre ? $membre['signature_path'] : null;

        $this->signature_relpath = 'uploads/tests/sig_guard_' . uniqid() . '.png';
        $abs_path = FCPATH . $this->signature_relpath;
        @mkdir(dirname($abs_path), 0775, true);
        file_put_contents($abs_path, 'fake-signature-bytes-for-prefill-test');

        $this->db->where('mlogin', $this->instructor_login)->update('membres', array('signature_path' => $this->signature_relpath));

        $ts = time() . '_' . rand(1000, 9999);
        $this->slug = 'sig-guard-test-' . $ts;

        $this->db->insert('forms', array(
            'code'            => 'sig_guard_test_' . $ts,
            'title'           => 'Signature guard test',
            'public_slug'     => $this->slug,
            'status'          => 'published',
            'required_params' => 'instructor',
            'club'            => null, // formulaire global : pas de contrôle d'accès par section à contourner ici
        ));
        $this->form_id = $this->db->insert_id();

        $this->db->insert('form_pages', array(
            'form_id'      => $this->form_id,
            'page_number'  => 1,
            'title'        => 'Page 1',
            'content_html' => '<div data-gvv-type="signature" data-gvv-name="sig" data-gvv-source="instructor.signature">Signature</div>',
        ));
    }

    protected function tearDown(): void
    {
        $this->db->where('form_id', $this->form_id)->delete('form_submissions');
        $this->db->where('form_id', $this->form_id)->delete('form_pages');
        $this->db->where('id', $this->form_id)->delete('forms');

        $abs_path = FCPATH . $this->signature_relpath;
        if (file_exists($abs_path)) {
            unlink($abs_path);
        }

        $this->db->where('mlogin', $this->instructor_login)->update('membres', array('signature_path' => $this->previous_signature_path));
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

    private function login_as($username, $password = 'password')
    {
        $body = http_build_query(array('username' => $username, 'password' => $password));
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

    private function form_url()
    {
        return $this->base_url() . 'forms/' . $this->slug . '?instructor_login=' . $this->instructor_login;
    }

    public function testSignatureNotPrefilledWhenNobodyIsLoggedIn()
    {
        $result = $this->http_get($this->form_url());
        $this->assertNotNull($result['body']);
        $this->assertStringNotContainsString('data-sig-prefill="1"', $result['body']);
    }

    public function testSignatureNotPrefilledForADifferentLoggedInUser()
    {
        $cookie = $this->login_as('testadmin');
        $this->assertNotNull($cookie, 'Login as testadmin should succeed and return a session cookie.');

        $result = $this->http_get($this->form_url(), $cookie);
        $this->assertStringNotContainsString('data-sig-prefill="1"', $result['body']);
    }

    public function testSignaturePrefilledWhenTheInstructorIsLoggedInAsThemselves()
    {
        $cookie = $this->login_as($this->instructor_login);
        $this->assertNotNull($cookie, 'Login as the instructor should succeed and return a session cookie.');

        $result = $this->http_get($this->form_url(), $cookie);
        $this->assertStringContainsString('data-sig-prefill="1"', $result['body']);
    }

    /**
     * A reference signature stored as a JPEG (uploaded image, not a drawn PNG) must
     * be pre-filled with its real MIME type carried in data-sig-prefill-mime — the
     * widget JS builds the data URI from that attribute instead of assuming PNG.
     * Regression: JPEG signatures rendered blank because the pipeline hard-coded
     * "data:image/png;base64,".
     */
    public function testJpegReferenceSignatureCarriesItsRealMimeType()
    {
        if (!function_exists('imagejpeg')) {
            $this->markTestSkipped('GD extension with JPEG support is required.');
        }

        $jpeg_relpath = 'uploads/tests/sig_guard_' . uniqid() . '.jpg';
        $jpeg_abs     = FCPATH . $jpeg_relpath;
        $im = imagecreatetruecolor(60, 20);
        imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255));
        imagejpeg($im, $jpeg_abs);
        imagedestroy($im);
        $this->db->where('mlogin', $this->instructor_login)->update('membres', array('signature_path' => $jpeg_relpath));

        try {
            $cookie = $this->login_as($this->instructor_login);
            $this->assertNotNull($cookie, 'Login as the instructor should succeed and return a session cookie.');

            $result = $this->http_get($this->form_url(), $cookie);
            $this->assertStringContainsString('data-sig-prefill="1"', $result['body']);
            $this->assertStringContainsString('data-sig-prefill-mime="image/jpeg"', $result['body']);
        } finally {
            $this->db->where('mlogin', $this->instructor_login)->update('membres', array('signature_path' => $this->signature_relpath));
            if (file_exists($jpeg_abs)) {
                unlink($jpeg_abs);
            }
        }
    }
}
