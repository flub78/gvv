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
        $self = $this;
        return $this->repartition($adherents, $sections, $keys, function ($member) use ($self, $year, $classification) {
            return $self->$classification($self->age_au_1er_janvier($member['mdaten'], $year));
        });
    }

    /**
     * Répartition des adhérents par sexe, par section et pour le club.
     *
     * @param array $adherents
     * @param array $sections
     * @return array Même structure que repartition_par_age()
     */
    public function repartition_par_sexe($adherents, $sections) {
        $self = $this;
        return $this->repartition($adherents, $sections, $this->cles_sexes(), function ($member) use ($self) {
            return $self->sexe($member['msexe']);
        });
    }

    /**
     * @return array Clés de sexe : sex_M, sex_F, sex_unknown
     */
    public function cles_sexes() {
        return array('sex_M', 'sex_F', 'sex_unknown');
    }

    /**
     * @param string|null $msexe
     * @return string Clé de sexe
     */
    public function sexe($msexe) {
        return in_array($msexe, array('M', 'F'), true) ? 'sex_' . $msexe : 'sex_unknown';
    }

    /**
     * Pyramide des âges du club : effectif par tranche de 10 ans et par sexe.
     *
     * @param array $adherents
     * @param int $year
     * @return array [tranche => [sex_M => n, sex_F => n, sex_unknown => n]], tranches dans l'ordre croissant, âge inconnu en dernier
     */
    public function pyramide_des_ages($adherents, $year) {
        $result = array();
        foreach ($this->cles_tranches_10_ans() as $tranche) {
            $result[$tranche] = array_fill_keys($this->cles_sexes(), 0);
        }
        foreach ($adherents as $member) {
            $tranche = $this->tranche_10_ans($this->age_au_1er_janvier($member['mdaten'], $year));
            $result[$tranche][$this->sexe($member['msexe'])]++;
        }
        return $result;
    }

    /**
     * Indicateurs synthétiques par section et pour le club.
     *
     * L'âge moyen et l'âge médian sont calculés sur les âges connus ;
     * ils valent null quand aucun âge n'est connu.
     *
     * @param array $adherents
     * @param array $sections
     * @param int $year
     * @return array [colonne => [effectif, age_moyen, age_median, age_inconnu]]
     */
    public function indicateurs($adherents, $sections, $year) {
        $ages = array_fill_keys($this->colonnes($sections), array());
        $result = array();
        foreach (array_keys($ages) as $column) {
            $result[$column] = array('effectif' => 0, 'age_moyen' => null, 'age_median' => null, 'age_inconnu' => 0);
        }
        foreach ($adherents as $member) {
            $age = $this->age_au_1er_janvier($member['mdaten'], $year);
            foreach ($this->colonnes_du_membre($member, $result) as $column) {
                $result[$column]['effectif']++;
                if ($age === null) {
                    $result[$column]['age_inconnu']++;
                } else {
                    $ages[$column][] = $age;
                }
            }
        }
        foreach ($ages as $column => $values) {
            $result[$column]['age_moyen'] = $this->moyenne($values);
            $result[$column]['age_median'] = $this->mediane($values);
        }
        return $result;
    }

    /**
     * Évolution de l'effectif du club sur les dernières années ayant des cotisations.
     *
     * @param array $members Tous les membres, avec leurs années de cotisation
     * @param int $year Dernière année incluse
     * @param int $nb_years Nombre maximal d'années
     * @return array [année => [effectif, age_moyen, under_25, 25_to_59, 60_and_over, unknown]], années croissantes
     */
    public function evolution($members, $year, $nb_years = 10) {
        $years = array();
        foreach ($members as $member) {
            foreach ($member['years'] as $y) {
                if ($y <= $year) {
                    $years[$y] = true;
                }
            }
        }
        $years = array_keys($years);
        sort($years);
        $years = array_slice($years, -$nb_years);

        $result = array();
        foreach ($years as $y) {
            $row = array_merge(array('effectif' => 0, 'age_moyen' => null), array_fill_keys($this->cles_classes_reglementaires(), 0));
            $ages = array();
            foreach ($this->adherents_de_l_annee($members, $y) as $member) {
                $age = $this->age_au_1er_janvier($member['mdaten'], $y);
                $row['effectif']++;
                $row[$this->classe_reglementaire($age)]++;
                if ($age !== null) {
                    $ages[] = $age;
                }
            }
            $row['age_moyen'] = $this->moyenne($ages);
            $result[$y] = $row;
        }
        return $result;
    }

    /**
     * @param array $values
     * @return float|null Moyenne arrondie à une décimale, null si vide
     */
    public function moyenne($values) {
        return $values ? round(array_sum($values) / count($values), 1) : null;
    }

    /**
     * @param array $values
     * @return float|null Médiane (moyenne des deux valeurs centrales pour un effectif pair), null si vide
     */
    public function mediane($values) {
        $n = count($values);
        if ($n == 0) {
            return null;
        }
        sort($values);
        $middle = intdiv($n, 2);
        return ($n % 2) ? (float) $values[$middle] : ($values[$middle - 1] + $values[$middle]) / 2.0;
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

    /**
     * Répartition générique : une ligne par clé, une colonne par section + club_total.
     */
    private function repartition($adherents, $sections, $keys, $classify) {
        $columns = $this->colonnes($sections);
        $counts = array();
        foreach ($keys as $key) {
            $counts[$key] = array_fill_keys($columns, 0);
        }
        $total = array_fill_keys($columns, 0);

        foreach ($adherents as $member) {
            $key = $classify($member);
            foreach ($this->colonnes_du_membre($member, $total) as $column) {
                $counts[$key][$column]++;
                $total[$column]++;
            }
        }

        return array('counts' => $counts, 'total' => $total);
    }

    private function colonnes($sections) {
        $columns = array();
        foreach ($sections as $section) {
            $columns[] = 'section_' . $section['id'];
        }
        $columns[] = 'club_total';
        return $columns;
    }

    /**
     * Colonnes où le membre est compté : club_total et chaque section existante où il a un compte.
     */
    private function colonnes_du_membre($member, $existing_columns) {
        $columns = array('club_total');
        foreach ($member['sections'] as $section_id) {
            if (isset($existing_columns['section_' . $section_id])) {
                $columns[] = 'section_' . $section_id;
            }
        }
        return $columns;
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
