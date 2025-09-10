<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration test for Configuration_model with real database access
 * 
 * NOTE: This test requires:
 * 1. Full CodeIgniter framework loaded
 * 2. Test database connection
 * 3. Test data in configuration table
 * 
 * This is currently in disabled/ because it requires full CI framework
 */
class ConfigurationModelIntegrationTest extends TestCase
{
    private $CI;
    private $configuration_model;
    
    public function setUp(): void
    {
        // This would require full CodeIgniter bootstrap
        // $this->CI = &get_instance();
        // $this->configuration_model = $this->CI->load->model('configuration_model');
        
        $this->markTestSkipped('Integration tests require full CI framework - moved to disabled/');
    }
    
    /**
     * Test actual database operations with real Configuration_model
     */
    public function testRealDatabaseAccess()
    {
        // These would be REAL database tests:
        
        // Test get_param() method with real database
        // $result = $this->configuration_model->get_param('app_name');
        // $this->assertNotNull($result);
        
        // Test image() method with real database
        // $result = $this->configuration_model->image('1');
        // $this->assertStringContainsString('app_name', $result);
        
        // Test select_page() method with real database  
        // $result = $this->configuration_model->select_page(10, 0);
        // $this->assertIsArray($result);
        // $this->assertGreaterThan(0, count($result));
        
        // Test create/update/delete operations
        // $test_data = [
        //     'cle' => 'test_key_' . time(),
        //     'valeur' => 'test_value',
        //     'description' => 'Test configuration',
        //     'lang' => 'fr',
        //     'club' => null
        // ];
        // 
        // $id = $this->configuration_model->save($test_data);
        // $this->assertGreaterThan(0, $id);
        // 
        // $retrieved = $this->configuration_model->get_by_id('id', $id);
        // $this->assertEquals('test_value', $retrieved['valeur']);
        // 
        // $this->configuration_model->delete($id);
        // $deleted = $this->configuration_model->get_by_id('id', $id);
        // $this->assertEmpty($deleted);
    }
    
    /**
     * Test language and club priority logic with real data
     */
    public function testRealLanguageAndClubPriority()
    {
        // This would test the actual get_param() logic with real database:
        
        // Insert test configurations with different priorities
        // $base_key = 'test_priority_' . time();
        // 
        // $configs = [
        //     ['cle' => $base_key, 'valeur' => 'global', 'lang' => null, 'club' => null],
        //     ['cle' => $base_key, 'valeur' => 'french', 'lang' => 'fr', 'club' => null],
        //     ['cle' => $base_key, 'valeur' => 'club1', 'lang' => null, 'club' => '1'],
        //     ['cle' => $base_key, 'valeur' => 'french_club1', 'lang' => 'fr', 'club' => '1']
        // ];
        // 
        // foreach ($configs as $config) {
        //     $this->configuration_model->save($config);
        // }
        // 
        // // Test priority resolution
        // $result = $this->configuration_model->get_param($base_key, 'fr'); // with club 1
        // $this->assertEquals('french_club1', $result);
        // 
        // // Cleanup
        // $this->db->where('cle', $base_key);
        // $this->db->delete('configuration');
    }
}

?>
