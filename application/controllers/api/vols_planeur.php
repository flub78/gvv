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
 * @filesource vols_planeur.php
 * @package controllers
 */

/**
 *
 * API de test des vols planeur
 *
 * @author Frédéric
 *
 *
 */
class Vols_planeur extends CI_Controller {
    
    protected $controller = 'vols_planeur';
    protected $model = 'vols_planeur_model';
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
        $this->load->model('planeurs_model');
        $this->load->model('terrains_model');
        $this->load->model('events_types_model');
        $this->load->model('vols_planeur_model');
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
        
        $first_flight = $this->vols_planeur_model->latest_flight(array (), "desc");
        
        $json = json_encode($first_flight);
        echo $json;
    }
    
    /**
     * Retourne les vols au format json
     * 
     * [{"vpid":"2897","vpdate":"2023-01-01","vppilid":"asterix","vpmacid":"F-CGAA",
     *   "vpcdeb":"10.00","vpcfin":"10.30","vpduree":"30.00","vpobs":"",
     *   "vpdc":"1","vpcategorie":"0","vpautonome":"3","vpnumvi":"","vpnbkm":"0",
     *   "vplieudeco":"","vplieuatt":"","vpaltrem":"500","vpinst":"panoramix",
     *   "facture":"0","payeur":"","pourcentage":"0","club":"0","saisie_par":"testadmin",
     *   "remorqueur":"F-JUFA","pilote_remorqueur":"abraracourcix","tempmoteur":"0.00",
     *   "reappro":"0","essence":"0","vpticcolle":"0"},
     *  {"vpid":"2898","vpdate":"2023-01-01","vppilid":"goudurix","vpmacid":"F-CGAA",
     *   "vpcdeb":"11.00","vpcfin":"12.15","vpduree":"75.00","vpobs":"",
     *   "vpdc":"1","vpcategorie":"0","vpautonome":"1","vpnumvi":"","vpnbkm":"0",
     *   "vplieudeco":"","vplieuatt":"","vpaltrem":"500","vpinst":"panoramix","facture":"0",
     *   "payeur":"","pourcentage":"0","club":"0","saisie_par":"testadmin",
     *   "remorqueur":"","pilote_remorqueur":"","tempmoteur":"0.00","reappro":"0","essence":"0","vpticcolle":"0"},
     *  {"vpid":"2899","vpdate":"2023-01-01","vppilid":"asterix","vpmacid":"F-CGAB",
     *   "vpcdeb":"11.00","vpcfin":"12.15","vpduree":"75.00","vpobs":"","vpdc":"0","vpcategorie":"0",
     *   "vpautonome":"3","vpnumvi":"","vpnbkm":"0","vplieudeco":"","vplieuatt":"","vpaltrem":"500",
     *   "vpinst":"","facture":"0","payeur":"","pourcentage":"0","club":"0","saisie_par":"testadmin",
     *   "remorqueur":"F-JUFA","pilote_remorqueur":"abraracourcix","tempmoteur":"0.00","reappro":"0","essence":"0",
     *   "vpticcolle":"0"}]
     * 
     * @param string $id
     */
    function get($id = "") {
        
        if ($this->denied()) return;
        
        $where = [];
        if ($id) {
            $where = ['vpid' => $id];
        }
        $flights = $this->vols_planeur_model->get($where);
        
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
    
    /**
     * Nombre de vol planeur
     */
    function count() {
        $flights = $this->vols_planeur_model->get();
        $count = count($flights);
        $res = ['count' => $count];
        $json = json_encode($res);
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
}