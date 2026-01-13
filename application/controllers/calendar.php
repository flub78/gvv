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
 * Experimentation Calendar
 */
set_include_path(getcwd() . "/..:" . get_include_path());
class Calendar extends CI_Controller {

    function __construct() {
        date_default_timezone_set('Europe/Paris');
        parent::__construct();

        // Check if user is logged in or not
        $this->load->library('DX_Auth');
        if (! getenv('TEST') && ! $this->dx_auth->is_logged_in()) {
            redirect("auth/login");
        }

        // Authorization: Code-based (v2.0) - only for migrated users
        // For non-migrated users, login check above is sufficient (no additional authorization)
        $this->load->model('authorization_model');
        $user_id = $this->dx_auth->get_user_id();
        $migration = $this->authorization_model->get_migration_status($user_id);

        if ($migration && $migration['use_new_system'] == 1) {
            // New system - require user role (any logged-in user)
            $this->dx_auth->require_roles(['user']);
        }
        // else: Legacy system - no additional authorization beyond login check

        $this->load->library('unit_test');
    }

    /*
     * Set a cookie with the date of the MOD
     */
    function set_cookie() {
        // Redirect to welcome controller
        redirect("welcome/set_cookie");
    }

    /**
     * Affiche le calendrier
     */
    function index() {
        // Check if calendar_id is configured
        $calendar_id = $this->config->item('calendar_id');

        // If no calendar_id is configured, redirect to local presences management
        if (empty($calendar_id)) {
            redirect('presences');
            return;
        }

        $this->load->model('membres_model');
        $this->lang->load('membre');

        $data = array();
        $data['pilote_selector'] = $this->membres_model->selector_with_null(array(
            'actif' => "1"
        ));

        $data['is_ca'] = $this->dx_auth->is_role('ca', true, true);
        $data['mlogin'] = $this->membres_model->default_id();
        $data['event_id'] = "";

        $data['cal_id'] = $calendar_id;
        load_last_view('calendar', $data);
    }
}

/* End of file tests.php */
/* Location: ./application/controllers/tests.php */