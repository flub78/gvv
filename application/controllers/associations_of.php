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
 * @filesource associations_of.php
 * @package controllers
 * Controleur de gestion de l'associationd des comptes OpenFLyers avec les comptes GVV.
 */
include('./application/libraries/Gvv_Controller.php');
class Associations_OF extends Gvv_Controller {

    // Tout le travail est fait par le parent
    protected $controller = 'associations_of';
    protected $model = 'associations_of_model';
    protected $modification_level = 'ca';
    protected $rules = array();


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

        // $this->load->model('sections_model');
        // $section_selector = $this->sections_model->selector_with_null();
        // $this->gvvmetadata->set_selector('section_selector', $section_selector);

        $this->load->model('comptes_model');
        $compte_selector = $this->comptes_model->selector_with_null([], TRUE);
        $this->gvvmetadata->set_selector('compte_selector', $compte_selector);
    }

    /**
     * Transforme les données brutes en base en données affichables
     * Default implementation returns the data attribute
     *
     * @param $action CREATION
     *            | MODIFICATION | VISUALISATION
     */
    function form2database($action = '') {
        $processed_data = parent::form2database($action);

        if (!$processed_data['id_compte_gvv']) {
            // Si pas de compte GVV, on met NULL
            // $processed_data['id_compte_gvv'] = NULL;
            unset($processed_data['id_compte_gvv']);
        }
        return $processed_data;
    }

    /**
     * Create an association and return a json status
     */
    public function associate () {
            $nom_of = $this->input->get('nom_of');
            $id_of = $this->input->get('id_of');
            $cptGVV = $this->input->get('cptGVV');

            gvv_debug("associate (id_of=$id_of, nom_of=$nom_of, gvv=$cptGVV)");

            // $this->load->model('associations_of_model');

            $data = array(
                'id_compte_of' => $id_of,
                'nom_of' => $nom_of,
                'id_compte_gvv' => $cptGVV
            );
        
            $result = $this->gvv_model->create($data);
        
            $response = array(
                'status' => $result ? 'success' : 'error',
                'message' => $result ? 'Association created successfully' : 'Failed to create association'
            );
        
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($response));
    }

}
