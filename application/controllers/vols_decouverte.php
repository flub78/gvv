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
 * @filesource vols_decouverte.php
 * @package controllers
 * Contrôleur de gestion des avions.
 */
include('./application/libraries/Gvv_Controller.php');
class Vols_decouverte extends Gvv_Controller {

    // Tout le travail est fait par le parent
    protected $controller = 'vols_decouverte';
    protected $model = 'vols_decouverte_model';
    protected $modification_level = 'ca';
    protected $rules = array();


    /**
     * Constructeur
     */
    function __construct() {
        parent::__construct();

        $this->load->helper('crypto');
    }

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

        $pilote_selector = $this->membres_model->selector_with_null(['actif' => 1]);
        $this->gvvmetadata->set_selector('pilote_selector', $pilote_selector);
    }


    /**
     * Affiche les différentes action possibles sur un vol de découverte
     */
    function action($id) {
        $unobfuscated_id = reverseTransform($id);

        // echo "action = $id";

        $vd = $this->gvv_model->get_by_id($this->kid, $unobfuscated_id);

        if (!count($vd)) {
            $data = [];
            $data['msg'] = "Le vol de découverte $id n'existe pas";

            load_last_view('error', $data);

            return;
        }

        var_dump($vd);
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

    function qr() {

        $originalNumber = 12345;
        $transformed = transformInteger($originalNumber);
        $recovered = reverseTransform($transformed);

        echo "QR:";
        echo "Nombre original: " . $originalNumber . "\n";
        echo "Nombre transformé: " . $transformed . "\n";
        echo "Nombre récupéré: " . $recovered . "\n";

        // Test avec quelques autres valeurs
        $testValues = [0, 1, 42, 99999, 1000000];
        foreach ($testValues as $value) {
            $transformed = transformInteger($value);
            $recovered = reverseTransform($transformed);
            echo "<br> Test avec $value: transformé = $transformed, récupéré = $recovered\n";
        }
    }
}
