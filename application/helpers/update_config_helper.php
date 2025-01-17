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
 */
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

if (!function_exists('update_config')) {

    /**
     * Mise à jour d'un fichier de configuration
     * 
     * Il est souvent pratique d'être capable de mettre à jour un fichier de configuration.
     * CodIgniter ne propose pas cela, set_item n'est persistent que jusqu'à la fin de la requête.
     * 
     * @param $filename nom du fichier
     * @param $data hash de valeurs à remplacer
     * @param $booleans liste des champs booleens
     */
    function update_config($filename, $data, $booleans = array()) {

        if (!get_file_info($filename)) {
            throw new Exception('File $filename not found');
        }

        $content = "";
        $lines = file($filename);
        $comment = FALSE;

        foreach ($lines as $line) {

            if (preg_match("/\/\*.*\*\//", $line, $matches)) {
                // full comment /*      */
                $content .= $line;
            } else if (preg_match("/\/\//", $line, $matches)) {
                // single line comment
                $content .= $line;
            } else if (preg_match("/\/\*/", $line, $matches)) {
                // start comment /*
                $content .= $line;
                $comment = TRUE;
            } else if (preg_match("/\*\//", $line, $matches)) {
                // end of comment */
                $content .= $line;
                $comment = FALSE;
            } else if (preg_match('/\$config\s*\[(.*)\]\s*\=\s*(.*)\s*;/', $line, $matches)) {
                // config line
                if ($comment) {
                    // ignore dans les commentaires
                    $content .= $line;
                } else {
                    $key = trim($matches[1], "'\"");
                    $value = $matches[2];
                    if (array_key_exists($key, $data)) {
                        // si on a une valeur
                        $value = $data[$key];
                        if (in_array($key, $booleans)) {
                            if ($value) {
                                $content .= "\$config['$key'] = TRUE;\n";
                            } else {
                                $content .= "\$config['$key'] = FALSE;\n";
                            }
                        } else {
                            $content .= "\$config['$key'] = $value;\n";
                        }
                    } else {
                        $content .= $line;
                    }
                }
            } else {
                $content .= $line;
            }
        }

        if (!write_file($filename, $content)) {
            throw new Exception("error writing $filename, check that it is writable by your WEB server");
        }
    }
}
