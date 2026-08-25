<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 173: machinesa.numero_identification
 *
 * Numéro d'identification (plaque) de la machine, distinct de l'immatriculation
 * (macimmat). Utilisé pour pré-remplir le formulaire public "attestation-de-test-en-vol".
 */
class Migration_Machinesa_numero_identification extends CI_Migration {

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
        $ok = TRUE;

        if (!$this->column_exists('machinesa', 'numero_identification')) {
            // The COMMENT is the actual form label: MetaData::field_long_name() prefers
            // the column COMMENT over the Gvvmetadata 'Name', matching the convention
            // already used by the other machinesa columns (macimmat -> "Immatriculation", etc.).
            $ok = (bool) $this->db->query(
                "ALTER TABLE `machinesa`
                 ADD COLUMN `numero_identification` VARCHAR(20) NULL DEFAULT NULL
                     COMMENT \"Numéro d'identification\" AFTER `macimmat`"
            ) && $ok;
        }

        log_message('info', 'Migration 173: machinesa.numero_identification created');
        return $ok;
    }

    public function down() {
        $ok = TRUE;

        if ($this->column_exists('machinesa', 'numero_identification')) {
            $ok = (bool) $this->db->query(
                "ALTER TABLE `machinesa` DROP COLUMN `numero_identification`"
            ) && $ok;
        }

        log_message('info', 'Migration 173: machinesa.numero_identification dropped');
        return $ok;
    }
}
