<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

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
 * Calculs des statistiques adhérents.
 *
 * Fonctions pures sur les tableaux fournis par Adherents_report_model::get_adherents_data() :
 * aucun accès base de données ni dépendance CodeIgniter.
 *
 * Un membre est un tableau associatif contenant au moins :
 *   mlogin, mnom, mprenom, mdaten, years (années de cotisation), sections (ids de section)
 *
 * @see doc/design_notes/statistiques_adherents.md
 * @see doc/prds/statistiques_adherents_prd.md
 */
class Adherents_stats {

    const UNKNOWN = 'unknown';

    /** Classes d'âge réglementaires (FFVV), clé => âge minimum */
    private static $classes_reglementaires = array(
        'under_25' => 0,
        '25_to_59' => 25,
        '60_and_over' => 60,
    );

    /** Tranches de 10 ans, clé => âge minimum */
    private static $tranches_10_ans = array(
        'lt_20' => 0,
        '20_29' => 20,
        '30_39' => 30,
        '40_49' => 40,
        '50_59' => 50,
        '60_69' => 60,
        '70_79' => 70,
        '80_plus' => 80,
    );

    /**
     * Âge révolu au 1er janvier de l'année, ou null si la date de naissance
     * est absente, invalide, antérieure à 1900 ou postérieure au 1er janvier.
     *
     * @param string|null $mdaten Date de naissance (Y-m-d)
     * @param int $year
     * @return int|null
     */
    public function age_au_1er_janvier($mdaten, $year) {
        if (empty($mdaten) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $mdaten, $m)) {
            return null;
        }
        $by = (int) $m[1];
        $bm = (int) $m[2];
        $bd = (int) $m[3];
        if ($by < 1900 || !checkdate($bm, $bd, $by)) {
            return null;
        }
        if ($mdaten > sprintf('%04d-01-01', $year)) {
            return null;
        }
        // Seule une naissance un 1er janvier donne un anniversaire déjà atteint au 1er janvier
        return $year - $by - (($bm == 1 && $bd == 1) ? 0 : 1);
    }

    /**
     * @return array Clés des classes réglementaires, suivies de 'unknown'
     */
    public function cles_classes_reglementaires() {
        return array_merge(array_keys(self::$classes_reglementaires), array(self::UNKNOWN));
    }

    /**
     * @return array Clés des tranches de 10 ans, suivies de 'unknown'
     */
    public function cles_tranches_10_ans() {
        return array_merge(array_keys(self::$tranches_10_ans), array(self::UNKNOWN));
    }

    /**
     * @param int|null $age
     * @return string Clé de classe réglementaire
     */
    public function classe_reglementaire($age) {
        return $this->classer($age, self::$classes_reglementaires);
    }

    /**
     * @param int|null $age
     * @return string Clé de tranche de 10 ans
     */
    public function tranche_10_ans($age) {
        return $this->classer($age, self::$tranches_10_ans);
    }

    /**
     * Membres ayant cotisé pour l'année.
     *
     * @param array $members
     * @param int $year
     * @return array
     */
    public function adherents_de_l_annee($members, $year) {
        return array_filter($members, function ($member) use ($year) {
            return in_array((int) $year, $member['years']);
        });
    }

    /**
     * Répartition des adhérents par section et pour le club selon une classification.
     *
     * Les colonnes sont 'section_<id>' pour chaque section et 'club_total'.
     * Un membre est compté dans chaque section où il est rattaché, et une seule
     * fois dans le total club.
     *
     * @param array $adherents Adhérents de l'année
     * @param array $sections Liste des sections (id, nom)
     * @param int $year
     * @param string $classification 'classe_reglementaire' ou 'tranche_10_ans'
     * @return array array('counts' => [clé => [colonne => n]], 'total' => [colonne => n])
     */
    public function repartition_par_age($adherents, $sections, $year, $classification) {
        $keys = ($classification == 'tranche_10_ans')
            ? $this->cles_tranches_10_ans()
            : $this->cles_classes_reglementaires();

        $columns = array();
        foreach ($sections as $section) {
            $columns[] = 'section_' . $section['id'];
        }
        $columns[] = 'club_total';

        $counts = array();
        foreach ($keys as $key) {
            $counts[$key] = array_fill_keys($columns, 0);
        }
        $total = array_fill_keys($columns, 0);

        foreach ($adherents as $member) {
            $key = $this->$classification($this->age_au_1er_janvier($member['mdaten'], $year));
            $member_columns = array('club_total');
            foreach ($member['sections'] as $section_id) {
                if (isset($total['section_' . $section_id])) {
                    $member_columns[] = 'section_' . $section_id;
                }
            }
            foreach ($member_columns as $column) {
                $counts[$key][$column]++;
                $total[$column]++;
            }
        }

        return array('counts' => $counts, 'total' => $total);
    }

    /**
     * Adhérents dont l'âge est inconnu, triés par nom et prénom.
     *
     * @param array $adherents
     * @param int $year
     * @return array
     */
    public function adherents_age_inconnu($adherents, $year) {
        $result = array();
        foreach ($adherents as $member) {
            if ($this->age_au_1er_janvier($member['mdaten'], $year) === null) {
                $result[] = $member;
            }
        }
        usort($result, function ($a, $b) {
            return strcasecmp($a['mnom'] . ' ' . $a['mprenom'], $b['mnom'] . ' ' . $b['mprenom']);
        });
        return $result;
    }

    /**
     * Pourcentage arrondi à l'entier, 0 si le total est nul.
     *
     * @param int $count
     * @param int $total
     * @return int
     */
    public function pourcentage($count, $total) {
        return $total ? (int) round(100 * $count / $total) : 0;
    }

    private function classer($age, $bornes) {
        if ($age === null) {
            return self::UNKNOWN;
        }
        $result = self::UNKNOWN;
        foreach ($bornes as $key => $min) {
            if ($age >= $min) {
                $result = $key;
            }
        }
        return $result;
    }
}
