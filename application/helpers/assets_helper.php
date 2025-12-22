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
 *	  Gestion des chemins des ressources
 */
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

function theme() {
    $CI = &get_instance();
    $theme = $CI->config->item('theme');
    return base_url() . "themes/" . $theme;
}

if (!function_exists('css_url')) {
    function css_url($nom) {
        return theme() . "/css/" . $nom . '.css';
    }
}

if (!function_exists('js_url')) {
    function js_url($nom) {
        return base_url() . 'assets/javascript/' . $nom . '.js';
    }
}

if (!function_exists('image_dir')) {
    /**
     * 
     * Répèrtoire de stockage des images, graphs, etc
     */
    function image_dir() {
        return 'assets/images/';
    }
}

if (!function_exists('img_url')) {
    function img_url($nom) {
        return theme() . '/images/' . $nom;
    }
}

if (!function_exists('asset_url')) {
    function asset_url($nom) {
        return theme() . '/assets/' . $nom;
    }
}

if (!function_exists('controller_url')) {
    function controller_url($nom) {
        // If $nom is already a full URL, return it as-is
        if (preg_match('/^https?:\/\//', $nom)) {
            return $nom;
        }

        // site_url() already handles the URI parameter correctly
        return site_url($nom);
    }
}
