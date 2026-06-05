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
 *
 * Playwright tests:
 *   - npx playwright test tests/reservations-timeline-create.spec.js
 */
set_include_path(getcwd() . "/..:" . get_include_path());

class Reservations extends MY_Controller {

    function __construct() {
        date_default_timezone_set('Europe/Paris');
        parent::__construct();

        if (! getenv('TEST') && ! $this->dx_auth->is_logged_in()) {
            redirect("auth/login");
        }

        // Bouton retour → tableau de bord Vols
        $this->lang->load('tableaux_de_bord');
        $this->load->vars([
            'nav_back_url'   => $this->session->userdata('nav_from_url')   ?: 'welcome/section/flights',
            'nav_back_label' => $this->session->userdata('nav_from_label') ?: $this->lang->line('db_section_flights'),
        ]);
    }

    /**
     * Display the FullCalendar v6 interface
     */
    function index() {
        $this->load->config('program');
        $this->load->model('reservations_model');
        $this->load->model('membres_model');
        $this->load->model('sections_model');
        $this->lang->load('reservations');

        $section = $this->sections_model->section();
        $aircraft_label = !empty($section['libelle_menu_avions'])
            ? $section['libelle_menu_avions']
            : $this->lang->line('reservations_form_aircraft');

        // Get aircraft list
        $aircraft_list = $this->reservations_model->get_aircraft_list();

        // Get pilots list (active members in the section)
        $pilots_list = $this->membres_model->section_pilots(0, true);

        // Get instructors list (active instructors in the section)
        $instructors_list = $this->membres_model->inst_selector(0, true);

        // Get all translations
        $translations = array(
            'timeline_desc' => $this->lang->line('reservations_timeline_desc'),
            'form_aircraft' => $aircraft_label,
            'form_pilot' => $this->lang->line('reservations_form_pilot'),
            'form_instructor' => $this->lang->line('reservations_form_instructor'),
            'form_instructor_optional' => $this->lang->line('reservations_form_instructor_optional'),
            'form_start_time' => $this->lang->line('reservations_form_start_time'),
            'form_end_time' => $this->lang->line('reservations_form_end_time'),
            'form_notes' => $this->lang->line('reservations_form_notes'),
            'form_status' => $this->lang->line('reservations_form_status'),
            'select_aircraft' => $this->lang->line('reservations_select_aircraft'),
            'select_pilot' => $this->lang->line('reservations_select_pilot'),
            'select_instructor_none' => $this->lang->line('reservations_select_instructor_none'),
            'status_maintenance' => $this->lang->line('reservations_status_maintenance'),
            'status_unavailable' => $this->lang->line('reservations_status_unavailable'),
            'status_vol_local' => $this->lang->line('reservations_status_vol_local'),
            'status_navigation' => $this->lang->line('reservations_status_navigation'),
            'status_vld' => $this->lang->line('reservations_status_vld'),
            'status_convoyage' => $this->lang->line('reservations_status_convoyage'),
            'modal_new' => $this->lang->line('reservations_modal_new'),
            'modal_edit' => $this->lang->line('reservations_modal_edit'),
            'btn_create' => $this->lang->line('reservations_btn_create'),
            'btn_save' => $this->lang->line('reservations_btn_save'),
            'btn_cancel' => $this->lang->line('reservations_btn_cancel'),
            'btn_delete' => $this->lang->line('reservations_btn_delete'),
            'error_no_aircraft' => $this->lang->line('reservations_error_no_aircraft'),
            'error_no_pilot' => $this->lang->line('reservations_error_no_pilot'),
            'error_invalid_datetime' => $this->lang->line('reservations_error_invalid_datetime'),
            'error_end_before_start' => $this->lang->line('reservations_error_end_before_start'),
            'error_unknown' => $this->lang->line('reservations_error_unknown'),
            'error_saving' => $this->lang->line('reservations_error_saving'),
            'error_deleting' => $this->lang->line('reservations_error_deleting'),
            'error_prefix' => $this->lang->line('reservations_error_prefix'),
            'success_saved' => $this->lang->line('reservations_success_saved'),
            'success_deleted' => $this->lang->line('reservations_success_deleted'),
            'confirm_delete' => $this->lang->line('reservations_confirm_delete')
        );

        list($current_username, $can_edit_others, $is_auto_planchiste, , $can_book) = $this->_reservation_permissions();

        $data = array(
            'timeline_increment' => $this->config->item('timeline_increment'),
            'aircraft_list' => $aircraft_list,
            'pilots_list' => $pilots_list,
            'instructors_list' => $instructors_list,
            'aircraft_label' => $aircraft_label,
            'translations' => $translations,
            'current_username' => $current_username,
            'can_edit_others' => $can_edit_others,
            'is_auto_planchiste' => $is_auto_planchiste,
            'can_book' => $can_book
        );
        load_last_view('reservations/reservations_v6', $data);
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
            
            // Load the reservations model
            $this->load->model('reservations_model');
            
            // Get events from database
            $events = $this->reservations_model->get_calendar_events($start, $end);
            
            echo json_encode($events);
        } catch (Exception $e) {
            gvv_error("Error in get_events: " . $e->getMessage());
            echo json_encode(array('error' => $e->getMessage()));
        }
    }

    /**
     * Display Timeline view organized by resources (aircraft)
     */
    function timeline() {
        $this->load->model('reservations_model');
        $this->load->model('avions_model');
        $this->load->model('membres_model');
        $this->load->model('sections_model');
        $this->load->config('program');
        $this->lang->load('reservations');

        $section = $this->sections_model->section();
        $aircraft_label = !empty($section['libelle_menu_avions'])
            ? $section['libelle_menu_avions']
            : ($this->lang->line('aircraft') ?: 'Aircraft');
        
        // Get date from request, default to today
        $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }
        
        // Get all active aircraft (resources)
        $aircraft = $this->reservations_model->get_aircraft_list();
        
        // Get reservations for the day
        $reservations = $this->reservations_model->get_day_reservations($date);
        
        // Get aircraft options for modal selects (using existing selector)
        $aircraft_options = $this->avions_model->selector(array('actif' => 1), 'asc', TRUE);

        // Get pilots (members) options for modal selects - only from active section
        $pilots_options = $this->membres_model->section_pilots(0, true);

        // Get instructors options - only instructors with role in active section
        $instructors_options = $this->membres_model->inst_selector(0, true);

        // User context for access control
        list($current_username, $can_edit_others, $is_auto_planchiste, , $can_book) = $this->_reservation_permissions();

        // Format date for display
        $date_obj = DateTime::createFromFormat('Y-m-d', $date);
        $date_formatted = $date_obj->format('l, d F Y');

        // Prepare translations for JavaScript
        $translations = array(
            'form_aircraft' => $this->lang->line('reservations_form_aircraft'),
            'form_pilot' => $this->lang->line('reservations_form_pilot'),
            'form_instructor' => $this->lang->line('reservations_form_instructor'),
            'form_instructor_optional' => $this->lang->line('reservations_form_instructor_optional'),
            'form_start_time' => $this->lang->line('reservations_form_start_time'),
            'form_end_time' => $this->lang->line('reservations_form_end_time'),
            'form_notes' => $this->lang->line('reservations_form_notes'),
            'form_status' => $this->lang->line('reservations_form_status'),
            'select_aircraft' => $this->lang->line('reservations_select_aircraft'),
            'select_pilot' => $this->lang->line('reservations_select_pilot'),
            'select_instructor_none' => $this->lang->line('reservations_select_instructor_none'),
            'status_maintenance' => $this->lang->line('reservations_status_maintenance'),
            'status_unavailable' => $this->lang->line('reservations_status_unavailable'),
            'status_vol_local' => $this->lang->line('reservations_status_vol_local'),
            'status_navigation' => $this->lang->line('reservations_status_navigation'),
            'status_vld' => $this->lang->line('reservations_status_vld'),
            'status_convoyage' => $this->lang->line('reservations_status_convoyage'),
            'modal_new' => $this->lang->line('reservations_modal_new'),
            'modal_edit' => $this->lang->line('reservations_modal_edit'),
            'btn_create' => $this->lang->line('reservations_btn_create'),
            'btn_save' => $this->lang->line('reservations_btn_save'),
            'btn_cancel' => $this->lang->line('reservations_btn_cancel'),
            'btn_delete' => $this->lang->line('reservations_btn_delete'),
            'error_no_aircraft' => $this->lang->line('reservations_error_no_aircraft'),
            'error_no_pilot' => $this->lang->line('reservations_error_no_pilot'),
            'error_invalid_datetime' => $this->lang->line('reservations_error_invalid_datetime'),
            'error_end_before_start' => $this->lang->line('reservations_error_end_before_start'),
            'error_unknown' => $this->lang->line('reservations_error_unknown'),
            'error_saving' => $this->lang->line('reservations_error_saving'),
            'error_deleting' => $this->lang->line('reservations_error_deleting'),
            'error_prefix' => $this->lang->line('reservations_error_prefix'),
            'success_saved' => $this->lang->line('reservations_success_saved'),
            'success_deleted' => $this->lang->line('reservations_success_deleted'),
            'confirm_delete' => $this->lang->line('reservations_confirm_delete')
        );

        // Prepare data for view
        $data = array(
            'current_date' => $date,
            'current_date_formatted' => $date_formatted,
            'aircraft' => $aircraft,
            'reservations' => $reservations,
            'timeline_increment' => $this->config->item('timeline_increment'),
            'aircraft_options' => $aircraft_options,
            'pilots_options' => $pilots_options,
            'instructors_options' => $instructors_options,
            'aircraft_label' => $aircraft_label,
            'translations' => $translations,
            'current_username' => $current_username,
            'can_edit_others' => $can_edit_others,
            'is_auto_planchiste' => $is_auto_planchiste,
            'can_book' => $can_book
        );

        load_last_view('reservations/timeline', $data);
    }

    /**
     * Get timeline data for a specific date (JSON API)
     */
    function get_timeline_data() {
        if (ob_get_level()) {
            ob_clean();
        }
        
        header('Content-Type: application/json; charset=UTF-8');
        
        try {
            $this->load->model('reservations_model');
            
            // Get date from request, default to today
            $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
            
            // Validate date format
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $date = date('Y-m-d');
            }
            
            // Get aircraft and reservations
            $aircraft_list = $this->reservations_model->get_aircraft_list();
            $events = $this->reservations_model->get_timeline_events($date);
            
            // Format resources for FullCalendar compatibility
            $resources = array();
            foreach ($aircraft_list as $id => $name) {
                $resources[] = array(
                    'id' => $id,
                    'title' => $name
                );
            }
            
            // Format response
            $response = array(
                'date' => $date,
                'resources' => $resources,
                'events' => $events
            );
            
            echo json_encode($response);
        } catch (Exception $e) {
            gvv_error("Error in get_timeline_data: " . $e->getMessage());
            echo json_encode(array('error' => $e->getMessage()));
        }
    }

    /**
     * Handle timeline event click
     */
    function on_event_click() {
        if (ob_get_level()) {
            ob_clean();
        }
        
        header('Content-Type: application/json; charset=UTF-8');
        
        try {
            $event_id = isset($_POST['event_id']) ? $_POST['event_id'] : null;
            
            if (!$event_id) {
                throw new Exception('Missing event_id');
            }
            
            $this->load->model('reservations_model');
            $reservation = $this->reservations_model->get_reservation($event_id);
            
            gvv_info("Timeline: User clicked on event ID " . $event_id);
            
            echo json_encode(array(
                'success' => true,
                'reservation' => $reservation
            ));
        } catch (Exception $e) {
            gvv_error("Error in on_event_click: " . $e->getMessage());
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    /**
     * Handle timeline event drag and drop
     */
    function on_event_drop() {
        if (ob_get_level()) {
            ob_clean();
        }
        
        header('Content-Type: application/json; charset=UTF-8');
        
        try {
            $this->load->model('reservations_model');
            $this->load->config('program');
            $this->lang->load('reservations');

            $event_id = isset($_POST['event_id']) ? $_POST['event_id'] : null;
            $start_datetime = isset($_POST['start_datetime']) ? $_POST['start_datetime'] : null;
            $end_datetime = isset($_POST['end_datetime']) ? $_POST['end_datetime'] : null;
            $resource_id = isset($_POST['resource_id']) ? $_POST['resource_id'] : null;
            $action = isset($_POST['action']) ? $_POST['action'] : 'move';
            
            if (!$event_id || !$start_datetime || !$end_datetime) {
                throw new Exception('Missing required parameters');
            }
            
            // Validate datetime format
            if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $start_datetime) ||
                !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $end_datetime)) {
                throw new Exception('Invalid datetime format');
            }

            // Validate actual datetime values (e.g., hours must be 0-23)
            if (!$this->_is_valid_datetime($start_datetime) || !$this->_is_valid_datetime($end_datetime)) {
                throw new Exception($this->lang->line('reservations_error_invalid_datetime'));
            }

            // Validate that end is strictly after start
            if ($end_datetime <= $start_datetime) {
                throw new Exception($this->lang->line('reservations_error_end_before_start'));
            }

            // Apply timeline increment constraint
            $increment = $this->config->item('timeline_increment');
            if ($increment && is_numeric($increment) && $increment > 0) {
                $start_datetime = $this->_snap_to_increment($start_datetime, $increment);
                $end_datetime = $this->_snap_to_increment($end_datetime, $increment);
            }

            // Get the current reservation to know pilot and instructor
            $reservation = $this->db->get_where('reservations', array('id' => $event_id))->row_array();
            if (!$reservation) {
                throw new Exception('Reservation not found');
            }

            // Access control
            list($drop_username, $drop_can_edit_others, $drop_is_auto_planchiste, $drop_balance_exempt, $drop_can_book) = $this->_reservation_permissions();

            // Read-only members cannot create or modify reservations
            if (!$drop_can_book) {
                $this->lang->load('reservations');
                throw new Exception($this->lang->line('reservations_error_not_authorized'));
            }

            if ($drop_is_auto_planchiste && !$drop_can_edit_others) {
                if ($reservation['pilot_member_id'] !== $drop_username) {
                    $this->lang->load('reservations');
                    throw new Exception($this->lang->line('reservations_error_not_authorized'));
                }
            }

            // Balance check on drag/resize: exclude current reservation to avoid double-counting
            if (!$drop_balance_exempt) {
                $balance_check = $this->_check_pilot_balance(
                    $drop_username,
                    $reservation['aircraft_id'],
                    $start_datetime,
                    $end_datetime,
                    $reservation['instructor_member_id'],
                    $event_id
                );
                if (!$balance_check['ok']) {
                    $balance_str = number_format($balance_check['balance'], 2, ',', ' ') . ' €';
                    $cost_str    = number_format($balance_check['cost'],    2, ',', ' ') . ' €';
                    throw new Exception(sprintf(
                        $this->lang->line('reservations_error_insufficient_balance'),
                        $balance_str,
                        $cost_str
                    ));
                }
            }

            // Determine the aircraft_id for conflict check
            $check_aircraft_id = $resource_id ? $resource_id : $reservation['aircraft_id'];

            // Check for conflicts (aircraft, pilot, instructor)
            $conflict_check = $this->reservations_model->check_reservation_conflicts(
                $check_aircraft_id,
                $reservation['pilot_member_id'],
                $reservation['instructor_member_id'],
                $start_datetime,
                $end_datetime,
                $event_id  // Exclude current reservation from conflict check
            );

            if (!$conflict_check['valid']) {
                // Load language file for error messages
                $this->lang->load('reservations');

                $error_messages = array();
                foreach ($conflict_check['conflicts'] as $conflict_type) {
                    $error_messages[] = $this->lang->line('reservations_conflict_' . $conflict_type);
                }

                throw new Exception(implode(', ', $error_messages));
            }

            // Prepare update data
            $username = $this->dx_auth->get_username();
            $update_data = array(
                'start_datetime' => $start_datetime,
                'end_datetime' => $end_datetime,
                'updated_by' => $username
            );

            // Only update aircraft_id if resource_id is provided
            if ($resource_id) {
                $update_data['aircraft_id'] = $resource_id;
            }

            // Update directly via database
            $this->db->update('reservations', $update_data, array('id' => $event_id));

            if ($this->db->affected_rows() <= 0) {
                // Check if reservation exists
                $exists = $this->db->get_where('reservations', array('id' => $event_id))->row();
                if (!$exists) {
                    throw new Exception('Reservation not found');
                }
                // If exists but no rows affected, data might be unchanged (not an error)
            }
            
            $log_message = "Reservation: User " . $drop_username . " " . ($action === 'resize' ? 'resized' : 'moved') .
                         " reservation ID " . $event_id .
                         " from " . $start_datetime . " to " . $end_datetime;
            if ($resource_id) {
                $log_message .= " (aircraft: " . $resource_id . ")";
            }
            gvv_info($log_message);
            
            echo json_encode(array(
                'success' => true,
                'message' => 'Reservation updated successfully',
                'start_datetime' => $start_datetime,
                'end_datetime' => $end_datetime
            ));
        } catch (Exception $e) {
            gvv_error("Error in on_event_drop: " . $e->getMessage());
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }
    
    /**
     * Snap a datetime to the nearest increment in minutes
     * @param string $datetime DateTime string in format 'YYYY-MM-DD HH:MM:SS'
     * @param int $increment_minutes Increment in minutes
     * @return string Snapped datetime
     */
    private function _snap_to_increment($datetime, $increment_minutes) {
        $dt = new DateTime($datetime);
        $minutes = (int)$dt->format('i');
        $seconds = (int)$dt->format('s');
        
        // Calculate minutes rounded down to nearest increment
        $rounded_minutes = (int)floor($minutes / $increment_minutes) * $increment_minutes;
        
        // Set minutes and seconds
        $dt->setTime(
            (int)$dt->format('H'),
            $rounded_minutes,
            0
        );
        
        return $dt->format('Y-m-d H:i:s');
    }

    /**
     * Validate that a datetime string represents a valid date and time
     * @param string $datetime DateTime string in format 'YYYY-MM-DD HH:MM:SS'
     * @return bool True if valid, false otherwise
     */
    private function _is_valid_datetime($datetime) {
        try {
            $dt = new DateTime($datetime);
            // Verify the parsed datetime matches the input (catches invalid dates like Feb 30)
            return $dt->format('Y-m-d H:i:s') === $datetime;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Handle timeline empty slot click
     */
    function on_slot_click() {
        if (ob_get_level()) {
            ob_clean();
        }
        
        header('Content-Type: application/json; charset=UTF-8');
        
        try {
            $resource_id = isset($_POST['resource_id']) ? $_POST['resource_id'] : null;
            $clicked_time = isset($_POST['clicked_time']) ? $_POST['clicked_time'] : null;
            
            if (!$resource_id || !$clicked_time) {
                throw new Exception('Missing required parameters');
            }
            
            gvv_info("Timeline: User clicked empty slot for aircraft " . $resource_id . 
                     " at " . $clicked_time);
            
            echo json_encode(array(
                'success' => true,
                'resource_id' => $resource_id,
                'clicked_time' => $clicked_time
            ));
        } catch (Exception $e) {
            gvv_error("Error in on_slot_click: " . $e->getMessage());
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    /**
     * Create or update reservation details from timeline modal
     */
    function update_reservation() {
        if (ob_get_level()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=UTF-8');

        try {
            $this->load->model('reservations_model');
            $this->load->config('program');
            $this->lang->load('reservations');

            $reservation_id = isset($_POST['reservation_id']) ? $_POST['reservation_id'] : null;
            $start_datetime = isset($_POST['start_datetime']) ? $_POST['start_datetime'] : null;
            $end_datetime = isset($_POST['end_datetime']) ? $_POST['end_datetime'] : null;
            $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
            $status = isset($_POST['status']) ? $_POST['status'] : 'vol_local';
            $aircraft_id = isset($_POST['aircraft_id']) ? $_POST['aircraft_id'] : null;
            $pilot_member_id = isset($_POST['pilot_member_id']) ? $_POST['pilot_member_id'] : null;
            $instructor_member_id = isset($_POST['instructor_member_id']) ? $_POST['instructor_member_id'] : null;

            // Clean pilot_member_id and instructor_member_id: empty string should be null
            if ($pilot_member_id === '') {
                $pilot_member_id = null;
            }
            if ($instructor_member_id === '') {
                $instructor_member_id = null;
            }

            // Validate required fields
            if (!$aircraft_id) {
                throw new Exception('Aircraft ID is required');
            }

            // maintenance and unavailable must not have a pilot
            $no_pilot_statuses = array('maintenance', 'unavailable');
            if (in_array($status, $no_pilot_statuses)) {
                $pilot_member_id = null;
                $instructor_member_id = null;
            }

            // Pilot is required for all flight types; optional only for 'maintenance' and 'unavailable'
            $pilot_required_statuses = array('vol_local', 'navigation', 'vld', 'convoyage');
            if (!$pilot_member_id && in_array($status, $pilot_required_statuses)) {
                throw new Exception('Pilot member ID is required for reservations');
            }

            if (!$start_datetime || !$end_datetime) {
                throw new Exception('Start and end datetime are required');
            }

            // Validate datetime format
            if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $start_datetime) ||
                !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $end_datetime)) {
                throw new Exception('Invalid datetime format');
            }

            // Validate that end is strictly after start
            if ($end_datetime <= $start_datetime) {
                throw new Exception($this->lang->line('reservations_error_end_before_start'));
            }

            // Apply timeline increment constraint
            $increment = $this->config->item('timeline_increment');
            if ($increment && is_numeric($increment) && $increment > 0) {
                $start_datetime = $this->_snap_to_increment($start_datetime, $increment);
                $end_datetime = $this->_snap_to_increment($end_datetime, $increment);
            }

            list($username, $can_edit_others, $is_auto_planchiste, $balance_exempt, $can_book) = $this->_reservation_permissions();

            // Read-only members cannot create or modify reservations
            if (!$can_book) {
                $this->lang->load('reservations');
                throw new Exception($this->lang->line('reservations_error_not_authorized'));
            }

            // Determine if this is a create or update
            $is_create = empty($reservation_id);

            // Access control for auto_planchiste
            if ($is_auto_planchiste && !$can_edit_others) {
                // maintenance and unavailable are restricted to privileged users
                if (in_array($status, array('maintenance', 'unavailable'))) {
                    throw new Exception($this->lang->line('reservations_error_not_authorized'));
                }
                if ($is_create) {
                    // Force pilot to current user
                    $pilot_member_id = $username;
                    // Require a valid cotisation for the reservation year
                    $this->lang->load('reservations');
                    $reservation_year = (int) substr($start_datetime, 0, 4);
                    $this->load->model('licences_model');
                    if (!$this->licences_model->check_cotisation_exists($username, $reservation_year)) {
                        throw new Exception($this->lang->line('reservations_error_no_cotisation'));
                    }
                } else {
                    // Only allow editing own reservations
                    $this->lang->load('reservations');
                    $existing = $this->db->get_where('reservations', array('id' => $reservation_id))->row_array();
                    if (!$existing || $existing['pilot_member_id'] !== $username) {
                        throw new Exception($this->lang->line('reservations_error_not_authorized'));
                    }
                    // Keep pilot as current user (cannot change pilot on own reservation)
                    $pilot_member_id = $username;
                }
            }

            // Balance check: applies to all non-privileged members (not admin/instructeur/pilote_vd)
            // On update, exclude the reservation being modified to avoid double-counting.
            if (!$balance_exempt) {
                $this->lang->load('reservations');
                $exclude_id = $is_create ? null : $reservation_id;
                $balance_check = $this->_check_pilot_balance($username, $aircraft_id, $start_datetime, $end_datetime, $instructor_member_id, $exclude_id);
                if (!$balance_check['ok']) {
                    $balance_str = number_format($balance_check['balance'], 2, ',', ' ') . ' €';
                    $cost_str    = number_format($balance_check['cost'],    2, ',', ' ') . ' €';
                    throw new Exception(sprintf(
                        $this->lang->line('reservations_error_insufficient_balance'),
                        $balance_str,
                        $cost_str
                    ));
                }
            }

            // Check for conflicts (aircraft, pilot, instructor)
            $conflict_check = $this->reservations_model->check_reservation_conflicts(
                $aircraft_id,
                $pilot_member_id,
                $instructor_member_id,
                $start_datetime,
                $end_datetime,
                $is_create ? null : $reservation_id
            );

            if (!$conflict_check['valid']) {
                // Load language file for error messages
                $this->lang->load('reservations');

                $error_messages = array();
                foreach ($conflict_check['conflicts'] as $conflict_type) {
                    $error_messages[] = $this->lang->line('reservations_conflict_' . $conflict_type);
                }

                throw new Exception(implode(', ', $error_messages));
            }

            if ($is_create) {
                // CREATE new reservation
                $data = array(
                    'aircraft_id' => $aircraft_id,
                    'pilot_member_id' => $pilot_member_id,
                    'instructor_member_id' => $instructor_member_id,
                    'start_datetime' => $start_datetime,
                    'end_datetime' => $end_datetime,
                    'notes' => $notes,
                    'status' => $status,
                    'created_by' => $username
                );

                $new_id = $this->reservations_model->create_reservation($data);

                if ($new_id <= 0) {
                    throw new Exception('Failed to create reservation');
                }

                gvv_info("Reservation: User " . $username . " created reservation ID " . $new_id . " for " . $start_datetime);

                echo json_encode(array(
                    'success' => true,
                    'message' => 'Reservation created successfully',
                    'reservation_id' => $new_id
                ));

            } else {
                // UPDATE existing reservation
                $update_data = array(
                    'start_datetime' => $start_datetime,
                    'end_datetime' => $end_datetime,
                    'notes' => $notes,
                    'status' => $status,
                    'aircraft_id' => $aircraft_id,
                    'pilot_member_id' => $pilot_member_id,
                    'instructor_member_id' => $instructor_member_id,
                    'updated_by' => $username
                );

                // Update in database
                $this->db->update('reservations', $update_data, array('id' => $reservation_id));

                if ($this->db->affected_rows() <= 0) {
                    // Check if reservation exists
                    $exists = $this->db->get_where('reservations', array('id' => $reservation_id))->row();
                    if (!$exists) {
                        throw new Exception('Reservation not found');
                    }
                    // If exists but no rows affected, data might be unchanged (not an error)
                }

                gvv_info("Reservation: User " . $username . " updated reservation ID " . $reservation_id . " for " . $start_datetime);

                echo json_encode(array(
                    'success' => true,
                    'message' => 'Reservation updated successfully'
                ));
            }

        } catch (Exception $e) {
            gvv_error("Error in update_reservation: " . $e->getMessage());
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    /**
     * Delete a reservation (AJAX endpoint)
     */
    function delete() {
        if (ob_get_level()) {
            ob_clean();
        }
        
        header('Content-Type: application/json; charset=UTF-8');
        
        try {
            $reservation_id = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : null;
            
            if (!$reservation_id) {
                throw new Exception('Missing reservation_id');
            }
            
            $this->load->model('reservations_model');

            // Access control
            list($del_username, $del_can_edit_others, $del_is_auto_planchiste, , $del_can_book) = $this->_reservation_permissions();

            // Read-only members cannot delete reservations
            if (!$del_can_book) {
                $this->lang->load('reservations');
                throw new Exception($this->lang->line('reservations_error_not_authorized'));
            }

            if ($del_is_auto_planchiste && !$del_can_edit_others) {
                $this->lang->load('reservations');
                $del_reservation = $this->db->get_where('reservations', array('id' => $reservation_id))->row_array();
                if (!$del_reservation || $del_reservation['pilot_member_id'] !== $del_username) {
                    throw new Exception($this->lang->line('reservations_error_not_authorized'));
                }
            }

            // Fetch reservation data before deletion for logging
            $del_res_data = $this->db->get_where('reservations', array('id' => $reservation_id))->row_array();
            $del_start = !empty($del_res_data['start_datetime']) ? $del_res_data['start_datetime'] : 'unknown';

            // Delete the reservation
            $success = $this->reservations_model->delete_reservation($reservation_id);

            if ($success) {
                gvv_info("Reservation: User " . $del_username . " deleted reservation ID " . $reservation_id . " for " . $del_start);
                echo json_encode(array('success' => true));
            } else {
                throw new Exception('Failed to delete reservation');
            }
        } catch (Exception $e) {
            gvv_error("Error in delete: " . $e->getMessage());
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    /**
     * Get list of available aircraft and pilots for modal selects
     */
    function get_options() {
        if (ob_get_level()) {
            ob_clean();
        }
        
        header('Content-Type: application/json; charset=UTF-8');
        
        try {
            // Get aircraft list
            $this->db->select('macimmat as id, macmodele as label')
                ->from('machinesa')
                ->where('en_service', 1)
                ->order_by('macmodele', 'asc');
            
            $aircraft = $this->db->get()->result_array();
            
            // Get members list (pilots)
            $this->db->select('mlogin as id, CONCAT(mprenom, " ", mnom) as label')
                ->from('membres')
                ->where('actif', 1)
                ->order_by('mnom', 'asc');
            
            $pilots = $this->db->get()->result_array();
            
            echo json_encode(array(
                'success' => true,
                'aircraft' => $aircraft,
                'pilots' => $pilots
            ));
        } catch (Exception $e) {
            gvv_error("Error in get_options: " . $e->getMessage());
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }

    /**
     * Look up the prix (float) for a tarif reference on a given date within the current section.
     * Returns 0.0 if the reference is empty or not found.
     */
    private function _get_tarif_price($reference, $date) {
        if (empty($reference)) return 0.0;
        $section_id = $this->session->userdata('section');
        $result = $this->db
            ->select('prix')
            ->from('tarifs')
            ->where('reference', $reference)
            ->where('date <=', $date)
            ->where('club', $section_id)
            ->order_by('date', 'desc')
            ->limit(1)
            ->get();
        if ($result && $result->num_rows() > 0) {
            return (float) $result->row()->prix;
        }
        return 0.0;
    }

    /**
     * Check whether a pilot has enough balance to cover the cost of:
     *   - existing future reservations on the given aircraft
     *   - the new reservation being created
     * If the pilot is the aircraft's owner (proprio), uses maprixproprio rate.
     * Returns array('ok' => bool, 'balance' => float, 'cost' => float).
     * Returns ok=true when no relevant pricing data is available (fail-open).
     */
    private function _check_pilot_balance($username, $aircraft_id, $start_datetime, $end_datetime, $instructor_member_id, $exclude_reservation_id = null) {
        // Check global config flag: if disabled, all balance checks are skipped
        $this->load->config('program');
        if (!$this->config->item('reservation_balance_check')) {
            return array('ok' => true);
        }

        // Check per-pilot exemption in membres table
        $membre = $this->db->select('exemption_solde')->get_where('membres', array('mlogin' => $username))->row_array();
        if (!empty($membre['exemption_solde'])) {
            return array('ok' => true);
        }

        // Get aircraft info
        $aircraft = $this->db->get_where('machinesa', array('macimmat' => $aircraft_id))->row_array();
        if (!$aircraft) {
            return array('ok' => true);
        }

        // Determine price reference: owner rate or regular rate
        $is_owner = (!empty($aircraft['proprio']) && $aircraft['proprio'] === $username);
        if ($is_owner && !empty($aircraft['maprixproprio'])) {
            $price_ref = $aircraft['maprixproprio'];
        } else {
            $price_ref = $aircraft['maprix'];
        }

        $date = substr($start_datetime, 0, 10);
        $hourly_rate = $this->_get_tarif_price($price_ref, $date);

        // No price defined: allow reservation
        if ($hourly_rate <= 0.0) {
            return array('ok' => true);
        }

        // New reservation duration in hours
        $dt_start = new DateTime($start_datetime);
        $dt_end   = new DateTime($end_datetime);
        $new_hours = ($dt_end->getTimestamp() - $dt_start->getTimestamp()) / 3600.0;

        // Existing future reservations for this pilot across ALL aircraft
        // Exclude the reservation being modified (on update) to avoid double-counting.
        $now = date('Y-m-d H:i:s');
        $this->db
            ->select('aircraft_id, start_datetime, end_datetime')
            ->from('reservations')
            ->where('pilot_member_id', $username)
            ->where('start_datetime >', $now)
            ->where_in('status', array('vol_local', 'navigation', 'vld', 'convoyage'));
        if ($exclude_reservation_id !== null) {
            $this->db->where('id !=', $exclude_reservation_id);
        }
        $existing = $this->db->get()->result_array();

        // Sum costs per aircraft (each has its own tarif)
        $existing_cost = 0.0;
        $rate_cache = array(); // aircraft_id => hourly_rate
        foreach ($existing as $res) {
            $res_id = $res['aircraft_id'];
            if (!array_key_exists($res_id, $rate_cache)) {
                $res_aircraft = $this->db->get_where('machinesa', array('macimmat' => $res_id))->row_array();
                if (!$res_aircraft) {
                    $rate_cache[$res_id] = 0.0;
                } else {
                    $res_is_owner = (!empty($res_aircraft['proprio']) && $res_aircraft['proprio'] === $username);
                    $res_price_ref = ($res_is_owner && !empty($res_aircraft['maprixproprio']))
                        ? $res_aircraft['maprixproprio']
                        : $res_aircraft['maprix'];
                    $rate_cache[$res_id] = $this->_get_tarif_price($res_price_ref, substr($res['start_datetime'], 0, 10));
                }
            }
            $res_rate = $rate_cache[$res_id];
            if ($res_rate > 0.0) {
                $s = new DateTime($res['start_datetime']);
                $e = new DateTime($res['end_datetime']);
                $existing_cost += ($e->getTimestamp() - $s->getTimestamp()) / 3600.0 * $res_rate;
            }
        }

        $total_cost = $existing_cost + $new_hours * $hourly_rate;

        // Add double-command surcharge for this reservation if instructor present
        if (!empty($instructor_member_id)) {
            $dc_rate = $this->_get_tarif_price($aircraft['maprixdc'], $date);
            $total_cost += $new_hours * $dc_rate;
        }

        // Find the pilot's 411 account in the aircraft's section
        // (membres.compte can be 0/null for legacy data, so we look up comptes directly)
        $section_id = !empty($aircraft['club']) ? (int) $aircraft['club'] : 0;
        $compte = $this->db->get_where('comptes', array(
            'pilote' => $username,
            'codec'  => '411',
            'club'   => $section_id,
            'actif'  => 1,
            'masked' => 0,
        ))->row_array();
        if (empty($compte)) {
            return array('ok' => true); // No account for this section, skip check
        }

        $this->load->helper('validation');
        $this->load->model('ecritures_model');
        $balance = (float) $this->ecritures_model->solde_compte($compte['id']);

        if ($balance >= $total_cost) {
            return array('ok' => true);
        }

        return array('ok' => false, 'balance' => $balance, 'cost' => $total_cost);
    }

    /**
     * Return reservation permission context for the current user.
     * Returns array($username, $can_edit_others, $is_auto_planchiste, $balance_exempt, $can_book)
     *
     * $can_edit_others  — may create/edit/delete reservations for other pilots (club-admin, instructeur)
     * $balance_exempt   — exempt from balance check: club-admin, instructeur, pilote_vd
     * $can_book         — may create/modify/delete any reservation (auto_planchiste, pilote_vd, instructeur, club-admin)
     *                     Members without any of these roles are read-only: they can view but not act.
     */
    private function _reservation_permissions() {
        $username = $this->dx_auth->get_username();
        if ($this->use_new_auth) {
            $section_id         = $this->session->userdata('section');
            $is_auto_planchiste = $this->gvv_authorization->has_role($this->user_id, 'auto_planchiste', $section_id);
            $is_pilote_vd       = $this->gvv_authorization->has_role($this->user_id, 'pilote_vd',       $section_id);
            $can_edit_others    = $this->gvv_authorization->has_role($this->user_id, 'club-admin',      NULL)
                                || $this->gvv_authorization->has_role($this->user_id, 'instructeur',    $section_id);
            $balance_exempt     = $can_edit_others || $is_pilote_vd;
            $can_book           = $can_edit_others || $is_auto_planchiste || $is_pilote_vd;
        } else {
            $is_auto_planchiste = false;
            $can_edit_others    = true;
            $balance_exempt     = true;
            $can_book           = true;
        }
        return array($username, $can_edit_others, $is_auto_planchiste, $balance_exempt, $can_book);
    }

}

/* End of file reservations.php */
/* Location: ./application/controllers/reservations.php */