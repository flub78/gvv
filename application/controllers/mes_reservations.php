<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Mes réservations
 *
 * Page personnelle listant les réservations futures de l'utilisateur connecté
 * (en tant que pilote ou instructeur), avec suppression et gestion des
 * préférences de rappel.
 */
class Mes_reservations extends MY_Controller
{
    function __construct()
    {
        parent::__construct();

        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
        }

        $this->lang->load('rappels_reservations');
        $this->lang->load('agenda_google');
        $this->lang->load('tableaux_de_bord');

        $this->load->vars([
            'nav_back_url'   => $this->session->userdata('nav_from_url')   ?: 'welcome/section/flights',
            'nav_back_label' => $this->session->userdata('nav_from_label') ?: $this->lang->line('db_section_flights'),
        ]);
    }

    /**
     * Liste les réservations futures de l'utilisateur connecté.
     */
    public function index()
    {
        $username = $this->dx_auth->get_username();

        $reservations = $this->_get_user_reservations($username);
        $prefs        = $this->_get_prefs($username);

        $data = array(
            'reservations'  => $reservations,
            'prefs'         => $prefs,
            'username'      => $username,
            'agenda_google' => $this->_agenda_google_view($username),
        );

        load_last_view('mes_reservations/index', $data);
    }

    /**
     * Supprime une réservation et déclenche l'événement cancel.
     * Accessible via POST depuis la liste (formulaire hidden).
     */
    public function delete()
    {
        $reservation_id = (int) $this->input->post('reservation_id');
        $username       = $this->dx_auth->get_username();

        if (!$reservation_id) {
            $this->session->set_flashdata('error', $this->lang->line('mes_reservations_not_found'));
            redirect('mes_reservations');
            return;
        }

        // Vérifier que la réservation appartient bien à cet utilisateur
        $reservation = $this->_load_own_reservation($reservation_id, $username);
        if (!$reservation) {
            $this->session->set_flashdata('error', $this->lang->line('mes_reservations_not_found'));
            redirect('mes_reservations');
            return;
        }

        // Supprimer la réservation (le modèle prévient les rappels)
        $this->load->model('reservations_model');
        $ok = $this->reservations_model->delete_reservation($reservation_id);

        if ($ok) {
            $this->session->set_flashdata('success', $this->lang->line('mes_reservations_deleted_ok'));
        } else {
            $this->session->set_flashdata('error', $this->lang->line('mes_reservations_deleted_error'));
        }

        redirect('mes_reservations');
    }

    /**
     * Enregistre les préférences de rappel (canal + délai).
     */
    public function save_preferences()
    {
        $username = $this->dx_auth->get_username();
        $channel  = $this->input->post('reminder_channel');
        $hours    = (int) $this->input->post('reminder_period_hours');

        $allowed  = array('email', 'sms', 'email+sms', 'none');
        if (!in_array($channel, $allowed)) {
            $channel = 'email';
        }
        $hours = max(1, min(168, $hours)); // entre 1h et 7 jours

        $this->load->model('reservation_reminder_model');
        $ok = $this->reservation_reminder_model->save_member_preferences($username, $channel, $hours);

        if ($ok) {
            $this->session->set_flashdata('success', $this->lang->line('mes_reservations_prefs_ok'));
        } else {
            $this->session->set_flashdata('error', $this->lang->line('mes_reservations_prefs_error'));
        }

        redirect('mes_reservations');
    }

    // =========================================================================
    // Synchronisation avec Google Agenda
    // =========================================================================

    /**
     * Demande d'activation : redirige le membre vers l'écran d'autorisation
     * de Google, avec un paramètre d'état lié à sa session.
     */
    public function agenda_google_activer()
    {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect('mes_reservations');
            return;
        }
        $sync = $this->_agenda_google();
        if (!$sync->available_for($this->dx_auth->get_username())) {
            $this->session->set_flashdata('error', $this->lang->line('agenda_google_indisponible'));
            redirect('mes_reservations');
            return;
        }
        $state = bin2hex(random_bytes(16));
        $this->session->set_userdata('agenda_google_state', $state);
        redirect($sync->authorization_url($state));
    }

    /**
     * Retour de l'écran d'autorisation de Google.
     */
    public function agenda_google_retour()
    {
        $username = $this->dx_auth->get_username();
        $expected = $this->session->userdata('agenda_google_state');
        $this->session->unset_userdata('agenda_google_state');

        $state = (string) $this->input->get('state');
        if (empty($expected) || !hash_equals((string) $expected, $state)) {
            gvv_error("AGENDA_GOOGLE retour refusé (paramètre d'état invalide) membre=$username");
            $this->session->set_flashdata('error', $this->lang->line('agenda_google_err_etat'));
            redirect('mes_reservations');
            return;
        }
        if ($this->input->get('error')) {
            $this->session->set_flashdata('error', $this->lang->line('agenda_google_err_refus'));
            redirect('mes_reservations');
            return;
        }

        $sync = $this->_agenda_google();
        if (!$sync->available_for($username)) {
            $this->session->set_flashdata('error', $this->lang->line('agenda_google_indisponible'));
            redirect('mes_reservations');
            return;
        }

        try {
            $result = $sync->activate($username, (string) $this->input->get('code'));
            $message = sprintf($this->lang->line('agenda_google_active_ok'), $result['compte']);
            if (isset($result['inscrites'])) {
                $message .= ' ' . sprintf($this->lang->line('agenda_google_active_nombre'), $result['inscrites']);
            }
            $this->session->set_flashdata('success', $message);
        } catch (Google_agenda_exception $e) {
            gvv_error("AGENDA_GOOGLE échec d'activation membre=$username : " . $e->getMessage());
            $key = ($e->kind === Google_agenda_exception::REFUS) ? 'agenda_google_err_refus'
                : (($e->kind === Google_agenda_exception::PASSAGERE) ? 'agenda_google_err_passagere' : 'agenda_google_err_activation');
            $this->session->set_flashdata('error', $this->lang->line($key));
        } catch (Exception $e) {
            gvv_error("AGENDA_GOOGLE échec d'activation membre=$username : " . $e->getMessage());
            $this->session->set_flashdata('error', $this->lang->line('agenda_google_err_activation'));
        }
        redirect('mes_reservations');
    }

    /**
     * Arrêt de la synchronisation, après l'avertissement affiché par la page.
     */
    public function agenda_google_arreter()
    {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect('mes_reservations');
            return;
        }
        $result = $this->_agenda_google()->stop($this->dx_auth->get_username());
        if ($result === NULL) {
            $this->session->set_flashdata('error', $this->lang->line('agenda_google_err_pas_active'));
        } elseif ($result['revoque']) {
            $this->session->set_flashdata('success', $this->lang->line('agenda_google_arret_ok'));
        } else {
            $this->session->set_flashdata('success', $this->lang->line('agenda_google_arret_ok') . ' '
                . $this->lang->line('agenda_google_arret_revocation'));
        }
        redirect('mes_reservations');
    }

    private function _agenda_google()
    {
        $this->load->library('Agenda_google_sync');
        return $this->agenda_google_sync;
    }

    /**
     * Données du bloc « Agenda Google », NULL s'il ne doit pas être affiché.
     */
    private function _agenda_google_view($username)
    {
        $sync = $this->_agenda_google();
        if (!$sync->available_for($username)) {
            return NULL;
        }
        return array('liaison' => $sync->status($username));
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Return all future reservations for a user (pilot or instructor), most recent first.
     */
    private function _get_user_reservations($login)
    {
        $now = date('Y-m-d H:i:s');
        $sql = "SELECT
                    r.id, r.aircraft_id, r.start_datetime, r.end_datetime,
                    r.status, r.purpose, r.notes,
                    r.pilot_member_id, r.instructor_member_id,
                    m.macmodele, m.macimmat
                FROM reservations r
                LEFT JOIN machinesa m ON r.aircraft_id = m.macimmat
                WHERE r.start_datetime >= ?
                  AND (r.pilot_member_id = ? OR r.instructor_member_id = ?)
                  AND r.status NOT IN ('maintenance','unavailable')
                ORDER BY r.start_datetime ASC";

        $query = $this->db->query($sql, array($now, $login, $login));
        return $query ? $query->result_array() : array();
    }

    /**
     * Load a reservation only if it belongs to the given user.
     */
    private function _load_own_reservation($id, $login)
    {
        $sql = "SELECT id, pilot_member_id, instructor_member_id, start_datetime
                FROM reservations
                WHERE id = ?
                  AND (pilot_member_id = ? OR instructor_member_id = ?)
                LIMIT 1";

        $query = $this->db->query($sql, array($id, $login, $login));
        if (!$query) {
            return null;
        }
        $row = $query->row_array();
        return $row ?: null;
    }

    /**
     * Return reminder preferences for the current user.
     */
    private function _get_prefs($login)
    {
        $row = $this->db
            ->select('reminder_channel, reminder_period_hours')
            ->from('membres')
            ->where('mlogin', $login)
            ->get()
            ->row_array();

        return $row ?: array('reminder_channel' => 'email', 'reminder_period_hours' => 24);
    }
}
