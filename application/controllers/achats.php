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
 * @filesource achats.php
 * @package controllers
 * Controleur des achats / CRUD
 *
 * Les achats peuvent être générés par les vols ou à traves la vue
 * des comptes pilotes.
 */

/**
 * Include parent library
 */
include('./application/libraries/Gvv_Controller.php');

/**
 * Controleur de gestion des achats
 */
class Achats extends Gvv_Controller {
    protected $controller = 'achats';
    protected $model = 'achats_model';
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
        $this->lang->load('achats');
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
     * Affiche le formulaire de création
     *
     * @deprecated
     *
     */
    function create() {

        // initialise les valeurs par défaut
        foreach ($this->fields as $field => $value) {
            $this->data[$field] = (array_key_exists('default', $value)) ? $value['default'] : '';
        }
        $this->form_static_element(CREATION);
        if (func_num_args() > 0) {
            $this->data['pilote'] = func_get_arg(0);
        }


        /**
         * Reverse logic. An amount is fetched from HEVA. A product has a price
         * so the quantity is the free variable
         * http://localhost/gvv_dev/index.php/achats/create?amount=80.01&pilot=vpeignot
         */
        $amount = $this->input->get('amount');

        $produit = $this->config->item('ffvv_product');
        $tarif_info = $this->tarifs_model->get_by_id('reference', $produit);
        $price = $tarif_info['prix'];

        $this->data['id'] = '';
        $this->data['date'] = $this->input->get('date');
        $this->data['produit'] = $produit;
        $this->data['pilote'] = $this->input->get('pilot');
        $this->data['quantite'] = $amount / $price;
        $this->data['description'] = $this->input->get('description');
        $this->data['num_cheque'] = $this->input->get('num_cheque');

        return load_last_view($this->form_view, $this->data, $this->unit_test);
    }

    /**
     * Modifie un élèment
     *
     * @param $id integer
     *            identifiant à modifier
     */
    function edit($id = "", $load_view = TRUE, $action = MODIFICATION) {
        $action = (count($this->ecritures_model->select_frozen_lines($id))) ? VISUALISATION : MODIFICATION;
        parent::edit($id, FALSE, $action);
        $this->data['date'] = date_db2ht($this->data['date']);

        if (isset($this->data['vol_avion'])) {
            // L'achat a été généré par un vol, c'est le vol qu'il faut éditer
            $vol = $this->data['vol_avion'];
            if ($vol != 0) {
                redirect("vols_avion/edit/" . $vol);
                return;
            }
        } else if (isset($this->data['vol_planeur'])) {
            // L'achat a été généré par un vol, c'est le vol qu'il faut éditer
            $vol = $this->data['vol_planeur'];
            if ($vol != 0) {
                redirect("vols_planeur/edit/" . $vol);
                return;
            }
        } else if (isset($this->data['mvt_pompe'])) {
            // L'achat a été généré par un mvt de pompe
            $mvt = $this->data['mvt_pompe'];
            if ($mvt != 0) {
                redirect("pompes/edit/" . $mvt);
                return;
            }
        }

        // affiche le formulaire
        load_last_view('achats/formView', $this->data);
    }

    /**
     * Hook called after element creation
     *
     * @param array $data
     *            tableau des champs de l'enregistrement
     */
    function post_create($data = array()) {
        $pilote = $data['pilote'];
        $produit = $data['produit'];
        $quantite = $data['quantite'];
        $date = $data['date'];
        $achat = $data['id'];
        $saisie_par = $data['saisie_par'];
        $desc = $data['description'];
        $club = $data['club'];

        $tarif_info = $this->tarifs_model->get_by_id('reference', $produit);

        if ($tarif_info['nb_tickets'] && ($tarif_info['nb_tickets'] > 0.000001)) {

            $this->load->model('tickets_model');
            // Prend en compte les remorqués
            $this->tickets_model->create(array(
                'date' => $date,
                'pilote' => $pilote,
                'achat' => $achat,
                'quantite' => $quantite * $tarif_info['nb_tickets'],
                'description' => "Achat " . $desc,
                'saisie_par' => $saisie_par,
                'club' => $club,
                'type' => $tarif_info['type_ticket']
            ));
        }

        $this->load->model('comptes_model');
        $compte_pilote = $this->comptes_model->compte_pilote($pilote);
        // redirect("compta/view/" . $compte_pilote);
    }

    /**
     * Hook called before delete
     *
     * @param $id integer
     *            identifiant à modifier
     */
    function pre_delete($id) {
    }

    /**
     * Hook called before element update
     *
     * @param $id integer
     *            identifiant à modifier
     * @param array $data
     *            tableau des champs de l'enregistrement
     */
    function pre_update($id, $data = array()) {
        // cancel previous action
        $previous = $this->gvv_model->get_by_id('id', $data[$id]);

        // todo: Il faut détruire la lignes de ticket qui référencent cet achat
        // Detruit les tickets correspondant
        $this->load->model('tickets_model');
        $this->tickets_model->delete(array(
            'achat' => $previous['id']
        ));
    }

    /**
     * Hook called after element update
     *
     * @param array $data
     *            tableau des champs de l'enregistrement
     */
    function post_update($data = array()) {
        $this->post_create($data);
    }

    /**
     *
     * Supprime un élèment
     *
     * @param $id integer
     *            identifiant à supprimer
     */
    function delete($id) {
        // détruit en base
        $current = $this->gvv_model->get_by_id('id', $id);

        if (isset($current['vol_avion'])) {
            $vol = $current['vol_avion'];

            // L'achat a été généré par un vol, c'est le vol qu'il faut détruire
            if ($vol != 0) {
                redirect("vols_avion/delete/" . $vol);
                return;
            }
        } else if (isset($current['vol_planeur'])) {
            $vol = $current['vol_planeur'];

            // L'achat a été généré par un vol, c'est le vol qu'il faut détruire
            if ($vol != 0) {
                redirect("vols_planeur/delete/" . $vol);
                return;
            }
        } else if (isset($current['mvt_pompe'])) {
            $mvt = $current['mvt_pompe'];

            // L'achat a été généré par un mouvement de pompe
            if ($mvt != 0) {
                redirect("pompes/delete/" . $mvt);
                return;
            }
        }

        $this->load->model('comptes_model');
        $compte_pilote = $this->comptes_model->compte_pilote($current['pilote']);

        if (count($this->ecritures_model->select_frozen_lines($id))) {
            // Il y a des lignes gelées la suppression est interdite
            $this->session->set_flashdata('popup', "Suppression interdite, écriture vérouillée par le comptable.");
        } else {

            // Detruit les tickets correspondant
            $this->load->model('tickets_model');
            $this->tickets_model->delete(array(
                'achat' => $id
            ));

            $this->pre_delete($id);
            $this->gvv_model->delete(array(
                $this->kid => $id
            ));
        }
        redirect("compta/view/" . $compte_pilote);
    }

    /**
     * Liste les ventes de produits par an.
     */
    function list_per_year() {
        $this->push_return_url("liste ventes par an");

        $data['year'] = $this->session->userdata('year');
        $data['year_selector'] = $this->ecritures_model->getYearSelector("date_op");
        $data['controller'] = $this->controller;
        $this->gvv_model->list_per_year($data['year']);
        load_last_view('achats/TablePerYear', $data);
    }

    /**
     * Export au format CSV ou PDF
     */
    function ventes_csv($year) {
        $this->gvv_model->list_per_year($year);
        $attrs = array(
            'numbered' => 1,
            'fields' => array(
                'produit',
                'prix_unit',
                'quantite',
                'prix'
            )
        );
        $this->gvvmetadata->csv("vue_achats_per_year", $attrs);
    }

    /*
     * Fonction de migration des données. Usage unique.
     *
     */
    function cleanup() {
        echo "cleanup" . br();
        $achats = $this->gvv_model->select_raw();
        $pattern = '/(.*)(, reste=(\d+))(.*)/';
        foreach ($achats as $key => $row) {
            $description = $row['description'];
            if (preg_match($pattern, $description, $matches)) {
                $avant = $matches[1];
                $reste = $matches[2];
                $apres = $matches[4];
                echo $avant . '---|' . $reste . '|---' . $apres . br();
                $replace = $matches[1] . $matches[4];
                $row['description'] = $replace;
                // echo "replace=$replace" . br();

                $this->db->where('id', $row['id']);
                $this->db->update('achats', $row);
            }
        }
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
