<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Agrégateur des alarmes
 *
 * Point d'entrée unique pour obtenir les occurrences (AlarmItem) des alarmes
 * du catalogue, quelle que soit leur source. Lot 1 : documents archivés
 * (Document_alarm_provider). Les alarmes calculées s'ajouteront au Lot 2.
 *
 * @package libraries
 * @see application/libraries/Document_alarm_provider.php
 * @see doc/design_notes/gestion_alarmes_design.md
 */
class Alarm_aggregator {

    /** @var CI_Controller */
    private $CI;

    public function __construct() {
        $this->CI = &get_instance();
        $this->CI->load->model('alarm_definitions_model');
        $this->CI->load->library('Document_alarm_provider');
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
        return $this->CI->document_alarm_provider->collect($definitions);
    }
}
