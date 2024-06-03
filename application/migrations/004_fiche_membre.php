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
class Migration_Fiche_Membre extends CI_Migration {

	/**
	 * Migration 003 -> 004
	 */
	public function up()
	{
		$sql = "ALTER TABLE `membres` ADD `profession` VARCHAR( 64 ) NULL COMMENT 'Profession'";
		return $this->db->query($sql);
	}

	/**
	 * Retour 004 -> 003
	 */
	public function down()
	{
		$sql = "ALTER TABLE `membres` DROP `profession`";
		return $this->db->query($sql);
	}
}
