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
 * @filesource document_types.php
 * @package controllers
 * Controller for document types administration.
 */
include('./application/libraries/Gvv_Controller.php');

class Document_types extends Gvv_Controller {

    protected $controller = 'document_types';
    protected $model = 'document_types_model';
    protected $modification_level = 'ca';
    protected $rules = array(
        'code' => 'required|max_length[32]',
        'name' => 'required|max_length[128]',
    );

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();

        // Authorization: Code-based (v2.0) - only for migrated users
        if ($this->use_new_auth) {
            $this->require_roles(['ca']);
        }

        $this->lang->load('document_types');
    }

    /**
     * Page principale - liste des types de documents
     */
    public function page() {
        $this->view_parameters['page'] = 'vue_document_types';
        $this->view_parameters['title'] = $this->lang->line('document_types_title');
        parent::page();
    }

    /**
     * Create form
     */
    public function create() {
        $this->view_parameters['page'] = 'document_types';
        parent::create();
    }

    /**
     * Edit form
     */
    public function edit($id = '') {
        $this->view_parameters['page'] = 'document_types';
        parent::edit($id);
    }

    /**
     * View a record
     */
    public function view($id = '') {
        $this->view_parameters['page'] = 'document_types';
        parent::view($id);
    }

    /**
     * Load selectors for forms
     */
    function form_static_element($action) {
        parent::form_static_element($action);
        $this->load->model('sections_model');
        $this->gvvmetadata->set_selector('section_selector_with_null', $this->sections_model->section_selector_with_null());
    }
}

/* End of file document_types.php */
/* Location: ./application/controllers/document_types.php */
