<?php

use PHPUnit\Framework\TestCase;

/**
 * Filtre par période de soumission sur forms_admin/submissions/<id> :
 * Form_submissions_model::get_form_submissions() accepte des bornes
 * $date_from / $date_to (Y-m-d, inclusives) sur submitted_at, et le
 * contrôleur lit date_from/date_to en GET (dates mal formées ignorées).
 *
 * Harnais HTTP identique à FormsSubmissionEditTest.
 */
class FormsSubmissionsDateFilterTest extends TestCase
{
    /** @var RealDatabase */
    private $db;
    /** @var Form_submissions_model */
    private $model;
    private $form_id;
    private $ids = array();

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;
        $CI->load->model('form_submissions_model');
        $this->model = $CI->form_submissions_model;

        $ts = time() . '_' . rand(1000, 9999);
        $this->db->insert('forms', array(
            'code' => 'datefilter_test_' . $ts, 'title' => 'Date filter test',
            'public_slug' => 'datefilter-test-' . $ts, 'status' => 'published', 'club' => null,
        ));
        $this->form_id = $this->db->insert_id();
        $this->db->insert('form_pages', array(
            'form_id' => $this->form_id, 'page_number' => 1, 'title' => 'P1',
            'content_html' => '<input type="text" name="x">',
        ));

        // Trois soumissions à des dates franchement séparées.
        $this->ids['jan'] = $this->_submission('2021-01-10 09:00:00');
        $this->ids['jun'] = $this->_submission('2021-06-15 14:30:00');
        $this->ids['dec'] = $this->_submission('2021-12-28 18:45:00');
    }

    private function _submission($submitted_at)
    {
        $this->db->insert('form_submissions', array(
            'form_id' => $this->form_id, 'submission_uuid' => uniqid('sub_df_', true),
            'status' => 'submitted', 'submission_method' => 'online',
            'submitted_at' => $submitted_at,
            'created_at' => $submitted_at, 'updated_at' => $submitted_at,
        ));
        return (int) $this->db->insert_id();
    }

    protected function tearDown(): void
    {
        $this->db->where('form_id', $this->form_id)->delete('form_submissions');
        $this->db->where('form_id', $this->form_id)->delete('form_pages');
        $this->db->where('id', $this->form_id)->delete('forms');
    }

    private function ids_of($rows)
    {
        return array_map('intval', array_column($rows, 'id'));
    }

    // ---- modèle ----

    public function testNoBoundsReturnsEverything()
    {
        $rows = $this->model->get_form_submissions($this->form_id, 200, 0, array());
        $this->assertCount(3, $rows);
    }

    public function testDateFromIsInclusiveLowerBound()
    {
        $rows = $this->model->get_form_submissions($this->form_id, 200, 0, array(), '2021-06-15', null);
        $got = $this->ids_of($rows);
        $this->assertContains($this->ids['jun'], $got, 'la borne basse est inclusive (même jour)');
        $this->assertContains($this->ids['dec'], $got);
        $this->assertNotContains($this->ids['jan'], $got);
    }

    public function testDateToIsInclusiveUpperBound()
    {
        $rows = $this->model->get_form_submissions($this->form_id, 200, 0, array(), null, '2021-06-15');
        $got = $this->ids_of($rows);
        $this->assertContains($this->ids['jan'], $got);
        $this->assertContains($this->ids['jun'], $got, 'la borne haute est inclusive (même jour, heure quelconque)');
        $this->assertNotContains($this->ids['dec'], $got);
    }

    public function testBothBoundsNarrowToTheRange()
    {
        $rows = $this->model->get_form_submissions($this->form_id, 200, 0, array(), '2021-03-01', '2021-09-30');
        $this->assertSame(array($this->ids['jun']), $this->ids_of($rows));
    }

    public function testEmptyRangeReturnsNothing()
    {
        $rows = $this->model->get_form_submissions($this->form_id, 200, 0, array(), '2022-01-01', '2022-12-31');
        $this->assertSame(array(), $rows);
    }

    // ---- rendu HTTP ----

    public function testSubmissionsPageAppliesDateFilterFromGet()
    {
        $cookie = $this->login_as_admin();
        $this->assertNotNull($cookie);

        $base = 'http://gvv.net/index.php/forms_admin/submissions/' . $this->form_id;

        $all = $this->http_get($base, $cookie)['body'];
        $this->assertStringContainsString('2021-01-10', $all);
        $this->assertStringContainsString('2021-12-28', $all);

        $filtered = $this->http_get($base . '?date_from=2021-05-01&date_to=2021-07-01', $cookie)['body'];
        $this->assertStringContainsString('2021-06-15', $filtered);
        $this->assertStringNotContainsString('2021-01-10', $filtered);
        $this->assertStringNotContainsString('2021-12-28', $filtered);
        // le champ conserve la valeur soumise
        $this->assertStringContainsString('value="2021-05-01"', $filtered);
    }

    public function testMalformedDateIsIgnored()
    {
        $cookie = $this->login_as_admin();
        $body = $this->http_get(
            'http://gvv.net/index.php/forms_admin/submissions/' . $this->form_id . '?date_from=pas-une-date',
            $cookie
        )['body'];
        // pas de plantage, les 3 lignes restent visibles
        $this->assertStringContainsString('2021-01-10', $body);
        $this->assertStringContainsString('2021-12-28', $body);
    }

    // ---- harnais ----

    private function login_as_admin()
    {
        $body = http_build_query(array('username' => 'testadmin', 'password' => 'password'));
        $ctx = stream_context_create(array('http' => array(
            'method' => 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $body, 'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 20,
        )));
        @file_get_contents('http://gvv.net/index.php/auth/login', false, $ctx);
        foreach ((isset($http_response_header) ? $http_response_header : array()) as $h) {
            if (stripos($h, 'Set-Cookie:') === 0 && stripos($h, 'ci_session=') !== false) {
                return explode(';', trim(substr($h, strlen('Set-Cookie:'))))[0];
            }
        }
        return null;
    }

    private function http_get($url, $cookie = null)
    {
        $ctx = stream_context_create(array('http' => array(
            'method' => 'GET', 'header' => "Cookie: " . ($cookie ?: '') . "\r\n",
            'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 20,
        )));
        $body = @file_get_contents($url, false, $ctx);
        return array('body' => $body, 'headers' => isset($http_response_header) ? $http_response_header : array());
    }
}
