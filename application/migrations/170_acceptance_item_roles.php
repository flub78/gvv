<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 170: acceptance_item_roles (Lot 4)
 *
 * Ciblage d'une acceptation par role(s), avec section optionnelle (NULL =
 * toutes sections), sur le modele du selecteur role x section deja utilise
 * par les listes d'email (email_list_roles / _criteria_tab.php).
 *
 * Remplace, pour la creation/edition, le champ texte libre
 * acceptance_items.target_roles (roles en clair, sans notion de section).
 * Cette colonne est conservee pour compatibilite avec d'eventuelles donnees
 * existantes mais n'est plus alimentee par le formulaire admin.
 *
 * @see doc/plans/acceptations_reconnaissances_plan.md
 * @see doc/prds/approbation_de_documents_prd.md
 */
class Migration_Acceptance_item_roles extends CI_Migration {

    public function up() {
        if ($this->db->table_exists('acceptance_item_roles')) {
            log_message('info', 'Migration 170: acceptance_item_roles already exists, skipping');
            return TRUE;
        }

        $ok = (bool) $this->db->query(
            "CREATE TABLE `acceptance_item_roles` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `item_id` INT(11) UNSIGNED NOT NULL COMMENT 'FK acceptance_items',
                `types_roles_id` INT(11) NOT NULL COMMENT 'FK types_roles',
                `section_id` INT(11) NULL COMMENT 'FK sections, NULL = toutes sections',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NULL,
                `created_by` VARCHAR(25) NULL,
                `updated_by` VARCHAR(25) NULL,
                PRIMARY KEY (`id`),
                KEY `idx_item_id` (`item_id`),
                KEY `idx_types_roles_id` (`types_roles_id`),
                KEY `idx_section_id` (`section_id`),
                CONSTRAINT `fk_acceptance_item_roles_item` FOREIGN KEY (`item_id`) REFERENCES `acceptance_items` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_acceptance_item_roles_role` FOREIGN KEY (`types_roles_id`) REFERENCES `types_roles` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_acceptance_item_roles_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Ciblage role x section des elements a accepter'"
        );

        log_message('info', 'Migration 170: acceptance_item_roles table created');
        return $ok;
    }

    public function down() {
        $ok = TRUE;
        if ($this->db->table_exists('acceptance_item_roles')) {
            $ok = (bool) $this->db->query("DROP TABLE `acceptance_item_roles`");
        }
        log_message('info', 'Migration 170: acceptance_item_roles table dropped');
        return $ok;
    }
}
