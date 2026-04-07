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
 * @filesource vols_decouverte.php
 * @package controllers
 * Contrôleur de gestion des avions.
 * 
 *  reviewed by: copilot on 2025-07-31

 */
include('./application/libraries/Gvv_Controller.php');
include(APPPATH . '/third_party/phpqrcode/qrlib.php');
include(APPPATH . '/third_party/tcpdf/tcpdf.php');


/**
 * @property CI_Loader $load
 */
class Vols_decouverte extends Gvv_Controller {

    // Tout le travail est fait par le parent
    protected $controller = 'vols_decouverte';
    protected $model = 'vols_decouverte_model';
    protected $modification_level = 'gestion_vd';
    protected $rules = array(
        'club'     => "callback_section_selected",
        'date_vol' => "callback_date_vol_not_future",
    );

    // Méthodes accessibles sans authentification
    protected $public_methods = array('public_vd');


    /**
     * Constructeur
     */
    function __construct() {
        parent::__construct();

        $this->load->helper('crypto');
        $this->load->model('tarifs_model');
        $this->load->model('configuration_model');
        $this->load->model('terrains_model');
        $this->load->model('vols_planeur_model');
        $this->load->library('session');
    }

    /**
     * Check if user has either pilote_vd or gestion_vd rights (or is admin).
     * Used for actions that are accessible to both pilots and managers.
     */
    private function has_vd_pilot_rights() {
        return $this->dx_auth->is_admin()
            || parent::user_has_role('gestion_vd')
            || parent::user_has_role('pilote_vd');
    }

    /**
     * Override: pilote_vd can modify records only for pre_flight and done actions.
     * Only gestion_vd (and admin) can edit, create, or delete.
     */
    protected function ensure_modification_rights($action = MODIFICATION) {
        if ($action == VISUALISATION) return TRUE;
        if (!isset($this->modification_level) || $this->modification_level === '') return TRUE;
        if ($this->dx_auth->is_admin() || parent::user_has_role('gestion_vd')) return TRUE;

        // pilote_vd may only access pre_flight and done
        $method = $this->uri->segment(2);
        if (parent::user_has_role('pilote_vd') && in_array($method, ['pre_flight', 'done'])) {
            return TRUE;
        }

        show_404();
        return FALSE;
    }

    /**
     * Override: creation requires gestion_vd, pilote_vd, tresorier or admin.
     * Injects HelloAsso visibility flags into view data.
     */
    function create() {
        if (!$this->dx_auth->is_admin()
            && !parent::user_has_role('gestion_vd')
            && !parent::user_has_role('pilote_vd')
            && !has_role('tresorier')
            && !has_role('bureau')) {
            show_404();
            return;
        }

        // Populate $this->data without rendering (no_view_loading = true)
        parent::create(true);

        // Visibilité bouton "Payer par CB"
        $this->data['is_tresorier'] = has_role('tresorier') || has_role('bureau') || $this->dx_auth->is_admin();
        $this->data['vd_par_cb_enabled'] = false;
        $section_id = (int) $this->session->userdata('section');
        if ($section_id > 0) {
            $section_row = $this->sections_model->get_by_id('id', $section_id);
            if (!empty($section_row['has_vd_par_cb'])) {
                $this->load->model('paiements_en_ligne_model');
                $enabled = $this->paiements_en_ligne_model->get_config('helloasso', 'enabled', $section_id);
                $this->data['vd_par_cb_enabled'] = ($enabled === '1');
            }
        }

        // Repeuple le formulaire après un échec d'initiation CB.
        $form_data = $this->session->flashdata('decouverte_form_data');
        if (is_array($form_data)) {
            foreach (array('product', 'beneficiaire', 'de_la_part', 'beneficiaire_email', 'occasion', 'date_vente', 'date_validite') as $field) {
                if (array_key_exists($field, $form_data)) {
                    $this->data[$field] = $form_data[$field];
                }
            }
        }

        return load_last_view($this->form_view, $this->data, $this->unit_test);
    }

    /**
     * Override: intercept "payer_cb" button before delegating to parent.
     * For done/pre_flight pages, redirect back to source URL on validation error
     * so the user sees the error on the correct page.
     */
    public function formValidation($action, $return_on_success = false) {
        $button = $this->input->post('button');

        if ($button === 'payer_cb' && (int) $action === CREATION) {
            $this->_initiate_decouverte_helloasso();
            return;
        }

        $source_url = $this->input->post('source_url') ?: '';
        $from_done_or_preflight = !empty($source_url)
            && (strpos($source_url, '/done/') !== false || strpos($source_url, '/pre_flight/') !== false);

        if ($from_done_or_preflight) {
            $date_vol = $this->input->post('date_vol');
            if (!empty($date_vol) && !$this->date_vol_not_future($date_vol)) {
                $this->lang->load('vols_decouverte');
                $this->session->set_flashdata('error', $this->lang->line('gvv_vd_date_vol_future'));
                redirect($source_url);
                return;
            }
        }

        return parent::formValidation($action, $return_on_success);
    }

    /**
     * Initiate a HelloAsso checkout from vols_decouverte/create form.
     * Creates the transaction and redirects to the QR/link page.
     */
    private function _initiate_decouverte_helloasso() {
        if (!$this->dx_auth->is_admin()
            && !parent::user_has_role('gestion_vd')
            && !parent::user_has_role('pilote_vd')
            && !has_role('tresorier')
            && !has_role('bureau')) {
            show_404();
            return;
        }

        $this->load->model('paiements_en_ligne_model');
        $this->load->library('Helloasso');
        $this->lang->load('paiements_en_ligne');
        $form_input = $this->_get_decouverte_form_input();

        $section_id = (int) $this->session->userdata('section');
        if (!$section_id) {
            $this->_redirect_decouverte_create_with_error($this->lang->line('gvv_bar_carte_error_section'), $form_input);
            return;
        }

        $section_row = $this->sections_model->get_by_id('id', $section_id);
        if (empty($section_row['has_vd_par_cb'])) {
            $this->_redirect_decouverte_create_with_error($this->lang->line('gvv_decouverte_error_cb_disabled'), $form_input);
            return;
        }
        $enabled = $this->paiements_en_ligne_model->get_config('helloasso', 'enabled', $section_id);
        if ($enabled !== '1') {
            $this->_redirect_decouverte_create_with_error($this->lang->line('gvv_bar_carte_error_disabled'), $form_input);
            return;
        }

        $product_ref        = trim((string) $this->input->post('product'));
        $beneficiaire       = trim((string) $this->input->post('beneficiaire'));
        $de_la_part         = trim((string) $this->input->post('de_la_part'));
        $beneficiaire_email = trim((string) $this->input->post('beneficiaire_email'));

        if (empty($product_ref)) {
            $this->_redirect_decouverte_create_with_error($this->lang->line('gvv_decouverte_error_product'), $form_input);
            return;
        }
        if (empty($beneficiaire)) {
            $this->_redirect_decouverte_create_with_error($this->lang->line('gvv_decouverte_error_beneficiaire'), $form_input);
            return;
        }
        if (!empty($beneficiaire_email) && !filter_var($beneficiaire_email, FILTER_VALIDATE_EMAIL)) {
            $this->_redirect_decouverte_create_with_error($this->lang->line('gvv_decouverte_error_email'), $form_input);
            return;
        }

        $produit = $this->db
            ->select('reference, description, prix, compte')
            ->from('tarifs')
            ->where('club', $section_id)
            ->where('reference', $product_ref)
            ->where('type_ticket', 1)
            ->get()
            ->row_array();

        if (!$produit) {
            $this->_redirect_decouverte_create_with_error($this->lang->line('gvv_decouverte_error_product'), $form_input);
            return;
        }

        $montant = (float) $produit['prix'];
        if ($montant <= 0) {
            $this->_redirect_decouverte_create_with_error($this->lang->line('gvv_decouverte_error_amount'), $form_input);
            return;
        }

        $current_user_email = '';
        $current_member = $this->db->select('memail')->from('membres')
            ->where('mlogin', $this->dx_auth->get_username())->get()->row_array();
        if ($current_member && !empty($current_member['memail'])) {
            $current_user_email = strtolower(trim((string) $current_member['memail']));
        }
        $beneficiaire_email_norm = strtolower(trim((string) $beneficiaire_email));
        $initiated_by_user = ($current_user_email !== ''
            && $beneficiaire_email_norm !== ''
            && $current_user_email === $beneficiaire_email_norm);

        $txid        = 'dec-' . $section_id . '-0-' . time() . '-' . substr(uniqid(), -6);
        $description = trim('Bon découverte - ' . $produit['description']);

        $metadata = array(
            'type'                  => 'decouverte',
            'product_reference'     => (string) $produit['reference'],
            'product_description'   => (string) $produit['description'],
            'beneficiaire'          => $beneficiaire,
            'de_la_part'            => $de_la_part,
            'beneficiaire_email'    => $beneficiaire_email,
            'initiated_by_user'     => $initiated_by_user,
            'compte_destination_id' => (int) $produit['compte'],
            'description'           => $description,
            'gvv_transaction_id'    => $txid,
        );

        $tx_id = $this->paiements_en_ligne_model->create_transaction(array(
            'user_id'        => 0,
            'montant'        => $montant,
            'plateforme'     => 'helloasso',
            'club'           => $section_id,
            'transaction_id' => $txid,
            'metadata'       => json_encode($metadata),
            'created_by'     => $this->dx_auth->get_username(),
        ));

        if (!$tx_id) {
            $error = $this->lang->line('gvv_decouverte_error_tx')
                . ' Détails: txid=' . $txid
                . ', section=' . (int) $section_id
                . ', montant=' . number_format((float) $montant, 2, '.', '');
            $this->_redirect_decouverte_create_with_error($error, $form_input);
            return;
        }

        // Le checkout HelloAsso est créé à la demande quand le client clique "Payer"
        // sur la page publique paiements_en_ligne/public_decouverte/{txid}
        redirect('paiements_en_ligne/decouverte_qr/' . $txid);
    }

    /**
     * Récupère les champs du formulaire de création bon découverte à conserver en cas d'échec CB.
     */
    private function _get_decouverte_form_input() {
        return array(
            'product'            => trim((string) $this->input->post('product')),
            'beneficiaire'       => trim((string) $this->input->post('beneficiaire')),
            'de_la_part'         => trim((string) $this->input->post('de_la_part')),
            'beneficiaire_email' => trim((string) $this->input->post('beneficiaire_email')),
            'occasion'           => trim((string) $this->input->post('occasion')),
            'date_vente'         => trim((string) $this->input->post('date_vente')),
            'date_validite'      => trim((string) $this->input->post('date_validite')),
        );
    }

    /**
     * Affiche une erreur détaillée et recharge le formulaire avec les données saisies.
     */
    private function _redirect_decouverte_create_with_error($error_message, array $form_input) {
        $this->session->set_flashdata('error', $error_message);
        $this->session->set_flashdata('decouverte_form_data', $form_input);
        redirect('vols_decouverte/create');
    }

    /**
     * Filter discovery flights based on user criteria
     * Handles filter form submissions and stores criteria in session
     */
    public function filter() {
        $post = $this->input->post();
        $button = $post['button'] ?? '';

        if ($button == $this->lang->line("gvv_str_select")) {
            // Enable filtering - validate and store filter parameters
            $start_date = $this->_validate_date($post['startDate'] ?? '');
            $end_date = $this->_validate_date($post['endDate'] ?? '');
            $filter_type = $this->_validate_filter_type($post['filter_type'] ?? '');
            $year = $this->_validate_year($post['year'] ?? date('Y'));

            // Date logic validation
            if ($start_date && $end_date && $start_date > $end_date) {
                $this->session->set_userdata('vd_filter_error', 'La date de début doit être antérieure à la date de fin.');
                $start_date = '';
                $end_date = '';
            }

            $this->session->set_userdata('vd_startDate', $start_date);
            $this->session->set_userdata('vd_endDate', $end_date);
            $this->session->set_userdata('vd_filter_type', $filter_type);
            $this->session->set_userdata('vd_year', $year);
            $this->session->set_userdata('vd_filter_active', true);
            $this->session->set_userdata('filter_active', true); // For filter_buttons() helper
        } else {
            // Disable filtering - clear filters but keep the year selector
            foreach (array('vd_startDate', 'vd_endDate', 'vd_filter_type', 'vd_filter_active', 'filter_active') as $field) {
                $this->session->unset_userdata($field);
            }
        }

        // Redirect back to page
        $return_url = $this->_validate_return_url($post['return_url'] ?? '');
        if (!empty($return_url)) {
            redirect($return_url);
        } else {
            redirect('vols_decouverte/page');
        }
    }

    /**
     * Validate date input (YYYY-MM-DD format)
     */
    private function _validate_date($date) {
        if (empty($date)) {
            return '';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date_parts = explode('-', $date);
            if (checkdate($date_parts[1], $date_parts[2], $date_parts[0])) {
                return $date;
            }
        }

        return '';
    }

    /**
     * Validation callback: date_vol must not be in the future.
     * Returns true if empty (field is optional on create/edit).
     * Value is in d/m/Y format as submitted by the datepicker.
     */
    public function date_vol_not_future($value) {
        if (empty($value)) {
            return true;
        }
        $this->load->helper('validation');
        $db_date = date_ht2db($value);
        if ($db_date > date('Y-m-d')) {
            $this->lang->load('vols_decouverte');
            $this->form_validation->set_message('date_vol_not_future', $this->lang->line('gvv_vd_date_vol_future'));
            return false;
        }
        return true;
    }

    /**
     * Validate filter type selection
     */
    private function _validate_filter_type($filter_type) {
        $valid_types = ['all', 'done', 'todo', 'cancelled', 'expired'];
        return in_array($filter_type, $valid_types) ? $filter_type : 'all';
    }

    /**
     * Validate year input
     */
    private function _validate_year($year) {
        if (is_numeric($year) && $year >= 2000 && $year <= date('Y') + 10) {
            return (int)$year;
        }
        return date('Y');
    }

    /**
     * Validate return URL to prevent open redirect vulnerability
     */
    private function _validate_return_url($return_url) {
        if (empty($return_url)) {
            return '';
        }

        // Only allow internal URLs (relative paths)
        $parsed_url = parse_url($return_url);
        if (!isset($parsed_url['scheme']) && !isset($parsed_url['host'])) {
            $return_url = filter_var($return_url, FILTER_SANITIZE_URL);
            if ($return_url) {
                return $return_url;
            }
        }

        return '';
    }

    /**
     * Set year for filtering discovery flights
     * Allows URLs like: vols_decouverte/set_year/2024
     */
    public function set_year($year = null) {
        if ($year !== null && is_numeric($year)) {
            $this->session->set_userdata('vd_year', (int)$year);
        }
        redirect('vols_decouverte/page');
    }

    /**
     * Page override to provide filter data to the view
     * Signature compatible with parent Gvv_Controller::page()
     */
    public function page($premier = 0, $message = '', $selection = array()) {
        // Check if $premier is being used as $year for backward compatibility
        // This allows URLs like vols_decouverte/page/2024 to still work
        if ($premier > 1900 && $premier < 2100) {
            $this->session->set_userdata('vd_year', (int)$premier);
            $premier = 0; // Reset to default page number
        }

        // Get current year from session
        $current_year = $this->session->userdata('vd_year') ?: date('Y');

        // Prepare filter data for the view
        $this->data['filter_active'] = $this->session->userdata('vd_filter_active') ?: false;
        $this->data['startDate'] = $this->session->userdata('vd_startDate') ?: '';
        $this->data['endDate'] = $this->session->userdata('vd_endDate') ?: '';
        $this->data['filter_type'] = $this->session->userdata('vd_filter_type') ?: 'all';
        $this->data['year'] = $current_year;
        $this->data['year_selector'] = $this->gvv_model->get_available_years();
        $this->data['controller'] = $this->controller;
        $this->data['has_pilot_rights'] = $this->has_vd_pilot_rights();

        // Visibilité bouton "Partager la page publique"
        $section_id = (int) $this->session->userdata('section');
        $this->data['current_section_id'] = $section_id;
        $this->data['vd_par_cb_enabled'] = false;
        if ($section_id > 0) {
            $section_row = $this->sections_model->get_by_id('id', $section_id);
            if (!empty($section_row['has_vd_par_cb'])) {
                $this->data['vd_par_cb_enabled'] = true;
            }
        }

        // Statistiques par section (toutes sections)
        $this->data['vd_stats_per_section'] = $this->gvv_model->stats_per_section($current_year);

        // Handle filter error messages
        $filter_error = $this->session->userdata('vd_filter_error');
        if ($filter_error) {
            $this->data['filter_error'] = $filter_error;
            $this->session->unset_userdata('vd_filter_error');
        }

        // Call parent page method with original parameters
        return parent::page($premier, $message, $selection);
    }

    /**
     * Génération des éléments statiques à passer au formulaire en cas de création,
     * modification ou ré-affichage après erreur.
     * Sont statiques les parties qui ne changent pas d'un élément sur l'autre.
     *
     * @param $action CREATION
     *            | MODIFICATION | VISUALISATION
     * @see constants.php
     */
    protected function form_static_element($action) {
        $this->data['action'] = $action;
        $this->data['fields'] = $this->fields;
        $this->data['controller'] = $this->controller;
        if ($action == "visualisation") {
            $this->data['readonly'] = "readonly";
        }

        $this->data['saisie_par'] = $this->dx_auth->get_username();

        // Initialize date_vente with current date for new records
        if ($action == CREATION && empty($this->data['date_vente'])) {
            $this->data['date_vente'] = date('Y-m-d');
        }
        // Initialize date_validite with current date + 1 year for new records
        if ($action == CREATION && empty($this->data['date_validite'])) {
            $this->data['date_validite'] = date('Y-m-d', strtotime('+1 year'));
        }

        $product_selector = $this->tarifs_model->selector(array('type_ticket' => 1));
        $this->gvvmetadata->set_selector('product_selector', $product_selector);

        $pilote_selector = $this->membres_model->vd_pilots();
        // vd_pilots() always includes an empty entry [''=>''], so count=1 means no pilots found
        if (count($pilote_selector) <= 1) {
            // No pilote_vd defined in this section: fall back to all active members
            $pilote_selector = $this->membres_model->selector_with_null(['actif' => 1]);
        }
        $this->gvvmetadata->set_selector('pilote_selector', $pilote_selector);

        $this->gvvmetadata->set_selector('machine_selector', $this->gvv_model->machine_selector());

        $this->gvvmetadata->set_selector('terrains_selector', $this->terrains_model->selector_with_null());

        // Pre-fill aerodrome on creation
        if ($action == CREATION) {
            $section = $this->gvv_model->section();
            if ($section && $section['nom'] == 'Planeur') {
                // Planeur: use terrain of the last registered planeur flight
                $latestf = $this->vols_planeur_model->latest_flight();
                if (!empty($latestf) && !empty($latestf[0]['vplieudeco'])) {
                    $this->data['aerodrome'] = $latestf[0]['vplieudeco'];
                }
            } else {
                // Avion / ULM: use defaut.aerodrome config parameter
                $defaut_aerodrome = $this->configuration_model->get_param('defaut.aerodrome');
                if ($defaut_aerodrome) {
                    $terrain = $this->terrains_model->get_by_id('oaci', $defaut_aerodrome);
                    if (!empty($terrain)) {
                        $this->data['aerodrome'] = $defaut_aerodrome;
                    }
                }
            }
        }
    }


    /**
     * Affiche les différentes action possibles sur un vol de découverte
     */
    function action($obfuscated_id) {
        $this->push_return_url("action");

        $id = reverseTransform($obfuscated_id);

        $this->data = $this->gvv_model->get_by_id($this->kid, $id);

        if (!count($this->data)) {
            $data = [];
            $data['msg'] = "Le vol de découverte $obfuscated_id n'existe pas";
            load_last_view('error', $data);
            return;
        }

        $this->data['obfuscated_id'] = $obfuscated_id;
        $product = $this->data['product'];
        $tarif = $this->tarifs_model->get_tarif($product, date("Y-m-d"));
        $this->data['description'] = ($tarif['description'] != "") ? $tarif['description'] : $product;

        // Check if expired: use date_validite if set, otherwise date_vente + 1 year
        if (!empty($this->data['date_validite'])) {
            $this->data['expired'] = strtotime($this->data['date_validite']) < time();
        } else {
            $this->data['expired'] = strtotime($this->data['date_vente']) < strtotime('-1 year -1 day', time());
        }

        $this->data['has_modification_rights'] = !isset($this->modification_level) || $this->dx_auth->is_admin() || parent::user_has_role($this->modification_level);
        $this->data['has_pilot_rights'] = $this->has_vd_pilot_rights();

        return load_last_view("vols_decouverte/formMenu", $this->data, $this->unit_test);
    }

    /**
     * Action when the flight is selected by the selector
     */
    function action_clear() {
        if ($this->input->post('vd_id')) {
            $this->push_return_url("action");

            $id = $this->input->post('vd_id');
            $obfuscated = transformInteger($id);
            redirect("vols_decouverte/action/" . $obfuscated);
        }
    }

    /**
     * Accés à un vol de découverte par numéro
     */
    function select_by_id() {

        $this->data['vd_selector'] = $this->gvv_model->selector();
        return load_last_view("vols_decouverte/formSelector", $this->data, $this->unit_test);
    }

    /**
     * pdf request
     */
    function print_vd($obfuscated_id) {
        $id = reverseTransform($obfuscated_id);
        $vd = $this->gvv_model->get_by_id($this->kid, $id);
        // var_dump($vd);exit;

        if (!count($vd)) {
            $data = [];
            $data['msg'] = "Le vol de découverte $obfuscated_id n'existe pas";
            load_last_view('error', $data);
            return;
        }

        $data = [];
        $data['obfuscated_id'] = $obfuscated_id;
        $data['id'] = $id;

        $data['offer_a'] = $vd['beneficiaire'];
        $data['occasion'] = $vd['occasion'];
        $data['de_la_part'] = $vd['de_la_part'];

        // Use date_validite if defined, otherwise use date_vente + 1 year
        if (!empty($vd['date_validite'])) {
            $data['validity'] = date_db2ht($vd['date_validite']);
        } else {
            $data['validity'] = date_db2ht(date('Y-m-d', strtotime($vd['date_vente'] . ' +1 year')));
        }

        $data[$vd['product']] = true;

        $this->generate_pdf($data);
    }

    /**
     * email un bon
     */
    function email_vd($obfuscated_id) {
        $id = reverseTransform($obfuscated_id);
        $vd = $this->gvv_model->get_by_id($this->kid, $id);
        // var_dump($vd);exit;

        if (!count($vd)) {
            $data = [];
            $data['msg'] = "Le vol de découverte $obfuscated_id n'existe pas";
            load_last_view('error', $data);
            return;
        }

        $data = [];
        $data['obfuscated_id'] = $obfuscated_id;
        $data['id'] = $id;

        $data['offer_a'] = $vd['beneficiaire'];
        $data['occasion'] = $vd['occasion'];
        $data['de_la_part'] = $vd['de_la_part'];

        // Use date_validite if defined, otherwise use date_vente + 1 year
        if (!empty($vd['date_validite'])) {
            $data['validity'] = date_db2ht($vd['date_validite']);
        } else {
            $data['validity'] = date_db2ht(date('Y-m-d', strtotime($vd['date_vente'] . ' +1 year')));
        }

        $data[$vd['product']] = true;

        $pdf_content = $this->generate_pdf($data, 'S');

        // Send email with PDF attachment
        $this->send_email_with_pdf($vd, $pdf_content, $id, $data['validity']);
    }

    /**
     * Send email with PDF attachment
     */
    function send_email_with_pdf($vd, $pdf_content, $id, $validity_date) {
        $this->load->library('email');

        $sender = "info@aeroclub-abbeville.fr";
        $sender = $this->configuration_model->get_param('vd.email.sender_email');

        // Feature flag: use standard email configuration if enabled
        $use_unified_cfg = (bool) $this->config->item('use_standard_email_configuration_for_vd');

        // Prepare email library
        $this->email->clear(true); // also clears attachments
        $this->email->set_mailtype('html');
        $this->email->set_newline("\r\n");
        $this->email->set_crlf("\r\n");
        gvv_debug('VD email: using standard email configuration (config/email.php)', 'email_config');

        // Set email parameters
        $this->email->from($sender, 'Aéroclub d\'Abbeville');
        $this->email->to($vd['beneficiaire_email']);
        $this->email->bcc($sender);

        $this->email->subject('Votre bon de vol de découverte');

        $message = "Bonjour " . $vd['beneficiaire'] . ",<br><br>";

        $message .= "Voici votre bon pour un vol de découverte. Il est valable jusqu'au <strong>" . $validity_date . "</strong>.<br><br>";
        $message .= "Cordialement,<br><br>L'équipe de l'Aéroclub d'Abbeville";

        $this->email->message($message);

        // Attach PDF
        $temp_file = "/tmp/vol_decouverte_acs_" . $id . ".pdf";
        file_put_contents($temp_file, $pdf_content);
        $this->email->attach($temp_file, 'attachment', "vol_decouverte_acs_" . $id . ".pdf", 'application/pdf');

        // Send email
        if ($this->email->send()) {
            unlink($temp_file); // Clean up after sending
            $msg = '<div class="alert alert-success alert-dismissible fade show">'
                 . '<i class="fas fa-check"></i> '
                 . 'Email envoyé avec succès à ' . htmlspecialchars($vd['beneficiaire_email'])
                 . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>'
                 . '</div>';
            $this->session->set_flashdata('message', $msg);
            redirect(site_url('vols_decouverte/page'));
        } else {
            // Error message
            $data['msg'] = "Erreur lors de l'envoi de l'email: " . $this->email->print_debugger();
            load_last_view('error', $data);
        }
    }

    /**
     * Génération du bon cadeau
     */
    function generate_pdf($data, $output = "I") {

        $obfuscated_id = $data['obfuscated_id'];

        $id = $data['id'];
        $this->data = $this->gvv_model->get_by_id($this->kid, $id);

        $tempDir = sys_get_temp_dir();
        $index_page = $this->config->item('index_page');

        $qr_url = site_url('vols_decouverte/action/' . $obfuscated_id);
        $qr_name =  $tempDir . '/qrcode_' . $id . '.png';
        QRcode::png($qr_url, $qr_name, QR_ECLEVEL_L, 10, 1);

        // create new PDF document
        $pdf = new TCPDF('L', 'mm', 'A5', true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        // $pdf->SetAuthor("Aéroclub d'Abbeville");
        $pdf->SetAuthor($this->configuration_model->get_param('vd.email.sender_name'));
        $pdf->SetTitle('Vol de découverte ' . $id);
        $pdf->SetSubject('Bon cadeau');
        $pdf->SetKeywords('vol, découverte');

        // set header and footer fonts
        $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(0);
        $pdf->SetFooterMargin(0);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // remove default footer
        $pdf->setPrintFooter(false);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // ---------------------------------------------------------


        // add a page
        $pdf->AddPage();

        // -- set background ---

        // get the current page break margin
        $bMargin = $pdf->getBreakMargin();
        // get current auto-page-break mode
        $auto_page_break = $pdf->getAutoPageBreak();
        // disable auto-page-break
        $pdf->SetAutoPageBreak(false, 0);
        // set background image
        $background_image = $this->configuration_model->get_file('vd.background_image');
        if (!empty($background_image) && file_exists($background_image)) {
            $img_file = $background_image;
        } else {
            // Fallback to default image if configuration is not set or file doesn't exist
            $img_file = image_dir() . "Bon-Bapteme.png";
        }
        $pdf->Image($img_file, 0, 0, 210, 150, '', '', '', false, 300, '', false, false, 0);
        // restore auto-page-break status
        $pdf->SetAutoPageBreak($auto_page_break, $bMargin);
        // set the starting point for the page content
        $pdf->setPageMark();

        // ---------------------------------------------------------
        // Check if QR code image exists
        if (file_exists($qr_name)) {
            // Position QR code at the right side of the page
            $qrX = 175;
            $qrY = 5;
            $qrSize = 30;

            // Add QR code
            $pdf->Image($qr_name, $qrX, $qrY, $qrSize, $qrSize, 'PNG', '', 'T', false, 300, '', false, false, 0, 'CM');
        }

        /** Verso */
        $pdf->AddPage();

        // Set content position 
        $pdf->SetXY(5, 5);
        $pdf->SetMargins(5, 5, 5);
        $pdf->setAutoPageBreak(false);

        // Reset font for normal content
        $pdf->SetFont('helvetica', '', 11);

        // Set font
        $pdf->SetFont('helvetica', '', 10);

        // Header section


        $offer_a = $data['offer_a'];
        $occasion = $data['occasion'];
        $de_la_part = $data['de_la_part'];
        $validity = $data['validity'];

        $header_html = <<<EOD
<table cellspacing="0" cellpadding="3" border="1">
    <tr>
        <td width="67%">Ce bon pour le survol de la région défini ci-après</td>
        <td width="33%">N° <strong>{$id}</strong></td>
    </tr>
    <tr>
        <td width="67%">Offert à <strong>{$offer_a}</strong></td>
        <td width="33%"></td>
    </tr>
    <tr>
        <td width="67%">à l'occasion de {$occasion}</td>
        <td width="33%">de la part de {$de_la_part}</td>
    </tr>
    <tr>
        <td width="67%">Ce bon est valable 1 an jusqu'au <strong>{$validity}</strong></td>
        <td width="33%"></td>
    </tr>
</table>
EOD;
        $pdf->writeHTML($header_html, true, false, false, false, '');

        // Options section - Airplane and Glider and Ultralight
        $checked = '<img src="' . image_dir() . 'checked.png" width="10" height="10" alt="Checked checkbox" >';
        $unchecked = '<img src="' . image_dir() . 'unchecked.png" width="10" height="10" alt="Unchecked checkbox" >';

        $abbeville = isset($data['abbeville']) ? $checked : $unchecked;
        $baie = isset($data['baie']) ? $checked : $unchecked;
        $falaise = isset($data['falaises']) ? $checked : $unchecked;
        $autre = isset($data['autre']) ? $checked : $unchecked;
        $noyelles = isset($data['noyelles']) ? $checked : $unchecked;
        $planeur = isset($data['planeur']) ? $checked : $unchecked;
        $abbeville_ulm = isset($data['abbeville_ulm']) ? $checked : $unchecked;
        $baie_ulm = isset($data['baie_ulm']) ? $checked : $unchecked;
        $falaise_ulm = isset($data['falaises_ulm']) ? $checked : $unchecked;
        $autre_ulm = isset($data['autre_ulm']) ? $checked : $unchecked;

        $options_html = <<<EOD
<table cellspacing="0" cellpadding="5" border="1">
    <tr>
        <td width="33%" align="center"><strong>Pour l'avion</strong></td>
        <td width="34%" align="center"><strong>Pour le planeur</strong></td>
        <td width="33%" align="center"><strong>Pour l'ULM</strong></td>
    </tr>
    <tr>
        <td width="33%" style="height: 120px; vertical-align: top;">
            <br /> {$abbeville} Tour d'Abbeville (15 mn environ) pour 2 personnes
            <br /><br />{$baie} Baie de Somme (30 mn environ) pour 2 personnes
            <br /><br />{$falaise} Falaises ou Marquenterre (40 mn) pour 2 personnes
            <br /><br />{$noyelles} Noyelles - Portes de la baie (20 mn) pour 2 personnes:
        </td>
        <td width="34%" style="vertical-align: top;">
            <br /><br />{$planeur} Vol en planeur (largage 500 m, 15 à 30 mn suivant la météo)
            <br /><br />
        </td>
        <td width="33%" style="height: 120px; vertical-align: top;">
            <br />{$abbeville_ulm} Tour d'Abbeville (15 mn environ) pour 1 personne
            <br /><br />{$baie_ulm} Baie de Somme (30 mn environ) pour 1 personne
            <br /><br />{$falaise_ulm} Falaises ou Marquenterre (40 mn) pour 1 personne
            <br /><br />{$autre_ulm} Autre (à détailler) :
        </td>
    </tr>
</table>
EOD;
        $pdf->writeHTML($options_html, true, false, false, false, '');

        // Contact section
        $contact_avion = $this->configuration_model->get_param('vd.avion.contact_name');
        $contact_planeur = $this->configuration_model->get_param('vd.planeur.contact_name');
        $contact_ulm = $this->configuration_model->get_param('vd.ulm.contact_name');
        $tel_avion = $this->configuration_model->get_param('vd.avion.contact_tel');
        $tel_planeur = $this->configuration_model->get_param('vd.planeur.contact_tel');
        $tel_ulm = $this->configuration_model->get_param('vd.ulm.contact_tel');

        $contact_html = <<<EOD
<table cellspacing="0" cellpadding="5" border="1" style="width: 100%;">
    <tr>
        <td>
            Pour prendre rendez-vous et organiser votre vol, vous devez contacter<br>
            
            <br />- pour l'avion <strong>{$contact_avion} ({$tel_avion})</strong> 
            <br />- pour le planeur <strong>{$contact_planeur} ({$tel_planeur})</strong>
            <br />- pour l'ULM <strong>{$contact_ulm} ({$tel_ulm})</strong>
            <br>
        </td>
    </tr>

    <tr style="width: 100%; background-color: #ddddd">
        <td width="33%" height="1.5cm">Vol effectué le :</td>
        <td width="33%">sur (nom de l'appareil) :</td>
        <td width="34%">par (nom du pilote) :</td>
    </tr>
</table>
EOD;

        $pdf->writeHTML($contact_html, true, false, false, false, '');

        //Close and output PDF document
        $res = $pdf->Output("vol_decouverte_acs_" . $id . ".pdf", $output);
        if ($output == "S") return $res;
    }

    /**
     * Edition avant vol (checklist, pré-vol, post-vol)
     */
    function pre_flight($obfuscated_id) {
        $id = reverseTransform($obfuscated_id);
        $this->edit($id);
    }

    /**
     * Edition après vol (date du vol)
     */
    function done($obfuscated_id) {
        $id = reverseTransform($obfuscated_id);
        $this->edit($id);
    }

    /**
     * Test de la génération du QR code
     */
    function qr() {

        $originalNumber = 12345;
        $transformed = transformInteger($originalNumber);
        $recovered = reverseTransform($transformed);

        echo "QR:";
        echo "Nombre original: " . $originalNumber . "\n";
        echo "Nombre transformé: " . $transformed . "\n";
        echo "Nombre récupéré: " . $recovered . "\n";

        // Test avec quelques autres valeurs
        $testValues = [0, 1, 42, 99999, 1000000];
        foreach ($testValues as $value) {
            $transformed = transformInteger($value);
            $recovered = reverseTransform($transformed);
            echo "<br> Test avec $value: transformé = $transformed, récupéré = $recovered\n";
        }

        QRcode::png('https://example.com');
        QRcode::png('https://example.com', 'qrcode.png');
    }

    /**
     * Export de la liste des vols de découverte en CSV ou PDF
     */
    public function export($mode = 'csv') {
        if (!$this->user_has_role('gestion_vd') && !$this->dx_auth->is_admin()) {
            $this->dx_auth->deny_access();
            return;
        }

        // Respect current filters/year via model
        $rows = $this->gvv_model->select_page(10000, 0);

        // Fields to export (exclude action columns)
        $fields = array('id', 'validite', 'product', 'beneficiaire', 'urgence', 'date_vol', 'pilote', 'airplane_immat', 'cancelled', 'paiement', 'participation');
        $this->lang->load('vols_decouverte');

        $section_data = $this->sections_model->section();
        $section_name = $section_data ? $section_data['nom'] : '';
        $year = $this->session->userdata('year') ?: date('Y');

        $title = $this->lang->line('gvv_vols_decouverte_title');
        if ($section_name) {
            $title .= ' - ' . $section_name;
        }
        $title .= ' - ' . $year;

        // Build a meaningful filename: vd_<section>_<year>.<ext>
        $section_slug = $section_name
            ? strtolower(preg_replace('/[^a-z0-9]+/i', '_', $section_name))
            : 'club';
        $base_filename = 'vd_' . $section_slug . '_' . $year;

        if ($mode === 'csv') {
            return $this->gvvmetadata->csv_table('vue_vols_decouverte', $rows, array(
                'title'    => $title,
                'fields'   => $fields,
                'filename' => $base_filename . '.csv',
            ));
        }

        $this->load->library('Pdf');
        $pdf = new Pdf();
        $pdf->AddPage('L');
        // Landscape widths summed to ~270mm
        $width = array(12, 22, 50, 40, 12, 18, 28, 22, 12, 18, 18, 18);
        $this->gvvmetadata->pdf_table('vue_vols_decouverte', $rows, $pdf, array(
            'title' => $title,
            'fields' => $fields,
            'width' => $width,
        ));
        $pdf->Output($base_filename . '.pdf', 'I');
    }

    // =========================================================================
    // Page publique de réservation de vol de découverte
    // =========================================================================

    /**
     * Envoie par email le lien de la page publique VD (POST).
     *
     * Accès : gestion_vd, tresorier, bureau, admin
     */
    public function send_public_link() {
        $this->lang->load('vols_decouverte');

        if (!$this->dx_auth->is_admin()
            && !parent::user_has_role('gestion_vd')
            && !has_role('tresorier')
            && !has_role('bureau')) {
            show_404();
            return;
        }

        $section_id     = (int) $this->input->post('section_id');
        $to             = trim((string) $this->input->post('to') ?: '');
        $custom_message = trim((string) $this->input->post('custom_message') ?: '');

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_vd_public_error_email'));
            redirect('vols_decouverte/page');
            return;
        }

        $lien    = site_url('vols_decouverte/public_vd?section=' . $section_id);
        $subject = $this->lang->line('gvv_vd_share_link_subject');
        $body    = $lien;

        $this->load->model('configuration_model');
        $nom_club     = $this->config->item('nom_club') ?: 'GVV';
        $sender_email = $this->configuration_model->get_param('vd.email.sender_email') ?: 'noreply@gvv.net';
        $sender_name  = $this->configuration_model->get_param('vd.email.sender_name')  ?: $nom_club;

        try {
            $this->load->library('email');
            $this->email->initialize(array(
                'wordwrap' => true,
                'mailtype' => 'html',
                'charset'  => 'utf-8',
            ));
            $greeting = $this->lang->line('gvv_vd_share_link_greeting');
            $intro    = $this->lang->line('gvv_vd_share_link_intro');
            $cta      = $this->lang->line('gvv_vd_share_link_cta');
            $closing  = $this->lang->line('gvv_vd_share_link_closing');
            $lien_safe      = htmlspecialchars($lien, ENT_QUOTES, 'UTF-8');
            $custom_block   = '';
            if ($custom_message !== '') {
                $custom_block = '<p>' . nl2br(htmlspecialchars($custom_message, ENT_QUOTES, 'UTF-8')) . '</p>';
            }
            $message  = '<p>' . $greeting . '</p>'
                      . '<p>' . $intro . '</p>'
                      . $custom_block
                      . '<p><a href="' . $lien_safe . '">' . $cta . '</a></p>'
                      . '<p>' . $closing . '<br>' . htmlspecialchars($sender_name, ENT_QUOTES, 'UTF-8') . '</p>';

            $this->email->from($sender_email, $sender_name);
            $this->email->to($to);
            $this->email->subject($subject);
            $this->email->message($message);

            if (@$this->email->send()) {
                $this->session->set_flashdata('success', $this->lang->line('gvv_vd_share_link_sent'));
            } else {
                $this->session->set_flashdata('error', $this->lang->line('gvv_vd_public_error_checkout'));
            }
        } catch (Exception $e) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_vd_public_error_checkout'));
        }

        redirect('vols_decouverte/page');
    }

    /**
     * Génère un QR Code PNG pointant vers la page publique VD de la section.
     *
     * Accès : gestion_vd, tresorier, bureau, club-admin, admin
     *
     * @param int $section_id  Identifiant de la section
     */
    public function qrcode($section_id = 0) {
        if (!$this->dx_auth->is_admin()
            && !parent::user_has_role('gestion_vd')
            && !has_role('tresorier')
            && !has_role('bureau')) {
            show_404();
            return;
        }

        $section_id = (int) $section_id;
        if (!$section_id) {
            $this->output->set_status_header(400);
            $this->output->set_output('Bad request: section_id required');
            return;
        }

        $section = $this->sections_model->get_by_id('id', $section_id);
        if (!$section || empty($section['has_vd_par_cb'])) {
            $this->output->set_status_header(404);
            $this->output->set_output('Not found: section not found or VD card payment not enabled');
            return;
        }

        $url = site_url('vols_decouverte/public_vd?section=' . $section_id);

        include_once(APPPATH . '/third_party/phpqrcode/qrlib.php');
        header('Content-Type: image/png');
        QRcode::png($url, false, QR_ECLEVEL_M, 5, 2);
        exit;
    }

    /**
     * Page publique d'achat d'un bon de vol de découverte.
     *
     * GET  : affiche le sélecteur de section, le formulaire et le statut de quota.
     * POST : valide le formulaire, crée la transaction pending et redirige vers HelloAsso.
     *
     * Accès : public (pas de session requise)
     *
     * @param int $section_id  Section forcée (via segment d'URL, optionnel)
     */
    public function public_vd($section_id = 0) {
        $this->load->helper('vd_quota');
        $this->load->helper('rate_limit');
        $this->load->model('paiements_en_ligne_model');
        $this->lang->load('vols_decouverte');
        $this->lang->load('paiements_en_ligne');

        $section_id = (int) ($section_id ?: $this->input->get('section'));

        // Liste de toutes les sections avec VD par CB activé et statut quota
        $sections_disponibles = get_sections_vd_disponibles();

        // Validation de la section si fournie
        $section_row = null;
        $section_error = '';
        if ($section_id > 0) {
            foreach ($sections_disponibles as $s) {
                if ((int) $s['id'] === $section_id) {
                    $section_row = $s;
                    break;
                }
            }
            if (!$section_row) {
                $section_error = $this->lang->line('gvv_vd_public_section_disabled');
            }
        }

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $this->_post_public_vd($section_id, $section_row, $sections_disponibles);
            return;
        }

        $this->_render_public_vd($section_id, $section_row, $sections_disponibles, $section_error);
    }

    /**
     * Traite la soumission POST du formulaire public VD.
     */
    private function _post_public_vd($section_id, $section_row, array $sections_disponibles) {
        // Rate limiting
        if (!check_rate_limit('vd_public_form', 10, 3600)) {
            $this->_render_public_vd(
                $section_id, $section_row, $sections_disponibles, '',
                array('rate_limit' => $this->lang->line('gvv_vd_public_rate_limit'))
            );
            return;
        }

        // Vérification quota
        if ($section_id > 0) {
            $quota_status = get_vd_quota_status($section_id);
            if ($quota_status['atteint']) {
                $this->_render_public_vd(
                    $section_id, $section_row, $sections_disponibles, '',
                    array(), $quota_status
                );
                return;
            }
        }

        // Validation des champs
        $beneficiaire   = trim((string) $this->input->post('beneficiaire'));
        $de_la_part     = trim((string) $this->input->post('de_la_part'));
        $occasion       = trim((string) $this->input->post('occasion'));
        $acheteur_email = trim((string) $this->input->post('acheteur_email'));
        $acheteur_tel   = trim((string) $this->input->post('acheteur_tel'));
        $urgence        = trim((string) $this->input->post('urgence'));
        $poids          = (int) $this->input->post('poids_passagers');
        $nb_personnes   = max(1, (int) $this->input->post('nb_personnes'));
        $product_ref    = trim((string) $this->input->post('product_ref'));
        $post_section   = (int) $this->input->post('section_id');

        if ($post_section) {
            $section_id = $post_section;
        }

        // POST may target /public_vd without ?section=...; reload section context from posted section_id.
        if ($section_id > 0 && !$section_row) {
            foreach ($sections_disponibles as $s) {
                if ((int) $s['id'] === $section_id) {
                    $section_row = $s;
                    break;
                }
            }
        }

        $form_data = compact(
            'beneficiaire', 'de_la_part', 'occasion',
            'acheteur_email', 'acheteur_tel', 'urgence',
            'poids', 'nb_personnes', 'product_ref'
        );

        $errors = array();

        if (empty($beneficiaire)) {
            $errors['beneficiaire'] = $this->lang->line('gvv_vd_public_error_beneficiaire');
        }
        if (!filter_var($acheteur_email, FILTER_VALIDATE_EMAIL)) {
            $errors['acheteur_email'] = $this->lang->line('gvv_vd_public_error_email');
        }
        if (empty($acheteur_tel)) {
            $errors['acheteur_tel'] = $this->lang->line('gvv_vd_public_error_tel');
        }
        if (empty($urgence)) {
            $errors['urgence'] = $this->lang->line('gvv_vd_public_error_urgence');
        }

        // Chargement du produit
        $produit = null;
        if (!empty($product_ref) && $section_id > 0) {
            $today = date('Y-m-d');
            $produit = $this->db
                ->query(
                    "SELECT reference, description, prix, compte, nb_personnes_max
                     FROM tarifs
                     WHERE club = ? AND reference = ? AND type_ticket = 1 AND public = 1
                       AND date <= ? AND (date_fin IS NULL OR date_fin >= ?)
                     ORDER BY date DESC LIMIT 1",
                    array($section_id, $product_ref, $today, $today)
                )
                ->row_array();
        }

        if (!$produit) {
            $errors['product'] = $this->lang->line('gvv_vd_public_error_product');
        } else {
            $nb_max = (int) $produit['nb_personnes_max'];
            if ($nb_max > 1 && $nb_personnes > $nb_max) {
                $errors['nb_personnes'] = sprintf(
                    $this->lang->line('gvv_vd_public_error_nb_personnes'),
                    $nb_max
                );
            }
        }

        if (!empty($errors)) {
            $this->_render_public_vd($section_id, $section_row, $sections_disponibles, '', $errors, null, $form_data);
            return;
        }

        // Vérification HelloAsso activé pour la section
        $enabled = $this->paiements_en_ligne_model->get_config('helloasso', 'enabled', $section_id);
        if ($enabled !== '1') {
            $errors['general'] = $this->lang->line('gvv_vd_public_section_disabled');
            $this->_render_public_vd($section_id, $section_row, $sections_disponibles, '', $errors, null, $form_data);
            return;
        }

        // Création de la transaction
        $this->load->library('Helloasso');

        $montant     = (float) $produit['prix'];
        $txid        = 'vdpub-' . $section_id . '-' . time() . '-' . substr(uniqid(), -6);
        $description = trim('Bon découverte - ' . $produit['description']);

        $meta = array(
            'type'                  => 'decouverte',
            'product_reference'     => (string) $produit['reference'],
            'product_description'   => (string) $produit['description'],
            'compte_destination_id' => (int) $produit['compte'],
            'beneficiaire'          => $beneficiaire,
            'de_la_part'            => $de_la_part,
            'occasion'              => $occasion,
            'beneficiaire_email'    => $acheteur_email,
            'beneficiaire_tel'      => $acheteur_tel,
            'urgence'               => $urgence,
            'poids_passagers'       => $poids,
            'nb_personnes'          => max(1, (int) ($produit['nb_personnes_max'] > 1 ? $nb_personnes : 1)),
            'origine'               => 'public',
            'description'           => $description,
            'gvv_transaction_id'    => $txid,
        );

        $tx_id = $this->paiements_en_ligne_model->create_transaction(array(
            'user_id'        => 0,
            'montant'        => $montant,
            'plateforme'     => 'helloasso',
            'club'           => $section_id,
            'transaction_id' => $txid,
            'metadata'       => json_encode($meta),
            'created_by'     => 'public',
        ));

        if (!$tx_id) {
            $errors['general'] = $this->lang->line('gvv_vd_public_error_db');
            $this->_render_public_vd($section_id, $section_row, $sections_disponibles, '', $errors, null, $form_data);
            return;
        }

        // Création du checkout HelloAsso
        $checkout = $this->helloasso->create_checkout($section_id, array(
            'amount'           => $montant,
            'item_name'        => $description,
            'payer_first_name' => $beneficiaire,
            'payer_last_name'  => '',
            'payer_email'      => $acheteur_email,
            'return_url'       => site_url('paiements_en_ligne/public_decouverte_confirmation?club=' . $section_id . '&txid=' . urlencode($txid)),
            'back_url'         => site_url('vols_decouverte/public_vd?section=' . $section_id),
            'error_url'        => site_url('vols_decouverte/public_vd?section=' . $section_id),
            'metadata'         => $meta,
        ));

        if (!$checkout['success']) {
            $detail = !empty($checkout['error_message']) ? $checkout['error_message'] : '';
            $errors['general'] = $this->lang->line('gvv_vd_public_error_checkout')
                . ($detail ? ' — ' . $detail : '');
            $this->_render_public_vd($section_id, $section_row, $sections_disponibles, '', $errors, null, $form_data);
            return;
        }

        if (!empty($checkout['session_id'])) {
            $this->paiements_en_ligne_model->attach_checkout_info(
                $txid,
                $checkout['session_id'],
                isset($checkout['redirect_url']) ? $checkout['redirect_url'] : null
            );
        }

        redirect($checkout['redirect_url']);
    }

    /**
     * Rend la vue publique VD avec les données construites.
     *
     * @param int         $section_id         Section sélectionnée (0 = aucune)
     * @param array|null  $section_row         Données de la section sélectionnée
     * @param array       $sections_disponibles Toutes les sections VD CB
     * @param string      $section_error        Erreur de section
     * @param array       $errors               Erreurs de validation POST
     * @param array|null  $quota_status         Statut quota (si atteint, POST)
     * @param array       $form_data            Données saisies à réafficher
     */
    private function _render_public_vd(
        $section_id,
        $section_row,
        array $sections_disponibles,
        $section_error = '',
        array $errors = array(),
        $quota_status = null,
        array $form_data = array()
    ) {
        $products    = array();
        $quota_status_get = null;
        $accueil_text = '';

        if ($section_id > 0 && $section_row && empty($section_error)) {
            $quota_status_get = $quota_status ?: get_vd_quota_status($section_id);

            if (!$quota_status_get['atteint']) {
                $today = date('Y-m-d');
                $products = $this->db->query(
                    "SELECT reference, description, prix, nb_personnes_max
                     FROM tarifs
                     WHERE club = ? AND type_ticket = 1 AND public = 1
                       AND date <= ? AND (date_fin IS NULL OR date_fin >= ?)
                     ORDER BY prix ASC",
                    array($section_id, $today, $today)
                )->result_array();
            }

            // Texte d'accueil depuis la config de la section
            $raw = $this->paiements_en_ligne_model->get_config('helloasso', 'vd_accueil_text', $section_id);
            $accueil_text = $raw ?: $this->lang->line('gvv_vd_public_default_accueil');
        }

        // Sections alternatives sans quota atteint (hors section courante)
        $sections_alternatives = array();
        foreach ($sections_disponibles as $s) {
            if ((int) $s['id'] !== $section_id && !$s['quota_status']['atteint']) {
                $sections_alternatives[] = $s;
            }
        }

        $data = array(
            'section_id'           => $section_id,
            'section_row'          => $section_row,
            'sections_disponibles' => $sections_disponibles,
            'sections_alternatives'=> $sections_alternatives,
            'section_error'        => $section_error,
            'quota_status'         => $quota_status_get,
            'products'             => $products,
            'accueil_text'         => $accueil_text,
            'errors'               => $errors,
            'form_data'            => $form_data,
            'title'                => $this->lang->line('gvv_vd_public_title'),
        );

        $this->load->view('vols_decouverte/bs_public_vd', $data);
    }
}
