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
 * Ajout de champs pour les instructeurs
 * 
 * @author frederic
 *
 */
class Migration_Instructor_Membre extends CI_Migration {

	/**
	 * Add membres.inst_glider, membres.inst_airplane, 
	 */
	public function up()
	{
		// Certains événements comme la licence, la cotisation la visite médicale 
		// peuvent exister, plusieurs fois dans la vie d'un pilote
		// d'autre comme le lachès ou les épreuves FAI ne compte que la première fois.
		$sql = "ALTER TABLE `events_types` ADD `multiple` TINYINT( 1 ) NULL COMMENT 'Multiple'";
		$this->db->query($sql);

		$sql = "ALTER TABLE `events` ADD `year` INT( 11 ) NULL COMMENT 'Année'";
		$this->db->query($sql);
		
		$sql = "ALTER TABLE `membres` ADD `inst_glider` VARCHAR( 25 ) NULL COMMENT 'Instructeur planeur'";
		$this->db->query($sql);
		
		$sql = "ALTER TABLE `membres` ADD `inst_airplane` VARCHAR( 25 ) NULL COMMENT 'Instructeur avion'";
		return $this->db->query($sql);
	}

	/**
	 * Retour 004 -> 003
	 */
	public function down()
	{
		$sql = "ALTER TABLE `events_types` DROP `multiple`";
		$this->db->query($sql);
		
		$sql = "ALTER TABLE `events` DROP `year`";
		$this->db->query($sql);
		
		$sql = "ALTER TABLE `membres` DROP `inst_gld`";
		$this->db->query($sql);
		
		$sql = "ALTER TABLE `membres` DROP `inst_gld`";
		return $this->db->query($sql);
	}
}
