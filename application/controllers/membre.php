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
 * @filesource membres.php
 * @package controllers
 */
include('./application/libraries/Gvv_Controller.php');

// First, include Requests
include(APPPATH . '/third_party/Requests.php');

// Next, make sure Requests can load internal classes
Requests::register_autoloader();

/**
 * controleur de gestion des membres.
 */
class Membre extends Gvv_Controller {
    protected $controller = 'membre';
    protected $model = 'membres_model';
    protected $kid = 'mlogin';
    protected $modification_level = 'ca'; // no edit delete buttons on list

    // régles de validation
    protected $rules = array(
        'mlogin' => 'alpha_dash'
    );
    protected $filter_variables = array(
        'filter_active',
        'filter_membre_actif',
        'filter_categorie',
        'filter_25',
        'filter_validation',
        'filter_sections'
    );

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();
        $this->load->helper('bitfields');
        $this->load->model('vols_avion_model');
        $this->load->model('vols_planeur_model');
        $this->lang->load('membre');
        $this->lang->load('events');
        $this->load->config('club');
        $this->load->helper('wsse');
        //$this->load->helper('form_elements');
    }

    /**
     * Hook activé avant la mise à jour
     */
    function pre_update($id, &$data = array()) {
        parent::pre_update($id, $data);
        if (isset($data['compte']) && $data['compte'] === '') {
            $data['compte'] = 0;
        }
    }

    /**
     * Charge les valeurs de certificats à présenter dans le formulaire
     */
    private function load_certificats($id) {
        // Load la liste des dates pour le membre
        $this->load->model('event_model');
        // 0=Autre,1=planeur,2=avion,3=ULM,4=FAI
        $this->event_model->evenement_de($id, array(
            'activite' => 0
        ), "vue_exp_autre");
        $this->event_model->evenement_de($id, array(
            'activite' => 1
        ), "vue_exp_vv");
        $this->event_model->evenement_de($id, array(
            'activite' => 2
        ), "vue_exp_avion");
        $this->event_model->evenement_de($id, array(
            'activite' => 4
        ), "vue_exp_fai");
    }

    /**
     * Affiche une page d'éléments
     *
     * @param $premier élément
     *            à afficher
     * @param
     *            message message à afficher
     */
    function page($premier = 0, $message = '', $selection = array()) {
        $this->data['action'] = VISUALISATION;
        $this->load_filter($this->filter_variables);

        $selection = $this->selection();

        // Load sections for filter
        $this->load->model('sections_model');
        $this->data['sections'] = $this->sections_model->section_list();

        // Get section filter before calling parent
        $filter_sections = $this->session->userdata('filter_sections');

        // If no filter is set, initialize with all sections checked by default
        if ($filter_sections === FALSE || $filter_sections === NULL) {
            $all_section_ids = array();
            foreach ($this->data['sections'] as $section) {
                $all_section_ids[] = $section['id'];
            }
            $this->session->set_userdata('filter_sections', $all_section_ids);
            $filter_sections = $all_section_ids;
        }

        // Apply section filter BEFORE calling parent (parent renders the view immediately)
        // Check if section filtering is active and needed
        $filter_active = $this->session->userdata('filter_active');

        if ($filter_active && !empty($filter_sections) && is_array($filter_sections)) {
            // Check if all sections are selected (no filtering needed)
            $all_section_ids = array();
            foreach ($this->data['sections'] as $section) {
                $all_section_ids[] = intval($section['id']);
            }
            $filter_section_ids = array_map('intval', $filter_sections);

            // Only filter if not all sections are selected
            $all_selected = (count($filter_section_ids) === count($all_section_ids));

            if (!$all_selected) {
                // Mark that we need to apply section filtering after fetching data
                $this->data['filter_sections_to_apply'] = $filter_section_ids;
            }
        }

        // Fetch data manually so we can filter it before rendering
        $this->data['select_result'] = $this->gvv_model->select_page(PER_PAGE, $premier, $selection);

        // Apply section filtering to the fetched data
        if (isset($this->data['filter_sections_to_apply'])) {
            $filter_section_ids = $this->data['filter_sections_to_apply'];
            $filtered_data = array();

            foreach ($this->data['select_result'] as $row) {
                if (isset($row['mlogin'])) {
                    $member_section_ids = $this->gvv_model->registered_in_sections($row['mlogin']);

                    // Include member if:
                    // 1. They have no 411 accounts (not registered in any section), OR
                    // 2. They are registered in ANY of the selected sections
                    if (empty($member_section_ids)) {
                        // Member has no 411 accounts - always show them
                        $filtered_data[] = $row;
                    } else {
                        // Member has section registrations - check if any match selected sections
                        $has_match = false;
                        foreach ($member_section_ids as $member_section_id) {
                            if (in_array($member_section_id, $filter_section_ids)) {
                                $has_match = true;
                                break;
                            }
                        }
                        if ($has_match) {
                            $filtered_data[] = $row;
                        }
                    }
                }
            }

            // Replace with filtered data
            $this->data['select_result'] = $filtered_data;

            // Update metadata db with filtered data so table() renders correctly
            $this->gvvmetadata->store_table($this->gvv_model->table, $filtered_data);
        }

        // Now call parent with filtered data already in place
        $this->data['kid'] = $this->kid;
        $this->data['controller'] = $this->controller;
        $this->data['count'] = $this->gvv_model->count();
        $this->data['premier'] = $premier;
        $this->data['message'] = $message;
        $this->data['has_modification_rights'] = (! isset($this->modification_level) || $this->dx_auth->is_role($this->modification_level, true, true));

        return load_last_view($this->table_view, $this->data, $this->unit_test);
    }

    /**
     * AJAX: Toggle member actif status (admin only)
     */
    public function ajax_toggle_actif()
    {
        header('Content-Type: application/json');

        // Admin only
        if (!$this->dx_auth->is_role('admin', true, true)) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $mlogin = $this->input->post('mlogin');
        $actif = $this->input->post('actif');

        if (empty($mlogin) || !isset($actif)) {
            echo json_encode(['success' => false, 'error' => 'Missing parameters']);
            return;
        }

        // Update the actif field
        $data = array(
            'mlogin' => $mlogin,
            'actif' => $actif ? '1' : '0'
        );

        try {
            $this->gvv_model->update('mlogin', $data, $mlogin);
            echo json_encode(['success' => true, 'actif' => $actif]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Database update failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Active ou désactive le filtrage
     */
    public function filterValidation() {
        $this->active_filter($this->filter_variables);

        // Il faut rediriger et non pas appeller $this->page, sinon l'URL
        // enregistrée pour le retour est incorrecte
        redirect($this->controller . '/page');
    }

    /**
     * Retourne la selection format ActiveData utilisable par les requêtes
     * SQL pour filtrer les données en fonction des choix faits par l'utilisateur
     * dans la section de filtrage.
     */
    function selection() {
        $this->data['filter_active'] = $this->session->userdata('filter_active');

        $selection = "";
        $year = $this->session->userdata('year');
        $date25 = date_m25ans($year);

        if ($this->session->userdata('filter_active')) {

            $filter_membre_actif = $this->session->userdata('filter_membre_actif');
            if ($filter_membre_actif) {
                $filter_membre_actif--;
                $selection .= "(actif = \"$filter_membre_actif\" )";
            }

            $filter_25 = $this->session->userdata('filter_25');

            if ($filter_25 == 1) {
                if ($selection) {
                    $selection .= " and ";
                }
                $selection .= "(mdaten >= \"$date25\" )";
            } else if ($filter_25 == 2) {
                if ($selection) {
                    $selection .= " and ";
                }
                $selection .= "(mdaten < \"$date25\" )";
            }

            $filter_categorie = $this->session->userdata('filter_categorie');
            if ($filter_categorie) {
                $categorie = $filter_categorie - 1;
                if ($selection) {
                    $selection .= " and ";
                }
                $selection .= "(categorie = \"$categorie\" )";
            }

            $filter_validation = $this->session->userdata('filter_validation');
            if ($filter_validation) {
                if ($selection) {
                    $selection .= " and ";
                }
                if ($filter_validation == 1) {
                    // En attente de validation - validation_date is null
                    $selection .= "(validation_date IS NULL )";
                } else if ($filter_validation == 2) {
                    // Validés - validation_date is not null
                    $selection .= "(validation_date IS NOT NULL )";
                }
            }
        }

        if ($selection == "")
            $selection = array();

        return $selection;
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::edit()
     */
    function edit($id = '', $load_view = true, $action = MODIFICATION) {
        if (func_num_args() == 0) {
            $id = $this->gvv_model->default_id();
            // echo "id=$id<br>";
            if ($id == '') {
                $data = array();
                $data['title'] = $this->lang->line("gvv_error");
                $data['text'] = $this->lang->line("membre_error_no_file");
                load_last_view('message', $data);
                return;
            }
        } else {
            $id = urldecode(func_get_arg(0));
        }

        $non_existing = false;
        try {
            parent::edit($id, FALSE);
        } catch (Exception $e) {
            // echo 'Exception reçue : ', $e->getMessage(), "\n";
            $non_existing = true;
        }
        $non_existing = $non_existing || (! array_key_exists('mnom', $this->data));
        if ($non_existing) {
            $data = array();
            $data['title'] = $this->lang->line("gvv_error");
            $data['text'] = $id . " " . $this->lang->line("membre_error_unknow");
            load_last_view('message', $data);
            return;
        }

        $this->data['mniveau'] = int2array($this->data['mniveaux']);
        $this->data['macce'] = int2array($this->data['macces']);

        $this->load->model('comptes_model');
        $this->load->model('sections_model');
        $sections = $this->sections_model->section_list();
        $member_sections = array();
        foreach ($sections as $section) {
            $account = $this->comptes_model->get_by_pilote_codec($this->data['mlogin'], '411', $section['id']);
            if ($account) {
                $member_sections[] = $section;
            }
        }
        $this->data['member_sections'] = $member_sections;
        $this->data['has_sections'] = count($sections) > 0;


        $this->load_certificats($id);

        // affiche le formulaire
        return load_last_view($this->form_view, $this->data, $this->unit_test);
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::form2database()
     */
    function form2database($action = '') {
        $processed_data = parent::form2database($action);
        $processed_data["mniveaux"] = array2int($this->input->post('mniveau'));
        $processed_data["macces"] = array2int($this->input->post('macce'));
        unset($processed_data["mniveau"]);
        unset($processed_data["macce"]);

        // unset date fields
        foreach (
            array(
                // 'mbradat',
                // 'mbraval',
                // 'mbrpdat',
                // 'mbrpval',
                'licfed',
                'membre_payeur',  // FK field must be NULL, not empty string
                'mdaten'
            )
            // 'dateinstavion',
            // 'dateivv',
            // 'medical',
            // 'vallicencefed'
            as $field
        ) {
            if ($processed_data[$field] == '')
                unset($processed_data[$field]);
        }
        if (!$processed_data['photo']) unset($processed_data['photo']);
        return $processed_data;
    }

    /**
     * Affiche une page d'éléments
     *
     * @param
     *            $premier
     */
    function trombinoscope($premier = 0, $message = '') {
        $this->page($premier, $message);
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::form_static_element()
     */
    function form_static_element($action = MODIFICATION) {
        $this->data['mniveau'] = array();
        // Utilisé seulement pour les certificats
        $this->data['has_modification_rights'] = $this->dx_auth->is_role('ca', true, true);
        parent::form_static_element($action);
        $this->load->model('comptes_model');
        if ($this->dx_auth->is_role('ca', true, true)) {
            $this->data['pilote_selector'] = $this->membres_model->selector();
        }

        // Set up member selector for membre_payeur field
        // This allows selecting which member's account should be charged
        $membre_payeur_selector = $this->membres_model->selector_with_null(array('actif' => 1));
        $this->gvvmetadata->set_selector('membre_payeur_selector', $membre_payeur_selector);

        if ($action != CREATION)
            $this->data['compte_pilote'] = $this->comptes_model->compte_pilote_id($this->data['mlogin']);

        $this->data['cp'] = sprintf("%05d", $this->data['cp']);
        // For compte_ticket, use membre_payeur if set, otherwise use member's own login
        if (isset($this->data['membre_payeur']) && $this->data['membre_payeur']) {
            $this->data['compte_ticket'] = $this->data['membre_payeur'];
        } else {
            $this->data['compte_ticket'] = $this->data['mlogin'];
        }
        $this->gvvmetadata->set_selector('inst_glider_selector', $this->membres_model->qualif_selector('mlogin', ITP | IVV));
        $this->gvvmetadata->set_selector('inst_airplane_selector', $this->membres_model->qualif_selector('mlogin', FE_AVION | FI_AVION));
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::post_create()
     */
    function post_create($data = array()) {
        if (! $data['compte']) {
            // Creation du compte comptable
            $id = $data['mlogin'];
            $section = $this->membres_model->section();
            if ($section)
                $section_id = $section['id'];
            else {
                $section_id = null;
                gvv_error("Nos section selected while creating a account for $id");
            }

            $cpt = array(
                'nom' => $data['mnom'] . " " . $data['mprenom'],
                'pilote' => $id,
                'desc' => "Compte client 411 " . $section['nom'] . " " . $data['mnom'] . " " . $data['mprenom'],
                'codec' => 411,
                'actif' => 1,
                'debit' => 0.0,
                'credit' => 0.0,
                'club' => $section_id,
                'saisie_par' => $this->dx_auth->get_username()
            );
            $this->load->model('comptes_model');
            if (!$this->comptes_model->get_by_pilote_codec($cpt['pilote'], $cpt['codec'], $section_id)) {
                $this->comptes_model->create($cpt);
            }

            // Et un second sur le compte général 
            $section_general = $this->config->item('section_general');
            if ($section_general) {
                $cpt['club'] = $section_general;
                $cpt['desc'] = "Compte client 411 général " . $data['mnom'] . " " . $data['mprenom'];
                if (!$this->comptes_model->get_by_pilote_codec($cpt['pilote'], $cpt['codec'], $cpt['club'])) {
                    $this->comptes_model->create($cpt);
                }
            }
        }

        // Creation de l'utilisateur
        if (! $this->dx_auth->is_username_available($data['mlogin'])) {
            // echo "l'utilisateur " . $data['mlogin'] . " existe déjà" . br();
            return;
        }
        if (! $user = $this->dx_auth->register($data['mlogin'], $data['mlogin'], $data['memail'])) {
            gvv_error("Erreur sur la création de l'utilisateur");
        }
    }

    /**
     * Affiche la liste des licenciés
     */
    function licences($premier = 0, $message = '') {
        $this->push_return_url("licences");

        $data['select_result'] = $this->gvv_model->select_licences($this->session->userdata('per_page'), $premier);
        $data['kid'] = $this->kid;

        $data['controller'] = $this->controller;
        $data['has_modification_rights'] = (! isset($this->modification_level) || $this->dx_auth->is_role($this->modification_level, true, true));
        return load_last_view("membre/licences", $data, $this->unit_test);
    }

    /**
     * Export au format CSV
     *
     * @param
     *            $premier
     */
    function export($mode = "pdf") {
        if ($mode == 'pdf') {
            redirect(controller_url("rapports/licences"));
        }

        $results = $this->gvv_model->select_licences();
        $attrs = array(
            'numbered' => 1,
            'fields' => array(
                'mnom',
                'mprenom',
                'madresse',
                'cp',
                'ville',
                'mdaten'
            )
        );
        $this->gvvmetadata->csv("membres", $attrs);
    }

    /**
     *
     *
     * Redirection
     *
     * @param unknown_type $id
     */
    function certificats($id) {
        redirect("event/page/$id");
    }

    /**
     * Validation du formulaire avec gestion améliorée de l'upload de photo
     *
     * Amélioration par rapport à l'ancienne version:
     * - Upload dans uploads/photos/ au lieu de uploads/
     * - Nom de fichier: random_mlogin.png
     * - Compression automatique avec File_compressor
     * - Suppression de l'ancienne photo si elle existe
     */
    public function formValidation($action, $return_on_success = false) {
        $mlogin = $this->input->post('mlogin');

        // Vérifier si un fichier a été uploadé
        if (isset($_FILES['userfile']) && $_FILES['userfile']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Configuration de l'upload
            $dirname = './uploads/photos/';
            if (!file_exists($dirname)) {
                mkdir($dirname, 0777, true);
                chmod($dirname, 0777);
            }

            // Générer un nom de fichier: random_mlogin.png
            $random = rand(100000, 999999);
            $storage_file = $random . '_' . $mlogin . '.png';

            $config['upload_path'] = $dirname;
            $config['allowed_types'] = 'jpg|jpeg|png|gif|webp';
            $config['max_size'] = '10000'; // 10MB
            $config['file_name'] = $storage_file;
            $config['overwrite'] = true;

            $this->load->library('upload', $config);

            if (!$this->upload->do_upload('userfile')) {
                // Erreur d'upload
                $this->data['message'] = '<div class="text-danger">' . $this->upload->display_errors() . '</div>';
                $this->form_static_element($action);
                load_last_view($this->form_view, $this->data);
                return;
            } else {
                // Upload réussi
                $upload_data = $this->upload->data();
                $file_path = $dirname . $storage_file;

                // Supprimer l'ancienne photo si elle existe
                $membre = $this->gvv_model->get_by_id('mlogin', $mlogin);
                if ($membre && !empty($membre['photo'])) {
                    $old_photo_path = './uploads/photos/' . $membre['photo'];
                    if (file_exists($old_photo_path)) {
                        unlink($old_photo_path);
                    }
                    // Vérifier aussi dans l'ancien emplacement (uploads/ racine)
                    $old_photo_path_legacy = './uploads/' . $membre['photo'];
                    if (file_exists($old_photo_path_legacy)) {
                        unlink($old_photo_path_legacy);
                    }
                }

                // Compression automatique (Phase 2)
                $this->load->library('file_compressor');
                $compression_result = $this->file_compressor->compress($file_path, array(
                    'max_width' => 1600,
                    'max_height' => 1200,
                    'quality' => 85
                ));

                if ($compression_result['success']) {
                    log_message('info', "Member photo compressed: " . basename($file_path) .
                               " - Ratio: " . round($compression_result['stats']['compression_ratio'] * 100) . "%");
                } else {
                    log_message('info', "Member photo compression skipped: " . $compression_result['error']);
                }

                // Mettre à jour la base de données avec le nom de fichier
                $data = array(
                    'photo' => $storage_file
                );
                $this->gvv_model->update(array(
                    'mlogin' => $mlogin
                ), $data);

                redirect("membre/edit/$mlogin");
            }
        }

        // Continuer avec la validation normale
        $this->load_certificats($mlogin);
        parent::formValidation($action);
    }

    /**
     * Fonction temporaire de migration des dates de certificats
     */
    function export_certificats() {
        $this->gvv_model->export();
    }

    /**
     * Imprime une cellule
     *
     * @param unknown_type $pdf
     * @param unknown_type $string
     */
    function pdf_cell($pdf, $txt) {
        $height = 5;
        $char_width = 1.5;
        $len = strlen($txt);
        $border = 0;
        $ln = 1;
        $fill = 0;
        $align = 'L';
        $link = "";

        // Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
        $pdf->Cell($len * $char_width, $height, $txt, $border, $ln, $align, $fill, $link);
    }

    /**
     * Display a line in a pdf document
     *
     * @param unknown_type $pdf
     * @param unknown_type $txt
     * @param unknown_type $margin
     */
    function pdf_line($pdf, $txt, $margin = 0) {
        $pdf->SetX($margin);
        $this->pdf_cell($pdf, $txt);
    }

    /**
     * Imprime le bulletin d'hhesion à faire signer
     *
     * @param unknown_type $mlogin
     * @param $is_subscription: TRUE:
     *            imprime la demande d'adhésion, FALSE: imprime la fiche pilote
     */
    function adhesion($mlogin = '', $is_subscription = FALSE) {
        $this->load->helper('validation');

        if ($mlogin != "") {
            parent::edit($mlogin, false);
        }
        if (isset($this->data['mniveau'])) {
            $this->data['mniveau'] = int2array($this->data['mniveaux']);
            $this->data['macce'] = int2array($this->data['macces']);
        }
        $this->load->model('comptes_model');
        $this->load_certificats($mlogin);

        $this->load->library('Pdf');
        $pdf = new Pdf();
        $pdf->AddPage();

        if ($is_subscription) {
            $pdf->title($this->lang->line("membre_title_subscription"), 1);
        } else {
            $pdf->title($this->lang->line("membre_title_perso"), 1);
        }

        // photo
        if ($this->data['photo']) {
            // Essayer le nouveau chemin d'abord
            $photofile = "./uploads/photos/" . $this->data['photo'];
            if (!file_exists($photofile)) {
                // Essayer l'ancien emplacement pour compatibilité
                $photofile = "./uploads/" . $this->data['photo'];
            }
            if (file_exists($photofile)) {
                $pdf->Image($photofile, 10, 40, 50);
            }
        } else {
            $pdf->cell(40, 50, $this->lang->line("membre_sheet_photo"), true, false, "C");
        }

        $photo_margin = 70;
        if ($is_subscription) {
            $this->pdf_line($pdf, $this->lang->line("membre_sheet_undersigned"), $photo_margin);
        }

        $txt = $this->lang->line("membre_sheet_name_and_firstanme") . ": " . $this->data['mnom'] . " " . $this->data['mprenom'];
        $this->pdf_line($pdf, $txt, $photo_margin);

        $txt = $this->lang->line("membre_sheet_address") . ": " . $this->data['madresse'] . " " . $this->data['cp'];
        $txt .= ", " . $this->data['ville'];
        $this->pdf_line($pdf, $txt, $photo_margin);

        $txt = $this->lang->line("membre_sheet_birthdate") . ": " . date_db2ht($this->data['mdaten']);
        $this->pdf_line($pdf, $txt, $photo_margin);

        $txt = $this->lang->line("membre_sheet_occupation") . ": " . $this->data['profession'];
        $this->pdf_line($pdf, $txt, $photo_margin);

        $txt = $this->lang->line("membre_sheet_telephone") . ": " . $this->data['mtelf'] . ", " . $this->lang->line("membre_sheet_mobile") . ": " . $this->data['mtelm'];
        $this->pdf_line($pdf, $txt, $photo_margin);

        $txt = $this->lang->line("membre_sheet_email") . ": " . $this->data['memail'];
        $this->pdf_line($pdf, $txt, $photo_margin);

        $pdf->SetXY(10, 100);
        $pdf->title($this->lang->line("membre_title_licences"), 4);

        // Load la liste des dates pour le membre
        $this->load->model('event_model');
        // 0=Autre,1=planeur,2=avion,3=ULM,4=FAI
        $events = $this->event_model->evenement_de($mlogin, array(), "vue_exp_aero");

        $this->gvvmetadata->pdf_table("events", $events, $pdf, array(
            'width' => array(
                50,
                30,
                30
            ),
            'fields' => array(
                'event_type',
                'date',
                'comment'
            )
        ));

        // $txt1 = $this->lang->line("membre_sheet_bia") . ": "
        // . $this->lang->line("membre_sheet_yes_no") . ", "
        // . $this->lang->line("membre_sheet_year") . ": . . . .";
        // $this->pdf_line($pdf, $txt1 , 10);

        // $txt2 = $this->lang->line("membre_sheet_glider_license") . " " . $this->data['mbrpnum']
        // . " " . $this->lang->line("membre_sheet_date") . " " . $this->data['mbrpdat']
        // . " " . $this->lang->line("membre_sheet_validity") ." " . $this->data['mbrpval'];
        // $this->pdf_line($pdf, $txt2 , 10);

        // $txt2 = "BB N° " . line_of (".", 40) . " du " . line_of (".", 40) . " validité " . line_of (".", 40);
        // $this->pdf_line($pdf, $txt2 , 10);

        // $txt2 = "PPL ou TT N° " . $this->data['mbranum'] . " du " . $this->data['mbradat'] . " validité " . $this->data['mbraval'];
        // $this->pdf_line($pdf, $txt2 , 10);

        $pdf->Ln(5);

        $comment = isset($this->data['comment']) ? $this->data['comment'] : '';

        $pdf->title($this->lang->line("membre_sheet_info"), 4);

        $pdf->cell(0, 50, $comment, true, false);
        $pdf->Ln(55);

        if ($is_subscription) {

            $club = ($this->config->item('nom_complet')) ? $this->config->item('nom_complet') : $this->config->item('nom_club');
            $this->pdf_line($pdf, $this->lang->line("membre_sheet_demande") . " " . $club, 10);

            $pdf->Ln();
            $this->pdf_line($pdf, $this->lang->line("membre_sheet_date") . " " . date("d/m/Y"), 150);
            $this->pdf_line($pdf, "Signature : ", 150);

            $pdf->AddPage();

            $content = $this->lang->line("membre_subscription_information");
            foreach ($content as $section) {
                $title = $section['title'];
                $txt = $section['text'];
                $txt = str_replace('$club', $club, $txt);

                if ($title) {
                    $pdf->title($title, 4);
                }

                foreach (explode("\n", $txt) as $line) {
                    $this->pdf_line($pdf, $line, 10);
                }

                $pdf->Ln(15);
            }
        }
        $pdf->Output();
    }

    /**
     * Ouvre le formulaire de saisie pré-rempli avec les indormations du licencié
     * @param unknown $licence_number
     */
    function heva_create($licence_number) {

        $request = heva_request("/persons/$licence_number");

        if (!$request->success) {
            echo "status_code = " . $request->status_code . br();
            echo "success = " . $request->success . br();
            return;
        }

        $result = json_decode($request->body, true);
        // var_dump($result);

        // membre/create
        $this->data = $this->gvvmetadata->defaults_list('membres');
        // var_dump($this->data);
        $this->form_static_element(CREATION);

        // var_dump($this->data);
        $this->data['controller'] = 'membre';
        $this->data['action'] = CREATION;

        $this->data['mlogin'] = strtolower(substr($result['first_name'], 0, 1) . $result['last_name']);
        $this->data['mprenom'] = $result['first_name'];
        $this->data['mnom'] = ucwords(strtolower($result['last_name']));
        $this->data['licfed'] = $result['licence_number'];
        $this->data['mdaten'] = $result['date_of_birth'];
        if (isset($result['comment']))
            $this->data['comment'] = $result['comment'];
        if (isset($result['insee_category']))
            $this->data['profession'] = $result['insee_category'];
        if (isset($result['email']['value']))
            $this->data['memail'] = $result['email']['value'];
        if (isset($result['mobile']['value']))
            $this->data['mtelm'] = $result['mobile']['value'];
        if (isset($result['phone']['value']))
            $this->data['mtelf'] = $result['phone']['value'];

        if (isset($result['address']['address']))
            $this->data['madresse'] = ucwords(strtolower($result['address']['address']));

        if (isset($result['address']['postal_code']))
            $this->data['cp'] = $result['address']['postal_code'];
        if (isset($result['address']['city']))
            $this->data['ville'] = ucwords(strtolower($result['address']['city']));
        if (isset($result['address']['country']))
            $this->data['pays'] = $result['address']['country'];

        $this->data['msexe'] = ($result['civility'] == 'M.') ? "M" : "F";
        //$this->data['m25ans'] = ($result['date_of_birth'] == 'M.') ? 1 : 0;

        return load_last_view('membre/formView', $this->data, false);
    }

    /**
     * Associe un numéro de licence avec un pilote
     * @param unknown $mlogin
     * @param unknown $licence_number
     */
    function associe_licence($licence_number, $image) {

        // Verifie que le numéro n'est pas déjà affecté

        // va chercher la liste des membres qui aurait déjà ce numéro de licence
        if (false) {
            // la liste est non vide

            // message = Ce numéro de licence est déjà affecté aux membres X, Y et Z
            // Corrigez la situation en éditant les fiches pilotes manuellement
            return;
        }

        $data['title'] = "Association d'un numéro de licence avec un membre";
        $data['licence_number'] = $licence_number;
        $data['image'] = urldecode($image);
        $data['selector'] = $this->membres_model->selector(array('licfed' => NULL));

        return load_last_view("membre/associe", $data, $this->unit_test);
    }

    /**
     * Affecte le numéro de licence d'un membre
     * @param unknown $licence_number
     * @param unknown $mlogin
     */
    function associe($licence_number) {
        $mlogin = $this->input->post('mlogin');

        // echo "associe($licence_number, $mlogin) " . br();
        $this->gvv_model->update(
            'mlogin',
            array('licfed' => $licence_number),
            $mlogin
        );

        redirect("FFVV/licences");
    }

    /**
     * Supprime la photo d'un membre
     * @param string $mlogin Login du membre
     */
    function delete_photo($mlogin) {

        $membre = $this->gvv_model->get_by_id('mlogin', $mlogin);
        $photo = $membre['photo'];

        // Essayer de supprimer dans le nouveau dossier d'abord
        $filename = './uploads/photos/' . $photo;
        if (file_exists($filename)) {
            unlink($filename);
        } else {
            // Essayer l'ancien emplacement pour compatibilité
            $filename_legacy = './uploads/' . $photo;
            if (file_exists($filename_legacy)) {
                unlink($filename_legacy);
            }
        }

        // Met à jour la base de données pour supprimer la référence à la photo
        $this->gvv_model->update(
            'mlogin',
            array('photo' => NULL),
            $mlogin
        );

        // Redirige vers la page du membre
        redirect("membre/edit/$mlogin");
    }


    /**
     * Override delete to handle validation errors from model
     */
    function delete($id) {
        $this->lang->load('membres');
        
        // Call pre_delete hook
        $this->pre_delete($id);
        
        // Try to delete - model will return FALSE if blocked by references
        $result = $this->gvv_model->delete(array(
            $this->kid => $id
        ));
        
        // Check if deletion was successful
        if ($result === TRUE) {
            // Set success message
            $this->session->set_flashdata('success', $this->lang->line('membre_delete_success'));
        }
        // If deletion failed, error message is already set by the model
        // Don't override it!
        
        // Return to list page
        $this->pop_return_url();
        redirect($this->controller . "/page");
    }

    /**
     * Synchronise les noms des comptes 411 avec les membres anonymisés
     * Corrige les incohérences après anonymisation
     * Gère TOUTES les relations : membres.compte ET comptes.pilote
     */
    public function sync_accounts() {
        // SÉCURITÉ CRITIQUE: Fonction uniquement disponible en mode développement
        if (ENVIRONMENT !== 'development') {
            $data['msg'] = "Les fonctions d'anonymisation ne sont disponibles qu'en mode développement.";
            load_last_view('error', $data);
            return;
        }

        if (!$this->dx_auth->is_role('ca')) {
            $this->dx_auth->deny_access();
            return;
        }

        $this->load->model('comptes_model');
        
        // Méthode 1: Récupérer tous les membres avec des comptes 411 (relation membres.compte → comptes.id)
        $this->db->select('m.mlogin, m.mnom, m.mprenom, m.compte, c.codec');
        $this->db->from('membres m');
        $this->db->join('comptes c', 'm.compte = c.id', 'inner');
        $this->db->where('c.codec LIKE', '411%');
        $this->db->where('m.compte IS NOT NULL');
        $this->db->where('m.compte >', 0);
        $query1 = $this->db->get();
        $results1 = $query1->result_array();
        
        // Méthode 2: Récupérer tous les comptes 411 qui référencent des membres (relation comptes.pilote → membres.mlogin)
        $this->db->select('m.mlogin, m.mnom, m.mprenom, c.id as compte_id, c.codec');
        $this->db->from('comptes c');
        $this->db->join('membres m', 'c.pilote = m.mlogin', 'inner');
        $this->db->where('c.codec LIKE', '411%');
        $this->db->where('c.pilote IS NOT NULL');
        $this->db->where('c.pilote !=', '');
        $query2 = $this->db->get();
        $results2 = $query2->result_array();
        
        $count_updated = 0;
        $compte_updates = array(); // Pour éviter les doublons
        
        // Traiter les relations membres.compte → comptes.id
        foreach ($results1 as $row) {
            $compte_id = $row['compte'];
            $nouveau_nom = $row['mnom'] . ' ' . $row['mprenom'];
            $nouvelle_desc = 'Compte client ' . $row['mnom'] . ' ' . $row['mprenom'];
            
            if (!isset($compte_updates[$compte_id])) {
                $data_update_compte = array(
                    'id' => $compte_id,
                    'nom' => $nouveau_nom,
                    'desc' => $nouvelle_desc
                );
                
                $this->comptes_model->update('id', $data_update_compte);
                $compte_updates[$compte_id] = $nouveau_nom;
                $count_updated++;
            }
        }
        
        // Traiter les relations comptes.pilote → membres.mlogin (PLUS IMPORTANT)
        foreach ($results2 as $row) {
            $compte_id = $row['compte_id'];
            $nouveau_nom = $row['mnom'] . ' ' . $row['mprenom'];
            $nouvelle_desc = 'Compte client ' . $row['mnom'] . ' ' . $row['mprenom'];
            
            if (!isset($compte_updates[$compte_id])) {
                $data_update_compte = array(
                    'id' => $compte_id,
                    'nom' => $nouveau_nom,
                    'desc' => $nouvelle_desc
                );
                
                $this->comptes_model->update('id', $data_update_compte);
                $compte_updates[$compte_id] = $nouveau_nom;
                $count_updated++;
            }
        }
        
        // Message de succès
        $data['title'] = "Synchronisation réussie";
        $data['text'] = "$count_updated comptes 411 ont été synchronisés avec les noms des membres anonymisés. " .
                       "Relations traitées: membres.compte ET comptes.pilote.";
        load_last_view('message', $data);
    }

}
