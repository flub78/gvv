<?php
if (!defined('BASEPATH'))
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
 * Read-only calendar showing archived documents by expiration date.
 *
 * @filesource deadlines_calendar.php
 * @package controllers
 */
set_include_path(getcwd() . "/..:" . get_include_path());

class Deadlines_calendar extends MY_Controller {

    function __construct() {
        parent::__construct();

        if (!getenv('TEST') && !$this->dx_auth->is_logged_in()) {
            redirect("auth/login");
        }

        if (!$this->config->item('gestion_documentaire')) {
            show_404();
        }

        $this->lang->load('tableaux_de_bord');
        $this->lang->load('archived_documents');

        $this->load->vars([
            'nav_back_url'   => $this->session->userdata('nav_from_url')   ?: 'welcome/section/admin_club',
            'nav_back_label' => $this->session->userdata('nav_from_label') ?: $this->lang->line('db_section_admin_club'),
        ]);
    }

    /**
     * Display the FullCalendar v6 read-only calendar
     */
    function index() {
        if (!$this->_is_admin()) {
            redirect('archived_documents/page');
        }

        // Let pages opened from the calendar (e.g. archived_documents/view) return here
        $this->session->set_userdata('nav_from_url',   'deadlines_calendar');
        $this->session->set_userdata('nav_from_label', $this->lang->line('archived_documents_calendar_title'));

        $translations = array(
            'title'            => $this->lang->line('archived_documents_calendar_title'),
            'legend_expired'   => $this->lang->line('archived_documents_legend_expired'),
            'legend_expiring'  => $this->lang->line('archived_documents_legend_expiring_soon'),
            'legend_active'    => $this->lang->line('archived_documents_legend_active'),
            'btn_year_view'    => $this->lang->line('archived_documents_year_view'),
            'btn_today'        => $this->lang->line('archived_documents_btn_today'),
            'btn_month'        => $this->lang->line('archived_documents_btn_month'),
            'btn_week'         => $this->lang->line('archived_documents_btn_week'),
            'btn_day'          => $this->lang->line('archived_documents_btn_day'),
            'btn_list'         => $this->lang->line('archived_documents_btn_list'),
        );

        $locale_map = array(
            'french'  => 'fr',
            'english' => 'en',
            'dutch'   => 'nl',
        );
        $ci_language = $this->config->item('language');
        $fullcalendar_locale = isset($locale_map[$ci_language]) ? $locale_map[$ci_language] : 'en';

        $data = array(
            'translations'        => $translations,
            'fullcalendar_locale' => $fullcalendar_locale,
        );

        load_last_view('deadlines_calendar/calendar', $data);
    }

    /**
     * JSON endpoint: returns archived documents as FullCalendar events
     */
    function get_events() {
        if (ob_get_level()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=UTF-8');

        if (!$this->_is_admin()) {
            echo json_encode(array());
            return;
        }

        try {
            $start = isset($_GET['start']) ? $_GET['start'] : null;
            $end   = isset($_GET['end'])   ? $_GET['end']   : null;

            $this->load->model('archived_documents_model');
            $docs = $this->archived_documents_model->get_documents_for_calendar($start, $end);

            $events = array();
            foreach ($docs as $doc) {
                $status = $this->archived_documents_model->compute_expiration_status($doc);

                if ($status === 'expired') {
                    $color = '#dc3545';
                } elseif ($status === 'expiring_soon') {
                    $color = '#fd7e14';
                } else {
                    $color = '#198754';
                }

                $pilot = trim($doc['pilot_nom'] . ' ' . $doc['pilot_prenom']);
                $title = $doc['type_name'] ?: ($doc['description'] ?: $doc['original_filename']);
                if ($pilot) {
                    $title .= ' – ' . $pilot;
                }

                $events[] = array(
                    'id'    => $doc['id'],
                    'title' => $title,
                    'start' => $doc['valid_until'],
                    'allDay' => true,
                    'url'   => site_url('archived_documents/view/' . $doc['id']),
                    'color' => $color,
                    'extendedProps' => array(
                        'status'      => $status,
                        'pilot'       => $pilot,
                        'section'     => $doc['section_name'],
                        'type'        => $doc['type_name'],
                        'description' => $doc['description'],
                    ),
                );
            }

            echo json_encode($events);
        } catch (Exception $e) {
            gvv_error("Error in deadlines_calendar get_events: " . $e->getMessage());
            echo json_encode(array('error' => $e->getMessage()));
        }
    }

    private function _is_admin() {
        return $this->user_has_role('ca') || $this->user_has_role('club-admin');
    }
}

/* End of file deadlines_calendar.php */
/* Location: ./application/controllers/deadlines_calendar.php */
