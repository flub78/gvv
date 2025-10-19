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
 * Markdown wrapper for Parsedown library
 */

require_once APPPATH . 'libraries/Parsedown.php';

class MY_Parsedown {
    private $parser;
    
    public function __construct() {
        $this->parser = new Parsedown();
    }
    
    public function text($markdown) {
        return $this->parser->text($markdown);
    }
}

/* End of file MY_Parsedown.php */
/* Location: ./application/libraries/MY_Parsedown.php */