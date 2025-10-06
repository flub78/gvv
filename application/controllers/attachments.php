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
    // protected $modification_level = 'ca';

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
        $table = $this->gvv_model->table();
        $this->data = $this->gvvmetadata->defaults_list($table);

        $this->form_static_element(CREATION);

        return load_last_view($this->form_view, $this->data, $this->unit_test);
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

        $year = date('Y');

        // Get section name from club field
        $club_id = $this->input->post('club');
        $section_name = $this->sections_model->image($club_id);

        // If club_id is empty or section name is not found, use 'Unknown'
        if (empty($section_name)) {
            $section_name = 'Unknown';
        }

        // Sanitize section name for use as directory name (remove spaces and special chars)
        $section_name = str_replace(' ', '_', $section_name);

        $dirname = './uploads/attachments/' . $year . '/' . $section_name . '/';
        if (!file_exists($dirname)) {
            mkdir($dirname, 0777, true);
            chmod($dirname, 0777); // Explicitly set permissions to override umask
        };

        // I am not sure that we need the capacity to specify a filename ...
        // The description is likely enough

        $storage_file = rand(100000, 999999) . '_' . str_replace(' ', '_', $_FILES['userfile']['name']);

        $config['upload_path'] = $dirname;
        $config['allowed_types'] = '*';
        $config['max_size']    = '20000';            // in kilobytes
        $config['file_name'] = $storage_file;
        // $config['encrypt_name']  = true;

        $this->load->library('upload', $config);

        // the purpose of attachment is to upload a file, so it's a fatal error if the file is not uploaded
        if (! $this->upload->do_upload("userfile")) {
            // erreur
            $this->data['message'] = '<div class="text-danger">' . $this->upload->display_errors() . '</div>';

            $this->form_static_element($action);
            load_last_view($this->form_view, $this->data);
        } else {
            // upload success
            $upload_data = array('upload_data' => $this->upload->data());

            // Add the uploaded file information to POST data
            $_POST['file'] = $dirname . $storage_file;

            // Delete the previous file for this attachment
            $initial_id = $this->session->userdata('initial_id');

            if ($initial_id) {
                $initial_elt = $this->gvv_model->get_by_id('id', $initial_id);
                if (!empty($initial_elt['file']) && file_exists($initial_elt['file'])) {
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

        $elt = $this->gvv_model->get_by_id('id', $id);

        if (!empty($elt['file']) && file_exists($elt['file'])) {
            unlink($elt['file']);
        }

        parent::delete($id);
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
        // Provide year and selector to the view
        $this->data['controller'] = $this->controller;
        $this->data['year'] = $this->session->userdata('year') ?: date('Y');
        $this->data['year_selector'] = $this->gvv_model->get_available_years();

        return parent::page($premier, $message, $selection);
    }
}
