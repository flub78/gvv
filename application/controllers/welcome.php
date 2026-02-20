<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

// Load Gvv_Controller base class
require_once(APPPATH . 'core/Gvv_Controller.php');

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
 *
 * Playwright tests:
 *   - npx playwright test tests/migration-test.spec.js
 *   - npx playwright test tests/resultat-par-sections.spec.js
 *   - npx playwright test tests/section-unique.spec.js
 */
class Welcome extends Gvv_Controller {

    function __construct() {
        parent::__construct();
        // Check if user is logged in or not
        $this->dx_auth->check_login();

        // Authorization: Code-based (v2.0) - only for migrated users
        // Authorization: Code-based (v2.0) - only for migrated users
        if ($this->use_new_auth) {
            $this->require_roles(['user']);
        }

        if ($this->config->item('calendar_id')) {
            gvv_debug('google account = ' . $this->config->item('calendar_id'));
            // Commenté ???
            // Uncaught Exception: Google PHP API Client requires the CURL PHP extension
            // $this->load->library('GoogleCal');
        }

        $this->load->helper('validation');
        $this->load->helper('markdown');
        // Store current URL to reload it after the certificate is granted
        $this->session->set_userdata('return_url', current_url());

        $this->lang->load('welcome');
        $this->lang->load('config');

        $this->load->library('migration');
        $this->config->load('migration');

        $this->load->model('clotures_model');
    }

    /**
     * Page d'accueil - Dashboard principal
     */
    public function index() {
        $data = array();

        // Get current username
        $data['username'] = $this->dx_auth->get_username();

        // Récupérer le nom complet du membre si disponible
        $this->load->model('membres_model');
        $membre = $this->membres_model->get_by_id('mlogin', $data['username']);
        if (!empty($membre) && !empty($membre['mprenom']) && !empty($membre['mnom'])) {
            $data['user_name'] = $membre['mprenom'] . ' ' . $membre['mnom'];
        } else {
            $data['user_name'] = $data['username'];
        }

        // Comptes multi-sections pour le pilote courant
        $this->load->model('comptes_model');
        $this->load->model('sections_model');
        $data['user_accounts'] = $this->comptes_model->get_pilote_comptes($data['username']);

        // Check user roles (following bs_menu.php role checks)
        $data['is_planchiste'] = $this->dx_auth->is_role('planchiste');
        $data['is_ca'] = $this->dx_auth->is_role('ca'); // Club admin
        $data['is_bureau'] = $this->dx_auth->is_role('bureau'); // Bureau member

        $data['is_admin'] = $this->dx_auth->is_role('admin'); // System admin
        $data['is_treasurer'] = $this->dx_auth->is_role('tresorier') || $this->dx_auth->is_role('super-tresorier');

        // Check if user is authorized for development/test features
        $dev_menu_users = array_map('trim', explode(',', $this->config->item('dev_menu_users') ?: ''));
        $data['is_dev_authorized'] = in_array($data['username'], $dev_menu_users);

        // Configuration options
        $data['show_calendar'] = ($this->config->item('url_gcalendar') != '');
        $data['ticket_management_active'] = $this->config->item('ticket_management') == true;

        // Formations du pilote (si gestion formations activée)
        $data['user_formations'] = array();
        if ($this->config->item('gestion_formations')) {
            $this->load->model('formation_inscription_model');
            // Récupérer les formations ouvertes et clôturées récemment (derniers 6 mois)
            $formations = $this->formation_inscription_model->get_by_pilote($data['username']);
            $date_limite = date('Y-m-d', strtotime('-6 months'));
            $data['user_formations'] = array_filter($formations, function($f) use ($date_limite) {
                return $f['statut'] === 'ouverte' || 
                       ($f['statut'] === 'cloturee' && $f['date_cloture'] >= $date_limite);
            });
        }

        // MOD (Message of the Day) handling
        $this->load->helper('file');
        // Date du dernier MOD
        $config_file = "./application/config/club.php";
        if (!$info = get_file_info($config_file)) {
            gvv_debug("$config_file non trouvé");
        }
        $mod_date = $info ? $info['date'] : 0;
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

        load_last_view('dashboard', $data);
    }

    /*
     * Set a cookie with the date of the MOD
     */
    function set_cookie() {
        $this->load->helper('file');
        // Date du dernier MOD
        $config_file = "./application/config/club.php";
        if (! $info = get_file_info($config_file)) {
            echo "$config_file non trouvé" . br();
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
        if ($this->use_new_auth) {
            $this->require_roles(['tresorier']);
        } elseif (! $this->dx_auth->is_role('tresorier')) {
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
        if ($this->use_new_auth) {
            $this->require_roles(['ca']);
        } elseif (! $this->dx_auth->is_role('ca')) {
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

        $section_id = $this->session->userdata('section');
        $q = $this->db->query(
            "SELECT gestion_planeurs, gestion_avions FROM sections WHERE id = ?",
            array((int)$section_id)
        );
        $section = $q->row_array();
        $data['any_planeurs'] = !empty($section['gestion_planeurs']);
        $data['any_avions']   = !empty($section['gestion_avions']);

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
        $data['dates_gel'] = $this->clotures_model->section_freeze_dates(true);

        $data['program_level'] = $this->config->item('migration_version');
        $data['base_level'] = $this->migration->get_version();

        $data['site_url'] = site_url();
        $data['base_url'] = base_url();

        load_last_view('welcome/about', $data);
    }
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */