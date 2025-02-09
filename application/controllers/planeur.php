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
 * controleur de gestion des planeurs.
 *
 * @filesource planeur.php
 * @package controllers
 */
include('./application/libraries/Gvv_Controller.php');

/**
 *
 * Controleur des planeurs
 *
 * @author Frédéric
 *
 */
class Planeur extends Gvv_Controller {
    protected $controller = 'planeur';
    protected $model = 'planeurs_model';
    protected $kid = 'mpimmat';
    protected $modification_level = 'ca';
    protected $rules = array(
        'mpimmat' => "strtoupper|alpha_dash",
        'mpnumc' => "strtoupper|alpha_dash"
    );
    protected $filter_variables = array(
        'filter_active',
        'filter_machine_actif',
        'filter_proprio'
    );

    /**
     * Constructeur
     */
    function __construct() {
        parent::__construct();
        $this->load->model('tarifs_model');
        $this->load->model('membres_model');
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::form_static_element()
     */
    function form_static_element($action) {
        parent::form_static_element($action);
        $this->data['machine_selector'] = $this->gvv_model->selector();
        $pilote_selector = $this->membres_model->selector_with_null(array());

        $this->gvvmetadata->set_selector('owner_selector', $pilote_selector);
        $this->gvvmetadata->set_selector('produit_selector', $this->tarifs_model->selector());
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
        $this->data['action'] = VISUALISATION;
        $this->load_filter($this->filter_variables);

        $selection = $this->selection();
        parent::page($premier, $message, $selection);
    }

    /**
     * Active ou désactive le filtrage
     */
    public function filterValidation() {
        $this->active_filter($this->filter_variables);

        // Il faut rediriger et non pas appeller $this->page, sinon l'URL
        // enregistrée pour le retour est incorrecte
        redirect($this->controller . '/page');
    }

    /**
     * Retourne la selection format ActiveData utilisable par les requêtes
     * SQL pour filtrer les données en fonction des choix faits par l'utilisateur
     * dans la section de filtrage.
     */
    function selection() {
        $this->data['filter_active'] = $this->session->userdata('filter_active');

        $selection = "";
        if ($this->session->userdata('filter_active')) {

            $filter_machine_active = $this->session->userdata('filter_machine_actif');
            if ($filter_machine_active) {
                $filter_machine_active--;
                $selection .= "(actif = \"$filter_machine_active\" )";
            }

            $filter_categorie = $this->session->userdata('filter_proprio');
            if ($filter_categorie) {
                $categorie = $filter_categorie - 1;
                if ($selection) {
                    $selection .= " and ";
                }
                $selection .= "(mpprive = \"$categorie\" )";
            }
        }

        if ($selection == "")
            $selection = array();

        return $selection;
    }

    /**
     * (non-PHPdoc)
     *
     * @see My_Controller::create()
     */
    function create() {
        if (! $this->dx_auth->is_role('ca')) {
            $this->dx_auth->deny_access();
        }
        parent::create();
    }

    /**
     * Test unitaire
     * cd /home/frederic/workspace/gvv2
     * export TEST=1
     * php index.php planeur/test
     */
    function test($format = "html") {
        parent::test($format);
        $this->unit->run('Foo', 'is_string', 'test planeur');
        if ($format == "xml") {
            $this->unit->XML_result("results/test_planeur.xml", "Test planeur");
        } else {
            echo $this->unit->report();
        }
        $this->unit->save_coverage();
    }
}
