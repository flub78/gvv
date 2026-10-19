<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Reservation_scheduler — Déclencheur du scheduler de rappels
 *
 * Deux points d'entrée :
 *   - run($secret) : URL publique, protégée par un secret technique
 *   - cron()       : entrée CLI uniquement (php_sapi_name() === 'cli')
 *
 * Chaque passage traite aussi les notifications d'alarmes
 * (Notification_manager::run_scheduled()) quand la gestion documentaire est
 * active, à partir de l'heure alarm_scheduler_hour : une seule entrée cron
 * suffit. Les synthèses et rappels SMS d'alarmes ne partent qu'une fois par
 * période.
 *
 * Configuration :
 *   application/config/program.php → $config['reservation_scheduler_secret'],
 *   $config['alarm_scheduler_hour']
 *
 * Exemple cron (horaire) :
 *   0 * * * * XDEBUG_MODE=off /usr/bin/php7.4 /path/to/gvv/index.php reservation_scheduler cron
 */
class Reservation_scheduler extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->config('program');
    }

    /**
     * Déclenchement via URL publique.
     * URL : /reservation_scheduler/run/SECRET
     *
     * Retourne JSON : {"sent": N, "alarms": {...}|null, "source": "public_url"}
     */
    public function run($secret = '')
    {
        header('Content-Type: application/json; charset=UTF-8');

        $expected = $this->config->item('reservation_scheduler_secret');
        if (empty($expected) || $secret !== $expected) {
            http_response_code(403);
            echo json_encode(array('error' => 'Forbidden'));
            return;
        }

        $sent = $this->_execute('public_url');
        $alarms = $this->_execute_alarms('public_url');
        echo json_encode(array('sent' => $sent, 'alarms' => $alarms, 'source' => 'public_url'));
    }

    /**
     * Déclenchement via cron (CLI uniquement).
     * Commande : /usr/bin/php7.4 /path/to/gvv/index.php reservation_scheduler cron
     */
    public function cron()
    {
        if (php_sapi_name() !== 'cli') {
            show_error('This action is only accessible via CLI.', 403);
            return;
        }

        $sent = $this->_execute('cron');
        echo "reservation_scheduler cron: sent=$sent\n";

        $alarms = $this->_execute_alarms('cron');
        if ($alarms === null) {
            echo "alarms: skipped\n";
        } else {
            echo "alarms: email={$alarms['email']} motd={$alarms['motd']} sms={$alarms['sms']} failed={$alarms['failed']}\n";
        }
    }

    /**
     * Charge la bibliothèque et exécute le scheduler.
     *
     * @param  string $source 'cron' | 'public_url'
     * @return int    Nombre de messages envoyés
     */
    private function _execute($source)
    {
        try {
            $this->load->library('Reservation_reminder');
            $sent = $this->reservation_reminder->run_scheduler($source);
            gvv_info("REMINDER scheduler source=$source sent=$sent");
            return $sent;
        } catch (Exception $e) {
            gvv_error("REMINDER scheduler exception: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Notifications d'alarmes, si la gestion documentaire est active et
     * l'heure alarm_scheduler_hour atteinte.
     *
     * @param  string $source 'cron' | 'public_url'
     * @return array|null Envois par canal, null si non traitées
     */
    private function _execute_alarms($source)
    {
        if (!$this->config->item('gestion_documentaire') || !$this->db->table_exists('alarm_definitions')) {
            return null;
        }
        try {
            $this->load->library('Notification_manager');
            return $this->notification_manager->run_scheduled($source);
        } catch (Exception $e) {
            gvv_error("ALARM scheduler exception: " . $e->getMessage());
            return null;
        }
    }
}
