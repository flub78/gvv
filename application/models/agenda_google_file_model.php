<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * File des réservations à réconcilier avec les agendas Google
 * (table agenda_google_file). Une réservation n'y figure qu'une fois, quel que
 * soit le nombre de changements accumulés.
 *
 * @see doc/design_notes/synchronisation_agenda_google_reservations_design.md (D1, D3)
 */
class Agenda_google_file_model extends CI_Model {
    const TABLE = 'agenda_google_file';

    /** Délai maximal entre deux essais, en minutes */
    const MAX_DELAI_MINUTES = 360;

    /**
     * Inscrit une réservation, ou la rend immédiatement traitable si elle y est déjà.
     *
     * @return bool
     */
    public function enqueue($reservation_id, $author = NULL) {
        $now = date('Y-m-d H:i:s');
        $author = $this->db->escape($author === NULL ? 'gvv' : (string) $author);
        $sql = "INSERT INTO `" . self::TABLE . "`
                    (reservation_id, tentatives, prochain_essai_at, derniere_erreur, created_at, updated_at, created_by, updated_by)
                VALUES (?, 0, NULL, NULL, ?, ?, $author, $author)
                ON DUPLICATE KEY UPDATE version = version + 1, tentatives = 0, prochain_essai_at = NULL, derniere_erreur = NULL,
                    updated_at = VALUES(updated_at), updated_by = VALUES(updated_by)";
        return (bool) $this->db->query($sql, array((int) $reservation_id, $now, $now));
    }

    /**
     * Réservations dont l'essai est dû, les plus anciennes d'abord.
     *
     * @return array lignes de la file
     */
    public function due($limit) {
        return $this->db->from(self::TABLE)
            ->where('(prochain_essai_at IS NULL OR prochain_essai_at <= ' . $this->db->escape(date('Y-m-d H:i:s')) . ')', NULL, FALSE)
            ->order_by('updated_at', 'asc')
            ->order_by('id', 'asc')
            ->limit((int) $limit)
            ->get()->result_array();
    }

    /**
     * La réservation est à jour dans les agendas : elle sort de la file, sauf
     * si elle a été réinscrite pendant son traitement (version changée).
     */
    public function done($row) {
        $this->db->where('id', $row['id'])
            ->where('version', $row['version'])
            ->delete(self::TABLE);
    }

    /**
     * Échec passager : l'essai suivant est repoussé de plus en plus loin
     * (1, 2, 4… minutes, au plus MAX_DELAI_MINUTES).
     *
     * @return int nombre de tentatives après celle-ci
     */
    public function retry_later($row, $error) {
        $tentatives = (int) $row['tentatives'] + 1;
        $minutes = min(self::MAX_DELAI_MINUTES, (int) pow(2, min($tentatives - 1, 10)));
        // Une réinscription pendant le traitement l'emporte : elle reste traitable tout de suite
        $this->db->where('id', $row['id'])->where('version', $row['version'])->update(self::TABLE, array(
            'tentatives'        => $tentatives,
            'prochain_essai_at' => date('Y-m-d H:i:s', time() + 60 * $minutes),
            'derniere_erreur'   => mb_substr((string) $error, 0, 255),
            'updated_by'        => 'agenda_google',
        ));
        return $tentatives;
    }

    /**
     * Abandon : retire la réservation de la file, sauf si elle a été
     * réinscrite pendant son traitement (version changée).
     */
    public function remove($row) {
        $this->db->where('id', $row['id'])
            ->where('version', $row['version'])
            ->delete(self::TABLE);
    }

    public function count_all() {
        return $this->db->count_all_results(self::TABLE);
    }
}
