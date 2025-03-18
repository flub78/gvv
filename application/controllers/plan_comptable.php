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
 * @filesource plan_comptable.php
 * @package controllers
 * Controleur du plan comptable
 */
include('./application/libraries/Gvv_Controller.php');
class Plan_Comptable extends Gvv_Controller {

    protected $controller = 'plan_comptable';
    protected $model = 'plan_comptable_model';
    protected $kid = 'pcode';

    // régles de validation
    protected $fields = array(
        'pcode' => array(
            'label' => 'Code',
            'default' => '',
            'rules' => 'trim||max_length[10]'
        ),

        'pdesc' => array(
            'label' => 'Description',
            'default' => '',
            'rules' => 'trim|required|max_length[50]'
        )
    );

    protected $rules = array(
        'pcode' => "required|is_uniq[planc.pcode]",
        'pdesc' => "trim|required|max_length[50]"
    );

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();
    }

    /**
     * Génération des éléments à passer au formulaire en cas de création,
     * modification ou réaffichage après erreur.
     */
    function form_static_element($action) {
        parent::form_static_element($action);
        $this->data['code_selector'] = $this->gvv_model->selector();
    }

    /**
     * Génere les information demandées par le datatable Jquery
     */
    function ajax_page() {
        gvv_debug("ajax_page plan comptable");

        /*
         * Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
         */
        $aColumns = array(
            'pcode',
            'pdesc'
        );

        $actions = array(
            'edit',
            'delete'
        );

        /* Indexed column (used for fast and accurate table cardinality) */
        $sIndexColumn = "pcode";

        /* DB table to use */
        $sTable = "planc";

        /* Database connection information */
        $this->load->library('Database');

        /*
         * Paging
         */
        $sLimit = "";
        if (isset($_GET['iDisplayStart']) && $_GET['iDisplayLength'] != '-1') {
            $sLimit = "LIMIT " . mysql_real_escape_string($_GET['iDisplayStart']) . ", " . mysql_real_escape_string($_GET['iDisplayLength']);
        }

        /*
         * Ordering
         */
        $sOrder = "";
        if (isset($_GET['iSortCol_0'])) {
            $sOrder = "ORDER BY  ";
            for ($i = 0; $i < intval($_GET['iSortingCols']); $i++) {
                if ($_GET['bSortable_' . intval($_GET['iSortCol_' . $i])] == "true") {
                    $sOrder .= $aColumns[intval($_GET['iSortCol_' . $i])] . "
                                        " . mysql_real_escape_string($_GET['sSortDir_' . $i]) . ", ";
                }
            }

            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = "";
            }
        }

        $sOrder = "order by pcode ";

        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $sWhere = "";
        if ($_GET['sSearch'] != "") {
            $sWhere = "WHERE (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $sWhere .= $aColumns[$i] . " LIKE '%" . mysql_real_escape_string($_GET['sSearch']) . "%' OR ";
            }
            $sWhere = substr_replace($sWhere, "", -3);
            $sWhere .= ')';
        }

        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if ($_GET['bSearchable_' . $i] == "true" && $_GET['sSearch_' . $i] != '') {
                if ($sWhere == "") {
                    $sWhere = "WHERE ";
                } else {
                    $sWhere .= " AND ";
                }
                $sWhere .= $aColumns[$i] . " LIKE '%" . mysql_real_escape_string($_GET['sSearch_' . $i]) . "%' ";
            }
        }

        /*
         * SQL queries
         * Get data to display
         */
        $sQuery = "
                SELECT SQL_CALC_FOUND_ROWS " . str_replace(" , ", " ", implode(", ", $aColumns)) . "
                FROM   $sTable
                $sWhere
                $sOrder
                $sLimit
            ";
        gvv_debug("ajax_page sql: " . $sQuery);
        $select = $this->database->sql($sQuery, true);
        $result = $select[0];
        gvv_debug(var_export($result, true));
        // $rResult = mysql_query($sQuery, $gaSql['link']) or die(mysql_error());

        /* Data set length after filtering */
        $sQuery = "
                SELECT FOUND_ROWS()
            ";
        // $rResultFilterTotal = mysql_query($sQuery, $gaSql['link']) or die(mysql_error());
        $rResultFilterTotal = $this->database->sql($sQuery, true);
        $iFilteredTotal = $rResultFilterTotal[0][0]['FOUND_ROWS()'];
        gvv_debug("iFilteredTotal=$iFilteredTotal");

        /* Total data set length */
        $sQuery = "
                SELECT COUNT(" . $sIndexColumn . ")
                FROM   $sTable
            ";

        $rResultTotal = $this->database->sql($sQuery, true);
        // gvv_debug(var_export($rResultTotal, true));
        $iTotal = $rResultTotal[0][0]['COUNT(pcode)'];
        gvv_debug("\$iTotal = $iTotal");

        /*
         * Output
         */
        $output = array(
            "sEcho" => intval($_GET['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array()
        );

        foreach ($result as $select_row) {
            $row = array();
            foreach ($actions as $action) {
                $url = $this->controller . "/$action";
                $elt_image = "la catégorie de compte " . $select_row['pdesc'];
                $confirm = ($action == 'delete');

                $image = $this->gvvmetadata->action($action, $url, $select_row[$sIndexColumn], $elt_image, $confirm);
                $row[] = $image;
            }
            for ($i = 0; $i < count($aColumns); $i++) {
                if ($aColumns[$i] != ' ') {
                    /* General output */
                    $row[] = $select_row[$aColumns[$i]];
                } else {
                    $row[] = "";
                }
            }


            $output['aaData'][] = $row;
        }

        $json = json_encode($output);
        gvv_debug("json = $json");
        echo $json;
    }

    /**
     * Test unitaire
     */
    function test($format = "html") {
        // parent::test($format);
        $this->unit_test = TRUE;
        $this->load->library('unit_test');

        $this->unit->run(true, true, "Tests $this->controller");
        $this->tests_results($format);
    }
}
