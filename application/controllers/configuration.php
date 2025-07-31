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
include('./application/libraries/Gvv_Controller.php');
class Configuration extends Gvv_Controller {

    // Tout le travail est fait par le parent
    protected $controller = 'configuration';
    protected $model = 'configuration_model';
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

        $this->load->model('sections_model');
        $section_selector = $this->sections_model->selector_with_null();
        $this->gvvmetadata->set_selector('section_selector', $section_selector);
    }


    /**
     * Test description
     * 
     * count the number of configuration
     * create one with club defined in french
     * create one with section not define in french (same key)
     * create one with section defined in english (same key)
     * check that there are three more in database
     * check it is possible to retrieve the first, second and third data
     * check that fetching an unknown key returns null
     * delete the test data 
     * check that we are back to the initial number of configuration
     * 
     */
    public function test_model() {

        $this->unit->run(true, true, "Testing $this->controller model");

        $data1 = [
            'cle' => 'test_cle',
            'valeur' => 'test_valeur',
            'lang' => 'french',
            'club' => '1',
            'categorie' => 'test_categorie',
            'description' => 'French with section defined'
        ];

        $data2 = [
            'cle' => 'cle2',
            'valeur' => 'test_valeur2',
            'lang' => 'french',
            'club' => null,
            'categorie' => 'test_categorie',
            'description' => 'French with no section defined'
        ];

        $data3 = [
            'cle' => 'test_cle',
            'valeur' => 'test_value',
            'lang' => 'english',
            'club' => '1',
            'categorie' => 'test_category',
            'description' => 'English with section defined'
        ];

        $count = $this->gvv_model->count();

        // Create test records
        $id1 = $this->gvv_model->create($data1);
        $id2 = $this->gvv_model->create($data2);
        $id3 = $this->gvv_model->create($data3);

        // Verify count increased by 3
        $this->unit->run($this->gvv_model->count(), $count + 3, "Count increased by 3 after creation");

        // Verify retrieval
        $value1 = $this->gvv_model->get_param($data1['cle']);
        $this->unit->run($value1, $data1['valeur'], "Retrieved value 1 matches");

        $value2 = $this->gvv_model->get_param($data2['cle']);
        $this->unit->run($value2, $data2['valeur'], "Retrieved value 2 matches");

        $value3 = $this->gvv_model->get_param($data3['cle'], $data3['lang']);
        $this->unit->run($value3, $data3['valeur'], "Retrieved value 3 matches");

        // Check unknown key returns null
        $unknown_value = $this->gvv_model->get_param('unknown_key');
        $this->unit->run($unknown_value, null, "Unknown key returns null");
        
        // Delete test data
        $this->gvv_model->delete(['id' => $id1]);
        $this->gvv_model->delete(['id' => $id2]);
        $this->gvv_model->delete(['id' => $id3]);

        // Verify back to initial count
        $this->unit->run($this->gvv_model->count(), $count, "Count back to initial after delete");
    }

    /**
     * Test unitaire
     */
    function test($format = "html") {
        // parent::test($format);
        $this->unit_test = TRUE;
        $this->load->library('unit_test');

        $this->unit->run(true, true, "Testing $this->controller controller");
        $this->test_model();
        $this->tests_results($format);
    }
}
