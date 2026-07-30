<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit Tests — DX_Auth::is_password() guard against a failed lookup.
 *
 * get_user_by_username() runs `$this->db->get($this->_table)`, which returns
 * FALSE (not a result object) when the query fails — e.g. the `users` table
 * is missing, as happened repeatedly during a botched /admin/do_restore.
 * Calling ->result_array() on that FALSE used to be an uncaught fatal error
 * on the login path. is_password() must now treat it as "not the password"
 * instead of crashing.
 */
class DXAuthIsPasswordTest extends TestCase
{
    private $dx_auth;

    protected function setUp(): void
    {
        require_once APPPATH . 'libraries/DX_Auth.php';
        $reflection = new ReflectionClass('DX_Auth');
        $this->dx_auth = $reflection->newInstanceWithoutConstructor();

        $fake_ci = new stdClass();
        $fake_ci->config = new FakeConfigForDXAuthTest();
        $fake_ci->load = new FakeLoaderForDXAuthTest();
        $fake_ci->users = new FakeUsersModelForDXAuthTest();
        $this->dx_auth->ci = $fake_ci;
    }

    public function test_returns_false_instead_of_crashing_when_lookup_query_fails()
    {
        $this->dx_auth->ci->users->result = FALSE; // simulates $this->db->get() failure

        $this->assertFalse($this->dx_auth->is_password('someuser', 'somepassword'));
    }

    public function test_returns_false_when_user_not_found()
    {
        $this->dx_auth->ci->users->result = new FakeDbResultForDXAuthTest(array());

        $this->assertFalse($this->dx_auth->is_password('someuser', 'somepassword'));
    }
}

class FakeConfigForDXAuthTest
{
    public function item($key)
    {
        return $key === 'DX_salt' ? 'test-salt' : '';
    }
}

class FakeLoaderForDXAuthTest
{
    public function model($path, $alias = '')
    {
    }

    public function helper($name)
    {
    }
}

class FakeUsersModelForDXAuthTest
{
    public $result;

    public function get_user_by_username($username)
    {
        return $this->result;
    }
}

class FakeDbResultForDXAuthTest
{
    private $rows;

    public function __construct($rows)
    {
        $this->rows = $rows;
    }

    public function result_array()
    {
        return $this->rows;
    }
}
