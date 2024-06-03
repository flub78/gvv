<?php  
//    GVV Gestion vol à voile
//    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
//
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Generate a submit button. When pressed this button calls
 * a controller and passes a parameter.
 * 
 * Note, as submit buttons uses their own form, they must not 
 * be embedeed inside a form.
 */
class ButtonEdit extends Button
{
	/**
	 * Constructor - Sets Button's Preferences
	 *
	 * The constructor can be passed an array of attributes values
	 * @param unknown_type $label
	 * @param unknown_type $controller
	 * @param unknown_type $param
	 * @return the object
	 */
    public function __construct($attrs = array())
	{
		// Defaults
		$attrs['label'] = 'Changer';
		$attrs['action'] = 'edit';
//		$attrs['image'] = theme() . "/images/update.png";
		$attrs['image'] = theme() . "/images/pencil.png";
		parent::__construct($attrs);
	}
}