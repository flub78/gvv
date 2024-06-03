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

class Coverage_Mgr {

	protected $CI;

	/**
	 * Log avec prefix (facilite le filtrage)
	 *
	 */
	function start() {
		$this->CI =& get_instance();
		gvv_debug("start coverage");
		$this->CI->load->library('PersistentCoverage', '', "cov");
		$this->CI->cov->start();
	}

	/**
	 * Log avec prefix (facilite le filtrage)
	 * Niveau info à utiliser pour l'application
	 */
	function stop() {
		$this->CI =& get_instance();
		gvv_debug("stop coverage");
		$this->CI->load->library('PersistentCoverage', '', "cov");
		$this->CI->cov->stop();
	}

}