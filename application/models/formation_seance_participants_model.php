<?php
/**
 * GVV Gestion vol à voile
 * Modèle – Participants aux séances de formation théoriques
 *
 * Gère la table formation_seances_participants (id, seance_id, pilote_id).
 */

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Formation_seance_participants_model extends Common_Model {

    public $table       = 'formation_seances_participants';
    public $primary_key = 'id';

    public function __construct() {
        parent::__construct();
    }

    /**
     * Retourne la liste des participants d'une séance avec les infos membre.
     *
     * @param int $seance_id
     * @return array [['pilote_id', 'mnom', 'mprenom', 'mmail'], ...]
     */
    public function get_by_seance($seance_id) {
        return $this->db
            ->select('p.pilote_id, m.mnom, m.mprenom, m.memail')
            ->from($this->table . ' p')
            ->join('membres m', 'p.pilote_id = m.mlogin', 'left')
            ->where('p.seance_id', (int)$seance_id)
            ->order_by('m.mnom, m.mprenom')
            ->get()->result_array();
    }

    /**
     * Retourne le nombre de participants pour une séance.
     *
     * @param int $seance_id
     * @return int
     */
    public function count_by_seance($seance_id) {
        return (int)$this->db
            ->where('seance_id', (int)$seance_id)
            ->count_all_results($this->table);
    }

    /**
     * Remplace tous les participants d'une séance.
     *
     * @param int   $seance_id
     * @param array $pilote_ids  Liste de mlogin
     * @return bool
     */
    public function replace_participants($seance_id, array $pilote_ids) {
        $this->db->where('seance_id', (int)$seance_id)->delete($this->table);

        foreach (array_unique($pilote_ids) as $pilote_id) {
            if (empty($pilote_id))
                continue;
            $this->db->insert($this->table, array(
                'seance_id' => (int)$seance_id,
                'pilote_id' => $pilote_id,
            ));
        }
        return TRUE;
    }

    /**
     * Supprime tous les participants d'une séance.
     *
     * @param int $seance_id
     */
    public function delete_by_seance($seance_id) {
        $this->db->where('seance_id', (int)$seance_id)->delete($this->table);
    }

    /**
     * Vérifie si un pilote participe à une séance.
     *
     * @param int    $seance_id
     * @param string $pilote_id
     * @return bool
     */
    public function is_participant($seance_id, $pilote_id) {
        return (bool)$this->db
            ->where('seance_id', (int)$seance_id)
            ->where('pilote_id', $pilote_id)
            ->count_all_results($this->table);
    }


    /**
     * Total distinct participants across all theoretical sessions in a given year.
     *
     * @param int $year
     * @return int
     */
    public function count_total_participants_year($year)
    {
        $sql = "
            SELECT COUNT(DISTINCT p.pilote_id) AS nb
            FROM {$this->table} p
            INNER JOIN formation_seances s ON s.id = p.seance_id
            WHERE YEAR(s.date_seance) = ?
        ";
        $row = $this->db->query($sql, array((int)$year))->row_array();
        return isset($row['nb']) ? (int)$row['nb'] : 0;
    }
}
