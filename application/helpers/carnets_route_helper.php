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
 * Construit la liste de lignes pour l'affichage du contrôle de continuité
 * d'un carnet de route.
 *
 * Chaque vol reçoit un champ 'status' :
 *   - 'ok'      : continuité exacte sur toutes les transitions adjacentes
 *   - 'error'   : au moins une transition adjacente est un écart ou recouvrement
 *   - 'missing' : l'horamètre du vol est absent (vacdeb et vacfin à 0)
 *
 * Entre deux vols avec une transition anormale, une ligne intermédiaire est
 * insérée avec 'type' = 'gap', 'overlap' ou 'missing'.
 *
 * @param  array $flights  Vols triés chronologiquement (vadate ASC, vacdeb ASC),
 *                         chaque élément doit contenir 'vacdeb' et 'vacfin'.
 * @return array           Tableau plat de lignes, chacune avec :
 *                         - 'type'     : 'flight' | 'gap' | 'overlap' | 'missing'
 *                         - 'data'     : données du vol (null pour lignes intermédiaires)
 *                         - 'duration' : durée de l'anomalie en unités horamètre (lignes intermédiaires)
 */
function build_continuity_rows($flights) {
    if (empty($flights)) {
        return [];
    }

    $n = count($flights);

    // Calcul des transitions entre vols consécutifs
    $transitions = [];
    for ($i = 0; $i < $n - 1; $i++) {
        $curr_deb  = (float)$flights[$i]['vacdeb'];
        $curr_fin  = (float)$flights[$i]['vacfin'];
        $next_deb  = (float)$flights[$i + 1]['vacdeb'];
        $next_fin  = (float)$flights[$i + 1]['vacfin'];
        $curr_miss = ($curr_deb == 0.0 && $curr_fin == 0.0);
        $next_miss = ($next_deb == 0.0 && $next_fin == 0.0);

        if ($curr_miss || $next_miss) {
            $transitions[$i] = ['type' => 'missing', 'duration' => 0.0];
        } else {
            $delta = round($next_deb - $curr_fin, 2);
            if (abs($delta) < 0.005) {
                $transitions[$i] = ['type' => 'ok', 'duration' => 0.0];
            } elseif ($delta > 0) {
                $transitions[$i] = ['type' => 'gap', 'duration' => $delta];
            } else {
                $transitions[$i] = ['type' => 'overlap', 'duration' => abs($delta)];
            }
        }
    }

    // Construction du tableau de lignes
    $rows = [];
    for ($i = 0; $i < $n; $i++) {
        $flight    = $flights[$i];
        $is_missing = ((float)$flight['vacdeb'] == 0.0 && (float)$flight['vacfin'] == 0.0);

        if ($is_missing) {
            $status = 'missing';
        } else {
            $has_error = (isset($transitions[$i - 1]) && $transitions[$i - 1]['type'] !== 'ok')
                      || (isset($transitions[$i])     && $transitions[$i]['type']     !== 'ok');
            $status = $has_error ? 'error' : 'ok';
        }

        $flight['status'] = $status;
        $rows[] = ['type' => 'flight', 'data' => $flight, 'duration' => 0.0];

        // Ligne intermédiaire après ce vol si transition anormale
        if (isset($transitions[$i]) && $transitions[$i]['type'] !== 'ok') {
            $rows[] = [
                'type'     => $transitions[$i]['type'],
                'data'     => null,
                'duration' => $transitions[$i]['duration'],
            ];
        }
    }

    return $rows;
}

/**
 * Calcule le résumé des anomalies à partir des lignes de continuité.
 *
 * @param  array $rows  Résultat de build_continuity_rows()
 * @return array        ['gap' => int, 'overlap' => int, 'missing' => int]
 */
function compute_continuity_summary($rows) {
    $summary = ['gap' => 0, 'overlap' => 0, 'missing' => 0];
    foreach ($rows as $row) {
        if ($row['type'] !== 'flight' && isset($summary[$row['type']])) {
            $summary[$row['type']]++;
        }
    }
    return $summary;
}
