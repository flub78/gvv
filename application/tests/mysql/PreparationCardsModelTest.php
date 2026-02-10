<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL integration tests for Preparation_cards_model
 *
 * Tests CRUD operations, ordering, and visibility filtering
 * with real database operations.
 *
 * @package tests
 * @see application/models/preparation_cards_model.php
 */
class PreparationCardsModelTest extends TestCase
{
    protected $CI;
    protected $model;

    protected function setUp(): void
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->model('preparation_cards_model');
        $this->model = $this->CI->preparation_cards_model;

        $this->cleanupTestData();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestData();
    }

    protected function cleanupTestData()
    {
        $this->CI->db->query("DELETE FROM preparation_cards WHERE title LIKE 'TEST_%'");
    }

    public function testCreateCard_InsertsRecord()
    {
        $data = array(
            'title' => 'TEST_Card_1',
            'type' => 'html',
            'html_fragment' => '<div>Test</div>',
            'display_order' => 1,
            'visible' => 1
        );

        $id = $this->model->create($data);
        $this->assertGreaterThan(0, $id);

        $record = $this->model->get_by_id('id', $id);
        $this->assertEquals('TEST_Card_1', $record['title']);
        $this->assertEquals('html', $record['type']);
        $this->assertEquals(1, (int)$record['visible']);
    }

    public function testSelectPage_OrdersByDisplayOrder()
    {
        $id1 = $this->model->create(array(
            'title' => 'TEST_Card_A',
            'type' => 'html',
            'display_order' => 2,
            'visible' => 1
        ));
        $id2 = $this->model->create(array(
            'title' => 'TEST_Card_B',
            'type' => 'link',
            'display_order' => 1,
            'visible' => 1
        ));

        $this->assertGreaterThan(0, $id1);
        $this->assertGreaterThan(0, $id2);

        $rows = $this->model->select_page(0, 0, array());
        $test_rows = array_values(array_filter($rows, function ($row) {
            return strpos($row['title'], 'TEST_') === 0;
        }));

        $this->assertGreaterThanOrEqual(2, count($test_rows));
        $this->assertTrue($test_rows[0]['display_order'] <= $test_rows[1]['display_order'],
            'Rows should be ordered by display_order ascending');
    }

    public function testSelectPage_FiltersVisible()
    {
        $this->model->create(array(
            'title' => 'TEST_Visible',
            'type' => 'html',
            'display_order' => 1,
            'visible' => 1
        ));
        $this->model->create(array(
            'title' => 'TEST_Hidden',
            'type' => 'html',
            'display_order' => 2,
            'visible' => 0
        ));

        $visible = $this->model->select_page(0, 0, array('visible' => 1));
        foreach ($visible as $row) {
            if (strpos($row['title'], 'TEST_') === 0) {
                $this->assertEquals(1, (int)$row['visible']);
            }
        }
    }

    public function testUpdate_ReordersDisplayOrder()
    {
        $id1 = $this->model->create(array(
            'title' => 'TEST_Order_1',
            'type' => 'html',
            'display_order' => 1,
            'visible' => 1
        ));
        $id2 = $this->model->create(array(
            'title' => 'TEST_Order_2',
            'type' => 'html',
            'display_order' => 2,
            'visible' => 1
        ));

        $this->model->update('id', array('id' => $id2, 'display_order' => 1), $id2);

        $row2 = $this->model->get_by_id('id', $id2);
        $this->assertEquals(1, (int)$row2['display_order']);

        $row1 = $this->model->get_by_id('id', $id1);
        $this->assertGreaterThanOrEqual(1, (int)$row1['display_order']);
    }

    public function testDelete_RenumbersDisplayOrder()
    {
        $id1 = $this->model->create(array(
            'title' => 'TEST_Delete_1',
            'type' => 'html',
            'display_order' => 1,
            'visible' => 1
        ));
        $id2 = $this->model->create(array(
            'title' => 'TEST_Delete_2',
            'type' => 'html',
            'display_order' => 2,
            'visible' => 1
        ));

        $this->model->delete(array('id' => $id1));

        $row2 = $this->model->get_by_id('id', $id2);
        $this->assertEquals(1, (int)$row2['display_order']);
    }
}

/* End of file PreparationCardsModelTest.php */
/* Location: ./application/tests/mysql/PreparationCardsModelTest.php */
