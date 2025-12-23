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
 *	Support pour la gestion des droits
 */

if (!defined('BASEPATH'))
    exit ('No direct script access allowed');

// define("TRESORIER", 8);
// define("SECRETAIRE", 16);
// define("SECRETAIRE_ADJ", 32);
// define("CA", 64);
// define("CHEF_PI", 128);
// define("VI_PLANEUR", 256);
// define("VI_AVION", 512);
// define("MECANO", 1024);
// define("PILOTE_PLANEUR", 2048);
// define("PILOTE_AVION", 4096);
// define("REMORQUEUR", 8192);
// define("PLIEUR", 16384);
// define("ITP", 32768);
// define("IVV", 65536);
// define("FI_AVION", 131072);
// define("FE_AVION", 262144);
// define("TREUILLARD", 524288);

if (!function_exists('logger_username')) {
    /**
     * Returns the first name and last name of the logged user for display
     *
     * @return string
     */
    function logged_username() {

        $CI = & get_instance();
        return $CI->dx_auth->get_username();
    }
}