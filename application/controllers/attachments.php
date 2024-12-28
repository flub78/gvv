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
 * @filesource attachments.php
 * @package controllers
 * Controleur des attachments / CRUD
 *
 * Les attachments peuvent être générés par les vols ou à traves la vue
 * des comptes pilotes.
 */

/**
 * Include parent library
 */
include('./application/libraries/Gvv_Controller.php');

/**
 * Controleur de gestion des attachments
 */
class Attachments extends Gvv_Controller {
    protected $controller = 'attachments';
    protected $model = 'attachments_model';
    protected $rules = array();

    /**
     * Constructor
     *
     * Affiche header et menu
     */
    function __construct() {
        parent::__construct();
        $this->load->model('membres_model');
        $this->load->model('tarifs_model');
        $this->load->model('ecritures_model');
        $this->lang->load('attachments');
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

        $this->gvvmetadata->set_selector('produit_selector', $this->tarifs_model->selector());
        $this->gvvmetadata->set_selector('pilote_selector', $this->membres_model->selector());

        $this->data['saisie_par'] = $this->dx_auth->get_username();
    }

    /**
     * Test unitaire
     */
    function test($format = "html") {
        $this->unit_test = TRUE;
        $this->load->library('unit_test');

        $this->unit->run(true, true, "Tests $this->controller");
        $this->tests_results($format);
    }

    public function message($to = 'World') {
        if ($this->input->is_cli_request()) {
            $msg = "CLI request";
        } else {
            $msg = "HTTP request";
        }
        gvv_debug("Hello {$msg} {$to}!" . PHP_EOL);
        echo "Hello {$msg} {$to}! " . PHP_EOL;
    }
}
