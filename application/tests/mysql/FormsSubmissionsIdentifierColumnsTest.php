<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL/HTTP tests for the submissions list of Forms_admin::submissions():
 * one column per field flagged data-gvv-identifier (labelled with the field's
 * own <label>), instead of the previous single merged "Identification" column.
 * A form with no identifier field gets no such column at all.
 *
 * Same HTTP harness as FormsSubmissionEditTest (Forms_admin::submissions() is
 * only reachable through a real HTTP round-trip against the dev server).
 */
class FormsSubmissionsIdentifierColumnsTest extends TestCase
{
    /** @var RealDatabase */
    private $db;
    private $form_id;          // form with 2 identifier fields
    private $plain_form_id;    // form with no identifier field
    private $sub1;
    private $sub2;

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;
        $CI->load->model('form_submissions_model');
        $this->model = $CI->form_submissions_model;

        $ts = time() . '_' . rand(1000, 9999);
        $now = date('Y-m-d H:i:s');

        // --- Form with two identifier fields + one plain field ---
        $this->db->insert('forms', array(
            'code' => 'ident_cols_test_' . $ts, 'title' => 'Identifier columns test',
            'public_slug' => 'ident-cols-test-' . $ts, 'status' => 'published', 'club' => null,
        ));
        $this->form_id = $this->db->insert_id();

        $content_html =
            '<label for="nom_pilote">Nom du pilote</label>'
            . '<input type="text" id="nom_pilote" name="nom_pilote" data-gvv-identifier>'
            . '<label for="num_licence">N° licence</label>'
            . '<input type="text" id="num_licence" name="num_licence" data-gvv-identifier>'
            . '<label for="commentaire">Commentaire</label>'
            . '<input type="text" id="commentaire" name="commentaire">';
        $this->db->insert('form_pages', array(
            'form_id' => $this->form_id, 'page_number' => 1, 'title' => 'Page 1',
            'content_html' => $content_html,
        ));

        $this->sub1 = $this->_make_submission($this->form_id, $now, array(
            'nom_pilote' => 'Dupont', 'num_licence' => 'FR-98765', 'commentaire' => 'RAS',
        ));
        $this->sub2 = $this->_make_submission($this->form_id, $now, array(
            'nom_pilote' => 'Martin', 'num_licence' => 'FR-11111', 'commentaire' => 'x',
        ));

        // --- Form with no identifier field ---
        $this->db->insert('forms', array(
            'code' => 'plain_cols_test_' . $ts, 'title' => 'Plain form test',
            'public_slug' => 'plain-cols-test-' . $ts, 'status' => 'published', 'club' => null,
        ));
        $this->plain_form_id = $this->db->insert_id();
        $this->db->insert('form_pages', array(
            'form_id' => $this->plain_form_id, 'page_number' => 1, 'title' => 'Page 1',
            'content_html' => '<label for="x">Champ libre</label><input type="text" id="x" name="x">',
        ));
        $this->_make_submission($this->plain_form_id, $now, array('x' => 'valeur'));
    }

    private function _make_submission($form_id, $now, array $values)
    {
        $this->db->insert('form_submissions', array(
            'form_id' => $form_id, 'submission_uuid' => uniqid('sub_ident_', true),
            'status' => 'submitted', 'submission_method' => 'online',
            'submitted_at' => $now, 'created_at' => $now, 'updated_at' => $now,
        ));
        $sid = $this->db->insert_id();
        foreach ($values as $name => $val) {
            $this->db->insert('form_submission_values', array(
                'submission_id' => $sid, 'field_name' => $name, 'value_text' => $val,
                'created_at' => $now, 'updated_at' => $now,
            ));
        }
        return $sid;
    }

    protected function tearDown(): void
    {
        foreach (array($this->form_id, $this->plain_form_id) as $fid) {
            $subs = $this->db->select('id')->where('form_id', $fid)->get('form_submissions')->result_array();
            foreach ($subs as $s) {
                $this->db->where('submission_id', (int) $s['id'])->delete('form_submission_values');
            }
            $this->db->where('form_id', $fid)->delete('form_submissions');
            $this->db->where('form_id', $fid)->delete('form_pages');
            $this->db->where('id', $fid)->delete('forms');
        }
    }

    // ---- model ----

    public function testGetIdentifierValuesReturnsPerFieldMapKeyedBySubmission()
    {
        $map = $this->model->get_identifier_values($this->form_id, array('nom_pilote', 'num_licence'));

        $this->assertArrayHasKey($this->sub1, $map);
        $this->assertSame('Dupont', $map[$this->sub1]['nom_pilote']);
        $this->assertSame('FR-98765', $map[$this->sub1]['num_licence']);
        $this->assertSame('Martin', $map[$this->sub2]['nom_pilote']);

        // 'commentaire' was not requested → not returned
        $this->assertArrayNotHasKey('commentaire', $map[$this->sub1]);
    }

    public function testGetIdentifierValuesEmptyFieldListReturnsEmpty()
    {
        $this->assertSame(array(), $this->model->get_identifier_values($this->form_id, array()));
    }

    // ---- HTTP render ----

    public function testSubmissionsListShowsOneColumnPerIdentifierField()
    {
        $cookie = $this->login_as_admin();
        $this->assertNotNull($cookie);

        $body = $this->http_get($this->base_url() . 'forms_admin/submissions/' . $this->form_id, $cookie)['body'];
        $this->assertNotNull($body);

        $this->assertStringContainsString('<th>Nom du pilote</th>', $body);
        $this->assertStringContainsString('<th>N° licence</th>', $body);
        // no merged column, no column for a non-identifier field
        $this->assertStringNotContainsString('<th>Identification</th>', $body);
        $this->assertStringNotContainsString('<th>Commentaire</th>', $body);
        // per-field values rendered
        $this->assertStringContainsString('Dupont', $body);
        $this->assertStringContainsString('FR-98765', $body);
        $this->assertStringContainsString('FR-11111', $body);
    }

    public function testSubmissionsListHasNoIdentifierColumnWhenFormHasNoIdentifierField()
    {
        $cookie = $this->login_as_admin();
        $this->assertNotNull($cookie);

        $body = $this->http_get($this->base_url() . 'forms_admin/submissions/' . $this->plain_form_id, $cookie)['body'];
        $this->assertNotNull($body);

        $this->assertStringContainsString('Réponses au formulaire', $body); // page rendered
        $this->assertStringNotContainsString('<th>Identification</th>', $body);
        $this->assertStringNotContainsString('<th>Champ libre</th>', $body);
    }

    // ---- harness ----

    private function base_url()
    {
        return 'http://gvv.net/index.php/';
    }

    private function login_as_admin()
    {
        $body = http_build_query(array('username' => 'testadmin', 'password' => 'password'));
        $context = stream_context_create(array('http' => array(
            'method' => 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $body, 'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 20,
        )));
        @file_get_contents($this->base_url() . 'auth/login', false, $context);
        $headers = isset($http_response_header) ? $http_response_header : array();
        foreach ($headers as $h) {
            if (stripos($h, 'Set-Cookie:') === 0 && stripos($h, 'ci_session=') !== false) {
                return explode(';', trim(substr($h, strlen('Set-Cookie:'))))[0];
            }
        }
        return null;
    }

    private function http_get($url, $cookie = null)
    {
        $context = stream_context_create(array('http' => array(
            'method' => 'GET', 'header' => "Cookie: " . ($cookie ?: '') . "\r\n",
            'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 20,
        )));
        $body = @file_get_contents($url, false, $context);
        return array('body' => $body, 'headers' => isset($http_response_header) ? $http_response_header : array());
    }
}
