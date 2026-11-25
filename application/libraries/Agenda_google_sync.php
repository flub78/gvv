<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'libraries/Google_agenda_client.php';
require_once APPPATH . 'libraries/Secret_box.php';

/**
 * Agenda_google_sync — règles métier de la synchronisation des réservations
 * avec Google Agenda : liaison du compte (activation, arrêt, état).
 *
 * Les dépendances (client Google, chiffrement) peuvent être remplacées par
 * les tests via les paramètres du constructeur.
 *
 * @see doc/prds/synchronisation_agenda_google_reservations_prd.md
 * @see doc/design_notes/synchronisation_agenda_google_reservations_design.md
 */
class Agenda_google_sync
{
    /** Rôles qui donnent le droit de réserver (voir le contrôleur reservations) */
    const BOOKING_ROLES = array('club-admin', 'instructeur', 'gestion_vd', 'auto_planchiste', 'pilote_vd', 'mecano');

    /** Échecs consécutifs d'une liaison à partir desquels l'échec est signalé comme persistant */
    const ECHECS_PERSISTANTS = 10;

    /** Tentatives après lesquelles une réservation est retirée de la file */
    const MAX_TENTATIVES = 20;

    protected $CI;

    /** @var Google_agenda_client */
    protected $client;

    /** @var Secret_box */
    protected $box;

    /** @var Agenda_google_liaisons_model */
    protected $liaisons;

    /** @var Agenda_google_file_model */
    protected $file;

    /** @var array jetons d'accès obtenus pendant un traitement, par liaison */
    protected $access_tokens = array();

    /** @var array rôles lus pendant un traitement, par membre */
    protected $roles = array();

    /**
     * @param array $params 'client' (Google_agenda_client), 'box' (Secret_box)
     */
    public function __construct($params = array())
    {
        $this->CI = &get_instance();
        $this->CI->load->config('program', FALSE, TRUE);
        $this->CI->load->model('agenda_google_liaisons_model');
        $this->liaisons = $this->CI->agenda_google_liaisons_model;
        $this->CI->load->model('agenda_google_file_model');
        $this->CI->load->model('reservations_model');
        $this->file = $this->CI->agenda_google_file_model;
        $this->client = isset($params['client']) ? $params['client'] : new Google_agenda_client();
        $this->box = isset($params['box']) ? $params['box'] : new Secret_box();
    }

    // =========================================================================
    // Disponibilité
    // =========================================================================

    /**
     * La fonction est activée pour le club et l'application Google est déclarée.
     */
    public function module_enabled()
    {
        return $this->CI->config->item('agenda_google_sync') == TRUE
            && $this->client->is_configured();
    }

    /**
     * Le membre a le droit de réserver dans au moins une section.
     */
    public function can_book($mlogin)
    {
        $roles = $this->member_roles($mlogin);
        return $roles === TRUE || ($roles !== FALSE && array_intersect($roles, self::BOOKING_ROLES));
    }

    /**
     * Le membre a le rôle d'instructeur dans au moins une section.
     */
    public function is_instructor($mlogin)
    {
        $roles = $this->member_roles($mlogin);
        return $roles === TRUE || ($roles !== FALSE && in_array('instructeur', $roles, TRUE));
    }

    /**
     * Rôles du membre examiné, dans toutes ses sections : FALSE s'il ne peut pas
     * se connecter, TRUE s'il est administrateur de l'ancien système de droits,
     * sinon la liste des noms de rôles. Gvv_authorization n'est pas utilisé
     * ici : il considère comme administrateur tout membre examiné quand
     * l'utilisateur connecté l'est.
     */
    protected function member_roles($mlogin)
    {
        if (array_key_exists($mlogin, $this->roles)) {
            return $this->roles[$mlogin];
        }
        $user = $this->CI->db->select('id, role_id, banned')->get_where('users', array('username' => $mlogin))->row_array();
        if (!$user || !empty($user['banned'])) {
            $roles = FALSE;
        } elseif ((int) $user['role_id'] === 2) {
            $roles = TRUE;
        } else {
            $this->CI->load->model('authorization_model');
            $roles = array_column($this->CI->authorization_model->get_user_roles((int) $user['id'], NULL), 'role_name');
        }
        return $this->roles[$mlogin] = $roles;
    }

    /**
     * La synchronisation est proposée à ce membre (SYNC-ACT-01, SYNC-ACT-08).
     */
    public function available_for($mlogin)
    {
        return $this->module_enabled() && $this->can_book($mlogin);
    }

    // =========================================================================
    // Liaison
    // =========================================================================

    /**
     * État de la liaison du membre, sans le jeton : NULL si jamais activée.
     */
    public function status($mlogin)
    {
        $liaison = $this->liaisons->for_member($mlogin);
        if ($liaison) {
            unset($liaison['jeton_renouvellement']);
        }
        return $liaison;
    }

    /**
     * Adresse de l'écran d'autorisation de Google.
     */
    public function authorization_url($state)
    {
        return $this->client->authorization_url($state);
    }

    /**
     * Termine l'activation au retour de Google : échange du code, chiffrement
     * et enregistrement du jeton de renouvellement.
     *
     * Une réactivation avec le même compte conserve les liens vers les
     * événements existants ; avec un autre compte, ils sont abandonnés (D7).
     *
     * @return array compte (adresse Google), changement_compte (bool)
     * @throws Google_agenda_exception
     */
    public function activate($mlogin, $code)
    {
        $tokens = $this->client->exchange_code($code);
        $previous = $this->liaisons->for_member($mlogin);

        $changed_account = $previous && $previous['compte_google'] !== $tokens['email'];
        $id = $this->liaisons->save_activation($mlogin, $tokens['email'], $this->box->encrypt($tokens['refresh_token']));
        if ($changed_account) {
            $this->liaisons->forget_events($id);
        }
        unset($this->access_tokens[$id]);

        // Réservations à venir du membre, comme pilote et comme instructeur (SYNC-ACT-05)
        $inscrites = 0;
        foreach ($this->upcoming_reservations($mlogin) as $reservation_id) {
            if ($this->file->enqueue($reservation_id, $mlogin)) {
                $inscrites++;
            }
        }

        gvv_info("AGENDA_GOOGLE activation membre=$mlogin" . ($previous ? ' (réactivation)' : '')
            . ($changed_account ? ' (changement de compte)' : '') . " réservations inscrites=$inscrites");
        return array('compte' => $tokens['email'], 'changement_compte' => $changed_account, 'inscrites' => $inscrites);
    }

    /**
     * Arrête la synchronisation (SYNC-ARR-01 à 05) : révoque le jeton chez
     * Google, l'efface, et laisse les événements et leurs liens en l'état.
     *
     * @return array|null NULL si aucune liaison n'était en place ;
     *                    sinon revoque (bool) : la révocation chez Google est confirmée
     */
    public function stop($mlogin)
    {
        $liaison = $this->liaisons->for_member($mlogin);
        if (!$liaison || $liaison['etat'] === Agenda_google_liaisons_model::ARRETEE) {
            return NULL;
        }

        $revoked = TRUE;
        $token = empty($liaison['jeton_renouvellement']) ? FALSE : $this->box->decrypt($liaison['jeton_renouvellement']);
        if ($token !== FALSE) {
            try {
                $revoked = $this->client->revoke($token);
            } catch (Exception $e) {
                $revoked = FALSE;
                gvv_error("AGENDA_GOOGLE révocation non confirmée membre=$mlogin : " . $e->getMessage());
            }
        }

        $this->liaisons->mark_stopped($liaison['id']);
        gvv_info("AGENDA_GOOGLE arrêt membre=$mlogin" . ($revoked ? '' : ' (révocation non confirmée)'));
        return array('revoque' => $revoked);
    }

    /**
     * Membre supprimé : la synchronisation est arrêtée avec sa fiche, et sa
     * liaison disparaît.
     */
    public function forget_member($mlogin)
    {
        $liaison = $this->liaisons->for_member($mlogin);
        if (!$liaison) {
            return;
        }
        $this->stop($mlogin);
        $this->liaisons->remove($liaison['id']);
        gvv_info("AGENDA_GOOGLE liaison supprimée avec le membre $mlogin");
    }

    /**
     * Identifiants des réservations à venir où le membre est pilote ou instructeur
     * (hors blocs d'indisponibilité sans pilote).
     */
    protected function upcoming_reservations($mlogin)
    {
        $rows = $this->CI->db->select('id, status')
            ->from('reservations')
            ->where('end_datetime >=', date('Y-m-d H:i:s'))
            ->where('(pilot_member_id = ' . $this->CI->db->escape($mlogin)
                . ' OR instructor_member_id = ' . $this->CI->db->escape($mlogin) . ')', NULL, FALSE)
            ->order_by('start_datetime', 'asc')
            ->get()->result_array();
        $ids = array();
        foreach ($rows as $row) {
            if (self::has_crew($row['status'])) {
                $ids[] = (int) $row['id'];
            }
        }
        return $ids;
    }

    /**
     * Une réservation de ce statut a un équipage (et non un bloc d'indisponibilité).
     */
    public static function has_crew($status)
    {
        $statuses = Reservations_model::get_statuses();
        return !isset($statuses[$status]) || $statuses[$status]['requires_pilot'];
    }

    // =========================================================================
    // Traitement de la file
    // =========================================================================

    /**
     * Traite les réservations dont l'essai est dû.
     *
     * @param int $limit       nombre maximal de réservations
     * @param int $max_seconds durée maximale du traitement
     * @return array compte rendu : traitees, a_jour, reportees, abandonnees, suspendues, inactif, occupe
     */
    public function process_queue($limit = 50, $max_seconds = 45)
    {
        $stats = array('traitees' => 0, 'a_jour' => 0, 'reportees' => 0, 'abandonnees' => 0,
            'suspendues' => 0, 'inactif' => FALSE, 'occupe' => FALSE);
        if (!$this->module_enabled()) {
            $stats['inactif'] = TRUE;
            return $stats;
        }
        $lock = $this->CI->db->query("SELECT GET_LOCK('gvv_agenda_google_sync', 0) AS l")->row_array();
        if (empty($lock['l'])) {
            $stats['occupe'] = TRUE;   // un autre traitement est en cours
            return $stats;
        }

        try {
            $this->access_tokens = array();
            $this->roles = array();
            $this->suspended_count = 0;
            $start = time();
            foreach ($this->file->due($limit) as $row) {
                if (time() - $start >= $max_seconds) {
                    break;
                }
                $stats['traitees']++;
                try {
                    $this->reconcile((int) $row['reservation_id']);
                    $this->file->done($row);
                    $stats['a_jour']++;
                } catch (Exception $e) {
                    $tentatives = $this->file->retry_later($row, $e->getMessage());
                    if ($tentatives >= self::MAX_TENTATIVES) {
                        $this->file->remove($row);
                        $stats['abandonnees']++;
                        gvv_error("AGENDA_GOOGLE réservation {$row['reservation_id']} abandonnée après $tentatives tentatives : " . $e->getMessage());
                    } else {
                        $stats['reportees']++;
                        gvv_info("AGENDA_GOOGLE réservation {$row['reservation_id']} reportée (tentative $tentatives) : " . $e->getMessage());
                    }
                }
            }
            $stats['suspendues'] = $this->suspended_count;
        } finally {
            $this->CI->db->query("SELECT RELEASE_LOCK('gvv_agenda_google_sync')");
        }
        return $stats;
    }

    /** @var int liaisons suspendues pendant le traitement en cours */
    protected $suspended_count = 0;

    /**
     * Réconcilie une réservation avec les agendas (D1) : calcule les agendas où
     * elle doit figurer, puis crée, met à jour ou supprime ce qu'il faut.
     * Idempotent : le rejouer ne crée pas de doublon.
     *
     * @throws Exception erreur passagère ou requête refusée : à retenter
     */
    public function reconcile($reservation_id)
    {
        $reservation = $this->load_reservation($reservation_id);
        $expected = $this->expected_liaisons($reservation);
        $links = $this->liaisons->links_for_reservation($reservation_id);
        $event = $reservation ? $this->build_event($reservation) : NULL;
        $hash = $event ? self::fingerprint($event) : NULL;
        // Une réservation terminée n'est jamais ajoutée à un agenda (SYNC-ACT-05) ;
        // un événement déjà créé reste mis à jour (PRD §8).
        $ended = $reservation && strtotime($reservation['end_datetime']) < time();
        $error = NULL;

        foreach ($expected as $liaison_id => $liaison) {
            $link = isset($links[$liaison_id]) ? $links[$liaison_id] : NULL;
            if (!$link && $ended) {
                continue;
            }
            $error = $this->guarded($liaison, function ($token) use ($liaison_id, $reservation_id, $link, $event, $hash) {
                if (!$link) {
                    $id = $this->client->insert_event($token, $event);
                    $this->liaisons->save_link($liaison_id, $reservation_id, $id, $hash);
                } elseif ($link['empreinte'] !== $hash) {
                    try {
                        $this->client->update_event($token, $link['google_event_id'], $event);
                        $this->liaisons->update_link($link['id'], $link['google_event_id'], $hash);
                    } catch (Google_agenda_exception $e) {
                        if ($e->kind !== Google_agenda_exception::DISPARU) {
                            throw $e;
                        }
                        // Supprimé à la main par le membre : recréé car la réservation a changé
                        $id = $this->client->insert_event($token, $event);
                        $this->liaisons->update_link($link['id'], $id, $hash);
                    }
                }
            }) ?: $error;
        }

        foreach ($links as $liaison_id => $link) {
            if (isset($expected[$liaison_id])) {
                continue;
            }
            if ($link['etat'] !== Agenda_google_liaisons_model::ACTIVE) {
                continue;   // liaison arrêtée ou suspendue : l'événement reste tel quel (SYNC-ARR-02)
            }
            $liaison = $this->liaisons->for_member($link['mlogin']);
            $error = $this->guarded($liaison, function ($token) use ($link) {
                $this->client->delete_event($token, $link['google_event_id']);
                $this->liaisons->delete_link($link['id']);
            }) ?: $error;
        }

        if ($error) {
            throw $error;
        }
    }

    /**
     * Exécute une opération sur l'agenda d'une liaison et applique les règles
     * d'erreur : une autorisation invalide suspend la liaison (l'opération est
     * abandonnée), toute autre erreur est rendue pour être retentée.
     *
     * @return Exception|null erreur à retenter
     */
    protected function guarded($liaison, $operation)
    {
        try {
            $operation($this->access_token($liaison));
            $this->liaisons->record_success($liaison['id']);
            return NULL;
        } catch (Google_agenda_exception $e) {
            if ($e->kind === Google_agenda_exception::AUTORISATION) {
                $this->suspend($liaison, $e->getMessage());
                return NULL;
            }
            $echecs = $this->liaisons->record_failure($liaison['id']);
            if ($echecs === self::ECHECS_PERSISTANTS) {
                gvv_error("AGENDA_GOOGLE échec persistant membre={$liaison['mlogin']} ($echecs échecs consécutifs) : " . $e->getMessage());
            }
            return $e;
        }
    }

    /**
     * Jeton d'accès de la liaison, obtenu une fois par traitement.
     *
     * @throws Google_agenda_exception
     */
    protected function access_token($liaison)
    {
        $id = (int) $liaison['id'];
        if (!isset($this->access_tokens[$id])) {
            $refresh = empty($liaison['jeton_renouvellement']) ? FALSE : $this->box->decrypt($liaison['jeton_renouvellement']);
            if ($refresh === FALSE) {
                throw new Google_agenda_exception('autorisation illisible (clé de chiffrement changée)', Google_agenda_exception::AUTORISATION);
            }
            $this->access_tokens[$id] = $this->client->access_token($refresh);
        }
        return $this->access_tokens[$id];
    }

    /**
     * Suspend une liaison dont l'autorisation n'est plus valable, et prévient
     * le membre par e-mail (SYNC-ERR-03).
     */
    protected function suspend($liaison, $cause)
    {
        $this->liaisons->mark_suspended($liaison['id'], $cause);
        unset($this->access_tokens[(int) $liaison['id']]);
        $this->suspended_count++;
        gvv_info("AGENDA_GOOGLE suspension membre={$liaison['mlogin']} cause=$cause");
        $this->notify_suspension($liaison['mlogin']);
    }

    /**
     * E-mail au membre : sa synchronisation est suspendue, il doit relier à
     * nouveau son agenda depuis « Mes réservations ».
     */
    protected function notify_suspension($mlogin)
    {
        $member = $this->CI->db->select('memail, mprenom, mnom')->get_where('membres', array('mlogin' => $mlogin))->row_array();
        if (!$member || empty($member['memail'])) {
            gvv_info("AGENDA_GOOGLE suspension membre=$mlogin : pas d'adresse e-mail, membre non prévenu");
            return FALSE;
        }
        try {
            $this->CI->lang->load('agenda_google');
            $this->CI->load->helper('email');
            $this->CI->load->library('email');
            $this->CI->load->config('club', TRUE);
            $from_email = $this->CI->config->item('email_club') ?: $this->CI->config->item('smtp_user');
            $from_name = $this->CI->config->item('nom_club') ?: 'GVV';
            $subject = $this->CI->lang->line('agenda_google_mail_suspension_sujet');
            $body = sprintf($this->CI->lang->line('agenda_google_mail_suspension_corps'),
                htmlspecialchars(trim($member['mprenom'] . ' ' . $member['mnom']), ENT_QUOTES, 'UTF-8'),
                site_url('mes_reservations'));
            $to = test_intercept_email($member['memail'], $subject);

            $this->CI->email->clear(TRUE);
            $this->CI->email->set_mailtype('html');
            $this->CI->email->from($from_email, $from_name);
            $this->CI->email->to($to);
            $this->CI->email->subject($subject);
            $this->CI->email->message($body);
            if (!@$this->CI->email->send()) {
                gvv_error("AGENDA_GOOGLE e-mail de suspension non envoyé à $mlogin");
                return FALSE;
            }
            return TRUE;
        } catch (Throwable $e) {
            gvv_error("AGENDA_GOOGLE e-mail de suspension non envoyé à $mlogin : " . $e->getMessage());
            return FALSE;
        }
    }

    // =========================================================================
    // Agendas attendus et contenu des événements
    // =========================================================================

    protected function load_reservation($reservation_id)
    {
        $row = $this->CI->db->select('r.*, m.macmodele, p.mprenom AS pilot_prenom, p.mnom AS pilot_nom, '
                . 'i.mprenom AS instructor_prenom, i.mnom AS instructor_nom')
            ->from('reservations r')
            ->join('machinesa m', 'm.macimmat = r.aircraft_id', 'left')
            ->join('membres p', 'p.mlogin = r.pilot_member_id', 'left')
            ->join('membres i', 'i.mlogin = r.instructor_member_id', 'left')
            ->where('r.id', $reservation_id)
            ->get()->row_array();
        return $row ?: NULL;
    }

    /**
     * Liaisons actives des agendas où la réservation doit figurer : celui du
     * pilote et celui de l'instructeur assigné (SYNC-MAJ-01). Un membre qui a
     * perdu le droit de réserver voit sa synchronisation arrêtée (PRD §8).
     *
     * @return array liaison_id => liaison
     */
    protected function expected_liaisons($reservation)
    {
        $expected = array();
        if (!$reservation || !self::has_crew($reservation['status'])) {
            return $expected;
        }
        $crew = array('pilote' => $reservation['pilot_member_id'], 'instructeur' => $reservation['instructor_member_id']);
        foreach ($crew as $role => $mlogin) {
            if (empty($mlogin)) {
                continue;
            }
            $liaison = $this->liaisons->for_member($mlogin);
            if (!$liaison || $liaison['etat'] !== Agenda_google_liaisons_model::ACTIVE) {
                continue;
            }
            if (!$this->can_book($mlogin)) {
                gvv_info("AGENDA_GOOGLE membre=$mlogin n'a plus le droit de réserver : synchronisation arrêtée");
                $this->stop($mlogin);
                continue;
            }
            if ($role === 'instructeur' && $mlogin !== $reservation['pilot_member_id'] && !$this->is_instructor($mlogin)) {
                continue;
            }
            $expected[(int) $liaison['id']] = $liaison;
        }
        return $expected;
    }

    /**
     * Contenu de l'événement Google d'une réservation (SYNC-CNT-01 à 04). Seul
     * ce contenu est envoyé : il ne contient que l'aéronef, les horaires, les
     * noms du pilote et de l'instructeur, le commentaire et un lien vers GVV.
     */
    public function build_event($reservation)
    {
        $this->CI->lang->load('agenda_google');
        $line = function ($key) { return $this->CI->lang->line($key); };
        $aircraft = trim($reservation['aircraft_id'] . (!empty($reservation['macmodele']) ? ' (' . $reservation['macmodele'] . ')' : ''));
        $pilot = trim($reservation['pilot_prenom'] . ' ' . $reservation['pilot_nom']) ?: (string) $reservation['pilot_member_id'];
        $instructor = empty($reservation['instructor_member_id']) ? ''
            : (trim($reservation['instructor_prenom'] . ' ' . $reservation['instructor_nom']) ?: $reservation['instructor_member_id']);
        $date = substr($reservation['start_datetime'], 0, 10);
        $url = site_url('reservations/timeline?date=' . $date);

        $description = array(sprintf($line('agenda_google_evt_pilote'), $pilot));
        if ($instructor !== '') {
            $description[] = sprintf($line('agenda_google_evt_instructeur'), $instructor);
        }
        if (trim((string) $reservation['notes']) !== '') {
            $description[] = sprintf($line('agenda_google_evt_commentaire'), trim($reservation['notes']));
        }
        $description[] = '';
        $description[] = sprintf($line('agenda_google_evt_lien'), $url);

        $timezone = $this->client->timezone();
        return array(
            'summary'     => sprintf($line('agenda_google_evt_titre'), $aircraft),
            'description' => implode("\n", $description),
            'start'       => array('dateTime' => str_replace(' ', 'T', $reservation['start_datetime']), 'timeZone' => $timezone),
            'end'         => array('dateTime' => str_replace(' ', 'T', $reservation['end_datetime']), 'timeZone' => $timezone),
            'source'      => array('title' => 'GVV', 'url' => $url),
            'extendedProperties' => array('private' => array('gvv_reservation' => (string) $reservation['id'])),
        );
    }

    /**
     * Empreinte du contenu envoyé : évite de renvoyer un événement inchangé.
     */
    public static function fingerprint($event)
    {
        return hash('sha256', json_encode($event));
    }
}
