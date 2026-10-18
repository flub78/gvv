<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Abonnements aux alarmes (alarm_subscriptions, alarm_subscription_optouts)
 *
 * Un abonnement relie une alarme du catalogue soit à un membre, soit à une
 * catégorie (rôle, éventuellement restreint à une section). Les abonnements
 * effectifs d'un membre sont recalculés à chaque usage : abonnements
 * individuels + abonnements des catégories dont il détient le rôle −
 * ses retraits (sauf abonnements obligatoires).
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

    /** Rôles qui peuvent superviser les documents de leurs sections */
    private static $supervisor_roles = array('ca', 'bureau');
    /** Rôles qui peuvent en plus superviser une alarme ciblant un rôle (ex. élèves) */
    private static $targeted_supervisor_roles = array('instructeur', 'rp');

    /** @var array|null login => [role => [section_id => true]] */
    private $roles_cache = null;

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
     * Le membre peut-il voir cette occurrence d'alarme ?
     * Ses propres documents, plus ceux qu'il a le droit de superviser.
     *
     * @param string $login
     * @param array $def
     * @param array $item AlarmItem
     * @return bool
     */
    public function can_see_item($login, $def, $item) {
        if (!empty($item['pilot_login']) && $item['pilot_login'] === $login) {
            return true;
        }
        $sections = $this->supervision_sections($login, $def);
        if ($sections === 'all') {
            return true;
        }
        if (empty($sections)) {
            return false;
        }
        // Sans section (document club) : visible par tout superviseur
        if (empty($item['section_ids'])) {
            return true;
        }
        foreach ($item['section_ids'] as $section_id) {
            if (isset($sections[(int) $section_id])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Le membre peut-il s'abonner à cette alarme ?
     *
     * Documents de pilote et documents manquants : tout membre actif, pour
     * ses propres documents. Autres documents et alarmes restreintes à une
     * population (ex. élèves) : membres ayant le droit de les superviser.
     *
     * @param string $login
     * @param array $def
     * @return bool
     */
    public function can_subscribe($login, $def) {
        if (!$this->is_active_member($login) || empty($def['active'])) {
            return false;
        }
        $own_documents = $def['source'] === Alarm_definitions_model::SOURCE_MISSING_DOCUMENT
            || (isset($def['type_scope']) && $def['type_scope'] === 'pilot');
        if ($own_documents && empty($def['target_role_id'])) {
            return true;
        }
        $sections = $this->supervision_sections($login, $def);
        return $sections === 'all' || !empty($sections);
    }

    /**
     * Alarmes du catalogue que le membre peut choisir
     *
     * @param string $login
     * @return array Lignes du catalogue
     */
    public function proposed_definitions($login) {
        $proposed = array();
        foreach ($this->alarm_definitions_model->get_all(true) as $def) {
            if ($this->can_subscribe($login, $def)) {
                $proposed[] = $def;
            }
        }
        return $proposed;
    }

    // ------------------------------------------------------------------
    // Abonnements effectifs
    // ------------------------------------------------------------------

    /**
     * Abonnements effectifs de tous les membres actifs
     *
     * @return array login => [definition_id => ['subscription_id', 'origin' (individual|category),
     *               'mandatory', 'created_by', 'role_id', 'role_section_id']]
     */
    public function effective_all() {
        $subscriptions = $this->db->query(
            "SELECT s.*, tr.nom AS role_nom FROM {$this->table} s
             LEFT JOIN types_roles tr ON tr.id = s.role_id"
        )->result_array();
        $optouts = array();
        foreach ($this->db->get(self::OPTOUTS_TABLE)->result_array() as $row) {
            $optouts[$row['subscription_id']][$row['member_login']] = true;
        }

        $effective = array();
        $active = array_flip($this->active_logins());
        foreach ($subscriptions as $sub) {
            if (!empty($sub['subscriber_login'])) {
                $logins = isset($active[$sub['subscriber_login']]) ? array($sub['subscriber_login']) : array();
                $origin = 'individual';
            } else {
                $logins = array();
                $section = ($sub['role_section_id'] === null) ? null : (int) $sub['role_section_id'];
                foreach (array_keys($active) as $login) {
                    if ($this->has_role($login, $sub['role_nom'], $section)) {
                        $logins[] = $login;
                    }
                }
                $origin = 'category';
            }
            foreach ($logins as $login) {
                if (!$sub['mandatory'] && isset($optouts[$sub['id']][$login])) {
                    continue;
                }
                $current = isset($effective[$login][$sub['alarm_definition_id']])
                    ? $effective[$login][$sub['alarm_definition_id']] : null;
                // Un abonnement obligatoire l'emporte ; à défaut, l'individuel
                if ($current && ($current['mandatory'] || (!$sub['mandatory'] && $current['origin'] === 'individual'))) {
                    continue;
                }
                $effective[$login][$sub['alarm_definition_id']] = array(
                    'subscription_id' => (int) $sub['id'],
                    'origin' => $origin,
                    'mandatory' => (int) $sub['mandatory'],
                    'created_by' => $sub['created_by'],
                    'role_id' => $sub['role_id'],
                    'role_section_id' => $sub['role_section_id'],
                );
            }
        }
        return $effective;
    }

    /**
     * Abonnements effectifs d'un membre
     *
     * @param string $login
     * @return array definition_id => voir effective_all()
     */
    public function effective_for($login) {
        $all = $this->effective_all();
        return isset($all[$login]) ? $all[$login] : array();
    }

    /**
     * Le membre a-t-il au moins un abonnement effectif ?
     * Sert aussi à afficher « Mes alarmes » dans le menu et le tableau de bord.
     *
     * @param string $login
     * @return bool
     */
    public function has_effective_subscription($login) {
        if (empty($login) || !$this->db->count_all_results($this->table)) {
            return false; // appelé à chaque page (menu) : sortie rapide sans abonnement
        }
        return !empty($this->effective_for($login));
    }

    /**
     * Abonnements de catégories qui couvrent le membre, retraits compris
     *
     * @param string $login
     * @return array definition_id => [subscription rows]
     */
    private function category_subscriptions_for($login) {
        $rows = $this->db->query(
            "SELECT s.*, tr.nom AS role_nom FROM {$this->table} s
             JOIN types_roles tr ON tr.id = s.role_id
             WHERE s.subscriber_login IS NULL"
        )->result_array();
        $result = array();
        foreach ($rows as $row) {
            $section = ($row['role_section_id'] === null) ? null : (int) $row['role_section_id'];
            if ($this->has_role($login, $row['role_nom'], $section)) {
                $result[$row['alarm_definition_id']][] = $row;
            }
        }
        return $result;
    }

    // ------------------------------------------------------------------
    // Écriture
    // ------------------------------------------------------------------

    /**
     * Abonnement individuel
     *
     * @param int $definition_id
     * @param string $login
     * @param bool $mandatory
     * @return array ['ok' => bool, 'error' => string|null]
     */
    public function subscribe($definition_id, $login, $mandatory = false) {
        $def = $this->alarm_definitions_model->get($definition_id);
        if (!$def) {
            return array('ok' => false, 'error' => $this->lang->line('alarm_error_unknown'));
        }
        if (!$this->can_subscribe($login, $def)) {
            return array('ok' => false, 'error' => sprintf($this->lang->line('alarm_error_no_right'),
                $def['display_label'], $login));
        }
        $existing = $this->db->where('alarm_definition_id', $definition_id)
            ->where('subscriber_login', $login)->get($this->table)->row_array();
        if ($existing) {
            if ((int) $existing['mandatory'] !== ($mandatory ? 1 : 0)) {
                $this->update('id', array('id' => $existing['id'], 'mandatory' => $mandatory ? 1 : 0));
            }
            return array('ok' => true, 'error' => null);
        }
        $id = $this->create(array(
            'alarm_definition_id' => (int) $definition_id,
            'subscriber_login' => $login,
            'mandatory' => $mandatory ? 1 : 0,
        ));
        return array('ok' => (bool) $id, 'error' => $id ? null : $this->lang->line('alarm_error_save'));
    }

    /**
     * Abonnement d'une catégorie de membres (rôle, section facultative)
     *
     * @param int $definition_id
     * @param int $role_id
     * @param int|null $section_id
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
        if ($section_id) {
            $this->db->where('role_section_id', $section_id);
        } else {
            $this->db->where('role_section_id IS NULL', null, false);
        }
        if ($this->db->get($this->table)->row_array()) {
            return array('ok' => false, 'error' => $this->lang->line('alarm_error_duplicate'));
        }
        $id = $this->create(array(
            'alarm_definition_id' => (int) $definition_id,
            'role_id' => (int) $role_id,
            'role_section_id' => $section_id ? (int) $section_id : null,
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
     * Enregistre les cases cochées de la page de configuration d'un membre
     *
     * Pour chaque alarme proposée : cochée sans abonnement effectif →
     * abonnement individuel (ou levée du retrait d'une catégorie) ; décochée
     * avec abonnement effectif → suppression de l'abonnement individuel ou
     * retrait de la catégorie. Les abonnements obligatoires ne peuvent pas
     * être décochés.
     *
     * @param string $login Membre configuré
     * @param array $checked_ids Identifiants des alarmes cochées
     * @return array ['ok' => bool, 'errors' => string[]]
     */
    public function save_choices($login, $checked_ids) {
        $checked = array_flip(array_map('intval', (array) $checked_ids));
        $effective = $this->effective_for($login);
        $categories = $this->category_subscriptions_for($login);
        $errors = array();

        foreach ($this->proposed_definitions($login) as $def) {
            $def_id = (int) $def['id'];
            $is_checked = isset($checked[$def_id]);
            $is_effective = isset($effective[$def_id]);

            if ($is_checked && !$is_effective) {
                if (!empty($categories[$def_id])) {
                    foreach ($categories[$def_id] as $sub) {
                        $this->db->where('subscription_id', $sub['id'])->where('member_login', $login)
                            ->delete(self::OPTOUTS_TABLE);
                    }
                } else {
                    $result = $this->subscribe($def_id, $login);
                    if (!$result['ok']) {
                        $errors[] = $result['error'];
                    }
                }
            } elseif (!$is_checked && $is_effective) {
                if ($effective[$def_id]['mandatory']) {
                    $errors[] = sprintf($this->lang->line('alarm_error_mandatory'), $def['display_label']);
                    continue;
                }
                $this->db->where('alarm_definition_id', $def_id)->where('subscriber_login', $login)
                    ->delete($this->table);
                if (!empty($categories[$def_id])) {
                    foreach ($categories[$def_id] as $sub) {
                        $exists = $this->db->where('subscription_id', $sub['id'])->where('member_login', $login)
                            ->count_all_results(self::OPTOUTS_TABLE);
                        if (!$exists) {
                            $row = array('subscription_id' => $sub['id'], 'member_login' => $login);
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
             LEFT JOIN sections sec ON sec.id = s.role_section_id
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

    private function _audit(&$row) {
        $user = $this->current_username();
        $now = date('Y-m-d H:i:s');
        $row['created_at'] = $now;
        $row['updated_at'] = $now;
        $row['created_by'] = $user;
        $row['updated_by'] = $user;
    }
}
