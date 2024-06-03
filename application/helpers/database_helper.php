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
 */
if (!defined('BASEPATH'))
    exit ('No direct script access allowed');

if (!function_exists('mysql_real_escape_string')) {

	/**
	 * mysql_real_escape_string est une fonction obsolète en PHP5.5.0 et supprimée en PHP 7.0.0
	 *
	 */
	function mysql_real_escape_string($str) {
		if (function_exists('mysqli_real_escape_string') AND is_object(get_instance()->db->conn_id))	{
			$str = mysqli_real_escape_string(get_instance()->db->conn_id, $str);
		}
		else {
			$str = addslashes($str);
		}
		return $str;
	}
}
