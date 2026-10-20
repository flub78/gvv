<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Désactivations d'occurrences d'alarmes (table alarm_dismissals)
 *
 * Une désactivation porte sur une occurrence (AlarmItem.key), pour un membre
 * ou pour tout le monde (member_login NULL), définitive ou jusqu'à une date
 * incluse. Elle est en cours tant qu'elle n'est pas réactivée, que sa date
 * de fin n'est pas dépassée et que l'état et l'échéance de l'occurrence sont
 * ceux de la désactivation ; sinon elle est réactivée automatiquement
 * (reactivated_by NULL). Les lignes réactivées sont conservées.
 *
 * Droits : pour soi, sur une occurrence non propre ; pour tout le monde et
 * toute réactivation d'un autre, club-admin (vérifié par l'appelant, qui
 * connaît le lecteur et les occurrences du membre).
 *
 * @package models
 * @see doc/design_notes/gestion_alarmes_design.md (Lot 4)
 */
class Alarm_dismissals_model extends Common_Model {
    public $table = 'alarm_dismissals';
    protected $primary_key = 'id';

    /** @var string Date de référence (Y-m-d), modifiable pour les tests */
    private $today;

    function __construct() {
        parent::__construct();
        $this->lang->load('alarmes');
        $this->today = date('Y-m-d');
    }

    /**
     * Fixe la date de référence (tests)
     *
     * @param string $date Y-m-d
     */
    public function set_today($date) {
        $this->today = $date;
    }

    /**
     * Désactivations non réactivées
     *
     * @return array alarm_key => lignes
     */
    public function pending_by_key() {
        $result = array();
        foreach ($this->db->where('reactivated_at IS NULL', null, false)->order_by('id')
                     ->get($this->table)->result_array() as $row) {
            $result[$row['alarm_key']][] = $row;
        }
        return $result;
    }

    /**
     * Une désactivation non réactivée vaut-elle encore pour l'occurrence ?
     *
     * @param array $row Ligne alarm_dismissals
     * @param array $item AlarmItem
     * @param string $today Y-m-d
     * @return bool
     */
    public static function still_valid($row, $item, $today) {
        if (!empty($row['until_date']) && $row['until_date'] < $today) {
            return false;
        }
        return $row['state_snapshot'] === $item['state']
            && (string) $row['due_date_snapshot'] === (string) $item['due_date'];
    }

    /**
     * Sépare les occurrences d'un lecteur en visibles et désactivées.
     * Les désactivations qui ne valent plus sont réactivées.
     *
     * @param string $login Lecteur
     * @param array $occurrences key => AlarmItem (avec 'own')
     * @param array|null $pending Résultat de pending_by_key() (null = lu ici)
     * @return array array(visibles key => AlarmItem, désactivées key => AlarmItem + 'dismissal')
     */
    public function split($login, $occurrences, $pending = null) {
        if ($pending === null) {
            $pending = $this->pending_by_key();
        }
        $visible = array();
        $dismissed = array();
        foreach ($occurrences as $key => $item) {
            $applies = null;
            if (isset($pending[$key])) {
                foreach ($pending[$key] as $row) {
                    $for_reader = ($row['member_login'] === null)
                        || ($row['member_login'] === $login && empty($item['own']));
                    if (!$for_reader) {
                        continue;
                    }
                    if (!self::still_valid($row, $item, $this->today)) {
                        $this->reactivate($row['id'], null);
                        continue;
                    }
                    // Une désactivation pour tout le monde prime sur celle du membre
                    if (!$applies || $row['member_login'] === null) {
                        $applies = $row;
                    }
                }
            }
            if ($applies) {
                $item['dismissal'] = $applies;
                $dismissed[$key] = $item;
            } else {
                $visible[$key] = $item;
            }
        }
        return array($visible, $dismissed);
    }

    /**
     * Clés désactivées pour tout le monde (liste des alarmes que personne ne reçoit)
     *
     * @param array $items AlarmItem[]
     * @return array key => true
     */
    public function global_keys($items) {
        $pending = $this->pending_by_key();
        $keys = array();
        foreach ($items as $item) {
            if (empty($pending[$item['key']])) {
                continue;
            }
            foreach ($pending[$item['key']] as $row) {
                if ($row['member_login'] === null && self::still_valid($row, $item, $this->today)) {
                    $keys[$item['key']] = true;
                }
            }
        }
        return $keys;
    }

    /**
     * Désactive une occurrence
     *
     * @param array $item AlarmItem (avec 'own' pour le membre)
     * @param string|null $member_login Membre, null = pour tout le monde
     * @param string|null $until_date Y-m-d, null = définitive
     * @param string $reason Obligatoire pour tout le monde
     * @return array ['ok' => bool, 'error' => string|null]
     */
    public function dismiss($item, $member_login, $until_date, $reason) {
        $reason = trim((string) $reason);
        if ($member_login !== null && !empty($item['own'])) {
            return array('ok' => false, 'error' => $this->lang->line('alarm_dismiss_error_own'));
        }
        if ($member_login === null && $reason === '') {
            return array('ok' => false, 'error' => $this->lang->line('alarm_dismiss_error_reason'));
        }
        if ($until_date !== null && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $until_date) || $until_date < $this->today)) {
            return array('ok' => false, 'error' => $this->lang->line('alarm_dismiss_error_date'));
        }

        // Une seule désactivation en cours par occurrence et par portée
        $this->db->where('alarm_key', $item['key'])->where('reactivated_at IS NULL', null, false);
        if ($member_login === null) {
            $this->db->where('member_login IS NULL', null, false);
        } else {
            $this->db->where('member_login', $member_login);
        }
        $user = $this->current_username();
        $now = date('Y-m-d H:i:s');
        $this->db->update($this->table, array('reactivated_at' => $now, 'reactivated_by' => $user,
            'updated_at' => $now, 'updated_by' => $user));

        $label = trim($item['label'] . (isset($item['detail']) && $item['detail'] !== '' ? ' — ' . $item['detail'] : ''));
        $ok = $this->db->insert($this->table, array(
            'alarm_definition_id' => (int) $item['definition_id'],
            'alarm_key' => $item['key'],
            'label' => mb_substr($label, 0, 255),
            'member_login' => $member_login,
            'until_date' => $until_date,
            'reason' => ($reason === '') ? null : mb_substr($reason, 0, 255),
            'state_snapshot' => $item['state'],
            'due_date_snapshot' => $item['due_date'] ?: null,
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => $user,
            'updated_by' => $user,
        ));
        return array('ok' => (bool) $ok, 'error' => $ok ? null : $this->lang->line('alarm_error_save'));
    }

    /**
     * Réactive une désactivation
     *
     * @param int $id
     * @param string|null $by Auteur, null = réactivation automatique
     * @return bool
     */
    public function reactivate($id, $by) {
        $now = date('Y-m-d H:i:s');
        $this->db->where('id', (int) $id)->where('reactivated_at IS NULL', null, false)
            ->update($this->table, array('reactivated_at' => $now, 'reactivated_by' => $by,
                'updated_at' => $now, 'updated_by' => $by));
        return $this->db->affected_rows() > 0;
    }

    /**
     * Réactive les désactivations des alarmes listées dont l'occurrence n'existe plus
     *
     * @param array $definition_ids Alarmes actives du catalogue
     * @param array $keys key => true, occurrences existantes de ces alarmes
     * @return int Nombre de désactivations réactivées
     */
    public function reactivate_missing($definition_ids, $keys) {
        if (!$definition_ids) {
            return 0;
        }
        $count = 0;
        $rows = $this->db->select('id, alarm_key')->where('reactivated_at IS NULL', null, false)
            ->where_in('alarm_definition_id', array_map('intval', $definition_ids))
            ->get($this->table)->result_array();
        foreach ($rows as $row) {
            if (!isset($keys[$row['alarm_key']]) && $this->reactivate($row['id'], null)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Désactivations non réactivées, avec le nom du membre et de l'auteur
     * (administration)
     *
     * @return array
     */
    public function pending_list() {
        $this->db->where('reactivated_at IS NULL', null, false)->where('until_date <', $this->today)
            ->update($this->table, array('reactivated_at' => date('Y-m-d H:i:s')));
        return $this->db->select('d.*, a.label AS definition_label_ref, a.code AS definition_code,
                    m.mnom, m.mprenom, c.mnom AS author_nom, c.mprenom AS author_prenom', false)
            ->from($this->table . ' d')
            ->join('alarm_definitions a', 'a.id = d.alarm_definition_id')
            ->join('membres m', 'm.mlogin = d.member_login', 'left')
            ->join('membres c', 'c.mlogin = d.created_by', 'left')
            ->where('d.reactivated_at IS NULL', null, false)
            ->order_by('d.created_at', 'desc')
            ->get()->result_array();
    }
}
