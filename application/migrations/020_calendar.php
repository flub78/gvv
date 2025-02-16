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
 * Passe les compteurs de tickets en décimal
 *
 * @author frederic
 *
 */
class Migration_Calendar extends CI_Migration {

	protected $number;

	/**
	 *
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->number = 20;
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
			}
		}
		return $errors;
	}


	/**
	 * Apply the migration
	 */
	public function up() {
		$errors = 0;

		$filename = getcwd() . '/application/migrations/calendar.sql';
		$res = $this->database->sqlfile($filename);
		gvv_info("Migration database up to " . $this->number . ", errors=$errors");
	}

	/**
	 * Reverse the migration
	 */
	public function down() {

		$errors = 0;
		$sql = 'SET FOREIGN_KEY_CHECKS=0;';
		if (!$this->db->query($sql)) {
			$errors += 1;
		}

		$this->dbforge->drop_table('calendar', true);

		$sql = 'SET FOREIGN_KEY_CHECKS=1;';
		if (!$this->db->query($sql)) {
			$errors += 1;
		}

		gvv_info("Migration database down to " . $this->number - 1 . ", errors=$errors");

		return !$errors;
	}
}
