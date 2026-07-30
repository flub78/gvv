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
 * @filesource motd.php
 * @package controllers
 *
 * Administration des messages du jour (MOTD)
 */
include('./application/libraries/Gvv_Controller.php');

class Motd extends Gvv_Controller {

    protected $controller = 'motd';
    protected $back_dashboard = 'welcome/section/admin_sys';
    protected $model = 'motd_model';
    protected $modification_level = 'club-admin';

    protected $rules = array(
        'content' => 'required',
        'target_type' => 'callback_valid_motd_target',
        'end_date' => 'callback_valid_motd_dates',
    );

    /**
     * Max upload size for message images, in KB (PRD EF6: taille max configurable;
     * hardcoded for now, no admin-facing setting requested).
     */
    const IMAGE_MAX_SIZE_KB = 4096;

    function __construct() {
        parent::__construct();
        $this->lang->load('motd');
        $this->load->helper('markdown');
        $this->load->model('motd_media_model');
        $this->load->model('membres_model');
        $this->load->model('motd_user_prefs_model');
        $this->load->model('motd_user_state_model');
        $this->load->model('motd_replies_model');
    }

    /**
     * Only club-admins manage messages (PRD EF5). Unlike the CRUD actions,
     * media() must stay reachable by any authorized recipient, so the check
     * is applied per-action rather than in the constructor.
     */
    private function can_manage() {
        return $this->user_has_role('club-admin');
    }

    /**
     * Admin list
     */
    public function page($premier = 0, $message = '', $selection = array()) {
        if (!$this->can_manage()) {
            show_404();
            return;
        }
        $this->view_parameters['page'] = 'vue_motd_messages';
        $this->view_parameters['title'] = $this->lang->line('motd_title');
        parent::page($premier, $message, $selection);
    }

    public function create() {
        if (!$this->can_manage()) {
            show_404();
            return;
        }
        $this->view_parameters['page'] = 'motd_messages';
        parent::create();
    }

    public function edit($id = '', $load_view = true, $action = MODIFICATION) {
        if (!$this->can_manage()) {
            show_404();
            return;
        }
        $this->view_parameters['page'] = 'motd_messages';
        parent::edit($id, $load_view, $action);
    }

    public function view($id = '') {
        if (!$this->can_manage()) {
            show_404();
            return;
        }
        $this->view_parameters['page'] = 'motd_messages';
        parent::view($id);
    }

    public function delete($id) {
        if (!$this->can_manage()) {
            show_404();
            return;
        }
        $message = $this->gvv_model->get_message($id);
        gvv_info("motd: message #$id (\"" . ($message['title'] ?: '') . "\") deleted by " . $this->dx_auth->get_username());
        // parent::delete() redirects (and exits) on success, so any file
        // cleanup must happen first; the motd_media rows themselves are
        // removed by the DB's ON DELETE CASCADE when the message row goes.
        $this->delete_media_files($this->motd_media_model->media_for_message($id));
        parent::delete($id);
    }

    /**
     * Remove the physical files backing a set of motd_media rows.
     */
    private function delete_media_files($media_files) {
        foreach ($media_files as $media) {
            $file_path = FCPATH . 'uploads/motd/' . $media['filename'];
            if (is_file($file_path) && !@unlink($file_path)) {
                gvv_error("motd: failed to delete media file " . $media['filename'] . " (media #" . $media['id'] . ")");
            }
        }
    }

    public function formValidation($action, $return_on_success = false) {
        if (!$this->can_manage()) {
            show_404();
            return;
        }
        return parent::formValidation($action, $return_on_success);
    }

    /**
     * Load selectors for the form (target mailing list / target user)
     */
    function form_static_element($action) {
        parent::form_static_element($action);
        $this->gvvmetadata->set_selector('motd_list_selector', $this->gvv_model->list_selector());
        $this->gvvmetadata->set_selector('motd_user_selector', $this->membres_model->selector_with_null());
    }

    /**
     * Apply time-of-day to the date-only widgets and null out the target
     * field that doesn't match the selected target_type.
     */
    function form2database($action = '') {
        $processed_data = parent::form2database($action);

        if (!empty($processed_data['start_date'])) {
            $processed_data['start_date'] .= ' 00:00:00';
        }
        if (!empty($processed_data['end_date'])) {
            $processed_data['end_date'] .= ' 23:59:59';
        }

        // level is optional (PRD EF1): no radio pre-selected means the browser
        // submits nothing for it. CodeIgniter's input->post() then returns
        // FALSE (not NULL) for the missing field, which would otherwise
        // violate the ENUM column - store that as "no level" (NULL).
        if (empty($processed_data['level'])) {
            $processed_data['level'] = null;
        }

        if (isset($processed_data['target_type'])) {
            if ($processed_data['target_type'] !== 'list') {
                $processed_data['target_list_id'] = null;
            }
            if ($processed_data['target_type'] !== 'user') {
                $processed_data['target_user_login'] = null;
            }
        }

        // origin/source_type/source_ref are not form fields - they only apply to
        // GVV-generated alarm messages (Motd_model::generate_system_message()).
        // Never let this admin form blank them out or overwrite them.
        unset($processed_data['source_type']);
        unset($processed_data['source_ref']);
        if ($action == CREATION) {
            $processed_data['origin'] = 'admin';
        } else {
            unset($processed_data['origin']);
        }

        return $processed_data;
    }

    /**
     * Link any image uploaded (and still orphan) while editing this message's
     * content to the now-saved message id.
     */
    function post_create($data = array()) {
        parent::post_create($data);
        gvv_info("motd: message #" . (isset($data['id']) ? $data['id'] : '?') . " (\"" . (isset($data['title']) ? $data['title'] : '') . "\") created by "
            . $this->dx_auth->get_username() . ", target_type=" . (isset($data['target_type']) ? $data['target_type'] : '?'));
        $this->link_uploaded_media($data);
    }

    function post_update($data = array()) {
        parent::post_update($data);
        gvv_info("motd: message #" . (isset($data['id']) ? $data['id'] : '?') . " (\"" . (isset($data['title']) ? $data['title'] : '') . "\") updated by "
            . $this->dx_auth->get_username() . ", target_type=" . (isset($data['target_type']) ? $data['target_type'] : '?'));
        $this->link_uploaded_media($data);
    }

    private function link_uploaded_media($data) {
        if (empty($data['id']) || empty($data['content'])) {
            return;
        }
        if (!preg_match_all('#motd/media/(\d+)#', $data['content'], $matches)) {
            return;
        }
        $this->motd_media_model->link_to_message($matches[1], $data['id'], $this->dx_auth->get_username());
    }

    /**
     * Validation callback: target_type must resolve to an existing list/user.
     */
    public function valid_motd_target($target_type) {
        $check = array(
            'target_type' => $target_type,
            'target_list_id' => $this->input->post('target_list_id'),
            'target_user_login' => $this->input->post('target_user_login'),
        );
        if ($this->gvv_model->is_target_valid($check)) {
            return TRUE;
        }
        $this->form_validation->set_message('valid_motd_target', $this->lang->line('motd_error_invalid_target'));
        return FALSE;
    }

    /**
     * Validation callback: end_date must not be before start_date.
     */
    public function valid_motd_dates($end_date) {
        $start_db = date_ht2db($this->input->post('start_date'));
        $end_db = date_ht2db($end_date);
        if ($start_db && $end_db && $end_db < $start_db) {
            $this->form_validation->set_message('valid_motd_dates', $this->lang->line('motd_error_dates_incoherent'));
            return FALSE;
        }
        return TRUE;
    }

    /**
     * AJAX endpoint: upload an image and return its (not yet linked) media id + serving URL,
     * for insertion into the content editor as ![alt](url).
     */
    public function upload_image() {
        if (!$this->can_manage()) {
            show_404();
            return;
        }
        if (empty($_FILES['image_file']['name'])) {
            return $this->json_error($this->lang->line('motd_image_upload_error'));
        }

        $upload_dir = FCPATH . 'uploads/motd/';
        if (!is_dir($upload_dir) && !@mkdir($upload_dir, 0775, true)) {
            return $this->json_error($this->lang->line('motd_image_upload_error'));
        }

        $config = array(
            'upload_path' => $upload_dir,
            'allowed_types' => 'png|jpg|jpeg|webp',
            'max_size' => self::IMAGE_MAX_SIZE_KB,
            'encrypt_name' => true,
        );

        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('image_file')) {
            return $this->json_error($this->upload->display_errors('', ''));
        }

        $uploaded = $this->upload->data();
        $full_path = $uploaded['full_path'];
        $mime_type = mime_content_type($full_path) ?: $uploaded['file_type'];

        $media_id = $this->motd_media_model->create_media(array(
            'message_id' => null,
            'filename' => $uploaded['file_name'],
            'original_filename' => $uploaded['orig_name'],
            'mime_type' => $mime_type,
            'size_bytes' => $uploaded['file_size'] * 1024,
            'sha256' => hash_file('sha256', $full_path),
            'created_by' => $this->dx_auth->get_username(),
            'updated_by' => $this->dx_auth->get_username(),
        ));

        if (!$media_id) {
            return $this->json_error($this->lang->line('motd_image_upload_error'));
        }

        header('Content-Type: application/json');
        echo json_encode(array('url' => site_url('motd/media/' . $media_id)));
    }

    private function json_error($message) {
        header('Content-Type: application/json');
        http_response_code(422);
        echo json_encode(array('error' => $message));
    }

    /**
     * Controlled serving endpoint for message images.
     * Access: admins, the message's own author, or a recipient of the linked
     * message; an orphan (not-yet-linked) upload is only visible to its uploader.
     */
    public function media($id) {
        $media = $this->motd_media_model->get_media($id);
        if (!$media) {
            show_404();
            return;
        }

        $username = $this->dx_auth->get_username();
        $is_admin = $this->user_has_role('club-admin');

        if (empty($media['message_id'])) {
            if (!$is_admin && $media['created_by'] !== $username) {
                show_404();
                return;
            }
        } else {
            $message = $this->gvv_model->get_message($media['message_id']);
            if (!$message || !$this->gvv_model->user_can_access_message($message, $username, $is_admin)) {
                show_404();
                return;
            }
        }

        $file_path = FCPATH . 'uploads/motd/' . $media['filename'];
        if (!is_file($file_path)) {
            show_404();
            return;
        }

        header('Content-Type: ' . $media['mime_type']);
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
    }

    /**
     * Dedicated page (PRD EF4): every currently active message applicable to
     * the user (direct target, mailing list, or all), including messages
     * already hidden from the dashboard section. Expired/not-yet-started
     * messages are never shown (policy set at plan step 1).
     */
    public function mine() {
        $username = $this->dx_auth->get_username();
        $motd_prefs = $this->motd_user_prefs_model->get_prefs($username);
        // Masquage persistant (session/rechargement) : comme sur le dashboard, un
        // message masqué reste masqué tant que l'utilisateur ne le démasque pas
        // explicitement via le bouton "Afficher les messages masqués".
        $motd_messages = $this->gvv_model->active_messages_for_user($username, $motd_prefs['sort_by'], TRUE);
        foreach ($motd_messages as &$motd_message) {
            $motd_message['replies'] = $this->motd_replies_model->replies_for_message($motd_message['id']);
        }
        unset($motd_message);

        $all_messages = $this->gvv_model->active_messages_for_user($username, $motd_prefs['sort_by'], FALSE);
        $hidden_count = 0;
        foreach ($all_messages as $motd_message) {
            if (!empty($motd_message['hidden'])) {
                $hidden_count++;
            }
        }

        $data = array(
            'motd_messages' => $motd_messages,
            'motd_hidden_count' => $hidden_count,
            'is_admin' => $this->can_manage(),
        );
        load_last_view('motd/bs_my_messages', $data, $this->unit_test);
    }

    /**
     * AJAX: the current user hides one message on their own dashboard.
     */
    public function hide_message($id) {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }
        $username = $this->dx_auth->get_username();
        $message = $this->gvv_model->get_message($id);
        if (!$message || !$this->gvv_model->user_can_access_message($message, $username, $this->can_manage())) {
            show_404();
            return;
        }
        $this->motd_user_state_model->hide_message($id, $username);
        header('Content-Type: application/json');
        echo json_encode(array('success' => TRUE));
    }

    /**
     * AJAX: the current user hides every message currently active and visible to them.
     */
    public function hide_all() {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }
        $count = $this->motd_user_state_model->hide_all_messages($this->dx_auth->get_username());
        header('Content-Type: application/json');
        echo json_encode(array('success' => TRUE, 'count' => $count));
    }

    /**
     * AJAX: the current user unhides every message they had previously hidden.
     */
    public function unhide_all() {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }
        $count = $this->motd_user_state_model->unhide_all_messages($this->dx_auth->get_username());
        header('Content-Type: application/json');
        echo json_encode(array('success' => TRUE, 'count' => $count));
    }

    /**
     * AJAX: the current user acknowledges ("pris connaissance") one message.
     */
    public function acknowledge_message($id) {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }
        $username = $this->dx_auth->get_username();
        $message = $this->gvv_model->get_message($id);
        if (!$message || !$this->gvv_model->user_can_access_message($message, $username, $this->can_manage())) {
            show_404();
            return;
        }
        $this->motd_user_state_model->acknowledge_message($id, $username);
        header('Content-Type: application/json');
        echo json_encode(array('success' => TRUE));
    }

    /**
     * AJAX: reply to a message. Recipients and the message's own author may
     * post a top-level reply; only admins may target an existing reply
     * (parent_reply_id) to reply to a reply received (PRD parcours admin).
     */
    public function reply($id) {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }
        $username = $this->dx_auth->get_username();
        $is_admin = $this->can_manage();
        $message = $this->gvv_model->get_message($id);
        if (!$message || !$this->gvv_model->user_can_access_message($message, $username, $is_admin)) {
            show_404();
            return;
        }

        $content = trim((string) $this->input->post('content'));
        if ($content === '') {
            header('Content-Type: application/json');
            http_response_code(422);
            echo json_encode(array('success' => FALSE, 'error' => $this->lang->line('motd_error_reply_empty')));
            return;
        }

        $parent_reply_id = NULL;
        $posted_parent_id = $this->input->post('parent_reply_id');
        if ($is_admin && !empty($posted_parent_id)) {
            $parent_reply = $this->motd_replies_model->get_reply($posted_parent_id);
            if ($parent_reply && (int) $parent_reply['message_id'] === (int) $id) {
                $parent_reply_id = $posted_parent_id;
            }
        }

        $reply_id = $this->motd_replies_model->create_reply(array(
            'message_id' => $id,
            'parent_reply_id' => $parent_reply_id,
            'author_login' => $username,
            'content' => $content,
            'created_by' => $username,
            'updated_by' => $username,
        ));
        $reply = $this->motd_replies_model->get_reply($reply_id);

        gvv_info("motd: reply #$reply_id posted on message #$id by $username" . ($parent_reply_id ? " (in reply to #$parent_reply_id)" : ''));

        header('Content-Type: application/json');
        echo json_encode(array(
            'success' => TRUE,
            'reply' => array(
                'id' => (int) $reply['id'],
                'author_login' => $reply['author_login'],
                'created_at' => date('d/m/Y H:i', strtotime($reply['created_at'])),
                'content_html' => markdown($reply['content']),
                'parent_reply_id' => $reply['parent_reply_id'] !== NULL ? (int) $reply['parent_reply_id'] : NULL,
            ),
        ));
    }

    /**
     * AJAX: persist the collapsed/expanded state of the dashboard MOTD section,
     * toggled manually by the user (any logged-in user, not just admins).
     */
    public function toggle_section() {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }
        $collapsed = $this->input->post('collapsed') ? 1 : 0;
        $this->motd_user_prefs_model->save_prefs($this->dx_auth->get_username(), array('section_collapsed' => $collapsed));
        header('Content-Type: application/json');
        echo json_encode(array('success' => TRUE));
    }

    /**
     * AJAX: persist the sort criterion (priority/date) of the dashboard MOTD section.
     */
    public function set_sort() {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }
        $sort_by = $this->input->post('sort_by');
        if (!in_array($sort_by, array('priority', 'date'), TRUE)) {
            header('Content-Type: application/json');
            http_response_code(422);
            echo json_encode(array('success' => FALSE));
            return;
        }
        $this->motd_user_prefs_model->save_prefs($this->dx_auth->get_username(), array('sort_by' => $sort_by));
        header('Content-Type: application/json');
        echo json_encode(array('success' => TRUE));
    }
}

/* End of file motd.php */
/* Location: ./application/controllers/motd.php */
