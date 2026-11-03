<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 184 : textes longs et champs d'audit dans la table configuration
 *
 * La page Configuration y enregistre des textes (règles de confidentialité,
 * conditions d'utilisation) plus longs que 255 caractères, et date la dernière
 * modification affichée sur les pages publiques.
 *
 * @see doc/prds/configuration_unifiee_prd.md
 * @see doc/design_notes/configuration_unifiee_design.md
 */
class Migration_Configuration_textes extends CI_Migration {

    private $audit = array(
        'created_at' => "DATETIME NULL",
        'updated_at' => "DATETIME NULL",
        'created_by' => "VARCHAR(50) NULL",
        'updated_by' => "VARCHAR(50) NULL",
    );

    public function up() {
        if (!$this->db->query("ALTER TABLE `configuration` MODIFY `valeur` TEXT NULL")) {
            log_message('error', 'Migration 184: valeur en TEXT échoué — ' . $this->db->_error_message());
            return FALSE;
        }
        foreach ($this->audit as $column => $type) {
            if ($this->db->field_exists($column, 'configuration')) {
                continue;
            }
            if (!$this->db->query("ALTER TABLE `configuration` ADD `$column` $type")) {
                log_message('error', "Migration 184: ajout de $column échoué — " . $this->db->_error_message());
                return FALSE;
            }
        }
        return TRUE;
    }

    public function down() {
        foreach (array_keys($this->audit) as $column) {
            if ($this->db->field_exists($column, 'configuration')) {
                $this->db->query("ALTER TABLE `configuration` DROP `$column`");
            }
        }
        // Les textes plus longs que 255 caractères seraient tronqués : ils sont supprimés.
        $this->db->query("DELETE FROM `configuration` WHERE CHAR_LENGTH(`valeur`) > 255");
        return (bool) $this->db->query("ALTER TABLE `configuration` MODIFY `valeur` VARCHAR(255) NULL");
    }
}
