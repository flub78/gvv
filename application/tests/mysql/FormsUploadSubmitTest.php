<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL/HTTP tests for Forms_public::upload_submit() (Lot 9, étape 3).
 *
 * Exerce le vrai endpoint HTTP public sur le serveur de dev (http://gvv.net),
 * car ce contrôleur (redirect()/show_404()/$_FILES) ne peut pas être appelé
 * directement en PHPUnit sans harnais HTTP. Pas de curl disponible dans cet
 * environnement : on utilise le wrapper http de file_get_contents().
 */
class FormsUploadSubmitTest extends TestCase
{
    /** @var RealDatabase */
    private $db;
    private $enabled_form_id;
    private $enabled_slug;
    private $disabled_form_id;
    private $disabled_slug;
    private $created_submission_ids = array();

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;

        $ts = time() . '_' . rand(1000, 9999);

        $this->db->insert('forms', array(
            'code'                   => 'upload_test_on_' . $ts,
            'title'                  => 'Upload test (enabled)',
            'public_slug'            => 'upload-test-on-' . $ts,
            'status'                 => 'published',
            'allow_upload_response'  => 1,
        ));
        $this->enabled_form_id = $this->db->insert_id();
        $this->enabled_slug    = 'upload-test-on-' . $ts;

        $this->db->insert('forms', array(
            'code'                   => 'upload_test_off_' . $ts,
            'title'                  => 'Upload test (disabled)',
            'public_slug'            => 'upload-test-off-' . $ts,
            'status'                 => 'published',
            'allow_upload_response'  => 0,
        ));
        $this->disabled_form_id = $this->db->insert_id();
        $this->disabled_slug    = 'upload-test-off-' . $ts;
    }

    protected function tearDown(): void
    {
        foreach ($this->created_submission_ids as $submission_id) {
            $files = $this->db->where('submission_id', $submission_id)->get('form_submission_files')->result_array();
            foreach ($files as $file) {
                $path = FCPATH . ltrim((string) $file['storage_path'], '/');
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            $this->db->where('submission_id', $submission_id)->delete('form_submission_files');
            $this->db->where('id', $submission_id)->delete('form_submissions');
        }

        foreach (array($this->enabled_form_id, $this->disabled_form_id) as $form_id) {
            $dir = FCPATH . 'uploads/reponses/' . $form_id;
            if (is_dir($dir)) {
                foreach (glob($dir . '/*') as $f) {
                    @unlink($f);
                }
                @rmdir($dir);
            }
            $this->db->where('form_id', $form_id)->delete('form_submissions');
            $this->db->where('id', $form_id)->delete('forms');
        }
    }

    private function base_url()
    {
        return 'http://gvv.net/index.php/';
    }

    private function http_post_multipart($url, array $fields, array $files, $follow_redirects = true)
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
                'header'          => "Content-Type: multipart/form-data; boundary=$boundary\r\n",
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

    private function location_header(array $headers)
    {
        foreach ($headers as $h) {
            if (stripos($h, 'Location:') === 0) {
                return trim(substr($h, strlen('Location:')));
            }
        }
        return null;
    }

    public function testValidUploadCreatesSubmissionAndFile()
    {
        $pdf_path = APPPATH . 'tests/data/attachments/documents/small_invoice_90kb.pdf';
        $this->assertFileExists($pdf_path);

        $result = $this->http_post_multipart(
            $this->base_url() . 'forms/upload/' . $this->enabled_slug,
            array('upload_comment' => 'Commentaire de test PHPUnit'),
            array('upload_response_file' => array(
                'filename' => 'reponse_scan.pdf',
                'type'     => 'application/pdf',
                'path'     => $pdf_path,
            ))
        );

        $this->assertNotNull($result['body'], 'La requête HTTP doit aboutir (serveur de dev disponible sur gvv.net).');

        $submission = $this->db
            ->where('form_id', $this->enabled_form_id)
            ->where('submission_method', 'upload')
            ->get('form_submissions')
            ->row_array();

        $this->assertNotEmpty($submission, 'Une soumission upload doit avoir été créée.');
        $this->created_submission_ids[] = $submission['id'];
        $this->assertSame('Commentaire de test PHPUnit', $submission['upload_comment']);

        $file = $this->db
            ->where('submission_id', $submission['id'])
            ->where('widget_name', 'uploaded_response')
            ->get('form_submission_files')
            ->row_array();

        $this->assertNotEmpty($file, 'Un enregistrement form_submission_files (widget_name=uploaded_response) doit exister.');
        $this->assertNull($file['field_id']);
        $this->assertSame('application/pdf', $file['mime_type']);

        $expected_path = FCPATH . 'uploads/reponses/' . $this->enabled_form_id . '/reponse_' . $submission['id'] . '.pdf';
        $this->assertFileExists($expected_path, 'Le fichier téléversé doit être nommé reponse_{submission_id}.pdf');
    }

    public function testRejectedFileTypeDoesNotLeaveOrphanSubmission()
    {
        $before = (int) $this->db->where('form_id', $this->enabled_form_id)->count_all_results('form_submissions');

        $txt_path = APPPATH . 'tests/data/attachments/text/small_text_file_50kb.txt';
        $this->assertFileExists($txt_path);

        $result = $this->http_post_multipart(
            $this->base_url() . 'forms/upload/' . $this->enabled_slug,
            array('upload_comment' => ''),
            array('upload_response_file' => array(
                'filename' => 'not_allowed.txt',
                'type'     => 'text/plain',
                'path'     => $txt_path,
            )),
            false // don't follow the redirect, we just want to see it happened
        );

        $location = $this->location_header($result['headers']);
        $this->assertNotNull($location, 'Un fichier de type refusé doit provoquer une redirection (pas un succès direct).');
        $this->assertStringNotContainsString('forms_public/upload_submit', $location);

        $after = (int) $this->db->where('form_id', $this->enabled_form_id)->count_all_results('form_submissions');
        $this->assertSame($before, $after, 'Aucune soumission ne doit rester en base après un type de fichier refusé.');
    }

    public function testDisabledFormRejectsUpload()
    {
        $before = (int) $this->db->where('form_id', $this->disabled_form_id)->count_all_results('form_submissions');

        $pdf_path = APPPATH . 'tests/data/attachments/documents/small_invoice_90kb.pdf';

        $result = $this->http_post_multipart(
            $this->base_url() . 'forms/upload/' . $this->disabled_slug,
            array('upload_comment' => ''),
            array('upload_response_file' => array(
                'filename' => 'reponse_scan.pdf',
                'type'     => 'application/pdf',
                'path'     => $pdf_path,
            )),
            false
        );

        $location = $this->location_header($result['headers']);
        $this->assertNotNull($location, 'Le formulaire doit rediriger quand allow_upload_response=0.');

        $after = (int) $this->db->where('form_id', $this->disabled_form_id)->count_all_results('form_submissions');
        $this->assertSame($before, $after, 'Aucune soumission ne doit être créée quand le téléchargement est désactivé.');
    }
}
