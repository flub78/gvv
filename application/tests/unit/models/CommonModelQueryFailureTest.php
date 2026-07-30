<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit Tests — Common_Model::get_first() / get_by_id() guard against a
 * failed lookup.
 *
 * $this->db->get() returns FALSE (not a result object) when the underlying
 * query fails — e.g. the table doesn't exist, as happened repeatedly during
 * a botched /admin/do_restore. Calling ->row_array() on that FALSE used to be
 * an uncaught fatal error (this is what produced the very first crash of
 * that incident, on the dashboard's motd_user_prefs lookup). Both methods
 * must now log the error and return an empty array instead of crashing.
 */
class CommonModelQueryFailureTest extends TestCase
{
    private $model;
    private $original_db;

    protected function setUp(): void
    {
        require_once APPPATH . 'models/common_model.php';

        $reflection = new ReflectionClass('Common_Model');
        $this->model = $reflection->newInstanceWithoutConstructor();
        $this->model->table = 'test_table';

        global $CI;
        $this->original_db = $CI->db;
        $CI->db = new FakeDbForCommonModelTest();
    }

    protected function tearDown(): void
    {
        global $CI;
        $CI->db = $this->original_db;
    }

    public function test_get_first_returns_empty_array_when_query_fails()
    {
        global $CI;
        $CI->db->get_result = FALSE;

        $this->assertSame(array(), $this->model->get_first(array('id' => 1)));
    }

    public function test_get_first_returns_row_on_success()
    {
        global $CI;
        $CI->db->get_result = new FakeDbResultForCommonModelTest(array('id' => 1, 'name' => 'x'));

        $this->assertSame(array('id' => 1, 'name' => 'x'), $this->model->get_first(array('id' => 1)));
    }

    public function test_get_by_id_returns_empty_array_when_query_fails()
    {
        global $CI;
        $CI->db->get_result = FALSE;

        $this->assertSame(array(), $this->model->get_by_id('id', 1));
    }

    public function test_get_by_id_returns_row_on_success()
    {
        global $CI;
        $CI->db->get_result = new FakeDbResultForCommonModelTest(array('id' => 1, 'name' => 'x'));

        $this->assertSame(array('id' => 1, 'name' => 'x'), $this->model->get_by_id('id', 1));
    }
}

class FakeDbForCommonModelTest
{
    public $get_result;

    public function select($select)
    {
        return $this;
    }

    public function from($table)
    {
        return $this;
    }

    public function where($key, $value = null)
    {
        return $this;
    }

    public function limit($limit, $offset = 0)
    {
        return $this;
    }

    public function get($table = '', $limit = null, $offset = null)
    {
        return $this->get_result;
    }

    public function last_query()
    {
        return '';
    }

    public function _error_message()
    {
        return 'simulated failure';
    }
}

class FakeDbResultForCommonModelTest
{
    private $row;

    public function __construct($row)
    {
        $this->row = $row;
    }

    public function row_array($n = 0)
    {
        return $this->row;
    }
}
