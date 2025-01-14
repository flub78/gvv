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
 * @filesource sections.php
 * @package controllers
 * Controleur des sections / CRUD
 *
 * Sections
 */

/**
 * Include parent library
 */
include('./application/libraries/Gvv_Controller.php');

/**
 * Controleur de gestion des sections
 */
class Sections extends Gvv_Controller {
    protected $controller = 'sections';
    protected $model = 'sections_model';

    protected $rules = array();

    /**
     * Constructor
     *
     * Affiche header et menu
     */
    function __construct() {
        parent::__construct();
        $this->lang->load('sections');
    }

    /**
     * Affiche le formulaire de création
     */
    // function create() {

    //     // Méthode basée sur les méta-données
    //     $table = $this->gvv_model->table();
    //     $this->data = $this->gvvmetadata->defaults_list($table);

    //     $this->form_static_element(CREATION);

    //     return load_last_view($this->form_view, $this->data, $this->unit_test);
    // }


    /**
     * Supprime un élément
     * TODO: interdire la suppression d'une section qui a des éléments
     */
    function delete($id) {
        parent::delete($id);
    }

    /**
     * Test unitaire
     */
    function test($format = "html") {

        $this->unit_test = TRUE;
        $this->load->library('unit_test');

        $this->unit->run(true, true, "Tests $this->controller");

        $res = $this->gvv_model->test();
        $all_passed = !in_array(false, array_column($res, 'result'));
        if ($all_passed) {
            $count = count($res);
            $this->unit->run(true, true, "All " . $count . " Model tests $this->controller are passed");
        } else {
            foreach ($res as $t) {
                $this->unit->run($t["result"], true, $t["description"]);
            }
        }

        parent::test();
        $this->tests_results('xml');
        $this->tests_results($format);
    }
}
