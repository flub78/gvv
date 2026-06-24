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

/**
 * Modèle pour le contrôle des carnets de route.
 *
 * Fournit les vols d'une machine sur une période, triés chronologiquement,
 * et la liste des avions de la section active.
 */
class Carnets_route_model extends Common_Model {
    public $table = 'volsa';
    protected $primary_key = 'vaid';

    /**
     * Retourne les vols d'une machine sur une période, triés chronologiquement.
     *
     * @param string $macid       Immatriculation de l'avion
     * @param string $date_debut  Date de début au format Y-m-d
     * @param string $date_fin    Date de fin au format Y-m-d
     * @return array
     */
    public function get_flights($macid, $date_debut, $date_fin) {
        $select = 'vaid, vadate, vapilid, vamacid, vacdeb, vacfin, vaduree,'
                . ' valieudeco, valieuatt, vaobs,'
                . " CONCAT(mprenom, ' ', mnom) AS pilote,"
                . ' machinesa.horametre_mode';

        $this->db
            ->select($select, FALSE)
            ->from('volsa')
            ->join('membres',   'volsa.vapilid = membres.mlogin',     'left')
            ->join('machinesa', 'volsa.vamacid = machinesa.macimmat', 'inner')
            ->where('volsa.vamacid', $macid)
            ->where('volsa.vadate >=', $date_debut)
            ->where('volsa.vadate <=', $date_fin);

        if ($this->section) {
            $this->db->where('volsa.club', $this->section_id);
        }

        $result = $this->db
            ->order_by('vadate ASC, vacdeb ASC')
            ->get()
            ->result_array();

        gvv_debug("carnets_route get_flights: " . $this->db->last_query());

        return $result;
    }

    /**
     * Retourne la liste des avions disponibles pour le filtre,
     * restreinte à la section active si une section est sélectionnée.
     *
     * @return array  Tableau associatif macimmat => macimmat pour le sélecteur
     */
    public function get_avions() {
        $this->db
            ->select('macimmat')
            ->from('machinesa')
            ->where('actif', 1);

        if ($this->section) {
            $this->db->where('club', $this->section_id);
        }

        $rows = $this->db
            ->order_by('macimmat ASC')
            ->get()
            ->result_array();

        $selector = array();
        foreach ($rows as $row) {
            $selector[$row['macimmat']] = $row['macimmat'];
        }
        return $selector;
    }
}
