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
 * TODO: renomer en Ecriture (ou pas pour limiter les risques)
 */
include('./application/libraries/Gvv_Controller.php');
class Compta extends Gvv_Controller {
    protected $controller = 'compta';
    protected $model = 'ecritures_model';
    protected $modification_level = 'tresorier';
    protected $rules = ['club' => "callback_section_selected"];

    // Account selector filters to preserve during validation errors
    protected $emploi_selection = [];
    protected $resource_selection = [];

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();
        // Check if user is logged in or not
        if (!$this->dx_auth->is_logged_in()) {
            // For AJAX requests, don't redirect - let the method handle it
            if (!$this->input->is_ajax_request()) {
                redirect("auth/login");
            }
        }
        $this->load->model('comptes_model');
        $this->load->model('tarifs_model');
        $this->load->model('categorie_model');
        $this->load->model('attachments_model');
        $this->lang->load('compta');
        $this->lang->load('comptes');
        $this->lang->load('attachments');
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::edit()
     */
    function edit($id = "", $load_view = true, $action = MODIFICATION) {

        $section = $this->gvv_model->section();
        if (!$section) {
            $msg = $this->lang->line('gvv_compta_no_section');
            $this->session->set_flashdata('message', $msg);
            echo "Activez une section pour modifier l'écriture $msg";
        }
        $this->data = $this->gvv_model->get_by_id($this->kid, $id);
        $this->session->set_userdata('back_url', current_url());

        if ($this->data['achat']) {
            redirect("achats/edit/" . $this->data['achat']);
            return;
        }

        $this->push_return_url("edit ecriture");

        // Store whether the line is frozen to pass to view
        $is_frozen = $this->data['gel'];
        
        if ($is_frozen) {
            $this->form_static_element(VISUALISATION);
            $this->data['frozen_message'] = $this->lang->line('gvv_compta_frozen_line_cannot_modify');
        } else {
            $this->form_static_element(MODIFICATION);
        }

        $this->attachments_model->select_page(
            0,
            0,
            ['referenced_table' => 'ecritures', 'referenced_id' => $id]
        );
        $this->data[$this->kid] = $id;
        $this->data['title'] = $this->lang->line("gvv_compta_title_line");

        $section = $this->gvv_model->section();
        if ($section) {
            $this->data['title'] .= " section " . $section['nom'];
        }

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

        // Restore account selection filters from POST data (during validation errors)
        $emploi_selection_json = $this->input->post('emploi_selection');
        $resource_selection_json = $this->input->post('resource_selection');

        if ($emploi_selection_json) {
            $this->emploi_selection = json_decode($emploi_selection_json, true);
        }
        if ($resource_selection_json) {
            $this->resource_selection = json_decode($resource_selection_json, true);
        }

        // Restore title from POST data and rebuild with section
        $title_key = $this->input->post('title_key');
        if ($title_key) {
            $this->data['title_key'] = $title_key;
            // Rebuild title with section name
            $title = $this->lang->line($title_key);
            $section = $this->gvv_model->section();
            if ($section) {
                $title .= " section " . $section['nom'];
            }
            $this->data['title'] = $title;
        } else {
            $this->data['title_key'] = "gvv_compta_title_line";
        }

        // Pass selection filters to view for hidden fields
        $this->data['emploi_selection'] = $this->emploi_selection;
        $this->data['resource_selection'] = $this->resource_selection;

        // Use stored account filters to preserve selection restrictions during validation errors
        $this->gvvmetadata->set_selector('compte1_selector',
            $this->comptes_model->selector_with_null($this->emploi_selection, TRUE));
        $this->gvvmetadata->set_selector('compte2_selector',
            $this->comptes_model->selector_with_null($this->resource_selection, TRUE));

        $this->data['date_creation'] = date("d/m/Y");

        $this->data['saisie_par'] = $this->dx_auth->get_username();
        $this->data['categorie_selector'] = $this->categorie_model->selector_with_null();
        $this->gvvmetadata->set_selector('categorie_selector', $this->categorie_model->selector_with_null());

        // Restore pending attachments from session (during validation errors - PRD CA1.9)
        if ($action == CREATION) {
            $session_id = $this->session->userdata('session_id');
            $pending_key = 'pending_attachments_' . $session_id;
            $pending = $this->session->userdata($pending_key);

            if (!empty($pending)) {
                $this->data['pending_attachments'] = $pending;
            }
        }
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

        // Check if line is frozen before attempting deletion
        if ($this->data['gel']) {
            $msg = $this->lang->line('gvv_compta_frozen_line_cannot_delete');
            $this->session->set_flashdata('popup', $msg);
            $this->pop_return_url();
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
        
        // Pre-process decimal fields to clean currency formatting before validation
        $this->load->helper('validation');
        foreach ($fields_list as $field) {
            $field_type = $this->gvvmetadata->field_type($table, $field);
            $value = $this->input->post($field);
            
            // Clean currency input for decimal fields before validation
            if ($field_type == 'decimal' && $value !== '' && $value !== null) {
                $cleaned_value = clean_currency_input($value);
                $_POST[$field] = $cleaned_value; // Update $_POST for validation
                $this->data[$field] = $cleaned_value;
            } else {
                $this->data[$field] = $value;
            }
        }
        // 'annee_exercise'
        if (!isset($this->data['annee_exercise']) || empty($this->data['annee_exercise'])) {
            $this->data['annee_exercise'] = date("Y");
        }

        // Add the compte1/compte2 same account check to the extra rules before setting metadata rules
        $extra_rules = $this->rules;
        $extra_rules['compte1'] = isset($extra_rules['compte1'])
            ? $extra_rules['compte1'] . '|callback_check_compte1_compte2'
            : 'callback_check_compte1_compte2';

        $this->gvvmetadata->set_rules($table, $fields_list, $extra_rules, $action);

        if ($this->form_validation->run()) {
            // get the processed data. It must not be done before because all the
            // processing is done by the run method.
            $processed_data = $this->form2database($action);

            if ($action == CREATION) {
                unset($processed_data['id']);
                $id = $this->gvv_model->create_ecriture($processed_data);

                // Process pending attachments only if record was successfully created
                if ($id) {
                    $session_id = $this->session->userdata('session_id');
                    $this->process_pending_attachments('ecritures', $id, $session_id);
                }

                if ($button != "Créer") {
                    // Créer et continuer, on reste sur la page de création
                    $image = $this->gvv_model->image($id);
                    $msg = "Ecriture $image créée avec succés.";
                    $this->data['message'] = '<div class="text-success">' . $msg . '</div>';
                    // Display the form again
                    $this->form_static_element($action);
                    load_last_view($this->form_view, $this->data);
                    return;
                } else {
                    // Créer il faut retourner sur qq chose de logique
                    $target = "compta/journal_compte/" . $processed_data['compte1'];
                    redirect($target);
                }
            } else {
                // Modification
                $this->change_ecriture($processed_data);

                // If the entry was just frozen (gel checkbox was checked), redirect to journal instead of edit form
                if (isset($processed_data['gel']) && $processed_data['gel'] == 1) {
                    // Entry was frozen - redirect to journal to avoid "frozen entry" error message
                    $compte = isset($processed_data['compte1']) ? $processed_data['compte1'] : '';
                    if ($compte) {
                        $this->session->set_flashdata('message', 'Écriture modifiée et gelée avec succès.');
                        redirect("compta/journal_compte/" . $compte);
                    }
                }

                $this->pop_return_url(1);
            }
        }
        // Display the form again
        $this->form_static_element($action);
        load_last_view($this->form_view, $this->data);
    }

    /**
     * Écriture entre deux comptes
     */
    function ecriture(string $title_key, $emploi_selection, $resource_selection, $message = "") {
        parent::create(FALSE);
        $this->session->set_userdata('current_url', current_url());

        // Store account selection filters to preserve them during validation errors
        $this->emploi_selection = $emploi_selection;
        $this->resource_selection = $resource_selection;

        $title = $this->lang->line($title_key);
        $section = $this->gvv_model->section();
        if ($section) {
            $title .= " section " . $section['nom'];
        }
        $this->data['title'] = $title;
        $this->data['title_key'] = $title_key;
        if ($message) $this->data['message'] = $message;

        // Pass selection filters to view for hidden fields
        $this->data['emploi_selection'] = $emploi_selection;
        $this->data['resource_selection'] = $resource_selection;

        $compte1_selector = $this->comptes_model->selector_with_null($emploi_selection, TRUE);
        $this->gvvmetadata->set_selector('compte1_selector', $compte1_selector);

        $compte2_selector = $this->comptes_model->selector_with_null($resource_selection, TRUE);
        $this->gvvmetadata->set_selector('compte2_selector', $compte2_selector);

        $count_compte1_selector = count($compte1_selector) - 1;
        $count_compte2_selector = count($compte2_selector) - 1;

        $errors = "";
        if ($count_compte1_selector < 1) {
            $errors = "Pas de comptes d'emploi correspondant à ce type d'écriture. Il faut créer les comptes.";
        }
        if ($count_compte2_selector < 1) {
            $errors .= " Pas de comptes de ressource correspondant à ce type d'écriture. Il faut créer les comptes.";
        }
        if ($errors) $this->data['errors'] = $errors;

        load_last_view('compta/formView', $this->data);
    }

    /**
     * Ecriture Générale
     */
    function create() {
        $this->ecriture("gvv_compta_title_line", [], []);
    }

    /**
     * Handle attachment upload during creation (AJAX)
     * Returns JSON response with temp file info
     */
    public function upload_temp_attachment() {
        // Clean any output buffer that might contain HTML/errors
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Always set JSON content type first
        $this->output->set_content_type('application/json');

        // Log entry for debugging
        log_message('debug', 'upload_temp_attachment called - User logged in: ' . ($this->dx_auth->is_logged_in() ? 'yes' : 'no'));

        try {
            // Check authentication FIRST (before constructor redirect can trigger)
            if (!$this->dx_auth->is_logged_in()) {
                log_message('error', 'upload_temp_attachment: User not logged in');
                $this->output->set_output(json_encode([
                    'success' => false,
                    'error' => 'Session expirée. Veuillez vous reconnecter.'
                ]));
                return;
            }

            if (!$this->input->is_ajax_request()) {
                $this->output->set_output(json_encode([
                    'success' => false,
                    'error' => 'Invalid request'
                ]));
                return;
            }

            $session_id = $this->session->userdata('session_id');
            if (empty($session_id)) {
                $this->output->set_output(json_encode([
                    'success' => false,
                    'error' => 'Session invalide'
                ]));
                return;
            }

            $year = date('Y');
            $club_id = $this->session->userdata('section');
            $this->load->model('sections_model');
            $section_name = $this->sections_model->image($club_id);

            if (empty($section_name)) {
                $section_name = 'Unknown';
            }
            $section_name = $this->sanitize_filename($section_name);

        // Create temp directory
        $temp_dir = './uploads/attachments/temp/' . $session_id . '/';
        if (!file_exists($temp_dir)) {
            mkdir($temp_dir, 0777, true);
            chmod($temp_dir, 0777);
        }

        // Generate unique filename
        $storage_file = rand(100000, 999999) . '_' . $this->sanitize_filename($_FILES['file']['name']);

        // Upload file
        $config['upload_path'] = $temp_dir;
        $config['allowed_types'] = '*';
        $config['max_size'] = '20000';
        $config['file_name'] = $storage_file;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('file')) {
            // Error
            $response = [
                'success' => false,
                'error' => $this->upload->display_errors('', '')
            ];
        } else {
            // Success - store in session
            $upload_data = $this->upload->data();

            $file_info = [
                'temp_id' => uniqid(),
                'temp_path' => $temp_dir . $storage_file,
                'original_name' => $_FILES['file']['name'],
                'storage_name' => $storage_file,
                'size' => $upload_data['file_size'] * 1024, // Convert KB to bytes
                'club' => $club_id,
                'section_name' => $section_name,
                'description' => '' // PRD CA1.9: Empty by default, user can add later
            ];

            // Add to session
            $pending_key = 'pending_attachments_' . $session_id;
            $pending = $this->session->userdata($pending_key) ?: [];
            $pending[$file_info['temp_id']] = $file_info;
            $this->session->set_userdata($pending_key, $pending);

            $response = [
                'success' => true,
                'file' => $file_info
            ];
        }

            $this->output->set_output(json_encode($response));

        } catch (Exception $e) {
            log_message('error', 'Upload temp attachment error: ' . $e->getMessage());
            $this->output->set_output(json_encode([
                'success' => false,
                'error' => 'Erreur lors de l\'upload: ' . $e->getMessage()
            ]));
        }
    }

    /**
     * Remove temp attachment (AJAX)
     */
    public function remove_temp_attachment() {
        $this->output->set_content_type('application/json');

        try {
            if (!$this->input->is_ajax_request()) {
                $this->output->set_output(json_encode(['success' => false, 'error' => 'Invalid request']));
                return;
            }

            $temp_id = $this->input->post('temp_id');
            $session_id = $this->session->userdata('session_id');
            $pending_key = 'pending_attachments_' . $session_id;
            $pending = $this->session->userdata($pending_key) ?: [];

            if (isset($pending[$temp_id])) {
                // Delete file
                $file_path = $pending[$temp_id]['temp_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }

                // Remove from session
                unset($pending[$temp_id]);
                $this->session->set_userdata($pending_key, $pending);

                $response = ['success' => true];
            } else {
                $response = ['success' => false, 'error' => 'File not found'];
            }

            $this->output->set_output(json_encode($response));

        } catch (Exception $e) {
            log_message('error', 'Remove temp attachment error: ' . $e->getMessage());
            $this->output->set_output(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * Update temp attachment description (AJAX)
     * PRD CA1.9: Allow user to associate description with attachment
     */
    public function update_temp_attachment_description() {
        $this->output->set_content_type('application/json');

        try {
            if (!$this->input->is_ajax_request()) {
                $this->output->set_output(json_encode(['success' => false, 'error' => 'Invalid request']));
                return;
            }

            $temp_id = $this->input->post('temp_id');
            $description = $this->input->post('description');
            $session_id = $this->session->userdata('session_id');
            $pending_key = 'pending_attachments_' . $session_id;
            $pending = $this->session->userdata($pending_key) ?: [];

            if (isset($pending[$temp_id])) {
                // Update description
                $pending[$temp_id]['description'] = $description;
                $this->session->set_userdata($pending_key, $pending);

                $response = ['success' => true];
            } else {
                $response = ['success' => false, 'error' => 'File not found'];
            }

            $this->output->set_output(json_encode($response));

        } catch (Exception $e) {
            log_message('error', 'Update temp attachment description error: ' . $e->getMessage());
            $this->output->set_output(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * Process pending attachments after successful record creation
     *
     * @param string $referenced_table Table name (e.g., 'ecritures')
     * @param int $referenced_id ID of the created record
     * @param string $session_id Current session ID
     * @return int Number of attachments processed
     */
    private function process_pending_attachments($referenced_table, $referenced_id, $session_id) {
        $pending_key = 'pending_attachments_' . $session_id;
        $pending = $this->session->userdata($pending_key);

        if (empty($pending)) {
            return 0;
        }

        $processed = 0;
        $year = date('Y');

        foreach ($pending as $temp_id => $file_info) {
            $temp_path = $file_info['temp_path'];

            if (!file_exists($temp_path)) {
                log_message('error', "GVV: Pending attachment file not found: $temp_path");
                continue;
            }

            // Build permanent path
            $section_name = $file_info['section_name'];
            $permanent_dir = './uploads/attachments/' . $year . '/' . $section_name . '/';

            if (!file_exists($permanent_dir)) {
                mkdir($permanent_dir, 0777, true);
                chmod($permanent_dir, 0777);
            }

            $storage_name = $file_info['storage_name'];
            $permanent_path = $permanent_dir . $storage_name;

            // Move file from temp to permanent
            if (rename($temp_path, $permanent_path)) {
                gvv_debug("Moved pending attachment: $storage_name → $permanent_path");

                // Attempt compression
                $this->load->library('file_compressor');
                gvv_debug("Attempting compression for: $permanent_path");

                $compression_result = $this->file_compressor->compress($permanent_path);

                if ($compression_result['success']) {
                    $compressed_path = $compression_result['compressed_path'];
                    $final_path = $compressed_path;

                    // Delete original if path changed (for .gz files)
                    if ($permanent_path !== $compressed_path && file_exists($permanent_path)) {
                        unlink($permanent_path);
                    }

                    gvv_debug("File compressed successfully: $final_path");
                } else {
                    // Use original file
                    $final_path = $permanent_path;
                    gvv_debug("Compression skipped: " . $compression_result['error']);
                }

                // Create attachment database record (PRD CA1.9: use description from file_info)
                $attachment_data = [
                    'referenced_table' => $referenced_table,
                    'referenced_id' => $referenced_id,
                    'user_id' => $this->dx_auth->get_username(),
                    'filename' => $file_info['original_name'],
                    'description' => $file_info['description'] ?? '', // PRD CA1.9: User-provided description
                    'file' => $final_path,
                    'club' => $file_info['club']
                ];

                $this->db->insert('attachments', $attachment_data);
                $processed++;

                gvv_info("Created attachment record with file: $final_path");
            } else {
                gvv_error("Failed to move pending attachment: $temp_path → $permanent_path");
            }
        }

        // Clear session data
        $this->session->unset_userdata($pending_key);

        // Clean up temp directory
        $temp_dir = './uploads/attachments/temp/' . $session_id . '/';
        if (is_dir($temp_dir)) {
            @rmdir($temp_dir);
        }

        return $processed;
    }

    /**
     * Ecriture entre un compte de charge et un compte de banque
     */
    function depenses() {
        $this->ecriture("gvv_compta_title_depense", [
            "codec >=" => "6",
            'codec <' => "7"
        ], [
            "codec >=" => "5",
            'codec <' => "6"
        ]);
    }

    /**
     * La saisie d'une recette est juste le passage d'une écriture mais uniquement
     * sur un compte de produit.
     */
    function recettes() {
        $this->ecriture("gvv_compta_title_recette", [
            "codec >=" => "5",
            'codec <' => "6"
        ], [
            "codec >=" => "7",
            'codec <' => "8"
        ]);
    }

    /**
     * La facturation pilote est une opération entre un compte client et un
     * compte produit
     */
    function factu_pilote() {
        $this->ecriture(
            "gvv_compta_title_manual",
            ["codec =" => "411"],
            [
                "codec >=" => "7",
                'codec <' => "8"
            ],
            $this->lang->line("gvv_compta_message_advice_manual")
        );
    }

    /**
     * Credit d'un compte pilote à partir d'un compte de charge
     */
    function credit_pilote() {
        $this->ecriture("gvv_compta_title_remboursement", [
            "codec >=" => "6",
            'codec <' => "7"
        ], ["codec =" => "411"]);
    }

    /**
     * Le règlement pilote est une opération entre un compte pilote et un compte
     * de caisse.
     */
    function reglement_pilote() {
        $this->ecriture("gvv_compta_title_paiement", [
            "codec >=" => "5",
            'codec <' => "6"
        ], ["codec" => "411"]);
    }

    /**
     * Remboursement avance pilote est une opération entre un compte pilote et un compte
     * de caisse.
     */
    function debit_pilote() {
        $this->ecriture("gvv_compta_title_avance", ["codec" => "411"], [
            "codec >=" => "5",
            'codec <' => "6"
        ]);
    }

    /**
     * Enregistrement d'un avoir fournisseur
     */
    function avoir_fournisseur() {
        $this->ecriture("gvv_compta_title_avoir", ["codec" => "401"], [
            "codec >=" => "6",
            'codec <' => "7"
        ]);
    }

    /**
     * Utilisation d'un avoir fournisseur
     */
    function utilisation_avoir_fournisseur() {
        $this->ecriture("gvv_compta_title_avoir_use", [
            "codec >=" => "6",
            'codec <' => "7"
        ], ["codec" => "401"]);
    }

    /**
     * Virement entre comptes bancaire
     */
    function virement() {
        $this->ecriture("gvv_compta_title_wire", ["codec" => "512"], ["codec" => "512"]);
    }

    /**
     * Dépot d'especes en banque
     */
    function depot_especes() {
        $this->ecriture("gvv_compta_title_depot", 
        ["codec" => "512"],
        ["codec" => "531"]);
    }

   /**
     * Retrait d'argent en liquide
     */
    function retrait_liquide() {
        $this->ecriture("gvv_compta_title_retrait", 
        ["codec" => "531"],
        ["codec" => "512"]);
    }

    /**
     * Remboursement capital d'un emprunt
     */
    function remb_capital() {
        $this->ecriture("gvv_compta_title_remb_capital", 
        ["codec" => "164"],
        ["codec" => "512"]);
    }

    /**
     * Encaissement pour une section
     */
    function encaissement_pour_une_section() {
        $this->ecriture("gvv_compta_title_encaissement_section", 
        ["codec" => "512"],
        ["codec" => "467"]);
    }

     /**
     * Reversement section
     */
    function reversement_section() {
        $this->ecriture("gvv_compta_title_reversement_section",
        ["codec" => "467"],
        ["codec" => "512"]);
    }

    /**
     * Saisie simplifiée de cotisation
     * Permet d'enregistrer le paiement d'une cotisation et de générer automatiquement
     * les écritures comptables associées en une seule opération
     */
    function saisie_cotisation() {
        // Charger les modèles nécessaires
        $this->load->model('comptes_model');
        $this->load->model('licences_model');
        $this->load->model('membres_model');
        $this->load->model('configuration_model');

        // Préparer les données du formulaire
        $this->data['controller'] = 'compta';
        $this->data['action'] = 'saisie_cotisation';
        $this->data['title'] = $this->lang->line('gvv_compta_title_saisie_cotisation');

        // Initialiser les valeurs par défaut
        $this->data['date_op'] = $this->input->post('date_op') ?: date('d/m/Y');
        $this->data['annee_cotisation'] = $this->input->post('annee_cotisation') ?: date('Y');
        $this->data['pilote'] = $this->input->post('pilote') ?: '';
        $this->data['compte_banque'] = $this->input->post('compte_banque') ?: '';
        $this->data['compte_pilote'] = $this->input->post('compte_pilote') ?: '';
        $this->data['compte_recette'] = $this->input->post('compte_recette') ?: '';
        $this->data['montant'] = $this->input->post('montant') ?: '';
        $this->data['description'] = $this->input->post('description') ?: '';
        $this->data['num_cheque'] = $this->input->post('num_cheque') ?: '';

        // Préparer les sélecteurs
        $this->data['pilote_selector'] = $this->membres_model->selector_with_null(array('actif' => 1));
        $this->data['compte_banque_selector'] = $this->comptes_model->selector_comptes_512();
        $this->data['compte_pilote_selector'] = $this->comptes_model->selector_comptes_411();
        $this->data['compte_recette_selector'] = $this->comptes_model->selector_comptes_700();

        // Si un seul compte 512 existe, le présélectionner automatiquement et masquer le sélecteur
        $comptes_512 = $this->data['compte_banque_selector'];
        // Retirer l'option null pour compter les vrais comptes
        $comptes_512_sans_null = array_filter($comptes_512, function($key) {
            return $key !== '';
        }, ARRAY_FILTER_USE_KEY);
        
        $this->data['single_compte_banque'] = false;
        $this->data['compte_banque_label'] = '';
        
        if (count($comptes_512_sans_null) === 1) {
            // Présélectionner le seul compte disponible
            $compte_ids = array_keys($comptes_512_sans_null);
            $this->data['compte_banque'] = $compte_ids[0];
            $this->data['single_compte_banque'] = true;
            $this->data['compte_banque_label'] = $comptes_512_sans_null[$compte_ids[0]];
        }

        // Vérifier si un compte de recette par défaut est configuré pour les cotisations
        $compte_cotisation_config = $this->configuration_model->get_param('comptes.cotisations');
        $this->data['single_compte_recette'] = false;
        $this->data['compte_recette_label'] = '';
        
        if (!empty($compte_cotisation_config)) {
            // Utiliser le compte configuré
            $this->data['compte_recette'] = $compte_cotisation_config;
            $this->data['single_compte_recette'] = true;
            // Récupérer le libellé du compte
            if (isset($this->data['compte_recette_selector'][$compte_cotisation_config])) {
                $this->data['compte_recette_label'] = $this->data['compte_recette_selector'][$compte_cotisation_config];
            }
        }

        // Charger la vue
        load_last_view('compta/bs_saisie_cotisation_formView', $this->data);
    }

    /**
     * Validation du formulaire de saisie de cotisation
     */
    public function formValidation_saisie_cotisation() {
        // Charger les modèles nécessaires
        $this->load->model('comptes_model');
        $this->load->model('licences_model');
        $this->load->model('ecritures_model');
        $this->load->model('membres_model');

        // Définir les règles de validation
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');

        $this->form_validation->set_rules('pilote', 'Membre', 'required');
        $this->form_validation->set_rules('date_op', 'Date opération', 'required');
        $this->form_validation->set_rules('annee_cotisation', 'Année de cotisation', 'required|integer');
        $this->form_validation->set_rules('compte_banque', 'Compte banque', 'required');
        $this->form_validation->set_rules('compte_pilote', 'Compte pilote', 'required');
        $this->form_validation->set_rules('compte_recette', 'Compte recette', 'required');
        $this->form_validation->set_rules('montant', 'Montant', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('description', 'Libellé', 'trim');
        $this->form_validation->set_rules('num_cheque', 'Numéro de pièce', 'trim');

        if ($this->form_validation->run() == FALSE) {
            // Validation échouée, réafficher le formulaire avec les erreurs
            $this->saisie_cotisation();
            return;
        }

        // Récupérer les données validées
        $pilote = $this->input->post('pilote');
        $date_op = $this->input->post('date_op');
        $annee_cotisation = $this->input->post('annee_cotisation');
        $compte_banque = $this->input->post('compte_banque');
        $compte_pilote = $this->input->post('compte_pilote');
        $compte_recette = $this->input->post('compte_recette');
        $montant = $this->input->post('montant');
        $description = trim($this->input->post('description'));
        $num_cheque = $this->input->post('num_cheque');

        // Si le libellé est vide ou correspond au pattern "Cotisation YYYY", utiliser l'année de cotisation
        if (empty($description) || preg_match('/^Cotisation \d{4}$/', $description)) {
            $description = 'Cotisation ' . $annee_cotisation;
        }

        // Convertir la date du format d/m/Y vers Y-m-d
        $date_parts = explode('/', $date_op);
        if (count($date_parts) == 3) {
            $date_op_sql = $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];
        } else {
            $date_op_sql = date('Y-m-d');
        }

        // Vérifier qu'il n'y a pas de double cotisation
        if ($this->licences_model->check_cotisation_exists($pilote, $annee_cotisation)) {
            $this->data['error_message'] = $this->lang->line('gvv_compta_error_double_cotisation');
            $this->saisie_cotisation();
            return;
        }

        // Traiter la cotisation
        $result = $this->process_saisie_cotisation(
            $pilote,
            $date_op_sql,
            $annee_cotisation,
            $compte_banque,
            $compte_pilote,
            $compte_recette,
            $montant,
            $description,
            $num_cheque
        );

        if ($result) {
            $this->session->set_flashdata('success', $this->lang->line('gvv_compta_success_cotisation'));
            redirect('compta/saisie_cotisation');
        } else {
            $this->data['error_message'] = $this->lang->line('gvv_compta_error_cotisation');
            $this->saisie_cotisation();
        }
    }

    /**
     * Traite la saisie de cotisation en créant les écritures et la licence
     *
     * @return bool True si succès, false sinon
     */
    private function process_saisie_cotisation(
        $pilote,
        $date_op,
        $annee_cotisation,
        $compte_banque,
        $compte_pilote,
        $compte_recette,
        $montant,
        $description,
        $num_cheque
    ) {
        // Démarrer une transaction
        $this->db->trans_start();

        try {
            $club_id = $this->session->userdata('section');
            $username = $this->dx_auth->get_username();
            $annee_exercise = date('Y');

            log_message('debug', "Process cotisation - club_id: $club_id, username: $username, annee: $annee_exercise");
            log_message('debug', "Comptes: banque=$compte_banque, pilote=$compte_pilote, recette=$compte_recette");

            // 1. Créer l'écriture encaissement (512 → 411)
            $ecriture_encaissement = array(
                'annee_exercise' => $annee_exercise,
                'date_creation' => date('Y-m-d'),
                'date_op' => $date_op,
                'compte1' => $compte_banque,
                'compte2' => $compte_pilote,
                'montant' => $montant,
                'description' => $description,
                'type' => 0,
                'num_cheque' => $num_cheque,
                'saisie_par' => $username,
                'gel' => 0,
                'club' => $club_id,
                'categorie' => 0
            );
            $this->load->model('ecritures_model');
            $ecriture_id_1 = $this->ecritures_model->create_ecriture($ecriture_encaissement);

            log_message('debug', "Ecriture encaissement créée: ID=$ecriture_id_1");

            if (!$ecriture_id_1) {
                log_message('error', 'Échec création écriture encaissement');
                throw new Exception('Erreur lors de la création de l\'écriture encaissement');
            }

            // 2. Créer l'écriture facturation (411 → 700)
            $ecriture_facturation = array(
                'annee_exercise' => $annee_exercise,
                'date_creation' => date('Y-m-d'),
                'date_op' => $date_op,
                'compte1' => $compte_pilote,
                'compte2' => $compte_recette,
                'montant' => $montant,
                'description' => $description,
                'type' => 0,
                'num_cheque' => $num_cheque,
                'saisie_par' => $username,
                'gel' => 0,
                'club' => $club_id,
                'categorie' => 0
            );
            $ecriture_id_2 = $this->ecritures_model->create_ecriture($ecriture_facturation);

            log_message('debug', "Ecriture facturation créée: ID=$ecriture_id_2");

            if (!$ecriture_id_2) {
                log_message('error', 'Échec création écriture facturation');
                throw new Exception('Erreur lors de la création de l\'écriture facturation');
            }

            // 3. Créer la licence
            $licence_id = $this->licences_model->create_cotisation(
                $pilote,
                0, // Type 0 = cotisation simple
                $annee_cotisation,
                $date_op,
                'Cotisation enregistrée via saisie simplifiée'
            );

            log_message('debug', "Licence créée: ID=$licence_id");

            if (!$licence_id) {
                log_message('error', 'Échec création licence');
                throw new Exception('Erreur lors de la création de la licence');
            }

            // 4. Gérer les pièces jointes (si présentes)
            // Les justificatifs sont attachés uniquement à l'écriture d'encaissement (512 → 411)
            if (isset($_FILES['attachment_files']) && !empty($_FILES['attachment_files']['name'][0])) {
                $this->handle_file_uploads('ecritures', $ecriture_id_1);
            }

            // Compléter la transaction
            $this->db->trans_complete();

            log_message('debug', "Transaction complétée, statut: " . ($this->db->trans_status() ? 'SUCCESS' : 'FAILED'));

            // Vérifier le statut de la transaction
            if ($this->db->trans_status() === FALSE) {
                log_message('error', 'Transaction échouée (trans_status = FALSE)');
                return false;
            }

            log_message('info', "Cotisation enregistrée avec succès - Écritures: $ecriture_id_1, $ecriture_id_2 - Licence: $licence_id");
            return true;

        } catch (Exception $e) {
            // En cas d'erreur, rollback automatique
            $this->db->trans_rollback();
            log_message('error', 'Erreur process_saisie_cotisation: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Méthode AJAX pour récupérer le compte 411 d'un pilote
     * Retourne les informations du compte au format JSON
     */
    public function ajax_get_compte_pilote() {
        // Vérifier que l'utilisateur est connecté
        if (!$this->dx_auth->is_logged_in()) {
            echo json_encode(['success' => false, 'message' => 'Non autorisé - veuillez vous connecter']);
            return;
        }

        $pilote_id = $this->input->post('pilote_id');
        
        log_message('debug', "ajax_get_compte_pilote appelé pour pilote_id: $pilote_id");
        
        if (empty($pilote_id)) {
            echo json_encode(['success' => false, 'message' => 'Pilote non spécifié']);
            return;
        }

        try {
            $this->load->model('comptes_model');
            $compte = $this->comptes_model->compte_pilote($pilote_id);
            
            if ($compte) {
                log_message('debug', "Compte trouvé pour pilote $pilote_id: " . $compte['id']);
                echo json_encode([
                    'success' => true,
                    'compte_id' => $compte['id'],
                    'compte_label' => $compte['nom']
                ]);
            } else {
                log_message('debug', "Aucun compte 411 trouvé pour pilote $pilote_id");
                echo json_encode([
                    'success' => false,
                    'message' => 'Aucun compte 411 trouvé pour ce pilote'
                ]);
            }
        } catch (Exception $e) {
            log_message('error', 'Erreur ajax_get_compte_pilote: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la récupération du compte'
            ]);
        }
    }

    /**
     * Gère l'upload des fichiers joints lors de la soumission du formulaire
     * Utilise la bibliothèque CodeIgniter Upload (comme dans attachments.php)
     *
     * @param string $referenced_table Table de référence (ex: 'ecritures')
     * @param int $referenced_id ID de l'enregistrement
     * @return bool True si succès, false sinon
     */
    private function handle_file_uploads($referenced_table, $referenced_id) {
        $year = date('Y');
        $club_id = $this->session->userdata('section');
        $this->load->model('sections_model');
        $section_name = $this->sections_model->image($club_id);

        if (empty($section_name)) {
            $section_name = 'Unknown';
        }
        $section_name = $this->sanitize_filename($section_name);

        // Créer le répertoire de destination (avec section pour cohérence)
        $upload_dir = './uploads/attachments/' . $year . '/' . $section_name . '/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
            chmod($upload_dir, 0777);
        }

        $this->load->model('attachments_model');
        $files = $_FILES['attachment_files'];
        $file_count = count($files['name']);

        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] == UPLOAD_ERR_OK) {
                // Générer un nom de fichier unique
                $original_name = $files['name'][$i];
                $storage_file = rand(100000, 999999) . '_' . $this->sanitize_filename($original_name);

                // Configurer la bibliothèque Upload CI (comme dans attachments.php)
                $config = [
                    'upload_path' => $upload_dir,
                    'allowed_types' => '*',
                    'max_size' => 20000, // 20MB en kilobytes
                    'file_name' => $storage_file,
                    'overwrite' => false
                ];

                // Initialiser/réinitialiser la bibliothèque upload avec la nouvelle config
                $this->load->library('upload', $config);
                $this->upload->initialize($config);

                // Simuler un upload depuis le tableau $_FILES multiple
                // On doit temporairement remplacer $_FILES pour que do_upload() fonctionne
                $temp_files = $_FILES;
                $_FILES['userfile'] = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];

                if ($this->upload->do_upload('userfile')) {
                    // Upload réussi
                    $upload_data = $this->upload->data();
                    $file_path = $upload_dir . $storage_file;

                    // Enregistrer dans la base de données (même structure que attachments)
                    $attachment_data = [
                        'referenced_table' => $referenced_table,
                        'referenced_id' => $referenced_id,
                        'user_id' => $this->dx_auth->get_username(),
                        'filename' => $original_name,
                        'description' => '', // Vide par défaut
                        'file' => $file_path,
                        'club' => $club_id
                    ];

                    $this->db->insert('attachments', $attachment_data);
                    log_message('debug', "Attachment created: $file_path for $referenced_table #$referenced_id");
                } else {
                    // Erreur d'upload
                    $error = $this->upload->display_errors('', '');
                    log_message('error', "Failed to upload file: $original_name - $error");

                    // Restaurer $_FILES et lancer l'exception
                    $_FILES = $temp_files;
                    throw new Exception("Erreur lors de l'upload du fichier $original_name: $error");
                }

                // Restaurer $_FILES
                $_FILES = $temp_files;

            } elseif ($files['error'][$i] != UPLOAD_ERR_NO_FILE) {
                // Erreur d'upload (sauf si aucun fichier sélectionné)
                log_message('error', "Upload error for file $i: " . $files['error'][$i]);
                throw new Exception("Erreur lors de l'upload du fichier: " . $files['name'][$i]);
            }
        }

        return true;
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
        // warning_count
        $this->data['count'] = $this->gvv_model->count_account();

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

        // warning_count
        // $iTotal = $this->ecritures_model->count();
        $iTotal = $this->gvv_model->count_account();
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
        $title = $this->lang->line('gvv_comptes_title_journal');
        $section = $this->gvv_model->section();
        if ($section) {
            $title .= " - section - " . $section['nom'];
        }
        $title .= " $year";

        $this->selection_filter();
        $selection = $this->gvv_model->select_journal('');
        if ($mode == 'csv') {
            $this->gvvmetadata->csv("vue_journal", array('title' => $title));
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
            $pdf->set_title($title);
            $pdf->AddPage('L');

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
                    10,   // id
                    17,   // date_op
                    12,   // code1
                    38,   // compte1 (reduced from 40)
                    12,   // code2
                    38,   // compte2 (reduced from 40)
                    75,   // description (reduced from 80)
                    35,   // num_cheque (reduced from 40)
                    24    // montant (increased from 16 to fit "99 999,99 €")
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
        $this->data['compte_selector'] = $this->comptes_model->selector_with_all([], true);

        $year = $this->session->userdata('year');
        $this->data['year_selector'] = $this->gvv_model->getYearSelector("date_op");
        $this->data['year'] = $year;

        $this->selection_filter();

        // par défaut on utilise le début et la fin de l'année
        $date_deb = "01/01/$year";
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

        $solde_previous_year = $this->ecritures_model->solde_compte($compte, $date_deb, "<");

        $solde_deb = $this->ecritures_model->solde_compte($compte, $date_deb, $operation = "<");
        $solde_fin = $this->ecritures_model->solde_compte($compte, $date_fin, $operation = "<=");
        $this->data['date_deb'] = $date_deb;
        $this->data['date_fin'] = $date_fin;
        $this->data['solde_avant'] = $solde_deb;
        $this->data['solde_fin'] = $solde_fin;

        // echo "debut $date_deb, solde=$solde_deb fin=$date_fin, solde=$solde_fin" . br();
        // warning_count
        $this->data['count'] = $this->gvv_model->count_account($compte);
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
            // recette ou dépense, ce sont des comptes remis à 0 en fin d'exercice
            // donc on ajuste pour ne prendre que l'exercice en compte
            $this->data['solde_avant'] -= $solde_previous_year;
            $this->data['solde_fin'] -= $solde_previous_year;
        }
        $this->data['has_modification_rights'] = (!isset($this->modification_level) || $this->dx_auth->is_role($this->modification_level, true, true));
    }

    /**
     * Display account extract
     */
    private function journal_data($data, $compte = '', $premier = 0, $message = '') {
        $this->select_data($data, $compte, $premier, $message);
        $this->data['section'] = $this->gvv_model->section();
        // Add section name for display in the form
        $section = $this->gvv_model->section();
        $this->data['section_name'] = $section ? $section['nom'] : '';
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
        if (!preg_match("/favicon/", $current_url) && !preg_match("/filterValidation/", $current_url) && !preg_match("/switch_line/", $current_url)) {
            $this->push_return_url("journal compte");
        }

        $data = $this->comptes_model->get_by_id('id', $compte);

        // if no account is found
        if (count($data) == 0) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_comptes_error_account_not_found'));
            redirect("comptes/balance");
        }

        // or it is not an account of the current section
        if ($this->gvv_model->section() && ($this->gvv_model->section_id() != $data['club'])) {
            $this->session->set_flashdata('error', $this->lang->line('gvv_comptes_error_account_not_found'));
            redirect("comptes/balance");
        }

        $this->journal_data($data, $compte, $premier, $message);
    }

    /**
     * AJAX endpoint for DataTables server-side processing (older format)
     */
    function datatable_journal_compte($compte = '') {
        // Clear any previous output buffer to prevent contamination
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Set JSON content type early
        $this->output->set_content_type('application/json');
        
        // Check authentication
        if (!$this->dx_auth->is_logged_in()) {
            $this->output->set_output(json_encode(['error' => 'Not authenticated']));
            return;
        }

        $data = $this->comptes_model->get_by_id('id', $compte);

        // Check account exists and user has access
        if (count($data) == 0) {
            $this->output->set_output(json_encode(['error' => 'Account not found: ' . $compte]));
            return;
        }
        
        if ($this->gvv_model->section() && ($this->gvv_model->section_id() != $data['club'])) {
            $this->output->set_output(json_encode(['error' => 'Access denied to account: ' . $compte]));
            return;
        }

        // Authorization check - same logic as select_data()
        $user = $this->comptes_model->user($compte);
        $mlogin = $this->dx_auth->get_username();
        $info_pilote = $this->membres_model->get_by_id('mlogin', $mlogin);

        // Check that the user has the right to display this account
        $authorized = false;
        if ($user == $mlogin) {
            $authorized = true;
        } else if ($this->dx_auth->is_role('bureau', true, true)) {
            $authorized = true;
        } else if ($compte == $info_pilote['compte']) {
            $authorized = true;
        }

        if (!$authorized) {
            log_message('error', "Unauthorized access attempt to datatable_journal_compte by user=$mlogin for compte=$compte");
            $this->output->set_output(json_encode(['error' => 'Access denied']));
            return;
        }

        try {
            // Get DataTables parameters in older format (GET params)
            $sEcho = isset($_GET['sEcho']) ? intval($_GET['sEcho']) : 1;
            $iDisplayStart = isset($_GET['iDisplayStart']) ? intval($_GET['iDisplayStart']) : 0;
            $iDisplayLength = isset($_GET['iDisplayLength']) ? intval($_GET['iDisplayLength']) : 100;
            
            // Handle search parameter - clean it properly
            $sSearch = isset($_GET['sSearch']) ? trim($_GET['sSearch']) : '';
            
            // Debug search specifically
            if (!empty($sSearch)) {
                log_message('debug', "DataTables: Search request with term: '$sSearch'");
            }

            // Build SQL query with filters
            $this->load->model('ecritures_model');
            
            // Get total count without search filter
            $total_count = $this->gvv_model->count_account($compte);
            
            // Get filtered data using our method
            $result = $this->ecritures_model->get_datatable_data([
                'compte' => $compte,
                'start' => $iDisplayStart,
                'length' => $iDisplayLength,
                'search' => $sSearch,
                'order_column' => 'date_op', // Default for now
                'order_direction' => 'ASC'  // Chronological order (oldest first) for correct balance display
            ]);

            $filtered_count = $result['filtered_count'];
            $ecritures = $result['data'];
            
            // Add debugging
            log_message('debug', "DataTables: Model returned filtered_count=$filtered_count, data rows=" . count($ecritures));
            if (count($ecritures) > 0) {
                log_message('debug', "DataTables: First row keys: " . implode(', ', array_keys($ecritures[0])));
            }

            // Check permissions for actions
            $section = $this->gvv_model->section();
            $has_modification_rights = (!isset($this->modification_level) || $this->dx_auth->is_role($this->modification_level, true, true));

            // Format data for older DataTables format
            $aaData = [];
            foreach ($ecritures as $ecriture) {
                $row = [];
                
                // Actions column first
                if ($has_modification_rights && $section) {
                    $actions = '';
                    $is_frozen = isset($ecriture['gel']) && $ecriture['gel'] == '1';

                    // Edit/View button - shows eye icon when frozen (view mode), edit icon otherwise
                    if ($is_frozen) {
                        // View button (eye icon) - active even when frozen, same blue color as edit
                        $actions .= '<a href="' . site_url("compta/edit/{$ecriture['id']}") . '" class="btn btn-sm btn-primary edit-entry-btn view-mode" title="Visualiser" data-ecriture-id="' . $ecriture['id'] . '" data-frozen="1"><i class="fas fa-eye"></i></a> ';
                    } else {
                        // Edit button (edit icon) - normal mode
                        $actions .= '<a href="' . site_url("compta/edit/{$ecriture['id']}") . '" class="btn btn-sm btn-primary edit-entry-btn" title="Modifier" data-ecriture-id="' . $ecriture['id'] . '" data-frozen="0"><i class="fas fa-edit"></i></a> ';
                    }

                    // Delete button - disabled if frozen
                    $disabled_class = $is_frozen ? ' disabled' : '';
                    $disabled_attr = $is_frozen ? ' disabled tabindex="-1" aria-disabled="true"' : '';
                    $delete_onclick = $is_frozen ? '' : ' onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer cette écriture ?\')"';
                    $actions .= '<a href="' . site_url("compta/delete/{$ecriture['id']}") . '" class="btn btn-sm btn-danger delete-entry-btn' . $disabled_class . '" title="' . ($is_frozen ? 'Écriture gelée' : 'Supprimer') . '"' . $disabled_attr . $delete_onclick . ' data-ecriture-id="' . $ecriture['id'] . '"><i class="fas fa-trash"></i></a>';

                    $row[] = $actions;
                }
                
                // Add basic data validation and fallbacks
                $row[] = date_db2ht($ecriture['date_op']); 
                
                // Make "Autre compte" a clickable link to the other account's journal
                $autre_compte_nom = isset($ecriture['autre_nom_compte']) ? $ecriture['autre_nom_compte'] : '';
                $autre_compte_id = isset($ecriture['autre_compte']) ? $ecriture['autre_compte'] : '';
                if (!empty($autre_compte_id) && !empty($autre_compte_nom)) {
                    $autre_compte_link = '<a href="' . site_url("compta/journal_compte/$autre_compte_id") . '">' . htmlspecialchars($autre_compte_nom) . '</a>';
                    $row[] = $autre_compte_link;
                } else {
                    $row[] = $autre_compte_nom;
                }
                
                // Add description with paperclip icon
                $description = isset($ecriture['description']) ? $ecriture['description'] : '';
                $ecriture_id = $ecriture['id'];

                // Get attachment count
                $this->db->where('referenced_table', 'ecritures');
                $this->db->where('referenced_id', $ecriture_id);
                $attachment_count = $this->db->count_all_results('attachments');

                // Build paperclip icon with appropriate color
                $icon_class = $attachment_count > 0 ? 'text-success fw-bold' : 'text-muted';
                $title = $attachment_count > 0 ? $attachment_count . ' justificatif(s)' : 'Aucun justificatif';

                $date_op = isset($ecriture['date_op']) ? $ecriture['date_op'] : '';
                $debit = isset($ecriture['debit']) ? $ecriture['debit'] : '';
                $credit = isset($ecriture['credit']) ? $ecriture['credit'] : '';

                $icon_html = '<i class="fas fa-paperclip ' . $icon_class . ' attachment-icon" ' .
                    'data-ecriture-id="' . $ecriture_id . '" ' .
                    'data-attachment-count="' . $attachment_count . '" ' .
                    'data-date="' . $date_op . '" ' .
                    'data-description="' . htmlspecialchars($description) . '" ' .
                    'data-debit="' . $debit . '" ' .
                    'data-credit="' . $credit . '" ' .
                    'style="cursor: pointer; margin-right: 5px; font-size: 1.1em;" ' .
                    'title="' . $title . '"></i>';

                $row[] = $icon_html . htmlspecialchars($description);
                $row[] = isset($ecriture['num_cheque']) ? $ecriture['num_cheque'] : '';
                $row[] = isset($ecriture['prix']) ? euros($ecriture['prix']) : '';
                $row[] = isset($ecriture['quantite']) ? $ecriture['quantite'] : '';
                $row[] = isset($ecriture['debit']) ? euros($ecriture['debit']) : '';
                $row[] = isset($ecriture['credit']) ? euros($ecriture['credit']) : '';
                $row[] = isset($ecriture['solde']) ? euros($ecriture['solde']) : '';
                
                // Gel column as checkbox with AJAX functionality
                $gel_checked = ($ecriture['gel'] == '1') ? 'checked="checked"' : '';
                $gel_checkbox = '<input type="checkbox" class="gel-checkbox" data-ecriture-id="' . $ecriture['id'] . '" ' . $gel_checked . ' />';
                $row[] = $gel_checkbox;
                
                $aaData[] = $row;
            }

            // Log some debug info
            log_message('debug', "DataTables: Found " . count($ecritures) . " rows, formatted " . count($aaData) . " rows");

            // Use older DataTables response format
            $output = [
                'sEcho' => $sEcho,
                'iTotalRecords' => intval($total_count),
                'iTotalDisplayRecords' => intval($filtered_count),
                'aaData' => $aaData
            ];

            // Ensure clean JSON output
            $json = json_encode($output);
            if ($json === false) {
                throw new Exception('JSON encoding failed: ' . json_last_error_msg());
            }
            
            $this->output->set_output($json);
            
        } catch (Exception $e) {
            log_message('error', 'DataTables AJAX error: ' . $e->getMessage());
            // Return valid JSON even for errors
            $error_output = [
                'sEcho' => isset($sEcho) ? $sEcho : 1,
                'iTotalRecords' => 0,
                'iTotalDisplayRecords' => 0,
                'aaData' => [],
                'error' => $e->getMessage()
            ];
            $this->output->set_output(json_encode($error_output));
        }
    }

    /**
     * AJAX endpoint to toggle gel (freeze) status of an ecriture
     */
    function toggle_gel() {
        // Set JSON content type
        $this->output->set_content_type('application/json');
        
        // Check authentication
        if (!$this->dx_auth->is_logged_in()) {
            $this->output->set_output(json_encode(['success' => false, 'message' => 'Not authenticated']));
            return;
        }
        
        // Check if user has modification rights
        $has_modification_rights = (!isset($this->modification_level) || $this->dx_auth->is_role($this->modification_level, true, true));
        if (!$has_modification_rights) {
            $this->output->set_output(json_encode(['success' => false, 'message' => 'Insufficient permissions']));
            return;
        }
        
        // Get POST parameters
        $id = $this->input->post('id');
        $gel = $this->input->post('gel');
        
        if (!$id || !is_numeric($id)) {
            $this->output->set_output(json_encode(['success' => false, 'message' => 'Invalid ID']));
            return;
        }
        
        try {
            // Load the ecritures model
            $this->load->model('ecritures_model');
            
            // Update the gel status
            $update_data = ['gel' => intval($gel)];
            $this->ecritures_model->update('id', $update_data, $id);
            
            $this->output->set_output(json_encode(['success' => true, 'message' => 'Status updated']));
            
        } catch (Exception $e) {
            log_message('error', 'Toggle gel error: ' . $e->getMessage());
            $this->output->set_output(json_encode(['success' => false, 'message' => 'Database error']));
        }
    }

    /**
     * Debug endpoint to test datatable data retrieval
     */
    function debug_datatable($compte = '775') {
        // Log that we reached this method
        log_message('debug', 'DEBUG_DATATABLE: Method called with compte: ' . $compte);
        
        // Set JSON content type
        $this->output->set_content_type('application/json');
        
        // Skip authentication for debugging
        // if (!$this->dx_auth->is_logged_in()) {
        //     $this->output->set_output(json_encode(['error' => 'Not authenticated']));
        //     return;
        // }
        
        try {
            log_message('debug', 'DEBUG_DATATABLE: Loading ecritures_model');
            $this->load->model('ecritures_model');
            
            // Test if the method exists
            if (!method_exists($this->ecritures_model, 'get_datatable_data')) {
                log_message('debug', 'DEBUG_DATATABLE: Method does not exist');
                $this->output->set_output(json_encode(['error' => 'Method get_datatable_data does not exist']));
                return;
            }
            
            log_message('debug', 'DEBUG_DATATABLE: Calling get_datatable_data');
            
            // Test simple parameters
            $result = $this->ecritures_model->get_datatable_data([
                'compte' => $compte,
                'start' => 0,
                'length' => 5,
                'search' => '',
                'order_column' => 'date_op',
                'order_direction' => 'DESC'
            ]);
            
            log_message('debug', 'DEBUG_DATATABLE: Method completed successfully');
            
            $response = [
                'debug' => true,
                'compte' => $compte,
                'result_structure' => array_keys($result),
                'data_count' => count($result['data']),
                'filtered_count' => $result['filtered_count'],
                'first_row' => isset($result['data'][0]) ? $result['data'][0] : null,
                'sql_last_query' => $this->db->last_query()
            ];
            
            $this->output->set_output(json_encode($response, JSON_PRETTY_PRINT));
            
        } catch (Exception $e) {
            log_message('error', 'DEBUG_DATATABLE: Exception: ' . $e->getMessage());
            $this->output->set_output(json_encode(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]));
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
            $compte = $this->comptes_model->compte_pilote_id($pilote);
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
        if (isset($info_pilote['compte']) && ($info_pilote['compte'] !== "0")) {
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
            $compte = $this->comptes_model->compte_pilote_id($user);
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
        $pdf->AddPage('L');  // 'L' for Landscape orientation

        // Build title with club name and section name if available
        $title = $nom_club;
        $section = $this->gvv_model->section();
        if ($section) {
            $title .= " section " . $section['nom'];
        }
        $pdf->title($title, 1);

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
                20,
                95,
                40,
                18,
                18,
                25,
                25,
                29
            );
            $align = array(
                'L',
                'L',
                'L',
                'R',
                'R',
                'R',
                'R',
                'R'
            );
            $data[0] = $this->lang->line("gvv_compta_csv_header_411");
        } else {
            $w = array(
                20,
                12,
                35,
                95,
                40,
                25,
                25,
                29
            );
            $align = array(
                'L',
                'R',
                'L',
                'L',
                'L',
                'R',
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
            $solde_formatted = isset($row['solde']) ? euro($row['solde'], $separator, 'pdf') : '';
            $data_row[] = $solde_formatted;
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

        // Génération d'un nom de fichier explicite
        date_default_timezone_set('Europe/Paris');
        $date_generation = date("Y-m-d");
        
        // Formatage des dates de période
        $date_debut = isset($this->data['date_deb']) ? str_replace('/', '-', $this->data['date_deb']) : '';
        $date_fin = isset($this->data['date_fin']) ? str_replace('/', '-', $this->data['date_fin']) : '';
        
        // Construction du nom de fichier
        $filename = "extrait_compte_" . $compte;
        if (!empty($date_debut)) {
            $filename .= "_" . $date_debut;
        }
        if (!empty($date_fin)) {
            $filename .= "_" . $date_fin;
        }
        $filename .= "_" . $date_generation . ".pdf";

        $pdf->Output($filename, 'I');
    }

    /**
     * Échappe un champ pour l'export CSV selon RFC 4180
     * 
     * Encadre le champ avec des guillemets doubles si nécessaire :
     * - Si le champ contient un point-virgule (séparateur)
     * - Si le champ contient un guillemet double
     * - Si le champ contient un retour à la ligne
     * 
     * Les guillemets doubles dans le champ sont doublés selon la norme.
     * 
     * @param string $field Le champ à échapper
     * @return string Le champ échappé
     */
    private function csv_escape($field) {
        // Si le champ est null ou vide, retourner une chaîne vide
        if ($field === null || $field === '') {
            return '';
        }
        
        // Convertir en chaîne si ce n'est pas déjà le cas
        $field = (string)$field;
        
        // Si le champ contient un point-virgule, un guillemet double, ou un retour à la ligne
        // il doit être encadré de guillemets doubles
        if (strpos($field, ';') !== false || 
            strpos($field, '"') !== false || 
            strpos($field, "\n") !== false || 
            strpos($field, "\r") !== false) {
            
            // Doubler les guillemets doubles existants (RFC 4180)
            $field = str_replace('"', '""', $field);
            
            // Encadrer avec des guillemets doubles
            return '"' . $field . '"';
        }
        
        // Sinon, retourner le champ tel quel
        return $field;
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
            $compte = $this->comptes_model->compte_pilote_id($user);
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
            // Add section name to CSV export if available
            $section = $this->gvv_model->section();
            if ($section) {
                $str .= $this->lang->line("gvv_compta_label_section") . ":; " . $section['nom'] . ";;\n";
            }
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
                $str .= $this->csv_escape($nom_compte) . "; ";
            }
            // Encadrer les champs texte avec des guillemets doubles selon RFC 4180
            // pour gérer correctement les point-virgules et autres caractères spéciaux
            $str .= $this->csv_escape($row['description']) . "; ";
            $str .= $this->csv_escape($row['num_cheque']) . "; ";
            if ($this->data['codec'] == 411) {
                $str .= $prix . "; ";
                $str .= $quantite . "; ";
            }
            $str .= $debit . "; ";
            $str .= $credit . "; ";
            $solde_formatted = isset($row['solde']) ? number_format($row['solde'], 2, ",", "") : '';
            $str .= $solde_formatted . "; ";
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

        // Génération d'un nom de fichier explicite
        date_default_timezone_set('Europe/Paris');
        $date_generation = date("Y-m-d");
        
        // Formatage des dates de période
        $date_debut = isset($this->data['date_deb']) ? str_replace('/', '-', $this->data['date_deb']) : '';
        $date_fin = isset($this->data['date_fin']) ? str_replace('/', '-', $this->data['date_fin']) : '';
        
        // Construction du nom de fichier
        $filename = "extrait_compte_" . $compte;
        if (!empty($date_debut)) {
            $filename .= "_" . $date_debut;
        }
        if (!empty($date_fin)) {
            $filename .= "_" . $date_fin;
        }
        $filename .= "_" . $date_generation . ".csv";

        // Load the download helper and send the file to your desktop
        $this->load->helper('download');
        force_download($filename, $str);
    }

    /**
     * Pointe les écritures (AJAX)
     * Returns JSON response for checkbox toggle
     *
     * @param unknown_type $id
     * @param unknown_type $state
     *            avant bascule
     */
    function switch_line($id, $state, $compte, $premier) {
        header('Content-Type: application/json');
        
        $new_state = ($state == 0) ? 1 : 0;
        $this->gvv_model->switch_line($id, $new_state);
        
        // Return JSON success response
        echo json_encode([
            'success' => true,
            'new_state' => $new_state,
            'id' => $id
        ]);
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
     * Create attachment via AJAX
     */
    public function create_attachment() {
        header('Content-Type: application/json');

        // Check authorization - only tresorier can create attachments
        if (!has_role('tresorier')) {
            echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
            return;
        }

        try {
            $ecriture_id = isset($_POST['ecriture_id']) ? $_POST['ecriture_id'] : $this->input->post('ecriture_id');
            $description = isset($_POST['description']) ? $_POST['description'] : $this->input->post('description');

            // Debug logging
            log_message('debug', 'Create attachment - ecriture_id: ' . var_export($ecriture_id, true));
            log_message('debug', 'Create attachment - description: ' . var_export($description, true));
            log_message('debug', 'Create attachment - $_POST: ' . print_r($_POST, true));

            if (!$ecriture_id) {
                echo json_encode(['success' => false, 'error' => 'ID écriture manquant']);
                return;
            }

            if (empty($_FILES['file']['name'])) {
                echo json_encode(['success' => false, 'error' => 'Fichier requis']);
                return;
            }

            // Get section name from ecriture
            $ecriture = $this->db->where('id', $ecriture_id)->get('ecritures')->row_array();
            if (!$ecriture) {
                echo json_encode(['success' => false, 'error' => 'Écriture introuvable']);
                return;
            }

            $club_id = $ecriture['club'];
            $this->load->model('sections_model');
            $section_name = $this->sections_model->image($club_id);

            if (empty($section_name)) {
                $section_name = 'Unknown';
            }

            // Sanitize section name for directory
            $section_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $section_name);

            $year = date('Y');
            $dirname = './uploads/attachments/' . $year . '/' . $section_name . '/';

            if (!file_exists($dirname)) {
                mkdir($dirname, 0777, true);
                chmod($dirname, 0777);
            }

            // Generate unique filename
            $storage_file = rand(100000, 999999) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['file']['name']);

            $config['upload_path'] = $dirname;
            $config['allowed_types'] = '*';
            $config['max_size'] = '20000';
            $config['file_name'] = $storage_file;

            $this->load->library('upload', $config);

            if (!$this->upload->do_upload('file')) {
                echo json_encode(['success' => false, 'error' => $this->upload->display_errors('', '')]);
                return;
            }

            $file_path = $dirname . $storage_file;

            // Attempt compression
            $this->load->library('file_compressor');
            $compression_result = $this->file_compressor->compress($file_path);

            if ($compression_result['success']) {
                $file_path = $compression_result['compressed_path'];
            }

            // Get current username (not user_id - the field name is misleading)
            $user_id = $this->dx_auth->get_username();

            // Debug: log user_id
            log_message('debug', 'Create attachment - user_id (username): ' . var_export($user_id, true));

            // Insert into database
            $insert_data = [
                'referenced_table' => 'ecritures',
                'referenced_id' => $ecriture_id,
                'user_id' => $user_id,
                'description' => $description,
                'file' => $file_path,
                'club' => $club_id
            ];

            log_message('debug', 'Create attachment - insert_data: ' . print_r($insert_data, true));

            $this->db->insert('attachments', $insert_data);
            $attachment_id = $this->db->insert_id();

            if (!$attachment_id) {
                // Clean up uploaded file
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'insertion en base de données']);
                return;
            }

            // Return success with attachment data
            $file_url = base_url() . ltrim($file_path, './');
            echo json_encode([
                'success' => true,
                'attachment_id' => $attachment_id,
                'description' => $description,
                'file_name' => basename($file_path),
                'file_url' => $file_url
            ]);

        } catch (Exception $e) {
            log_message('error', 'Create attachment error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Get attachments section HTML for AJAX modal display
     */
    public function get_attachments_section($ecriture_id) {
        try {
            // Load necessary models and language
            $this->load->model('attachments_model');
            $this->lang->load('attachments');

            // Get existing attachments directly from database
            $this->db->where('referenced_table', 'ecritures');
            $this->db->where('referenced_id', $ecriture_id);
            $query = $this->db->get('attachments');
            $attachments = $query->result_array();

            // Build attachments section HTML
            $html = '<div class="ms-4">';

            // Add inline creation form - only for tresorier role
            if (has_role('tresorier')) {
                $html .= '<div class="card mb-3" id="createAttachmentCard" style="display: none;">';
                $html .= '<div class="card-body">';
                $html .= '<h5 class="card-title">Nouveau justificatif</h5>';
                $html .= '<div class="mb-2">';
                $html .= '<label class="form-label">Description</label>';
                $html .= '<input type="text" class="form-control form-control-sm" id="newDescription" placeholder="Description du justificatif">';
                $html .= '</div>';
                $html .= '<div class="mb-2">';
                $html .= '<label class="form-label">Fichier</label>';
                $html .= '<input type="file" class="form-control form-control-sm" id="newFile" accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx">';
                $html .= '</div>';
                $html .= '<div class="text-danger mb-2" id="createErrorMessage" style="display: none;"></div>';
                $html .= '<button class="btn btn-sm btn-success" id="saveNewAttachment"><i class="fas fa-save"></i> Enregistrer</button> ';
                $html .= '<button class="btn btn-sm btn-secondary" id="cancelNewAttachment"><i class="fas fa-times"></i> Annuler</button>';
                $html .= '</div>';
                $html .= '</div>';
            }

            // Add "Create" button - only for tresorier role
            if (has_role('tresorier')) {
                $html .= '<div class="mb-3">';
                $html .= '<button class="btn btn-sm btn-success" id="showCreateForm"><i class="fas fa-plus"></i> Créer</button>';
                $html .= '</div>';
            }

            // Generate attachments table with inline editing
            if (!empty($attachments)) {
                $html .= '<table class="table table-striped table-sm" id="attachmentsTable">';
                $html .= '<thead><tr>';
                $html .= '<th style="width: 40%;">Description</th>';
                $html .= '<th style="width: 35%;">Fichier</th>';
                $html .= '<th style="width: 25%;">Actions</th>';
                $html .= '</tr></thead><tbody>';

                foreach ($attachments as $attachment) {
                    $attach_id = $attachment['id'];
                    $html .= '<tr id="attachment-row-' . $attach_id . '" data-attachment-id="' . $attach_id . '">';

                    // Description cell - with view/edit mode
                    $html .= '<td class="attachment-cell">';
                    $html .= '<div class="view-mode">';
                    $html .= '<span class="description-text">' . htmlspecialchars($attachment['description']) . '</span>';
                    $html .= '</div>';
                    $html .= '<div class="edit-mode" style="display: none;">';
                    $html .= '<input type="text" class="form-control form-control-sm description-input" value="' . htmlspecialchars($attachment['description']) . '">';
                    $html .= '<div class="text-danger mt-1 error-message" style="display: none;"></div>';
                    $html .= '</div>';
                    $html .= '</td>';

                    // File cell - with view/edit mode
                    $file_path = $attachment['file'];
                    $file_name = basename($file_path);
                    $html .= '<td class="attachment-cell">';
                    $html .= '<div class="view-mode">';
                    if (file_exists($file_path)) {
                        $file_url = base_url() . ltrim($file_path, './');
                        $html .= '<a href="' . $file_url . '" target="_self">' . htmlspecialchars($file_name) . '</a>';
                    } else {
                        $html .= htmlspecialchars($file_name) . ' <span class="text-danger">(manquant)</span>';
                    }
                    $html .= '</div>';
                    $html .= '<div class="edit-mode" style="display: none;">';
                    $html .= '<input type="file" class="form-control form-control-sm file-input" accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx">';
                    $html .= '<small class="text-muted">Laissez vide pour conserver le fichier actuel</small>';
                    $html .= '<div class="text-danger mt-1 error-message" style="display: none;"></div>';
                    $html .= '</div>';
                    $html .= '</td>';

                    // Actions cell
                    $html .= '<td style="white-space: nowrap;">';
                    if (has_role('tresorier')) {
                        $html .= '<div class="view-mode">';
                        $html .= '<button class="btn btn-sm btn-primary edit-attachment-btn" title="Modifier">';
                        $html .= '<i class="fas fa-edit"></i></button> ';
                        $html .= '<button class="btn btn-sm btn-danger delete-attachment-btn" title="Supprimer">';
                        $html .= '<i class="fas fa-trash"></i></button>';
                        $html .= '</div>';
                        $html .= '<div class="edit-mode" style="display: none;">';
                        $html .= '<button class="btn btn-sm btn-success save-attachment-btn" title="Enregistrer">';
                        $html .= '<i class="fas fa-check"></i></button> ';
                        $html .= '<button class="btn btn-sm btn-secondary cancel-edit-btn" title="Annuler">';
                        $html .= '<i class="fas fa-times"></i></button>';
                        $html .= '</div>';
                    } else {
                        $html .= '<span class="text-muted">-</span>';
                    }
                    $html .= '</td>';
                    $html .= '</tr>';
                }

                $html .= '</tbody></table>';
            } else {
                $html .= '<div class="alert alert-info">Aucun justificatif</div>';
            }

            $html .= '</div>';

            echo $html;
        } catch (Exception $e) {
            log_message('error', 'Error in get_attachments_section: ' . $e->getMessage());
            echo '<div class="alert alert-danger">Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    /**
     * Update attachment via AJAX
     */
    public function update_attachment() {
        header('Content-Type: application/json');

        // Check authorization - only tresorier can update attachments
        if (!has_role('tresorier')) {
            echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
            return;
        }

        try {
            // Debug: log everything we receive
            log_message('debug', 'Update attachment called');
            log_message('debug', 'Request method: ' . $_SERVER['REQUEST_METHOD']);
            log_message('debug', 'Content-Type: ' . (isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : 'not set'));
            log_message('debug', '$_POST: ' . print_r($_POST, true));
            log_message('debug', '$_FILES: ' . print_r($_FILES, true));
            log_message('debug', '$_REQUEST: ' . print_r($_REQUEST, true));
            log_message('debug', 'input->post: ' . print_r($this->input->post(), true));
            log_message('debug', 'Raw input: ' . file_get_contents('php://input'));

            // Try multiple ways to get the data
            $attachment_id = null;
            $description = null;

            // Method 1: $_POST
            if (isset($_POST['attachment_id'])) {
                $attachment_id = $_POST['attachment_id'];
                $description = $_POST['description'];
                log_message('debug', 'Got data from $_POST');
            }
            // Method 2: $_REQUEST
            else if (isset($_REQUEST['attachment_id'])) {
                $attachment_id = $_REQUEST['attachment_id'];
                $description = $_REQUEST['description'];
                log_message('debug', 'Got data from $_REQUEST');
            }
            // Method 3: CodeIgniter input
            else if ($this->input->post('attachment_id')) {
                $attachment_id = $this->input->post('attachment_id');
                $description = $this->input->post('description');
                log_message('debug', 'Got data from input->post');
            }

            log_message('debug', 'Final attachment_id: ' . var_export($attachment_id, true));
            log_message('debug', 'Final description: ' . var_export($description, true));

            if (!$attachment_id) {
                // Debug: log what we received
                log_message('error', 'Update attachment - ID manquant. POST data: ' . print_r($_POST, true));
                echo json_encode(['success' => false, 'error' => 'ID manquant (reçu: ' . var_export($attachment_id, true) . ')']);
                return;
            }

            $this->load->model('attachments_model');

            // Get current attachment
            $attachment = $this->db->where('id', $attachment_id)->get('attachments')->row_array();
            if (!$attachment) {
                echo json_encode(['success' => false, 'error' => 'Justificatif introuvable']);
                return;
            }

            // Update description
            $update_data = ['description' => $description];

            // Handle file upload if present
            if (!empty($_FILES['file']['name'])) {
                $year = date('Y');
                $section_name = $this->sections_model->image($attachment['club']) ?: 'Unknown';
                $section_name = $this->sanitize_filename($section_name);
                $dirname = './uploads/attachments/' . $year . '/' . $section_name . '/';

                if (!file_exists($dirname)) {
                    mkdir($dirname, 0777, true);
                    chmod($dirname, 0777);
                }

                $storage_file = rand(100000, 999999) . '_' . $this->sanitize_filename($_FILES['file']['name']);
                $config['upload_path'] = $dirname;
                $config['allowed_types'] = '*';
                $config['max_size'] = '20000';
                $config['file_name'] = $storage_file;

                $this->load->library('upload', $config);

                if (!$this->upload->do_upload('file')) {
                    echo json_encode(['success' => false, 'error' => $this->upload->display_errors('', '')]);
                    return;
                }

                // Delete old file
                if (!empty($attachment['file']) && file_exists($attachment['file'])) {
                    unlink($attachment['file']);
                }

                $update_data['file'] = $dirname . $storage_file;
            }

            // Update database
            $this->db->where('id', $attachment_id);
            $this->db->update('attachments', $update_data);

            // Get updated file info
            $file_name = !empty($update_data['file']) ? basename($update_data['file']) : basename($attachment['file']);
            $file_path = !empty($update_data['file']) ? $update_data['file'] : $attachment['file'];
            $file_url = base_url() . ltrim($file_path, './');

            echo json_encode([
                'success' => true,
                'description' => $description,
                'file_name' => $file_name,
                'file_url' => $file_url
            ]);
        } catch (Exception $e) {
            log_message('error', 'Error updating attachment: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Delete attachment via AJAX
     */
    public function delete_attachment() {
        header('Content-Type: application/json');

        // Check authorization - only tresorier can delete attachments
        if (!has_role('tresorier')) {
            echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
            return;
        }

        try {
            $attachment_id = isset($_POST['attachment_id']) ? $_POST['attachment_id'] : $this->input->post('attachment_id');

            if (!$attachment_id) {
                log_message('error', 'Delete attachment - ID manquant. POST data: ' . print_r($_POST, true));
                echo json_encode(['success' => false, 'error' => 'ID manquant']);
                return;
            }

            // Get attachment
            $attachment = $this->db->where('id', $attachment_id)->get('attachments')->row_array();
            if (!$attachment) {
                echo json_encode(['success' => false, 'error' => 'Justificatif introuvable']);
                return;
            }

            // Delete file
            if (!empty($attachment['file']) && file_exists($attachment['file'])) {
                unlink($attachment['file']);
            }

            // Delete from database
            $this->db->where('id', $attachment_id);
            $this->db->delete('attachments');

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            log_message('error', 'Error deleting attachment: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
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
