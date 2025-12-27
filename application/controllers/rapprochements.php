<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * GVV Gestion vol à voile
 * Copyright (C) 2011 Philippe Boissel & Frédéric Peignot
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * Rapprochements bancaires
 */
class Rapprochements extends CI_Controller {

    protected $shared_reconciliator = null; // Reconciliator partagé pour éviter de reparser

    function __construct() {
        parent::__construct();
        // Check if user is logged in or not
        $this->dx_auth->check_login();

        $this->load->helper('validation');
        $this->load->helper('url');
        // Store current URL to reload it after the certificate is granted
        $this->session->set_userdata('return_url', current_url());

        $this->lang->load('welcome');
        $this->load->model('comptes_model');
        $this->load->model('ecritures_model');
        $this->load->model('sections_model');
        $this->lang->load('rapprochements');
        $this->load->model('associations_ecriture_model');
        $this->load->model('associations_releve_model');
        $this->config->load('debug');


        $this->load->library('rapprochements/Reconciliator');
    }

    /**
     * Display bank statement file selection page
     * 
     * Loads the initial page for bank reconciliation where users can select
     * a bank account and upload a bank statement file. Initializes filter
     * parameters from session data and prepares dropdown selectors.
     * 
     * @return void Loads the bank statement selection view
     */
    function select_releve() {
        $data = array();

        $bank_selector = $this->comptes_model->selector_with_null([
            "codec >=" => "5",
            'codec <' => "6"
        ], TRUE);
        $this->gvvmetadata->set_selector('bank_selector', $bank_selector);
        $data['bank_selector'] = $bank_selector;
        $data['bank_account'] = ''; // Default value for the dropdown

        $filter_active = $this->session->userdata('filter_active');
        $startDate = $this->session->userdata('startDate');
        if (!$startDate) {
            $startDate = date('Y') . '-01-01';
        }
        $endDate = $this->session->userdata('endDate');
        if (!$endDate) {
            $endDate = date('Y') . '-12-31';
        }
        $filter_type = $this->session->userdata('filter_type');
        $type_selector = $this->session->userdata('type_selector');

        $data['filter_active'] = $filter_active;
        $data['startDate'] = $startDate;
        $data['endDate'] = $endDate;
        $data['filter_type'] = $filter_type;
        $data['type_selector'] = $type_selector;

        load_last_view('rapprochements/select_releve', $data);
    }

    /**
     * Charge ou retourne le reconciliator en cache
     */
    private function get_reconciliator() {
        if ($this->shared_reconciliator !== null) {
            return $this->shared_reconciliator;
        }

        $filename = $this->session->userdata('file_releve');
        if (!$filename) {
            throw new Exception('Aucun fichier relevé en session');
        }

        $this->load->library('rapprochements/ReleveParser');
        $parser = new ReleveParser();
        $parser_result = $parser->parse($filename);

        // gvv_dump($parser_result);

        $this->shared_reconciliator = new Reconciliator($parser_result);
        
        $this->shared_reconciliator->set_filename($filename);

        // $this->shared_reconciliator->dump();

        return $this->shared_reconciliator;
    }

    /**
     * Import a bank statement CSV file via file upload
     * 
     * Handles the file upload process for bank statement files, validates the upload,
     * and redirects to the import processing page. Cleans up previous temporary files
     * to maintain disk space.
     * 
     * @return void Redirects to appropriate view based on upload success/failure
     * @throws Exception If file upload fails or directory permissions are inadequate
     */
    public function import_releve() {

        $upload_path = './uploads/restore/';
        if (! file_exists($upload_path)) {
            if (! mkdir($upload_path, 0755)) {
                die("Cannot create " . $upload_path . " with proper permissions.");
            }
        }

        // delete all files in the uploads/restore directory
        // these files are temporary, there is no need to keep them
        // I only keep the last one for debugging
        $files = glob($upload_path . '*'); // get all file names
        foreach ($files as $file) { // iterate files
            if (is_file($file))
                if (!unlink($file)) {
                    gvv_error("Failed to delete file: $file");
                }
        }

        // upload archive
        $config['upload_path'] = $upload_path;
        $config['allowed_types'] = '*';
        $config['max_size'] = '1500'; // in kilobytes (KB)
        $config['overwrite'] = TRUE;

        $this->load->library('upload', $config);

        if (! $this->upload->do_upload()) {
            // On a pas réussi à recharger le fichier
            // On affiche le message d'erreur
            $error = array(
                'error' => $this->upload->display_errors()
            );
            load_last_view('rapprochements/select_releve', $error);
        } else {
            // on a chargé le fichier
            $data = $this->upload->data();

            $this->session->set_userdata('filter_active', false);

            $filename = $config['upload_path'] . $data['file_name'];
            $this->session->set_userdata('file_releve', $filename);

            $this->import_releve_from_file();
        }
    }

    /**
     * Retrieves GVV bank transaction lines within a specified date range.
     *
     * @param string $startDate The start date (inclusive) for filtering transactions, in 'YYYY-MM-DD' format.
     * @param string $endDate The end date (inclusive) for filtering transactions, in 'YYYY-MM-DD' format.
     * @param string $gvv_bank The identifier or name of the GVV bank account to filter transactions.
     *
     * @return array Returns an array of transaction lines. Each line is typically represented as an associative array
     *              containing details such as date, amount, description, and other relevant fields for the transaction.
     */
    protected function get_gvv_lines($startDate, $endDate, $gvv_bank) {
        $gvv_lines = $this->ecritures_model->select_ecritures_rapprochements($startDate, $endDate, $gvv_bank);

        foreach ($gvv_lines as &$gvv_line) {
            $id = $gvv_line['id'];
            $rapproched = $this->associations_ecriture_model->get_rapproches($id);
            $gvv_line['rapproched'] = $rapproched;
        }

        return $gvv_lines;
    }

    private function get_filtered_gvv_lines($startDate, $endDate, $gvv_bank) {
        $gvv_lines = $this->get_gvv_lines($startDate, $endDate, $gvv_bank);

        $filter_active = $this->session->userdata('filter_active');
        $startDate = $this->session->userdata('startDate');
        if (!$startDate) {
            $startDate = date('Y') . '-01-01';
        }
        $endDate = $this->session->userdata('endDate');
        if (!$endDate) {
            $endDate = date('Y') . '-12-31';
        }
        $filter_type = $this->session->userdata('filter_type');
        $type_selector = $this->session->userdata('type_selector');

        $cnt = 0;
        $filtered_lines = [];
        foreach ($gvv_lines as &$gvv_line) {

            $rapproched = $gvv_line['rapproched'];

            if ($filter_active && $filter_type != 'display_all') {
                if ($filter_type == '') {
                    $filtered_lines[] = $gvv_line;
                }
                if ($filter_type == 'filter_matched' && $rapproched) {
                    $filtered_lines[] = $gvv_line;
                }

                $unmatched_list = [
                    'filter_unmatched',
                    'filter_unmatched_0',
                    'filter_unmatched_1',
                    'filter_unmatched_choices',
                    'filter_unmatched_multi'
                ];
                if (in_array($filter_type, $unmatched_list) && !$rapproched) {
                    $filtered_lines[] = $gvv_line;
                }
            } else {
                $filtered_lines[] = $gvv_line;
            }
            $cnt++;
        }
        return $filtered_lines;
    }

    /**
     * Import and process bank statement from uploaded file using Reconciliator
     * 
     * Main entry point for bank reconciliation. Processes the uploaded bank statement file,
     * applies filters, generates HTML tables for display, and loads the reconciliation interface.
     * Handles both bank statement operations and GVV accounting entries for comparison.
     * 
     * @return void Loads the reconciliation view or error view on exception
     * @throws Exception If file processing fails or reconciliator encounters errors
     */
    public function import_releve_from_file() {

        $filter_active = $this->session->userdata('filter_active');
        $startDate = $this->session->userdata('startDate');
        if (!$startDate) {
            $startDate = date('Y') . '-01-01';
        }
        $endDate = $this->session->userdata('endDate');
        if (!$endDate) {
            $endDate = date('Y') . '-12-31';
        }
        $filter_type = $this->session->userdata('filter_type');
        $type_selector = $this->session->userdata('type_selector');

        try {
            // Réinitialiser le reconciliator en cache quand on recharge la page principale
            $this->shared_reconciliator = null;
            $reconciliator = $this->get_reconciliator();

            $data['section'] = $this->sections_model->section();
            $data['header'] = $reconciliator->header();
            $data['count_selected'] = $reconciliator->filtered_operations_count();
            $data['html_tables'] = $reconciliator->to_HTML();

            $type_hash = ["all" => "Tous les types"];
            $recognized_types = $reconciliator->recognized_types();
            $type_hash = array_merge($type_hash, $recognized_types);
            $data['type_dropdown'] = dropdown_field(
                'type_selector',
                $type_selector,
                $type_hash,
                'class="form-control big_select"'
            );

            $data['filter_active'] = $filter_active;
            $data['startDate'] = $startDate;
            $data['endDate'] = $endDate;
            $data['filter_type'] = $filter_type;
            $data['type_selector'] = $type_selector;
            $data['maxDays'] = $this->session->userdata('rapprochement_delta') ?? 5;
            if (!$data['maxDays']) {
                $data['maxDays'] = 5; // Default delta value
            }
            $data['smartMode'] = $this->session->userdata('rapprochement_smart_mode') ?? false;

            $data['status'] = "";
            $data['errors'] = $this->session->userdata('errors');
            $this->session->unset_userdata('errors');

            // data for the GVV tab
            // récupérer le gvv_bank depuis le reconciliator
            $gvv_bank = $reconciliator->gvv_bank_account();

            $filtered_lines = $this->get_filtered_gvv_lines($data['startDate'], $data['endDate'], $gvv_bank);
            $data['gvv_lines'] = $this->to_ecritures_table($filtered_lines);

            load_last_view('rapprochements/tableRapprochements', $data);
        } catch (Exception $e) {
            gvv_error("Erreur: " . $e->getMessage() . "\n");
            $data = [];
            $data['error'] = $e->getMessage();
            load_last_view('rapprochements/select_releve', $data);
        }
    }

    /**
     * Process bank reconciliation between statement operations and GVV accounting entries
     * 
     * Handles both single and multiple reconciliations, validates user selections,
     * prevents duplicate assignments, and creates associations between bank statement
     * operations and GVV accounting entries. Supports manual mode for direct reconciliation.
     * 
     * @return void Redirects to import page with success/error status
     * @throws Exception If validation fails or database operations encounter errors
     */
    public function rapprochez() {
        // Rapproche les écritures sélectionnées

        $post = $this->input->post();

        // Input validation for POST data
        if (empty($post) || !is_array($post)) {
            $this->session->set_userdata('errors', ['Aucune donnée reçue pour le rapprochement']);
            redirect('rapprochements/import_releve_from_file');
            return;
        }

        $counts = [];
        $operations = [];

        // Déterminer si c'est un rapprochement manuel
        $is_manual = $this->input->post('manual_mode') === '1';

        if (isset($post['button']) && $post['button'] == 'Supprimer les rapprochements') {
            // On supprime les rapprochements
            $this->delete_rapprochement();
            return;
        }

        // Process valid selections
        foreach ($post as $key => $value) {
            if (strpos($key, 'cb_') === 0) {
                $line = str_replace('cb_', '', $key);

                if (isset($post['string_releve_' . $line])) {
                    $string_releve = $post['string_releve_' . $line];

                    // Validate string_releve input
                    if (empty($string_releve) || !is_string($string_releve)) {
                        gvv_error("Invalid string_releve for line $line");
                        continue; // Skip this operation
                    }

                    // Check string length for security
                    if (strlen($string_releve) > 500) {
                        gvv_error("String releve too long for line $line: " . strlen($string_releve) . " characters");
                        continue; // Skip this operation
                    }

                    $operations[$line] = ['string_releve' => $string_releve];
                } else {
                    gvv_dump('string_releve_' . $line . "not defined");
                }

                // Check for single ecriture selection
                if (isset($post['op_' . $line]) && $post['op_' . $line] !== '') {
                    // echo "op_$line => " . $post['op_' . $line] . "<br>";
                    $op = $post['op_' . $line];
                    // Count occurrences of this operation ID
                    if (!isset($counts[$op])) {
                        $counts[$op] = 0;
                    }
                    $counts[$op]++;
                    $operations[$line]['ecriture'] = $post['op_' . $line];
                } else {
                    // Check for multiple ecritures selection (rapprochement multiple)
                    $multiple_ecritures = [];
                    foreach ($post as $multi_key => $multi_value) {
                        // Look for pattern cbmulti_LINE_ECRITURE_ID
                        if (strpos($multi_key, 'cbmulti_' . $line . '_') === 0) {
                            $ecriture_id = str_replace('cbmulti_' . $line . '_', '', $multi_key);
                            if ($multi_value == '1') {
                                $multiple_ecritures[] = $ecriture_id;
                                // Count occurrences of this operation ID
                                if (!isset($counts[$ecriture_id])) {
                                    $counts[$ecriture_id] = 0;
                                }
                                $counts[$ecriture_id]++;
                            }
                        }
                    }

                    if (!empty($multiple_ecritures)) {
                        $operations[$line]['multiple_ecritures'] = $multiple_ecritures;
                    } else {
                        // En mode manuel, $line est directement l'ID de l'écriture
                        if ($is_manual && is_numeric($line)) {
                            $operations[$line]['ecriture'] = $line;
                            if (!isset($counts[$line])) {
                                $counts[$line] = 0;
                            }
                            $counts[$line]++;
                        } else {
                            gvv_dump('op_' . $line . " not defined and no multiple ecritures found");
                        }
                    }
                }
            }
        }

        $errors = [];

        foreach ($counts as $key => $value) {
            if ($value > 1) {
                $image = $this->ecritures_model->image($key);
                $errors[] = "L'écriture $image a été sélectionnée $value fois";
            }
        }
        if ($errors) {
            $this->session->set_userdata('errors', $errors);
            redirect('rapprochements/import_releve_from_file');
            return;
        }

        // process valid operations
        foreach ($operations as $key => $ope) {
            if (isset($ope['ecriture'])) {
                // Single reconciliation
                $this->associations_ecriture_model->check_and_create([
                    'string_releve' => $ope['string_releve'],
                    'id_ecriture_gvv' => $ope['ecriture']
                ]);
            } elseif (isset($ope['multiple_ecritures'])) {
                // Multiple reconciliations
                foreach ($ope['multiple_ecritures'] as $ecriture_id) {
                    $this->associations_ecriture_model->check_and_create([
                        'string_releve' => $ope['string_releve'],
                        'id_ecriture_gvv' => $ecriture_id
                    ]);
                }
            }
        }

        redirect('rapprochements/import_releve_from_file');
    }

    /**
     * Delete selected reconciliation associations
     * 
     * Processes POST data to identify and delete reconciliation associations
     * between bank statement operations and GVV accounting entries based on
     * string_releve identifiers from selected checkboxes.
     * 
     * @return void Redirects to import page after processing deletions
     */
    function delete_rapprochement() {
        // Supprime les rapprochements sélectionnés
        $post = $this->input->post();
        $operations = [];

        // Process valid selections
        foreach ($post as $key => $value) {
            if (strpos($key, 'cbdel_') === 0) {
                $line = str_replace('cbdel_', '', $key);
                if (isset($post['string_releve_' . $line])) {

                    $operation = $post['string_releve_' . $line];
                    $this->associations_ecriture_model->delete_by_string_releve($operation);
                } else {
                    gvv_dump('string_releve_' . $line . "not defined");
                }
            }
        }
        redirect('rapprochements/import_releve_from_file');
    }

    /**
     * Inserts a movement with the given parameters
     *
     * @param array $params Associative array of movement parameters
     * @throws Exception If there is an error during the insertion of the movement 
     */
    public function insert_movement(array $params) {

        // Quel est la section courante?
        $section = $this->sections_model->section();

        // Il faut une section active pour importer les écritures
        if (!$section) return false;

        $montant = 0;
        $num_cheque = "OpenFlyers : " . $params['description'];
        $data = array(
            'annee_exercise' => date('Y', $params['date']),
            'date_op' => $params['date'],
            'date_creation' => date("Y-m-d"),
            'club' => $section['id'],
            'compte1' => $params['compte1'],
            'compte2' => $params['compte2'],
            'montant' => $montant,
            'description' => $params['intitule'],
            'num_cheque' => $num_cheque,
            'saisie_par' => $this->dx_auth->get_username()
        );

        if ($params['debit'] != "0.00") {
            $data['montant'] = $params['debit'];
        } else {
            $data['montant'] = $params['credit'];
            $data['compte1'] = $params['compte2'];
            $data['compte2'] = $params['compte1'];
        }

        // Si elle existe détruit l'écriture avec le même numéro de flux OpenFlyers
        $this->ecritures_model->delete_all(["club" => $data['club'], 'num_cheque' =>  $data['num_cheque']]);

        // Insert l'écriture
        $ecriture = $this->ecritures_model->create($data);

        if (!$ecriture) {
            throw new Exception("Erreur pendant le passage d'écriture de solde:");
        } else {
            return true;
        }
    }

    /**
     * Filter bank reconciliation operations based on user criteria
     * 
     * Processes filter form submissions to store filter criteria in session.
     * Validates date formats, filter types, and return URLs for security.
     * Supports filtering by date range, reconciliation status, and operation type.
     * 
     * @return void Redirects to the appropriate return URL or import page
     * @throws Exception If validation fails or session operations encounter errors
     */
    public function filter() {
        // Redirection vers la page de sélection du relevé
        $post = $this->input->post();
        // gvv_dump($post);
        $button = $post['button'] ?? '';

        if ($button == 'Filtrer') {
            // Validate and sanitize input data
            $start_date = $this->_validate_date($post['startDate'] ?? '');
            $end_date = $this->_validate_date($post['endDate'] ?? '');
            $filter_type = $this->_validate_filter_type($post['filter_type'] ?? '');
            $type_selector = $this->_validate_type_selector($post['type_selector'] ?? '');

            // Additional date logic validation
            if ($start_date && $end_date && $start_date > $end_date) {
                gvv_error("Filter validation: Start date ($start_date) is after end date ($end_date)");
                $this->session->set_userdata('status', 'Erreur: La date de début doit être antérieure à la date de fin.');
                // Reset to safe defaults
                $start_date = '';
                $end_date = '';
            }

            $this->session->set_userdata('startDate', $start_date);
            $this->session->set_userdata('endDate', $end_date);
            $this->session->set_userdata('filter_type', $filter_type);
            $this->session->set_userdata('type_selector', $type_selector);
            $this->session->set_userdata('filter_active', true);
        } else {
            $this->session->unset_userdata('startDate');
            $this->session->unset_userdata('endDate');
            $this->session->unset_userdata('filter_type');
            $this->session->unset_userdata('type_selector');
            $this->session->set_userdata('filter_active', false);
        }

        // Validate return_url parameter
        $return_url = $this->_validate_return_url($post['return_url'] ?? '');
        if (!empty($return_url)) {
            redirect($return_url);
        } else {
            redirect('rapprochements/import_releve_from_file');
        }
    }

    /**
     * Update maximum days window for bank reconciliation matching (AJAX)
     * 
     * Updates the session variable for the maximum number of days used in
     * automatic reconciliation matching algorithms. Returns JSON response.
     * 
     * @return void Outputs JSON response with success status
     */
    public function max_days_change() {
        // Change le nombre de jours maximum pour le rapprochement
        $delta = $this->input->get('maxDays');
        $this->session->set_userdata('rapprochement_delta', $delta);

        $json = json_encode(['success' => true]);
        gvv_debug("max_days_change($delta)" . $json);
        $this->output
            ->set_content_type('application/json')
            ->set_output($json);
    }

    /**
     * Toggle smart reconciliation mode (AJAX)
     * 
     * Updates the session variable for intelligent reconciliation mode which
     * enables advanced matching algorithms for automatic reconciliation.
     * Returns JSON response with operation status.
     * 
     * @return void Outputs JSON response with success status
     */
    public function smart_mode_change() {
        // Change le mode de rapprochement intelligent
        $smartMode = $this->input->get('smartMode') === 'true';
        $this->session->set_userdata('rapprochement_smart_mode', $smartMode);

        $json = json_encode(['success' => true]);
        $this->output
            ->set_content_type('application/json')
            ->set_output($json);
    }

    /**
     * Delete all selected reconciliations or accounting entries
     * 
     * Bulk operation to delete either reconciliation associations or both
     * reconciliations and the underlying accounting entries based on user selection.
     * Processes checkboxes with 'cbdel_' prefix to identify target entries.
     * 
     * @return void Redirects to import page with status message
     */
    function delete_all() {

        $posts = $this->input->post();

        if (!isset($posts['button']) || $posts['button'] == 'Supprimez les rapprochements sélectionnés') {
            $rappro = true;
        } else {
            $rappro = false;
        }

        $status = "";
        foreach ($posts as $key => $value) {
            // echo "$key => $value<br>";
            if (strpos($key, 'cbdel_') === 0) {
                // Key starts with "cbdel_" ce sont les checkboxes actives
                $id = str_replace("cbdel_", "", $key);

                // supprimer les rapprochements
                $rapproched = $this->associations_ecriture_model->get_rapproches($id);
                foreach ($rapproched   as $r) {
                    $status .= "rapprochement " . $r['id'] . " supprimé<br>";
                }
                $this->associations_ecriture_model->delete_rapprochements($id);
                if (!$rappro) {
                    // les rapprochements et l'écriture
                    $image = $this->ecritures_model->image($id);
                    $this->ecritures_model->delete_ecriture($id);
                    $status .= "$image supprimée<br>";
                }
            }
        }

        $this->session->set_userdata('status', $status);
        redirect('rapprochements/import_releve_from_file');
    }

    /**
     * Converts GVV accounting entries to HTML table format for display
     *
     * Transforms an array of GVV accounting lines into a formatted array suitable
     * for HTML table display. Adds checkboxes, status badges, and formatted data
     * columns. Reconciled entries show green badges, unreconciled show red badges.
     *
     * @param array $gvv_lines Array of GVV accounting entries to convert
     * @return array Formatted array with HTML elements suitable for table display
     */
    function to_ecritures_table($gvv_lines) {
        // gvv_dump($gvv_lines);
        $res = [];
        foreach ($gvv_lines as $line) {
            $elt = [];

            if ($line['rapproched']) {
                // Badge vert cliquable pour supprimer le rapprochement
                $badge = '<span class="bg-success badge text-white rounded-pill ms-1 cursor-pointer supprimer-rapprochement-badge" 
                            data-ecriture-id="' . $line['id'] . '" 
                            title="Cliquez pour supprimer le rapprochement">' . $line['id'] . '</span>';
                $element = '<input type="checkbox" name="cbdel_' . $line['id'] . '" value="1"">' . $badge;
            } else {
                $element = '<input type="checkbox" name="cbdel_' . $line['id'] . '" value="1"">'
                    . '<span class="bg-danger badge text-white rounded-pill ms-1">' . $line['id'] . '</span>';
            }
            $element .= anchor_ecriture_edit($line['id'], ' class="ms-1"');
            $element .= anchor_ecriture_delete($line['id'], ' class="ms-1"');
            $elt[] = $element;


            $elt[] = date_db2ht($line['date_op']);
            $elt[] = euro($line['montant']);
            $elt[] = $line['description'];
            $elt[] = $line['num_cheque'];

            $elt[] = anchor_compte($line['compte1']);
            $elt[] = anchor_compte($line['compte2']);
            $res[] = $elt;
        }
        return $res;
    }

    /**
     * Export GVV accounting entries in CSV or PDF format
     *
     * Exports the current selection of GVV accounting entries respecting active filters.
     * Supports both CSV (Excel-compatible) and PDF output formats. Access restricted
     * to users with 'ca' role.
     *
     * @param string $mode Export format: 'csv' or 'pdf' (default: 'csv')
     * @return void Outputs file for download
     */
    public function export_ecritures($mode = 'csv') {
        // Verify user has appropriate role
        if (!$this->dx_auth->is_role('ca')) {
            $this->dx_auth->deny_access();
            return;
        }

        // Get current filter settings
        $startDate = $this->session->userdata('startDate');
        if (!$startDate) {
            $startDate = date('Y') . '-01-01';
        }
        $endDate = $this->session->userdata('endDate');
        if (!$endDate) {
            $endDate = date('Y') . '-12-31';
        }

        // Get reconciliator to retrieve GVV bank account
        $reconciliator = $this->get_reconciliator();
        $gvv_bank = $reconciliator->gvv_bank_account();

        // Get filtered GVV lines (respects current filters)
        $filtered_lines = $this->get_filtered_gvv_lines($startDate, $endDate, $gvv_bank);

        // Fields to export (matching display columns, excluding checkboxes/actions)
        $fields = array('id', 'date_op', 'montant', 'description', 'num_cheque', 'compte1', 'compte2');
        $title = $this->lang->line('gvv_rapprochements_title') . ' - ' . $this->lang->line('gvv_rapprochements_ecritures_gvv');

        if ($mode === 'csv') {
            return $this->gvvmetadata->csv_table('ecritures', $filtered_lines, array(
                'title' => $title,
                'fields' => $fields,
            ));
        }

        // PDF export
        $this->load->library('Pdf');
        $pdf = new Pdf();
        $pdf->AddPage('L'); // Landscape orientation

        // Column widths (total ~270mm for landscape A4)
        $width = array(15, 25, 25, 100, 30, 37, 38);

        $this->gvvmetadata->pdf_table('ecritures', $filtered_lines, $pdf, array(
            'title' => $title,
            'fields' => $fields,
            'width' => $width,
        ));

        $pdf->Output();
    }

    /**
     * Reconcile a single bank operation with a GVV accounting entry (AJAX)
     *
     * Creates a reconciliation association between a bank statement operation
     * and a GVV accounting entry. Validates inputs and returns JSON response
     * with operation status. Only accessible via AJAX requests.
     *
     * @return void Outputs JSON response with success/error status and message
     */
    public function rapprocher_unique() {
        // Vérifier que c'est une requête AJAX
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $response = array('success' => false, 'message' => '');

        try {
            $string_releve = $this->input->post('string_releve');
            $ecriture_id = $this->input->post('ecriture_id');

            // Enhanced input validation
            if (empty($string_releve) || empty($ecriture_id)) {
                $response['message'] = 'Paramètres manquants';
            } elseif (!is_string($string_releve) || strlen($string_releve) > 500) {
                $response['message'] = 'Paramètre string_releve invalide';
            } elseif (!is_numeric($ecriture_id) || $ecriture_id <= 0) {
                $response['message'] = 'Paramètre ecriture_id invalide';
            } else {
                // Créer l'association
                $result = $this->associations_ecriture_model->check_and_create([
                    'string_releve' => $string_releve,
                    'id_ecriture_gvv' => $ecriture_id
                ]);

                if ($result) {
                    $response['success'] = true;
                    $response['message'] = 'Rapprochement effectué avec succès';
                } else {
                    $response['message'] = 'Erreur lors du rapprochement';
                }
            }
        } catch (Exception $e) {
            $error_msg = 'Erreur lors du rapprochement unique: ' . $e->getMessage();
            gvv_error($error_msg);
            $response['message'] = 'Erreur: ' . $e->getMessage();
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Delete a single reconciliation association (AJAX)
     * 
     * Removes the reconciliation association for a specific bank statement operation
     * identified by its string_releve. Validates input and returns JSON response
     * with operation status. Only accessible via AJAX requests.
     * 
     * @return void Outputs JSON response with success/error status and message
     */
    public function supprimer_rapprochement_unique() {
        // Vérifier que c'est une requête AJAX
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $response = array('success' => false, 'message' => '');

        try {
            $string_releve = $this->input->post('string_releve');

            // Enhanced input validation
            if (empty($string_releve)) {
                $response['message'] = 'Paramètre manquant';
            } elseif (!is_string($string_releve) || strlen($string_releve) > 500) {
                $response['message'] = 'Paramètre string_releve invalide';
            } else {
                // Supprimer l'association
                $result = $this->associations_ecriture_model->delete_by_string_releve($string_releve);

                if ($result !== false) {
                    $response['success'] = true;
                    $response['message'] = 'Rapprochement supprimé avec succès';
                } else {
                    $response['message'] = 'Erreur lors de la suppression du rapprochement';
                }
            }
        } catch (Exception $e) {
            $error_msg = 'Erreur lors de la suppression du rapprochement: ' . $e->getMessage();
            gvv_error($error_msg);
            $response['message'] = 'Erreur: ' . $e->getMessage();
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Reconcile multiple GVV entries with a single bank statement operation (AJAX)
     * 
     * Creates multiple reconciliation associations between one bank statement operation
     * and several GVV accounting entries. Validates inputs, processes each reconciliation,
     * and returns detailed JSON response with success/error counts and messages.
     * Only accessible via AJAX requests.
     * 
     * @return void Outputs JSON response with detailed operation results
     */
    public function rapprocher_multiple() {
        // Vérifier que c'est une requête AJAX
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $response = array('success' => false, 'message' => '');

        try {
            $string_releve = $this->input->post('string_releve');
            $ecriture_ids = $this->input->post('ecriture_ids');

            // Enhanced input validation
            if (empty($string_releve) || empty($ecriture_ids)) {
                $response['message'] = 'Paramètres manquants';
            } elseif (!is_string($string_releve) || strlen($string_releve) > 500) {
                $response['message'] = 'Paramètre string_releve invalide';
            } else {
                // Décoder les IDs des écritures
                $ecriture_ids_array = json_decode($ecriture_ids, true);

                if (!is_array($ecriture_ids_array) || empty($ecriture_ids_array)) {
                    $response['message'] = 'Format des IDs d\'écritures invalide';
                } else {
                    // Additional validation: check all ecriture IDs are numeric
                    $validation_failed = false;
                    foreach ($ecriture_ids_array as $ecriture_id) {
                        if (!is_numeric($ecriture_id) || $ecriture_id <= 0) {
                            $response['message'] = 'ID d\'écriture invalide: ' . $ecriture_id;
                            $validation_failed = true;
                            break;
                        }
                    }

                    if (!$validation_failed) {
                        $success_count = 0;
                        $errors = array();

                        // Créer un rapprochement pour chaque écriture
                        foreach ($ecriture_ids_array as $ecriture_id) {
                            try {
                                $result = $this->associations_ecriture_model->check_and_create([
                                    'string_releve' => $string_releve,
                                    'id_ecriture_gvv' => $ecriture_id
                                ]);

                                if ($result) {
                                    $success_count++;
                                } else {
                                    $errors[] = "Erreur lors du rapprochement de l'écriture $ecriture_id";
                                }
                            } catch (Exception $e) {
                                $errors[] = "Exception pour l'écriture $ecriture_id: " . $e->getMessage();
                            }
                        }

                        if ($success_count > 0 && empty($errors)) {
                            $response['success'] = true;
                            $response['message'] = "Rapprochement multiple effectué avec succès ($success_count écritures)";
                        } elseif ($success_count > 0 && !empty($errors)) {
                            $response['success'] = true;
                            $response['message'] = "Rapprochement partiel: $success_count succès, " . count($errors) . " erreurs";
                            $response['errors'] = $errors;
                        } else {
                            $response['message'] = 'Aucun rapprochement n\'a pu être effectué';
                            $response['errors'] = $errors;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $error_msg = 'Erreur lors du rapprochement multiple: ' . $e->getMessage();
            gvv_error($error_msg);
            $response['message'] = 'Erreur: ' . $e->getMessage();
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Delete reconciliation associations for a specific GVV accounting entry (AJAX)
     * 
     * Removes all reconciliation associations for a specific GVV accounting entry
     * identified by its ecriture_id. Called from the GVV entries tab to clean up
     * reconciliations. Only accessible via AJAX requests.
     * 
     * @return void Outputs JSON response with success/error status and message
     */
    public function supprimer_rapprochement_ecriture() {
        // Vérifier que c'est une requête AJAX
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $response = array('success' => false, 'message' => '');

        try {
            $ecriture_id = $this->input->post('ecriture_id');

            if (empty($ecriture_id)) {
                $response['message'] = 'ID d\'écriture manquant';
            } else {
                // Supprimer tous les rapprochements de cette écriture
                $result = $this->associations_ecriture_model->delete_rapprochements($ecriture_id);

                if ($result !== false) {
                    $response['success'] = true;
                    $response['message'] = 'Rapprochement supprimé avec succès';
                } else {
                    $response['message'] = 'Erreur lors de la suppression du rapprochement';
                }
            }
        } catch (Exception $e) {
            $error_msg = 'Erreur lors de la suppression de rapprochement d\'écriture: ' . $e->getMessage();
            gvv_error($error_msg);
            $response['message'] = 'Erreur: ' . $e->getMessage();
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    public function rapprochement_manuel() {
        $line = $this->input->get('line');
        $gvv_bank_account = $this->input->get('gvv_bank_account');

        if (!$line) {
            show_error('Paramètre line manquant', 400);
            return;
        }

        try {
            // Utiliser le reconciliator en cache au lieu de reparser le fichier
            $reconciliator = $this->get_reconciliator();

            // Trouver la StatementOperation spécifique par numéro de ligne (plus efficace)
            $statement_operation = $reconciliator->get_operation_by_line($line);

            if (!$statement_operation) {
                show_error('Opération non trouvée dans le relevé (ligne: ' . $line . ')', 404);
                return;
            }

            // Récupérer les paramètres de session pour le filtrage (same as import_releve_from_file)
            $filter_active = $this->session->userdata('filter_active');
            $startDate = $this->session->userdata('startDate');
            if (!$startDate) {
                $startDate = date('Y') . '-01-01';
            }
            $endDate = $this->session->userdata('endDate');
            if (!$endDate) {
                $endDate = date('Y') . '-12-31';
            }
            $filter_type = $this->session->userdata('filter_type');
            $type_selector = $this->session->userdata('type_selector');

            // Create the type dropdown (similar to import_releve_from_file)
            $type_hash = ["all" => "Tous les types"];
            $recognized_types = $reconciliator->recognized_types();
            $type_hash = array_merge($type_hash, $recognized_types);
            $type_dropdown = dropdown_field(
                'type_selector',
                $type_selector,
                $type_hash,
                'class="form-control big_select"'
            );

            // Extraire les informations depuis l'objet StatementOperation
            $string_releve = $statement_operation->str_releve();
            $amount = $statement_operation->amount();
            $date = $statement_operation->local_date();
            $nature = $statement_operation->nature();

            // Préparer les données pour la vue
            $data['statement_operation'] = $statement_operation;
            $data['header'] = $reconciliator->header();
            $data['section'] = $this->sections_model->section();
            $data['string_releve'] = $string_releve;
            $data['line'] = $line;
            $data['amount'] = $amount;
            $data['date'] = $date;
            $data['nature'] = $nature;
            $data['gvv_bank_account'] = $gvv_bank_account;
            $data['type_dropdown'] = $type_dropdown;
            
            // Add filter variables needed by the view
            $data['filter_active'] = $filter_active;
            $data['startDate'] = $startDate;
            $data['endDate'] = $endDate;
            $data['filter_type'] = $filter_type;
            $data['type_selector'] = $type_selector;

            // Récupérer les paramètres de session pour la cohérence
            $data['maxDays'] = $this->session->userdata('rapprochement_delta') ?? 5;
            $data['smartMode'] = $this->session->userdata('rapprochement_smart_mode') ?? false;

            // Récupérer toutes les écritures disponibles pour la sélection manuelle
            $gvv_bank = $reconciliator->gvv_bank_account();

            $filtered_lines = $this->get_filtered_gvv_lines($startDate, $endDate, $gvv_bank);
            $data['gvv_lines'] = $this->to_ecritures_table($filtered_lines);

            // $gvv_lines = $this->ecritures_model->select_ecritures_rapprochements($startDate, $endDate, $gvv_bank);

            // // Enrichir avec les informations de rapprochement
            // foreach ($gvv_lines as &$gvv_line) {
            //     $id = $gvv_line['id'];
            //     $rapproched = $this->associations_ecriture_model->get_rapproches($id);
            //     $gvv_line['rapproched'] = $rapproched;
            // }

            // $data['gvv_lines'] = $gvv_lines;
            $data['status'] = "";
            $data['errors'] = $this->session->userdata('errors');
            $this->session->unset_userdata('errors');

            // Charger la vue de rapprochement manuel
            $this->load->view('rapprochements/bs_rapprochement_manuel', $data);
        } catch (Exception $e) {
            $msg = "Erreur: " . $e->getMessage();
            gvv_error($msg);
            show_error($msg, 500);
        }
    }

    /**
     * Validate date input (YYYY-MM-DD format)
     * @param string $date Input date string
     * @return string Validated date or empty string if invalid
     */
    private function _validate_date($date) {
        if (empty($date)) {
            return '';
        }

        // Check format and validate date
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date_parts = explode('-', $date);
            if (checkdate($date_parts[1], $date_parts[2], $date_parts[0])) {
                return $date;
            }
        }

        gvv_error("Invalid date format: $date");
        return '';
    }

    /**
     * Validate filter type selection
     * @param string $filter_type Input filter type
     * @return string Validated filter type or default
     */
    private function _validate_filter_type($filter_type) {
        $valid_types = [
            'display_all',
            'filter_matched',
            'filter_unmatched',
            'filter_unmatched_1',
            'filter_unmatched_choices',
            'filter_unmatched_multi',
            'filter_unmatched_0'
        ];

        if (in_array($filter_type, $valid_types)) {
            return $filter_type;
        }

        if (!empty($filter_type)) {
            gvv_error("Invalid filter type: $filter_type");
        }
        return 'display_all'; // Default
    }

    /**
     * Validate type selector input
     * @param string $type_selector Input type selector
     * @return string Validated type selector
     */
    private function _validate_type_selector($type_selector) {
        if (empty($type_selector)) {
            return '';
        }

        // Sanitize and validate - should be numeric ID or empty
        $type_selector = trim($type_selector);
        if ($type_selector === '' || ctype_digit($type_selector)) {
            return $type_selector;
        }

        gvv_error("Invalid type selector: $type_selector");
        return '';
    }

    /**
     * Validate return URL to prevent open redirect vulnerability
     * @param string $return_url Input return URL
     * @return string Validated return URL or empty string
     */
    private function _validate_return_url($return_url) {
        if (empty($return_url)) {
            return '';
        }

        // Only allow internal URLs (relative paths or same domain)
        $parsed_url = parse_url($return_url);

        // Allow relative URLs
        if (!isset($parsed_url['scheme']) && !isset($parsed_url['host'])) {
            // Remove any potential dangerous characters
            $return_url = filter_var($return_url, FILTER_SANITIZE_URL);
            if ($return_url) {
                return $return_url;
            }
        }

        gvv_error("Invalid or potentially dangerous return URL: $return_url");
        return '';
    }
}
/* End of file rapprochements.php */
/* Location: ./application/controllers/rapprochements.php */
