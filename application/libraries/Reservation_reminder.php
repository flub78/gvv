<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reservation_reminder — Core reminder library
 *
 * Two entry points:
 *   - handle_event()   : event notification on create/update/cancel
 *   - run_scheduler()  : temporal reminder for reservations within 48 h
 *
 * Recipient routing rules (design §Flux conceptuels):
 *   - Creator is crew member  → notify the other crew member only
 *   - Creator is third party  → notify both crew members
 *
 * Idempotency is enforced by reservation_reminder_model::already_sent()
 * backed by a DB UNIQUE constraint on idempotency_key.
 *
 * SMS dispatch is stubbed — implemented in Phase 8 (Brevo adapter).
 */
class Reservation_reminder
{
    /** @var object CodeIgniter instance */
    private $CI;
    /** @var Reservation_reminder_model */
    private $model;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->_load_dependencies();
    }

    // =========================================================================
    // Public entry points
    // =========================================================================

    /**
     * Handle a reservation lifecycle event (create / update / cancel).
     *
     * @param int    $reservation_id
     * @param string $event_type      'create' | 'update' | 'cancel'
     * @param string $triggered_by    Login of the user who triggered the event
     * @return bool  TRUE if at least one message was dispatched
     */
    public function handle_event($reservation_id, $event_type, $triggered_by)
    {
        $reservation = $this->_load_reservation($reservation_id);

        if ($reservation && !$this->_reminders_enabled((int) $reservation['section_id'])) {
            gvv_info("Reservation_reminder::handle_event reminders disabled for section {$reservation['section_id']}");
            return false;
        }

        if (empty($reservation)) {
            gvv_info("Reservation_reminder::handle_event reservation $reservation_id not found — skipped");
            $this->_log_skipped(
                null,
                $reservation_id,
                'event_' . $event_type,
                'event_notification',
                'Reservation not found'
            );
            return false;
        }

        $recipients = $this->_get_recipients($reservation, $triggered_by);

        if (empty($recipients)) {
            gvv_info("Reservation_reminder::handle_event no eligible recipients for reservation $reservation_id");
            $this->_log_skipped(
                null,
                $reservation_id,
                'event_' . $event_type,
                'event_notification',
                'No eligible recipients'
            );
            return false;
        }

        $trigger_source = 'event_' . $event_type;
        $any_sent = false;

        foreach ($recipients as $recipient) {
            $hour = (int) floor(time() / 3600);
            $key  = $this->_build_idempotency_key(
                'event', $reservation['id'], $recipient['login'], $event_type, $hour
            );
            $sent = $this->_dispatch(
                $reservation, $recipient, 'event_notification', $trigger_source, $event_type, $key
            );
            if ($sent) {
                $any_sent = true;
            }
        }

        return $any_sent;
    }

    /**
     * Evaluate all active reservations in the next 48 hours and send temporal
     * reminders according to each user's reminder_period_hours preference.
     *
     * Called either by the cron entry-point or the public URL trigger.
     *
     * @param string $source 'cron' | 'public_url'
     * @return int   Number of messages dispatched
     */
    public function run_scheduler($source = 'cron')
    {
        $pending   = $this->model->get_pending_reservations(48);
        // Filter to sections where reminders are enabled
        $pending = array_filter($pending, function($r) {
            return $this->_reminders_enabled((int) $r['section_id']);
        });
        $sent      = 0;
        $evaluated = 0;

        foreach ($pending as $reservation) {
            if (!empty($reservation['pilot_member_id'])) {
                $evaluated++;
                if ($this->_check_and_dispatch_scheduled($reservation, 'pilot', $source)) {
                    $sent++;
                }
            }
            if (!empty($reservation['instructor_member_id'])) {
                $evaluated++;
                if ($this->_check_and_dispatch_scheduled($reservation, 'instructor', $source)) {
                    $sent++;
                }
            }
        }

        gvv_info("Reservation_reminder::run_scheduler source=$source evaluated=$evaluated sent=$sent");
        return $sent;
    }

    // =========================================================================
    // Recipient routing
    // =========================================================================

    /**
     * Determine recipients for an event notification.
     *
     * @param array  $reservation  Full row from _load_reservation()
     * @param string $creator_login
     * @return array  Array of recipient arrays (may be empty)
     */
    public function _get_recipients($reservation, $creator_login)
    {
        $pilot_login      = $reservation['pilot_member_id'];
        $instructor_login = $reservation['instructor_member_id'];

        $is_pilot      = ($creator_login === $pilot_login);
        $is_instructor = (!empty($instructor_login) && $creator_login === $instructor_login);
        $is_crew       = ($is_pilot || $is_instructor);

        $recipients = array();

        if ($is_crew) {
            // Notify only the other crew member
            if ($is_pilot && !empty($instructor_login)) {
                $r = $this->_build_recipient($reservation, 'instructor');
                if ($r) {
                    $recipients[] = $r;
                }
            } elseif ($is_instructor) {
                $r = $this->_build_recipient($reservation, 'pilot');
                if ($r) {
                    $recipients[] = $r;
                }
            }
            // Solo pilot (no instructor) → nobody to notify
        } else {
            // Third-party creator → notify all crew members
            $r = $this->_build_recipient($reservation, 'pilot');
            if ($r) {
                $recipients[] = $r;
            }
            if (!empty($instructor_login)) {
                $r = $this->_build_recipient($reservation, 'instructor');
                if ($r) {
                    $recipients[] = $r;
                }
            }
        }

        return $recipients;
    }

    // =========================================================================
    // Idempotency key
    // =========================================================================

    /**
     * Build a deterministic idempotency key from an ordered list of parts.
     *
     * @param  string ...$parts
     * @return string  SHA-1 hex digest (40 chars, fits VARCHAR(255))
     */
    public function _build_idempotency_key()
    {
        $parts = func_get_args();
        return sha1(implode('|', $parts));
    }

    // =========================================================================
    // Message composition
    // =========================================================================

    /**
     * Compose an HTML email body for a given reservation, action type and recipient role.
     *
     * @param array  $reservation
     * @param string $action_type  'scheduled_reminder' | 'event_notification'
     * @param string $role         'pilot' | 'instructor'
     * @param string $event_type   'create' | 'update' | 'cancel' | null
     * @param string $source       Trigger source label
     * @return string  HTML body
     */
    public function _compose_email_body($reservation, $action_type, $role, $event_type = null, $source = '')
    {
        if ($this->CI) {
            $this->CI->lang->load('rappels_reservations', 'french');
        }

        $date_heure = date('d/m/Y H:i', strtotime($reservation['start_datetime']));
        $date_fin   = date('H:i',       strtotime($reservation['end_datetime']));
        $aeronef    = $reservation['macimmat'] . ' (' . $reservation['macmodele'] . ')';
        $pilote     = trim($reservation['pilot_prenom'] . ' ' . $reservation['pilot_nom']);
        $instructeur = '';
        if (!empty($reservation['instructor_member_id'])) {
            $instructeur = trim($reservation['instructor_prenom'] . ' ' . $reservation['instructor_nom']);
        }

        if ($action_type === 'scheduled_reminder') {
            $type_label = $this->_lang('reminder_type_scheduled',   'Rappel de réservation');
            $intro      = $this->_lang('reminder_intro_scheduled',  'Vous avez une réservation prévue prochainement.');
        } else {
            $event_labels = array(
                'create' => $this->_lang('reminder_event_create', 'Nouvelle réservation'),
                'update' => $this->_lang('reminder_event_update', 'Réservation modifiée'),
                'cancel' => $this->_lang('reminder_event_cancel', 'Réservation annulée'),
            );
            $type_label = isset($event_labels[$event_type]) ? $event_labels[$event_type] : 'Notification réservation';
            $intro      = $this->_lang('reminder_intro_event', 'Une réservation vous concerne.');
        }

        $role_label = ($role === 'instructor')
            ? $this->_lang('reminder_role_instructor', 'Instructeur')
            : $this->_lang('reminder_role_pilot',      'Pilote');

        $status_label = $reservation['status'];

        $nom_club = ($this->CI) ? ($this->CI->config->item('nom_club') ?: 'GVV') : 'GVV';

        $body  = '<html><body>';
        $body .= '<h2 style="color:#0d6efd;">' . htmlspecialchars($type_label, ENT_QUOTES, 'UTF-8') . '</h2>';
        $body .= '<p>' . htmlspecialchars($intro, ENT_QUOTES, 'UTF-8') . '</p>';
        $body .= '<table style="border-collapse:collapse;width:100%;max-width:500px;">';
        $body .= $this->_row('Date / heure',
                    htmlspecialchars($date_heure . ' – ' . $date_fin, ENT_QUOTES, 'UTF-8'));
        $body .= $this->_row('Aéronef',
                    htmlspecialchars($aeronef, ENT_QUOTES, 'UTF-8'));
        $body .= $this->_row('Pilote',
                    htmlspecialchars($pilote, ENT_QUOTES, 'UTF-8'));
        if ($instructeur) {
            $body .= $this->_row('Instructeur',
                        htmlspecialchars($instructeur, ENT_QUOTES, 'UTF-8'));
        }
        $body .= $this->_row('Statut',
                    htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8'));
        $body .= $this->_row('Votre rôle',
                    htmlspecialchars($role_label, ENT_QUOTES, 'UTF-8'));
        $body .= $this->_row('Type de message',
                    htmlspecialchars($type_label, ENT_QUOTES, 'UTF-8'));
        if ($source) {
            $body .= $this->_row('Déclenchement',
                        htmlspecialchars($source, ENT_QUOTES, 'UTF-8'));
        }
        $body .= '</table>';
        $body .= '<p style="color:#6c757d;font-size:0.85em;margin-top:24px;">'
               . 'Message automatique envoyé par ' . htmlspecialchars($nom_club, ENT_QUOTES, 'UTF-8')
               . ' – GVV. Ne pas répondre à cet email.</p>';
        $body .= '</body></html>';

        return $body;
    }

    /**
     * Compose a concise SMS body (EF-036).
     *
     * @param array  $reservation
     * @param string $action_type
     * @param string $role
     * @param string $event_type
     * @return string  Plain text (≤160 chars recommended)
     */
    public function _compose_sms_body($reservation, $action_type, $role, $event_type = null)
    {
        $date  = date('d/m H:i', strtotime($reservation['start_datetime']));
        $immat = $reservation['macimmat'];

        if ($action_type === 'scheduled_reminder') {
            return "Rappel vol $immat le $date – rôle: $role – GVV";
        }

        $events = array('create' => 'Nouvelle', 'update' => 'Modif.', 'cancel' => 'Annulée');
        $label  = isset($events[$event_type]) ? $events[$event_type] : 'Notification';
        return "$label réservation $immat le $date – rôle: $role – GVV";
    }

    // =========================================================================
    // Dispatch and delivery
    // =========================================================================

    /**
     * Orchestrate delivery for one recipient: check idempotency, send, log.
     *
     * @param array  $reservation
     * @param array  $recipient          From _build_recipient()
     * @param string $action_type        'scheduled_reminder' | 'event_notification'
     * @param string $source             Trigger source for the log
     * @param string $event_type         For event notifications
     * @param string $idempotency_key    Pre-built key (required)
     * @return bool  TRUE on success
     */
    public function _dispatch(
        $reservation, $recipient, $action_type, $source, $event_type, $idempotency_key
    ) {
        if ($this->model->already_sent($idempotency_key)) {
            gvv_debug("Reservation_reminder::_dispatch already sent key=$idempotency_key");
            return false;
        }

        $channel      = $recipient['channel'] ?: 'email';
        $notification = ($action_type === 'scheduled_reminder')
                      ? 'scheduled'
                      : ('event_' . ($event_type ?: 'unknown'));

        $subject      = $this->_compose_subject($reservation, $action_type, $recipient['role'], $event_type);
        $email_body   = $this->_compose_email_body($reservation, $action_type, $recipient['role'], $event_type, $source);

        $email_result = null;
        $sms_result   = null;
        $error_msg    = null;

        // Email delivery
        if ($channel === 'email' || $channel === 'email+sms') {
            if (!empty($recipient['email']) && validate_email($recipient['email'])) {
                $email_result = $this->_send_email(
                    $recipient['email'], $recipient['name'], $subject, $email_body
                );
                if (!$email_result) {
                    $error_msg = 'SMTP send failed to ' . $recipient['email'];
                    gvv_error("Reservation_reminder: $error_msg (reservation {$reservation['id']})");
                }
            } else {
                gvv_info("Reservation_reminder: no valid email for {$recipient['login']} — email skipped");
                $email_result = null; // not applicable
            }
        }

        // SMS delivery via Brevo
        if ($channel === 'sms' || $channel === 'email+sms') {
            if (!empty($recipient['phone'])) {
                $sms_body = $this->_compose_sms_body($reservation, $action_type, $recipient['role'], $event_type);
                $this->CI->load->library('Brevo_sms_adapter');
                $sms_res = $this->CI->brevo_sms_adapter->send($recipient['phone'], $sms_body);
                $sms_result = $sms_res['ok'];
                if (!$sms_result) {
                    $sms_error = $sms_res['error'];
                    $error_msg = $error_msg ? $error_msg . ' | ' . $sms_error : $sms_error;
                    gvv_error("Reservation_reminder: SMS failed for {$recipient['login']}: $sms_error");
                }
            } else {
                gvv_info("Reservation_reminder: no phone for {$recipient['login']} — SMS skipped");
                $sms_result = null;
            }
        }

        // Overall status
        if ($email_result === false || $sms_result === false) {
            $status = 'failure';
        } elseif ($email_result === null && $sms_result === null) {
            $status = 'skipped';
        } else {
            $status = 'success';
        }

        $this->model->log_attempt(array(
            'idempotency_key'   => $idempotency_key,
            'reservation_id'    => $reservation['id'],
            'trigger_source'    => $source,
            'action_type'       => $action_type,
            'notification_type' => $notification,
            'recipients'        => json_encode(array($recipient['login'])),
            'channel'           => $channel,
            'provider'          => (strpos($channel, 'email') !== false) ? 'smtp' : 'brevo',
            'status'            => $status,
            'message_body'      => $email_body,
            'error_message'     => $error_msg,
        ));

        return ($status === 'success');
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Check if a given crew member is in their reminder window and dispatch if so.
     *
     * @param array  $reservation
     * @param string $role    'pilot' | 'instructor'
     * @param string $source
     * @return bool
     */
    protected function _check_and_dispatch_scheduled($reservation, $role, $source)
    {
        $recipient = $this->_build_recipient($reservation, $role);
        if (empty($recipient)) {
            return false;
        }

        $period_hours = ($role === 'pilot')
            ? (int) ($reservation['pilot_reminder_period_hours'] ?: 24)
            : (int) ($reservation['instructor_reminder_period_hours'] ?: 24);

        $start_ts      = strtotime($reservation['start_datetime']);
        $send_after_ts = $start_ts - ($period_hours * 3600);

        if (time() < $send_after_ts) {
            gvv_debug("Reservation_reminder: not in window yet for {$recipient['login']} reservation {$reservation['id']}");
            return false;
        }

        $key = $this->_build_idempotency_key(
            'scheduled',
            $reservation['id'],
            $recipient['login'],
            date('Y-m-d', $start_ts)
        );

        return $this->_dispatch($reservation, $recipient, 'scheduled_reminder', $source, null, $key);
    }

    /**
     * Load a single reservation with full crew and preference data.
     *
     * @param int $reservation_id
     * @return array|null
     */
    protected function _load_reservation($reservation_id)
    {
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
            WHERE r.id = ?
            LIMIT 1";

        $query = $this->CI->db->query($sql, array((int) $reservation_id));
        if (!$query) {
            return null;
        }
        $row = $query->row_array();
        return $row ?: null;
    }

    /**
     * Build a recipient array for a crew role.
     *
     * @param array  $reservation
     * @param string $role  'pilot' | 'instructor'
     * @return array|null  null if the role is not filled in the reservation
     */
    protected function _build_recipient($reservation, $role)
    {
        if ($role === 'pilot') {
            if (empty($reservation['pilot_member_id'])) {
                return null;
            }
            return array(
                'login'   => $reservation['pilot_member_id'],
                'name'    => trim($reservation['pilot_prenom'] . ' ' . $reservation['pilot_nom']),
                'email'   => $reservation['pilot_email'],
                'phone'   => $reservation['pilot_phone'],
                'channel' => $reservation['pilot_reminder_channel'] ?: 'email',
                'role'    => 'pilot',
            );
        }

        if (empty($reservation['instructor_member_id'])) {
            return null;
        }
        return array(
            'login'   => $reservation['instructor_member_id'],
            'name'    => trim($reservation['instructor_prenom'] . ' ' . $reservation['instructor_nom']),
            'email'   => $reservation['instructor_email'],
            'phone'   => $reservation['instructor_phone'],
            'channel' => $reservation['instructor_reminder_channel'] ?: 'email',
            'role'    => 'instructor',
        );
    }

    /**
     * Compose the email subject line.
     */
    protected function _compose_subject($reservation, $action_type, $role, $event_type = null)
    {
        $immat = $reservation['macimmat'];
        $date  = date('d/m/Y', strtotime($reservation['start_datetime']));

        if ($action_type === 'scheduled_reminder') {
            return "[GVV] Rappel réservation $immat le $date";
        }

        $labels = array(
            'create' => 'Nouvelle réservation',
            'update' => 'Réservation modifiée',
            'cancel' => 'Réservation annulée',
        );
        $label = isset($labels[$event_type]) ? $labels[$event_type] : 'Notification réservation';
        return "[GVV] $label – $immat le $date";
    }

    /**
     * Send an HTML email using the CI Email library + Brevo SMTP config.
     *
     * @param string $to_email
     * @param string $to_name
     * @param string $subject
     * @param string $body  HTML
     * @return bool
     */
    protected function _send_email($to_email, $to_name, $subject, $body)
    {
        try {
            $this->CI->load->library('email');
            $this->CI->load->config('club', true);

            $from_email = $this->CI->config->item('email_club') ?: $this->CI->config->item('smtp_user');
            $from_name  = $this->CI->config->item('nom_club')   ?: 'GVV';

            $this->CI->email->clear(true);
            $this->CI->email->from($from_email, $from_name);
            $this->CI->email->to($to_email);
            $this->CI->email->subject($subject);
            $this->CI->email->message($body);

            $result = @$this->CI->email->send();

            if (!$result) {
                gvv_error("Reservation_reminder::_send_email failed to $to_email: "
                         . $this->CI->email->print_debugger());
            }

            return (bool) $result;
        } catch (Exception $e) {
            gvv_error("Reservation_reminder::_send_email exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Write a skipped log entry when no dispatch is attempted.
     */
    protected function _log_skipped($key, $reservation_id, $source, $action_type, $reason)
    {
        if ($key === null) {
            $key = $this->_build_idempotency_key('skip', $reservation_id, $source, time());
        }
        $this->model->log_attempt(array(
            'idempotency_key'   => $key,
            'reservation_id'    => $reservation_id,
            'trigger_source'    => $source,
            'action_type'       => $action_type,
            'status'            => 'skipped',
            'error_message'     => $reason,
        ));
    }

    /**
     * Check if reminders are enabled for a section.
     *
     * @param int $section_id
     * @return bool
     */
    protected function _reminders_enabled($section_id)
    {
        if (!$this->CI || !$section_id) {
            return true; // default enabled in test context or no section
        }
        $row = $this->CI->db
            ->select('reservation_reminders_enabled')
            ->from('sections')
            ->where('id', $section_id)
            ->get()
            ->row_array();
        return !empty($row) && !empty($row['reservation_reminders_enabled']);
    }

    /**
     * Translate a key with fallback when CI is unavailable (unit tests).
     */
    protected function _lang($key, $default)
    {
        if (!$this->CI) {
            return $default;
        }
        $val = $this->CI->lang->line($key);
        return ($val !== false && $val !== null) ? $val : $default;
    }

    /**
     * HTML table row helper for email body.
     */
    protected function _row($label, $value)
    {
        return '<tr>'
             . '<td style="padding:6px 12px;font-weight:bold;background:#f8f9fa;border:1px solid #dee2e6;">'
             . $label
             . '</td>'
             . '<td style="padding:6px 12px;border:1px solid #dee2e6;">'
             . $value
             . '</td>'
             . '</tr>';
    }

    /**
     * Load required models and helpers.
     */
    protected function _load_dependencies()
    {
        if (!class_exists('Common_Model')) {
            require_once APPPATH . 'models/common_model.php';
        }
        if (!class_exists('Reservation_reminder_model')) {
            require_once APPPATH . 'models/reservation_reminder_model.php';
        }

        $this->CI->load->helper('email');

        $this->model = new Reservation_reminder_model();
    }
}
