<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * GVV Gestion vol à voile
 * Copyright (C) 2011 Philippe Boissel & Frédéric Peignot
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * Markdown Helper
 */

if (!function_exists('markdown')) {
    /**
     * Convert markdown text to HTML
     * 
     * @param string $text The markdown text to convert
     * @return string The converted HTML
     */
    function markdown($text) {
        $CI =& get_instance();
        if (!isset($CI->my_parsedown)) {
            $CI->load->library('MY_Parsedown', null, 'my_parsedown');
        }
        return $CI->my_parsedown->text($text);
    }
}

/* End of file markdown_helper.php */
/* Location: ./application/helpers/markdown_helper.php */