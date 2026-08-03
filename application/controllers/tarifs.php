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
 * controleur de gestion des tarifs (historique de prix d'un produit — voir
 * produits.php pour l'identité du produit). Sous-CRUD scoped par produit_id.
 */
include('./application/libraries/Gvv_Controller.php');
class Tarifs extends Gvv_Controller {
    protected $controller = 'tarifs';
    protected $back_dashboard = 'produits/page';
    protected $model = 'tarifs_model';
    protected $rules = array();

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();

        $this->require_roles(['user']);

        $this->load->model('produits_model');
    }

    /**
     * Active ou désactive le filtrage des tarifs affichés pour ce produit
     */
    function filterValidation() {
        $produit_id = $this->input->post('produit_id');
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
        redirect($this->controller . '/page/' . $produit_id);
    }

    /**
     * Affiche les tarifs (historique de prix) d'un produit
     *
     * $produit_id est optionnel uniquement pour rester compatible avec la
     * signature de Gvv_Controller::page() (PHP 8 impose une surcharge
     * compatible) — ce sous-CRUD est toujours appelé avec un produit_id.
     *
     * $selection n'est pas utilisé ici (tarifs.php a son propre mécanisme de
     * filtrage par session, cf. filterValidation()) — le paramètre existe
     * uniquement pour rester compatible avec la signature de
     * Gvv_Controller::page() (PHP 8 refuse une surcharge avec moins de
     * paramètres que le parent).
     *
     * @param $premier élément
     *            à afficher
     * @param
     *            message message à afficher
     */
    function page($premier = 0, $message = '', $selection = array()) {
        $this->data['select_result'] = $this->gvv_model->select_page(PER_PAGE, $premier);
        $this->data['kid'] = $this->kid;
        $this->data['controller'] = $this->controller;
        $this->data['count'] = $this->gvv_model->count(array('produit_id' => $produit_id));
        $this->data['premier'] = $premier;
        $this->data['message'] = $message;
        $this->data['has_modification_rights'] = $this->has_modification_rights();
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

        // Fil d'ariane retour vers la liste des produits
        $this->set_nav_back('produits/page', 'db_btn_retour_liste');

        $this->push_return_url("Tarifs");

        return load_last_view($this->table_view, $this->data, $this->unit_test);
    }

    /**
     * Duplique un tarif à la date courante
     */
    function clone_elt($id) {
        $data = $this->gvv_model->get_by_id('id', $id);
        $produit_id = $data['produit_id'];
        unset($data['id'], $data['created_at'], $data['created_by'], $data['updated_at'], $data['updated_by']);
        $data['date'] = date('Y-m-d');
        $this->gvv_model->create($data);
        redirect(controller_url("tarifs/page/" . $produit_id));
    }

}
