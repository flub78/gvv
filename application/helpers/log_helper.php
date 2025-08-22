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
 *    Mécanisme de log spécifique. C'est un helper plutôt qu'une extension de CI_Log
 *    parce que cela permet de séparer les logs applicatifs des logs systèmes.
 *    
 *    J'aurais préféré un mécanisme de log un peu plus sophistiqué style Log4J ou on peut spécifier
 *    les loggers mais CodeIgniter est une Framework minimaliste (cela a aussi des avantages,
 *    même si cela a quelques inconvénients).
 */
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

if (!function_exists('gvv_log')) {

	/**
	 * Log avec prefix (facilite le filtrage)
	 *
	 */
	function gvv_log($level, $msg, $php_error = FALSE) {
		log_message($level, "GVV: " . $msg, $php_error);
	}
}

if (!function_exists('gvv_info')) {

	/**
	 * Log avec prefix (facilite le filtrage)
	 * Niveau info à utiliser pour l'application
	 */
	function gvv_info($msg, $php_error = FALSE) {
		gvv_log('info', $msg, $php_error);
	}
}

if (!function_exists('gvv_error')) {

	/**
	 * Log avec prefix (facilite le filtrage)
	 *
	 */
	function gvv_error($msg, $php_error = FALSE) {
		gvv_log('error', $msg, $php_error);
	}
}

if (!function_exists('gvv_debug')) {

	/**
	 * Log avec prefix (facilite le filtrage)
	 * Niveau debug à utiliser pour les tests.
	 *
	 */
	function gvv_debug($msg, $php_error = FALSE) {
		gvv_log('debug', $msg, $php_error);
	}
}

if (!function_exists('current_logfile')) {

	/**
	 * Return the file name of the current log file
	 *
	 */
	function current_logfile() {
		$logname = "log-" . date("Y-m-d") . ".php";
		$logpath = getcwd() . "/../application/logs/" . $logname;
		return $logpath;
	}
}

if (!function_exists('occurences')) {
	/**
	 * Retourne la liste des occurences d'une chaine de caractère dans le fichier de log
	 */
	function occurences($pattern) {
		$getText = file_get_contents(current_logfile(), true);
		return substr_count($getText, $pattern);
	}
}

if (!function_exists('gvv_dump')) {
	/**
	 * Prints variable contents with file and line information
	 */
	function gvv_dump($string, $dye = true) {
		$bt = debug_backtrace();
		$caller = $bt[0];
		echo "<pre>";
		echo "gvv_dump from file: " . $caller['file'] . " Line: " . $caller['line'] . "\n";
		print_r($string);
		echo "</pre>";
		if ($dye) {
			exit;
		}
	}
}

if (!function_exists('gvv_assert')) {
	/**
	 * Prints variable contents with file and line information
	 */
	function gvv_assert($assertion, $string, $dye = true) {
		if ($assertion) {
			return;
		}
		$bt = debug_backtrace();
		$caller = $bt[0];
		$msg = "Assertion failed  file: " . $caller['file'] . " Line: " . $caller['line'] . " $string" . "\n";
		gvv_error($msg);
		if ($dye) {
			exit;
		}
	}
}
