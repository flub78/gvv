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
class Coverage extends CI_Controller {
    function __construct() {
        date_default_timezone_set('Europe/Paris');
        parent::__construct();
        // Check if user is logged in or not
        $this->load->library('DX_Auth');
        // if (!getenv('TEST') && !$this->dx_auth->is_logged_in()) {
        // redirect("auth/login");
        // }
        $this->load->library('PersistentCoverage', '', "cov");
    }
    function index() {
        $this->load->view('coverage');
    }

    /**
     * URL to reset the coverage data
     */
    public function reset_coverage() {
        $this->cov->enable();
        echo "coverage enabled" . PHP_EOL;
    }

    /**
     * URL to reset the coverage data
     */
    public function disable_coverage() {
        $this->cov->disable();
        echo "coverage disabled" . PHP_EOL;
    }

    /**
     * Generate the coverage results
     */
    public function coverage_result($format = "html") {
        $this->cov->coverage_result($format);
        // echo "coverage $format results generated" . PHP_EOL;

        // header('http://localhost/gvv2/code-coverage-report/index.html');
    }
}

/* End of file tests.php */
/* Location: ./application/controllers/tests.php */