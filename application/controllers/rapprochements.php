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

        $this->load->library('rapprochements/Reconciliator');
    }

    /**
     * Page de selection du fichier journal OpenFLyers
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
     * Import a CSV journal 
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
     * Import a CSV listing from a file - Version 2 uniquement avec Reconciliator
     */
    public function import_releve_from_file() {

        $filename = $this->session->userdata('file_releve');

        $this->load->library('rapprochements/ReleveParser');

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
            $parser = new ReleveParser();
            try {
                $parser_result = $parser->parse($filename);
                // gvv_dump($parser_result);
                $reconciliator = new Reconciliator($parser_result);
                $reconciliator->set_filename($filename);
            } catch (Exception $e) {
                $msg = "Erreur: " . $e->getMessage() . "\n";
                gvv_error($msg);

                $error = array(
                    'error' => $msg
                );
                load_last_view('rapprochements/select_releve', $error);
                return;
            }

            $data['section'] = $this->sections_model->section();

            // Version 2 uniquement - utilise l'objet Reconciliator
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
            $gvv_lines = $this->ecritures_model->select_ecritures_rapprochements($data['startDate'], $data['endDate'], $parser_result['gvv_bank']);

            $cnt = 0;
            $filtered_lines = [];
            foreach ($gvv_lines as &$line) {
                $id = $line['id'];
                $rapproched = $this->associations_ecriture_model->get_rapproches($id);
                $line['rapproched'] = $rapproched;

                if ($filter_active && $filter_type != 'display_all') {
                    if ($filter_type == '') {
                        $filtered_lines[] = $line;
                    }
                    if ($filter_type == 'filter_matched' && $rapproched) {
                        $filtered_lines[] = $line;
                    }

                    $unmatched_list = [
                        'filter_unmatched',
                        'filter_unmatched_0',
                        'filter_unmatched_1',
                        'filter_unmatched_choices',
                        'filter_unmatched_multi'
                    ];
                    if (in_array($filter_type, $unmatched_list) && !$rapproched) {
                        $filtered_lines[] = $line;
                    }
                } else {
                    $filtered_lines[] = $line;
                }
                $cnt++;
            }

            $data['gvv_lines'] = $this->to_ecritures_table($filtered_lines);

            load_last_view('rapprochements/tableRapprochements', $data);
        } catch (Exception $e) {
            gvv_error("Erreur: " . $e->getMessage() . "\n");
        }
    }

    public function rapprochez() {
        // Rapproche les écritures sélectionnées

        $post = $this->input->post();
        $counts = [];
        $operations = [];

        if ($post['button'] == 'Supprimer les rapprochements') {
            // On supprime les rapprochements
            $this->delete_rapprochement();
            return;
        }

        // Process valid selections
        foreach ($post as $key => $value) {
            if (strpos($key, 'cb_') === 0) {
                $line = str_replace('cb_', '', $key);

                if (isset($post['string_releve_' . $line])) {
                    // echo "string_releve_$line => " . $post['string_releve_' . $line] . "<br>";
                    $operations[$line] = ['string_releve' => $post['string_releve_' . $line]];
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
                        gvv_dump('op_' . $line . " not defined and no multiple ecritures found");
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
     * Supprime les rapprochements sélectionnés
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
     * Filtrage des opérations
     *     [startDate] => 2025-02-01
     *       [endDate] => 2025-01-31
     *       [filter_type] => unmatched
     *       [type_selector] => paiement_cb
     *       [button] => Filtrer
     */
    public function filter() {
        // Redirection vers la page de sélection du relevé
        $post = $this->input->post();
        // gvv_dump($post);
        $button = $post['button'] ?? '';
        if ($button == 'Filtrer') {
            // On filtre les opérations
            $start_date = $post['startDate'] ?? '';
            $end_date = $post['endDate'] ?? '';
            $filter_type = $post['filter_type'] ?? '';
            $type_selector = $post['type_selector'] ?? '';

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

        redirect('rapprochements/import_releve_from_file');
    }

    /**
     * Change le nombre de jours maximum pour le rapprochement
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
     * Change le mode de rapprochement intelligent
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
     * Supprime tous les rapprochements ou les écritures
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
     * Converts GVV lines to a table format for display
     *
     * @param array $gvv_lines Array of GVV lines to convert
     * @return array Formatted array suitable for table display
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
                $elt[] = '<input type="checkbox" name="cbdel_' . $line['id'] . '" value="1"">' . $badge;
            } else {
                $elt[] = '<input type="checkbox" name="cbdel_' . $line['id'] . '" value="1"">'
                    . '<span class="bg-danger badge text-white rounded-pill ms-1">' . $line['id'] . '</span>';
            }

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
     * Rapproche une seule opération (appelé via AJAX)
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

            if (empty($string_releve) || empty($ecriture_id)) {
                $response['message'] = 'Paramètres manquants';
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
            $response['message'] = 'Erreur: ' . $e->getMessage();
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Supprime un rapprochement unique (appelé via AJAX)
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

            if (empty($string_releve)) {
                $response['message'] = 'Paramètre manquant';
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
            $response['message'] = 'Erreur: ' . $e->getMessage();
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Rapproche plusieurs opérations en une seule transaction (appelé via AJAX)
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

            if (empty($string_releve) || empty($ecriture_ids)) {
                $response['message'] = 'Paramètres manquants';
            } else {
                // Décoder les IDs des écritures
                $ecriture_ids_array = json_decode($ecriture_ids, true);

                if (!is_array($ecriture_ids_array) || empty($ecriture_ids_array)) {
                    $response['message'] = 'Format des IDs d\'écritures invalide';
                } else {
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
        } catch (Exception $e) {
            $response['message'] = 'Erreur: ' . $e->getMessage();
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Supprime le rapprochement d'une écriture unique (appelé via AJAX depuis l'onglet GVV)
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
            $response['message'] = 'Erreur: ' . $e->getMessage();
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Page de rapprochement manuel pour une StatementOperation spécifique
     */
    public function rapprochement_manuel() {
        $string_releve = $this->input->get('string_releve');
        $line = $this->input->get('line');
        $amount = $this->input->get('amount');
        $date = $this->input->get('date');
        $nature = $this->input->get('nature');

        if (!$string_releve) {
            show_error('Paramètre string_releve manquant', 400);
            return;
        }

        // Récupérer le fichier relevé en session
        $filename = $this->session->userdata('file_releve');
        if (!$filename) {
            show_error('Aucun fichier relevé en session', 400);
            return;
        }

        $this->load->library('rapprochements/ReleveParser');

        try {
            $parser = new ReleveParser();
            $parser_result = $parser->parse($filename);
            $reconciliator = new Reconciliator($parser_result);
            $reconciliator->set_filename($filename);

            // Trouver la StatementOperation spécifique
            $statement_operation = null;
            $operations = $reconciliator->get_operations();
            
            foreach ($operations as $operation) {
                if ($operation->str_releve() === $string_releve) {
                    $statement_operation = $operation;
                    break;
                }
            }

            if (!$statement_operation) {
                show_error('Opération non trouvée dans le relevé', 404);
                return;
            }

            // Préparer les données pour la vue
            $data['statement_operation'] = $statement_operation;
            $data['header'] = $reconciliator->header();
            $data['section'] = $this->sections_model->section();
            $data['string_releve'] = $string_releve;
            $data['line'] = $line;
            $data['amount'] = $amount;
            $data['date'] = $date;
            $data['nature'] = $nature;

            // Récupérer les paramètres de session pour la cohérence
            $data['maxDays'] = $this->session->userdata('rapprochement_delta') ?? 5;
            $data['smartMode'] = $this->session->userdata('rapprochement_smart_mode') ?? false;

            // Récupérer toutes les écritures disponibles pour la sélection manuelle
            $startDate = date('Y') . '-01-01';
            $endDate = date('Y') . '-12-31';
            $gvv_lines = $this->ecritures_model->select_ecritures_rapprochements($startDate, $endDate, $parser_result['gvv_bank']);
            
            // Enrichir avec les informations de rapprochement
            foreach ($gvv_lines as &$line) {
                $id = $line['id'];
                $rapproched = $this->associations_ecriture_model->get_rapproches($id);
                $line['rapproched'] = $rapproched;
            }
            
            $data['gvv_lines'] = $gvv_lines;
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
}
/* End of file rapprochements.php */
/* Location: ./application/controllers/rapprochements.php */
