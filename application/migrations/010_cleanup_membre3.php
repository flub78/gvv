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
 * Cette migration à pour but de netoyer les champs de la fiche pilote
 *  
 * mbranum Numéro brevet avion			
 * mbradat Date brevet avion  
 * mbraval Validité brevet avion 
 * 
 * mbrpnum Numéro brevet planeur 
 * mbrpdat dat ebrevet planeur  
 * mbrpval Validité brevet planeur  
 * 
 * numinstavion Numéro instructeur avion 
 * dateinstavion Validité instructeur avion 
 * 
 * numivv Numéro instructeur planeur
 * dateivv Date instructeur planeur   
 * 
 * medical Vavilité visite médicale  
 * 
 *    
 * @author frederic
 *
 */
class Migration_Cleanup_Membre3 extends CI_Migration {

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
				"ALTER TABLE `membres` DROP `mbranum`",
				"ALTER TABLE `membres` DROP `mbradat`",
				"ALTER TABLE `membres` DROP `mbraval`",
				"ALTER TABLE `membres` DROP `mbrpnum`",
				"ALTER TABLE `membres` DROP `mbrpdat`",
				
				"ALTER TABLE `membres` DROP `mbrpval`",
				"ALTER TABLE `membres` DROP `numinstavion`",
				"ALTER TABLE `membres` DROP `dateinstavion`",
				"ALTER TABLE `membres` DROP `numivv`",
				"ALTER TABLE `membres` DROP `dateivv`",
				
				"ALTER TABLE `membres` DROP `medical`",
				"ALTER TABLE `membres` DROP `numlicencefed`",
				"ALTER TABLE `membres` DROP `vallicencefed`",
		);

		$errors += $this->run_queries($sqls); 
		gvv_info("Migration database up to 10, errors=$errors");
		
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
				"ALTER TABLE `membres` ADD `mbranum` VARCHAR(20) COMMENT 'Numéro brevet avion'",
				"ALTER TABLE `membres` ADD `mbradat` DATE COMMENT 'Date brevet avion'",
				"ALTER TABLE `membres` ADD `mbraval` DATE COMMENT 'Validité brevet avion'",
				"ALTER TABLE `membres` ADD `mbrpnum` VARCHAR(20) COMMENT 'Numéro brevet planeur'",
				"ALTER TABLE `membres` ADD `mbrpdat` DATE COMMENT 'Date brevet planeur'",
				
				"ALTER TABLE `membres` ADD `mbrpval` DATE COMMENT 'Validité brevet planeur'",
				"ALTER TABLE `membres` ADD `numinstavion` VARCHAR(20) COMMENT 'Numéro instructeur avion'",
				"ALTER TABLE `membres` ADD `dateinstavion` DATE COMMENT 'Date instructeur avion'",
				"ALTER TABLE `membres` ADD `numivv` VARCHAR(20) COMMENT 'Validité instructeur avion'",
				"ALTER TABLE `membres` ADD `dateivv` DATE COMMENT 'Date instructeur planeur'",
				
				"ALTER TABLE `membres` ADD `medical` DATE COMMENT 'Date visite médicale'",
				"ALTER TABLE `membres` ADD `numlicencefed` VARCHAR(20) COMMENT 'Numéro licence fédéral'",
				"ALTER TABLE `membres` ADD `vallicencefed` DATE COMMENT 'Validité licence fédérale'",
		);

		$errors += $this->run_queries($sqls); 
		gvv_info("Migration database down to 9, errors=$errors");
		
		return !$errors;	
	}
}
