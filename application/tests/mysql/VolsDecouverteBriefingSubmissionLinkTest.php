<?php

use PHPUnit\Framework\TestCase;

/**
 * Vérifie que vols_decouverte_model::select_page() expose briefing_submission_id
 * et briefing_form_id pour la dernière soumission form_submissions du formulaire
 * briefing-passager-ulm. Ces colonnes sont utilisées par MetaData::action()
 * (cas 'briefing_vd') pour lier l'icône directement vers
 * forms_admin/submission_pdf/{form_id}/{submission_id} plutôt que vers
 * briefing_passager/upload/{vld_id} quand une soumission en ligne existe.
 *
 * @see application/models/vols_decouverte_model.php
 * @see application/libraries/MetaData.php
 */
class VolsDecouverteBriefingSubmissionLinkTest extends TestCase
{
    protected $CI;
    protected $model;
    protected $vld_id;
    protected $submission_id;
    protected $briefing_form_id;

    protected function setUp(): void
    {
        $this->CI =& get_instance();
        $this->CI->load->model('vols_decouverte_model');
        $this->model = $this->CI->vols_decouverte_model;

        $this->CI->session->set_userdata('section', 1);
        $this->CI->session->set_userdata('vd_year', date('Y'));
        $this->CI->session->set_userdata('vd_filter_active', false);

        $row = $this->CI->db->query(
            "SELECT id FROM forms WHERE public_slug = 'briefing-passager-ulm' LIMIT 1"
        )->row_array();
        if (empty($row)) {
            $this->markTestSkipped('Formulaire briefing-passager-ulm introuvable.');
        }
        $this->briefing_form_id = (int) $row['id'];
    }

    protected function tearDown(): void
    {
        if ($this->submission_id) {
            $this->CI->db->delete('form_submissions', array('id' => $this->submission_id));
        }
        if ($this->vld_id) {
            $this->CI->db->delete('vols_decouverte', array('id' => $this->vld_id));
        }
        $this->CI->session->unset_userdata(array('vd_year', 'vd_filter_active'));
    }

    private function createVld()
    {
        $this->CI->db->insert('vols_decouverte', array(
            'date_vente' => date('Y-m-d'),
            'club'       => 1,
            'product'    => 'TEST_BRIEFING_LINK',
            'saisie_par' => 'phpunit',
            'cancelled'  => 0,
        ));
        $this->vld_id = (int) $this->CI->db->insert_id();
    }

    private function createSubmission()
    {
        $this->CI->db->insert('form_submissions', array(
            'form_id'         => $this->briefing_form_id,
            'submission_uuid' => uniqid('phpunit_', true),
            'status'          => 'submitted',
            'subject_type'    => 'vols_decouverte',
            'subject_id'      => $this->vld_id,
            'created_at'      => date('Y-m-d H:i:s'),
        ));
        $this->submission_id = (int) $this->CI->db->insert_id();
    }

    private function findRow()
    {
        foreach ($this->model->select_page() as $row) {
            if ((int) $row['id'] === $this->vld_id) {
                return $row;
            }
        }
        return null;
    }

    public function testSelectPageExposesBriefingSubmissionAndFormIdWhenSubmissionExists()
    {
        $this->createVld();
        $this->createSubmission();

        $row = $this->findRow();
        $this->assertNotNull($row, 'Le VLD doit être présent dans select_page().');
        $this->assertEquals(1, (int) $row['has_briefing']);
        $this->assertEquals($this->submission_id, (int) $row['briefing_submission_id']);
        $this->assertEquals($this->briefing_form_id, (int) $row['briefing_form_id']);
    }

    public function testSelectPageLeavesBriefingSubmissionIdNullWithoutSubmission()
    {
        $this->createVld();

        $row = $this->findRow();
        $this->assertNotNull($row, 'Le VLD doit être présent dans select_page().');
        $this->assertEquals(0, (int) $row['has_briefing']);
        $this->assertNull($row['briefing_submission_id']);
        $this->assertNull($row['briefing_form_id']);
    }
}

/* End of file VolsDecouverteBriefingSubmissionLinkTest.php */
/* Location: ./application/tests/mysql/VolsDecouverteBriefingSubmissionLinkTest.php */
