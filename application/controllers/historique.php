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
 * @filesource historique.php
 * @package controllers
 * Controleur de l'historique des heures.
 *
 * Pour pouvoir commencer les diagrammes d'heures cumulées avant l'utilisation
 * de GVV, la table historique contient les heures par machine des années précédantes.
 *
 */
include ('./application/libraries/Gvv_Controller.php');
class Historique extends Gvv_Controller {

    // Tout le travail est fait par le parent
    protected $controller = 'historique';
    protected $model = 'historique_model';
    protected $modification_level = 'ca';
    protected $rules = array (
            'machine' => "strtoupper"
    );

    /**
     * Constructeur
     */
    function __construct() {
        parent::__construct();
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::form_static_element()
     */
    function form_static_element($action) {
        parent::form_static_element($action);
        $this->load->model('planeurs_model');
        $this->gvvmetadata->set_selector('machine_selector', $this->planeurs_model->selector(array (
                'actif' => 1
        )));
    }

    /**
     * Affiche une page d'éléments
     *
     * @param $premier élément
     *            à afficher
     * @param
     *            message message à afficher
     */
    function page($premier = 0, $message = '') {
        $this->data ['action'] = VISUALISATION;

        parent::page($premier, $message);
    }

}