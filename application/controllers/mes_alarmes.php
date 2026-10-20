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
 * Désactivation d'une alarme (Lot 4) : pour le membre, sur une alarme qu'il
 * suit par abonnement ; pour tout le monde, par un administrateur club.
 * Les alarmes désactivées s'affichent à la demande, avec leur réactivation.
 *
 * @package controllers
 * @see doc/prds/gestion_alarmes_generiques_prd.md (exigences 2 à 5, 13)
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

        $this->load->model('alarm_subscriptions_model');
        $this->load->model('alarm_member_settings_model');
        $this->load->model('alarm_dismissals_model');
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
        $proposed = Alarm_definitions_model::sort_by_category(
            $this->alarm_subscriptions_model->proposed_definitions($login));
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

        // Alarmes ; liens de superviseur quand un administrateur consulte un autre membre
        $is_self = ($login === $this->dx_auth->get_username());
        list($definitions, $items_by_def) = $this->notification_manager->collect_items();
        $dismissed = array();
        $occurrences = $this->notification_manager->occurrences_for($login, $effective, $definitions, $items_by_def, $dismissed);

        // Sans paramètre section, la liste suit la section active (« Toutes » = pas de filtre)
        $filter_def = (int) $this->input->get('alarme');
        $requested_section = $this->input->get('section');
        $filter_section = ($requested_section === false)
            ? (int) $this->session->userdata('section')
            : (int) $requested_section;
        if (!isset($sections[$filter_section])) {
            $filter_section = 0;
        }
        $lists = array('rows' => array(), 'dismissed_rows' => array());
        $listed_defs = array();
        $hidden_count = 0;
        foreach (array('rows' => $occurrences, 'dismissed_rows' => $dismissed) as $list => $items) {
            foreach ($items as $item) {
                $listed_defs[$item['definition_id']] = $definitions[$item['definition_id']]['display_label'];
                if ($filter_def && $item['definition_id'] !== $filter_def) {
                    continue;
                }
                if (!Notification_manager::item_in_section($item, $filter_section)) {
                    $hidden_count += ($list === 'rows') ? 1 : 0;
                    continue;
                }
                $item['definition_label'] = $definitions[$item['definition_id']]['display_label'];
                $item['category'] = $definitions[$item['definition_id']]['category'];
                $item['link'] = Notification_manager::item_link($item, $is_self);
                $item['section_names'] = array_values(array_intersect_key($sections, array_flip($item['section_ids'])));
                $lists[$list][] = $item;
            }
        }
        list($rows, $dismissed_rows) = array($lists['rows'], $lists['dismissed_rows']);
        asort($listed_defs);

        load_last_view('mes_alarmes/index', array(
            'member' => $member,
            'is_self' => $is_self,
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
            'hidden_count' => $hidden_count,
            'dismissed_rows' => $dismissed_rows,
            'authors' => $this->_dismissal_authors($dismissed_rows),
            'today' => date('Y-m-d'),
        ));
    }

    /**
     * Désactivation d'une alarme (POST) : pour le membre affiché, ou pour
     * tout le monde (administrateur club)
     */
    public function dismiss()
    {
        $login = $this->_target_login($this->input->post('membre'));
        $back = $this->_back_url($login);
        $for_all = ($this->input->post('scope') === 'all');
        if ($for_all && !$this->_is_admin()) {
            $this->session->set_flashdata('error', $this->lang->line('alarm_dismiss_error_right'));
            redirect($back);
            return;
        }

        $item = $this->_member_item($login, (string) $this->input->post('key'));
        if (!$item) {
            $this->session->set_flashdata('error', $this->lang->line('alarm_dismiss_error_unknown'));
            redirect($back);
            return;
        }
        $until = ($this->input->post('duration') === 'until') ? (string) $this->input->post('until_date') : null;
        if ($until === '') {
            $this->session->set_flashdata('error', $this->lang->line('alarm_dismiss_error_date'));
            redirect($back);
            return;
        }

        $result = $this->alarm_dismissals_model->dismiss($item, $for_all ? null : $login, $until,
            (string) $this->input->post('reason'));
        if ($result['ok']) {
            $this->session->set_flashdata('success', sprintf($this->lang->line($for_all
                ? 'alarm_dismiss_done_all' : 'alarm_dismiss_done_self'), $item['label'] . ' — ' . $item['detail']));
        } else {
            $this->session->set_flashdata('error', $result['error']);
        }
        redirect($back);
    }

    /**
     * Réactivation d'une alarme désactivée (POST) : celle du membre affiché,
     * ou pour tout le monde par un administrateur club
     */
    public function reactivate()
    {
        $login = $this->_target_login($this->input->post('membre'));
        $back = $this->_back_url($login);
        $row = $this->db->where('id', (int) $this->input->post('dismissal_id'))
            ->where('reactivated_at IS NULL', null, false)->get('alarm_dismissals')->row_array();
        if (!$row) {
            $this->session->set_flashdata('error', $this->lang->line('alarm_dismiss_error_unknown'));
        } elseif ($row['member_login'] !== $login && !($row['member_login'] === null && $this->_is_admin())) {
            $this->session->set_flashdata('error', $this->lang->line('alarm_dismiss_error_right'));
        } else {
            $this->alarm_dismissals_model->reactivate($row['id'], $this->dx_auth->get_username());
            $this->session->set_flashdata('success', sprintf($this->lang->line('alarm_reactivate_done'), $row['label']));
        }
        redirect($back);
    }

    /** Occurrence d'un membre, visible ou désactivée, par sa clé */
    private function _member_item($login, $key)
    {
        list($definitions, $items_by_def) = $this->notification_manager->collect_items();
        $dismissed = array();
        $occurrences = $this->notification_manager->occurrences_for($login,
            $this->alarm_subscriptions_model->effective_for($login), $definitions, $items_by_def, $dismissed);
        if (isset($occurrences[$key])) {
            return $occurrences[$key];
        }
        return isset($dismissed[$key]) ? $dismissed[$key] : null;
    }

    /** Retour à la liste, avec le membre et les filtres de la page */
    private function _back_url($login)
    {
        $query = array();
        if ($login !== $this->dx_auth->get_username()) {
            $query['membre'] = $login;
        }
        foreach (array('alarme', 'section') as $filter) {
            $value = $this->input->post('filter_' . $filter);
            if ($value !== false) {
                $query[$filter] = (string) (int) $value === (string) $value ? $value : '';
            }
        }
        return 'mes_alarmes' . ($query ? '?' . http_build_query($query) : '') . '#alarm-list';
    }

    /** Nom des auteurs des désactivations affichées */
    private function _dismissal_authors($rows)
    {
        $logins = array();
        foreach ($rows as $row) {
            $logins[$row['dismissal']['created_by']] = true;
        }
        unset($logins['']);
        $names = array();
        if ($logins) {
            foreach ($this->db->select('mlogin, mnom, mprenom')->where_in('mlogin', array_keys($logins))
                         ->get('membres')->result_array() as $row) {
                $names[$row['mlogin']] = trim($row['mprenom'] . ' ' . $row['mnom']);
            }
        }
        return $names;
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
