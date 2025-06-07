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
 *
 */
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * Database maangement
 */
class Database {

	// backup order, the database is restored in the reverse order. All referenced tables
	// must already exist before each restored one. Put the tables that depends on others ones
	// on top of the list.
	// So everything which is referenced by another table must be above the referencing table

	protected $gvv_tables = array(
		'roles',
		'permissions',
		'users',
		'user_autologin',
		'user_profile',
		'user_temp',

		'pompes',
		'achats',
		'tarifs',
		'volsa',
		'volsp',
		'machinesa',
		'machinesp',
		'ecritures',
		'comptes',
		'licences',
		'membres',
		'planc',
		'categorie',
		'tickets',
		'events_types',
		'events',
		'terrains',
		'type_ticket',
		'reports',
		'sections',
		'mails',
		'historique',
		'migrations',
		'attachments',
		'config',
		'sections',
		'types_roles',
		'user_roles_per_section',
		'roles',
		'vols_decouverte'
	);

	protected $table_list;

	protected $defaut_list = array(
		'roles',
		'permissions',
		'planc',
		'events_types',
		'terrains',
		'type_ticket',
		'reports',
		'migrations'
	);

	protected $CI;

	/**
	 * Constructor - Sets DataTableer's Preferences
	 *
	 * The constructor can be passed an array of attributes values
	 */
	public function __construct() {
		$this->CI = &get_instance();
		$this->CI->load->dbforge();

		$this->table_list = array_merge(
			array(
				'ci_sessions',
				'login_attempts'
			),

			$this->gvv_tables
		);
		$this->CI->load->helper('log');
	}


	/**
	 * Backup the database
	 *
	 * @param string $type
	 */
	public function backup($type = "") {
		date_default_timezone_set('Europe/Paris');

		// Load the DB utility class
		$this->CI->load->dbutil();

		$nom_club = $this->CI->config->item('nom_club');
		$clubid = 'gvv_' . strtolower(str_replace(' ', '_', $nom_club)) . '_backup_';
		$dt = date("Y_m_d");
		$format = 'zip';
		if ($type == "") {
			$filename = $clubid . "$dt.zip";
			$add_drop = TRUE;
			$add_insert = TRUE;
			$list = $this->table_list;
		} else
			if ($type == "structure") {
			$filename = "gvv_structure.sql";
			$add_drop = FALSE;
			$add_insert = FALSE;
			$format = 'txt';
			$list = $this->table_list;
		} else {
			$filename = "gvv_defaut.sql";
			$add_drop = TRUE;
			$add_insert = TRUE;
			$format = 'txt';
			$list = $this->defaut_list;
		}

		// Backup your entire database and assign it to a variable
		$prefs = array(
			'filename' => $filename,
			'format' => $format,
			'add_insert' => $add_insert,
			'add_drop' => $add_drop,
			'tables' => array_reverse($list)
		);

		$backup = &$this->CI->dbutil->backup($prefs);

		// Load the file helper and write the file to your server
		$this->CI->load->helper('file');

		// Load the download helper and send the file to your desktop
		$this->CI->load->helper('download');
		force_download($filename, $backup);
	}

	public function backup2() {

		gvv_info("backup: ");

		if (PHP_OS == "WINNT") {
			$mysqldump = 'c:\xampp_php8\mysql\bin\mysqldump.exe';
		} else {
			// Default on Linux
			$mysqldump = '/usr/bin/mysqldump';
		}

		// check if executable file exists
		if (!file_exists($mysqldump)) {
			throw new Exception("mysqldump $mysqldump not found");
		}

		gvv_debug("backup: $mysqldump found");

		// compute the directory name
		$dirname = getcwd() . "/backups/";

		// Create backup directory if it doesn't exist
		if (!file_exists($dirname)) {
			mkdir($dirname, 0777, true);
		}

		gvv_debug("backup: backup dir $dirname exist");

		// Change directory to backup location
		chdir($dirname);

		// compute a filename
		date_default_timezone_set('Europe/Paris');

		$nom_club = $this->CI->config->item('nom_club');
		$clubid = strtolower(str_replace(' ', '_', $nom_club));
		$dt = date("Ymd_His");

		$this->CI->db->select_max('version');
		$query = $this->CI->db->get('migrations');
		$row = $query->row();
		$migration = $row ? $row->version : 0;

		$database = $this->CI->db->database;

		$filename = $database . "_backup_" . $clubid . "_" . $dt . "_migration_" . $migration . ".sql";
		$zipname = $database . "_backup_" . $clubid . "_" . $dt . "_migration_" . $migration . ".zip";

		gvv_debug("backup: filename=$filename");
		gvv_debug("backup: zipame=$zipname");

		// Get database credentials from config
		$db_user = $this->CI->db->username;
		$db_password = $this->CI->db->password;
		$db_host = $this->CI->db->hostname;

		// Build the command
		$cmd = "$mysqldump --user=$db_user --password=$db_password --host=$db_host $database  > $filename";

		gvv_debug("backup: " . $cmd);
		exec($cmd, $output, $returnVar);
		if ($output) {
			gvv_error("backup: mysqldump output: $output\n");
		}
		if ($returnVar != 0) {
			gvv_error("backup: mysqldump returns: $returnVar\n");
		}

		// check if backup file exists
		if (!file_exists($filename)) {
			gvv_error("backup: $filename does not exist");
			throw new Exception("mysqldump backup file $filename does not exist");
		}

		$cmd = "zip $zipname $filename";
		gvv_debug("backup: cmd: " . $cmd);

		exec($cmd, $output, $returnVar);
		if ($output) {
			gvv_error("backup: zip output: $output\n");
		}
		if ($returnVar != 0) {
			gvv_error("backup: zip returns: $returnVar\n");
		}

		// check if zip file exists
		if (!file_exists($zipname)) {
			gvv_error("backup: $zipname does not exist");
			throw new Exception("mysqldump backup file $zipname not found");
		}

		unlink($filename);

		$this->CI->load->helper('download');
		$data = file_get_contents($zipname);
		force_download($zipname, $data);
	}

	/*
	 * Drop all the GVV tables
	*/
	public function drop_all() {
		foreach ($this->gvv_tables as $table) {
			$this->CI->dbforge->drop_table($table);
		}
	}

	public function sql($sql, $return_result = false) {
		$this->CI->db->query("SET sql_mode='NO_AUTO_VALUE_ON_ZERO'");
		$reqs = preg_split("/;\n/", $sql); // on sépare les requêtes
		$all_results = array();
		foreach ($reqs as $req) { // et on les éxécute
			if (trim($req) != "") {
				// echo "req = $req<br>";
				$res = $this->CI->db->query($req);
				if ($return_result && $res)
					$all_results[] = $res->result_array();
			}
		}
		return $all_results;
	}

	/**
	 * Execute les requêtes sql contenuess dans un fichier
	 * @param unknown $filename
	 * @param string $return_result
	 */
	public function sqlfile($filename, $return_result = false) {
		$sql = file_get_contents($filename);
		return $this->sql($sql, $return_result);
	}
}
