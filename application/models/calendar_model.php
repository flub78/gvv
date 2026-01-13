<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Calendar Model
 *
 * Handles pilot presence declarations for the calendar.
 * Manages presence data with full-day events for FullCalendar v6 display.
 *
 * @author Claude Sonnet 4.5
 */
class Calendar_model extends Common_Model {
    public $table = 'calendar';
    protected $primary_key = 'id';

    function __construct() {
        parent::__construct();
    }

    /**
     * Get events for FullCalendar display
     *
     * Returns presences formatted for FullCalendar v6 as JSON-compatible array.
     * Filters by date range if provided.
     *
     * @param string $start_date Start date (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)
     * @param string $end_date End date (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)
     * @return array Array of events formatted for FullCalendar
     */
    public function get_events($start_date = null, $end_date = null) {
        $this->db->select(
            'c.id, c.mlogin, c.role, c.commentaire, c.start_datetime, c.end_datetime, ' .
            'c.full_day, c.status, c.created_by, c.updated_by, ' .
            'm.mprenom, m.mnom'
        )
            ->from('calendar c')
            ->join('membres m', 'c.mlogin = m.mlogin', 'left')
            ->where('c.status !=', 'cancelled')
            ->order_by('c.start_datetime', 'asc');

        // Filter by date range if provided
        if ($start_date) {
            // Extract just the date part if datetime was provided
            $start_date_only = substr($start_date, 0, 10);
            $this->db->where('c.start_datetime >=', $start_date_only . ' 00:00:00');
        }
        if ($end_date) {
            // Extract just the date part if datetime was provided
            $end_date_only = substr($end_date, 0, 10);
            $this->db->where('c.end_datetime <=', $end_date_only . ' 23:59:59');
        }

        $presences = $this->db->get()->result_array();

        // Format for FullCalendar
        $events = $this->format_for_fullcalendar($presences);

        gvv_debug("calendar_model get_events sql: " . $this->db->last_query());
        return $events;
    }

    /**
     * Get a single presence event by ID
     *
     * @param int $event_id The event ID
     * @return array Event data or empty array if not found
     */
    public function get_event($event_id) {
        $this->db->select(
            'c.*, m.mprenom, m.mnom'
        )
            ->from('calendar c')
            ->join('membres m', 'c.mlogin = m.mlogin', 'left')
            ->where('c.id', $event_id);

        $result = $this->db->get()->row_array();
        gvv_debug("calendar_model get_event sql: " . $this->db->last_query());
        return $result ?: array();
    }

    /**
     * Get events for a specific user
     *
     * @param string $mlogin Member login
     * @param string $start_date Optional start date filter
     * @param string $end_date Optional end date filter
     * @return array Array of events
     */
    public function get_user_events($mlogin, $start_date = null, $end_date = null) {
        $this->db->select('c.*, m.mprenom, m.mnom')
            ->from('calendar c')
            ->join('membres m', 'c.mlogin = m.mlogin', 'left')
            ->where('c.mlogin', $mlogin)
            ->where('c.status !=', 'cancelled')
            ->order_by('c.start_datetime', 'asc');

        if ($start_date) {
            $start_date_only = substr($start_date, 0, 10);
            $this->db->where('c.start_datetime >=', $start_date_only . ' 00:00:00');
        }
        if ($end_date) {
            $end_date_only = substr($end_date, 0, 10);
            $this->db->where('c.end_datetime <=', $end_date_only . ' 23:59:59');
        }

        $result = $this->db->get()->result_array();
        gvv_debug("calendar_model get_user_events sql: " . $this->db->last_query());
        return $result;
    }

    /**
     * Create a new presence event
     *
     * Automatically normalizes timestamps for full_day events.
     *
     * @param array $data Event data (start_date, end_date, mlogin, role, etc.)
     * @return int|bool The new event ID on success, false on failure
     */
    public function create_event($data) {
        // Normalize data for full_day events
        $normalized = $this->normalize_event_data($data);

        // Add timestamps
        $normalized['created_at'] = date('Y-m-d H:i:s');
        $normalized['updated_at'] = date('Y-m-d H:i:s');

        $this->db->insert('calendar', $normalized);
        $insert_id = $this->db->insert_id();

        if ($insert_id > 0) {
            gvv_info("calendar_model: Created event ID $insert_id for user " . $normalized['mlogin']);
            return $insert_id;
        }

        gvv_error("calendar_model: Failed to create event");
        return false;
    }

    /**
     * Update an existing presence event
     *
     * Automatically normalizes timestamps for full_day events.
     *
     * @param int $event_id Event ID to update
     * @param array $data Updated event data
     * @return bool True on success, false on failure
     */
    public function update_event($event_id, $data) {
        // Normalize data for full_day events
        $normalized = $this->normalize_event_data($data);

        // Update timestamp
        $normalized['updated_at'] = date('Y-m-d H:i:s');

        $this->db->where('id', $event_id);
        $this->db->update('calendar', $normalized);

        $affected = $this->db->affected_rows();
        if ($affected > 0) {
            gvv_info("calendar_model: Updated event ID $event_id");
            return true;
        }

        gvv_debug("calendar_model: No rows affected for event ID $event_id (may be unchanged)");
        return true; // Not an error if data unchanged
    }

    /**
     * Delete a presence event
     *
     * @param int $event_id Event ID to delete
     * @return bool True on success, false on failure
     */
    public function delete_event($event_id) {
        $this->db->where('id', $event_id);
        $this->db->delete('calendar');

        $affected = $this->db->affected_rows();
        if ($affected > 0) {
            gvv_info("calendar_model: Deleted event ID $event_id");
            return true;
        }

        gvv_error("calendar_model: Failed to delete event ID $event_id");
        return false;
    }

    /**
     * Check if a user has a conflicting presence
     *
     * Returns true if the user already has a presence during the requested period.
     * This is a soft check - returns conflict info but doesn't prevent creation.
     *
     * @param string $mlogin Member login
     * @param string $start_datetime Start datetime (YYYY-MM-DD HH:MM:SS)
     * @param string $end_datetime End datetime (YYYY-MM-DD HH:MM:SS)
     * @param int $exclude_event_id Optional: event ID to exclude from conflict check
     * @return array Array with 'has_conflict' (bool), 'conflict_count' (int), 'conflicts' (array)
     */
    public function check_conflict($mlogin, $start_datetime, $end_datetime, $exclude_event_id = null) {
        $this->db->select('id, start_datetime, end_datetime, role, commentaire')
            ->from('calendar')
            ->where('mlogin', $mlogin)
            ->where('status !=', 'cancelled')
            ->where('(start_datetime < "' . $end_datetime . '" AND end_datetime > "' . $start_datetime . '")');

        if ($exclude_event_id) {
            $this->db->where('id !=', $exclude_event_id);
        }

        $conflicts = $this->db->get()->result_array();
        $conflict_count = count($conflicts);

        gvv_debug("calendar_model check_conflict sql: " . $this->db->last_query());

        return array(
            'has_conflict' => ($conflict_count > 0),
            'conflict_count' => $conflict_count,
            'conflicts' => $conflicts
        );
    }

    /**
     * Format events for FullCalendar display
     *
     * Converts database records to FullCalendar event format with allDay support.
     *
     * @param array $events Array of database records
     * @return array Array of FullCalendar events
     */
    public function format_for_fullcalendar($events) {
        $formatted = array();

        foreach ($events as $event) {
            $pilot_name = trim(($event['mprenom'] ?? '') . ' ' . ($event['mnom'] ?? ''));
            if (empty($pilot_name)) {
                $pilot_name = $event['mlogin'];
            }

            // Build title: "Pilot Name - Role - Commentaire" or combinations
            $title_parts = array();

            if (!empty($pilot_name)) {
                $title_parts[] = $pilot_name;
            }

            if (!empty($event['role'])) {
                $title_parts[] = $event['role'];
            }

            if (!empty($event['commentaire'])) {
                $title_parts[] = $event['commentaire'];
            }

            $title = implode(' - ', $title_parts);

            // Determine if this is an all-day event
            $is_all_day = (isset($event['full_day']) && $event['full_day'] == 1);

            // Format dates for FullCalendar
            // For all-day events, FullCalendar expects dates without times
            if ($is_all_day) {
                // Extract just the date part
                $start = substr($event['start_datetime'], 0, 10);
                // For FullCalendar all-day events, end date should be the day AFTER the last day
                // If event is 2024-06-15 to 2024-06-15, FullCalendar wants 2024-06-15 to 2024-06-16
                $end_date = new DateTime(substr($event['end_datetime'], 0, 10));
                $end_date->modify('+1 day');
                $end = $end_date->format('Y-m-d');
            } else {
                // For timed events, use full datetime
                $start = $event['start_datetime'];
                $end = $event['end_datetime'];
            }

            $formatted_event = array(
                'id' => $event['id'],
                'title' => $title,
                'start' => $start,
                'end' => $end,
                'allDay' => $is_all_day,
                'backgroundColor' => $this->get_status_color($event['status']),
                'borderColor' => $this->get_status_color($event['status']),
                'extendedProps' => array(
                    'mlogin' => $event['mlogin'],
                    'pilot_name' => $pilot_name,
                    'role' => $event['role'] ?? '',
                    'commentaire' => $event['commentaire'] ?? '',
                    'status' => $event['status'],
                    'full_day' => $is_all_day,
                    'created_by' => $event['created_by'] ?? '',
                    'updated_by' => $event['updated_by'] ?? ''
                )
            );

            $formatted[] = $formatted_event;
        }

        return $formatted;
    }

    /**
     * Normalize event data for full_day events
     *
     * If full_day is true (or not set, defaults to true), normalizes:
     * - start_datetime to date + 00:00:00
     * - end_datetime to date + 23:59:59
     *
     * @param array $data Event data
     * @return array Normalized event data
     */
    private function normalize_event_data($data) {
        $normalized = $data;

        // Default full_day to true if not specified
        if (!isset($normalized['full_day'])) {
            $normalized['full_day'] = 1;
        }

        // Ensure commentaire has a value (never null for NOT NULL column)
        if (!isset($normalized['commentaire']) || $normalized['commentaire'] === null || $normalized['commentaire'] === '') {
            $normalized['commentaire'] = ' '; // Use space instead of empty string to avoid CI Active Record issues
        }

        // Only normalize if full_day is true
        if ($normalized['full_day'] == 1) {
            // Handle start_date/start_datetime
            if (isset($data['start_date'])) {
                // If start_date provided (just date), convert to datetime
                $normalized['start_datetime'] = $data['start_date'] . ' 00:00:00';
                unset($normalized['start_date']); // Remove temporary field
            } elseif (isset($data['start_datetime'])) {
                // If start_datetime provided, extract date and set to 00:00:00
                $start_date = substr($data['start_datetime'], 0, 10);
                $normalized['start_datetime'] = $start_date . ' 00:00:00';
            }

            // Handle end_date/end_datetime
            if (isset($data['end_date'])) {
                // If end_date provided (just date), convert to datetime
                $normalized['end_datetime'] = $data['end_date'] . ' 23:59:59';
                unset($normalized['end_date']); // Remove temporary field
            } elseif (isset($data['end_datetime'])) {
                // If end_datetime provided, extract date and set to 23:59:59
                $end_date = substr($data['end_datetime'], 0, 10);
                $normalized['end_datetime'] = $end_date . ' 23:59:59';
            }
        }

        return $normalized;
    }

    /**
     * Get color for event status
     *
     * @param string $status Event status
     * @return string Hex color code
     */
    private function get_status_color($status) {
        $colors = array(
            'confirmed' => '#28a745',  // Green
            'pending' => '#ffc107',    // Yellow
            'completed' => '#6c757d',  // Gray
            'cancelled' => '#dc3545'   // Red
        );

        return isset($colors[$status]) ? $colors[$status] : '#007bff'; // Default blue
    }

    /**
     * Format title for event display
     *
     * @param string $pilot_name Pilot name
     * @param string $role Role
     * @param string $commentaire Comment
     * @return string Formatted title
     */
    public function format_event_title($pilot_name, $role = '', $commentaire = '') {
        $parts = array();

        if (!empty($pilot_name)) {
            $parts[] = $pilot_name;
        }

        if (!empty($role)) {
            $parts[] = $role;
        }

        if (empty($parts) && !empty($commentaire)) {
            $parts[] = $commentaire;
        }

        return implode(' - ', $parts);
    }
}

/* End of file calendar_model.php */
/* Location: ./application/models/calendar_model.php */
