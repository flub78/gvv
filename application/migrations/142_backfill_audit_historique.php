<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Migration 142: Lot 6 (doc/plans/journalisation_crud_plan.md) — rattrapage des
 * created_by/created_at corrompus par la régression isset(FALSE) du Lot 0/1
 * (voir §2.3 du plan). Cette régression stockait le placeholder '0' / la date
 * zéro '0000-00-00 00:00:00' au lieu de NULL, donc le backfill de la migration
 * 092 (qui ne testait que IS NULL) n'avait rien corrigé sur les lignes saisies
 * depuis mars 2026 via le formulaire web.
 *
 * Portée : les 8 tables qui possèdent created_by/created_at ET saisie_par
 * (achats, comptes, ecritures, tarifs, tickets, volsa, volsp, vols_decouverte).
 *
 * 6.1 - created_by <- saisie_par quand saisie_par est fiable (source directe,
 *       cf. confirmation §2.3 du plan).
 * 6.2 - created_at <- colonne date métier la plus proche disponible (date,
 *       date_creation, date_vente, vpdate...), approximation à minuit.
 * 6.3 - volsa uniquement : created_at <- vadate + heure d'atterrissage (vahfin,
 *       décimal-heures) + 20 minutes, hypothèse retenue qu'un pilote saisit son
 *       vol 15 à 30 minutes après l'atterrissage. Bien plus précis qu'une simple
 *       date à minuit.
 * comptes : aucune colonne date métier utilisable — created_at reste NULL pour
 *       les lignes non couvertes par saisie_par plutôt que d'inventer une valeur.
 *
 * Idempotente : toutes les UPDATE ne touchent que les lignes encore marquées
 * comme non renseignées (NULL, '', '0' ou date zéro), rejouable sans effet sur
 * les lignes déjà correctes (import_of, helloasso, facturation, Lot 5...).
 */
class Migration_Backfill_audit_historique extends CI_Migration
{
    private $migration_number = 142;

    private $zero_date = '0000-00-00 00:00:00';

    public function up()
    {
        $tables_with_saisie_par = array('achats', 'comptes', 'ecritures', 'tarifs', 'tickets', 'volsa', 'volsp', 'vols_decouverte');

        // 6.1 - created_by depuis saisie_par.
        foreach ($tables_with_saisie_par as $table) {
            $sql = "UPDATE `$table`
                    SET created_by = saisie_par
                    WHERE (created_by IS NULL OR created_by = '' OR created_by = '0')
                      AND saisie_par IS NOT NULL AND saisie_par <> '' AND saisie_par <> '0'";
            $this->run($sql, "6.1 created_by<-saisie_par on $table");
        }

        // 6.2 - created_at depuis la colonne date métier la plus proche, par table.
        $date_proxy_by_table = array(
            'achats' => 'date',
            'ecritures' => 'date_creation',
            'tarifs' => 'date',
            'tickets' => 'date',
            'vols_decouverte' => 'date_vente',
            'volsp' => 'vpdate',
            // volsa handled separately below (6.3, more precise proxy)
            // comptes has no usable date proxy (see class doc)
        );
        foreach ($date_proxy_by_table as $table => $date_column) {
            // The proxy column itself can carry a zero-date placeholder ('0000-00-00',
            // used in this app to mean "no meaningful date", e.g. some tarifs rows) —
            // that is just as unusable as NULL and must not be copied into created_at.
            $sql = "UPDATE `$table`
                    SET created_at = CONCAT($date_column, ' 00:00:00')
                    WHERE (created_at IS NULL OR created_at = '{$this->zero_date}')
                      AND $date_column IS NOT NULL AND $date_column <> '0000-00-00'";
            $this->run($sql, "6.2 created_at<-$date_column on $table");
        }

        // 6.3 - volsa : vadate + heure d'atterrissage (vahfin, décimal-heures) + 20 min.
        $sql = "UPDATE volsa
                SET created_at = DATE_ADD(
                    DATE_ADD(vadate, INTERVAL (FLOOR(vahfin) * 3600 + ROUND((vahfin - FLOOR(vahfin)) * 3600)) SECOND),
                    INTERVAL 20 MINUTE
                )
                WHERE (created_at IS NULL OR created_at = '{$this->zero_date}')
                  AND vadate IS NOT NULL";
        $this->run($sql, '6.3 created_at<-vadate+vahfin+20min on volsa');

        // Miroir updated_by/updated_at une fois created_by/created_at rattrapés,
        // même logique que la migration 092 pour les lignes jamais modifiées depuis.
        foreach ($tables_with_saisie_par as $table) {
            $this->run(
                "UPDATE `$table` SET updated_by = created_by
                 WHERE (updated_by IS NULL OR updated_by = '' OR updated_by = '0')
                   AND created_by IS NOT NULL AND created_by <> '' AND created_by <> '0'",
                "mirror updated_by<-created_by on $table"
            );
            $this->run(
                "UPDATE `$table` SET updated_at = created_at
                 WHERE (updated_at IS NULL OR updated_at = '{$this->zero_date}')
                   AND created_at IS NOT NULL AND created_at <> '{$this->zero_date}'",
                "mirror updated_at<-created_at on $table"
            );
        }

        gvv_info('Migration database up to ' . $this->migration_number . ' (Lot 6 audit backfill)');
        return true;
    }

    public function down()
    {
        // Rattrapage de données historiques uniquement, aucune colonne ajoutée ou
        // supprimée. On ne peut pas distinguer après coup une valeur backfillée
        // d'une valeur déjà correcte au même format, donc il n'y a rien de sûr à
        // annuler ici : down() est un no-op assumé (cf. §6 "Périmètre exclu" du
        // plan : pas d'historisation des valeurs avant modification).
        gvv_info('Migration database down to ' . ($this->migration_number - 1) . ' (Lot 6 backfill: no-op, data-only migration)');
        return true;
    }

    private function run($sql, $label)
    {
        $result = $this->db->query($sql);
        $affected = $this->db->affected_rows();
        if ($result === false) {
            gvv_error("Migration 142 failed ($label): " . $this->db->_error_message());
            return false;
        }
        gvv_info("Migration 142 ($label): $affected row(s) updated");
        return true;
    }
}
