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

if (!function_exists('pdf_filename')) {
    function pdf_filename($title) {
        date_default_timezone_set('Europe/Paris');
        $dt = date("Y_m_d");
        $filename = "gvv_" . ($title ?: 'document') . "_$dt.pdf";
        $filename = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $filename);
        $filename = strtolower($filename);
        $filename = str_replace(["'", "'", ' ', '=', '-', ',', '/'], '_', $filename);
        $filename = preg_replace('/_+/', '_', $filename);
        return $filename;
    }
}

if (!function_exists('csv_escape_cell')) {

    /**
     * Encadre une valeur CSV selon la RFC 4180 si elle contient le séparateur
     * (point-virgule), un guillemet ou un saut de ligne ; sinon la renvoie
     * inchangée. Sans cela, une description multi-lignes coupe
     * l'enregistrement en plusieurs lignes à l'import dans un tableur.
     *
     * Le guillemet n'ouvre un champ encadré que s'il en est le premier
     * caractère : la valeur passée doit donc inclure un éventuel espace de
     * tête (cf. csv_spaced_line()).
     *
     * @param mixed $cell Valeur déjà formatée
     * @return string
     */
    function csv_escape_cell($cell) {
        $cell = (string) $cell;
        if (preg_match('/[;"\r\n]/', $cell)) {
            return '"' . str_replace('"', '""', $cell) . '"';
        }
        return $cell;
    }
}

if (!function_exists('csv_spaced_line')) {

    /**
     * Ligne CSV au format historique de GVV "a; b; c; " + saut de ligne.
     * L'espace qui suit chaque séparateur fait partie de la valeur suivante :
     * il est placé à l'intérieur des guillemets quand la valeur doit être
     * encadrée. Les valeurs sans caractère spécial restent inchangées à
     * l'octet près.
     *
     * @param array $cells Valeurs déjà formatées
     * @return string
     */
    function csv_spaced_line($cells) {
        $line = '';
        $first = true;
        foreach ($cells as $cell) {
            $line .= csv_escape_cell(($first ? '' : ' ') . $cell) . ';';
            $first = false;
        }
        return $line . " \n";
    }
}

if (!function_exists('csv_file')) {

    /**
     * Génère un fichier csv à partir d'un tableau
     *
     * @param unknown_type $data
     * @param unknown_type $nodisplay
     */
    function csv_file($title, $data, $download = true, $header = false, $filename_title = null) {
        $CI = &get_instance();

        // Load the file helper and write the file to your server
        $CI->load->helper('file');

        // Load the download helper and send the file to your desktop
        $CI->load->helper('download');

        date_default_timezone_set('Europe/Paris');
        $dt =  date("Y_m_d");
        $fn = ($filename_title !== null) ? $filename_title : $title;
        $filename = "gvv_" . $fn . "_$dt.csv";
        $filename = strtolower($filename);
        $filename = str_replace(' ', '_', $filename);

        $str = "\xEF\xBB\xBF";
        if ($title)
            $str .= csv_escape_cell($title) . ";\n";
        foreach ($data as $row) {
            if ($header) {        // affichage des noms des champs sur la première ligne
                foreach ($row as $key => $cell) {
                    $str .= csv_escape_cell($key) . ";";
                }
                $str .= "\n";
                $header = False;
            }
            foreach ($row as $cell) {
                $formatted_cell = is_numeric($cell) ? str_replace('.', ',', $cell) : $cell;
                $str .= csv_escape_cell($formatted_cell) . ";";
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
