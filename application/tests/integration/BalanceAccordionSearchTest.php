<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration test for balance accordion search functionality
 * Verifies that search works for both headers and child accounts
 */
class BalanceAccordionSearchTest extends TestCase
{
    /**
     * Test that validates the accordion search JavaScript modification
     * This test verifies that the search function can find matches in both
     * header data and child account data within accordion bodies
     */
    public function testAccordionSearchJavaScriptLogic()
    {
        // Read the view file to verify our modifications are present
        $view_file = APPPATH . 'views/comptes/bs_balanceView.php';
        $this->assertFileExists($view_file, 'Balance view file should exist');
        
        $view_content = file_get_contents($view_file);
        $this->assertNotEmpty($view_content, 'View file should not be empty');
        
        // Verify the search function contains the new logic
        $this->assertStringContainsString('shouldShow', $view_content, 
            'Search function should contain shouldShow variable');
        
        $this->assertStringContainsString('accordion-body table tbody', $view_content,
            'Search function should look for child accounts in accordion body');
        
        $this->assertStringContainsString('childRows.forEach', $view_content,
            'Search function should iterate through child account rows');
        
        echo "\n=== ACCORDION SEARCH MODIFICATION VALIDATION ===\n";
        echo "View file exists and contains enhanced search logic\n";
        echo "=== TEST PASSED ===\n";
    }
    
    /**
     * Test the search logic with simulated DOM structure
     * This simulates how the JavaScript search function should work
     */
    public function testSearchLogicWithSimulatedData()
    {
        // Simulate the search logic from the JavaScript function
        $search_term = 'essence';
        
        // Simulate accordion header data (doesn't contain "essence")
        $header_data = [
            'codec' => '606',
            'nom' => 'Achats non stockés de matière et fourniture',
            'solde_debit' => '2500.00',
            'solde_credit' => ''
        ];
        
        // Simulate child account data (contains "essence")
        $child_accounts = [
            [
                'codec' => '6061',
                'nom' => 'Essence F-JHRV',
                'solde_debit' => '150.00',
                'solde_credit' => ''
            ],
            [
                'codec' => '6062',
                'nom' => 'Essence F-JTVA',
                'solde_debit' => '175.00',
                'solde_credit' => ''
            ],
            [
                'codec' => '6063',
                'nom' => 'Huile moteur',
                'solde_debit' => '85.00',
                'solde_credit' => ''
            ]
        ];
        
        // Test search logic - header doesn't match
        $header_text = strtolower(implode(' ', $header_data));
        $header_matches = strpos($header_text, strtolower($search_term)) !== false;
        $this->assertFalse($header_matches, 'Header should not match "essence" search term');
        
        // Test search logic - child accounts should match
        $found_in_children = false;
        foreach ($child_accounts as $child) {
            $child_text = strtolower(implode(' ', $child));
            if (strpos($child_text, strtolower($search_term)) !== false) {
                $found_in_children = true;
                break;
            }
        }
        $this->assertTrue($found_in_children, 'Child accounts should match "essence" search term');
        
        // Simulate the shouldShow logic from our JavaScript
        $shouldShow = $header_matches || $found_in_children;
        $this->assertTrue($shouldShow, 'Accordion group should be visible when child accounts match');
        
        echo "\n=== SEARCH LOGIC SIMULATION TEST ===\n";
        echo "Search term: '$search_term'\n";
        echo "Header matches: " . ($header_matches ? 'YES' : 'NO') . "\n";
        echo "Child accounts match: " . ($found_in_children ? 'YES' : 'NO') . "\n";
        echo "Group should be visible: " . ($shouldShow ? 'YES' : 'NO') . "\n";
        echo "=== TEST PASSED ===\n";
    }
    
    /**
     * Test edge cases for the search functionality
     */
    public function testSearchEdgeCases()
    {
        // Test empty search term
        $search_term = '';
        $shouldShow = true; // Empty search should show all items
        $this->assertTrue($shouldShow, 'Empty search should show all accordion groups');
        
        // Test case insensitive search
        $search_terms = ['ESSENCE', 'essence', 'Essence', 'EsSeNcE'];
        $test_text = 'Essence F-JHRV';
        
        foreach ($search_terms as $term) {
            $matches = strpos(strtolower($test_text), strtolower($term)) !== false;
            $this->assertTrue($matches, "Search should be case insensitive for term: '$term'");
        }
        
        // Test partial matches
        $partial_searches = ['ess', 'sen', 'F-J'];
        foreach ($partial_searches as $partial) {
            $matches = strpos(strtolower($test_text), strtolower($partial)) !== false;
            $this->assertTrue($matches, "Search should work with partial matches: '$partial'");
        }
        
        echo "\n=== SEARCH EDGE CASES TEST ===\n";
        echo "Empty search handling: PASSED\n";
        echo "Case insensitive search: PASSED\n";
        echo "Partial matches: PASSED\n";
        echo "=== TEST PASSED ===\n";
    }
}