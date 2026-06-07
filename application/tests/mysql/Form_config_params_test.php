<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit Tests — Migration 124 (form_config_params) et Form_config_params_model.
 *
 * Vérifie la création de la table, la ligne par défaut organisme_formation,
 * le CRUD du modèle, la résolution avec priorité section > global, et le rollback.
 */
class Form_config_params_test extends TestCase {

    private $db;

    protected function setUp(): void {
        $CI = &get_instance();
        $this->db = $CI->db;

        if (!class_exists('CI_Migration')) {
            require_once BASEPATH . 'libraries/Migration.php';
        }
        require_once APPPATH . 'migrations/124_form_config_params.php';

        // État propre avant chaque test
        if ($this->tableExists()) {
            $this->db->query("DROP TABLE IF EXISTS `form_config_params`");
        }
    }

    protected function tearDown(): void {
        if ($this->tableExists()) {
            $this->db->query("DELETE FROM form_config_params WHERE param_key LIKE 'test_%'");
        }
    }

    public static function tearDownAfterClass(): void {
        $CI = &get_instance();
        $CI->db->query("DROP TABLE IF EXISTS `form_config_params`");
    }

    private function tableExists(): bool {
        $q = $this->db->query("SHOW TABLES LIKE 'form_config_params'");
        return $q->num_rows() > 0;
    }

    private function loadModel() {
        $CI = &get_instance();
        if (!$this->tableExists()) {
            (new Migration_Form_config_params())->up();
        }
        $CI->load->model('form_config_params_model');
        return $CI->form_config_params_model;
    }

    // -------------------------------------------------------------------------
    // Migration
    // -------------------------------------------------------------------------

    public function test_migration_up_creates_table(): void {
        $this->assertFalse($this->tableExists(), 'La table ne doit pas exister avant up()');
        (new Migration_Form_config_params())->up();
        $this->assertTrue($this->tableExists(), 'La table doit exister après up()');
    }

    public function test_migration_up_creates_required_columns(): void {
        (new Migration_Form_config_params())->up();
        $q = $this->db->query("DESCRIBE form_config_params");
        $cols = array_column($q->result_array(), 'Field');

        foreach (['id', 'club_id', 'param_key', 'param_value', 'param_label',
                  'param_description', 'created_at', 'updated_at', 'created_by', 'updated_by'] as $col) {
            $this->assertContains($col, $cols, "La colonne $col doit exister");
        }
    }

    public function test_migration_up_inserts_default_param(): void {
        (new Migration_Form_config_params())->up();
        $row = $this->db
            ->where('param_key', 'organisme_formation')
            ->where('club_id IS NULL', null, false)
            ->get('form_config_params')
            ->row_array();

        $this->assertNotEmpty($row, 'Le paramètre organisme_formation doit être inséré');
        $this->assertEquals('Organisme de formation', $row['param_label']);
    }

    public function test_migration_down_drops_table(): void {
        (new Migration_Form_config_params())->up();
        $this->assertTrue($this->tableExists());
        (new Migration_Form_config_params())->down();
        $this->assertFalse($this->tableExists(), 'La table ne doit plus exister après down()');
    }

    // -------------------------------------------------------------------------
    // Modèle — CRUD
    // -------------------------------------------------------------------------

    public function test_model_create_and_get(): void {
        $model = $this->loadModel();
        $id = $model->create(array(
            'club_id'     => null,
            'param_key'   => 'test_create',
            'param_label' => 'Test création',
            'param_value' => 'val_globale',
        ), 'test');

        $this->assertNotFalse($id, 'create() doit retourner un id');
        $row = $model->get_by_id($id);
        $this->assertEquals('test_create', $row['param_key']);
        $this->assertEquals('val_globale', $row['param_value']);
        $this->assertNull($row['club_id']);
    }

    public function test_model_update(): void {
        $model = $this->loadModel();
        $id = $model->create(array(
            'club_id'     => null,
            'param_key'   => 'test_update',
            'param_label' => 'Avant',
            'param_value' => 'ancienne_val',
        ), 'test');

        $model->update($id, array(
            'club_id'     => null,
            'param_label' => 'Après',
            'param_value' => 'nouvelle_val',
        ), 'test');

        $row = $model->get_by_id($id);
        $this->assertEquals('Après', $row['param_label']);
        $this->assertEquals('nouvelle_val', $row['param_value']);
    }

    public function test_model_delete(): void {
        $model = $this->loadModel();
        $id = $model->create(array(
            'club_id'     => null,
            'param_key'   => 'test_delete',
            'param_label' => 'À supprimer',
            'param_value' => '',
        ), 'test');

        $model->delete($id);
        $this->assertFalse($model->get_by_id($id), 'La ligne ne doit plus exister après delete()');
    }

    public function test_model_key_exists(): void {
        $model = $this->loadModel();
        $model->create(array(
            'club_id'     => null,
            'param_key'   => 'test_key_exists',
            'param_label' => 'Unicité',
            'param_value' => '',
        ), 'test');

        $this->assertTrue($model->key_exists('test_key_exists', null));
        $this->assertFalse($model->key_exists('test_key_absent', null));
    }

    // -------------------------------------------------------------------------
    // Modèle — Résolution
    // -------------------------------------------------------------------------

    public function test_model_resolve_global_fallback(): void {
        $model = $this->loadModel();
        $model->create(array(
            'club_id'     => null,
            'param_key'   => 'test_resolve',
            'param_label' => 'Résolution',
            'param_value' => 'val_globale',
        ), 'test');

        $this->assertEquals('val_globale', $model->resolve('test_resolve'));
        $this->assertEquals('val_globale', $model->resolve('test_resolve', 999));
    }

    public function test_model_resolve_section_overrides_global(): void {
        $model = $this->loadModel();
        $model->create(array(
            'club_id'     => null,
            'param_key'   => 'test_override',
            'param_label' => 'Global',
            'param_value' => 'val_globale',
        ), 'test');
        $fake_section = 9988;
        $model->create(array(
            'club_id'     => $fake_section,
            'param_key'   => 'test_override',
            'param_label' => 'Section',
            'param_value' => 'val_section',
        ), 'test');

        $this->assertEquals('val_section', $model->resolve('test_override', $fake_section));
        $this->assertEquals('val_globale', $model->resolve('test_override', null));
    }

    public function test_model_resolve_returns_null_when_missing(): void {
        $model = $this->loadModel();
        $this->assertNull($model->resolve('parametre_inexistant'));
    }
}
