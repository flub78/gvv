<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Administration des alarmes (club-admin)
 *
 * - catalogue : activation et délai d'anticipation de chaque alarme ;
 * - abonnements de catégories de membres (rôle, section facultative, obligatoire) ;
 * - alarmes actives ou à venir que personne ne reçoit.
 *
 * La configuration des alarmes d'un membre précis se fait sur la page
 * Mes alarmes (mes_alarmes), avec le sélecteur de membre.
 *
 * @package controllers
 * @see doc/prds/gestion_alarmes_generiques_prd.md (exigences 1, 5 et 16)
 */
class Alarmes_admin extends MY_Controller
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
        if (!$this->user_has_role('club-admin')) {
            show_error($this->lang->line('gvv_error_access_denied') ?: 'Accès refusé.', 403);
        }

        $this->load->model('alarm_definitions_model');
        $this->load->model('alarm_subscriptions_model');
        $this->load->model('alarm_member_settings_model');
        $this->load->library('Notification_manager');
        $this->load->helper('validation');
        $this->lang->load('alarmes');
        $this->lang->load('tableaux_de_bord');

        $this->load->vars([
            'nav_back_url'   => $this->session->userdata('nav_from_url')   ?: 'welcome/section/admin_club',
            'nav_back_label' => $this->session->userdata('nav_from_label') ?: $this->lang->line('db_section_admin_club'),
        ]);
    }

    public function index()
    {
        $this->alarm_definitions_model->sync_document_types();
        $definitions = $this->alarm_definitions_model->get_all(false);

        $roles = array();
        foreach ($this->db->order_by('display_order')->get('types_roles')->result_array() as $role) {
            $roles[$role['id']] = ($role['translation_key'] && $this->lang->line($role['translation_key']))
                ? $this->lang->line($role['translation_key']) : $role['nom'];
        }
        $sections = array();
        foreach ($this->db->select('id, nom')->order_by('nom')->get('sections')->result_array() as $row) {
            $sections[(int) $row['id']] = $row['nom'];
        }

        load_last_view('alarmes_admin/index', array(
            'definitions' => $definitions,
            'categories' => $this->alarm_subscriptions_model->get_categories(),
            'uncovered' => $this->_uncovered($definitions),
            'roles' => $roles,
            'sections' => $sections,
        ));
    }

    /**
     * Catalogue : activation et délai d'anticipation (POST)
     */
    public function save_catalogue()
    {
        $active = array_flip(array_map('intval', (array) $this->input->post('active')));
        $lead_days = (array) $this->input->post('lead_days');
        foreach ($this->alarm_definitions_model->get_all(false) as $def) {
            $lead = isset($lead_days[$def['id']]) ? (int) $lead_days[$def['id']] : (int) $def['lead_days'];
            $this->alarm_definitions_model->configure($def['id'], isset($active[(int) $def['id']]), $lead);
        }
        $this->session->set_flashdata('success', $this->lang->line('alarm_admin_catalogue_saved'));
        redirect('alarmes_admin');
    }

    /**
     * Abonnement d'une catégorie de membres (POST)
     */
    public function add_category()
    {
        $result = $this->alarm_subscriptions_model->subscribe_category(
            (int) $this->input->post('alarm_definition_id'),
            (int) $this->input->post('role_id'),
            (int) $this->input->post('section_id') ?: null,
            (bool) $this->input->post('mandatory')
        );
        if ($result['ok']) {
            $this->session->set_flashdata('success', $this->lang->line('alarm_admin_category_added'));
        } else {
            $this->session->set_flashdata('error', $result['error']);
        }
        redirect('alarmes_admin');
    }

    /**
     * Suppression d'un abonnement de catégorie (POST)
     */
    public function delete_category()
    {
        $id = (int) $this->input->post('subscription_id');
        $row = $this->db->where('id', $id)->where('subscriber_login IS NULL', null, false)
            ->get('alarm_subscriptions')->row_array();
        if (!$row) {
            $this->session->set_flashdata('error', $this->lang->line('alarm_error_unknown'));
        } else {
            $this->alarm_subscriptions_model->unsubscribe_by_id($id);
            $this->session->set_flashdata('success', $this->lang->line('alarm_admin_category_deleted'));
        }
        redirect('alarmes_admin');
    }

    /**
     * Occurrences actives ou à venir que personne ne reçoit : ni le membre
     * concerné, ni un abonné, avec un canal choisi (email ou message du jour)
     *
     * @param array $definitions
     * @return array AlarmItem[] (une ligne par occurrence)
     */
    private function _uncovered($definitions)
    {
        $active_defs = array();
        foreach ($definitions as $def) {
            if ($def['active']) {
                $active_defs[$def['id']] = $def;
            }
        }
        if (!$active_defs) {
            return array();
        }
        $covered = array();
        $items_by_def = array();
        $items = $this->alarm_aggregator->collect(array_values($active_defs));
        foreach ($items as $item) {
            $items_by_def[$item['definition_id']][] = $item;
        }
        $effective = $this->alarm_subscriptions_model->effective_all();
        $active = array_flip($this->alarm_subscriptions_model->active_logins());
        foreach ($this->alarm_member_settings_model->get_all_by_login() as $login => $settings) {
            if (!isset($active[$login]) || (empty($settings['channel_email']) && empty($settings['channel_motd']))) {
                continue;
            }
            $cells = isset($effective[$login]) ? $effective[$login] : array();
            foreach ($this->notification_manager->occurrences_for($login, $cells, $active_defs, $items_by_def) as $key => $item) {
                $covered[$key] = true;
            }
        }
        $uncovered = array();
        foreach ($items as $item) {
            if (!isset($covered[$item['key']]) && !isset($uncovered[$item['key']])) {
                $item['definition_label'] = $active_defs[$item['definition_id']]['display_label'];
                $uncovered[$item['key']] = $item;
            }
        }
        return array_values($uncovered);
    }
}
