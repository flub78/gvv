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
 *    Gestion du calendrier
 */
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

    // Google API library
$google = getcwd();
if (preg_match("/tests/", $google)) {
    $google .= "/..";
}
$google .= "/application/third_party/google-api-php-client";
require_once $google . '/src/Google_Client.php';
require_once $google . '/src/contrib/Google_CalendarService.php';

/**
 * Access to a Google Calendar
 */
class GoogleCal {

    // Class attributes
    protected $attr = array ();
    protected $CI;
    protected $client;
    protected $cal;
    protected $events;
    protected $cal_id;

    /**
     * Constructor
     *
     * The constructor can be passed an array of attributes values
     */
    public function __construct($attrs = array ()) {

        // set object attributes
        foreach ( $attrs as $key => $value ) {
            $this->attr [$key] = $attrs [$key];
        }

        $this->CI = & get_instance();
        $this->CI->config->load('google');

        // Store current URL to reload it after the certificate is granted
        $this->CI->session->set_userdata('return_url', current_url());

        // Creation d'un client de connexion pour l'Agenda Google
        $this->client = new Google_Client();
        $this->client->setApplicationName("Google Calendar GVV");

        // Certificat du compte Google
        $redirect_uri = base_url() . "index.php/presences/code";

        $this->cal_id = $this->CI->config->item('calendar_id');
        $this->client->setClientId($this->CI->config->item('client_id'));
        $this->client->setClientSecret($this->CI->config->item('client_secret'));
        $this->client->setRedirectUri($redirect_uri);
        $this->client->setDeveloperKey($this->CI->config->item('api_key'));

        gvv_debug('Google calendar cal_id=' . $this->cal_id);
        gvv_debug('Google calendar client_id=' . $this->CI->config->item('client_id'));
        gvv_debug('Google calendar client_secret=' . $this->CI->config->item('client_secret'));
        gvv_debug('Google calendar redirect uri=' . $redirect_uri);
        gvv_debug('Google calendar API key=' . $this->CI->config->item('api_key'));

        $this->cal = new Google_CalendarService($this->client);
        $this->events = $this->cal->events;

        // Si le token d'accès existe dans la session on le recharge
        // $token = $this->session->userdata('token');
        $token = $this->CI->config->item('token');
        if ($token) {
            gvv_debug("Google calendar token found in google.php");
            $this->client->setAccessToken($token);
        } else {
            gvv_debug("Google calendar no token found in google.php");
        }

        // $this->session->unset_userdata('token_requested');

        if (! $this->client->getAccessToken()) {
            gvv_debug("Google calendar client has no access token");
            // The client has no access token, one must be requested
            if (! $this->CI->session->userdata('token_requested')) {
                gvv_debug("Google calendar requesting token");
                $this->CI->session->set_userdata('token_requested', TRUE);
                $authUrl = $this->client->createAuthUrl();
                redirect($authUrl);
            } else {
                gvv_debug("token already requested");
            }
        } else {
            gvv_debug("Google calendar client token present");
        }
    }

    /**
     * Set the authorisation token from Google
     */
    public function code() {
        gvv_debug("Google calendar calling back code");

        $this->CI->load->helper('update_config');
        $this->CI->load->helper('file');

        $this->client->authenticate();
        $token = $this->client->getAccessToken();
        // Store the token
        $this->CI->session->unset_userdata('token_requested');

        gvv_debug("Google calendar token=" . var_export($token, true));

        $config ['token'] = "'" . $token . "'";
        update_config("./application/config/google.php", $config);
    }

    /**
     * Return the list of events
     */
    public function listEvents($optParam = array()) {
        $event_list = $this->events->listEvents($this->cal_id, $optParam);
        // var_dump($event_list);
        if (array_key_exists('items', $event_list)) {
            // en cas de succés
            return $event_list ['items'];
        }
        return array ();
    }

    /**
     * Creation d'une entrée Google Calendar
     */
    public function create($name, $date, $commentaire = "") {
        $timestamp = strtotime($date) + (24 * 60 * 60);
        $date_end = date("Y-m-d", $timestamp);

        // echo "create date=$date, end=$date_end" . br();

        $attrs = array (
                'summary' => $name,
                'start' => array (
                        'date' => $date
                ),
                'end' => array (
                        'date' => $date_end
                )
        );
        if ($commentaire) {
            $attrs ['description'] = $commentaire;
        }

        try {
            $event = new Google_Event($attrs);
            $this->events->insert($this->cal_id, $event);

            return $event;
        } catch ( Exception $e ) {
        }
    }

    /**
     * Modification d'une entrée Google Calendar
     */
    public function update($event_id, $name, $date, $commentaire = "") {
        $timestamp = strtotime($date) + (24 * 60 * 60);
        $date_end = date("Y-m-d", $timestamp);

        // GVV: update event start=2015-01-06, end=2015-01-07
        gvv_debug("update event start=$date, end=$date_end");

        $attrs = array (
                'summary' => $name,
                'start' => array (
                        'date' => $date
                ),
                'end' => array (
                        'date' => $date_end
                )
        );
        if ($commentaire) {
            $attrs ['description'] = $commentaire;
        }

        try {
            $event = new Google_Event($attrs);
            $this->events->update($this->cal_id, $event_id, $event);

            return $event;
        } catch ( Exception $e ) {
        }
    }

    /**
     * Modification d'une entrée Google Calendar
     */
    public function change($event_id, $attrs = array()) {
        $event = new Google_Event($attrs);

        // $event['summary'] = $attrs['summary'];
        // $event['start'] = array('date' => $attrs['start']);
        gvv_debug("event: attrs " . var_export($attrs, true));
        gvv_debug("event: $event_id " . var_export($event, true));

        $this->events->update($this->cal_id, $event_id, $event);

        return "";
    }

    /**
     * Suppression d'une entrée Google Calendar
     */
    public function delete($id) {
        // echo "delete $id" . br();
        $event = $this->events->delete($this->cal_id, $id);
        return $event;
    }

    /**
     * Vérifie si un événement exist
     *
     * @param unknown_type $nam
     * @param unknown_type $date
     */
    public function exist($name, $date) {
        $timestamp = strtotime($date) + (24 * 60 * 60);
        $date_end = date("Y-m-d", $timestamp) . "T00:00:00+00:00";

        // echo "exist date=$date, end=$date_end" . br();

        $optParam = array (
                "orderBy" => "startTime",
                "singleEvents" => true,
                "q" => $name,
                "timeMin" => $date,
                "timeMax" => $date_end
        );

        $event_list = $this->events->listEvents($this->cal_id, $optParam);
        // var_dump($event_list);
        if (array_key_exists('items', $event_list)) {
            return (count($event_list ['items']));
        } else {
            return 0;
        }
    }

    /**
     * The "calendars" collection of methods.
     * Typical usage is:
     * <code>
     * $calendarService = new apiCalendarService(...);
     * $calendars = $calendarService->calendars;
     * </code>
     */
    public function futur_events($name) {

        // Liste des événements de l'année venir
        $optParam = array (
                "orderBy" => "startTime",
                "singleEvents" => true,
                "timeMin" => date(DateTime::ATOM),
                "timeMax" => date(DateTime::ATOM, time() + (365 * 24 * 60 * 60))
        );

        // Liste des événements d'une personne
        $optParam ['q'] = $name;

        // tout les événements
        $event_list = array ();
        try {
            $event_list = $this->listEvents($optParam);
        } catch ( Exception $e ) {
        }
        $result = array ();

        foreach ( $event_list as $event ) {
            $elt = array (
                    'id' => $event ['id'],
                    'summary' => $event ['summary']
            );

            if (array_key_exists('start', $event)) {
                if (array_key_exists('date', $event ['start'])) {
                    $elt ['start'] = $event ['start'] ['date'];
                }
                if (array_key_exists('dateTime', $event ['start'])) {
                    $elt ['start'] = $event ['start'] ['dateTime'];
                }
            }
            if (array_key_exists('end', $event)) {
                if (array_key_exists('date', $event ['end'])) {
                    $elt ['end'] = $event ['end'] ['date'];
                }
                if (array_key_exists('dateTime', $event ['end'])) {
                    $elt ['end'] = $event ['end'] ['dateTime'];
                }
            }
            $result [] = $elt;
        }
        return $result;
    }

    /**
     * returns an event identified by event id
     *
     * @param unknown_type $event_id
     */
    public function get($event_id) {
        return $this->events->get($this->cal_id, $event_id);
    }
}