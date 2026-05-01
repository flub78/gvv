<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Modèle — Cartes membre
 *
 * Fournit les données nécessaires à la génération des cartes de membre :
 * données membres, années de cotisation, sélection du lot, président.
 */
class Cartes_membre_model extends CI_Model {

    function __construct() {
        parent::__construct();
    }

    /**
     * Retourne les données d'un membre pour la carte.
     *
     * @param string $mlogin
     * @return array|null
     */
    public function get_membre($mlogin) {
        $row = $this->db
            ->select('mlogin, mnom, mprenom, mnumero, photo, actif')
            ->from('membres')
            ->where('mlogin', $mlogin)
            ->get()->row_array();
        return $row ?: null;
    }

    /**
     * Retourne les années pour lesquelles un membre a une cotisation (type 0),
     * triées décroissantes (la plus récente en premier).
     *
     * @param string $mlogin
     * @return int[]
     */
    public function get_years_with_cotisation($mlogin) {
        $rows = $this->db
            ->select('year')
            ->from('licences')
            ->where('pilote', $mlogin)
            ->where('type', 0)
            ->order_by('year', 'DESC')
            ->get()->result_array();
        return array_column($rows, 'year');
    }

    /**
     * Retourne les membres actifs ayant une cotisation pour l'année donnée,
     * triés par nom puis prénom.
     *
     * @param int $year
     * @return array
     */
    public function get_membres_actifs_annee($year) {
        return $this->db
            ->select('m.mlogin, m.mnom, m.mprenom, m.mnumero, m.photo')
            ->from('membres m')
            ->join('licences l', 'l.pilote = m.mlogin AND l.type = 0 AND l.year = ' . (int)$year, 'inner')
            ->where('m.actif', 1)
            ->order_by('m.mnom')
            ->order_by('m.mprenom')
            ->get()->result_array();
    }

    /**
     * Retourne les données d'un membre même sans cotisation (usage admin).
     *
     * @param string $mlogin
     * @return array|null
     */
    public function get_membre_admin($mlogin) {
        return $this->get_membre($mlogin);
    }

    /**
     * Retourne le président actif du club (mniveaux & PRESIDENT).
     * PRESIDENT = 2 (voir application/config/constants.php).
     *
     * @return array|null  ['mnom' => ..., 'mprenom' => ...]
     */
    public function get_president() {
        $row = $this->db
            ->select('mnom, mprenom')
            ->from('membres')
            ->where('(mniveaux & 2) != 0', null, false)
            ->where('actif', 1)
            ->limit(1)
            ->get()->row_array();
        return $row ?: null;
    }

    /**
     * Retourne le chemin absolu vers la photo d'un membre, ou null si absente.
     *
     * @param string|null $photo  Valeur du champ photo en base
     * @return string|null
     */
    public function get_photo_path($photo) {
        if (empty($photo)) {
            return null;
        }
        $paths = array(
            FCPATH . 'uploads/photos/' . $photo,
            FCPATH . 'uploads/' . $photo,
        );
        foreach ($paths as $p) {
            if (file_exists($p)) {
                return $p;
            }
        }
        return null;
    }

    /**
     * Enregistre (ou met à jour) le chemin du fond de carte dans la configuration.
     *
     * @param int    $annee
     * @param string $face    'recto' ou 'verso'
     * @param string $valeur  Chemin relatif (depuis FCPATH) vers le fichier uploadé
     */
    public function save_fond_path($annee, $face, $valeur) {
        $cle = 'carte_' . $face . '_' . (int)$annee;
        $exists = $this->db
            ->select('cle')
            ->from('configuration')
            ->where('cle', $cle)
            ->count_all_results() > 0;
        if ($exists) {
            $this->db->where('cle', $cle)->update('configuration', array('valeur' => $valeur));
        } else {
            $this->db->insert('configuration', array('cle' => $cle, 'valeur' => $valeur));
        }
    }

    /**
     * Retourne le chemin du fond de carte (recto ou verso) pour une année.
     * Stocké dans la table configuration sous la clé carte_{face}_{annee}.
     *
     * @param int    $annee
     * @param string $face  'recto' ou 'verso'
     * @return string|null  Chemin absolu ou null si non configuré
     */
    public function get_fond_path($annee, $face) {
        $cle = 'carte_' . $face . '_' . (int)$annee;
        $row = $this->db
            ->select('valeur')
            ->from('configuration')
            ->where('cle', $cle)
            ->get()->row_array();
        if (!$row || empty($row['valeur'])) {
            return null;
        }
        $path = FCPATH . $row['valeur'];
        return file_exists($path) ? $path : null;
    }

    /**
     * Retourne la liste de tous les membres actifs (pour sélection manuelle en lot).
     *
     * @return array
     */
    public function get_all_membres_actifs() {
        return $this->db
            ->select('mlogin, mnom, mprenom, mnumero')
            ->from('membres')
            ->where('actif', 1)
            ->order_by('mnom')
            ->order_by('mprenom')
            ->get()->result_array();
    }

    /**
     * Retourne la liste de tous les membres (actifs et inactifs) pour ajout manuel.
     *
     * @return array
     */
    public function get_all_membres() {
        return $this->db
            ->select('mlogin, mnom, mprenom, mnumero, actif')
            ->from('membres')
            ->order_by('mnom')
            ->order_by('mprenom')
            ->get()->result_array();
    }
}
