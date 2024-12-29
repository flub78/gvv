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
        'filter_25'
    );

    /**
     * Constructor
     *
     * Affiche header et menu
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
        parent::page($premier, $message, $selection);
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
        $compte_selector = $this->comptes_model->selector_with_null(array(
            'codec' => 411
        ));
        $this->gvvmetadata->set_selector('compte_pilote_selector', $compte_selector);
        if ($action != CREATION)
            $this->data['compte_pilote'] = $this->comptes_model->compte_pilote($this->data['mlogin']);

        $this->data['cp'] = sprintf("%05d", $this->data['cp']);
        if ($this->data['compte']) {
            $this->load->model('comptes_model');
            $compte_info = $this->comptes_model->get_by_id('id', $this->data['compte']);
            $this->data['compte_ticket'] = $compte_info['pilote'];
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
            $cpt = array(
                'nom' => $data['mnom'] . " " . $data['mprenom'],
                'pilote' => $id,
                'desc' => "Compte pilote",
                'codec' => 411,
                'actif' => 1,
                'debit' => 0.0,
                'credit' => 0.0,
                'saisie_par' => $this->dx_auth->get_username()
            );
            $this->load->model('comptes_model');
            $this->comptes_model->create($cpt);
        }

        // Creation de l'utilisateur
        if (! $this->dx_auth->is_username_available($data['mlogin'])) {
            // echo "l'utilisateur " . $data['mlogin'] . " existe déjà" . br();
            return;
        }
        if (! $user = $this->dx_auth->register($data['mlogin'], $data['mlogin'], $data['memail'])) {
            echo "Erreur sur la création de l'utilisateur<br>";
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
     */
    public function formValidation($action, $return_on_success = false) {
        // echo "formValidation($action)" . br(); exit;
        $mlogin = $this->input->post('mlogin');
        $upload = $this->gvvmetadata->upload("membres");
        if ($upload[1]) {
            // erreur
            $this->data['message'] = '<div class="text-danger">' . $upload[1] . '</div>';

            $this->form_static_element($action);
            load_last_view($this->form_view, $this->data);
            // show_error($upload[1]);
        }

        // pas d'erreur
        if ($newfile = $upload[0]) {
            // Un fichier a été chargé
            $photo = $this->input->post('photo');
            if (file_exists("uploads/$photo"))
                unlink("uploads/$photo");
            // update the file name
            $data = array(
                'photo' => $newfile
            );
            $this->gvv_model->update(array(
                'mlogin' => $mlogin
            ), $data);
            redirect("membre/edit/$mlogin");
        }
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
            $photofile = "./assets/uploads/" . $this->data['photo'];
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
     * Test unitaire
     */
    function test($format = "html") {
        // parent::test($format);
        $this->unit_test = TRUE;
        $this->load->library('unit_test');

        $this->unit->run(true, true, "Tests $this->controller");
        $this->tests_results($format);
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
}
