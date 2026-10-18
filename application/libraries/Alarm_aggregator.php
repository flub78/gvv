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
     * Occurrences des alarmes données, éventuellement restreintes à une section
     *
     * @param array|null $definitions Lignes du catalogue ; null = toutes les alarmes actives
     * @param int|null $section_id Section ; les occurrences sans section sont toujours retenues
     * @return array AlarmItem[]
     */
    public function collect($definitions = null, $section_id = null) {
        if ($definitions === null) {
            $definitions = $this->CI->alarm_definitions_model->get_all(true);
        }
        $items = $this->CI->document_alarm_provider->collect($definitions);

        if ($section_id) {
            $items = array_values(array_filter($items, function ($item) use ($section_id) {
                return empty($item['section_ids']) || in_array((int) $section_id, $item['section_ids'], true);
            }));
        }
        return $items;
    }
}
