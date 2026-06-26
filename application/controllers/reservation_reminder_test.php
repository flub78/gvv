<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reservation_reminder_test — Page de test des rappels de réservation
 *
 * Accessible depuis le tableau de bord Développement & Tests.
 * Restreint aux utilisateurs listés dans program.php::dev_users.
 *
 * GET  /reservation_reminder_test        : formulaire de test
 * POST /reservation_reminder_test/send   : envoi du rappel de test
 */
class Reservation_reminder_test extends MY_Controller
{
    function __construct()
    {
        parent::__construct();

        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
        }

        if (!$this->_is_dev_authorized()) {
            show_error('Accès réservé aux utilisateurs de développement.', 403);
            return;
        }

        $this->lang->load('rappels_reservations');
        $this->lang->load('tableaux_de_bord');

        $this->load->vars([
            'nav_back_url'   => 'welcome/section/dev',
            'nav_back_label' => $this->lang->line('db_section_dev'),
        ]);
    }

    /**
     * GET — Affiche le formulaire de test
     */
    public function index()
    {
        $data = [
            'reservations' => $this->_get_reservations_list(),
            'result'       => null,
            'form'         => [],
        ];
        load_last_view('reservation_reminder_test/index', $data);
    }

    /**
     * POST — Envoie le rappel de test et affiche le résultat
     */
    public function send()
    {
        $reservation_id    = (int) $this->input->post('reservation_id');
        $channel           = $this->input->post('channel');
        $notification_type = $this->input->post('notification_type');
        $recipient_role    = $this->input->post('recipient_role');

        $allowed_channels = ['email', 'sms', 'email+sms'];
        $allowed_notifs   = ['scheduled_reminder', 'create', 'update', 'cancel'];
        $allowed_roles    = ['pilot', 'instructor'];

        $data = [
            'reservations' => $this->_get_reservations_list(),
            'form'         => $this->input->post(),
        ];

        // Validation
        if (!$reservation_id
            || !in_array($channel, $allowed_channels)
            || !in_array($notification_type, $allowed_notifs)
            || !in_array($recipient_role, $allowed_roles)
        ) {
            $data['result'] = ['ok' => false, 'message' => 'Paramètres invalides.'];
            load_last_view('reservation_reminder_test/index', $data);
            return;
        }

        $reservation = $this->_load_reservation($reservation_id);
        if (empty($reservation)) {
            $data['result'] = ['ok' => false, 'message' => "Réservation #$reservation_id introuvable."];
            load_last_view('reservation_reminder_test/index', $data);
            return;
        }

        // Vérifier que le rôle demandé est présent dans la réservation
        if ($recipient_role === 'pilot' && empty($reservation['pilot_member_id'])) {
            $data['result'] = ['ok' => false, 'message' => 'Cette réservation n\'a pas de pilote défini.'];
            load_last_view('reservation_reminder_test/index', $data);
            return;
        }
        if ($recipient_role === 'instructor' && empty($reservation['instructor_member_id'])) {
            $data['result'] = ['ok' => false, 'message' => 'Cette réservation n\'a pas d\'instructeur défini.'];
            load_last_view('reservation_reminder_test/index', $data);
            return;
        }

        // Construire le destinataire avec le canal overridé depuis le formulaire
        $recipient = $this->_build_test_recipient($reservation, $recipient_role, $channel);

        // Déterminer action_type et event_type
        if ($notification_type === 'scheduled_reminder') {
            $action_type = 'scheduled_reminder';
            $event_type  = null;
        } else {
            $action_type = 'event_notification';
            $event_type  = $notification_type; // create | update | cancel
        }

        // Clé unique pour bypasser l'idempotence (c'est un test)
        $idempotency_key = sha1('test|' . $reservation_id . '|' . time() . '|' . mt_rand());

        $this->load->library('Reservation_reminder');
        $sent = $this->reservation_reminder->_dispatch(
            $reservation,
            $recipient,
            $action_type,
            'test_page',
            $event_type,
            $idempotency_key
        );

        $test_email = $this->config->item('test_email');
        $test_phone = $this->config->item('test_phone');

        if ($sent) {
            $details = [];
            if (strpos($channel, 'email') !== false) {
                $details[] = 'Email → ' . ($test_email ?: $recipient['email']);
            }
            if (strpos($channel, 'sms') !== false) {
                $details[] = 'SMS → ' . ($test_phone ?: $recipient['phone']);
            }
            $data['result'] = [
                'ok'      => true,
                'message' => 'Rappel envoyé avec succès.',
                'details' => $details,
            ];
        } else {
            $data['result'] = [
                'ok'      => false,
                'message' => 'Échec de l\'envoi. Consultez les logs pour le détail.',
            ];
        }

        load_last_view('reservation_reminder_test/index', $data);
    }

    // =========================================================================
    // Helpers privés
    // =========================================================================

    /**
     * Retourne la liste des réservations récentes/futures pour le <select>.
     */
    private function _get_reservations_list()
    {
        $sql = "SELECT
                    r.id,
                    r.start_datetime,
                    r.end_datetime,
                    r.pilot_member_id,
                    r.instructor_member_id,
                    m.macimmat,
                    CONCAT(p.mprenom, ' ', p.mnom) AS pilot_name,
                    CONCAT(i.mprenom, ' ', i.mnom) AS instructor_name
                FROM reservations r
                LEFT JOIN machinesa m   ON r.aircraft_id          = m.macimmat
                LEFT JOIN membres p     ON r.pilot_member_id      = p.mlogin
                LEFT JOIN membres i     ON r.instructor_member_id = i.mlogin
                WHERE r.status NOT IN ('cancelled')
                ORDER BY r.start_datetime DESC
                LIMIT 100";

        $query = $this->db->query($sql);
        return $query ? $query->result_array() : [];
    }

    /**
     * Charge une réservation complète avec données membres (même requête que la lib).
     */
    private function _load_reservation($reservation_id)
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

        $query = $this->db->query($sql, [(int) $reservation_id]);
        if (!$query) {
            return null;
        }
        $row = $query->row_array();
        return $row ?: null;
    }

    /**
     * Construit un tableau recipient avec canal overridé depuis le formulaire.
     */
    private function _build_test_recipient($reservation, $role, $channel)
    {
        if ($role === 'pilot') {
            return [
                'login'   => $reservation['pilot_member_id'],
                'name'    => trim($reservation['pilot_prenom'] . ' ' . $reservation['pilot_nom']),
                'email'   => $reservation['pilot_email'],
                'phone'   => $reservation['pilot_phone'],
                'channel' => $channel,
                'role'    => 'pilot',
            ];
        }

        return [
            'login'   => $reservation['instructor_member_id'],
            'name'    => trim($reservation['instructor_prenom'] . ' ' . $reservation['instructor_nom']),
            'email'   => $reservation['instructor_email'],
            'phone'   => $reservation['instructor_phone'],
            'channel' => $channel,
            'role'    => 'instructor',
        ];
    }

    /**
     * Vérifie que l'utilisateur connecté est dans la liste dev_users.
     */
    private function _is_dev_authorized()
    {
        $username = $this->dx_auth->get_username();
        $config   = $this->config->item('dev_users');
        if (empty($config) || empty($username)) {
            return false;
        }
        $dev_users = array_map('trim', explode(',', $config));
        return in_array($username, $dev_users);
    }
}
