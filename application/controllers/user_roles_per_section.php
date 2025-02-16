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
class User_roles_per_section extends Gvv_Controller {
    protected $controller = 'user_roles_per_section';
    protected $model = 'user_roles_per_section_model';

    protected $rules = array();

    /**
     * Constructor
     */
    function __construct() {

        parent::__construct();
        $this->lang->load('user_roles_per_section');
        $this->load->model('types_roles_model');
        $this->load->model('dx_auth/users', 'users_model');
    }

    /** 
     * Select the current section
     */
    function set_section() {
        $section = $this->input->post('section');
        $this->session->set_userdata('section', $section);
        redirect(site_url($this->controller));
    }

    /**
     * Affiche une page d'éléments
     *
     * @param $premier élément
     *            à afficher
     * @param
     *            message message à afficher
     */
    function page($premier = 0, $message = '', $selection = array()) {
        $this->data['section_selector'] = $this->section_selector;
        $section = $this->session->userdata('section');

        parent::page($premier, $message, $selection);
    }

    /**
     * Génération des éléments à passer au formulaire en cas de création,
     * modification ou réaffichage après erreur.
     *
     * @param string $action
     *            creation, modification
     */
    function form_static_element($action) {
        parent::form_static_element($action);

        $role_selector = $this->types_roles_model->selector();
        $this->gvvmetadata->set_selector('role_selector', $role_selector);

        $user_selector = $this->users_model->selector();
        $this->gvvmetadata->set_selector('user_selector', $user_selector);

        $this->data['saisie_par'] = $this->dx_auth->get_username();
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

        // parent::test();
        $this->tests_results('xml');
        $this->tests_results($format);
    }
}
