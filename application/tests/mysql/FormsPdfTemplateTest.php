<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL/HTTP tests for Forms_admin::pdf_template_upload()/pdf_template_delete()
 * (Lot 16, étape 2 — EF18).
 *
 * Same HTTP harness as FormsAdminSubmissionRotateTest/FormsUploadSubmitTest:
 * this controller (redirect()/session/$_FILES) can only be exercised through
 * a real HTTP round-trip against the dev server, not called directly from
 * PHPUnit — no curl in this environment, file_get_contents()'s http wrapper
 * with manual session cookie handling instead.
 */
class FormsPdfTemplateTest extends TestCase
{
    /** @var RealDatabase */
    private $db;
    /** @var Forms_file_storage */
    private $storage;
    private $form_id;
    private $code;

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;
        $CI->load->library('forms_file_storage');
        $this->storage = $CI->forms_file_storage;

        $ts = time() . '_' . rand(1000, 9999);
        $this->code = 'pdf_template_test_' . $ts;

        $this->db->insert('forms', array(
            'code'                  => $this->code,
            'title'                 => 'PDF template test',
            'public_slug'           => 'pdf-template-test-' . $ts,
            'status'                => 'published',
            'allow_upload_response' => 1,
        ));
        $this->form_id = $this->db->insert_id();
    }

    protected function tearDown(): void
    {
        $this->storage->delete_form_dir($this->code);
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

    private function http_post_multipart($url, array $fields, array $files, $cookie = null, $follow_redirects = false)
    {
        $boundary = '----GvvTest' . uniqid();
        $body = '';
        foreach ($fields as $name => $value) {
            $body .= "--$boundary\r\n";
            $body .= "Content-Disposition: form-data; name=\"$name\"\r\n\r\n";
            $body .= $value . "\r\n";
        }
        foreach ($files as $name => $file) {
            $body .= "--$boundary\r\n";
            $body .= "Content-Disposition: form-data; name=\"$name\"; filename=\"{$file['filename']}\"\r\n";
            $body .= "Content-Type: {$file['type']}\r\n\r\n";
            $body .= file_get_contents($file['path']) . "\r\n";
        }
        $body .= "--$boundary--\r\n";

        $context = stream_context_create(array(
            'http' => array(
                'method'          => 'POST',
                'header'          => "Content-Type: multipart/form-data; boundary=$boundary\r\n"
                                    . "Cookie: " . ($cookie ?: '') . "\r\n",
                'content'         => $body,
                'ignore_errors'   => true,
                'follow_location' => $follow_redirects ? 1 : 0,
                'timeout'         => 20,
            ),
        ));

        $response_body = @file_get_contents($url, false, $context);
        $headers = isset($http_response_header) ? $http_response_header : array();

        return array('body' => $response_body, 'headers' => $headers);
    }

    private function http_post($url, $cookie = null)
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

    public function testAuthenticatedAdminCanUploadPdfTemplate()
    {
        $cookie = $this->login_as_admin();
        $this->assertNotNull($cookie, 'La connexion admin doit renvoyer un cookie de session.');

        $pdf_path = APPPATH . 'tests/data/attachments/documents/small_invoice_90kb.pdf';
        $this->assertFileExists($pdf_path);

        $result = $this->http_post_multipart(
            $this->base_url() . 'forms_admin/pdf_template_upload/' . $this->form_id,
            array(),
            array('pdf_template' => array(
                'filename' => 'vierge.pdf',
                'type'     => 'application/pdf',
                'path'     => $pdf_path,
            )),
            $cookie
        );

        $location = $this->location_header($result['headers']);
        $this->assertNotNull($location);
        $this->assertStringContainsString('forms_admin/edit/' . $this->form_id, $location);

        $this->assertTrue($this->storage->has_pdf_template($this->code));
        $this->assertSame(file_get_contents($pdf_path), $this->storage->read_pdf_template($this->code));

        $meta = $this->storage->read_meta($this->code);
        $this->assertNotNull($meta);
        $this->assertTrue($meta['pdf_template'], 'meta.json doit refléter la présence du template PDF.');
    }

    public function testUploadingAgainReplacesThePreviousFileRatherThanAccumulating()
    {
        $cookie = $this->login_as_admin();

        $first_path  = APPPATH . 'tests/data/attachments/documents/small_invoice_90kb.pdf';
        $second_path = APPPATH . 'tests/data/attachments/documents/medium_contract_600kb.pdf';
        $this->assertFileExists($first_path);
        $this->assertFileExists($second_path);

        $this->http_post_multipart(
            $this->base_url() . 'forms_admin/pdf_template_upload/' . $this->form_id,
            array(),
            array('pdf_template' => array('filename' => 'v1.pdf', 'type' => 'application/pdf', 'path' => $first_path)),
            $cookie
        );
        $this->assertSame(file_get_contents($first_path), $this->storage->read_pdf_template($this->code));

        $this->http_post_multipart(
            $this->base_url() . 'forms_admin/pdf_template_upload/' . $this->form_id,
            array(),
            array('pdf_template' => array('filename' => 'v2.pdf', 'type' => 'application/pdf', 'path' => $second_path)),
            $cookie
        );

        $this->assertSame(
            file_get_contents($second_path),
            $this->storage->read_pdf_template($this->code),
            'Le second dépôt doit remplacer le premier.'
        );

        // No versioning, no accumulation: a single template.pdf on disk.
        $pdf_files = glob($this->storage->form_dir($this->code) . '/*.pdf');
        $this->assertCount(1, $pdf_files, 'Un seul fichier PDF doit subsister après remplacement (pas de fichier orphelin).');
    }

    public function testRejectedFileTypeDoesNotWritePdfTemplate()
    {
        $cookie = $this->login_as_admin();

        $txt_path = APPPATH . 'tests/data/attachments/text/small_text_file_50kb.txt';
        $this->assertFileExists($txt_path);

        $result = $this->http_post_multipart(
            $this->base_url() . 'forms_admin/pdf_template_upload/' . $this->form_id,
            array(),
            array('pdf_template' => array('filename' => 'not_a_pdf.txt', 'type' => 'text/plain', 'path' => $txt_path)),
            $cookie
        );

        $location = $this->location_header($result['headers']);
        $this->assertNotNull($location, 'Un type de fichier refusé doit provoquer une redirection.');
        $this->assertFalse($this->storage->has_pdf_template($this->code), 'Aucun template ne doit être écrit pour un type refusé.');
    }

    public function testUnauthenticatedUploadRedirectsToLoginAndDoesNotWrite()
    {
        $pdf_path = APPPATH . 'tests/data/attachments/documents/small_invoice_90kb.pdf';

        $result = $this->http_post_multipart(
            $this->base_url() . 'forms_admin/pdf_template_upload/' . $this->form_id,
            array(),
            array('pdf_template' => array('filename' => 'vierge.pdf', 'type' => 'application/pdf', 'path' => $pdf_path)),
            null
        );

        $location = $this->location_header($result['headers']);
        $this->assertNotNull($location);
        $this->assertStringContainsString('auth/login', $location, 'Sans session, le contrôleur doit rediriger vers le login.');
        $this->assertFalse($this->storage->has_pdf_template($this->code));
    }

    public function testDeletePdfTemplateRemovesFile()
    {
        $cookie = $this->login_as_admin();
        $this->storage->write_pdf_template($this->code, '%PDF-1.4 fake');
        $this->assertTrue($this->storage->has_pdf_template($this->code));

        $headers = $this->http_post(
            $this->base_url() . 'forms_admin/pdf_template_delete/' . $this->form_id,
            $cookie
        );

        $location = $this->location_header($headers);
        $this->assertNotNull($location);
        $this->assertStringContainsString('forms_admin/edit/' . $this->form_id, $location);

        $this->assertFalse($this->storage->has_pdf_template($this->code));
        $meta = $this->storage->read_meta($this->code);
        $this->assertFalse($meta['pdf_template']);
    }

    public function testDeleteIsNoOpWhenNoTemplateExists()
    {
        $cookie = $this->login_as_admin();

        // Must not error even though there is nothing to delete.
        $headers = $this->http_post(
            $this->base_url() . 'forms_admin/pdf_template_delete/' . $this->form_id,
            $cookie
        );

        $location = $this->location_header($headers);
        $this->assertNotNull($location);
        $this->assertFalse($this->storage->has_pdf_template($this->code));
    }
}
