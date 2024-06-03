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
 *	  Script de migration de la base
 */

if (!defined('BASEPATH'))
    exit ('No direct script access allowed');

/**
 * Cette migration à pour but de créer des événements à partir de la fichie pilote
 *  
 * mbranum Numéro brevet avion			\
 * mbradat Date brevet avion             -> remplacé par création d'un evenement PPL
 * mbraval Validité brevet avion        /
 * 
 * mbrpnum Numéro brevet planeur        \
 * mbrpdat dat ebrevet planeur           -> remplacé par création d'un evenement BPP
 * mbrpval Validité brevet planeur      /
 * 
 * numinstavion Numéro instructeur avion 	\ remplacé par un événement instructreur avion
 * dateinstavion Validité instructeur avion /
 * 
 * numivv Numéro instructeur planeur \ remplacé par un événement instructreur avion
 * dateivv Date instructeur planeur  / 
 * 
 * medical Vavilité visite médicale  --> remplacé par un événement "Visite médical"
 * 
 * numlicencefed 
 * vallicencefed
 *    
 * @author frederic
 *
 */
class Migration_Cleanup_Membre2 extends CI_Migration {

	/*
	 * Execute an array of sql requests
	 */
	private function run_queries($sqls = array()) {
		$errors = 0;
		foreach ($sqls as $sql) {
			// 			echo $sql . br();
			if (!$this->db->query($sql)) {$errors += 1;}
		}
		return $errors;
	}
	
	/*
	 * mbranum Numéro brevet avion			\
     * mbradat Date brevet avion             -> remplacé par création d'un evenement PPL
     * mbraval Validité brevet avion        /
	 */
	private function replace ($row, $numero, $date, $validity, $event_type) {
		
		$cnt = 0;
		$num = '';
		$create_date = '';
		$create_validity = '';
		
		if (isset($row[$numero]) && ($row[$numero])) {
			// numero déclare
			$num = $row[$numero];
			$cnt += 1;
		}

		if (isset($row[$date]) && ($row[$date]) && ($row[$date]) != '0000-00-00') {
			// date déclare
			$create_date = $row[$date];
			$cnt += 1;
		}

		if (isset($row[$validity]) && ($row[$validity]) && ($row[$validity] =! '0000-00-00')) {
			// date déclare
			$create_validity = $row[$validity];
			$cnt += 1;
		}

		if (!$cnt) {
			//echo "no $event_type to create for " . $row['mlogin'] . br();
			return;   // if nothing is specified
		}

		// check that the specified event exists
		$sql = "select events_types.id as event_type, events_types.name";
		$sql .= " from events_types";
		$sql .= " where events_types.name = '$event_type'";
		$query = $this->db->query($sql);
		
		if (count($query->result_array()) < 1) {
			gvv_info("Cannot create events of type $event_type");
			return;   
		}

		$results = $query->result_array();
		$event_id = $results[0]['event_type'];
		$mlogin = $row['mlogin'];
		
		// Check that the event that we want to create does not already exist fo the user
		$sql = "select events.emlogin, events.etype";
		$sql .= " from events";
		$sql .= " where events.etype = '$event_id'";
		$sql .= " and events.emlogin = '$mlogin'";
		$query = $this->db->query($sql);
		
		if (count($query->result_array()) > 0) {
			gvv_info("Event $event_type already exists for $mlogin");
			return;
		}
		
		
		if ($event_type == 'Visite médical') {
			$num = '';
			if ($create_validity == '') {
				$create_validity = $create_date;
			}
		}
			
		$event = array (
				'emlogin' => $mlogin,
				'etype' => $event_id,
				'edate' => $create_date,
				'evaid' => 0,
				'evpid' => 0,
				'ecomment' => $num,
				'year' => 0,
				'date_expiration' => $create_validity,
		);
		
		gvv_info("creating an event of type=$event_type, id=$event_id for membre=$mlogin");;
		$this->db->insert('events', $event);		
		
		
	}
	
	/**
	 * Add membres.inst_glider, membres.inst_airplane, 
	 */
	public function up()
	{	
		$errors = 0;
		
		// nettoyage de champs inutiles			
		$sqls = array(
		);
		$errors += $this->run_queries($sqls);
		
		$event_type = array (
				'name' => 'Cotisation',
				'activite' => 0,
				'en_vol' => 0,
				'multiple' => 1,
				'expirable' => 1,
				'ordre' => 3
		);
		
		$this->db->insert('events_types', $event_type);
		
		$event_type = array (
				'name' => 'Licence/Assurance planeur',
				'activite' => 1,
				'en_vol' => 0,
				'multiple' => 1,
				'expirable' => 1,
				'ordre' => 3
		);
		
		$this->db->insert('events_types', $event_type);
		
		$event_type = array (
				'name' => 'Licence/Assurance avion',
				'activite' => 2,
				'en_vol' => 0,
				'multiple' => 1,
				'expirable' => 1,
				'ordre' => 3
		);
		
		$this->db->insert('events_types', $event_type);
		
		
		// membres list			
		$sql =  'select * from membres where actif = "1";';
		
		$query = $this->db->query($sql);
		foreach ($query->result_array() as $row)
		{			
			$this->replace($row, 'mbranum', 'mbradat', 'mbraval', 'PPL');
			$this->replace($row, 'mbrpnum', 'mbrpdat', 'mbrpval', 'BPP');
			$this->replace($row, 'numinstavion', 'dateinstavion', 'dateinstavion', 'FI Formateur instructeur');
			$this->replace($row, 'numivv', 'dateivv', 'dateivv', 'ITP');
			$this->replace($row, 'numivv', 'dateivv', 'dateivv', 'ITV');
			$this->replace($row, 'medical', 'medical', 'medical', 'Visite médical');
			$this->replace($row, 'numlicencefed', 'vallicencefed', 'vallicencefed', 'Licence/Assurance planeur');
		}		
		gvv_info("Migration database up to 9");
		return !$errors;
	}

	/**
	 * Retour 008 -> 007
	 */
	public function down()
	{
		$errors = 0;
		
		// nettoyage de champs inutiles
		$sqls = array(
				);

		$errors += $this->run_queries($sqls); 
		gvv_info("Migration database down to 8");
		
		return !$errors;
		
	}
}
