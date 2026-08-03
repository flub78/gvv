<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Migration 146: Création de la table `produits` (refactoring tarifs -> produits + tarifs)
 *
 * Étape 4 de doc/plans/refactoring_produits_tarifs_plan.md. Sépare l'identité du
 * produit (aujourd'hui dupliquée sur chaque ligne de `tarifs`) dans une table
 * dédiée. `tarifs` n'est pas modifiée par cette migration (étape 5 : ajout de
 * `tarifs.produit_id`).
 *
 * Peuplement : un produit par groupe (reference, club) de `tarifs`, valeurs des
 * colonnes produit prises sur la ligne à la date la plus récente du groupe
 * (départage par id le plus grand en cas d'égalité de date exacte) — règle
 * validée à l'étape 2 du plan (aucune divergence anormale constatée), cf.
 * doc/design_notes/refactoring_produits_tarifs_audit.md. created_at/created_by
 * proviennent de la ligne la plus ancienne du groupe (départage par id le plus
 * petit) ; updated_at/updated_by de la ligne la plus récente.
 *
 * @see doc/design_notes/refactoring_produits_tarifs.md
 */
class Migration_Create_produits_table extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 146;
    }

    private function run_queries($sqls = array()) {
        $errors = 0;
        foreach ($sqls as $sql) {
            gvv_info("Migration sql: " . $sql);
            if (!$this->db->query($sql)) {
                $mysql_msg = $this->db->_error_message();
                $mysql_error = $this->db->_error_number();
                gvv_error("Migration error: code=$mysql_error, msg=$mysql_msg");
                $errors += 1;
            }
        }
        return $errors;
    }

    public function up() {
        $errors = 0;

        $sqls = array(
            "CREATE TABLE `produits` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `reference` VARCHAR(32) NOT NULL COMMENT 'Référence du produit',
                `description` VARCHAR(80) DEFAULT NULL COMMENT 'Description',
                `compte` INT(11) NOT NULL DEFAULT 0 COMMENT 'Numéro de compte associé',
                `club` TINYINT(1) DEFAULT 0 COMMENT 'Gestion multi-club',
                `is_cotisation` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Produit de cotisation',
                `nb_personnes_max` TINYINT(3) UNSIGNED NOT NULL DEFAULT 1,
                `public` TINYINT(4) DEFAULT 1 COMMENT 'Permet le filtrage sur l''impression',
                `type_ticket` INT(11) DEFAULT NULL COMMENT 'Type de ticket à créditer',
                `created_by` VARCHAR(25) DEFAULT NULL COMMENT 'User who created the row',
                `created_at` DATETIME DEFAULT NULL COMMENT 'Creation timestamp',
                `updated_by` VARCHAR(25) DEFAULT NULL COMMENT 'User who last updated the row',
                `updated_at` DATETIME DEFAULT NULL COMMENT 'Last update timestamp',
                PRIMARY KEY (`id`),
                UNIQUE KEY `reference_club` (`reference`, `club`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci",

            "INSERT INTO `produits`
                (reference, description, compte, club, is_cotisation, nb_personnes_max, public, type_ticket,
                 created_by, created_at, updated_by, updated_at)
             SELECT
                latest.reference, latest.description, latest.compte, latest.club,
                latest.is_cotisation, latest.nb_personnes_max, latest.public, latest.type_ticket,
                earliest.created_by, earliest.created_at,
                latest.updated_by, latest.updated_at
             FROM
                (SELECT t.* FROM tarifs t
                 WHERE NOT EXISTS (
                     SELECT 1 FROM tarifs t2
                     WHERE t2.reference = t.reference AND t2.club = t.club
                       AND (t2.date > t.date OR (t2.date = t.date AND t2.id > t.id))
                 )
                ) AS latest
             JOIN
                (SELECT t.* FROM tarifs t
                 WHERE NOT EXISTS (
                     SELECT 1 FROM tarifs t2
                     WHERE t2.reference = t.reference AND t2.club = t.club
                       AND (t2.date < t.date OR (t2.date = t.date AND t2.id < t.id))
                 )
                ) AS earliest
             ON latest.reference = earliest.reference AND latest.club = earliest.club",
        );

        $errors += $this->run_queries($sqls);

        if ($errors > 0) {
            gvv_error("Migration " . $this->migration_number . ": $errors error(s) while creating/populating produits");
            return false;
        }

        // Garde-fou : un produit par couple (reference, club) distinct de tarifs.
        $expected = $this->db->query(
            "SELECT COUNT(*) AS n FROM (SELECT 1 FROM tarifs GROUP BY reference, club) g"
        )->row_array();
        $actual = $this->db->query("SELECT COUNT(*) AS n FROM produits")->row_array();

        if ((int) $expected['n'] !== (int) $actual['n']) {
            $msg = "Migration " . $this->migration_number . ": incohérence de peuplement, "
                . "attendu " . $expected['n'] . " produits (COUNT DISTINCT reference,club de tarifs), "
                . "obtenu " . $actual['n'];
            gvv_error($msg);
            throw new Exception($msg);
        }

        gvv_info("Migration database up to " . $this->migration_number . ", " . $actual['n'] . " produits créés");
        return true;
    }

    public function down() {
        $errors = 0;
        $sqls = array(
            "DROP TABLE IF EXISTS `produits`",
        );
        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}
