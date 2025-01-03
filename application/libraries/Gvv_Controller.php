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
 *
 * Contrôleur générique
 */

/**
 * Le controleur GVV parent de la plupart des controleurs fournit les services
 * suivant:
 * - fonctions page, edit, create
 * - verification des droits d'accés
 * - une vue page et une vue forumlaire
 * - un modèle de référence
 */
class Gvv_Controller extends CI_Controller {
    protected $controller;
    protected $model;
    protected $kid;
    protected $modification_level;
    protected $view_level;
    public $table_view;
    protected $form_view;
    protected $unit_test = FALSE;

    // régles de validation
    protected $fields = array();

    // Données transmises au formulaire
    protected $data = array();

    /**
     * Constructeur
     */
    function __construct() {
        $model_name = $this->model;

        parent::__construct();

        date_default_timezone_set("Europe/Paris");

        $this->load->library('DX_Auth');
        if (getenv('TEST') != '1') {
            // Checks to be done only when not controlled by PHPUnit
            $this->dx_auth->check_login();
        }

        $this->lang->load('gvv');

        $this->load->helper('date');
        $this->load->helper('validation');

        $this->table_view = $this->controller . "/tableView";
        $this->form_view = $this->controller . "/formView";
        $this->popup_view = $this->controller . "/popupView";

        // remplit les selecteurs depuis la base
        $this->load->model($model_name, 'gvv_model');
        $this->kid = $this->gvv_model->primary_key();

        if (! $this->session->userdata('year')) {
            $this->session->set_userdata('year', date('Y'));
        }
        if (! $this->session->userdata('per_page')) {
            $this->session->set_userdata('per_page', PER_PAGE);
        }
        if (! $this->session->userdata('licence_type')) {
            $this->session->set_userdata('licence_type', 0);
        }
    }

    /**
     * Génération des éléments statiques à passer au formulaire en cas de création,
     * modification ou réaffichage après erreur.
     * Sont statiques les parties qui ne changent
     * pas d'un élément sur l'autre.
     *
     * @param $action CREATION
     *            | MODIFICATION | VISUALISATION
     * @see constants.php
     */
    protected function form_static_element($action) {
        $this->data['action'] = $action;
        $this->data['fields'] = $this->fields;
        $this->data['controller'] = $this->controller;
        if ($action == "visualisation") {
            $this->data['readonly'] = "readonly";
        }
    }

    /**
     * Affiche le formulaire de création
     */
    function create() {
        if (func_num_args() > 0) {
            $no_view_loading = func_get_arg(0);
        }

        // Méthode basée sur les méta-données
        $table = $this->gvv_model->table();
        $this->data = $this->gvvmetadata->defaults_list($table);

        $this->form_static_element(CREATION);

        if (! isset($no_view_loading)) {
            return load_last_view($this->form_view, $this->data, $this->unit_test);
        } else {
            return "";
        }
    }

    /**
     * Supprime un élèment
     */
    function delete($id) {
        // détruit en base
        $this->pre_delete($id);
        $this->gvv_model->delete(array(
            $this->kid => $id
        ));

        // réaffiche la liste (serait sympa de réafficher la même page)
        redirect($this->controller . "/page");
    }

    /**
     * Affiche le formulaire de modification
     *
     * @param $id de
     *            l'élément à modifier
     * @param $load_view =
     *            TRUE si il faut afficher la vue
     * @param $action CREATION
     *            | MODIFICATION | VISUALISATION
     */
    function edit($id = "", $load_view = TRUE, $action = MODIFICATION) {
        // charge les données
        $this->data = $this->gvv_model->get_by_id($this->kid, $id);

        // TODO le faire aussi en production
        // Cela améliorera la qualité du code de signaler tous les tentatives d'accès à des données inconnues
        // Cependant il faut être sur que les exceptions sont traitées partout
        if (ENVIRONMENT == 'development') {
            if (count($this->data) < 1) {
                throw new Exception("$id not found");
            }
        }
        $this->form_static_element($action);

        $this->session->set_userdata('inital_id', $id);
        $this->data[$this->kid] = $id;
        if ($load_view) {
            return load_last_view($this->form_view, $this->data, $this->unit_test);
        }
    }

    /**
     * Vérifie que l'élément n'existe pas déjà en base de données
     *
     * @param unknown_type $id
     */
    // TODO a renomer en do_not_exist
    function check_uniq($id) {
        if ($id == "")
            return TRUE;

        if ($this->gvv_model->count(array(
            $this->kid => $id
        )) > 0) {
            $this->form_validation->set_message('check_uniq', $this->lang->line("check_uniq"));
            return FALSE;
        } else {
            return $id;
        }
    }

    /**
     * Validation des dates postérieures à la date de gel
     */
    function valid_activity_date($date) {

        // Special date validation under watir
        if ($this->config->item('watir')) {
            gvv_debug("watir date format = " . $date);
            if (preg_match('/(\d{2,2})(\d{2,2})(\d{4,4})/', $date, $matches)) {
                $day = $matches[1];
                $month = $matches[2];
                $year = $matches[3];
                // force the date
                $date = $day . '/' . $month . '/' . $year;
            }
        }

        // check that it is a regular date
        if (! $this->valid_date($date)) {
            gvv_debug("non valide date " . $date);
            return false;
        }

        $date_gel = $this->config->item('date_gel');
        $this->form_validation->set_message('valid_activity_date', "Date antérieure au " . $date_gel);

        if (preg_match('/(\d+)\/(\d+)\/(\d+)/', $date, $matches)) {
            $day = $matches[1];
            $month = $matches[2];
            $year = $matches[3];

            $time = mktime(0, 0, 0, $month, $day, $year);

            if (preg_match('/(\d+)\/(\d+)\/(\d+)/', $date_gel, $matches)) {
                $day = $matches[1];
                $month = $matches[2];
                $year = $matches[3];
                $freeze_time = mktime(0, 0, 0, $month, $day, $year);
                return $time > $freeze_time;
            }
        } else {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Validation de la date
     *
     * Tout le boulot est fait dans le helper, mais je n'arrive pas à réferencer autre chose que des
     * fonctions locales dans les règles de validation.
     *
     * @param string $date
     * @return boolean
     */
    function valid_date($date) {
        if ($date == '')
            return '';
        $this->form_validation->set_message('valid_date', $this->lang->line("valid_activity_date"));

        if (preg_match('/(\d+)\/(\d+)\/(\d+)/', $date, $matches)) {
            $day = $matches[1];
            $month = $matches[2];
            $year = $matches[3];
            // echo "day=$day, month=$month, year=$year" . br();
            if (! checkdate($month, $day, $year)) {
                return FALSE;
            }
        } else {
            return FALSE;
        }

        if ($res = mysql_date($date)) {
            // do not return a modified date or the field will not be repopulated correctly
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Validation des heures
     *
     * Tout le boulot est fait dans le helper, mais je n'arrive pas à réferencer autre chose que des
     * fonctions locales dans les règles de validation.
     *
     * @param unknown_type $time
     * @return boolean
     */
    function valid_time($time) {
        if ($res = mysql_time($time)) {
            return $res;
        } else {
            $this->form_validation->set_message('valid_time', $this->lang->line("valid_minute_time"));
            return FALSE;
        }
    }

    /**
     *
     * Validation des temps en minute
     *
     * @param unknown_type $time
     */
    function valid_minute_time($time) {
        // Todo: the error messages should be localized
        $res = mysql_minutes($time);
        if ($res < 0) {
            $this->form_validation->set_message('valid_minute_time', $this->lang->line("valid_minute_time"));
            return FALSE;
        }
        if (!$res) {
            $this->form_validation->set_message('valid_minute_time', $this->lang->line("valid_minute_time"));
            return FALSE;
        }
        return $res;
    }

    /**
     *
     * Validation des requêtes sql
     *
     * @param unknown_type $sql
     */
    function safe_sql($sql) {
        if (preg_match('/drop/', $sql) || preg_match('/delete/', $sql)) {
            $this->form_validation->set_message('safe_sql', $this->lang->line("safe_sql"));
            gvv_debug("safe_sql unsafe $sql");
            return FALSE;
        }

        if (preg_match('/update/', $sql)) {
            $this->form_validation->set_message('safe_sql', $this->lang->line("safe_sql_update"));
            gvv_debug("safe_sql unsafe $sql");
            return FALSE;
        }

        if (! preg_match('/select/', $sql)) {
            $this->form_validation->set_message('safe_sql', $this->lang->line("safe_sql_select"));
            gvv_debug("safe_sql unsafe $sql");
            return FALSE;
        }
        return $sql;
    }

    /**
     * Validation des checkboxes
     *
     * @param $value checkbox
     *            field
     * @return 0 ou 1
     */
    function valid_checkbox($value) {
        if ($value == '')
            return 0;
        if ($value != 0)
            return 1;
        return 0;
    }

    /**
     * Destination après validation sans erreurs
     */
    function validationOkPage($processed_data, $button) {
        if ($button == $this->lang->line("gvv_button_create_and_continue")) {
            // Display the form again
            redirect($this->controller . "/create");
            return;
        }

        $this->pop_return_url();
    }

    /**
     * Transforme les données brutes en base en données affichables
     * Default implementation returns the data attribute
     *
     * @param $action CREATION
     *            | MODIFICATION | VISUALISATION
     */
    function form2database($action = '') {
        $processed_data = array();
        if (isset($this->rules)) {
            // Méthode basée sur les méta-données
            $table = $this->gvv_model->table();
            $fields_list = $this->gvvmetadata->fields_list($table);
            foreach ($fields_list as $field) {
                $processed_data[$field] = $this->gvvmetadata->post2database($table, $field, $this->input->post($field));
            }
        } else {
            foreach ($this->fields as $field => $value) {
                $processed_data[$field] = $this->input->post($field);
            }
        }
        return $processed_data;
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
        gvv_debug($this->controller . " creation " . var_export($data, true));
    }

    /**
     * Hook activé avant la destruction
     *
     * @param $id clé
     *            de l'élément à détruire
     */
    function pre_delete($id) {
        gvv_debug($this->controller . " delete $id");
    }

    /**
     * Hook activé après la mise à jour
     *
     * @param $data enregistrement
     *            modifié
     */
    function post_update($data = array()) {
        gvv_debug($this->controller . " post modification " . var_export($data, true));
    }

    /**
     * Hook activé avant la mise à jour
     */
    function pre_update($id, $data = array()) {
        gvv_debug($this->controller . " pre modification $id " . var_export($data, true));
    }

    /**
     * Validation du formulaire d'édition
     *
     * @param $action CREATION
     *            | MODIFICATION | VISUALISATION
     */
    public function formValidation($action, $return_on_success = false) {
        // $button = $_POST['button'];
        $button = $this->input->post('button');
        $numlign = $this->input->post('numlign');

        if ($button == $this->lang->line("gvv_button_show_list")) {
            $this->page();
            return;
        } else if ($button == $this->lang->line("gvv_button_cancel")) {
            $this->pop_return_url();
            return;
        } elseif ($button == $this->lang->line("gvv_button_delete")) {
            $id = $this->input->post($this->kid);
            $this->delete($id);
            return;
        }

        // Validates the form entries
        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
        // definit les règles de validation et charge les données
        // On ne doit avoir que les champs de la base dans data

        if (isset($this->rules)) {
            // echo "Méthode basée sur les méta-données" . br(); exit;

            $table = $this->gvv_model->table();
            $fields_list = $this->gvvmetadata->fields_list($table);
            foreach ($fields_list as $field) {
                $this->data[$field] = $this->input->post($field);
            }

            $this->gvvmetadata->set_rules($table, $fields_list, $this->rules, $action);
        } else {
            // Ancienne méthode
            // TODO: à supprimer après validation
            // echo "Ancienne méthode de validation à migrer" . br();
            // $data = array();
            // foreach ($this->fields as $field => $value) {
            //     $rules = array_key_exists('rules', $value) ? $value['rules'] : '';
            //     $label = array_key_exists('label', $value) ? $value['label'] : $field;

            //     if ($action == CREATION && ($field == $this->kid)) {
            //         // On vérifie également que l'enregistrement n'existe pas déja
            //         $rules .= "|callback_check_uniq";
            //     }
            //     $this->form_validation->set_rules($field, $label, $rules);
            //     $this->data[$field] = $this->input->post($field);
            // }
        }

        if ($this->form_validation->run($this)) {
            try {
                // get the processed data. It must not be done before because all the
                // processing is done by the run method.
                $processed_data = $this->form2database($action);
                if ($action == CREATION) {
                    # var_dump($processed_data); exit();
                    $id = $this->gvv_model->create($processed_data);
                    // only replace autoincremented id
                    if ($id) {
                        gvv_debug("autoincremented id=$id, key=" . $this->kid);
                        $processed_data[$this->kid] = $id;
                        gvv_debug("processed_data = " . var_export($processed_data, true));
                    } else {
                        $msg = "No ID returned by create()";
                        $this->data['message'] = '<div class="text-danger">' . $msg . '</div>';
                    }
                    $this->post_create($processed_data);
                    if ($button != $this->lang->line("gvv_button_create")) {
                        $image = $this->gvv_model->image($id);
                        $msg = $image . ' ' . $this->lang->line("gvv_succesful_creation");
                        $this->session->set_flashdata('popup', $msg);
                    }
                } elseif ($action == MODIFICATION) {

                    $initial_id = $this->session->userdata('inital_id');
                    $this->pre_update($this->kid, $processed_data);
                    $this->gvv_model->update($this->kid, $processed_data, $initial_id);
                    $this->post_update($processed_data);
                    if ($return_on_success)
                        return;
                    $this->pop_return_url();

                    return;
                }
                if ($return_on_success)
                    return;

                if ($button == $this->lang->line('gvv_button_logs_submitbutton')) {

                    $this->data['vol_ok'] = $msg;
                    $this->data['numligne'] = $numlign;
                    $this->load->view($this->popup_view, $this->data);
                } else {
                    $this->validationOkPage($processed_data, $button);
                }

                return;
            } catch (Exception $e) {
                $msg = $e->getMessage();
                $data = array();
                $data['title'] = 'Erreur';
                $data['text'] = $msg;
                // load_last_view('message', $data);
                $this->data['message'] = '<div class="text-danger">' . $msg . '</div>';
            }
        }

        // Display the form again
        if ($button == $this->lang->line("gvv_button_logs_submitbutton"))
            load_last_view($this->popup_view, $this->data);
        else {
            $this->form_static_element($action);
            // $this->load->view($this->form_view, $this->data);
            load_last_view($this->form_view, $this->data);
        }
    }

    /**
     * Affiche une page d'éléments
     *
     * @param $premier élément
     *            à afficher
     * @param
     *            message message à afficher
     */
    function page($premier = 0, $message = '', $selection = array()) {
        $this->push_return_url("GVV controller page");
        // retourne le tableau de valeurs mais pas les boutons edit, delete
        $this->data['select_result'] = $this->gvv_model->select_page(PER_PAGE, $premier, $selection);

        $this->data['kid'] = $this->kid;
        $this->data['controller'] = $this->controller;
        $this->data['count'] = $this->gvv_model->count();
        $this->data['premier'] = $premier;
        $this->data['message'] = $message;
        $this->data['has_modification_rights'] = (! isset($this->modification_level) || $this->dx_auth->is_role($this->modification_level, true, true));

        return load_last_view($this->table_view, $this->data, $this->unit_test);
    }

    /**
     * Defaut
     */
    function index() {
        $this->page();
    }
    function push_return_url($context) {
        $this->session->set_userdata('back_url', current_url());
        gvv_debug("push back_url  $context: " . current_url());
    }
    function pop_return_url() {
        if ($this->session->userdata('back_url')) {
            // retour d'ou l'on vient
            $url = $this->session->userdata('back_url');
            gvv_debug("pop back_url $url");
            redirect($url);
            return;
        } else {
            // par défaut ou retourne à la vue table
            $url = $this->controller . "/page";
            gvv_debug("pop default back_url $url");
            redirect($url);
        }
    }

    /**
     * Enable ou disable a set of filter variables
     *
     * @param
     *            filter_variables list of variables names
     */
    function active_filter($filter_variables) {
        $button = $this->input->post('button');

        // TODO remove 'Filtrer' when initernational support is completed
        if (($button == "Filtrer") || ($button == $this->lang->line("gvv_str_select"))) {

            // Enable filtering
            foreach ($filter_variables as $field) {
                $session[$field] = $this->input->post($field);
                // echo "$field => " . $this->data[$field] . br();
            }

            $session['filter_active'] = 1;
            $this->session->set_userdata($session);
            // var_dump($session);
        } else {
            // Disable filtering
            foreach ($filter_variables as $field) {
                $this->session->unset_userdata($field);
            }
        }
    }

    /**
     * Load a set of filter variables from the session
     *
     * @param
     *            filter_variables list of variables names
     */
    function load_filter($filter_variables) {
        foreach ($filter_variables as $field) {
            $this->data[$field] = $this->session->userdata($field);
            // echo "$field => " . $this->data[$field] . br();
        }
    }

    /**
     *
     * Selection d'une nouvelle annnée
     *
     * @param unknown_type $year
     */
    function new_year($year) {
        $this->session->set_userdata('year', $year);
        $this->session->set_userdata('balance_date', "31/12/$year");
        $this->pop_return_url();
    }

    /**
     * Test d'affichage du contrôleur
     */
    function test($format = "html") {
        $this->unit_test = TRUE;

        $this->load->library('unit_test');
        $res = $this->create();
        $this->unit->run(($res == ""), FALSE, $this->controller . "/create", "non vide");
        $this->unit->run(preg_match("/PHP Error/", $res), 0, $this->controller . "/create_no_error", "pas d'erreurs PHP");

        $res = $this->edit();
        $this->unit->run(($res == ""), FALSE, $this->controller . "/edit", "non vide");
        $this->unit->run(preg_match("/PHP Error/", $res), 0, $this->controller . "/edit_no_error", "pas d'erreurs PHP");

        $res = $this->page();
        $this->unit->run(($res == ""), FALSE, $this->controller . "/page", "non vide");
        $this->unit->run(preg_match("/PHP Error/", $res), 0, $this->controller . "/page_no_error", "pas d'erreurs PHP");
    }

    /**
     * Définit le nombre d'éléments sur une page d'affichage
     *
     * @param unknown_type $per_page
     */
    function set_per_page($per_page) {
        // echo "set_per_page $per_page" . br();
        $session['per_page'] = $per_page;
        $this->session->set_userdata($session);
        $this->pop_return_url();
    }

    /**
     * Tests unitaire pour le model
     */
    function test_model($primary_key) {
        $model = $this->model;
        $this->unit->header("Test model $model");
        $this->load->model($model);

        // test of initial conditions
        $count = $this->$model->count();
        $this->unit->run($count >= 0, true, "Nombre d'éléments", "count=$count");

        // Crée des éléments

        $nb = 3;
        $field_number = 0;
        for ($i = 0; $i < $nb; $i++) {
            $elt = $this->test_element($i);
            $id = $this->test_element_id($i);
            $this->unit->run($this->check_uniq($id), true, "elt $id n'existe pas");
            $res = $this->$model->create($elt);
            $this->unit->run($this->check_uniq($id), false, "elt $id existe");
            $field_number = count($elt);
            $this->unit->run($res, true, "creation $id");
            $expected_count = $count + $i + 1;
            $this->unit->run($this->$model->count() == $expected_count, true, "Nombre d'élement==$expected_count");
        }

        // Nominal tests on created data
        // -----------------------------
        $key = $this->$model->primary_key();
        $this->unit->run($key, $primary_key, "Primary key");

        // Lit le premier
        $elt_initial = $this->$model->get_first();
        $this->unit->run(count($elt_initial), $field_number, "all fields");

        // Modifie les valeurs
        $res = $elt_initial;
        $id = $res[$key];
        $this->unit->run($this->$model->image($id), $id, "image == $id");

        $this->test_change($res);
        $this->$model->update($key, $res);

        // Verifie les modifs
        $res = $this->$model->get_by_id($key, $id);
        $changes = array_diff($elt_initial, $res);
        $this->unit->run(count($changes) != 0, true, "changes");

        // Remet en place
        $this->$model->update($key, $elt_initial);
        $res = $this->$model->get_by_id($key, $id);
        $this->unit->run(count(array_diff($elt_initial, $res)), 0, "no changes after restore");

        // Error tests on created data
        // ---------------------------

        // Attempt to duplicate an entry
        $this->unit->run($this->$model->create($elt), false, "duplicated entries detected");
        // $this->db->display_error('my message');

        // Reset database to its initial state
        for ($i = 0; $i < $nb; $i++) {
            $id = $this->test_element_id($i);
            $this->delete($id);
            $expected_count--;
            $this->unit->run($this->$model->count() == $expected_count, true, "Avion number==$expected_count");
        }

        // Selectors
        $select_with_all = $this->$model->selector_with_all();
        $select_with_null = $this->$model->selector_with_null();
        $this->unit->run(count(array_diff($select_with_all, $select_with_null)), 1, "different selector");
    }

    /**
     * End of unit tests operations
     *
     * @param string $format
     */
    public function tests_results($format = "html") {
        if ($format == "xml") {
            $this->unit->XML_result("build/logs/test_$controller.xml", "Test $controller");
        } else {
            echo $this->unit->report();
        }
        $this->unit->save_coverage();
    }
}
