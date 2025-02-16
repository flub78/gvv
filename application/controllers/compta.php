<?php

/**
 *
 *    GVV Gestion vol à voile
 *    Copyright(C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @filesource compta.php
 * @packages controllers
 *
 * Controleur de gestion des écritures comptables
 */
include('./application/libraries/Gvv_Controller.php');
class Compta extends Gvv_Controller {
    protected $controller = 'compta';
    protected $model = 'ecritures_model';
    protected $modification_level = 'tresorier';
    protected $rules = array();

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();
        // Check if user is logged in or not
        if (!$this->dx_auth->is_logged_in()) {
            redirect("auth/login");
        }
        $this->load->model('comptes_model');
        $this->load->model('tarifs_model');
        $this->load->model('categorie_model');
        $this->load->model('attachments_model');
        $this->lang->load('compta');
        $this->lang->load('attachments');
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::edit()
     */
    function edit($id = "", $load_view = true, $action = MODIFICATION) {
        $this->data = $this->gvv_model->get_by_id($this->kid, $id);
        $this->session->set_userdata('back_url', current_url());

        if ($this->data['achat']) {
            redirect("achats/edit/" . $this->data['achat']);
            return;
        }

        $this->push_return_url("edit ecriture");

        if ($this->data['gel']) {
            $this->form_static_element(VISUALISATION);
        } else {
            $this->form_static_element(MODIFICATION);
        }

        $this->attachments_model->select_page(
            0,
            0,
            ['referenced_table' => 'ecritures', 'referenced_id' => $id]
        );
        $this->data[$this->kid] = $id;
        return load_last_view($this->form_view, $this->data, $this->unit_test);
    }

    /**
     */
    function to_hash($select, &$total_actif, &$total_passif) {
        $data = array();
        foreach ($select as $row) {
            $actif = $row['actif'];
            $nom = $row['nom'];

            if ($actif) {
                $solde = $row['debit'] - $row['credit'];
                $data[$nom] = euro($solde);
                $total_actif += $solde;
            } else {
                $solde = $row['credit'] - $row['debit'];
                $data[$nom] = euro($solde);
                $total_passif += $solde;
            }
        }
        return $data;
    }

    /**
     * Génération des éléments à passer au formulaire en cas de création,
     * modification ou réaffichage après erreur.
     *
     * @param $action CREATION
     *            | VISUALISATION | MODIFICATION
     */
    protected function form_static_element($action) {
        parent::form_static_element($action);

        $this->data['title_key'] = "gvv_compta_title_line";
        $this->gvvmetadata->set_selector('compte1_selector', $this->comptes_model->selector());
        $this->gvvmetadata->set_selector('compte2_selector', $this->comptes_model->selector());
        $this->data['date_creation'] = date("d/m/Y");

        $this->data['saisie_par'] = $this->dx_auth->get_username();
        $this->data['categorie_selector'] = $this->categorie_model->selector_with_null();
        $this->gvvmetadata->set_selector('categorie_selector', $this->categorie_model->selector_with_null());
    }

    /**
     *
     * Supprime un élèment
     *
     * @param $id clé
     */
    function delete($id) {
        $this->data = $this->gvv_model->get_by_id($this->kid, $id);

        if ($this->data['achat']) {
            redirect("achats/delete/" . $this->data['achat']);
            return;
        }

        $this->load->model('ecritures_model');
        $this->ecritures_model->delete_ecriture($id);

        $this->pop_return_url();
    }

    /**
     * Modification d'une ecriture comptable.
     * Annule la version précédente
     * avent de remettre à jour la valeur.
     *
     * @param unknown_type $data
     *            hash enregistrement
     */
    private function change_ecriture($data) {
        $this->db->trans_start();

        // Annule l'écritue précédente
        $id = $data['id'];
        $previous = $this->gvv_model->get_by_id('id', $id);

        $previous_compte1 = $previous['compte1'];
        $previous_compte2 = $previous['compte2'];
        $previous_montant = $previous['montant'];
        $this->comptes_model->maj_comptes($previous_compte1, $previous_compte2, -$previous_montant);

        $this->gvv_model->update_ecriture('id', $data);

        $this->db->trans_complete();
    }

    /**
     * Validation callback to check that compte1 and compte2 are different
     * 
     * @return boolean True if accounts are different, false if they are the same
     */
    public function check_compte1_compte2() {
        $compte1 = $this->input->post('compte1');
        $compte2 = $this->input->post('compte2');

        if ($compte1 === $compte2) {
            $this->form_validation->set_message(
                'check_compte1_compte2',
                $this->lang->line('gvv_compta_error_same_accounts')
            );
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Validation du formulaire de passage d'écriture.
     * Il est spécifique dans le sens ou il doit enregistrer l'écriture et modifier
     * les soldes de façon atomique(transaction)
     *
     * @param $action CREATION
     *            | VISUALISATION | MODIFICATION
     */
    public function formValidation($action, $return_on_success = false) {
        $button = $this->input->post('button');

        if ($button == "Abandonner") {
            redirect("welcome");
        } elseif ($button == "Supprimer") {
            $id = $this->input->post($this->kid);
            $this->delete($id);
            return;
        }

        // Validates the form entries
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');

        $table = $this->gvv_model->table();
        $fields_list = $this->gvvmetadata->fields_list($table);
        foreach ($fields_list as $field) {
            $this->data[$field] = $this->input->post($field);
        }

        $this->gvvmetadata->set_rules($table, $fields_list, $this->rules, $action);

        // Add to the form validation rules a rule to prevent the valous of compte1 and compte2 to be the same.
        $this->form_validation->set_rules('compte1', 'Compte 1', 'callback_check_compte1_compte2');
        # $this->form_validation->set_rules('compte2', 'Compte 2', 'callback_check_compte1_compte2');

        if ($this->form_validation->run()) {
            // get the processed data. It must not be done before because all the
            // processing is done by the run method.
            $processed_data = $this->form2database($action);

            if ($action == CREATION) {
                unset($processed_data['id']);
                $id = $this->gvv_model->create_ecriture($processed_data);
                $this->data['popup'] = "Ecriture passée avec succés";
                if ($button != "Créer") {
                    $image = $this->gvv_model->image($id);
                    $msg = "Ecriture $image créée avec succés.";
                    $this->session->set_flashdata('popup', $msg);
                    redirect($this->session->userdata('current_url'));
                } else {
                    redirect("compta");
                }
            } else {
                $this->change_ecriture($processed_data);
                $this->pop_return_url(1);
            }
        }
        // Display the form again
        $this->form_static_element($action);
        load_last_view($this->form_view, $this->data);
    }

    /**
     * Ecriture entre un compte de charge et un compte de banque
     */
    function depenses() {
        parent::create(FALSE);
        $this->session->set_userdata('current_url', current_url());

        $this->data['title_key'] = "gvv_compta_title_depense";

        $where = array(
            "codec >=" => "6",
            'codec <' => "7"
        );
        $this->gvvmetadata->set_selector('compte1_selector', $this->comptes_model->selector($where));

        $where = array(
            "codec >=" => "5",
            'codec <' => "6"
        );
        $this->gvvmetadata->set_selector('compte2_selector', $this->comptes_model->selector($where));

        load_last_view('compta/formView', $this->data);
    }

    /**
     * La saisie d'une recette est juste le passage d'une écriture mais uniquement
     * sur un compte de produit.
     */
    function recettes() {
        parent::create(FALSE);
        $this->session->set_userdata('current_url', current_url());
        $this->data['title_key'] = "gvv_compta_title_recette";

        $where = array(
            "codec >=" => "5",
            'codec <' => "6"
        );
        $this->gvvmetadata->set_selector('compte1_selector', $this->comptes_model->selector($where));

        $where = array(
            "codec >=" => "7",
            'codec <' => "8"
        );
        $this->gvvmetadata->set_selector('compte2_selector', $this->comptes_model->selector($where));

        load_last_view('compta/formView', $this->data);
    }

    /**
     * La facturation pilote est une opération entre un compte client et un
     * compte produit
     */
    function factu_pilote() {
        parent::create(FALSE);
        $this->session->set_userdata('current_url', current_url());
        $this->data['title_key'] = "gvv_compta_title_manual";
        $this->data['message'] = $this->lang->line("gvv_compta_message_advice_manual");

        $where = array(
            "codec =" => "411"
        );
        $this->gvvmetadata->set_selector('compte1_selector', $this->comptes_model->selector($where));

        $where = array(
            "codec >=" => "7",
            'codec <' => "8"
        );
        $this->gvvmetadata->set_selector('compte2_selector', $this->comptes_model->selector($where));

        load_last_view('compta/formView', $this->data);
    }

    /**
     * Credit d'un compte pilote à partir d'un compte de charge
     */
    function credit_pilote() {
        parent::create(FALSE);
        $this->session->set_userdata('current_url', current_url());
        $this->data['title_key'] = "gvv_compta_title_remboursement";

        $where = array(
            "codec >=" => "6",
            'codec <' => "7"
        );
        $this->gvvmetadata->set_selector('compte1_selector', $this->comptes_model->selector($where));

        $where = "codec = '411'";
        $this->gvvmetadata->set_selector('compte2_selector', $this->comptes_model->selector($where));

        load_last_view('compta/formView', $this->data);
    }

    /**
     * Le reglement pilote est une opération entre un compte pilote et un compte
     * de caisse.
     */
    function reglement_pilote() {
        parent::create(FALSE);
        $this->session->set_userdata('current_url', current_url());
        $this->data['title_key'] = "gvv_compta_title_paiement";

        $where = array(
            "codec >=" => "5",
            'codec <' => "6"
        );
        $this->gvvmetadata->set_selector('compte1_selector', $this->comptes_model->selector($where));

        $where = array(
            "codec" => "411"
        );
        $this->gvvmetadata->set_selector('compte2_selector', $this->comptes_model->selector($where));

        load_last_view('compta/formView', $this->data);
    }

    /**
     * Remboursement avance pilote est une opération entre un compte pilote et un compte
     * de caisse.
     */
    function debit_pilote() {
        parent::create(FALSE);
        $this->session->set_userdata('current_url', current_url());
        $this->data['title_key'] = "gvv_compta_title_avance";

        $where = array(
            "codec" => "411"
        );
        $this->gvvmetadata->set_selector('compte1_selector', $this->comptes_model->selector($where));

        $where = array(
            "codec >=" => "5",
            'codec <' => "6"
        );
        $this->gvvmetadata->set_selector('compte2_selector', $this->comptes_model->selector($where));

        load_last_view('compta/formView', $this->data);
    }

    /**
     * Enregistrement d'un avoir fournisseur
     */
    function avoir_fournisseur() {
        parent::create(FALSE);
        $this->session->set_userdata('current_url', current_url());

        $this->data['title_key'] = "gvv_compta_title_avoir";

        $where = array(
            "codec" => "401"
        );
        $this->gvvmetadata->set_selector('compte1_selector', $this->comptes_model->selector($where));

        $where = array(
            "codec >=" => "6",
            'codec <' => "7"
        );
        $this->gvvmetadata->set_selector('compte2_selector', $this->comptes_model->selector($where));

        load_last_view('compta/formView', $this->data);
    }

    /**
     * Utilisation d'un avoir fournisseur
     */
    function utilisation_avoir_fournisseur() {
        parent::create(FALSE);
        $this->session->set_userdata('current_url', current_url());

        $this->data['title_key'] = "gvv_compta_title_avoir_use";

        $where = array(
            "codec >=" => "6",
            'codec <' => "7"
        );
        $this->gvvmetadata->set_selector('compte1_selector', $this->comptes_model->selector($where));

        $where = array(
            "codec" => "401"
        );
        $this->gvvmetadata->set_selector('compte2_selector', $this->comptes_model->selector($where));

        load_last_view('compta/formView', $this->data);
    }

    /**
     * Virement entre comptes bancaire
     */
    function virement() {
        parent::create(FALSE);
        $this->session->set_userdata('current_url', current_url());
        $this->data['title_key'] = "gvv_compta_title_wire";
        $this->data['message'] = $this->lang->line("gvv_compta_message_advice_wire");

        $where = array(
            "codec" => "512"
        );
        $this->gvvmetadata->set_selector('compte1_selector', $this->comptes_model->selector($where));

        $where = array(
            "codec" => "512"
        );
        $this->gvvmetadata->set_selector('compte2_selector', $this->comptes_model->selector($where));

        load_last_view('compta/formView', $this->data);
    }

    /**
     * journal
     *
     * @param $premier élément
     *            à afficher
     * @param $message à
     *            afficher
     */
    function page($premier = 0, $message = '', $selection = array()) {
        $current_url = current_url();

        $this->push_return_url("grand journal");

        $this->data = $this->comptes_model->get_first();
        $this->data['id'] = "";

        $year = $this->session->userdata('year');
        $this->data['year_selector'] = $this->gvv_model->getYearSelector("date_op");
        $this->data['year'] = $year;

        $this->data['compte_selector'] = $this->comptes_model->selector_with_all([], "asc", true);

        $this->selection_filter();
        $this->data['select_result'] = $this->gvv_model->select_journal('', $this->session->userdata('per_page'), $premier);
        $this->data['count'] = $this->gvv_model->count();

        $this->data['query'] = 0;

        $this->data['kid'] = 'id';
        $this->data['controller'] = $this->controller;
        $this->data['premier'] = $premier;
        $this->data['compte'] = '';
        $this->data['tresorier'] = $this->dx_auth->is_role('tresorier', true, true);

        $has_modification_rights = (!isset($this->modification_level)
            || $this->dx_auth->is_role($this->modification_level, true, true));
        $has_modification_rights = $has_modification_rights && ($this->gvv_model->section());

        $this->data['has_modification_rights'] = $has_modification_rights;

        $this->data['section'] = $this->gvv_model->section();

        load_last_view('compta/journalView', $this->data);
    }

    /**
     * Vérifie qu'un des éléments du tableau match le pattern
     */
    function matching_row($row, $pattern) {
        foreach ($row as $elt) {
            if (preg_match('/' . $pattern . '/', $elt, $matches)) {
                return TRUE;
            }
        }
        return false;
    }

    /**
     * Génere les information demandées par le datatable Jquery
     *
     * Support du filtrage, du tri par colonne et de la pagination.
     * La pagination doit être faite après le filtrage(on pagine sur les
     * données filtrées). Le filtrage doit être fait après formattage des
     * données de façon à pour voir filtrer sur les champs tels qu'ils sont
     * affichés.
     */
    function ajax_page() {
        $year = $this->session->userdata('year');

        gvv_debug("ajax_page compta $year");
        gvv_debug("ajax_page url = " . curPageURL());

        $selection = $this->ecritures_model->filtrage();

        /*
         * Paging
         */
        $per_page = 1000000;
        $first = 0;
        if (isset($_GET['iDisplayStart']) && $_GET['iDisplayLength'] != '-1') {
            $first = mysql_real_escape_string($_GET['iDisplayStart']);
            $per_page = mysql_real_escape_string($_GET['iDisplayLength']);
            gvv_debug("ajax_page first = $first, per_page = $per_page ");
        }

        $order = "";
        /*
         * Ordering
         */
        $direction = "desc";
        if (isset($_GET['iSortCol_0'])) {
            for ($i = 0; $i < intval($_GET['iSortingCols']); $i++) {
                // foreach column $i
                if ($_GET['bSortable_' . intval($_GET['iSortCol_' . $i])] == "true") {
                    $direction = mysql_real_escape_string($_GET['sSortDir_' . $i]);

                    if ($i == 1) {
                        $order .= "date_op $direction, ";
                    }
                }
            }

            $order = substr_replace($order, "", -2); // remove last comma
        }

        $order = $direction;
        gvv_debug("ajax order = $order");

        /*
         * Filtering
         */
        $search = "";
        if (isset($_GET['sSearch'])) {
            if ($_GET['sSearch'] != "") {
                $search = mysql_real_escape_string($_GET['sSearch']);

                // En cas de filtrage, il faut faire la pagination à la main
                $per_page = 1000000;
                $first = 0;
            }
        }
        gvv_debug("ajax search = $search");

        $result = $this->ecritures_model->select_journal('', $per_page, $first, $selection);
        // gvv_debug("ajax result 1 =" . var_export($result, true));

        $has_modification_rights = (!isset($this->modification_level) || $this->dx_auth->is_role($this->modification_level, true, true));

        $has_modification_rights = $has_modification_rights && ($this->gvv_model->section());

        $actions = [];
        if ($has_modification_rights) {
            $actions = array(
                'edit',
                'delete'
            );
        }

        $attrs = array(
            // 'controller' => $controller,
            'actions' => $actions,
            'mode' => ($has_modification_rights) ? "rw" : "ro"
        );

        $result = $this->gvvmetadata->normalise("vue_journal", $result, $attrs);
        gvv_debug("ajax result 2 =" . var_export($result, true));

        $iTotal = $this->ecritures_model->count();
        gvv_debug("\$iTotal = $iTotal");

        if ($search != "") {
            // selection
            $not_filtered = $result;
            $result = array();
            $iFilteredTotal = 0;

            // reset la pagination qui a pu être écrasée à cause de la gestion manuelle
            if (isset($_GET['iDisplayStart']) && $_GET['iDisplayLength'] != '-1') {
                $first = mysql_real_escape_string($_GET['iDisplayStart']);
                $per_page = mysql_real_escape_string($_GET['iDisplayLength']);
            }

            foreach ($not_filtered as $row) {

                $match = true;
                if ($this->matching_row($row, $search)) {
                    $iFilteredTotal++;

                    // in the window ?
                    if (($iFilteredTotal >= $first) && ($iFilteredTotal < $first + $per_page)) {
                        $result[] = $row;
                    }
                }
            }
        } else {
            $iFilteredTotal = $iTotal;
        }

        /*
         * Output generation
         */
        $output = array(
            "sEcho" => intval($_GET['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array()
        );

        /*
         * Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field(for example a counter or static image)
         */
        $out_cols = array(
            'id',
            'date_op',
            'code1',
            'compte1',
            'code2',
            'compte2',
            'description',
            'num_cheque',
            'montant',
            'section',
            'gel'
        );



        /* Indexed column(used for fast and accurate table cardinality) */
        $sIndexColumn = "id";

        foreach ($result as $select_row) {
            $row = array();

            foreach ($actions as $action) {
                $url = $this->controller . "/$action";
                $elt_image = $select_row['image'];
                $confirm = ($action == 'delete');

                $image = $this->gvvmetadata->action($action, $url, $select_row[$sIndexColumn], $elt_image, $confirm);
                $row[] = $image;
            }

            for ($i = 0; $i < count($out_cols); $i++) {
                if (isset($out_cols[$i]) && $out_cols[$i] != ' ') {
                    // General output
                    $value = $select_row[$out_cols[$i]];
                    if ($value == null)
                        $value = "";
                    $row[] = $value;
                } else {
                    $row[] = "";
                }
            }

            $output['aaData'][] = $row;
        }

        $json = json_encode($output);
        gvv_debug("json = $json");
        echo $json;
    }

    /**
     * Export du journal soue Excel ou Pdf
     */
    function export_journal() {
        if ($_POST['button'] == 'Pdf') {
            $mode = 'pdf';
        } else if ($_POST['button'] == 'Excel') {
            $mode = 'csv';
        } else if ($_POST['button'] == $this->lang->line("gvv_compta_button_freeze")) {
            $mode = 'gel';
        } else {
            $mode = "";
        }

        $year = $this->session->userdata('year');

        $this->selection_filter();
        $selection = $this->gvv_model->select_journal('');
        if ($mode == 'csv') {
            $this->gvvmetadata->csv("vue_journal", array());
        } else if ($mode == 'gel') {
            foreach ($selection as $row) {
                if (!$row['gel']) {
                    // echo "id=" . $row['id'] . ", gel=" . $row['gel'] . br();
                    $this->gvv_model->switch_line($row['id'], 1);
                }
            }
            $this->pop_return_url();
        } else {
            $this->load->library('Pdf');
            $pdf = new Pdf();

            $pdf->AddPage('L');
            $pdf->title($this->lang->line('gvv_comptes_title_journal') . " $year", 1);

            $attrs = array(
                'fields' => array(
                    'id',
                    'date_op',
                    'code1',
                    'compte1',
                    'code2',
                    'compte2',
                    'description',
                    'num_cheque',
                    'montant'
                ),
                'width' => array(
                    10,
                    17,
                    12,
                    40,
                    12,
                    40,
                    80,
                    40,
                    16
                ),
                'mode' => "pdf"
            );
            $this->gvvmetadata->pdf("vue_journal", $pdf, $attrs);
            $pdf->Output();
        }
    }

    /**
     * Rempli les données à transmettre au formulaire avec la selection du filtrage
     */
    private function selection_filter() {
        $this->data['filter_active'] = $this->session->userdata('filter_active');
        $this->data['filter_date'] = $this->session->userdata('filter_date');
        $this->data['date_end'] = $this->session->userdata('date_end');
        $this->data['filter_code1'] = $this->session->userdata('filter_code1');
        $this->data['code1_end'] = $this->session->userdata('code1_end');
        $this->data['filter_code2'] = $this->session->userdata('filter_code2');
        $this->data['code2_end'] = $this->session->userdata('code2_end');
        $this->data['montant_min'] = $this->session->userdata('montant_min');
        $this->data['montant_max'] = $this->session->userdata('montant_max');
        $this->data['filter_checked'] = $this->session->userdata('filter_checked');
        $this->data['filter_debit'] = $this->session->userdata('filter_debit');
    }

    /**
     * Fetch data for account extract for display, PDF or export
     *
     * Enter description here ...
     *
     * @param unknown_type $data
     *            of the account from DB
     * @param unknown_type $compte
     * @param unknown_type $premier
     * @param unknown_type $message
     * @param unknown_type $per_page
     */
    private function select_data($account_data, $compte = '', $premier = 0, $message = '', $per_page = 0) {
        if (!$per_page)
            $per_page = $this->session->userdata('per_page');

        // The following line has to be first
        $this->data = $account_data;
        $this->data['compte_selector'] = $this->comptes_model->selector_with_all();

        $year = $this->session->userdata('year');
        $this->data['year_selector'] = $this->gvv_model->getYearSelector("date_op");
        $this->data['year'] = $year;

        $this->selection_filter();

        // par défaut on utilise le début et la fin de l'année
        $date_deb = "01/01/$year";
        $solde_previous_year = $this->ecritures_model->solde_compte($compte, $date_deb, "<");
        if ($year < date("Y")) {
            $date_fin = "31/12/$year";
        } else {
            $date_fin = date("d/m/Y");
        }
        if ($this->session->userdata('filter_active')) {
            // sauf en cas de selection explicit
            if ($this->data['filter_date']) {
                $date_deb = $this->data['filter_date'];
            }
            if ($this->data['date_end']) {
                $date_fin = $this->data['date_end'];
            }
        }

        $solde_deb = $this->ecritures_model->solde_compte($compte, $date_deb, $operation = "<");
        $solde_fin = $this->ecritures_model->solde_compte($compte, $date_fin, $operation = "<=");
        $this->data['date_deb'] = $date_deb;
        $this->data['date_fin'] = $date_fin;
        $this->data['solde_avant'] = $solde_deb;
        $this->data['solde_fin'] = $solde_fin;

        // echo "debut $date_deb, solde=$solde_deb fin=$date_fin, solde=$solde_fin" . br();

        $this->data['count'] = $this->gvv_model->count($compte);
        if ($this->data['count'] > 400) {
            $this->data['select_result'] = $this->gvv_model->select_journal($compte, $per_page, $premier);
        } else {
            $this->data['select_result'] = $this->gvv_model->select_journal($compte);
        }
        $user = $this->comptes_model->user($compte);
        $mlogin = $this->dx_auth->get_username();
        $info_pilote = $this->membres_model->get_by_id('mlogin', $mlogin);

        // Check that the user as the right to display this account
        if ($user == $this->dx_auth->get_username()) {
        } else if ($this->dx_auth->is_role('bureau', true, true)) {
        } else if ($compte == $info_pilote['compte']) {
        } else {
            $this->dx_auth->deny_access();
        }

        $this->data['kid'] = 'id';
        $this->data['controller'] = $this->controller;
        $this->data['nom'] = $this->comptes_model->image($compte);
        $this->data['premier'] = $premier;
        $this->data['compte'] = $compte;
        $this->data['navigation_allowed'] = $this->dx_auth->is_role('bureau', true, true);
        $this->data['tresorier'] = $this->dx_auth->is_role('tresorier', true, true);

        // fields for purchase
        $this->data['date'] = date("d/m/Y", time());
        $this->data['produit_selector'] = $this->tarifs_model->selector();
        $this->data['quantite'] = 1;
        $this->data['produit'] = '';
        $this->data['description'] = '';
        $this->data['action'] = CREATION;

        // si c'est un compte pilote, ajoute les champs pour la facture
        $codec = $this->data['codec'];
        if ($codec == 411) {
            $pilote = $this->comptes_model->user($compte);
            // echo "pilote=$pilote<br>";
            $this->data['pilote_name'] = $this->membres_model->image($pilote);
            $this->data['pilote_info'] = $this->membres_model->get_by_id('mlogin', $pilote);
        } else if ($codec >= 600 && $codec < 800) {
            // recette ou dépense
            $this->data['solde_avant'] -= $solde_previous_year;
            $this->data['solde_fin'] -= $solde_previous_year;
        }
        $this->data['has_modification_rights'] = (!isset($this->modification_level) || $this->dx_auth->is_role($this->modification_level, true, true));
    }

    /**
     * Display acount extract
     */
    private function journal_data($data, $compte = '', $premier = 0, $message = '') {
        $this->select_data($data, $compte, $premier, $message);
        load_last_view('compta/journalCompteView', $this->data);
    }

    /**
     * journal
     */
    function journal_compte($compte = '', $premier = 0, $message = '') {
        $current_url = current_url();

        /*
         * Patch. Je ne sais pas pourquoi mais journal_compte est rappelé
         * avec un current_url incohérent. Ce patch évite juste de ré-enregistrer une URL de
         * retour fausse.
         */
        if (!preg_match("/favicon/", $current_url) && !preg_match("/filterValidation/", $current_url)) {
            $this->push_return_url("journal compte");
        }
        $data = $this->comptes_model->get_by_id('id', $compte);
        if (count($data) == 0) {
            return $this->page();
        } else {
            $this->journal_data($data, $compte, $premier, $message);
        }
    }

    /**
     *
     * Visualisation d'un compte alias pour journal
     *
     * @param unknown_type $compte
     */
    function view($compte) {
        $this->journal_compte($compte);
    }

    /**
     * journal d'un compte pilote
     *
     * @param
     *            $pilote
     */
    function compte_pilote($pilote) {
        if ($this->comptes_model->has_compte($pilote)) {
            $compte = $this->comptes_model->compte_pilote($pilote);
            $data = $this->comptes_model->get_by_id('id', $compte);
            $this->journal_data($data, $compte);
        } else {
            $data = array();
            $data['title'] = $this->lang->line("gvv_comptes_title_error");
            $data['text'] = $this->lang->line("gvv_comptes_error_no_account") . " $pilote.";
            load_last_view('message', $data);
        }
    }

    /**
     * journal
     */
    function mon_compte() {
        $this->push_return_url("mon compte");

        $mlogin = $this->dx_auth->get_username();
        $info_pilote = $this->membres_model->get_by_id('mlogin', $mlogin);
        if (isset($info_pilote['compte']) && $info_pilote['compte']) {
            $compte = $info_pilote['compte'];
            // $this->compte_pilote($mlogin);
            $this->journal_compte($info_pilote['compte']);
        } else {
            $this->compte_pilote($mlogin);
        }
    }

    /**
     * Validation du filtre d'affichage de compte.
     */
    private function _filterValidation() {
        $button = $this->input->post('button');
        $filter_variables = array(
            'filter_date',
            'date_end',
            'filter_code1',
            'code1_end',
            'filter_code2',
            'code2_end',
            'montant_min',
            'montant_max',
            'filter_active',
            'filter_checked',
            'filter_debit'
        );

        if ($button == "Filtrer") {
            gvv_debug("filtrage compta enabled");
            // Enable filtering
            foreach ($filter_variables as $field) {
                $session[$field] = $this->input->post($field);
            }
            $session['filter_active'] = 1;
            $this->session->set_userdata($session);
        } else {
            gvv_debug("filtrage compta disabled");
            // Disable filtering
            foreach ($filter_variables as $field) {
                $this->session->unset_userdata($field);
            }
        }
    }

    /**
     * Validation du filtre d'affichage de compte.
     */
    public function filterValidation($compte) {
        $this->_filterValidation();
        // Le filtrage modifie la pagination, donc après filtrage on ne peut pas retourner
        // à la page initiale
        $this->journal_compte($compte);
    }

    /**
     * Validation du filtre d'affichage de compte.
     * 1 => "Les dépenses", // Emploi 600 - 700
     * 2 => "Les recettes", // ressources 700 - 800
     * 3 => "Les paiements pilotes", // Ressources 411
     * 4 => "Les immobilisations" // Emploi 200-300
     */
    public function query($selection) {
        // echo "query = $selection" . br();
        $session = array();
        $session['filter_active'] = 1;
        $filter_variables = array(
            'filter_code1',
            'code1_end',
            'filter_code2',
            'code2_end'
        );
        foreach ($filter_variables as $field) {
            $this->session->unset_userdata($field);
        }
        if ($selection == 1) {
            $session['filter_code1'] = 600;
            $session['code1_end'] = 700;
        } else if ($selection == 2) {
            $session['filter_code2'] = 700;
            $session['code2_end'] = 800;
        } else if ($selection == 3) {
            $session['filter_code2'] = 411;
            $session['code2_end'] = 411;
        } else if ($selection == 4) {
            $session['filter_code1'] = 200;
            $session['code1_end'] = 300;
        }
        $this->session->set_userdata($session);
        $this->pop_return_url();
    }

    /**
     * Validation du filtre d'affichage de compte.
     */
    public function JournalFilterValidation() {
        $this->_filterValidation();
        redirect($this->controller . '/page'); // bug #1639
    }

    /**
     * Génère un extrait de compte en pdf
     *
     * @param unknown_type $compte
     */
    function pdf($compte = '') {
        $separator = ',';
        if ($compte == '') {
            $user = $this->dx_auth->get_username();
            if (!$this->comptes_model->has_compte($user)) {
                return;
            }
            $compte = $this->comptes_model->compte_pilote($user);
        }

        $height = 6;
        $compte_data = $this->comptes_model->get_by_id('id', $compte);
        $this->select_data($compte_data, $compte, 0, '', 10000);

        $nom_club = $this->config->item('nom_club');
        $tel_club = $this->config->item('tel_club');
        $email_club = $this->config->item('email_club');
        $adresse_club = $this->config->item('adresse_club');
        $cp_club = $this->config->item('cp_club');
        $ville_club = $this->config->item('ville_club');

        $this->load->library('Pdf');
        $pdf = new Pdf();
        $pdf->AddPage();

        $pdf->title($this->lang->line("gvv_compta_title_entries"), 1);

        // Dates de filtrage
        if ($this->data['filter_date'] != '') {
            $pdf->printl($this->lang->line("gvv_compta_date") . ": " . $this->data['filter_date'] . ", " . $this->lang->line("gvv_compta_jusqua") . ": " . $this->data['date_end']);
        }
        $pdf->Ln();

        // Information pilote si c'est un compte pilote
        if (isset($this->data['pilote_name'])) {

            $cp = $this->data['pilote_info']['cp'];
            $ville = $this->data['pilote_info']['ville'];

            $info = array();
            $info[] = array(
                $nom_club,
                $this->data['pilote_name']
            );
            $info[] = array(
                $adresse_club,
                $this->data['pilote_info']['madresse']
            );
            $info[] = array(
                $cp_club . ' ' . $ville_club,
                sprintf("%05d", $cp) . ' ' . $ville
            );
            $info[] = array(
                $tel_club . ', ' . $email_club,
                $this->data['pilote_info']['memail']
            );

            $pdf->table(array(
                120,
                75
            ), 5, array(
                'L',
                'L'
            ), $info, '');
            $pdf->Ln();
        } else {
            $pdf->printl($this->lang->line("gvv_compta_compte") . ': ' . $this->data['nom']);
            $pdf->printl($this->data['desc']);
            // print_r($this->data);
        }

        $solde = $this->lang->line("gvv_compta_label_balance_before") . " " . $this->data['date_deb'];
        $solde_avant = $this->data['solde_avant'];
        if ($solde_avant < 0) {
            $solde .= " " . $this->lang->line("gvv_compta_label_debitor") . " = ";
            $solde .= euro($solde_avant, $separator, 'pdf');
        } else {
            $solde .= " " . $this->lang->line("gvv_compta_label_creditor") . " = ";
            $solde .= euro($solde_avant, $separator, 'pdf');
        }
        $pdf->printl($solde);
        $pdf->Ln();

        // Lignes de factures
        $select_result = $this->data['select_result'];
        if ($this->data['codec'] == 411) {
            $w = array(
                18,
                70,
                34,
                15,
                16,
                18,
                18
            );
            $align = array(
                'L',
                'L',
                'L',
                'R',
                'R',
                'R',
                'R'
            );
            $data[0] = $this->lang->line("gvv_compta_csv_header_411");
        } else {
            $w = array(
                18,
                9,
                30,
                70,
                34,
                18,
                18
            );
            $align = array(
                'L',
                'R',
                'L',
                'L',
                'L',
                'R',
                'R'
            );
            $data[0] = $this->lang->line("gvv_compta_csv_header");
        }

        foreach ($select_result as $row) {
            $data_row = array();
            $quantite = $row['quantite'];
            $prix = ($row['prix'] < 0) ? '' : euro($row['prix'], $separator, 'pdf');
            $compte1 = $row['compte1'];
            if ($compte == $compte1) {
                // Débit
                $debit = euro($row['montant'], $separator, 'pdf');
                $credit = '';
                $code = $row['code2'];
                $nom_compte = $row['nom_compte2'];
            } else {
                $debit = '';
                $credit = euro($row['montant'], $separator, 'pdf');
                $code = $row['code1'];
                $nom_compte = $row['nom_compte1'];
            }

            $data_row[] = date_db2ht($row['date_op']);
            if ($this->data['codec'] != 411) {
                $data_row[] = $code;
                $data_row[] = $nom_compte;
            }
            $data_row[] = $row['description'];
            $data_row[] = $row['num_cheque'];
            if ($this->data['codec'] == 411) {
                $data_row[] = $prix;
                $data_row[] = $quantite;
            }
            $data_row[] = $debit;
            $data_row[] = $credit;
            $data[] = $data_row;
        }
        $pdf->table($w, $height, $align, $data);

        // Solde
        $pdf->Ln();
        $solde_fin = $this->data['solde_fin'];
        $solde = $this->lang->line("gvv_compta_label_balance_at") . " " . $this->data['date_fin'];
        if ($solde_fin < 0) {
            $solde .= " " . $this->lang->line("gvv_compta_label_debitor") . " = ";
            $solde .= euro($solde_fin, $separator, 'pdf');
        } else {
            $solde .= " " . $this->lang->line("gvv_compta_label_creditor") . " = ";
            $solde .= euro($solde_fin, $separator, 'pdf');
        }
        $pdf->printl($solde);

        $pdf->Output();
    }

    /**
     * Génère un extrait de compte sous Excel ou PDF
     *
     * @param unknown_type $compte
     */
    function export($compte = '') {
        if ($compte == '') {
            $user = $this->dx_auth->get_username();
            if (!$this->comptes_model->has_compte($user)) {
                return;
            }
            $compte = $this->comptes_model->compte_pilote($user);
        }

        if ($_POST['button'] == 'Pdf') {
            $this->pdf($compte);
            return;
        }

        $compte_data = $this->comptes_model->get_by_id('id', $compte);
        $this->select_data($compte_data, $compte, 0, '', 10000);

        if ($_POST['button'] == $this->lang->line("gvv_compta_button_freeze")) {
            $selection = $this->data['select_result'];
            foreach ($selection as $row) {
                if (!$row['gel']) {
                    // echo "id=" . $row['id'] . ", gel=" . $row['gel'] . br();
                    $this->gvv_model->switch_line($row['id'], 1);
                }
            }
            $this->pop_return_url();
        }

        // Generation de l'extrait de compte en csv
        $nom_club = $this->config->item('nom_club');
        $tel_club = $this->config->item('tel_club');
        $email_club = $this->config->item('email_club');
        $adresse_club = $this->config->item('adresse_club');
        $cp_club = $this->config->item('cp_club');
        $ville_club = $this->config->item('ville_club');

        $str = $this->lang->line("gvv_compta_title_entries") . "\n";

        // Dates de filtrage
        if ($this->data['filter_date'] != '') {
            $str .= $this->lang->line("gvv_compta_date") . ":; " . $this->data['filter_date'] . "; " . $this->lang->line("gvv_compta_jusqua") . ":; " . $this->data['filter_date'] . "\n";
        }

        // Information pilote si c'est un compte pilote
        if (isset($this->data['pilote_name'])) {

            $cp = $this->data['pilote_info']['cp'];
            $ville = $this->data['pilote_info']['ville'];

            $str .= "$nom_club;; " . $this->data['pilote_name'] . "\n";
            $str .= "$adresse_club;; " . $this->data['pilote_info']['madresse'] . "\n";
            $str .= "$cp_club; $ville_club; " . sprintf("%05d", $cp) . "; $ville\n";
            $str .= "$tel_club; $email_club; " . $this->data['pilote_info']['memail'] . "\n";
        } else {
            $str .= $this->lang->line("gvv_compta_compte") . "; " . $this->data['nom'] . "; " . $this->data['desc'] . "\n";
        }

        $str .= $this->lang->line("gvv_compta_label_balance_before") . "; " . $this->data['date_deb'] . ";";
        $solde_avant = $this->data['solde_avant'];
        if ($solde_avant < 0) {
            $str .= " " . $this->lang->line("gvv_compta_label_debitor") . "; ";
            $str .= number_format($solde_avant, 2, ",", "");
        } else {
            $str .= " " . $this->lang->line("gvv_compta_label_creditor") . " ;";
            $str .= number_format($solde_avant, 2, ",", "");
        }
        $str .= "\n";

        // Lignes de factures
        $select_result = $this->data['select_result'];
        if ($this->data['codec'] == 411) {
            $str .= join("; ", $this->lang->line("gvv_compta_csv_header_411")) . "\n";
        } else {
            $str .= join("; ", $this->lang->line("gvv_compta_csv_header")) . "\n";
        }

        foreach ($select_result as $row) {
            $data_row = array();
            $montant = number_format($row['montant'], 2, ",", "");
            $quantite = $row['quantite'];
            $prix = ($row['prix'] < 0) ? '' : $row['prix']; // number_format($row['prix'], 2, ",", "");
            $compte1 = $row['compte1'];
            if ($compte == $compte1) {
                // Débit
                $debit = number_format($row['montant'], 2, ",", "");
                $credit = '';
                $code = $row['code2'];
                $nom_compte = $row['nom_compte2'];
            } else {
                $debit = '';
                $credit = number_format($row['montant'], 2, ",", "");
                $code = $row['code1'];
                $nom_compte = $row['nom_compte1'];
            }

            $str .= date_db2ht($row['date_op']) . "; ";
            if ($this->data['codec'] != 411) {
                $str .= $code . "; ";
                $str .= $nom_compte . "; ";
            }
            $str .= $row['description'] . "; ";
            $str .= $row['num_cheque'] . "; ";
            if ($this->data['codec'] == 411) {
                $str .= $prix . "; ";
                $str .= $quantite . "; ";
            }
            $str .= $debit . "; ";
            $str .= $credit . "; ";
            $str .= "\n";
        }

        // Solde
        $solde = $this->data['solde_fin'];
        $str .= $this->lang->line("gvv_compta_label_balance_at") . ";" . $this->data['date_fin'] . ";";
        if ($solde < 0) {
            $str .= $this->lang->line("gvv_compta_label_debitor") . "; ";
            $str .= number_format($solde, 2, ",", "") . "\n";
        } else {
            $str .= $this->lang->line("gvv_compta_label_creditor") . "; ";
            $str .= number_format($solde, 2, ",", "") . "\n";
        }
        # $str = iconv('UTF-8', 'windows-1252', $str);

        // echo $str; return;

        date_default_timezone_set('Europe/Paris');
        $dt = $compte . '_' . date("Y_m_d");
        $filename = "gvv_compte_$dt.csv";

        // Load the download helper and send the file to your desktop
        $this->load->helper('download');
        force_download($filename, $str);
    }

    /**
     * Pointe les écritures
     *
     * @param unknown_type $id
     * @param unknown_type $state
     *            avant bascule
     */
    function switch_line($id, $state, $compte, $premier) {
        $new_state = ($state == 0) ? 1 : 0;
        $this->gvv_model->switch_line($id, $new_state);
        $this->pop_return_url();
    }

    /*
     * Retourne la liste des dernierres références pour l'autocompletion
     *
     */
    function search_ref() {
        if (isset($_GET['term'])) {
            $term = $_GET['term'];
        } else {
            $term = "";
        }
        gvv_debug("search_ref term=$term");

        $res = $this->gvv_model->latest("num_cheque", $term);
        $json = json_encode($res);
        gvv_debug("json = $json");
        $this->output->set_content_type('application/json')->set_output($json);
    }

    /*
     * Retourne la liste des dernierres références pour l'autocompletion
     *
     */
    function search_description() {
        if (isset($_GET['term'])) {
            $term = $_GET['term'];
        } else {
            $term = "";
        }
        gvv_debug("search_description term=$term");

        $res = $this->gvv_model->latest("description", $term);
        $json = json_encode($res);
        gvv_debug("json = $json");
        $this->output->set_content_type('application/json')->set_output($json);
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
