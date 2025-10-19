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
 *
 * Modèle vols planeur
 */
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = & get_instance();
$CI->load->model('common_model');

/**
 *
 * Accès base pour les vols planeurs
 *
 * @author Frédéric
 *
 *
 */
class Vols_planeur_model extends Common_Model {
    public $table = 'volsp';
    protected $primary_key = 'vpid';

    /**
     * Retourne le tableau tableau utilisé pour l'affichage par page
     *
     * @param $nb taille
     *            de la page
     * @param $debut premier
     *            élément à afficher
     * @param $selection filtre
     * @param $order ordre
     *            de tri
     * @return objet La liste
     */
    public function select_page($year, $nb = 1000, $debut = 0, $selection = array (), $order = 'desc') {
        $this->load->model('membres_model');

        $date25 = date_m25ans($year);
        $where = "volsp.vppilid = membres.mlogin and volsp.vpmacid = machinesp.mpimmat";

        $select = 'vpid, vpdate, vppilid, vpmacid, vpcdeb, vpcfin, vpduree, vpobs, vpinst as instructeur';
        $select .= ', trigramme, pilote_remorqueur, vplieudeco, tempmoteur';
        $select .= ', concat(mprenom," ", mnom) as pilote, vpaltrem, vpcategorie, vpdc, vpnbkm, mpprive as prive';
        $select .= ", facture, mdaten, (mdaten > \"$date25\") as m25ans, payeur, vpautonome, YEAR(vpdate) as year";

        $db_res = $this->db->select($select, FALSE)
            ->from('volsp, membres, machinesp')
            ->where($where)->where($selection)
            ->limit($nb, $debut)
            ->order_by("vpdate $order, vpcdeb $order")
            ->get();
        
        gvv_debug("sql: page: " . $this->db->last_query());
        
        if ($db_res) {
            $result = $db_res->result_array();
        } else {
            $result = array();
            if ($this->db->_error_number()) {
                gvv_debug("sql: error: " .  $this->db->_error_number() . " - " . $this->db->_error_message());
            }
        }
    
        foreach ( $result as $key => $row ) {
            // var_dump($row);
            $kid = $this->primary_key;
            $image = $this->image($row [$kid], TRUE);
            $result [$key] ['image'] = $image;
            $result [$key] ['instructeur'] = $this->membres_model->image($row ['instructeur'], true);
            $result [$key] ['rem_id'] = $this->membres_model->image($row ['pilote_remorqueur'], true);

            $vpautonome = $result [$key] ['vpautonome'];
            if ($vpautonome == TREUIL) {
                $result [$key] ['vpaltrem'] = '';
            } elseif ($vpautonome == AUTONOME) {
                $result [$key] ['vpaltrem'] = $result [$key] ['tempmoteur'];
            }
        }

        // var_dump($result);exit;
        $this->gvvmetadata->store_table("vue_vols_planeur", $result);
        return $result;
    }
		
		
	/**
     * Retourne le tableau tableau contenant les données pour créer un fichier cvs pour Gesasso
     *
     * @param $where clause de selection pour la requête sql "cond1 and cond2 and cond3 ..."
	 *
	 * le temps de vol du remorqueur n'étant pas renseigné dans GVV mais nécessaire dans Gesasso,
	 * il est mis par défaut 7 minutes soit 12 centième d'heure ('12' as 'Temps moteur')
	 *
     * @return objet La liste
     */
    public function select_gesasso($where = "") {

				$sql = "SELECT 

DATE_FORMAT(vpdate, \"%d/%m/%Y\") as Date,

vpmacid as Aéronef, 

'' as 'Aéronef externe',

if (m.licfed is Null OR m.licfed=0,
		'Pilote externe',
		if (vpdc=1,if (i.licfed is Null,'',i.licfed),m.licfed)
		) as 'Individu 1',

if (m.licfed is Null OR m.licfed=0,'1','') as 'Individu 1 externe',

if (vpdc=1,m.licfed,'') as 'Individu 2',

'' as 'Individu 2 externe',
if (vpdc=1,'1','') as 'Vol d\'instruction',

replace(vpcdeb,'.',':') as 'Heure de décollage',

replace(vpcfin,'.',':') as 'Heure de l\'atterrissage',

'' as Durée,

vplieudeco as 'Code OACI du décollage',

vplieuatt as 'Code OACI de l\'atterrissage',

'1' as 'Nombre de décollage/atterrissage',

CASE
	WHEN vpautonome=1 THEN \"Treuil\"
	WHEN vpautonome=2 THEN \"Autonome\"
	WHEN vpautonome=3 THEN \"Remorquage\"
	WHEN vpautonome=4 THEN \"Remorquage\"
END
as 'Moyen de mise en l\'air',

CASE
	WHEN vpautonome=1 THEN \"\"
	WHEN vpautonome=2 THEN tempmoteur * 100
	WHEN vpautonome=3 THEN \"12\"
	WHEN vpautonome=4 THEN \"12\"
END
as 'Temps moteur',

'' as 'Temps moteur en h:m',

CASE
	WHEN vpautonome=1 THEN \"\"
	WHEN vpautonome=2 THEN \"\"
	WHEN vpautonome=3 THEN remorqueur
	WHEN vpautonome=4 THEN \"Remorqueur externe\"
END
as 'Immatriculation du remorqueur',

if (vpautonome=4,'1','') as 'Remorqueur externe',

CASE
	WHEN vpautonome=1 THEN \"\"
	WHEN vpautonome=2 THEN \"\"
	WHEN vpautonome=3 and pilote_remorqueur!='' THEN r.licfed
	WHEN vpautonome=3 and pilote_remorqueur='' THEN \"Pilote R externe\"
	WHEN vpautonome=4 THEN \"Pilote R externe\"
END
as 'Individu 1 dans le remorqueur',

CASE
	WHEN vpautonome=1 THEN \"\"
	WHEN vpautonome=2 THEN \"\"
	WHEN vpautonome=3 and pilote_remorqueur!='' THEN \"\"
	WHEN vpautonome=3 and pilote_remorqueur='' THEN \"1\"
	WHEN vpautonome=4 THEN \"1\"
END
as 'Individu 1 externe dans le remorqueur',

'' as 'Individu 2 dans le remorqueur',
'' as 'Individu 2 externe dans le remorqueur',
'' as 'Instruction Remorqueur',

if (vpautonome=1,'Treuil externe','') as Treuil,

if (vpautonome=1,'1','') as 'Treuil externe',

CASE
   WHEN vpautonome=1 and pilote_remorqueur!='' THEN r.licfed
   WHEN vpautonome=1 and pilote_remorqueur='' THEN \"Treuillard externe\"
END
as Treuilleur,

if (vpautonome=1 and pilote_remorqueur='','1','') as 'Treuilleur externe',

'' as 'Commentaire'

FROM `volsp` as v
LEFT JOIN `membres` m ON v.vppilid = m.mlogin
LEFT JOIN `membres` i ON v.vpinst = i.mlogin
LEFT JOIN `membres` r ON v.pilote_remorqueur = r.mlogin";

if ($where) {
	$sql .= "\n" . "WHERE $where";
}
		// gvv_debug("sql: gesasso: " . $sql . "\n"); exit;
		$query = $this->db->query($sql);
		$result = $query->result_array();

        gvv_debug("sql: " . $this->db->last_query());
				
        return $result;
    }
		
			
		
		

    /**
     * Retourne le nombre d'occurence
     *
     * @param array $where
     *            Tableau associatif permettant de définir des conditions
     * @return integer Le nombre de news satisfaisant la condition
     */
    public function count($selection = array (), $where2 = array()) {
        $where = "volsp.vppilid = membres.mlogin and volsp.vpmacid = machinesp.mpimmat  ";

        $select = 'vpid, vpdate, vppilid, vpmacid, vpcdeb, vpcfin, vpduree, vpobs, vpinst as instructeur';
        $select .= ', concat(mprenom," ", mnom) as pilote, vpaltrem, vpcategorie, vpdc, vpnbkm, mpprive as prive';
        $select .= ', facture, mdaten, payeur, vpautonome, YEAR(vpdate) as year';

        $count = $this->db->select($select, FALSE)->from('volsp, membres, machinesp')->where($where)->where($selection)->where($where2)->count_all_results();
        return $count;
    }

    /**
     * Retourne le tableau des vols à facturer
     *
     * @return objet La liste
     */
    public function a_facturer($id = '') {
        $this->load->model('membres_model');

        $where = "volsp.vppilid = membres.mlogin and volsp.vpmacid = machinesp.mpimmat";

        if ($id != '') {
            $where .= " and vpid = $id";
        } else {
            $where .= " and facture = 0";
        }

        $select = 'vpid, vpdate, vppilid, vpmacid, vpcdeb, vpcfin, vpduree, tempmoteur, vpobs, vpinst as instructeur, pilote_remorqueur';
        $select .= ', vpcategorie, vpautonome, vpaltrem, payeur, pourcentage, facture, remorqueur, vplieudeco, vpdc, vpticcolle, vpnumvi';
        $select .= ', concat(mprenom," ", mnom) as pilote, compte, mpprive as prive, mprix, mprix_forfait, mprix_moteur, mmax_facturation';

        $db_res = $this->db->select($select, FALSE)
            ->from('volsp, membres, machinesp')
            ->where($where)
            ->order_by('vpdate, vpcdeb')
            ->get();
        
        $result = $this->get_to_array($db_res);
        
        return $result;
    }

    /**
     * Retourne le dernier ou premier vol de l'année
     *
     * @return objet La liste
     */
    public function latest_flight($where = array(), $order = "desc") {
        $this->load->model('membres_model');

        $where = "volsp.vppilid = membres.mlogin and volsp.vpmacid = machinesp.mpimmat";

        $select = 'vpid, vpdate, vppilid, vpmacid, vpcdeb, vpcfin, vpduree, vpobs, vpinst as instructeur';
        $select .= ', YEAR(vpdate) as year';
        $select .= ', vpcategorie, vpautonome, vpaltrem, payeur, pourcentage, facture, remorqueur, vplieudeco, pilote_remorqueur';
        $select .= ', concat(mprenom," ", mnom) as pilote, compte, mpprive as prive, mprix, mprix_forfait, mmax_facturation';

        $db_res = $this->db->select($select, FALSE)
            ->from('volsp, membres, machinesp')
            ->where($where)
            ->order_by("vpdate $order, vpcdeb $order")
            ->limit(1)->get();
        
        $result = $this->get_to_array($db_res);
        
        return $result;
    }

    /**
     * Retourne la première année pour laquelle des vols sont enregistrés
     * Sinon retourne l'année courante
     */
    function first_year($whith_histo = false) {
        $first_flight = $this->latest_flight(array (), "asc");
        if (count($first_flight) < 1) {
            return date("Y");
        }
        $min = $first_flight [0] ['year'];

        if ($whith_histo) {
            $select = $this->historique_model->select_page(1000, 0, array ());
            foreach ( $select as $row ) {
                if ($row ['annee'] < $min) {
                    $min = $row ['annee'];
                }
            }
        }

        return $min;
    }

    /**
     * Retourne le total des heures
     *
     * @param array $where
     *            Tableau associatif permettant de définir des conditions
     * @return integer Le nombre de news satisfaisant la condition
     */
    public function sum($field, $selection = array (), $where2 = array()) {
        $where = "volsp.vppilid = membres.mlogin and volsp.vpmacid = machinesp.mpimmat  ";
        $what = "MAX('vpduree'), MAX('mdaten'), MAX('vpnbkm')";

        $res = $this->db->select($what)->from('volsp, membres, machinesp')->select_sum($field)->where($where)->where($selection)->where($where2)->get();
 
        gvv_debug("sql: sum hours: " . $this->db->last_query());

        if ($res) {
			return $res->row()->$field;
        } else {
            return 0;
        }
    }

    /**
     * select count(*) as count, sum(vpduree) as minutes, sum(vpnbkm) as kms, month(vpdate) as month
     * from volsp group by month;
     */
    public function monthly_sum($group_by = '', $where = array (), $selection = array ()) {
        $db_res = $this->db->select('count(*) as count, year(vpdate) as current_year, month(vpdate) as month, mdaten, msexe, vpcategorie, vpdc, vpmacid, vpautonome, mpprive')
            ->from('volsp, membres, machinesp')
            ->select_sum('vpduree', 'minutes')
            ->select_sum('vpnbkm', 'kms')
            ->where($where)->where($selection)
            ->where('volsp.vppilid = membres.mlogin')
            ->where("volsp.vpmacid = machinesp.mpimmat and machinesp.mpprive != '2' ")
            ->group_by($group_by)->get();
        
        $result = $this->get_to_array($db_res);

        return $result;
    }

    /**
     * select count(*) as count, sum(vpduree) as minutes, month(vpdate) as month
     * from volsp group by month;
     * 
     * $type = count : retourne le compte sinon le total
     * $where = selection database
     * $percent = total de toute la selection pour calculer le pourcentage
     */
    public function line_monthly($type = 'count', $where = array (), $percent = array ()) {
        $what = 'count(*) as count, year(vpdate) as current_year, month(vpdate) as month, mdaten, msexe, vpcategorie, vpdc, vpmacid, mpprive, tempmoteur';
        $from = 'volsp, membres, machinesp';

        /**
         * Recherche les totaux de vol sur la selection
         * Return in case of success
         * array (size=1)
  0 => 
    array (size=13)
      'count' => string '76' (length=2)
      'current_year' => string '2020' (length=4)
      'month' => string '1' (length=1)
      'mdaten' => string '1971-04-28' (length=10)
      'msexe' => string 'M' (length=1)
      'vpcategorie' => string '0' (length=1)
      'vpdc' => string '1' (length=1)
      'vpmacid' => string 'F-CJRG' (length=6)
      'mpprive' => string '0' (length=1)
      'tempmoteur' => string '0.00' (length=4)
      'minutes' => string '12975.00' (length=8)
      'kms' => string '10852' (length=5)
      'moteur' => string '0.00' (length=4)
         */
        $db_res = $this->db->select($what)->from($from)
            ->select_sum('vpduree', 'minutes')
            ->select_sum('vpnbkm', 'kms')
            ->select_sum('tempmoteur', 'moteur')
            ->where($where)->where('volsp.vppilid = membres.mlogin')
            ->where("volsp.vpmacid = machinesp.mpimmat  and machinesp.mpprive != '2' ")->get();
    
        $total = $this->get_to_array($db_res);
    
        /** 
         * Même chose groupé par mois
         * 
         */
        $db_res = $this->db->select($what)
            ->from($from)
            ->select_sum('vpduree', 'minutes')
            ->select_sum('vpnbkm', 'kms')
            ->select_sum('tempmoteur', 'moteur')
            ->where($where)->where('volsp.vppilid = membres.mlogin')
            ->where("volsp.vpmacid = machinesp.mpimmat  and machinesp.mpprive != '2' ")
            ->group_by('month')
            ->get();

        $per_month = $this->get_to_array($db_res);
            
        $res = array (
                ''
        );
        $res [] = $total [0] [$type];
        if ($type == 'minutes') {
            $min = $res [1];
            $hour = ( int ) ($res [1] / 60);
            $minute = $min - 60 * $hour;
            $res [1] = $hour + ($minute / 100);
            $res [1] = sprintf("%4.2f", $res [1]);
        }
        for($i = 1; $i <= 12; $i ++) {
            $res [] = '';
        }

        foreach ( $per_month as $row ) {
            $month = $row ['month'];
            $res [$month + 1] = $row [$type];
            if ($type == 'minutes') {
                $min = $res [$month + 1];
                $hour = ( int ) ($res [$month + 1] / 60);
                $minute = $min - 60 * $hour;
                $res [$month + 1] = $hour + ($minute / 100);
                $res [$month + 1] = sprintf("%4.2f", $res [$month + 1]);
            }
        }
        if ($percent) {
            for($i = 1; $i <= 13; $i ++) {
                if (isset($percent [$i-1]) and ($percent [$i-1] != 0)) {
                    $res [$i] = ( int ) (100 * floatval($res [$i]) / $percent [$i-1]);
                } else {
                    $res [$i] = '';
                }
                if (abs($res [$i]) < 0.00001)
                    $res [$i] = '';
            }
        }

        array_shift($res);
        return $res;
    }

    /*
     * Facture le vol
     *
     * @param $id identifiant du vol
     */
    public function facture($id) {
        // selectionne les vols à facturer (Il serait plus simple de facturer le vol lui même)
        $this->load->model('vols_planeur_model');
        gvv_debug("facture vol planeur $id");

        $vols = $this->vols_planeur_model->a_facturer($id);

        // Active la facturation
        $this->load->library("Facturation", '', 'facturation_generique');
        $club = $this->config->item('club');
        if ($club) {
            $facturation_module = "Facturation_" . $club;
            $this->load->library($facturation_module, '', "facturation_club");
            foreach ( $vols as $vol ) {
                $this->facturation_club->facture_vol_planeur($vol);
            }
        } else {
            foreach ( $vols as $vol ) {
                $this->facturation_generique->facture_vol_planeur($vol);
            }
        }
    }

    /*
     * Supprime les elements de facturation du vol
     */
    public function delete_facture($id) {
        $this->load->model('achats_model');
        $achats = $this->achats_model->delete(array (
                'vol_planeur' => $id
        ));
    }

    /**
     * Ajoute un vol planeur
     *
     * @param
     *            hash des valeurs
     */
    public function create($data) {
        
        // Special date validation under watir
        if ($this->config->item('watir')) {
            if (preg_match('/(\d{2,2})(\d{2,2})(\d{4,4})/', $data['vpdate'], $matches)) {
                $day = $matches [1];
                $month = $matches [2];
                $year = $matches [3];
                // force the date
                $data['vpdate'] = $year . '-' . $month . '-' . $day;
            }
        }
        
        gvv_debug("create vol planeur, data = " . var_export($data, true));

        if (isset($data ['vpid'])) {
            if ($data ['vpid'] == '0') {
                unset($data ['vpid']);
            }
        }

        // Si ce n'est pas un remorqué, efface le remorqueur
        if (isset($data ['vpautonome'])) {

            if ($data ['vpautonome'] != 3) {
                $data ['remorqueur'] = '';
            }
            if ($data ['vpautonome'] != 1 && $data ['vpautonome'] != 3) {
                $data ['pilote_remorqueur'] = '';
            }
        }

        if ($this->db->insert($this->table, $data)) {
            $id = $this->db->insert_id();

            $this->facture($id);

            return $id;
        } else {
            return FALSE;
        }
    }

    /**
     * Edite un element existant
     *
     * @param integer $id
     *            $id de l'élément
     * @param string $data
     *            donnée à remplacer
     * @return bool Le résultat de la requête
     */
    public function update($keyid, $data, $keyvalue = '') {
        gvv_debug("update vol planeur, keyid=$keyid, data = " . var_export($data, true));

        // detruit les lignes d'achat correspondante
        $this->delete_facture($data [$keyid]);

        // Si ce n'est pas un remorqué, efface le remorqueur
        if (isset($data ['vpautonome'])) {

            if ($data ['vpautonome'] != 3) {
                $data ['remorqueur'] = '';
            }
            if ($data ['vpautonome'] != 1 && $data ['vpautonome'] != 3) {
                $data ['pilote_remorqueur'] = '';
            }
        }

        // MAJ du vol
        $keyvalue = $data [$keyid];
        $this->db->where($keyid, $keyvalue);
        $this->db->update($this->table, $data);

        // Nouvelle facturation
        $this->facture($data [$keyid]);
    }

    /**
     * delete
     *
     * @param unknown_type $data
     */
    function delete($where = array ()) {

        // detruit les lignes d'achat correspondante
        $selection = $this->select_all($where);
        foreach ( $selection as $row ) {
            $this->delete_facture($row ['vpid']);
        }
        // Detruit le vol
        $this->db->delete($this->table, $where);
    }

    /**
     * Retourne une chaine de caractère qui identifie une ligne de façon unique.
     * Cette chaine est utilisé dans les affichages.
     * Par défaut elle retourne la valeur de la clé, mais elle est conçue pour être
     * surchargée.
     */
    public function image($key, $full = FALSE) {
        if ($key == "")
            return "";
        $vals = $this->get_by_id('vpid', $key);
        if (array_key_exists('vpmacid', $vals) && array_key_exists('vplieudeco', $vals) && array_key_exists('vpcdeb', $vals) && array_key_exists('vpcfin', $vals) && array_key_exists('vpdate', $vals)) {
            $res = date_db2ht($vals ['vpdate']) . ":" . $vals ['vpcdeb'] . " " . $vals ['vpmacid'];
            if ($full) {
                $res = "le vol " . $res;
            }
            return $res;
        } else {
            return "Vol inconnu $key";
        }
    }

    /**
     * Retourne un hash qui peut-être utilisé dans un menu drow-down
     *
     * @param $where selection
     * @param $order ordre de tri
     * @param $filter_section filter by section (inherited from Common_Model)
     */
    public function selector($where = array (), $order = "asc", $filter_section = false) {
        $key = $this->primary_key;

        $db_res = $this->db->select($key)
            ->from($this->table)
            ->where($where)
            ->order_by("vpdate $order, vpcdeb $order")
            ->get();

        $allkeys = $this->get_to_array($db_res);
        $result = array ();
        foreach ( $allkeys as $row ) {
            $value = $row [$key];
            $result [$value] = $this->image($value);
        }
        
        return $result;
    }

    /**
     * Generate a json string containing the hours per month
     *
     * @param unknown_type $year
     *            current year
     * @param int $first_year
     *            start year
     *
     */
    public function cumul_heures($year, $first_year) {
        $json = "[";
        $y = $first_year;
        while ( $y <= $year ) {
            $yearly_res = $this->monthly_sum('month', array (
                    'year(vpdate)' => $y
            ));

            $partial_json = "[";

            $total = 0;
            foreach ( $yearly_res as $month_values ) {
                $month = $month_values ['month'];
                $hours = $month_values ['minutes'] / 60;

                $total += $hours;
                if (strlen($partial_json) > 2) {
                    $partial_json .= ", ";
                }
                $rounded = round($total, 0);
                $partial_json .= "[$month, $rounded]";
            }

            $partial_json .= "]";

            $json .= $partial_json;
            if ($y != $year) {
                $json .= ", ";
            }
            $y ++;
        }
        $json .= "]";

        return $json;
    }

    /**
     * Génère le début de la courbe pour les cumul et age
     * des machines
     */
    private function historique_heures($machine, &$hs) {
        $select = $this->historique_model->select_page(1000, 0, array (
                'machine' => $machine
        ));
        $res = array ();
        foreach ( $select as $row ) {
            $hs += $row ['heures'];
            $res [] = array (
                    $row ['annee'],
                    $hs
            );
        }
        return $res;
    }

    /**
     * Génère le début de la courbe pour les cumul et age
     * des machines
     */
    private function historique_age($machine, $fabrication) {
        $select = $this->historique_model->select_page(1000, 0, array (
                'machine' => $machine
        ));
        $res = array ();
        foreach ( $select as $row ) {
            $age = $row ['annee'] - $fabrication;
            $res [] = array (
                    $row ['annee'],
                    $age
            );
        }
        return $res;
    }

    /**
     * Generate a json string containing the hours per year
     *
     * @param unknown_type $year
     */
    public function histo($machine_list = false) {
        $json = "[";

        // nombre d'heures hors système
        $nbhs = array ();
        $this->load->model('planeurs_model');
        $this->load->model('historique_model');

        $select = $this->planeurs_model->select_all(array (
                'actif' => 1
        ));
        // var_dump($select);
        foreach ( $select as $elt ) {
            $nbhs [$elt ['mpimmat']] = $elt ['mpnbhdv'];
            $modele [$elt ['mpimmat']] = $elt ['mpmodele'];
        }
        // var_dump($nbhs);

        // cumuls annuels par machine
        $yearly_res = $this->monthly_sum('current_year, vpmacid', array (
                'machinesp.mpprive' => 0,
                'machinesp.actif' => 1
        ));
        $res = array ();
        $total = array ();
        $machines = array ();
        $machines_str = array ();
        foreach ( $yearly_res as $row ) {
            $year = $row ['current_year'];
            $machine = $row ['vpmacid'];
            $minutes = $row ['minutes'];
            $hours = $minutes / 60;
            // echo "year=$year, machine=$machine, minutes=$minutes, hours=$hours" .br();
            if (! isset($res [$machine])) {
                $machines [] = $machine;
                $machines_str [] = "$machine " . $modele [$machine];
                $hs = isset($nbhs [$machine]) ? $nbhs [$machine] : 0;
                $res [$machine] = $this->historique_heures($machine, $hs);
                $total [$machine] = $hs;
                // echo "$machine " . $nbhs[$machine] . ' -> ' .$total[$machine] .br();
            }
            $total [$machine] += $minutes / 60;
            $res [$machine] [] = array (
                    $year,
                    round($total [$machine], 0)
            );
        }

        if ($machine_list) {
            return $machines_str;
        }

        // Génération du json
        foreach ( $machines as $machine ) {
            $pjson = '[';
            foreach ( $res [$machine] as $elt ) {
                if (strlen($pjson) > 2) {
                    $pjson .= ', ';
                }
                $pjson .= '[' . $elt [0] . ', ' . $elt [1] . ']';
            }
            $pjson .= ']';

            if (strlen($json) > 2) {
                $json .= ', ';
            }
            $json .= $pjson;
        }
        $json .= "]";
        // var_dump($json);

        return $json;
    }

    /**
     * Generate a json string containing the hours per year
     *
     * @param $machine_list retourne
     *            la liste des immatriculations
     *
     *            http://localhost/gvv2/index.php/vols_planeur/ajax_age
     */
    public function age($machine_list = false) {
        $json = "[";

        $current_year = date('Y');

        // Année de fabrication
        $nbhs = array ();
        $modele = array ();
        $fabrication = array ();
        $this->load->model('planeurs_model');
        $this->load->model('historique_model');

        // Extraction des données de la table machine
        $machines_actives = $this->planeurs_model->select_all(array (
                'actif' => 1
        ));
        foreach ( $machines_actives as $elt ) {
            // pour chaque machine active, année de fabrication, heures de vol hors système et modèle
            $nbhs [$elt ['mpimmat']] = $elt ['mpnbhdv'];
            $fabrication [$elt ['mpimmat']] = $elt ['fabrication'];
            $modele [$elt ['mpimmat']] = $elt ['mpmodele'];
        }

        // cumuls annuels par machine
        // liste de array('vpmacid' => , 'current_year' => , 'minutes' =>)
        $yearly_res = $this->monthly_sum('current_year, vpmacid', array (
                'machinesp.mpprive' => 0,
                'machinesp.actif' => 1
        ));
        $res = array ();
        $ages = array ();
        $machines = array ();
        $machines_str = array ();

        $average = "Age moyen";
        $average_total = array (); // tableau age total index par année
        $average_nb = array (); // tableau nombre de machine index par année
        $years = array (); // liste des années
        $machines [] = $average;
        $machines_str [] = $average;

        // extraction des données de la table historique
        $select = $this->historique_model->select_page(1000, 0, array ());
        foreach ( $select as $row ) {
            $machine = $row ['machine'];
            $year = $row ['annee'];
            if (! isset($fabrication [$machine])) {
                continue;
            }

            $age = $year - $fabrication [$machine];

            // gestion de l'age moyen
            if (! isset($average_total [$year])) {
                $years [] = $year;
                $average_total [$year] = $age;
                $average_nb [$year] = 1;
            } else {
                $average_nb [$year] ++;
                $average_total [$year] += $age;
            }
        }

        // extraction des données de la table volsp
        // pour chaque machine active non privée
        foreach ( $yearly_res as $row ) {
            $year = $row ['current_year'];
            $machine = $row ['vpmacid'];
            $minutes = $row ['minutes'];
            $hours = $minutes / 60;
            // echo "year=$year, machine=$machine, minutes=$minutes, hours=$hours" .br();

            if (isset($fabrication [$machine]) && ($fabrication [$machine] != 0)) {

                // gestion de l'age
                if (! isset($res [$machine])) {
                    $machines [] = $machine;
                    $machines_str [] = "$machine " . $modele [$machine];
                    $fab = $fabrication [$machine];
                    $res [$machine] = $this->historique_age($machine, $fab);
                }
                $ages [$machine] = $year - $fabrication [$machine];
                $res [$machine] [] = array (
                        $year,
                        round($ages [$machine], 0)
                );

                // gestion de l'age moyen
                if (! isset($average_total [$year])) {
                    $years [] = $year;
                    $average_total [$year] = $ages [$machine];
                    $average_nb [$year] = 1;
                } else {
                    $average_nb [$year] ++;
                    $average_total [$year] += $ages [$machine];
                }
            }
        }

        // Calcul de l'age moyen
        $res [$average] = array ();
        foreach ( $years as $year ) {
            $res [$average] [] = array (
                    $year,
                    $average_total [$year] / $average_nb [$year]
            );
        }

        if ($machine_list) {
            return $machines_str;
        }

        // Génération du json
        foreach ( $machines as $machine ) {
            $pjson = '[';
            foreach ( $res [$machine] as $elt ) {
                if (strlen($pjson) > 2) {
                    $pjson .= ', ';
                }
                $pjson .= '[' . $elt [0] . ', ' . $elt [1] . ']';
            }
            $pjson .= ']';

            if (strlen($json) > 2) {
                $json .= ', ';
            }
            $json .= $pjson;
        }
        $json .= "]";
        // var_dump($json);

        return $json;
    }

    /**
     * Retourne un tableau des jours de vols par pilote sur les machines club
     */
    public function jours_de_vol($selection) {
        $this->load->model('membres_model');
        $order = "asc";
        $year = date("Y");
        $date25 = date_m25ans($year);
        $where = "volsp.vppilid = membres.mlogin and volsp.vpmacid = machinesp.mpimmat";

        $select = 'vpid, vpdate, vppilid, vpmacid, vpcdeb, vpcfin, vpduree, vpobs, vpinst as instructeur';
        $select .= ', vplieudeco';
        $select .= ', concat(mprenom," ", mnom) as pilote';

        // machines club
        $db_res = $this->db->select($select, FALSE)
            ->from('volsp, membres, machinesp')
            ->where($where)
            ->where($selection)
            ->where(array ('machinesp.mpprive' => 0))
            ->order_by("membres.mlogin, vpdate $order, vpcdeb $order")
            ->group_by("mlogin, vpdate")
            ->get();

        $result = $this->get_to_array($db_res);
        // foreach ($result as $key => $row) {
        // // var_dump($row);
        // $kid = $this->primary_key;
        // $image = $this->image($row[$kid], TRUE);
        // $result[$key]['image'] = $image;
        // $result[$key]['instructeur'] = $this->membres_model->image($row['instructeur'], true);
        // $result[$key]['rem_id'] = $this->membres_model->image($row['pilote_remorqueur'], true);
        // }

        // $this->gvvmetadata->store_table("vue_vols_planeur", $result);
        return $result;
    }

    /**
     * Retourne un tableau des heures par piloste et machines
     */
    public function par_pilote_machine($group_by, $where = array (), $selection = array()) {

        // SELECT count(*) as count, year(vpdate) as year, vpdc, vpmacid, CONCAT(mnom, ' ', mprenom) as nom, SUM(`vpduree`) AS minutes, SUM(`vpnbkm`) AS kms, membres.actif
        // FROM (`membres`)
        // LEFT JOIN `volsp` ON `volsp`.`vppilid` = `membres`.`mlogin`
        // JOIN `machinesp` ON `volsp`.`vpmacid` = `machinesp`.`mpimmat`
        // WHERE YEAR(vpdate) = "2013"
        // and membres.actif = 1
        // and machinesp.actif = 1
        // GROUP BY `mlogin`, `vpmacid`
        // ORDER BY `year`, `nom`
        $select = "count(*) as count, year(vpdate) as year, vpdc, vpmacid";
        $select .= ", CONCAT(mnom, ' ',mprenom) as nom";

        // D'abord les vols en tant qu'instructeurs
        $db_res = $this->db->select($select, FALSE)
            ->from("membres")
            ->select_sum('vpduree', 'minutes')
            ->select_sum('vpnbkm', 'kms')
            ->join("volsp", 'volsp.vpinst = membres.mlogin', 'left')
            ->join("machinesp", 'volsp.vpmacid = machinesp.mpimmat')
            ->where($where)->where($selection)
            ->group_by($group_by)
            ->order_by('volsp.vpmacid, nom, year')
            ->get();

        $instruction = $this->get_to_array($db_res);
        // On les indexe
        $inst = array ();
        $pilot = array ();

        $pilot_list = array ();
        $year_list = array ();
        foreach ( $instruction as $row ) {
            if (! in_array($row ['nom'], $pilot_list)) {
                $pilot_list [] = $row ['nom'];
            }
            if (! in_array($row ['year'], $year_list)) {
                $year_list [] = $row ['year'];
            }
            $inst [$row ['nom']] [$row ['year']] = $row;
        }

        // Puis les vols comme pilote
        $db_res = $this->db->select($select, FALSE)
            ->from("membres")
            ->select_sum('vpduree', 'minutes')
            ->select_sum('vpnbkm', 'kms')
            ->join("volsp", 'volsp.vppilid = membres.mlogin', 'left')
            ->join("machinesp", 'volsp.vpmacid = machinesp.mpimmat')
            ->where($where)->where($selection)
            ->group_by($group_by)
            ->order_by('volsp.vpmacid, nom, year')
            ->get();
        $pilotes  = $this->get_to_array($db_res);
            
        $result = array ();
        foreach ( $pilotes as $row ) {
            if (! in_array($row ['nom'], $pilot_list)) {
                $pilot_list [] = $row ['nom'];
            }
            if (! in_array($row ['year'], $year_list)) {
                $year_list [] = $row ['year'];
            }
            $pilot [$row ['nom']] [$row ['year']] = $row;

            // $result[] = $row;
        }
        sort($year_list);
        sort($pilot_list);

        $result = array ();
        foreach ( $year_list as $year ) {
            foreach ( $pilot_list as $nom ) {
                $line = array (
                        'count' => 0,
                        'year' => $year,
                        'vpdc' => 0,
                        'vpmacid' => "",
                        'nom' => $nom,
                        'minutes' => '0.00',
                        'kms' => 0
                );

                if (isset($pilot [$nom] [$year])) {
                    $elt = $pilot [$nom] [$year];
                    $line ['count'] += $elt ['count'];
                    $line ['vpdc'] = $elt ['vpdc'];
                    $line ['vpmacid'] = $elt ['vpmacid'];
                    $line ['minutes'] += $elt ['minutes'];
                    $line ['kms'] += $elt ['kms'];
                }

                if (isset($inst [$nom] [$year])) {
                    $elt = $inst [$nom] [$year];
                    $line ['count'] += $elt ['count'];
                    $line ['vpdc'] = $elt ['vpdc'];
                    $line ['vpmacid'] = $elt ['vpmacid'];
                    $line ['minutes'] += $elt ['minutes'];
                    $line ['kms'] += $elt ['kms'];
                }
                $result [] = $line;
            }
        }

        return $result;
    }
    
    function experience($pilot, $since) {
    	$result = array(
    		'vols_cdb' => 0,
    		'heures_cdb' => 0,
    		'vols_dc' => 0,
    		'autonomes' => 0,
    		'rem' => 0,
    		'treuil' => 0
    	);
    	$where = array('vppilid' => $pilot, 'vpdate >=' => $since, 'vpdc' => 0);
    	$hours_cdb = $this->sum('vpduree', $where) / 60;
    	$vols_cdb = $this->count($where);
    	 
    	$where = array('vpinst' => $pilot, 'vpdate >=' => $since);
    	$hours_inst = $this->sum('vpduree', $where) / 60;
    	$vols_inst = $this->count($where);
    	
    	$where = array('vppilid' => $pilot, 'vpdate >=' => $since, 'vpdc' => 1);
    	$vols_dc = $this->count($where);
    	$result['vols_dc'] = $vols_dc;
    	 
    	$result['heures_cdb'] = sprintf("%0.02f", $hours_cdb + $hours_inst);
    	$result['vols_cdb'] = $vols_cdb + $vols_inst;
    	
    	// lancements
    	// remorquages 
    	$where = array('vppilid' => $pilot, 'vpdate >=' => $since, 'vpautonome' => 3);
    	$rem = $this->count($where);
    	$where = array('vpinst' => $pilot, 'vpdate >=' => $since, 'vpautonome' => 3);
    	$rem_inst = $this->count($where);
    	$result['rem'] = $rem + $rem_inst;
    	
    	// treuilés
    	$where = array('vppilid' => $pilot, 'vpdate >=' => $since, 'vpautonome' => 1);
    	$rem = $this->count($where);
    	$where = array('vpinst' => $pilot, 'vpdate >=' => $since, 'vpautonome' => 1);
    	$rem_inst = $this->count($where);
    	$result['treuil'] = $rem + $rem_inst;
    	 
    	// autonomes
    	$where = array('vppilid' => $pilot, 'vpdate >=' => $since, 'vpautonome' => 2);
    	$rem = $this->count($where);
    	$where = array('vpinst' => $pilot, 'vpdate >=' => $since, 'vpautonome' => 2);
    	$rem_inst = $this->count($where);
    	$result['autonomes'] = $rem + $rem_inst;
    	
    	 
    	return $result;
    }
    
    /*
     * Retourne les vols
     */
    function get($where = array ()) {
        
        $selection = $this->select_all($where);
        return $selection;
    }
}

/* End of file vols_planeur_model.php */
/* Location: ./application/models/vols_planeur_model.php */
