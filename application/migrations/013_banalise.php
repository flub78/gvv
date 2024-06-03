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
 * Ajout des champs banalisé et propriétaire sur les planeurs
 *    
 * @author frederic
 *
 */
class Migration_Banalise extends CI_Migration {

	/*
	 * Execute an array of sql requests
	 */
	private function run_queries($sqls = array()) {
		$errors = 0;
		foreach ($sqls as $sql) {
			// echo $sql . br();
			if (!$this->db->query($sql)) {$errors += 1;}
		}
		return $errors;
	}
	
	
	/**
	 * 
	 */
	public function up()
	{	
		$errors = 0;
		
		// nettoyage de champs inutiles
		$sqls = array(
				"ALTER TABLE `machinesp` ADD `banalise` TINYINT(1) COMMENT 'Machine banalisée'",
				"ALTER TABLE `machinesp` ADD `proprio` VARCHAR(25) COMMENT 'Propriétaire'",
		);

		$errors += $this->run_queries($sqls); 
		gvv_info("Migration database up to 13, errors=$errors");
		
		return !$errors;
	}

	/**
	 * Retour 010 -> 009
	 */
	public function down()
	{
		$errors = 0;
		
		// nettoyage de champs inutiles
		$sqls = array(
				"ALTER TABLE `machinesp` DROP `mbranum`",
				"ALTER TABLE `machinesp` DROP `mbradat`",
		);

		$errors += $this->run_queries($sqls); 
		gvv_info("Migration database down to 12, errors=$errors");
		
		return !$errors;	
	}
}
