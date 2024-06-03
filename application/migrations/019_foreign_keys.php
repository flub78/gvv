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
 * Passe la base en InnoDB et dfinie les clé trangères
 *
 * @author frederic
 *
 */
class Migration_Foreign_Keys extends CI_Migration {

	protected $number;

	/**
	 *
	 * Constructor
	 *
	 * Affiche header et menu
	 */
	function __construct() {
		parent :: __construct();
		$this->number = 18;
		$this->load->library('database');
		$this->error_msgs = array();
	}

	/*
	 * Execute an array of sql requests
	 */
	private function run_queries($sqls = array()) {
		$errors = 0;
		foreach ($sqls as $sql) {
			// echo $sql . br();
			if (!$this->db->query($sql)) {
				$errors += 1;
				$mysql_msg = $this->db->_error_message(); 
				$mysql_error = $this->db->_error_number();
				$msg = "code=$mysql_error, msg= $mysql_msg, sql=$sql";
				
				gvv_error("Migration error: $msg");
				$this->error_msgs[] = $msg;
			}
		}
		return $errors;
	}


	/**
	 * " . "
	 * @param unknown $from_table
	 * @param unknown $from_field
	 */
	protected function add_index($from_table, $from_field) {
		return "ALTER TABLE " . $from_table . " ADD INDEX " . $from_field . "(" . $from_field . ");";
	}
	
	/**
	 * 
	 * @param unknown $from_table
	 * @param unknown $from_field
	 * @param unknown $to_table
	 * @param unknown $to_key
	 */
	protected function add_key($from_table, $from_field, $to_table, $to_key) {
		return "ALTER TABLE `" . $from_table . "` ADD CONSTRAINT `" 
		. $from_table . "_ibfk_" . $from_field 
		. "` FOREIGN KEY ( `" . $from_field 
		. "` ) REFERENCES `gvv2`.`" . $to_table . "` (`" . $to_field . "`) ON DELETE RESTRICT ON UPDATE RESTRICT ;";
	}
	
	/**
	 * Apply the migration
	 */
	public function up()
	{
		$errors = 0;
		
		// Utilise InnoDB pour toutes les tables
		// ALTER TABLE `categorie` ENGINE = InnoDB;
		$sql = "select * from information_schema.tables where TABLE_SCHEMA = 'gvv2' and ENGINE != 'InnoDB';";
		$select = $this->db->query($sql)->result_array();
		foreach ($select as $table) {
			echo $table['TABLE_NAME'] . br();
			$sql = 'ALTER TABLE ' . $table['TABLE_NAME'] . ' ENGINE = InnoDB;';
			$this->db->query($sql);
		}
		
		$sqls = array(
				"ALTER TABLE achats ADD FOREIGN KEY ( `pilote` ) REFERENCES `gvv2`.`membres` (`mlogin`) ON DELETE RESTRICT ON UPDATE RESTRICT ;",
				// "ALTER TABLE achats ADD INDEX vol_planeur(vol_planeur);",
				$this->add_index('achats', 'vol_planeur'),
				"UPDATE `achats` SET `vol_planeur` = NULL WHERE `vol_planeur` =0;",
				"ALTER TABLE `achats` ADD CONSTRAINT `achats_ibfk_vol_planeur` FOREIGN KEY ( `vol_planeur` ) REFERENCES `gvv2`.`volsp` (`vpid`) ON DELETE RESTRICT ON UPDATE RESTRICT ;",
				// $this->add_key('achats', 'vol_planeur', 'volsp', 'vpid'),
// 				"ALTER TABLE achats ADD INDEX vol_avion(vol_avion);",
				$this->add_index('achats', 'vol_avion'),
				"UPDATE `achats` SET `vol_avion` = NULL WHERE `vol_avion` =0;",
				"ALTER TABLE `achats` ADD CONSTRAINT `achats_ibfk_vol_avion` FOREIGN KEY ( `vol_avion` ) REFERENCES `gvv2`.`volsa` (`vaid`) ON DELETE RESTRICT ON UPDATE RESTRICT ;",
				//$this->add_key('achats', 'vol_avion', 'volsa', 'vaid')
		);
		
		$errors += $this->run_queries($sqls);
		
		$this->db->update('achats', array('vol_planeur' => "NULL") , array('vol_planeur' => 0));
		
		
		gvv_info("Migration database up to " . $this->number . ", errors=$errors");
		
		if ($errors) {
			$this->session->set_flashdata('migration_errors', $errors);
			$this->session->set_flashdata('migration_msgs', $this->error_msgs);
		}
		return !$errors;
	}

	/**
	 * Reverse the migration
	 */
	public function down()
	{
		
		$errors = 0;

		// Pas vraiment de raisons de revenir en arrières sur le moteur des tables.
		
		$sqls = array(
				"ALTER TABLE `achats` DROP FOREIGN KEY `achats_ibfk_vol_planeur` ;",
				"ALTER TABLE achats DROP INDEX vol_planeur;",
				"ALTER TABLE `achats` DROP FOREIGN KEY `achats_ibfk_vol_avion` ;",
				"ALTER TABLE achats DROP INDEX vol_avion;",
		);
		
		$errors += $this->run_queries($sqls);
		
		gvv_info("Migration database down to " . $this->number - 1 . ", errors=$errors");
		if ($errors) {
			$this->session->set_flashdata('migration_errors', $errors);
			$this->session->set_flashdata('migration_msgs', $this->error_msgs);
		}
		
		return !$errors;
	}
}
