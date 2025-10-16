<?php

require_once 'BaseExportTest.php';

/**
 * Comprehensive tests for flight data export functionality
 * Tests vols_planeur, vols_avion, and vols_decouverte controller exports
 */
class FlightDataExportsTest extends BaseExportTest
{
    /**
     * Test vols_planeur/csv - Glider flights CSV export
     */
    public function testGliderFlightsCsvExport()
    {
        $response = $this->simulateExportRequest('vols_planeur', 'csv', ['year' => 2015]);
        
        $this->assertTrue($response['success'], 'Glider flights CSV export should succeed');
        $this->assertGreaterThan(0, $response['size'], 'CSV file should not be empty');
        
        $expectedHeaders = ['Date', 'Pilote', 'Machine', 'Durée', 'Type'];
        $requiredTerms = ['F-', 'DUPONT', '01/01/2015'];
        
        $csvData = $this->validateCsvStructure($response['content'], $expectedHeaders, $requiredTerms);
        
        // Verify flight-specific data
        $this->assertGreaterThan(1, count($csvData), 'CSV should contain flight data');
        
        // Check for flight duration format (HH:MM)
        $duration_matches = preg_match_all('/\d{1,2}:\d{2}/', $response['content']);
        $this->assertGreaterThan(0, $duration_matches, 'CSV should contain flight durations');
        
        echo "\n=== GLIDER FLIGHTS CSV TEST ===\n";
        echo "Flight records: " . (count($csvData) - 1) . "\n";
        echo "Duration entries found: $duration_matches\n";
        echo "=== TEST PASSED ===\n";
    }

    /**
     * Test vols_planeur/pdf - Glider flights PDF export
     */
    public function testGliderFlightsPdfExport()
    {
        $response = $this->simulateExportRequest('vols_planeur', 'pdf', ['year' => 2015]);
        
        $this->assertTrue($response['success'], 'Glider flights PDF export should succeed');
        
        $expectedTerms = ['Carnet de vol', 'Date', 'Pilote', 'Machine', 'F-'];
        $expectedStructure = ['title', 'table', 'date'];
        
        $this->validatePdfContentFromText($response['content'], $expectedTerms, $expectedStructure);
        
        echo "\n=== GLIDER FLIGHTS PDF TEST ===\n";
        echo "Glider flights PDF export validated successfully\n";
        echo "=== TEST PASSED ===\n";
    }

    /**
     * Test vols_avion/csv - Airplane flights CSV export
     */
    public function testAirplaneFlightsCsvExport()
    {
        $response = $this->simulateExportRequest('vols_avion', 'csv', ['year' => 2015]);
        
        $this->assertTrue($response['success'], 'Airplane flights CSV export should succeed');
        
        $expectedHeaders = ['Date', 'Pilote', 'Machine', 'Durée'];
        $requiredTerms = ['F-'];
        
        $this->validateCsvStructure($response['content'], $expectedHeaders, $requiredTerms);
        
        echo "\n=== AIRPLANE FLIGHTS CSV TEST ===\n";
        echo "Airplane flights CSV export validated successfully\n";
        echo "=== TEST PASSED ===\n";
    }

    /**
     * Test vols_avion/pdf - Airplane flights PDF export
     */
    public function testAirplaneFlightsPdfExport()
    {
        $response = $this->simulateExportRequest('vols_avion', 'pdf', ['year' => 2015]);
        
        $this->assertTrue($response['success'], 'Airplane flights PDF export should succeed');
        
        $expectedTerms = ['Carnet de vol', 'Date', 'Pilote', 'Machine'];
        $this->validatePdfContentFromText($response['content'], $expectedTerms);
        
        echo "\n=== AIRPLANE FLIGHTS PDF TEST ===\n";
        echo "Airplane flights PDF export validated successfully\n";
        echo "=== TEST PASSED ===\n";
    }

    /**
     * Test vols_planeur/pdf_machine/{year} - Machine statistics PDF
     */
    public function testGliderMachineStatisticsPdfExport()
    {
        $response = $this->simulateExportRequest('vols_planeur', 'pdf_machine', ['year' => 2015]);
        
        $this->assertTrue($response['success'], 'Machine statistics PDF export should succeed');
        
        $expectedTerms = ['Statistiques', 'machines', '2015', 'Machine', 'Heures'];
        $this->validatePdfContentFromText($response['content'], $expectedTerms);
        
        echo "\n=== MACHINE STATISTICS PDF TEST ===\n";
        echo "Machine statistics PDF export validated successfully\n";
        echo "=== TEST PASSED ===\n";
    }

    /**
     * Test vols_planeur/pdf_month/{year} - Monthly statistics PDF
     */
    public function testGliderMonthlyStatisticsPdfExport()
    {
        $response = $this->simulateExportRequest('vols_planeur', 'pdf_month', ['year' => 2015]);
        
        $this->assertTrue($response['success'], 'Monthly statistics PDF export should succeed');
        
        $expectedTerms = ['Statistiques', 'mensuelles', '2015', 'Mois', 'Heures'];
        $this->validatePdfContentFromText($response['content'], $expectedTerms);
        
        echo "\n=== MONTHLY STATISTICS PDF TEST ===\n";
        echo "Monthly statistics PDF export validated successfully\n";
        echo "=== TEST PASSED ===\n";
    }

    /**
     * Test vols_decouverte/export/csv - Discovery flights CSV export
     */
    public function testDiscoveryFlightsCsvExport()
    {
        $response = $this->simulateExportRequest('vols_decouverte', 'export_csv');
        
        $this->assertTrue($response['success'], 'Discovery flights CSV export should succeed');
        
        $expectedHeaders = ['Date', 'Pilote', 'Passager', 'Machine', 'Durée'];
        $this->validateCsvStructure($response['content'], $expectedHeaders);
        
        echo "\n=== DISCOVERY FLIGHTS CSV TEST ===\n";
        echo "Discovery flights CSV export validated successfully\n";
        echo "=== TEST PASSED ===\n";
    }

    /**
     * Test consistency between glider flight CSV and PDF exports
     */
    public function testFlightDataExportConsistency()
    {
        $csvResponse = $this->simulateExportRequest('vols_planeur', 'csv', ['year' => 2015]);
        $pdfResponse = $this->simulateExportRequest('vols_planeur', 'pdf', ['year' => 2015]);
        
        $this->assertTrue($csvResponse['success'] && $pdfResponse['success'], 
            'Both CSV and PDF flight exports should succeed');
        
        // Compare content consistency (flight data doesn't typically have monetary amounts)
        $comparisonFields = ['Date', 'Pilote', 'Machine', 'Durée', 'F-'];
        $this->validateCrosFormatConsistency(
            $csvResponse['content'], 
            $pdfResponse['content'], 
            $comparisonFields,
            false // Don't require monetary amounts for flight data
        );
        
        echo "\n=== FLIGHT DATA CONSISTENCY TEST ===\n";
        echo "Flight CSV and PDF content consistency validated\n";
        echo "=== TEST PASSED ===\n";
    }

    /**
     * Test flight data export access control
     */
    public function testFlightDataExportAccessControl()
    {
        $exportUrls = [
            'vols_planeur/csv',
            'vols_planeur/pdf',
            'vols_avion/csv',
            'vols_avion/pdf',
            'vols_decouverte/export/csv'
        ];
        
        foreach ($exportUrls as $url) {
            $this->checkExportAccess($url, 'pilote');
        }
        
        echo "\n=== FLIGHT DATA ACCESS CONTROL TEST ===\n";
        echo "Access control validated for " . count($exportUrls) . " endpoints\n";
        echo "=== TEST PASSED ===\n";
    }

    /**
     * Test flight data integrity validation
     */
    public function testFlightDataIntegrity()
    {
        $response = $this->simulateExportRequest('vols_planeur', 'csv', ['year' => 2015]);
        
        $csvData = $this->validateCsvStructure($response['content']);
        
        // Validate flight-specific data integrity
        foreach ($csvData as $index => $row) {
            if ($index === 0) continue; // Skip header
            
            // Check date format (DD/MM/YYYY)
            if (isset($row[0]) && !empty($row[0])) {
                $this->assertRegExp('/^\d{2}\/\d{2}\/\d{4}$/', $row[0], 
                    "Date should be in DD/MM/YYYY format");
            }
            
            // Check aircraft registration format (F-xxxx)
            if (isset($row[2]) && !empty($row[2])) {
                $this->assertRegExp('/^F-[A-Z]{3,4}$/', $row[2], 
                    "Aircraft registration should be in F-XXXX format");
            }
            
            // Check duration format (HH:MM)
            if (isset($row[3]) && !empty($row[3])) {
                $this->assertRegExp('/^\d{1,2}:\d{2}$/', $row[3], 
                    "Duration should be in HH:MM format");
            }
        }
        
        echo "\n=== FLIGHT DATA INTEGRITY TEST ===\n";
        echo "Flight data integrity validated for " . (count($csvData) - 1) . " records\n";
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
                case 'date':
                    $this->assertRegExp('/\d{1,2}\/\d{1,2}\/\d{4}/', $content, 'PDF should contain dates');
                    break;
            }
        }
    }
}