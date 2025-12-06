<?php
/**
 * Test for balance calculations in journal_compte with server-side pagination
 * 
 * This test verifies that:
 * 1. Balances are calculated correctly in chronological order
 * 2. Balances are independent of pagination (same ecriture has same balance regardless of page size)
 * 3. Balance increments are consistent (each line's balance = previous + operation)
 */

use PHPUnit\Framework\TestCase;

class EcrituresBalanceTest extends TestCase {

    protected $CI;

    protected function setUp(): void {
        parent::setUp();
        $this->CI =& get_instance();

        // FIRST: Load models
        $this->CI->load->model('ecritures_model');
        $this->CI->load->model('comptes_model');
        $this->CI->load->model('sections_model');

        // THEN: Configure session
        // Use section from session or default to 1
        $this->CI->session->set_userdata('section', 1);

        // Ensure year is set for filtrage
        $this->CI->session->set_userdata('year', date('Y'));

        // Disable additional filtering to see all ecritures for the year
        $this->CI->session->set_userdata('filter_active', false);
        $this->CI->session->set_userdata('filter_date', '');
        $this->CI->session->set_userdata('date_end', '');

        // FINALLY: Reload models to pick up session changes
        $this->CI->load->model('ecritures_model', '', TRUE);
        $this->CI->load->model('comptes_model', '', TRUE);
    }

    /**
     * Test that balance is independent of pagination size
     * CRITICAL: The same ecriture should have the same balance regardless of pagination
     *
     * This test uses ecritures that appear on different pages depending on pagination size
     * For example, with a compte that has 50+ ecritures:
     * - With 10 per page: ecriture at position 15 is on page 2
     * - With 25 per page: ecriture at position 15 is on page 1
     * - The balance should be IDENTICAL in both cases
     */
    public function test_balance_is_independent_of_pagination() {
        // Find a compte with many ecritures (at least 30 for meaningful test)
        // Filter by current year and section to match what get_datatable_data will return
        $year = date('Y');
        $section = $this->CI->session->userdata('section');

        $query = "SELECT compte1 as id, COUNT(*) as cnt
                  FROM ecritures
                  WHERE YEAR(date_op) = '$year' AND club = $section
                  GROUP BY compte1
                  HAVING cnt >= 30
                  LIMIT 1";
        $result = $this->CI->db->query($query)->row_array();
        
        if (!$result) {
            $this->markTestSkipped("Need a compte with at least 30 ecritures for pagination test. Please create test data.");
            return;
        }
        
        $compte_with_many_ecritures = $result['id'];
        $total_count = $this->CI->ecritures_model->count_account($compte_with_many_ecritures);

        TestLogger::info("\nTesting with compte $compte_with_many_ecritures having $total_count ecritures");

        // Choose target positions that will be on different pages with different pagination
        // Position 14 (15th ecriture, 0-indexed):
        // - With page_size=10: on page 2 (start=10, local position 4)
        // - With page_size=25: on page 1 (start=0, local position 14)
        $target_position = 14;

        if ($total_count <= $target_position) {
            $this->markTestSkipped("Compte doesn't have enough ecritures for position test");
            return;
        }

        // Get data with pagination of 10
        $result_10 = $this->CI->ecritures_model->get_datatable_data([
            'compte' => $compte_with_many_ecritures,
            'start' => 10,  // Page 2
            'length' => 10,
            'search' => '',
            'order_column' => 'date_op',
            'order_direction' => 'ASC'
        ]);

        // Get data with pagination of 25
        $result_25 = $this->CI->ecritures_model->get_datatable_data([
            'compte' => $compte_with_many_ecritures,
            'start' => 0,   // Page 1
            'length' => 25,
            'search' => '',
            'order_column' => 'date_op',
            'order_direction' => 'ASC'
        ]);

        // Verify we got data
        $this->assertNotEmpty($result_10['data'], "Should have data for page 2 with 10/page");
        $this->assertNotEmpty($result_25['data'], "Should have data for page 1 with 25/page");
        $this->assertGreaterThanOrEqual(5, count($result_10['data']), "Should have at least 5 rows on page 2");
        $this->assertGreaterThan($target_position, count($result_25['data']), "Should have more than $target_position rows with 25/page");

        // Find the target ecriture in both results
        // In result_10, it should be at position 4 (14 - 10)
        // In result_25, it should be at position 14
        $local_pos_10 = 4;
        $local_pos_25 = 14;

        $this->assertArrayHasKey($local_pos_10, $result_10['data'], "Should have ecriture at position $local_pos_10 in result_10");
        $this->assertArrayHasKey($local_pos_25, $result_25['data'], "Should have ecriture at position $local_pos_25 in result_25");

        $ecriture_10 = $result_10['data'][$local_pos_10];
        $ecriture_25 = $result_25['data'][$local_pos_25];

        // Verify it's the same ecriture
        $this->assertEquals($ecriture_10['id'], $ecriture_25['id'],
            "Should be the same ecriture ID at global position $target_position");

        TestLogger::info("Testing ecriture ID {$ecriture_10['id']} at global position $target_position:");
        TestLogger::info("  - With 10/page (page 2): solde = {$ecriture_10['solde']}");
        TestLogger::info("  - With 25/page (page 1): solde = {$ecriture_25['solde']}");

        // CRITICAL: The balance must be identical
        $this->assertEquals($ecriture_10['solde'], $ecriture_25['solde'],
            "Balance for ecriture {$ecriture_10['id']} must be identical regardless of pagination. " .
            "Got {$ecriture_10['solde']} with 10/page and {$ecriture_25['solde']} with 25/page",
            0.01);

        // Also test with another target position to be thorough (if we have enough data)
        if ($total_count > 22) {
            $target_position_2 = 22; // 23rd ecriture
            // With 10/page: position 2 on page 3 (start=20)
            // With 25/page: position 22 on page 1 (start=0)

            $result_10_p3 = $this->CI->ecritures_model->get_datatable_data([
                'compte' => $compte_with_many_ecritures,
                'start' => 20,  // Page 3
                'length' => 10,
                'search' => '',
                'order_column' => 'date_op',
                'order_direction' => 'ASC'
            ]);

            if (!empty($result_10_p3['data']) && isset($result_10_p3['data'][2])) {
                $ecriture_10_p3 = $result_10_p3['data'][2];
                $ecriture_25_2 = $result_25['data'][22];

                $this->assertEquals($ecriture_10_p3['id'], $ecriture_25_2['id'],
                    "Should be the same ecriture at position $target_position_2");
                    
                TestLogger::info("Testing ecriture ID {$ecriture_10_p3['id']} at global position $target_position_2:");
                TestLogger::info("  - With 10/page (page 3): solde = {$ecriture_10_p3['solde']}");
                TestLogger::info("  - With 25/page (page 1): solde = {$ecriture_25_2['solde']}");

                $this->assertEquals($ecriture_10_p3['solde'], $ecriture_25_2['solde'],
                    "Balance for ecriture {$ecriture_10_p3['id']} must be identical regardless of pagination",
                    0.01);
            }
        }
    }

    /**
     * Test that balance increments are correct
     * Each line's balance should equal previous balance +/- operation
     */
    public function test_balance_increments_are_consistent() {
        // Find a compte with at least 100 ecritures
        $year = date('Y');
        $section = $this->CI->session->userdata('section');

        $query = "SELECT compte1 as id, COUNT(*) as cnt
                  FROM ecritures
                  WHERE YEAR(date_op) = '$year' AND club = $section
                  GROUP BY compte1
                  HAVING cnt >= 100
                  LIMIT 1";
        $result = $this->CI->db->query($query)->row_array();

        if (!$result) {
            $this->markTestSkipped("Need a compte with at least 20 ecritures for increment test");
            return;
        }
        
        $compte_to_test = $result['id'];
        TestLogger::info("\nTesting balance increments for compte $compte_to_test");

        // Get a page of data
        $result = $this->CI->ecritures_model->get_datatable_data([
            'compte' => $compte_to_test,
            'start' => 0,
            'length' => 20,
            'search' => '',
            'order_column' => 'date_op',
            'order_direction' => 'ASC'
        ]);

        $this->assertNotEmpty($result['data'], "Should have data");
        $this->assertGreaterThan(1, count($result['data']), "Should have at least 2 rows to test increments");

        $data = $result['data'];
        TestLogger::info("\nTesting balance increments for compte $compte_to_test:");

        // Check that each balance increment matches the operation
        for ($i = 1; $i < count($data); $i++) {
            $prev = $data[$i - 1];
            $curr = $data[$i];

            $prev_solde = floatval($prev['solde']);
            $curr_solde = floatval($curr['solde']);

            // Determine if current operation is debit or credit
            if (isset($curr['debit']) && $curr['debit'] !== '') {
                // Debit decreases balance
                $operation = -floatval($curr['debit']);
            } else {
                // Credit increases balance
                $operation = floatval($curr['credit']);
            }

            $expected_solde = $prev_solde + $operation;

            TestLogger::info("  Line " . ($i + 1) . " (ID {$curr['id']}): prev={$prev_solde}, op={$operation}, curr={$curr_solde}, expected={$expected_solde}\n");

            $this->assertEquals($expected_solde, $curr_solde,
                "Balance increment error at line " . ($i + 1) . " (ID {$curr['id']}). " .
                "Previous balance: {$prev_solde}, Operation: {$operation}, " .
                "Expected: {$expected_solde}, Got: {$curr_solde}",
                0.01);
        }
    }

    /**
     * Test that first page balance calculation is correct
     */
    public function test_first_page_balance_is_correct() {
        // Find a compte with ecritures
        $year = date('Y');
        $section = $this->CI->session->userdata('section');

        $query = "SELECT compte1 as id, COUNT(*) as cnt
                  FROM ecritures
                  WHERE YEAR(date_op) = '$year' AND club = $section
                  GROUP BY compte1
                  HAVING cnt >= 10
                  LIMIT 1";
        $result = $this->CI->db->query($query)->row_array();

        if (!$result) {
            $this->markTestSkipped("Need a compte with at least 10 ecritures");
            return;
        }
        
        $compte_to_test = $result['id'];

        // Get first page
        $result = $this->CI->ecritures_model->get_datatable_data([
            'compte' => $compte_to_test,
            'start' => 0,
            'length' => 10,
            'search' => '',
            'order_column' => 'date_op',
            'order_direction' => 'ASC'
        ]);

        $this->assertNotEmpty($result['data'], "Should have data");

        $first_row = $result['data'][0];
        $first_solde = floatval($first_row['solde']);

        TestLogger::info("\nTesting first page balance for compte $compte_to_test:");
        TestLogger::info("  First row ID: {$first_row['id']}, Date: {$first_row['date_op']}");
        TestLogger::info("  First row balance: {$first_solde}");

        // Calculate expected initial balance the same way get_datatable_data() does:
        // Use solde_compte() before the date + solde_jour() for the same day before the ID
        $expected_initial = $this->CI->ecritures_model->solde_compte($compte_to_test, $first_row['date_op'], '<');
        $expected_initial += $this->CI->ecritures_model->solde_jour($compte_to_test, $first_row['date_op'], $first_row['id']);

        // Add the current operation to get the balance AFTER this ecriture
        if (isset($first_row['debit']) && $first_row['debit'] !== '') {
            $expected_initial -= floatval($first_row['debit']);
        } else {
            $expected_initial += floatval($first_row['credit']);
        }

        TestLogger::info("  Expected balance (independently calculated): {$expected_initial}");

        $this->assertEquals($expected_initial, $first_solde,
            "First row balance should match independently calculated value",
            0.01);
    }

    /**
     * Test balance calculation with different page starts
     */
    public function test_balance_with_different_page_starts() {
        // Find a compte with many ecritures
        $year = date('Y');
        $section = $this->CI->session->userdata('section');

        $query = "SELECT compte1 as id, COUNT(*) as cnt
                  FROM ecritures
                  WHERE YEAR(date_op) = '$year' AND club = $section
                  GROUP BY compte1
                  HAVING cnt >= 25
                  LIMIT 1";
        $result = $this->CI->db->query($query)->row_array();

        if (!$result) {
            $this->markTestSkipped("Need a compte with at least 25 ecritures");
            return;
        }
        
        $compte_to_test = $result['id'];

        TestLogger::info("\nTesting balance with different page starts for compte $compte_to_test:");

        // Get overlapping pages and verify the overlapping ecriture has same balance
        // Page 1: start=0, length=15 (ecritures 0-14)
        // Page 2: start=10, length=15 (ecritures 10-24)
        // Overlap: ecritures 10-14 should have identical balances

        $page1 = $this->CI->ecritures_model->get_datatable_data([
            'compte' => $compte_to_test,
            'start' => 0,
            'length' => 15,
            'search' => '',
            'order_column' => 'date_op',
            'order_direction' => 'ASC'
        ]);

        $page2 = $this->CI->ecritures_model->get_datatable_data([
            'compte' => $compte_to_test,
            'start' => 10,
            'length' => 15,
            'search' => '',
            'order_column' => 'date_op',
            'order_direction' => 'ASC'
        ]);

        $this->assertNotEmpty($page1['data'], "Should have data for page 1");
        $this->assertNotEmpty($page2['data'], "Should have data for page 2");

        // Check overlapping ecritures (positions 10-14 from page1 = positions 0-4 from page2)
        $overlap_count = min(5, count($page1['data']) - 10, count($page2['data']));

        for ($i = 0; $i < $overlap_count; $i++) {
            $page1_idx = 10 + $i; // Global position 10, 11, 12, 13, 14
            $page2_idx = $i;      // Local position 0, 1, 2, 3, 4 in page 2

            if (isset($page1['data'][$page1_idx]) && isset($page2['data'][$page2_idx])) {
                $e1 = $page1['data'][$page1_idx];
                $e2 = $page2['data'][$page2_idx];

                $this->assertEquals($e1['id'], $e2['id'],
                    "Overlapping ecritures should have same ID at global position " . (10 + $i));

                TestLogger::info("  Position " . (10 + $i) . " (ID {$e1['id']}): page1 solde={$e1['solde']}, page2 solde={$e2['solde']}\n");

                $this->assertEquals($e1['solde'], $e2['solde'],
                    "Overlapping ecriture ID {$e1['id']} must have identical balance. " .
                    "Page 1: {$e1['solde']}, Page 2: {$e2['solde']}",
                    0.01);
            }
        }
    }
}
