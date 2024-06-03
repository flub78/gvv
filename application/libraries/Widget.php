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
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Base class for the graphical elements.
 *
 * Light support for graphical elements. I find convenient to use
 * objects rather than functions either in WEB development where objects
 * have really short lives because they can be setup steps by steps instead
 * of building everything before to call a function.
 */
class Widget {
    // Class attributes
    protected $attr = array ();

    /**
     * Constructor - Sets Widget Preferences
     *
     * The constructor can be passed an array of attributes values
     */
    public function __construct($attrs = array ()) {
        // set object attributes
        foreach ( $attrs as $key => $value ) {
            $this->attr [$key] = $attrs [$key];
        }
    }

    /**
     * Set the value of an attribute.
     *
     *
     * The default implementation destroys
     * all encapsulation of object oriented programming. It is convenient
     * as long as there is no more than a few of these objects. Do not use this approach
     * for large projects.
     *
     * @param unknown_type $field
     * @param unknown_type $value
     */
    public function set($field, $value) {
        $this->attr [$field] = $value;
    }

    /**
     * Get the value of an attribute
     *
     * @param unknown_type $field
     */
    public function get($field) {
        return $this->attr [$field];
    }

    /**
     * Image of the widget
     *
     * @return s a string containing HTML code
     */
    public function image() {
        return "";
    }

    /**
     * Display the widget
     */
    public function display() {
        echo $this->image();
    }
}