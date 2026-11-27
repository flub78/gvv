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
        try {
            // The file left by a test was written by the web server (www-data):
            // unlink it first so the restored copy belongs to the test runner,
            // which write_stamp() can then chmod.
            $this->storage->delete_stamp(null);
            if ($this->saved_global_stamp !== null) {
                $this->storage->write_stamp(null, $this->saved_global_stamp);
            }
            if (!$this->stamp_dir_existed && is_dir($this->storage->stamp_dir())) {
                @unlink($this->storage->stamp_dir() . '/.htaccess');
                @rmdir($this->storage->stamp_dir());
            }
        } finally {
            $this->db->where('id', $this->submission_id)->delete('form_submissions');
            $this->db->where('form_id', $this->form_id)->delete('form_pages');
            $this->db->where('id', $this->form_id)->delete('forms');
            $this->storage->delete_form_dir($this->code);
            foreach ($this->tmp_files as $f) {
                @unlink($f);
            }
        }
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

    private function http_get($url, $cookie = null)
    {
        return GvvTestHttp::get($url, $cookie)['body'];
    }

    private function upload_stamp($scope, $path, $type, $cookie, $origin = 'http://gvv.net')
    {
        $response = GvvTestHttp::post_multipart(GvvTestHttp::base_url() . 'forms_admin/stamp_upload/' . $scope,
            array(), array('stamp' => array('path' => $path, 'type' => $type)), $cookie,
            array('headers' => $origin !== null ? "Origin: $origin\r\n" : ''));
        return $response['headers'];
    }

    private function http_post($url, $cookie, $origin = 'http://gvv.net')
    {
        return GvvTestHttp::request('POST', $url, $cookie, array('content' => '', 'headers' => $origin !== null ? "Origin: $origin\r\n" : ''))['headers'];
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
        $cookie = GvvTestHttp::login();
        $this->assertNotNull($cookie);
        $png = $this->png_file(true);

        $headers = $this->upload_stamp('global', $png, 'image/png', $cookie);

        $this->assertStringContainsString('forms_admin/config', (string) $this->location_header($headers));
        $this->assertSame(file_get_contents($png), file_get_contents($this->storage->stamp_path(null)));

        $page = $this->http_get(GvvTestHttp::base_url() . 'forms_admin/config', $cookie);
        $this->assertStringContainsString('Tampon enregistré.', $page);
        $this->assertStringNotContainsString('alert-warning', $page);
        $this->assertStringContainsString('data:image/png;base64,' . base64_encode(file_get_contents($png)), $page);
    }

    public function testOpaquePngIsAcceptedWithWarning()
    {
        $cookie = GvvTestHttp::login();
        $png = $this->png_file(false);

        $this->upload_stamp('global', $png, 'image/png', $cookie);

        $this->assertTrue($this->storage->has_stamp(null));
        $page = $this->http_get(GvvTestHttp::base_url() . 'forms_admin/config', $cookie);
        $this->assertStringContainsString('alert-warning', $page);
    }

    public function testNonPngIsRejected()
    {
        $cookie = GvvTestHttp::login();
        $img = imagecreatetruecolor(8, 8);
        $jpg = tempnam(sys_get_temp_dir(), 'gvv_stamp_') . '.jpg';
        imagejpeg($img, $jpg);
        imagedestroy($img);
        $this->tmp_files[] = $jpg;

        $this->upload_stamp('global', $jpg, 'image/jpeg', $cookie);

        $this->assertFalse($this->storage->has_stamp(null));
        $page = $this->http_get(GvvTestHttp::base_url() . 'forms_admin/config', $cookie);
        $this->assertStringContainsString('Le tampon doit être une image PNG.', $page);
    }

    public function testUnknownScopeIsRejected()
    {
        $cookie = GvvTestHttp::login();
        $png = $this->png_file(true);

        $this->upload_stamp('999999', $png, 'image/png', $cookie);

        $this->assertFalse($this->storage->has_stamp(999999));
        $page = $this->http_get(GvvTestHttp::base_url() . 'forms_admin/config', $cookie);
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
        $cookie = GvvTestHttp::login();
        $this->storage->write_stamp(null, file_get_contents($this->png_file(true)));

        $this->http_post(GvvTestHttp::base_url() . 'forms_admin/stamp_delete/global', $cookie);

        $this->assertFalse($this->storage->has_stamp(null));
    }

    public function testStampShownInAdminAnswerViewButNeverOnPublicForm()
    {
        $cookie = GvvTestHttp::login();
        $png = file_get_contents($this->png_file(true, 200));
        $this->storage->write_stamp(null, $png);
        $data_uri = 'data:image/png;base64,' . base64_encode($png);

        $public = $this->http_get(GvvTestHttp::base_url() . 'forms/' . $this->slug);
        $this->assertStringContainsString('stamp-placeholder.svg', $public);
        $this->assertStringNotContainsString($data_uri, $public);
        $this->assertStringNotContainsString('.tampons', $public);

        $view = $this->http_get(
            GvvTestHttp::base_url() . 'forms_admin/submission_view/' . $this->form_id . '/' . $this->submission_id,
            $cookie
        );
        $this->assertStringContainsString($data_uri, $view);
        $this->assertStringNotContainsString('stamp-placeholder.svg', $view);
    }

    public function testUploadFromForeignOriginIsRefused()
    {
        $cookie = GvvTestHttp::login();

        $this->upload_stamp('global', $this->png_file(true), 'image/png', $cookie, 'https://evil.example');

        $this->assertFalse($this->storage->has_stamp(null), 'Un dépôt venant d\'un autre site ne doit rien écrire.');
        $page = $this->http_get(GvvTestHttp::base_url() . 'forms_admin/config', $cookie);
        $this->assertStringContainsString('Requête refusée', $page);
    }

    public function testUploadWithoutOriginNorRefererIsRefused()
    {
        $cookie = GvvTestHttp::login();

        $this->upload_stamp('global', $this->png_file(true), 'image/png', $cookie, null);

        $this->assertFalse($this->storage->has_stamp(null));
    }

    public function testDeleteFromForeignOriginIsRefused()
    {
        $cookie = GvvTestHttp::login();
        $png = file_get_contents($this->png_file(true));
        $this->storage->write_stamp(null, $png);

        $this->http_post(GvvTestHttp::base_url() . 'forms_admin/stamp_delete/global', $cookie, 'http://gvv.net.evil.example');

        $this->assertTrue($this->storage->has_stamp(null), 'Une suppression venant d\'un autre site doit être refusée.');
        $this->assertSame($png, file_get_contents($this->storage->stamp_path(null)));
    }
}
