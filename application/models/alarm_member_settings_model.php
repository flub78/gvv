<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('common_model');

/**
 * Réglages des alarmes par membre (table alarm_member_settings)
 *
 * Canaux (email, message du jour), rappel SMS et fréquence, communs à toutes
 * les alarmes du membre. Aucun canal n'est présélectionné : sans réglage
 * enregistré, le membre n'a aucun canal.
 *
 * @package models
 * @see doc/design_notes/gestion_alarmes_design.md
 */
class Alarm_member_settings_model extends Common_Model {
    public $table = 'alarm_member_settings';
    protected $primary_key = 'member_login';

    const FREQ_IMMEDIATE = 'immediate';
    const FREQ_WEEKLY = 'weekly';
    const FREQ_MONTHLY = 'monthly';

    function __construct() {
        parent::__construct();
    }

    /**
     * Fréquences possibles
     *
     * @return array
     */
    public static function frequencies() {
        return array(self::FREQ_IMMEDIATE, self::FREQ_WEEKLY, self::FREQ_MONTHLY);
    }

    /**
     * Réglages d'un membre, valeurs par défaut s'il n'en a pas
     *
     * @param string $login
     * @return array channel_email, channel_motd, sms, frequency, saved (bool)
     */
    public function get_for($login) {
        $row = $this->db->where('member_login', $login)->get($this->table)->row_array();
        if (!$row) {
            return array(
                'member_login' => $login,
                'channel_email' => 0,
                'channel_motd' => 0,
                'sms' => 0,
                'frequency' => self::FREQ_WEEKLY,
                'saved' => false,
            );
        }
        $row['saved'] = true;
        return $row;
    }

    /**
     * Réglages de tous les membres qui en ont
     *
     * @return array login => réglages
     */
    public function get_all_by_login() {
        $result = array();
        foreach ($this->db->get($this->table)->result_array() as $row) {
            $result[$row['member_login']] = $row;
        }
        return $result;
    }

    /**
     * Enregistre les réglages d'un membre
     *
     * @param string $login
     * @param bool $email
     * @param bool $motd
     * @param bool $sms
     * @param string $frequency
     * @return bool
     */
    public function save_for($login, $email, $motd, $sms, $frequency) {
        if (!in_array($frequency, self::frequencies(), true)) {
            $frequency = self::FREQ_WEEKLY;
        }
        $data = array(
            'member_login' => $login,
            'channel_email' => $email ? 1 : 0,
            'channel_motd' => $motd ? 1 : 0,
            'sms' => $sms ? 1 : 0,
            'frequency' => $frequency,
        );
        if ($this->db->where('member_login', $login)->count_all_results($this->table)) {
            return (bool) $this->update('member_login', $data);
        }
        return $this->create($data) !== false;
    }
}
