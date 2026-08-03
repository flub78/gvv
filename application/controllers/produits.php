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
