<?php
/**
 * GVV Gestion vol à voile
 * Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Modèle des statistiques adhérents : accès aux données uniquement
 *
 * @package models
 */

class Adherents_report_model extends CI_Model {

    const LICENCE_TYPE_COTISATION = 0;

    public function __construct() {
        parent::__construct();
        $this->load->model('sections_model');
    }

    /**
     * Charge les données brutes nécessaires aux statistiques adhérents.
     *
     * Aucun calcul statistique ici : voir la bibliothèque Adherents_stats.
     *
     * @param int $year Année sélectionnée ; les cotisations postérieures sont ignorées
     * @return array array(
     *     'sections' => liste des sections,
     *     'members'  => [mlogin => [mlogin, mnom, mprenom, mdaten, msexe,
     *                     inscription_date, years => [années de cotisation],
     *                     sections => [ids des sections où le membre a un compte 411]]]
     * )
     */
    public function get_adherents_data($year) {
        $rows = $this->db
            ->distinct()
            ->select('membres.mlogin, membres.mnom, membres.mprenom, membres.mdaten, membres.msexe, membres.inscription_date, licences.year')
            ->from('licences')
            ->join('membres', 'membres.mlogin = licences.pilote', 'inner')
            ->where('licences.type', self::LICENCE_TYPE_COTISATION)
            ->where('licences.year <=', (int) $year)
            ->get()->result_array();

        $members = array();
        foreach ($rows as $row) {
            $mlogin = $row['mlogin'];
            if (!isset($members[$mlogin])) {
                $members[$mlogin] = array(
                    'mlogin' => $mlogin,
                    'mnom' => $row['mnom'],
                    'mprenom' => $row['mprenom'],
                    'mdaten' => $row['mdaten'],
                    'msexe' => $row['msexe'],
                    'inscription_date' => $row['inscription_date'],
                    'years' => array(),
                    'sections' => array(),
                );
            }
            $members[$mlogin]['years'][] = (int) $row['year'];
        }

        $accounts = $this->db
            ->distinct()
            ->select('pilote, club')
            ->from('comptes')
            ->where('codec', '411')
            ->get()->result_array();
        foreach ($accounts as $account) {
            if (isset($members[$account['pilote']])) {
                $members[$account['pilote']]['sections'][] = (int) $account['club'];
            }
        }

        return array(
            'sections' => $this->sections_model->section_list(),
            'members' => $members,
        );
    }

    /**
     * Récupère la liste des années disponibles pour les cotisations
     *
     * @return array Liste des années
     */
    public function get_available_years() {
        $this->db->distinct();
        $this->db->select('year');
        $this->db->from('licences');
        $this->db->where('type', self::LICENCE_TYPE_COTISATION);
        $this->db->order_by('year', 'DESC');
        $result = $this->db->get()->result_array();

        $years = array();
        foreach ($result as $row) {
            $years[] = $row['year'];
        }

        // Ajouter l'année courante si elle n'est pas dans la liste
        $current_year = (int)date('Y');
        if (!in_array($current_year, $years)) {
            array_unshift($years, $current_year);
        }

        return $years;
    }

    /**
     * Construit un sélecteur d'années pour le formulaire
     *
     * @return array Tableau associatif année => année pour dropdown
     */
    public function get_year_selector() {
        $years = $this->get_available_years();
        $selector = array();
        foreach ($years as $year) {
            $selector[$year] = $year;
        }
        return $selector;
    }
}
