<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 104 — Contrainte UNIQUE anti-doublon sur vols_decouverte
 *
 * Un double-clic sur un réseau lent peut soumettre deux fois le même formulaire
 * et créer deux enregistrements identiques.  Cette contrainte sur (club, date_vente,
 * beneficiaire, product) bloque l'insertion du second doublon au niveau base de données.
 * La contrainte s'appuie sur l'erreur MySQL 1062 déjà gérée par Gvv_Controller
 * avec un message utilisateur propre.
 *
 * Si des doublons existent déjà dans la base la migration est ignorée (log d'erreur)
 * afin de ne pas bloquer le déploiement.
 */
class Migration_Vols_decouverte_unique_submit extends CI_Migration {

    public function up() {
        $result = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM (
                SELECT club, date_vente, beneficiaire, product, COUNT(*) AS n
                FROM vols_decouverte
                GROUP BY club, date_vente, beneficiaire, product
                HAVING n > 1
            ) t"
        )->row_array();

        if ((int) $result['cnt'] > 0) {
            log_message('error',
                'Migration 104: cannot add UNIQUE constraint on vols_decouverte — '
                . $result['cnt'] . ' duplicate group(s) exist. Run manually after deduplication.');
            return;
        }

        $this->db->query(
            "ALTER TABLE vols_decouverte
             ADD UNIQUE KEY uk_vd_no_double_submit (club, date_vente, beneficiaire, product)"
        );
    }

    public function down() {
        $this->db->query("ALTER TABLE vols_decouverte DROP INDEX IF EXISTS uk_vd_no_double_submit");
    }
}
