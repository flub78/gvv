<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 176 : emails vides des membres remis à NULL
 *
 * La migration 107 a posé un index unique sur membres.memail après avoir
 * converti les emails vides en NULL. Le formulaire membre enregistrait
 * cependant un email vide comme '' : le premier membre sans email modifié
 * recevait '', et tout enregistrement d'un autre membre sans email échouait
 * ensuite sur l'index unique (« doublon détecté »).
 *
 * Le formulaire enregistre maintenant NULL ; cette migration corrige les ''
 * déjà présents. Il n'y a rien à défaire : down() ne modifie pas les données.
 */
class Migration_Membres_memail_vide_null extends CI_Migration {

    public function up() {
        $ok = (bool) $this->db->query("UPDATE membres SET memail = NULL WHERE memail = ''");

        log_message('info', 'Migration 176: membres.memail vides remis à NULL');
        return $ok;
    }

    public function down() {
        log_message('info', 'Migration 176: rien à défaire');
        return TRUE;
    }
}
