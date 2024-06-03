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
set_include_path(getcwd() . "/..:" . get_include_path());
class Import extends CI_Controller {
    function __construct() {
        date_default_timezone_set('Europe/Paris');
        parent::__construct();
        // Check if user is logged in or not
        $this->load->library('DX_Auth');
        // if (!getenv('TEST') && !$this->dx_auth->is_logged_in()) {
        // redirect("auth/login");
        // }
        $this->load->library('unit_test');
    }
    function index() {
        echo "Import" . br();

        $this->db_of = $this->load->database('of', TRUE);

        $sql = 'select * from flight where aircraft_id="5"';
        $sql = 'select flight.id as flight_id,aircraft_id,start_date,duration,counter_departure,counter_arrival,comments,pilot_id,name,first_name,last_name';

        $sql = 'select name,first_name,last_name,email, address,zipcode,city,state,country,home_phone,work_phone,cell_phone,birthdate,sex,nationality';

        $sql .= ' from flight, flight_pilot,person';
        $sql .= ' where flight_pilot.flight_id=flight.id';
        $sql .= ' and flight_pilot.pilot_id=person.id';
        $sql .= ' and aircraft_id="5"';
        $sql .= ' group by person.id';
        $sql .= ' order by last_name,first_name';
        $query = $this->db_of->query($sql);
        foreach ( $query->result_array() as $row ) {
            // var_dump($row);
        }

        $data = array ();
        $data ['table'] = $query->result_array();
        $data ['request'] = '';
        $data ['attrs'] = array ();
        load_last_view('message', $data);
        // $sql = "select * from roles";
        // $query = $this->db->query($sql);
        // foreach ($query->result_array() as $row)
        // {
        // var_dump($row);
        // }
    }
}

/* End of file tests.php */
/* Location: ./application/controllers/tests.php */