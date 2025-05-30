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
 * Controleur de facturation
 *
 * La facturation est une opération cachée activée par la saisie des vols
 * ou des pleins. Pour l'instant ce controleur ne contient que les méthodes relatives à
 * la configuration. Si l'on gère des profils de facturation plus tard ils seront gérés ici.
 */
include('./application/libraries/Gvv_Controller.php');
class Facturation extends Gvv_Controller {
    protected $controller = 'facturation';
    protected $model = 'facturation_model';
    protected $modification_level = 'ca';
    protected $rules = array();
    protected $fields = array(
        'payeur_non_pilote',
        'partage',
        'gestion_pompes',
        'remorque_100eme',
        'date_gel'
    );

    /**
     * Constructeur
     */
    function __construct() {
        parent::__construct();
        $this->config->load('facturation');
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::form_static_element()
     */
    function form_static_element($action) {
        parent::form_static_element($action);
    }


    /**
     * Test facturation
     *
     * Voir classe Facturation pour la documentation
     */
    function test_facturation() {
        // pour les modes de facturation suivants: defaut, accabs, aces

        // reccupère le solde du pilote et payeurs potentiel

        // pour chaque type de vol
        // crée le vol
        // vérifie le solde attendu
        // vérifie lne nombre d'achat attachés au vol
        // détruit le vol
        // vérifie le retour aux conditions initiales
        $this->unit->run(true, true, "test facturation activé");

        // Chargement de la base de données de test
        $this->load->library('Database');
        $filename = getcwd() . '/install/gvv_test.sql';
        $sql = file_get_contents($filename);

        $this->load->library('Database');
        $this->database->drop_all();
        $this->database->sql($sql);
    }

    /**
     * Test unitaire
     */
    function test($format = "html") {
        $this->unit_test = TRUE;

        $this->load->library('unit_test');
        $this->test_facturation();

        $this->tests_results($format);
    }
}
