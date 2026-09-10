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
class Terrains extends Gvv_Controller {

    // Tout le travail est fait par le parent
    protected $controller = 'terrains';
    protected $back_dashboard = 'welcome/section/admin_club';
    protected $model = 'terrains_model';
    protected $modification_level = 'ca'; // Legacy authorization for non-migrated users
    protected $rules = array ();


    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();

        // La création rapide d'un aérodrome depuis le formulaire de saisie de
        // vol (ajax_create) est ouverte aux planchistes, en plus des rôles qui
        // administrent la table terrains. Le CRUD complet (liste, édition,
        // suppression) reste réservé à ca / bureau / trésorier.
        if ($this->router->fetch_method() === 'ajax_create') {
            $this->require_roles(['planchiste', 'ca', 'bureau', 'tresorier']);
        } else {
            $this->require_roles(['ca', 'bureau', 'tresorier']);
        }
    }

    /**
     * Création d'un aérodrome via une requête AJAX, depuis le bouton "+"
     * des sélecteurs d'aérodrome des formulaires de saisie de vol
     * (vols_avion/create, vols_planeur/create).
     *
     * Entrée  : POST oaci, nom, freq1, freq2, comment
     * Sortie  : JSON
     *   succès : { "success": true, "oaci": "...", "label": "OACI Nom" }
     *   erreur : { "success": false, "errors": { "champ": "message", ... } }  (HTTP 422)
     */
    function ajax_create() {
        $this->lang->load('terrains');
        $this->output->set_content_type('application/json');

        $oaci    = strtoupper(trim((string) $this->input->post('oaci')));
        $nom     = trim((string) $this->input->post('nom'));
        $freq1   = trim((string) $this->input->post('freq1'));
        $freq2   = trim((string) $this->input->post('freq2'));
        $comment = trim((string) $this->input->post('comment'));

        $errors = array();

        if ($oaci === '') {
            $errors['oaci'] = $this->lang->line('gvv_terrains_ajax_error_oaci_required');
        } elseif (mb_strlen($oaci) > 10) {
            $errors['oaci'] = $this->lang->line('gvv_terrains_ajax_error_oaci_too_long');
        } elseif (!empty($this->gvv_model->get_by_id('oaci', $oaci))) {
            $errors['oaci'] = $this->lang->line('gvv_terrains_ajax_error_oaci_exists');
        }

        if ($nom === '') {
            $errors['nom'] = $this->lang->line('gvv_terrains_ajax_error_nom_required');
        }

        foreach (array('freq1' => $freq1, 'freq2' => $freq2) as $champ => $valeur) {
            if ($valeur !== '' && !is_numeric(str_replace(',', '.', $valeur))) {
                $errors[$champ] = $this->lang->line('gvv_terrains_ajax_error_freq_invalid');
            }
        }

        if (!empty($errors)) {
            $this->output->set_status_header(422, 'Unprocessable Entity');
            $this->output->set_output(json_encode(array('success' => false, 'errors' => $errors)));
            return;
        }

        $data = array(
            'oaci'    => $oaci,
            'nom'     => $nom,
            'freq1'   => ($freq1 === '') ? 0 : floatval(str_replace(',', '.', $freq1)),
            'freq2'   => ($freq2 === '') ? 0 : floatval(str_replace(',', '.', $freq2)),
            'comment' => $comment,
        );

        $id = $this->gvv_model->create($data);
        $code = $this->db->_error_number();

        if (!$id) {
            if ($code == 1062) {
                $this->output->set_status_header(422, 'Unprocessable Entity');
                $this->output->set_output(json_encode(array(
                    'success' => false,
                    'errors' => array('oaci' => $this->lang->line('gvv_terrains_ajax_error_oaci_exists')),
                )));
                return;
            }
            $this->output->set_status_header(500, 'Internal Server Error');
            $this->output->set_output(json_encode(array(
                'success' => false,
                'errors' => array('oaci' => $this->lang->line('gvv_terrains_ajax_error_save')),
            )));
            return;
        }

        gvv_info("Aérodrome créé via la saisie de vol: $oaci ($nom) par " . $this->dx_auth->get_username());

        $this->output->set_output(json_encode(array(
            'success' => true,
            'oaci'    => $oaci,
            'label'   => $this->gvv_model->image($oaci),
        )));
    }

}