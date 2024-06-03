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
 */
 
if (!defined('BASEPATH'))
    exit ('No direct script access allowed');

/**
 * Returns a variable width string of spaces.
 * Generated HTML indentation. It is more convenient to
 * generate clean indented HTML when you need to read it for analysis.
 *
 * @param unknown_type $nb
 */
function tab($nb) {
    $pattern = '    ';
    $res = "";
    for ($i = 0; $i < $nb; $i++) {
        $res .= $pattern;
    }
    return $res;
}

/**
 * Generates a menu. Basically a menu is just a structured
 * list of anchors. This menu may have conditional sub items. The
 * goal is to support disabled or invisible entries according to the user
 * level of authorization.
 */ 
class Menu {

	protected $CI;
	
    /**
     * Constructor 
     */
    public function __construct ($attrs = array ()) {
    	$this->CI = & get_instance();    	
    }

    /**
     * Génération d'un menu en HTML
     * @param unknown_type $menu
     */
    public function html ($menu, $level = 0, $li = false, $button_class = "") {
    	
    	$ul_attr = 'data-role="listview" data-divider-theme="b" data-inset="true"';
    	$li_attr = 'data-theme="c"';
    	$anchor_attr = 'data-transition="slide"';
    	
    	$res = "";
    	
    	// Si on est pas admin
    	if (!$this->CI->dx_auth->is_admin()) {
    		// Et qu'une autorisation est requise
    		if (isset($menu['role']) && $menu['role']) {
    			// Et qu'on ne l'a pas
    			if (!$this->CI->dx_auth->is_logged_in()) {
    				return $res;
    			}
    			if (!$this->CI->dx_auth->is_role($menu['role'], true, true)) {
    				// Boum
    				return $res;
    			}
    		}
    	}
    	
    	$class = (isset($menu['class'])) ? 'class="' . $menu['class'] .'"' : "";
    	$href = (isset($menu['url'])) ? 'href="' . $menu['url'] .'"' : '';
    	$label = (isset($menu['label'])) ? $menu['label'] : '';

    	if ($li) $res .= "<li $class $li_attr>";
    	    	
    	if ($href || $label) 
	    	$res .= "<a $href $button_class $anchor_attr>$label</a>";

    	if (isset($menu['submenu'])) {
    		// $res .= tab($level) . "<ul $class>\n";
    		$res .= tab($level) . "<ul $ul_attr>\n";
    		foreach ($menu['submenu'] as $elt) {
    			$res .= tab($level);
    			$res .= $this->html($elt, $level + 1, true, $button_class);
    			$res .= "\n"; 
    		}
    		$res .= tab($level) . "</ul>\n"; 
    	}

    	if ($li) $res .= '</li>';
    	
    	return $res;
    }
}