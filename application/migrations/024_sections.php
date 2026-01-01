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
class Migration_Sections extends CI_Migration {

	protected $migration_number;

	/**
	 *
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->migration_number = 24;
		$this->load->library('database');
	}

	/*
	 * Execute an array of sql requests
	 */
	private function run_queries($sqls = array()) {
		$errors = 0;
		foreach ($sqls as $sql) {
			gvv_info("Migration sql: " . $sql);
			if (!$this->db->query($sql)) {
				$mysql_msg = $this->db->_error_message();
				$mysql_error = $this->db->_error_number();
				gvv_error("Migration error: code=$mysql_error, msg=$mysql_msg");
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

		// Step 1: Check and drop existing FK constraints that reference sections
		echo "Migration 24: Checking for existing FK constraints to sections...\n";
		gvv_info("Migration 24: Checking for existing FK constraints to sections");
		$fk_constraints = array();

		// Use INFORMATION_SCHEMA to find FK constraints that reference sections
		$fk_query = $this->db->query("
			SELECT
				TABLE_NAME,
				CONSTRAINT_NAME,
				COLUMN_NAME,
				REFERENCED_TABLE_NAME,
				REFERENCED_COLUMN_NAME
			FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
			WHERE TABLE_SCHEMA = DATABASE()
				AND REFERENCED_TABLE_NAME = 'sections'
		");

		if ($fk_query) {
			foreach ($fk_query->result_array() as $fk) {
				$fk_constraints[] = array(
					'table' => $fk['TABLE_NAME'],
					'constraint' => $fk['CONSTRAINT_NAME'],
					'column' => $fk['COLUMN_NAME'],
					'ref_table' => $fk['REFERENCED_TABLE_NAME'],
					'ref_column' => $fk['REFERENCED_COLUMN_NAME'],
					'on_delete' => 'CASCADE'
				);
				echo "  Found FK: {$fk['CONSTRAINT_NAME']} on {$fk['TABLE_NAME']}\n";
			}
		}

		// Drop existing FK constraints
		foreach ($fk_constraints as $fk) {
			$sql = "ALTER TABLE `{$fk['table']}` DROP FOREIGN KEY `{$fk['constraint']}`";
			echo "  Dropping FK: {$fk['constraint']}...\n";
			gvv_info("Migration sql: " . $sql);
			if (!$this->db->query($sql)) {
				$mysql_msg = $this->db->_error_message();
				$mysql_error = $this->db->_error_number();
				$error_msg = "Migration error: code=$mysql_error, msg=$mysql_msg, sql=$sql";
				echo "ERROR: $error_msg\n";
				gvv_error($error_msg);
				$errors++;
			}
		}

		if ($errors > 0) {
			$error_msg = "Migration 24: Failed to drop FK constraints, errors=$errors";
			echo "ERROR: $error_msg\n";
			gvv_error($error_msg);
			throw new Exception($error_msg);
		}

		// Step 2: Create sections table
		echo "Migration 24: Creating sections table...\n";
		$filename = getcwd() . '/application/migrations/sections.sql';
		if (!file_exists($filename)) {
			$error_msg = "Migration 24: File not found: $filename";
			echo "ERROR: $error_msg\n";
			gvv_error($error_msg);
			throw new Exception($error_msg);
		}
		gvv_info("Migration 24: Executing $filename");

		// Drop table if it exists (for idempotence)
		$this->db->query("DROP TABLE IF EXISTS sections");
		echo "  Dropped existing table if any\n";

		try {
			$this->database->sqlfile($filename);

			// Verify table was created using SQL query
			$query = $this->db->query("SHOW TABLES LIKE 'sections'");
			if (!$query || $query->num_rows() == 0) {
				$error_msg = "Migration 24: Failed to create sections table";
				echo "ERROR: $error_msg\n";
				gvv_error($error_msg);
				throw new Exception($error_msg);
			}

			// Verify we have at least one section
			$count_query = $this->db->query("SELECT COUNT(*) as cnt FROM sections");
			$count = $count_query->row()->cnt;
			echo "  sections table created successfully with $count row(s)\n";

			if ($count == 0) {
				echo "  WARNING: sections table is empty\n";
			}

		} catch (Exception $e) {
			$error_msg = "Migration 24: Exception executing sections.sql: " . $e->getMessage();
			echo "ERROR: $error_msg\n";
			gvv_error($error_msg);
			throw $e;
		}

		// Step 3: Recreate FK constraints
		if (count($fk_constraints) > 0) {
			echo "Migration 24: Recreating FK constraints...\n";
			foreach ($fk_constraints as $fk) {
				$sql = "ALTER TABLE `{$fk['table']}` ADD CONSTRAINT `{$fk['constraint']}` " .
				       "FOREIGN KEY (`{$fk['column']}`) REFERENCES `{$fk['ref_table']}` (`{$fk['ref_column']}`) " .
				       "ON DELETE {$fk['on_delete']}";
				echo "  Adding FK: {$fk['constraint']} on {$fk['table']}...\n";
				gvv_info("Migration sql: " . $sql);
				if (!$this->db->query($sql)) {
					$mysql_msg = $this->db->_error_message();
					$mysql_error = $this->db->_error_number();
					$error_msg = "Migration error: code=$mysql_error, msg=$mysql_msg, sql=$sql";
					echo "ERROR: $error_msg\n";
					gvv_error($error_msg);
					$errors++;
				}
			}

			if ($errors > 0) {
				$error_msg = "Migration 24: Failed to recreate FK constraints, errors=$errors";
				echo "ERROR: $error_msg\n";
				gvv_error($error_msg);
				throw new Exception($error_msg);
			}
		} else {
			echo "  No FK constraints to recreate\n";
		}

		echo "Migration 24: SUCCESS - Database upgraded to version {$this->migration_number}\n";
		gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");
		return true;
	}

	/**
	 * Reverse the migration
	 */
	public function down() {
		$errors = 0;
		$sqls = array(
			"DROP TABLE IF EXISTS sections"
		);

		$errors += $this->run_queries($sqls);
		gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");

		return !$errors;
	}
}
