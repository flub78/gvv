<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for form_elements helper functions
 * 
 * Tests checkbox arrays, input fields, dropdown fields, and table manipulation functions
 */
class FormElementsHelperTest extends TestCase
{
    public function setUp(): void
    {
        // Load form_elements helper
        if (!function_exists('checkbox_array')) {
            require_once APPPATH . 'helpers/form_elements_helper.php';
        }
    }

    /**
     * Test checkbox_array() function
     */
    public function testCheckboxArrayFunction()
    {
        if (function_exists('checkbox_array')) {
            $mniveaux = 0;
            $checkbox_array = checkbox_array('mniveau', 1, $mniveaux);
            
            $this->assertIsString($checkbox_array, "checkbox_array should return a string");
            $this->assertStringContainsString('mniveau', $checkbox_array, "Checkbox should contain the field name");
        } else {
            $this->markTestSkipped('checkbox_array function not available');
        }
    }

    /**
     * Test input_field() function
     */
    public function testInputFieldFunction()
    {
        if (function_exists('input_field')) {
            $input = input_field("name", "value", array());
            
            $this->assertIsString($input, "input_field should return a string");
            $this->assertStringContainsString('name', $input, "Input should contain the field name");
            $this->assertStringContainsString('value', $input, "Input should contain the field value");
        } else {
            $this->markTestSkipped('input_field function not available');
        }
    }

    /**
     * Test dropdown_field() function
     */
    public function testDropdownFieldFunction()
    {
        if (function_exists('dropdown_field')) {
            $mlogin = 'moi';
            $pilote_selector = array();
            $dropdown = dropdown_field('mlogin', $mlogin, $pilote_selector, "id='selector' onchange='new_selection();'");
            
            $this->assertIsString($dropdown, "dropdown_field should return a string");
        } else {
            $this->markTestSkipped('dropdown_field function not available');
        }
    }

    /**
     * Test add_first_row() function
     */
    public function testAddFirstRowFunction()
    {
        if (function_exists('add_first_row')) {
            $table = array(
                array(6, 12, 17),
                array(7, 11, 23)
            );
            
            $this->assertCount(2, $table, "Initial table should have 2 rows");
            
            add_first_row($table, array('Jan', 'Fev', 'Mar'));
            
            $this->assertCount(3, $table, "Table should have 3 rows after add_first_row");
            $this->assertEquals('Jan', $table[0][0], "First element of first row should be 'Jan'");
            $this->assertEquals('Fev', $table[0][1], "Second element of first row should be 'Fev'");
            $this->assertEquals('Mar', $table[0][2], "Third element of first row should be 'Mar'");
        } else {
            $this->markTestSkipped('add_first_row function not available');
        }
    }

    /**
     * Test add_first_col() function
     */
    public function testAddFirstColFunction()
    {
        if (function_exists('add_first_col')) {
            $table = array(
                array(6, 12, 17),
                array(7, 11, 23)
            );
            
            // Add header row first
            add_first_row($table, array('Jan', 'Fev', 'Mar'));
            
            // Now add first column
            add_first_col($table, array('Année', '2013', '2014'));
            
            $this->assertEquals('Année', $table[0][0], 'First cell should be "Année"');
            $this->assertEquals('2013', $table[1][0], 'Second row first cell should be "2013"');
            $this->assertEquals(6, $table[1][1], 'Second row second cell should be 6');
            $this->assertEquals(11, $table[2][2], 'Third row third cell should be 11');
        } else {
            $this->markTestSkipped('add_first_col function not available');
        }
    }

    /**
     * Test complete table manipulation workflow
     */
    public function testCompleteTableManipulation()
    {
        if (function_exists('add_first_row') && function_exists('add_first_col')) {
            $table = array(
                array(6, 12, 17),
                array(7, 11, 23)
            );
            
            // Initial state
            $this->assertCount(2, $table, "Initial table size should be 2");
            
            // Add header row
            add_first_row($table, array('Jan', 'Fev', 'Mar'));
            $this->assertCount(3, $table, "Table size after add_first_row should be 3");
            
            // Add first column with labels
            add_first_col($table, array('Année', '2013', '2014'));
            
            // Verify final structure
            $this->assertEquals('Année', $table[0][0], '$table[0][0] should be "Année"');
            $this->assertEquals('2013', $table[1][0], '$table[1][0] should be "2013"');
            $this->assertEquals(6, $table[1][1], '$table[1][1] should be 6');
            $this->assertEquals(11, $table[2][2], '$table[2][2] should be 11');
        } else {
            $this->markTestSkipped('Table manipulation functions not available');
        }
    }
}
