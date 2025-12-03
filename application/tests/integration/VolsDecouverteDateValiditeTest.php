<?php

/**
 * GVV Gestion vol à voile
 * Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Integration tests for date_validite feature in vols_decouverte
 */

use PHPUnit\Framework\TestCase;

class VolsDecouverteDateValiditeTest extends TestCase
{
    protected $CI;
    protected $model;
    protected $test_ids = [];

    public function setUp(): void
    {
        $this->CI =& get_instance();
        $this->CI->load->model('vols_decouverte_model');
        $this->model = $this->CI->vols_decouverte_model;

        // Initialize session for testing - set year only, don't set empty filter dates
        $this->CI->session->set_userdata('vd_year', date('Y'));
        $this->CI->session->set_userdata('vd_filter_active', false);

        // Clean up any test data
        $this->cleanupTestData();
    }

    public function tearDown(): void
    {
        $this->cleanupTestData();
    }

    private function cleanupTestData()
    {
        if (!empty($this->test_ids)) {
            foreach ($this->test_ids as $id) {
                $this->CI->db->delete('vols_decouverte', array('id' => $id));
            }
            $this->test_ids = [];
        }
    }

    /**
     * Test that date_validite column exists and accepts NULL
     */
    public function testDateValiditeColumnExists()
    {
        // Query the information_schema to check column exists
        $query = $this->CI->db->query("
            SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'vols_decouverte'
            AND COLUMN_NAME = 'date_validite'
        ");

        $this->assertEquals(1, $query->num_rows(), 'Column date_validite should exist');

        $column = $query->row();
        $this->assertEquals('date_validite', $column->COLUMN_NAME);
        $this->assertEquals('date', $column->DATA_TYPE);
        $this->assertEquals('YES', $column->IS_NULLABLE, 'date_validite should accept NULL');
    }

    /**
     * Test that when date_validite is NULL, the system uses date_vente + 1 year
     */
    public function testValidityCalculationWithNullDateValidite()
    {
        // Create a test discovery flight without date_validite
        $test_data = [
            'id' => 990001,
            'date_vente' => '2024-06-15',
            'date_validite' => null,
            'club' => 1,
            'product' => 'TEST_PRODUCT',
            'saisie_par' => 'test_user',
            'cancelled' => 0
        ];

        $this->CI->db->insert('vols_decouverte', $test_data);
        $this->test_ids[] = 990001;

        // Retrieve via select_page which calculates validite
        $results = $this->model->select_page();

        // Find our test record
        $test_record = null;
        foreach ($results as $record) {
            if ($record['id'] == 990001) {
                $test_record = $record;
                break;
            }
        }

        $this->assertNotNull($test_record, 'Test record should be found');
        $this->assertEquals('2025-06-15', $test_record['validite'], 'Validite should be date_vente + 1 year');
    }

    /**
     * Test that when date_validite is set, the system uses it instead of calculating
     */
    public function testValidityUsesDateValiditeWhenSet()
    {
        // Create a test discovery flight with explicit date_validite
        $test_data = [
            'id' => 990002,
            'date_vente' => '2024-06-15',
            'date_validite' => '2025-12-31', // Different from date_vente + 1 year
            'club' => 1,
            'product' => 'TEST_PRODUCT',
            'saisie_par' => 'test_user',
            'cancelled' => 0
        ];

        $this->CI->db->insert('vols_decouverte', $test_data);
        $this->test_ids[] = 990002;

        // Retrieve via select_page
        $results = $this->model->select_page();

        // Find our test record
        $test_record = null;
        foreach ($results as $record) {
            if ($record['id'] == 990002) {
                $test_record = $record;
                break;
            }
        }

        $this->assertNotNull($test_record, 'Test record should be found');
        $this->assertEquals('2025-12-31', $test_record['validite'], 'Validite should use date_validite');
    }

    /**
     * Test that filter 'todo' includes flights with date_validite >= today
     */
    public function testFilterTodoWithDateValidite()
    {
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        // Create a test flight that should appear in 'todo' filter
        $test_data = [
            'id' => 990003,
            'date_vente' => '2023-01-01', // Old date
            'date_validite' => $tomorrow, // Still valid
            'club' => 1,
            'product' => 'TEST_PRODUCT',
            'saisie_par' => 'test_user',
            'cancelled' => 0,
            'date_vol' => null // Not done yet
        ];

        $this->CI->db->insert('vols_decouverte', $test_data);
        $this->test_ids[] = 990003;

        // Set filter to 'todo'
        $this->CI->session->set_userdata('vd_filter_active', true);
        $this->CI->session->set_userdata('vd_filter_type', 'todo');
        $this->CI->session->set_userdata('vd_year', date('Y'));

        // Retrieve filtered results
        $results = $this->model->select_page();

        // Find our test record
        $found = false;
        foreach ($results as $record) {
            if ($record['id'] == 990003) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'Flight with future date_validite should appear in todo filter');

        // Clear session
        $this->CI->session->unset_userdata('vd_filter_active');
        $this->CI->session->unset_userdata('vd_filter_type');
    }

    /**
     * Test that filter 'expired' includes flights with date_validite < today
     */
    public function testFilterExpiredWithDateValidite()
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        // Create a test flight that should appear in 'expired' filter
        $test_data = [
            'id' => 990004,
            'date_vente' => '2024-01-01',
            'date_validite' => $yesterday, // Expired
            'club' => 1,
            'product' => 'TEST_PRODUCT',
            'saisie_par' => 'test_user',
            'cancelled' => 0,
            'date_vol' => null // Not done yet
        ];

        $this->CI->db->insert('vols_decouverte', $test_data);
        $this->test_ids[] = 990004;

        // Set filter to 'expired'
        $this->CI->session->set_userdata('vd_filter_active', true);
        $this->CI->session->set_userdata('vd_filter_type', 'expired');
        $this->CI->session->set_userdata('vd_year', date('Y'));

        // Retrieve filtered results
        $results = $this->model->select_page();

        // Find our test record
        $found = false;
        foreach ($results as $record) {
            if ($record['id'] == 990004) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'Flight with past date_validite should appear in expired filter');

        // Clear session
        $this->CI->session->unset_userdata('vd_filter_active');
        $this->CI->session->unset_userdata('vd_filter_type');
    }

    /**
     * Test that existing flights without date_validite still work correctly
     */
    public function testBackwardCompatibilityWithNullDateValidite()
    {
        // This test ensures that flights created before the migration still work
        $old_date = date('Y-m-d', strtotime('-6 months'));

        $test_data = [
            'id' => 990005,
            'date_vente' => $old_date,
            'date_validite' => null, // As it would be for old records
            'club' => 1,
            'product' => 'TEST_PRODUCT',
            'saisie_par' => 'test_user',
            'cancelled' => 0
        ];

        $this->CI->db->insert('vols_decouverte', $test_data);
        $this->test_ids[] = 990005;

        // Retrieve via select_page
        $results = $this->model->select_page();

        // Find our test record
        $test_record = null;
        foreach ($results as $record) {
            if ($record['id'] == 990005) {
                $test_record = $record;
                break;
            }
        }

        $this->assertNotNull($test_record, 'Old record should still be retrievable');

        // Validite should be calculated as date_vente + 1 year
        $expected_validite = date('Y-m-d', strtotime($old_date . ' +1 year'));
        $this->assertEquals($expected_validite, $test_record['validite'], 'Old records should calculate validite correctly');
    }
}
