<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require_once APPPATH . 'controllers/reservation_scheduler.php';   // Reservation_scheduler::log_line()

/**
 * Agenda_google_traitement — traite la file de synchronisation des
 * réservations avec Google Agenda.
 *
 * Deux points d'entrée, comme reservation_scheduler :
 *   - cron()       : ligne de commande uniquement
 *   - run($secret) : URL publique protégée par $config['agenda_google_sync_secret']
 *
 * Cron conseillée (toutes les minutes ; sortie immédiate si la file est vide) :
 *   * * * * * XDEBUG_MODE=off /usr/bin/php /path/to/gvv/index.php agenda_google_traitement cron >> /var/log/gvv_agenda_google.log 2>&1
 *
 * Le contrôleur ne porte pas le nom agenda_google_sync : sa classe entrerait en
 * conflit avec la bibliothèque Agenda_google_sync.
 *
 * @see doc/design_notes/synchronisation_agenda_google_reservations_design.md (D3)
 */
class Agenda_google_traitement extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        // Fuseau du club, comme MY_Controller : les horaires des réservations
        // sont envoyés à Google avec ce fuseau (SYNC-CNT-04)
        date_default_timezone_set('Europe/Paris');
        $this->load->config('program');
    }

    /**
     * URL : /agenda_google_traitement/run/SECRET — rend le compte rendu en JSON.
     */
    public function run($secret = '')
    {
        header('Content-Type: application/json; charset=UTF-8');
        $expected = (string) $this->config->item('agenda_google_sync_secret');
        if ($expected === '' || $expected === 'CHANGE_ME_IN_PRODUCTION' || !hash_equals($expected, (string) $secret)) {
            http_response_code(403);
            echo json_encode(array('error' => 'Forbidden'));
            return;
        }
        echo json_encode($this->_process() + array('source' => 'public_url'));
    }

    /**
     * Commande : php index.php agenda_google_traitement cron
     */
    public function cron()
    {
        if (php_sapi_name() !== 'cli') {
            show_error('This action is only accessible via CLI.', 403);
            return;
        }
        $stats = $this->_process();
        if ($stats['traitees'] > 0 || $stats['occupe']) {
            echo Reservation_scheduler::log_line('agenda_google cron: ' . http_build_query($stats, '', ' '));
        }
    }

    private function _process()
    {
        $this->load->library('Agenda_google_sync');
        return $this->agenda_google_sync->process_queue();
    }
}
