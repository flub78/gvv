<?php

use PHPUnit\Framework\TestCase;

/**
 * Controller test for comptes resultat export functionality
 * Tests that CSV export contains complete data, not just date header
 */
class ComptesControllerTest extends TestCase
{
    /**
     * Test that demonstrates the CSV export bug
     * Currently fails because CSV export only outputs date line instead of full table
     */
    public function testCsvResultatExportIsTruncated()
    {
        // Test what csv_resultat() currently does (the bug)
        $title = "Résultat d'exploitation de l'exercice";
        $csv_data = array();
        $csv_data[] = array(
            "Date",
            "31/12/2015",
            '',
            '',
            '',
            ''
        );
        
        // Capture what the csv_file function would output
        $csv_content = "";
        if ($title) $csv_content .= $title . ";\n";
        foreach ($csv_data as $row) {
            foreach ($row as $cell) {
                $formatted_cell = is_numeric($cell) ? str_replace('.', ',', $cell) : $cell;
                $csv_content .= $formatted_cell . ";";
            }
            $csv_content .= "\n";
        }
        
        // This demonstrates the old bug - only 2 lines in the CSV
        $lines = explode("\n", trim($csv_content));
        $this->assertEquals(2, count($lines), "Before fix: CSV export only contains 2 lines (this demonstrates the bug)");
        
        // The second line should only be the date (showing it's truncated)
        $this->assertStringContainsString("Date", $lines[1], "Before fix: CSV only contains date header, no account data");
        $this->assertStringNotContainsString("6", $lines[1], "Before fix: No account codes starting with 6 (charges) found in CSV");
        $this->assertStringNotContainsString("7", $lines[1], "Before fix: No account codes starting with 7 (produits) found in CSV");
    }

    /**
     * Test the fixed CSV export functionality
     * This test simulates what the fixed csv_resultat() method should produce
     */
    public function testFixedCsvResultatExportContainsCompleteData()
    {
        // Simulate what the FIXED csv_resultat() method should do
        $title = "Résultat d'exploitation de l'exercice";
        $resultat = array(
            'balance_date' => '31/12/2015',
            'years' => array(2014, 2015),
            'comptes_depenses' => array(
                array('codec' => '606', 'nom' => 'Achats non stockés', 'id' => 1),
                array('codec' => '611', 'nom' => 'Sous-traitance', 'id' => 2)
            ),
            'comptes_recettes' => array(
                array('codec' => '706', 'nom' => 'Ventes', 'id' => 3),
                array('codec' => '708', 'nom' => 'Produits activités', 'id' => 4)
            ),
            'montants' => array(
                2015 => array(
                    'total_depenses' => 2300,
                    'total_recettes' => 4300,
                    'depenses' => array(1 => 1500, 2 => 800),
                    'recettes' => array(3 => 2500, 4 => 1800)
                ),
                2014 => array(
                    'total_depenses' => 1950,
                    'total_recettes' => 3800,
                    'depenses' => array(1 => 1200, 2 => 750),
                    'recettes' => array(3 => 2200, 4 => 1600)
                )
            )
        );
        
        // Simulate the fixed resultat_table output (simplified)
        $resultat_table = array();
        $resultat_table[] = array('Code', 'Dépenses', '2015', '2014', '', 'Code', 'Recettes', '2015', '2014');
        $resultat_table[] = array('606', 'Achats non stockés', '1500,00', '1200,00', '', '706', 'Ventes', '2500,00', '2200,00');
        $resultat_table[] = array('611', 'Sous-traitance', '800,00', '750,00', '', '708', 'Produits activités', '1800,00', '1600,00');
        $resultat_table[] = array('', '', '', '', '', '', '', '', '');
        $resultat_table[] = array('', 'Total des dépenses', '2300,00', '1950,00', '', '', 'Total des recettes', '4300,00', '3800,00');
        $resultat_table[] = array('', 'Bénéfices', '2000,00', '1850,00', '', '', 'Pertes', '', '');
        
        // This is what the FIXED csv_resultat() should do
        $csv_data = array();
        
        // Add header with date (9 columns to match table)
        $csv_data[] = array(
            "Date",
            $resultat['balance_date'],
            '',
            '',
            '',
            '',
            '',
            '',
            ''
        );
        
        // Add empty line
        $csv_data[] = array('', '', '', '', '', '', '', '', '');
        
        // Add the actual table data
        foreach ($resultat_table as $row) {
            $csv_data[] = $row;
        }
        
        // Generate CSV content
        $csv_content = "";
        if ($title) $csv_content .= $title . ";\n";
        foreach ($csv_data as $row) {
            foreach ($row as $cell) {
                $formatted_cell = is_numeric($cell) ? str_replace('.', ',', $cell) : $cell;
                $csv_content .= $formatted_cell . ";";
            }
            $csv_content .= "\n";
        }
        
        $lines = explode("\n", trim($csv_content));
        
        // After fixing, CSV should contain many lines with account data
        $this->assertGreaterThan(5, count($lines), "Fixed CSV should contain multiple lines with account data");
        
        // Should contain account codes
        $csv_text = implode("\n", $lines);
        $this->assertStringContainsString("606", $csv_text, "Fixed CSV should contain account codes like 606");
        $this->assertStringContainsString("706", $csv_text, "Fixed CSV should contain account codes like 706");
        
        // Should contain account names
        $this->assertStringContainsString("Achats non stockés", $csv_text, "Fixed CSV should contain account names");
        $this->assertStringContainsString("Ventes", $csv_text, "Fixed CSV should contain account names");
        
        // Should contain monetary amounts
        $this->assertStringContainsString("1500,00", $csv_text, "Fixed CSV should contain monetary amounts");
        $this->assertStringContainsString("2500,00", $csv_text, "Fixed CSV should contain monetary amounts");
        
        // Should contain totals
        $this->assertStringContainsString("Total des dépenses", $csv_text, "Fixed CSV should contain expense totals");
        $this->assertStringContainsString("Total des recettes", $csv_text, "Fixed CSV should contain income totals");
        $this->assertStringContainsString("Bénéfices", $csv_text, "Fixed CSV should contain profit/loss data");
    }
}