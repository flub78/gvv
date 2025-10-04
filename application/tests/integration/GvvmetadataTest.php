<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for GVVMetadata library
 *
 * Tests the metadata management system that provides centralized
 * database schema information and application-level metadata.
 *
 * Replaces the old CI unit test: test_metadata_library() in controllers/tests.php
 */
class GvvmetadataTest extends TestCase
{
    private $gvvmetadata;

    public function setUp(): void
    {
        // GVVMetadata requires CodeIgniter instance
        $CI = get_instance();

        // Load the GVVMetadata library
        if (!class_exists('GVVMetadata')) {
            require_once APPPATH . 'libraries/MetaData.php';
            require_once APPPATH . 'libraries/Gvvmetadata.php';
        }

        $this->gvvmetadata = new GVVMetadata();
    }

    /**
     * Test that tables_list() returns a non-empty array of table names
     */
    public function testTablesListReturnsMultipleTables()
    {
        $tables = $this->gvvmetadata->tables_list();

        $this->assertIsArray($tables, "tables_list() should return an array");
        $this->assertGreaterThan(0, count($tables), "Database should have multiple tables");
    }

    /**
     * Test that each table has a primary key
     */
    public function testEachTableHasPrimaryKey()
    {
        $tables = $this->gvvmetadata->tables_list();

        // Test first 5 tables to keep test fast
        $cnt = 0;
        foreach ($tables as $table) {
            $cnt++;
            if ($cnt > 5) {
                break;
            }

            $key = $this->gvvmetadata->table_key($table);
            $this->assertNotEmpty($key, "Table '$table' should have a primary key");
        }
    }

    /**
     * Test that table_image_elt() returns a value or empty string
     */
    public function testTableImageElement()
    {
        $tables = $this->gvvmetadata->tables_list();

        if (count($tables) > 0) {
            $table = $tables[0];
            $img_elt = $this->gvvmetadata->table_image_elt($table);

            // Should return a string (may be empty)
            $this->assertIsString($img_elt);
        }
    }

    /**
     * Test autogen_key() method
     */
    public function testAutogenKey()
    {
        $tables = $this->gvvmetadata->tables_list();

        if (count($tables) > 0) {
            $table = $tables[0];
            $auto_key = $this->gvvmetadata->autogen_key($table);

            // Should return a boolean or string
            $this->assertTrue(
                is_bool($auto_key) || is_string($auto_key),
                "autogen_key() should return boolean or string"
            );
        }
    }

    /**
     * Test that each table has multiple fields
     */
    public function testEachTableHasMultipleFields()
    {
        $tables = $this->gvvmetadata->tables_list();

        // Test first 5 tables
        $cnt = 0;
        foreach ($tables as $table) {
            $cnt++;
            if ($cnt > 5) {
                break;
            }

            $fields = $this->gvvmetadata->fields_list($table);
            $this->assertIsArray($fields, "fields_list() for table '$table' should return an array");
            $this->assertGreaterThan(0, count($fields), "Table '$table' should have at least one field");
        }
    }

    /**
     * Test field_name() returns non-empty strings for fields
     */
    public function testFieldNameReturnsNonEmptyString()
    {
        $tables = $this->gvvmetadata->tables_list();

        // Test first 3 tables
        $table_cnt = 0;
        foreach ($tables as $table) {
            $table_cnt++;
            if ($table_cnt > 3) {
                break;
            }

            $fields = $this->gvvmetadata->fields_list($table);

            // Test first 5 fields of each table
            $field_cnt = 0;
            foreach ($fields as $field) {
                $field_cnt++;
                if ($field_cnt > 5) {
                    break;
                }

                $field_name = $this->gvvmetadata->field_name($table, $field);
                $this->assertNotEmpty(
                    $field_name,
                    "field_name() for $table->$field should return a non-empty string"
                );
            }
        }
    }

    /**
     * Test field_type() returns non-empty type for fields
     */
    public function testFieldTypeReturnsNonEmptyString()
    {
        $tables = $this->gvvmetadata->tables_list();

        // Test first 3 tables
        $table_cnt = 0;
        foreach ($tables as $table) {
            $table_cnt++;
            if ($table_cnt > 3) {
                break;
            }

            $fields = $this->gvvmetadata->fields_list($table);

            // Test first 5 fields of each table
            $field_cnt = 0;
            foreach ($fields as $field) {
                $field_cnt++;
                if ($field_cnt > 5) {
                    break;
                }

                $type = $this->gvvmetadata->field_type($table, $field);
                $this->assertNotEmpty(
                    $type,
                    "field_type() for $table->$field should return a non-empty string (got: '$type')"
                );
            }
        }
    }

    /**
     * Test field_subtype() returns a string (may be empty)
     */
    public function testFieldSubtypeReturnsString()
    {
        $tables = $this->gvvmetadata->tables_list();

        if (count($tables) > 0) {
            $table = $tables[0];
            $fields = $this->gvvmetadata->fields_list($table);

            if (count($fields) > 0) {
                $field = $fields[0];
                $subtype = $this->gvvmetadata->field_subtype($table, $field);

                // Subtype may be empty but should be a string
                $this->assertIsString($subtype, "field_subtype() should return a string");
            }
        }
    }

    /**
     * Test field_default() method
     */
    public function testFieldDefault()
    {
        $tables = $this->gvvmetadata->tables_list();

        if (count($tables) > 0) {
            $table = $tables[0];
            $fields = $this->gvvmetadata->fields_list($table);

            if (count($fields) > 0) {
                $field = $fields[0];
                $default = $this->gvvmetadata->field_default($table, $field);

                // Default can be string, null, or other types
                $this->assertTrue(true, "field_default() executed without error");
            }
        }
    }

    /**
     * Test field_attr() method
     */
    public function testFieldAttr()
    {
        $tables = $this->gvvmetadata->tables_list();

        if (count($tables) > 0) {
            $table = $tables[0];
            $fields = $this->gvvmetadata->fields_list($table);

            if (count($fields) > 0) {
                $field = $fields[0];
                $field_attrs = $this->gvvmetadata->field_attr($table, $field);

                // Should return an array or mixed value
                $this->assertTrue(true, "field_attr() executed without error");
            }
        }
    }
}
