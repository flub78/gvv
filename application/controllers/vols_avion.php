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
include('./application/libraries/Gvv_Controller.php');

/**
 *
 * Controleur de gestion des vols avion
 *
 * @author Frédéric
 *
 *         TODO Interdire la saisie aux personnes non autorisées.
 *
 */
class Vols_avion extends Gvv_Controller {
    protected $controller = 'vols_avion';
    protected $model = 'vols_avion_model';
    protected $kid = 'vaid';
    protected $modification_level = 'planchiste';

    // Headers and first colomns
    protected $title_row;
    protected $first_col;
    protected $pm_first_row;

    // régles de validation
    protected $rules = array(
        'vanbpax' => "is_natural|max_length[1]",
        'vaatt' => "is_natural"
    );

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();

        // remplit les selecteurs depuis la base
        $this->load->model('membres_model');
        $this->load->model('avions_model');
        $this->load->model('terrains_model');
        $this->load->model('events_types_model');
        $this->lang->load('vols_avion');

        $this->load->helper('statistic');

        // prépare les entêtes pour les stats
        $this->title_row = array_merge(array(
            $this->lang->line("gvv_total")
        ), $this->lang->line("gvv_months"));

        $this->first_col = $this->lang->line("gvv_vols_avion_stats_col");

        $this->pm_first_row = array_merge(array(
            $this->lang->line("gvv_vue_vols_avion_short_field_type"),
            $this->lang->line("gvv_vue_vols_avion_short_field_vamacid")
        ), $this->title_row);
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::form_static_element()
     */
    function form_static_element($action) {
        parent::form_static_element($action);
        $pilote_selector = $this->membres_model->selector_with_null(array(
            'actif' => 1
        ));
        $this->data['saisie_par'] = $this->dx_auth->get_username();
        if (CREATION == $action) {

            $this->data['vacdeb'] = $this->gvv_model->latest_horametre();
        }

        $this->config->load('facturation');
        $this->data['payeur_selector'] = $pilote_selector;
        $this->data['payeur_non_pilote'] = $this->config->item('payeur_non_pilote');
        $this->data['partage'] = $this->config->item('partage');

        $this->data['default_user'] = $this->membres_model->default_id();
        if (! $this->dx_auth->is_role('planchiste', true, true) && ($this->config->item('auto_planchiste'))) {
            // Si l'utilisateur n'est pas planchiste mais que le système est 'auto_planchiste'
            $this->data['auto_planchiste'] = true;
            $this->data['payeur_non_pilote'] = false;
            $this->data['partage'] = false;
            $this->data['pilote_name'] = $this->membres_model->image($this->data['default_user']);
            $pilote_selector = array(
                $this->data['default_user'] => $this->data['pilote_name']
            );
        } else {
            $this->data['auto_planchiste'] = false;
        }

        // Avec les méta-données
        $this->gvvmetadata->set_selector('machine_selector', $this->avions_model->selector(array(
            'actif' => 1
        )));
        $this->gvvmetadata->set_selector('pilote_selector', $pilote_selector);
        $this->gvvmetadata->set_selector('inst_selector', $this->membres_model->qualif_selector('mlogin', FI_AVION | FE_AVION));
        $this->gvvmetadata->set_selector('payeur_selector', $pilote_selector);
        $this->gvvmetadata->set_selector('terrains_selector', $this->terrains_model->selector_with_null());

        // Checkboxes formation
        $certificats = array();
        $select = $this->events_types_model->select_all(array(
            'activite' => 2,
            'en_vol' => 1
        ));

        $date_values = array();
        foreach ($select as $row) {
            $id = $row['id'];
            $certificats[] = array(
                'label' => $row['name'],
                'id' => $id
            );
            // if ($cnt++ % 2)
            // $date_values [$id] = $id;
        }

        $this->data['certificats'] = $certificats;
        $this->data['certificat_values'] = $date_values;

        $this->data['machines'] = $this->avions_model->machine_list(array(
            'actif' => 1
        ));
        $this->data['horametres_en_min'] = $this->avions_model->machine_list(array(
            'actif' => 1
        ), false);

        // ici la $this->data['vaduree'] contient la valuer en 1/100 eme
        // var_dump($this->data); exit;
    }

    /*
     * Transforme une valeur HEURE.MINUTE en HEURE.CENTIEME
     */
    private function to_hundredth($hm) {
        $hours = intval($hm);
        $minutes = ($hm - $hours) * 100;
        $centiemes = $minutes / 60;
        $result = $hours + $centiemes;
        return $result;
    }

    /**
     * Transforme les données brutes en base en données affichables
     * Default implementation returns the data attribute
     *
     * @param $action CREATION
     *            | MODIFICATION | VISUALISATION
     */
    function form2database($action = '') {
        $processed_data = parent::form2database($action);
        $duree = $processed_data['vaduree'];
        $pattern = "\d+h\d+";
        // var_dump($processed_data);
        if (preg_match('/' . $pattern . '/', $duree, $matches)) {
            $debut = $this->to_hundredth($processed_data["vacdeb"]);
            $fin = $this->to_hundredth($processed_data["vacfin"]);
            $duree = intval(($fin - $debut) * 1000) / 1000;
            $processed_data['vaduree'] = $duree;
        }

        return $processed_data;
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::create()
     */
    function create() {
        if (! $this->dx_auth->is_role('planchiste')) {
            $this->dx_auth->deny_access();
        }
        parent::create(TRUE);
        $this->data['vaid'] = 0;
        $this->data['vadate'] = date("Y-m-d");

        $year = $this->session->userdata('year');
        $latestf = $this->gvv_model->latest_flight(array(
            'year(vadate)' => $year
        ));
        $flight_exist = (count($latestf) > 0);

        if ($flight_exist) {
            $this->data['vadate'] = $latestf[0]['vadate'];
            $this->data['vapilid'] = $latestf[0]['vapilid'];
            $this->data['vamacid'] = $latestf[0]['vamacid'];
        }

        // et affiche le formulaire
        load_last_view('vols_avion/formView', $this->data);
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::edit()
     */
    function edit($id = '', $load_view = true, $action = MODIFICATION) {
        if (! $this->dx_auth->is_role('planchiste')) {
            $this->dx_auth->deny_access();
        }

        $this->load->model('ecritures_model');
        $action = (count($this->ecritures_model->select_flight_frozen_lines($id, "vol_avion"))) ? VISUALISATION : MODIFICATION;
        $action = MODIFICATION;
        parent::edit($id, FALSE, $action);

        // Recharge les evénements de formation
        $events = $this->event_model->flight_events(array(
            'evaid' => $id,
            'en_vol' => 1,
            'activite' => 2
        ));
        $date_values = array();
        foreach ($events as $event) {
            $date_values[$event['etype']] = 1;
        }
        $this->data['certificat_values'] = $date_values;

        // affiche le formulaire
        load_last_view('vols_avion/formView', $this->data);
    }

    /**
     * Supprime un élèment
     */
    function delete($id) {
        if (! $this->dx_auth->is_role('planchiste')) {
            $this->dx_auth->deny_access();
        }

        $this->load->model('ecritures_model');
        if (count($this->ecritures_model->select_flight_frozen_lines($id, "vol_avion"))) {
            // Il y a des lignes gelées la suppression est interdite
            $this->session->set_flashdata('popup', "Suppression interdite, écriture vérouillée par le comptable.");
        } else {
            // détruit en base
            $this->pre_delete($id);
            $this->gvv_model->delete(array(
                $this->kid => $id
            ));
        }
        $this->pop_return_url();
    }

    /**
     * Selectionne les éléments à afficher sur une page
     *
     * @param unknown_type $premier
     * @param unknown_type $message
     * @param unknown_type $per_page
     */
    function select_page($premier = 0, $message = '', $per_page = NULL, $order = "desc") {
        if (! isset($per_page))
            $per_page = $this->session->userdata('per_page');

        $this->data['action'] = VISUALISATION;
        $this->data['section'] = $this->gvv_model->section();

        $this->data['filter_active'] = $this->session->userdata('filter_active');
        $this->data['filter_date'] = '';
        $this->data['date_end'] = '';
        $this->data['filter_pilote'] = '';
        $this->data['filter_machine'] = '';
        $this->data['filter_aero'] = '';
        $this->data['filter_25'] = 0;
        $this->data['filter_dc'] = 0;
        $this->data['filter_vi'] = 0;
        $this->data['filter_prive'] = 0;
        $this->data['planchiste'] = $this->dx_auth->is_role('planchiste', true, true);
        $year = $this->session->userdata('year');
        $date25 = date_m25ans($year);
        $selection = "YEAR(vadate) = \"$year\"";

        $this->data['machine_selector'] = '';
        $pilote_selector = $this->membres_model->selector_with_null();
        $this->data['pilote_selector'] = $pilote_selector;

        $machine_selector = $this->avions_model->selector_with_null();
        $this->data['machine_selector'] = $machine_selector;

        $aero_selector = $this->terrains_model->selector_with_all();
        $this->data['aero_selector'] = $aero_selector;

        $this->data['year_selector'] = $this->gvv_model->getYearSelector("vadate");
        $this->data['year'] = $this->session->userdata('year');

        if ($this->session->userdata('filter_active')) {
            $order = "asc";

            $filter_pilote = $this->session->userdata('filter_pilote');
            if ($filter_pilote) {
                $this->data['filter_pilote'] = $filter_pilote;
                $selection .= " and (vapilid = \"$filter_pilote\" or vainst = \"$filter_pilote\" )";
            }

            $filter_machine = $this->session->userdata('filter_machine');
            if ($filter_machine) {
                $this->data['filter_machine'] = $filter_machine;
                if ($selection != '')
                    $selection .= " and ";
                $selection .= "vamacid = \"$filter_machine\" ";
            }

            $filter_aero = $this->session->userdata('filter_aero');
            if ($filter_aero) {
                $this->data['filter_aero'] = $filter_aero;
                if ($selection != '')
                    $selection .= " and ";
                $selection .= "valieudeco = \"$filter_aero\" ";
            }

            $filter_25 = $this->session->userdata('filter_25');
            if ($filter_25 == 1) {
                $this->data['filter_25'] = $filter_25;
                $selection .= " and (mdaten >= \"$date25\" )";
            } else if ($filter_25 == 2) {
                $this->data['filter_25'] = $filter_25;
                $selection .= " and (mdaten < \"$date25\" )";
            }

            $filter_dc = $this->session->userdata('filter_dc');
            if ($filter_dc) {
                $this->data['filter_dc'] = $filter_dc;
                $selection .= " and (vadc = \"$filter_dc\" )";
            }

            $filter_vi = $this->session->userdata('filter_vi');
            if ($filter_vi) {
                $this->data['filter_vi'] = $filter_vi;
                $categorie = $filter_vi - 1;
                $selection .= " and (vacategorie = \"$categorie\" )";
            }

            $filter_prive = $this->session->userdata('filter_prive');
            if ($filter_prive) {
                $this->data['filter_prive'] = $filter_prive;
                $filter_prive--;
                $selection .= " and (machinesa.maprive = \"$filter_prive\" )";
            }

            $filter_date = $this->session->userdata('filter_date');
            $date_end = $this->session->userdata('date_end');
            if ($filter_date) {
                if ($selection != '')
                    $selection .= " and ";
                $this->data['filter_date'] = $filter_date;
                if ($date_end) {
                    $selection .= "vadate >= \"" . date_ht2db($filter_date) . "\" ";
                } else {
                    $selection .= "vadate = \"" . date_ht2db($filter_date) . "\" ";
                }
            }

            if ($date_end) {
                if ($selection != '')
                    $selection .= " and ";
                $this->data['date_end'] = $date_end;
                $selection .= "vadate <= \"" . date_ht2db($date_end) . "\" ";
            }

            if ($selection == "")
                $selection = array();
        }

        // calcul des consommations
        // Doit être appelé avant le select_page
        $this->data['conso'] = $this->gvv_model->conso($year, $selection);

        $this->data['select_result'] = $this->gvv_model->select_page($year, $per_page, $premier, $selection, $order);
        $this->data['kid'] = $this->kid;
        $this->data['controller'] = $this->controller;
        $this->data['lines'] = $this->gvv_model->count($selection);
        $this->data['count'] = $this->gvv_model->sum('vaatt', $selection);
        $this->data['total'] = $this->gvv_model->sum('vaduree', $selection);
        $this->data['m25ans'] = $this->gvv_model->sum('vaduree', $selection, array(
            'mdaten >' => $date25
        ));
        $this->data['count_m25ans'] = $this->gvv_model->sum('vaatt', $selection, array(
            'mdaten >' => $date25
        ));
        $this->data['remorquage'] = $this->gvv_model->sum('vaduree', $selection, array(
            'vacategorie' => 3
        ));
        $this->data['count_remorquage'] = $this->gvv_model->sum('vaatt', $selection, array(
            'vacategorie' => 3
        ));
        $this->data['premier'] = $premier;
        $this->data['message'] = $message;

        if ($this->session->userdata('filter_active') && $filter_pilote) {
            // Calcul aussi les heures CDB, et instructeurs
            $this->data['by_pilote'] = 1;
            $this->data['dc'] = $this->gvv_model->sum('vaduree', $selection, array(
                'vadc' => 1,
                'vapilid' => $filter_pilote
            ));
            $this->data['count_dc'] = $this->gvv_model->sum('vaatt', $selection, array(
                'vadc' => 1,
                'vapilid' => $filter_pilote
            ));
            $this->data['inst'] = $this->gvv_model->sum('vaduree', $selection, array(
                'vadc' => 1,
                'vainst' => $filter_pilote
            ));
            $this->data['cdb'] = $this->data['inst'] + $this->gvv_model->sum('vaduree', $selection, array(
                'vadc' => 0,
                'vapilid' => $filter_pilote
            ));
        } else {
            $this->data['by_pilote'] = 0;
            $this->data['dc'] = $this->gvv_model->sum('vaduree', $selection, array(
                'vadc' => 1
            ));
            $this->data['count_dc'] = $this->gvv_model->sum('vaatt', $selection, array(
                'vadc' => 1
            ));
            $this->data['inst'] = 0;
            $this->data['cdb'] = 0;
        }
        $this->data['has_modification_rights'] = (! isset($this->modification_level) || $this->dx_auth->is_role($this->modification_level, true, true));

        $this->data['default_user'] = $this->membres_model->default_id();
        if (! $this->dx_auth->is_role('planchiste', true, true) && ($this->config->item('auto_planchiste'))) {
            // Si l'utilisateur n'est pas planchiste mais que le système est 'auto_planchiste'
            $this->data['auto_planchiste'] = true;
            $this->data['payeur_non_pilote'] = false;
            $this->data['partage'] = false;
            $this->data['pilote_name'] = $this->membres_model->image($this->data['default_user']);
            $pilote_selector = array(
                $this->data['default_user'] => $this->data['pilote_name']
            );
        } else {
            $this->data['auto_planchiste'] = false;
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::page()
     */
    function page($premier = 0, $message = '', $selection = array()) {
        $this->push_return_url("vols avion page");
        $this->select_page($premier, $message);
        return load_last_view($this->table_view, $this->data, $this->unit_test);
    }

    /**
     * Export des planches au format CSV
     */
    function csv() {
        $this->select_page(0, "", 100000);
        $this->gvvmetadata->csv("vue_vols_avion");
    }

    /**
     * Export des planches au format PDF
     */
    function pdf() {
        $this->select_page(0, "", 100000, null, "asc");

        $year = $this->session->userdata('year');

        $this->load->library('Pdf');
        $pdf = new Pdf();

        $pdf->AddPage('L');
        $pdf->title($this->lang->line("gvv_vols_avion_title_list") . " $year", 1);

        $tab = array();

        $tab[0] = $this->lang->line("gvv_vols_avion_pdf_header");

        $data = $this->data;
        $results = $this->data['select_result'];
        $pilots = $this->data['pilote_selector'];
        // print_r($data);
        $line = 1;
        foreach ($results as $row) {

            $row['vadate'] = date_db2ht($row['vadate']);
            if (isset($row['vainst'])) {
                $row['vainst'] = substr($pilots[$row['vainst']], 0, 12);
            }
            foreach (
                array(
                    'vadc',
                    'm25ans'
                ) as $field
            ) {
                $row[$field] = ($row[$field]) ? 'X' : '';
            }
            $categories = $this->config->item('categories_vol_avion_short');
            $row['vacategorie'] = $categories[$row['vacategorie']];

            $fld = 0;
            $fields = array(
                'vadate',
                'vacdeb',
                'vacfin',
                'vaduree',
                'vamacid',
                'vaatt',
                'pilote',
                'instructeur',
                'vacategorie',
                'vadc',
                'prive',
                'm25ans',
                'vaobs'
            );
            foreach ($fields as $field) {
                $tab[$line][$fld++] = $row[$field];
            }
            $line++;
            foreach (
                array(
                    'vadate',
                    'vacdeb',
                    'vacfin',
                    'vaduree',
                    'vamacid',
                    'pilote',
                    'instructeur',
                    'vacategorie',
                    'vadc',
                    'prive',
                    'm25ans'
                ) as $field
            ) {
                // $backup .= $row[$field] . ";";
            }
            // $backup .= $row['vaobs'] . "\n";
        }
        $w = array(
            18,
            15,
            15,
            15,
            15,
            16,
            35,
            16,
            8,
            8,
            8,
            10,
            60
        );
        $align = array(
            'L',
            'R',
            'R',
            'R',
            'R',
            'R',
            'L',
            'L',
            'L',
            'C',
            'C',
            'C',
            'L'
        );
        $pdf->table($w, 8, $align, $tab);
        $pdf->Output();
    }

    /**
     * Active ou désactive le filtrage
     */
    public function filterValidation($action) {
        $button = $this->input->post('button');

        if ($button == $this->lang->line("gvv_str_select")) {
            // Enable filtering
            $session['filter_date'] = $this->input->post('filter_date');
            $session['date_end'] = $this->input->post('date_end');
            $session['filter_pilote'] = $this->input->post('filter_pilote');
            $session['filter_machine'] = $this->input->post('filter_machine');
            $session['filter_aero'] = $this->input->post('filter_aero');

            $session['filter_25'] = $this->input->post('filter_25');
            $session['filter_dc'] = $this->input->post('filter_dc');
            $session['filter_prive'] = $this->input->post('filter_prive');
            $session['filter_vi'] = $this->input->post('filter_vi');

            $session['filter_active'] = 1;
            $this->session->set_userdata($session);
        } else {
            // Disable filtering
            foreach (
                array(
                    'filter_date',
                    'date_end',
                    'filter_pilote',
                    'filter_machine',
                    'filter_aero',
                    'filter_active',
                    'filter_25',
                    'filter_dc',
                    'filter_prive',
                    'filter_vi'
                ) as $field
            ) {
                $this->session->unset_userdata($field);
            }
        }
        redirect($this->controller . '/page');
    }

    /**
     * Affiche les vols de la machine
     */
    public function vols_de_la_machine($machine) {
        // Enable filtering
        $session['filter_date'] = '';
        $session['date_end'] = '';
        $session['filter_pilote'] = '';
        $session['filter_machine'] = $machine;
        $session['filter_aero'] = '';
        $session['filter_active'] = 1;
        $this->session->set_userdata($session);
        $this->page();
    }

    /**
     * Affiche les vols du pilote
     */
    public function vols_du_pilote($pilote) {
        // Enable filtering
        $session['filter_date'] = '';
        $session['date_end'] = '';
        $session['filter_pilote'] = $pilote;
        $session['filter_machine'] = '';
        $session['filter_aero'] = '';
        $session['filter_active'] = 1;
        $this->session->set_userdata($session);
        $this->page();
    }

    /**
     * Statistiques
     */
    public function stat_per_month($year) {
        $selection = array(
            'year(vadate)' => $year
        );
        $date25 = date_m25ans($year);

        $pm = array();
        $where = $selection;
        $pm[] = $this->gvv_model->line_monthly('centiemes', $where);
        $pm[] = $this->gvv_model->line_monthly('count', $where);

        $where = array_merge($selection, array(
            'mdaten >=' => $date25
        ));
        $pm[] = $this->gvv_model->line_monthly('centiemes', $where);
        $pm[] = $this->gvv_model->line_monthly('centiemes', $where, $pm[1]);
        $pm[] = $this->gvv_model->line_monthly('count', $where);
        $pm[] = $this->gvv_model->line_monthly('count', $where, $pm[2]);

        $where = array_merge($selection, array(
            'msexe' => 'F'
        ));
        $pm[] = $this->gvv_model->line_monthly('centiemes', $where);
        $pm[] = $this->gvv_model->line_monthly('centiemes', $where, $pm[1]);
        $pm[] = $this->gvv_model->line_monthly('count', $where);
        $pm[] = $this->gvv_model->line_monthly('count', $where, $pm[2]);

        $where = array_merge($selection, array(
            'vadc' => 1
        ));
        $pm[] = $this->gvv_model->line_monthly('centiemes', $where);
        $pm[] = $this->gvv_model->line_monthly('centiemes', $where, $pm[1]);
        $pm[] = $this->gvv_model->line_monthly('count', $where);
        $pm[] = $this->gvv_model->line_monthly('count', $where, $pm[2]);

        $where = array_merge($selection, array(
            'vacategorie' => 1
        ));
        $pm[] = $this->gvv_model->line_monthly('count', $where);
        return $pm;
    }

    /**
     * Calcul les statistiques par machine
     */
    public function stat_per_machine($year, $type = 'centiemes') {
        $selection = array(
            'year(vadate)' => $year
        );

        $pm = array();

        $machines = $this->avions_model->select_all(array(), "macmodele");
        foreach ($machines as $machine) {
            $immat = $machine['macimmat'];
            $modele = $machine['macmodele'];
            $line = array(
                $modele,
                $immat
            );
            $where = array_merge($selection, array(
                'vamacid' => $immat
            ));
            $line = array_merge($line, $this->gvv_model->line_monthly($type, $where));
            if ($line[2] > 0) {
                $pm[] = $line;
            }
        }
        return $pm;
    }

    /**
     * Affiche la page de statistique
     */
    public function statistic($force_regeneration = false) {
        $this->load->helper('Statistic');
        $year = $this->session->userdata('year');

        $data['per_month'] = $this->stat_per_month($year);
        $data['per_machine'] = $this->stat_per_machine($year);
        $data['machines'] = $this->avions_model->list_of();
        $data['year'] = $year;
        $data['year_selector'] = $this->gvv_model->getYearSelector("vadate");
        $this->push_return_url("vols avion statistiques");

        // var_dump($data['per_month']);

        $data['latest_flight'] = $this->gvv_model->latest_flight(array(
            'year(vadate)' => $year
        ));
        $flight_exist = (count($data['latest_flight']) > 0);

        if (false) {
            if ($flight_exist || $force_regeneration) {

                $latest_date = $data['latest_flight'][0]['vadate'];
                $latest_time = $data['latest_flight'][0]['vacdeb'];
                $latest_epoch = strtotime($latest_date) + (int) ($latest_time * 3600);

                $filename = image_dir() . "avion_mois_$year.png";
                if ($force_regeneration || no_file_or_file_too_old($filename, $latest_epoch)) {
                    month_chart($filename, $data['per_month'], array(
                        1,
                        3,
                        7,
                        11
                    ), "Heures de vol");
                }

                $filename = image_dir() . "avion_machine_$year.png";
                if ($force_regeneration || no_file_or_file_too_old($filename, $latest_epoch)) {
                    # machine_barchart($filename, $data ['per_machine'], "Heures de vol");
                }
            }
        }
        load_last_view('vols_avion/statistic', $data);
    }

    /**
     * Export des stats par mois en CSV
     */
    function csv_month($year) {
        $this->load->helper('csv');

        $this->load->helper('statistic');

        $data = $this->stat_per_month($year);
        $title = $this->lang->line("gvv_vols_avion_header_airplane_activity") . " " . $this->lang->line("gvv_vols_avion_header_per_month") . " " . $this->lang->line("gvv_vols_avion_header_in") . " $year";

        add_first_row($data, $this->title_row);
        add_first_col($data, $this->first_col);

        csv_file($title, $data, true);
    }

    /**
     *
     * Export des stats par mois en pdf
     *
     * @param unknown_type $year
     */
    function pdf_month($year) {
        $this->load->helper('statistic');

        $data = $this->stat_per_month($year);

        $this->load->library('Pdf');
        $pdf = new Pdf();

        $title = "Répartition des heures de vol avion $year par mois";
        pdf_per_month_page($pdf, $year, $data, $title, "avion");

        $pdf->Output();
    }

    /**
     *
     * Export des stats par machine en CSV
     *
     * @param unknown_type $year
     */
    function csv_machine($year) {
        $this->load->helper('csv');

        $this->load->helper('statistic');

        $data = $this->stat_per_machine($year);

        $title = $this->lang->line("gvv_vols_avion_header_airplane_activity") . " " . $this->lang->line("gvv_vols_avion_header_per_aircraft") . " " . $this->lang->line("gvv_vols_avion_header_in") . " $year";
        add_first_row($data, $this->pm_first_row);

        csv_file($title, $data, true);
    }

    /**
     * Export des stats par machine en PDF
     * Enter description here .
     *
     * ..
     *
     * @param unknown_type $year
     */
    function pdf_machine($year) {
        $this->load->helper('statistic');

        $data = $this->stat_per_machine($year);
        $vols = $this->stat_per_machine($year, 'count');
        add_first_row($data, $this->pm_first_row);
        add_first_row($vols, $this->pm_first_row);

        $this->load->library('Pdf');
        $pdf = new Pdf();

        // pdf_per_machine_page($pdf, $year, $data, $vols, "avion");

        $pdf->AddPage();
        $title1 = $this->lang->line("gvv_vols_avion_header_hours") . " $year " . $this->lang->line("gvv_vols_avion_header_per_aircraft");

        $pdf->title($title1);

        $w = array(
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
        $align = array(
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

        $pdf->table($w, 8, $align, $data);

        if (count($vols)) {
            $pdf->AddPage();
            $title2 = $this->lang->line("gvv_vols_avion_header_flights") . " $year " . $this->lang->line("gvv_vols_avion_header_per_aircraft");

            $pdf->title($title2);
            $pdf->table($w, 8, $align, $vols);
        }

        $pdf->AddPage();
        $pdf->title($title1);
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->Image(image_dir() . "avion_machine_$year.png", $x + 10, $y, 170);

        $pdf->Output();
    }

    /**
     * Retourne des informations sur la machine selectionnée
     */
    function ajax_machine_info() {
        $machine = $this->input->post('machine');

        gvv_debug("machine=$machine");
        $avion = $this->avions_model->get_by_id('macimmat', $machine);
        $id = $avion['macimmat'];
        $places = $avion['macplaces'];
        if ($avion['horametre_en_minutes']) {
            $unit = 'min';
        } else {
            $unit = 'cent';
        }
        $hora = $this->gvv_model->latest_horametre(array(
            'vamacid' => $id
        ));
        gvv_debug("latest_horametre($id)=$hora");

        $json = '{';
        $json .= "\"machine\": \"$id\"";
        $json .= ", \"places\": \"$places\"";
        $json .= ", \"unit\": \"$unit\"";
        $json .= ", \"hora\": \"$hora\"";
        $json .= "}";
        echo $json;
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

        $vaid = $data['vaid'];

        if ($vaid < 1) {
            var_dump("Erreur vols_avion.post_create vaid = 0");
            var_dump($_POST);
            exit();
        }

        $event = array(
            'emlogin' => $data['vapilid'],
            'edate' => $data['vadate'],
            'evaid' => $data['vaid'],
            'ecomment' => $data['vaobs']
        );

        if ($certificats)
            foreach ($certificats as $etype) {
                $event['etype'] = $etype;
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
        $this->event_model->delete(array(
            'evaid' => $id
        ));
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
        $this->event_model->delete(array(
            'evaid' => $data[$id]
        ));
    }

    /**
     * Affiche la page de cumuls annuel
     */
    public function cumuls() {
        $year = date("Y");
        $first_flight = $this->gvv_model->latest_flight(array(), "asc");
        if (count($first_flight) < 1) {
            $data['title'] = $this->lang->line("gvv_error");
            $data['text'] = $this->lang->line("gvv_no_flights");
            return load_last_view('message', $data);
        }
        $first_year = $first_flight[0]['year'];

        $data = array();
        $data['controller'] = $this->controller;
        $data['jsonurl'] = base_url() . 'index.php/' . $this->controller . '/ajax_cumuls';

        $data['year'] = $year;
        $data['first_year'] = $first_year;
        $data['title_key'] = "gvv_vols_avion_title_cumul";

        return load_last_view('vols_planeur/cumuls', $data);
    }

    /**
     * Retourne les informations pour le cumul
     */
    function ajax_cumuls() {
        $year = date("Y");
        $first_flight = $this->gvv_model->latest_flight(array(), "asc");
        $first_year = $first_flight[0]['year'];
        $json = $this->gvv_model->cumul_heures($year, $first_year);

        echo $json;
    }

    /**
     * Test unitaire
     */
    function test($format = "html") {
        // parent::test($format);
        $this->unit_test = TRUE;
        $this->load->library('unit_test');

        $this->unit->run(true, true, "Tests $this->controller");
        $this->tests_results($format);
    }
}
