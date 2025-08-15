<?php
class MonModelTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->CI =& get_instance();
        $this->CI->load->model('achats_model');

    }
    
    public function test_ma_fonction() {
        $result = $this->CI->achats_model->select_raw();

        $this->assertEquals('attendu', 'attendu');
        $this->assertNotNull($result);
    }
}