<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Mes alarmes : configuration puis liste des alarmes d'un membre
 *
 * Configuration : matrice d'abonnement alarme × colonne (sections que le
 * membre peut superviser, plus Club pour les documents sans section),
 * canaux, rappel SMS et fréquence de synthèse. Liste : alarmes propres du
 * membre (toujours présentes) et occurrences de ses cases effectives,
 * actives et à venir.
 *
 * Un administrateur club utilise la même page, avec en plus un sélecteur de
 * membre positionné par défaut sur son propre profil.
 *
 * @package controllers
 * @see doc/prds/gestion_alarmes_generiques_prd.md (exigences 2 à 5)
 */
class Mes_alarmes extends MY_Controller
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

        $this->load->model('alarm_definitions_model');
        $this->load->model('alarm_subscriptions_model');
        $this->load->model('alarm_member_settings_model');
        $this->load->library('Notification_manager');
        $this->load->helper('validation');
        $this->lang->load('alarmes');
        $this->lang->load('tableaux_de_bord');

        $this->load->vars([
            'nav_back_url'   => $this->session->userdata('nav_from_url')   ?: 'welcome/section/user',
            'nav_back_label' => $this->session->userdata('nav_from_label') ?: $this->lang->line('db_section_personal'),
        ]);
    }

    /**
     * Page ; ?membre=<login> pour un administrateur club, filtres ?alarme et ?section
     */
    public function index()
    {
        $login = $this->_target_login($this->input->get('membre'));
        $member = $this->db->select('mlogin, mnom, mprenom, mtelm, memail')
            ->where('mlogin', $login)->get('membres')->row_array();
        if (!$member) {
            $this->session->set_flashdata('error', $this->lang->line('alarm_config_unknown_member'));
            redirect('mes_alarmes');
            return;
        }

        $sections = array();
        foreach ($this->db->select('id, nom')->order_by('nom')->get('sections')->result_array() as $row) {
            $sections[(int) $row['id']] = $row['nom'];
        }

        // Configuration
        $effective = $this->alarm_subscriptions_model->effective_for($login);
        $proposed = $this->alarm_subscriptions_model->proposed_definitions($login);
        $columns = array();
        foreach ($proposed as $def) {
            $columns += $def['cells'];
        }
        $matrix_columns = array();
        foreach ($sections as $id => $name) {
            if (isset($columns[$id])) {
                $matrix_columns[$id] = $name;
            }
        }
        if (isset($columns[Alarm_subscriptions_model::CLUB])) {
            $matrix_columns[Alarm_subscriptions_model::CLUB] = $this->lang->line('alarm_column_club');
        }

        // Alarmes
        $definitions = array();
        foreach ($this->alarm_definitions_model->get_all(true) as $def) {
            $definitions[$def['id']] = $def;
        }
        $occurrences = $this->notification_manager->member_occurrences($login);

        $filter_def = (int) $this->input->get('alarme');
        $filter_section = (int) $this->input->get('section');
        $rows = array();
        $listed_defs = array();
        foreach ($occurrences as $item) {
            $listed_defs[$item['definition_id']] = $definitions[$item['definition_id']]['display_label'];
            if ($filter_def && $item['definition_id'] !== $filter_def) {
                continue;
            }
            if ($filter_section && !empty($item['section_ids']) && !in_array($filter_section, $item['section_ids'], true)) {
                continue;
            }
            $item['definition_label'] = $definitions[$item['definition_id']]['display_label'];
            $item['section_names'] = array_values(array_intersect_key($sections, array_flip($item['section_ids'])));
            $rows[] = $item;
        }
        asort($listed_defs);

        load_last_view('mes_alarmes/index', array(
            'member' => $member,
            'is_self' => ($login === $this->dx_auth->get_username()),
            'is_admin' => $this->_is_admin(),
            'members' => $this->_is_admin() ? $this->_member_selector() : array(),
            'proposed' => $proposed,
            'matrix_columns' => $matrix_columns,
            'effective' => $effective,
            'creators' => $this->_creator_names($effective),
            'settings' => $this->alarm_member_settings_model->get_for($login),
            'has_mobile' => !empty($member['mtelm']),
            'frequencies' => Alarm_member_settings_model::frequencies(),
            'rows' => $rows,
            'listed_defs' => $listed_defs,
            'sections' => $sections,
            'filter_def' => $filter_def,
            'filter_section' => $filter_section,
        ));
    }

    /**
     * Enregistrement de la configuration (POST)
     */
    public function save()
    {
        $login = $this->_target_login($this->input->post('membre'));
        $back = 'mes_alarmes' . ($login !== $this->dx_auth->get_username() ? '?membre=' . rawurlencode($login) : '');

        $member = $this->db->select('mlogin, mtelm')->where('mlogin', $login)->get('membres')->row_array();
        if (!$member) {
            $this->session->set_flashdata('error', $this->lang->line('alarm_config_unknown_member'));
            redirect('mes_alarmes');
            return;
        }

        $checked = (array) $this->input->post('cells');
        $email = (bool) $this->input->post('channel_email');
        $motd = (bool) $this->input->post('channel_motd');
        $sms = (bool) $this->input->post('sms');
        $frequency = $this->input->post('frequency');

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
     * Membre affiché : soi-même, ou le membre choisi par un administrateur club
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

    /** Nom des auteurs des abonnements créés pour le membre */
    private function _creator_names($effective)
    {
        $logins = array();
        foreach ($effective as $cells) {
            foreach ($cells as $sub) {
                if (!empty($sub['created_by'])) {
                    $logins[$sub['created_by']] = true;
                }
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
