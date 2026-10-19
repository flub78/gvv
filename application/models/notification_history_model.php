<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Historique des notifications d'alarmes (table notification_history)
 *
 * Un enregistrement par envoi. Sert à l'idempotence (rien n'est renvoyé
 * pour une période déjà traitée), à l'anti-spam (une occurrence n'est
 * notifiée qu'une fois dans un état donné) et à la traçabilité.
 *
 * @package models
 * @see doc/design_notes/gestion_alarmes_design.md
 */
class Notification_history_model extends Common_Model {
    public $table = 'notification_history';
    protected $primary_key = 'id';

    function __construct() {
        parent::__construct();
    }

    /**
     * Enregistre un envoi
     *
     * @param array $data recipient_login, channel, kind, status ; facultatifs :
     *                    alarm_definition_id, occurrence_key, occurrence_state, period_key, error
     * @return int|false
     */
    public function record($data) {
        $data['sent_at'] = date('Y-m-d H:i:s');
        if (!isset($data['created_by'])) {
            $data['created_by'] = 'alarm_scheduler';
            $data['updated_by'] = 'alarm_scheduler';
        }
        return $this->create($data);
    }

    /**
     * Une synthèse a-t-elle déjà été envoyée sur ce canal pour cette période ?
     *
     * @param string $login
     * @param string $channel
     * @param string $period_key
     * @return bool
     */
    public function digest_sent($login, $channel, $period_key) {
        return $this->db->where('recipient_login', $login)->where('channel', $channel)
            ->where('kind', 'digest')->where('period_key', $period_key)->where('status', 'sent')
            ->count_all_results($this->table) > 0;
    }

    /**
     * Occurrences déjà notifiées au membre, par état
     *
     * @param string $login
     * @return array occurrence_key => [state => true]
     */
    public function notified_occurrences($login) {
        $rows = $this->db->select('occurrence_key, occurrence_state')
            ->where('recipient_login', $login)->where('kind', 'trigger')->where('status', 'sent')
            ->where('occurrence_key IS NOT NULL', null, false)
            ->get($this->table)->result_array();
        $result = array();
        foreach ($rows as $row) {
            $result[$row['occurrence_key']][$row['occurrence_state']] = true;
        }
        return $result;
    }
}
