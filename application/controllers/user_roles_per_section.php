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
        $current_url = $this->input->post('current_url');
        $this->session->set_userdata('section', $section);
        gvv_debug("section set to $section");

        // Determine where to redirect after section change
        // Default: redirect to welcome page to avoid errors
        $redirect_url = site_url('welcome');
        
        // If a current URL is provided and it's safe to return to it, use it
        // Safe conditions: URL is a GET page (not a POST form that could be resubmitted)
        if ($current_url && $this->is_safe_redirect_url($current_url)) {
            $redirect_url = $current_url;
            gvv_debug("Redirecting to current page: $redirect_url");
        } else {
            gvv_debug("Redirecting to welcome page for safety");
        }
        
        if ($this->input->is_ajax_request()) {
            // Return JSON response for AJAX
            echo json_encode(['redirect' => $redirect_url]);
        } else {
            redirect($redirect_url);
        }
    }

    /**
     * Check if a URL is safe to redirect to after section change
     * 
     * @param string $url The URL to check
     * @return bool True if safe to redirect
     */
    private function is_safe_redirect_url($url) {
        // Parse the URL to extract the path
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['path'])) {
            return false;
        }
        
        $path = $parsed['path'];
        
        // List of controllers/actions that are NOT safe (POST endpoints, forms)
        // These are pages where reloading could cause double submission or errors
        $unsafe_patterns = array(
            '/create',
            '/edit/',
            '/modify',
            '/delete',
            '/save',
            '/update',
            '/submit',
            '/insert',
            '/add',
        );
        
        // Check if URL matches unsafe patterns
        foreach ($unsafe_patterns as $pattern) {
            if (strpos($path, $pattern) !== false) {
                return false;
            }
        }
        
        // Default: safe to redirect (most GET pages are safe)
        // This allows pages like comptes/balance, tableau, page, view, etc.
        return true;
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

}
