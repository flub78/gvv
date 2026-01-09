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
        $this->load->config('program');
        
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
        
        // Format date for display
        $date_obj = DateTime::createFromFormat('Y-m-d', $date);
        $date_formatted = $date_obj->format('l, d F Y');
        
        // Prepare data for view
        $data = array(
            'current_date' => $date,
            'current_date_formatted' => $date_formatted,
            'aircraft' => $aircraft,
            'reservations' => $reservations,
            'timeline_increment' => $this->config->item('timeline_increment')
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
            
            if (!$event_id || !$start_datetime || !$end_datetime || !$resource_id) {
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
            
            // Update directly via database
            $username = $this->dx_auth->get_username();
            $this->db->update('reservations', array(
                'start_datetime' => $start_datetime,
                'end_datetime' => $end_datetime,
                'aircraft_id' => $resource_id,
                'updated_by' => $username
            ), array('id' => $event_id));
            
            if ($this->db->affected_rows() <= 0) {
                throw new Exception('No rows updated - reservation may not exist');
            }
            
            gvv_info("Timeline: User " . ($action === 'resize' ? 'resized' : 'moved') . 
                     " reservation ID " . $event_id . 
                     " to aircraft " . $resource_id . " from " . $start_datetime . " to " . $end_datetime);
            
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

}

/* End of file reservations.php */
/* Location: ./application/controllers/reservations.php */