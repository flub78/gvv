<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 178 : alarmes, Lot 2 (alarmes de conformité calculées)
 *
 * Ajoute au catalogue les alarmes calculées « vols sans cotisation » et
 * « vols sans licence » (source computed, règle évaluée par
 * Computed_alarm_provider). Aucun abonnement n'est créé.
 *
 * @see doc/prds/gestion_alarmes_generiques_prd.md
 * @see doc/design_notes/gestion_alarmes_design.md
 * @see doc/plans/gestion_alarmes_plan.md
 */
class Migration_Alarmes_lot2 extends CI_Migration {

    private static $alarms = array(
        'computed_vols_sans_cotisation' => array('rule' => 'vols_sans_cotisation', 'label' => 'Vols sans cotisation'),
        'computed_vols_sans_licence'    => array('rule' => 'vols_sans_licence', 'label' => 'Vols sans licence'),
    );

    public function up() {
        $now = date('Y-m-d H:i:s');
        foreach (self::$alarms as $code => $alarm) {
            if ($this->db->where('code', $code)->count_all_results('alarm_definitions')) {
                continue;
            }
            $ok = $this->db->insert('alarm_definitions', array(
                'code'       => $code,
                'label'      => $alarm['label'],
                'source'     => 'computed',
                'rule'       => $alarm['rule'],
                'lead_days'  => 0,
                'active'     => 1,
                'created_at' => $now,
                'created_by' => 'migration',
            ));
            if (!$ok) {
                log_message('error', 'Migration 178: ' . $this->db->_error_message());
                return FALSE;
            }
        }
        log_message('info', 'Migration 178: alarmes lot 2 appliquée');
        return TRUE;
    }

    public function down() {
        // Abonnements et retraits liés supprimés en cascade
        $this->db->where_in('code', array_keys(self::$alarms))->delete('alarm_definitions');
        log_message('info', 'Migration 178: alarmes lot 2 retirée');
        return TRUE;
    }
}
