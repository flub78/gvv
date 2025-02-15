<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * GVV Gestion vol à voile
 * Copyright (C) 2011 Philippe Boissel & Frédéric Peignot
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @filesource avion.php
 * @package controllers
 *          Controleur de gestion des avions.
 */
// set_include_path(getcwd() . "/..:" . get_include_path());

// include_once ('application/libraries/Gvv_Controller.php');
include_once(APPPATH . '/libraries/Gvv_Controller.php');
// include_once (APPPATH . '/libraries/My_Controller.php');
class Avion extends Gvv_Controller {

    // Tout le travail est fait par le parent
    protected $controller = 'avion';
    protected $model = 'avions_model';
    protected $modification_level = 'ca';
    protected $rules = array(
        'macimmat' => "strtoupper",
        'club' => "callback_section_selected"
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
        $this->lang->load('avion');
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::form_static_element()
     */
    function form_static_element($action) {
        parent::form_static_element($action);
        $this->gvvmetadata->set_selector('produit_selector', $this->tarifs_model->selector(array(), "asc", 'nom'));
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
        $this->data['section'] = $this->gvv_model->section();

        $selection = $this->selection();
        parent::page($premier, $message, $selection);

        $this->form_static_element(MODIFICATION);
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
                $selection .= "(maprive = \"$categorie\" )";
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
}
