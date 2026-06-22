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
 * Configuration :
 *   application/config/program.php → $config['reservation_scheduler_secret']
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
     * Retourne JSON : {"sent": N, "source": "public_url"}
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
        echo json_encode(array('sent' => $sent, 'source' => 'public_url'));
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
            gvv_info("reservation_scheduler::_execute source=$source sent=$sent");
            return $sent;
        } catch (Exception $e) {
            gvv_error("reservation_scheduler::_execute exception: " . $e->getMessage());
            return 0;
        }
    }
}
