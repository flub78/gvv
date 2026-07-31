<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL tests for migration 145 (Lot 12 — export d'une réponse vers un
 * formulaire de création GVV) and the associated
 * Form_submissions_model::get_export_query_params()/build_export_url().
 */
class FormsExportTargetMigrationTest extends TestCase
{
    /** @var RealDatabase */
    private $db;

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;

        if (!class_exists('CI_Migration')) {
            require_once BASEPATH . 'libraries/Migration.php';
        }
        require_once APPPATH . 'migrations/145_forms_export_target.php';
    }

    private function columnExists($table, $column)
    {
        $t = $this->db->escape_str($table);
        $c = $this->db->escape_str($column);
        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND COLUMN_NAME = '$c'"
        )->row_array();

        return isset($row['cnt']) && (int) $row['cnt'] > 0;
    }

    private function runMigrationUp()
    {
        $migration = new Migration_Forms_export_target();
        $this->assertTrue($migration->up(), 'Migration 145 up() should succeed');
    }

    public function testMigration145AddsExpectedColumns()
    {
        $this->runMigrationUp();

        $this->assertTrue($this->columnExists('forms', 'target_url'));
        $this->assertTrue($this->columnExists('forms', 'target_label'));
    }

    public function testMigration145UpIsIdempotent()
    {
        $this->runMigrationUp();
        $this->runMigrationUp();

        $this->assertTrue($this->columnExists('forms', 'target_url'));
        $this->assertTrue($this->columnExists('forms', 'target_label'));
    }

    public function testMigration145DefaultsAndDownRoundtrip()
    {
        $this->runMigrationUp();

        $this->db->insert('forms', array(
            'code'        => 'mig145_test_' . time(),
            'title'       => 'Migration 145 test',
            'public_slug' => 'mig145-test-' . time(),
            'status'      => 'draft',
        ));
        $form_id = $this->db->insert_id();
        $form = $this->db->where('id', $form_id)->get('forms')->row_array();
        $this->assertNull($form['target_url']);
        $this->assertNull($form['target_label']);

        $this->db->where('id', $form_id)->delete('forms');

        $migration = new Migration_Forms_export_target();
        $this->assertTrue($migration->down(), 'Migration 145 down() should succeed');
        $this->assertFalse($this->columnExists('forms', 'target_url'));
        $this->assertFalse($this->columnExists('forms', 'target_label'));

        // Restore expected state for the rest of the suite / the application.
        $this->runMigrationUp();
        $this->assertTrue($this->columnExists('forms', 'target_url'));
    }

    public function testGetExportQueryParamsExcludesFileSignatureSubformAndMultiValued()
    {
        $this->runMigrationUp();

        $CI = &get_instance();
        $CI->load->model('form_submissions_model');
        $CI->load->model('form_fields_model');

        $this->db->insert('forms', array(
            'code'        => 'mig145_export_test_' . time(),
            'title'       => 'Migration 145 export test',
            'public_slug' => 'mig145-export-test-' . time(),
            'status'      => 'published',
        ));
        $form_id = $this->db->insert_id();

        $this->db->insert('form_pages', array('form_id' => $form_id, 'page_number' => 1));
        $page_id = $this->db->insert_id();

        $field_nom = $CI->form_fields_model->create_field(array(
            'form_id' => $form_id, 'page_id' => $page_id, 'sort_order' => 1,
            'name' => 'nom', 'label' => 'Nom', 'field_type' => 'text',
        ));
        $field_file = $CI->form_fields_model->create_field(array(
            'form_id' => $form_id, 'page_id' => $page_id, 'sort_order' => 2,
            'name' => 'scan', 'label' => 'Scan', 'field_type' => 'file',
        ));
        $field_multi = $CI->form_fields_model->create_field(array(
            'form_id' => $form_id, 'page_id' => $page_id, 'sort_order' => 3,
            'name' => 'options', 'label' => 'Options', 'field_type' => 'select',
        ));

        $submission_id = $CI->form_submissions_model->create_submission(array(
            'form_id' => $form_id,
            'status'  => 'submitted',
            'values'  => array(
                $field_nom   => 'Dupont',
                $field_file  => 'scan.pdf',
                $field_multi => array('a', 'b'),
            ),
        ));

        $params = $CI->form_submissions_model->get_export_query_params($submission_id);
        $this->assertSame(array('nom' => 'Dupont'), $params);

        $url = $CI->form_submissions_model->build_export_url('membre/create', $submission_id);
        $this->assertStringContainsString('membre/create', $url);
        $this->assertStringContainsString('nom=Dupont', $url);
        $this->assertStringNotContainsString('scan=', $url);
        $this->assertStringNotContainsString('options=', $url);

        $this->db->where('submission_id', $submission_id)->delete('form_submission_values');
        $this->db->where('form_id', $form_id)->delete('form_submissions');
        $this->db->where('page_id', $page_id)->delete('form_fields');
        $this->db->where('id', $page_id)->delete('form_pages');
        $this->db->where('id', $form_id)->delete('forms');
    }

    public function testBuildExportUrlReturnsTargetUnchangedWhenNoParams()
    {
        $this->runMigrationUp();

        $CI = &get_instance();
        $CI->load->model('form_submissions_model');

        $this->db->insert('forms', array(
            'code'        => 'mig145_empty_test_' . time(),
            'title'       => 'Migration 145 empty test',
            'public_slug' => 'mig145-empty-test-' . time(),
            'status'      => 'published',
        ));
        $form_id = $this->db->insert_id();

        $submission_id = $CI->form_submissions_model->create_submission(array(
            'form_id' => $form_id,
            'status'  => 'submitted',
        ));

        $url = $CI->form_submissions_model->build_export_url('membre/create', $submission_id);
        $this->assertStringEndsWith('membre/create', rtrim($url, '/'));
        $this->assertStringNotContainsString('?', $url);

        $this->db->where('form_id', $form_id)->delete('form_submissions');
        $this->db->where('id', $form_id)->delete('forms');
    }
}
