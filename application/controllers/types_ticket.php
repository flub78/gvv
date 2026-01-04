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
 * @filesource type_ticket.php
 * @package controllers
 * Controleur de gestion des types_ticket.
 */
include ('./application/libraries/Gvv_Controller.php');
class Types_ticket extends Gvv_Controller {

    // Tout le travail est fait par le parent
    protected $controller = 'types_ticket';
    protected $model = 'types_ticket_model';
    protected $modification_level = 'ca';
    protected $rules = array ();

    /**
     * Constructeur
     */
    function __construct() {
        parent::__construct();
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::form_static_element()
     */
    function form_static_element($action) {
        parent::form_static_element($action);
    }
}