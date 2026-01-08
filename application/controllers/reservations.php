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
 * Aircraft Reservations - FullCalendar v6 Experimentation
 */
set_include_path(getcwd() . "/..:" . get_include_path());

class Reservations extends CI_Controller {

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
    }

    /**
     * Display the FullCalendar v6 interface
     */
    function index() {
        $data = array();
        load_last_view('reservations/reservations_v6', $data);
    }

    /**
     * Get sample events for the calendar (JSON API)
     */
    function get_events() {
        header('Content-Type: application/json');
        
        // Sample events for testing
        $events = array(
            array(
                'id' => '1',
                'title' => 'Sample Booking - Aircraft N-123',
                'start' => date('Y-m-d') . 'T09:00:00',
                'end' => date('Y-m-d') . 'T11:00:00',
                'extendedProps' => array(
                    'aircraft' => 'N-123',
                    'pilot' => 'John Doe',
                    'instructor' => ''
                )
            ),
            array(
                'id' => '2',
                'title' => 'Sample Booking - Aircraft N-456',
                'start' => date('Y-m-d', strtotime('+1 day')) . 'T14:00:00',
                'end' => date('Y-m-d', strtotime('+1 day')) . 'T16:00:00',
                'extendedProps' => array(
                    'aircraft' => 'N-456',
                    'pilot' => 'Jane Smith',
                    'instructor' => 'Bob Johnson'
                )
            )
        );
        
        echo json_encode($events);
    }
}

/* End of file reservations.php */
/* Location: ./application/controllers/reservations.php */