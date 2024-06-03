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
 * Ajout d'un champ vol a la table ticket
 * 
 * @author frederic
 *
 */
class Migration_Volsa_Champs_Obligatoires extends CI_Migration {

	/**
	 * Migration 020 -> 021
	 */
	public function up()
	{
		$sql = "ALTER TABLE `volsa` CHANGE `vahdeb` `vahdeb` DECIMAL(4,2) NOT NULL COMMENT 'Heure de décollage'";
		$this->db->query($sql);
		$sql = "ALTER TABLE `volsa` CHANGE `vahfin` `vahfin` DECIMAL(4,2) NOT NULL COMMENT 'Heure d\'atterrissage'";
		return $this->db->query($sql);
	}

	/**
	 * Retour 021 -> 020
	 */
	public function down()
	{
	    $sql = "ALTER TABLE `volsa` CHANGE `vahdeb` `vahdeb` DECIMAL(4,2) NULL COMMENT 'Heure de décollage'";
	    $this->db->query($sql);
	    $sql = "ALTER TABLE `volsa` CHANGE `vahfin` `vahfin` DECIMAL(4,2) NULL COMMENT 'Heure d\'atterrissage'";
	    return $this->db->query($sql);
	}
}
