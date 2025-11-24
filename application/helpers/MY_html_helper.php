<?php
/*
 * GVV Gestion vol à voile
 * Copyright (C) 2011 Philippe Boissel & Frédéric Peignot
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

if (! function_exists('p')) {
    /**
     * Generates an HTML paragraph
     *
     * @param unknown_type $str
     * @return string
     */
    function p($str, $attr = '') {
        if (func_num_args() > 0) {
            $str = func_get_arg(0);
        }
        if (func_num_args() > 1) {
            $attr = func_get_arg(1);
        } else {
            $attr = '';
        }

        return "<p $attr>" . $str . "</p>";
    }
}

if (! function_exists('e_p')) {
    function e_p($str, $attr = '') {
        echo p($str, $attr);
    }
}

if (! function_exists('hr')) {
    /**
     * Generates one or several HTML horizontal line
     */
    function hr($n = 1) {
        $res = "";
        for ($i = 0; $i < $n; $i++) {
            $res .= "<hr/>";
        }
        return $res;
    }
}

if (! function_exists('e_hr')) {
    function e_hr($n = 1) {
        echo hr($n);
    }
}

if (! function_exists('e_br')) {
    function e_br($n = 1) {
        echo br($n);
    }
}

/**
 * Heading
 *
 * Generates an HTML heading tag. First param is the data.
 * Second param is the size of the heading tag.
 *
 * @access public
 * @param
 *            string
 * @param
 *            integer
 * @return string
 * 
 * Todo: static analyzer says heading() override never used: The heading() function override attempts to add i18n translation support and add newlines, but it's never used because the core CI html_helper.php is loaded first and defines heading() already. 
 */
if (! function_exists('heading')) {
    function heading($txt = '', $h = '1', $attrs = '') {
        $CI = &get_instance();
        $translation = $CI->lang->line($txt);
        if ($translation) {
            $txt = $translation;
        }
        return "<h$h $attrs>" . $txt . "</h$h>\n";
    }
}

if (! function_exists('e_heading')) {
    function e_heading($data = '', $h = '1', $attrs = '') {
        echo heading($data, $h, $attrs);
    }
}

if (! function_exists('html_row')) {

    function html_row(array $cols, array $attrs = []) {
        $res = '';
        $res .= "<tr";
        foreach ($attrs as $key => $value) {
            $res .= " $key=\"$value\"";
        }
        $res .= ">";
        foreach ($cols as $col) {
            $res .= "\t<td>$col</td>\n";
        }        
        $res .= "</tr>\n";
        return $res;
    } 
}

if (! function_exists('table_from_array')) {
    /**
     * Generates a html table from an array of rows
     * Each rows must be an array of cells
     *
     * @return string TODO check for incorrect parameter type
     */
    function table_from_array($table, $attrs = array()) {
        $class = isset($attrs['class']) ? $attrs['class'] : '';
        $res = "";
 
        $style_str = isset($attrs['style']) ? 'style="' . $attrs['style'] .'"' : '';
        $res .= "<table class=\"$class\" $style_str >\n";

        if (array_key_exists('title', $attrs)) {
            $title = $attrs['title'];
            $res .= "\t<caption>$title</caption>\n";
        }
        $alignments = (array_key_exists('align', $attrs)) ? $attrs['align'] : array();

        if (array_key_exists('fields', $attrs)) {
            $res .= "\t<thead>";
            $res .= "<tr>";
            $cnt = 0;
            foreach ($attrs['fields'] as $field) {
                $align = (array_key_exists($cnt, $alignments)) ? 'align="' . $alignments[$cnt] . '"' : "";
                $res .= "\t\t<th $align class=\"ui-state-default\" >";
                $res .= $field;
                $res .= "</th>\n";
                $cnt++;
            }
            $res .= "</tr>";
            $res .= "</thead>\n";
        }

        $line_cnt = 0;
        foreach ($table as $row) {
            $line_cnt++;
            if ($line_cnt % 2) {
                $res .= "\t<tr class=\"odd\"  >";
            } else {
                $res .= "\t<tr class=\"even\"  >";
            }
            $cnt = 0;
            foreach ($row as $cell) {
                $align = (array_key_exists($cnt, $alignments)) ? 'align="' . $alignments[$cnt] . '"' : "";
                $res .= "\t\t<td $align>";
                $res .= $cell;
                $res .= "</td>\n";
                $cnt++;
            }
            $res .= "\t</tr>\n";
        }
        $res .= "</table>\n";
        return $res;
    }
}

if (! function_exists('flat_array')) {

    /**
     * Convert a list of hash tables into an array of rows of cells
     *
     * The list of hash table is typicall the result of SQL query
     * array (size=109)
     * 0 =>
     * array (size=7)
     * 'count' => string '4' (length=1)
     * 'year' => string '2013' (length=4)
     * 'vpdc' => string '1' (length=1)
     * 'vpmacid' => string 'F-CJRG' (length=6)
     * 'nom' => string 'Adams Louis' (length=11)
     * 'minutes' => string '470.00' (length=6)
     * 'kms' => string '0' (length=1)
     * 1 =>
     * array (size=7)
     * 'count' => string '10' (length=2)
     * 'year' => string '2013' (length=4)
     * 'vpdc' => string '0' (length=1)
     * 'vpmacid' => string 'F-CXXX' (length=6)
     * 'nom' => string 'Autre pilote ExtÃ©rieur' (length=23)
     * 'minutes' => string '1470.00' (length=7)
     * 'kms' => string '0' (length=1)
     *
     * @param
     *            hash_list see example
     * @param
     *            row_id name of the field used as row identifier
     * @param
     *            col_id name of the filed uased as colomn identifier
     * @param
     *            value name of the filed to be used as value in the array
     */
    function flat_array($hash_list, $row_id = '', $col_id = '', $values = '', $delete = '') {
        $col_list = array(); // sequential list of colomns
        $col_index = array(); // access to colomn index from colomn id
        $row_list = array(); // same with rows
        $row_index = array();
        $col_nb = 0; // number of colomns
        $row_nb = 0; // number of rows

        // Look for colomns and rows
        foreach ($hash_list as $elt) {
            $col = $elt[$col_id];
            $row = $elt[$row_id];

            if (! isset($col_index[$col])) {
                // it is a new col
                $col_index[$col] = $col_nb++;
                $col_list[] = $col;
            }

            if (! isset($row_index[$row])) {
                // It is a new row
                $row_index[$row] = $row_nb++;
                $row_list[] = $row;
            }
        }

        // sort rows and cols
        // it implies to reset the indexes
        sort($col_list);
        $col_nb = 0;
        foreach ($col_list as $col) {
            $col_index[$col] = $col_nb++;
        }

        sort($row_list);
        $row_nb = 0;
        foreach ($row_list as $row) {
            $row_index[$row] = $row_nb++;
        }

        // Generates an empty array
        $empty_line = array();
        $empty_line[] = '';
        foreach ($col_list as $col_title) {
            $empty_line[] = 0;
        }

        $result = array();
        // push the title line
        $result[] = array_merge(array(
            ''
        ), $col_list);
        // fill the result with empty lines
        $n = 1;
        foreach ($row_list as $row_title) {
            $result[$n] = $empty_line;
            $result[$n][0] = $row_title;
            $n++;
        }

        // fill the result array with values
        foreach ($hash_list as $elt) {
            $col = $elt[$col_id];
            $row = $elt[$row_id];
            $val = $elt[$values];

            $i = $row_index[$row];
            $j = $col_index[$col];

            $result[$i + 1][$j + 1] = $val;
        }

        // Totaux
        $total = 1;
        $CI = &get_instance();
        $total = $CI->lang->line('gvv_total');

        if ($total) {
            $total_col_index = count($col_list) + 1;
            $total_col = array();
            for ($j = 1; $j <= count($col_list); $j++) {
                $total_col[$j] = 0;
            }

            $result[0][$total_col_index] = "$total:";
            $total_total = 0;
            for ($i = 1; $i <= count($row_list); $i++) {
                $total_line = 0;
                for ($j = 1; $j <= count($col_list); $j++) {
                    $total_line += $result[$i][$j];
                    $total_col[$j] += $result[$i][$j];
                }
                $result[$i][$total_col_index] = $total_line;
                $total_total += $total_line;
            }
            $total_col[] = $total_total;

            $result[] = array_merge(array(
                "$total: "
            ), $total_col);
            $col_list[] = $total;
            $row_list[] = $total;
        }

        // format
        if ($values == 'minutes') {
            for ($i = 1; $i <= count($row_list); $i++) {
                for ($j = 1; $j <= count($col_list); $j++) {
                    $result[$i][$j] = minute_to_time($result[$i][$j]);
                    if ($result[$i][$j] == $delete) {
                        $result[$i][$j] = '';
                    }
                }
            }
        }

        return $result;
    }
}

if (! function_exists('flatten')) {

    /**
     * Flatten an array of hashs
     * 
     * @param unknown $table
     * @param unknown $fields
     */
    function flatten($table, $attrs = array()) {

        $fields = $attrs['fields'];
        $result = array();

        if (isset($attrs['headers'])) {
            $result[] = $attrs['headers'];
        } else {
            $result[] = $attrs['fields'];
        }

        foreach ($table as $elt) {

            $row = array();
            foreach ($fields as $field) {
                if (array_key_exists($field, $elt)) {
                    $row[] = $elt[$field];
                } else {
                    $row[] = "";
                }
            }
            $result[] = $row;
        }
        return $result;
    }
}

if (! function_exists('curPageURL')) {
    /**
     * Retourne l'URL de la page courante
     */
    function curPageURL() {
        $pageURL = 'http';
        if (isset($_SERVER["HTTPS"])) {
            if ($_SERVER["HTTPS"] == "on") {
                $pageURL .= "s";
            }
        }
        $pageURL .= "://";
        if (isset($_SERVER["SERVER_NAME"])) {
            $pageURL .= $_SERVER["SERVER_NAME"];
        }
        if (isset($_SERVER["SERVER_PORT"]) && $_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= ":" . $_SERVER["SERVER_PORT"];
        }
        if (isset($_SERVER["REQUEST_URI"])) {
            $pageURL .= $_SERVER["REQUEST_URI"];
        }
        return $pageURL;
    }
}

if (! function_exists('html_link')) {
    /**
     * genere une balise lien
     */
    function html_link($args = array(), $balise = "link") {
        $res = "<$balise ";

        foreach (
            array(
                'rel',
                'type',
                'href',
                'src',
                'title',
                'media'
            ) as $opt
        ) {
            if (isset($args[$opt])) {
                $res .= " $opt=\"$args[$opt]\" ";
            }
        }
        $res .= "></$balise>\n";
        return $res;
    }
}

if (! function_exists('html_script')) {
    /**
     * genere une balise script
     */
    function html_script($args = array()) {
        return html_link($args, "script");
    }
}

if (! function_exists('e_html_script')) {
    /**
     * genere une balise script
     */
    function e_html_script($args = array()) {
        echo html_script($args);
    }
}

if (! function_exists('markup_open')) {
    /**
     * genere une balise
     */
    function markup_open($type = 'div', $attrs = array()) {
        $res = "<$type";
        foreach ($attrs as $key => $value) {
            $res .= " $key=\"$value\"";
        }
        return $res . ">";
    }
}

if (! function_exists('markup_close')) {
    /**
     * genere une balise div
     */
    function markup_close($type = 'div') {
        return "</$type>\n";
    }
}

if (! function_exists('markup')) {
    /**
     * genere une balise script
     */
    function markup($type = 'div', $content, $attrs = array()) {
        return markup_open($type, $attrs) . $content . markup_close($type);
    }
}

if (! function_exists('e_div_open')) {
    /**
     * genere une balise div
     */
    function e_div_open($attrs = array()) {
        echo markup_open('div', $attrs);
    }
}

if (! function_exists('e_div_close')) {
    /**
     * genere une balise div
     */
    function e_div_close() {
        echo markup_close('div');
    }
}

if (! function_exists('e_div')) {
    /**
     * genere une balise script
     */
    function e_div($content, $attrs = array()) {
        echo markup('div', $content, $attrs);
    }
}

if (! function_exists('e_input')) {
    /**
     * genere une balise script
     */
    function e_input($attrs = array()) {
        echo markup('input', '', $attrs);
    }
}

if (! function_exists('input')) {
    /**
     * genere une balise script
     */
    function input($attrs = array()) {
        return markup('input', '', $attrs);
    }
}

// article
if (! function_exists('e_article_open')) {
    /**
     * genere une balise div
     */
    function e_article_open($attrs = array()) {
        echo markup_open('article', $attrs);
    }
}

if (! function_exists('e_article_close')) {
    /**
     * genere une balise div
     */
    function e_article_close() {
        echo markup_close('article');
    }
}

if (! function_exists('e_article')) {
    /**
     * genere une balise script
     */
    function e_article($content, $attrs = array()) {
        echo markup('article', $content, $attrs);
    }
}

// section
if (! function_exists('e_section_open')) {
    /**
     * genere une balise div
     */
    function e_section_open($attrs = array()) {
        echo markup_open('section', $attrs);
    }
}

if (! function_exists('e_section_close')) {
    /**
     * genere une balise div
     */
    function e_section_close() {
        echo markup_close('section');
    }
}

if (! function_exists('e_section')) {
    /**
     * genere une balise script
     */
    function e_section($content, $attrs = array()) {
        echo markup('section', $content, $attrs);
    }
}

if (! function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}

if (! function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        if ($needle === '') {
            return true;
        }
        return substr($haystack, -strlen($needle)) === $needle;
    }
}

if (! function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        if ($needle === '') {
            return true;
        }
        return strpos($haystack, $needle) !== false;
    }
}


if (! function_exists('attachment')) {
    /**
     * Generate a link to download an uploaded file
     * @param unknown $route_name
     * @param unknown $id
     * @param unknown $field
     * @param unknown $label
     * @return string

     * @SuppressWarnings("PMD.ShortVariable")
     */
    function attachment($id, $filename, $url = "") {
        if (!$filename) return "";

        $mime_type = mime_content_type($filename);

        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        $inner_html = "";
        if (str_starts_with($mime_type, 'image') || ($extension == 'avif')) {
            $img = '<img class="doc-thumbnail"';
            $img .= ' src="' . $url . '"';
            $img .= '/>';
            $inner_html = $img;
        } else {
            if (str_ends_with($mime_type, 'pdf')) {
                $inner_html = "<i class=\"fas fa-file-pdf fa-2x text-danger\" title=\"$filename\"></i>";
            } else if (str_ends_with($mime_type, 'txt') || str_ends_with($mime_type, 'text/plain')) {
                $inner_html = "<i class=\"fas fa-file-alt fa-2x\" title=\"$filename\"></i>";
            } else if (str_ends_with($mime_type, 'md') || str_ends_with($mime_type, 'markdown')) {
                $inner_html = "<i class=\"fas fa-file-alt fa-2x\" title=\"$filename\"></i>";
            } else if (str_ends_with($mime_type, 'csv')) {
                $inner_html = "<i class=\"fas fa-file-csv fa-2x\" title=\"$filename\"></i>";
            } else if (str_ends_with($mime_type, 'xlsx') || str_ends_with($mime_type, 'xls') || str_contains($mime_type, 'spreadsheet')) {
                $inner_html = "<i class=\"fas fa-file-excel fa-2x text-success\" title=\"$filename\"></i>";
            } else if (str_ends_with($mime_type, 'doc') || str_ends_with($mime_type, 'docx') || str_contains($mime_type, 'word')) {
                $inner_html = "<i class=\"fas fa-file-word fa-2x text-primary\" title=\"$filename\"></i>";
            } else if (str_ends_with($mime_type, 'ppt') || str_ends_with($mime_type, 'pptx') || str_contains($mime_type, 'powerpoint') || str_contains($mime_type, 'presentation')) {
                $inner_html = "<i class=\"fas fa-file-powerpoint fa-2x text-warning\" title=\"$filename\"></i>";
            } else {
                $inner_html = "<i class=\"fas fa-file fa-2x\" title=\"$filename\"></i>";
            }
        }
        return "<a href=\"$url\" target=\"_self\">$inner_html</a>";
    }
}

if (! function_exists('euros')) {
    /**
     * Format a numeric value as euros with French formatting and euro symbol
     *
     * @param mixed $value The numeric value to format
     * @param string $target Output target: 'html' (default), 'pdf', or 'csv'
     * @return string Formatted string with euro symbol for html/pdf, without for csv
     */
    function euros($value, $target = 'html') {
        if ($value === null || $value === '') {
            return '';
        }

        if ($target == 'html') {
            $thousand_sep = '&nbsp;';
            $symbol = '&nbsp;€';
        } elseif ($target == 'pdf') {
            $thousand_sep = ' ';
            $symbol = ' €';
        } else {
            // csv or other
            $thousand_sep = ' ';
            $symbol = '';
        }

        return number_format((float)$value, 2, ',', $thousand_sep) . $symbol;
    }
}
