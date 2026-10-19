<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Déclenchement manuel des notifications d'alarmes
 *
 * En production, les alarmes sont traitées par le cron horaire des rappels
 * de réservation (reservation_scheduler), sans entrée cron dédiée. Ce
 * contrôleur permet un lancement immédiat, sans condition d'heure :
 * - cron() : ligne de commande uniquement ;
 * - run($secret) : URL publique protégée par alarm_scheduler_secret
 *   (program.php), réponse JSON.
 *
 * Une exécution répétée le même jour n'envoie rien de plus (notification_history).
 *
 * @package controllers
 * @see application/libraries/Notification_manager.php
 */
class Alarm_scheduler extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->config('program');
    }

    /**
     * Déclenchement par URL : /alarm_scheduler/run/<secret>
     *
     * @param string $secret
     */
    public function run($secret = '')
    {
        header('Content-Type: application/json; charset=UTF-8');

        $expected = $this->config->item('alarm_scheduler_secret');
        if (empty($expected) || $secret !== $expected) {
            http_response_code(403);
            echo json_encode(array('error' => 'Forbidden'));
            return;
        }

        $stats = $this->_execute('public_url');
        echo json_encode(array('sent' => $stats, 'source' => 'public_url'));
    }

    /**
     * Déclenchement en ligne de commande : php index.php alarm_scheduler cron
     */
    public function cron()
    {
        if (php_sapi_name() !== 'cli') {
            show_error('This action is only accessible via CLI.', 403);
            return;
        }

        $stats = $this->_execute('cron');
        echo 'alarm_scheduler cron: ' . Notification_manager::format_stats($stats) . "\n";
    }

    /**
     * @param string $source
     * @return array Envois par canal
     */
    private function _execute($source)
    {
        try {
            $this->load->library('Notification_manager');
            return $this->notification_manager->run($source);
        } catch (Exception $e) {
            gvv_error("ALARM scheduler exception: " . $e->getMessage());
            return array('email' => 0, 'motd' => 0, 'sms' => 0, 'failed' => 0);
        }
    }
}
