<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');
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
 */
include('./application/libraries/Gvv_Controller.php');
class Dbchecks extends Gvv_Controller {

    protected $model = 'dbchecks_model';

    function __construct() {
        date_default_timezone_set('Europe/Paris');
        parent::__construct();

        // Check if user is logged in or not
        $this->load->library('DX_Auth');
        // if (!getenv('TEST') && !$this->dx_auth->is_logged_in()) {
        // redirect("auth/login");
        // }        
    }

    function index() {
        $dt = $this->gvv_model->unreferenced_accounts();
        $data['wrong_lines'] = $dt['lines'];
        $data['wrong_accounts'] = $dt['accounts'];
        $data['wrong_purchases'] = $dt['bad_purchase_lines'];

        load_last_view('checks/dbchecks', $data);
    }

    function volsp() {
        $dt = $this->gvv_model->volsp_references();
        $dt['title'] = "Vols planeur";
        load_last_view('checks/volsp', $dt);
    }

    function volsa() {
        $dt = $this->gvv_model->volsa_references();
        $dt['title'] = "Vols avion/ULM";
        load_last_view('checks/volsp', $dt);
    }

    function achats() {
        $dt = $this->gvv_model->achats_references();
        $dt['title'] = "Achats";
        load_last_view('checks/achats', $dt);
    }

    function soldes() {
        $dt = $this->gvv_model->soldes();
        $dt['title'] = "Vérification des soldes des comptes (seuls les comptes en erreur sont affichés)";
        echo $dt['title'];
        exit;
    }
}

/* End of file tests.php */
/* Location: ./application/controllers/tests.php */