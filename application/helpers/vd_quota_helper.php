<?php
/*
 * GVV Gestion vol à voile
 * Copyright (C) 2011 Philippe Boissel & Frédéric Peignot
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Retourne le statut du quota mensuel de vols de découverte pour une section.
 *
 * Fenêtre glissante de 30 jours. Seuls les bons non annulés (cancelled = 0)
 * sont comptabilisés, quelle que soit leur origine (public ou interne).
 *
 * @param  int $section_id  Identifiant de la section
 * @return array {
 *   quota       int   Quota configuré (0 = illimité)
 *   vendu       int   Bons vendus dans la fenêtre de 30 jours
 *   atteint     bool  TRUE si vendu >= quota > 0
 *   jours_reset int   Jours avant libération d'un slot (0 si non atteint)
 * }
 */
if (!function_exists('get_vd_quota_status')) {
    function get_vd_quota_status($section_id) {
        $CI =& get_instance();

        // Lecture du quota configuré
        $quota_raw = $CI->db
            ->select('param_value')
            ->where('plateforme', 'helloasso')
            ->where('club', (int) $section_id)
            ->where('param_key', 'vd_quota_mensuel')
            ->get('paiements_en_ligne_config')
            ->row();

        $quota = $quota_raw ? (int) $quota_raw->param_value : 0;

        if ($quota === 0) {
            return array(
                'quota'       => 0,
                'vendu'       => 0,
                'atteint'     => false,
                'jours_reset' => 0,
            );
        }

        // Comptage des bons dans la fenêtre glissante de 30 jours
        $count_row = $CI->db->query(
            'SELECT COUNT(*) AS cnt, MIN(date_vente) AS oldest
             FROM vols_decouverte
             WHERE club = ? AND cancelled = 0
               AND date_vente >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)',
            array((int) $section_id)
        )->row();

        $vendu  = $count_row ? (int) $count_row->cnt : 0;
        $atteint = ($vendu >= $quota);

        $jours_reset = 0;
        if ($atteint && $count_row && $count_row->oldest) {
            // Jours restants avant que le bon le plus ancien sorte de la fenêtre
            $diff = $CI->db->query(
                'SELECT 30 - DATEDIFF(CURDATE(), ?) AS jours',
                array($count_row->oldest)
            )->row();
            $jours_reset = $diff ? max(0, (int) $diff->jours) : 0;
            // Si 0, le slot se libère aujourd'hui — afficher "disponible demain"
            if ($jours_reset === 0) {
                $jours_reset = 1;
            }
        }

        return array(
            'quota'       => $quota,
            'vendu'       => $vendu,
            'atteint'     => $atteint,
            'jours_reset' => $jours_reset,
        );
    }
}

/**
 * Retourne toutes les sections ayant les vols de découverte par CB activés,
 * avec leur statut de quota et le nombre de jours avant réarmement.
 *
 * @return array  Liste de tableaux section, chacun contenant :
 *                id, nom, acronyme, quota_status (résultat de get_vd_quota_status)
 */
if (!function_exists('get_sections_vd_disponibles')) {
    function get_sections_vd_disponibles() {
        $CI =& get_instance();

        $sections = $CI->db
            ->select('id, nom, acronyme')
            ->where('has_vd_par_cb', 1)
            ->order_by('nom', 'ASC')
            ->get('sections')
            ->result_array();

        $today = date('Y-m-d');
        $result = array();
        foreach ($sections as $section) {
            // Ne retenir la section que si elle a au moins un produit VD actif
            $has_products = (bool) $CI->db->query(
                "SELECT 1 FROM tarifs
                 WHERE club = ? AND type_ticket = 1 AND public = 1
                   AND date <= ? AND (date_fin IS NULL OR date_fin >= ?)
                 LIMIT 1",
                array((int) $section['id'], $today, $today)
            )->num_rows();

            if (!$has_products) {
                continue;
            }

            $section['quota_status'] = get_vd_quota_status($section['id']);
            $result[] = $section;
        }

        return $result;
    }
}
