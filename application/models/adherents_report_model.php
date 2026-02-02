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
 * Modèle pour le rapport des adhérents par année et classe d'âge
 *
 * @package models
 */

$CI = &get_instance();
$CI->load->helper('statistic');

class Adherents_report_model extends CI_Model {

    const LICENCE_TYPE_COTISATION = 0;
    const AGE_UNDER_25 = 'under_25';
    const AGE_25_TO_59 = '25_to_59';
    const AGE_60_AND_OVER = '60_and_over';

    public function __construct() {
        parent::__construct();
        $this->load->model('sections_model');
    }

    /**
     * Récupère les statistiques d'adhérents pour une année donnée
     *
     * @param int $year L'année pour laquelle calculer les statistiques
     * @return array Tableau avec les sections et les statistiques par classe d'âge
     */
    public function get_adherents_stats($year) {
        // Récupérer la liste des sections
        $sections = $this->sections_model->section_list();

        // Initialiser les compteurs
        $stats = array(
            self::AGE_UNDER_25 => array(),
            self::AGE_25_TO_59 => array(),
            self::AGE_60_AND_OVER => array(),
            'total' => array()
        );

        // Initialiser les compteurs pour chaque section
        foreach ($sections as $section) {
            $section_key = 'section_' . $section['id'];
            $stats[self::AGE_UNDER_25][$section_key] = 0;
            $stats[self::AGE_25_TO_59][$section_key] = 0;
            $stats[self::AGE_60_AND_OVER][$section_key] = 0;
            $stats['total'][$section_key] = 0;
        }

        // Initialiser les totaux club (dédoublonnés)
        $stats[self::AGE_UNDER_25]['club_total'] = 0;
        $stats[self::AGE_25_TO_59]['club_total'] = 0;
        $stats[self::AGE_60_AND_OVER]['club_total'] = 0;
        $stats['total']['club_total'] = 0;

        // Dates limites pour le calcul d'âge au 1er janvier
        $date_25 = ($year - 25) . '-01-01';  // Né après cette date = moins de 25 ans
        $date_60 = ($year - 60) . '-01-01';  // Né avant ou le cette date = 60 ans et plus

        // Pour chaque section, compter les adhérents par classe d'âge
        foreach ($sections as $section) {
            $section_key = 'section_' . $section['id'];
            $section_id = $section['id'];

            // Adhérents de moins de 25 ans dans cette section
            $stats[self::AGE_UNDER_25][$section_key] = $this->count_adherents_by_age_and_section(
                $year, $section_id, 'under_25', $date_25, $date_60
            );

            // Adhérents de 25 à 59 ans dans cette section
            $stats[self::AGE_25_TO_59][$section_key] = $this->count_adherents_by_age_and_section(
                $year, $section_id, '25_to_59', $date_25, $date_60
            );

            // Adhérents de 60 ans et plus dans cette section
            $stats[self::AGE_60_AND_OVER][$section_key] = $this->count_adherents_by_age_and_section(
                $year, $section_id, '60_and_over', $date_25, $date_60
            );

            // Total pour cette section
            $stats['total'][$section_key] = $stats[self::AGE_UNDER_25][$section_key]
                + $stats[self::AGE_25_TO_59][$section_key]
                + $stats[self::AGE_60_AND_OVER][$section_key];
        }

        // Calculer les totaux club (dédoublonnés - chaque membre compté une seule fois)
        $stats[self::AGE_UNDER_25]['club_total'] = $this->count_adherents_by_age_club_total(
            $year, 'under_25', $date_25, $date_60
        );
        $stats[self::AGE_25_TO_59]['club_total'] = $this->count_adherents_by_age_club_total(
            $year, '25_to_59', $date_25, $date_60
        );
        $stats[self::AGE_60_AND_OVER]['club_total'] = $this->count_adherents_by_age_club_total(
            $year, '60_and_over', $date_25, $date_60
        );
        $stats['total']['club_total'] = $stats[self::AGE_UNDER_25]['club_total']
            + $stats[self::AGE_25_TO_59]['club_total']
            + $stats[self::AGE_60_AND_OVER]['club_total'];

        return array(
            'sections' => $sections,
            'stats' => $stats,
            'year' => $year
        );
    }

    /**
     * Compte les adhérents par classe d'âge pour une section donnée
     *
     * @param int $year L'année
     * @param int $section_id L'ID de la section
     * @param string $age_group Le groupe d'âge ('under_25', '25_to_59', '60_and_over')
     * @param string $date_25 Date limite pour moins de 25 ans
     * @param string $date_60 Date limite pour 60 ans et plus
     * @return int Le nombre d'adhérents
     */
    private function count_adherents_by_age_and_section($year, $section_id, $age_group, $date_25, $date_60) {
        $this->db->distinct();
        $this->db->select('membres.mlogin');
        $this->db->from('membres');
        $this->db->join('licences', 'membres.mlogin = licences.pilote', 'inner');
        $this->db->join('comptes', 'membres.mlogin = comptes.pilote', 'inner');
        $this->db->where('licences.type', self::LICENCE_TYPE_COTISATION);
        $this->db->where('licences.year', $year);
        $this->db->where('comptes.codec', '411');
        $this->db->where('comptes.club', $section_id);

        // Filtrer par classe d'âge
        $this->apply_age_filter($age_group, $date_25, $date_60);

        return $this->db->count_all_results();
    }

    /**
     * Compte les adhérents par classe d'âge pour tout le club (dédoublonnés)
     *
     * @param int $year L'année
     * @param string $age_group Le groupe d'âge
     * @param string $date_25 Date limite pour moins de 25 ans
     * @param string $date_60 Date limite pour 60 ans et plus
     * @return int Le nombre d'adhérents
     */
    private function count_adherents_by_age_club_total($year, $age_group, $date_25, $date_60) {
        $this->db->distinct();
        $this->db->select('membres.mlogin');
        $this->db->from('membres');
        $this->db->join('licences', 'membres.mlogin = licences.pilote', 'inner');
        $this->db->where('licences.type', self::LICENCE_TYPE_COTISATION);
        $this->db->where('licences.year', $year);

        // Filtrer par classe d'âge
        $this->apply_age_filter($age_group, $date_25, $date_60);

        return $this->db->count_all_results();
    }

    /**
     * Applique le filtre d'âge à la requête en cours
     *
     * @param string $age_group Le groupe d'âge
     * @param string $date_25 Date limite pour moins de 25 ans
     * @param string $date_60 Date limite pour 60 ans et plus
     */
    private function apply_age_filter($age_group, $date_25, $date_60) {
        switch ($age_group) {
            case 'under_25':
                // Né après le 1er janvier de (année - 25) = moins de 25 ans au 1er janvier
                $this->db->where('membres.mdaten >', $date_25);
                break;
            case '25_to_59':
                // Né entre les deux dates
                $this->db->where('membres.mdaten <=', $date_25);
                $this->db->where('membres.mdaten >', $date_60);
                break;
            case '60_and_over':
                // Né avant ou le 1er janvier de (année - 60) = 60 ans et plus au 1er janvier
                $this->db->where('membres.mdaten <=', $date_60);
                break;
        }
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
