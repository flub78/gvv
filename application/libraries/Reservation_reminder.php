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

    /** @var array Noms des jours de la semaine (ISO-8601, 1 = lundi) */
    private static $jours_semaine = array(
        1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi',
        5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche',
    );

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
            gvv_info("REMINDER handle_event reminders disabled for section {$reservation['section_id']}");
            return false;
        }

        if (empty($reservation)) {
            gvv_info("REMINDER handle_event reservation $reservation_id not found — skipped");
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
            gvv_info("REMINDER handle_event no eligible recipients for reservation $reservation_id");
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
     * Evaluate all active reservations in the next 48 hours and send a single
     * daily summary per recipient per flight day, grouping all flights of the
     * day into one message.
     *
     * Called either by the cron entry-point or the public URL trigger.
     *
     * @param string $source 'cron' | 'public_url'
     * @return int   Number of daily summaries dispatched
     */
    public function run_scheduler($source = 'cron')
    {
        $pending = $this->model->get_pending_reservations(48);
        $pending = array_filter($pending, function($r) {
            return $this->_reminders_enabled((int) $r['section_id']);
        });

        // Group reservations by (member_login, flight_date)
        // groups[login][date] = array('recipient' => ..., 'reservations' => [...])
        $groups = array();

        foreach ($pending as $reservation) {
            $start_ts = strtotime($reservation['start_datetime']);
            $date     = date('Y-m-d', $start_ts);

            foreach (array('pilot', 'instructor') as $role) {
                $id_field = $role . '_member_id';
                if (empty($reservation[$id_field])) {
                    continue;
                }

                $recipient = $this->_build_recipient($reservation, $role);
                if (!$recipient) {
                    continue;
                }

                $period_hours = ($role === 'pilot')
                    ? (int) ($reservation['pilot_reminder_period_hours'] ?: 24)
                    : (int) ($reservation['instructor_reminder_period_hours'] ?: 24);

                if (time() < $start_ts - ($period_hours * 3600)) {
                    gvv_debug("REMINDER not in window yet for {$recipient['login']} reservation {$reservation['id']}");
                    continue;
                }

                $login = $recipient['login'];
                if (!isset($groups[$login][$date])) {
                    $groups[$login][$date] = array(
                        'recipient'    => $recipient,
                        'reservations' => array(),
                    );
                }
                $already = array_column($groups[$login][$date]['reservations'], 'id');
                if (!in_array($reservation['id'], $already)) {
                    $groups[$login][$date]['reservations'][] = $reservation;
                }
            }
        }

        $sent      = 0;
        $evaluated = 0;
        foreach ($groups as $login => $dates) {
            foreach ($dates as $date => $group) {
                $evaluated++;
                if ($this->_dispatch_daily_summary($group['recipient'], $date, $group['reservations'], $source)) {
                    $sent++;
                }
            }
        }

        gvv_info("REMINDER run_scheduler source=$source evaluated=$evaluated sent=$sent");
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
            $this->CI->lang->load('rappels_reservations');
        }

        $start_ts   = strtotime($reservation['start_datetime']);
        $date_heure = $this->_jour_semaine($start_ts) . ' ' . date('d/m/Y H:i', $start_ts);
        $date_fin   = date('H:i', strtotime($reservation['end_datetime']));
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

        $body  = '<html><head><meta charset="UTF-8"></head><body>';
        $body .= '<h2 style="color:#0d6efd;">' . htmlspecialchars($type_label, ENT_QUOTES, 'UTF-8') . '</h2>';
        $body .= '<p>' . htmlspecialchars($intro, ENT_QUOTES, 'UTF-8') . '</p>';
        $body .= '<table style="border-collapse:collapse;width:100%;max-width:500px;">';
        $body .= $this->_row($this->_lang('label_date_heure', 'Date / heure'),
                    htmlspecialchars($date_heure . ' – ' . $date_fin, ENT_QUOTES, 'UTF-8'));
        $body .= $this->_row($this->_lang('label_aeronef', 'Aéronef'),
                    htmlspecialchars($aeronef, ENT_QUOTES, 'UTF-8'));
        $body .= $this->_row($this->_lang('label_pilote', 'Pilote'),
                    htmlspecialchars($pilote, ENT_QUOTES, 'UTF-8'));
        if ($instructeur) {
            $body .= $this->_row($this->_lang('label_instructeur', 'Instructeur'),
                        htmlspecialchars($instructeur, ENT_QUOTES, 'UTF-8'));
        }
        $body .= $this->_row($this->_lang('label_statut', 'Statut'),
                    htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8'));
        $body .= $this->_row($this->_lang('label_votre_role', 'Votre rôle'),
                    htmlspecialchars($role_label, ENT_QUOTES, 'UTF-8'));
        $body .= $this->_row($this->_lang('label_type_message', 'Type de message'),
                    htmlspecialchars($type_label, ENT_QUOTES, 'UTF-8'));
        if ($source) {
            $body .= $this->_row($this->_lang('label_declenchement', 'Déclenchement'),
                        htmlspecialchars($source, ENT_QUOTES, 'UTF-8'));
        }
        $body .= '</table>';
        $body .= $this->_footer($nom_club);
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
        $ts      = strtotime($reservation['start_datetime']);
        $date    = substr($this->_jour_semaine($ts), 0, 3) . ' ' . date('d/m H:i', $ts);
        $immat   = $reservation['macimmat'];
        $le      = $this->_lang('connector_le', 'le');
        $role_word  = $this->_lang('sms_role_label', 'rôle');
        $role_label = ($role === 'instructor')
            ? $this->_lang('reminder_role_instructor', 'Instructeur')
            : $this->_lang('reminder_role_pilot',      'Pilote');

        if ($action_type === 'scheduled_reminder') {
            $prefix = $this->_lang('sms_rappel_vol', 'Rappel vol');
            return "$prefix $immat $le $date – $role_word: $role_label – GVV";
        }

        $events = array(
            'create' => $this->_lang('sms_event_create', 'Nouvelle'),
            'update' => $this->_lang('sms_event_update', 'Modif.'),
            'cancel' => $this->_lang('sms_event_cancel', 'Annulée'),
        );
        $label = isset($events[$event_type]) ? $events[$event_type] : 'Notification';
        $resa  = $this->_lang('sms_reservation_word', 'réservation');
        return "$label $resa $immat $le $date – $role_word: $role_label – GVV";
    }

    /**
     * Compose an HTML email body for a daily summary grouping all reservations
     * of a given day for one recipient.
     *
     * @param array  $recipient
     * @param string $date         'Y-m-d'
     * @param array  $reservations Sorted by start_datetime ASC
     * @param string $source
     * @return string HTML body
     */
    public function _compose_daily_email_body($recipient, $date, $reservations, $source = '')
    {
        $date_ts    = strtotime($date);
        $date_label = $this->_jour_semaine($date_ts) . ' ' . date('d/m/Y', $date_ts);
        $nom_club   = ($this->CI) ? ($this->CI->config->item('nom_club') ?: 'GVV') : 'GVV';

        $body  = '<html><head><meta charset="UTF-8"></head><body>';
        $body .= '<h2 style="color:#0d6efd;">'
               . htmlspecialchars($this->_lang('daily_summary_heading', 'Rappels de réservation pour la journée du'), ENT_QUOTES, 'UTF-8') . ' '
               . htmlspecialchars($date_label, ENT_QUOTES, 'UTF-8') . '</h2>';
        $body .= '<table style="border-collapse:collapse;width:100%;max-width:640px;">';
        $body .= '<tr style="background:#0d6efd;color:white;">'
               . '<th style="padding:8px 12px;text-align:left;">' . htmlspecialchars($this->_lang('label_heure', 'Heure'), ENT_QUOTES, 'UTF-8') . '</th>'
               . '<th style="padding:8px 12px;text-align:left;">' . htmlspecialchars($this->_lang('label_aeronef', 'Aéronef'), ENT_QUOTES, 'UTF-8') . '</th>'
               . '<th style="padding:8px 12px;text-align:left;">' . htmlspecialchars($this->_lang('label_pilote', 'Pilote'), ENT_QUOTES, 'UTF-8') . '</th>'
               . '<th style="padding:8px 12px;text-align:left;">' . htmlspecialchars($this->_lang('label_instructeur', 'Instructeur'), ENT_QUOTES, 'UTF-8') . '</th>'
               . '<th style="padding:8px 12px;text-align:left;">' . htmlspecialchars($this->_lang('label_statut', 'Statut'), ENT_QUOTES, 'UTF-8') . '</th>'
               . '</tr>';

        foreach ($reservations as $i => $r) {
            $heure     = date('H:i', strtotime($r['start_datetime']));
            $heure_fin = date('H:i', strtotime($r['end_datetime']));
            $aeronef   = $r['macimmat'];
            $pilote    = trim($r['pilot_prenom'] . ' ' . $r['pilot_nom']);
            $instr     = (!empty($r['instructor_member_id']))
                       ? trim($r['instructor_prenom'] . ' ' . $r['instructor_nom'])
                       : '–';
            $status    = $r['status'] ?: '–';
            $bg        = ($i % 2 === 0) ? '#f8f9fa' : '#ffffff';

            $body .= '<tr style="background:' . $bg . ';">';
            $body .= '<td style="padding:6px 12px;border:1px solid #dee2e6;">'
                   . htmlspecialchars("$heure – $heure_fin", ENT_QUOTES, 'UTF-8') . '</td>';
            $body .= '<td style="padding:6px 12px;border:1px solid #dee2e6;">'
                   . htmlspecialchars($aeronef, ENT_QUOTES, 'UTF-8') . '</td>';
            $body .= '<td style="padding:6px 12px;border:1px solid #dee2e6;">'
                   . htmlspecialchars($pilote, ENT_QUOTES, 'UTF-8') . '</td>';
            $body .= '<td style="padding:6px 12px;border:1px solid #dee2e6;">'
                   . htmlspecialchars($instr, ENT_QUOTES, 'UTF-8') . '</td>';
            $body .= '<td style="padding:6px 12px;border:1px solid #dee2e6;">'
                   . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</td>';
            $body .= '</tr>';
        }

        $body .= '</table>';
        $body .= $this->_footer($nom_club);
        $body .= '</body></html>';

        return $body;
    }

    /**
     * Compose a SMS for a daily summary.
     *
     * One flight  → full detail in the SMS (≤160 chars).
     * Several flights → short notice with link to mes_reservations.
     *
     * @param array  $recipient
     * @param string $date         'Y-m-d'
     * @param array  $reservations Sorted by start_datetime ASC
     * @return string Plain text
     */
    public function _compose_daily_sms_body($recipient, $date, $reservations)
    {
        $date_ts    = strtotime($date);
        $date_label = substr($this->_jour_semaine($date_ts), 0, 3) . ' ' . date('d/m/y', $date_ts);

        if (count($reservations) === 1) {
            $r      = $reservations[0];
            $heure  = date('H:i', strtotime($r['start_datetime']));
            $immat  = $r['macimmat'];
            $pilote = trim($r['pilot_prenom'] . ' ' . $r['pilot_nom']);
            $extra  = '';
            if (!empty($r['instructor_member_id'])) {
                $instr = trim($r['instructor_prenom'] . ' ' . $r['instructor_nom']);
                $extra = ', ' . $this->_lang('sms_instr_label', 'instr') . ': ' . $instr;
            }
            $prefix = $this->_lang('sms_rappel_vol', 'Rappel vol');
            return "$prefix $date_label $heure $immat – $pilote$extra – GVV";
        }

        $base_url = ($this->CI) ? rtrim($this->CI->config->item('base_url'), '/') : '';
        $count    = count($reservations);
        $format   = $this->_lang('sms_multi_reservations', 'Vous avez %1$d réservations pour la journée du %2$s. Détails sur %3$s');
        return sprintf($format, $count, $date_label, "$base_url/mes_reservations");
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
            gvv_debug("REMINDER _dispatch already sent key=$idempotency_key");
            return false;
        }

        $channel      = $recipient['channel'] ?: 'email';

        if ($channel === 'none') {
            gvv_info("REMINDER _dispatch skipped (channel=none) for {$recipient['login']} reservation {$reservation['id']}");
            return false;
        }

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
                    gvv_error("REMINDER email failed: $error_msg (reservation {$reservation['id']})");
                } else {
                    gvv_info("REMINDER email sent to {$recipient['email']} login={$recipient['login']} reservation={$reservation['id']}");
                }
            } else {
                gvv_info("REMINDER no valid email for {$recipient['login']} — email skipped");
                $email_result = null; // not applicable
            }
        }

        // SMS delivery via Brevo
        if ($channel === 'sms' || $channel === 'email+sms') {
            if (!empty($recipient['phone'])) {
                $sms_body = $this->_compose_sms_body($reservation, $action_type, $recipient['role'], $event_type);
                $this->CI->load->library('Brevo_sms_adapter');
                $sms_phone = test_intercept_phone($recipient['phone']);
                $sms_res = $this->CI->brevo_sms_adapter->send($sms_phone, $sms_body);
                $sms_result = $sms_res['ok'];
                if (!$sms_result) {
                    $sms_error = $sms_res['error'];
                    $error_msg = $error_msg ? $error_msg . ' | ' . $sms_error : $sms_error;
                    gvv_error("REMINDER SMS failed for {$recipient['login']}: $sms_error");
                } else {
                    gvv_info("REMINDER SMS sent to {$recipient['login']} phone={$sms_phone} reservation={$reservation['id']}");
                }
            } else {
                gvv_info("REMINDER no phone for {$recipient['login']} — SMS skipped");
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
     * Send a single daily summary grouping all reservations on a given date for
     * one recipient.
     *
     * @param array  $recipient   From _build_recipient()
     * @param string $date        'Y-m-d'
     * @param array  $reservations  Sorted list of reservations for that day
     * @param string $source
     * @return bool
     */
    protected function _dispatch_daily_summary($recipient, $date, $reservations, $source)
    {
        $key = $this->_build_idempotency_key('daily_summary', $recipient['login'], $date);

        if ($this->model->already_sent($key)) {
            gvv_debug("REMINDER daily_summary already sent key=$key");
            return false;
        }

        $channel = $recipient['channel'] ?: 'email';

        if ($channel === 'none') {
            gvv_info("REMINDER daily_summary skipped (channel=none) for {$recipient['login']} date=$date");
            return false;
        }

        usort($reservations, function($a, $b) {
            return strcmp($a['start_datetime'], $b['start_datetime']);
        });

        $date_ts       = strtotime($date);
        $date_label    = $this->_jour_semaine($date_ts) . ' ' . date('d/m/Y', $date_ts);
        $subject_label = $this->_lang('subject_rappels_journee', 'Rappels de réservation pour le');
        $subject       = "[GVV] $subject_label $date_label";
        $email_body   = $this->_compose_daily_email_body($recipient, $date, $reservations, $source);

        $email_result = null;
        $sms_result   = null;
        $error_msg    = null;

        if ($channel === 'email' || $channel === 'email+sms') {
            if (!empty($recipient['email']) && validate_email($recipient['email'])) {
                $email_result = $this->_send_email(
                    $recipient['email'], $recipient['name'], $subject, $email_body
                );
                if (!$email_result) {
                    $error_msg = 'SMTP send failed to ' . $recipient['email'];
                    gvv_error("REMINDER daily email failed: $error_msg (date $date)");
                } else {
                    gvv_info("REMINDER daily email sent to {$recipient['email']} login={$recipient['login']} date=$date reservations=" . count($reservations));
                }
            } else {
                gvv_info("REMINDER no valid email for {$recipient['login']} — email skipped");
            }
        }

        if ($channel === 'sms' || $channel === 'email+sms') {
            if (!empty($recipient['phone'])) {
                $sms_body  = $this->_compose_daily_sms_body($recipient, $date, $reservations);
                $this->CI->load->library('Brevo_sms_adapter');
                $sms_phone = test_intercept_phone($recipient['phone']);
                $sms_res   = $this->CI->brevo_sms_adapter->send($sms_phone, $sms_body);
                $sms_result = $sms_res['ok'];
                if (!$sms_result) {
                    $sms_error = $sms_res['error'];
                    $error_msg = $error_msg ? $error_msg . ' | ' . $sms_error : $sms_error;
                    gvv_error("REMINDER daily SMS failed for {$recipient['login']}: $sms_error");
                } else {
                    gvv_info("REMINDER daily SMS sent to {$recipient['login']} phone={$sms_phone} date=$date");
                }
            } else {
                gvv_info("REMINDER no phone for {$recipient['login']} — SMS skipped");
            }
        }

        if ($email_result === false || $sms_result === false) {
            $status = 'failure';
        } elseif ($email_result === null && $sms_result === null) {
            $status = 'skipped';
        } else {
            $status = 'success';
        }

        $this->model->log_attempt(array(
            'idempotency_key'   => $key,
            'reservation_id'    => $reservations[0]['id'],
            'trigger_source'    => $source,
            'action_type'       => 'scheduled_reminder',
            'notification_type' => 'daily_summary',
            'recipients'        => json_encode(array($recipient['login'])),
            'channel'           => $channel,
            'provider'          => (strpos($channel, 'email') !== false) ? 'smtp' : 'brevo',
            'status'            => $status,
            'message_body'      => $email_body,
            'error_message'     => $error_msg,
        ));

        return ($status === 'success');
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
        $ts    = strtotime($reservation['start_datetime']);
        $date  = $this->_jour_semaine($ts) . ' ' . date('d/m/Y', $ts);
        $le    = $this->_lang('connector_le', 'le');

        if ($action_type === 'scheduled_reminder') {
            $subject_label = $this->_lang('subject_rappel_reservation', 'Rappel réservation');
            return "[GVV] $subject_label $immat $le $date";
        }

        $labels = array(
            'create' => $this->_lang('reminder_event_create', 'Nouvelle réservation'),
            'update' => $this->_lang('reminder_event_update', 'Réservation modifiée'),
            'cancel' => $this->_lang('reminder_event_cancel', 'Réservation annulée'),
        );
        $label = isset($labels[$event_type]) ? $labels[$event_type] : 'Notification réservation';
        return "[GVV] $label – $immat $le $date";
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

            $to_email = test_intercept_email($to_email, $subject);

            $this->CI->email->clear(true);
            $this->CI->email->set_mailtype('html');
            $this->CI->email->from($from_email, $from_name);
            $this->CI->email->to($to_email);
            $this->CI->email->subject($subject);
            $this->CI->email->message($body);

            $result = @$this->CI->email->send();

            if (!$result) {
                gvv_error("REMINDER _send_email failed to $to_email: "
                         . $this->CI->email->print_debugger());
            }

            return (bool) $result;
        } catch (Exception $e) {
            gvv_error("REMINDER _send_email exception: " . $e->getMessage());
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
     * Nom du jour de la semaine pour un timestamp donné, dans la langue
     * configurée du site (repli sur le français).
     *
     * @param int $timestamp
     * @return string
     */
    protected function _jour_semaine($timestamp)
    {
        $numero = (int) date('N', $timestamp);
        return $this->_lang('jour_' . $numero, self::$jours_semaine[$numero]);
    }

    /**
     * Translate a key with fallback when CI is unavailable (unit tests).
     */
    protected function _lang($key, $default)
    {
        if (!$this->CI) {
            return $default;
        }
        $this->CI->lang->load('rappels_reservations');
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
     * HTML footer paragraph shared by all reminder emails.
     */
    protected function _footer($nom_club)
    {
        $format = $this->_lang('footer_auto_message', 'Message automatique envoyé par %s – GVV. Ne pas répondre à cet email.');
        return '<p style="color:#6c757d;font-size:0.85em;margin-top:24px;">'
             . sprintf(htmlspecialchars($format, ENT_QUOTES, 'UTF-8'), htmlspecialchars($nom_club, ENT_QUOTES, 'UTF-8'))
             . '</p>';
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
