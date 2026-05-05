<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 107 — Email membre unique, adresse optionnelle
 *
 * - Rend membres.madresse nullable (champ non obligatoire)
 * - Normalise les emails vides ('') en NULL
 * - Ajoute un index UNIQUE sur memail (MySQL autorise plusieurs NULLs dans un index unique)
 */
class Migration_Membre_email_unique_adresse_nullable extends CI_Migration {

    public function up() {
        $this->db->query(
            "ALTER TABLE membres MODIFY COLUMN madresse VARCHAR(80) NULL DEFAULT NULL COMMENT 'Adresse'"
        );
        $this->db->query("UPDATE membres SET memail = NULL WHERE memail = ''");
        $this->db->query(
            "ALTER TABLE membres ADD UNIQUE INDEX idx_membres_memail (memail)"
        );
    }

    public function down() {
        $this->db->query("ALTER TABLE membres DROP INDEX idx_membres_memail");
        $this->db->query(
            "ALTER TABLE membres MODIFY COLUMN madresse VARCHAR(80) NOT NULL COMMENT 'Adresse'"
        );
    }
}
