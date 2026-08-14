<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL tests for Acceptance_item_roles_model (role x section targeting).
 */
class AcceptanceItemRolesModelTest extends TestCase
{
    /** @var RealDatabase */
    private $db;
    private $model;
    private $item_id;
    private $login;
    private $role_ids = array();
    private $section_ids = array();

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;
        $CI->load->model('acceptance_item_roles_model');
        $this->model = $CI->acceptance_item_roles_model;

        $login = $this->db->query("SELECT mlogin FROM membres LIMIT 1")->row_array();
        $this->assertNotNull($login, 'Au moins un membre est requis pour ce test');
        $this->login = $login['mlogin'];

        $roles = $this->db->query("SELECT id FROM types_roles LIMIT 2")->result_array();
        $this->assertGreaterThanOrEqual(1, count($roles), 'Au moins un role est requis pour ce test');
        foreach ($roles as $r) {
            $this->role_ids[] = $r['id'];
        }

        $sections = $this->db->query("SELECT id FROM sections LIMIT 1")->result_array();
        foreach ($sections as $s) {
            $this->section_ids[] = $s['id'];
        }

        $this->db->insert('acceptance_items', array(
            'title'       => 'AcceptanceItemRolesModelTest ' . time(),
            'category'    => 'document',
            'target_type' => 'internal',
            'created_by'  => $this->login,
            'created_at'  => date('Y-m-d H:i:s'),
        ));
        $this->item_id = $this->db->insert_id();
    }

    protected function tearDown(): void
    {
        if ($this->item_id) {
            $this->db->where('id', $this->item_id)->delete('acceptance_items');
        }
    }

    public function testReplaceForItemInsertsRows()
    {
        $role_id = $this->role_ids[0];
        $values = array($role_id . '_0');

        $this->model->replace_for_item($this->item_id, $values, $this->login);

        $rows = $this->model->get_for_item($this->item_id);
        $this->assertCount(1, $rows);
        $this->assertEquals($role_id, $rows[0]['types_roles_id']);
        $this->assertNull($rows[0]['section_id']);
        $this->assertEquals($this->login, $rows[0]['created_by']);
    }

    public function testReplaceForItemWithSectionSpecificRole()
    {
        if (empty($this->section_ids)) {
            $this->markTestSkipped('Aucune section disponible pour ce test');
        }

        $role_id = $this->role_ids[0];
        $section_id = $this->section_ids[0];
        $values = array($role_id . '_' . $section_id);

        $this->model->replace_for_item($this->item_id, $values, $this->login);

        $rows = $this->model->get_for_item($this->item_id);
        $this->assertCount(1, $rows);
        $this->assertEquals($section_id, $rows[0]['section_id']);

        $map = $this->model->get_checked_map($this->item_id);
        $this->assertArrayHasKey($role_id . '_' . $section_id, $map);
    }

    public function testReplaceForItemIsFullReplace()
    {
        $role_id = $this->role_ids[0];

        $this->model->replace_for_item($this->item_id, array($role_id . '_0'), $this->login);
        $this->assertCount(1, $this->model->get_for_item($this->item_id));

        // A second call with an empty set must clear all previous targeting rows.
        $this->model->replace_for_item($this->item_id, array(), $this->login);
        $this->assertCount(0, $this->model->get_for_item($this->item_id));
    }
}
