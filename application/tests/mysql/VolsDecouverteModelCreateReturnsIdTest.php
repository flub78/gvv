<?php

use PHPUnit\Framework\TestCase;

/**
 * Regression test — Vols_decouverte_model::create() must return the id of
 * the newly created row.
 *
 * Bug found while implementing Lot 4 (doc/plans/configuration_bons_vols_decouverte_plan.md) :
 * create() called parent::create($data) without returning its result, so
 * Gvv_Controller::formValidation() always received `null` as the new id.
 * This was silently harmless before (the base post_create() hook only logs),
 * but it broke the new post_create() override in vols_decouverte.php, which
 * needs the real id to generate and store the bon's PDF right after creation.
 *
 * @see application/models/vols_decouverte_model.php
 * @see application/controllers/vols_decouverte.php
 */
class VolsDecouverteModelCreateReturnsIdTest extends TestCase
{
    private $CI;
    private $model;
    private $createdId;

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        $this->CI->load->model('vols_decouverte_model');
        $this->model = $this->CI->vols_decouverte_model;
    }

    protected function tearDown(): void
    {
        if ($this->createdId) {
            $this->CI->db->delete('vols_decouverte', array('id' => $this->createdId));
        }
    }

    public function testCreateReturnsTheNewRowId()
    {
        $id = $this->model->create(array(
            'date_vente'  => date('Y-m-d'),
            'club'        => 1,
            'product'     => 'TEST_CREATE_RETURNS_ID',
            'beneficiaire' => 'PHPUnit Regression',
        ));

        $this->assertNotNull($id, 'create() must return the id of the newly created row, not null');
        $this->assertIsNumeric($id);
        $this->createdId = $id;

        $row = $this->model->get_by_id('id', $id);
        $this->assertNotEmpty($row, 'The row returned by create() id must actually exist');
        $this->assertEquals('PHPUnit Regression', $row['beneficiaire']);
    }
}
