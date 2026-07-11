<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL tests for Forms_model handler_class persistence (forms_admin UI field).
 */
class FormsModelHandlerClassTest extends TestCase
{
    /** @var RealDatabase */
    private $db;
    private $form_id;

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;
        $CI->load->model('forms_model');
    }

    protected function tearDown(): void
    {
        if ($this->form_id) {
            $this->db->where('id', $this->form_id)->delete('forms');
        }
    }

    public function testCreateFormPersistsHandlerClass()
    {
        $CI = &get_instance();
        $suffix = uniqid();

        $this->form_id = $CI->forms_model->create_form(array(
            'code'          => 'handler_test_' . $suffix,
            'title'         => 'Handler test',
            'public_slug'   => 'handler-test-' . $suffix,
            'handler_class' => 'BriefingPassagerUlmHandler',
        ));

        $row = $this->db->where('id', $this->form_id)->get('forms')->row_array();
        $this->assertSame('BriefingPassagerUlmHandler', $row['handler_class']);
    }

    public function testCreateFormWithoutHandlerClassDefaultsToNull()
    {
        $CI = &get_instance();
        $suffix = uniqid();

        $this->form_id = $CI->forms_model->create_form(array(
            'code'        => 'handler_test_' . $suffix,
            'title'       => 'Handler test',
            'public_slug' => 'handler-test-' . $suffix,
        ));

        $row = $this->db->where('id', $this->form_id)->get('forms')->row_array();
        $this->assertNull($row['handler_class']);
    }

    public function testUpdateFormSetsAndClearsHandlerClass()
    {
        $CI = &get_instance();
        $suffix = uniqid();

        $this->form_id = $CI->forms_model->create_form(array(
            'code'        => 'handler_test_' . $suffix,
            'title'       => 'Handler test',
            'public_slug' => 'handler-test-' . $suffix,
        ));

        $CI->forms_model->update_form($this->form_id, array(
            'handler_class' => 'BriefingPassagerUlmHandler',
        ));
        $row = $this->db->where('id', $this->form_id)->get('forms')->row_array();
        $this->assertSame('BriefingPassagerUlmHandler', $row['handler_class']);

        $CI->forms_model->update_form($this->form_id, array(
            'handler_class' => '',
        ));
        $row = $this->db->where('id', $this->form_id)->get('forms')->row_array();
        $this->assertNull($row['handler_class']);
    }

    public function testUpdateFormWithoutHandlerClassKeyKeepsCurrentValue()
    {
        $CI = &get_instance();
        $suffix = uniqid();

        $this->form_id = $CI->forms_model->create_form(array(
            'code'          => 'handler_test_' . $suffix,
            'title'         => 'Handler test',
            'public_slug'   => 'handler-test-' . $suffix,
            'handler_class' => 'BriefingPassagerUlmHandler',
        ));

        $CI->forms_model->update_form($this->form_id, array(
            'title' => 'Handler test renamed',
        ));

        $row = $this->db->where('id', $this->form_id)->get('forms')->row_array();
        $this->assertSame('BriefingPassagerUlmHandler', $row['handler_class']);
    }
}
