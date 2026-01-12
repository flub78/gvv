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
        $this->load->config('program');
        $this->load->model('reservations_model');
        $this->load->model('membres_model');
        $this->lang->load('reservations');

        // Get aircraft list
        $aircraft_list = $this->reservations_model->get_aircraft_list();

        // Get pilots list (active members in the section)
        $pilots_list = $this->membres_model->section_pilots(0, true);

        // Get instructors list (active instructors in the section)
        $instructors_list = $this->membres_model->inst_selector(0, true);

        // Get all translations
        $translations = array(
            'timeline' => $this->lang->line('reservations_timeline'),
            'timeline_desc' => $this->lang->line('reservations_timeline_desc'),
            'form_aircraft' => $this->lang->line('reservations_form_aircraft'),
            'form_pilot' => $this->lang->line('reservations_form_pilot'),
            'form_instructor' => $this->lang->line('reservations_form_instructor'),
            'form_instructor_optional' => $this->lang->line('reservations_form_instructor_optional'),
            'form_start_time' => $this->lang->line('reservations_form_start_time'),
            'form_end_time' => $this->lang->line('reservations_form_end_time'),
            'form_purpose' => $this->lang->line('reservations_form_purpose'),
            'form_notes' => $this->lang->line('reservations_form_notes'),
            'form_status' => $this->lang->line('reservations_form_status'),
            'select_aircraft' => $this->lang->line('reservations_select_aircraft'),
            'select_pilot' => $this->lang->line('reservations_select_pilot'),
            'select_instructor_none' => $this->lang->line('reservations_select_instructor_none'),
            'status_confirmed' => $this->lang->line('reservations_status_confirmed'),
            'status_pending' => $this->lang->line('reservations_status_pending'),
            'status_completed' => $this->lang->line('reservations_status_completed'),
            'status_no_show' => $this->lang->line('reservations_status_no_show'),
            'modal_new' => $this->lang->line('reservations_modal_new'),
            'modal_edit' => $this->lang->line('reservations_modal_edit'),
            'btn_create' => $this->lang->line('reservations_btn_create'),
            'btn_save' => $this->lang->line('reservations_btn_save'),
            'btn_cancel' => $this->lang->line('reservations_btn_cancel'),
            'error_no_aircraft' => $this->lang->line('reservations_error_no_aircraft'),
            'error_no_pilot' => $this->lang->line('reservations_error_no_pilot'),
            'error_unknown' => $this->lang->line('reservations_error_unknown'),
            'error_saving' => $this->lang->line('reservations_error_saving'),
            'error_prefix' => $this->lang->line('reservations_error_prefix'),
            'success_saved' => $this->lang->line('reservations_success_saved')
        );

        $data = array(
            'timeline_increment' => $this->config->item('timeline_increment'),
            'aircraft_list' => $aircraft_list,
            'pilots_list' => $pilots_list,
            'instructors_list' => $instructors_list,
            'translations' => $translations
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
        $this->load->config('program');
        $this->lang->load('reservations');
        
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
            'form_purpose' => $this->lang->line('reservations_form_purpose'),
            'form_notes' => $this->lang->line('reservations_form_notes'),
            'form_status' => $this->lang->line('reservations_form_status'),
            'select_aircraft' => $this->lang->line('reservations_select_aircraft'),
            'select_pilot' => $this->lang->line('reservations_select_pilot'),
            'select_instructor_none' => $this->lang->line('reservations_select_instructor_none'),
            'status_confirmed' => $this->lang->line('reservations_status_confirmed'),
            'status_pending' => $this->lang->line('reservations_status_pending'),
            'status_completed' => $this->lang->line('reservations_status_completed'),
            'status_no_show' => $this->lang->line('reservations_status_no_show'),
            'modal_new' => $this->lang->line('reservations_modal_new'),
            'modal_edit' => $this->lang->line('reservations_modal_edit'),
            'btn_create' => $this->lang->line('reservations_btn_create'),
            'btn_save' => $this->lang->line('reservations_btn_save'),
            'btn_cancel' => $this->lang->line('reservations_btn_cancel'),
            'error_no_aircraft' => $this->lang->line('reservations_error_no_aircraft'),
            'error_no_pilot' => $this->lang->line('reservations_error_no_pilot'),
            'error_unknown' => $this->lang->line('reservations_error_unknown'),
            'error_saving' => $this->lang->line('reservations_error_saving'),
            'error_prefix' => $this->lang->line('reservations_error_prefix')
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
            'translations' => $translations
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
            
            $log_message = "Reservation: User " . ($action === 'resize' ? 'resized' : 'moved') . 
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

            $reservation_id = isset($_POST['reservation_id']) ? $_POST['reservation_id'] : null;
            $start_datetime = isset($_POST['start_datetime']) ? $_POST['start_datetime'] : null;
            $end_datetime = isset($_POST['end_datetime']) ? $_POST['end_datetime'] : null;
            $purpose = isset($_POST['purpose']) ? $_POST['purpose'] : '';
            $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
            $status = isset($_POST['status']) ? $_POST['status'] : 'confirmed';
            $aircraft_id = isset($_POST['aircraft_id']) ? $_POST['aircraft_id'] : null;
            $pilot_member_id = isset($_POST['pilot_member_id']) ? $_POST['pilot_member_id'] : null;
            $instructor_member_id = isset($_POST['instructor_member_id']) ? $_POST['instructor_member_id'] : null;

            // Clean instructor_member_id: empty string should be null
            if ($instructor_member_id === '') {
                $instructor_member_id = null;
            }

            // Validate required fields
            if (!$aircraft_id) {
                throw new Exception('Aircraft ID is required');
            }
            if (!$pilot_member_id) {
                throw new Exception('Pilot member ID is required');
            }
            if (!$start_datetime || !$end_datetime) {
                throw new Exception('Start and end datetime are required');
            }

            // Validate datetime format
            if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $start_datetime) ||
                !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $end_datetime)) {
                throw new Exception('Invalid datetime format');
            }

            // Apply timeline increment constraint
            $increment = $this->config->item('timeline_increment');
            if ($increment && is_numeric($increment) && $increment > 0) {
                $start_datetime = $this->_snap_to_increment($start_datetime, $increment);
                $end_datetime = $this->_snap_to_increment($end_datetime, $increment);
            }

            $username = $this->dx_auth->get_username();

            // Determine if this is a create or update
            $is_create = empty($reservation_id);

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
                    'purpose' => $purpose,
                    'notes' => $notes,
                    'status' => $status,
                    'created_by' => $username
                );

                $new_id = $this->reservations_model->create_reservation($data);

                if ($new_id <= 0) {
                    throw new Exception('Failed to create reservation');
                }

                gvv_info("Reservation: Created new reservation ID " . $new_id . " by user " . $username);

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
                    'purpose' => $purpose,
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

                gvv_info("Reservation: Updated reservation ID " . $reservation_id . " by user " . $username);

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

}

/* End of file reservations.php */
/* Location: ./application/controllers/reservations.php */