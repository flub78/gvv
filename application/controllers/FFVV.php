<?php

/**
 *    Project {$PROJECT}
 *    Copyright (C) 2015 {$AUTHOR}
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
 * @filesource Metadata.php
 * @package controllers
 * Development tools controler
 *
 * It is just a controller for experiments and to display information useful
 * during development. Could be disabled in production.
 *
 */
defined('BASEPATH') or exit('No direct script access allowed');

// First, include Requests
include(APPPATH . '/third_party/Requests.php');

function addstr(&$str, $hash, $field, $label) {
    if (isset($hash[$field])) {
        $str .= $label . ':' . nbs() . $hash[$field] . br();
    } else {
        $str .= $label . ':' . nbs() . 'undefined' . br();
    }
}

// Next, make sure Requests can load internal classes
Requests::register_autoloader();

/**
 * Development controller
 * @author frederic
 *
 */
class FFVV extends CI_Controller {

    var $controller = 'FFVV';

    function __construct() {
        parent::__construct();

        $this->load->library('DX_Auth');
        if (getenv('TEST') != '1') {
            // Checks to be done only when not controlled by PHPUnit
            $this->dx_auth->check_login();
        }

        $this->load->config('club');
        $this->load->helper('wsse');
        $this->load->helper('form_elements');

        $this->load->model('membres_model');
        $this->load->model('achats_model');
        $this->load->model('comptes_model');

        // To have full var_dump
        ini_set('xdebug.var_display_max_depth', 5);
        ini_set('xdebug.var_display_max_children', 256);
        ini_set('xdebug.var_display_max_data', 1024);
    }

    /**
     * Get licences information from HEVA
     */
    public function licences() {
        $request = heva_request("/persons");

        if (!$request->success) {
            echo "licences status_code = " . $request->status_code . br();
            echo "success = " . $request->success . br();
            return;
        }

        $result = json_decode($request->body, true);

        /**
         * array (size=79)
  0 =>
    array (size=17)
      'civility' => string 'M.' (length=2)
      'first_name' => string 'Jérome' (length=7)
      'last_name' => string 'DELABRE' (length=7)
      'licence_number' => string '28256' (length=5)
      'date_of_birth' => string '1966-10-24' (length=10)
      'comment' => string '' (length=0)
      'city_of_birth' => string 'ABBEVILLE' (length=9)
      'country_of_birth' => string 'FR' (length=2)
      'insee_category' => string 'Chef d'entreprise/commerçant' (length=29)
      'nationality' => string 'Français(e)' (length=12)
      'is_commercial_use' => boolean false
      'card_sent_at' => null
      'address' =>
        array (size=9)
          'delivery_point' => string '' (length=0)
          'localisation' => string '' (length=0)
          'address' => string '6 rue des Moines' (length=16)
          'distribution' => string '' (length=0)
          'postal_code' => string '80100' (length=5)
          'cedex' => string '' (length=0)
          'city' => string 'ABBEVILLE' (length=9)
          'country' => string 'FR' (length=2)
          'receiver' => null
      'email' =>
        array (size=2)
          'value' => string 'j.delabre@delabre.fr' (length=20)
          'is_private' => boolean false
      'mobile' =>
        array (size=2)
          'value' => string '0607279764' (length=10)
          'is_private' => boolean true
      'phone' =>
        array (size=2)
          'value' => string '0322249907' (length=10)
          'is_private' => boolean true
      'players' =>
        array (size=1)
          0 =>
            array (size=7)
              ...

         */

        $table = array();
        foreach ($result as $row) {
            if (isset($row['first_name']) && isset($row['last_name'])) {
                $row['image'] = $row['first_name'] . ' ' . $row['last_name'];
            }
            $row['linked'] = false;
            $row['info'] = anchor(controller_url($this->controller) . "/edit/" . $row['licence_number'], 'Info');
            $row['sales'] = anchor(controller_url($this->controller) . "/sales_pilote/" . $row['licence_number'], 'Ventes');
            $row['qualifs'] = anchor(controller_url($this->controller) . "/qualif_pilote/" . $row['licence_number'], 'Qualifications');

            // cherche les membres avec ce numéro de licence
            $licence_number = $row['licence_number'];
            $this->membres_model->default_id();
            $membre = $this->membres_model->get_first(array('licfed' => $licence_number));
            if ($membre) {
                $row['linked'] = true;
                $row['linked'] = anchor(controller_url('membre') . "/edit/" . $membre['mlogin'], $row['image']);
            } else {
                // Pas de membre associé, proposer création ou association
                $create = anchor(controller_url('membre') . "/heva_create/" . $row['licence_number'], 'Créer');
                $associe = anchor(controller_url('membre') . "/associe_licence/" . $row['licence_number']
                    . '/' . $row['image'], 'Associe', array("class" => "btn btn-primary"));
                $row['linked'] = $associe;
            }
            // var_dump($membre);

            $actions = array(
                'Info' => $row['info'],
                'Ventes' => $row['sales']
            );

            $row['actions'] = form_dropdown('target_level', $actions);
            $table[] = $row;
        }

        $attrs['fields'] = array(
            'civility',
            'first_name',
            'last_name',
            'licence_number',
            'date_of_birth',
            'comment',
            'linked',
            'sales',
            'qualifs'
        );
        $attrs['headers'] = array(
            'Civilité',
            'Prénom',
            'Nom',
            'N° licence',
            'Date de naissance',
            'Commentaire',
            'Membre',
            'Licences',
            'Qualifs'
        );
        $attrs['controller'] = $this->controller;
        $data['controller'] = $this->controller;

        $table = flatten($table, $attrs);
        // var_dump($table);

        $data['data_table'] = $table; // datatable('heva_licences', $table, $attrs);
        $data['table_title'] = 'Licenciés HEVA';

        //$this->load->view('default_table', $data);
        return load_last_view('default_table', $data, false);
    }

    /**
     * Retourne les informations sur l'association
     * @param string $id
     */
    public function association() {
        $id = $this->config->item('ffvv_id');
        $request = heva_request("/associations/$id");

        if (!$request->success) {
            echo "status_code = " . $request->status_code . br();
            echo "success = " . $request->success . br();
            return;
        }

        $result = json_decode($request->body, true);

        // var_dump($result);

        $txt = "Nom:" . nbs() . $result['name'] . br();
        addstr($txt, $result, 'code', 'Code');
        addstr($txt, $result, 'website', 'Site WEB');
        addstr($txt, $result, 'type', 'Type');

        $txt .= br() . 'Banque' . br();
        addstr($txt, $result, 'transfer_info', 'Coordonnées bancaire');

        $txt .= br() . 'Adresse' . br();
        $txt .= $result['address']['delivery_point'] . br();
        $txt .= $result['address']['localisation'] . br();
        $txt .= $result['address']['postal_code'] . nbs() . $result['address']['city'] . br();

        $txt .= br();
        $txt .= br() . 'Courriel:' . $result['email']['value'] . br();
        $txt .= br() . 'Mobile:' . $result['mobile']['value'] . br();

        $data['title'] = "Information fédérale sur l'association";
        $data['text'] = $txt;
        return load_last_view('message', $data, false);
    }

    /**
     * Formatte une ligne de vente
     * 
     * @param unknown $row
     */
    private function format_sale_row($row, $attrs = array()) {
        $row['assoc_name'] = isset($row['association']['name']) ? $row['association']['name'] : "";
        $row['reference'] = isset($row['payment']['reference']) ? $row['payment']['reference'] : "";
        $row['collecting_assoc'] = isset($row['collecting_association']['name']) ? $row['collecting_association']['name'] : "";
        $row['year'] = isset($row['season']['short_name']) ? $row['season']['short_name'] : "";
        if (isset($attrs['to_merge'])) {
            $row = array_merge($row, $attrs['to_merge']);
        }
        if (isset($attrs['year'])) {
            if ($row['year'] != $attrs['year']) {
                $row = array();
            }
        }
        if (isset($attrs['ref_only'])) {
            if (!isset($row['reference']) || $row['reference'] == "") {
                $row = array();
            }
        }

        return $row;
    }
    /**
     * transforme résultat en un tableau a deux dimensions
     * @param unknown $result
     */
    private function format_sales($result) {
        /**
         * array (size=30)
         0 =>
         array (size=8)
         'created_at' => string '2016-01-09 14:13:15' (length=19)
         'amount' => string '0.00' (length=4)
         'association' =>
         array (size=3)
         'name' => string 'ABBEVILLE' (length=9)
         'code' => string '228002' (length=6)
         'type' => string 'Club' (length=4)
         'season' =>
         array (size=1)
         'short_name' => string '2016' (length=4)
         'payment' => null
         'collecting_association' =>
         array (size=3)
         'name' => string 'FFVV' (length=4)
         'code' => string '100000' (length=6)
         'type' => string 'Federation' (length=10)
         'type' => string 'Affiliate' (length=9)
         'total_amount' => string '0.00' (length=4)

         28 =>
         array (size=8)
         'created_at' => string '2016-01-07 15:08:53' (length=19)
         'amount' => string '10.00' (length=5)
         'association' =>
         array (size=3)
         'name' => string 'ABBEVILLE' (length=9)
         'code' => string '228002' (length=6)
         'type' => string 'Club' (length=4)
         'season' =>
         array (size=1)
         'short_name' => string '2016' (length=4)
         'payment' =>
         array (size=7)
         'method' => string 'pr' (length=2)
         'reference' => string '20160107031' (length=11)
         'recepted_at' => null
         'amount' => string '10.00' (length=5)
         'cheque_number' => null
         'bank' => null
         'tag' => null
         'collecting_association' =>
         array (size=3)
         'name' => string 'FFVV' (length=4)
         'code' => string '100000' (length=6)
         'type' => string 'Federation' (length=10)
         'type' => string 'BadgeSale' (length=9)
         'total_amount' => string '10.00' (length=5)
         */

        $table = array();
        foreach ($result as $row) {
            // var_dump($row);
            $table[] = $this->format_sale_row($row);
        }
        return $table;
    }

    /**
     * Liste les ventes l'association
     * @param string $id
     */
    public function sales() {
        $id = $this->config->item('ffvv_id');
        $request = heva_request("/associations/$id/sales");

        if (!$request->success) {
            echo "status_code = " . $request->status_code . br();
            echo "success = " . $request->success . br();
            return;
        }

        $result = json_decode($request->body, true);

        $table = $this->format_sales($result);

        $attrs['fields'] = array('created_at', 'year', 'total_amount', 'assoc_name', 'collecting_assoc', 'type', 'reference');
        $attrs['headers'] = array('Créé le', 'Année', 'Montant', 'Association', 'Fédération', 'Type', 'Référence');
        $data['controller'] = $this->controller;
        $data['data_table'] = flatten($table, $attrs);
        $data['table_title'] = 'Facturation HEVA';

        return load_last_view('default_table', $data, false);
    }

    /**
     * Fetch sales and licences
     * 
     * as the HEVA API is suboptimal, the result is cached in the session when it is a global research
     * 
     * @param string $id
     */
    public function fetch_sales($licence_number = 0, $year = 0) {
        $id = $this->config->item('ffvv_id');

        if ($licence_number != 0) {
            # cherche les ventes pour un pilote
            // echo "cherche les ventes pour un pilote" . br();
            $pilot_list = array($licence_number);
        } else {

            if ($this->session->userdata('sales_ffvv')) {
                return $this->session->userdata('sales_ffvv');
            }
            # cherche les ventes pour tous les pilotes
            $request = heva_request("/persons");

            if (!$request->success) {
                echo "status_code = " . $request->status_code . br();
                echo "success = " . $request->success . br();
                return;
            }

            $result = json_decode($request->body, true);
            $pilot_list = array();
            foreach ($result as $row) {
                $pilot_list[] = $row['licence_number'];
            }
        }


        $sales = array();
        foreach ($pilot_list as $licence_number) {
            // fetch les informations pilotes
            $request = heva_request("/persons/$licence_number");
            if (!$request->success) {
                echo "status_code = " . $request->status_code . br();
                echo "success = " . $request->success . br();
                return;
            }
            $info_pilot = json_decode($request->body, true);
            $first_name = isset($info_pilot['first_name']) ? $info_pilot['first_name'] : "";
            $last_name = isset($info_pilot['last_name']) ? $info_pilot['last_name'] : "";
            $pilot_name = $first_name . ' ' . $last_name;

            $status = '';
            $membre = $this->membres_model->get_first(array('licfed' => $licence_number));

            if ($membre) {
                $mlogin = $membre['mlogin'];
                $compte = $membre['compte'];
            } else {
                $status = 'Licencié inconnu';
                $mlogin = "";
                $compte = 0;
            }

            // fetch les sales
            $request = heva_request("/persons/$licence_number/sales");
            if (!$request->success) {
                echo "status_code = " . $request->status_code . br();
                echo "success = " . $request->success . br();
                return;
            }

            $result = json_decode($request->body, true);

            foreach ($result as $row) {
                // var_dump($row);
                $attrs = array('ref_only' => true);
                if ($year) {
                    $attrs['year'] = $year;
                }
                $attrs['to_merge'] = array(
                    'status' => $status,
                    'pilot_name' => $pilot_name,
                    'mlogin' => $mlogin,
                    'compte' => $compte
                );
                $whole_row = $this->format_sale_row($row, $attrs);
                if ($whole_row) {
                    $sales[] = $whole_row;
                    // var_dump($whole_row);
                }
            }
        }
        $this->session->set_userdata('sales_ffvv', $sales);
        return $sales;
    }

    /**
     * Facture les licences et les badges aux pilotes
     * @param string $id
     */
    public function facturation($licence_number = 0, $year = 0) {

        // $this->push_return_url("Facturation licences");
        $this->session->set_userdata('back_url', current_url());
        gvv_debug("push back_url facturation licences: " . current_url());

        $sales = $this->fetch_sales($licence_number, $year);
        // var_dump($sales);
        $i = 0;
        foreach ($sales as $row) {
            if ($row['status'] == '') {
                // pas d'erreur détectée

                // est-ce que la référence à déja été facturé ?
                $num_cheque = $row['reference'];
                if (isset($row['compte']) && $row['compte']) {
                    $compte_info = $this->comptes_model->get_by_id('id', $row['compte']);
                    $payeur = $compte_info['pilote'];
                } else {
                    $payeur = $row['mlogin'];
                }

                $facture = $this->achats_model->get_first(array('num_cheque' => $num_cheque, 'pilote' => $payeur));

                if ($facture) {
                    $sales[$i]['status'] = "facturé";
                } else {
                    // var_dump($row);
                    // build information to pre-fill a billing form
                    $date = substr($row['created_at'], 0, 10);
                    $amount = $row['total_amount'];
                    $mlogin = $row['mlogin'];

                    $description = $row['type'];
                    if ($description == "Player") {
                        $description = "Licence FFVV " . $row['year'];
                    } elseif ($description == "BadgeSale") {
                        $description = "Badge FAI FFVV";
                    }

                    if ($row['compte']) {
                        // pilote à facturer sur un autre compte
                        $compte_info = $this->comptes_model->get_by_id('id', $row['compte']);
                        $name = $this->membres_model->image($mlogin);
                        $mlogin = $compte_info['pilote'];
                        $description .= ' de ' . $name;
                    }

                    $url = controller_url('achats') . '/create'
                        . '?amount=' . urlencode($amount)
                        . '&date=' . urlencode($date)
                        . '&pilot=' . urlencode($mlogin)
                        . '&description=' . urlencode($description)
                        . '&num_cheque=' . urlencode($num_cheque);

                    // var_dump($url);
                    $sales[$i]['status'] = anchor($url, "Facturation", array("class" => "btn btn-primary"));
                }
            }
            $i++;
        }

        $attrs['fields'] = array('created_at', 'year', 'total_amount', 'pilot_name', 'status', 'type', 'reference');
        $attrs['headers'] = array('Créé le', 'Année', 'Montant', 'Membre', 'Status', 'Type', 'Référence');
        $data['controller'] = $this->controller;
        $data['data_table'] = flatten($sales, $attrs);
        $data['table_title'] = 'Facturation HEVA';

        return load_last_view('default_table', $data, false);
    }

    /**
     * Retourne les informations sur les licences
     * @param string $id
     */
    public function players() {
        $id = $this->config->item('ffvv_id');
        $request = heva_request("/associations/$id/players", array('page_size' => 50000));

        if (!$request->success) {
            echo "status_code = " . $request->status_code . br();
            echo "success = " . $request->success . br();
            return;
        }

        $result = json_decode($request->body, true);

        /**
         */

        $table = array();
        foreach ($result as $row) {
            $row['first_name'] = isset($row['person']['first_name']) ? $row['person']['first_name'] : "";
            $row['last_name'] = isset($row['person']['last_name']) ? $row['person']['last_name'] : "";
            $row['assoc'] = isset($row['association']['name']) ? $row['association']['name'] : "";
            $row['year'] = isset($row['season']['short_name']) ? $row['season']['short_name'] : "";
            $row['lic_fed'] = isset($row['person']['licence_number']) ? $row['person']['licence_number'] : "";

            $row['type_name'] = isset($row['type']['name']) ? $row['type']['name'] : "";
            // var_dump($row);
            $table[] = $row;
        }
        $attrs['fields'] = array(
            'licence_number',
            'starting_at',
            'ending_at',
            'first_name',
            'last_name',
            'lic_fed',
            'assoc',
            'year',
            'type_name'
        );
        $attrs['headers'] = array(
            'N° licence',
            'Début',
            'Fin',
            'Prénom',
            'Nom',
            'N° licencié',
            'Association',
            'Année',
            'Type'
        );
        $data['controller'] = $this->controller;
        $data['data_table'] = flatten($table, $attrs);
        $data['table_title'] = 'Licences HEVA';

        return load_last_view('default_table', $data, false);
    }

    /**
     * Retourne les informations sur l'association
     * @param string $id
     */
    public function info_pilote($pilot = "1029") {
        $request = heva_request("/persons/$pilot");

        if (!$request->success) {
            echo "status_code = " . $request->status_code . br();
            echo "success = " . $request->success . br();
            return;
        }

        $result = json_decode($request->body, true);

        // var_dump($result); ;

    }

    public function edit($pilot) {
        $this->info_pilote($pilot);
    }

    /**
     * Retourne les informations sur l'association
     * @param string $id
     */
    public function sales_pilote($pilot = "1029") {
        $id = $this->config->item('ffvv_id');

        // fetch les informations pilotes
        $request = heva_request("/persons/$pilot");
        if (!$request->success) {
            echo "status_code = " . $request->status_code . br();
            echo "success = " . $request->success . br();
            return;
        }
        $info_pilot = json_decode($request->body, true);
        $first_name = isset($info_pilot['first_name']) ? $info_pilot['first_name'] : "";
        $last_name = isset($info_pilot['last_name']) ? $info_pilot['last_name'] : "";

        $request = heva_request("/persons/$pilot/sales");
        if (!$request->success) {
            echo "status_code = " . $request->status_code . br();
            echo "success = " . $request->success . br();
            return;
        }

        $result = json_decode($request->body, true);
        // var_dump($result);

        $table = $this->format_sales($result);

        $attrs['fields'] = array('created_at', 'year', 'total_amount', 'assoc_name', 'collecting_assoc', 'type', 'reference');
        $attrs['headers'] = array('Créé le', 'Année', 'Montant', 'Association', 'Fédération', 'Type', 'Référence');
        $data['controller'] = $this->controller;
        $data['data_table'] = flatten($table, $attrs);
        $data['table_title'] = 'Licences HEVA du pilote ' . $first_name . ' ' . $last_name;

        return load_last_view('default_table', $data, false);
    }

    /**
     * Retourne les informations sur l'association
     * @param string $id
     */
    public function qualif_types() {
        $id = $this->config->item('ffvv_id');
        $request = heva_request("/qualification-types");

        if (!$request->success) {
            echo "status_code = " . $request->status_code . br();
            echo "success = " . $request->success . br();
            return;
        }

        $result = json_decode($request->body, true);

        $attrs['fields'] = array('id', 'category', 'name', 'is_date_required');
        $attrs['headers'] = array('Id', 'Categorie', 'Nom', 'Avec date');
        $data['controller'] = $this->controller;
        $data['data_table'] = flatten($result, $attrs);
        $data['table_title'] = 'Types de qualification HEVA';

        return load_last_view('default_table', $data, false);
    }

    /**
     * Retourne les informations sur l'association
     * @param string $id
     */
    public function qualif_pilote($pilot = "1029") {
        $id = $this->config->item('ffvv_id');

        // fetch les informations pilotes
        $request = heva_request("/persons/$pilot");
        if (!$request->success) {
            echo "status_code = " . $request->status_code . br();
            echo "success = " . $request->success . br();
            return;
        }
        $info_pilot = json_decode($request->body, true);
        $first_name = isset($info_pilot['first_name']) ? $info_pilot['first_name'] : "";
        $last_name = isset($info_pilot['last_name']) ? $info_pilot['last_name'] : "";

        // fetch les qualifs
        $request = heva_request("/persons/$pilot/qualifications");
        if (!$request->success) {
            echo "status_code = " . $request->status_code . br();
            echo "success = " . $request->success . br();
            return;
        }

        $result = json_decode($request->body, true);
        // var_dump($result);exit;

        $table = array();
        foreach ($result as $row) {
            $row['type_name'] = isset($row['type']['name']) ? $row['type']['name'] : "";

            $table[] = $row;
        }
        $attrs['fields'] = array('awarded_at', 'type_name');
        $attrs['headers'] = array('Date', 'Type');
        $data['controller'] = $this->controller;
        $data['data_table'] = flatten($table, $attrs);
        $data['table_title'] = 'Qualifications pour ' . $first_name . ' ' . $last_name;

        return load_last_view('default_table', $data, false);
    }

    /**
     * Fonction de test
     */
    function echo_params() {
        echo "echo_params" . br();
        echo "\$_SERVER";
        var_dump($_SERVER);
        echo "\$_GET";
        var_dump($_GET);
        echo "\$_POST";
        var_dump($_POST);
    }
}
