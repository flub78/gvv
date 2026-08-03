<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 151: rattache le handler post-soumission au formulaire
 * briefing-passager-ulm (Lot 6, étape 6.6)
 *
 * Le formulaire public briefing-passager-ulm doit rediriger vers
 * vols_decouverte/page après soumission (BriefingPassagerUlmHandler, cf.
 * migration 141). Cette association n'était jusqu'ici faite que
 * manuellement via l'admin (forms_admin) et n'existait sur aucun
 * environnement fraîchement installé. Cette migration la fixe de façon
 * reproductible.
 */
class Migration_Briefing_passager_ulm_handler_class extends CI_Migration {

    private $slug = 'briefing-passager-ulm';
    private $handler_class = 'BriefingPassagerUlmHandler';

    private function column_exists($table, $column)
    {
        $t = $this->db->escape_str($table);
        $c = $this->db->escape_str($column);

        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND COLUMN_NAME = '$c'"
        )->row_array();

        return isset($row['cnt']) && (int) $row['cnt'] > 0;
    }

    public function up() {
        if (!$this->column_exists('forms', 'handler_class')) {
            log_message('info', 'Migration 151: forms.handler_class column missing, skipped');
            return TRUE;
        }

        $this->db->where('public_slug', $this->slug);
        $this->db->where('handler_class IS NULL', NULL, FALSE);
        $ok = $this->db->update('forms', array('handler_class' => $this->handler_class));

        log_message('info', 'Migration 151: forms.handler_class set for ' . $this->slug);
        return $ok;
    }

    public function down() {
        if (!$this->column_exists('forms', 'handler_class')) {
            return TRUE;
        }

        $this->db->where('public_slug', $this->slug);
        $this->db->where('handler_class', $this->handler_class);
        $ok = $this->db->update('forms', array('handler_class' => NULL));

        log_message('info', 'Migration 151: forms.handler_class reset for ' . $this->slug);
        return $ok;
    }
}
