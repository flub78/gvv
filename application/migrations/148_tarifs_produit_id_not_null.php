<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Migration 148: Durcissement du schéma `tarifs` pour la façade `Tarifs_model` (étape 7)
 *
 * Étape 7 de doc/plans/refactoring_produits_tarifs_plan.md.
 *
 * 1. `produit_id` passe NOT NULL — reporté de la migration 147 : à ce moment-là,
 *    la façade `Tarifs_model::create()` ne le fournissait pas encore
 *    systématiquement (cf. docblock de 147_tarifs_add_produit_id.php). Elle le
 *    fait désormais toujours, et 100% des lignes existantes sont backfillées
 *    (vérifié par la migration 147).
 *
 * 2. `reference`, `saisie_par`, `compte`, `nb_personnes_max`, `is_cotisation`
 *    passent NULLABLE. Ce sont des colonnes produit historiques que
 *    `Tarifs_model::create()`/`update()` n'écrit plus à partir de cette étape
 *    (l'identité produit vit désormais sur `produits`) et qui ne sont plus
 *    rendues sur le formulaire tarif (étape 9). Elles restent physiquement sur
 *    `tarifs` en lecture pour compatibilité transitoire (8 accès SQL directs non
 *    encore basculés, étape 10) mais deux mécanismes différents les rendraient
 *    bloquantes si elles restaient `NOT NULL`, même avec un défaut :
 *      - `reference`/`saisie_par` n'ont pas de défaut → tout INSERT qui les omet
 *        échoue en SQL strict (`doesn't have a default value`) ;
 *      - `compte`/`nb_personnes_max`/`is_cotisation` ont un défaut, mais
 *        `MetaData::is_required()` (donc la règle de validation CodeIgniter
 *        `required`) ne regarde que `Null = 'NO'`, pas l'existence d'un défaut :
 *        sans ce changement, la validation du formulaire tarif (qui ne les
 *        affiche plus) échouerait avec « champ obligatoire » sur des champs
 *        invisibles. `club`, `public`, `type_ticket` sont déjà NULLABLE, aucun
 *        changement nécessaire pour eux.
 *
 * @see doc/design_notes/refactoring_produits_tarifs.md
 */
class Migration_Tarifs_produit_id_not_null extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 148;
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

        $missing = $this->db->query(
            "SELECT COUNT(*) AS n FROM `tarifs` WHERE produit_id IS NULL"
        )->row_array();

        if ((int) $missing['n'] !== 0) {
            $msg = "Migration " . $this->migration_number . ": " . $missing['n']
                . " ligne(s) de tarifs sans produit_id — NOT NULL refusé tant que le backfill n'est pas complet";
            gvv_error($msg);
            throw new Exception($msg);
        }

        $sqls = array(
            "ALTER TABLE `tarifs` MODIFY `produit_id` INT(11) NOT NULL COMMENT 'Produit (produits.id)'",
            "ALTER TABLE `tarifs` MODIFY `reference` VARCHAR(32) NULL COMMENT 'Legacy — remplacé par produits.reference, plus écrit depuis Tarifs_model'",
            "ALTER TABLE `tarifs` MODIFY `saisie_par` VARCHAR(25) NULL COMMENT 'Legacy — remplacé par created_by, plus écrit depuis Tarifs_model'",
            "ALTER TABLE `tarifs` MODIFY `compte` INT(11) NULL DEFAULT 0 COMMENT 'Legacy — remplacé par produits.compte, plus écrit depuis Tarifs_model'",
            "ALTER TABLE `tarifs` MODIFY `nb_personnes_max` TINYINT(3) UNSIGNED NULL DEFAULT 1 COMMENT 'Legacy — remplacé par produits.nb_personnes_max, plus écrit depuis Tarifs_model'",
            "ALTER TABLE `tarifs` MODIFY `is_cotisation` TINYINT(1) NULL DEFAULT 0 COMMENT 'Legacy — remplacé par produits.is_cotisation, plus écrit depuis Tarifs_model'",
        );

        $errors += $this->run_queries($sqls);

        if ($errors > 0) {
            gvv_error("Migration " . $this->migration_number . ": $errors error(s)");
            return false;
        }

        gvv_info("Migration database up to " . $this->migration_number);
        return true;
    }

    public function down() {
        $errors = 0;
        $sqls = array(
            "ALTER TABLE `tarifs` MODIFY `produit_id` INT(11) NULL COMMENT 'Produit (produits.id)'",
            "ALTER TABLE `tarifs` MODIFY `reference` VARCHAR(32) NOT NULL COMMENT 'Référence du produit'",
            "ALTER TABLE `tarifs` MODIFY `saisie_par` VARCHAR(25) NOT NULL COMMENT 'Opérateur'",
            "ALTER TABLE `tarifs` MODIFY `compte` INT(11) NOT NULL DEFAULT 0 COMMENT 'Numéro de compte associé'",
            "ALTER TABLE `tarifs` MODIFY `nb_personnes_max` TINYINT(3) UNSIGNED NOT NULL DEFAULT 1",
            "ALTER TABLE `tarifs` MODIFY `is_cotisation` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Produit de cotisation'",
        );
        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}
