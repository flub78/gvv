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
 * Nettoyage de la table membre
 * msolde
 * mforfvv
 * manneeins
 * profil
 * manneeffvv
 * manneeffa
 * 
 *  champs suplémentaires pour events et events_types
 *    
 * @author frederic
 *
 */
class Migration_Cleanup_Membre extends CI_Migration {

	/**
	 * Add membres.inst_glider, membres.inst_airplane, 
	 */
	public function up()
	{	
		$errors = 0;
		
		// nettoyage de champs inutiles			
		$sqls = array(
				"ALTER TABLE `membres` DROP `msolde`",
				"ALTER TABLE `membres` DROP `mforfvv`",
				"ALTER TABLE `membres` DROP `profil`",
				"ALTER TABLE `membres` DROP `manneeins`",
				"ALTER TABLE `membres` DROP `manneeffvv`",
				"ALTER TABLE `membres` DROP `manneeffa`",
				
				"ALTER TABLE `events_types` ADD `expirable` TINYINT( 1 ) COMMENT 'a une date d_expiration'",
				"ALTER TABLE `events_types` ADD `ordre` TINYINT( 2 ) COMMENT 'ordre d_affichage'",
				"ALTER TABLE `events_types` CHANGE `name` `name` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL",
				
				"ALTER TABLE `events` ADD `date_expiration` DATE COMMENT 'date expiration'");

		foreach ($sqls as $sql) {
// 			echo $sql . br();
			if (!$this->db->query($sql)) {$errors += 1;}
		}
		
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
				"ALTER TABLE `events_types` DROP `expirable`",
				"ALTER TABLE `events_types` DROP `ordre`",
				"ALTER TABLE `events_types` CHANGE `name` `name` VARCHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL",
				
				"ALTER TABLE `events` DROP `date_expiration`",
				
				"ALTER TABLE `membres` ADD `msolde` DECIMAL(5,2) COMMENT 'Solde'",
				"ALTER TABLE `membres` ADD `mforfvv` CHAR(1) COMMENT 'Forfait ACES'",
				"ALTER TABLE `membres` ADD `manneeins` CHAR(1) COMMENT 'Année d inscription'",
				"ALTER TABLE `membres` ADD `manneeffvv` CHAR(1) COMMENT 'Licence FFVV'",
				"ALTER TABLE `membres` ADD `manneeffa` CHAR(1) COMMENT 'Licence FFA'",
				);
		
		foreach ($sqls as $sql) {
// 			echo $sql . br();
			if (!$this->db->query($sql)) {$errors += 1;}
		}
		
		return !$errors;		
	}
}
