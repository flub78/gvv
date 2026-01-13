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
        // Si Google Calendar n'est pas configuré, rediriger vers le système moderne (presences)
        $cal_id = $this->config->item('calendar_id');
        if (empty($cal_id)) {
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

        $data['cal_id'] = $cal_id;
        load_last_view('calendar', $data);
    }

    /**
     * ====================================================================
     * GOOGLE CALENDAR INTEGRATION ENDPOINTS
     * ====================================================================
     * These methods handle Google Calendar operations via the GoogleCal library.
     * This is the legacy system that predates the database-backed presences system.
     * Used by bs_calendar.php view and calendar.js JavaScript.
     * ====================================================================
     */

    /**
     * Check if user is allowed to modify an event
     * CA members can modify any event, regular users can only modify their own
     */
    private function modification_allowed($event_id) {
        if ($this->dx_auth->is_role('ca', true, true)) {
            return true;
        }

        $this->load->model('membres_model');
        $this->load->library('GoogleCal');
        $event = $this->googlecal->get($event_id);
        gvv_debug("event: " . var_export($event, true));

        $summary = $event['summary'];
        $mlogin = $this->dx_auth->get_username();
        $name = $this->membres_model->image($mlogin);
        $pattern = "/" . preg_quote($name) . "/";
        $match = preg_match($pattern, $summary);

        gvv_debug("summary = $summary, name=$name, match=$match");
        return $match;
    }

    /**
     * Check if user is allowed to create an event
     * CA members can create events for anyone, regular users only for themselves
     */
    private function creation_allowed($mlogin) {
        if ($this->dx_auth->is_role('ca', true, true)) {
            return true;
        }
        return ($this->dx_auth->get_username() == $mlogin);
    }

    /**
     * Set the authorization token from Google
     * OAuth callback endpoint
     */
    public function code() {
        $this->load->library('GoogleCal');
        $this->googlecal->code();
    }

    /**
     * Create or update a Google Calendar event
     * Called by calendar.js add_event() and event_click()
     * @param string $format 'html' or 'json'
     */
    public function ajout($format = 'html') {
        $this->load->helper('validation');
        $this->load->model('membres_model');
        
        $roles = $this->lang->line("welcome_options");
        $mlogin = $this->input->post('mlogin');
        $commentaire = $this->input->post('commentaire');
        $date_ajout = $this->input->post('date_ajout');
        $date = date_ht2db($date_ajout);
        $role = $this->input->post('role');
        $event_id = $this->input->post('event_id');

        if ($event_id) {
            // Modification
            if (!$this->modification_allowed($event_id)) {
                return;
            }
        } else {
            // Creation
            if (!$this->creation_allowed($mlogin)) {
                return;
            }
        }

        gvv_debug("ajout_event date=$date, mlogin=$mlogin, role=$role, commentaire=$commentaire");

        if ($mlogin) {
            $name = $this->membres_model->image($mlogin, false);
        } else {
            $name = $commentaire;
        }

        if ($name != '' && $date_ajout != "") {
            if ($role != "") {
                $name .= ", " . $roles[$role];
            }
            
            $this->load->library('GoogleCal');
            
            if ($event_id) {
                $id = $this->googlecal->update($event_id, $name, $date, $commentaire);
            } else {
                $id = $this->googlecal->create($name, $date, $commentaire);
            }
        }

        if ($format == 'html') {
            redirect(base_url());
        } else {
            $json = json_encode(array(
                'status' => "OK",
                'action' => 'ajout'
            ));
            echo $json;
            gvv_debug("json = $json");
        }
    }

    /**
     * Delete a Google Calendar event
     * Called by calendar.js confirmDelete()
     * @param string $id Event ID
     * @param string $format 'html' or 'json'
     */
    public function delete($id, $format = 'html') {
        if (!$this->modification_allowed($id)) {
            return;
        }

        $this->load->library('GoogleCal');
        $res = $this->googlecal->delete($id);

        if ($format == 'html') {
            redirect(base_url());
        } else {
            $json = json_encode(array(
                'status' => "OK",
                'action' => 'delete',
                'id' => $id
            ));
            echo $json;
            gvv_debug("json = $json");
        }
    }

    /**
     * Update a Google Calendar event from drag/drop
     * Called by calendar.js update_event()
     * @param string $format Always 'json' for this endpoint
     */
    public function update($format = 'json') {
        $event_id = $this->input->post('id');
        $description = $this->input->post('description');
        $end = $this->input->post('end');
        $allDay = $this->input->post('allDay');
        $dateformat = ($allDay == "true") ? "date" : "dateTime";
        $start_date = $this->input->post('start');

        if ($allDay == "true") {
            $tz = "";
        } else {
            $tz = "+00:00";
        }

        $start = array(
            $dateformat => $start_date . $tz
        );

        $result = array(
            'allDay' => $allDay,
            'summary' => $this->input->post('title'),
            'start' => $start
        );

        if ($description) {
            $result['description'] = $description;
        }

        gvv_debug("event summary: " . $this->input->post('title'));

        if ($end) {
            $result['end'] = array(
                $dateformat => $end . $tz
            );
        } else {
            if ($allDay != "true") {
                // allDay is false and no end is defined
                // compute a default duration (2 hours)
                $timestamp = strtotime($start_date) + (2 * 60 * 60);
                $date_end = date("Y-m-d", $timestamp) . "T" . date("H:i:s", $timestamp);
                $result['end'] = array(
                    $dateformat => $date_end . $tz
                );
            }
        }

        $result['action'] = 'update';

        if (!$this->modification_allowed($event_id)) {
            $json = json_encode(array(
                "status" => "KO",
                'error' => $this->lang->line("welcome_not_a_user_event")
            ));
            echo $json;
            gvv_debug("json = $json");
            return;
        }

        try {
            $this->load->library('GoogleCal');
            $id = $this->googlecal->change($event_id, $result);
            $result['status'] = 'OK';
        } catch (Exception $e) {
            $result['status'] = 'KO';
            $result['error'] = 'Exception raised in google->events->update $event_id: ' . $e->getMessage();
        }

        $json = json_encode($result);
        echo $json;
        gvv_debug("json = $json");
    }
}

/* End of file calendar.php */
/* Location: ./application/controllers/calendar.php */