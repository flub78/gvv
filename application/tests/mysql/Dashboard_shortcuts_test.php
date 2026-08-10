<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit Tests — Migration 167 (dashboard_shortcuts) et Dashboard_shortcuts_model.
 *
 * Vérifie la création de la table, le CRUD du modèle, et le filtrage
 * (dashboard, club_id global/section, actif, rôle requis) de get_for_dashboard().
 */
class Dashboard_shortcuts_test extends TestCase {

    private $db;

    protected function setUp(): void {
        $CI = &get_instance();
        $this->db = $CI->db;

        if (!class_exists('CI_Migration')) {
            require_once BASEPATH . 'libraries/Migration.php';
        }
        require_once APPPATH . 'migrations/167_dashboard_shortcuts.php';

        if ($this->tableExists()) {
            $this->db->query("DROP TABLE IF EXISTS `dashboard_shortcuts`");
        }
    }

    protected function tearDown(): void {
        if ($this->tableExists()) {
            $this->db->query("DELETE FROM dashboard_shortcuts WHERE title LIKE 'test_%'");
        }
    }

    public static function tearDownAfterClass(): void {
        $CI = &get_instance();
        $CI->db->query("DROP TABLE IF EXISTS `dashboard_shortcuts`");
    }

    private function tableExists(): bool {
        $q = $this->db->query("SHOW TABLES LIKE 'dashboard_shortcuts'");
        return $q->num_rows() > 0;
    }

    private function loadModel() {
        $CI = &get_instance();
        if (!$this->tableExists()) {
            (new Migration_Dashboard_shortcuts())->up();
        }
        $CI->load->model('dashboard_shortcuts_model');
        return $CI->dashboard_shortcuts_model;
    }

    // -------------------------------------------------------------------------
    // Migration
    // -------------------------------------------------------------------------

    public function test_migration_up_creates_table(): void {
        $this->assertFalse($this->tableExists(), 'La table ne doit pas exister avant up()');
        (new Migration_Dashboard_shortcuts())->up();
        $this->assertTrue($this->tableExists(), 'La table doit exister après up()');
    }

    public function test_migration_up_creates_required_columns(): void {
        (new Migration_Dashboard_shortcuts())->up();
        $q = $this->db->query("DESCRIBE dashboard_shortcuts");
        $cols = array_column($q->result_array(), 'Field');

        foreach (['id', 'dashboard', 'section', 'title_key', 'title', 'description_key', 'description',
                  'url', 'icon', 'color', 'role_required', 'sort_order', 'active', 'club_id',
                  'created_at', 'updated_at', 'created_by', 'updated_by'] as $col) {
            $this->assertContains($col, $cols, "La colonne $col doit exister");
        }
    }

    public function test_migration_down_drops_table(): void {
        (new Migration_Dashboard_shortcuts())->up();
        $this->assertTrue($this->tableExists());
        (new Migration_Dashboard_shortcuts())->down();
        $this->assertFalse($this->tableExists(), 'La table ne doit plus exister après down()');
    }

    // -------------------------------------------------------------------------
    // Modèle — CRUD
    // -------------------------------------------------------------------------

    public function test_model_create_and_get(): void {
        $model = $this->loadModel();
        $id = $model->create(array(
            'dashboard' => 'formation',
            'title'     => 'test_create',
            'url'       => 'forms_admin/generate/attestation-de-formation-ulm',
        ), 'test');

        $this->assertNotFalse($id, 'create() doit retourner un id');
        $row = $model->get_by_id($id);
        $this->assertEquals('test_create', $row['title']);
        $this->assertEquals('formation', $row['dashboard']);
        $this->assertNull($row['club_id']);
        $this->assertEquals(1, (int) $row['active']);
    }

    public function test_model_update(): void {
        $model = $this->loadModel();
        $id = $model->create(array(
            'dashboard' => 'formation',
            'title'     => 'test_update_avant',
            'url'       => 'forms_admin',
        ), 'test');

        $model->update($id, array(
            'dashboard' => 'formation',
            'title'     => 'test_update_apres',
            'url'       => 'forms_admin',
            'active'    => 0,
        ), 'test');

        $row = $model->get_by_id($id);
        $this->assertEquals('test_update_apres', $row['title']);
        $this->assertEquals(0, (int) $row['active']);
    }

    public function test_model_delete(): void {
        $model = $this->loadModel();
        $id = $model->create(array(
            'dashboard' => 'formation',
            'title'     => 'test_delete',
            'url'       => 'forms_admin',
        ), 'test');

        $model->delete($id);
        $this->assertFalse($model->get_by_id($id), 'La ligne ne doit plus exister après delete()');
    }

    public function test_model_toggle_active(): void {
        $model = $this->loadModel();
        $id = $model->create(array(
            'dashboard' => 'formation',
            'title'     => 'test_toggle',
            'url'       => 'forms_admin',
            'active'    => 1,
        ), 'test');

        $model->toggle_active($id);
        $this->assertEquals(0, (int) $model->get_by_id($id)['active']);

        $model->toggle_active($id);
        $this->assertEquals(1, (int) $model->get_by_id($id)['active']);
    }

    // -------------------------------------------------------------------------
    // Modèle — get_for_dashboard
    // -------------------------------------------------------------------------

    public function test_get_for_dashboard_filters_by_dashboard(): void {
        $model = $this->loadModel();
        $model->create(array('dashboard' => 'formation', 'title' => 'test_formation', 'url' => 'x'), 'test');
        $model->create(array('dashboard' => 'flights',   'title' => 'test_flights',   'url' => 'x'), 'test');

        $results = $model->get_for_dashboard('formation', null);
        $titles = array_column($results, 'title');
        $this->assertContains('test_formation', $titles);
        $this->assertNotContains('test_flights', $titles);
    }

    public function test_get_for_dashboard_filters_by_active(): void {
        $model = $this->loadModel();
        $model->create(array('dashboard' => 'formation', 'title' => 'test_active_yes', 'url' => 'x', 'active' => 1), 'test');
        $model->create(array('dashboard' => 'formation', 'title' => 'test_active_no',  'url' => 'x', 'active' => 0), 'test');

        $titles = array_column($model->get_for_dashboard('formation', null), 'title');
        $this->assertContains('test_active_yes', $titles);
        $this->assertNotContains('test_active_no', $titles);
    }

    public function test_get_for_dashboard_global_and_section_scope(): void {
        $model = $this->loadModel();
        $fake_section = 9977;
        $model->create(array('dashboard' => 'formation', 'title' => 'test_global',  'url' => 'x', 'club_id' => null), 'test');
        $model->create(array('dashboard' => 'formation', 'title' => 'test_section', 'url' => 'x', 'club_id' => $fake_section), 'test');

        // Sans section active : seuls les raccourcis globaux
        $titles_no_section = array_column($model->get_for_dashboard('formation', null), 'title');
        $this->assertContains('test_global', $titles_no_section);
        $this->assertNotContains('test_section', $titles_no_section);

        // Avec la section active : globaux + section
        $titles_with_section = array_column($model->get_for_dashboard('formation', $fake_section), 'title');
        $this->assertContains('test_global', $titles_with_section);
        $this->assertContains('test_section', $titles_with_section);

        // Avec une autre section active : globaux seulement, pas ceux d'une autre section
        $titles_other_section = array_column($model->get_for_dashboard('formation', 123456), 'title');
        $this->assertContains('test_global', $titles_other_section);
        $this->assertNotContains('test_section', $titles_other_section);
    }

    public function test_get_for_dashboard_filters_by_role_required(): void {
        $model = $this->loadModel();
        $model->create(array('dashboard' => 'formation', 'title' => 'test_no_role', 'url' => 'x', 'role_required' => null), 'test');
        $model->create(array('dashboard' => 'formation', 'title' => 'test_role_ca', 'url' => 'x', 'role_required' => 'ca'), 'test');

        // Contexte PHPUnit CLI : personne n'est loggé, has_role() est donc toujours faux.
        $titles = array_column($model->get_for_dashboard('formation', null), 'title');
        $this->assertContains('test_no_role', $titles, 'Un raccourci sans rôle requis doit toujours apparaître');
        $this->assertNotContains('test_role_ca', $titles, 'Un raccourci avec rôle requis non satisfait ne doit pas apparaître');
    }

    public function test_get_for_dashboard_orders_by_section_then_sort_order(): void {
        $model = $this->loadModel();
        $model->create(array('dashboard' => 'formation', 'title' => 'test_order_b', 'url' => 'x', 'section' => 'A', 'sort_order' => 2), 'test');
        $model->create(array('dashboard' => 'formation', 'title' => 'test_order_a', 'url' => 'x', 'section' => 'A', 'sort_order' => 1), 'test');

        $titles = array_column($model->get_for_dashboard('formation', null), 'title');
        $pos_a = array_search('test_order_a', $titles);
        $pos_b = array_search('test_order_b', $titles);
        $this->assertNotFalse($pos_a);
        $this->assertNotFalse($pos_b);
        $this->assertLessThan($pos_b, $pos_a, 'sort_order=1 doit précéder sort_order=2');
    }
}
