<?php
/**
 * Test class for Vols_decouverte controller filtering functionality
 */

require_once(APPPATH . 'tests/CITestCase.php');

class Vols_decouverteTest extends CITestCase {

    public function setUp(): void {
        parent::setUp();
        $this->resetInstance();
        $this->CI = &get_instance();
        
        // Load necessary models and libraries
        $this->CI->load->model('vols_decouverte_model');
        $this->CI->load->library('session');
    }

    public function test_validate_date() {
        // Use reflection to access private method
        $controller = new Vols_decouverte();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('_validate_date');
        $method->setAccessible(true);
        
        // Test valid dates
        $this->assertEquals('2024-01-01', $method->invoke($controller, '2024-01-01'));
        $this->assertEquals('2024-12-31', $method->invoke($controller, '2024-12-31'));
        
        // Test invalid dates
        $this->assertEquals('', $method->invoke($controller, 'invalid-date'));
        $this->assertEquals('', $method->invoke($controller, '2024-13-01'));
        $this->assertEquals('', $method->invoke($controller, '2024-01-32'));
        $this->assertEquals('', $method->invoke($controller, ''));
    }

    public function test_validate_filter_type() {
        // Use reflection to access private method
        $controller = new Vols_decouverte();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('_validate_filter_type');
        $method->setAccessible(true);
        
        // Test valid filter types
        $this->assertEquals('all', $method->invoke($controller, 'all'));
        $this->assertEquals('done', $method->invoke($controller, 'done'));
        $this->assertEquals('todo', $method->invoke($controller, 'todo'));
        $this->assertEquals('cancelled', $method->invoke($controller, 'cancelled'));
        $this->assertEquals('expired', $method->invoke($controller, 'expired'));
        
        // Test invalid filter types
        $this->assertEquals('all', $method->invoke($controller, 'invalid'));
        $this->assertEquals('all', $method->invoke($controller, ''));
    }

    public function test_validate_year() {
        // Use reflection to access private method
        $controller = new Vols_decouverte();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('_validate_year');
        $method->setAccessible(true);
        
        $current_year = date('Y');
        
        // Test valid years
        $this->assertEquals(2024, $method->invoke($controller, '2024'));
        $this->assertEquals(2024, $method->invoke($controller, 2024));
        $this->assertEquals($current_year, $method->invoke($controller, $current_year));
        
        // Test invalid years
        $this->assertEquals($current_year, $method->invoke($controller, '1999'));
        $this->assertEquals($current_year, $method->invoke($controller, 'invalid'));
        $this->assertEquals($current_year, $method->invoke($controller, ''));
    }

    public function test_model_get_available_years() {
        $model = new Vols_decouverte_model();
        $years = $model->get_available_years();
        
        // Should be an array
        $this->assertTrue(is_array($years));
        
        // Should contain current year
        $current_year = date('Y');
        $this->assertArrayHasKey($current_year, $years);
        $this->assertEquals((string)$current_year, $years[$current_year]);
    }

    public function test_model_select_page_filters() {
        // This would require database setup with test data
        // For now, just verify the method exists and runs without fatal errors
        $model = new Vols_decouverte_model();
        
        // Test without filters
        $this->CI->session->set_userdata('vd_filter_active', false);
        $result = $model->select_page();
        $this->assertTrue(is_array($result));
        
        // Test with filter active but no specific filters
        $this->CI->session->set_userdata('vd_filter_active', true);
        $this->CI->session->set_userdata('vd_filter_type', 'all');
        $result = $model->select_page();
        $this->assertTrue(is_array($result));
    }
}
