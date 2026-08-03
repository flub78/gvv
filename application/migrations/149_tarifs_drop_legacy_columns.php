<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Migration 149: Nettoyage final — suppression des colonnes produit legacy de `tarifs`
 *
 * Étape 12 (point de non-retour) de doc/plans/refactoring_produits_tarifs_plan.md.
 *
 * Supprime les colonnes d'identité produit dupliquées sur `tarifs` depuis la
 * création de `produits` (migration 146) : `reference`, `description`, `compte`,
 * `club`, `is_cotisation`, `nb_personnes_max`, `public`, `type_ticket`,
 * `saisie_par`. Elles ne sont plus écrites par `Tarifs_model` depuis l'étape 7
 * et plus lues nulle part dans le code applicatif (façade `Tarifs_model`,
 * `Produits_model`, `achats_model`, `reservations.php`, `welcome.php`,
 * `vols_decouverte.php`, `paiements_en_ligne.php` — tous basculés en jointure
 * `produits` aux étapes 7 et 10, vérifié par grep de contrôle avant cette
 * migration). `tarifs` ne porte plus après cette migration que l'historique de
 * prix : `id`, `produit_id`, `date`, `date_fin`, `prix`, `nb_tickets` + audit.
 *
 * `tarifs.id` reste inchangé (jamais renuméroté, cf. compat ACES point B —
 * cette migration ne touche qu'aux colonnes, pas aux lignes ni aux clés).
 *
 * @see doc/design_notes/refactoring_produits_tarifs.md
 */
class Migration_Tarifs_drop_legacy_columns extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 149;
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
            "ALTER TABLE `tarifs`
                DROP COLUMN `reference`,
                DROP COLUMN `description`,
                DROP COLUMN `compte`,
                DROP COLUMN `club`,
                DROP COLUMN `is_cotisation`,
                DROP COLUMN `nb_personnes_max`,
                DROP COLUMN `public`,
                DROP COLUMN `type_ticket`,
                DROP COLUMN `saisie_par`",
        );

        $errors += $this->run_queries($sqls);

        if ($errors > 0) {
            gvv_error("Migration " . $this->migration_number . ": $errors error(s) while dropping legacy columns");
            return false;
        }

        gvv_info("Migration database up to " . $this->migration_number . " — colonnes produit legacy supprimées de tarifs");
        return true;
    }

    public function down() {
        $errors = 0;
        $sqls = array(
            "ALTER TABLE `tarifs`
                ADD COLUMN `reference` VARCHAR(32) NULL COMMENT 'Legacy — remplacé par produits.reference, plus écrit depuis Tarifs_model' AFTER `produit_id`,
                ADD COLUMN `description` VARCHAR(80) NULL COMMENT 'Description' AFTER `date_fin`,
                ADD COLUMN `nb_personnes_max` TINYINT(3) UNSIGNED NULL DEFAULT 1 COMMENT 'Legacy — remplacé par produits.nb_personnes_max, plus écrit depuis Tarifs_model' AFTER `prix`,
                ADD COLUMN `compte` INT(11) NULL DEFAULT 0 COMMENT 'Legacy — remplacé par produits.compte, plus écrit depuis Tarifs_model' AFTER `nb_personnes_max`,
                ADD COLUMN `saisie_par` VARCHAR(25) NULL COMMENT 'Legacy — remplacé par created_by, plus écrit depuis Tarifs_model' AFTER `compte`,
                ADD COLUMN `club` TINYINT(1) NULL DEFAULT 0 COMMENT 'Gestion multi-club' AFTER `saisie_par`,
                ADD COLUMN `type_ticket` INT(11) NULL COMMENT 'Type de ticket à créditer' AFTER `nb_tickets`,
                ADD COLUMN `is_cotisation` TINYINT(1) NULL DEFAULT 0 COMMENT 'Legacy — remplacé par produits.is_cotisation, plus écrit depuis Tarifs_model' AFTER `type_ticket`,
                ADD COLUMN `public` TINYINT(4) NULL DEFAULT 1 COMMENT 'Permet le filtrage sur l''impression' AFTER `is_cotisation`",
        );
        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1)
            . " — colonnes recréées vides (aucune donnée restaurée, voir dump de sauvegarde pré-migration), errors=$errors");
        return !$errors;
    }
}
