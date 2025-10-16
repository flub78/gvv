<?php

require_once 'BaseExportTest.php';

/**
 * Comprehensive tests for financial export functionality
 * Tests all comptes controller export endpoints
 */
class FinancialExportsTest extends BaseExportTest
{
    /**
     * Test comptes/export_resultat/csv - Income statement CSV export
     */
    public function testIncomeStatementCsvExport()
    {
        $response = $this->simulateExportRequest('comptes', 'export_resultat_csv', ['year' => 2015]);
        
        $this->assertTrue($response['success'], 'Income statement CSV export should succeed');
        $this->assertGreaterThan(0, $response['size'], 'CSV file should not be empty');
        
        // Validate CSV structure and content
        $expectedHeaders = ['Code', 'Dépenses', '2015', '2014'];
        $requiredTerms = ['606', '706', 'Total', 'Bénéfices'];
        
        $csvData = $this->validateCsvStructure($response['content'], $expectedHeaders, $requiredTerms);
        
        // Verify financial data is present
        $this->assertGreaterThan(2, count($csvData), 'CSV should contain header plus data rows');
        
        // Check for monetary amounts
        $monetary_amounts = preg_match_all('/\d{1,6}[,\.]\d{2}/', $response['content']);
        $this->assertGreaterThan(0, $monetary_amounts, 'CSV should contain monetary amounts');
        
        echo "\n=== INCOME STATEMENT CSV TEST ===\n";
        echo "CSV rows: " . count($csvData) . "\n";
        echo "Monetary amounts found: $monetary_amounts\n";
        echo "Content length: " . strlen($response['content']) . " characters\n";
        echo "=== TEST PASSED ===\n";
    }

    /**
     * Test comptes/export_resultat/pdf - Income statement PDF export
     */
    public function testIncomeStatementPdfExport()
    {
        $response = $this->simulateExportRequest('comptes', 'export_resultat_pdf', ['year' => 2015]);
        
        $this->assertTrue($response['success'], 'Income statement PDF export should succeed');
        
        // For PDF, we validate the simulated content as if it were extracted from PDF
        $expectedTerms = ['Résultat', 'exploitation', 'Dépenses', 'Recettes', '606', '706', 'Total'];
        $expectedStructure = ['title', 'table', 'totals', 'date'];
        
        // Since this is simulated content, we validate it directly
        $this->validatePdfContentFromText($response['content'], $expectedTerms, $expectedStructure);
        
        echo "\n=== INCOME STATEMENT PDF TEST ===\n";
        echo "PDF content length: " . strlen($response['content']) . " characters\n";
        echo "=== TEST PASSED ===\n";
    }

    /**
     * Test comptes/dashboard/csv - Financial dashboard CSV
     */
    public function testFinancialDashboardCsvExport()
    {
        $response = $this->simulateExportRequest('comptes', 'dashboard_csv');
        
        $this->assertTrue($response['success'], 'Dashboard CSV export should succeed');
        
        $expectedHeaders = ['Code', 'Libellé', 'Débit', 'Crédit', 'Solde'];
        $requiredTerms = ['Total'];
        
        $this->validateCsvStructure($response['content'], $expectedHeaders, $requiredTerms);
        
        echo "\n=== DASHBOARD CSV TEST ===\n";
        echo "Dashboard CSV export validated successfully\n";
        echo "=== TEST PASSED ===\n";
    }

    /**
     * Test comptes/dashboard/pdf - Financial dashboard PDF
     */
    public function testFinancialDashboardPdfExport()
    {
        $response = $this->simulateExportRequest('comptes', 'dashboard_pdf');
        
        $this->assertTrue($response['success'], 'Dashboard PDF export should succeed');
        
        $expectedTerms = ['Code', 'Libellé', 'Total'];
        $this->validatePdfContentFromText($response['content'], $expectedTerms);
        
        echo "\n=== DASHBOARD PDF TEST ===\n";
        echo "Dashboard PDF export validated successfully\n";
        echo "=== TEST PASSED ===\n";
    }

    /**
     * Test comptes/export_bilan/csv - Balance sheet CSV export
     */
    public function testBalanceSheetCsvExport()
    {
        $response = $this->simulateExportRequest('comptes', 'export_bilan_csv', ['year' => 2015]);
        
        $this->assertTrue($response['success'], 'Balance sheet CSV export should succeed');
        
        $expectedHeaders = ['Code', 'Libellé', 'Débit', 'Crédit', 'Solde'];
        $requiredTerms = ['Bilan'];
        
        $this->validateCsvStructure($response['content'], $expectedHeaders, $requiredTerms);
        
        echo "\n=== BALANCE SHEET CSV TEST ===\n";
        echo "Balance sheet CSV export validated successfully\n";
        echo "=== TEST PASSED ===\n";
    }

    /**
     * Test comptes/balance_csv - Account balance CSV export
     */
    public function testAccountBalanceCsvExport()
    {
        $response = $this->simulateExportRequest('comptes', 'balance_csv', ['codec' => '606']);
        
        $this->assertTrue($response['success'], 'Account balance CSV export should succeed');
        
        $expectedHeaders = ['Code', 'Libellé', 'Débit', 'Crédit', 'Solde'];
        $this->validateCsvStructure($response['content'], $expectedHeaders);
        
        echo "\n=== ACCOUNT BALANCE CSV TEST ===\n";
        echo "Account balance CSV export validated successfully\n";
        echo "=== TEST PASSED ===\n";
    }

    /**
     * Test consistency between CSV and PDF financial exports
     */
    public function testFinancialExportConsistency()
    {
        $csvResponse = $this->simulateExportRequest('comptes', 'export_resultat_csv', ['year' => 2015]);
        $pdfResponse = $this->simulateExportRequest('comptes', 'export_resultat_pdf', ['year' => 2015]);
        
        $this->assertTrue($csvResponse['success'] && $pdfResponse['success'], 
            'Both CSV and PDF exports should succeed');
        
        // Compare content consistency
        $comparisonFields = ['606', '706', 'Total', 'Débit', 'Crédit'];
        $this->validateCrosFormatConsistency(
            $csvResponse['content'], 
            $pdfResponse['content'], 
            $comparisonFields
        );
        
        echo "\n=== FINANCIAL EXPORT CONSISTENCY TEST ===\n";
        echo "CSV and PDF content consistency validated\n";
        echo "=== TEST PASSED ===\n";
    }

    /**
     * Test financial export access control
     */
    public function testFinancialExportAccessControl()
    {
        // Test that financial exports require proper role (typically 'ca' or 'tresorier')
        $exportUrls = [
            'comptes/export_resultat/csv',
            'comptes/export_resultat/pdf',
            'comptes/dashboard/csv',
            'comptes/dashboard/pdf',
            'comptes/export_bilan/csv',
            'comptes/balance_csv'
        ];
        
        foreach ($exportUrls as $url) {
            $this->checkExportAccess($url, 'ca');
        }
        
        echo "\n=== FINANCIAL EXPORT ACCESS CONTROL TEST ===\n";
        echo "Access control validated for " . count($exportUrls) . " endpoints\n";
        echo "=== TEST PASSED ===\n";
    }

    /**
     * Helper method to validate PDF content from text (for simulated PDFs)
     */
    private function validatePdfContentFromText($content, $expectedTerms, $expectedStructure = [])
    {
        $this->assertNotEmpty($content, 'PDF content should not be empty');
        
        $content_lower = strtolower($content);
        foreach ($expectedTerms as $term) {
            $term_lower = strtolower($term);
            $this->assertStringContainsString($term_lower, $content_lower, 
                "PDF should contain '$term'");
        }
        
        // Validate structure if specified
        foreach ($expectedStructure as $element) {
            switch ($element) {
                case 'title':
                    $lines = explode("\n", $content);
                    $this->assertNotEmpty(trim($lines[0]), 'PDF should have a title line');
                    break;
                case 'table':
                    $this->assertStringContainsString('   ', $content, 'PDF should contain tabular data');
                    break;
                case 'totals':
                    $this->assertStringContainsString('total', $content_lower, 'PDF should contain totals');
                    break;
                case 'date':
                    $this->assertRegExp('/\d{1,2}\/\d{1,2}\/\d{4}/', $content, 'PDF should contain dates');
                    break;
            }
        }
    }
}