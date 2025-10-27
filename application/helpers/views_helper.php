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
 *    Mécanisme pour afficher des vues spécifiques par club
 */
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

if (!function_exists('load_club_view')) {

    /**
     * Affiche une vue spéciale pour chaque club si elle existe
     *
     * @param unknown_type $view
     * @param unknown_type $data
     * @param unknown_type $nodisplay
     */
    function load_club_view($view, $data, $nodisplay) {
        $CI = &get_instance();
        $club = $CI->config->item('club');
        $filename = "./application/views/" . $view . "_" . $club . ".php";
        if (file_exists($filename)) {
            $view .= "_$club";
        }
        return $CI->load->view($view, $data, $nodisplay);
    }
}

if (!function_exists('load_bs_view')) {

    /**
     * Affiche une vue spéciale avec Bootstrap si elle existe
     *
     * @param unknown_type $view
     * @param string $data
     * @param unknown_type $nodisplay
     */
    function load_bs_view(string $view, $data, $nodisplay) {
        $CI = &get_instance();
        $legacy_gui = $CI->session->userdata('legacy_gui');
        // echo "legacy_gui = $legacy_gui"; exit;

        $path_array = explode('/', $view);
        $path_array[count($path_array) - 1] = 'bs_' . $path_array[count($path_array) - 1];
        $bs_view = implode('/', $path_array);

        $filename = "./application/views/" . $bs_view . ".php";
        if (file_exists($filename)  && !$legacy_gui) {
            $view = $bs_view;
        }

        return $CI->load->view($view, $data, $nodisplay);
    }
}

if (!function_exists('load_last_view')) {
    /**
     * Affiche la dernière vue et le pied de page.
     *
     * Le header est affiché par le constructeur. Il est possible après de charger
     * toutes les vues que l'on veut et l'on finit avec cette fonction.
     *
     * @param unknown_type $view
     * @param unknown_type $data
     * @param unknown_type $nodisplay
     */
    function load_last_view() {
        $args = func_get_args(); // récupère les arguments
        $view = array_shift($args); // retire et retourne le premier argument
        $data = array_shift($args); // retire et retourne le second argument
        $nodisplay = array_shift($args); // retire et retourne le premier

        $res = load_bs_view($view, $data, $nodisplay); // $CI->load->view($view, $data, $nodisplay);
        load_bs_view('footer', null, $nodisplay);
        return $res;
    }
}

/**
 * Ensemble de fonction booléenes utilisées pour configurer les vues
 * 
 * Syntaxe recommendée:
 * 
 * <?php if (has_role('ca')) : ?>
 * <?php if (is_logged_in()) : ?>
 *    ... HTML to include
 * <?php endif; ?>
 *  
 */

if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        $CI = &get_instance();
        return $CI->dx_auth->is_logged_in();
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        $CI = &get_instance();
        return $CI->dx_auth->is_admin();
    }
}

if (!function_exists('has_role')) {
    function has_role($role) {
        $CI = &get_instance();

        if ($CI->dx_auth->is_admin()) {
            return true;
        }

        return $CI->dx_auth->is_role($role, true, true);
    }
}
