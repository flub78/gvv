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
 * File: tarifs.php
 * controleur de gestion des tarifs.
 */
include('./application/libraries/Gvv_Controller.php');
class Tarifs extends Gvv_Controller {
    protected $controller = 'tarifs';
    protected $model = 'tarifs_model';
    protected $rules = array();

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();

        // Authorization: Code-based (v2.0) - only for migrated users
        // page/view accessible to all users, create/edit/delete requires ca (via modification_level)
        if ($this->use_new_auth) {
            $this->require_roles(['user']);
        }
        
        $this->load->model('comptes_model');
        $this->load->model('types_ticket_model');
    }

    /**
     * Génération des éléments à passer au formulaire en cas de création,
     * modification ou réaffichage après erreur.
     */
    function form_static_element($action) {
        parent::form_static_element($action);
        $this->data['tarif_selector'] = $this->gvv_model->selector();
        $this->data['saisie_par'] = $this->dx_auth->get_username();
        $where = array(
            "codec >=" => "7",
            'codec <' => "8"
        );
        $this->gvvmetadata->set_selector('compte_selector', $this->comptes_model->selector($where, "asc", TRUE));

        $this->gvvmetadata->set_selector('ticket_selector', $this->types_ticket_model->selector_with_null());
    }

    /**
     * Active ou désactive le filtrage des tarifs
     */
    function filterValidation() {
        $button = $this->input->post('button');
        if ($button == "Afficher tout") {
            gvv_debug("filterValidation tout");
            $session['filter_tarif_tout'] = true;
            $session['filter_tarif_date'] = '';
            $session['filter_tarif_public'] = 0;
            $this->session->set_userdata($session);
        } else {
            $session['filter_tarif_tout'] = false;
            $session['filter_tarif_date'] = $this->input->post('filter_tarif_date');
            $session['filter_tarif_public'] = $this->input->post('filter_tarif_public');
            $this->session->set_userdata($session);
            gvv_debug("filterValidation selection " . $session['filter_tarif_date'] . ", public=" . $session['filter_tarif_public']);
        }
        redirect($this->controller . '/page');
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
        $this->data['select_result'] = $this->gvv_model->select_page(PER_PAGE, $premier);
        $this->data['kid'] = $this->kid;
        $this->data['controller'] = $this->controller;
        $this->data['count'] = $this->gvv_model->count();
        $this->data['premier'] = $premier;
        $this->data['message'] = $message;
        $this->data['has_modification_rights'] = (! isset($this->modification_level) || $this->dx_auth->is_role($this->modification_level, true, true));
        $this->data['section'] = $this->gvv_model->section();


        if ($this->session->userdata('filter_tarif_tout')) {
            $this->data['filter_tarif_date'] = "";
            $this->data['filter_tarif_public'] = 0;
        } else {
            if ($this->session->userdata('filter_tarif_date')) {
                $this->data['filter_tarif_date'] = $this->session->userdata('filter_tarif_date');
            } else {
                $this->data['filter_tarif_date'] = "";
            }
            $this->data['filter_tarif_public'] = $this->session->userdata('filter_tarif_public');
        }

        $this->push_return_url("Tarifs");

        return load_last_view($this->table_view, $this->data, $this->unit_test);
    }

    /**
     * Duplique un tarif à la date courante
     */
    function clone_elt($id) {
        $data = $this->gvv_model->get_by_id('id', $id);
        unset($data['id']);
        $data['date'] = date('Y-m-d');
        $this->gvv_model->create($data);
        redirect(controller_url("tarifs/page"));
    }

}
