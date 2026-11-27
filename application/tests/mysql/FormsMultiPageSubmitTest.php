<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL/HTTP tests for the multi-page accumulation mechanism in
 * Forms_public::submit() (nav_action prev/next/finalize) — added when the
 * "Test en vol SPL" form (7 pages) exposed that "Page suivante"/"précédente"
 * used to be plain <a> links: navigating away from a page discarded
 * everything typed on it, since only the last page's POST ever reached
 * form_submissions. See doc/design_notes for the forms module.
 *
 * Exerce le vrai endpoint HTTP public sur le serveur de dev (http://gvv.net),
 * comme FormsUploadSubmitTest — ce contrôleur ne peut pas être appelé
 * directement en PHPUnit sans harnais HTTP. À la différence de
 * FormsUploadSubmitTest, cette suite doit conserver la session (cookie
 * ci_session) entre plusieurs requêtes successives pour exercer la
 * navigation multi-page — d'où le petit client HTTP avec cookie jar
 * ci-dessous, absent des autres tests forms_public existants.
 */
class FormsMultiPageSubmitTest extends TestCase
{
    /** @var RealDatabase */
    private $db;
    private $form_id;
    private $slug;
    private $created_submission_ids = array();

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;

        $ts = time() . '_' . rand(1000, 9999);
        $code = 'mp_test_' . $ts;
        $this->slug = 'mp-test-' . $ts;

        $this->db->insert('forms', array(
            'code'            => $code,
            'title'           => 'Multi-page test',
            'public_slug'     => $this->slug,
            'status'          => 'published',
            'required_params' => 'none',
        ));
        $this->form_id = $this->db->insert_id();

        // No file on disk for this code — Forms_public falls back to these
        // DB rows (Forms_file_storage::read_page() returns null when absent).
        $this->db->insert('form_pages', array(
            'form_id'      => $this->form_id,
            'page_number'  => 1,
            'title'        => 'Page 1',
            'content_html' => '<input type="text" name="f1" id="f1" required>',
        ));
        $this->db->insert('form_pages', array(
            'form_id'      => $this->form_id,
            'page_number'  => 2,
            'title'        => 'Page 2',
            'content_html' => '<input type="text" name="f2" id="f2" required>',
        ));
        $this->db->insert('form_pages', array(
            'form_id'      => $this->form_id,
            'page_number'  => 3,
            'title'        => 'Page 3',
            'content_html' => '<input type="text" name="f3" id="f3" required>',
        ));
    }

    protected function tearDown(): void
    {
        foreach ($this->created_submission_ids as $submission_id) {
            $this->db->where('submission_id', $submission_id)->delete('form_submission_values');
            $this->db->where('submission_id', $submission_id)->delete('form_submission_files');
            $this->db->where('id', $submission_id)->delete('form_submissions');
        }
        $this->db->where('form_id', $this->form_id)->delete('form_submissions');
        $this->db->where('form_id', $this->form_id)->delete('form_pages');
        $this->db->where('id', $this->form_id)->delete('forms');
    }

    /**
     * Requête avec un jeu de cookies conservé d'une requête à l'autre, comme
     * un navigateur (le jeton CSRF du test remplace celui posé par le serveur).
     */
    private function http_request($method, $url, array $post_fields, array &$cookies)
    {
        $pairs = array();
        foreach ($cookies as $name => $value) {
            if ($name !== 'gvv_csrf_cookie') {
                $pairs[] = $name . '=' . $value;
            }
        }
        $options = array('headers' => "User-Agent: GvvPhpUnitMultiPageTest/1.0\r\n");
        $response = ($method === 'POST')
            ? GvvTestHttp::post($url, $post_fields, implode('; ', $pairs), $options)
            : GvvTestHttp::get($url, implode('; ', $pairs), $options);
        $cookies = array_merge($cookies, $response['cookies']);
        return $response;
    }

    public function testNextThenFinalizeMergesAllThreePages()
    {
        $cookies = array();

        // Visit page 1 first so the session/user-agent handshake is in place.
        $this->http_request('GET', GvvTestHttp::base_url() . 'forms/' . $this->slug, array(), $cookies);

        $r1 = $this->http_request('POST', GvvTestHttp::base_url() . 'forms/submit/' . $this->slug, array(
            'page_number' => 1, 'nav_action' => 'next', 'f1' => 'valeur-page-1',
        ), $cookies);
        $this->assertNotNull($r1['location'], 'La page 1 doit rediriger vers la page 2.');
        $this->assertStringContainsString('page=2', $r1['location']);

        $r2 = $this->http_request('POST', GvvTestHttp::base_url() . 'forms/submit/' . $this->slug, array(
            'page_number' => 2, 'nav_action' => 'next', 'f2' => 'valeur-page-2',
        ), $cookies);
        $this->assertNotNull($r2['location'], 'La page 2 doit rediriger vers la page 3.');
        $this->assertStringContainsString('page=3', $r2['location']);

        $before = (int) $this->db->where('form_id', $this->form_id)->count_all_results('form_submissions');

        $r3 = $this->http_request('POST', GvvTestHttp::base_url() . 'forms/submit/' . $this->slug, array(
            'page_number' => 3, 'nav_action' => 'finalize', 'f3' => 'valeur-page-3',
        ), $cookies);

        $after = (int) $this->db->where('form_id', $this->form_id)->count_all_results('form_submissions');
        $this->assertSame($before + 1, $after, 'La finalisation doit créer exactement une soumission.');

        $submission = $this->db->where('form_id', $this->form_id)->order_by('id', 'DESC')->limit(1)
            ->get('form_submissions')->row_array();
        $this->assertNotEmpty($submission);
        $this->created_submission_ids[] = $submission['id'];

        $values = $this->db->where('submission_id', $submission['id'])->get('form_submission_values')->result_array();
        $by_name = array();
        foreach ($values as $v) {
            $by_name[$v['field_name']] = $v['value_text'];
        }

        $this->assertSame('valeur-page-1', isset($by_name['f1']) ? $by_name['f1'] : null,
            'Le champ de la page 1 doit avoir été conservé jusqu\'à la finalisation.');
        $this->assertSame('valeur-page-2', isset($by_name['f2']) ? $by_name['f2'] : null,
            'Le champ de la page 2 doit avoir été conservé jusqu\'à la finalisation.');
        $this->assertSame('valeur-page-3', isset($by_name['f3']) ? $by_name['f3'] : null);
    }

    public function testPrevSkipsValidationOfTheCurrentPage()
    {
        $cookies = array();
        $this->http_request('GET', GvvTestHttp::base_url() . 'forms/' . $this->slug . '?page=2', array(), $cookies);

        // Leave the required f2 field empty and click "page précédente".
        $r = $this->http_request('POST', GvvTestHttp::base_url() . 'forms/submit/' . $this->slug, array(
            'page_number' => 2, 'nav_action' => 'prev',
        ), $cookies);

        $this->assertNotNull($r['location'], 'Le bouton "précédent" doit rediriger sans être bloqué par la validation.');
        $this->assertStringContainsString('page=1', $r['location']);
    }

    public function testFinalizeDirectlyOnLastPageWithoutVisitingEarlierPagesFailsValidation()
    {
        $cookies = array();
        // Jump straight to the last page — no page_values_store accumulated for pages 1/2.
        $this->http_request('GET', GvvTestHttp::base_url() . 'forms/' . $this->slug . '?page=3', array(), $cookies);

        $before = (int) $this->db->where('form_id', $this->form_id)->count_all_results('form_submissions');

        $r = $this->http_request('POST', GvvTestHttp::base_url() . 'forms/submit/' . $this->slug, array(
            'page_number' => 3, 'nav_action' => 'finalize', 'f3' => 'valeur-page-3',
        ), $cookies);

        $after = (int) $this->db->where('form_id', $this->form_id)->count_all_results('form_submissions');
        $this->assertSame($before, $after, 'f1/f2 manquants (jamais visités) doivent bloquer la finalisation.');
        $this->assertNotNull($r['location']);
        $this->assertStringContainsString('page=1', $r['location'], 'En cas d\'échec, on renvoie vers la page 1 pour compléter.');
    }
}
