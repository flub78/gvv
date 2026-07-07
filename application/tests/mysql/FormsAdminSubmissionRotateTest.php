<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL/HTTP tests for Forms_admin::submission_rotate() (Lot 9, étape 4).
 *
 * Comme pour FormsUploadSubmitTest, ce contrôleur (redirect()/session/rôle admin)
 * n'est testable qu'en HTTP réel (pas de curl dans cet environnement : wrapper
 * http de PHP, avec gestion manuelle du cookie de session pour l'authentification).
 */
class FormsAdminSubmissionRotateTest extends TestCase
{
    /** @var RealDatabase */
    private $db;
    private $form_id;
    private $submission_id;
    private $file_id;
    private $upload_dir;
    private $file_path;

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;

        $ts = time() . '_' . rand(1000, 9999);

        $this->db->insert('forms', array(
            'code'                  => 'rotate_test_' . $ts,
            'title'                 => 'Rotate test',
            'public_slug'           => 'rotate-test-' . $ts,
            'status'                => 'published',
            'allow_upload_response' => 1,
        ));
        $this->form_id = $this->db->insert_id();

        $this->db->insert('form_submissions', array(
            'form_id'            => $this->form_id,
            'submission_uuid'    => 'sub_rotate_' . $ts,
            'status'             => 'submitted',
            'submission_method'  => 'upload',
            'upload_comment'     => 'Rotate test comment',
            'submitted_at'       => date('Y-m-d H:i:s'),
        ));
        $this->submission_id = $this->db->insert_id();

        $this->upload_dir = FCPATH . 'uploads/reponses/' . $this->form_id;
        if (!is_dir($this->upload_dir)) {
            $old_umask = umask(0);
            mkdir($this->upload_dir, 0775, true);
            umask($old_umask);
        }

        $fixture = APPPATH . 'tests/data/attachments/images/small_receipt_scan_600x400.png';
        $this->assertFileExists($fixture);
        $this->file_path = $this->upload_dir . '/reponse_' . $this->submission_id . '.png';
        copy($fixture, $this->file_path);

        $this->db->insert('form_submission_files', array(
            'submission_id' => $this->submission_id,
            'field_id'      => null,
            'widget_name'   => 'uploaded_response',
            'original_name' => 'scan.png',
            'stored_name'   => basename($this->file_path),
            'mime_type'     => 'image/png',
            'size_bytes'    => filesize($this->file_path),
            'storage_path'  => 'uploads/reponses/' . $this->form_id . '/' . basename($this->file_path),
        ));
        $this->file_id = $this->db->insert_id();
    }

    protected function tearDown(): void
    {
        $this->db->where('submission_id', $this->submission_id)->delete('form_submission_files');
        $this->db->where('id', $this->submission_id)->delete('form_submissions');
        $this->db->where('id', $this->form_id)->delete('forms');

        if (is_dir($this->upload_dir)) {
            foreach (glob($this->upload_dir . '/*') as $f) {
                @unlink($f);
            }
            @rmdir($this->upload_dir);
        }
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
                'method'          => 'POST',
                'header'          => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content'         => $body,
                'ignore_errors'   => true,
                'follow_location' => 0,
                'timeout'         => 20,
            ),
        ));
        @file_get_contents($this->base_url() . 'auth/login', false, $context);
        $headers = isset($http_response_header) ? $http_response_header : array();

        return $this->extract_session_cookie($headers);
    }

    private function http_get($url, $cookie = null)
    {
        $header = "Cookie: " . ($cookie ?: '') . "\r\n";
        $context = stream_context_create(array(
            'http' => array(
                'method'          => 'GET',
                'header'          => $header,
                'ignore_errors'   => true,
                'follow_location' => 0,
                'timeout'         => 20,
            ),
        ));
        @file_get_contents($url, false, $context);
        return isset($http_response_header) ? $http_response_header : array();
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

    public function testAuthenticatedAdminCanRotateUploadedResponse()
    {
        $cookie = $this->login_as_admin();
        $this->assertNotNull($cookie, 'La connexion admin doit renvoyer un cookie de session.');

        $before = getimagesize($this->file_path);

        $headers = $this->http_get(
            $this->base_url() . 'forms_admin/submission_rotate/' . $this->form_id . '/' . $this->submission_id . '/cw',
            $cookie
        );

        $location = $this->location_header($headers);
        $this->assertNotNull($location);
        $this->assertStringContainsString('forms_admin/submissions/' . $this->form_id, $location);

        clearstatcache(true, $this->file_path);
        $after = getimagesize($this->file_path);
        $this->assertSame($before[1], $after[0], 'La largeur après rotation doit être l\'ancienne hauteur');
        $this->assertSame($before[0], $after[1], 'La hauteur après rotation doit être l\'ancienne largeur');
    }

    public function testUnauthenticatedRequestDoesNotRotateAndRedirectsToLogin()
    {
        $before = getimagesize($this->file_path);

        $headers = $this->http_get(
            $this->base_url() . 'forms_admin/submission_rotate/' . $this->form_id . '/' . $this->submission_id . '/cw',
            null
        );

        $location = $this->location_header($headers);
        $this->assertNotNull($location);
        $this->assertStringContainsString('auth/login', $location, 'Sans session, le contrôleur doit rediriger vers le login (contrôle d\'accès hérité de MY_Controller).');

        clearstatcache(true, $this->file_path);
        $after = getimagesize($this->file_path);
        $this->assertSame($before[0], $after[0]);
        $this->assertSame($before[1], $after[1]);
    }

    public function testInvalidDirectionIsRejected()
    {
        $cookie = $this->login_as_admin();
        $before = getimagesize($this->file_path);

        $headers = $this->http_get(
            $this->base_url() . 'forms_admin/submission_rotate/' . $this->form_id . '/' . $this->submission_id . '/sideways',
            $cookie
        );

        $location = $this->location_header($headers);
        $this->assertNotNull($location);
        $this->assertStringContainsString('forms_admin/submissions/' . $this->form_id, $location);

        clearstatcache(true, $this->file_path);
        $after = getimagesize($this->file_path);
        $this->assertSame($before[0], $after[0]);
        $this->assertSame($before[1], $after[1]);
    }
}
