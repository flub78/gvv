<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 115 : ajout de exemption_solde dans la table membres
 *
 * Ce champ permet de désactiver le contrôle de solde lors des réservations
 * pour un pilote individuel, indépendamment du flag global reservation_balance_check.
 *
 * Le contrôle de solde est de toute façon toujours désactivé pour les rôles
 * administrateur club, instructeur et pilote_vd.
 */
class Migration_Membres_exemption_solde extends CI_Migration {

    public function up()
    {
        // Vérifier que la colonne n'existe pas déjà (migration idempotente)
        $fields = $this->db->list_fields('membres');
        if (!in_array('exemption_solde', $fields)) {
            $this->dbforge->add_column('membres', array(
                'exemption_solde' => array(
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'null'       => FALSE,
                    'comment'    => 'Exempte ce pilote du contrôle de solde lors des réservations (0=contrôle actif, 1=exempté)',
                    'after'      => 'mnumero',
                ),
            ));
        }
    }

    public function down()
    {
        $fields = $this->db->list_fields('membres');
        if (in_array('exemption_solde', $fields)) {
            $this->dbforge->drop_column('membres', 'exemption_solde');
        }
    }
}
