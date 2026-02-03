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
 * @filesource attachments.php
 * @package controllers
 * Controleur des attachments / CRUD
 *
 * Attachments
 */

/**
 * Include parent library
 */
include('./application/libraries/Gvv_Controller.php');

/**
 * Controleur de gestion des attachments
 */
class Attachments extends Gvv_Controller {
    protected $controller = 'attachments';
    protected $model = 'attachments_model';
    // Custom access control: bureau = read-only, tresorier = read-write
    protected $view_level = 'bureau';        // Minimum role for viewing
    protected $modification_level = 'tresorier';  // Role required for modifications

    protected $rules = array();

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();
        $this->lang->load('attachments');
        $this->load->model('ecritures_model');
        $this->load->model('sections_model');
    }

    /**
     * Affiche le formulaire de création
     */
    function create() {
        // Check authorization - only tresorier can create attachments
        if (!has_role('tresorier')) {
            redirect('welcome/deny');
            return;
        }

        $table = $this->gvv_model->table();
        $this->data = $this->gvvmetadata->defaults_list($table);

        $this->form_static_element(CREATION);

        return load_last_view($this->form_view, $this->data, $this->unit_test);
    }

    /**
     * Affiche le formulaire d'édition
     */
    function edit($id = "", $load_view = TRUE, $action = MODIFICATION) {
        // Check authorization - only tresorier can edit attachments
        if (!has_role('tresorier')) {
            redirect('welcome/deny');
            return;
        }

        return parent::edit($id, $load_view, $action);
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

        if (CREATION == $action) {
            $referenced_table = $this->input->get('table');
            $id = $this->input->get('id');

            $this->data['user_id'] = $this->dx_auth->get_username();
            $this->data['referenced_table'] = $referenced_table;
            $this->data['referenced_id'] = $id;
        }

        if (
            isset($this->data['referenced_table'])
            && ($this->data['referenced_table'] != "")
            && isset($this->data['referenced_id'])
        ) {
            $referenced_model = $this->data['referenced_table'] . '_model';
            $this->load->model($referenced_model);

            $id = $this->data['referenced_id'];
            $image = $this->$referenced_model->image($id);
            $this->data['image'] = $this->data['referenced_table'] . ': ' . $image;

            // Get club field from current session (active section)
            // This ensures attachments are stored in the currently active section's directory
            $club_id = $this->session->userdata('section');
            $this->data['club'] = $club_id ? $club_id : 0;
        }
    }

    /**
     * Validation du formulaire
     */
    public function formValidation($action, $return_on_success = false) {
        // Check authorization - only tresorier can modify attachments
        if (!has_role('tresorier')) {
            redirect('welcome/deny');
            return;
        }

        $year = date('Y');

        // Get section name from club field
        $club_id = $this->input->post('club');
        $section_name = $this->sections_model->image($club_id);

        // If club_id is empty or section name is not found, use 'Unknown'
        if (empty($section_name)) {
            $section_name = 'Unknown';
        }

        // Sanitize section name for use as directory name (remove spaces and special chars)
        $section_name = $this->sanitize_filename($section_name);

        $dirname = './uploads/attachments/' . $year . '/' . $section_name . '/';
        if (!file_exists($dirname)) {
            // Create directory with full permissions
            $old_umask = umask(0);
            $created = @mkdir($dirname, 0777, true);
            umask($old_umask);

            if (!$created) {
                // Try to provide more information about the failure
                $parent = dirname($dirname);
                $error_msg = "Impossible de créer le répertoire: $dirname";
                if (!file_exists($parent)) {
                    $error_msg .= " (le parent $parent n'existe pas)";
                } elseif (!is_writable($parent)) {
                    $error_msg .= " (le parent $parent n'est pas accessible en écriture)";
                }
                $this->data['message'] = '<div class="text-danger">' . $error_msg . '</div>';
                $this->form_static_element($action);
                load_last_view($this->form_view, $this->data);
                return;
            }
        } elseif (!is_writable($dirname)) {
            $this->data['message'] = '<div class="text-danger">Le répertoire ' . $dirname . ' n\'est pas accessible en écriture</div>';
            $this->form_static_element($action);
            load_last_view($this->form_view, $this->data);
            return;
        }

        // I am not sure that we need the capacity to specify a filename ...
        // The description is likely enough

        $storage_file = rand(100000, 999999) . '_' . $this->sanitize_filename($_FILES['userfile']['name']);

        $config['upload_path'] = $dirname;
        $config['allowed_types'] = '*';
        $config['max_size']    = '20000';            // in kilobytes
        $config['file_name'] = $storage_file;
        // $config['encrypt_name']  = true;

        $this->load->library('upload', $config);

        // the purpose of attachment is to upload a file, so it's a fatal error if the file is not uploaded
        if (! $this->upload->do_upload("userfile")) {
            // erreur
            $this->data['message'] = '<div class="text-danger">' . $this->upload->display_errors() .
                '<br><small>Chemin: ' . htmlspecialchars($dirname) . '</small></div>';

            $this->form_static_element($action);
            load_last_view($this->form_view, $this->data);
        } else {
            // upload success
            $upload_data = array('upload_data' => $this->upload->data());
            $file_path = $dirname . $storage_file;

            // Attempt compression (Phase 2 - Images only)
            $this->load->library('file_compressor');
            $compression_result = $this->file_compressor->compress($file_path);

            if ($compression_result['success']) {
                // Compression succeeded
                $compressed_path = $compression_result['compressed_path'];
                $_POST['file'] = $compressed_path;

                // Original file is already replaced by compressed version (in-place compression)
                log_message('info', "File compressed successfully: " . basename($compressed_path));
            } else {
                // Compression skipped or failed - use original file
                $_POST['file'] = $file_path;
                log_message('info', "Compression skipped: " . $compression_result['error']);
            }

            // Generate PDF thumbnail if applicable
            $final_file_path = $_POST['file'];
            $mime = mime_content_type($final_file_path);
            if ($mime === 'application/pdf') {
                $this->load->library('pdf_thumbnail');
                $thumb_result = $this->pdf_thumbnail->generate($final_file_path);
                if ($thumb_result['success']) {
                    log_message('info', "PDF thumbnail generated: " . $thumb_result['thumbnail_path']);
                } else {
                    log_message('debug', "PDF thumbnail not generated: " . $thumb_result['error']);
                }
            }

            // Delete the previous file and its thumbnail for this attachment
            $initial_id = $this->session->userdata('initial_id');

            if ($initial_id) {
                $initial_elt = $this->gvv_model->get_by_id('id', $initial_id);
                if (!empty($initial_elt['file']) && file_exists($initial_elt['file'])) {
                    // Delete old thumbnail if it exists
                    $this->load->library('pdf_thumbnail');
                    $this->pdf_thumbnail->delete_thumbnail($initial_elt['file']);
                    // Delete old file
                    unlink($initial_elt['file']);
                }
            }

            parent::formValidation($action);
        }
    }

    /**
     * Supprime un élément
     */
    function delete($id) {
        // Check authorization - only tresorier can delete attachments
        if (!has_role('tresorier')) {
            redirect('welcome/deny');
            return;
        }

        $elt = $this->gvv_model->get_by_id('id', $id);

        if (!empty($elt['file']) && file_exists($elt['file'])) {
            // Delete thumbnail if it exists
            $this->load->library('pdf_thumbnail');
            $this->pdf_thumbnail->delete_thumbnail($elt['file']);
            // Delete file
            unlink($elt['file']);
        }

        parent::delete($id);
    }

    /**
     * Generate PDF thumbnail on demand (AJAX endpoint)
     * Used for existing PDFs that don't have thumbnails yet
     *
     * @param string $file_path Base64 encoded file path
     */
    function generate_thumbnail() {
        // Only allow authenticated users
        if (!$this->dx_auth->is_logged_in()) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            return;
        }

        $file_path = $this->input->post('file_path');
        if (!$file_path) {
            echo json_encode(['success' => false, 'error' => 'No file path provided']);
            return;
        }

        // Security: ensure the file is within uploads directory
        $real_path = realpath($file_path);
        $uploads_path = realpath('./uploads');
        if ($real_path === false || strpos($real_path, $uploads_path) !== 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid file path']);
            return;
        }

        // Check if file exists and is a PDF
        if (!file_exists($file_path)) {
            echo json_encode(['success' => false, 'error' => 'File not found']);
            return;
        }

        $mime = mime_content_type($file_path);
        if ($mime !== 'application/pdf') {
            echo json_encode(['success' => false, 'error' => 'Not a PDF file']);
            return;
        }

        // Generate thumbnail
        $this->load->library('pdf_thumbnail');
        $result = $this->pdf_thumbnail->generate($file_path);

        if ($result['success']) {
            // Return the thumbnail URL
            $base = rtrim(base_url(), '/') . '/';
            $thumb_url = $base . ltrim($result['thumbnail_path'], './');
            echo json_encode([
                'success' => true,
                'thumbnail_url' => $thumb_url
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => $result['error']
            ]);
        }
    }
    /**
     * Test unitaire
     */
    function test($format = "html") {

        $this->unit_test = TRUE;
        $this->load->library('unit_test');

        $this->unit->run(true, true, "Tests $this->controller");

        $res = $this->gvv_model->test();
        $all_passed = !in_array(false, array_column($res, 'result'));
        if ($all_passed) {
            $count = count($res);
            $this->unit->run(true, true, "All " . $count . " Model tests $this->controller are passed");
        } else {
            foreach ($res as $t) {
                $this->unit->run($t["result"], true, $t["description"]);
            }
        }


        parent::test();

        $this->tests_results('xml');
        $this->tests_results($format);
    }

    /**
     * Un test pour les invocations CLI
     * current status:
     * /usr/bin/php7.4 index.php hello joe                      OK
     * /usr/bin/php7.4 index.php bye joe                        OK
     * /usr/bin/php7.4 index.php attachments message frederic   KO
     * http://gvv.net/index.php/attachments/message/frederic    OK
     */
    public function message($to = 'World') {
        if ($this->input->is_cli_request()) {
            $msg = "CLI request";
        } else {
            $msg = "HTTP request";
        }
        gvv_debug("Hello {$msg} {$to}!" . PHP_EOL);
        echo "Hello {$msg} {$to}! " . PHP_EOL;
    }

    /**
     * Page with year selector like other views
     */
    function page($premier = 0, $message = '', $selection = array()) {
        // Check minimum authorization - bureau can view, tresorier can modify
        if (!has_role('bureau')) {
            redirect('welcome/deny');
            return;
        }

        // Provide year and selector to the view
        $this->data['controller'] = $this->controller;
        $this->data['year'] = $this->session->userdata('year') ?: date('Y');
        $this->data['year_selector'] = $this->gvv_model->get_available_years();
        
        // Set role-based permissions for the view
        $this->data['can_modify'] = has_role('tresorier');
        $this->data['can_view'] = has_role('bureau'); // Always true if we reach here

        return parent::page($premier, $message, $selection);
    }
}
