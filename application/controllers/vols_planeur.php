<?php
/**
 *    GVV Gestion vol à voile
 *    Copyright(C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
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
 *
 * controleur de gestion des vols planeur
 *
 * Playwright tests:
 *   - npx playwright test tests/bugfix-payeur-selector.spec.js
 */
include ('./application/libraries/Gvv_Controller.php');

/**
 * Gestion des vols en planeurs
 */
class Vols_planeur extends Gvv_Controller {
    protected $controller = 'vols_planeur';
    protected $model = 'vols_planeur_model';
    protected $kid = 'vpid';
    protected $modification_level = 'planchiste';

    // régles de validation
    protected $rules = array (
            'vpduree' => 'callback_valid_minute_time',
            'payeur' => "callback_pilote_au_sol",
    		'vpobs' => "callback_machine_au_sol",
    );

    // Headers and first colomns
    protected $title_row;
    protected $first_col;
    protected $pm_first_row;

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();

        // Authorization: Code-based (v2.0) - only for migrated users
        if ($this->use_new_auth) {
            $this->require_roles(['user']);
        }

        // remplit les selecteurs depuis la base
        $this->load->model('membres_model');
        $this->load->model('comptes_model');
        $this->load->model('planeurs_model');
        $this->load->model('avions_model');
        $this->load->model('terrains_model');
        $this->load->helper('csv');
        $this->load->helper('statistic');
        $this->load->model('events_types_model');
        $this->lang->load('vols_planeur');

        $this->title_row = array_merge(array (
                $this->lang->line("gvv_total")
        ), $this->lang->line("gvv_months"));

        $this->first_col = $this->lang->line("gvv_vols_planeur_stats_col");

        $this->pm_first_row = array_merge(array (
                $this->lang->line("gvv_vue_vols_planeur_short_field_type"),
                $this->lang->line("gvv_vue_vols_planeur_short_field_vpmacid")
        ), $this->title_row);
    }

    /**
     * Génération des éléments à passer au formulaire en cas de création,
     * modification ou réaffichage après erreur.
     */
    function form_static_element($action) {
        // var_dump($this->data);exit;
        parent::form_static_element($action);
        $pilote_selector = $this->membres_model->section_pilots(0, true);
        $this->data ['vpduree'] = minute_to_time($this->data ['vpduree']);
        $this->data ['vppassager'] = $this->data ['vpinst'];
        $this->data ['vptreuillard'] = $this->data ['pilote_remorqueur'];
        $this->data ['saisie_par'] = $this->dx_auth->get_username();
        $rem_selector = $this->avions_model->selector_with_null(array (
                "macrem" => 1,
                'actif' => 1
        ));
        $this->config->load('facturation');
        $payeur_selector = $this->comptes_model->section_client_accounts(0, true);
        $this->data ['payeur_selector'] = $payeur_selector;
        $this->data ['payeur_non_pilote'] = $this->config->item('payeur_non_pilote');
        $this->data ['partage'] = $this->config->item('partage');
        $this->data ['remorque_100eme'] = $this->config->item('remorque_100eme');

        if (CREATION == $action) {
            if (isset($_POST ['Cb_date'])) { // si données POST pour planche automatique
                $dec = $_POST ['Cb_dec'];
                $att = $_POST ['Cb_att'];
                $this->data ['vpdate'] = $_POST ['Cb_date'];
                $this->data ['remorqueur'] = $_POST ['Cb_rem'];
                $this->data ['vplieudeco'] = $_POST ['Cb_lieu'];
                $this->data ['vplieuatt'] = $_POST ['Cb_lieu'];
                $this->data ['vpmacid'] = $_POST ['Cb_pla'];
                $this->data ['vpcdeb'] = $dec;
                $this->data ['vpcfin'] = $att;
                // calculer la durée ici

                if ($dec != "" && $att != "") {
                    $debe = intval($dec);
                    $debd = ($dec - $debe) * 100;
                    $fine = intval($att);
                    $find = ($att - $fine) * 100;
                    $diff = (($fine * 60) + $find) - (($debe * 60) + $debd);

                    if ($diff > 0) {
                        $rese = floor($diff / 60);
                        $resd = $diff - ($rese * 60);
                        if ($resd < 10) {
                            $resdaff = "0" . $resd;
                        } else {
                            $resdaff = $resd;
                        }
                        $result = "" . $rese . ":" . $resdaff;  // concatene heure : minutes
                        $this->data ['vpduree'] = $result;
                    }
                }
            } else {
                $year = $this->session->userdata('year');

                $latestf = $this->gvv_model->latest_flight(array (
                        'year(vpdate)' => $year
                ));
                $flight_exist = (count($latestf) > 0);

                if ($flight_exist) {
                    $this->data ['vpdate'] = $latestf [0] ['vpdate'];
                    $this->data ['remorqueur'] = $latestf [0] ['remorqueur'];
                    $this->data ['pilote_remorqueur'] = $latestf [0] ['pilote_remorqueur'];
                    $this->data ['vpautonome'] = $latestf [0] ['vpautonome'];
                    if ($latestf [0] ['vpautonome'] == 1)
                        $this->data ['vptreuillard'] = $this->data ['pilote_remorqueur'];
                    $this->data ['vplieudeco'] = $latestf [0] ['vplieudeco'];
                    $this->data ['vplieuatt'] = $latestf [0] ['vplieudeco'];
                } else
                    $this->data ['vpdate'] = date("Y-m-d");
            }
        }

        // Avec les méta-données
        $this->gvvmetadata->set_selector('machine_selector', $this->planeurs_model->selector(array (
                'actif' => 1
        )));
        $this->gvvmetadata->set_selector('pilote_selector', $pilote_selector);
        $this->gvvmetadata->set_selector('inst_selector', $this->membres_model->qualif_selector('mlogin', ITP | IVV));
        $this->gvvmetadata->set_selector('rem_selector', $rem_selector);
        $this->gvvmetadata->set_selector('pilrem_selector', $this->membres_model->qualif_selector('mlogin', REMORQUEUR));
        $this->gvvmetadata->set_selector('treuillard_selector', $this->membres_model->qualif_selector('mlogin', TREUILLARD));
        $this->gvvmetadata->set_selector('payeur_selector', $payeur_selector);
        $this->gvvmetadata->set_selector('terrains_selector', $this->terrains_model->selector_with_null());

        // Checkboxes formation et FAI
        $certificats = array ();
        $select = $this->events_types_model->select_all(array (
                'activite' => 1,
                'en_vol' => 1
        ));

        $date_values = array ();
        foreach ( $select as $row ) {
            $id = $row ['id'];
            $certificats [] = array (
                    'label' => $row ['name'],
                    'id' => $id
            );
            // if($id % 2) $date_values[$id] = 1;
        }
        $this->data ['certificats'] = $certificats;
        $this->data ['certificat_values'] = $date_values;

        $select = $this->events_types_model->select_all(array (
                'activite' => 4,
                'en_vol' => 1
        ));

        $certificats = array ();
        $date_values = array ();
        foreach ( $select as $row ) {
            $id = $row ['id'];
            $certificats [] = array (
                    'label' => $row ['name'],
                    'id' => $id
            );
            // if($id % 2) $date_values[$id] = 1;
        }
        $this->data ['certificats_fai'] = $certificats;
        $this->data ['certificat_fai_values'] = $date_values;
    }

    /**
     * Affiche le formulaire de création
     */
    function create() {
        $is_planchiste = $this->user_has_role('planchiste');
        $is_auto_planchiste = $this->user_has_role('auto_planchiste');
        if (! $is_planchiste && ! $is_auto_planchiste) {
            $this->dx_auth->deny_access();
            return;
        }
        parent::create(TRUE);
        $this->data ['vpid'] = 0;

        // et affiche le formulaire
        load_last_view('vols_planeur/formView', $this->data);
    }

    /**
     * Affiche le formulaire de saisie automatisée
     */
    function plancheauto() {
        if (! $this->user_has_role('planchiste')) {
            $this->dx_auth->deny_access();
            return;
        }
        
        $this->data ['controller'] = $this->controller;
        // affichage de la planche du terrain et du jour choisi
        $this->data ['saisie_par'] = $this->dx_auth->get_username();
        $sdate = $_POST ['vpdate'];
        $this->data ['vpdate'] = $sdate;
        $this->data ['vplieudeco'] = $_POST ['terrain'];
        $this->data ['remorque_100eme'] = $this->config->item('remorque_100eme');
        $this->data ['vppilid'] = "";
        $this->data ['pilote_remorqueur'] = "";
        $this->data ['vpautonome'] = "";
        $this->data ['vpcategorie'] = "";

        $sdate = substr($sdate, 0, 2) . substr($sdate, 3, 2) . substr($sdate, 6, 4); // mise au format jjmmaaaa

        // téléchargement du fichier JSON depuis cunimb.fr
        $this->config->load('club');
        $url = $this->config->item('url_planche_auto');
        $url .= '?d=' . $sdate . '&a=' . $_POST ['terrain'] . '&j=2&s=QFE&u=m&z=' . $_POST ['z'];

        $planche = file_get_contents($url);
        $planche = json_decode($planche, true);

        $this->data ['planche'] = $planche;

        $this->gvvmetadata->set_selector('pilote_selector', $this->membres_model->section_pilots(0, true));

        $machine_selector = $this->planeurs_model->selector_with_null(array (
                'actif' => 1
        ));
        $this->gvvmetadata->set_selector('machine_selector', $machine_selector);
        $this->gvvmetadata->set_selector('inst_selector', $this->membres_model->qualif_selector('mlogin', ITP | IVV));

        $rem_selector = $this->avions_model->selector_with_null(array (
                "macrem" => 1,
                'actif' => 1
        ));
        $this->gvvmetadata->set_selector('rem_selector', $rem_selector);

        $this->gvvmetadata->set_selector('pilrem_selector', $this->membres_model->qualif_selector('mlogin', REMORQUEUR));

        load_last_view('vols_planeur/LogsView', $this->data);
    }

    /**
     * Selection de la date et du terrain
     */
    function plancheauto_select() {
        if (! $this->user_has_role('planchiste')) {
            $this->dx_auth->deny_access();
            return;
        }
        $this->data ['controller'] = $this->controller;
        $this->data ['choix'] = 1;
        $this->data ['vpdate'] = date("d/m/Y");
        $this->data ['terrains'] = $this->terrains_model->selector_with_null();
        load_last_view('vols_planeur/LogsSelect', $this->data);
    }

    /**
     * Affiche le formulaire de modification
     *
     * @param $id vol
     *            à modifier
     */
    function edit($id= '', $load_view = true, $action = MODIFICATION) {
        // planchiste: full access to all flights
        // auto_planchiste: can modify their own flights only
        // other users: can view their own flights only
        $is_planchiste = $this->user_has_role('planchiste');
        $is_auto_planchiste = $this->user_has_role('auto_planchiste');
        if (! $is_planchiste) {
            $flight = $this->model->get_by_id('vpid', $id);
            $mlogin = $this->dx_auth->get_username();
            $is_own_flight = (!empty($flight) && $flight['vppilid'] == $mlogin);
            if (! $is_own_flight) {
                $this->dx_auth->deny_access();
                return;
            }
            if (! $is_auto_planchiste) {
                // Regular user: view only their own flight
                $action = VISUALISATION;
            }
            // auto_planchiste with own flight: keep MODIFICATION (frozen check applies below)
        }
        
        $this->load->model('ecritures_model');
        $action = (count($this->ecritures_model->select_flight_frozen_lines($id, "vol_planeur"))) ? VISUALISATION : MODIFICATION;
        parent::edit($id, FALSE, $action);
        
        // Convert member ID to account ID for payeur field (for form display)
        if (!empty($this->data['payeur'])) {
            $account = $this->comptes_model->pilot_account($this->data['payeur']);
            if ($account && !empty($account['id'])) {
                $this->data['payeur'] = $account['id'];
            }
        }

        // Recharge les evénements de formation
        $events = $this->event_model->flight_events(array (
                'evpid' => $id,
                'en_vol' => 1,
                'activite' => 1
        ));
        $date_values = array ();
        foreach ( $events as $event ) {
            $date_values [$event ['etype']] = 1;
        }
        $this->data ['certificat_values'] = $date_values;

        // Recharge les certificats FAI
        $events = $this->event_model->flight_events(array (
                'evpid' => $id,
                'en_vol' => 1,
                'activite' => 4
        ));
        $date_values = array ();
        foreach ( $events as $event ) {
            $date_values [$event ['etype']] = 1;
        }
        $this->data ['certificat_fai_values'] = $date_values;
        // affiche le formulaire
        load_last_view('vols_planeur/formView', $this->data);
    }

    /**
     * Supprime un élèment
     */
    function delete($id) {
        if (! $this->user_has_role('planchiste')) {
            $this->dx_auth->deny_access();
            return;
        }
        
        $this->load->model('ecritures_model');
        if (count($this->ecritures_model->select_flight_frozen_lines($id, "vol_planeur"))) {
            // Il y a des lignes gelées la suppression est interdite
            $this->session->set_flashdata('popup', "Suppression interdite, écriture vérouillée par le comptable.");
        } else {
            // détruit en base
            gvv_debug("delete vol planeur " . $id);

            $this->pre_delete($id);
            $this->gvv_model->delete(array (
                    $this->kid => $id
            ));
        }
        $this->pop_return_url();
    }

    /**
     * Retourne la selection format ActiveData utilisable par les requêtes
     * SQL pour filtrer les données en fonction des choix faits par l'utilisateur
     * dans la section de filtrage.
     */
    function selection($this_year = TRUE, $gesasso = false) {
        $this->data ['filter_active'] = $this->session->userdata('filter_active');
        $this->data ['filter_date'] = '';
        $this->data ['date_end'] = '';
        $this->data ['filter_pilote'] = '';
        $this->data ['filter_machine'] = '';
        $this->data ['filter_aero'] = 'all';
        $this->data ['filter_25'] = 0;
        $this->data ['filter_dc'] = 0;
        $this->data ['filter_vi'] = 0;
        $this->data ['filter_prive'] = 0;
        $this->data ['filter_lanc'] = 0;

        $selection = "YEAR(`vpdate`) > 0 ";
        $year = $this->session->userdata('year');
        $date25 = date_m25ans($year);

        if ($this->session->userdata('filter_active')) {
            $order = "asc";

            $filter_pilote = $this->session->userdata('filter_pilote');
            if ($filter_pilote) {
                $this->data ['filter_pilote'] = $filter_pilote;
                if ($selection != '') $selection .= " and ";
                $selection .= " (vppilid = \"$filter_pilote\" or vpinst = \"$filter_pilote\")";
            }

            $filter_25 = $this->session->userdata('filter_25');

            if ($filter_25 == 1) {
                $this->data ['filter_25'] = $filter_25;
                if ($selection != '') $selection .= " and ";
                $selection .= " (mdaten >= \"$date25\")";
            } else if ($filter_25 == 2) {
                $this->data ['filter_25'] = $filter_25;
                if ($selection != '') $selection .= " and ";
                $selection .= " (mdaten < \"$date25\")";
            }

            $filter_dc = $this->session->userdata('filter_dc');
            if ($filter_dc) {
                $this->data ['filter_dc'] = $filter_dc;
                if ($selection != '') $selection .= " and ";
                $selection .= " (vpdc = \"$filter_dc\")";
            }

            $filter_vi = $this->session->userdata('filter_vi');
            if ($filter_vi) {
                $this->data ['filter_vi'] = $filter_vi;
                $categorie = $filter_vi - 1;
                if ($selection != '') $selection .= " and ";
                
                $selection .= " (vpcategorie = \"$categorie\")";
            }

            $filter_prive = $this->session->userdata('filter_prive');
            if ($filter_prive) {
                $this->data ['filter_prive'] = $filter_prive;
                $filter_prive --;
                if ($selection != '') $selection .= " and ";
                
                $selection .= " (machinesp.mpprive = \"$filter_prive\")";
            }

            $filter_lanc = $this->session->userdata('filter_lanc');
            if ($filter_lanc) {
                $this->data ['filter_lanc'] = $filter_lanc;
                if ($selection != '') $selection .= " and ";
                
                $selection .= " (vpautonome = \"$filter_lanc\")";
            } 

            $filter_machine = $this->session->userdata('filter_machine');
            if ($filter_machine) {
                $this->data ['filter_machine'] = $filter_machine;
                if ($selection != '') $selection .= " and ";
                $selection .= "vpmacid = \"$filter_machine\" ";
            } else {
            	if ($gesasso) {
            		if ($selection != '') $selection .= " and ";
            		$selection .= " vpmacid <> 'F-XXXX'";
            	}
            }

            $filter_aero = $this->session->userdata('filter_aero');
            if ($filter_aero == "all") {
                $filter_aero = '';
            } 
            if ($filter_aero) {
                $this->data ['filter_aero'] = $filter_aero;
                if ($selection != '')
                    $selection .= " and ";
                $selection .= "vplieudeco = \"$filter_aero\" ";
            }

            $filter_date = $this->session->userdata('filter_date');
            $date_end = $this->session->userdata('date_end');
            if ($filter_date) {
                if ($selection != '') $selection .= " and ";
                $this->data ['filter_date'] = $filter_date;
                if ($date_end) {
                    $selection .= "vpdate >= \"" . date_ht2db($filter_date) . "\" ";
                } else {
                    $selection .= "vpdate = \"" . date_ht2db($filter_date) . "\" ";
                }
            } else {
            	if ($selection != '') $selection .= " and ";
            	$date_deb = $year . "-01-01";
            	$selection .= "vpdate >= \"" . $date_deb . "\" ";
            }

            if ($date_end) {
                if ($selection != '')
                    $selection .= " and ";
                $this->data ['date_end'] = $date_end;
                $selection .= "vpdate <= \"" . date_ht2db($date_end) . "\" ";
            } else {
            	$date_fin = $year . "-12-31";
            	if ($selection != '') $selection .= " and ";
            	$selection .= "vpdate <= \"" . $date_fin . "\" ";
            }

            if ($selection == "")
                $selection = array ();
            // else echo "selection=$selection" .br();
        } else {
        	if ($this_year) {
        		$selection = "YEAR(vpdate) = \"$year\"";
        	} else {
        		$selection = "YEAR(vpdate) > 0";
        	}
        }
        gvv_debug("selection = $selection");
        
        return $selection;
    }

    /**
     * Selectionne les éléments à afficher sur la page des vols
     *
     * @param $premier élément
     *            à afficher
     * @param $message à
     *            afficher
     * @param $per_page nombre
     *            d'élémenta à afficher'
     */
    function select_page($premier = 0, $message = '', $per_page = NULL, $order = "desc") {
        if (! isset($per_page)) {
            $per_page = $this->session->userdata('per_page');
        }
        $this->data ['action'] = VISUALISATION;
        $this->data ['filter_active'] = $this->session->userdata('filter_active');
        $this->data ['planchiste'] = $this->user_has_role('planchiste');
        $year = $this->session->userdata('year');
        $date25 = date_m25ans($year);

        $this->data ['pilote_selector'] = $this->membres_model->section_pilots(0, true);

        $machine_selector = $this->planeurs_model->selector_with_null(array (
                'actif' => 1
        ));
        $this->data ['machine_selector'] = $machine_selector;

        $this->data ['aero_selector'] = $this->terrains_model->selector_with_all();

        $this->data ['year_selector'] = $this->gvv_model->getYearSelector("vpdate");
        $this->data ['year'] = $this->session->userdata('year');

        if ($this->session->userdata('filter_active')) {
            $filter_pilote = $this->session->userdata('filter_pilote');
        }

        $selection = $this->selection();    // charge la selection définie par le filtre
        // echo "selection = $selection"; exit;
        // echo "per_page=" . $this->session->userdata('per_page') . br();
        $this->data ['select_result'] = $this->gvv_model->select_page($year, $per_page, $premier, $selection, $order);
        $this->data ['kid'] = $this->kid;
        $this->data ['controller'] = $this->controller;
        $this->data ['count'] = $this->gvv_model->count($selection);

        $this->data ['treuils'] = $this->gvv_model->count($selection, array (
                "vpautonome" => "1"
        ));
        $this->data ['autonomes'] = $this->gvv_model->count($selection, array (
                "vpautonome" => "2"
        ));
        $this->data ['rems'] = $this->gvv_model->count($selection, array (
                "vpautonome" => "3"
        ));
        $this->data ['exts'] = $this->gvv_model->count($selection, array (
                "vpautonome" => "4"
        ));

        $this->data ['total'] = $this->gvv_model->sum('vpduree', $selection);
        $this->data ['kms'] = ( int ) $this->gvv_model->sum('vpnbkm', $selection);
        $this->data ['m25ans'] = $this->gvv_model->sum('vpduree', $selection, array (
                'mdaten >' => $date25
        ));
        $this->data ['premier'] = $premier;
        $this->data ['message'] = $message;

        if ($this->session->userdata('filter_active') && $filter_pilote) {
            // Calcul aussi les heures CDB, et instructeurs
            $this->data ['by_pilote'] = 1;
            $this->data ['dc'] = $this->gvv_model->sum('vpduree', $selection, array (
                    'vpdc' => 1,
                    'vppilid' => $filter_pilote
            ));
            $this->data ['inst'] = $this->gvv_model->sum('vpduree', $selection, array (
                    'vpdc' => 1,
                    'vpinst' => $filter_pilote
            ));
            $this->data ['cdb'] = $this->data ['inst'] + $this->gvv_model->sum('vpduree', $selection, array (
                    'vpdc' => 0,
                    'vppilid' => $filter_pilote
            ));
            $this->data ['filter_aero'] = "all";
    
        } else {
            $this->data ['by_pilote'] = 0;
            $this->data ['dc'] = $this->gvv_model->sum('vpduree', $selection, array (
                    'vpdc' => 1
            ));
            $this->data ['inst'] = 0;
            $this->data ['cdb'] = 0;
        }
        $this->data ['has_modification_rights'] = (! isset($this->modification_level) || $this->user_has_role($this->modification_level));
        $this->data ['auto_planchiste'] = $this->user_has_role('auto_planchiste');
    }
		
    /**
     * Selectionne les éléments à exporter vers Gesasso
     *
     */
	 function select_gesasso() {
        
        $this->data ['action'] = VISUALISATION;
        $this->data ['filter_active'] = $this->session->userdata('filter_active');

			
		$selection = $this->selection($this_year = true, $gesasso = true);
		
		$this->data ['select_result'] = $this->gvv_model->select_gesasso($selection);
				
        $this->data ['kid'] = $this->kid;
        $this->data ['controller'] = $this->controller;
        
        $this->data ['has_modification_rights'] = (! isset($this->modification_level) || $this->user_has_role($this->modification_level));
    }
		

    /**
     * Affiche une page d'éléments
     *
     * @param $premier élément
     *            à afficher
     */
    function page($premier = 0, $message = '', $selection = array()) {
        if (! $this->dx_auth->is_logged_in()) {
            $this->dx_auth->deny_access();
        }
        
        $this->push_return_url("vols planeur page");

        $this->select_page($premier, $message);
        return load_last_view($this->table_view, $this->data, $this->unit_test);
    }

    /**
     * Export au format CSV
     */
    function csv() {
        $this->select_page(0, "", 100000);
        $this->gvvmetadata->csv("vue_vols_planeur");
    }

		/**
     * Export au format CSV vers Gesasso
     */
		function gesasso() {
			$this->select_gesasso();
			$results = $this->data ['select_result'];
			csv_file('', $results, true, true);
		}

		
    /**
     * Export au format PDF
     */
    function pdf() {
        gvv_debug("export des planches en pdf");
        $this->select_page(0, "", 100000, null, "asc");

        $year = $this->session->userdata('year');

        $this->load->library('Pdf');
        $pdf = new Pdf();

        $titre = $this->lang->line("gvv_vols_planeur_title") . " $year";
        $pdf->set_title($titre);
        $pdf->AddPage('L');
        $pdf->title($titre, 1);

        $tab = array ();

        $tab [0] = $this->lang->line("gvv_vols_planeur_pdf_header");
        $data = $this->data;
        $results = $this->data ['select_result'];
        $pilots = $this->data ['pilote_selector'];
        $line = 1;
        $launches = $this->lang->line("gvv_short_launch_type");
        foreach ( $results as $row ) {
            $row ['vpdate'] = date_db2ht($row ['vpdate']);
            if (isset($launches [$row ['vpautonome']])) {
                $row ['vpautonome'] = $launches [$row ['vpautonome']];
            } else {
                $row ['vpautonome'] = "";
            }

            $row ['vpduree'] = minute_to_time($row ['vpduree']);
            $row ['vpnbkm'] = ( int ) $row ['vpnbkm'];
            $row ['vpnbkm'] = ($row ['vpnbkm'] == 0) ? ' ' : $row ['vpnbkm'];
            foreach ( array (
                    'vpdc',
                    'm25ans'
            ) as $field ) {
                $row [$field] = ($row [$field]) ? 'X' : '';
            }
            $categories = $this->config->item('categories_vol_planeur_short');
            $row ['vpcategorie'] = $categories [$row ['vpcategorie']];
            $fld = 0;
            $fields = array (
                    'vpdate',
                    'vpcdeb',
                    'vpduree',
                    'vpautonome',
                    'rem_id',
                    'vpaltrem',
                    'vpmacid',
                    'pilote',
                    'instructeur',
                    'vpcategorie',
                    'vpdc',
                    'm25ans',
                    'vpnbkm',
                    'vpobs'
            );
            foreach ( $fields as $field ) {
                if ('rem_id' == $field) {
                    // On tronque pour ne pas casser l'alignement
                    // Cela n'a pas d'impact si on utilise les trigrammes
                    $tab [$line] [$fld ++] = substr($row [$field], 0, 6);
                } elseif ('instructeur' == $field) {
                    $tab [$line] [$fld ++] = substr($row [$field], 0, 12);
                } else {
                    $tab [$line] [$fld ++] = $row [$field];
                }
            }
            $line ++;
        }
        $w = array (
                18,
                15,
                15,
                12,
                10,
                10,
                16,
                35,
                16,
                8,
                8,
                8,
                10,
                60
        );
        $align = array (
                'L',
                'R',
                'R',
                'R',
                'C',
                'R',
                'L',
                'L',
                'L',
                'C',
                'C',
                'C',
                'R',
                'L'
        );
        $pdf->table($w, 8, $align, $tab);
        gvv_debug("generating pdf ... ");
        $pdf->Output();
    }

    /**
     * Active ou désactive le filtrage
     */
    public function filterValidation() {
        if (! $this->dx_auth->is_role('planchiste')) {
            $this->dx_auth->deny_access();
        }
        
        $button = $this->input->post('button');

        if ($button == $this->lang->line("gvv_str_select")) {
            // Enable filtering
            $session ['filter_date'] = $this->input->post('filter_date');
            $session ['date_end'] = $this->input->post('date_end');
            $session ['filter_pilote'] = $this->input->post('filter_pilote');
            $session ['filter_machine'] = $this->input->post('filter_machine');
            $session ['filter_aero'] = $this->input->post('filter_aero');

            $session ['filter_25'] = $this->input->post('filter_25');
            $session ['filter_dc'] = $this->input->post('filter_dc');
            $session ['filter_prive'] = $this->input->post('filter_prive');
            $session ['filter_lanc'] = $this->input->post('filter_lanc');
            $session ['filter_vi'] = $this->input->post('filter_vi');

            $session ['filter_active'] = 1;
            $this->session->set_userdata($session);
            // var_dump($session); exit;
        } else {
            // Disable filtering
            foreach ( array (
                    'filter_date',
                    'date_end',
                    'filter_pilote',
                    'filter_machine',
                    'filter_aero',
                    'filter_active',
                    'filter_25',
                    'filter_dc',
                    'filter_prive',
                    'filter_lanc',
                    'filter_vi'
            ) as $field ) {
                $this->session->unset_userdata($field);
            }
        }
        // Il faut rediriger et non pas appeller $this->page, sinon l'URL
        // enregistrée pour le retour est incorrecte
        redirect($this->controller . '/page');
    }

    /**
     * Affiche les vols du pilote
     */
    public function vols_du_pilote($pilote) {
        // Regular users can view their own flights, planchiste can view any pilot's flights
        $mlogin = $this->dx_auth->get_username();
        if ($pilote != $mlogin && !$this->dx_auth->is_role('planchiste')) {
            $this->dx_auth->deny_access();
            return;
        }
        
        // Enable filtering
        $session ['filter_date'] = '';
        $session ['date_end'] = '';
        $session ['filter_pilote'] = $pilote;
        $session ['filter_machine'] = '';
        $session ['filter_aero'] = 'all';

        $session ['filter_25'] = 0;
        $session ['filter_dc'] = 0;
        $session ['filter_prive'] = 0;
        $session ['filter_lanc'] = 0;
        $session ['filter_vi'] = 0;

        $session ['filter_active'] = 1;

        $this->session->set_userdata($session);
        redirect($this->controller . '/page');
    }

    /**
     * Affiche les vols de la machine
     */
    public function vols_de_la_machine($machine) {
        // Any logged-in user can view flights by machine
        
        // Enable filtering
        $session ['filter_date'] = '';
        $session ['date_end'] = '';
        $session ['filter_pilote'] = '';
        $session ['filter_machine'] = $machine;
        $session ['filter_aero'] = 'all';
        $session ['filter_25'] = 0;
        $session ['filter_dc'] = FALSE;
        $session ['filter_prive'] = 0;
        $session ['filter_lanc'] = 0;
        $session ['filter_vi'] = 0;
        $session ['filter_active'] = 1;
        $this->session->set_userdata($session);
        redirect($this->controller . '/page');
    }

    /**
     * Vérifie que le pilote n'est pas déja en vol
     *
     * @return boolean
     */
    public function pilote_au_sol() {
        $vpid = $this->input->post('vpid');
        $vpcdeb = str_replace(':', '.', $this->input->post('vpcdeb'));
        $vpcfin = str_replace(':', '.', $this->input->post('vpcfin'));
        $pilote = $this->input->post('vppilid');
        $date = date_ht2db($this->input->post('vpdate'));

        gvv_debug("check pilote $pilote au sol");
        
        // Pas de vérification pour les pilotes extérieurs
        $pilote_info = $this->membres_model->get_by_id('mlogin', $pilote);
        if (! isset($pilote_info ['ext']))
            return TRUE;
        if ($pilote_info ['categorie'] == 1) {
            return TRUE;
        }

        // le vol n'est pas le vol du formulaire (pour les modifications)
        $selection = "vpid != \"$vpid\"";
        // et le pilote est pilote ou  instructeur (ou pourrait ajouter treuillard ou remorqueur
        // mais pour les treuillard et remorqueurs c'est plus compliqué, ils peuvent être libre et décollé avant la
        // fin du vol qu'ils ont lancé
        $selection .= " and (vppilid = \"$pilote\" or vpinst = \"$pilote\")";
        // et c'est le bon jour
        $selection .= " and vpdate = \"" . $date . "\" ";
        // et le début du vol saisie est entre le début et la fin du vol en base
        $selection .= " and (  ((vpcdeb <= \"$vpcdeb\") and(vpcfin >= \"$vpcdeb\"))";
        // ou la fin du vol est entre le début et la fin du vol en base
        $selection .= " or ((vpcdeb <= \"$vpcfin\") and (vpcfin >= \"$vpcfin\"))";
        // ou le vol englobe le vol en base
        $selection .= " or ((vpcdeb >= \"$vpcdeb\") and (vpcfin <= \"$vpcfin\"))";
        // ou le vol en base englobe le vol
        $selection .= " or ((vpcdeb <= \"$vpcdeb\") and (vpcfin >= \"$vpcfin\"))  )";
        
        $count = $this->gvv_model->count($selection);
        $this->form_validation->set_message('pilote_au_sol', $this->lang->line("pilote_au_sol"));
        return ($count == 0);
    }

    /**
     * Vérifie que la machine n'est pas déja en vol
     *
     * @return boolean
     */
    public function machine_au_sol() {
        $vpid = $this->input->post('vpid');
        $vpcdeb = str_replace(':', '.', $this->input->post('vpcdeb'));
        $vpcfin = str_replace(':', '.', $this->input->post('vpcfin'));
        $machine = $this->input->post('vpmacid');
        $date = date_ht2db($this->input->post('vpdate'));

        gvv_debug("check machine $machine au sol, vpid=$vpid, vpcdeb=$vpcdeb, vpcfin=$vpcfin, date=$date");
        
        $planeur = $this->planeurs_model->get_by_id('mpimmat', $machine);
        if ($planeur ['mpprive'] == 2)  // machine extérieure
            return TRUE;

        // le vol n'est pas le vol du formulaire (pour les modifications)
        $selection = "vpid != \"$vpid\"";
        // C'est la même machine
        $selection .= " and vpmacid = \"$machine\" ";
        // la bonne date
        $selection .= " and vpdate = \"" . $date . "\" ";
        // et le début du vol saisie est entre le début et la fin du vol en base
        $selection .= " and (((vpcdeb <= \"$vpcdeb\") and (vpcfin >= \"$vpcdeb\"))";
        // ou la fin du vol est entre le début et la fin du vol en base
        $selection .= " or ((vpcdeb <= \"$vpcfin\") and (vpcfin >= \"$vpcfin\"))";
        // ou le vol englobe le vol en base
        $selection .= " or ((vpcdeb >= \"$vpcdeb\") and (vpcfin <= \"$vpcfin\"))";
        // ou le vol en base englobe le vol
        $selection .= " or ((vpcdeb <= \"$vpcdeb\") and (vpcfin >= \"$vpcfin\"))  )";

        $count = $this->gvv_model->count($selection);
        $this->form_validation->set_message('machine_au_sol', "Le planeur est déjà en vol");
        return ($count == 0);
    }

    /**
     * Calcul les statistiques par mois de l'année
     *
     * @param
     *            $year
     * @return un tableau comportantles données
     */
    public function stat_per_month($year) {
        $selection = array (
                'year(vpdate)' => $year
        );
        $date25 = date_m25ans($year);

        $pm = array ();
        
        // Le tableau est construit ligne par ligne
        // Chaque ligne: total année, janvier, fevrier, ..., Novembre, decembre
        $where = $selection;
        $pm [] = $this->gvv_model->line_monthly('minutes', $where);             // total des heures
        $pm [] = $this->gvv_model->line_monthly('count', $where);               // Total des vols

        $where = array_merge($selection, array (
                'mdaten >=' => $date25
        ));
        $pm [] = $this->gvv_model->line_monthly('minutes', $where);             // Total des heures - 25 ans
        $pm [] = $this->gvv_model->line_monthly('minutes', $where, $pm [0]);    // Pourcentage heures - 25 ans
        $pm [] = $this->gvv_model->line_monthly('count', $where);               // Total des vols   - 25 ans
        $pm [] = $this->gvv_model->line_monthly('count', $where, $pm [1]);      // Pourcentage des vols - 25 ans

        $where = array_merge($selection, array (
                'msexe' => 'F'
        ));
        $pm [] = $this->gvv_model->line_monthly('minutes', $where);             // total heurs feminines
        $pm [] = $this->gvv_model->line_monthly('minutes', $where, $pm [0]);    // pourcentage feminines
        $pm [] = $this->gvv_model->line_monthly('count', $where);               // vols feminines
        $pm [] = $this->gvv_model->line_monthly('count', $where, $pm [1]);      // pourcentage vols feminines

        $where = array_merge($selection, array (
                'vpdc' => 1
        ));
        $pm [] = $this->gvv_model->line_monthly('minutes', $where);             // total heures double
        $pm [] = $this->gvv_model->line_monthly('minutes', $where, $pm [0]);
        $pm [] = $this->gvv_model->line_monthly('count', $where);
        $pm [] = $this->gvv_model->line_monthly('count', $where, $pm [1]);

        $where = array_merge($selection, array (
                'vpcategorie' => 1
        ));
        $pm [] = $this->gvv_model->line_monthly('count', $where);
        $pm [] = $this->gvv_model->line_monthly('kms', $selection);
        return $pm;
    }

    /**
     * Calcul les statistiques par machine de l'année
     *
     * @param
     *            $year
     * @param $type 'minutes'
     *            | 'count' | 'moteur'
     */
    public function stat_per_machine($year, $type = 'minutes') {
        $selection = array (
                'year(vpdate)' => $year
        );

        $pm = array ();

        $machines = $this->planeurs_model->select_all(array (), "mpmodele");
        foreach ( $machines as $machine ) {
            $immat = $machine ['mpimmat'];
            $modele = $machine ['mpmodele'];
            $line = array (
                    $modele,
                    $immat
            );
            $where = array_merge($selection, array (
                    'vpmacid' => $immat
            ));
            $line = array_merge($line, $this->gvv_model->line_monthly($type, $where));
            if ($line [2] > 0) {
                $pm [] = $line;
            }
        }
        return $pm;
    }

    /**
     * Calcul les statistiques par mois de l'année
     *
     * @param
     *            $year
     * @return un tableau comportantles données
     */
    public function state_per_category($year, $selection = array()) {

        // var_dump('state_per_category ' . $year);
        // var_dump($selection);
        $selection = array_merge($selection, array (
                'year(vpdate)' => $year
        ));

        $pm = array ();

        $where = $selection;
        $pm [] = $this->gvv_model->line_monthly('minutes', $where);
        $pm [] = $this->gvv_model->line_monthly('count', $where);

        $where = array_merge($selection, array (
                'vpautonome' => 3
        ));
        $pm [] = $this->gvv_model->line_monthly('count', $where);
        $where = array_merge($selection, array (
                'vpautonome' => 1
        ));
        $pm [] = $this->gvv_model->line_monthly('count', $where);
        $where = array_merge($selection, array (
                'vpautonome' => 2
        ));
        $pm [] = $this->gvv_model->line_monthly('count', $where);
        $where = array_merge($selection, array (
                'vpautonome' => 4
        ));
        $pm [] = $this->gvv_model->line_monthly('count', $where);

        $pm [] = $this->gvv_model->line_monthly('kms', $selection);
        return $pm;
    }

    /**
     * Affiche la page de statistiques
     */
    public function statistic($force_regeneration = false) {
        if (! $this->user_has_role('planchiste')) {
            $this->dx_auth->deny_access();
        }
        
        $this->load->helper('Statistic');

        $year = $this->session->userdata('year');

        $data ['per_month'] = $this->stat_per_month($year);
        $data ['per_machine'] = $this->stat_per_machine($year);
        $data ['flights_per_machine'] = $this->stat_per_machine($year, 'count');
        $data ['motor_per_machine'] = $this->stat_per_machine($year, 'moteur');
        $data ['gliders'] = $this->planeurs_model->list_of();
        $data ['year'] = $year;
        $data ['year_selector'] = $this->gvv_model->getYearSelector("vpdate");
        $this->push_return_url("statistiques planeur page");

        $data ['latest_flight'] = $this->gvv_model->latest_flight(array (
                'year(vpdate)' => $year
        ));

        $flight_exist = (count($data ['latest_flight']) > 0);

        if (false) {
        if ($flight_exist || $force_regeneration) {
            // var_dump($data['latest_flight']);

            $latest_date = $data ['latest_flight'] [0] ['vpdate'];
            $latest_time = $data ['latest_flight'] [0] ['vpcdeb'];
            $latest_epoch = strtotime($latest_date) + ( int ) ($latest_time * 3600);

            // Génération image par mois
            $filename = image_dir() . "planeur_mois_$year.png";
            if ($force_regeneration || no_file_or_file_too_old($filename, $latest_epoch)) {
                $lines = $data ['per_month'];
                add_first_row($lines, $this->title_row);
                add_first_col($lines, $this->first_col);

                month_chart($filename, $lines, array (
                        1,
                        3,
                        7,
                        11
                ), $this->lang->line("gvv_vols_planeur_header_hours"));
            }

            // Génération image par machine
            $filename = image_dir() . "planeur_machine_$year.png";
            if ($force_regeneration || no_file_or_file_too_old($filename, $latest_epoch)) {
                machine_barchart($filename, $data ['per_machine'], $this->lang->line("gvv_vols_planeur_header_hours"));
            }
        } // flight_exist
        }
        $data ['total'] = $this->state_per_category($year);
        $data ['totaldc'] = $this->state_per_category($year, array (
                'vpdc' => 1
        ));
        $data ['totalhommes'] = $this->state_per_category($year, array (
                'msexe' => 'M'
        ));
        $data ['totalfem'] = $this->state_per_category($year, array (
                'msexe' => 'F'
        ));

        // date anniversaire de basculement
        $date25 = date_m25ans($year);

        $data ['total25'] = $this->state_per_category($year, array (
                'mdaten >=' => $date25
        ));
        $data ['total_plus'] = $this->state_per_category($year, array (
                'mdaten <' => $date25
        ));
        $data ['totalclub'] = $this->state_per_category($year, array (
                'mpprive' => 0
        ));
        $data ['totalpriv'] = $this->state_per_category($year, array (
                'mpprive' => 1
        ));

        load_last_view('vols_planeur/statistic', $data);
    }

    /**
     * Affiche la page de cumuls annuel
     */
    public function cumuls() {
        if (! $this->user_has_role('planchiste')) {
            $this->dx_auth->deny_access();
        }
        
        $first_flight = $this->gvv_model->latest_flight(array (), "asc");
        if (count($first_flight) < 1) {
            $data ['title'] = $this->lang->line("gvv_error");
            $data ['text'] = $this->lang->line("gvv_no_flights");
            return load_last_view('message', $data);
        }

        $data = array ();
        $data ['controller'] = $this->controller;
        $data ['jsonurl'] = site_url() . '/' . $this->controller . '/ajax_cumuls';
        $data ['year'] = date("Y");
        $data ['first_year'] = $this->gvv_model->first_year();
        $data ['title_key'] = "gvv_vols_planeur_title_cumul";

        return load_last_view($this->controller . '/cumuls', $data);
    }

    /**
     * Affiche la page historique
     */
    public function histo() {
        if (! $this->user_has_role('planchiste')) {
            $this->dx_auth->deny_access();
        }
        
        $data = array ();
        $data ['machines'] = $this->gvv_model->histo(true);
        $data ['controller'] = $this->controller;
        $data ['jsonurl'] = site_url() . '/' . $this->controller . '/ajax_histo';
        $data ['title_key'] = 'gvv_vols_planeur_title_histo';
        $data ['year'] = date('Y');
        $data ['first_year'] = $this->gvv_model->first_year(true);

        return load_last_view($this->controller . '/histo', $data);
    }

    /**
     * Affiche la page Age moyen du parc
     */
    public function age() {
        if (! $this->user_has_role('planchiste')) {
            $this->dx_auth->deny_access();
        }

        // $tmp = $this->gvv_model->age();
        // var_dump($tmp); exit;
        $data = array ();
        $data ['machines'] = $this->gvv_model->age(true);
        $data ['controller'] = $this->controller;
        $data ['jsonurl'] = site_url() . '/' . $this->controller . '/ajax_age';
        $data ['title_key'] = 'gvv_vols_planeur_title_age';

        $data ['year'] = date('Y');
        $data ['first_year'] = $this->gvv_model->first_year(true);

        return load_last_view($this->controller . '/histo', $data);
    }

    /**
     * Export au format CSV
     *
     * @param
     *            $premier
     */
    function export_per($year, $type = "month") {
        if (! $this->dx_auth->is_role('planchiste')) {
            $this->dx_auth->deny_access();
        }
        
        $this->load->helper('statistic');
        date_default_timezone_set('Europe/Paris');

        $dt = date("Y_m_d");

        if ($type == "month") {
            $data = $this->stat_per_month($year);
            $title = $this->lang->line("gvv_vols_planeur_header_glider_activity") . " " . $this->lang->line("gvv_vols_planeur_header_per_month") . " " . $this->lang->line("gvv_vols_planeur_header_in") . " $year";

            add_first_row($data, $this->title_row);
            add_first_col($data, $this->first_col);
        } else {
            $data = $this->stat_per_machine($year);
            $title = $this->lang->line("gvv_vols_planeur_header_glider_activity") . " " . $this->lang->line("gvv_vols_planeur_header_per_glider") . " " . $this->lang->line("gvv_vols_planeur_header_in") . " $year";

            add_first_row($data, $this->pm_first_row);
        }

        csv_file($title, $data, true);
    }

    /**
     * Export au format PDF
     *
     * @param
     *            $premier
     */
    function pdf_machine($year) {
        if (! $this->user_has_role('planchiste')) {
            $this->dx_auth->deny_access();
        }
        
        $this->load->helper('statistic');

        $data = $this->stat_per_machine($year);
        $vols = $this->stat_per_machine($year, 'count');
        $moteur = $this->stat_per_machine($year, 'moteur');

        add_first_row($data, $this->pm_first_row);

        $this->load->library('Pdf');
        $pdf = new Pdf();

        $w = array (
                15,
                18,
                12,
                12,
                12,
                12,
                12,
                12,
                12,
                12,
                12,
                12,
                12,
                12,
                12,
                12,
                12,
                12,
                12
        );
        $align = array (
                'L',
                'L',
                'R',
                'R',
                'R',
                'R',
                'R',
                'R',
                'R',
                'R',
                'R',
                'R',
                'R',
                'R',
                'R',
                'R',
                'R',
                'R',
                'R'
        );

        $pdf->AddPage();
        $title1 = $this->lang->line("gvv_vols_planeur_header_hours") . " $year " . $this->lang->line("gvv_vols_planeur_header_per_glider");
        $pdf->title($title1);
        $pdf->table($w, 8, $align, $data);

        if (count($vols)) {
            $pdf->AddPage();
            $title = $this->lang->line("gvv_vols_planeur_header_flights") . " $year " . $this->lang->line("gvv_vols_planeur_header_per_glider");
            $pdf->title($title);
            $pdf->table($w, 8, $align, $vols);
        }

        if (count($moteur)) {
            $pdf->AddPage();
            $title = $this->lang->line("gvv_vols_planeur_header_engine") . " $year " . $this->lang->line("gvv_vols_planeur_header_per_glider");
            $pdf->title($title);
            $pdf->table($w, 8, $align, $moteur);
        }

        $pdf->AddPage();
        $pdf->title($title1);
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->Image(image_dir() . "planeur_machine_$year.png", $x + 10, $y, 170);

        $pdf->Output();
    }

    /**
     * Export au format PDF
     */
    function pdf_month($year) {
        if (! $this->user_has_role('planchiste')) {
            $this->dx_auth->deny_access();
        }
        
        $this->load->helper('statistic');

        $data = $this->stat_per_month($year);

        add_first_row($data, $this->title_row);
        add_first_col($data, $this->first_col);

        $this->load->library('Pdf');
        $pdf = new Pdf();

        $title = $this->lang->line("gvv_vols_planeur_header_glider_activity") . " " . $this->lang->line("gvv_vols_planeur_header_per_month") . " " . $this->lang->line("gvv_vols_planeur_header_in") . " $year";

        pdf_per_month_page($pdf, $year, $data, $title, "planeur");

        $pdf->Output();
    }

    /**
     * Retourne des informations sur la machine selectionnée
     */
    function ajax_machine_info() {
        $machine = $this->input->post('machine');

        $planeur = $this->planeurs_model->get_by_id('mpimmat', $machine);
        $id = $planeur ['mpimmat'];
        $treuillable = $planeur ['mptreuil'];
        $autonome = $planeur ['mpautonome'];
        $biplace = $planeur ['mpbiplace'];

        $json = '{';
        $json .= "\"machine\": \"$id\"";
        $json .= ", \"treuil\": $treuillable";
        $json .= ", \"autonome\": $autonome";
        $json .= ", \"biplace\": $biplace";
        $json .= "}";
        echo $json;
    }

    /**
     * Vérifie qu'un des éléments du tableau match le pattern
     */
    function matching_row($row, $pattern) {
        foreach ( $row as $elt ) {
            if (preg_match('/' . $pattern . '/', $elt, $matches)) {
                return TRUE;
            }
        }
        return false;
    }

    /**
     * Génere les information demandées par le datatable Jquery
     *
     * Support du filtrage, du tri par colonne et de la pagination.
     * La pagination doit être faite après le filtrage(on pagine sur les
     * données filtrées). Le filtrage doit être fait après formattage des
     * données de façon à pour voir filtrer sur les champs tels qu'ils sont
     * affichés.
     */
    function ajax_page() {
        $year = $this->session->userdata('year');
        $date25 = date_m25ans($year);

        $this->load->model('vols_planeur_model');

        gvv_debug("ajax_page vols planeur $year");
        gvv_debug("ajax_page url = " . curPageURL());

        $selection = $this->selection();

        /*
         * Paging
         */
        $per_page = 1000000;
        $first = 0;
        if (isset($_GET ['iDisplayStart']) && $_GET ['iDisplayLength'] != '-1') {
            $first = mysql_real_escape_string($_GET ['iDisplayStart']);
            $per_page = mysql_real_escape_string($_GET ['iDisplayLength']);
            gvv_debug("ajax_page first = $first, per_page = $per_page ");
        }

        $order = "";
        /*
         * Ordering
         */
        $direction = "desc";
        if (isset($_GET ['iSortCol_0'])) {
            for($i = 0; $i < intval($_GET ['iSortingCols']); $i ++) {
                // foreach column $i
                if ($_GET ['bSortable_' . intval($_GET ['iSortCol_' . $i])] == "true") {
                    $direction = mysql_real_escape_string($_GET ['sSortDir_' . $i]);

                    if ($i == 0) {
                        $order .= "vpdate $direction, vpcdeb $direction, ";
                    }
                }
            }

            $order = substr_replace($order, "", - 2); // remove last comma
        }

        $order = $direction;
        gvv_debug("ajax order = $order");

        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $search = "";
        if (isset($_GET ['sSearch'])) {
            if ($_GET ['sSearch'] != "") {
                $search = mysql_real_escape_string($_GET ['sSearch']);

                // En cas de filtrage, il faut faire la pagination à la main
                $per_page = 1000000;
                $first = 0;
            }
        }
        gvv_debug("ajax search = $search");

        $result = $this->vols_planeur_model->select_page($year, $per_page, $first, $selection, $order);
        $result = $this->gvvmetadata->normalise("vue_vols_planeur", $result);
        gvv_debug("ajax result 1 =" . var_export($result, true));

        $iTotal = $this->vols_planeur_model->count($selection);
        gvv_debug("\$iTotal = $iTotal");

        if ($search != "") {
            // selection
            $not_filtered = $result;
            $result = array ();
            $iFilteredTotal = 0;

            // reset la pagination qui a pu être écrasée à cause de la gestion manuelle
            if (isset($_GET ['iDisplayStart']) && $_GET ['iDisplayLength'] != '-1') {
                $first = mysql_real_escape_string($_GET ['iDisplayStart']);
                $per_page = mysql_real_escape_string($_GET ['iDisplayLength']);
            }

            foreach ( $not_filtered as $row ) {

                $match = true;
                if ($this->matching_row($row, $search)) {
                    $iFilteredTotal ++;

                    // in the window ?
                    if (($iFilteredTotal >= $first) && ($iFilteredTotal < $first + $per_page)) {
                        $result [] = $row;
                    }
                }
            }
        } else {
            $iFilteredTotal = $iTotal;
        }

        /*
         * Output generation
         */
        $output = array (
                "sEcho" => intval($_GET ['sEcho']),
                "iTotalRecords" => $iTotal,
                "iTotalDisplayRecords" => $iFilteredTotal,
                "aaData" => array ()
        );

        /*
         * Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field(for example a counter or static image)
         */
        $out_cols = array (
                'vpdate',
                'vpcdeb',
                'vpduree',
                'vpmacid',
                'pilote',
                'instructeur',
                'vpautonome',
                'rem_id',
                'vpaltrem',
                'vpobs',
                'vplieudeco',
                'vpnbkm',
                'm25ans',
                'vpdc',
                'prive',
                'vpcategorie'
        );
        // 'vpcfin'

        $actions = array (
                'edit',
                'delete'
        );
        if (! $this->user_has_role('planchiste')) {
            $actions = [];
        }
        

        /* Indexed column(used for fast and accurate table cardinality) */
        $sIndexColumn = "vpid";

        foreach ( $result as $select_row ) {
            $row = array ();

            foreach ( $actions as $action ) {
                $url = $this->controller . "/$action";
                $elt_image = $select_row ['image'];
                $confirm = ($action == 'delete');

                $image = $this->gvvmetadata->action($action, $url, $select_row [$sIndexColumn], $elt_image, $confirm);
                $row [] = $image;
            }

            for($i = 0; $i < count($out_cols); $i ++) {
                if ($out_cols [$i] != ' ') {
                    // General output
                    $value = $select_row [$out_cols [$i]];
                    if ($value == null)
                        $value = "";
                    $row [] = $value;
                } else {
                    $row [] = "";
                }
            }

            $output ['aaData'] [] = $row;
        }

        $json = json_encode($output);
        gvv_debug("json = $json");
        echo $json;
    }

    /**
     * Retourne les informations pour le cumul
     */
    function ajax_cumuls() {
        $year = date("Y");
        $first_flight = $this->gvv_model->latest_flight(array (), "asc");
        if (count($first_flight) < 1) {
            $first_year = date("Y");
        } else {
            $first_year = $first_flight [0] ['year'];
        }
        $json = $this->gvv_model->cumul_heures($year, $first_year);

        echo $json;
    }

    /**
     * Retourne les informations pour le cumul
     */
    function ajax_histo() {
        echo $this->gvv_model->histo();
    }

    /**
     * Retourne les informations pour le viellissement
     */
    function ajax_age() {
        echo $this->gvv_model->age();
    }

    /**
     * Hook activé après la création d'un élément
     * Ce mécanisme permet de laisser le contrôleur parent faire la majeur
     * partie de boulot mais également de réaliser des traitements spécifiques
     * dans les enfants.
     *
     * @param $data enregistrement
     *            crée
     */
    function post_create($data = array()) {
        gvv_debug($this->controller . " overwritten creation " . var_export($data, true));

        $certificats = $this->input->post('certificat_values');
        $fai = $this->input->post('certificat_fai_values');

        $vpid = $data ['vpid'];

        if ($vpid < 1) {
            var_dump("Erreur vpid = 0");
            var_dump($_POST);
            exit();
        }

        $event = array (
                'emlogin' => $data ['vppilid'],
                'edate' => $data ['vpdate'],
                'evpid' => $data ['vpid'],
                'ecomment' => $data ['vpobs']
        );

        $certifs = array ();
        if ($certificats)
            $certifs = array_merge($certifs, $certificats);
        if ($fai)
            $certifs = array_merge($certifs, $fai);
        foreach ( $certifs as $etype ) {
            $event ['etype'] = $etype;
            $this->event_model->replace($event);
        }
    }

    /**
     * Hook activé avant la destruction
     *
     * @param $id clé
     *            de l'élément à détruire
     */
    function pre_delete($id) {
        gvv_debug($this->controller . " overwritten delete $id");

        // Supprime les certificats associés au vol
        $this->event_model->delete(array (
                'evpid' => $id
        ));

        // Annule la facturation
        $this->load->library("Facturation", '', 'facturation_generique');
        $club = $this->config->item('club');
        if ($club) {
            $facturation_module = "Facturation_" . $club;
            $this->load->library($facturation_module, '', "facturation_club");
            $this->facturation_club->annule_facturation_vol_planeur($id);
        } else {
            $this->facturation_generique->annule_facturation_vol_planeur($id);
        }
    }

    /**
     * Hook activé après la mise à jour
     *
     * @param $data enregistrement
     *            modifié
     */
    function post_update($data = array()) {
        gvv_debug($this->controller . " overwritten post modification " . var_export($data, true));
        $this->post_create($data);
    }

    /**
     * Hook activé avant la mise à jour
     */
    function pre_update($id, $data = array()) {
        gvv_debug($this->controller . " overwritten pre modification $id " . var_export($data, true));
        $this->pre_delete($data [$id]);
    }

    /**
     * Transforme les données brutes en base en données affichables
     * Default implementation returns the data attribute
     *
     * @param $action CREATION
     *            | MODIFICATION | VISUALISATION
     */
    function form2database($action = '') {
        $processed_data = array ();
        // Méthode basée sur les méta-données
        $table = $this->gvv_model->table();
        $fields_list = $this->gvvmetadata->fields_list($table);
        foreach ( $fields_list as $field ) {
            $processed_data [$field] = $this->gvvmetadata->post2database($table, $field, $this->input->post($field));
        }
        
        // Convert account ID to member ID for payeur field
        if (!empty($processed_data['payeur']) && is_numeric($processed_data['payeur'])) {
            $account = $this->comptes_model->get_by_id('id', $processed_data['payeur']);
            if ($account && !empty($account['pilote'])) {
                $processed_data['payeur'] = $account['pilote'];
            }
        }
        
        if (! $processed_data ['vpdc']) {
            $processed_data ['vpinst'] = $this->input->post('vppassager');
        }
        if ($processed_data ['vpautonome'] == 1) {
            $processed_data ['pilote_remorqueur'] = $this->input->post('vptreuillard');
        }
        return $processed_data;
    }

    /**
     * compte le nombre de jours de vol par pilote pour la selection
     */
    function jours_de_vol() {
        if (! $this->dx_auth->is_role('planchiste')) {
            $this->dx_auth->deny_access();
        }
        
        $data ['title'] = 'Jours de vol sur machine club par personne';
        $data ['text'] = 'Attention, le filtrage est controlé sur la page vols planeur.';
        $data ['attrs'] = array ();
        $data ['request'] = "";
        $data ['table'] = $this->gvv_model->jours_de_vol($this->selection());
        load_last_view('message', $data);
    }

    /**
     * Liste les heurs par pilotes et par machines
     */
    function par_pilote_machine($type = "html", $what = "total") {
        // Legacy: is_role('planchiste') returns TRUE for ca/bureau/tresorier via hierarchy.
        // New auth is non-hierarchical, so check all equivalent roles explicitly.
        if (! $this->user_has_role('planchiste')
            && ! $this->user_has_role('ca')
            && ! $this->user_has_role('tresorier')) {
            $this->dx_auth->deny_access();
        }
        
        $selection_total = $this->selection(FALSE);

        // total par machine
        $result = $this->gvv_model->par_pilote_machine("mlogin, vpmacid", $selection_total, array (
                'membres.actif' => "1",
                'machinesp.actif' => "1"
        ));
        $result = flat_array($result, 'nom', 'vpmacid', 'minutes', " 0h00");
        $data ['total'] = $result;

        // Solo par machine
        $result = $this->gvv_model->par_pilote_machine("mlogin, vpmacid", $selection_total, array (
                'membres.actif' => "1",
                'machinesp.actif' => "1",
                "vpdc" => "0"
        ));
        $result = flat_array($result, 'nom', 'vpmacid', 'minutes', " 0h00");
        $data ['total_solo'] = $result;

        // total heures par an
        $result = $this->gvv_model->par_pilote_machine("mlogin, year", $selection_total, array (
                'membres.actif' => "1",
                'machinesp.actif' => "1"
        ));
        $result = flat_array($result, 'nom', 'year', 'minutes');
        $data ['hours_per_year'] = $result;

        // total vols par an
        $result = $this->gvv_model->par_pilote_machine("mlogin, year", $selection_total, array (
                'membres.actif' => "1",
                'machinesp.actif' => "1"
        ));
        $result = flat_array($result, 'nom', 'year', 'count');
        $data ['flights_per_year'] = $result;

        // total heures double par an
        $result = $this->gvv_model->par_pilote_machine("mlogin, year", $selection_total, array (
                'membres.actif' => "1",
                'machinesp.actif' => "1",
                'vpdc' => "1"
        ));
        $result = flat_array($result, 'nom', 'year', 'minutes');
        $data ['double_per_year'] = $result;

        // total vols solo par an
        $result = $this->gvv_model->par_pilote_machine("mlogin, year", $selection_total, array (
                'membres.actif' => "1",
                'machinesp.actif' => "1",
                'vpdc' => "0"
        ));
        $result = flat_array($result, 'nom', 'year', 'count');
        $data ['solo_per_year'] = $result;

        $titles = array (
                'total' => $this->lang->line("gvv_vols_planeur_title_yearly_machine"),
                'total_solo' => $this->lang->line("gvv_vols_planeur_title_solo_machine"),
                'hours_per_year' => $this->lang->line("gvv_vols_planeur_title_yearly_hours"),
                'flights_per_year' => $this->lang->line("gvv_vols_planeur_title_yearly_flights"),
                'double_per_year' => $this->lang->line("gvv_vols_planeur_title_yearly_dual"),
                'solo_per_year' => $this->lang->line("gvv_vols_planeur_title_yearly_solo")
        );

        if ($type == "html") {
            load_last_view('vols_planeur/par_pilote', $data);
        } elseif ($type == "csv") {
            // Excel
            csv_file($titles [$what], $data [$what], true);
        } else {
            // pdf

            $count = count($data [$what] [0]);
            $width = array (
                    'total' => array (
                            30
                    ),
                    'total_solo' => array (
                            30
                    ),
                    'hours_per_year' => array (
                            40
                    ),
                    'flights_per_year' => array (
                            40
                    ),
                    'double_per_year' => array (
                            40
                    ),
                    'solo_per_year' => array (
                            40
                    )
            );

            $align = array (
                    'total' => array (
                            'L'
                    ),
                    'total_solo' => array (
                            'L'
                    ),
                    'hours_per_year' => array (
                            'L'
                    ),
                    'flights_per_year' => array (
                            'L'
                    ),
                    'double_per_year' => array (
                            'L'
                    ),
                    'solo_per_year' => array (
                            'L'
                    )
            );

            $page = array (
                    'total' => 'L',
                    'total_solo' => 'L',
                    'hours_per_year' => 'P',
                    'flights_per_year' => 'P',
                    'double_per_year' => 'P',
                    'solo_per_year' => 'P'
            );

            $wdth = $width [$what];
            $algn = $align [$what];
            for($i = 0; $i < $count; $i ++) {
                $wdth [] = 15;
                $algn [] = 'R';
            }

            $this->load->library('Pdf');
            $pdf = new Pdf();

            $pdf->AddPage($page [$what]);
            $pdf->title($titles [$what], 1);

            $pdf->table($wdth, 8, $algn, $data [$what]);
            $pdf->Output();
        }
    }

}