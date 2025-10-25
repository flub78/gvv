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
 * Gestion des présences
 *
 * @filesource presences.php
 * @package controllers
 *
 * La gestion des présences est réalisée à l'aide d'un calendrier Google Agenda
 *
 * https://developers.google.com/google-apps/calendar/
 */
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Gestion des présences dans un Agenda Google
 *
 * Basé sur http://localhost/google-api-php-client/examples/calendar/simple.php
 */
class Presences extends CI_Controller {
    protected $controller = "presencess";
    protected $unit_test = FALSE;

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();

        // Check if user is logged in or not
        if (! $this->dx_auth->is_logged_in()) {
            redirect("auth/login");
        }

        // Authorization: Code-based (v2.0) - only for migrated users
        // For non-migrated users, use legacy check_uri_permissions()
        $this->load->model('authorization_model');
        $user_id = $this->dx_auth->get_user_id();
        $migration = $this->authorization_model->get_migration_status($user_id);

        if ($migration && $migration['use_new_system'] == 1) {
            // New system - require ca role
            $this->dx_auth->require_roles(['ca']);
        } else {
            // Legacy system - use URI permissions
            $this->dx_auth->check_uri_permissions();
        }

        $this->load->library('GoogleCal');
        $this->load->helper('validation_helper');
        $this->load->model('membres_model');

        // Store current URL to reload it after the certificate is granted
        $this->session->set_userdata('return_url', current_url());
        gvv_debug('set_return_url presences to: ' . current_url());

        $this->lang->load('welcome');
    }

    /*
     * check that the user is allowed to change an event
     *
     * ca members and above can change any events
     * regular membres can only change their own events
     */
    function modification_allowed($event_id) {
        if ($this->dx_auth->is_role('ca', true, true)) {
            return true;
        }

        $event = $this->googlecal->get($event_id);
        gvv_debug("event: " . var_export($event, true));

        // TODO replace by the object API
        $summary = $event ['summary'];

        $mlogin = $this->dx_auth->get_username();
        $name = $this->membres_model->image($mlogin);

        $pattern = "/$name/";
        $match = preg_match($pattern, $summary);
        gvv_debug("summary = $summary, name=$name, match=$match");
        return $match;
    }

    /*
     * check that the user is allowed to create an event
     *
     * ca members and above can change any events
     * regular membres can only create their own events
     */
    function creation_allowed($mlogin) {
        if ($this->dx_auth->is_role('ca', true, true)) {
            return true;
        }
        return ($this->dx_auth->get_username() == $mlogin);
    }

    /**
     * The "calendars" collection of methods.
     * Typical usage is:
     * <code>
     * $calendarService = new apiCalendarService(...);
     * $calendars = $calendarService->calendars;
     * </code>
     */
    public function futur_events($mlogin) {
        $name = $this->membres_model->image($mlogin, true);
        $result = $this->googlecal->futur_events($name);
        var_dump($result);
        return $result;
    }

    /**
     * Set the authorisation token from Google
     */
    public function code() {
        $this->googlecal->code();
    }

    /**
     * Création d'un evénement
     */
    public function create() {
        $event = $this->googlecal->create("Frédéric");
        var_dump($event);
    }

    /**
     *
     * Détruit un événement
     *
     * @param unknown_type $id
     */
    public function delete($id, $format = 'html') {
        if (! $this->modification_allowed($id)) {
            return;
        }
        $res = $this->googlecal->delete($id);

        if ($format === 'html') {
            redirect(base_url());
        } else {
            $json = json_encode(array (
                    'status' => "OK",
                    'action' => 'delete',
                    'id' => $id
            ));
            echo $json;
            gvv_debug("json = $json");
        }
    }

    /**
     * Ajout d'une présence pour l'utilisateur courant
     */
    public function ajout($format = 'html') {
        $roles = $this->lang->line("welcome_options");
        $mlogin = $this->input->post('mlogin');
        $commentaire = $this->input->post('commentaire');

        $date_ajout = $this->input->post('date_ajout');
        $date = mysql_date($date_ajout);
        $role = $this->input->post('role');
        $event_id = $this->input->post('event_id');

        if ($event_id) {
            // modification
            if (! $this->modification_allowed($event_id)) {
                return;
            }
        } else {
            // creation
            if (! $this->creation_allowed($mlogin)) {
                return;
            }
        }

        gvv_debug("ajout_event date=$date, mlogin=$mlogin, role=$role, commentaire=$commentaire ");
        if ($mlogin) {
            $name = $this->membres_model->image($mlogin, false);
        } else {
            $name = $commentaire;
        }

        if ($name != '' && $date_ajout != "") {
            if ($role != "") {
                $name .= ", " . $roles [$role];
            }
            if ($event_id) {
                $id = $this->googlecal->update($event_id, $name, $date, $commentaire);
            } else {
                $id = $this->googlecal->create($name, $date, $commentaire);
            }
        }
        if ($format === 'html') {
            redirect(base_url());
        } else {
            $json = json_encode(array (
                    'status' => "OK",
                    'action' => 'ajout'
            ));
            echo $json;
            gvv_debug("json = $json");
        }
    }

    /**
     * MAJ d'une présence pour l'utilisateur courant
     */
    public function update($format = 'json') {
        $event_id = $this->input->post('id');
        $description = $this->input->post('descrition');
        $end = $this->input->post('end');
        $allDay = $this->input->post('allDay');
        $dateformat = ($allDay == "true") ? "date" : "dateTime";
        $start_date = $this->input->post('start');

        $now = new DateTime();
        $tz_offset = $now->getOffset();
        gvv_debug("tz_offset $tzoffset, now=" . var_export($now, true));

        if ($allDay == "true") {
            $tz = "";
        } else {
            $tz = "+00:00";
        }

        $start = array (
                $dateformat => $start_date . $tz
        );

        $result = array (
                'allDay' => $allDay,
                'summary' => $this->input->post('title'),
                'start' => $start
        );
        if ($description) {
            $result ['description'] = $description;
        }
        gvv_debug("event summary: " . $this->input->post('title'));
        if ($end) {
            $result ['end'] = array (
                    $dateformat => $end . $tz
            );
        } else {
            if ($allDay != "true") {
                // allDay is false and no end is defined
                // so compute a default duration (2 hours)

                $timestamp = strtotime($start_date) + (2 * 60 * 60);
                $date_end = date("Y-m-d", $timestamp) . "T" . date("H:i:s", $timestamp);

                $result ['end'] = array (
                        $dateformat => $date_end . $tz
                );
            }
        }

        $result ['action'] = 'update';

        if (! $this->modification_allowed($event_id)) {
            $json = json_encode(array (
                    "status" => "KO",
                    'error' => $this->lang->line("welcome_not_a_user_event")
            ));
            echo $json;
            gvv_debug("json = $json");
            return;
        }

        try {
            $id = $this->googlecal->change($event_id, $result);
            $result ['status'] = 'OK';
        } catch ( Exception $e ) {
            $result ['status'] = 'KO';
            $result ['error'] = 'Exception raised in google->events->update $event_id: ' . $e->getMessage();
        }

        $json = json_encode($result);
        echo $json;
        gvv_debug("json = $json");
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

/**
 * Non automated use cases
 *
 * As a regular user I want to
 * * create an event
 * * modify
 * - pilote, intend, commentaire
 * - from all day to limited
 * - to limited to allday
 * * delete an event
 *
 * * get an error on creation, modification or change of a non owned event
 *
 * As an administrator, I want to
 * * CRUD events owned by another user
 */
}