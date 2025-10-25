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
 * Controleur de gestion des avions.
 */
include ('./application/libraries/Gvv_Controller.php');
class Licences extends Gvv_Controller {

    // Tout le travail est fait par le parent
    protected $controller = 'licences';
    protected $model = 'licences_model';
    protected $modification_level = 'ca'; // Legacy authorization for non-migrated users
    protected $rules = array ();


    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();

        // Authorization: Code-based (v2.0) - only for migrated users
        if ($this->use_new_auth) {
            $this->require_roles(['ca']);
        }
    }

    /**
     * Licences par année
     */
    public function per_year() {
        $this->push_return_url("Tarifs");

        $data ['controller'] = $this->controller;
        $data ['year'] = $this->session->userdata('year');
        $data ['year_selector'] = $this->gvv_model->getYearSelector("date");
        $data ['type'] = $this->session->userdata('licence_type');

        $data ['table'] = $this->gvv_model->per_year($data ['type']);
        load_last_view('licences/TablePerYear', $data);
    }

    /**
     * Active la licence pour le pilote et pour l'année
     *
     * @param unknown_type $pilote
     * @param unknown_type $year
     * @param unknown_type $type
     */
    public function set($pilote, $year, $type = 0) {
        $row = array (
                'pilote' => $pilote,
                'year' => $year,
                'type' => $type,
                'date' => "$year-01-01"
        );
        $this->gvv_model->create($row);
        $this->per_year();
    }

    /**
     * Desactive la licence pour le pilote et pour l'année
     *
     * @param unknown_type $pilote
     * @param unknown_type $year
     * @param unknown_type $type
     */
    public function switch_it($pilote, $year, $type = 0) {
        $this->gvv_model->delete(array (
                'pilote' => $pilote,
                'year' => $year,
                'type' => $type
        ));
        $this->per_year();
    }

    /**
     * Active le type de licence par défaut
     *
     * @param unknown_type $type
     */
    public function switch_to($type) {
        $this->session->set_userdata('licence_type', $type);
        redirect(controller_url("licences/per_year"));
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