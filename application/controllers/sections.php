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
 *
 * Playwright tests:
 *   - npx playwright test tests/sections_ordre_affichage.spec.js
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
     */
    function __construct() {
        parent::__construct();
        
        // Authorization: Code-based (v2.0) - only for migrated users
        // page/view accessible to all users, create/edit/delete requires ca (via modification_level)
        if ($this->use_new_auth) {
            $this->require_roles(['user']);
        }
        
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

    /**
     * Export de la liste des sections en CSV ou PDF
     */
    public function export($mode = 'csv') {
        // Legacy authorization for non-migrated users
        if (!$this->use_new_auth && !$this->dx_auth->is_role('ca')) {
            $this->dx_auth->deny_access();
            return;
        }
        
        $rows = $this->gvv_model->select_page(10000, 0);
        $fields = array('nom', 'description');
        $title = $this->lang->line('gvv_sections_title');

        if ($mode === 'csv') {
            return $this->gvvmetadata->csv_table('vue_sections', $rows, array(
                'title' => $title,
                'fields' => $fields,
            ));
        }

        $this->load->library('Pdf');
        $pdf = new Pdf();
        $pdf->AddPage('P');
        $width = array(60, 120);
        $this->gvvmetadata->pdf_table('vue_sections', $rows, $pdf, array(
            'title' => $title,
            'fields' => $fields,
            'width' => $width,
        ));
        $pdf->Output();
    }
}
