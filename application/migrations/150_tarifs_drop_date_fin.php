<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Migration 150: Suppression de `tarifs.date_fin`
 *
 * `date_fin` n'a jamais été utilisée en pratique (100% des lignes portent la
 * valeur sentinelle par défaut 2099-12-31) et sa présence dans le code était
 * incohérente : `Tarifs_model::get_tarif()` (résolution du prix pour la
 * facturation/les vols) l'ignorait totalement, tandis que d'autres chemins
 * (carte cotisation de welcome.php, get_cotisation_products_for_section(),
 * get_cotisation_product_by_id(), vols_decouverte.php, vd_quota_helper.php) la
 * filtraient — avec un risque de tarifs qui se chevauchent/se masquent
 * mutuellement selon l'écran consulté si une date de fin réelle avait été
 * saisie. Décision : simplifier le modèle à un historique par date de début
 * seule (la ligne suivante clôt implicitement la précédente).
 *
 * @see doc/design_notes/refactoring_produits_tarifs.md
 */
class Migration_Tarifs_drop_date_fin extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 150;
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
            "ALTER TABLE `tarifs` DROP COLUMN `date_fin`",
        );

        $errors += $this->run_queries($sqls);

        if ($errors > 0) {
            gvv_error("Migration " . $this->migration_number . ": $errors error(s) while dropping date_fin");
            return false;
        }

        gvv_info("Migration database up to " . $this->migration_number . " — colonne date_fin supprimée de tarifs");
        return true;
    }

    public function down() {
        $errors = 0;
        $sqls = array(
            "ALTER TABLE `tarifs`
                ADD COLUMN `date_fin` DATE NULL DEFAULT '2099-12-31' AFTER `date`",
        );
        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1)
            . " — colonne recréée avec la valeur par défaut (aucune donnée restaurée), errors=$errors");
        return !$errors;
    }
}
