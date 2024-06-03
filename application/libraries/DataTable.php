<?php


/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * 
 */
if (!defined('BASEPATH'))
    exit ('No direct script access allowed');

/**
 * A DataTable is a widget in charge of displaying a table with pagination
 * computed form a database select or others data.
 */
class DataTable extends Widget {
    /**
     * Constructor - Sets DataTableer's Preferences
     *
     * The constructor can be passed an array of attributes values
     */
    public function __construct($attrs = array ()) {
        $CI = & get_instance();

        // Defaults
        $this->attr['title'] = '';
        $this->attr['values'] = array ();
        $this->attr['controller'] = ""; // ??
        $this->attr['create'] = '';
        $this->attr['count'] = 0;
        $this->attr['first'] = 0;
        $this->attr['pagination'] = '';
        $this->attr['align'] = array ();

        // calls the parent constructor
        parent::__construct($attrs);

        $CI->load->library('pagination');

        // if no pagination has been specified but we have enough information
        // to compute one
        if ($this->attr['pagination'] == '') {
            if ($this->attr['count'] > PER_PAGE) {

                $config['base_url'] = controller_url($this->attr['controller']) . '/page/';
                $config['total_rows'] = $this->attr['count'];
                $config['per_page'] = PER_PAGE;

                $CI->pagination->initialize($config);
                $this->attr['pagination'] = $CI->pagination->create_links();
            }
        }
    }

    /**
     *	Image of the widget
     *
     *	@param array $where	hash array with conditions
     */
    public function image() {
        // just some aliases for convenience
        $title = $this->attr['title'];
        $values = $this->attr['values'];
        $pagination = $this->attr['pagination'];
        $create = $this->attr['create'];
        $count = $this->attr['count'];
        $first = $this->attr['first'] + 1;
        $last = $first +count($values) - 2;
        $class = array_key_exists('class', $this->attr) ? "class=\"" . $this->attr['class'] . "\"" : '';

        // build the image
        $res = ""; // <center>\n";
        if ($count != '') {
            $res .= br() . nbs(2) . "$first-$last/$count" . nbs(4) . $pagination;
        }

        if (count($values) == 0) {
            return $res;
        }

        $res .= "<table $class>\n";
        $res .= "\t<caption>$title</caption>\n";

        $res .= "\t<thead>";
        $res .= "<tr>\n";
        $title_row = $values[0];
        if ($create != "") {
            $title_row[count($title_row) - 1] = $create;
        }
        foreach ($title_row as $colName) {
            $res .= "\t\t<th>$colName</th>\n";
        }
        //		$res .=  "\t\t<th>$create</th>\n";
        $res .= "</tr>";
        $res .= "</thead>\n";

        // foreach row
        for ($i = 1; $i < count($values); $i++) {
            $row = $values[$i];
            $res .= "\t<tr>\n";

            $col = 0;
            foreach ($row as $fieldValue) {
                // $res .=  "\t\t<td >" . $fieldValue . "</td>\n";

                if (array_key_exists($col, $this->attr['align'])) {
                    $align = 'align="' . $this->attr['align'][$col] . '"';
                } else {
                    $align = "";
                }
                $res .= "\t\t<td $align>";
                $res .= $fieldValue . "</td>\n";
                $col++;
            }
            $res .= "\t</tr>\n";
        }
        $res .= "</table>\n";

        if ($count != '') {
            $res .= nbs(2) . "$first-$last/$count" . nbs(4) . $pagination . br(2);
        }

        //		$res .= "</center>\n";
        return $res;
    }
}