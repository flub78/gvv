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
    exit ('No direct script access allowed');

if (!function_exists('array2int')) {
    /**
     * Encode an array used as selectbox array into an integer
     *
    
     * @return int
     */
    function array2int($boxes) {
        $result = 0;
        if ($boxes) {
            foreach ($boxes as $level) {
                $result |= $level;
            }
        }
        return $result;
    }
}

if (!function_exists('int2array')) {
    /**
     * Translate an int into an array usable at setboxes array
     *
     * @return array
     */
    function int2array($i) {
        $result = array ();
        $exp = 1;
        while ($i) {
            if ($i & 1) {
                $result[$exp] = $exp;
            }
            $i = $i >> 1;
            $exp = $exp << 1;
        }
        return $result;
    }
}