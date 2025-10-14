<?php

use PHPUnit\Framework\TestCase;

/**
 * Simple test for PayeurSelector fix
 * 
 * This test verifies that the payeur selector modification has been implemented
 * by checking that the implementation is correct in the code files.
 */
class BugFixPayeurSelectorTest extends TestCase
{
    /**
     * Test that vols_planeur controller has been modified correctly
     */
    public function testVolsPlaneurControllerModified()
    {
        $controller_content = file_get_contents(APPPATH . 'controllers/vols_planeur.php');
        
        // Check that comptes_model is loaded
        $this->assertTrue(
            strpos($controller_content, "load->model('comptes_model')") !== false,
            'vols_planeur controller should load comptes_model'
        );
            
        // Check that payeur_selector is created using comptes_model
        $this->assertTrue(
            strpos($controller_content, 'comptes_model->payeur_selector_with_null()') !== false,
            'vols_planeur controller should use comptes_model for payeur_selector'
        );
    }
    
    /**
     * Test that vols_avion controller has been modified correctly
     */
    public function testVolsAvionControllerModified()
    {
        $controller_content = file_get_contents(APPPATH . 'controllers/vols_avion.php');
        
        // Check that comptes_model is loaded
        $this->assertTrue(
            strpos($controller_content, "load->model('comptes_model')") !== false,
            'vols_avion controller should load comptes_model'
        );
            
        // Check that payeur_selector is created using comptes_model
        $this->assertTrue(
            strpos($controller_content, 'comptes_model->payeur_selector_with_null()') !== false,
            'vols_avion controller should use comptes_model for payeur_selector'
        );
    }
    
    /**
     * Test that controllers no longer set payeur_selector to pilote_selector
     */
    public function testControllersNoLongerUsePiloteSelector()
    {
        $planeur_content = file_get_contents(APPPATH . 'controllers/vols_planeur.php');
        $avion_content = file_get_contents(APPPATH . 'controllers/vols_avion.php');
        
        // Check that pilote_selector is no longer assigned to payeur_selector
        $this->assertFalse(
            strpos($planeur_content, "'payeur_selector'] = \$pilote_selector") !== false,
            'vols_planeur should not set payeur_selector to pilote_selector'
        );
        $this->assertFalse(
            strpos($avion_content, "['payeur_selector'] = \$pilote_selector") !== false,
            'vols_avion should not set payeur_selector to pilote_selector'
        );
    }
    
    /**
     * Test that the Comptes_model has the new method with correct implementation
     */
    public function testComptesModelHasNewMethod()
    {
        $comptes_content = file_get_contents(APPPATH . 'models/comptes_model.php');
        
        // Check that the method exists
        $this->assertTrue(
            strpos($comptes_content, 'function payeur_selector_with_null()') !== false,
            'Comptes_model should have payeur_selector_with_null method'
        );
        
        // Check that the method contains the expected logic
        $this->assertTrue(
            strpos($comptes_content, 'codec LIKE') !== false,
            'payeur_selector_with_null should filter by codec LIKE 411%'
        );
        
        $this->assertTrue(
            strpos($comptes_content, 'list_of_account') !== false,
            'payeur_selector_with_null should use list_of_account method'
        );
        
        $this->assertTrue(
            strpos($comptes_content, '-- Sélectionner --') !== false,
            'payeur_selector_with_null should include empty option'
        );
        
        // Check that it uses account name (nom) not pilote
        $this->assertTrue(
            strpos($comptes_content, '$account[\'nom\']') !== false,
            'payeur_selector_with_null should display account nom field'
        );
        
        $this->assertTrue(
            strpos($comptes_content, 'Nom du compte') !== false,
            'payeur_selector_with_null should reference account name in comment'
        );
        
        // Check that the ORDER BY issue is fixed
        $this->assertTrue(
            strpos($comptes_content, 'codec, nom') !== false,
            'payeur_selector_with_null should order by codec, nom'
        );
        
        // Check that list_of_account properly handles empty order parameter
        $this->assertTrue(
            strpos($comptes_content, 'if (!empty($order))') !== false,
            'list_of_account should check if order is not empty before applying ORDER BY'
        );
    }
    
    /**
     * Test that the files have valid PHP syntax
     */
    public function testFilesSyntax()
    {
        // Test comptes_model syntax
        $output = shell_exec('php -l ' . APPPATH . 'models/comptes_model.php 2>&1');
        $this->assertTrue(
            strpos($output, 'No syntax errors detected') !== false,
            'comptes_model.php should have valid PHP syntax'
        );
        
        // Test vols_planeur syntax
        $output = shell_exec('php -l ' . APPPATH . 'controllers/vols_planeur.php 2>&1');
        $this->assertTrue(
            strpos($output, 'No syntax errors detected') !== false,
            'vols_planeur.php should have valid PHP syntax'
        );
        
        // Test vols_avion syntax
        $output = shell_exec('php -l ' . APPPATH . 'controllers/vols_avion.php 2>&1');
        $this->assertTrue(
            strpos($output, 'No syntax errors detected') !== false,
            'vols_avion.php should have valid PHP syntax'
        );
    }
}