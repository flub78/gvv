<?php

require_once __DIR__ . '/../integration/TransactionalTestCase.php';

/**
 * MySQL Integration Test for Ecritures_model::solde_compte_gestion()
 *
 * This test uses a real MySQL database connection to test the solde_compte_gestion method.
 * It verifies that the method executes without errors and returns valid results.
 * Uses transactions to restore the database to its initial state after each test.
 *
 * Requirements:
 * - MySQL database connection (configured in integration_bootstrap.php)
 * - InnoDB tables (for transaction support)
 * - Database credentials set in integration_bootstrap.php
 * - Test database with comptes and ecritures tables populated
 *
 * Usage:
 * phpunit --bootstrap application/tests/integration_bootstrap.php --configuration phpunit_mysql.xml application/tests/mysql/EcrituresModelSoldeCompteGestionMySqlTest.php
 */
class EcrituresModelSoldeCompteGestionMySqlTest extends TransactionalTestCase {
    /**
     * @var Ecritures_model
     */
    private $ecritures_model;

    /**
     * IDs of created test records for cleanup
     */
    private $created_ids = [];

    /**
     * Set up test environment with database transaction
     */
    public function setUp(): void {
        parent::setUp(); // Initializes $this->CI and starts transaction

        // Load Ecritures_model
        $this->CI->load->model('ecritures_model');
        $this->ecritures_model = $this->CI->ecritures_model;

        // Verify database connection
        if (!$this->CI->db->conn_id) {
            $this->markTestSkipped('Database connection not available');
        }
    }

    /**
     * Clean up after each test - rollback transaction
     */
    public function tearDown(): void {
        // Reset created IDs array
        $this->created_ids = [];

        parent::tearDown(); // Forces _trans_depth = 0 and rolls back transaction
    }

    /**
     * Test solde_compte_gestion with basic parameters
     * Verifies the method executes without PHP errors and returns a numeric result
     */
    public function testSoldeCompteGestionBasicExecution() {
        // Define test parameters - to be customized by user with actual test data
        $date_op = '2024-12-31';  // Date limite
        $compte = null;           // Pas de compte spécifique
        $codec_min = '';          // Pas de codec minimum
        $codec_max = '';          // Pas de codec maximum
        $section_id = 0;          // Pas de section spécifique

        // Execute the method
        $result = $this->ecritures_model->solde_compte_gestion(
            $date_op,
            $compte,
            $codec_min,
            $codec_max,
            $section_id
        );

        // echo "\nsolde_compte_gestion result: " . var_export($result, true) . "\n";

        // Verify result equals 0.0
        $this->assertEquals(0.0, $result, 'solde_compte_gestion should return 0.0 for basic execution');

        // Verify result is numeric (float or int)
        $this->assertIsNumeric($result, 'solde_compte_gestion should return a numeric value');

        // Verify no PHP errors occurred (test completes successfully)
        $this->assertTrue(true, 'Method executed without PHP errors');
    }

    /**
     * Test solde_compte_gestion with compte parameter
     * User will provide specific compte ID and expected result
     */
    public function testSoldeCompteGestionWithCompte() {
        // TODO: User to provide:
        // - $compte_id: ID of an existing compte in test database
        // - $date_op: Date for calculation
        // - $expected_solde: Expected balance result

        $date_op = '2024-12-31';
        $compte_id = 56;  // Compte de classe 707 recette
        $expected_solde = 1326.28;

        // Skip test if no compte ID provided
        if (empty($compte_id)) {
            $this->markTestSkipped('Test skipped - compte_id parameter not set by user');
            return;
        }

        // Execute the method
        $result = $this->ecritures_model->solde_compte_gestion(
            $date_op,
            $compte_id,
            '',
            '',
            1
        );

        $this->assertIsNumeric($result, 'solde_compte_gestion should return a numeric value');
        $this->assertEqualsWithDelta($expected_solde, $result, 0.0001, 'Balance should match expected value');

        // Another date
        $date_op = '2023-12-31';
        $expected_solde = 2750.00;

        $result = $this->ecritures_model->solde_compte_gestion(
            $date_op,
            $compte_id,
            '',
            '',
            1
        );

        $this->assertIsNumeric($result, 'solde_compte_gestion should return a numeric value');
        $this->assertEqualsWithDelta($expected_solde, $result, 0.0001, 'Balance should match expected value');

        // If the section does not match the account the function returns 0
        $expected_solde = 0.0;
        $result = $this->ecritures_model->solde_compte_gestion(
            $date_op,
            $compte_id,
            '',
            '',
            3
        );

        $this->assertIsNumeric($result, 'solde_compte_gestion should return a numeric value');
        $this->assertEqualsWithDelta($expected_solde, $result, 0.0001, 'Balance should match expected value');

        // another year
        $date_op = '2022-12-31';
        $expected_solde = 3117.46;
        $result = $this->ecritures_model->solde_compte_gestion(
            $date_op,
            $compte_id,
            '',
            '',
            1
        );

        $this->assertIsNumeric($result, 'solde_compte_gestion should return a numeric value');
        $this->assertEqualsWithDelta($expected_solde, $result, 0.0001, 'Balance should match expected value');

        // Now an account of class 606
        $compte_id = 60;  // Compte de classe 606 charge

        // another year
        $date_op = '2022-12-31';
        $expected_solde = 5258.61;
        $result = $this->ecritures_model->solde_compte_gestion(
            $date_op,
            $compte_id,
            '',
            '',
            1
        );

        $this->assertIsNumeric($result, 'solde_compte_gestion should return a numeric value');
        $this->assertEqualsWithDelta($expected_solde, $result, 0.0001, 'Balance should match expected value');

        // another year
        $date_op = '2023-12-01';
        $expected_solde = 4869.79;
        $result = $this->ecritures_model->solde_compte_gestion(
            $date_op,
            $compte_id,
            '',
            '',
            1
        );

        $this->assertIsNumeric($result, 'solde_compte_gestion should return a numeric value');
        $this->assertEqualsWithDelta($expected_solde, $result, 0.0001, 'Balance should match expected value');
    }

    /**
     * Test solde_compte_gestion with codec range
     * User will provide codec range and expected result
     */
    public function testSoldeCompteGestionWithCodecRange() {
        // TODO: User to provide:
        // - $codec_min: Minimum codec (e.g., '600' for charges)
        // - $codec_max: Maximum codec (e.g., '699' for charges)
        // - $date_op: Date for calculation
        // - $expected_solde: Expected balance result

        $date_op = '2023-12-31';
        $codec_min = '607';  // PLACEHOLDER - to be set by user (e.g., '600')
        $codec_max = '607';  // PLACEHOLDER - to be set by user (e.g., '699')

        $expected_solde = 38.00; // PLACEHOLDER - to be set by user
        $result = $this->ecritures_model->solde_compte_gestion(
            $date_op,
            '',
            $codec_min,
            $codec_max,
            1
        );
        // echo "\nsolde_compte_gestion result for codec range $codec_min-$codec_max: " . var_export($result, true) . "\n";
        $this->assertEqualsWithDelta($expected_solde, $result, 0.0001, 'Balance should match expected value');

        $date_op = '2022-10-31';
        $expected_solde = 78.80; // PLACEHOLDER - to be set by user
        $result = $this->ecritures_model->solde_compte_gestion(
            $date_op,
            '',
            $codec_min,
            $codec_max,
            1
        );
        $this->assertEqualsWithDelta($expected_solde, $result, 0.0001, 'Balance should match expected value');

        $codec_min = '75';  // PLACEHOLDER - to be set by user (e.g., '600')
        $codec_max = '75';  // PLACEHOLDER - to be set by user (e.g., '699')
        $date_op = '2022-12-31';
        $expected_solde = 30.00; // PLACEHOLDER - to be set by user
        $result = $this->ecritures_model->solde_compte_gestion(
            $date_op,
            '',
            $codec_min,
            $codec_max,
            1
        );
        $this->assertEqualsWithDelta($expected_solde, $result, 0.0001, 'Balance should match expected value');

        $codec_min = '708';  // PLACEHOLDER - to be set by user (e.g., '600')
        $codec_max = '708';  // PLACEHOLDER - to be set by user (e.g., '699')
        $date_op = '2022-12-31';
        $expected_solde = 5206.00; // PLACEHOLDER - to be set by user
        $result = $this->ecritures_model->solde_compte_gestion(
            $date_op,
            '',
            $codec_min,
            $codec_max,
            1
        );
        $this->assertEqualsWithDelta($expected_solde, $result, 0.0001, 'Balance should match expected value');

        $codec_min = '606';  // PLACEHOLDER - to be set by user (e.g., '600')
        $codec_max = '606';  // PLACEHOLDER - to be set by user (e.g., '699')
        $date_op = '2023-12-31';
        $expected_solde = 9493.81; // PLACEHOLDER - to be set by user
        $result = $this->ecritures_model->solde_compte_gestion(
            $date_op,
            '',
            $codec_min,
            $codec_max
        );
        $this->assertEqualsWithDelta($expected_solde, $result, 0.0001, 'Balance should match expected value');
    }

    /**
     * Test solde_compte_gestion with section filter
     * User will provide section ID and expected result
     */
    public function testSoldeCompteGestionWithSection() {
        // D'abord juste la section planeur
        $codec_min = '606';  // PLACEHOLDER - to be set by user (e.g., '600')
        $codec_max = '606';  // PLACEHOLDER - to be set by user (e.g., '699')
        $date_op = '2024-12-31';
        $expected_solde = 4245.16; // PLACEHOLDER - to be set by user
        $result = $this->ecritures_model->solde_compte_gestion(
            $date_op,
            '',
            $codec_min,
            $codec_max,
            1
        );
        $this->assertEqualsWithDelta($expected_solde, $result, 0.0001, 'Balance should match expected value');

        // Puis toutes les sections
        $expected_solde = 29060.16;
        $result = $this->ecritures_model->solde_compte_gestion(
            $date_op,
            '',
            $codec_min,
            $codec_max
        );
        $this->assertEqualsWithDelta($expected_solde, $result, 0.0001, 'Balance should match expected value');
    }

    /**
     * Test solde_compte_gestion with invalid date format
     * Verifies error handling for malformed dates
     */
    public function testSoldeCompteGestionWithInvalidDate() {
        $invalid_date = 'invalid-date';

        // Execute the method - should handle error gracefully
        $result = $this->ecritures_model->solde_compte_gestion(
            $invalid_date,
            '',
            '',
            '',
            0
        );

        // Method should return 0 for invalid date (as per implementation)
        $this->assertEquals(0, $result, 'Invalid date should return 0');
    }

    /**
     * Test solde_compte_gestion with empty database (no matching ecritures)
     * Verifies behavior when no data matches the criteria
     */
    public function testSoldeCompteGestionWithNoMatchingData() {
        // Use a future date where no ecritures should exist
        $future_date = '2099-12-31';

        // Execute the method
        $result = $this->ecritures_model->solde_compte_gestion(
            $future_date,
            '',
            '',
            '',
            0
        );

        // Should return 0 when no matching ecritures found
        $this->assertEquals(0, $result, 'No matching data should return 0');
    }

}
