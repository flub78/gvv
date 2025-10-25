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
 * @filesource alarmes.php
 * @package controllers
 *          Controleur des alarmes et conditions d'expérience
 *          
 * Vérifications:
 * 		- licence assurance dans la période de validité
 * 
 *      - Médical dans la période de validité
 *      
 *      - Si le pilote à un BPP
 *      	- moins de 2 ans ou contrôle depuis moins de 2 ans OK
 *          
 *          - si BPP ou contrôle > 2 ans
 *          	6 heures 10 lancés
 *          	ou 3 heures, 5 lancés et 3 vols en DC
 *
 *		- Si le pilote à une SPL
 *			- moins de 2 ans OK
 *			
 *			- Si SPL ou contrôle > 2 ans
 *				5 heure et 15 lancés CDB
 *				2 vols avec instructeur
 *
 */

include(APPPATH . '/third_party/Requests.php');
// Next, make sure Requests can load internal classes
Requests::register_autoloader();

include_once (APPPATH . '/libraries/Gvv_Controller.php');
class Alarmes extends Gvv_Controller {

    protected $controller = 'alarmes';
    protected $model = 'membres_model';
    protected $modification_level = 'ca'; // Legacy authorization for non-migrated users
    protected $rules = array ();
    protected $filter_variables = array ();

    /**
     * Constructeur
     */
    function __construct() {
        parent::__construct();

        // Authorization: Code-based (v2.0) - only for migrated users
        if ($this->use_new_auth) {
            $this->require_roles(['ca']);
        }

        $this->load->model('membres_model');
        $this->load->model('event_model');
        $this->load->model('vols_planeur_model');
        $this->load->config('club');
		$this->load->helper('wsse');
		
		// To have full var_dump
		ini_set('xdebug.var_display_max_depth', 5);
		ini_set('xdebug.var_display_max_children', 256);
		ini_set('xdebug.var_display_max_data', 1024);
    }


    /**
     * 
     * @param unknown $mlogin
     */
    private function licences($mlogin, $year) {
    	$result = array(
    		'licence_ok' => false,
    		'licence_type' => ''
    	);
    	
    	if ($this->config->item('ffvv_pwd')) {
    		# Use HEVA to find the licence
    		$membre = $this->membres_model->get_first(array('mlogin' => $mlogin));
    		
    		if (isset($membre["licfed"])) {
				$licfed = $membre ["licfed"];
				
				$request = heva_request ( "/persons/$licfed" );
				if (! $request->success) {
					echo "status_code = " . $request->status_code . br ();
					echo "success = " . $request->success . br ();
					return;
				}
				
				$licences = json_decode ( $request->body, true );
				
				foreach ( $licences ['players'] as $licence ) {
					$season = $licence ['season'] ['short_name'];
					$type = $licence ['type'] ['name'];
					
					if ($season == $year) {
						$result = array (
								'licence_ok' => true,
								'licence_type' => $type 
						);
						
						
					}
				}
			} else {
				// membre sans numéro de licence fédérale
				$result['licence_type'] = "Votre numéro de licence n'est pas renseigné dans GVV.";
			}
    		
    	} else {
    		# use GVV to find the licence
    	}
    	return $result;
    }

    /**
     *
     * @param unknown $mlogin
     */
    private function medical($mlogin) {
    	
    	$validity = $this->event_model->medical_validity_date($mlogin);
    	
    	if ($validity) {
    		$result = array('medical_ok' => true, 
    		'medical_message' => "Votre visite médicale est valable jusqu'au " . date_db2ht($validity));
    	} else {
    		$result = array('medical_ok' => false,
    				'medical_message' => "Vous n'avez pas de visite médicale en cours de validité.");
    	}
    	return $result;    	 
    }
    
    
    /**
     *
     * @param unknown $mlogin
     */
    private function brevet($mlogin) {
    	     	
     	
    	$result = array();
    	$result['moins_de_2ans'] = false;
    	$result['moins_de_6ans'] = false;
    	$result['dernier_controle'] = false;
    	$result['depuis_test'] = 6;
    	 
    	$now = new DateTime("now");
    	// brevet de plus de 2 ans ?
     	$date = $this->event_model->bpp_date($mlogin);
    	$result['brevet'] = $date;
     	if ($date) {
    		$date_licence = new DateTime($date);
    		$interval = date_diff($now, $date_licence, true);
    		$result['moins_de_2ans'] = $interval->y < 2;
    		$result['moins_de_6ans'] = $interval->y < 6;
    		$result['depuis_test'] = $interval->y;
    		$result['date_test'] = $date;
    	}
    	
    	$date = $this->event_model->controle_date($mlogin);
    	if ($date) {
    		$date_controle = new DateTime($date);
    		$interval = date_diff($now, $date_controle, true);
    		$result['moins_de_6ans'] |= $interval->y < 6;    		
    		$result['depuis_test'] = $interval->y;
    		$result['date_test'] = $date;
    	}
    	
    	$result['emport_passager'] = $this->event_model->passager_date($mlogin);
    	$validity_inst = $this->event_model->inst_validity($mlogin);
    	$result['validity_inst'] = $validity_inst;
    	if ($validity_inst) {
   			$result['inst_valid'] = (strtotime('now') <= strtotime($validity_inst));
    	}
    	 
    	return $result;
    }

    /**
     *
     * @param unknown $mlogin
     */
    private function experience_recente($mlogin) {

    	$depuis_2_ans = date("Y-m-d", strtotime("-2 year"));
    	$experience_24 = $this->vols_planeur_model->experience($mlogin, $depuis_2_ans);
    	
    	$depuis_3_mois = date("Y-m-d", strtotime("-90 day"));
    	$experience_90 = $this->vols_planeur_model->experience($mlogin, $depuis_3_mois);
    	
    	$result = array(
    			'vols_cdb_depuis_90_jours' => $experience_90['vols_cdb'],
    			'vols_en_double_depuis_2_ans' => $experience_24['vols_dc'],
    			'heures_cdb_2_ans' => $experience_24['heures_cdb'],
    			'vols_cdb_2_ans' => $experience_24['vols_cdb'],
    			'treuille_2_ans' => $experience_24['treuil'],
    			'rem_2_ans' => $experience_24['rem'],
    			'autonome_2_ans' => $experience_24['autonomes']
    	);
    	return $result;
    }
    
    /**
     *
     * checks experienc conditions for a user
     */
    function index($mlogin = "") {
    	
    	if (!$mlogin)
    		$mlogin = $this->dx_auth->get_username();
    	
    	$data['mlogin'] = $mlogin;
    	$data['pilote_selector'] = $this->membres_model->selector(array('actif' => 1));
    	$data['pilot_name'] = $this->membres_model->image($mlogin);
    	 
    	$data['title'] = "Conditions d'expérience";
    	$data['year'] = date("Y");
    	
    	$data = array_merge($data, $this->licences($mlogin, $data['year']));
    	$data = array_merge($data, $this->medical($mlogin));
    	$data = array_merge($data, $this->brevet($mlogin));
    	$data = array_merge($data, $this->experience_recente($mlogin));
    	$data['controller'] = 'alarmes';
    	return load_last_view('alarmes', $data, false);
    }
    
}
