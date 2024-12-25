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
	exit('No direct script access allowed');

/**
 * Set correct defaults to frequencies
 * 
 * @author frederic
 *
 */
class Migration_Terrain_frequencies_default extends CI_Migration {

	/**
	 * Migration 021 -> 022
	 */
	public function up() {
		$sql = "ALTER TABLE `terrains` ALTER COLUMN `freq1` SET DEFAULT '0.000', ALTER COLUMN `freq2` SET DEFAULT '0.000'";
		$this->db->query($sql);
	}

	/**
	 * Retour 022 -> 021
	 */
	public function down() {
		$sql = "ALTER TABLE `terrains` ALTER COLUMN `freq1` SET DEFAULT NULL, ALTER COLUMN `freq2` SET DEFAULT NULL";

		$this->db->query($sql);
	}
}
