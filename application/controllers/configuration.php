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
 * @filesource avion.php
 * @package controllers
 * 
 * Contrôleur de gestion des paramètres de configuration
 */
include ('./application/libraries/Gvv_Controller.php');
class Configuration extends Gvv_Controller {

    // Tout le travail est fait par le parent
    protected $controller = 'configuration';
    protected $model = 'configuration_model';
    protected $modification_level = 'ca';
    protected $rules = array ();

        /**
     * Génération des éléments statiques à passer au formulaire en cas de création,
     * modification ou ré-affichage après erreur.
     * Sont statiques les parties qui ne changent pas d'un élément sur l'autre.
     *
     * @param $action CREATION
     *            | MODIFICATION | VISUALISATION
     * @see constants.php
     */
    protected function form_static_element($action) {
        $this->data['action'] = $action;
        $this->data['fields'] = $this->fields;
        $this->data['controller'] = $this->controller;
        if ($action == "visualisation") {
            $this->data['readonly'] = "readonly";
        }

        $this->data['saisie_par'] = $this->dx_auth->get_username();

        $this->load->model('sections_model');
        $section_selector = $this->sections_model->selector_with_null();
        $this->gvvmetadata->set_selector('section_selector', $section_selector);

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