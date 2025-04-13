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
 * @filesource welcome.php
 * @package controllers
 *          Page d'acceuil
 */
class Welcome extends CI_Controller {

    function __construct() {
        parent::__construct();
        // Check if user is logged in or not
        $this->dx_auth->check_login();

        if ($this->config->item('calendar_id')) {
            gvv_debug('google account = ' . $this->config->item('calendar_id'));
            // Commenté ???
            // Uncaught Exception: Google PHP API Client requires the CURL PHP extension
            // $this->load->library('GoogleCal');
        }

        $this->load->helper('validation');
        // Store current URL to reload it after the certificate is granted
        $this->session->set_userdata('return_url', current_url());

        $this->lang->load('welcome');

        $this->load->library('migration');
        $this->config->load('migration');
    }

    function nyi() {
        $data = array();
        $data['title'] = $this->lang->line("welcome_nyi_title");
        $data['text'] = $this->lang->line("welcome_nyi_text");
        load_last_view('message', $data);
    }

    /**
     * Page d'acceuil du comptable
     */
    public function compta() {
        if (! $this->dx_auth->is_role('tresorier')) {
            $this->dx_auth->deny_access();
        }
        load_last_view('welcome/compta', array());
    }

    /**
     * Change l'année courante
     *
     * @param unknown_type $year
     */
    public function new_year($year) {
        $this->session->set_userdata('year', $year);
        redirect("welcome/ca");
    }

    /**
     * Page d'acceuil du comptable
     */
    public function ca() {
        if (! $this->dx_auth->is_role('ca')) {
            $this->dx_auth->deny_access();
        }
        $year = $this->session->userdata('year');
        if (! $year) {
            $year = Date("Y");
            $this->session->set_userdata('year', $year);
        }
        $data = array();
        $this->load->model('ecritures_model');
        $data['year'] = $year;
        $data['controller'] = 'welcome';
        $data['year_selector'] = $this->ecritures_model->getYearSelector("date_op");
        load_last_view('welcome/ca', $data);
    }

    public function about() {

        $this->config->load('version');

        $data = [];
        $data['pwd'] = getcwd();
        $data['commit'] = $this->config->item('commit');
        $data['commit_date'] = $this->config->item('commit_date');
        $data['commit_message'] = $this->config->item('commit_message');

        $data['user'] = exec('whoami');

        $data['date_gel'] = $this->config->item('date_gel');

        $data['program_level'] = $this->config->item('migration_version');
        $data['base_level'] = $this->migration->get_version();

        load_last_view('welcome/about', $data);
    }
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */