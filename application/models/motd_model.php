<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Model for motd_messages table (Messages du jour)
 *
 * Handles CRUD for messages, recipient resolution (all users, a mailing
 * list, or a single user) and the entry point used by GVV to generate
 * alarm-originated messages.
 *
 * @package models
 * @see application/migrations/143_create_motd_tables.php
 * @see doc/design_notes/messages_du_jour_design.md
 */
class Motd_model extends Common_Model {
    public $table = 'motd_messages';
    protected $primary_key = 'id';

    /**
     * Priority order used when sorting by 'priority' (lower = more urgent).
     * A NULL level sorts after all defined levels.
     */
    private static $priority_rank = array(
        'urgent' => 0,
        'important' => 1,
        'info' => 2,
        'alerte' => 3,
    );

    function __construct() {
        parent::__construct();
        $this->load->model('email_lists_model');
    }

    /**
     * Create a message after checking its target is resolvable.
     *
     * @param array $data
     * @return int|false New message id, or FALSE if the target is invalid.
     */
    public function create_message($data) {
        if (!$this->is_target_valid($data)) {
            gvv_error("motd create_message rejected: invalid target, data=" . var_export($data, true));
            return FALSE;
        }
        return $this->create($data);
    }

    /**
     * Update a message. Re-validates the target only if it is part of $data.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update_message($id, $data) {
        if (isset($data['target_type']) && !$this->is_target_valid($data)) {
            gvv_error("motd update_message rejected: invalid target, id=$id");
            return FALSE;
        }
        $this->update('id', $data, $id);
        return TRUE;
    }

    public function delete_message($id) {
        $this->delete(array('id' => $id));
    }

    public function get_message($id) {
        return $this->get_by_id('id', $id);
    }

    /**
     * Admin listing of messages (no visibility filtering).
     */
    public function list_messages($where = array(), $order_by = 'start_date DESC') {
        return $this->select_all($where, $order_by);
    }

    /**
     * Admin list view (vue_motd_messages): adds a human-readable target_label
     * column so the table view doesn't need to resolve list names itself.
     */
    public function select_page($per_page = 0, $premier = 0, $selection = array()) {
        $this->db->select('id, title, level, start_date, end_date, target_type,
            target_list_id, target_user_login, origin');
        $this->db->from($this->table);
        if (!empty($selection)) {
            $this->db->where($selection);
        }
        $this->db->order_by('start_date', 'desc');
        if ($per_page) {
            $this->db->limit($per_page, $premier);
        }

        $rows = $this->get_to_array($this->db->get());

        foreach ($rows as &$row) {
            $row['target_label'] = $this->target_label($row);
        }

        $this->gvvmetadata->store_table('vue_motd_messages', $rows);
        return $rows;
    }

    /**
     * Human-readable recipient description for a message row.
     */
    public function target_label($message) {
        switch ($message['target_type']) {
            case 'user':
                return $message['target_user_login'];
            case 'list':
                $list = $this->email_lists_model->get_list($message['target_list_id']);
                return $list ? $list['name'] : '?';
            default:
                return get_instance()->lang->line('motd_target_all');
        }
    }

    /**
     * id => name selector for the target_list_id field, restricted to visible lists.
     */
    public function list_selector() {
        $result = array('' => '');
        foreach ($this->email_lists_model->get_visible_lists() as $list) {
            $result[$list['id']] = $list['name'];
        }
        return $result;
    }

    /**
     * Messages targeted at a given mailing list (admin filter).
     */
    public function messages_for_list($list_id) {
        return $this->select_all(array('target_list_id' => $list_id), 'start_date DESC');
    }

    /**
     * Entry point for GVV-generated alarm messages.
     * Forces origin='system'; the message remains editable by admins afterwards.
     *
     * @param array $params title?, content, level?, start_date?, end_date,
     *                      target_type?, target_list_id?, target_user_login?,
     *                      source_type, source_ref?
     * @return int|false
     */
    public function generate_system_message($params) {
        $data = array(
            'title' => isset($params['title']) ? $params['title'] : NULL,
            'content' => $params['content'],
            'level' => isset($params['level']) ? $params['level'] : 'alerte',
            'start_date' => isset($params['start_date']) ? $params['start_date'] : date('Y-m-d H:i:s'),
            'end_date' => $params['end_date'],
            'target_type' => isset($params['target_type']) ? $params['target_type'] : 'user',
            'target_list_id' => isset($params['target_list_id']) ? $params['target_list_id'] : NULL,
            'target_user_login' => isset($params['target_user_login']) ? $params['target_user_login'] : NULL,
            'origin' => 'system',
            'source_type' => $params['source_type'],
            'source_ref' => isset($params['source_ref']) ? $params['source_ref'] : NULL,
        );

        // Optional: let the caller identify itself (e.g. a cron job) when no
        // interactive session is available for Common_Model's audit fields.
        if (isset($params['created_by'])) {
            $data['created_by'] = $params['created_by'];
        }
        if (isset($params['updated_by'])) {
            $data['updated_by'] = $params['updated_by'];
        }

        return $this->create_message($data);
    }

    /**
     * Active messages (within their display period) visible to a given user,
     * either because target_type='all', they are the direct target, or they
     * belong to the targeted mailing list.
     *
     * @param string $mlogin
     * @param string $sort_by 'priority' (default) or 'date'
     * @param bool $exclude_hidden Exclude messages the user already hid
     * @return array Rows of motd_messages plus hidden/acknowledged/acknowledged_at
     */
    public function active_messages_for_user($mlogin, $sort_by = 'priority', $exclude_hidden = TRUE) {
        $now = date('Y-m-d H:i:s');

        $this->db->select('m.*, s.hidden, s.acknowledged, s.acknowledged_at');
        $this->db->from('motd_messages m');
        $this->db->join(
            'motd_user_message_state s',
            's.message_id = m.id AND s.user_login = ' . $this->db->escape($mlogin),
            'left'
        );
        $this->db->where('m.start_date <=', $now);
        $this->db->where('m.end_date >=', $now);
        $this->db->where(
            "(m.target_type = 'all' OR (m.target_type = 'user' AND m.target_user_login = "
            . $this->db->escape($mlogin) . ") OR m.target_type = 'list')",
            NULL,
            FALSE
        );

        $rows = $this->get_to_array($this->db->get());

        $result = array();
        foreach ($rows as $row) {
            if ($row['target_type'] === 'list' && !$this->user_belongs_to_list($mlogin, $row['target_list_id'])) {
                continue;
            }
            if ($exclude_hidden && !empty($row['hidden'])) {
                continue;
            }
            $result[] = $row;
        }

        return $this->sort_messages($result, $sort_by);
    }

    /**
     * Whether $mlogin may see/reply to $message (recipient or the message's own author).
     *
     * @param array $message A motd_messages row
     * @param string $mlogin
     * @param bool $is_admin
     */
    public function user_can_access_message($message, $mlogin, $is_admin = FALSE) {
        if ($is_admin) {
            return TRUE;
        }
        if (!empty($message['created_by']) && $message['created_by'] === $mlogin) {
            return TRUE;
        }

        switch ($message['target_type']) {
            case 'all':
                return TRUE;
            case 'user':
                return $message['target_user_login'] === $mlogin;
            case 'list':
                return $this->user_belongs_to_list($mlogin, $message['target_list_id']);
        }
        return FALSE;
    }

    /**
     * Whether $mlogin belongs to mailing list $list_id (roles, manual members,
     * or one level of sublist - mirrors Email_lists_model's own depth=1 limit).
     */
    public function user_belongs_to_list($mlogin, $list_id) {
        if (empty($list_id)) {
            return FALSE;
        }
        return in_array($mlogin, $this->list_member_logins($list_id), TRUE);
    }

    private function list_member_logins($list_id, $depth = 0) {
        $logins = array();
        $list = $this->email_lists_model->get_list($list_id);
        if (!$list) {
            return $logins;
        }
        $require_cotisation = !empty($list['require_cotisation']);

        foreach ($this->email_lists_model->get_list_roles($list_id) as $role) {
            $role_members = $this->email_lists_model->get_users_by_role_and_section(
                $role['types_roles_id'], $role['section_id'], $list['active_member'], $require_cotisation
            );
            foreach ($role_members as $user) {
                if (!empty($user['mlogin'])) {
                    $logins[] = $user['mlogin'];
                }
            }
        }

        foreach ($this->email_lists_model->get_manual_members($list_id) as $member) {
            if (!empty($member['membre_id'])) {
                $logins[] = $member['membre_id'];
            }
        }

        if ($depth < 1) {
            foreach ($this->email_lists_model->get_sublists($list_id) as $sublist) {
                $logins = array_merge($logins, $this->list_member_logins($sublist['id'], $depth + 1));
            }
        }

        return array_unique($logins);
    }

    private function sort_messages($messages, $sort_by) {
        usort($messages, function ($a, $b) use ($sort_by) {
            if ($sort_by === 'date') {
                return strcmp($a['start_date'], $b['start_date']);
            }
            $rank_a = isset(self::$priority_rank[$a['level']]) ? self::$priority_rank[$a['level']] : 99;
            $rank_b = isset(self::$priority_rank[$b['level']]) ? self::$priority_rank[$b['level']] : 99;
            if ($rank_a === $rank_b) {
                return strcmp($a['start_date'], $b['start_date']);
            }
            return $rank_a <=> $rank_b;
        });

        return $messages;
    }

    /**
     * A message must target 'all', an existing mailing list, or an existing member.
     */
    public function is_target_valid($data) {
        $target_type = isset($data['target_type']) ? $data['target_type'] : 'all';

        if ($target_type === 'list') {
            if (empty($data['target_list_id'])) {
                return FALSE;
            }
            return (bool) $this->email_lists_model->get_list($data['target_list_id']);
        }

        if ($target_type === 'user') {
            if (empty($data['target_user_login'])) {
                return FALSE;
            }
            $this->db->where('mlogin', $data['target_user_login']);
            $query = $this->db->get('membres');
            return $query && $query->num_rows() > 0;
        }

        return $target_type === 'all';
    }
}

/* End of file motd_model.php */
/* Location: ./application/models/motd_model.php */
