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
        return array_merge($this->CI->document_alarm_provider->collect($definitions),
            $this->CI->computed_alarm_provider->collect($definitions));
    }
}
