<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 179 : alarmes, Lot 3 (expérience récente et compte débiteur)
 *
 * - rôle « emport_passager », attribuable par section, attribué en section
 *   planeur (acronyme PLA) aux membres ayant l'événement « Emport passager » ;
 * - alarmes calculées computed_emport_passager, computed_inactivite,
 *   computed_remorquages (délai d'anticipation 15 jours) et
 *   computed_compte_debiteur. Aucun abonnement
 *   n'est créé ;
 * - sections.licence_type : type de licence (licences.type) exigé pour voler
 *   dans la section, vide = aucune. Initialisé une fois d'après l'acronyme
 *   des sections existantes (PLA 1, AVI 2, ULM 3), comme la correspondance
 *   jusque-là écrite dans le code ; réglable ensuite dans la fiche section.
 *
 * down() supprime les alarmes, le rôle et ses attributions, et la colonne.
 *
 * @see doc/prds/gestion_alarmes_generiques_prd.md
 * @see doc/design_notes/gestion_alarmes_design.md
 * @see doc/plans/gestion_alarmes_plan.md
 */
class Migration_Alarmes_lot3 extends CI_Migration {

    const ROLE = 'emport_passager';

    private static $alarms = array(
        'computed_emport_passager' => array('rule' => 'emport_passager', 'label' => 'Emport passager', 'lead_days' => 15),
        'computed_inactivite'      => array('rule' => 'inactivite', 'label' => '120 jours sans voler', 'lead_days' => 15),
        'computed_remorquages'     => array('rule' => 'remorquages', 'label' => 'Remorquage (10 remorqués en 12 mois)', 'lead_days' => 15),
        'computed_compte_debiteur' => array('rule' => 'compte_debiteur', 'label' => 'Compte débiteur', 'lead_days' => 0),
    );

    public function up() {
        $now = date('Y-m-d H:i:s');

        if (!$this->db->field_exists('licence_type', 'sections')) {
            $ok = $this->db->query("ALTER TABLE `sections` ADD COLUMN `licence_type` TINYINT(2) NULL
                COMMENT 'Type de licence (licences.type) exigé pour voler dans la section, NULL = aucune'");
            if (!$ok) {
                return $this->_fail();
            }
            foreach (array('PLA' => 1, 'AVI' => 2, 'ULM' => 3) as $acronyme => $type) {
                $this->db->where('acronyme', $acronyme)->update('sections', array('licence_type' => $type));
            }
        }

        if (!$this->db->where('nom', self::ROLE)->count_all_results('types_roles')) {
            $ok = $this->db->insert('types_roles', array(
                'nom'             => self::ROLE,
                'description'     => 'Emport de passagers',
                'scope'           => 'section',
                'is_system_role'  => 1,
                'display_order'   => 98,
                'translation_key' => 'role_emport_passager',
            ));
            if (!$ok) {
                return $this->_fail();
            }
            $this->_grant_from_events($now);
        }

        foreach (self::$alarms as $code => $alarm) {
            if ($this->db->where('code', $code)->count_all_results('alarm_definitions')) {
                continue;
            }
            $ok = $this->db->insert('alarm_definitions', array(
                'code'       => $code,
                'label'      => $alarm['label'],
                'source'     => 'computed',
                'rule'       => $alarm['rule'],
                'lead_days'  => $alarm['lead_days'],
                'active'     => 1,
                'created_at' => $now,
                'created_by' => 'migration',
            ));
            if (!$ok) {
                return $this->_fail();
            }
        }
        log_message('info', 'Migration 179: alarmes lot 3 appliquée');
        return TRUE;
    }

    public function down() {
        // Abonnements et retraits liés supprimés en cascade
        $this->db->where_in('code', array_keys(self::$alarms))->delete('alarm_definitions');
        $role = $this->db->select('id')->where('nom', self::ROLE)->get('types_roles')->row_array();
        if ($role) {
            $this->db->where('types_roles_id', $role['id'])->delete('user_roles_per_section');
            $this->db->where('id', $role['id'])->delete('types_roles');
        }
        if ($this->db->field_exists('licence_type', 'sections')) {
            $this->db->query("ALTER TABLE `sections` DROP COLUMN `licence_type`");
        }
        log_message('info', 'Migration 179: alarmes lot 3 retirée');
        return TRUE;
    }

    /** Rôle accordé en section planeur aux membres ayant l'événement « Emport passager » */
    private function _grant_from_events($now) {
        $role = $this->db->select('id')->where('nom', self::ROLE)->get('types_roles')->row_array();
        $section = $this->db->select('id')->where('acronyme', 'PLA')->order_by('id')->get('sections')->row_array();
        if (!$role || !$section) {
            return;
        }
        $users = $this->db->query(
            "SELECT DISTINCT u.id FROM events e
             JOIN events_types et ON et.id = e.etype
             JOIN users u ON u.username = e.emlogin
             WHERE et.name = 'Emport passager'"
        )->result_array();
        foreach ($users as $user) {
            $this->db->insert('user_roles_per_section', array(
                'user_id'        => $user['id'],
                'types_roles_id' => $role['id'],
                'section_id'     => $section['id'],
                'granted_at'     => $now,
                'notes'          => 'Migration 179 : événement Emport passager',
            ));
        }
    }

    private function _fail() {
        log_message('error', 'Migration 179: ' . $this->db->_error_message());
        return FALSE;
    }
}
