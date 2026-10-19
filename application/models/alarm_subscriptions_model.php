<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Abonnements aux alarmes (alarm_subscriptions, alarm_subscription_optouts)
 *
 * Un membre a toujours ses alarmes propres, sans abonnement. Un abonnement
 * porte sur une case de la matrice alarme × colonne : une section, ou la
 * colonne Club pour les occurrences sans section. Il relie l'alarme soit à
 * un membre, soit à une catégorie (rôle, éventuellement restreint à une
 * section). Les cases effectives d'un membre sont recalculées à chaque
 * usage : abonnements individuels + abonnements des catégories dont il
 * détient le rôle − ses retraits (sauf obligatoires), restreints aux cases
 * qu'il a le droit de superviser.
 *
 * Les droits de supervision sont évalués pour n'importe quel membre (pas
 * seulement l'utilisateur connecté) à partir de user_roles_per_section.
 *
 * @package models
 * @see doc/design_notes/gestion_alarmes_design.md
 */
class Alarm_subscriptions_model extends Common_Model {
    public $table = 'alarm_subscriptions';
    protected $primary_key = 'id';

    const OPTOUTS_TABLE = 'alarm_subscription_optouts';

    /** Colonne Club de la matrice : occurrences sans section */
    const CLUB = 0;

    /** Rôles qui peuvent superviser les documents de leurs sections */
    private static $supervisor_roles = array('ca', 'bureau');
    /** Rôles qui peuvent en plus superviser une alarme ciblant un rôle (ex. élèves) */
    private static $targeted_supervisor_roles = array('instructeur', 'rp');

    /** @var array|null login => [role => [section_id => true]] */
    private $roles_cache = null;

    /** @var array|null section_id => true */
    private $sections_cache = null;

    function __construct() {
        parent::__construct();
        $this->load->model('alarm_definitions_model');
        $this->lang->load('alarmes');
    }

    // ------------------------------------------------------------------
    // Rôles des membres
    // ------------------------------------------------------------------

    /**
     * Rôles non révoqués de tous les membres, par section
     *
     * @return array login => [role_nom => [section_id => true]] (section 0 = sans section)
     */
    public function all_member_roles() {
        if ($this->roles_cache === null) {
            $rows = $this->db->query(
                "SELECT u.username, tr.nom, urps.section_id
                 FROM user_roles_per_section urps
                 JOIN users u ON u.id = urps.user_id
                 JOIN types_roles tr ON tr.id = urps.types_roles_id
                 WHERE urps.revoked_at IS NULL"
            )->result_array();
            $this->roles_cache = array();
            foreach ($rows as $row) {
                $section = ($row['section_id'] === null) ? 0 : (int) $row['section_id'];
                $this->roles_cache[$row['username']][$row['nom']][$section] = true;
            }
        }
        return $this->roles_cache;
    }

    /** Oublie les rôles mémorisés (après une modification des rôles) */
    public function reset_roles_cache() {
        $this->roles_cache = null;
    }

    /**
     * Rôles d'un membre
     *
     * @param string $login
     * @return array role_nom => [section_id => true]
     */
    public function member_roles($login) {
        $all = $this->all_member_roles();
        return isset($all[$login]) ? $all[$login] : array();
    }

    /**
     * Membre actif : rôle user non révoqué dans au moins une section
     *
     * @param string $login
     * @return bool
     */
    public function is_active_member($login) {
        $roles = $this->member_roles($login);
        return !empty($roles['user']);
    }

    /**
     * Logins des membres actifs
     *
     * @return array
     */
    public function active_logins() {
        $logins = array();
        foreach ($this->all_member_roles() as $login => $roles) {
            if (!empty($roles['user'])) {
                $logins[] = $login;
            }
        }
        return $logins;
    }

    /**
     * Le membre détient-il le rôle dans la section (ou dans une section quelconque) ?
     *
     * @param string $login
     * @param string $role_nom
     * @param int|null $section_id null = n'importe quelle section
     * @return bool
     */
    public function has_role($login, $role_nom, $section_id = null) {
        $roles = $this->member_roles($login);
        if (empty($roles[$role_nom])) {
            return false;
        }
        return $section_id === null || isset($roles[$role_nom][(int) $section_id]);
    }

    /**
     * Sections dans lesquelles le membre détient l'un des rôles
     *
     * @param string $login
     * @param array $role_noms
     * @return array section_id => true (0 = sans section)
     */
    public function role_sections($login, $role_noms) {
        $roles = $this->member_roles($login);
        $sections = array();
        foreach ($role_noms as $nom) {
            if (!empty($roles[$nom])) {
                $sections += $roles[$nom];
            }
        }
        return $sections;
    }

    // ------------------------------------------------------------------
    // Droits
    // ------------------------------------------------------------------

    /**
     * Sections que le membre peut superviser pour une alarme
     *
     * club-admin : toutes ; ca et bureau : leurs sections ; instructeur et
     * rp en plus pour une alarme ciblant un rôle (ex. élèves). Types privés :
     * club-admin et bureau seulement.
     *
     * @param string $login
     * @param array $def Ligne du catalogue (Alarm_definitions_model::get_all)
     * @return array|string 'all' ou section_id => true (vide = aucune)
     */
    public function supervision_sections($login, $def) {
        if ($this->has_role($login, 'club-admin')) {
            return 'all';
        }
        if (!empty($def['type_private'])) {
            $roles = array('bureau');
        } else {
            $roles = self::$supervisor_roles;
            if (!empty($def['target_role_id'])) {
                $roles = array_merge($roles, self::$targeted_supervisor_roles);
            }
        }
        $sections = $this->role_sections($login, $roles);
        if (!empty($def['section_id'])) {
            $sections = isset($sections[(int) $def['section_id']])
                ? array((int) $def['section_id'] => true) : array();
        }
        return $sections;
    }

    /**
     * L'alarme a-t-elle une colonne Club ? Oui pour les documents qui ne sont
     * pas des documents de pilote (machine, section, club) : ils peuvent
     * n'être rattachés à aucune section.
     *
     * @param array $def
     * @return bool
     */
    public function has_club_column($def) {
        return $def['source'] === Alarm_definitions_model::SOURCE_DOCUMENT
            && isset($def['type_scope']) && $def['type_scope'] !== 'pilot';
    }

    /**
     * Identifiants de toutes les sections
     *
     * @return array section_id => true
     */
    public function all_sections() {
        if ($this->sections_cache === null) {
            $this->sections_cache = array();
            foreach ($this->db->select('id')->order_by('id')->get('sections')->result_array() as $row) {
                $this->sections_cache[(int) $row['id']] = true;
            }
        }
        return $this->sections_cache;
    }

    /**
     * Cases de la matrice auxquelles le membre peut s'abonner pour une alarme
     *
     * Une case par section qu'il peut superviser (restreinte à la section de
     * l'alarme), plus la colonne Club (clé CLUB) si l'alarme en a une et
     * qu'il supervise au moins une section.
     *
     * @param string $login
     * @param array $def
     * @return array section_id|CLUB => true (vide = aucune)
     */
    public function allowed_cells($login, $def) {
        if (empty($def['active']) || !$this->is_active_member($login)) {
            return array();
        }
        $sections = $this->supervision_sections($login, $def);
        if (empty($sections)) {
            return array();
        }
        if ($sections === 'all') {
            $sections = $this->all_sections();
            if (!empty($def['section_id'])) {
                $sections = isset($sections[(int) $def['section_id']])
                    ? array((int) $def['section_id'] => true) : array();
            }
        }
        $cells = array();
        foreach (array_keys($sections) as $section_id) {
            if ((int) $section_id !== self::CLUB) {
                $cells[(int) $section_id] = true;
            }
        }
        if ($this->has_club_column($def) && empty($def['section_id'])) {
            $cells[self::CLUB] = true;
        }
        return $cells;
    }

    /**
     * L'occurrence relève-t-elle de l'une des cases ?
     * Occurrence sans section : colonne Club ; sinon l'une de ses sections.
     *
     * @param array $item AlarmItem
     * @param array $cells section_id|CLUB => mixed
     * @return bool
     */
    public static function item_in_cells($item, $cells) {
        if (empty($item['section_ids'])) {
            return isset($cells[self::CLUB]);
        }
        foreach ($item['section_ids'] as $section_id) {
            if ((int) $section_id !== self::CLUB && isset($cells[(int) $section_id])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Alarmes du catalogue que le membre peut superviser, avec leurs cases
     *
     * @param string $login
     * @return array Lignes du catalogue, avec 'cells' (section_id|CLUB => true)
     */
    public function proposed_definitions($login) {
        $proposed = array();
        foreach ($this->alarm_definitions_model->get_all(true) as $def) {
            $def['cells'] = $this->allowed_cells($login, $def);
            if ($def['cells']) {
                $proposed[] = $def;
            }
        }
        return $proposed;
    }

    // ------------------------------------------------------------------
    // Cases effectives
    // ------------------------------------------------------------------

    /**
     * Cases couvertes par un abonnement de catégorie pour un membre
     *
     * @param array $sub Ligne d'abonnement, avec role_nom
     * @param string $login
     * @return array section_id|CLUB (vide si le membre n'a pas le rôle)
     */
    private function category_cells($sub, $login) {
        if ($sub['section_id'] !== null) {
            return $this->has_role($login, $sub['role_nom'], (int) $sub['section_id'])
                ? array((int) $sub['section_id']) : array();
        }
        $roles = $this->member_roles($login);
        if (empty($roles[$sub['role_nom']])) {
            return array();
        }
        $cells = array();
        foreach (array_keys($roles[$sub['role_nom']]) as $section_id) {
            if ((int) $section_id !== self::CLUB) {
                $cells[] = (int) $section_id;
            }
        }
        $cells[] = self::CLUB;
        return $cells;
    }

    /**
     * Cases effectives de tous les membres actifs
     *
     * Abonnements individuels ∪ abonnements des catégories dont le membre
     * détient le rôle − ses retraits (sauf obligatoires), restreints aux
     * cases autorisées.
     *
     * @return array login => [definition_id => [section_id|CLUB => ['subscription_id',
     *               'origin' (individual|category), 'mandatory', 'created_by']]]
     */
    public function effective_all() {
        $subscriptions = $this->db->query(
            "SELECT s.*, tr.nom AS role_nom FROM {$this->table} s
             LEFT JOIN types_roles tr ON tr.id = s.role_id"
        )->result_array();
        if (!$subscriptions) {
            return array();
        }
        $optouts = array();
        foreach ($this->db->get(self::OPTOUTS_TABLE)->result_array() as $row) {
            $cell = ($row['section_id'] === null) ? self::CLUB : (int) $row['section_id'];
            $optouts[$row['subscription_id']][$row['member_login']][$cell] = true;
        }
        $definitions = array();
        foreach ($this->alarm_definitions_model->get_all(true) as $def) {
            $definitions[$def['id']] = $def;
        }

        $effective = array();
        $allowed = array();
        $active = array_flip($this->active_logins());
        foreach ($subscriptions as $sub) {
            $def_id = $sub['alarm_definition_id'];
            if (!isset($definitions[$def_id])) {
                continue;
            }
            $cells_by_login = array();
            if (!empty($sub['subscriber_login'])) {
                if (isset($active[$sub['subscriber_login']])) {
                    $cells_by_login[$sub['subscriber_login']] =
                        array(($sub['section_id'] === null) ? self::CLUB : (int) $sub['section_id']);
                }
                $origin = 'individual';
            } else {
                foreach (array_keys($active) as $login) {
                    $cells = $this->category_cells($sub, $login);
                    if ($cells) {
                        $cells_by_login[$login] = $cells;
                    }
                }
                $origin = 'category';
            }
            foreach ($cells_by_login as $login => $cells) {
                if (!isset($allowed[$login][$def_id])) {
                    $allowed[$login][$def_id] = $this->allowed_cells($login, $definitions[$def_id]);
                }
                foreach ($cells as $cell) {
                    if (!isset($allowed[$login][$def_id][$cell])) {
                        continue;
                    }
                    if (!$sub['mandatory'] && isset($optouts[$sub['id']][$login][$cell])) {
                        continue;
                    }
                    $current = isset($effective[$login][$def_id][$cell]) ? $effective[$login][$def_id][$cell] : null;
                    // Un abonnement obligatoire l'emporte ; à défaut, l'individuel
                    if ($current && ($current['mandatory'] || (!$sub['mandatory'] && $current['origin'] === 'individual'))) {
                        continue;
                    }
                    $effective[$login][$def_id][$cell] = array(
                        'subscription_id' => (int) $sub['id'],
                        'origin' => $origin,
                        'mandatory' => (int) $sub['mandatory'],
                        'created_by' => $sub['created_by'],
                    );
                }
            }
        }
        return $effective;
    }

    /**
     * Cases effectives d'un membre
     *
     * @param string $login
     * @return array definition_id => [section_id|CLUB => voir effective_all()]
     */
    public function effective_for($login) {
        $all = $this->effective_all();
        return isset($all[$login]) ? $all[$login] : array();
    }

    /**
     * Abonnements de catégories qui couvrent le membre, retraits compris
     *
     * @param string $login
     * @return array definition_id => [section_id|CLUB => [subscription rows]]
     */
    private function category_subscriptions_for($login) {
        $rows = $this->db->query(
            "SELECT s.*, tr.nom AS role_nom FROM {$this->table} s
             JOIN types_roles tr ON tr.id = s.role_id
             WHERE s.subscriber_login IS NULL"
        )->result_array();
        $result = array();
        foreach ($rows as $row) {
            foreach ($this->category_cells($row, $login) as $cell) {
                $result[$row['alarm_definition_id']][$cell][] = $row;
            }
        }
        return $result;
    }

    // ------------------------------------------------------------------
    // Écriture
    // ------------------------------------------------------------------

    /**
     * Abonnement individuel à une case
     *
     * @param int $definition_id
     * @param string $login
     * @param int $cell Section, ou CLUB pour la colonne Club
     * @param bool $mandatory
     * @return array ['ok' => bool, 'error' => string|null]
     */
    public function subscribe($definition_id, $login, $cell, $mandatory = false) {
        $def = $this->alarm_definitions_model->get($definition_id);
        if (!$def) {
            return array('ok' => false, 'error' => $this->lang->line('alarm_error_unknown'));
        }
        $cell = (int) $cell;
        $allowed = $this->allowed_cells($login, $def);
        if (!isset($allowed[$cell])) {
            return array('ok' => false, 'error' => sprintf($this->lang->line('alarm_error_no_right'),
                $def['display_label'], $this->cell_name($cell), $login));
        }
        $this->db->where('alarm_definition_id', $definition_id)->where('subscriber_login', $login);
        $this->where_cell('section_id', $cell);
        $existing = $this->db->get($this->table)->row_array();
        if ($existing) {
            if ((int) $existing['mandatory'] !== ($mandatory ? 1 : 0)) {
                $this->update('id', array('id' => $existing['id'], 'mandatory' => $mandatory ? 1 : 0));
            }
            return array('ok' => true, 'error' => null);
        }
        $id = $this->create(array(
            'alarm_definition_id' => (int) $definition_id,
            'subscriber_login' => $login,
            'section_id' => ($cell === self::CLUB) ? null : $cell,
            'mandatory' => $mandatory ? 1 : 0,
        ));
        return array('ok' => (bool) $id, 'error' => $id ? null : $this->lang->line('alarm_error_save'));
    }

    /**
     * Abonnement d'une catégorie de membres (rôle, section facultative)
     *
     * @param int $definition_id
     * @param int $role_id
     * @param int|null $section_id null = toutes les sections du rôle, plus Club
     * @param bool $mandatory
     * @return array ['ok' => bool, 'error' => string|null]
     */
    public function subscribe_category($definition_id, $role_id, $section_id = null, $mandatory = false) {
        $def = $this->alarm_definitions_model->get($definition_id);
        if (!$def || !$role_id) {
            return array('ok' => false, 'error' => $this->lang->line('alarm_error_unknown'));
        }
        $this->db->where('alarm_definition_id', $definition_id)->where('role_id', $role_id)
            ->where('subscriber_login IS NULL', null, false);
        $this->where_cell('section_id', $section_id ? (int) $section_id : self::CLUB);
        if ($this->db->get($this->table)->row_array()) {
            return array('ok' => false, 'error' => $this->lang->line('alarm_error_duplicate'));
        }
        $id = $this->create(array(
            'alarm_definition_id' => (int) $definition_id,
            'role_id' => (int) $role_id,
            'section_id' => $section_id ? (int) $section_id : null,
            'mandatory' => $mandatory ? 1 : 0,
        ));
        return array('ok' => (bool) $id, 'error' => $id ? null : $this->lang->line('alarm_error_save'));
    }

    /**
     * Supprime un abonnement (individuel ou de catégorie) et ses retraits
     *
     * @param int $id
     */
    public function unsubscribe_by_id($id) {
        $this->delete(array('id' => (int) $id));
    }

    /**
     * Enregistre la matrice de la page Mes alarmes d'un membre
     *
     * Pour chaque case autorisée : cochée sans abonnement effectif →
     * abonnement individuel (ou levée du retrait d'une catégorie) ; décochée
     * avec abonnement effectif → suppression de l'abonnement individuel ou
     * retrait de la case de la catégorie. Les cases obligatoires ne peuvent
     * pas être décochées.
     *
     * @param string $login Membre configuré
     * @param array $checked definition_id => [section_id|CLUB, ...] cases cochées
     * @return array ['ok' => bool, 'errors' => string[]]
     */
    public function save_choices($login, $checked) {
        $effective = $this->effective_for($login);
        $categories = $this->category_subscriptions_for($login);
        $errors = array();

        foreach ($this->proposed_definitions($login) as $def) {
            $def_id = (int) $def['id'];
            $checked_cells = isset($checked[$def_id]) ? array_flip(array_map('intval', (array) $checked[$def_id])) : array();
            foreach (array_keys($def['cells']) as $cell) {
                $is_checked = isset($checked_cells[$cell]);
                $current = isset($effective[$def_id][$cell]) ? $effective[$def_id][$cell] : null;
                $cell_categories = isset($categories[$def_id][$cell]) ? $categories[$def_id][$cell] : array();

                if ($is_checked && !$current) {
                    if ($cell_categories) {
                        foreach ($cell_categories as $sub) {
                            $this->db->where('subscription_id', $sub['id'])->where('member_login', $login);
                            $this->where_cell('section_id', $cell);
                            $this->db->delete(self::OPTOUTS_TABLE);
                        }
                    } else {
                        $result = $this->subscribe($def_id, $login, $cell);
                        if (!$result['ok']) {
                            $errors[] = $result['error'];
                        }
                    }
                } elseif (!$is_checked && $current) {
                    if ($current['mandatory']) {
                        $errors[] = sprintf($this->lang->line('alarm_error_mandatory'),
                            $def['display_label'], $this->cell_name($cell));
                        continue;
                    }
                    $this->db->where('alarm_definition_id', $def_id)->where('subscriber_login', $login);
                    $this->where_cell('section_id', $cell);
                    $this->db->delete($this->table);
                    foreach ($cell_categories as $sub) {
                        $this->db->where('subscription_id', $sub['id'])->where('member_login', $login);
                        $this->where_cell('section_id', $cell);
                        if (!$this->db->count_all_results(self::OPTOUTS_TABLE)) {
                            $row = array('subscription_id' => $sub['id'], 'member_login' => $login,
                                'section_id' => ($cell === self::CLUB) ? null : $cell);
                            $this->_audit($row);
                            $this->db->insert(self::OPTOUTS_TABLE, $row);
                        }
                    }
                }
            }
        }
        return array('ok' => empty($errors), 'errors' => $errors);
    }

    /**
     * Abonnements de catégories, pour l'administration
     *
     * @return array
     */
    public function get_categories() {
        $rows = $this->db->query(
            "SELECT s.*, tr.nom AS role_nom, tr.translation_key AS role_key, sec.nom AS section_name
             FROM {$this->table} s
             JOIN types_roles tr ON tr.id = s.role_id
             LEFT JOIN sections sec ON sec.id = s.section_id
             WHERE s.subscriber_login IS NULL
             ORDER BY s.alarm_definition_id, tr.nom"
        )->result_array();
        $defs = array();
        foreach ($this->alarm_definitions_model->get_all(false) as $def) {
            $defs[$def['id']] = $def;
        }
        foreach ($rows as &$row) {
            $row['definition_label'] = isset($defs[$row['alarm_definition_id']])
                ? $defs[$row['alarm_definition_id']]['display_label'] : '';
            $row['role_label'] = ($row['role_key'] && $this->lang->line($row['role_key'])) ? $this->lang->line($row['role_key']) : $row['role_nom'];
        }
        return $rows;
    }

    /**
     * Nom d'une colonne de la matrice
     *
     * @param int $cell Section ou CLUB
     * @return string
     */
    public function cell_name($cell) {
        if ((int) $cell === self::CLUB) {
            return $this->lang->line('alarm_column_club');
        }
        $row = $this->db->select('nom')->where('id', (int) $cell)->get('sections')->row_array();
        return $row ? $row['nom'] : (string) $cell;
    }

    /** Condition sur une colonne : section, ou NULL pour la colonne Club */
    private function where_cell($column, $cell) {
        if ((int) $cell === self::CLUB) {
            $this->db->where("$column IS NULL", null, false);
        } else {
            $this->db->where($column, (int) $cell);
        }
    }

    private function _audit(&$row) {
        $user = $this->current_username();
        $now = date('Y-m-d H:i:s');
        $row['created_at'] = $now;
        $row['updated_at'] = $now;
        $row['created_by'] = $user;
        $row['updated_by'] = $user;
    }
}
