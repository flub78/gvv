<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Mes alarmes : alarmes actives et à venir auxquelles le membre est abonné
 *
 * Réservée aux membres ayant au moins un abonnement effectif ; les autres
 * sont renvoyés vers la page de configuration avec un message.
 *
 * @package controllers
 * @see doc/prds/gestion_alarmes_generiques_prd.md (exigence 4)
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
     * Liste ; filtres ?alarme=<id> et ?section=<id>
     */
    public function index()
    {
        $login = $this->dx_auth->get_username();
        $subscriptions = $this->alarm_subscriptions_model->effective_for($login);
        if (!$subscriptions) {
            $this->session->set_flashdata('error', $this->lang->line('alarm_my_alarms_no_subscription'));
            redirect('alarmes_config');
            return;
        }

        $definitions = array();
        foreach ($this->alarm_definitions_model->get_all(true) as $def) {
            if (isset($subscriptions[$def['id']])) {
                $definitions[$def['id']] = $def;
            }
        }
        $items_by_def = array();
        foreach ($this->alarm_aggregator->collect(array_values($definitions)) as $item) {
            $items_by_def[$item['definition_id']][] = $item;
        }
        $occurrences = $this->notification_manager->occurrences_for($login, $subscriptions, $definitions, $items_by_def);

        $filter_def = (int) $this->input->get('alarme');
        $filter_section = (int) $this->input->get('section');
        $sections = array();
        foreach ($this->db->select('id, nom')->order_by('nom')->get('sections')->result_array() as $row) {
            $sections[(int) $row['id']] = $row['nom'];
        }
        $rows = array();
        foreach ($occurrences as $item) {
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

        load_last_view('mes_alarmes/index', array(
            'rows' => $rows,
            'definitions' => $definitions,
            'sections' => $sections,
            'filter_def' => $filter_def,
            'filter_section' => $filter_section,
        ));
    }
}
