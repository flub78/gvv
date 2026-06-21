<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Reservation_reminder_log — Vue admin des logs de rappels
 *
 * Page de supervision accessible aux administrateurs section.
 * Affiche les dernières entrées de reservation_reminder_log avec filtres.
 */
class Reservation_reminder_log extends MY_Controller
{
    function __construct()
    {
        parent::__construct();

        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
        }

        $this->load->library('Gvv_Authorization');
        $user_id    = $this->dx_auth->get_user_id();
        $section_id = (int) $this->session->userdata('section');
        if (!$this->gvv_authorization->has_any_role($user_id, array('club-admin', 'ca', 'bureau'), $section_id)) {
            show_error('Accès réservé aux administrateurs.', 403);
            return;
        }

        $this->lang->load('rappels_reservations');
        $this->lang->load('tableaux_de_bord');

        $this->load->vars([
            'nav_back_url'   => $this->session->userdata('nav_from_url')   ?: 'welcome/section/admin_sys',
            'nav_back_label' => $this->session->userdata('nav_from_label') ?: $this->lang->line('db_section_admin_sys'),
        ]);
    }

    /**
     * Liste les entrées de log avec filtre optionnel par statut.
     */
    public function index()
    {
        $status = $this->input->get('status');
        $allowed_statuses = array('success', 'failure', 'skipped');
        if (!in_array($status, $allowed_statuses)) {
            $status = null;
        }

        $this->load->model('reservation_reminder_model');
        $logs = $this->reservation_reminder_model->get_recent_logs(200, $status);

        $data = array(
            'logs'           => $logs,
            'status_filter'  => $status,
        );

        load_last_view('reservation_reminder_log/index', $data);
    }
}
