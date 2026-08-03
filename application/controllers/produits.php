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
 * File: produits.php
 * Contrôleur de gestion des produits (identité — voir tarifs.php pour
 * l'historique de prix d'un produit).
 */
include('./application/libraries/Gvv_Controller.php');
class Produits extends Gvv_Controller {
    protected $controller = 'produits';
    protected $back_dashboard = 'welcome/section/treasurer';
    protected $model = 'produits_model';
    protected $rules = array();

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();

        $this->require_roles(['user']);

        $this->lang->load('produits');
        $this->load->model('comptes_model');
        $this->load->model('types_ticket_model');
    }

    /**
     * Génération des éléments à passer au formulaire en cas de création,
     * modification ou réaffichage après erreur.
     */
    function form_static_element($action) {
        parent::form_static_element($action);
        $where = array(
            "codec >=" => "7",
            'codec <' => "8"
        );
        $this->gvvmetadata->set_selector('compte_selector', $this->comptes_model->selector($where, "asc", TRUE));
        $this->gvvmetadata->set_selector('ticket_selector', $this->types_ticket_model->selector_with_null());
    }

    /**
     * Affiche une page de produits.
     *
     * Surcharge la version générique pour exposer `section` à la vue (le mode
     * rw/ro du tableau dépend d'une section active, comme l'ancien contrôleur
     * tarifs.php).
     */
    function page($premier = 0, $message = '', $selection = array()) {
        $this->data['section'] = $this->gvv_model->section();
        return parent::page($premier, $message, $selection);
    }

    /**
     * Ouvre la liste des tarifs (historique de prix) du produit.
     */
    function tarifs($id) {
        redirect(controller_url('tarifs') . '/page/' . $id);
    }

    /**
     * Formulaire de création — ajoute la liste (vide) des tarifs pour le
     * panneau intégré du formulaire (cf. bs_formView.php).
     */
    function create() {
        parent::create(true);
        $this->data['tarifs'] = array();
        return load_last_view($this->form_view, $this->data, $this->unit_test);
    }

    /**
     * Formulaire de modification — ajoute les tarifs existants du produit
     * pour le panneau intégré du formulaire.
     */
    function edit($id = "", $load_view = TRUE, $action = MODIFICATION) {
        parent::edit($id, false, $action);
        $this->load->model('tarifs_model');
        $this->data['tarifs'] = $this->tarifs_model->all_for_produit($id);
        if ($load_view) {
            return load_last_view($this->form_view, $this->data, $this->unit_test);
        }
    }

    /**
     * Ajoute la règle "au moins un tarif" avant de déléguer à la validation
     * générique (création ou modification).
     *
     * `tarifs.produit_id` est NOT NULL sans cascade (migrations 147/148) : un
     * tarif ne peut jamais être inséré avant que le produit existe. On ne
     * persiste donc jamais un produit sans tarif, même transitoirement — la
     * validation bloque avant toute écriture en base, et post_create()/
     * post_update() synchronisent les tarifs dans la même requête.
     */
    function formValidation($action, $return_on_success = false) {
        $this->form_validation->set_rules('tarifs_json', 'Tarifs', 'callback_at_least_one_tarif');
        return parent::formValidation($action, $return_on_success);
    }

    /**
     * Callback de validation CodeIgniter (nom exposé requis: at_least_one_tarif,
     * appelé via la règle "callback_at_least_one_tarif").
     */
    function at_least_one_tarif($json) {
        $rows = json_decode((string) $json, true);
        if (!is_array($rows) || count($rows) < 1) {
            $this->form_validation->set_message('at_least_one_tarif', $this->lang->line('gvv_produits_tarif_requis'));
            return false;
        }
        foreach ($rows as $row) {
            if (!isset($row['date']) || !isset($row['prix']) || trim((string) $row['date']) === '' || trim((string) $row['prix']) === '') {
                $this->form_validation->set_message('at_least_one_tarif', $this->lang->line('gvv_produits_tarif_invalide'));
                return false;
            }
        }
        return true;
    }

    /**
     * Synchronise les tarifs après création du produit (cf. formValidation()).
     */
    function post_create($data = array()) {
        parent::post_create($data);
        $this->_sync_tarifs($data[$this->kid]);
    }

    /**
     * Synchronise les tarifs après modification du produit (cf. formValidation()).
     */
    function post_update($data = array()) {
        parent::post_update($data);
        $this->_sync_tarifs($data[$this->kid]);
    }

    /**
     * Applique l'état "brouillon" des tarifs (champ caché tarifs_json, géré
     * par assets/js/produits_tarifs.js) au produit qui vient d'être créé ou
     * modifié : create() pour les lignes sans id, update() pour celles avec
     * id, delete() pour les tarifs existants en base mais absents du POST.
     */
    private function _sync_tarifs($produit_id) {
        $this->load->model('tarifs_model');
        $this->load->helper('validation');

        $rows = json_decode((string) $this->input->post('tarifs_json'), true);
        if (!is_array($rows)) {
            $rows = array();
        }

        $submitted_ids = array();
        foreach ($rows as $row) {
            $data = array(
                'produit_id' => $produit_id,
                'date' => $row['date'],
                'prix' => clean_currency_input($row['prix']),
                'nb_tickets' => (isset($row['nb_tickets']) && $row['nb_tickets'] !== '')
                    ? clean_currency_input($row['nb_tickets']) : 0,
            );
            if (!empty($row['id'])) {
                $this->tarifs_model->update('id', $data, $row['id']);
                $submitted_ids[] = (int) $row['id'];
            } else {
                $new_id = $this->tarifs_model->create($data);
                $submitted_ids[] = (int) $new_id;
            }
        }

        $existing = $this->tarifs_model->all_for_produit($produit_id);
        foreach ($existing as $tarif) {
            if (!in_array((int) $tarif['id'], $submitted_ids, true)) {
                $this->tarifs_model->delete(array('id' => $tarif['id']));
            }
        }
    }

    /**
     * Supprime un produit.
     *
     * `tarifs.produit_id` porte une contrainte FOREIGN KEY (fk_tarifs_produit,
     * migration 147) sans ON DELETE CASCADE : MySQL refuse la suppression
     * d'un produit tant qu'il a des tarifs. Sans ce contrôle, la suppression
     * échouerait silencieusement (db_debug=FALSE en configuration, cf.
     * application/config/database.php) — Gvv_Controller::delete() ignore le
     * retour de Common_Model::delete() et redirige comme si l'opération avait
     * réussi. Contrôle explicite ici, même principe que Comptes::delete().
     */
    function delete($id) {
        if (!$this->ensure_modification_rights(MODIFICATION)) {
            return;
        }

        $this->load->model('tarifs_model');
        $count = $this->tarifs_model->count(array('produit_id' => $id));
        if ($count) {
            $this->session->set_flashdata('popup',
                sprintf($this->lang->line('gvv_produits_delete_has_tarifs'), $count));
            redirect($this->controller . "/page");
            return;
        }

        $this->pre_delete($id);
        $this->gvv_model->delete(array(
            $this->kid => $id
        ));
        $this->pop_return_url();
        redirect($this->controller . "/page");
    }
}
