<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL/HTTP tests for Forms_admin::submission_edit() / submission_edit_submit() (Lot 13
 * — modification en place d'une réponse déjà soumise).
 *
 * Same HTTP harness as FormsAdminSubmissionRotateTest / FormsUploadSubmitTest: these
 * controller actions (redirect()/session/role checks) are only testable through a real
 * HTTP round-trip against the dev server, no curl available in this environment.
 */
class FormsSubmissionEditTest extends TestCase
{
    /** @var RealDatabase */
    private $db;
    private $form_id;
    private $field_nom_id;
    private $field_file_id;
    private $submission_id;
    private $submission_uuid;
    private $upload_dir;
    private $file_path;
    private $sig_path;
    private $fixture_png;

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;

        $ts = time() . '_' . rand(1000, 9999);
        $now = date('Y-m-d H:i:s');

        $this->db->insert('forms', array(
            'code'        => 'edit_test_' . $ts,
            'title'       => 'Edit test',
            'public_slug' => 'edit-test-' . $ts,
            'status'      => 'published',
            'club'        => null, // global form: no active-section access check to fight in these tests
        ));
        $this->form_id = $this->db->insert_id();

        $content_html = '<input type="text" name="nom" value="">'
            . '<input type="file" name="piece_jointe">'
            . '<div data-gvv-type="signature" data-gvv-name="signature_test">Signature</div>';

        $this->db->insert('form_pages', array(
            'form_id'      => $this->form_id,
            'page_number'  => 1,
            'title'        => 'Page 1',
            'content_html' => $content_html,
        ));
        $page_id = $this->db->insert_id();

        $this->db->insert('form_fields', array(
            'form_id' => $this->form_id, 'page_id' => $page_id, 'name' => 'nom',
            'label' => 'Nom', 'field_type' => 'text', 'is_required' => 0, 'sort_order' => 1,
        ));
        $this->field_nom_id = $this->db->insert_id();

        $this->db->insert('form_fields', array(
            'form_id' => $this->form_id, 'page_id' => $page_id, 'name' => 'piece_jointe',
            'label' => 'Pièce jointe', 'field_type' => 'file', 'is_required' => 0, 'sort_order' => 2,
        ));
        $this->field_file_id = $this->db->insert_id();

        // No form_fields row for the signature widget: form_fields.field_type is an ENUM
        // that was never migrated to include 'signature' (see migration 116_forms_core.php)
        // — in practice every signature widget is the HTML-only kind (data-gvv-type, no
        // form_fields row, matched by widget_name; migration 137 / Lot 5-bis).

        $this->submission_uuid = 'sub_edit_' . $ts;
        $this->db->insert('form_submissions', array(
            'form_id' => $this->form_id, 'submission_uuid' => $this->submission_uuid,
            'status' => 'submitted', 'submission_method' => 'online',
            'submitted_at' => $now, 'created_at' => $now, 'updated_at' => $now,
        ));
        $this->submission_id = $this->db->insert_id();

        $this->db->insert('form_submission_values', array(
            'submission_id' => $this->submission_id, 'field_id' => $this->field_nom_id,
            'value_text' => 'Ancien nom', 'created_at' => $now, 'updated_at' => $now,
        ));

        $this->fixture_png = APPPATH . 'tests/data/attachments/images/small_receipt_scan_600x400.png';
        $this->assertFileExists($this->fixture_png);

        $this->upload_dir = FCPATH . 'uploads/forms_submissions/' . date('Y/m');
        if (!is_dir($this->upload_dir)) {
            $old_umask = umask(0);
            mkdir($this->upload_dir, 0775, true);
            umask($old_umask);
        }

        $this->file_path = $this->upload_dir . '/edit_test_file_' . $ts . '.pdf';
        file_put_contents($this->file_path, 'fake pdf content for test');
        $this->db->insert('form_submission_files', array(
            'submission_id' => $this->submission_id, 'field_id' => $this->field_file_id,
            'original_name' => 'ancien.pdf', 'stored_name' => basename($this->file_path),
            'mime_type' => 'application/pdf', 'size_bytes' => filesize($this->file_path),
            'storage_path' => 'uploads/forms_submissions/' . date('Y/m') . '/' . basename($this->file_path),
            'created_at' => $now, 'updated_at' => $now,
        ));
        $this->old_file_id = $this->db->insert_id();

        $this->sig_path = $this->upload_dir . '/edit_test_sig_' . $ts . '.png';
        copy($this->fixture_png, $this->sig_path);
        $this->db->insert('form_submission_files', array(
            'submission_id' => $this->submission_id, 'field_id' => null, 'widget_name' => 'signature_test',
            'original_name' => 'signature.png', 'stored_name' => basename($this->sig_path),
            'mime_type' => 'image/png', 'size_bytes' => filesize($this->sig_path),
            'storage_path' => 'uploads/forms_submissions/' . date('Y/m') . '/' . basename($this->sig_path),
            'created_at' => $now, 'updated_at' => $now,
        ));
        $this->old_sig_id = $this->db->insert_id();
    }

    protected function tearDown(): void
    {
        $files = $this->db->where('submission_id', $this->submission_id)->get('form_submission_files')->result_array();
        foreach ($files as $file) {
            $path = FCPATH . ltrim((string) $file['storage_path'], '/');
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $this->db->where('submission_id', $this->submission_id)->delete('form_submission_files');
        $this->db->where('submission_id', $this->submission_id)->delete('form_submission_values');
        $this->db->where('id', $this->submission_id)->delete('form_submissions');
        $this->db->where('form_id', $this->form_id)->delete('form_fields');
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

    private function http_post_multipart($url, array $fields, array $files, $cookie = null)
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
                'method' => 'POST',
                'header' => "Content-Type: multipart/form-data; boundary=$boundary\r\nCookie: " . ($cookie ?: '') . "\r\n",
                'content' => $body, 'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 20,
            ),
        ));
        $response_body = @file_get_contents($url, false, $context);
        return array('body' => $response_body, 'headers' => isset($http_response_header) ? $http_response_header : array());
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

    public function testUnauthenticatedRequestRedirectsToLogin()
    {
        $result = $this->http_get($this->base_url() . 'forms_admin/submission_edit/' . $this->form_id . '/' . $this->submission_id);
        $location = $this->location_header($result['headers']);
        $this->assertNotNull($location);
        $this->assertStringContainsString('auth/login', $location);
    }

    public function testEditFormIsPrefilledWithExistingValueAndShowsExistingFileAndSignature()
    {
        $cookie = $this->login_as_admin();
        $this->assertNotNull($cookie);

        $result = $this->http_get(
            $this->base_url() . 'forms_admin/submission_edit/' . $this->form_id . '/' . $this->submission_id,
            $cookie
        );

        $this->assertNotNull($result['body']);
        $this->assertStringContainsString('Ancien nom', $result['body'], 'La valeur déjà soumise doit préremplir le champ texte.');
        $this->assertStringContainsString('ancien.pdf', $result['body'], 'Le nom du fichier déjà soumis doit être affiché.');
        $this->assertStringContainsString('gvv-sig-existing-preview', $result['body'], 'La signature existante doit être affichée en aperçu.');
    }

    public function testUploadMethodSubmissionCannotBeEdited()
    {
        $this->db->where('id', $this->submission_id)->update('form_submissions', array('submission_method' => 'upload'));

        $cookie = $this->login_as_admin();
        $result = $this->http_get(
            $this->base_url() . 'forms_admin/submission_edit/' . $this->form_id . '/' . $this->submission_id,
            $cookie
        );

        $location = $this->location_header($result['headers']);
        $this->assertNotNull($location, 'Une soumission de type téléchargement doit rediriger, pas afficher le formulaire d\'édition.');
        $this->assertStringContainsString('forms_admin/submissions/' . $this->form_id, $location);
    }

    public function testResubmissionWithoutTouchingFileOrSignatureKeepsThemUnchanged()
    {
        $cookie = $this->login_as_admin();

        $before_file = $this->db->where('id', $this->old_file_id)->get('form_submission_files')->row_array();
        $before_sig  = $this->db->where('id', $this->old_sig_id)->get('form_submission_files')->row_array();
        $before_submission = $this->db->where('id', $this->submission_id)->get('form_submissions')->row_array();

        $result = $this->http_post_multipart(
            $this->base_url() . 'forms_admin/submission_edit_submit/' . $this->form_id . '/' . $this->submission_id,
            array(
                'page_number'        => '1',
                'nom'                => 'Nouveau nom',
                'signature_test'     => '',
                'signature_test_type' => 'canvas',
            ),
            array(), // no new files posted
            $cookie
        );

        $location = $this->location_header($result['headers']);
        $this->assertNotNull($location);
        $this->assertStringContainsString('forms_admin/submission/' . $this->form_id . '/' . $this->submission_id, $location);

        // id / uuid never change
        $after_submission = $this->db->where('id', $this->submission_id)->get('form_submissions')->row_array();
        $this->assertNotEmpty($after_submission, 'La soumission doit toujours exister sous le même id.');
        $this->assertSame($before_submission['submission_uuid'], $after_submission['submission_uuid']);
        $this->assertSame($before_submission['submitted_at'], $after_submission['submitted_at']);
        $this->assertSame('testadmin', $after_submission['updated_by']);

        // value actually updated in place
        $value = $this->db->where('submission_id', $this->submission_id)->where('field_id', $this->field_nom_id)
            ->get('form_submission_values')->row_array();
        $this->assertSame('Nouveau nom', $value['value_text']);

        // file and signature untouched: same DB rows, same files still on disk
        $after_file = $this->db->where('id', $this->old_file_id)->get('form_submission_files')->row_array();
        $after_sig  = $this->db->where('id', $this->old_sig_id)->get('form_submission_files')->row_array();
        $this->assertNotEmpty($after_file, 'Le fichier existant ne doit pas être supprimé quand il n\'est pas remplacé.');
        $this->assertSame($before_file['stored_name'], $after_file['stored_name']);
        $this->assertNotEmpty($after_sig, 'La signature existante ne doit pas être supprimée quand elle n\'est pas remplacée.');
        $this->assertSame($before_sig['stored_name'], $after_sig['stored_name']);
        $this->assertFileExists($this->file_path);
        $this->assertFileExists($this->sig_path);
    }

    public function testReplacingFileAndSignatureDeletesThePreviousOnes()
    {
        $cookie = $this->login_as_admin();

        $pdf_path = APPPATH . 'tests/data/attachments/documents/small_invoice_90kb.pdf';
        $this->assertFileExists($pdf_path);

        $result = $this->http_post_multipart(
            $this->base_url() . 'forms_admin/submission_edit_submit/' . $this->form_id . '/' . $this->submission_id,
            array(
                'page_number'        => '1',
                'nom'                => 'Ancien nom', // unchanged
                'signature_test'     => base64_encode(file_get_contents($this->fixture_png)),
                'signature_test_type' => 'canvas',
            ),
            array('piece_jointe' => array(
                'filename' => 'nouveau.pdf',
                'type'     => 'application/pdf',
                'path'     => $pdf_path,
            )),
            $cookie
        );

        $location = $this->location_header($result['headers']);
        $this->assertNotNull($location);
        $this->assertStringContainsString('forms_admin/submission/' . $this->form_id . '/' . $this->submission_id, $location);

        // old file/signature rows gone, old disk files gone
        $old_file = $this->db->where('id', $this->old_file_id)->get('form_submission_files')->row_array();
        $old_sig  = $this->db->where('id', $this->old_sig_id)->get('form_submission_files')->row_array();
        $this->assertEmpty($old_file, 'L\'ancien fichier doit être supprimé de form_submission_files une fois remplacé.');
        $this->assertEmpty($old_sig, 'L\'ancienne signature doit être supprimée de form_submission_files une fois remplacée.');
        $this->assertFileNotExists($this->file_path, 'L\'ancien fichier doit être supprimé du disque.');
        $this->assertFileNotExists($this->sig_path, 'L\'ancienne signature doit être supprimée du disque.');

        // new file/signature present for the same fields
        $new_file = $this->db->where('submission_id', $this->submission_id)
            ->where('field_id', $this->field_file_id)->get('form_submission_files')->row_array();
        $new_sig = $this->db->where('submission_id', $this->submission_id)
            ->where('widget_name', 'signature_test')->get('form_submission_files')->row_array();
        $this->assertNotEmpty($new_file, 'Le nouveau fichier doit être enregistré.');
        $this->assertSame('nouveau.pdf', $new_file['original_name']);
        $this->assertFileExists(FCPATH . ltrim((string) $new_file['storage_path'], '/'));
        $this->assertNotEmpty($new_sig, 'La nouvelle signature doit être enregistrée.');
        $this->assertFileExists(FCPATH . ltrim((string) $new_sig['storage_path'], '/'));
    }
}
