<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 106 — Ajout du flag show_on_member_card aux sections
 *
 * Ajoute un flag permettant de contrôler quelles sections apparaissent
 * dans le champ "activités" des cartes de membre.
 * Par défaut, toutes les sections sont affichées (1), sauf la section
 * par défaut "Club" (id=1) qui est masquée (0).
 */
class Migration_Show_on_member_card extends CI_Migration {

    public function up() {
        // Ajouter le champ show_on_member_card avec valeur par défaut 1
        $this->db->query(
            "ALTER TABLE sections ADD COLUMN show_on_member_card TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Afficher cette section sur les cartes de membre'"
        );

        // Désactiver l'affichage pour la section par défaut (id=1, "Club")
        $this->db->query(
            "UPDATE sections SET show_on_member_card = 0 WHERE id = 1"
        );
    }

    public function down() {
        $this->db->query("ALTER TABLE sections DROP COLUMN show_on_member_card");
    }
}
