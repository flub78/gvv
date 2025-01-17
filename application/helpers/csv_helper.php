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
 *    Gestion des fichiers CSV (Comma separated values) format d'export Excel.
 *    
 */
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

if (!function_exists('csv_file')) {

    /**
     * Généère un fichier csv à partir d'un tableau
     *
     * @param unknown_type $data
     * @param unknown_type $nodisplay
     */
    function csv_file($title, $data, $download = true, $header = false) {
        $CI = &get_instance();

        // Load the file helper and write the file to your server
        $CI->load->helper('file');

        // Load the download helper and send the file to your desktop
        $CI->load->helper('download');

        date_default_timezone_set('Europe/Paris');
        $dt =  date("Y_m_d");
        $filename = "gvv_" . $title . "_$dt.csv";
        $filename = strtolower($filename);
        $filename = str_replace(' ', '_', $filename);

        $str = "";
        if ($title)
            $str .= $title . ";\n";
        foreach ($data as $row) {
            if ($header) {        // affichage des noms des champs sur la première ligne
                foreach ($row as $key => $cell) {
                    $str .= $key . ";";
                }
                $str .= "\n";
                $header = False;
            }
            foreach ($row as $cell) {
                $str .= $cell . ";";
            }
            $str .= "\n";
        }

        # $str = iconv('UTF-8', 'windows-1252', $str);

        if ($download) {
            force_download($filename, $str);
        }
        return $str;
    }
}
