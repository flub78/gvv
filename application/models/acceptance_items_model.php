<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Model for acceptance_items table
 *
 * Manages elements to be accepted (documents, training, checks, briefings, authorizations).
 *
 * @package models
 * @see application/migrations/068_acceptance_system.php
 */
class Acceptance_items_model extends Common_Model {
    public $table = 'acceptance_items';
    protected $primary_key = 'id';

    // motd_messages.source_type tag for messages generated from an item's
    // targeting (see sync_target_motd())
    const MOTD_SOURCE_TYPE = 'acceptance_item';

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();
    }

    /**
     * Returns paginated list for display
     * @param int $per_page Number of items per page
     * @param int $premier Offset
     * @param array $selection Filter criteria
     * @return array
     */
    public function select_page($per_page = 0, $premier = 0, $selection = []) {
        $this->db->select('acceptance_items.*,
            membres.mnom as created_by_nom, membres.mprenom as created_by_prenom');
        $this->db->from($this->table);
        $this->db->join('membres', 'acceptance_items.created_by = membres.mlogin', 'left');

        if (!empty($selection)) {
            $this->db->where($selection);
        }

        $this->db->order_by('acceptance_items.created_at', 'desc');

        $query = $this->db->get();
        $select = $this->get_to_array($query);

        // Add computed display name for creator
        foreach ($select as &$row) {
            $row['created_by_name'] = trim(
                (isset($row['created_by_prenom']) ? $row['created_by_prenom'] : '') . ' ' .
                (isset($row['created_by_nom']) ? $row['created_by_nom'] : '')
            );
        }

        $this->gvvmetadata->store_table("vue_acceptance_items", $select);
        return $select;
    }

    /**
     * Get active items, optionally filtered by category
     * @param string|null $category Category filter
     * @return array
     */
    public function get_active_items($category = null) {
        $this->db->where('active', 1);
        if ($category !== null) {
            $this->db->where('category', $category);
        }
        $this->db->order_by('title', 'asc');
        $query = $this->db->get($this->table);
        return $this->get_to_array($query);
    }

    /**
     * Get active items targeting this user, either individually
     * (target_user_login) or through one or more roles held in a matching
     * section (acceptance_item_roles, section_id NULL = all sections) — the
     * two are exclusive per item (cf. formulaire admin). Items with no
     * targeting restriction at all (target_user_login NULL and no
     * acceptance_item_roles rows) apply to everyone.
     * @param string $user_login User login
     * @return array Active items eligible for this user
     */
    public function get_items_for_user($user_login) {
        $login = $this->db->escape($user_login);
        $sql = "SELECT DISTINCT acceptance_items.*
            FROM acceptance_items
            WHERE acceptance_items.active = 1
            AND (
                acceptance_items.target_user_login = $login
                OR (
                    (acceptance_items.target_user_login IS NULL OR acceptance_items.target_user_login = '')
                    AND (
                        NOT EXISTS (
                            SELECT 1 FROM acceptance_item_roles air
                            WHERE air.item_id = acceptance_items.id
                        )
                        OR EXISTS (
                            SELECT 1 FROM acceptance_item_roles air
                            JOIN user_roles_per_section urps
                                ON urps.types_roles_id = air.types_roles_id
                                AND (air.section_id IS NULL OR urps.section_id = air.section_id)
                            JOIN users u ON u.id = urps.user_id
                            WHERE air.item_id = acceptance_items.id
                            AND u.username = $login
                            AND urps.revoked_at IS NULL
                        )
                    )
                )
            )
            ORDER BY acceptance_items.title ASC";

        $query = $this->db->query($sql);
        return $this->get_to_array($query);
    }

    /**
     * Items in get_items_for_user() the user has not yet accepted or refused
     * (drives the member dashboard and the pending-count badge).
     * @param string $user_login User login
     * @return array
     */
    public function get_pending_items_for_user($user_login) {
        $items = $this->get_items_for_user($user_login);
        if (empty($items)) {
            return array();
        }

        $item_ids = array_column($items, 'id');
        $this->db->select('item_id');
        $this->db->from('acceptance_records');
        $this->db->where_in('item_id', $item_ids);
        $this->db->where('user_login', $user_login);
        $this->db->where_in('status', array('accepted', 'refused'));
        $handled_ids = array_column($this->db->get()->result_array(), 'item_id');

        return array_values(array_filter($items, function ($item) use ($handled_ids) {
            return !in_array($item['id'], $handled_ids);
        }));
    }

    /**
     * Ensure a message du jour exists for every person currently targeted by
     * this item and not yet accepted/refused, matching the item's current
     * targeting and mandatory_level (Lot 3d.4 — the message du jour is the
     * default notification channel, cf. PRD "Canal de notification").
     *
     * Full replace on every call (delete then regenerate): admins can edit
     * targeting/deadline/obligation freely, and messages are cheap to
     * recreate, so this is simpler and more robust than diffing old vs new
     * targets. Always resolves to one message per person, never
     * target_type='all' — a shared broadcast row could not become
     * dismissible for one person without affecting everyone else, breaking
     * the per-person "cannot hide until validated" rule (Lot 3d.3) for
     * mandatory items.
     *
     * @param int $item_id
     */
    public function sync_target_motd($item_id) {
        $this->clear_target_motd($item_id);

        $item = $this->get_by_id('id', $item_id);
        if (!$item || empty($item['active'])) {
            return;
        }

        $targets = $this->resolve_targets($item);
        if (empty($targets)) {
            return;
        }

        $this->db->select('user_login');
        $this->db->from('acceptance_records');
        $this->db->where('item_id', $item_id);
        $this->db->where_in('status', array('accepted', 'refused'));
        $handled = array_column($this->db->get()->result_array(), 'user_login');

        $pending_targets = array_diff($targets, $handled);
        if (empty($pending_targets)) {
            return;
        }

        $this->load->model('motd_model');
        $this->lang->load('acceptance');

        $mandatory_level = isset($item['mandatory_level']) ? $item['mandatory_level'] : 'optional';
        $level = ($mandatory_level === 'mandatory_hard') ? 'urgent'
            : (($mandatory_level === 'mandatory_soft') ? 'important' : 'info');

        $url = site_url('acceptance/read/' . $item_id);
        $content = !empty($item['description'])
            ? sprintf($this->lang->line('acceptance_motd_content_with_description'), $item['title'], $item['description'], $url)
            : sprintf($this->lang->line('acceptance_motd_content'), $item['title'], $url);
        $base = array(
            'title' => sprintf($this->lang->line('acceptance_motd_title'), $item['title']),
            'content' => $content,
            'level' => $level,
            // Dismissible mirrors Lot 3d.3: an optional item can be hidden
            // freely, a mandatory one (soft or hard) cannot be hidden until
            // the person accepts/refuses (which removes this row entirely).
            'dismissible' => ($mandatory_level === 'optional') ? 1 : 0,
            'end_date' => !empty($item['deadline'])
                ? $item['deadline'] . ' 23:59:59'
                : date('Y-m-d H:i:s', strtotime('+1 year')),
            'source_type' => self::MOTD_SOURCE_TYPE,
            'source_ref' => (string) $item_id,
            'created_by' => 'system',
        );

        foreach ($pending_targets as $login) {
            $this->motd_model->generate_system_message(array_merge($base, array(
                'target_type' => 'user',
                'target_user_login' => $login,
            )));
        }
    }

    /**
     * Remove every message du jour generated for this item's targeting
     * (cascades to motd_user_message_state). Safe to call even if none exist.
     * @param int $item_id
     */
    public function clear_target_motd($item_id) {
        $this->db->where('source_type', self::MOTD_SOURCE_TYPE);
        $this->db->where('source_ref', $item_id);
        $this->db->delete('motd_messages');
    }

    /**
     * Remove only $user_login's own message for this item (they just
     * accepted or refused; others targeted by the same item are unaffected).
     * @param int $item_id
     * @param string $user_login
     */
    public function clear_target_motd_for_user($item_id, $user_login) {
        $this->db->where('source_type', self::MOTD_SOURCE_TYPE);
        $this->db->where('source_ref', $item_id);
        $this->db->where('target_type', 'user');
        $this->db->where('target_user_login', $user_login);
        $this->db->delete('motd_messages');
    }

    /**
     * Drop any acceptance motd notification $user_login is no longer
     * eligible for. Notifications link to acceptance/read/<item_id>, which
     * denies access once the user holds neither the item's targeting nor an
     * existing personal record (cf. Acceptance::_get_authorized_item()) —
     * so a role change that drops eligibility would otherwise leave a dead
     * link sitting in the message du jour indefinitely. Called after any
     * role revocation, regardless of which role/section, since eligibility
     * can combine several roles (cf. get_items_for_user()); cheap no-op
     * when the user has no acceptance notification at all.
     * @param string $user_login
     */
    public function clear_dangling_motd_for_user($user_login) {
        $this->db->select('source_ref');
        $this->db->distinct();
        $this->db->from('motd_messages');
        $this->db->where('source_type', self::MOTD_SOURCE_TYPE);
        $this->db->where('target_type', 'user');
        $this->db->where('target_user_login', $user_login);
        $notified_ids = array_column($this->db->get()->result_array(), 'source_ref');
        if (empty($notified_ids)) {
            return;
        }

        $eligible_ids = array_map('strval', array_column($this->get_items_for_user($user_login), 'id'));

        foreach (array_diff($notified_ids, $eligible_ids) as $item_id) {
            $this->clear_target_motd_for_user((int) $item_id, $user_login);
        }
    }

    /**
     * Resolve an item's targeting to a flat list of member logins: the
     * individual target_user_login, or every member reached by its
     * acceptance_item_roles rows (role x section, mirroring
     * get_items_for_user()'s targeting rules), or — when neither is set —
     * every active member club-wide (an unrestricted item applies to
     * everyone).
     *
     * Activity is checked with Membres_model::actif_dans_au_moins_une_section()
     * (the legacy membres.actif flag is being phased out) for every case
     * except an individually targeted user, who is always included regardless
     * of their activity status.
     * @param array $item acceptance_items row
     * @return string[] Unique mlogin values
     */
    public function resolve_targets($item) {
        if (!empty($item['target_user_login'])) {
            return array($item['target_user_login']);
        }

        $this->load->model('membres_model');
        $this->load->model('acceptance_item_roles_model');
        $roles = $this->acceptance_item_roles_model->get_for_item($item['id']);

        $logins = array();

        if (empty($roles)) {
            $rows = $this->db->select('mlogin')->get('membres')->result_array();
            foreach ($rows as $row) {
                if ($this->membres_model->actif_dans_au_moins_une_section($row['mlogin'])) {
                    $logins[$row['mlogin']] = true;
                }
            }
            return array_keys($logins);
        }

        $this->load->model('email_lists_model');
        foreach ($roles as $role) {
            $members = $this->email_lists_model->get_users_by_role_and_section(
                $role['types_roles_id'], $role['section_id'], 'all', false
            );
            foreach ($members as $member) {
                if (!empty($member['mlogin'])
                    && $this->membres_model->actif_dans_au_moins_une_section($member['mlogin'])) {
                    $logins[$member['mlogin']] = true;
                }
            }
        }
        return array_keys($logins);
    }

    /**
     * Get overdue items (deadline passed, still active)
     * @return array
     */
    public function get_overdue_items() {
        $this->db->where('active', 1);
        $this->db->where('deadline IS NOT NULL', null, false);
        $this->db->where('deadline <', date('Y-m-d'));
        $this->db->order_by('deadline', 'asc');
        $query = $this->db->get($this->table);
        return $this->get_to_array($query);
    }

    /**
     * Human-readable identifier for selectors
     * @param mixed $key Primary key value
     * @return string
     */
    public function image($key) {
        if ($key == "") return "";

        $vals = $this->get_by_id('id', $key);
        if ($vals && array_key_exists('title', $vals)) {
            return $vals['title'];
        }
        return "element inconnu $key";
    }

    /**
     * Returns selector array for dropdown
     * @param array $where Additional where conditions
     * @return array
     */
    public function selector($where = array(), $order = "asc", $filter_section = false) {
        $this->db->select('id, title');
        $this->db->from($this->table);
        if (!empty($where)) {
            $this->db->where($where);
        }
        $this->db->where('active', 1);
        $this->db->order_by('title', $order);
        $query = $this->db->get();
        $rows = $this->get_to_array($query);

        $result = array('' => '');
        foreach ($rows as $row) {
            $result[$row['id']] = $row['title'];
        }
        return $result;
    }
}

/* End of file acceptance_items_model.php */
/* Location: ./application/models/acceptance_items_model.php */
