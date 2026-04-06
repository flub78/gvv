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
 * Vérifie et incrémente le compteur de soumissions pour une IP + endpoint.
 *
 * Purge aléatoire des entrées expirées (1 chance sur 100 par appel).
 *
 * @param  string $endpoint        Identifiant de l'endpoint (ex. 'vd_public_form')
 * @param  int    $max             Nombre maximum de tentatives dans la fenêtre
 * @param  int    $window_seconds  Durée de la fenêtre en secondes (défaut : 3600)
 * @return bool   TRUE si sous la limite, FALSE si dépassée
 */
if (!function_exists('check_rate_limit')) {
    function check_rate_limit($endpoint, $max = 10, $window_seconds = 3600) {
        $CI =& get_instance();

        // Ne jamais faire confiance à X-Forwarded-For côté client : source spoofable.
        $ip = $_SERVER['REMOTE_ADDR'];

        // Purge aléatoire des entrées expirées (1% de probabilité)
        if (mt_rand(1, 100) === 1) {
            $CI->db->query(
                'DELETE FROM public_rate_limit WHERE window_start < DATE_SUB(NOW(), INTERVAL ? SECOND)',
                array((int) $window_seconds)
            );
        }

        // Lecture de l'entrée existante
        $row = $CI->db
            ->where('ip', $ip)
            ->where('endpoint', $endpoint)
            ->get('public_rate_limit')
            ->row();

        $now = date('Y-m-d H:i:s');

        if (!$row) {
            // Première tentative : création de l'entrée
            $CI->db->insert('public_rate_limit', array(
                'ip'           => $ip,
                'endpoint'     => $endpoint,
                'attempts'     => 1,
                'window_start' => $now,
            ));
            return true;
        }

        // Vérifier si la fenêtre est expirée
        $window_start = strtotime($row->window_start);
        if ((time() - $window_start) >= $window_seconds) {
            // Fenêtre expirée : réinitialiser
            $CI->db
                ->where('ip', $ip)
                ->where('endpoint', $endpoint)
                ->update('public_rate_limit', array(
                    'attempts'     => 1,
                    'window_start' => $now,
                ));
            return true;
        }

        // Fenêtre active : vérifier la limite
        if ($row->attempts >= $max) {
            return false;
        }

        // Incrémenter le compteur
        $CI->db
            ->where('ip', $ip)
            ->where('endpoint', $endpoint)
            ->set('attempts', 'attempts + 1', false)
            ->update('public_rate_limit');

        return true;
    }
}
