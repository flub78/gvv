<?php
if (!defined('BASEPATH'))
    exit ('No direct script access allowed');

/**
 *	Gestion des dates d'événements
 */

// $CI =& get_instance();
// $CI->load->model('common_model');

class Event_model extends Common_Model {
    public $table = 'events';
    protected $primary_key = 'id';

    /**
     *	Retourne la liste des évenement d'un pilote
     *	@return objet		  La liste
     */
    public function evenement_de($membre, $where = array (), $name = "events") {
        $select = 'events.id as id, events.edate as date, events.evaid as plane_flight, events.evpid as glider_flight' 
            . ', events.ecomment as comment, events_types.name as event_type, events_types.id as events_types_id, events_types.activite as activite, ordre, date_expiration';
        $result = $this->db->select($select)
            ->from("events, events_types")
            ->where("events.etype = events_types.id")
            ->where(array ("events.emlogin" => $membre))
            ->where($where)
            ->order_by('date DESC')
            ->get()->result_array();

        $CI = & get_instance();

        foreach ($result as $key => $row) {
            $result[$key]['image'] = date_db2ht($row['date']) . ' ' . $row['event_type'];
            if ($row['glider_flight']) {
                $image = $CI->vols_planeur_model->image($row['glider_flight']);
                $result[$key]['glider_flight_image'] = $image;
            } else {
                $result[$key]['glider_flight'] = "";
            }
            if ($row['plane_flight']) {
                $image = $CI->vols_avion_model->image($row['plane_flight']);
                $result[$key]['plane_flight_image'] = $image;
            } else {
                $result[$key]['plane_flight'] = "";
            }
        }
        $this->gvvmetadata->store_table($name, $result);
        return $result;
    }

    /**
     * @return a default user who has an event, either the logged in one or the first one.
     */
    public function default_id() {
        if ($this->count() == 0)
            return "";
        $id = $this->dx_auth->get_username();
        $count = $this->count(array (
            'emlogin' => $id
        ));
        if ($count == 0) {
            $query = $this->db->select('emlogin')->from($this->table)->order_by('emlogin')->get()->result_array();
            $row = $query[0];
            $id = $row['emlogin'];
            return $id;
        } else {
            return $id;
        }
    }

    public function getEventMember($id) {
        $count = $this->count(array (
            'id' => $id
        ));
        if ($count == 0)
            return "";
        else {
            $query = $this->db->select('id,emlogin')->from($this->table)->where(array (
                "id" => $id
            ))->get()->result_array();
            $row = $query[0];
            return $row['emlogin'];
        }
    }

    /**
     *	Retourne le tableau tableau utilisé pour l'affichage par page
     *	@return objet		  La liste
     */
    public function select_page($nb = 1000, $debut = 0, $where = array (), $name = "event") {
        $select = $this->select_columns('etype, edate, evpid, evaid, ecomment', $nb, $debut, $where);
        $this->gvvmetadata->store_table($name, $select);
        return $select;
    }

    /**
     * Certificats annuels
     * @param unknown_type $year
     */
    public function getStats($year) {
        $select = 'events_types.name as event_type, etype, count(*) as stat';

        $db_res = $this->db->select($select)
            ->from("events,events_types")
            ->where("events.etype = events_types.id")
            ->where(array ("YEAR(events.edate)" => $year))
            ->order_by("name ASC")
            ->group_by("YEAR(edate),events_types.name")
            ->get();
        $result = $this->get_to_array($db_res);

        $max = 0;
        foreach ($result as $key => $row) {
            // var_dump($row);
            $type = $row["etype"];
            unset ($result[$key]["etype"]);

            $who = $this->db->select('emlogin, etype, mnom, mprenom, YEAR(edate) as year')->from("events, membres, events_types")->where("events.etype = events_types.id")->where("events.emlogin = membres.mlogin")->where("events.etype = \"$type\"")->where("YEAR(edate) = \"$year\"")->get()->result_array();

            $count = 0;
            foreach ($who as $evt) {
                $count++;
                $login = $evt['emlogin'];
                $name = $evt["mprenom"] . " " . $evt["mnom"];
                $result[$key][$count] = anchor(controller_url("event/page/$login"), $name);
                if ($count > $max)
                    $max = $count;
            }
        }

        // remplit les lignes incomplètes
        foreach ($result as $key => $row) {
            for ($count = 1; $count <= $max; $count++) {
                if (!isset ($result[$key][$count])) {
                    $result[$key][$count] = '';
                }
            }
        }

        $this->gvvmetadata->store_table("events_year", $result);
        return $result;
    }

    /**
     * Extrait les informations de formation
     */
    public function formation($activites = array (), $format = "html") {

        // Liste de pilotes actifs.
        $select = 'mlogin, mnom, mprenom, m25ans';
        $actifs = $this->db->select($select)->from("membres")
        	->where("actif = \"1\" ")
        	->where("categorie <> \"1\" ")
        	->order_by("mnom, mprenom")
        	->get()->result_array();
        // var_dump($actifs);

        // extraction des noms des certificats
        $types = $this->db->select("id, name, activite, ordre")
            ->from("events_types")
            ->order_by('activite, ordre')
            ->get()->result_array();

        // extraction des certificats par pilotes
        $select = 'mlogin, mnom, mprenom, events.etype as event_type, events.edate as event_date, events_types.name as event_name';
        $select .= ", events_types.expirable as expirable, events.date_expiration as date_expiration";

        $db_res = $this->db->select($select)
            ->from("membres, events, events_types")
            ->where("events.etype = events_types.id")
            ->where("events.emlogin = membres.mlogin")
            ->order_by("mnom, mprenom, event_date")
            ->group_by("mlogin, event_name, event_date")->get();
        $certifs = $this->get_to_array($db_res);

        $revert = array ();
        foreach ($certifs as $row) {
            $date = $row['event_date'];
            $str = date_db2ht($date);
            if (($format == "html") 
            		&& ($row['expirable']) 
            		&& isset($row['date_expiration'])  
            		&& (strtotime($row['date_expiration']) < strtotime("now")))
                $str = '<div class="error">' . $str . '</div>';
            $revert[$row['mlogin']][$row['event_type']] = $str;

        }

        $results = array ();
        $line = 0;
        $col = 0;
        // ligne de titre
        $results[$line][$col++] = "Pilote";
        foreach ($types as $row) {
            if (in_array($row['activite'], $activites))
                $results[$line][$col++] = $row['name'];
        }
        
        foreach ($actifs as $pilote) {
            $line++;
            $col = 0;
            $mlogin = $pilote['mlogin'];
            if ($format == "html") {
            	$results[$line][$col++] = anchor(controller_url("event/page/$mlogin"), $pilote['mnom'] . ' ' . $pilote['mprenom']);
            } else {
            	$results[$line][$col++] = $pilote['mnom'] . ' ' . $pilote['mprenom'];
            }
            foreach ($types as $row) {
                $id = $row['id'];
                $activite = $row['activite'];
                if (in_array($activite, $activites))
                    $results[$line][$col++] = isset ($revert[$mlogin][$id]) ? $revert[$mlogin][$id] : '';
            }
        }

        return $results;
    }

    /**
     * Extrait les informations de formation
     */
    public function gen_dates($events = array ()) {

        // Liste de pilotes actifs.
        $select = "*";
        $actifs = $this->db->select($select)->from("membres")->order_by("mnom, mprenom")->get()->result_array();

        foreach ($actifs as $row) {
            foreach ($row as $key => $value) {
                // echo "   $key => $value" . br();
            }

            $mlogin = $row['mlogin'];

            $vv_num = $row['mbrpnum'];
            $vv_date = date_db2ht($row['mbrpdat']);
            if ($vv_num != '') {
                $type = 27;
                $event = $this->db->select()->from($this->table)->where('emlogin', $mlogin)->where('etype', $type)->get()->row_array();

                // var_dump($event);
                if (count($event) == 0) {
                    echo "$mlogin VV = $vv_num $vv_date" . br();
                }
            }

            $ppl_num = $row['mbranum'];
            $ppl_date = date_db2ht($row['mbradat']);
            if ($ppl_num != "") {
                echo "$mlogin PPL = $ppl_num $ppl_date" . br();
            }

            $medical = date_db2ht($row['medical']);
            if ($medical != '') {
                $type = 26;
                $event = $this->db->select()->from($this->table)->where('emlogin', $mlogin)->where('etype', $type)->get()->row_array();

                // var_dump($event);
                if (count($event) == 0) {
                    echo "$mlogin medical = $medical" . br();
                }
            }

            $control = date_db2ht($row['mbrpval']);
            if ($control != '') {
                $type = 30;
                $event = $this->db->select()->from($this->table)->where('emlogin', $mlogin)->where('etype', $type)->get()->row_array();

                // var_dump($event);
                if (count($event) == 0) {
                    echo "$mlogin control = $control" . br();
                }
            }
            echo br(2);
        }

    }

    /**
     * Retourne un selecteur d'année
     */
    public function getYearSelector($date_field) {
        $query = $this->db->select('YEAR(edate) as year')->from("events")->order_by("edate ASC")->limit(1)->get()->result_array();
        if ($query != null)
            $minDate = $query[0]['year'];
        else
            $minDate = date('Y');
        $year_selector = array ();
        for ($i = date('Y'); $i >= $minDate; $i--) {
            $year_selector[$i] = $i;
        }
        return $year_selector;
    }

    /**
     *    Retourne le nombre d'occurence
     *
     *    @param array $where    Tableau associatif permettant de définir des conditions
     *    @return integer        Le nombre de news satisfaisant la condition
     */
    public function count($selection = array (), $where2 = array()) {
        $where = "events.etype = events_types.id and events.emlogin = membres.mlogin";
        $what = 'edate, emlogin, etype, categorie, mdaten, name';
        $from = 'events, membres, events_types';
 
        // echo "select $what from $from where $where";
        // return 0;
        $count = $this->db->select($what)
            ->from($from)
            ->where($selection)
            ->where($where)->count_all_results();
        // var_dump($count);
        return $count;
    }
    
    /*
     * Remplace un evenement pour un pilote
     */
    public function replace ($event) {
    	gvv_debug("event->replace " . var_export($event, true));
    	$this->delete(array('emlogin' => $event['emlogin'], 'etype' => $event['etype']));
    	$this->create($event);
    }
    
    /**
     *    Retourne un tableau
     *
     *  <pre>
     *  foreach ($list as $line) {
     *     $line['mlogin'], $line['mnom']
     *  </pre>
     *  
     *  @param $where selection
     *    @return objet          La liste
     */
    public function flight_events($where = array ()) {
    	return $this->db
    		->select("events.id as id, emlogin, events.etype, evpid, evaid, events_types.id as type_id, en_vol, activite")
    		->from("events, events_types")
        	->where($where)
        	->where("events.etype = events_types.id")
        	->get()->result_array();
    }

    /**
     * Extrait les informations de formation
     */
    public function licences_per_year($type) {
    
    	// Liste de tous les pilotes qui ont eux des licences
    	// Il faut fusionner les pilotes qui ont eu des licences (actifs ou non) et les pilotes actifs
    	
    	$select = 'mlogin, mnom, mprenom, m25ans';
    	$actifs = $this->db->select($select)->from("membres,licences")
    	->where("membres.mlogin = licences.pilote")
    	->group_by("membres.mlogin")
    	->order_by("mnom, mprenom")->get()->result_array();
    
    	// extraction des licences
    	$selection = $this->select_columns('id, pilote, type, year, date, comment', 1000000, 0, array (
    			'type' => $type
    	));
    
    	// look for min and max year
    	$min_range = 10;
    	$min = date("Y");
    	$max = $min;
    	foreach ($selection as $licence) {
    		$year = $licence['year'];
    		if ($year < $min)
    			$min = $year;
    		if ($year > $max)
    			$max = $year;
    	}
    	if (($max - $min) < $min_range)
    		$min = $max - $min_range;
    
    	// Initialise the array
    	$results = array ();
    	$line = 0;
    	$col = 0;
    	$total_annuel = array ();
    	$total_annuel[$col] = "Total";
    	$results[$line][$col++] = "Pilote";
    
    	for ($year = $min; $year <= $max; $year++) {
    		$results[$line][$col] = $year;
    		$total_annuel[$col] = 0;
    		$col++;
    	}
    	$pilote_line = array ();
    	foreach ($actifs as $pilote) {
    		$line++;
    		$col = 0;
    		$mlogin = $pilote['mlogin'];
    		$pilote_line[$mlogin] = $line;
    		$results[$line][$col++] = anchor(controller_url("event/page/$mlogin"), $pilote['mnom'] . ' ' . $pilote['mprenom']);
    		for ($year = $min; $year <= $max; $year++) {
    			$url = controller_url("licences/set/$mlogin/$year/$type");
    			$box = anchor($url, '-');
    			$results[$line][$col++] = $box;
    		}
    	}
    
    	foreach ($selection as $licence) {
    		$pilote = $licence['pilote'];
    		$year = $licence['year'];
    		$url = controller_url("licences/switch_it/$pilote/$year/$type");
    		$box = anchor($url, $year);
    		$col = $year - $min +1;
    		if ( array_key_exists($pilote, $pilote_line) ) {
    			$results[$pilote_line[$pilote]][$col] = $box;
    			$total_annuel[$col] += 1;
    		}
    	}
    	$results[] = $total_annuel;
    	//var_dump($results);
    	return $results;
    }
    
    /**
     * return the validity date of the last medical
     * or an empty string when there is none
     * @param unknown $mlogin
     */
    function medical_validity_date($mlogin) {
    	$medical_id = $this->config->item('medical_id');
    	$where = array('events_types.id' => $medical_id);
    	$visites = $this->event_model->evenement_de($mlogin, $where);
    	
    	if (count($visites)) {
    		$visite = $visites[0];
    		if (isset($visite['date_expiration']) && 
    				(strtotime('now') <= strtotime($visite['date_expiration']))) {
    			return $visite['date_expiration'];	
    		} 
    	} 
    	return "";
    }
    
    /**
     * Return the 
     * @param unknown $mlogin
     */
    function bpp_date($mlogin) {
    	$where = array('events_types.name' => 'BPP');
    	$brevets = $this->event_model->evenement_de($mlogin, $where);
    	
    	if (count($brevets) > 0) {
    		return $brevets[0]['date'];
    	}
    	return "";
    }

    /**
     * Return the 
     * @param unknown $mlogin
     */
    function controle_date($mlogin) {
    	$where = array('events_types.name' => 'Contrôle de compétence');
    	$liste = $this->event_model->evenement_de($mlogin, $where);
    	
    	if (count($liste) > 0) {
    		return $liste[0]['date'];
    	}
    	return "";
    }
    
    /**
     * Return the
     * @param unknown $mlogin
     */
    function passager_date($mlogin) {
    	$where = array('events_types.name' => 'Emport passager');
    	$liste = $this->event_model->evenement_de($mlogin, $where);
    	 
    	if (count($liste) > 0) {
    		return $liste[0]['date'];
    	}
    	return "";
    }

    /**
     * Return the
     * @param unknown $mlogin
     */
    function inst_validity($mlogin) {
    	$where = array('events_types.name' => 'ITP');
    	$where = "(events_types.name like 'ITP' or events_types.name like 'ITV')";
    	$liste = $this->event_model->evenement_de($mlogin, $where);
    
    	// var_dump($liste);
    	if (count($liste) > 0) {
    		return $liste[0]['date_expiration'];
    	}
    	return "";
    }
    
}
?>
