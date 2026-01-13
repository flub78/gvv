<?php
if (!defined('BASEPATH'))
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
 * Pilot Presences Management - FullCalendar v6
 *
 * @author Claude Sonnet 4.5
 * @filesource presences.php
 * @package controllers
 */
set_include_path(getcwd() . "/..:" . get_include_path());

class Presences extends CI_Controller {

    function __construct() {
        date_default_timezone_set('Europe/Paris');
        parent::__construct();

        // Check if user is logged in or not
        $this->load->library('DX_Auth');
        if (!getenv('TEST') && !$this->dx_auth->is_logged_in()) {
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
     * Display the FullCalendar v6 interface for presences
     */
    function index() {
        $this->load->model('calendar_model');
        $this->load->model('membres_model');
        $this->lang->load('presences');
        $this->lang->load('welcome'); // For role options

        // Get pilots list (active members in the section)
        $pilots_list = $this->membres_model->section_pilots(0, true);

        // Get roles from welcome_options
        $roles_options = $this->lang->line('welcome_options');

        // Get all translations
        $translations = array(
            'title' => $this->lang->line('presences_title'),
            'modal_new' => $this->lang->line('presences_modal_new'),
            'modal_edit' => $this->lang->line('presences_modal_edit'),
            'form_pilot' => $this->lang->line('presences_form_pilot'),
            'form_role' => $this->lang->line('presences_form_role'),
            'form_comment' => $this->lang->line('presences_form_comment'),
            'form_start_date' => $this->lang->line('presences_form_start_date'),
            'form_end_date' => $this->lang->line('presences_form_end_date'),
            'form_status' => $this->lang->line('presences_form_status'),
            'select_pilot' => $this->lang->line('presences_select_pilot'),
            'select_role' => $this->lang->line('presences_select_role'),
            'status_confirmed' => $this->lang->line('presences_status_confirmed'),
            'status_pending' => $this->lang->line('presences_status_pending'),
            'status_completed' => $this->lang->line('presences_status_completed'),
            'status_cancelled' => $this->lang->line('presences_status_cancelled'),
            'btn_create' => $this->lang->line('presences_btn_create'),
            'btn_save' => $this->lang->line('presences_btn_save'),
            'btn_delete' => $this->lang->line('presences_btn_delete'),
            'btn_cancel' => $this->lang->line('presences_btn_cancel'),
            'confirm_delete' => $this->lang->line('presences_confirm_delete'),
            'error_unauthorized' => $this->lang->line('presences_error_unauthorized'),
            'error_no_pilot' => $this->lang->line('presences_error_no_pilot'),
            'error_invalid_dates' => $this->lang->line('presences_error_invalid_dates'),
            'error_unknown' => $this->lang->line('presences_error_unknown'),
            'success_created' => $this->lang->line('presences_success_created'),
            'success_updated' => $this->lang->line('presences_success_updated'),
            'success_deleted' => $this->lang->line('presences_success_deleted'),
            'conflict_warning' => $this->lang->line('presences_conflict_warning')
        );

        $data = array(
            'pilots_list' => $pilots_list,
            'roles_options' => $roles_options,
            'translations' => $translations,
            'current_user' => $this->dx_auth->get_username(),
            'is_ca' => $this->dx_auth->is_role('ca', true, true)
        );

        load_last_view('presences/presences', $data);
    }

    /**
     * Get events for the calendar (JSON API)
     */
    function get_events() {
        // Prevent any output buffering issues
        if (ob_get_level()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=UTF-8');

        try {
            // Get date range from request parameters (FullCalendar provides these)
            $start = isset($_GET['start']) ? $_GET['start'] : null;
            $end = isset($_GET['end']) ? $_GET['end'] : null;

            // Load the calendar model
            $this->load->model('calendar_model');

            // Get events from database
            $events = $this->calendar_model->get_events($start, $end);

            gvv_debug("presences get_events: retrieved " . count($events) . " events");
            echo json_encode($events);
        } catch (Exception $e) {
            gvv_error("Error in presences get_events: " . $e->getMessage());
            echo json_encode(array('error' => $e->getMessage()));
        }
    }

    /**
     * Create a new presence (JSON API)
     */
    function create_presence() {
        // Prevent any output buffering issues
        if (ob_get_level()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=UTF-8');

        try {
            // Get POST data
            $mlogin = $this->input->post('mlogin');
            $role = $this->input->post('role');
            $commentaire = $this->input->post('commentaire');
            $start_date = $this->input->post('start_date');
            $end_date = $this->input->post('end_date');

            // Validate authorization
            if (!$this->can_create($mlogin)) {
                echo json_encode(array(
                    'success' => false,
                    'error' => $this->lang->line('presences_error_unauthorized')
                ));
                gvv_info("presences create_presence: unauthorized for user " . $this->dx_auth->get_username());
                return;
            }

            // Validate required fields
            if (empty($mlogin)) {
                echo json_encode(array(
                    'success' => false,
                    'error' => $this->lang->line('presences_error_no_pilot')
                ));
                return;
            }

            // Validate dates
            if (empty($start_date) || empty($end_date)) {
                echo json_encode(array(
                    'success' => false,
                    'error' => $this->lang->line('presences_error_invalid_dates')
                ));
                return;
            }

            if (strtotime($start_date) > strtotime($end_date)) {
                echo json_encode(array(
                    'success' => false,
                    'error' => $this->lang->line('presences_error_invalid_dates')
                ));
                return;
            }

            // Prepare data
            $data = array(
                'mlogin' => $mlogin,
                'role' => $role ? $role : '',
                'commentaire' => $commentaire ? $commentaire : ' ',
                'start_date' => $start_date,
                'end_date' => $end_date,
                'status' => 'confirmed', // Always confirmed
                'full_day' => 1, // Always full day for MVP
                'created_by' => $this->dx_auth->get_username()
            );

            // Check for conflicts (warning only, not blocking)
            $this->load->model('calendar_model');
            $start_datetime = $start_date . ' 00:00:00';
            $end_datetime = $end_date . ' 23:59:59';
            $conflict_result = $this->calendar_model->check_conflict($mlogin, $start_datetime, $end_datetime);

            // Create the presence
            $event_id = $this->calendar_model->create_event($data);

            if ($event_id) {
                $response = array(
                    'success' => true,
                    'id' => $event_id,
                    'message' => $this->lang->line('presences_success_created')
                );

                if ($conflict_result['has_conflict']) {
                    $response['warning'] = $this->lang->line('presences_conflict_warning');
                    $response['conflict_count'] = $conflict_result['conflict_count'];
                }

                echo json_encode($response);
                gvv_info("presences create_presence: created presence ID $event_id for user $mlogin");
            } else {
                echo json_encode(array(
                    'success' => false,
                    'error' => $this->lang->line('presences_error_unknown')
                ));
                gvv_error("presences create_presence: failed to create presence for user $mlogin");
            }
        } catch (Exception $e) {
            gvv_error("Error in presences create_presence: " . $e->getMessage());
            echo json_encode(array(
                'success' => false,
                'error' => $this->lang->line('presences_error_unknown')
            ));
        }
    }

    /**
     * Update an existing presence (JSON API)
     */
    function update_presence() {
        // Prevent any output buffering issues
        if (ob_get_level()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=UTF-8');

        try {
            $event_id = $this->input->post('id');

            // Check authorization
            if (!$this->can_modify($event_id)) {
                echo json_encode(array(
                    'success' => false,
                    'error' => $this->lang->line('presences_error_unauthorized')
                ));
                gvv_info("presences update_presence: unauthorized for event ID $event_id by user " . $this->dx_auth->get_username());
                return;
            }

            // Get POST data
            $mlogin = $this->input->post('mlogin');
            $role = $this->input->post('role');
            $commentaire = $this->input->post('commentaire');
            $start_date = $this->input->post('start_date');
            $end_date = $this->input->post('end_date');

            // Validate required fields
            if (empty($mlogin)) {
                echo json_encode(array(
                    'success' => false,
                    'error' => $this->lang->line('presences_error_no_pilot')
                ));
                return;
            }

            // Validate dates
            if (empty($start_date) || empty($end_date)) {
                echo json_encode(array(
                    'success' => false,
                    'error' => $this->lang->line('presences_error_invalid_dates')
                ));
                return;
            }

            if (strtotime($start_date) > strtotime($end_date)) {
                echo json_encode(array(
                    'success' => false,
                    'error' => $this->lang->line('presences_error_invalid_dates')
                ));
                return;
            }

            // Prepare data
            $data = array(
                'mlogin' => $mlogin,
                'role' => $role ? $role : '',
                'commentaire' => $commentaire ? $commentaire : ' ',
                'start_date' => $start_date,
                'end_date' => $end_date,
                'status' => 'confirmed', // Always confirmed
                'full_day' => 1, // Always full day for MVP
                'updated_by' => $this->dx_auth->get_username()
            );

            // Check for conflicts (warning only, not blocking)
            $this->load->model('calendar_model');
            $start_datetime = $start_date . ' 00:00:00';
            $end_datetime = $end_date . ' 23:59:59';
            $conflict_result = $this->calendar_model->check_conflict($mlogin, $start_datetime, $end_datetime, $event_id);

            // Update the presence
            $success = $this->calendar_model->update_event($event_id, $data);

            if ($success) {
                $response = array(
                    'success' => true,
                    'message' => $this->lang->line('presences_success_updated')
                );

                if ($conflict_result['has_conflict']) {
                    $response['warning'] = $this->lang->line('presences_conflict_warning');
                    $response['conflict_count'] = $conflict_result['conflict_count'];
                }

                echo json_encode($response);
                gvv_info("presences update_presence: updated presence ID $event_id");
            } else {
                echo json_encode(array(
                    'success' => false,
                    'error' => $this->lang->line('presences_error_unknown')
                ));
                gvv_error("presences update_presence: failed to update presence ID $event_id");
            }
        } catch (Exception $e) {
            gvv_error("Error in presences update_presence: " . $e->getMessage());
            echo json_encode(array(
                'success' => false,
                'error' => $this->lang->line('presences_error_unknown')
            ));
        }
    }

    /**
     * Delete a presence (JSON API)
     */
    function delete_presence() {
        // Prevent any output buffering issues
        if (ob_get_level()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=UTF-8');

        try {
            $event_id = $this->input->post('id');

            // Check authorization
            if (!$this->can_modify($event_id)) {
                echo json_encode(array(
                    'success' => false,
                    'error' => $this->lang->line('presences_error_unauthorized')
                ));
                gvv_info("presences delete_presence: unauthorized for event ID $event_id by user " . $this->dx_auth->get_username());
                return;
            }

            // Delete the presence
            $this->load->model('calendar_model');
            $success = $this->calendar_model->delete_event($event_id);

            if ($success) {
                echo json_encode(array(
                    'success' => true,
                    'message' => $this->lang->line('presences_success_deleted')
                ));
                gvv_info("presences delete_presence: deleted presence ID $event_id");
            } else {
                echo json_encode(array(
                    'success' => false,
                    'error' => $this->lang->line('presences_error_unknown')
                ));
                gvv_error("presences delete_presence: failed to delete presence ID $event_id");
            }
        } catch (Exception $e) {
            gvv_error("Error in presences delete_presence: " . $e->getMessage());
            echo json_encode(array(
                'success' => false,
                'error' => $this->lang->line('presences_error_unknown')
            ));
        }
    }

    /**
     * Handle event drag and drop (JSON API)
     */
    function on_event_drop() {
        // Prevent any output buffering issues
        if (ob_get_level()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=UTF-8');

        try {
            $event_id = $this->input->post('id');
            $new_start = $this->input->post('start_date');
            $new_end = $this->input->post('end_date');

            // Check authorization
            if (!$this->can_modify($event_id)) {
                echo json_encode(array(
                    'success' => false,
                    'error' => $this->lang->line('presences_error_unauthorized')
                ));
                gvv_info("presences on_event_drop: unauthorized for event ID $event_id by user " . $this->dx_auth->get_username());
                return;
            }

            // Validate dates
            if (empty($new_start) || empty($new_end)) {
                echo json_encode(array(
                    'success' => false,
                    'error' => $this->lang->line('presences_error_invalid_dates')
                ));
                return;
            }

            // Prepare data
            $data = array(
                'start_date' => $new_start,
                'end_date' => $new_end,
                'updated_by' => $this->dx_auth->get_username()
            );

            // Update the presence
            $this->load->model('calendar_model');
            $success = $this->calendar_model->update_event($event_id, $data);

            if ($success) {
                echo json_encode(array(
                    'success' => true,
                    'message' => $this->lang->line('presences_success_updated')
                ));
                gvv_info("presences on_event_drop: moved presence ID $event_id to $new_start - $new_end");
            } else {
                echo json_encode(array(
                    'success' => false,
                    'error' => $this->lang->line('presences_error_unknown')
                ));
                gvv_error("presences on_event_drop: failed to move presence ID $event_id");
            }
        } catch (Exception $e) {
            gvv_error("Error in presences on_event_drop: " . $e->getMessage());
            echo json_encode(array(
                'success' => false,
                'error' => $this->lang->line('presences_error_unknown')
            ));
        }
    }

    /**
     * Handle event resize (JSON API)
     */
    function on_event_resize() {
        // Prevent any output buffering issues
        if (ob_get_level()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=UTF-8');

        try {
            $event_id = $this->input->post('id');
            $new_end = $this->input->post('end_date');

            // Check authorization
            if (!$this->can_modify($event_id)) {
                echo json_encode(array(
                    'success' => false,
                    'error' => $this->lang->line('presences_error_unauthorized')
                ));
                gvv_info("presences on_event_resize: unauthorized for event ID $event_id by user " . $this->dx_auth->get_username());
                return;
            }

            // Validate date
            if (empty($new_end)) {
                echo json_encode(array(
                    'success' => false,
                    'error' => $this->lang->line('presences_error_invalid_dates')
                ));
                return;
            }

            // Prepare data
            $data = array(
                'end_date' => $new_end,
                'updated_by' => $this->dx_auth->get_username()
            );

            // Update the presence
            $this->load->model('calendar_model');
            $success = $this->calendar_model->update_event($event_id, $data);

            if ($success) {
                echo json_encode(array(
                    'success' => true,
                    'message' => $this->lang->line('presences_success_updated')
                ));
                gvv_info("presences on_event_resize: resized presence ID $event_id to end at $new_end");
            } else {
                echo json_encode(array(
                    'success' => false,
                    'error' => $this->lang->line('presences_error_unknown')
                ));
                gvv_error("presences on_event_resize: failed to resize presence ID $event_id");
            }
        } catch (Exception $e) {
            gvv_error("Error in presences on_event_resize: " . $e->getMessage());
            echo json_encode(array(
                'success' => false,
                'error' => $this->lang->line('presences_error_unknown')
            ));
        }
    }

    /**
     * Check if the current user can modify a presence
     *
     * CA members and above can modify any presence.
     * Regular users can only modify their own presences.
     *
     * @param int $event_id The event ID
     * @return bool True if authorized, false otherwise
     */
    private function can_modify($event_id) {
        // CA and above can modify all presences
        if ($this->dx_auth->is_role('ca', true, true)) {
            return true;
        }

        // Regular users can only modify their own presences
        $this->load->model('calendar_model');
        $event = $this->calendar_model->get_event($event_id);

        if (empty($event)) {
            gvv_error("presences can_modify: event ID $event_id not found");
            return false;
        }

        $current_user = $this->dx_auth->get_username();
        $is_owner = ($event['mlogin'] === $current_user);

        gvv_debug("presences can_modify: event ID $event_id, owner=" . $event['mlogin'] . ", current_user=$current_user, is_owner=" . ($is_owner ? 'yes' : 'no'));

        return $is_owner;
    }

    /**
     * Check if the current user can create a presence for a given pilot
     *
     * CA members and above can create presences for any pilot.
     * Regular users can only create presences for themselves.
     *
     * @param string $mlogin The pilot login
     * @return bool True if authorized, false otherwise
     */
    private function can_create($mlogin) {
        // CA and above can create presences for anyone
        if ($this->dx_auth->is_role('ca', true, true)) {
            return true;
        }

        // Regular users can only create presences for themselves
        $current_user = $this->dx_auth->get_username();
        $is_self = ($mlogin === $current_user);

        gvv_debug("presences can_create: mlogin=$mlogin, current_user=$current_user, is_self=" . ($is_self ? 'yes' : 'no'));

        return $is_self;
    }

    /**
     * ====================================================================
     * LEGACY ENDPOINTS FOR GOOGLE CALENDAR (calendar.js with Google Calendar)
     * ====================================================================
     * These methods maintain backward compatibility with the old
     * calendar view (bs_calendar.php + assets/javascript/calendar.js)
     * when calendar_id is configured (Google Calendar integration).
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
     * Legacy endpoint: ajout - Create or update a Google Calendar event
     * Called by calendar.js add_event() and event_click()
     * @param string $format 'html' or 'json'
     */
    public function ajout($format = 'html') {
        $this->load->helper('validation');
        
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
     * Legacy endpoint: delete - Delete a Google Calendar event
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
     * Legacy endpoint: update - Update a Google Calendar event from drag/drop
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

/* End of file presences.php */
/* Location: ./application/controllers/presences.php */
