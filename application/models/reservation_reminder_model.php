<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Reservation Reminder Model
 *
 * CRUD on reservation_reminder_log and business queries for the reminder mechanism.
 * Idempotency is enforced at the database level via a UNIQUE constraint on
 * idempotency_key; this model exposes that guarantee through already_sent().
 */
class Reservation_reminder_model extends Common_Model {
    public $table = 'reservation_reminder_log';
    protected $primary_key = 'id';

    function __construct() {
        parent::__construct();
    }

    /**
     * Return true if a reminder with this key was already sent or attempted.
     *
     * @param string $idempotency_key
     * @return bool
     */
    public function already_sent($idempotency_key) {
        $result = $this->db
            ->select('id')
            ->from($this->table)
            ->where('idempotency_key', $idempotency_key)
            ->where('status !=', 'skipped')
            ->get()
            ->row_array();

        gvv_debug("already_sent key=$idempotency_key: " . ($result ? 'yes' : 'no'));
        return !empty($result);
    }

    /**
     * Insert a new log entry. On duplicate idempotency_key, the existing row
     * is left untouched (INSERT IGNORE) so the UNIQUE constraint acts as the
     * single gate against double sends.
     *
     * Returns the inserted id, or false on error.
     *
     * @param array $data  Keys: idempotency_key, reservation_id, trigger_source,
     *                           action_type, notification_type, recipients, channel,
     *                           provider, sent_at, status, message_body, error_message
     * @return int|false
     */
    public function log_attempt($data) {
        $CI = &get_instance();
        $username = $CI->dx_auth->get_username();

        $data['created_by'] = $username;
        $data['updated_by'] = $username;

        if (!isset($data['sent_at'])) {
            $data['sent_at'] = date('Y-m-d H:i:s');
        }

        $sql = "INSERT IGNORE INTO `{$this->table}`
            (`idempotency_key`, `reservation_id`, `trigger_source`, `action_type`,
             `notification_type`, `recipients`, `channel`, `provider`,
             `sent_at`, `status`, `message_body`, `error_message`,
             `created_by`, `updated_by`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = array(
            $data['idempotency_key'],
            $data['reservation_id'],
            $data['trigger_source'],
            $data['action_type'],
            isset($data['notification_type']) ? $data['notification_type'] : null,
            isset($data['recipients'])        ? $data['recipients']        : null,
            isset($data['channel'])           ? $data['channel']           : null,
            isset($data['provider'])          ? $data['provider']          : null,
            $data['sent_at'],
            $data['status'],
            isset($data['message_body'])      ? $data['message_body']      : null,
            isset($data['error_message'])     ? $data['error_message']     : null,
            $data['created_by'],
            $data['updated_by'],
        );

        if (!$this->db->query($sql, $params)) {
            gvv_error("reservation_reminder_model::log_attempt failed: " . $this->db->_error_message());
            return false;
        }

        $inserted_id = $this->db->insert_id();
        gvv_debug("log_attempt: key={$data['idempotency_key']} status={$data['status']} id=$inserted_id");
        return $inserted_id ?: false;
    }

    /**
     * Return active reservations whose start_datetime falls within the next
     * $window_hours hours from now.
     *
     * Excludes cancelled and completed reservations.
     * Joins members to provide email, phone, and reminder preferences for
     * both pilot and instructor.
     *
     * @param int $window_hours  Look-ahead window in hours (default 48)
     * @return array
     */
    public function get_pending_reservations($window_hours = 48) {
        $now    = date('Y-m-d H:i:s');
        $cutoff = date('Y-m-d H:i:s', time() + $window_hours * 3600);

        $section_filter = '';
        if ($this->section) {
            $section_id     = (int) $this->section_id;
            $section_filter = "AND r.section_id = $section_id";
        }

        $sql = "SELECT
                r.id, r.aircraft_id, r.start_datetime, r.end_datetime,
                r.pilot_member_id, r.instructor_member_id, r.purpose, r.status,
                r.notes, r.section_id, r.created_by AS reservation_created_by,
                m.macmodele, m.macimmat,
                pilot.mprenom AS pilot_prenom, pilot.mnom AS pilot_nom,
                pilot.memail AS pilot_email, pilot.mtelm AS pilot_phone,
                pilot.reminder_channel AS pilot_reminder_channel,
                pilot.reminder_period_hours AS pilot_reminder_period_hours,
                instr.mprenom AS instructor_prenom, instr.mnom AS instructor_nom,
                instr.memail AS instructor_email, instr.mtelm AS instructor_phone,
                instr.reminder_channel AS instructor_reminder_channel,
                instr.reminder_period_hours AS instructor_reminder_period_hours
            FROM reservations r
            LEFT JOIN machinesa m   ON r.aircraft_id           = m.macimmat
            LEFT JOIN membres pilot ON r.pilot_member_id       = pilot.mlogin
            LEFT JOIN membres instr ON r.instructor_member_id  = instr.mlogin
            WHERE r.start_datetime >= ?
              AND r.start_datetime <= ?
              AND r.status NOT IN ('maintenance', 'unavailable')
              $section_filter
            ORDER BY r.start_datetime ASC";

        $query = $this->db->query($sql, array($now, $cutoff));
        $result = $query ? $query->result_array() : array();

        gvv_debug("get_pending_reservations window={$window_hours}h now=$now cutoff=$cutoff count=" . count($result));
        return $result;
    }

    /**
     * Return all log entries for a given reservation, most recent first.
     *
     * @param int $reservation_id
     * @return array
     */
    public function get_log_for_reservation($reservation_id) {
        $result = $this->db
            ->from($this->table)
            ->where('reservation_id', $reservation_id)
            ->order_by('sent_at', 'desc')
            ->get()
            ->result_array();

        gvv_debug("get_log_for_reservation reservation_id=$reservation_id count=" . count($result));
        return $result;
    }

    /**
     * Return the most recent log entries, optionally filtered by status.
     * Used by the admin supervision view.
     *
     * @param int    $limit   Max rows to return (default 100)
     * @param string $status  Optional filter: 'success', 'failure', 'skipped'
     * @return array
     */
    public function get_recent_logs($limit = 100, $status = null) {
        $this->db
            ->select(
                'rrl.*, ' .
                'r.aircraft_id, r.start_datetime, r.pilot_member_id, ' .
                'pilot.mnom as pilot_nom, pilot.mprenom as pilot_prenom'
            )
            ->from($this->table . ' rrl')
            ->join('reservations r',    'rrl.reservation_id = r.id',       'left')
            ->join('membres pilot',     'r.pilot_member_id = pilot.mlogin', 'left')
            ->order_by('rrl.sent_at', 'desc')
            ->limit($limit);

        if ($status !== null) {
            $this->db->where('rrl.status', $status);
        }

        $result = $this->db->get()->result_array();
        gvv_debug("get_recent_logs limit=$limit status=" . var_export($status, true) . " count=" . count($result));
        return $result;
    }

    /**
     * Return reminder preferences for a single member.
     *
     * @param string $mlogin  Member login
     * @return array  ['reminder_channel' => ..., 'reminder_period_hours' => ...]
     *                or defaults if member not found.
     */
    public function get_member_preferences($mlogin) {
        $row = $this->db
            ->select('reminder_channel, reminder_period_hours')
            ->from('membres')
            ->where('mlogin', $mlogin)
            ->get()
            ->row_array();

        if (empty($row)) {
            return array('reminder_channel' => 'email', 'reminder_period_hours' => 24);
        }
        return $row;
    }

    /**
     * Persist reminder preferences for a member.
     *
     * @param string $mlogin
     * @param string $channel  'email' | 'sms' | 'email+sms'
     * @param int    $hours    Lead time in hours
     * @return bool
     */
    public function save_member_preferences($mlogin, $channel, $hours) {
        $allowed_channels = array('email', 'sms', 'email+sms');
        if (!in_array($channel, $allowed_channels)) {
            gvv_error("save_member_preferences: invalid channel '$channel'");
            return false;
        }

        $hours = max(1, intval($hours));

        $ok = $this->db->update('membres', array(
            'reminder_channel'      => $channel,
            'reminder_period_hours' => $hours,
        ), array('mlogin' => $mlogin));

        gvv_debug("save_member_preferences mlogin=$mlogin channel=$channel hours=$hours ok=" . var_export($ok, true));
        return $ok;
    }
}

/* End of file reservation_reminder_model.php */
/* Location: ./application/models/reservation_reminder_model.php */
