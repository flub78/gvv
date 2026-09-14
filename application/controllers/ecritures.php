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
 * @filesource ecritures.php
 * @package controllers
 *
 * Page "Autres écritures" : point d'entrée vers les écritures guidées rares
 * (non couvertes par le menu principal Ecritures) et vers l'écriture générale.
 *
 * @see doc/design_notes/analyse_types_ecritures.md
 */

class Ecritures extends MY_Controller {
    protected $controller = 'ecritures';

    function __construct() {
        parent::__construct();
        $this->require_roles(['tresorier']);

        $this->lang->load('compta');
        $this->lang->load('gvv');
        $this->lang->load('tableaux_de_bord');

        $this->load->vars([
            'nav_back_url'   => 'welcome/section/treasurer',
            'nav_back_label' => $this->lang->line('db_section_treasury'),
        ]);
    }

    /**
     * Page "Autres écritures" : liste les écritures guidées rares et l'écriture générale.
     */
    function index() {
        $this->data['title'] = $this->lang->line('gvv_ecritures_autres_title');
        load_last_view('ecritures/index', $this->data);
    }
}

/* End of file ecritures.php */
/* Location: ./application/controllers/ecritures.php */
