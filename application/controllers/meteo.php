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
 * @filesource meteo.php
 * @package controllers
 *
 * Page “Météo & préparation des vols” + CRUD des cartes
 */

include('./application/libraries/Gvv_Controller.php');

class Meteo extends Gvv_Controller {
    protected $controller = 'meteo';
    protected $model = 'preparation_cards_model';
    protected $modification_level = 'ca';

    protected $rules = array();

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();
        $this->lang->load('meteo');
    }

    /**
     * Page publique : cartes visibles (membres)
     */
    public function index() {
        $this->data['controller'] = $this->controller;
        $this->data['cards'] = $this->gvv_model->select_page(0, 0, array('visible' => 1));
        $this->data['can_manage'] = $this->can_manage_cards();

        return load_last_view($this->controller . '/publicView', $this->data, $this->unit_test);
    }

    /**
     * Liste admin (CRUD)
     */
    public function page($premier = 0, $message = '', $selection = array()) {
        if (!$this->can_manage_cards()) {
            return $this->index();
        }
        $this->view_parameters['page'] = 'vue_preparation_cards';
        $this->view_parameters['title'] = $this->lang->line('meteo_admin_title');
        return parent::page($premier, $message, $selection);
    }

    /**
     * Create form
     */
    public function create() {
        if (!$this->can_manage_cards()) {
            return $this->index();
        }
        $this->view_parameters['page'] = 'preparation_cards';
        return parent::create();
    }

    /**
     * Edit form
     */
    public function edit($id = '', $load_view = true, $action = MODIFICATION) {
        if (!$this->can_manage_cards()) {
            return $this->index();
        }
        $this->view_parameters['page'] = 'preparation_cards';
        return parent::edit($id, $load_view, $action);
    }

    /**
     * View record (admin)
     */
    public function view($id = '') {
        if (!$this->can_manage_cards()) {
            return $this->index();
        }
        $this->view_parameters['page'] = 'preparation_cards';
        return parent::view($id);
    }

    /**
     * Delete (admin)
     */
    public function delete($id) {
        if (!$this->can_manage_cards()) {
            return $this->index();
        }
        return parent::delete($id);
    }

    /**
     * Override form validation to process image upload/paste before save
     */
    public function formValidation($action, $return_on_success = false) {
        if ($this->can_manage_cards()) {
            $error = $this->process_image_inputs();
            if ($error) {
                $this->data = $this->input->post();
                $this->data['message'] = $error;
                $this->form_static_element($action);
                return load_last_view($this->form_view, $this->data, $this->unit_test);
            }
        }

        return parent::formValidation($action, $return_on_success);
    }

    /**
     * Process image upload or pasted data URL / URL
     * @return string error message or empty string
     */
    private function process_image_inputs() {
        $image_paste = trim((string)$this->raw_post_value('image_paste'));
        if (strpos($image_paste, '%') !== false) {
            $image_paste = urldecode($image_paste);
        }

        if ($image_paste !== '') {
            if (preg_match('#<img[^>]+src=["\"]([^"\"]+)["\"]#i', $image_paste, $img_matches)) {
                $image_paste = $img_matches[1];
            }

            if (preg_match('#!\[[^\]]*\]\(([^)]+)\)#', $image_paste, $md_matches)) {
                $image_paste = $md_matches[1];
            }

            if (preg_match('#(data:image/(png|jpe?g|gif|webp)[^,]*;base64,[A-Za-z0-9+/_=\s-]+)#i', $image_paste, $data_matches)) {
                $image_paste = $data_matches[1];
            }

            if (preg_match('#^data:image/(png|jpe?g|gif|webp)[^,]*;base64,#i', $image_paste, $matches)) {
                $ext = strtolower($matches[1]);
                if ($ext === 'jpeg') {
                    $ext = 'jpg';
                }
                $comma = strpos($image_paste, ',');
                $data = ($comma !== false) ? substr($image_paste, $comma + 1) : '';
                $data = str_replace(array(' ', "\t", "\r", "\n"), '', $data);
                $data = str_replace('-', '+', $data);
                $data = str_replace('_', '/', $data);
                $data = str_replace(' ', '+', $data);
                $data = preg_replace('/[^A-Za-z0-9+\/=]/', '', $data);
                $decoded = base64_decode($data, false);
                if ($decoded === false) {
                    return $this->lang->line('meteo_image_paste_error_decode');
                }

                $path = $this->store_preparation_card_image($decoded, $ext);
                if (!$path) {
                    return $this->lang->line('meteo_image_upload_error');
                }

                $_POST['image_url'] = $path;
                return '';
            }

            if (preg_match('#^https?://#i', $image_paste)) {
                $_POST['image_url'] = $image_paste;
                return '';
            }

            return $this->lang->line('meteo_image_paste_error_invalid');
        }

        if (!empty($_FILES['image_file']['name'])) {
            $upload_dir = FCPATH . 'uploads/preparation_cards/';
            if (!is_dir($upload_dir) && !@mkdir($upload_dir, 0775, true)) {
                return $this->lang->line('meteo_image_upload_error');
            }

            $config = array(
                'upload_path' => $upload_dir,
                'allowed_types' => 'png|jpg|jpeg|gif|webp',
                'max_size' => 4096,
                'encrypt_name' => true
            );

            $this->load->library('upload', $config);
            if (!$this->upload->do_upload('image_file')) {
                return $this->lang->line('meteo_image_upload_error') . ' ' . $this->upload->display_errors('', '');
            }

            $uploaded = $this->upload->data();
            $_POST['image_url'] = '/uploads/preparation_cards/' . $uploaded['file_name'];
        }

        return '';
    }

    /**
     * Store base64 decoded image data on disk
     * @param string $binary
     * @param string $ext
     * @return string|false
     */
    private function store_preparation_card_image($binary, $ext) {
        $upload_dir = FCPATH . 'uploads/preparation_cards/';
        if (!is_dir($upload_dir) && !@mkdir($upload_dir, 0775, true)) {
            return false;
        }

        $filename = 'pc_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $full_path = $upload_dir . $filename;
        if (file_put_contents($full_path, $binary) === false) {
            return false;
        }

        return '/uploads/preparation_cards/' . $filename;
    }

    /**
     * Authorization helper
     */
    private function can_manage_cards() {
        return $this->dx_auth->is_role('ca', true, true) || $this->dx_auth->is_role('admin', true, true);
    }
}

/* End of file meteo.php */
/* Location: ./application/controllers/meteo.php */
