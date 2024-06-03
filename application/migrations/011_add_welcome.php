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
 * Ajout d'autorisation URI
 *    
 * @author frederic
 *
 */
class Migration_Add_Welcome extends CI_Migration {

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
    	$role_id = 1;
    	$uri = "/welcome/";
        $this->load->model('dx_auth/permissions', 'permissions');

        $allowed_uris = $this->permissions->get_permission_value($role_id, 'uri');
    	$allowed_uris[] = "$uri";
        $this->permissions->set_permission_value($role_id, 'uri', $allowed_uris);       
		gvv_info("Migration database up to 11");
	}

	/**
	 * Retour 010 -> 009
	 */
	public function down()
	{
    	$role_id = 1;
    	$uri = "/welcome/";
        $this->load->model('dx_auth/permissions', 'permissions');

        $allowed_uris = $this->permissions->get_permission_value($role_id, 'uri');
    	$tmp = array();
    	foreach ($allowed_uris as $value) {
    		if ($value != $uri) {
    			$tmp[] = $value;
    		}
    	}
        $this->permissions->set_permission_value($role_id, 'uri', $tmp);       
		gvv_info("Migration database down to 10");
	}
}
