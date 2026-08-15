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
 * @filesource acceptance.php
 * @package controllers
 *
 * Espace membre des acceptations (Lot 4) : tableau de bord des elements en
 * attente, lecture et acceptation/refus, historique personnel.
 *
 * Distinct de acceptance_admin.php (administration des elements, reserve CA).
 */

include('./application/libraries/Gvv_Controller.php');

class Acceptance extends Gvv_Controller {
    protected $controller = 'acceptance';
    protected $model = 'acceptance_records_model';
    protected $view_level = 'membre';
    protected $modification_level = 'membre';

    protected $rules = array();

    function __construct() {
        parent::__construct();

        $this->lang->load('acceptance');
        $this->load->model('acceptance_items_model');
        $this->load->model('archived_documents_model');
        $this->load->model('membres_model');

        $this->lang->load('tableaux_de_bord');
        $this->load->vars([
            'nav_back_url'   => 'welcome/section/user',
            'nav_back_label' => $this->lang->line('db_section_personal'),
        ]);
    }

    /**
     * Default page - dashboard of pending items
     */
    function index() {
        $this->dashboard();
    }

    /**
     * List elements the current user still has to process
     */
    function dashboard() {
        $user_login = $this->dx_auth->get_username();
        $today = date('Y-m-d');

        $items = $this->acceptance_items_model->get_pending_items_for_user($user_login);
        foreach ($items as &$item) {
            $item['is_overdue'] = !empty($item['deadline']) && $item['deadline'] < $today;
            $item['is_near_deadline'] = !empty($item['deadline']) && !$item['is_overdue']
                && $item['deadline'] <= date('Y-m-d', strtotime('+7 days'));
            $item['can_postpone'] = $item['mandatory_level'] === 'optional'
                && (empty($item['deadline']) || $item['deadline'] >= $today);
        }
        unset($item);

        $this->data['items'] = $items;
        $this->data['controller'] = $this->controller;

        return load_last_view('acceptance/bs_dashboardView', $this->data, $this->unit_test);
    }

    /**
     * All items eligible for the user, each annotated with the user's
     * personal status for it (pending/accepted/refused) and action date.
     * Unlike dashboard() (pending only) and history() (already acted upon
     * only), this combines both into a single "my documents" overview.
     */
    function my_documents() {
        $user_login = $this->dx_auth->get_username();

        $items = $this->acceptance_items_model->get_items_for_user($user_login);
        $records_by_item = array();
        foreach ($this->gvv_model->get_by_user($user_login) as $record) {
            $records_by_item[$record['item_id']] = $record;
        }

        foreach ($items as &$item) {
            $record = isset($records_by_item[$item['id']]) ? $records_by_item[$item['id']] : null;
            $item['status'] = $record ? $record['status'] : 'pending';
            $item['acted_at'] = $record ? $record['acted_at'] : null;
        }
        unset($item);

        $this->data['items'] = $items;
        $this->data['controller'] = $this->controller;

        return load_last_view('acceptance/bs_myDocumentsView', $this->data, $this->unit_test);
    }

    /**
     * Read and accept/refuse screen for a single item
     */
    function read($item_id) {
        $user_login = $this->dx_auth->get_username();
        $item = $this->_get_authorized_item($item_id, $user_login);
        if (!$item) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">' . $this->lang->line('acceptance_item_not_found') . '</div>');
            redirect('acceptance');
            return;
        }

        $record = $this->gvv_model->get_or_create_pending($item_id, $user_login);

        $this->data['item'] = $item;
        $this->data['record'] = $record;
        $this->data['has_pdf'] = !empty($item['archived_document_id']) || !empty($item['pdf_path']);
        $this->data['controller'] = $this->controller;

        return load_last_view('acceptance/bs_readView', $this->data, $this->unit_test);
    }

    /**
     * Accept an item - records the acceptance formula (PRD, Formule d'acceptation)
     */
    function accept($item_id) {
        $user_login = $this->dx_auth->get_username();
        $item = $this->_get_authorized_item($item_id, $user_login);
        if (!$item) {
            show_404();
            return;
        }

        $record = $this->gvv_model->get_or_create_pending($item_id, $user_login);
        if (!$record) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">' . $this->lang->line('acceptance_error_no_member_record') . '</div>');
            redirect('acceptance');
            return;
        }

        $formula = $this->_acceptance_formula($user_login, $item['title']);
        $this->gvv_model->accept($record['id'], $formula);
        $this->acceptance_items_model->clear_target_motd_for_user($item_id, $user_login);

        $this->session->set_flashdata('message', '<div class="alert alert-success">' . $this->lang->line('acceptance_accept_success') . '</div>');
        redirect('acceptance');
    }

    /**
     * Refuse an item
     */
    function refuse($item_id) {
        $user_login = $this->dx_auth->get_username();
        $item = $this->_get_authorized_item($item_id, $user_login);
        if (!$item) {
            show_404();
            return;
        }

        $record = $this->gvv_model->get_or_create_pending($item_id, $user_login);
        if (!$record) {
            $this->session->set_flashdata('message', '<div class="alert alert-danger">' . $this->lang->line('acceptance_error_no_member_record') . '</div>');
            redirect('acceptance');
            return;
        }

        $this->gvv_model->refuse($record['id']);
        $this->acceptance_items_model->clear_target_motd_for_user($item_id, $user_login);

        $this->session->set_flashdata('message', '<div class="alert alert-warning">' . $this->lang->line('acceptance_refuse_success') . '</div>');
        redirect('acceptance');
    }

    /**
     * Stream the item's PDF inline (for the read page's iframe viewer).
     * Not a force-download: see acceptance_admin/download for the admin equivalent.
     */
    function pdf($item_id) {
        $user_login = $this->dx_auth->get_username();
        $item = $this->_get_authorized_item($item_id, $user_login);
        if (!$item) {
            show_404();
            return;
        }

        if (!empty($item['archived_document_id'])) {
            $doc = $this->archived_documents_model->get_by_id('id', $item['archived_document_id']);
            $file_path = $doc ? $doc['file_path'] : null;
            $filename = $doc ? $doc['original_filename'] : 'document.pdf';
        } else {
            $file_path = $item['pdf_path'];
            $filename = $file_path ? basename($file_path) : 'document.pdf';
        }

        if (empty($file_path) || !file_exists($file_path)) {
            show_404();
            return;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit;
    }

    /**
     * Personal history: items already accepted or refused
     */
    function history() {
        $user_login = $this->dx_auth->get_username();
        $today = date('Y-m-d');

        $records = $this->gvv_model->get_by_user($user_login);
        $records = array_values(array_filter($records, function ($r) {
            return $r['status'] !== 'pending';
        }));
        foreach ($records as &$r) {
            $r['was_overdue'] = !empty($r['deadline']) && !empty($r['acted_at'])
                && substr($r['acted_at'], 0, 10) > $r['deadline'];
        }
        unset($r);

        $this->data['records'] = $records;
        $this->data['controller'] = $this->controller;

        return load_last_view('acceptance/bs_historyView', $this->data, $this->unit_test);
    }

    // ---------------------------------------------------------------
    // Private helpers
    // ---------------------------------------------------------------

    /**
     * An item is accessible to the member if they are currently targeted by
     * it, if they already have a personal record for it (kept accessible
     * for re-reading even if targeting changed since), or if they are admin.
     */
    private function _get_authorized_item($item_id, $user_login) {
        $item = $this->acceptance_items_model->get_by_id('id', $item_id);
        if (!$item || count($item) < 1) {
            return null;
        }

        if ($this->_is_admin()) {
            return $item;
        }

        $existing_record = $this->gvv_model->get_first(array('item_id' => $item_id, 'user_login' => $user_login));
        if ($existing_record) {
            return $item;
        }

        if (empty($item['active'])) {
            return null;
        }

        foreach ($this->acceptance_items_model->get_items_for_user($user_login) as $eligible) {
            if ($eligible['id'] == $item_id) {
                return $item;
            }
        }
        return null;
    }

    private function _is_admin() {
        return $this->user_has_role('ca') || $this->user_has_role('club-admin');
    }

    /**
     * PRD, Formule d'acceptation:
     * "Je soussigné(e) [Prénom Nom], membre du club identifié par le
     * système, reconnais avoir pris connaissance et accepter [titre de
     * l'élément] en date du [date]."
     */
    private function _acceptance_formula($user_login, $item_title) {
        $member = $this->membres_model->get_by_id('mlogin', $user_login);
        $full_name = trim(($member['mprenom'] ?? '') . ' ' . ($member['mnom'] ?? ''));
        if ($full_name === '') {
            $full_name = $user_login;
        }
        return sprintf(
            $this->lang->line('acceptance_formula_member'),
            $full_name,
            $item_title,
            date('d/m/Y')
        );
    }
}

/* End of file acceptance.php */
/* Location: ./application/controllers/acceptance.php */
