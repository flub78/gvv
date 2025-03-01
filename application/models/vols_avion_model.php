<?php

/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package models
 */
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');
$CI->load->model('sections_model');

/**
 * Modèle vols avion
 *
 * C'est un CRUD de base, la seule chose que fait cette classe
 * est de définir le nom de la table. Tous les méthodes sont
 * implémentés dans Common_Model
 */
class Vols_avion_model extends Common_Model {
    public $table = 'volsa';
    protected $primary_key = 'vaid';

    /**
     * Retourne le total des heures
     *
     * @param array $where
     *            Tableau associatif permettant de définir des conditions
     * @return integer La somme du champ satisfaisant la condition
     */
    public function sum($field, $where = array(), $selection = array()) {
        $where2 = 'volsa.vapilid = membres.mlogin and volsa.vamacid = machinesa.macimmat';

        $this->db->select("MIN('vaduree'), MIN('mdaten')")
            ->from('volsa, membres, machinesa')
            ->select_sum($field)
            ->where($where2)
            ->where($where)
            ->where($selection);
        // select per section
        if ($this->section) {
            $this->db->where('volsa.club', $this->section_id);
        }

        $res = $this->db->get();

        gvv_debug("sql: sum hours avion: " . $this->db->last_query());
        if ($this->db->_error_number()) {
            gvv_debug("sql: error: " .  $this->db->_error_number() . " - " . $this->db->_error_message());
        }
        if ($res) {
            return $res->row()->$field;
        } else {
            return 0;
        }
        return $row->$field;
    }

    /**
     * Retourne le nombre de lignes
     *
     * @param array $where
     *            Tableau associatif permettant de définir des conditions
     * @return integer Le nombre de lignes satisfaisant la condition
     */
    public function count($where = array(), $selection = array()) {
        $where2 = 'volsa.vapilid = membres.mlogin and volsa.vamacid = machinesa.macimmat';

        $this->db->select('vaduree, mdaten')
            ->from('volsa, membres, machinesa')
            ->where($where2)
            ->where($where);
        // select per section
        if ($this->section) {
            $this->db->where('volsa.club', $this->section_id);
        }
        $count = $this->db->count_all_results();

        return $count;
    }

    /**
     * return the latest value for the horameter
     */
    public function latest_horametre($where = array()) {
        $this->db
            ->select('vacfin')
            ->from('volsa')
            ->where($where)
            ->order_by('vacfin', 'desc');
        // select per section
        if ($this->section) {
            $this->db->where('volsa.club', $this->section_id);
        }
        $row = $this->db->limit(1)->get()->row();

        gvv_debug("sql: " . $this->db->last_query());
        return isset($row->vacfin) ? $row->vacfin : 0;
    }

    /**
     * Retourne le dernier vol de l'année
     *
     * @return objet La liste
     */
    public function latest_flight($where = array(), $order = "desc") {
        $this->db
            ->select('vaid, vadate, vacdeb, vacfin, vamacid, vapilid, year(vadate) as year, vahdeb, vahfin, vaobs')
            ->from('volsa')
            ->order_by("vadate $order, vacfin $order");
        // select per section
        if ($this->section) {
            $this->db->where('volsa.club', $this->section_id);
        }
        $result = $this->db->limit(1)->get()->result_array();

        return $result;
    }

    /**
     * Retourne le tableau tableau utilisé pour l'affichage par page
     *
     * @return objet La liste
     */
    public function select_page($year, $nb = 1000, $debut = 0, $selection = array(), $order = 'desc') {
        $this->load->model('membres_model');

        $date25 = date_m25ans($year);
        $where = "volsa.vapilid = membres.mlogin and volsa.vamacid = machinesa.macimmat";

        $select = 'vaid, vadate, vapilid, vamacid, vacdeb, vacfin, vaduree, vaatt, vaobs, vainst as instructeur, valieudeco, volsa.club';
        $select .= ', concat(mprenom," ", mnom) as pilote, vacategorie, vadc, maprive as prive';
        $select .= ", facture, mdaten, (mdaten > \"$date25\") as m25ans, payeur, essence, reappro";

        $from = 'volsa, membres, machinesa';

        // echo "select $select from $from where $where and $selection" . br(); exit;

        $this->db
            ->select($select, FALSE)
            ->from($from)
            ->where($where)
            ->where($selection)
            ->order_by("vadate $order, vacdeb $order");
        // select per section
        if ($this->section) {
            $this->db->where('volsa.club', $this->section_id);
        }
        $result = $this->db->get()->result_array();

        gvv_debug("sql: " . $this->db->last_query());

        foreach ($result as $key => $row) {
            // var_dump($row);
            $kid = $this->primary_key;
            $image = $this->image($row[$kid], TRUE);
            $result[$key]['image'] = "le vol " . $image;
            $result[$key]['instructeur'] = $this->membres_model->image($row['instructeur'], true);

            $section = $this->sections_model->get_by_id('id', $row['club']);
            if ($section) {
                $result[$key]['section_name'] = $section['nom'];
            } else {
                $result[$key]['section_name'] = '';
            }
        }

        $this->gvvmetadata->store_table("vue_vols_avion", $result);
        return $result;
    }

    /**
     * Retourne le tableau des consomations.
     * Les consomations doivent être calcullées sur
     * l'ensemble de la selection, pas seulement sur les pages affichées
     *
     * @param unknown_type $selection
     */
    public function conso($year, $selection = array()) {
        $conso = array();
        // On extrait la selection complète
        $select_result = $this->select_page($year, 1000000000, 0, $selection, "asc");

        // Calcul des infos par machine sur tout l'intervalle
        foreach ($select_result as $vol) {
            $machine = $vol['vamacid'];
            $essence = $vol['essence'];
            $debut = $vol['vacdeb'];
            $fin = $vol['vacfin'];
            $reappro = $vol['reappro'];

            // aucun = 0, avant = 1, après = 2
            $avant = 1;
            $apres = 2;

            // si pas d'essence on passe à la ligne suivante
            if ($essence <= 0)
                continue;

            // Il y a un avitaillement
            // echo "machine=$machine, essence=$essence, debut=$debut, fin=$fin, reappro=$reappro" . br();

            if (! array_key_exists($machine, $conso)) {
                // creation de la machine
                $conso[$machine] = array(
                    'debut' => $debut,
                    'fin' => $fin,
                    'essence' => $essence,
                    'ess_avant' => 0
                );
                if ($reappro == $avant) {
                    $conso[$machine]['essence'] = 0;
                    $conso[$machine]['ess_avant'] = $essence;
                } else {
                    $conso[$machine]['essence'] = $essence;
                }
            }

            if ($fin > $conso[$machine]['fin']) {
                // étend l'interval par sa limite supérieure
                $conso[$machine]['fin'] = $fin;
                $conso[$machine]['essence'] += $essence;
            }
        }

        $conso_tab = array();
        $conso_tab[] = array(
            "Machine",
            "Temps",
            "Essence",
            "Moyenne"
        );
        foreach ($conso as $machine => $row) {
            $temps = $row['fin'] - $row['debut'];
            $essence = $row['essence'];
            $moyenne = $essence / $temps;
            $conso_tab[] = array(
                $machine,
                sprintf("%6.2f", $temps),
                $essence,
                sprintf("%6.2f", $moyenne)
            );
        }
        return $conso_tab;
    }

    /**
     * Retourne une chaine de caractère qui identifie une ligne de façon unique.
     * Cette chaine est utilisé dans les affichages.
     * Par défaut elle retourne la valeur de la clé, mais elle est conçue pour être
     * surchargée.
     */
    public function image($key) {
        if ($key == "")
            return "";
        $vals = $this->get_by_id('vaid', $key);
        if (
            array_key_exists('vamacid', $vals) &&
            array_key_exists('vacdeb', $vals) &&
            array_key_exists('vadate', $vals)
        ) {
            return date_db2ht($vals['vadate']) . " " . $vals['vacdeb']  . " " . $vals['vamacid'];
        } else {
            return "Vol inconnu $key";
        }
    }

    /**
     * select count(*) as count, sum(vpduree) as minutes, month(vpdate) as month
     * from volsp group by month;
     */
    public function monthly_sum($group_by = '', $where = array(), $selection = array()) {

        $this->db
            ->select('count(*) as count, year(vadate) as current_year, month(vadate) as month, mdaten, msexe, vacategorie, vadc, vamacid')
            ->from('volsa, membres')
            ->select_sum('vaduree', 'centiemes')
            ->where($where)
            ->where($selection)
            ->where('volsa.vapilid = membres.mlogin');

        // select per section
        if ($this->section) {
            $this->db->where('volsa.club', $this->section_id);
        }

        $select = $this->db->group_by($group_by)->get()->result_array();

        $query = $this->db->last_query();

        return $select;
    }

    /**
     * select count(*) as count, sum(vpduree) as minutes, month(vpdate) as month
     * from volsp group by month;
     */
    public function line_monthly($type = 'count', $where = array(), $percent = array()) {
        $what = 'count(*) as count, year(vadate) as current_year, month(vadate) as month, mdaten, msexe, vacategorie, vadc, vamacid';

        $this->db->select($what)
            ->from('volsa, membres')
            ->select_sum('vaduree', 'centiemes')
            ->where($where)
            ->where('volsa.vapilid = membres.mlogin');
        // select per section
        if ($this->section) {
            $this->db->where('volsa.club', $this->section_id);
        }
        $db_res = $this->db->get();
        $total = $this->get_to_array($db_res);

        $this->db->select($what)
            ->from('volsa, membres')
            ->select_sum('vaduree', 'centiemes')
            ->where($where)
            ->where('volsa.vapilid = membres.mlogin');
        // select per section
        if ($this->section) {
            $this->db->where('volsa.club', $this->section_id);
        }
        $db_res = $this->db->group_by('month')
            ->get();
        $per_month = $this->get_to_array($db_res);


        $res = array(
            ''
        );
        $res[] = $total[0][$type];
        for ($i = 1; $i <= 12; $i++) {
            $res[] = '';
        }
        foreach ($per_month as $row) {
            $month = $row['month'];
            $res[$month + 1] = $row[$type];
        }

        if ($percent) {
            for ($i = 1; $i <= 12; $i++) {
                if ($percent[$i]) {
                    $res[$i] = (int) ($res[$i] * 1000 / $percent[$i]) / 10;
                } else {
                    $res[$i] = '';
                }
                if (abs($res[$i]) < 0.00001)
                    $res[$i] = '';
            }
        }

        array_shift($res);
        return $res;
    }

    /**
     * Retourne un hash qui peut-être utilisé dans un menu drow-down
     *
     * @param $where selection
     * @param $order ordre
     *            de tri
     */
    public function selector($where = array(), $order = "asc", $filter_section = false) {
        $key = $this->primary_key;

        $this->db
            ->select($key)
            ->from($this->table)
            ->where($where);
        // select per section
        if ($this->section && $filter_section) {
            $this->db->where('volsa.club', $this->section_id);
        }
        $allkeys = $this->db->order_by("vadate $order, vacdeb $order")
            ->get()->result_array();

        $result = array();
        foreach ($allkeys as $row) {
            $value = $row[$key];
            $result[$value] = $this->image($value);
        }
        return $result;
    }

    /*
     * Facture le vol
     *
     * @param $vol
     */
    public function facture($vol) {
        // Active la facturation
        $this->load->library("Facturation", '', 'facturation_generique');
        $club = $this->config->item('club');
        if ($club) {
            $facturation_module = "Facturation_" . $club;
            $this->load->library($facturation_module, '', "facturation_club");
            $data['logs'] = $this->facturation_club->facture_vol_avion($vol);
        } else {
            $data['logs'] = $this->facturation_generique->facture_vol_avion($vol);
        }
    }

    /*
     * Supprime les elements de facturation du vol
     *
     * @param $id identifiant du vol
     */
    public function delete_facture($id) {
        $this->load->model('achats_model');
        $this->achats_model->delete(array(
            'vol_avion' => $id
        ));
    }

    /**
     * Ajoute un vol avion
     *
     * @param
     *            hash des valeurs
     */
    public function create($data) {
        unset($data['vaid']);
        if ($this->db->insert($this->table, $data)) {
            $id = $this->db->insert_id();
            $data['vaid'] = $id;
            /*
             * var_dump($data);
             * array
             * 'vaid' => string '0' (length=1)
             * 'vadate' => string '2012-03-26' (length=10)
             * 'vapilid' => string 'fpeignot' (length=8)
             * 'vamacid' => string 'F-BLIT' (length=6)
             * 'vacdeb' => string '840.00' (length=6)
             * 'vacfin' => string '841' (length=3)
             * 'vaduree' => string '1' (length=1)
             * 'vaobs' => string '' (length=0)
             * 'vadc' => boolean false
             * 'vacategorie' => boolean false
             * 'vanumvi' => string '' (length=0)
             * 'vanbpax' => string '' (length=0)
             * 'vaprixvol' => boolean false
             * 'vainst' => string '' (length=0)
             * 'valieudeco' => string '' (length=0)
             * 'valieuatt' => string '' (length=0)
             * 'facture' => boolean false
             * 'payeur' => boolean false
             * 'pourcentage' => boolean false
             * 'club' => boolean false
             * 'gel' => boolean false
             * 'saisie_par' => string 'fpeignot' (length=8)
             * 'vaatt' => string '1' (length=1)
             * 'local' => string '0' (length=1)
             * 'nuit' => boolean false
             * 'reappro' => string '0' (length=1)
             * 'essence' => string '0' (length=1)
             */
            $this->facture($data);

            return $id;
        } else {
            gvv_error("sql error: " . $this->db->_error_message());

            return FALSE;
        }
    }

    /**
     * Mise à jour vol avion
     *
     * @param integer $id
     *            $id de l'élément
     * @param string $data
     *            donnée à remplacer
     * @return bool Le résultat de la requête
     */
    public function update($keyid, $data, $keyvalue = '') {
        // detruit les lignes d'achat correspondante
        $this->delete_facture($data[$keyid]);

        // MAJ du vol
        $keyvalue = $data[$keyid];
        $this->db->where($keyid, $keyvalue);
        $this->db->update($this->table, $data);

        // Nouvelle facturation
        $this->facture($data);
    }

    /**
     * delete
     *
     * @param unknown_type $data
     */
    function delete($where = array()) {

        // detruit les lignes d'achat correspondante
        $selection = $this->select_all($where);
        foreach ($selection as $row) {
            $this->delete_facture($row['vaid']);
        }
        // Detruit le vol
        $this->db->delete($this->table, $where);
    }

    /**
     * Generate a json string containing the hours per month
     *
     * @param unknown_type $year
     */
    public function cumul_heures($year, $first_year) {
        $json = "[";
        $y = $first_year;
        while ($y <= $year) {
            $yearly_res = $this->monthly_sum('month', array(
                'year(vadate)' => $y
            ));

            $partial_json = "[";

            $total = 0;
            foreach ($yearly_res as $month_values) {
                $month = $month_values['month'];
                $hours = $month_values['centiemes'];

                $total += $hours;
                if (strlen($partial_json) > 2) {
                    $partial_json .= ", ";
                }
                $partial_json .= "[$month, $total]";
            }

            $partial_json .= "]";

            $json .= $partial_json;
            if ($y != $year) {
                $json .= ", ";
            }
            $y++;
        }
        $json .= "]";

        return $json;
    }

    /*
     * Retourne les vols
     */
    function get($where = array()) {

        // $selection = $this->select_all($where);

        $selection = $this->db
            ->from($this->table)
            ->where($where);
        // select per section
        if ($this->section) {
            $this->db->where('volsa.club', $this->section_id);
        }
        $selection = $this->db->get()->result_array();

        foreach ($selection as $row) {
        }
        return $selection;
    }
}

/* End of file vols_avion_model.php */
/* Location: ./application/models/vols_avion_model.php */
