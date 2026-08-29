<?php

use PHPUnit\Framework\TestCase;

/**
 * Exports CSV et PDF de la liste des réponses (forms_admin/submissions_csv|pdf/<id>) :
 * mêmes colonnes que la liste écran (ID, une colonne par champ identifiant,
 * "Soumis par", date), même filtre de période (date_from / date_to en GET),
 * sans le plafond de 200 lignes.
 *
 * Harnais HTTP identique à FormsSubmissionEditTest.
 */
class FormsSubmissionsExportTest extends TestCase
{
    /** @var RealDatabase */
    private $db;
    private $form_id;

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;

        $ts = time() . '_' . rand(1000, 9999);
        $this->db->insert('forms', array(
            'code' => 'export_test_' . $ts, 'title' => 'Export test',
            'public_slug' => 'export-test-' . $ts, 'status' => 'published', 'club' => null,
        ));
        $this->form_id = $this->db->insert_id();
        $this->db->insert('form_pages', array(
            'form_id' => $this->form_id, 'page_number' => 1, 'title' => 'P1',
            'content_html' => '<label for="pilote">Nom du pilote</label>'
                . '<input type="text" id="pilote" name="pilote" data-gvv-identifier>',
        ));

        $this->_submission('2021-02-01 10:00:00', 'Alice Martin', 'alice@example.org', 'Alice Martin');
        $this->_submission('2021-11-20 16:00:00', 'Bob Durand',   'bob@example.org',   'Bob Durand');
    }

    private function _submission($submitted_at, $submitter_name, $submitter_email, $pilote_value)
    {
        $this->db->insert('form_submissions', array(
            'form_id' => $this->form_id, 'submission_uuid' => uniqid('sub_exp_', true),
            'status' => 'submitted', 'submission_method' => 'online',
            'submitter_name' => $submitter_name, 'submitter_email' => $submitter_email,
            'submitted_at' => $submitted_at, 'created_at' => $submitted_at, 'updated_at' => $submitted_at,
        ));
        $sid = (int) $this->db->insert_id();
        $this->db->insert('form_submission_values', array(
            'submission_id' => $sid, 'field_name' => 'pilote', 'value_text' => $pilote_value,
            'created_at' => $submitted_at, 'updated_at' => $submitted_at,
        ));
        return $sid;
    }

    protected function tearDown(): void
    {
        $subs = $this->db->select('id')->where('form_id', $this->form_id)->get('form_submissions')->result_array();
        foreach ($subs as $s) {
            $this->db->where('submission_id', (int) $s['id'])->delete('form_submission_values');
        }
        $this->db->where('form_id', $this->form_id)->delete('form_submissions');
        $this->db->where('form_id', $this->form_id)->delete('form_pages');
        $this->db->where('id', $this->form_id)->delete('forms');
    }

    private function base()
    {
        return 'http://gvv.net/index.php/forms_admin/';
    }

    // ---- CSV ----

    public function testCsvExportHasHeaderAndAllRows()
    {
        $cookie = $this->login();
        $r = $this->http_get($this->base() . 'submissions_csv/' . $this->form_id, $cookie);

        $this->assertStringContainsString('text/x-comma-separated-values', $this->header($r, 'Content-Type'));
        $body = $r['body'];
        // en-tête : ID + libellé du champ identifiant + Soumis par + Date
        $this->assertStringContainsString('ID;Nom du pilote;Soumis par;Date;', $body);
        // lignes de données
        $this->assertStringContainsString('Alice Martin;Alice Martin <alice@example.org>;2021-02-01 10:00:00', $body);
        $this->assertStringContainsString('Bob Durand;Bob Durand <bob@example.org>;2021-11-20 16:00:00', $body);
    }

    public function testCsvExportRespectsDateFilter()
    {
        $cookie = $this->login();
        $body = $this->http_get(
            $this->base() . 'submissions_csv/' . $this->form_id . '?date_from=2021-01-01&date_to=2021-06-30',
            $cookie
        )['body'];

        $this->assertStringContainsString('Alice Martin', $body);
        $this->assertStringNotContainsString('Bob Durand', $body);
    }

    // ---- PDF ----

    public function testPdfExportReturnsPdfDocument()
    {
        $cookie = $this->login();
        $r = $this->http_get($this->base() . 'submissions_pdf/' . $this->form_id, $cookie);

        $this->assertStringContainsString('application/pdf', $this->header($r, 'Content-Type'));
        $this->assertStringStartsWith('%PDF', (string) $r['body']);
        $this->assertGreaterThan(1000, strlen((string) $r['body']));
    }

    // ---- harnais ----

    private function login()
    {
        $ctx = stream_context_create(array('http' => array(
            'method' => 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query(array('username' => 'testadmin', 'password' => 'password')),
            'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 20,
        )));
        @file_get_contents('http://gvv.net/index.php/auth/login', false, $ctx);
        foreach ((isset($http_response_header) ? $http_response_header : array()) as $h) {
            if (stripos($h, 'Set-Cookie:') === 0 && stripos($h, 'ci_session=') !== false) {
                return explode(';', trim(substr($h, strlen('Set-Cookie:'))))[0];
            }
        }
        return null;
    }

    private function http_get($url, $cookie)
    {
        $ctx = stream_context_create(array('http' => array(
            'method' => 'GET', 'header' => "Cookie: " . ($cookie ?: '') . "\r\n",
            'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 20,
        )));
        $body = @file_get_contents($url, false, $ctx);
        return array('body' => $body, 'headers' => isset($http_response_header) ? $http_response_header : array());
    }

    private function header($result, $name)
    {
        foreach ($result['headers'] as $h) {
            if (stripos($h, $name . ':') === 0) {
                return trim(substr($h, strlen($name) + 1));
            }
        }
        return '';
    }
}
