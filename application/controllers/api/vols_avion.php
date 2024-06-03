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
 * @filesource vols_avion.php
 * @package controllers
 */

/**
 *
 * API de test des vols avion
 *
 * @author Frédéric
 *
 *
 */
class Vols_avion extends CI_Controller {
    
    protected $controller = 'vols_avion';
    protected $model = 'vols_avion_model';
    protected $kid = 'vaid';
    protected $modification_level = 'planchiste';

    // Headers and first colomns
    protected $title_row;
    protected $first_col;
    protected $pm_first_row;

    // régles de validation
    protected $rules = array (
            'vanbpax' => "is_natural|max_length[1]",
            'vaatt' => "is_natural"
    );

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();
        $this->load->model('membres_model');
        $this->load->model('avions_model');
        $this->load->model('terrains_model');
        $this->load->model('events_types_model');
        $this->load->model('vols_avion_model');
        $this->config->load('program');
        
    }

    function denied() {
        if (!$this->config->item('test_api')) {
            return $this->output
            ->set_content_type('application/json')
            ->set_status_header(405)
            ->set_output(json_encode([
                'text' => 'Error 405',
                'type' => 'Access denied'
            ]));
            $this->error("REQUEST_DENIED", "Test API");
        } else {
            return false;
        }
    }
       
    /*
     * Latest flight
     */
    function ajax_latest() {
        
        if ($this->denied()) return;
        
        $first_flight = $this->vols_avion_model->latest_flight(array (), "desc");
        
        $json = json_encode($first_flight);
        echo $json;
    }
    
    function get($id = "") {
        
        if ($this->denied()) return;
        
        $flights = $this->vols_avion_model->get();
        
        if ($id) {
            foreach ($flights as $flight) {
                if ($id == $flight['vaid']) {
                    $json = json_encode($flight);
                } else {
                    $json = json_encode(["error" => 404, "message" => "flight not found"]);
                }
            }
        } else {
            $json = json_encode($flights);
        }
        echo $json;
    }
    
    function delete($id) {
        echo "delete $id\n";
    }
    

    function post () {
        echo "post\n";
    }
    
    function put() {
        echo "put\n";
    }
    
    /**
     * Nombre de vol planeur
     */
    function count() {
        $flights = $this->vols_avion_model->get();
        $count = count($flights);
        $res = ['count' => $count];
        $json = json_encode($res);
        echo $json;
    }
}