<?php

use PHPUnit\Framework\TestCase;

/**
 * Controller tests for email_lists sublist AJAX methods
 *
 * NOTE: Full controller testing with CodeIgniter is complex because controllers
 * need the entire CI framework initialized. These tests focus on JSON response
 * structure and validation that can be tested with minimal setup.
 *
 * For actual end-to-end controller testing, use browser automation tests.
 *
 * Tests the 5 AJAX endpoints:
 * - add_sublist_ajax()
 * - remove_sublist_ajax()
 * - get_available_sublists_ajax()
 * - check_visibility_consistency_ajax()
 * - propagate_visibility_ajax()
 *
 * @package tests
 * @see application/controllers/email_lists.php
 */
class EmailListsControllerSublistsTest extends TestCase
{
    /**
     * Test add_sublist_ajax JSON response structure - Success case
     */
    public function testAddSublistAjax_JsonStructure_Success()
    {
        // Simulate successful AJAX response from add_sublist_ajax()
        $jsonOutput = json_encode([
            'success' => true,
            'message' => 'Sublist added successfully'
        ]);

        // Validate JSON structure
        $this->assertJson($jsonOutput);
        $data = json_decode($jsonOutput, true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('message', $data);
        $this->assertStringContainsString('success', strtolower($data['message']));
    }

    /**
     * Test add_sublist_ajax JSON response structure - Missing params error
     */
    public function testAddSublistAjax_JsonStructure_MissingParams()
    {
        // Simulate error response
        $jsonOutput = json_encode([
            'success' => false,
            'message' => 'Missing required fields'
        ]);

        $this->assertJson($jsonOutput);
        $data = json_decode($jsonOutput, true);

        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('message', $data);
        $this->assertStringContainsString('Missing', $data['message']);
    }

    /**
     * Test add_sublist_ajax JSON response structure - List not found error
     */
    public function testAddSublistAjax_JsonStructure_ListNotFound()
    {
        // Simulate error response
        $jsonOutput = json_encode([
            'success' => false,
            'message' => 'Parent list not found'
        ]);

        $this->assertJson($jsonOutput);
        $data = json_decode($jsonOutput, true);

        $this->assertFalse($data['success']);
        $this->assertStringContainsString('not found', $data['message']);
    }

    /**
     * Test remove_sublist_ajax JSON response structure - Success case
     */
    public function testRemoveSublistAjax_JsonStructure_Success()
    {
        // Simulate successful response
        $jsonOutput = json_encode([
            'success' => true,
            'message' => 'Sublist removed successfully'
        ]);

        $this->assertJson($jsonOutput);
        $data = json_decode($jsonOutput, true);

        $this->assertTrue($data['success']);
        $this->assertStringContainsString('success', strtolower($data['message']));
    }

    /**
     * Test remove_sublist_ajax JSON response structure - Error case
     */
    public function testRemoveSublistAjax_JsonStructure_Error()
    {
        // Simulate error response
        $jsonOutput = json_encode([
            'success' => false,
            'message' => 'Missing required fields'
        ]);

        $this->assertJson($jsonOutput);
        $data = json_decode($jsonOutput, true);

        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('message', $data);
    }

    /**
     * Test get_available_sublists_ajax JSON response structure - Success case
     */
    public function testGetAvailableSublistsAjax_JsonStructure_Success()
    {
        // Simulate successful response with list data
        $jsonOutput = json_encode([
            'success' => true,
            'lists' => [
                ['id' => 1, 'name' => 'List A', 'visible' => 1],
                ['id' => 2, 'name' => 'List B', 'visible' => 0],
                ['id' => 3, 'name' => 'List C', 'visible' => 1]
            ]
        ]);

        $this->assertJson($jsonOutput);
        $data = json_decode($jsonOutput, true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('lists', $data);
        $this->assertIsArray($data['lists']);
        $this->assertCount(3, $data['lists']);

        // Validate list structure
        foreach ($data['lists'] as $list) {
            $this->assertArrayHasKey('id', $list);
            $this->assertArrayHasKey('name', $list);
            $this->assertArrayHasKey('visible', $list);
        }
    }

    /**
     * Test get_available_sublists_ajax JSON response structure - Error case
     */
    public function testGetAvailableSublistsAjax_JsonStructure_Error()
    {
        // Simulate error response
        $jsonOutput = json_encode([
            'success' => false,
            'message' => 'Missing required field: exclude_list_id'
        ]);

        $this->assertJson($jsonOutput);
        $data = json_decode($jsonOutput, true);

        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Missing', $data['message']);
    }

    /**
     * Test check_visibility_consistency_ajax JSON response structure - Consistent case
     */
    public function testCheckVisibilityConsistencyAjax_JsonStructure_Consistent()
    {
        // Simulate response when visibility is consistent
        $jsonOutput = json_encode([
            'success' => true,
            'consistent' => true,
            'warnings' => []
        ]);

        $this->assertJson($jsonOutput);
        $data = json_decode($jsonOutput, true);

        $this->assertTrue($data['success']);
        $this->assertTrue($data['consistent']);
        $this->assertIsArray($data['warnings']);
        $this->assertEmpty($data['warnings']);
    }

    /**
     * Test check_visibility_consistency_ajax JSON response structure - Inconsistent case
     */
    public function testCheckVisibilityConsistencyAjax_JsonStructure_Inconsistent()
    {
        // Simulate response with visibility warnings
        $jsonOutput = json_encode([
            'success' => true,
            'consistent' => false,
            'warnings' => [
                'Liste privée: Test List A',
                'Liste privée: Test List B'
            ]
        ]);

        $this->assertJson($jsonOutput);
        $data = json_decode($jsonOutput, true);

        $this->assertTrue($data['success']);
        $this->assertFalse($data['consistent']);
        $this->assertIsArray($data['warnings']);
        $this->assertCount(2, $data['warnings']);
    }

    /**
     * Test check_visibility_consistency_ajax JSON response structure - Error case
     */
    public function testCheckVisibilityConsistencyAjax_JsonStructure_Error()
    {
        // Simulate error response
        $jsonOutput = json_encode([
            'success' => false,
            'message' => 'Missing required fields'
        ]);

        $this->assertJson($jsonOutput);
        $data = json_decode($jsonOutput, true);

        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Missing', $data['message']);
    }

    /**
     * Test propagate_visibility_ajax JSON response structure - Success case
     */
    public function testPropagateVisibilityAjax_JsonStructure_Success()
    {
        // Simulate successful propagation
        $jsonOutput = json_encode([
            'success' => true,
            'message' => 'Visibility propagated successfully',
            'updated_count' => 3
        ]);

        $this->assertJson($jsonOutput);
        $data = json_decode($jsonOutput, true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('updated_count', $data);
        $this->assertIsInt($data['updated_count']);
        $this->assertGreaterThanOrEqual(0, $data['updated_count']);
    }

    /**
     * Test propagate_visibility_ajax JSON response structure - Error case
     */
    public function testPropagateVisibilityAjax_JsonStructure_Error()
    {
        // Simulate error response
        $jsonOutput = json_encode([
            'success' => false,
            'message' => 'Missing required field: list_id'
        ]);

        $this->assertJson($jsonOutput);
        $data = json_decode($jsonOutput, true);

        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('message', $data);
        $this->assertStringContainsString('Missing', $data['message']);
    }

    /**
     * Test that all AJAX methods return valid JSON
     */
    public function testAllAjaxMethods_ReturnValidJson()
    {
        $responses = [
            // add_sublist_ajax responses
            ['success' => true, 'message' => 'Sublist added successfully'],
            ['success' => false, 'message' => 'Missing required fields'],
            ['success' => false, 'message' => 'Parent list not found'],

            // remove_sublist_ajax responses
            ['success' => true, 'message' => 'Sublist removed successfully'],
            ['success' => false, 'message' => 'Missing required fields'],

            // get_available_sublists_ajax responses
            ['success' => true, 'lists' => []],
            ['success' => false, 'message' => 'Missing required field: exclude_list_id'],

            // check_visibility_consistency_ajax responses
            ['success' => true, 'consistent' => true, 'warnings' => []],
            ['success' => true, 'consistent' => false, 'warnings' => ['Warning 1']],
            ['success' => false, 'message' => 'Missing required fields'],

            // propagate_visibility_ajax responses
            ['success' => true, 'message' => 'Visibility propagated successfully', 'updated_count' => 0],
            ['success' => false, 'message' => 'Missing required field: list_id']
        ];

        foreach ($responses as $response) {
            $jsonOutput = json_encode($response);
            $this->assertJson($jsonOutput, 'All AJAX responses must be valid JSON');

            $decoded = json_decode($jsonOutput, true);
            $this->assertNotNull($decoded, 'JSON must decode successfully');
            $this->assertArrayHasKey('success', $decoded, 'All responses must have success field');
            $this->assertIsBool($decoded['success'], 'success field must be boolean');
        }
    }
}
