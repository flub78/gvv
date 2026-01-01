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
class Migration_Roles_per_section extends CI_Migration {

	protected $migration_number;

	/**
	 *
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->migration_number = 25;
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

		// Step 1: Check and drop existing FK constraints that reference types_roles
		echo "Migration 25: Checking for existing FK constraints to types_roles...\n";
		gvv_info("Migration 25: Checking for existing FK constraints to types_roles");
		$fk_constraints = array();

		// Use INFORMATION_SCHEMA to find FK constraints - more reliable than SHOW CREATE TABLE
		$fk_query = $this->db->query("
			SELECT
				TABLE_NAME,
				CONSTRAINT_NAME,
				COLUMN_NAME,
				REFERENCED_TABLE_NAME,
				REFERENCED_COLUMN_NAME
			FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
			WHERE TABLE_SCHEMA = DATABASE()
				AND REFERENCED_TABLE_NAME = 'types_roles'
				AND TABLE_NAME IN ('role_permissions', 'data_access_rules', 'email_list_roles')
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
			$error_msg = "Migration 25: Failed to drop FK constraints, errors=$errors";
			echo "ERROR: $error_msg\n";
			gvv_error($error_msg);
			throw new Exception($error_msg);
		}

		// Step 2: Create types_roles table
		echo "Migration 25: Creating types_roles table...\n";
		$filename = getcwd() . '/application/migrations/types_roles.sql';
		if (!file_exists($filename)) {
			$error_msg = "Migration 25: File not found: $filename";
			echo "ERROR: $error_msg\n";
			gvv_error($error_msg);
			throw new Exception($error_msg);
		}
		gvv_info("Migration 25: Executing $filename");

		// Drop table if it exists (for idempotence)
		$this->db->query("DROP TABLE IF EXISTS types_roles");

		try {
			$this->database->sqlfile($filename);
			// Verify table was created using SQL query to avoid cache
			$query = $this->db->query("SHOW TABLES LIKE 'types_roles'");
			if (!$query || $query->num_rows() == 0) {
				$error_msg = "Migration 25: Failed to create types_roles table";
				echo "ERROR: $error_msg\n";
				gvv_error($error_msg);
				throw new Exception($error_msg);
			}
			echo "  types_roles table created successfully\n";
		} catch (Exception $e) {
			$error_msg = "Migration 25: Exception executing types_roles.sql: " . $e->getMessage();
			echo "ERROR: $error_msg\n";
			gvv_error($error_msg);
			throw $e;
		}

		// Step 3: Create user_roles_per_section table
		echo "Migration 25: Creating user_roles_per_section table...\n";
		$filename = getcwd() . '/application/migrations/user_roles_per_section.sql';
		if (!file_exists($filename)) {
			$error_msg = "Migration 25: File not found: $filename";
			echo "ERROR: $error_msg\n";
			gvv_error($error_msg);
			throw new Exception($error_msg);
		}
		gvv_info("Migration 25: Executing $filename");

		// Drop table if it exists (for idempotence)
		$this->db->query("DROP TABLE IF EXISTS user_roles_per_section");
		echo "  Dropped existing table if any\n";

		try {
			$this->database->sqlfile($filename);

			// Verify table was created using SQL query
			$query = $this->db->query("SHOW TABLES LIKE 'user_roles_per_section'");
			if (!$query || $query->num_rows() == 0) {
				$error_msg = "Migration 25: Failed to create user_roles_per_section table";
				echo "ERROR: $error_msg\n";
				gvv_error($error_msg);
				throw new Exception($error_msg);
			}

			// Verify FK constraints were created
			$fk_check = $this->db->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_roles_per_section' AND CONSTRAINT_TYPE = 'FOREIGN KEY'")->row();
			echo "  Found {$fk_check->cnt} FK constraints\n";
			if ($fk_check->cnt < 3) {
				$error_msg = "Migration 25: user_roles_per_section table created but missing FK constraints (found {$fk_check->cnt}, expected 3)";
				echo "ERROR: $error_msg\n";
				gvv_error($error_msg);
				throw new Exception($error_msg);
			}
			echo "  user_roles_per_section table created successfully with all FK constraints\n";
		} catch (Exception $e) {
			$error_msg = "Migration 25: Exception executing user_roles_per_section.sql: " . $e->getMessage();
			echo "ERROR: $error_msg\n";
			gvv_error($error_msg);
			throw $e;
		}

		// Step 4: Execute role-mapping
		echo "Migration 25: Executing role-mapping...\n";
		$filename = getcwd() . '/application/migrations/role-mapping.sql';
		if (!file_exists($filename)) {
			$error_msg = "Migration 25: File not found: $filename";
			echo "ERROR: $error_msg\n";
			gvv_error($error_msg);
			throw new Exception($error_msg);
		}
		gvv_info("Migration 25: Executing $filename");

		try {
			$this->database->sqlfile($filename);
			echo "  role-mapping executed successfully\n";
		} catch (Exception $e) {
			$error_msg = "Migration 25: Exception executing role-mapping.sql: " . $e->getMessage();
			echo "ERROR: $error_msg\n";
			gvv_error($error_msg);
			throw $e;
		}

		// Step 5: Recreate FK constraints
		if (count($fk_constraints) > 0) {
			echo "Migration 25: Recreating FK constraints...\n";
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
				$error_msg = "Migration 25: Failed to recreate FK constraints, errors=$errors";
				echo "ERROR: $error_msg\n";
				gvv_error($error_msg);
				throw new Exception($error_msg);
			}
		} else {
			echo "  No FK constraints to recreate\n";
		}

		echo "Migration 25: SUCCESS - Database upgraded to version {$this->migration_number}\n";
		gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");
		return true;
	}

	/**
	 * Reverse the migration
	 */
	public function down() {
		$errors = 0;

		// Step 1: Drop FK constraints that reference types_roles
		gvv_info("Migration 25 down: Dropping FK constraints to types_roles");
		$fk_constraints = array();

		if ($this->db->table_exists('role_permissions')) {
			$query = $this->db->query("SHOW CREATE TABLE role_permissions");
			if ($query && $query->num_rows() > 0) {
				$row = $query->row_array();
				if (isset($row['Create Table']) && strpos($row['Create Table'], 'fk_role_permissions_role') !== false) {
					$fk_constraints[] = array('table' => 'role_permissions', 'constraint' => 'fk_role_permissions_role');
				}
			}
		}

		if ($this->db->table_exists('data_access_rules')) {
			$query = $this->db->query("SHOW CREATE TABLE data_access_rules");
			if ($query && $query->num_rows() > 0) {
				$row = $query->row_array();
				if (isset($row['Create Table']) && strpos($row['Create Table'], 'fk_data_access_rules_role') !== false) {
					$fk_constraints[] = array('table' => 'data_access_rules', 'constraint' => 'fk_data_access_rules_role');
				}
			}
		}

		if ($this->db->table_exists('email_list_roles')) {
			$query = $this->db->query("SHOW CREATE TABLE email_list_roles");
			if ($query && $query->num_rows() > 0) {
				$row = $query->row_array();
				if (isset($row['Create Table']) && strpos($row['Create Table'], 'fk_elr_types_roles_id') !== false) {
					$fk_constraints[] = array('table' => 'email_list_roles', 'constraint' => 'fk_elr_types_roles_id');
				}
			}
		}

		// Drop FK constraints
		foreach ($fk_constraints as $fk) {
			$sql = "ALTER TABLE `{$fk['table']}` DROP FOREIGN KEY `{$fk['constraint']}`";
			gvv_info("Migration sql: " . $sql);
			if (!$this->db->query($sql)) {
				$mysql_msg = $this->db->_error_message();
				$mysql_error = $this->db->_error_number();
				gvv_error("Migration error: code=$mysql_error, msg=$mysql_msg, sql=$sql");
				$errors++;
			}
		}

		if ($errors > 0) {
			gvv_error("Migration 25 down: Failed to drop FK constraints, errors=$errors");
			return false;
		}

		// Step 2: Drop tables
		$sqls = array(
			"DROP TABLE IF EXISTS user_roles_per_section",
			"DROP TABLE IF EXISTS types_roles"
		);
		$errors += $this->run_queries($sqls);

		if ($errors > 0) {
			gvv_error("Migration 25 down: Failed to drop tables, errors=$errors");
			return false;
		}

		gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
		return true;
	}
}
