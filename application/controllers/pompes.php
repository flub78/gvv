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
 * @filesource pompes.php
 * @package controllers
 *
 * controleur de gestion des comptes.
 */
include ('./application/libraries/Gvv_Controller.php');
class Pompes extends Gvv_Controller {
    protected $controller = 'pompes';
    protected $model = 'pompes_model';
    protected $modification_level = 'bureau';

    // régles de validation
    protected $rules = array ();

    /**
     * Constructor
     *
     * Affiche header et menu
     */
    function __construct() {
        parent::__construct();
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::page()
     */
    function page($pompe = 0) {
        $selection = "pnum = $pompe ";
        $this->data ['pnum'] = $pompe;

        $this->data ['action'] = VISUALISATION;
        $this->data ['filter_active'] = $this->session->userdata('filter_active');
        $this->data ['filter_date'] = '';
        $this->data ['date_end'] = '';
        $this->data ['filter_pilote'] = '';
        $this->data ['filter_machine'] = '';
        // $this->data['planchiste'] = $this->dx_auth->is_role('planchiste', true, true);

        $pilote_selector = $this->membres_model->selector_with_null(array ());
        $this->data ['pilote_selector'] = $pilote_selector;

        if ($this->session->userdata('filter_active')) {
            $order = "asc";

            $filter_pilote = $this->session->userdata('filter_pilote');
            if ($filter_pilote) {
                $this->data ['filter_pilote'] = $filter_pilote;
                $selection .= " and (ppilid = \"$filter_pilote\" )";
            }

            $filter_date = $this->session->userdata('filter_date');
            $date_end = $this->session->userdata('date_end');
            if ($filter_date) {
                $selection .= " and ";
                $this->data ['filter_date'] = $filter_date;
                if ($date_end) {
                    $selection .= "pdatemvt >= \"" . date_ht2db($filter_date) . "\" ";
                } else {
                    $selection .= "pdatemvt = \"" . date_ht2db($filter_date) . "\" ";
                }
            }

            if ($date_end) {
                $selection .= " and ";
                $this->data ['date_end'] = $date_end;
                $selection .= "pdatemvt <= \"" . date_ht2db($date_end) . "\" ";
            }
        }

        $result = $this->gvv_model->select_page($selection);

        $this->data ['select_result'] = $result;
        $this->data ['kid'] = $this->kid;
        $this->data ['controller'] = $this->controller;
        $this->data ['count'] = $this->gvv_model->count();
        $this->data ['premier'] = 0;
        $this->data ['message'] = "";
        $this->data ['has_modification_rights'] = (! isset($this->modification_level) || $this->dx_auth->is_role($this->modification_level, true, true));

        $this->data ['totaux'] = $this->gvv_model->select_totaux($selection);

        return load_last_view($this->table_view, $this->data, $this->unit_test);
    }
    function create($pompe = 0) {
        parent::create(TRUE);
        $this->data ['pnum'] = $pompe;

        // et affiche le formulaire
        load_last_view('pompes/formView', $this->data);
    }

    /**
     * Génération des éléments à passer au formulaire en cas de création,
     * modification ou réaffichage après erreur.
     *
     * @param
     *            $actions
     */
    function form_static_element($action) {
        parent::form_static_element($action);
        // @todo supprimer après validation
        $this->CI = & get_instance();
        // $this->CI->config->load('facturation');
        $this->load->model('tarifs_model');

        $this->data ['saisie_par'] = $this->dx_auth->get_username();
        $pil_selector = $this->membres_model->selector_with_null();
        $this->data ['pil_selector'] = $pil_selector;
        /*
         * // récupération des prix des tarif essence extérieurs, basés et ACES
         * $prodexte = $this->CI->config->item('essexte');
         * $product_info = $this->db->where('id', $prodexte)->get('tarifs')->row_array();
         * $pxexte = $product_info['prix'];
         * $prodbase = $this->CI->config->item('essbase');
         * $product_info = $this->db->where('id', $prodbase)->get('tarifs')->row_array();
         * $pxbase = $product_info['prix'];
         * $prodaces = $this->CI->config->item('essaces');
         * $product_info = $this->db->where('id', $prodaces)->get('tarifs')->row_array();
         * $pxaces = $product_info['prix'];
         */
        $today = date("Y-m-j");
        // récupération des prix des tarif essence extérieurs, basés et ACES

        $idpomp = substr(current_url(), - 1);

        if ($idpomp == 0) {
            $prodexte = 'Essence Extérieurs';
            $product_info = $this->tarifs_model->get_tarif($prodexte, $today);
            $pxexte = $product_info ['prix'];

            $prodbase = 'Essence Basés';
            $product_info = $this->tarifs_model->get_tarif($prodbase, $today);
            $pxbase = $product_info ['prix'];

            $prodaces = 'Essence ACES';
            $product_info = $this->tarifs_model->get_tarif($prodaces, $today);
            $pxaces = $product_info ['prix'];

            $pu_selector = array (
                    '' => '',
                    $prodexte => '100LL extérieurs ' . $pxexte,
                    $prodbase => '100LL basés ' . $pxbase,
                    $prodaces => '100LL ACES ' . $pxaces
            );
        }

        if ($idpomp == 1) {
            $prodexte = 'Essence ULM Extérieurs';
            $product_info = $this->tarifs_model->get_tarif($prodexte, $today);
            $pxexte = $product_info ['prix'];

            $prodbase = 'Essence ULM Basés';
            $product_info = $this->tarifs_model->get_tarif($prodbase, $today);
            $pxbase = $product_info ['prix'];

            $prodaces = 'Essence ULM ACES';
            $product_info = $this->tarifs_model->get_tarif($prodaces, $today);
            $pxaces = $product_info ['prix'];

            $pu_selector = array (
                    '' => '',
                    $prodexte => 'ULM extérieurs ' . $pxexte,
                    $prodbase => 'ULM basés ' . $pxbase,
                    $prodaces => 'ULM ACES ' . $pxaces
            );
        }

        $this->gvvmetadata->set_selector('pilote_selector', $pil_selector);
        $this->gvvmetadata->set_selector('prixu_selector', $pu_selector);
    }

    /**
     * Active ou désactive le filtrage
     */
    public function filterValidation() {
        $button = $this->input->post('button');
        $num = $this->input->post('pnum');
        if ($button == "Filtrer") {
            // Enable filtering
            $session ['filter_date'] = $this->input->post('filter_date');
            $session ['date_end'] = $this->input->post('date_end');
            $session ['filter_pilote'] = $this->input->post('filter_pilote');
            $session ['pnum'] = $this->input->post('pnum');

            $session ['filter_active'] = 1;
            $this->session->set_userdata($session);
            // var_dump($session);
        } else {
            // Disable filtering
            foreach ( array (
                    'filter_date',
                    'date_end',
                    'filter_pilote'
            ) as $field ) {
                $this->session->unset_userdata($field);
            }
        }
        // Il faut rediriger et non pas appeller $this->page, sinon l'URL
        // enregistrée pour le retour est incorrecte
        redirect($this->controller . '/page/' . $num);
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