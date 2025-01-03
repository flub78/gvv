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
     *
     * Affiche header et menu
     */
    function __construct() {
        parent::__construct();
        $this->lang->load('attachments');
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

        $this->data['saisie_par'] = $this->dx_auth->get_username();
    }

    /**
     * Validation du formulaire
     */
    public function formValidation($action, $return_on_success = false) {

        $year = date('Y');
        $dirname = './uploads/attachments/' . $year . '/';
        if (!file_exists($dirname)) {
            mkdir($dirname, 0777, true);
        };

        // I am not sure that we need the capacity to specify a filename ...
        // The description is likely enough

        $storage_file = rand(100000, 999999) . '_' . $_FILES['userfile']['name'];

        $config['upload_path'] = $dirname;
        $config['allowed_types'] = 'gif|jpg|png|bnp|svg|avif|webp|md|pdf|doc|docx|xls|xlsx|ppt|pptx|txt|rtf|odt|odp|ods|odg|odc|odf';
        $config['allowed_types'] = '*';
        $config['max_size']    = '2000';            // in kilobytes
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

            if ($action == MODIFICATION) {
                // Get previous attachment data
                $previous_attachment = $this->gvv_model->get_by_id('id', $this->input->post('id'));

                // Delete the old file if it exists
                if (!empty($previous_attachment->file_path) && file_exists($previous_attachment->file_path)) {
                    unlink($previous_attachment->file_path);
                }
            }

            parent::formValidation($action);
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
        // $all_passed = false;
        if ($all_passed) {
            $count = count($res);
            $this->unit->run(true, true, "All " . $count . " Model tests $this->controller are passed");
        } else {
            foreach ($res as $t) {
                $this->unit->run($t["result"], true, $t["description"]);
            }
        }

        // test page/create/edit
        // parent::test($format);

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
}
