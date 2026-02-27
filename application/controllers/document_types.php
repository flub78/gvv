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

        // Check if feature is enabled
        if (!$this->config->item('gestion_documentaire')) {
            show_404();
        }

        // Authorization: Code-based (v2.0) - only for migrated users
        if ($this->use_new_auth) {
            $this->require_roles(['ca']);
        }

        $this->lang->load('document_types');
    }

    /**
     * Page principale - liste des types de documents, avec filtres portée et section
     */
    public function page($premier = 0, $message = '', $selection = array()) {
        $session_key = 'document_types_filters';

        if ($this->input->get('filter_submitted')) {
            $filters = array(
                'scope'      => $this->input->get('scope'),
                'section_id' => $this->input->get('section_id'),
            );
            $this->session->set_userdata($session_key, $filters);
        } else {
            $saved = $this->session->userdata($session_key);
            $filters = ($saved !== false && is_array($saved))
                ? $saved
                : array('scope' => '', 'section_id' => '');
        }

        if (!empty($filters['scope'])) {
            $selection['document_types.scope'] = $filters['scope'];
        }
        if ($filters['section_id'] !== '' && $filters['section_id'] !== null) {
            $selection['document_types.section_id'] = $filters['section_id'];
        }

        $this->load->model('sections_model');
        $this->data['scope_selector'] = array(
            ''        => $this->lang->line('document_types_filter_all'),
            'pilot'   => $this->lang->line('document_types_scope_pilot'),
            'section' => $this->lang->line('document_types_scope_section'),
            'club'    => $this->lang->line('document_types_scope_club'),
        );
        $this->data['section_selector']  = array('' => $this->lang->line('document_types_filter_all'))
            + $this->sections_model->section_selector_with_null();
        $this->data['filter_scope']      = $filters['scope'];
        $this->data['filter_section_id'] = $filters['section_id'];

        $this->view_parameters['page'] = 'vue_document_types';
        $this->view_parameters['title'] = $this->lang->line('document_types_title');
        parent::page($premier, $message, $selection);
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
    public function edit($id = '', $load_view = true, $action = MODIFICATION) {
        $this->view_parameters['page'] = 'document_types';
        parent::edit($id, $load_view, $action);
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
