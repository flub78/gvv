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
 *
 * Experimentation Calendar
 */
set_include_path(getcwd() . "/..:" . get_include_path());
class Calendar extends CI_Controller {

    function __construct() {
        date_default_timezone_set('Europe/Paris');
        parent::__construct();
        // Check if user is logged in or not
        $this->load->library('DX_Auth');
        if (! getenv('TEST') && ! $this->dx_auth->is_logged_in() && ! $this->dx_auth->is_admin()) {
            redirect("auth/login");
        }
        $this->load->library('unit_test');
    }

    /*
     * Set a cookie with the date of the MOD
     */
    function set_cookie() {
        $this->load->helper('file');
        // Date du dernier MOD
        $config_file = "./application/config/club.php";
        if (! $info = get_file_info($config_file)) {
            echo "$filename non trouvé" . br();
        }
        $mod_date = $info['date'];
        $this->load->helper('cookie');

        $this->input->set_cookie(array(
            'name' => 'mod_date',
            'value' => $mod_date,
            'expire' => 86500 * 7,
            'prefix' => 'gvv_'
        ));

        $json = json_encode(array(
            'status' => "OK",
            'action' => 'set_cookie'
        ));
        gvv_debug("json = $json");
        echo $json;
    }

    /**
     * Affiche le calendrier
     */
    function index() {
        $this->load->model('membres_model');
        $this->lang->load('membre');

        $data = array();
        $data['pilote_selector'] = $this->membres_model->selector_with_null(array(
            'actif' => "1"
        ));

        $data['is_ca'] = $this->dx_auth->is_role('ca', true, true);
        $data['mlogin'] = $this->membres_model->default_id();
        $data['event_id'] = "";

        // MOD
        $this->load->helper('file');
        // Date du dernier MOD
        $config_file = "./application/config/club.php";
        if (! $info = get_file_info($config_file)) {
            echo "$filename non trouvé" . br();
        }
        $mod_date = $info['date'];
        $this->load->helper('cookie');

        $cookie = get_cookie('gvv_mod_date');

        if ($cookie && ($mod_date <= $cookie)) {
            // Cookie set et mod est plus vieux
            // on affiche rien
            $data['mod'] = '';
        } else {
            // pas de cookie ou MOD est plus récent
            $data['mod'] = $this->config->item('mod');
        }

        $data['cal_id'] = $this->config->item('calendar_id');
        load_last_view('calendar', $data);
    }
}

/* End of file tests.php */
/* Location: ./application/controllers/tests.php */