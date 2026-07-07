<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 139: soumission par téléchargement (formulaire scanné)
 *
 * Ajoute la possibilité, pour un formulaire donné, d'accepter une réponse
 * sous forme de fichier téléchargé (scan/photo du formulaire imprimé) en
 * alternative au remplissage en ligne. Un seul fichier par réponse, stocké
 * dans form_submission_files avec field_id NULL et widget_name = 'uploaded_response'
 * (mécanisme déjà en place depuis la migration 137).
 */
class Migration_Forms_upload_response extends CI_Migration {

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

    private function add_column_if_missing($table, $column, $definition)
    {
        if (!$this->column_exists($table, $column)) {
            $t = $this->db->escape_str($table);
            $c = $this->db->escape_str($column);
            return (bool) $this->db->query("ALTER TABLE `$t` ADD COLUMN `$c` $definition");
        }
        return TRUE;
    }

    private function drop_column_if_exists($table, $column)
    {
        if ($this->column_exists($table, $column)) {
            $t = $this->db->escape_str($table);
            $c = $this->db->escape_str($column);
            return (bool) $this->db->query("ALTER TABLE `$t` DROP COLUMN `$c`");
        }
        return TRUE;
    }

    public function up() {
        $ok = TRUE;
        $ok = $this->add_column_if_missing('forms', 'allow_upload_response',
            "TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Autorise le telechargement d une reponse scannee a la place du remplissage en ligne'") && $ok;
        $ok = $this->add_column_if_missing('form_submissions', 'submission_method',
            "ENUM('online','upload') NOT NULL DEFAULT 'online'") && $ok;
        $ok = $this->add_column_if_missing('form_submissions', 'upload_comment',
            "VARCHAR(255) NULL DEFAULT NULL COMMENT 'Commentaire saisi lors du telechargement, utilise comme identification de la reponse'") && $ok;

        log_message('info', 'Migration 139: forms_upload_response columns created');
        return $ok;
    }

    public function down() {
        $ok = TRUE;
        $ok = $this->drop_column_if_exists('form_submissions', 'upload_comment') && $ok;
        $ok = $this->drop_column_if_exists('form_submissions', 'submission_method') && $ok;
        $ok = $this->drop_column_if_exists('forms', 'allow_upload_response') && $ok;

        log_message('info', 'Migration 139: forms_upload_response columns dropped');
        return $ok;
    }
}
