<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL/HTTP tests for Forms_public::pdf_template() and the public download
 * link on the form page (Lot 16, étape 3 — EF18).
 *
 * Same HTTP harness as FormsUploadSubmitTest: forms_public is only reachable
 * through a real HTTP round-trip against the dev server (redirect()/
 * show_404()), not callable directly from PHPUnit.
 */
class FormsPublicPdfTemplateTest extends TestCase
{
    /** @var RealDatabase */
    private $db;
    /** @var Forms_file_storage */
    private $storage;
    private $form_id;
    private $code;
    private $slug;

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;
        $CI->load->library('forms_file_storage');
        $this->storage = $CI->forms_file_storage;

        $ts = time() . '_' . rand(1000, 9999);
        $this->code = 'pdf_public_test_' . $ts;
        $this->slug = 'pdf-public-test-' . $ts;

        $this->db->insert('forms', array(
            'code'                  => $this->code,
            'title'                 => 'PDF public test',
            'public_slug'           => $this->slug,
            'status'                => 'published',
            'allow_upload_response' => 1,
        ));
        $this->form_id = $this->db->insert_id();

        $this->db->insert('form_pages', array(
            'form_id'      => $this->form_id,
            'page_number'  => 1,
            'title'        => 'Page 1',
            'content_html' => '<p>Contenu</p>',
        ));
        $this->storage->write_page($this->code, 1, '<p>Contenu</p>');
    }

    protected function tearDown(): void
    {
        $this->storage->delete_form_dir($this->code);
        $this->db->where('form_id', $this->form_id)->delete('form_pages');
        $this->db->where('id', $this->form_id)->delete('forms');
    }

    private function base_url()
    {
        return 'http://gvv.net/index.php/';
    }

    private function http_get($url)
    {
        $context = stream_context_create(array(
            'http' => array(
                'method'          => 'GET',
                'ignore_errors'   => true,
                'follow_location' => 0,
                'timeout'         => 20,
            ),
        ));
        $body = @file_get_contents($url, false, $context);
        $headers = isset($http_response_header) ? $http_response_header : array();
        return array('body' => $body, 'headers' => $headers);
    }

    private function status_code(array $headers)
    {
        foreach ($headers as $h) {
            if (preg_match('#^HTTP/\S+\s+(\d+)#', $h, $m)) {
                return (int) $m[1];
            }
        }
        return null;
    }

    private function header_value(array $headers, $name)
    {
        foreach ($headers as $h) {
            if (stripos($h, $name . ':') === 0) {
                return trim(substr($h, strlen($name) + 1));
            }
        }
        return null;
    }

    public function testDownloadsExistingTemplateWithPdfContentType()
    {
        $this->storage->write_pdf_template($this->code, '%PDF-1.4 fake content');

        $result = $this->http_get($this->base_url() . 'forms_public/pdf_template/' . $this->code);

        $this->assertSame(200, $this->status_code($result['headers']));
        $this->assertSame('application/pdf', $this->header_value($result['headers'], 'Content-Type'));
        $this->assertSame('%PDF-1.4 fake content', $result['body']);
    }

    public function test404WhenNoTemplateUploaded()
    {
        $result = $this->http_get($this->base_url() . 'forms_public/pdf_template/' . $this->code);

        $this->assertSame(404, $this->status_code($result['headers']));
    }

    public function test404ForUnknownFormCode()
    {
        $result = $this->http_get($this->base_url() . 'forms_public/pdf_template/does-not-exist-' . uniqid());

        $this->assertSame(404, $this->status_code($result['headers']));
    }

    public function test404ForPathTraversalAttempt()
    {
        $result = $this->http_get($this->base_url() . 'forms_public/pdf_template/' . rawurlencode('../../application/config/database'));

        $this->assertSame(404, $this->status_code($result['headers']));
    }

    public function testDownloadLinkVisibleOnPublicPageWhenEnabledAndTemplatePresent()
    {
        $this->storage->write_pdf_template($this->code, '%PDF-1.4 fake');

        $result = $this->http_get($this->base_url() . 'forms/' . $this->slug);

        $this->assertStringContainsString('forms_public/pdf_template/' . $this->code, $result['body']);
    }

    public function testDownloadLinkAbsentWhenNoTemplateUploaded()
    {
        $result = $this->http_get($this->base_url() . 'forms/' . $this->slug);

        $this->assertStringNotContainsString('forms_public/pdf_template/' . $this->code, $result['body']);
    }

    public function testDownloadLinkAbsentWhenUploadResponseDisabledEvenIfTemplatePresent()
    {
        $this->storage->write_pdf_template($this->code, '%PDF-1.4 fake');
        $this->db->where('id', $this->form_id)->update('forms', array('allow_upload_response' => 0));

        $result = $this->http_get($this->base_url() . 'forms/' . $this->slug);

        $this->assertStringNotContainsString('forms_public/pdf_template/' . $this->code, $result['body']);
    }
}
