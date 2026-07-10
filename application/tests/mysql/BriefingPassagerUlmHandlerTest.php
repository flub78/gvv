<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL tests for BriefingPassagerUlmHandler (Lot 6, étape 6.4).
 */
class BriefingPassagerUlmHandlerTest extends TestCase
{
    /** @var RealDatabase */
    private $db;
    private $form_id;
    private $page_id;
    private $field_ids = array();
    private $vld_id;
    private $submission_ids = array();

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;
        $CI->load->model('form_submissions_model');
        $CI->load->model('vols_decouverte_model');

        require_once APPPATH . 'libraries/form_handlers/GvvFormHandlerInterface.php';
        require_once APPPATH . 'libraries/form_handlers/BriefingPassagerUlmHandler.php';

        $suffix = uniqid();

        $this->db->insert('forms', array(
            'code'        => 'briefing_ulm_test_' . $suffix,
            'title'       => 'Briefing ULM test',
            'public_slug' => 'briefing-ulm-test-' . $suffix,
            'status'      => 'published',
        ));
        $this->form_id = $this->db->insert_id();

        $this->db->insert('form_pages', array(
            'form_id'     => $this->form_id,
            'page_number' => 1,
        ));
        $this->page_id = $this->db->insert_id();

        $field_names = array('nom', 'prenom', 'poids_declare', 'personne_a_prevenir', 'telephone', 'date_vol');
        foreach ($field_names as $i => $name) {
            $this->db->insert('form_fields', array(
                'form_id'    => $this->form_id,
                'page_id'    => $this->page_id,
                'name'       => $name,
                'label'      => $name,
                'field_type' => $name === 'date_vol' ? 'date' : 'text',
                'sort_order' => $i,
            ));
            $this->field_ids[$name] = $this->db->insert_id();
        }

        $this->db->insert('vols_decouverte', array(
            'date_vente'  => date('Y-m-d'),
            'club'        => 1,
            'product'     => 'ulm',
            'saisie_par'  => 'test',
            'beneficiaire' => 'Ancien Nom',
            'urgence'      => 'Ancien contact',
        ));
        $this->vld_id = $this->db->insert_id();
    }

    protected function tearDown(): void
    {
        foreach ($this->submission_ids as $submission_id) {
            $this->db->where('submission_id', $submission_id)->delete('form_submission_values');
            $this->db->where('id', $submission_id)->delete('form_submissions');
        }
        if ($this->vld_id) {
            $this->db->where('id', $this->vld_id)->delete('vols_decouverte');
        }
        if ($this->form_id) {
            $this->db->where('form_id', $this->form_id)->delete('form_fields');
            $this->db->where('form_id', $this->form_id)->delete('form_pages');
            $this->db->where('id', $this->form_id)->delete('forms');
        }
    }

    private function createSubmission(array $values_by_name, $subject_id)
    {
        $CI = &get_instance();
        $values_by_field = array();
        foreach ($values_by_name as $name => $value) {
            $values_by_field[$this->field_ids[$name]] = $value;
        }

        $submission_id = $CI->form_submissions_model->create_submission(array(
            'form_id'      => $this->form_id,
            'status'       => 'submitted',
            'subject_type' => 'vols_decouverte',
            'subject_id'   => $subject_id,
            'values'       => $values_by_field,
        ));
        $this->submission_ids[] = $submission_id;

        return $submission_id;
    }

    public function testAfterSubmitUpdatesVolDecouverteFromSubmittedValues()
    {
        $submission_id = $this->createSubmission(array(
            'nom'                  => 'Dupont',
            'prenom'               => 'Jean',
            'poids_declare'        => '82',
            'personne_a_prevenir'  => 'Marie Dupont',
            'telephone'            => '0600000000',
            'date_vol'             => '2026-08-01',
        ), $this->vld_id);

        $handler = new BriefingPassagerUlmHandler();
        $result = $handler->after_submit($submission_id, 'vols_decouverte', $this->vld_id);

        $this->assertNull($result['error']);
        $this->assertNull($result['redirect_url']);

        $vld = $this->db->where('id', $this->vld_id)->get('vols_decouverte')->row_array();
        $this->assertSame('Dupont Jean', $vld['beneficiaire']);
        $this->assertSame('82', $vld['participation']);
        $this->assertSame('Marie Dupont', $vld['urgence']);
        $this->assertSame('0600000000', $vld['beneficiaire_tel']);
        $this->assertSame('2026-08-01', $vld['date_vol']);
    }

    public function testAfterSubmitWithWrongSubjectTypeLogsErrorWithoutCrashing()
    {
        $submission_id = $this->createSubmission(array('nom' => 'Dupont'), $this->vld_id);

        $handler = new BriefingPassagerUlmHandler();
        $result = $handler->after_submit($submission_id, 'membres', $this->vld_id);

        $this->assertNotEmpty($result['error']);

        $vld = $this->db->where('id', $this->vld_id)->get('vols_decouverte')->row_array();
        $this->assertSame('Ancien Nom', $vld['beneficiaire']);
    }

    public function testAfterSubmitWithMissingVldLogsErrorWithoutCrashing()
    {
        $submission_id = $this->createSubmission(array('nom' => 'Dupont'), $this->vld_id);

        $handler = new BriefingPassagerUlmHandler();
        $result = $handler->after_submit($submission_id, 'vols_decouverte', 999999999);

        $this->assertNotEmpty($result['error']);
    }
}
