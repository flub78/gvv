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

    /**
     * Test to_list() function - converts hash to list
     */
    public function testToListFunction()
    {
        if (function_exists('to_list')) {
            $hash = array(
                'key1' => array('value1', 'value2'),
                'key2' => array('value3', 'value4'),
                'key3' => array('value5', 'value6')
            );

            $list = to_list($hash);

            $this->assertIsArray($list, "to_list should return an array");
            $this->assertCount(3, $list, "List should have 3 elements");
            $this->assertEquals(array('value1', 'value2'), $list[0], "First element should be first hash value");
            $this->assertEquals(array('value3', 'value4'), $list[1], "Second element should be second hash value");
            $this->assertEquals(array('value5', 'value6'), $list[2], "Third element should be third hash value");
        } else {
            $this->markTestSkipped('to_list function not available');
        }
    }

    /**
     * Test to_list() with empty array
     */
    public function testToListEmptyArray()
    {
        if (function_exists('to_list')) {
            $hash = array();
            $list = to_list($hash);

            $this->assertIsArray($list, "to_list should return an array");
            $this->assertCount(0, $list, "Empty hash should produce empty list");
        } else {
            $this->markTestSkipped('to_list function not available');
        }
    }

    /**
     * Test highlight() function with HTML enabled
     */
    public function testHighlightWithHtml()
    {
        if (function_exists('highlight')) {
            $str = "Test String";
            $result = highlight($str, true);

            $this->assertIsString($result, "highlight should return a string");
            $this->assertStringContainsString('<h4>', $result, "Should contain h4 tag");
            $this->assertStringContainsString('Test String', $result, "Should contain the original string");
            $this->assertStringContainsString('</h4>', $result, "Should contain closing h4 tag");
            $this->assertEquals('<h4>Test String</h4>', $result, "Should wrap string in h4 tags");
        } else {
            $this->markTestSkipped('highlight function not available');
        }
    }

    /**
     * Test highlight() function with HTML disabled
     */
    public function testHighlightWithoutHtml()
    {
        if (function_exists('highlight')) {
            $str = "Test String";
            $result = highlight($str, false);

            $this->assertIsString($result, "highlight should return a string");
            $this->assertEquals('Test String', $result, "Should return the original string without tags");
            $this->assertStringNotContainsString('<h4>', $result, "Should not contain h4 tag");
        } else {
            $this->markTestSkipped('highlight function not available');
        }
    }

    /**
     * Test checkbox_field() function
     */
    public function testCheckboxFieldFunction()
    {
        if (function_exists('checkbox_field')) {
            $checkbox = checkbox_field('test_field', 1, true);

            $this->assertIsString($checkbox, "checkbox_field should return a string");
            $this->assertStringContainsString('test_field', $checkbox, "Checkbox should contain the field name");
            $this->assertStringContainsString('checkbox', $checkbox, "Should be a checkbox input");
        } else {
            $this->markTestSkipped('checkbox_field function not available');
        }
    }

    /**
     * Test checkbox_field() with unchecked state
     */
    public function testCheckboxFieldUnchecked()
    {
        if (function_exists('checkbox_field')) {
            $checkbox = checkbox_field('test_field', 0, false);

            $this->assertIsString($checkbox, "checkbox_field should return a string");
            $this->assertStringContainsString('test_field', $checkbox, "Checkbox should contain the field name");
        } else {
            $this->markTestSkipped('checkbox_field function not available');
        }
    }

    /**
     * Test radio_field() function
     */
    public function testRadioFieldFunction()
    {
        if (function_exists('radio_field')) {
            $values = array('Option A' => 1, 'Option B' => 2, 'Option C' => 3);
            $result = radio_field('test_radio', 2, $values);

            $this->assertIsString($result, "radio_field should return a string");
            $this->assertStringContainsString('test_radio', $result, "Should contain the field name");
            $this->assertStringContainsString('Option A', $result, "Should contain first option");
            $this->assertStringContainsString('Option B', $result, "Should contain second option");
            $this->assertStringContainsString('Option C', $result, "Should contain third option");
            $this->assertStringContainsString('radio', $result, "Should contain radio inputs");
        } else {
            $this->markTestSkipped('radio_field function not available');
        }
    }

    /**
     * Test radio_field() with attributes
     */
    public function testRadioFieldWithAttributes()
    {
        if (function_exists('radio_field')) {
            $values = array('Yes' => 1, 'No' => 0);
            $attrs = 'class="custom-radio"';
            $result = radio_field('agree', 1, $values, $attrs);

            $this->assertIsString($result, "radio_field should return a string");
            $this->assertStringContainsString('agree', $result, "Should contain the field name");
            $this->assertStringContainsString('Yes', $result, "Should contain Yes option");
            $this->assertStringContainsString('No', $result, "Should contain No option");
        } else {
            $this->markTestSkipped('radio_field function not available');
        }
    }

    /**
     * Test enumerate_radio_fields() function
     */
    public function testEnumerateRadioFieldsFunction()
    {
        if (function_exists('enumerate_radio_fields')) {
            $values = array(0 => 'Tous', 1 => 'Club', 2 => 'Privé', 3 => 'Extérieur');
            $result = enumerate_radio_fields($values, 'filter_proprio', 0);

            $this->assertIsString($result, "enumerate_radio_fields should return a string");
            $this->assertStringContainsString('filter_proprio', $result, "Should contain the field name");
            $this->assertStringContainsString('Tous', $result, "Should contain 'Tous' option");
            $this->assertStringContainsString('Club', $result, "Should contain 'Club' option");
            $this->assertStringContainsString('Privé', $result, "Should contain 'Privé' option");
            $this->assertStringContainsString('Extérieur', $result, "Should contain 'Extérieur' option");
        } else {
            $this->markTestSkipped('enumerate_radio_fields function not available');
        }
    }

    /**
     * Test enumerate_radio_fields() with different selected value
     */
    public function testEnumerateRadioFieldsWithSelection()
    {
        if (function_exists('enumerate_radio_fields')) {
            $values = array(0 => 'All', 1 => 'Active', 2 => 'Inactive');
            $result = enumerate_radio_fields($values, 'status_filter', 1);

            $this->assertIsString($result, "enumerate_radio_fields should return a string");
            $this->assertStringContainsString('status_filter', $result, "Should contain the field name");
            $this->assertStringContainsString('checked', $result, "Should have checked attribute");
        } else {
            $this->markTestSkipped('enumerate_radio_fields function not available');
        }
    }

    /**
     * Test add_first_row() with empty table
     */
    public function testAddFirstRowEmptyTable()
    {
        if (function_exists('add_first_row')) {
            $table = array();
            $result = add_first_row($table, array('Header1', 'Header2'));

            $this->assertCount(1, $table, "Table should have 1 row after adding to empty table");
            $this->assertEquals(1, $result, "Should return 1 for empty table");
            $this->assertEquals('Header1', $table[0][0], "First element should be 'Header1'");
        } else {
            $this->markTestSkipped('add_first_row function not available');
        }
    }

    /**
     * Test dropdown_field() with null attributes
     */
    public function testDropdownFieldNullAttrs()
    {
        if (function_exists('dropdown_field')) {
            $options = array('opt1' => 'Option 1', 'opt2' => 'Option 2');
            $dropdown = dropdown_field('test_select', 'opt1', $options, null);

            $this->assertIsString($dropdown, "dropdown_field should return a string");
            $this->assertStringContainsString('form-control', $dropdown, "Should contain default class");
        } else {
            $this->markTestSkipped('dropdown_field function not available');
        }
    }

    /**
     * Test dropdown_field() with empty options
     */
    public function testDropdownFieldEmptyOptions()
    {
        if (function_exists('dropdown_field')) {
            $options = array();
            $dropdown = dropdown_field('test_select', '', $options, 'class="custom-select"');

            $this->assertIsString($dropdown, "dropdown_field should return a string");
            $this->assertStringContainsString('test_select', $dropdown, "Should contain field name");
        } else {
            $this->markTestSkipped('dropdown_field function not available');
        }
    }

    /**
     * Test account_sums() function - calculates account summary totals
     */
    public function testAccountSumsFunction()
    {
        if (function_exists('account_sums') && function_exists('euro')) {
            $row = array(
                'total_debit' => 5000.00,
                'total_credit' => 7500.00
            );
            $title = "Total Général";

            $result = account_sums($row, $title);

            $this->assertIsArray($result, "account_sums should return an array");
            $this->assertCount(1, $result, "Should return one row");
            $this->assertCount(7, $result[0], "Row should have 7 columns");
            $this->assertEquals($title, $result[0][1], "Second column should be the title");
        } else {
            $this->markTestSkipped('account_sums or euro function not available');
        }
    }

    /**
     * Test account_sums() with negative solde (debit > credit)
     */
    public function testAccountSumsNegativeSolde()
    {
        if (function_exists('account_sums') && function_exists('euro')) {
            $row = array(
                'total_debit' => 10000.00,
                'total_credit' => 3000.00
            );
            $title = "Charges";

            $result = account_sums($row, $title);

            $this->assertIsArray($result, "account_sums should return an array");
            // When solde < 0, column 5 has the absolute value, column 6 is nbs
            $this->assertStringContainsString('€', $result[0][5] ?? '', "Fifth column should contain euro formatted value");
        } else {
            $this->markTestSkipped('account_sums or euro function not available');
        }
    }

    /**
     * Test display_form_table() function
     */
    public function testDisplayFormTableFunction()
    {
        if (function_exists('display_form_table')) {
            $table = array(
                array('Name:', 'John Doe'),
                array('Email:', 'john@example.com'),
                array('Phone:', '123-456-7890')
            );

            ob_start();
            display_form_table($table);
            $output = ob_get_clean();

            $this->assertIsString($output, "display_form_table should produce string output");
            $this->assertStringContainsString('<table>', $output, "Should contain table tag");
            $this->assertStringContainsString('</table>', $output, "Should contain closing table tag");
            $this->assertStringContainsString('<tr>', $output, "Should contain table rows");
            $this->assertStringContainsString('<td', $output, "Should contain table cells");
            $this->assertStringContainsString('Name:', $output, "Should contain table content");
            $this->assertStringContainsString('John Doe', $output, "Should contain table content");
        } else {
            $this->markTestSkipped('display_form_table function not available');
        }
    }

    /**
     * Test display_form_table() with empty table
     */
    public function testDisplayFormTableEmpty()
    {
        if (function_exists('display_form_table')) {
            $table = array();

            ob_start();
            display_form_table($table);
            $output = ob_get_clean();

            $this->assertIsString($output, "display_form_table should produce string output");
            $this->assertStringContainsString('<table>', $output, "Should contain table tag even when empty");
            $this->assertStringContainsString('</table>', $output, "Should contain closing table tag");
        } else {
            $this->markTestSkipped('display_form_table function not available');
        }
    }

    /**
     * Test input_field() with various attributes
     */
    public function testInputFieldWithAttributes()
    {
        if (function_exists('input_field')) {
            $attrs = array('class' => 'form-control', 'id' => 'email_field');
            $input = input_field("email", "test@example.com", $attrs);

            $this->assertIsString($input, "input_field should return a string");
            $this->assertStringContainsString('email', $input, "Input should contain the field name");
            $this->assertStringContainsString('test@example.com', $input, "Input should contain the value");
            $this->assertStringContainsString('form-control', $input, "Input should contain the class attribute");
        } else {
            $this->markTestSkipped('input_field function not available');
        }
    }

    /**
     * Test e_form_dropdown() function - wrapper for form_dropdown that echoes
     */
    public function testEFormDropdownFunction()
    {
        if (function_exists('e_form_dropdown')) {
            $options = array('opt1' => 'Option 1', 'opt2' => 'Option 2');

            ob_start();
            e_form_dropdown('test_select', $options, 'opt1', 'class="form-control"');
            $output = ob_get_clean();

            $this->assertIsString($output, "e_form_dropdown should produce string output");
            $this->assertStringContainsString('test_select', $output, "Should contain select name");
            $this->assertStringContainsString('Option 1', $output, "Should contain options");
        } else {
            $this->markTestSkipped('e_form_dropdown function not available');
        }
    }

    /**
     * Test checkbox_array() with checked value
     */
    public function testCheckboxArrayChecked()
    {
        if (function_exists('checkbox_array')) {
            $array_val = array(1 => 'value1', 2 => 'value2');
            $checkbox = checkbox_array('test_array', 1, $array_val);

            $this->assertIsString($checkbox, "checkbox_array should return a string");
            $this->assertStringContainsString('test_array', $checkbox, "Should contain array name");
            $this->assertStringContainsString('checkbox', $checkbox, "Should be a checkbox input");
        } else {
            $this->markTestSkipped('checkbox_array function not available');
        }
    }

    /**
     * Test checkbox_array() with unchecked value
     */
    public function testCheckboxArrayUnchecked()
    {
        if (function_exists('checkbox_array')) {
            $array_val = array(1 => 'value1', 2 => 'value2');
            $checkbox = checkbox_array('test_array', 3, $array_val);

            $this->assertIsString($checkbox, "checkbox_array should return a string");
            $this->assertStringContainsString('test_array', $checkbox, "Should contain array name");
        } else {
            $this->markTestSkipped('checkbox_array function not available');
        }
    }

    /**
     * Test checkbox_array() with null array
     */
    public function testCheckboxArrayNullArray()
    {
        if (function_exists('checkbox_array')) {
            $checkbox = checkbox_array('test_array', 1, null);

            $this->assertIsString($checkbox, "checkbox_array should return a string");
            $this->assertStringContainsString('test_array', $checkbox, "Should contain array name");
        } else {
            $this->markTestSkipped('checkbox_array function not available');
        }
    }

    /**
     * Test add_first_col() with more column values than table rows
     */
    public function testAddFirstColExtraValues()
    {
        if (function_exists('add_first_col')) {
            $table = array(
                array('A', 'B'),
                array('C', 'D')
            );

            $result = add_first_col($table, array('1', '2', '3', '4', '5'));

            $this->assertEquals(2, $result, "Should return number of rows processed");
            $this->assertEquals('1', $table[0][0], "First row should have first column");
            $this->assertEquals('2', $table[1][0], "Second row should have first column");
        } else {
            $this->markTestSkipped('add_first_col function not available');
        }
    }
}
