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
 * (reactivated_by NULL), à la lecture des alarmes d'un lecteur ou au passage
 * du scheduler (close_obsolete()). Les lignes réactivées sont conservées.
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

    /** @var bool|null Table présente (migration 180 appliquée), null = pas encore vérifié */
    private $available = null;

    function __construct() {
        parent::__construct();
        $this->lang->load('alarmes');
        $this->load->helper('views'); // alarm_item_title()
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
     * La table existe-t-elle ? Sans elle (code déployé avant la migration
     * 180), aucune occurrence n'est désactivée et les alarmes fonctionnent
     * comme avant.
     *
     * @return bool
     */
    public function available() {
        if ($this->available === null) {
            $this->available = (bool) $this->db->table_exists($this->table);
        }
        return $this->available;
    }

    /**
     * Désactivations non réactivées
     *
     * @return array alarm_key => lignes
     */
    public function pending_by_key() {
        $result = array();
        if (!$this->available()) {
            return $result;
        }
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
     * @param string|null $login Lecteur ; null = désactivations pour tout le monde seulement
     * @param array $occurrences key => AlarmItem (avec 'own' pour le lecteur)
     * @return array array(visibles key => AlarmItem, désactivées key => AlarmItem + 'dismissal')
     */
    public function split($login, $occurrences) {
        $pending = $this->pending_by_key();
        $visible = array();
        $dismissed = array();
        foreach ($occurrences as $key => $item) {
            $applies = null;
            foreach (isset($pending[$key]) ? $pending[$key] : array() as $row) {
                $for_reader = ($row['member_login'] === null)
                    || ($login !== null && $row['member_login'] === $login && empty($item['own']));
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
        $by_key = array();
        foreach ($items as $item) {
            $by_key[$item['key']] = $item;
        }
        list(, $dismissed) = $this->split(null, $by_key);
        return array_fill_keys(array_keys($dismissed), true);
    }

    /**
     * Désactive une occurrence. Une désactivation déjà en cours pour la même
     * occurrence et la même portée est remplacée (mise à jour).
     *
     * @param array $item AlarmItem (avec 'own' pour le membre)
     * @param string|null $member_login Membre, null = pour tout le monde
     * @param string|null $until_date Y-m-d, null = définitive
     * @param string $reason Obligatoire pour tout le monde
     * @return array ['ok' => bool, 'error' => string|null]
     */
    public function dismiss($item, $member_login, $until_date, $reason) {
        $reason = trim((string) $reason);
        if (!$this->available()) {
            return array('ok' => false, 'error' => $this->lang->line('alarm_error_save'));
        }
        if ($member_login !== null && !empty($item['own'])) {
            return array('ok' => false, 'error' => $this->lang->line('alarm_dismiss_error_own'));
        }
        if ($member_login === null && $reason === '') {
            return array('ok' => false, 'error' => $this->lang->line('alarm_dismiss_error_reason'));
        }
        if ($until_date !== null && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $until_date) || $until_date < $this->today)) {
            return array('ok' => false, 'error' => $this->lang->line('alarm_dismiss_error_date'));
        }

        $user = $this->current_username();
        $now = date('Y-m-d H:i:s');
        $row = array(
            'label' => mb_substr(alarm_item_title($item), 0, 255),
            'until_date' => $until_date,
            'reason' => ($reason === '') ? null : mb_substr($reason, 0, 255),
            'state_snapshot' => $item['state'],
            'due_date_snapshot' => $item['due_date'] ?: null,
            'updated_at' => $now,
            'updated_by' => $user,
        );

        $this->db->select('id')->where('alarm_key', $item['key'])->where('reactivated_at IS NULL', null, false);
        if ($member_login === null) {
            $this->db->where('member_login IS NULL', null, false);
        } else {
            $this->db->where('member_login', $member_login);
        }
        $existing = $this->db->get($this->table)->row_array();
        if ($existing) {
            $ok = $this->db->where('id', $existing['id'])->update($this->table, $row);
        } else {
            $ok = $this->db->insert($this->table, $row + array(
                'alarm_definition_id' => (int) $item['definition_id'],
                'alarm_key' => $item['key'],
                'member_login' => $member_login,
                'created_at' => $now,
                'created_by' => $user,
            ));
        }
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
     * Réactive les désactivations qui ne valent plus, sans attendre qu'un
     * lecteur affiche ses alarmes (scheduler, administration) : date de fin
     * dépassée ; pour les alarmes actives du catalogue, occurrence disparue
     * (condition résolue) ou dont l'état ou l'échéance a changé.
     *
     * @param array $definition_ids Alarmes actives du catalogue
     * @param array $items_by_key key => AlarmItem, occurrences de ces alarmes
     * @return int Nombre de désactivations réactivées
     */
    public function close_obsolete($definition_ids, $items_by_key) {
        $active = array_flip(array_map('intval', $definition_ids));
        $count = 0;
        foreach ($this->pending_by_key() as $key => $rows) {
            foreach ($rows as $row) {
                if (!empty($row['until_date']) && $row['until_date'] < $this->today) {
                    $obsolete = true;
                } elseif (isset($active[(int) $row['alarm_definition_id']])) {
                    $obsolete = !isset($items_by_key[$key]) || !self::still_valid($row, $items_by_key[$key], $this->today);
                } else {
                    $obsolete = false; // alarme inactive : occurrences non calculées
                }
                if ($obsolete && $this->reactivate($row['id'], null)) {
                    $count++;
                }
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
        if (!$this->available()) {
            return array();
        }
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
