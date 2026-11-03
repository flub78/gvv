<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Historique des modifications de la page Configuration (table parametres_history).
 *
 * Une ligne par paramètre modifié : onglet, section, langue, ancienne et nouvelle
 * valeur (NULL pour un secret, ou quand le paramètre n'était pas défini), action
 * ('modification', 'effacement' d'un secret, 'retour' à une valeur précédente).
 *
 * @see doc/design_notes/configuration_unifiee_design.md
 */
class Parametres_history_model extends CI_Model {

    public $table = 'parametres_history';

    /**
     * Enregistre une modification.
     *
     * @param array $row cle, onglet, section, lang, ancienne_valeur, nouvelle_valeur, secret, action
     * @return bool
     */
    public function trace($row) {
        $now = date('Y-m-d H:i:s');
        $user = $this->dx_auth->is_logged_in() ? $this->dx_auth->get_username() : NULL;
        $row = array_merge(array(
            'section' => NULL, 'lang' => NULL, 'ancienne_valeur' => NULL, 'nouvelle_valeur' => NULL,
            'secret' => 0, 'action' => 'modification',
        ), $row, array(
            'created_at' => $now, 'updated_at' => $now, 'created_by' => $user, 'updated_by' => $user,
        ));
        return (bool) $this->db->insert($this->table, $row);
    }

    /**
     * Modifications d'un onglet, les plus récentes d'abord.
     */
    public function by_tab($tab, $limit = 200) {
        return $this->db->from($this->table)->where('onglet', $tab)
            ->order_by('created_at', 'DESC')->order_by('id', 'DESC')
            ->limit($limit)->get()->result_array();
    }

    /**
     * Une modification, ou NULL.
     */
    public function get_by_id($id) {
        $query = $this->db->from($this->table)->where('id', (int) $id)->get();
        return ($query && $query->num_rows() > 0) ? $query->row_array() : NULL;
    }

    /**
     * TRUE si la table existe (migration 185 appliquée).
     */
    public function available() {
        return $this->db->table_exists($this->table);
    }
}

/* End of file parametres_history_model.php */
