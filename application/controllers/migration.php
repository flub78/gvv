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
 * Migration de la base de données
 *
 * @filesource migration.php
 * @package controllers
 *
 * Playwright tests:
 *   - npx playwright test tests/migration-test.spec.js
 *
 */
set_include_path(getcwd() . "/..:" . get_include_path());
class Migration extends CI_Controller {
    protected $controller = "migration";
    protected $unit_test = FALSE;

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();

        // Check if user is logged in or not
        if (! $this->dx_auth->is_logged_in()) {
            redirect("auth/login");
        }
        $this->dx_auth->check_uri_permissions();

        $this->load->library('Database');
        $this->load->helper('file');
        $this->load->library('migration');
        $this->config->load('migration');
    }

    /**
     * Fait migrer la base vers une version données
     */
    public function to_level() {
        $target_level = $this->input->post('target_level');
        $program_level = $this->input->post('program_level');
        $base_level = $this->input->post('base_level');

        gvv_info ("Migration: depuis $base_level vers $target_level");

        if ($target_level != $base_level) {
            # TRUE if already latest, FALSE if failed, int if upgraded
            if (! $this->migration->version($target_level)) {
                echo "Migration to $target_level" . br();
                show_error($this->migration->error_string());
                return;
            }
            gvv_info("Migration: migration effectuée vers le niveau $target_level");
            $reached_level = $this->migration->get_version();
            if ($target_level != $reached_level) {
                gvv_error ("Migration: échec de la migration au niveau $target_level, niveau atteint $reached_level");
            } else {
                gvv_info ("Migration: succès de la migration au niveau $target_level");
            }
        }
        redirect(controller_url($this->controller));        
    }
    
    
    public function index() {
        $program_level = $this->config->item('migration_version');
        $data ['program_level'] = $program_level;
        $data ['base_level'] = $this->migration->get_version();

        $levels = array ();
        for($i = $program_level; $i >= 1; $i --) {
            $levels [$i] = $i;
        }
        $data ['levels'] = $levels;

        load_last_view('migration/avant', $data);
    }

    /**
     * Just display phpinfo
     */
    public function info() {
        echo phpinfo();
    }
}