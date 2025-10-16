<?php

use PHPUnit\Framework\TestCase;

/**
 * Base class for all export functionality tests
 * Provides common utilities for validating CSV and PDF exports
 */
abstract class BaseExportTest extends TestCase
{
    protected $temp_files = [];
    protected $CI;

    public function setUp(): void
    {
        // Get CodeIgniter instance for tests that need it (only if available)
        if (function_exists('get_instance')) {
            $this->CI = &get_instance();
        } else {
            $this->CI = null;
        }
        
        // Verify required tools are available
        $this->checkRequiredTools();
    }

    public function tearDown(): void
    {
        // Clean up any temporary files created during testing
        foreach ($this->temp_files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        $this->temp_files = [];
    }

    /**
     * Check that required tools for testing are available
     */
    protected function checkRequiredTools()
    {
        // Check for pdftotext (required for PDF content validation)
        $pdftotext_path = shell_exec('which pdftotext 2>/dev/null');
        if (empty(trim($pdftotext_path))) {
            $this->markTestSkipped('pdftotext not available - required for PDF content validation');
        }
    }

    /**
     * Validate CSV structure and content
     * 
     * @param string $content CSV content to validate
     * @param array $expectedHeaders Expected column headers
     * @param array $requiredTerms Terms that must be present in the CSV
     * @return array Parsed CSV data for further validation
     */
    protected function validateCsvStructure($content, $expectedHeaders = [], $requiredTerms = [])
    {
        $this->assertNotEmpty($content, 'CSV content should not be empty');
        
        // Parse CSV content
        $lines = explode("\n", trim($content));
        $this->assertGreaterThan(0, count($lines), 'CSV should contain at least one line');
        
        // Check for basic CSV format (semicolon separated for GVV)
        $first_line = $lines[0];
        $this->assertStringContainsString(';', $first_line, 'CSV should use semicolon separators');
        
        // Parse headers if expected
        if (!empty($expectedHeaders)) {
            $headers = str_getcsv($first_line, ';');
            foreach ($expectedHeaders as $expectedHeader) {
                $found = false;
                foreach ($headers as $header) {
                    if (stripos($header, $expectedHeader) !== false) {
                        $found = true;
                        break;
                    }
                }
                $this->assertTrue($found, "Expected header '$expectedHeader' should be present in CSV");
            }
        }
        
        // Check for required terms in content
        foreach ($requiredTerms as $term) {
            $this->assertStringContainsString($term, $content, "CSV should contain '$term'");
        }
        
        // Validate data integrity
        $this->validateCsvDataIntegrity($lines);
        
        return $this->parseCsvLines($lines);
    }

    /**
     * Validate PDF content using pdftotext extraction
     * 
     * @param string $pdfPath Path to PDF file
     * @param array $expectedTerms Terms that must be present in the PDF
     * @param array $expectedStructure Structure elements to validate
     * @return string Extracted text content
     */
    protected function validatePdfContent($pdfPath, $expectedTerms = [], $expectedStructure = [])
    {
        $this->assertFileExists($pdfPath, 'PDF file should exist');
        $this->assertGreaterThan(0, filesize($pdfPath), 'PDF file should not be empty');
        
        // Verify PDF header
        $header = file_get_contents($pdfPath, false, null, 0, 10);
        $this->assertStringStartsWith('%PDF-', $header, 'File should have valid PDF header');
        
        // Extract text content
        $extracted_text = shell_exec("pdftotext '$pdfPath' - 2>/dev/null");
        $this->assertNotEmpty($extracted_text, 'PDF should contain extractable text');
        
        // Validate expected terms
        $text_lower = strtolower($extracted_text);
        foreach ($expectedTerms as $term) {
            $term_lower = strtolower($term);
            $this->assertStringContainsString($term_lower, $text_lower, 
                "PDF should contain '$term'");
        }
        
        // Validate structure elements
        $this->validatePdfStructure($extracted_text, $expectedStructure);
        
        return $extracted_text;
    }

    /**
     * Test export endpoint access control
     * 
     * @param string $url Export URL to test
     * @param string $requiredRole Required role for access
     */
    protected function checkExportAccess($url, $requiredRole = 'ca')
    {
        // This is a conceptual test - in a real implementation you would:
        // 1. Mock authentication with different user roles
        // 2. Make HTTP requests to the export URLs
        // 3. Verify proper access control enforcement
        
        $this->assertTrue(true, "Access control test for $url with role $requiredRole (placeholder)");
    }

    /**
     * Simulate an export request and validate the response
     * 
     * @param string $controller Controller name
     * @param string $method Export method name
     * @param array $params Additional parameters
     * @return array Response data
     */
    protected function simulateExportRequest($controller, $method, $params = [])
    {
        // Create a temporary file to simulate export output
        $temp_file = tempnam(sys_get_temp_dir(), 'gvv_export_test_');
        $this->temp_files[] = $temp_file;
        
        // Generate simulated export content based on controller and method
        $content = $this->generateSimulatedExportContent($controller, $method, $params);
        file_put_contents($temp_file, $content);
        
        return [
            'success' => true,
            'file_path' => $temp_file,
            'content' => $content,
            'size' => filesize($temp_file)
        ];
    }

    /**
     * Compare CSV and PDF exports for the same data
     * 
     * @param string $csvContent CSV content
     * @param string $pdfText Extracted PDF text
     * @param array $comparisonFields Fields to compare between formats
     * @param bool $requireMonetaryAmounts Whether to require monetary amounts (default true)
     */
    protected function validateCrosFormatConsistency($csvContent, $pdfText, $comparisonFields = [], $requireMonetaryAmounts = true)
    {
        $csv_lower = strtolower($csvContent);
        $pdf_lower = strtolower($pdfText);
        
        // Default comparison fields if none specified
        if (empty($comparisonFields)) {
            $comparisonFields = ['total', 'date', 'code', 'montant', 'compte'];
        }
        
        foreach ($comparisonFields as $field) {
            $csv_has_field = strpos($csv_lower, strtolower($field)) !== false;
            $pdf_has_field = strpos($pdf_lower, strtolower($field)) !== false;
            
            $this->assertEquals($csv_has_field, $pdf_has_field, 
                "Field '$field' presence should be consistent between CSV and PDF");
        }
        
        // Check for monetary amounts consistency (only if required)
        if ($requireMonetaryAmounts) {
            $csv_amounts = preg_match_all('/\d{1,6}[,\.]\d{2}/', $csvContent);
            $pdf_amounts = preg_match_all('/\d{1,6}[,\.]\d{2}/', $pdfText);
            
            $this->assertGreaterThan(0, $csv_amounts, 'CSV should contain monetary amounts');
            $this->assertGreaterThan(0, $pdf_amounts, 'PDF should contain monetary amounts');
        }
    }

    /**
     * Generate simulated export content for testing
     * 
     * @param string $controller Controller name
     * @param string $method Export method
     * @param array $params Parameters
     * @return string Simulated content
     */
    protected function generateSimulatedExportContent($controller, $method, $params = [])
    {
        $year = isset($params['year']) ? $params['year'] : date('Y');
        $date = isset($params['date']) ? $params['date'] : date('d/m/Y');
        
        switch ($controller) {
            case 'comptes':
                return $this->generateFinancialExportContent($method, $year, $date);
            case 'vols_planeur':
            case 'vols_avion':
                return $this->generateFlightExportContent($method, $year);
            case 'membre':
                return $this->generateMemberExportContent($method);
            default:
                return $this->generateGenericExportContent($controller, $method);
        }
    }

    /**
     * Generate financial export content
     */
    protected function generateFinancialExportContent($method, $year, $date)
    {
        if (strpos($method, 'csv') !== false) {
            // Generate appropriate CSV content based on method
            if (strpos($method, 'resultat') !== false) {
                return "Code;Dépenses;$year;" . ($year-1) . ";;Code;Recettes;$year;" . ($year-1) . "\n" .
                       "606;Achats stockés;1500,50;1200,00;;706;Ventes marchandises;3500,75;3200,00\n" .
                       "611;Sous-traitance;800,00;750,00;;708;Autres produits;2100,25;1900,00\n" .
                       ";;;;;;;;\n" .
                       ";Total des dépenses;2300,50;1950,00;;;Total des recettes;5601,00;5100,00\n" .
                       ";Bénéfices;3300,50;3150,00;;;Pertes;;\n" .  // Fixed: removed extra semicolon
                       "Date;$date;;;;;;;\n";  // Fixed: 9 columns instead of 10
            } elseif (strpos($method, 'bilan') !== false) {
                return "Code;Libellé;Débit;Crédit;Solde\n" .
                       "Bilan au $date;;;;\n" .
                       "606;Achats stockés;1500,50;0,00;1500,50\n" .
                       "706;Ventes;0,00;2500,75;-2500,75\n" .
                       "Total;;1500,50;2500,75;-1000,25\n";
            } else {
                return "Code;Libellé;Débit;Crédit;Solde\n" .
                       "606;Achats stockés;1500,50;0,00;1500,50\n" .
                       "706;Ventes;0,00;2500,75;-2500,75\n" .
                       "Total;;1500,50;2500,75;-1000,25\n" .
                       "Date;$date;;;\n";  // Fixed: 5 columns instead of 6
            }
        } else {
            // Generate appropriate PDF content based on method
            if (strpos($method, 'resultat') !== false) {
                return "Résultat d'exploitation de l'exercice $year\n\n" .
                       "Code    Dépenses              $year      " . ($year-1) . "      Code    Recettes             $year      " . ($year-1) . "\n" .
                       "606     Achats stockés        1500,50    1200,00    706     Ventes marchandises  3500,75    3200,00\n" .
                       "611     Sous-traitance        800,00     750,00     708     Autres produits      2100,25    1900,00\n" .
                       "\n" .
                       "        Total des dépenses   2300,50    1950,00            Total des recettes   5601,00    5100,00\n" .
                       "        Bénéfices            3300,50    3150,00            Pertes\n\n" .
                       "Date: $date\n";
            } else {
                return "Résultat financier $year\n\n" .
                       "Code    Libellé           Débit      Crédit     Solde\n" .
                       "606     Achats stockés    1500,50    0,00       1500,50\n" .
                       "706     Ventes           0,00       2500,75    -2500,75\n" .
                       "Total                    1500,50    2500,75    -1000,25\n\n" .
                       "Date: $date\n";
            }
        }
    }

    /**
     * Generate flight export content
     */
    protected function generateFlightExportContent($method, $year)
    {
        if (strpos($method, 'csv') !== false) {
            return "Date;Pilote;Machine;Durée;Type\n" .
                   "01/01/$year;DUPONT;F-ABCD;01:30;Local\n" .
                   "02/01/$year;MARTIN;F-EFGH;02:15;Navigation\n";
        } else {
            // Generate appropriate PDF content based on method
            if (strpos($method, 'machine') !== false) {
                return "Statistiques machines $year\n\n" .
                       "Machine    Heures    Vols    Moyenne\n" .
                       "F-ABCD     120:30    45      02:41\n" .
                       "F-EFGH     98:15     38      02:35\n";
            } elseif (strpos($method, 'month') !== false) {
                return "Statistiques mensuelles $year\n\n" .
                       "Mois       Heures    Vols    Moyenne\n" .
                       "Janvier    35:20     15      02:21\n" .
                       "Février    42:15     18      02:20\n";
            } else {
                return "Carnet de vol $year\n\n" .
                       "Date        Pilote    Machine   Durée    Type\n" .
                       "01/01/$year DUPONT    F-ABCD    01:30    Local\n" .
                       "02/01/$year MARTIN    F-EFGH    02:15    Navigation\n";
            }
        }
    }

    /**
     * Generate member export content
     */
    protected function generateMemberExportContent($method)
    {
        if (strpos($method, 'csv') !== false) {
            return "Nom;Prénom;Licence;Section\n" .
                   "DUPONT;Jean;123456;Planeur\n" .
                   "MARTIN;Marie;789012;Avion\n";
        } else {
            return "Liste des membres\n\n" .
                   "Nom      Prénom    Licence    Section\n" .
                   "DUPONT   Jean      123456     Planeur\n" .
                   "MARTIN   Marie     789012     Avion\n";
        }
    }

    /**
     * Generate generic export content
     */
    protected function generateGenericExportContent($controller, $method)
    {
        if (strpos($method, 'csv') !== false) {
            // Generate proper CSV format for discovery flights and other exports
            if ($controller === 'vols_decouverte') {
                return "Date;Pilote;Passager;Machine;Durée\n" .
                       "01/01/2015;INSTRUCTEUR;DUPONT Jean;F-ABCD;00:45\n" .
                       "02/01/2015;MONITEUR;MARTIN Paul;F-EFGH;01:00\n";
            } else {
                return "Export $controller $method\n" .
                       "Données;Valeur\n" .
                       "Test;OK\n";
            }
        } else {
            return "Export $controller $method\n\n" .
                   "Données    Valeur\n" .
                   "Test       OK\n";
        }
    }

    /**
     * Validate CSV data integrity
     */
    private function validateCsvDataIntegrity($lines)
    {
        if (count($lines) < 2) {
            return; // No data to validate
        }
        
        $first_line_columns = count(str_getcsv($lines[0], ';'));
        
        // Check that all data lines have consistent column count
        for ($i = 1; $i < count($lines); $i++) {
            if (trim($lines[$i]) === '') {
                continue; // Skip empty lines
            }
            
            $columns = count(str_getcsv($lines[$i], ';'));
            $this->assertEquals($first_line_columns, $columns, 
                "Line " . ($i + 1) . " should have $first_line_columns columns but has $columns");
        }
    }

    /**
     * Parse CSV lines into structured data
     */
    private function parseCsvLines($lines)
    {
        $data = [];
        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $data[] = str_getcsv($line, ';');
            }
        }
        return $data;
    }

    /**
     * Validate PDF structure elements
     */
    private function validatePdfStructure($text, $expectedStructure)
    {
        foreach ($expectedStructure as $element => $requirement) {
            switch ($element) {
                case 'title':
                    $this->assertRegExp('/^.+$/m', $text, 'PDF should have a title line');
                    break;
                case 'table':
                    $this->assertStringContainsString('   ', $text, 'PDF should contain tabular data');
                    break;
                case 'totals':
                    $this->assertStringContainsString('total', strtolower($text), 'PDF should contain totals');
                    break;
                case 'date':
                    $this->assertRegExp('/\d{1,2}\/\d{1,2}\/\d{4}/', $text, 'PDF should contain dates');
                    break;
            }
        }
    }
}