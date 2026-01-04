<?php

/**
 *   GVV Gestion vol à voile
 *   Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @filesource event.php
 * @package controllers
 *
 * controleur de gestion des évenements.
 */
include ('./application/libraries/Gvv_Controller.php');

/**
 * Gestion des certificats
 *
 * @author Grégoire
 *
 */
class Event extends Gvv_Controller {
    protected $controller = 'event';
    protected $model = 'event_model';
    protected $kid = 'id';
    protected $modification_level = 'ca'; // no edit delete buttons on list
    protected $rules = array ();
    protected $filter_variables = array (
            'filter_active',
            'filter_membre_actif',
            'filter_25'
    );

    /**
     * Constructeur
     */
    function __construct() {
        parent::__construct();
        $this->load->model('membres_model');
        $this->load->model('events_types_model');
        $this->load->model('vols_avion_model');
        $this->load->model('vols_planeur_model');
        $this->fields ['edate'] ['default'] = date("d/m/Y");
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::index()
     */
    public function index() {
        $this->page();
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::page()
     */
    public function page($premier = 0, $message = '', $selection = array()) {
        // Support legacy $mlogin parameter
        if (!is_numeric($premier) && $premier != '') {
            $mlogin = $premier;
            $premier = 0;
        } else {
            $mlogin = "";
        }
        $this->push_return_url("Events");

        if ($mlogin == "") {
            $mlogin = $this->gvv_model->default_id();
        }
        $this->data ['mlogin'] = $mlogin;
        $this->data ['pilotes_selector'] = $this->membres_model->selector(array (
                'actif' => 1
        ));
        $this->data ['events_list'] = $this->gvv_model->evenement_de($mlogin);
        $this->data ['count'] = count($this->data ['events_list']);
        // var_dump($this->data['events_list']);
        parent::page();
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::form_static_element()
     */
    protected function form_static_element($action) {
        parent::form_static_element($action);
        $this->data ['event_type_selector'] = $this->events_types_model->selector();
        $this->data ["mimage"] = $this->membres_model->image($this->data ["emlogin"]);
        $pilote = $this->data ['emlogin'];
        $this->data ['avions_selector'] = $this->vols_avion_model->selector(array (
                'vapilid' => $pilote
        ));
        $this->data ['planeurs_selector'] = $this->vols_planeur_model->selector(array (
                'vppilid' => $pilote
        ));
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::edit()
     */
    public function edit($id = '', $load_view = true, $action = MODIFICATION) {
        parent::edit($id, FALSE);
        $this->data ['edate'] = date_db2ht($this->data ['edate']);

        return load_last_view($this->form_view, $this->data, $this->unit_test);
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::create()
     */
    public function create($mlogin = "") {
        parent::create(TRUE);

        if ($mlogin == "") {
            $msg = "Pas de pilote séléctionné pour lui ajouter des certificats";
            $data = array (
                    'text' => $msg,
                    'popup' => $msg,
                    'title' => "Création des certificats"
            );
            return load_last_view('message', $data);
        }
        $this->data ['emlogin'] = $mlogin;
        $this->data ["mimage"] = $this->membres_model->image($this->data ["emlogin"]);
        $this->data ['avions_selector'] = $this->vols_avion_model->selector(array (
                'vapilid' => $mlogin
        ));
        $this->data ['planeurs_selector'] = $this->vols_planeur_model->selector(array (
                'vppilid' => $mlogin
        ));

        return load_last_view($this->form_view, $this->data, $this->unit_test);
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::delete()
     */
    public function delete($id) {
        $this->gvv_model->delete(array (
                $this->kid => $id
        ));
        $this->pop_return_url();
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::formValidation()
     */
    public function formValidation($action, $return_on_success = false) {
        if ($_POST ["event_type_flight"] == "aucun") {
            $_POST ['evaid'] = 0;
            $_POST ['evpid'] = 0;
        } else if ($_POST ["event_type_flight"] == "planeur")
            $_POST ['evaid'] = 0;
        else if ($_POST ["event_type_flight"] == "avion")
            $_POST ['evpid'] = 0;
        parent::formValidation($action);
    }

    /**
     * (non-PHPdoc)
     *
     * @see Gvv_Controller::validationOkPage()
     */
    public function validationOkPage($processed_data, $button = "") {
        redirect(controller_url($this->controller) . "/page/" . $processed_data ['emlogin']);
    }

    /**
     * Active ou désactive le filtrage
     */
    public function filterValidation() {
        $this->active_filter($this->filter_variables);

        // Il faut rediriger et non pas appeller $this->page, sinon l'URL
        // enregistrée pour le retour est incorrecte
        redirect($this->pop_return_url());
    }

    /**
     * Retourne la selection format ActiveData utilisable par les requêtes
     * SQL pour filtrer les données en fonction des choix faits par l'utilisateur
     * dans la section de filtrage.
     */
    function selection() {
        $this->data ['filter_active'] = $this->session->userdata('filter_active');

        $selection = "";
        $year = $this->session->userdata('year');
        $date25 = date_m25ans($year);

        if ($this->session->userdata('filter_active')) {

            $filter_membre_actif = $this->session->userdata('filter_membre_actif');
            if ($filter_membre_actif) {
                $filter_membre_actif --;
                $selection .= "(actif = \"$filter_membre_actif\" )";
            }

            $filter_25 = $this->session->userdata('filter_25');

            if ($filter_25 == 1) {
                if ($selection) {
                    $selection .= " and ";
                }
                $selection .= "(mdaten >= \"$date25\" )";
            } else if ($filter_25 == 2) {
                if ($selection) {
                    $selection .= " and ";
                }
                $selection .= "(mdaten < \"$date25\" )";
            }

            $filter_categorie = $this->session->userdata('filter_categorie');
            if ($filter_categorie) {
                $categorie = $filter_categorie - 1;
                if ($selection) {
                    $selection .= " and ";
                }
                $selection .= "(categorie = \"$categorie\" )";
            }
        }

        if ($selection == "")
            $selection = array ();

        return $selection;
    }

    /**
     * Affiche les statistiques annuelles
     */
    public function stats() {
        if (func_num_args() == 0)
            $year = date('Y');
        else
            $year = func_get_arg(0);
        $this->data ['year'] = $year;
        $this->data ['year_selector'] = $this->gvv_model->getYearSelector("edate");
        $this->data ['controller'] = $this->controller;
        $this->data ['events_stats'] = $this->gvv_model->getStats($year);
        return load_last_view("event/statsView", $this->data, $this->unit_test);
    }

    /**
     * Affiche les niveaux de formation par pilotes
     */
    public function formation($discipline = "planeur") {
        if ($discipline == "planeur") {
            $types = array (
                    0,
                    1
            );
            $title_key = "gvv_events_title_training";
        } elseif ($discipline == "avion") {
            $types = array (
                    0,
                    2
            );
            $title_key = "gvv_events_title_training";
        } elseif ($discipline == "ulm") {
            $types = array (
                    0,
                    3
            );
            $title_key = "gvv_events_title_training";
        } elseif ($discipline == "fai") {
            $types = array (
                    4
            );
            $title_key = "gvv_events_title_FAI";
        }

        $this->load_filter($this->filter_variables);
        $this->push_return_url(current_url());
        $selection = $this->selection();

        $this->data ['controller'] = $this->controller;
        $this->data ['title_key'] = $title_key;
        $this->data ['type'] = "formation";
        $this->data ['type'] = $discipline;
        $this->data ['formation'] = $this->gvv_model->formation($types);
        return load_last_view("event/formationView", $this->data, $this->unit_test);
    }

    /**
     * Affiche les niveau FAI par pilotes
     */
    public function fai() {
        $this->load_filter($this->filter_variables);
        $this->push_return_url(current_url());
        $selection = $this->selection();

        $this->data ['controller'] = $this->controller;
        $this->data ['formation'] = $this->gvv_model->formation(array (
                4
        ));
        $this->data ['title_key'] = "gvv_events_title_FAI";
        $this->data ['type'] = "fai";
        return load_last_view("event/formationView", $this->data, $this->unit_test);
    }

    /*
     * Affiche les licences par an
     */
    public function licences($type = "") {
        $data = array ();

        $data ['controller'] = $this->controller;
        $data ['event_type_selector'] = $this->events_types_model->selector(array (
                'annual' => "1"
        ));

        if ($type == "") {
            foreach ( $data ['event_type_selector'] as $key => $value ) {
                $type = $key;
                break;
            }
        }
        $data ['type'] = $type;
        $data ['table'] = $this->gvv_model->licences_per_year($type);

        // var_dump($data['event_type_selector']); exit;
        load_last_view('event/licences', $data);
    }
    public function gen_dates() {
        $this->gvv_model->gen_dates();
    }

    /*
     * Export formation or FAI tables into CSV
     */
    public function csv($type) {
        $this->lang->load('events');
        $this->load->helper('csv');

        $this->load_filter($this->filter_variables);
        $selection = $this->selection();

        if ($type == "fai") {
            $title = $this->lang->line("gvv_events_title_FAI");
            $table = $this->gvv_model->formation(array (
                    4
            ), "csv");
        } else {
            $title = $this->lang->line("gvv_events_title_training");
            $table = $this->gvv_model->formation(array (
                    0,
                    1
            ), "csv");
        }
        csv_file($title, $table);
    }

    /*
     * Export formation or FAI tables into PDF
     */
    public function pdf($type) {
        $this->lang->load('events');

        $this->load_filter($this->filter_variables);
        $selection = $this->selection();

        if ($type == "fai") {
            $title = $this->lang->line("gvv_events_title_FAI");
            $table = $this->gvv_model->formation(array (
                    4
            ), "csv");
        } else {
            $title = $this->lang->line("gvv_events_title_training");
            $table = $this->gvv_model->formation(array (
                    0,
                    1
            ), "csv");
        }

        $this->load->library('Pdf');
        $pdf = new Pdf("L", "mm", "A3");

        $pdf->AddPage('L');
        $pdf->title($title, 1);
        $w = array (
                40
        );
        $align = array (
                'L'
        );
        $count = count($table [0]);
        for($i = 0; $i < $count; $i ++) {
            $w [] = 24;
            $align [] = 'R';
        }

        $pdf->table($w, 8, $align, $table);
        $pdf->Output();
    }

}

/* end of event.php */
