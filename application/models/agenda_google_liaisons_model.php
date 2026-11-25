<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Liaisons des membres avec leur agenda Google (table agenda_google_liaisons)
 * et liens vers les événements créés (table agenda_google_evenements).
 *
 * Accès aux données seulement : les règles métier sont dans la bibliothèque
 * Agenda_google_sync.
 *
 * @see doc/design_notes/synchronisation_agenda_google_reservations_design.md
 */
class Agenda_google_liaisons_model extends Common_Model {
    public $table = 'agenda_google_liaisons';
    protected $primary_key = 'id';

    const ACTIVE = 'active';
    const SUSPENDUE = 'suspendue';
    const ARRETEE = 'arretee';

    /**
     * Liaison d'un membre, ou NULL s'il n'a jamais activé la synchronisation.
     */
    public function for_member($mlogin) {
        $row = $this->db->get_where($this->table, array('mlogin' => $mlogin))->row_array();
        return $row ?: NULL;
    }

    /**
     * Enregistre une activation : crée la liaison ou la réactive.
     *
     * @return int identifiant de la liaison
     */
    public function save_activation($mlogin, $compte_google, $jeton_chiffre) {
        $data = array(
            'compte_google'        => $compte_google,
            'jeton_renouvellement' => $jeton_chiffre,
            'etat'                 => self::ACTIVE,
            'cause_suspension'     => NULL,
            'echecs_consecutifs'   => 0,
        );
        $existing = $this->for_member($mlogin);
        if ($existing) {
            $this->update('id', $data, $existing['id']);
            return (int) $existing['id'];
        }
        $data['mlogin'] = $mlogin;
        return (int) $this->create($data);
    }

    /**
     * Arrêt : le jeton est effacé, les liens vers les événements sont conservés.
     */
    public function mark_stopped($liaison_id) {
        $this->update('id', array(
            'etat'                 => self::ARRETEE,
            'jeton_renouvellement' => NULL,
            'cause_suspension'     => NULL,
            'echecs_consecutifs'   => 0,
        ), $liaison_id);
    }

    /**
     * Suspension : le jeton n'est plus utilisable, le membre doit relier
     * à nouveau son agenda.
     */
    public function mark_suspended($liaison_id, $cause) {
        $this->update('id', array(
            'etat'                 => self::SUSPENDUE,
            'jeton_renouvellement' => NULL,
            'cause_suspension'     => mb_substr((string) $cause, 0, 255),
        ), $liaison_id);
    }

    /**
     * Abandonne les liens vers les événements d'une liaison (changement de
     * compte Google : les événements de l'ancien agenda n'y sont plus suivis).
     */
    public function forget_events($liaison_id) {
        $this->db->delete('agenda_google_evenements', array('liaison_id' => $liaison_id));
        return $this->db->affected_rows();
    }

    /**
     * Mise à jour réussie : date de la dernière réussite, compteur d'échecs remis à zéro.
     */
    public function record_success($liaison_id) {
        $this->db->where('id', $liaison_id)->update($this->table, array(
            'derniere_reussite_at' => date('Y-m-d H:i:s'),
            'echecs_consecutifs'   => 0,
            'updated_at'           => date('Y-m-d H:i:s'),
        ));
    }

    /**
     * Échec d'une mise à jour.
     *
     * @return int nombre d'échecs consécutifs
     */
    public function record_failure($liaison_id) {
        $this->db->set('echecs_consecutifs', 'echecs_consecutifs + 1', FALSE)
            ->where('id', $liaison_id)->update($this->table);
        $row = $this->db->select('echecs_consecutifs')->get_where($this->table, array('id' => $liaison_id))->row_array();
        return $row ? (int) $row['echecs_consecutifs'] : 0;
    }

    /**
     * Supprime la liaison (membre supprimé). Ses liens sont supprimés avec elle.
     */
    public function remove($liaison_id) {
        $this->db->delete($this->table, array('id' => $liaison_id));
    }

    // -------------------------------------------------------------------------
    // Liens vers les événements Google (agenda_google_evenements)
    // -------------------------------------------------------------------------

    /**
     * Liens d'une réservation, indexés par liaison, avec l'état de la liaison.
     */
    public function links_for_reservation($reservation_id) {
        $rows = $this->db->select('e.*, l.etat, l.mlogin')
            ->from('agenda_google_evenements e')
            ->join($this->table . ' l', 'l.id = e.liaison_id', 'inner')
            ->where('e.reservation_id', $reservation_id)
            ->get()->result_array();
        $links = array();
        foreach ($rows as $row) {
            $links[(int) $row['liaison_id']] = $row;
        }
        return $links;
    }

    public function save_link($liaison_id, $reservation_id, $google_event_id, $empreinte) {
        $now = date('Y-m-d H:i:s');
        $this->db->insert('agenda_google_evenements', array(
            'liaison_id' => $liaison_id, 'reservation_id' => $reservation_id,
            'google_event_id' => $google_event_id, 'empreinte' => $empreinte,
            'created_at' => $now, 'updated_at' => $now,
            'created_by' => 'agenda_google', 'updated_by' => 'agenda_google',
        ));
    }

    public function update_link($link_id, $google_event_id, $empreinte) {
        $this->db->where('id', $link_id)->update('agenda_google_evenements', array(
            'google_event_id' => $google_event_id, 'empreinte' => $empreinte,
            'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => 'agenda_google',
        ));
    }

    public function delete_link($link_id) {
        $this->db->delete('agenda_google_evenements', array('id' => $link_id));
    }

    /**
     * Liaisons suspendues, pour l'administrateur : membre, état, cause, date.
     * Jamais le jeton.
     */
    public function suspended() {
        return $this->db->select('l.id, l.mlogin, m.mprenom, m.mnom, l.etat, l.cause_suspension, l.updated_at, l.derniere_reussite_at')
            ->from($this->table . ' l')
            ->join('membres m', 'm.mlogin = l.mlogin', 'left')
            ->where('l.etat', self::SUSPENDUE)
            ->order_by('l.updated_at', 'desc')
            ->get()->result_array();
    }
}
