<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Reservations Model
 *
 * Handles aircraft reservations for the booking calendar.
 * Queries and manages reservation data for FullCalendar display.
 *
 * @author Frédéric Peignot
 */
class Reservations_model extends Common_Model {
    public $table = 'reservations';
    protected $primary_key = 'id';

    function __construct() {
        parent::__construct();
    }

    /**
     * Get events for FullCalendar display
     *
     * Returns reservations formatted for FullCalendar v6 as JSON-compatible array.
     * Filters by date range if provided, and by section if user has section restriction.
     *
     * @param string $start_date Start date (YYYY-MM-DD)
     * @param string $end_date End date (YYYY-MM-DD)
     * @return array Array of events formatted for FullCalendar
     */
    public function get_calendar_events($start_date = null, $end_date = null) {
        $this->db->select(
            'r.id, r.aircraft_id, r.start_datetime, r.end_datetime, ' .
            'r.pilot_member_id, r.instructor_member_id, r.purpose, r.status, ' .
            'r.notes, m.macmodele, ma.mprenom, ma.mnom'
        )
            ->from('reservations r')
            ->join('machinesa m', 'r.aircraft_id = m.macimmat', 'left')
            ->join('membres ma', 'r.pilot_member_id = ma.mlogin', 'left')
            ->where('r.status !=', 'cancelled')
            ->order_by('r.start_datetime', 'asc');

        // Filter by date range if provided
        if ($start_date) {
            $this->db->where('r.start_datetime >=', $start_date . ' 00:00:00');
        }
        if ($end_date) {
            $this->db->where('r.end_datetime <=', $end_date . ' 23:59:59');
        }

        // Filter by section if user has section restriction
        if ($this->section) {
            $this->db->where('r.section_id', $this->section_id);
        }

        $reservations = $this->db->get()->result_array();

        // Format for FullCalendar
        $events = array();
        foreach ($reservations as $reservation) {
            $pilot_name = trim($reservation['mprenom'] . ' ' . $reservation['mnom']);
            $title = $reservation['macmodele'] . ' - ' . $pilot_name;

            if ($reservation['instructor_member_id']) {
                $title .= ' (+ instructeur)';
            }

            $event = array(
                'id' => $reservation['id'],
                'title' => $title,
                'start' => $reservation['start_datetime'],
                'end' => $reservation['end_datetime'],
                'backgroundColor' => $this->get_status_color($reservation['status']),
                'borderColor' => $this->get_status_color($reservation['status']),
                'extendedProps' => array(
                    'aircraft' => $reservation['aircraft_id'],
                    'aircraft_model' => $reservation['macmodele'],
                    'pilot' => $pilot_name,
                    'instructor' => $reservation['instructor_member_id'] ?: '',
                    'purpose' => $reservation['purpose'] ?: '',
                    'status' => $reservation['status'],
                    'notes' => $reservation['notes'] ?: ''
                )
            );

            $events[] = $event;
        }

        gvv_debug("sql: " . $this->db->last_query());
        return $events;
    }

    /**
     * Get a single reservation by ID
     *
     * @param int $reservation_id The reservation ID
     * @return array Reservation data or empty array if not found
     */
    public function get_reservation($reservation_id) {
        $this->db->select(
            'r.*, m.macmodele, m.macmodele as aircraft_model, ' .
            'pilot.mprenom as pilot_prenom, pilot.mnom as pilot_nom, ' .
            'instr.mprenom as instructor_prenom, instr.mnom as instructor_nom'
        )
            ->from('reservations r')
            ->join('machinesa m', 'r.aircraft_id = m.macimmat', 'left')
            ->join('membres pilot', 'r.pilot_member_id = pilot.mlogin', 'left')
            ->join('membres instr', 'r.instructor_member_id = instr.mlogin', 'left')
            ->where('r.id', $reservation_id);

        if ($this->section) {
            $this->db->where('r.section_id', $this->section_id);
        }

        $result = $this->db->get()->row_array();
        gvv_debug("sql: " . $this->db->last_query());
        return $result ?: array();
    }

    /**
     * Check if aircraft is available for a time slot
     *
     * Returns true if the aircraft is not reserved during the requested period.
     *
     * @param string $aircraft_id Aircraft registration
     * @param string $start_datetime Start datetime (YYYY-MM-DD HH:MM:SS)
     * @param string $end_datetime End datetime (YYYY-MM-DD HH:MM:SS)
     * @param int $exclude_reservation_id Optional: reservation ID to exclude from conflict check
     * @return bool True if available, false if conflict
     */
    public function is_aircraft_available($aircraft_id, $start_datetime, $end_datetime, $exclude_reservation_id = null) {
        $this->db->select('COUNT(*) as conflict_count')
            ->from('reservations')
            ->where('aircraft_id', $aircraft_id)
            ->where('status !=', 'cancelled')
            ->where('(start_datetime < "' . $end_datetime . '" AND end_datetime > "' . $start_datetime . '")');

        if ($exclude_reservation_id) {
            $this->db->where('id !=', $exclude_reservation_id);
        }

        $result = $this->db->get()->row_array();
        gvv_debug("sql: " . $this->db->last_query());
        return ($result['conflict_count'] == 0);
    }

    /**
     * Create a new reservation
     *
     * @param array $data Reservation data
     * @return int Inserted reservation ID or 0 on failure
     */
    public function create_reservation($data) {
        // Ensure required fields
        if (!isset($data['aircraft_id']) || !isset($data['start_datetime']) || !isset($data['end_datetime']) || !isset($data['pilot_member_id'])) {
            gvv_error("Reservations_model::create_reservation - Missing required fields");
            return 0;
        }

        // Set defaults
        if (!isset($data['section_id'])) {
            $data['section_id'] = $this->section_id;
        }
        if (!isset($data['status'])) {
            $data['status'] = 'pending';
        }

        $CI = &get_instance();
        if (!isset($data['created_by'])) {
            $data['created_by'] = $CI->dx_auth->get_username();
        }

        return $this->create($data);
    }

    /**
     * Update an existing reservation
     *
     * @param int $reservation_id Reservation ID
     * @param array $data Updated data
     * @return bool True on success, false on failure
     */
    public function update_reservation($reservation_id, $data) {
        $CI = &get_instance();
        $data['updated_by'] = $CI->dx_auth->get_username();

        return $this->update($reservation_id, $data);
    }

    /**
     * Get color code for reservation status
     *
     * @param string $status Status value
     * @return string Color code for FullCalendar
     */
    private function get_status_color($status) {
        $colors = array(
            'pending' => '#FFC107',     // Amber
            'confirmed' => '#28A745',   // Green
            'completed' => '#6C757D',   // Gray
            'cancelled' => '#DC3545',   // Red
            'no_show' => '#E83E8C'      // Pink
        );

        return isset($colors[$status]) ? $colors[$status] : '#007BFF'; // Default blue
    }

    /**
     * Get list of aircraft for selection/filtering
     *
     * @return array Array of aircraft [aircraft_id => aircraft_model]
     */
    public function get_aircraft_list() {
        $this->db->select('macimmat, macmodele')
            ->from('machinesa')
            ->where('actif', 1)
            ->order_by('macimmat', 'asc');

        if ($this->section) {
            $this->db->where('club', $this->section_id);
        }

        $result = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());

        $aircraft = array();
        foreach ($result as $row) {
            $aircraft[$row['macimmat']] = $row['macmodele'] . ' (' . $row['macimmat'] . ')';
        }
        return $aircraft;
    }

    /**
     * Get all reservations for a specific day organized by aircraft
     *
     * Returns reservations formatted for Timeline view with aircraft information.
     *
     * @param string $date Date in YYYY-MM-DD format
     * @return array Array of reservations with aircraft details
     */
    public function get_day_reservations($date) {
        $start_datetime = $date . ' 00:00:00';
        $end_datetime = $date . ' 23:59:59';

        $this->db->select(
            'r.id, r.aircraft_id, r.start_datetime, r.end_datetime, ' .
            'r.pilot_member_id, r.instructor_member_id, r.purpose, r.status, ' .
            'r.notes, r.section_id, ' .
            'm.macmodele, ma.mprenom, ma.mnom'
        )
            ->from('reservations r')
            ->join('machinesa m', 'r.aircraft_id = m.macimmat', 'left')
            ->join('membres ma', 'r.pilot_member_id = ma.mlogin', 'left')
            ->where('r.status !=', 'cancelled')
            ->where('r.start_datetime < "' . $end_datetime . '" AND r.end_datetime > "' . $start_datetime . '"', null, false)
            ->order_by('r.aircraft_id, r.start_datetime', 'asc');

        // Filter by section if user has section restriction
        if ($this->section) {
            $this->db->where('r.section_id', $this->section_id);
        }

        $reservations = $this->db->get()->result_array();
        gvv_debug("sql: " . $this->db->last_query());

        // Format with computed fields
        $formatted = array();
        foreach ($reservations as $res) {
            $pilot_name = trim($res['mprenom'] . ' ' . $res['mnom']);

            $formatted[] = array(
                'id' => $res['id'],
                'aircraft_id' => $res['aircraft_id'],
                'aircraft_model' => $res['macmodele'],
                'start_datetime' => $res['start_datetime'],
                'end_datetime' => $res['end_datetime'],
                'pilot_member_id' => $res['pilot_member_id'],
                'pilot_name' => $pilot_name,
                'instructor_member_id' => $res['instructor_member_id'],
                'purpose' => $res['purpose'],
                'status' => $res['status'],
                'notes' => $res['notes'],
                'section_id' => $res['section_id']
            );
        }

        return $formatted;
    }

    /**
     * Get timeline events for JSON API
     *
     * Returns events formatted for Timeline visualization.
     *
     * @param string $date Date in YYYY-MM-DD format
     * @return array Array of event objects with status colors
     */
    public function get_timeline_events($date) {
        $reservations = $this->get_day_reservations($date);
        $events = array();

        foreach ($reservations as $res) {
            $event = array(
                'id' => $res['id'],
                'resourceId' => $res['aircraft_id'],
                'title' => $res['pilot_name'],
                'start' => $res['start_datetime'],
                'end' => $res['end_datetime'],
                'status' => $res['status'],
                'extendedProps' => array(
                    'aircraft_id' => $res['aircraft_id'],
                    'aircraft_model' => $res['aircraft_model'],
                    'pilot_name' => $res['pilot_name'],
                    'instructor_member_id' => $res['instructor_member_id'],
                    'purpose' => $res['purpose'],
                    'status' => $res['status'],
                    'notes' => $res['notes']
                )
            );

            $events[] = $event;
        }

        return $events;
    }
}

/* End of file reservations_model.php */
/* Location: ./application/models/reservations_model.php */
