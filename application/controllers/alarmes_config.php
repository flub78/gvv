<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Configuration des alarmes d'un membre
 *
 * Chaque membre choisit ses alarmes (cases à cocher), ses canaux (email,
 * message du jour), le rappel SMS et la fréquence de synthèse. Un
 * administrateur club utilise la même page, avec en plus un sélecteur de
 * membre positionné par défaut sur son propre profil.
 *
 * @package controllers
 * @see doc/prds/gestion_alarmes_generiques_prd.md (exigences 3 et 5)
 */
class Alarmes_config extends MY_Controller
{
    function __construct()
    {
        parent::__construct();

        if (!$this->dx_auth->is_logged_in()) {
            redirect('auth/login');
        }
        if (!$this->config->item('gestion_documentaire')) {
            show_404();
        }

        $this->load->model('alarm_subscriptions_model');
        $this->load->model('alarm_member_settings_model');
        $this->lang->load('alarmes');
        $this->lang->load('tableaux_de_bord');

        $this->load->vars([
            'nav_back_url'   => $this->session->userdata('nav_from_url')   ?: 'welcome/section/user',
            'nav_back_label' => $this->session->userdata('nav_from_label') ?: $this->lang->line('db_section_personal'),
        ]);
    }

    /**
     * Page de configuration ; ?membre=<login> pour un administrateur club
     */
    public function index()
    {
        $login = $this->_target_login($this->input->get('membre'));
        $member = $this->db->select('mlogin, mnom, mprenom, mtelm, memail')
            ->where('mlogin', $login)->get('membres')->row_array();
        if (!$member) {
            $this->session->set_flashdata('error', $this->lang->line('alarm_config_unknown_member'));
            redirect('alarmes_config');
            return;
        }

        $effective = $this->alarm_subscriptions_model->effective_for($login);
        $proposed = $this->alarm_subscriptions_model->proposed_definitions($login);
        $creators = $this->_creator_names($effective);

        $data = array(
            'member' => $member,
            'is_self' => ($login === $this->dx_auth->get_username()),
            'is_admin' => $this->_is_admin(),
            'members' => $this->_is_admin() ? $this->_member_selector() : array(),
            'proposed' => $proposed,
            'effective' => $effective,
            'creators' => $creators,
            'settings' => $this->alarm_member_settings_model->get_for($login),
            'has_mobile' => !empty($member['mtelm']),
            'frequencies' => Alarm_member_settings_model::frequencies(),
        );
        load_last_view('alarmes_config/index', $data);
    }

    /**
     * Enregistrement des choix (POST)
     */
    public function save()
    {
        $login = $this->_target_login($this->input->post('membre'));
        $back = 'alarmes_config' . ($login !== $this->dx_auth->get_username() ? '?membre=' . rawurlencode($login) : '');

        $member = $this->db->select('mlogin, mtelm')->where('mlogin', $login)->get('membres')->row_array();
        if (!$member) {
            $this->session->set_flashdata('error', $this->lang->line('alarm_config_unknown_member'));
            redirect('alarmes_config');
            return;
        }

        $checked = (array) $this->input->post('alarms');
        $email = (bool) $this->input->post('channel_email');
        $motd = (bool) $this->input->post('channel_motd');
        $sms = (bool) $this->input->post('sms');
        $frequency = $this->input->post('frequency');

        // Abonnements après enregistrement : cochés + obligatoires
        $will_have_alarms = !empty($checked);
        foreach ($this->alarm_subscriptions_model->effective_for($login) as $sub) {
            if ($sub['mandatory']) {
                $will_have_alarms = true;
            }
        }
        if ($will_have_alarms && !$email && !$motd) {
            $this->session->set_flashdata('error', $this->lang->line('alarm_config_channel_required'));
            redirect($back);
            return;
        }
        if ($sms && empty($member['mtelm'])) {
            $this->session->set_flashdata('error', $this->lang->line('alarm_config_sms_no_mobile'));
            redirect($back);
            return;
        }

        $result = $this->alarm_subscriptions_model->save_choices($login, $checked);
        $this->alarm_member_settings_model->save_for($login, $email, $motd, $sms, $frequency);

        if ($result['ok']) {
            $this->session->set_flashdata('success', $this->lang->line('alarm_config_saved'));
        } else {
            $this->session->set_flashdata('error', implode("\n", $result['errors']));
        }
        redirect($back);
    }

    /**
     * Membre configuré : soi-même, ou le membre choisi par un administrateur club
     */
    private function _target_login($requested)
    {
        $self = $this->dx_auth->get_username();
        if ($requested && $requested !== $self && $this->_is_admin()) {
            return $requested;
        }
        return $self;
    }

    private function _is_admin()
    {
        return $this->user_has_role('club-admin');
    }

    /** Membres actifs, pour le sélecteur des administrateurs */
    private function _member_selector()
    {
        $logins = $this->alarm_subscriptions_model->active_logins();
        if (!$logins) {
            return array();
        }
        $rows = $this->db->select('mlogin, mnom, mprenom')->where_in('mlogin', $logins)
            ->order_by('mnom')->order_by('mprenom')->get('membres')->result_array();
        $selector = array();
        foreach ($rows as $row) {
            $selector[$row['mlogin']] = trim($row['mnom'] . ' ' . $row['mprenom']);
        }
        return $selector;
    }

    /** Nom des administrateurs auteurs des abonnements créés pour le membre */
    private function _creator_names($effective)
    {
        $logins = array();
        foreach ($effective as $sub) {
            if (!empty($sub['created_by'])) {
                $logins[$sub['created_by']] = true;
            }
        }
        $names = array();
        if ($logins) {
            foreach ($this->db->select('mlogin, mnom, mprenom')->where_in('mlogin', array_keys($logins))
                         ->get('membres')->result_array() as $row) {
                $names[$row['mlogin']] = trim($row['mprenom'] . ' ' . $row['mnom']);
            }
        }
        return $names;
    }
}
