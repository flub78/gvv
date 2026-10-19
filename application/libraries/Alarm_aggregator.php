<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Agrégateur des alarmes
 *
 * Point d'entrée unique pour obtenir les occurrences (AlarmItem) des alarmes
 * du catalogue, quelle que soit leur source : documents archivés
 * (Document_alarm_provider), alarmes calculées (Computed_alarm_provider).
 *
 * Une occurrence qui concerne un pilote est supprimée si le pilote est
 * extérieur (membres.ext = 1) ; sauf si elle porte 'any_pilot' (compte
 * débiteur : un pilote parti doit encore être relancé), elle n'est retenue
 * que pour les sections où il est actif (rôle user non révoqué) et supprimée
 * s'il n'y en a aucune.
 *
 * @package libraries
 * @see application/libraries/Document_alarm_provider.php
 * @see application/libraries/Computed_alarm_provider.php
 * @see doc/design_notes/gestion_alarmes_design.md
 */
class Alarm_aggregator {

    /** @var CI_Controller */
    private $CI;

    public function __construct() {
        $this->CI = &get_instance();
        $this->CI->load->model('alarm_definitions_model');
        $this->CI->load->library('Document_alarm_provider');
        $this->CI->load->library('Computed_alarm_provider');
        $this->CI->load->model('alarm_subscriptions_model');
    }

    /**
     * Occurrences des alarmes données
     *
     * @param array|null $definitions Lignes du catalogue ; null = toutes les alarmes actives
     * @return array AlarmItem[]
     */
    public function collect($definitions = null) {
        if ($definitions === null) {
            $definitions = $this->CI->alarm_definitions_model->get_all(true);
        }
        return $this->active_pilots_only(array_merge($this->CI->document_alarm_provider->collect($definitions),
            $this->CI->computed_alarm_provider->collect($definitions)));
    }

    /**
     * Retire les occurrences des pilotes extérieurs et restreint celles des
     * autres pilotes aux sections où ils sont actifs
     *
     * @param array $items AlarmItem[]
     * @return array AlarmItem[]
     */
    private function active_pilots_only($items) {
        $external = array();
        foreach ($this->CI->db->select('mlogin')->where('ext', 1)->get('membres')->result_array() as $row) {
            $external[$row['mlogin']] = true;
        }
        $roles = $this->CI->alarm_subscriptions_model->all_member_roles();

        $kept = array();
        foreach ($items as $item) {
            $login = $item['pilot_login'];
            if (empty($login)) {
                $kept[] = $item; // machine, section, club
                continue;
            }
            if (isset($external[$login])) {
                continue;
            }
            if (!empty($item['any_pilot'])) {
                $kept[] = $item;
                continue;
            }
            if (empty($roles[$login]['user'])) {
                continue;
            }
            if ($item['section_ids']) {
                $active = array_values(array_filter($item['section_ids'], function ($section_id) use ($roles, $login) {
                    return isset($roles[$login]['user'][(int) $section_id]);
                }));
                if (!$active) {
                    continue;
                }
                $item['section_ids'] = $active;
            }
            $kept[] = $item;
        }
        return $kept;
    }
}
