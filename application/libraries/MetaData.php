<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
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
 * @package    GVV
 * @subpackage Libraries
 * @category   Metadata
 * @author     Philippe Boissel, Frédéric Peignot
 * @license    GPL-3.0
 * @link       https://github.com/flub78/gvv
 *
 * Metadata management for database-driven form rendering and validation.
 *
 * Extracts field types, sizes, constraints from MySQL schema and augments with application
 * metadata (subtypes, selectors, enumerations) for uniform display, validation, and formatting.
 * Drives table views, form generation, CSV/PDF export, and input validation rules.
 *
 * Types: varchar, int, tinyint, decimal, date, datetime, time
 * Subtypes: email, password, boolean, currency, enumerate, selector, key, image, minute, etc.
 * Defaults: today, current_year, current_user (dynamic PHP-generated values)
 */
abstract class Metadata {
    protected $db = array();
    protected $keys = array();
    protected $alias = array(); // Table des alias des champs
    protected $alias_table = array(); // Table des alias des table
    protected $selects = array();
    protected $selectors = array();

    /**
     * Loads database schema metadata for all tables
     *
     * Queries MySQL SHOW TABLES and SHOW FIELDS to extract type, size, keys.
     * Stores in $this->tables, $this->fields, $this->field, $this->keys arrays.
     */
    function __construct() {
        $this->CI = &get_instance();
        $this->CI->load->database();

        // look for indormation inside the database
        $sql = 'show tables';
        $res = $this->CI->db->query($sql);

        if (isset($this->tables)) {
            return;
        }

        $this->CI->load->helper('crypto');

        foreach ($res->result_array() as $row) {
            foreach ($row as $key => $table) {
                $this->tables[] = $table;

                // look for fields for each table
                $sql = "show full fields from $table";
                $res = $this->CI->db->query($sql);
                foreach ($res->result_array() as $row) {
                    $field = $row['Field'];
                    $this->fields[$table][] = $field;
                    $this->field[$table][$field] = $row;
                    if (isset($row['Key']) && ('PRI' == $row['Key'])) {
                        // echo "key[$table]=$field" . br();
                        $this->keys[$table] = $field;
                    }
                }
            }
        }
    }

    /**
     * Data query API
     */

    /**
     * Returns list of all database tables
     *
     * @return string[] Table names
     */
    function tables_list() {
        return $this->tables;
    }

    /**
     * Returns list of fields for a table
     *
     * @param string $table Table name
     * @param bool $no_autogen_key When true, excludes auto-generated key
     * @return string[] Field names
     *
     * @todo BUG: No occurrences of calls with $no_autogen_key - verify if needed
     */
    function fields_list($table, $no_autogen_key = FALSE) {
        $tmp = $this->fields[$table];
        if ($no_autogen_key) {
            $key = $this->autogen_key();
            if ($key)
                unset($tmp[$key]);
        }
        return $tmp;
    }

    /**
     * Returns primary key column name for table
     *
     * @param string $table Table name
     * @return string Primary key field name (defaults to 'id')
     */
    function table_key($table) {
        return isset($this->keys[$table]) ? $this->keys[$table] : 'id';
    }

    /**
     * Returns key field name if auto-incremented, FALSE otherwise
     *
     * @param string $table Table name
     * @return string|bool Key field name or FALSE
     */
    function autogen_key($table) {
        $key = $this->table_key($table);
        if ($this->field_attr($table, $key, 'Extra') == 'auto_increment') {
            return $key;
        }
        return FALSE;
    }

    /**
     * Returns image element field (currently returns primary key)
     *
     * @param string $table Table name
     * @return string Field name for image representation
     */
    function table_image_elt($table) {
        return $this->table_key($table);
    }

    /**
     * Returns field attribute(s) from metadata
     *
     * @param string $table Table name
     * @param string $field Field name
     * @param string $attr Specific attribute name (empty = all attributes)
     * @return mixed Attribute value, all attributes array, or empty string
     */
    function field_attr($table, $field, $attr = '') {
        $this->resolve($table, $field);

        if ($attr == '') {
            return $this->field[$table][$field];
        } else {
            if (isset($this->field[$table][$field][$attr])) {
                return $this->field[$table][$field][$attr];
            } else {
                return '';
            }
        }
    }

    /**
     * Returns translated field name for display
     *
     * Priority: language file > metadata Name > field name
     *
     * @param string $table Table name
     * @param string $field Field name
     * @return string Display name for field
     */
    function field_name($table, $field) {
        $this->resolve($table, $field);

        $key = "gvv_" . $table . "_short_field_" . $field;
        // echo "$key = ". br();
        if ($this->CI->lang->line($key)) {
            // echo "$key = " . $this->CI->lang->line($key) . br();
            return $this->CI->lang->line($key);
        }

        // look for the exact definition
        if (isset($this->field[$table][$field]['Name'])) {
            $name = $this->field[$table][$field]['Name'];
            // echo "field_name ($table, $field) = $name" . br();
            return $name;
        }

        // @deprecated
        // Ne peux pas être supprimé avant que toutes les définitions d'alias ne soient correctes.
        // look in the ancestor table
        $real_table = $this->real_table($table, $field);
        $real_field = $this->real_field($table, $field);

        if (isset($this->field[$real_table][$real_field]['Name'])) {
            $name = $this->field[$real_table][$real_field]['Name'];
            // echo "field_name ($real_table, $real_field) = $name" . br();
            return $name;
        }

        // echo "field_name ($real_table, $real_field) = $field" . br();
        return $field;
    }

    /**
     * Returns field descriptor/long name from MySQL Comment or language file
     *
     * @param string $table Table name
     * @param string $field Field name
     * @return string Field description or ucwords(field)
     */
    function field_long_name($table, $field) {
        $this->resolve($table, $field);

        $key = "gvv_" . $table . "_field_" . $field;
        // echo "$key" . br();
        if ($this->CI->lang->line($key)) {
            return $this->CI->lang->line($key);
        }

        if (isset($this->field[$table][$field]['Comment'])) {
            $fln = $this->field[$table][$field]['Comment'];
            if (!$fln) $fln = ucwords($field);
            return $fln;
        }
        return $this->field_name($table, $field);
    }

    /**
     * Returns field subtype (semantic type: email, currency, selector, etc.)
     *
     * @param string $table Table name
     * @param string $field Field name
     * @return string Subtype or empty string
     */
    function field_subtype($table, $field) {
        $this->resolve($table, $field);

        // look for the exact definition
        if (isset($this->field[$table][$field]['Subtype']))
            return $this->field[$table][$field]['Subtype'];

        // look in the ancestor table
        $real_table = $this->real_table($table, $field);
        $real_field = $this->real_field($table, $field);

        if (isset($this->field[$real_table][$real_field]['Subtype']))
            return $this->field[$real_table][$real_field]['Subtype'];

        return '';
    }

    /**
     * Returns field default value in form format
     *
     * Resolves dynamic defaults: today, current_year, current_user
     *
     * @param string $table Table name
     * @param string $field Field name
     * @return mixed Default value (form-formatted, not database format)
     */
    function field_default($table, $field) {
        $this->resolve($table, $field);

        if (isset($this->field[$table][$field]['Default'])) {
            $def = $this->field[$table][$field]['Default'];
            $type = $this->field_type($table, $field);
            if (('date' == $type) && ('today' == $def)) {
                $def = date("d/m/Y");
            } elseif (('int' == $type) && ('current_year' == $def)) {
                // @todo replace.
                $def = date("Y");
            } elseif (('varchar' == $type) && ('current_user' == $def)) {
                $def = $this->CI->dx_auth->get_username();
            }
        } else {
            $def = $this->field_attr($table, $field, 'Default');
        }
        // echo "field_default ($table, $field) = $def" . br();
        return $def;
    }

    /**
     * Returns array of default values for all fields in table
     *
     * @param string $table Table name
     * @return array Field => default value hash
     */
    function defaults_list($table) {
        $fields = $this->fields_list($table);
        $defaults = array();
        foreach ($fields as $field) {
            $defaults[$field] = $this->field_default($table, $field);
        }
        return $defaults;
    }

    /**
     * returns the type of a field.
     * If the size is included between parenthesis, remove the size part.
     *
     * @param string $table
     *            name of the table
     * @param string $field
     *            name of the field
     */
    function field_type($table, $field) {
        $this->resolve($table, $field);

        $db_type = $this->field_attr($table, $field, 'Type');

        // look in the ancestor table
        if ('' == $db_type) {
            $real_table = $this->real_table($table, $field);
            $real_field = $this->real_field($table, $field);

            $db_type = $this->field_attr($real_table, $real_field, 'Type');
        }
        // echo "field_type ($table, $field) $real_table, $real_field" . br();
        if (preg_match("/(.*)(\((.*)\))/", $db_type, $matches)) {
            // print_r($matches);
            $type = $matches[1];
            // echo "type($table, $field)=$type" . br();
            return $type;
        } else {
            // echo "db_type($table, $field)=$db_type" . br();
            return $db_type;
        }
    }

    /**
     * returns the size of a field
     *
     * @param string $table
     *            name of the table
     * @param string $field
     *            name of the field
     */
    function field_size($table, $field) {
        $this->resolve($table, $field);

        $db_type = $this->field_attr($table, $field, 'Type');

        if (preg_match("/(.*)(\((.*)\))/", $db_type, $matches)) {
            // print_r($matches);
            $type = $matches[1];
            $size = $matches[3];
            return $size;
        } else {
            if ('date' == $db_type)
                return 10;
            return 0;
        }
    }

    /**
     * returns the field default alignement
     *
     * @param string $table
     *            name of the table
     * @param string $field
     *            name of the field
     * @param
     *            boolean short format for PDF
     */
    function field_align($table, $field, $short = 0) {
        $this->resolve($table, $field);

        $left = ($short) ? 'L' : 'align="left"';
        $center = ($short) ? 'C' : 'align="center"';
        $right = ($short) ? 'R' : 'align="right"';

        $type = $this->field_type($table, $field);
        $subtype = $this->field_subtype($table, $field);

        // echo "field_align ($table, $field) type=$type, subtype=$subtype" . br();
        if ($subtype == 'key')
            return $left;
        if ($subtype == 'int')
            return $right;
        if ($type == 'varchar')
            return $left;
        if ($type == 'decimal')
            return $right;
        if ($type == 'date')
            return $right;
        if ($subtype == 'enumerate')
            return $left;
        if ($subtype == 'selector')
            return $left;
        if ($subtype == 'currency')
            return $right;
        if ($type == 'int')
            return $right;
        if ($type == 'tinyint')
            return $right;
        return $left;
    }

    /**
     * HTML Generation API
     */

    /**
     * Generate an HTML table from $this->db[$table]
     *
     * @param string $table name
     * @param $attrs display
     *            attributes
     *            possible values:
     *            - controller nom du controleur à invoquer pour les actions
     *            - fields liste des champs à afficher
     *            - actions liste de boutton action à ajouter à chaque ligne
     *            - title de la table
     *            - first offset du premier element dans la table (affichage par page)
     *            - count nombre total d'éléments de la sélection
     *            - mode "ro" | "rw" table en lecture seule "ro" ou modifiable "rw"
     */
    function table($table, $attrs = array(), $data = "") {
        if (isset($data) && $data != "") {
            $this->db[$table] = $data;
        } else {
            if (!array_key_exists($table, $this->db))
                throw new Exception('unknown table ' . $table);
        }

        // echo "table $table" . br();
        $datatable = FALSE;

        $CI = &get_instance();
        $CI->load->library('pagination');
        $CI->load->library('ButtonNew');

        // Even if the table is empty the first line must be displayed

        $actions = (isset($attrs['actions'])) ? $attrs['actions'] : array();
        $base_controller = isset($attrs['controller']) ? $attrs['controller'] : '';
        $controller = (isset($attrs['controller'])) ? controller_url($attrs['controller']) : '';
        $count = (isset($attrs['count'])) ? $attrs['count'] : '';
        $page = (isset($attrs['page'])) ? $attrs['page'] : 'page';
        $uri_segment = (isset($attrs['uri_segment'])) ? $attrs['uri_segment'] : 3;
        $class = (isset($attrs['class'])) ? "class=\"" . $attrs['class'] . "\"" : "class=\"sql_table datatab\"";
        $mode = "ro";
        if (isset($attrs['mode']) && ($attrs['mode'] == "rw")) {
            $mode = "rw";
        }
        $per_page = $this->CI->session->userdata('per_page');
        $numbered = (isset($attrs['numbered'])) ? $attrs['numbered'] : 0;
        if (isset($attrs['fields'])) {
            $fields = $attrs['fields'];
        } else {
            if (isset($this->db['default_fields'][$table])) {
                $fields = $this->db['default_fields'][$table];
            } else {
                $fields = (isset($this->db[$table][0])) ? array_keys($this->db[$table][0]) : array();
            }
        }
        $param = (isset($attrs['param'])) ? $attrs['param'] : "";
        $autoplanchiste = (isset($attrs['autoplanchiste'])) ? $attrs['autoplanchiste'] : "";
        $autoplanchiste_id = (isset($attrs['autoplanchiste_id'])) ? $attrs['autoplanchiste_id'] : "";

        $res = "";

        // pagination, obsolete dans la plupart des cas, on utilise datatable
        if (isset($attrs['count'])) {
            $config['base_url'] = "$controller/$page";
            $config['total_rows'] = $attrs['count'];
            $config['per_page'] = $per_page;
            $config['uri_segment'] = $uri_segment;
            $config['first_link'] = 'Premier';
            $config['last_link'] = 'Dernier';

            $CI->pagination->initialize($config);
            $pagination = $CI->pagination->create_links();
        } else {
            $pagination = '';
        }

        // page number
        if ($count != '') {
            $first = (isset($attrs['first'])) ? $attrs['first'] + 1 : 1;
            $last = $first + count($this->db[$table]) - 1;

            // Selector of number of element per page
            $pagination = "$first-$last/$count" . nbs(4) . $pagination;
        }

        if (!$datatable && $count) {
            $res .= "<table><tr><td>" . "Afficher par page: " . form_dropdown('perpage', array(
                10 => 10,
                50 => 50,
                100 => 100,
                500 => 500,
                1000 => 1000
            ), $per_page, "id='per_page' onchange=per_page();") . nbs(4) . $pagination . "</td></tr></table>";
        }

        $res .= "<table $class>\n";

        // Table title
        if (isset($attrs['title'])) {
            $title = $attrs['title'];
            $res .= "\t<caption>$title</caption>\n";
        }

        // Title row
        $res .= "\t<thead>";
        $res .= "<tr>";

        if ($numbered) {
            $align = 'align="right"';
            $res .= "<th $align>N°</th>";
        }

        // Optional global Create button (Bootstrap) in header when provided
        $header_create_html = '';
        if (isset($attrs['create']) && is_array($attrs['create']) && isset($attrs['create']['url'])) {
            $create_url = $attrs['create']['url'];
            $label_key = isset($attrs['create']['label_key']) ? $attrs['create']['label_key'] : 'gvv_button_create';
            $create_label = $this->CI->lang->line($label_key) ?: $this->CI->lang->line('gvv_button_create');
            $header_create_html = '<a href="' . site_url(trim($create_url, '/')) . '" class="btn btn-sm btn-success">'
                                . '<i class="fas fa-plus" aria-hidden="true"></i> '
                                . htmlspecialchars($create_label, ENT_QUOTES, 'UTF-8')
                                . '</a>';
        }

        // Actions title
        $action_cnt = count($actions);
        foreach ($actions as $action) {
            $action_cnt--;
            $name = '';
            if ($action_cnt == 0) {
                // Put Create button in the last actions header cell if available
                $name = $header_create_html;
            }
            if ($mode == "rw")
                $res .= "<th class=\"ui-state-default\" >$name</th>";
        }

        // column title
        foreach ($fields as $field) {
            $align = $this->field_align($table, $field);
            $colName = $this->field_name($table, $field);
            $res .= "<th $align  class=\"ui-state-default\" >$colName</th>";
        }


        $res .= "</tr>";
        $res .= "</thead>\n";

        // Value lines
        $cnt = 1;
        foreach ($this->db[$table] as $row) {

            // Columns
            if ($cnt % 2) {
                $res .= "\t<tr class=\"odd\"  >";
            } else {
                $res .= "\t<tr class=\"even\"  >";
            }

            if ($numbered) {
                $align = 'align="right"';
                $res .= "<td $align>$cnt</td>";
            }
            $cnt++;

            $elt_id = isset($row[$this->table_key($table)]) ? $row[$this->table_key($table)] : 'XXX';


            // and the actions
            if ($mode == "rw") {
                foreach ($actions as $action) {

                    $url = "$base_controller/$action";  // Use base_controller (relative path) not $controller (full URL)
                    $elt_image = isset($row['image']) ? $row['image'] : $row[$this->table_key($table)];
                    $confirm = ($action == 'delete');
                    
                    // Check if line is frozen for delete action
                    $is_frozen = isset($row['gel']) ? $row['gel'] : 0;

                    if ($autoplanchiste) {
                        if ($row[$autoplanchiste_id] == $autoplanchiste) {
                            $res .= "\t\t<td>" . $this->action($action, $url, $elt_id, $elt_image, $confirm, $is_frozen) . "</td>\n";
                        } else {
                            $res .= "\t\t<td>" . "</td>\n";
                        }
                    } else {
                        $res .= "\t\t<td>" . $this->action($action, $url, $elt_id, $elt_image, $confirm, $is_frozen) . "</td>\n";
                    }
                }
            }

            foreach ($fields as $field) {

                $align = $this->field_align($table, $field);

                $res .= "\t\t<td $align>";
                $value = isset($row[$field]) ? $row[$field] : '';
                $res .= $this->array_field($table, $field, $value, $row, $mode, $elt_id);
                $res .= "</td>\n";
            }

            $res .= "\t</tr>\n";
        }

        if (isset($attrs['footer'])) {
            $footers = $attrs['footer'];
            foreach ($footers as $footer) {
                // column title
                $col = 0;
                $res .= "\t<tr>\n";

                if ($mode == "rw") {
                    foreach ($actions as $action) {
                        $res .= "<td></td>";
                    }
                }

                foreach ($fields as $field) {
                    $align = $this->field_align($table, $field);
                    $value = isset($footer[$col]) ? $footer[$col] : '';
                    $res .= "<td $align>$value </td>";
                    $col++;
                }



                $res .= "\t</tr>\n";
            }
        }
        $res .= "</table>\n";

        if ($count != '' && (!$datatable)) {
            $res .= "<table><tr><td>" . $pagination . "</td></tr></table>";
        } else {
            $res .= br(2);
        }
        // gvv_debug("MetaData.table " . $res);
        return $res;
    }

    /**
     * Normalise an HTML table according to the data type définition
     *
     * This method is used by Ajax routines to format pages.
     *
     * @param string $table name
     * @param $attrs display
     *            attributes
     *            possible values:
     *            - fields liste des champs à afficher
     *            - mode "ro" | "rw" table en lecture seule "ro" ou modifiable "rw"
     *
     * @todo : remplacer par une version qui ne travaille qu'avec les paramètres d'entrée.
     *
     */
    function normalise($table, $array, $attrs = array()) {
        $result = $array;
        if (!array_key_exists($table, $this->db))
            throw new Exception('unknown table ' . $table);

        $mode = "ro";
        if (isset($attrs['mode']) && ($attrs['mode'] == "rw")) {
            $mode = "rw";
        }
        if (isset($attrs['fields'])) {
            $fields = $attrs['fields'];
        } else {
            if (isset($this->db['default_fields'][$table])) {
                $fields = $this->db['default_fields'][$table];
            } else {
                $fields = (isset($this->db[$table][0])) ? array_keys($this->db[$table][0]) : array();
            }
        }

        // Value lines
        $cnt = 0;
        foreach ($this->db[$table] as $row) {

            $elt_id = isset($row[$this->table_key($table)]) ? $row[$this->table_key($table)] : '';

            foreach ($fields as $field) {
                $value = isset($row[$field]) ? $row[$field] : '';
                $result[$cnt][$field] = $this->array_field($table, $field, $value, $row, $mode, $elt_id);
            }
            $cnt++;
        }

        return $result;
    }

    /**
     * Generate an HTML table with only a header
     *
     * @param string $table name
     * @param mixed[] $attrs display
     *            attributes
     *            possible values:
     *            - controller nom du controleur à invoquer pour les actions
     *            - fields liste des champs à afficher
     *            - actions liste de boutton action à ajouter à chaque ligne
     *            - title de la table
     *            - first offset du premier element dans la table (affichage par page)
     *            - count nombre total d'éléments de la sélection
     *            - mode "ro" | "rw" table en lecture seule "ro" ou modifiable "rw"
     */
    function empty_table($table, $attrs = array()) {
        if (!array_key_exists($table, $this->db))
            throw new Exception('unknown table ' . $table);

        $CI = &get_instance();
        $CI->load->library('ButtonNew');

        // Even if the table is empty the first line must be displayed

        $actions = (isset($attrs['actions'])) ? $attrs['actions'] : array();
        $base_controller = isset($attrs['controller']) ? $attrs['controller'] : '';
        $controller = (isset($attrs['controller'])) ? controller_url($attrs['controller']) : '';
        $class = (isset($attrs['class'])) ? "class=\"" . $attrs['class'] . "\"" : "class=\"sql_table datatab\"";
        $widths = isset($attrs['width']) ? $attrs['width'] : array();

        $mode = "ro";
        if (isset($attrs['mode']) && ($attrs['mode'] == "rw")) {
            $mode = "rw";
        }
        if (isset($attrs['fields'])) {
            $fields = $attrs['fields'];
        } else {
            if (isset($this->db['default_fields'][$table])) {
                $fields = $this->db['default_fields'][$table];
            } else {
                $fields = (isset($this->db[$table][0])) ? array_keys($this->db[$table][0]) : array();
            }
        }
        $param = (isset($attrs['param'])) ? $attrs['param'] : "";

        $res = "";

        $res .= "<table $class>\n";

        // Table title
        if (isset($attrs['title'])) {
            $title = $attrs['title'];
            $res .= "\t<caption>$title</caption>\n";
        }

        // Title row
        $res .= "\t<thead>";
        $res .= "<tr>";

        // Optional global Create button (Bootstrap) in header when provided
        $header_create_html = '';
        if (isset($attrs['create']) && is_array($attrs['create']) && isset($attrs['create']['url'])) {
            $create_url = $attrs['create']['url'];
            $label_key = isset($attrs['create']['label_key']) ? $attrs['create']['label_key'] : 'gvv_button_create';
            $create_label = $this->CI->lang->line($label_key) ?: $this->CI->lang->line('gvv_button_create');
            $header_create_html = '<a href="' . site_url(trim($create_url, '/')) . '" class="btn btn-sm btn-success">'
                                . '<i class="fas fa-plus" aria-hidden="true"></i> '
                                . htmlspecialchars($create_label, ENT_QUOTES, 'UTF-8')
                                . '</a>';
        }

        // Actions title
        $action_cnt = count($actions);
        foreach ($actions as $action) {
            $action_cnt--;
            $name = '';
            if ($action_cnt == 0) {
                $name = $header_create_html;
            }
            if ($mode == "rw")
                $res .= "<th>$name</th>";
        }

        // column title
        $cnt = 0;
        foreach ($fields as $field) {
            $align = $this->field_align($table, $field);
            $colName = $this->field_name($table, $field);
            $width = isset($widths[$cnt]) ? "width=\"" . $widths[$cnt] . '"' : "";
            $res .= "<th $align $width>$colName</th>";
            $cnt++;
        }


        $res .= "</tr>";
        $res .= "</thead>\n";

        $res .= "</table>\n";

        return $res;
    }

    /**
     * Export the table in comma separated value
     *
     * @param string $table name
     * @param $attrs display
     *            attributes
     *            possible values:
     *            - fields
     *            - title
     *            - offset
     *
     * @deprecated
     *
     */
    function csv($table, $attrs = array()) {
        if (!array_key_exists($table, $this->db))
            throw new Exception('unknown table ' . $table);

        $class = (isset($attrs['class'])) ? "class=\"" . $attrs['class'] . "\"" : "class=\"sql_table\"";
        $numbered = (isset($attrs['numbered'])) ? $attrs['numbered'] : 0;
        if (isset($attrs['fields'])) {
            $fields = $attrs['fields'];
        } else {
            if (isset($this->db['default_fields'][$table])) {
                $fields = $this->db['default_fields'][$table];
            } else {
                $fields = array_keys(($this->db[$table][0]));
            }
        }
        $header = (isset($attrs['header'])) ? $attrs['header'] . "\n" : '';
        $footer = (isset($attrs['header'])) ? $attrs['header'] . "\n" : '';

        $res = $header;
        // Table title
        if (isset($attrs['title'])) {
            $title = $attrs['title'];
            $res .= "$title\n";
        }

        // Title row
        if ($numbered) {
            $res .= "N°; ";
        }

        // column title
        foreach ($fields as $field) {
            $colName = $this->field_name($table, $field);
            $res .= "$colName; ";
        }
        $res .= "\n";

        // Value lines
        $cnt = 1;
        foreach ($this->db[$table] as $row) {
            // Columns
            if ($numbered) {
                $res .= "$cnt; ";
                $cnt++;
            }

            foreach ($fields as $field) {

                $value = isset($row[$field]) ? $row[$field] : '';
                $res .= $this->array_field($table, $field, $value, $row, "csv");
                $res .= "; ";
            }
            $res .= "\n";
        }
        $res .= $footer;
        // echo $res . br() ; return;

        date_default_timezone_set('Europe/Paris');
        $dt = date("Y_m_d");
        if (isset($attrs['title'])) {
            $title = strtolower(str_replace([' ', '-'], ['_', ''], $attrs['title']));
            $title = str_replace('__', '_', $title);
            $filename = "gvv_" . $title . "_$dt.csv";
        } else {
            $filename = "gvv_" . $table . "_$dt.csv";
        }

        # $res = iconv('UTF-8', 'windows-1252', $res);
        $CI = &get_instance();
        // Load the download helper and send the file to your desktop
        $CI->load->helper('download');
        force_download($filename, $res);
    }

    /**
     * Export the table in comma separated value
     *
     * @param string $table name
     * @param $data the
     *            table to display
     * @param $attrs display
     *            attributes
     *            possible values:
     *            - title
     *            - offset
     */
    function csv_table($table, $data, $attrs = array()) {
        $class = (isset($attrs['class'])) ? "class=\"" . $attrs['class'] . "\"" : "class=\"sql_table\"";
        $numbered = (isset($attrs['numbered'])) ? $attrs['numbered'] : 0;
        if (isset($attrs['fields'])) {
            $fields = $attrs['fields'];
        } else {
            if (isset($this->db['default_fields'][$table])) {
                $fields = $this->db['default_fields'][$table];
            } else {
                $fields = array_keys(($data[0]));
            }
        }
        $header = (isset($attrs['header'])) ? $attrs['header'] . "\n" : '';
        $footer = (isset($attrs['header'])) ? $attrs['header'] . "\n" : '';

        $res = $header;
        // Table title
        if (isset($attrs['title'])) {
            $title = $attrs['title'];
            $res .= "$title\n";
        }

        // Title row
        if ($numbered) {
            $res .= "N°; ";
        }

        // column title
        foreach ($fields as $field) {
            $colName = $this->field_name($table, $field);
            $res .= "$colName; ";
        }
        $res .= "\n";

        // Value lines
        $cnt = 1;
        foreach ($data as $row) {
            // Columns
            if ($numbered) {
                $res .= "$cnt; ";
                $cnt++;
            }

            foreach ($fields as $field) {

                $value = isset($row[$field]) ? $row[$field] : '';
                $res .= $this->array_field($table, $field, $value, $row, "csv");
                $res .= "; ";
            }
            $res .= "\n";
        }
        $res .= $footer;
        // echo $res . br() ; return;

        date_default_timezone_set('Europe/Paris');
        $dt = date("Y_m_d");
        if (isset($attrs['title'])) {
            $title = strtolower(str_replace([' ', '-', ',', '='], ['_', '', '', '_'], $title));

            $filename = "gvv_" . $title . ".csv";
        } else {
            $filename = "gvv_" . $table . "_$dt.csv";
        }

        $CI = &get_instance();
        // Load the download helper and send the file to your desktop
        $CI->load->helper('download');
        force_download($filename, $res);
    }

    /**
     * Add the table in a pdf documment
     *
     * @param string $table name
     * @param $pdf document
     * @param $attrs display
     *            attributes
     *            possible values:
     *            - fields
     *            - title
     *            - offset
     *
     * @deprecated
     *
     */
    function pdf($table, $pdf, $attrs = array()) {
        if (!array_key_exists($table, $this->db))
            throw new Exception('unknown table ' . $table);

        // $class = (isset($attrs['class'])) ? "class=\"" . $attrs['class'] . "\"" : "class=\"sql_table\"";
        $numbered = (isset($attrs['numbered'])) ? $attrs['numbered'] : 0;
        if (isset($attrs['fields'])) {
            $fields = $attrs['fields'];
        } else {
            if (isset($this->db['default_fields'][$table])) {
                $fields = $this->db['default_fields'][$table];
            } else {
                $fields = array_keys(($this->db[$table][0]));
            }
        }

        // Table title
        if (isset($attrs['title'])) {
            $title = $attrs['title'];
            $pdf->title($title);
        }

        $tab = array();
        $line = array();
        $align = array();
        // Title row
        if ($numbered) {
            $line[] = "N°";
            $align[] = 'R';
        }

        // column title
        foreach ($fields as $field) {
            $align[] = $this->field_align($table, $field, 1);
            $colName = $this->field_name($table, $field);
            $line[] = $colName;
        }
        $tab[] = $line;

        // Value lines
        $cnt = 1;
        foreach ($this->db[$table] as $row) {
            $line = array();

            // Columns
            if ($numbered) {
                $line[] = $cnt;
                $cnt++;
            }

            foreach ($fields as $field) {
                $value = isset($row[$field]) ? $row[$field] : '';
                $mode = isset($attrs['mode']) ? $attrs['mode'] : 'pdf';
                $line[] = $this->array_field($table, $field, $value, $row, $mode);
            }
            $tab[] = $line;
        }
        $pdf->table($attrs['width'], 8, $align, $tab);
    }

    /**
     * Add the table in a pdf documment
     *
     * @param string $table name
     * @param $pdf document
     * @param $attrs display
     *            attributes
     *            possible values:
     *            - fields
     *            - title
     *            - offset
     *
     */
    function pdf_table($table, $data, $pdf, $attrs = array()) {

        // $class = (isset($attrs['class'])) ? "class=\"" . $attrs['class'] . "\"" : "class=\"sql_table\"";
        $numbered = (isset($attrs['numbered'])) ? $attrs['numbered'] : 0;
        if (isset($attrs['fields'])) {
            $fields = $attrs['fields'];
        } else {
            if (isset($this->db['default_fields'][$table])) {
                $fields = $this->db['default_fields'][$table];
            } else {
                $fields = array_keys(($data[0]));
            }
        }

        // Table title
        if (isset($attrs['title'])) {
            $title = $attrs['title'];
            $pdf->title($title);
        }

        $tab = array();
        $line = array();
        $align = array();
        // Title row
        if ($numbered) {
            $line[] = "N°";
            $align[] = 'R';
        }

        // column title
        foreach ($fields as $field) {
            $align[] = $this->field_align($table, $field, 1);
            $colName = $this->field_name($table, $field);
            $line[] = $colName;
        }
        $tab[] = $line;

        // Value lines
        $cnt = 1;
        foreach ($data as $row) {
            $line = array();

            // Columns
            if ($numbered) {
                $line[] = $cnt;
                $cnt++;
            }

            foreach ($fields as $field) {
                $value = isset($row[$field]) ? $row[$field] : '';
                $mode = isset($attrs['mode']) ? $attrs['mode'] : 'pdf';
                $line[] = $this->array_field($table, $field, $value, $row, $mode);
            }
            $tab[] = $line;
        }
        $pdf->table($attrs['width'], 8, $align, $tab);
    }

    /**
     * Affichage d'un champ de table dans une vue table
     *
     * @param string $table name
     * @param $field column
     *            name
     * @param $value default
     *            value
     * @param $mode ro=read
     *            only, rw=input mode
     * @param $id of
     *            the element
     */
    protected function array_field($table, $field, $value, &$row, $mode = "ro", $id = '') {
        $this->resolve($table, $field);

        $type = $this->field_type($table, $field);
        $subtype = $this->field_subtype($table, $field);
        // gvv_debug("array_field ($table, $field), id=$id, type=$type, subtype=$subtype, value=$value");

        // Special handling for description field in vue_journal to add attachment paperclip icon
        if ($table == 'vue_journal' && $field == 'description' && $mode != 'csv' && $mode != 'pdf') {
            // Get attachment count for this ecriture
            $ecriture_id = isset($row['id']) ? $row['id'] : '';
            if ($ecriture_id) {
                $CI =& get_instance();
                      $CI->db->where('referenced_table', 'ecritures');
          $CI->db->where('referenced_id', $ecriture_id);
                $attachment_count = $CI->db->count_all_results('attachments');

                // Build paperclip icon with appropriate color
                // Gray (muted) for no attachments, bright green for attachments present
                $icon_class = $attachment_count > 0 ? 'text-success fw-bold' : 'text-light-muted';
                $title = $attachment_count > 0 ? $attachment_count . ' justificatif(s)' : 'Aucun justificatif';
                
                $date_op = isset($row['date_op']) ? $row['date_op'] : '';
                $description = isset($row['description']) ? $row['description'] : '';
                $debit = isset($row['debit']) ? $row['debit'] : '';
                $credit = isset($row['credit']) ? $row['credit'] : '';

                $icon_html = '<i class="fas fa-paperclip ' . $icon_class . ' attachment-icon" ' .
                    'data-ecriture-id="' . $ecriture_id . '" ' .
                    'data-attachment-count="' . $attachment_count . '" ' .
                    'data-date="' . $date_op . '" ' .
                    'data-description="' . htmlspecialchars($description) . '" ' .
                    'data-debit="' . $debit . '" ' .
                    'data-credit="' . $credit . '" ' .
                    'style="cursor: pointer; margin-right: 5px; font-size: 1.1em;" ' .
                    'title="' . $title . '"></i>';

                return $icon_html . htmlspecialchars($value);
            }
        }

        if ($subtype == 'boolean') {
            if ($mode == 'csv' || $mode == 'pdf')
                return $value;
            return ($value) ? img(theme() . "/images/tick.png") : '';
        } elseif ('currency' == $subtype) {

            if ($value === '')
                return '';
            if ($mode == 'csv') {
                return number_format((float) $value, 2, ",", "");
            }
            $target = ($mode == 'pdf') ? 'pdf' : 'html';
            return euro($value, ',', $target);
            // return sprintf("%6.2f", $value);

        } elseif ('minute' == $subtype) {
            return minute_to_time($value);
        } elseif ('time' == $subtype) {
            return decimal_to_time($value);
        } elseif ('enumerate' == $subtype) {
            if (isset($this->field[$table][$field]['Enumerate'])) {
                $values = $this->field[$table][$field]['Enumerate'];
                return (isset($values[$value])) ? $values[$value] : $value;
            } else {
                log_message('error', "MetaData: Missing 'Enumerate' definition for field '$field' in table '$table'");
                return $value;
            }
        } elseif ('selector' == $subtype) {
            return $value;
        } elseif ('checkbox' == $subtype) {

            if ($mode == 'csv') {
                return $value;
            } elseif ($mode == 'rw') {
                $check = form_checkbox(array(
                    'name' => "check_$id",
                    'value' => 1,
                    'checked' => ($value == 1),
                    'onchange' => "line_checked($id, $value, 0, 0)"
                ));
                return $check;
            } else {
                return ($value) ? img(theme() . "/images/tick.png") : '';
            }
        } elseif ('key' == $subtype) {
            if ($value == '')
                return '';

            $action = $this->field[$table][$field]['Action'];
            if (isset($this->field[$table][$field]['Image'])) {
                $image = $this->field[$table][$field]['Image'];
                $label = $row[$image];
            } else {
                $label = $value;
            }
            if ($mode == 'csv' || $mode == 'pdf')
                return $label;
            $url = controller_url($action . "/$value");
            return anchor($url, $label);
        } elseif ('email' == $subtype) {
            return $value;
            // return auto_link($value);

        } elseif ($subtype == 'image' || $subtype == 'upload_image') {
            if (!$value) return "";
            // Use base_url() for direct file access (not through CodeIgniter router)
            // Ensure there's a slash between base_url and path
            $base = rtrim(base_url(), '/') . '/';
            $url = $base . ltrim($value, './');
            if (file_exists($value)) {
                return attachment($id, $value, $url);
            }

            // For configuration files, if the path doesn't start with "./" it might be a relative path
            if ($table == 'vue_configuration' && $field == 'file' && !str_starts_with($value, './')) {
                $config_value = "./uploads/configuration/" . $value;
                if (file_exists($config_value)) {
                    $url = $base . ltrim($config_value, './');
                    return attachment($id, $config_value, $url);
                }
            }

            if (file_exists($value)) {
                return attachment($id, $value, $url);
            }

            // Try legacy location for backward compatibility
            if ($table == 'membres' && $field == 'photo') {
                $legacy_value = "uploads/" . basename($value);
                if (file_exists($legacy_value)) {
                    $url = $base . $legacy_value;
                    return attachment($id, $legacy_value, $url);
                }
            }

            // $img = (file_exists($filename)) ? img($filename) : '';
            // return $img;
            return "Error array_field($table, $field): type=$type, subtype=$subtype, value=" . $value;
        } elseif ($subtype == 'color') {
            if ($value) {
                return '<div style="width: 20px; height: 20px; background-color: ' . $value . '; border-radius: 50%; border: 1px solid black;"></div>';
            }
            return '';
        }

        if ($type == 'date') {
            return date_db2ht($value);
        } elseif ($type == 'datetime') {
            $list = explode(" ", $value);
            return date_db2ht($list[0]) . " " . $list[1];
        } elseif ($type == 'decimal') {
            if ($mode == 'csv')
                return number_format((float) $value, 2, ",", "");
        }
        return $value;
    }

    /**
     * Compute the HTML image of an action
     *
     * @param $action
     * @param $url
     * @param
     *            $row_id
     * @param
     *            confirm
     */
    function action($action = '', $url = '', $elt_id = '', $elt_image = '', $confirm = 0, $is_frozen = 0) {
        $label = $this->action_name($action);
        $attrs = '';

        if ($confirm) {
            // For delete action on frozen lines, show information popup instead of confirmation
            if ($action == 'delete' && $is_frozen) {
                $txt = $this->CI->lang->line("gvv_compta_frozen_line_cannot_delete");
                $attrs = "onclick=\"alert('" . addslashes($txt) . "'); return false;\" ";
            } else {
                $txt = $this->CI->lang->line("gvv_button_delete_confirm") . " $elt_image?";
                $attrs = "onclick=\"return confirm('" . addslashes($txt) . "')\" ";
            }
        }

        // Build Bootstrap-styled buttons with Font Awesome icons for common actions
        if ($action == 'edit') {
            $btn = '<a href="' . site_url(trim($url, '/') . '/' . $elt_id) . '" class="btn btn-sm btn-primary" title="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '">'
                 . '<i class="fas fa-edit" aria-hidden="true"></i>'
                 . '</a>';            // Append confirm attribute if any (usually not for edit)
            if ($attrs) {
                // inject attribute into anchor
                $btn = str_replace('" class=', '" ' . $attrs . ' class=', $btn);
            }
            return $btn;
        } elseif ($action == 'delete') {
            $delete_label = $this->CI->lang->line('gvv_button_delete');
            $btn = '<a href="' . site_url(trim($url, '/') . '/' . $elt_id) . '" class="btn btn-sm btn-danger" title="' . htmlspecialchars($delete_label, ENT_QUOTES, 'UTF-8') . '" ' . $attrs . '>'
                 . '<i class="fas fa-trash" aria-hidden="true"></i>'
                 . '</a>';
            return $btn;
        } elseif ($action == 'pdf') {
            $btn = '<a href="' . site_url(trim($url, '/') . '/' . $elt_id) . '" class="btn btn-sm btn-secondary" title="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '">'
                 . '<i class="fas fa-file-pdf" aria-hidden="true"></i>'
                 . '</a>';
            if ($attrs) {
                $btn = str_replace('" class=', '" ' . $attrs . ' class=', $btn);
            }
            return $btn;
        } elseif ($action == 'action') {
            // Keep obfuscation for custom action
            $obfuscated = transformInteger($elt_id);
            $btn = '<a href="' . site_url(trim($url, '/') . '/' . $obfuscated) . '" class="btn btn-sm btn-info" title="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '" ' . $attrs . '>'
                 . '<i class="fas fa-info-circle" aria-hidden="true"></i>'
                 . '</a>';
            return $btn;
        } elseif ($action == 'print_vd') {
            $obfuscated = transformInteger($elt_id);
            $btn = '<a href="' . site_url(trim($url, '/') . '/' . $obfuscated) . '" class="btn btn-sm btn-secondary" title="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '">'
                 . '<i class="fas fa-file-pdf" aria-hidden="true"></i>'
                 . '</a>';
            if ($attrs) {
                $btn = str_replace('" class=', '" ' . $attrs . ' class=', $btn);
            }
            return $btn;
        } elseif ($action == 'email_vd') {
            $obfuscated = transformInteger($elt_id);
            $btn = '<a href="' . site_url(trim($url, '/') . '/' . $obfuscated) . '" class="btn btn-sm btn-secondary" title="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '">'
                 . '<i class="fas fa-envelope" aria-hidden="true"></i>'
                 . '</a>';
            if ($attrs) {
                $btn = str_replace('" class=', '" ' . $attrs . ' class=', $btn);
            }
            return $btn;
        } elseif ($action == 'csv') {
            $btn = '<a href="' . site_url(trim($url, '/') . '/' . $elt_id) . '" class="btn btn-sm btn-secondary" title="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '">'
                 . '<i class="fas fa-file-excel" aria-hidden="true"></i>'
                 . '</a>';
            if ($attrs) {
                $btn = str_replace('" class=', '" ' . $attrs . ' class=', $btn);
            }
            return $btn;
        } elseif ($action == 'clone_elt') {
            $btn = '<a href="' . site_url(trim($url, '/') . '/' . $elt_id) . '" class="btn btn-sm btn-secondary" title="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '">'
                 . '<i class="fas fa-copy" aria-hidden="true"></i>'
                 . '</a>';
            if ($attrs) {
                $btn = str_replace('" class=', '" ' . $attrs . ' class=', $btn);
            }
            return $btn;
        }

        // Default fallback: simple anchor to preserve legacy behavior
        return anchor($url . "/$elt_id", $label, $attrs);
    }

    /**
     * Name of an action for title row
     *
     * @param $action
     */
    function action_name($action = '') {
        if ($action == 'edit')
            return $this->CI->lang->line("gvv_button_update");

        if ($action == 'delete')
            return $this->CI->lang->line("gvv_button_delete");

        if ($action == 'clone_elt')
            return $this->CI->lang->line("gvv_button_clone");

        return ucwords($action);
    }

    /**
     * Other functions
     */

    /**
     * Store the result of a select for a view.
     *
     * @param $view name
     * @param $selection result
     *            of a select in an array format
     * @param $query to
     *            analyze to find the data types (default = the latest query on the database)
     */
    function store_table($view, $selection, $query = '') {
        $log = 0;
        $detail = 1;

        // store the data
        $this->db[$view] = $selection;

        // dump de la selection
        if ($log && $detail)
            foreach ($selection as $row) {
                foreach ($row as $key => $value) {
                    echo "$key => $value" . br();
                }
                echo br();
            }

        // analyse la requête
        if ($query == '')
            $query = $this->CI->db->last_query();
        gvv_debug("sql: " . $query);

        if (isset($this->selects[$view]))
            return;
        $this->selects[$view] = $query;

        if ($log)
            echo "\$this->selects['$view'] = \"$query\";" . br();

        // Analyse de la partie from de la requête
        $aliases = array();
        $all_tables = array();
        if (preg_match("/FROM \((.*)\)/", $query, $matches)) {
            $from = $matches[1];
            if ($log)
                echo "// FROM=$from" . br();
            foreach (preg_split("/,/", $from) as $col) {

                if (preg_match("/\`(.*)\`\sas\s(.*)/", $col, $matches)) {
                    // Il y a un alias
                    $real_table = $matches[1];
                    $table_name = $matches[2];

                    $aliases[$table_name] = $real_table;
                    if ($log)
                        echo "//" . nbs(4) . "\$aliases['$table_name'] = \"$real_table\";" . br();
                } elseif (preg_match("/\`(.*)\`/", $col, $matches)) {
                    // Un seul champ entre quotes
                    $real_table = $matches[1];
                } else {
                    // Un seul champ sans quotes
                    $real_table = $col;
                }
                $all_tables[$real_table] = 1;
            }
        }

        if ($log)
            echo "// tables: " . join(", ", array_keys($all_tables)) . br(2);
        if (count($all_tables) == 1) {
            $this->real_table[$view][''] = $real_table;
            // return; // regular table
        }

        // analyse de la partie select de la requête
        // Cela consiste à trouver les real_table et real_field
        if (preg_match("/SELECT\s*(.*)/", $query, $matches)) {
            $select = $matches[1];
            if ($log)
                echo "SELECT=$select" . br();
            foreach (preg_split("/,\s+/", $select) as $col) {
                // if ($log) echo "col=$col" .br();
                $real_table = "unknown";
                $real_field = "unknown";

                if (preg_match("/(.*)\sas\s(.*)/", $col, $matches)) {
                    // forme `tarifs1`.`prix` as prix_forfait
                    $target_table = $matches[1];
                    $field_name = $matches[2];

                    if (preg_match("/\`(.*)\`\.\`(.*)\`/", $target_table, $matches)) {
                        $alias = $matches[1];
                        // echo "alias=$alias" . br();
                        $real_table = isset($aliases[$alias]) ? $aliases[$alias] : $alias;
                        $real_field = $matches[2];
                    }
                } else {

                    // Il n'y a qu'un nom de champ, il faut trouver la table ...
                    $field_name = $col;
                    if (preg_match("/\`(.*)\`\.\`(.*)\`/", $col, $matches)) {
                        // format `table\`.`champ`
                        $real_table = $matches[1];
                        $field_name = $matches[2];
                    } elseif (preg_match("/\`(.*)\`/", $col, $matches)) {
                        // format = `champ`
                        $field_name = $matches[1];
                        foreach ($all_tables as $tried_table => $value) {
                            if (isset($this->field[$tried_table][$field_name])) {
                                $real_table = $tried_table;
                                break;
                            }
                        }
                    } else {
                        // format = champ
                        $field_name = $col;
                        foreach ($all_tables as $tried_table => $value) {
                            if (isset($this->field[$tried_table][$field_name])) {
                                $real_table = $tried_table;
                                break;
                            }
                        }
                    }
                    $real_field = $field_name;
                }

                $this->real_table[$view][$field_name] = $real_table;
                $this->real_field[$view][$field_name] = $real_field;
                if ($log)
                    echo nbs(4) . "\$this->real_table[`$view`][`$field_name`] = `$real_table`;" . br();
                if ($log)
                    echo nbs(4) . "\$this->real_field[`$view`][`$field_name`] = `$real_field`;" . br();
            }
        }
    }

    /**
     * Debug function
     */
    function dump() {
        echo "// Metadata dump" . br();
        $tables_list = $this->tables_list();
        echo "// Tables = " . join(", ", $tables_list) . br();
        foreach ($tables_list as $table) {
            echo "//" . br() . "// table $table" . br();
            $fields_list = $this->fields_list($table);
            foreach ($fields_list as $field) {
                echo "// " . nbs(8) . $field . " -> ";
                $row = $this->field_attr($table, $field);

                echo nbs(8) . "type=" . $this->field_type($table, $field);
                echo nbs(8) . "size=" . $this->field_size($table, $field);
                echo nbs(8) . "name=" . $this->field_name($table, $field);
                echo nbs(8) . "default=" . $this->field_default($table, $field);
                echo nbs(8) . "subtype=" . $this->field_subtype($table, $field);
                // echo nbs(8) . "collatione=" . $this->field_attr($table, $field, 'Collation');
                echo nbs(8) . "key=" . $this->field_attr($table, $field, 'Key');
                echo nbs(8) . "extra=" . $this->field_attr($table, $field, 'Extra');
                // echo nbs(8) . "privileges=" . $this->field_attr($table, $field, 'Privileges');
                echo br();

                foreach ($row as $key => $value) {
                    echo nbs(8) . "\$this->field['$table']['$field']['$key'] = '$value';" . br();
                }
            }
        }
    }

    /**
     * Returns the real table associated with a view field
     *
     * @param $view
     * @param $field
     */
    function real_table($view, $field) {
        if (isset($this->real_table[$view][$field]))
            return $this->real_table[$view][$field];
        return $view;
    }

    /**
     * Returns the real field associated with a view field
     *
     * @param $view
     * @param $field
     */
    function real_field($view, $field) {
        // echo "real_field ($view, $field)" . br();
        if (isset($this->real_field[$view][$field])) {
            return $this->real_field[$view][$field];
        }
        // echo "real_field ($view, $field)=$field" . br();

        return $field;
    }

    /**
     * Remplace les noms de vue et champs par leurs valeurs réelles
     *
     * @param $view
     * @param $field
     */
    function resolve(&$view, &$field) {
        $previous_view = $view;
        $previous_field = $field;
        // d'abord les champs
        if (isset($this->alias[$view][$field])) {
            if (isset($this->alias[$view][$field][0]))
                $view = $this->alias[$view][$field][0];
            if (isset($this->alias[$previous_view][$field][1]))
                $field = $this->alias[$previous_view][$field][1];
        } elseif (isset($this->alias_table[$view])) {
            $view = $this->alias_table[$view];
        }
    }

    /**
     * Returns the validation rules for the field
     *
     * @param string $table name
     * @param string $field name
     */
    function rules($table, $field, $action) {
        // 'trim|required|min_length[3]|max_length[52]|encode_php_tags|xss_clean');
        $rules = "";
        $type = $this->field_type($table, $field);
        $subtype = $this->field_subtype($table, $field);
        $size = $this->field_size($table, $field);
        $may_be_null = $this->field_attr($table, $field, 'Null');
        $required = "";

        if ($this->autogen_key($table, $field) != $field) {
            if ($may_be_null == "NO") {
                $required = "required|";
            }
        }

        if ('minute' == $subtype || 'centieme' == $subtype) {
            $rules = "trim|required";
        } elseif ('email' == $subtype) {
            $rules = "trim|" . $required . "valid_email";
        } elseif ('varchar' == $type) {
            $rules = "trim|" . $required . "max_length[$size]|encode_php_tags|xss_clean";
        } elseif ('int' == $type) {
            $rules = "trim|" . $required . "integer";
        } elseif ('time' == $subtype) {
            $rules = "trim|" . $required;
        } elseif ('decimal' == $type) {
            $rules = "trim|" . $required . "callback_valid_numeric";
            // $rules = "trim|" . $required . "numeric";
        } elseif ('date' == $type) {
            if ($subtype == 'activity_date') {
                $rules = $required . "trim|callback_valid_activity_date";
            } else {
                $rules = $required . "trim|callback_valid_date";
            }
        }

        if (($action == CREATION) && ($this->table_key($table) == $field)) {
            $rules .= "|callback_check_uniq";
        }
        // echo "rules ($table, $field, $action) $may_be_null"
        // . ", type=$type, subtype=$subtype: " . $rules . br();
        return $rules;
    }

    /**
     * Set the validation rules
     *
     * @param
     *            table to add to
     * @param $fields
     *            list of fields for which to set rules
     * @param $rules
     *            hash of additional rules
     * @param
     *            action CREATION | MODIFICATION
     */
    function set_rules($table, $fields = array(), $ext_rules = array(), $action) {
        foreach ($fields as $field) {
            $rules = $this->rules($table, $field, $action);
            if (isset($ext_rules[$field])) {
                $rules .= "|" . $ext_rules[$field];
            }
            $name = $this->field_long_name($table, $field);
            // echo "rule $field, $name, $rules" . br();
            $this->CI->form_validation->set_rules($field, $name, $rules);
        }
    }

    /**
     * Génére un label pour les formulaires
     */
    function label($table, $field) {
        $res = '<label class="form-label"';
        $res .= ' for="' . $field . '">';
        $res .= $this->field_long_name($table, $field);
        $res .= '</label>';
        return $res;
    }

    /**
     * Génère un champ de saisie
     *
     * @param string $table name
     * @param string $field name
     * @param $value
     *            précédante
     * @param $mode
     *            "ro"
     */
    function input_field($table, $field, $value = '', $mode = "ro", $attrs = array()) {
        $radio_limit = 4;
        $text_limit = 128;

        $subtype = $this->field_subtype($table, $field);
        $type = $this->field_type($table, $field);
        $size = $this->field_size($table, $field);
        $default = $this->field_default($table, $field);
        $def_attrs = $this->field_attr($table, $field, 'Attrs');
        if ($def_attrs) {
            if (is_array($def_attrs)) {
                $attrs = array_merge($attrs, $def_attrs);
            }
        }

        gvv_debug("input_field($table, $field, $value, $mode) type=$type, subtype=$subtype");

        if ($subtype == 'boolean' || 'checkbox' == $subtype) {
            $checkbox_attrs = array(
                'name' => $field,
                'id' => $field,
                'value' => 1,
                'checked' => (0 != $value)
            );
            // Merge with any additional attributes (disabled, title, etc.)
            if (!empty($attrs)) {
                $checkbox_attrs = array_merge($checkbox_attrs, $attrs);
            }
            return form_checkbox($checkbox_attrs);
        } elseif ($subtype == 'enumerate') {
            if (isset($this->field[$table][$field]['Enumerate'])) {
                $values = $this->field[$table][$field]['Enumerate'];
                if (count($values) > $radio_limit) {
                    $js = "id=\"$field\" class=\"big_select\"";
                    return form_dropdown($field, $values, $value, $js);
                } else {
                    return enumerate_radio_fields($values, $field, $value, $mode, $attrs);
                }
            } else {
                return "unknown value: " . $value;
            }
        } elseif ($subtype == 'selector') {
            $to_select = $this->field[$table][$field]['Selector'];
            $selector = $this->selector($to_select);
            // return dropdown_field($field, $value, $selector, "")
            $attrsv = "id=\"$field\"";

            if ($def_attrs) {
                foreach ($def_attrs as $k => $v) {
                    $attrsv .= " $k = \"$v\"";
                }
            }
            
            # if there are more than 8 values in the selector,
            if ($selector && count($selector) > 8) {
                if (strpos($attrsv, 'class="big_select"') === false) {
                    $attrsv .= " class=\"big_select\" ";
                }
            }


            // if (isset($this->field[$table][$field]['Alias'])) {
            // $field = $this->field[$table][$field]['Alias'];
            // $attrsv="id=\"$field\"";
            // }
            $input_fld = dropdown_field($field, $value, $selector, $attrsv);
            return $input_fld;
        } elseif ($subtype == 'image') {
            $filename = "assets/uploads/$value";
            if (file_exists($filename)) {
                return img($filename);
            } else {
                return "file $filename not found";
            }

        } elseif ($subtype == 'upload_image') {
            // Determine the correct path for different file types
            $filename = "";
            if ($table == 'membres' && $field == 'photo') {
                $filename = $value ? "uploads/photos/$value" : "";
            } elseif ($table == 'configuration' && $field == 'file') {
                // Configuration files store full path, use as-is if it exists
                $filename = $value && file_exists($value) ? $value : "";
            } elseif ($table == 'attachments' && $field == 'file') {
                // Attachment files store full path, use as-is if it exists
                $filename = $value && file_exists($value) ? $value : "";
            } else {
                $filename = $value ? "assets/uploads/$value" : "";
            }
            
            // Special handling for configuration file display in forms (limit size, make clickable)
            if ($table == 'configuration' && $field == 'file' && $filename && file_exists($filename)) {
                $mime_type = mime_content_type($filename);
                if (str_starts_with($mime_type, 'image')) {
                    // For images in configuration forms, show resized version with click to full size
                    $url = site_url() . '/' . ltrim($filename, './');
                    $img = '<div class="configuration-image-preview">';
                    $img .= '<a href="' . $url . '" target="_blank" title="Cliquer pour voir en taille réelle">';
                    $img .= '<img src="' . $url . '" alt="Configuration image" ';
                    $img .= 'style="max-width: 640px; max-height: 480px; width: auto; height: auto; ';
                    $img .= 'border: 1px solid #ccc; padding: 3px; cursor: pointer;" />';
                    $img .= '</a>';
                    $img .= '<div class="preview-help">';
                    $img .= '<i class="fa fa-external-link"></i> Cliquer sur l\'image pour la voir en taille réelle';
                    $img .= '</div>';
                    $img .= '</div>' . br();
                } else {
                    // For non-image files, show icon/link
                    $url = site_url() . '/' . ltrim($filename, './');
                    $img = attachment('', $filename, $url) . br();
                }
            } elseif ($table == 'attachments' && $field == 'file' && $filename && file_exists($filename)) {
                // Special handling for attachment file display
                $mime_type = mime_content_type($filename);
                $url = site_url() . '/' . ltrim($filename, './');
                
                if (str_starts_with($mime_type, 'image')) {
                    // For images, show preview with click to full size
                    $img = '<div class="attachment-image-preview">';
                    $img .= '<a href="' . $url . '" target="_blank" title="Cliquer pour voir en taille réelle">';
                    $img .= '<img src="' . $url . '" alt="Attachment image" ';
                    $img .= 'style="max-width: 400px; max-height: 300px; width: auto; height: auto; ';
                    $img .= 'border: 1px solid #ccc; padding: 3px; cursor: pointer;" />';
                    $img .= '</a>';
                    $img .= '<div class="preview-help">';
                    $img .= '<i class="fa fa-external-link"></i> Cliquer sur l\'image pour la voir en taille réelle';
                    $img .= '</div>';
                    $img .= '</div>' . br();
                } else {
                    // For non-image files, show icon/link
                    $img = attachment('', $filename, $url) . br();
                }
            } else {
                // Default image display for other contexts
                if ($table == 'membres' && $field == 'photo') {
                    // Special styling for member photos in forms - constrain to container
                    if (file_exists($filename)) {
                        $photo_url = base_url($filename);
                        $img = '<a href="' . $photo_url . '" target="_blank" title="Cliquer pour voir en taille réelle">' .
                               '<img src="' . $photo_url . '" alt="Photo" style="max-width: 100%; max-height: 300px; width: auto; height: auto; border: 1px solid #dee2e6; border-radius: 0.25rem; padding: 0.25rem; background-color: #f8fafc;" />' .
                               '</a>' . br();
                    } else {
                        $img = '';
                    }
                } else {
                    $img = (file_exists($filename)) ? img(array(
                        'src' => $filename,
                        'alt' => 'Photo'
                    )) . br() : '';
                }
            }
            
            $img .= form_hidden($field, $value);
            $attrs = array_merge($attrs, array(
                'type' => 'file',
                'name' => "userfile",
                'size' => 32,
                'capture' => 'camera'
            ));
            
            $js = '<script>
                document.getElementById("fileInput").onchange = function() {
                    document.getElementsByName("display_userfile")[0].value = this.value.split("\\\\").pop();
                };
            </script>';
            $input = '<label for="fileInput" class="btn btn-default">
                        <i class="fa fa-camera"></i> ' . $this->CI->lang->line('gvv_button_file') . '
                    </label>
                    <input type="file" id="fileInput" class="form-control" name="userfile" style="display:none" capture="camera">
                    <input type="text" name="display_userfile" class="form-control" value="' . $this->CI->lang->line('gvv_no_upload_file') . '">
                    ' . $js;

            return $img . $input; // Upload happens on form submit, not separate button

        } elseif ($subtype == 'minute') {
            // echo "value=$value" . br();
            $sv = set_value($field, $value);
            $value = minute_to_time($sv);
            // echo "field=$field, value=$value, sv=$sv" . br(); exit;
            $attrs = array_merge($attrs, array(
                'type' => 'text',
                'name' => $field,
                'value' => $value,
                'size' => (int) $size
            ));
            $input = form_input($attrs);
            // echo "value=$value, " . $input; exit;
            return $input;
        } elseif ($subtype == 'time') {
            // it is decimal to translate to time
            // echo "value=$value" . br();
            $sv = set_value($field, $value);
            $value = decimal_to_time($sv);
            // echo "field=$field, value=$value, sv=$sv" . br(); 
            $attrs = array_merge($attrs, array(
                'type' => 'time',
                'name' => $field,
                'value' => $value,
                'size' => (int) $size
            ));
            $input = form_input($attrs);
            // echo "value=$value, " . $input; exit;
            return $input;
        } elseif ($subtype == 'centieme') {
            // echo "value=$value" . br();
            $sv = set_value($field, $value);
            $value = $sv; // minute_to_time($sv);
            // echo "field=$field, value=$value, sv=$sv" . br(); exit;
            $attrs = array_merge($attrs, array(
                'type' => 'text',
                'name' => $field,
                'value' => $value,
                'size' => (int) $size
            ));
            $input = form_input($attrs);
            // echo "value=$value, " . $input; exit;
            return $input;
        } elseif ($subtype == 'loader') {

            $attrs = array_merge($attrs, array(
                'type' => 'file',
                'name' => "userfile",
                'size' => 64
            ));
            $input = form_input($attrs, null);

            $upload = form_input(array(
                'type' => 'submit',
                'name' => 'button',
                'value' => 'Upload'
            ));
            $upload = '<button type="submit" class="btn btn-primary">Submit</button>';
            return $input; // . $upload;
        } elseif ($subtype == 'color') {
            $attrs = array_merge($attrs, array(
                'type' => 'color',
                'name' => $field,
                'id' => $field,
                'value' => set_value($field, $value),
                'class' => 'form-control form-control-color',
            ));
            return form_input($attrs);
        }

        if ($type == 'date') {
            $value = date_db2ht($value);
            $attrs = array_merge($attrs, array(
                'type' => 'text',
                'name' => $field,
                'id' => $field,
                'value' => set_value($field, $value),
                'size' => 10,
                'class' => 'datepicker',
                'title' => 'JJ/MM/AAAA'
            ));
            return form_input($attrs);
        } elseif ('datetime' == $type) {
            $value = date_db2ht($value);
            $attrs = array_merge($attrs, array(
                'type' => 'text',
                'name' => $field,
                'id' => $field,
                'value' => set_value($field, $value),
                'size' => 16
            ));
            return form_input($attrs, null, "readonly");
        } elseif ('varchar' == $type) {
            $attrs = array_merge($attrs, array(
                'type' => 'text',
                'name' => $field,
                'id' => $field,
                'value' => set_value($field, $value),
                'size' => $size
            ));
            if ($size > $text_limit) {
                // big text area
                if (isset($this->field[$table][$field]['Title']))
                    $attrs['title'] = $this->field[$table][$field]['Title'];
                $input = form_textarea($attrs);
            } else {
                // small varchar
                if (isset($this->field[$table][$field]['Title']))
                    $attrs['title'] = $this->field[$table][$field]['Title'];
                $input = form_input($attrs);
                if ('email' == $subtype) {
                    $image = theme() . "/images/email.png";
                    $input .= nbs(2) . mailto($value, img($image));
                }
            }
            return $input;
        } elseif ('decimal' == $type) {
            $type = ($subtype == 'time') ? "time" : "text";
            $attrs = array_merge($attrs, array(
                'type' => $type,
                'name' => $field,
                'value' => set_value($field, $value),
                'size' => (int) $size
            ));
            $input = form_input($attrs);
            return $input;
        } elseif (('int' == $type) || ('tinyint' == $type)) {
            $attrs = array_merge($attrs, array(
                'type' => 'text',
                'name' => $field,
                'id' => $field,
                'value' => set_value($field, $value),
                'size' => $size
            ));
            $input = form_input($attrs);
            return $input;
        } elseif ('time' == $type) {
            $attrs = array_merge($attrs, array(
                'type' => $type,
                'name' => $field,
                'value' => set_value($field, $value),
                'size' => (int) $size
            ));
            $input = form_input($attrs);
            return $input;
        }
        echo "input_field ($table, $field, $value) undefined type=$type, size=$size<br>";
        return '';
    }

    /**
     * Transforme un champ en valeur en base
     *
     * @param string $table name
     * @param string $field name
     * @param string $value précédante
     * @return string value
     */
    function post2database($table, $field, $value = '') {
        $type = $this->field_type($table, $field);
        $subtype = $this->field_subtype($table, $field);
        $may_be_null = $this->field_attr($table, $field, 'Null');

        if ('date' == $type) {
            $formated = date_ht2db($value);
            if (!$formated && $may_be_null) {
                return null;
            }
            return $formated;
        } elseif ("time" == $subtype) {
            return str_replace(":", ".", $value);
        } elseif ("decimal" == $type) {
            if ($value == "") return 0;
            // Decimal fields are now pre-cleaned in formValidation, just normalize decimal separator
            return str_replace(',', '.', $value);
        } elseif ("time" == $type) {
            if (!$value && $may_be_null) {
                return null;
            }
            return $value;
        }
        return $value;
    }

    /**
     * Basic form
     *
     * @param string $table name
     * @param mixed[] $fields hash of field names with initial values
     * @return mixed[]
     */
    function form($table, $fields = array()) {
        $res = "";
        $res .= form_error('club') . "\n";
        $res .= "<table>\n";
        foreach ($fields as $field => $init) {
            $label = $this->field_long_name($table, $field) . ":";
            $field_value = $this->input_field($table, $field, $init);
            $res .= "<tr>";
            $res .= "\t<td align=\"right\">" . "$label</td>";
            $res .= "<td>$field_value ";
            $res .= form_error($field) . "</td>";
            $res .= "</tr>\n";
        }
        $res .= "</table>\n";
        return $res;
    }

    /**
     * Basic form
     *
     * @param string $table name
     * @param mixed[] $fields hash of field names with initial values
     * @return mixed[]
     */
    function form_flexbox($table, $fields = array()) {
        $res = "";
        $res .= form_error('club') . "\n";
        $res .= '<div class="d-flex flex-wrap">';
        foreach ($fields as $field => $init) {
            $label = $this->field_long_name($table, $field) . ":";
            $field_value = $this->input_field($table, $field, $init);
            $res .= '<div class="form-floating mb-2 border">';
            $res .= $label;
            $res .= $field_value;
            $res .= form_error($field);
            $res .= "</div>\n";
        }
        $res .= "</div>\n";
        return $res;
    }

    /**
     * Génère un formulaire à coller dans la vue.
     * Utilisable pour générer
     * rapidement une vue qu'on voudra modifier
     *
     * @param string $table name
     * @param mixed[] $fields hash of field names with initial values
     * @return string
     */
    function form_generator($table, $fields = array()) {
        $res = "\n";
        $res .= '$table = array();' . "\n";
        $res .= '$row = 0;' . "\n";
        foreach ($fields as $field => $init) {
            $res .= '$table [$row][] = $this->gvvmetadata->field_long_name("' . $table . '", "' . $field . '") . ":";' . "\n";
            $res .= '$table [$row][] = $this->gvvmetadata->input_field("' . $table . '", "' . $field . '", $' . $field . ');' . "\n";
            $res .= '$row++;' . "\n";
            $res .= '' . "\n";
        }
        $res .= 'display_form_table($table);' . "\n";
        gvv_debug("Form: " . $res);
        return $res;
    }

    /**
     * Store selector values
     *
     * @param $selector
     * @param $values
     */
    function set_selector($selector, $values) {
        $this->selectors[$selector] = $values;
    }

    /**
     * Set a field attribute dynamically
     * @param string $table Table name
     * @param string $field Field name  
     * @param string $attr_name Attribute name (e.g., 'disabled', 'readonly', 'title')
     * @param mixed $attr_value Attribute value
     */
    function set_field_attr($table, $field, $attr_name, $attr_value) {
        if (!isset($this->field[$table][$field]['Attrs'])) {
            $this->field[$table][$field]['Attrs'] = array();
        }
        $this->field[$table][$field]['Attrs'][$attr_name] = $attr_value;
    }

    /**
     * Return selector
     */
    function selector($selector) {
        return $this->selectors[$selector];
    }

    /**
     * Look for "upload_image" subtypes in a table an try to upload them
     * when their associated button has a "Télécharger" value.
     *
     * returns an array with uploaded filename as first element and
     * error message as second one (empty when no error)
     *
     * @param string $table name
     * usage:
     *   if ($newfile = $this->smsmetadata->upload("subscribers")) {
     *     $logo = $this->input->post('logo');
     *     if (file_exists("uploads/$logo"))
     *       unlink("uploads/$logo");
     *       // update the file name
     *       $data = array('logo' => $newfile);
     *       $this->subscribers_model->update($stb, $data);
     *       redirect("subscriber/edit/$stb");
     *     }
     *   }
     */
    public function upload($table) {
        $list = $this->fields_list($table);
        foreach ($list as $field) {
            if (isset($this->field[$table][$field]['Subtype']) && $this->field[$table][$field]['Subtype'] == "upload_image") {
                $button = $this->CI->input->post("button_$field");

                $userfile = $this->CI->input->post('userfile');
                // pb userfile = false
                // C'est un bug mais userfile est seulement utilisé pour les traces
                // CF $_FILES

                if ($button == $this->CI->lang->line('gvv_button_upload')) {
                    // echo "uploading $userfile to ..." . br();
                    $config['upload_path'] = './uploads/';
                    $config['allowed_types'] = 'zip|png|jpeg|jpg|gif';
                    $config['max_size'] = '4000000';
                    $config['encrypt_name'] = TRUE;

                    $this->CI->load->library('upload', $config);
                    if ($this->CI->upload->do_upload()) {
                        $uploaded = $this->CI->upload->data();
                        return array(
                            $uploaded['file_name'],
                            ""
                        );
                    } else {
                        // TODO translate this message
                        return array(
                            "",
                            "Chargement de $userfile\n" . "vers " . $config['upload_path'] . "\n" . "extension supportées " . $config['allowed_types'] . "\n" . "taille max " . $config['max_size'] . "\n" . $this->CI->upload->display_errors()
                        );
                    }
                }
            }
        }
        return array("", "");
    }
}
