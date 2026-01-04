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
 * @filesource associations_releve.php
 * @package controllers
 * Controleur de gestion des associations de relevés avec les comptes GVV.
 */
include('./application/libraries/Gvv_Controller.php');
class Associations_releve extends Gvv_Controller {

    // Tout le travail est fait par le parent
    protected $controller = 'associations_releve';
    protected $model = 'associations_releve_model';
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
            unset($processed_data['id_compte_gvv']);
        }
        return $processed_data;
    }

    /**
     * Create an association and return a json status
     */
    public function associate () {
            $string_releve = $this->input->get('string_releve');
            $type = $this->input->get('type');
            $cptGVV = $this->input->get('cptGVV');

            gvv_debug("associate (string_releve=$string_releve, type=$type, gvv=$cptGVV)");

            $data = array(
                'string_releve' => $string_releve,
                'type' => $type,
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