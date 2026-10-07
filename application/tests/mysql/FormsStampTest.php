<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL/HTTP tests for the association stamp (Lot 17 / EF19):
 * Forms_admin::stamp_upload()/stamp_delete() and the stamp rendering rules
 * (real image in the admin answer view, never on the public form).
 *
 * Same HTTP harness as FormsPdfTemplateTest. The tests work on the global
 * stamp, which is shared with real use of gvv.net: it is snapshotted in
 * setUp() and restored identically in tearDown().
 */
class FormsStampTest extends TestCase
{
    /** @var RealDatabase */
    private $db;
    /** @var Forms_file_storage */
    private $storage;
    private $saved_global_stamp;
    private $stamp_dir_existed;
    private $form_id;
    private $submission_id;
    private $code;
    private $slug;
    private $tmp_files = array();

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;
        $CI->load->library('forms_file_storage');
        $this->storage = $CI->forms_file_storage;

        $this->stamp_dir_existed = is_dir($this->storage->stamp_dir());
        $this->saved_global_stamp = $this->storage->has_stamp(null)
            ? file_get_contents($this->storage->stamp_path(null))
            : null;
        $this->storage->delete_stamp(null);

        $ts = time() . '_' . rand(1000, 9999);
        $this->code = 'stamp_test_' . $ts;
        $this->slug = 'stamp-test-' . $ts;
        $html = '<div style="position:relative"><p>Attestation</p>'
              . '<div data-gvv-type="stamp" style="position:absolute; right:0; width:4cm">'
              . '<img src="/assets/images/forms-widgets/stamp-placeholder.svg" alt="Tampon"> Tampon de l\'association</div></div>';

        $this->db->insert('forms', array(
            'code'        => $this->code,
            'title'       => 'Stamp test',
            'public_slug' => $this->slug,
            'status'      => 'published',
        ));
        $this->form_id = $this->db->insert_id();
        $this->db->insert('form_pages', array(
            'form_id'      => $this->form_id,
            'page_number'  => 1,
            'title'        => 'Page 1',
            'content_html' => $html,
        ));
        $this->storage->write_page($this->code, 1, $html);

        $this->db->insert('form_submissions', array(
            'form_id'         => $this->form_id,
            'submission_uuid' => 'stamp-test-' . $ts,
            'status'          => 'submitted',
            'submitted_at'    => date('Y-m-d H:i:s'),
        ));
        $this->submission_id = $this->db->insert_id();
    }

    protected function tearDown(): void
    {
        if ($this->saved_global_stamp !== null) {
            $this->storage->write_stamp(null, $this->saved_global_stamp);
        } else {
            $this->storage->delete_stamp(null);
        }
        if (!$this->stamp_dir_existed && is_dir($this->storage->stamp_dir())) {
            @unlink($this->storage->stamp_dir() . '/.htaccess');
            @rmdir($this->storage->stamp_dir());
        }
        $this->db->where('id', $this->submission_id)->delete('form_submissions');
        $this->db->where('form_id', $this->form_id)->delete('form_pages');
        $this->db->where('id', $this->form_id)->delete('forms');
        $this->storage->delete_form_dir($this->code);
        foreach ($this->tmp_files as $f) {
            @unlink($f);
        }
    }

    private function base_url()
    {
        return 'http://gvv.net/index.php/';
    }

    private function png_file($with_alpha, $color = 0)
    {
        $img = imagecreatetruecolor(8, 8);
        if ($with_alpha) {
            imagesavealpha($img, true);
            imagefill($img, 0, 0, imagecolorallocatealpha($img, $color, 0, 0, 127));
        } else {
            imagefill($img, 0, 0, imagecolorallocate($img, $color, 0, 0));
        }
        $path = tempnam(sys_get_temp_dir(), 'gvv_stamp_') . '.png';
        imagepng($img, $path);
        imagedestroy($img);
        $this->tmp_files[] = $path;
        return $path;
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
        $context = stream_context_create(array(
            'http' => array(
                'method'          => 'GET',
                'header'          => "Cookie: " . ($cookie ?: '') . "\r\n",
                'ignore_errors'   => true,
                'follow_location' => 0,
                'timeout'         => 20,
            ),
        ));
        return (string) @file_get_contents($url, false, $context);
    }

    private function upload_stamp($scope, $path, $type, $cookie)
    {
        $boundary = '----GvvTest' . uniqid();
        $body = "--$boundary\r\n"
              . "Content-Disposition: form-data; name=\"stamp\"; filename=\"" . basename($path) . "\"\r\n"
              . "Content-Type: $type\r\n\r\n"
              . file_get_contents($path) . "\r\n"
              . "--$boundary--\r\n";
        $context = stream_context_create(array(
            'http' => array(
                'method'          => 'POST',
                'header'          => "Content-Type: multipart/form-data; boundary=$boundary\r\n"
                                    . "Cookie: " . ($cookie ?: '') . "\r\n",
                'content'         => $body,
                'ignore_errors'   => true,
                'follow_location' => 0,
                'timeout'         => 20,
            ),
        ));
        @file_get_contents($this->base_url() . 'forms_admin/stamp_upload/' . $scope, false, $context);
        return isset($http_response_header) ? $http_response_header : array();
    }

    private function http_post($url, $cookie)
    {
        $context = stream_context_create(array(
            'http' => array(
                'method'          => 'POST',
                'header'          => "Cookie: " . ($cookie ?: '') . "\r\n",
                'content'         => '',
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

    public function testUploadTransparentPngStoresGlobalStampAndShowsPreview()
    {
        $cookie = $this->login_as_admin();
        $this->assertNotNull($cookie);
        $png = $this->png_file(true);

        $headers = $this->upload_stamp('global', $png, 'image/png', $cookie);

        $this->assertStringContainsString('forms_admin/config', (string) $this->location_header($headers));
        $this->assertSame(file_get_contents($png), file_get_contents($this->storage->stamp_path(null)));

        $page = $this->http_get($this->base_url() . 'forms_admin/config', $cookie);
        $this->assertStringContainsString('Tampon enregistré.', $page);
        $this->assertStringNotContainsString('alert-warning', $page);
        $this->assertStringContainsString('data:image/png;base64,' . base64_encode(file_get_contents($png)), $page);
    }

    public function testOpaquePngIsAcceptedWithWarning()
    {
        $cookie = $this->login_as_admin();
        $png = $this->png_file(false);

        $this->upload_stamp('global', $png, 'image/png', $cookie);

        $this->assertTrue($this->storage->has_stamp(null));
        $page = $this->http_get($this->base_url() . 'forms_admin/config', $cookie);
        $this->assertStringContainsString('alert-warning', $page);
    }

    public function testNonPngIsRejected()
    {
        $cookie = $this->login_as_admin();
        $img = imagecreatetruecolor(8, 8);
        $jpg = tempnam(sys_get_temp_dir(), 'gvv_stamp_') . '.jpg';
        imagejpeg($img, $jpg);
        imagedestroy($img);
        $this->tmp_files[] = $jpg;

        $this->upload_stamp('global', $jpg, 'image/jpeg', $cookie);

        $this->assertFalse($this->storage->has_stamp(null));
        $page = $this->http_get($this->base_url() . 'forms_admin/config', $cookie);
        $this->assertStringContainsString('Le tampon doit être une image PNG.', $page);
    }

    public function testUnknownScopeIsRejected()
    {
        $cookie = $this->login_as_admin();
        $png = $this->png_file(true);

        $this->upload_stamp('999999', $png, 'image/png', $cookie);

        $this->assertFalse($this->storage->has_stamp(999999));
        $page = $this->http_get($this->base_url() . 'forms_admin/config', $cookie);
        $this->assertStringContainsString('Portée de tampon inconnue.', $page);
    }

    public function testUnauthenticatedUploadRedirectsToLogin()
    {
        $headers = $this->upload_stamp('global', $this->png_file(true), 'image/png', null);

        $this->assertStringContainsString('auth/login', (string) $this->location_header($headers));
        $this->assertFalse($this->storage->has_stamp(null));
    }

    public function testDeleteRemovesGlobalStamp()
    {
        $cookie = $this->login_as_admin();
        $this->storage->write_stamp(null, file_get_contents($this->png_file(true)));

        $this->http_post($this->base_url() . 'forms_admin/stamp_delete/global', $cookie);

        $this->assertFalse($this->storage->has_stamp(null));
    }

    public function testStampShownInAdminAnswerViewButNeverOnPublicForm()
    {
        $cookie = $this->login_as_admin();
        $png = file_get_contents($this->png_file(true, 200));
        $this->storage->write_stamp(null, $png);
        $data_uri = 'data:image/png;base64,' . base64_encode($png);

        $public = $this->http_get($this->base_url() . 'forms/' . $this->slug);
        $this->assertStringContainsString('stamp-placeholder.svg', $public);
        $this->assertStringNotContainsString($data_uri, $public);
        $this->assertStringNotContainsString('.tampons', $public);

        $view = $this->http_get(
            $this->base_url() . 'forms_admin/submission_view/' . $this->form_id . '/' . $this->submission_id,
            $cookie
        );
        $this->assertStringContainsString($data_uri, $view);
        $this->assertStringNotContainsString('stamp-placeholder.svg', $view);
    }
}
