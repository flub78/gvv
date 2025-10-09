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
 * Bitfield utilities for encoding/decoding boolean flags into integers.
 * Used for storing multiple checkboxes/permissions in a single database field.
 */
if (!defined('BASEPATH'))
    exit ('No direct script access allowed');

if (!function_exists('array2int')) {
    /**
     * Combines checkbox values into a bitmask integer
     *
     * @param array|null $boxes Array of powers of 2 (e.g., [1, 4, 16])
     * @return int Bitmask (e.g., 21 from [1, 4, 16])
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
     * Extracts checkbox values from a bitmask integer
     *
     * @param int $i Bitmask integer
     * @return array Powers of 2 as [value => value] (e.g., [1 => 1, 4 => 4])
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
