<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Fournisseur des alarmes calculées
 *
 * Traduit les règles de conformité en occurrences d'alarme (AlarmItem, cf.
 * Document_alarm_provider) pour les alarmes du catalogue de source
 * « computed ». Règles (alarm_definitions.rule), sur les vols de l'année
 * civile en cours, pilotes extérieurs exclus :
 * - vols_sans_cotisation : vols sans cotisation de l'année ;
 * - vols_sans_licence : vols d'une activité sans la licence de l'activité
 *   pour l'année (planeur, avion, ULM).
 *
 * Une occurrence est toujours active, sans échéance ; ses sections sont
 * celles des vols concernés. Elle disparaît dès que la cotisation ou la
 * licence est enregistrée.
 *
 * @package libraries
 * @see application/models/licences_model.php
 * @see doc/design_notes/gestion_alarmes_design.md
 */
class Computed_alarm_provider {

    const RULE_VOLS_SANS_COTISATION = 'vols_sans_cotisation';
    const RULE_VOLS_SANS_LICENCE = 'vols_sans_licence';

    /** @var CI_Controller */
    private $CI;

    /** @var int Année contrôlée, modifiable pour les tests */
    private $year;

    public function __construct() {
        $this->CI = &get_instance();
        $this->CI->load->model('alarm_definitions_model');
        $this->CI->load->model('licences_model');
        $this->CI->lang->load('alarmes');
        $this->year = (int) date('Y');
    }

    /**
     * Fixe l'année contrôlée (tests)
     *
     * @param int $year
     */
    public function set_year($year) {
        $this->year = (int) $year;
    }

    /**
     * Occurrences des alarmes calculées
     *
     * @param array $definitions Lignes du catalogue (seule la source computed est traitée)
     * @return array AlarmItem[]
     */
    public function collect($definitions) {
        $items = array();
        foreach ($definitions as $def) {
            if ($def['source'] !== Alarm_definitions_model::SOURCE_COMPUTED) {
                continue;
            }
            if ($def['rule'] === self::RULE_VOLS_SANS_COTISATION) {
                $items = array_merge($items, $this->vols_sans_cotisation($def));
            } elseif ($def['rule'] === self::RULE_VOLS_SANS_LICENCE) {
                $items = array_merge($items, $this->vols_sans_licence($def));
            }
        }
        return $items;
    }

    private function vols_sans_cotisation($def) {
        $items = array();
        foreach ($this->CI->licences_model->pilotes_vols_sans_cotisation($this->year) as $row) {
            $vols = (int) $row['planeur'] + (int) $row['avion'] + (int) $row['ulm'];
            $items[] = $this->item($def, $row, 'computed:' . self::RULE_VOLS_SANS_COTISATION . ':' . $row['pilote'] . ':' . $this->year,
                sprintf($this->CI->lang->line('alarm_item_vols_sans_cotisation'), $this->year), $vols,
                'licences/vols_sans_cotisation/' . $this->year, 'paiements_en_ligne/cotisation');
        }
        return $items;
    }

    private function vols_sans_licence($def) {
        $items = array();
        foreach ($this->CI->licences_model->pilotes_vols_sans_licence($this->year) as $row) {
            $activity = $this->CI->lang->line('alarm_activity_' . $row['categorie']);
            $items[] = $this->item($def,
                $row, 'computed:' . self::RULE_VOLS_SANS_LICENCE . ':' . $row['pilote'] . ':' . $row['licence_type'] . ':' . $this->year,
                sprintf($this->CI->lang->line('alarm_item_vols_sans_licence'), $activity, $this->year), (int) $row['vols'],
                'licences/per_year', ''); // la licence s'obtient hors de GVV : pas de lien pour le pilote
        }
        return $items;
    }

    private function item($def, $row, $key, $label, $vols, $link, $link_own) {
        $who = trim($row['prenom'] . ' ' . $row['nom']) ?: $row['pilote'];
        $sections = array();
        foreach (explode(',', (string) $row['sections']) as $section_id) {
            if ((int) $section_id > 0) {
                $sections[] = (int) $section_id;
            }
        }
        return array(
            'definition_id' => (int) $def['id'],
            'definition_code' => $def['code'],
            'key' => $key,
            'entity_type' => 'pilot',
            'entity_id' => $row['pilote'],
            'pilot_login' => $row['pilote'],
            'section_ids' => $sections,
            'label' => $label,
            'detail' => sprintf($this->CI->lang->line('alarm_item_vols_detail'), $who, $vols,
                date('d/m/Y', strtotime($row['dernier_vol']))),
            'state' => 'active',
            'due_date' => null,
            'link' => $link,
            'link_own' => $link_own,
        );
    }
}
