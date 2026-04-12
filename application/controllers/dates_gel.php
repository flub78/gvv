<?php

/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 */
include('./application/libraries/Gvv_Controller.php');

class Dates_gel extends Gvv_Controller {

    protected $controller = 'dates_gel';
    protected $model = 'clotures_model';
    protected $modification_level = 'admin';
    protected $rules = array();

    function __construct() {
        parent::__construct();

        if (!$this->dx_auth->is_admin()) {
            show_error('Accès réservé aux administrateurs.', 403);
        }

        $allowed_users = $this->dev_users();
        if (!in_array($this->dx_auth->get_username(), $allowed_users)) {
            show_error('Accès réservé aux utilisateurs de développement.', 403);
        }
    }

    private function dev_users() {
        $configured = $this->config->item('users_dev');
        if (!$configured) {
            $configured = $this->config->item('dev_users');
        }
        if (!$configured) {
            return array();
        }
        return array_filter(array_map('trim', explode(',', $configured)));
    }

    private function require_section() {
        $section = $this->gvv_model->section();
        if (!$section || !isset($section['id'])) {
            $this->output->set_status_header(400);
            $data = array(
                'title' => ($this->lang->line('authorization_error_occurred') ?: 'Error'),
                'text' => 'Une section active est requise pour modifier la date de gel.'
            );
            load_last_view('message', $data, $this->unit_test);
            return NULL;
        }
        return $section;
    }

    function page($premier = 0, $message = '', $selection = array()) {
        $section = $this->require_section();
        if (!$section) {
            return;
        }

        $this->push_return_url('Dates_gel page');
        $this->data['select_result'] = $this->gvv_model->select_page(PER_PAGE, $premier, $selection);
        $this->data['kid'] = $this->kid;
        $this->data['controller'] = $this->controller;
        $this->data['count'] = $this->gvv_model->count(array('section' => $section['id']));
        $this->data['premier'] = $premier;
        $this->data['message'] = $message;
        $this->data['active_section'] = $section;
        $this->data['has_modification_rights'] = (!isset($this->modification_level) || $this->dx_auth->is_admin() || $this->user_has_role($this->modification_level));

        return load_last_view($this->table_view, $this->data, $this->unit_test);
    }

    function create() {
        $section = $this->require_section();
        if (!$section) {
            return;
        }

        parent::create(TRUE);
        $this->data['active_section'] = $section;
        $this->data['section'] = $section['id'];
        return load_last_view($this->form_view, $this->data, $this->unit_test);
    }

    function edit($id = '', $load_view = TRUE, $action = MODIFICATION) {
        $section = $this->require_section();
        if (!$section) {
            return;
        }

        parent::edit($id, FALSE, $action);

        // Keep section bound to the active selector for this CRUD.
        $this->data['section'] = $section['id'];
        $this->data['active_section'] = $section;

        if ($load_view) {
            return load_last_view($this->form_view, $this->data, $this->unit_test);
        }
    }

    function form2database($action = '') {
        $processed_data = parent::form2database($action);
        $section = $this->require_section();
        if ($section) {
            $processed_data['section'] = $section['id'];
        }
        return $processed_data;
    }
}
