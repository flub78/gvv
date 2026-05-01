<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 105 — Ajout du numéro de membre (mnumero)
 *
 * Ajoute un identifiant numérique séquentiel aux membres pour l'impression
 * des cartes de membre. Distinct du login (mlogin) qui reste la clé primaire.
 */
class Migration_Mnumero_membre extends CI_Migration {

    public function up() {
        $this->db->query(
            "ALTER TABLE membres ADD COLUMN mnumero INT UNSIGNED NULL DEFAULT NULL COMMENT 'Numéro de membre pour impression carte'"
        );
    }

    public function down() {
        $this->db->query("ALTER TABLE membres DROP COLUMN mnumero");
    }
}
