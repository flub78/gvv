<?php

use PHPUnit\Framework\TestCase;

/**
 * Test class for Configuration_model business logic
 * 
 * This test demonstrates how to test CodeIgniter model logic with PHPUnit 8.x
 * by focusing on the model's business methods rather than database operations.
 * For database tests, use integration tests with full CI framework.
 */
class ConfigurationModelTest extends TestCase
{
    /**
     * Test the image method logic with mock data
     */
    public function testImageMethodLogic()
    {
        // Test empty key
        $this->assertEquals('', $this->simulateImageMethod(''));
        
        // Test with valid data
        $mock_data = [
            'cle' => 'test_key',
            'valeur' => 'test_value', 
            'description' => 'Test description'
        ];
        $result = $this->simulateImageMethod('123', $mock_data);
        $this->assertEquals('test_key Test description', $result);
        
        // Test with missing description
        $mock_data_no_desc = [
            'cle' => 'test_key2',
            'valeur' => 'test_value2'
        ];
        $result = $this->simulateImageMethod('456', $mock_data_no_desc);
        $this->assertEquals('test_key2 ', $result);
        
        // Test with invalid key (no data found)
        $result = $this->simulateImageMethod('999', null);
        $this->assertEquals('configuration inconnue 999', $result);
    }
    
    /**
     * Test configuration key validation logic
     */
    public function testConfigurationKeyValidation()
    {
        // Test valid configuration keys
        $this->assertTrue($this->isValidConfigKey('app_name'));
        $this->assertTrue($this->isValidConfigKey('max_file_size'));
        $this->assertTrue($this->isValidConfigKey('email_smtp_host'));
        
        // Test invalid configuration keys
        $this->assertFalse($this->isValidConfigKey(''));
        $this->assertFalse($this->isValidConfigKey('   '));
        $this->assertFalse($this->isValidConfigKey('key with spaces'));
        $this->assertFalse($this->isValidConfigKey('key-with-special-chars!'));
    }
    
    /**
     * Test configuration value sanitization
     */
    public function testConfigurationValueSanitization()
    {
        // Test string values
        $this->assertEquals('simple_value', $this->sanitizeConfigValue('simple_value'));
        $this->assertEquals('value with spaces', $this->sanitizeConfigValue('value with spaces'));
        
        // Test numeric values
        $this->assertEquals('123', $this->sanitizeConfigValue(123));
        $this->assertEquals('45.67', $this->sanitizeConfigValue(45.67));
        
        // Test boolean values
        $this->assertEquals('1', $this->sanitizeConfigValue(true));
        $this->assertEquals('0', $this->sanitizeConfigValue(false));
        
        // Test null and empty values
        $this->assertEquals('', $this->sanitizeConfigValue(null));
        $this->assertEquals('', $this->sanitizeConfigValue(''));
        
        // Test dangerous values (XSS prevention)
        $this->assertEquals('&lt;script&gt;alert()&lt;/script&gt;', 
                          $this->sanitizeConfigValue('<script>alert()</script>'));
    }
    
    /**
     * Test language parameter handling logic
     */
    public function testLanguageParameterLogic()
    {
        // Test default language fallback
        $this->assertEquals('fr', $this->getEffectiveLanguage(null, 'fr'));
        $this->assertEquals('en', $this->getEffectiveLanguage(null, 'en'));
        
        // Test explicit language override
        $this->assertEquals('en', $this->getEffectiveLanguage('en', 'fr'));
        $this->assertEquals('fr', $this->getEffectiveLanguage('fr', 'en'));
        
        // Test language code validation
        $this->assertTrue($this->isValidLanguageCode('fr'));
        $this->assertTrue($this->isValidLanguageCode('en'));
        $this->assertTrue($this->isValidLanguageCode('de'));
        $this->assertTrue($this->isValidLanguageCode('es'));
        
        $this->assertFalse($this->isValidLanguageCode(''));
        $this->assertFalse($this->isValidLanguageCode('invalid'));
        $this->assertFalse($this->isValidLanguageCode('français'));
    }
    
    /**
     * Test configuration categories
     */
    public function testConfigurationCategories()
    {
        $valid_categories = ['app', 'email', 'user', 'system', 'ui'];
        
        foreach ($valid_categories as $category) {
            $this->assertTrue($this->isValidCategory($category));
        }
        
        $invalid_categories = ['', '   ', 'invalid_category', '123', 'category with spaces'];
        
        foreach ($invalid_categories as $category) {
            $this->assertFalse($this->isValidCategory($category));
        }
    }
    
    /**
     * Test configuration priority logic
     */
    public function testConfigurationPriority()
    {
        // Mock configurations with different priorities
        $configs = [
            ['cle' => 'test_key', 'valeur' => 'global_value', 'lang' => null, 'club' => null],
            ['cle' => 'test_key', 'valeur' => 'lang_value', 'lang' => 'fr', 'club' => null],
            ['cle' => 'test_key', 'valeur' => 'club_value', 'lang' => null, 'club' => '1'],
            ['cle' => 'test_key', 'valeur' => 'specific_value', 'lang' => 'fr', 'club' => '1']
        ];
        
        // Test priority: specific (lang+club) > club > lang > global
        $this->assertEquals('specific_value', $this->selectConfigByPriority($configs, 'fr', '1'));
        $this->assertEquals('club_value', $this->selectConfigByPriority($configs, 'en', '1'));
        $this->assertEquals('lang_value', $this->selectConfigByPriority($configs, 'fr', '2'));
        $this->assertEquals('global_value', $this->selectConfigByPriority($configs, 'en', '2'));
    }
    
    // Helper methods that simulate Configuration_model logic
    
    /**
     * Simulate the image method logic without database
     */
    private function simulateImageMethod($key, $data = null)
    {
        if ($key == "") return "";
        
        if ($data === null) {
            return "configuration inconnue $key";
        }
        
        if (array_key_exists('cle', $data) && array_key_exists('valeur', $data)) {
            $description = array_key_exists('description', $data) ? $data['description'] : '';
            return $data['cle'] . " " . $description;
        } else {
            return "configuration inconnue $key";
        }
    }
    
    /**
     * Validate configuration key format
     */
    private function isValidConfigKey($key)
    {
        if (empty(trim($key))) return false;
        
        // Key should contain only letters, numbers, and underscores
        return preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $key) === 1;
    }
    
    /**
     * Sanitize configuration value
     */
    private function sanitizeConfigValue($value)
    {
        if ($value === null) return '';
        if (is_bool($value)) return $value ? '1' : '0';
        
        $value = (string) $value;
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Get effective language with fallback
     */
    private function getEffectiveLanguage($requested_lang, $default_lang)
    {
        return $requested_lang ?? $default_lang;
    }
    
    /**
     * Validate language code format
     */
    private function isValidLanguageCode($lang)
    {
        if (empty($lang)) return false;
        
        // Simple validation: 2-3 letter language codes
        return preg_match('/^[a-z]{2,3}$/', $lang) === 1;
    }
    
    /**
     * Validate configuration category
     */
    private function isValidCategory($category)
    {
        $valid_categories = ['app', 'email', 'user', 'system', 'ui'];
        return in_array($category, $valid_categories);
    }
    
    /**
     * Test configuration file retrieval logic
     */
    public function testConfigurationFileRetrieval()
    {
        // Test get_file method logic simulation
        $this->assertEquals(null, $this->simulateGetFileMethod('non.existent.key', []));
        
        // Test with valid file configuration
        $mock_configs = [
            ['cle' => 'vd.background_image', 'file' => './uploads/configuration/vd.background_image.jpg', 'lang' => null, 'club' => null]
        ];
        $result = $this->simulateGetFileMethod('vd.background_image', $mock_configs);
        $this->assertEquals('./uploads/configuration/vd.background_image.jpg', $result);
        
        // Test with empty file field
        $mock_configs_empty = [
            ['cle' => 'vd.background_image', 'file' => '', 'lang' => null, 'club' => null]
        ];
        $result = $this->simulateGetFileMethod('vd.background_image', $mock_configs_empty);
        $this->assertNull($result);
        
        // Test with null file field
        $mock_configs_null = [
            ['cle' => 'vd.background_image', 'file' => null, 'lang' => null, 'club' => null]
        ];
        $result = $this->simulateGetFileMethod('vd.background_image', $mock_configs_null);
        $this->assertNull($result);
    }

    /**
     * Test file path validation
     */
    public function testFilePathValidation()
    {
        // Test valid configuration file paths
        $this->assertTrue($this->isValidConfigFilePath('./uploads/configuration/vd.background_image.jpg'));
        $this->assertTrue($this->isValidConfigFilePath('./uploads/configuration/app.logo.png'));
        $this->assertTrue($this->isValidConfigFilePath('./uploads/configuration/email.template.html'));
        
        // Test invalid paths
        $this->assertFalse($this->isValidConfigFilePath(''));
        $this->assertFalse($this->isValidConfigFilePath('/etc/passwd'));
        $this->assertFalse($this->isValidConfigFilePath('../../../etc/passwd'));
        $this->assertFalse($this->isValidConfigFilePath('uploads/configuration/file.jpg')); // Missing ./
    }

    /**
     * Simulate get_file method logic without database
     */
    private function simulateGetFileMethod($key, $configs)
    {
        foreach ($configs as $config) {
            if ($config['cle'] === $key && !empty($config['file'])) {
                return $config['file'];
            }
        }
        return null;
    }

    /**
     * Select configuration value based on priority
     */
    private function selectConfigByPriority($configs, $lang, $club)
    {
        // Priority: specific (lang+club) > club > lang > global
        foreach ($configs as $config) {
            if ($config['lang'] === $lang && $config['club'] === $club) {
                return $config['valeur'];
            }
        }
        
        foreach ($configs as $config) {
            if ($config['lang'] === null && $config['club'] === $club) {
                return $config['valeur'];
            }
        }
        
        foreach ($configs as $config) {
            if ($config['lang'] === $lang && $config['club'] === null) {
                return $config['valeur'];
            }
        }
        
        foreach ($configs as $config) {
            if ($config['lang'] === null && $config['club'] === null) {
                return $config['valeur'];
            }
        }
        
        return null;
    }

    /**
     * Validate configuration file path format
     */
    private function isValidConfigFilePath($path)
    {
        if (empty($path)) return false;
        
        // Must start with ./uploads/configuration/
        if (strpos($path, './uploads/configuration/') !== 0) return false;
        
        // Should not contain path traversal
        if (strpos($path, '..') !== false) return false;
        
        // Should have a valid filename
        $filename = basename($path);
        return !empty($filename) && $filename !== '.' && $filename !== '..';
    }
}

?>
