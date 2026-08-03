<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Migration 147: Ajout de `tarifs.produit_id` (refactoring tarifs -> produits + tarifs)
 *
 * Étape 5 de doc/plans/refactoring_produits_tarifs_plan.md. Relie chaque ligne de
 * `tarifs` à son produit (créé par la migration 146) via une clé étrangère.
 *
 * `tarifs.id` n'est jamais renuméroté (au moins une surcharge club — ACES — stocke
 * cette clé primaire directement dans avions.maprix / pompes.ppu, cf. point B du
 * design note). Les anciennes colonnes produit (reference, description, compte,
 * club, is_cotisation, nb_personnes_max, public, type_ticket, saisie_par) restent
 * en place à ce stade pour compatibilité transitoire — elles ne sont supprimées
 * qu'à la migration 148, une fois tout le code applicatif basculé.
 *
 * `produit_id` reste NULLABLE à ce stade (bien que backfillée à 100% sur les
 * lignes existantes) : la façade `Tarifs_model` n'est réécrite qu'à l'étape 7,
 * donc le code applicatif actuel (et plusieurs tests, ex.
 * ReservationsBalanceCheckTest, PaiementsEnLigneCotisationPiloteTest) continue
 * d'insérer de nouvelles lignes `tarifs` sans fournir `produit_id`. Un `NOT NULL`
 * immédiat casse ces insertions (`Field 'produit_id' doesn't have a default
 * value`), ce que `./run-all-tests.sh` a confirmé en pratique. La contrainte
 * `NOT NULL` sera posée une fois la façade en place (étape 7) et confirmée par
 * les tests ; la FK reste posée dès maintenant (une FK autorise les valeurs
 * NULL, qui ne sont simplement pas contrôlées tant que la colonne l'est).
 *
 * @see doc/design_notes/refactoring_produits_tarifs.md
 */
class Migration_Tarifs_add_produit_id extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = 147;
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
            "ALTER TABLE `tarifs` ADD COLUMN `produit_id` INT(11) NULL COMMENT 'Produit (produits.id)' AFTER `id`",
            "UPDATE `tarifs` t
             JOIN `produits` p ON t.reference = p.reference AND t.club = p.club
             SET t.produit_id = p.id",
        );

        $errors += $this->run_queries($sqls);

        if ($errors > 0) {
            gvv_error("Migration " . $this->migration_number . ": $errors error(s) while adding/backfilling produit_id");
            return false;
        }

        $missing = $this->db->query(
            "SELECT COUNT(*) AS n FROM `tarifs` WHERE produit_id IS NULL"
        )->row_array();

        if ((int) $missing['n'] !== 0) {
            $msg = "Migration " . $this->migration_number . ": " . $missing['n']
                . " ligne(s) de tarifs sans produit_id après backfill (jointure reference/club en échec)";
            gvv_error($msg);
            throw new Exception($msg);
        }

        $sqls_constraints = array(
            // produit_id reste NULLABLE à ce stade — voir docblock de la classe.
            "ALTER TABLE `tarifs` ADD CONSTRAINT `fk_tarifs_produit` FOREIGN KEY (`produit_id`) REFERENCES `produits`(`id`)",
        );

        $errors += $this->run_queries($sqls_constraints);

        if ($errors > 0) {
            gvv_error("Migration " . $this->migration_number . ": $errors error(s) while enforcing produit_id constraints");
            return false;
        }

        gvv_info("Migration database up to " . $this->migration_number . ", " . $missing['n'] . " ligne(s) restante(s) sans produit_id (attendu 0)");
        return true;
    }

    public function down() {
        $errors = 0;
        $sqls = array(
            "ALTER TABLE `tarifs` DROP FOREIGN KEY `fk_tarifs_produit`",
            "ALTER TABLE `tarifs` DROP COLUMN `produit_id`",
        );
        $errors += $this->run_queries($sqls);
        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}
