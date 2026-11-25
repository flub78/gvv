<?php

require_once(__DIR__ . '/TransactionalTestCase.php');
require_once(__DIR__ . '/AgendaGoogleFakeClient.php');
require_once APPPATH . 'libraries/Secret_box.php';
require_once APPPATH . 'libraries/Agenda_google_sync.php';

/**
 * Synchronisation dont l'e-mail de suspension est enregistré au lieu d'être envoyé.
 */
class AgendaGoogleSyncTestDouble extends Agenda_google_sync
{
    public $suspension_mails = array();

    protected function notify_suspension($mlogin)
    {
        $this->suspension_mails[] = $mlogin;
        return TRUE;
    }
}

/**
 * Textes français réels, pour que le contenu des événements soit complet.
 */
class AgendaGoogleSyncTestLang
{
    private $lines = array();

    public function load($file)
    {
        $lang = array();
        $path = APPPATH . 'language/french/' . $file . '_lang.php';
        if (is_file($path)) {
            include $path;
        }
        $this->lines = array_merge($this->lines, $lang);
        return TRUE;
    }

    public function line($key)
    {
        return isset($this->lines[$key]) ? $this->lines[$key] : $key;
    }
}

/**
 * @feature Synchronisation agenda Google
 *
 * Mise à jour des agendas Google à partir des réservations : réconciliation
 * de chaque réservation et traitement de la file. Google est remplacé par un
 * faux client ; la clé de chiffrement est temporaire.
 */
class AgendaGoogleSyncTest extends TransactionalTestCase
{
    /** @var AgendaGoogleSyncTestDouble */
    private $sync;
    /** @var AgendaGoogleFakeClient */
    private $client;
    /** @var Reservations_model */
    private $reservations;
    private $key_file;
    private $saved_lang;
    private $saved_config;
    private static $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->saved_lang = $this->CI->lang;
        $this->CI->lang = new AgendaGoogleSyncTestLang();

        $this->key_file = sys_get_temp_dir() . '/gvv_agenda_google_sync_' . getmypid() . '_' . uniqid() . '.php';
        $this->client = new AgendaGoogleFakeClient();
        $this->sync = new AgendaGoogleSyncTestDouble(array(
            'client' => $this->client,
            'box'    => new Secret_box(array('key_file' => $this->key_file)),
        ));
        $this->saved_config = $this->CI->config->item('agenda_google_sync');
        $this->CI->config->set_item('agenda_google_sync', TRUE);

        $this->CI->load->model('reservations_model');
        $this->reservations = $this->CI->reservations_model;
        $this->reservations->set_reminder(new class { public function handle_event($id, $type, $by) { return TRUE; } });
        $this->CI->load->model('agenda_google_file_model');
        $this->reservations->set_agenda_queue($this->CI->agenda_google_file_model);
        $this->CI->db->query('DELETE FROM agenda_google_file');   // traitement limité aux réservations du test ; annulé en fin de test
    }

    protected function tearDown(): void
    {
        $this->CI->lang = $this->saved_lang;
        $this->CI->config->set_item('agenda_google_sync', $this->saved_config);
        if (is_file($this->key_file)) {
            unlink($this->key_file);
        }
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Données de test
    // -------------------------------------------------------------------------

    private function _member($roles = array('auto_planchiste'), $prenom = 'Prenom')
    {
        $login = 'ags_' . getmypid() . '_' . (++self::$seq);
        $this->CI->db->insert('membres', array(
            'mlogin' => $login, 'mnom' => 'Nom' . self::$seq, 'mprenom' => $prenom,
            'memail' => $login . '@test.invalid', 'msexe' => 'M', 'actif' => 1, 'ext' => 0, 'm25ans' => 0,
        ));
        $this->CI->db->insert('users', array(
            'username' => $login, 'password' => 'x', 'email' => $login . '@test.invalid',
            'role_id' => 1, 'banned' => 0, 'last_ip' => '127.0.0.1',
            'last_login' => '2000-01-01 00:00:00', 'created' => date('Y-m-d H:i:s'),
        ));
        $user_id = (int) $this->CI->db->insert_id();
        $section = $this->CI->db->select('id')->limit(1)->get('sections')->row_array();
        foreach ($roles as $role) {
            $type = $this->CI->db->get_where('types_roles', array('nom' => $role))->row_array();
            $this->CI->db->insert('user_roles_per_section', array(
                'user_id' => $user_id, 'types_roles_id' => $type['id'], 'section_id' => $section['id'],
                'granted_by' => $user_id, 'granted_at' => date('Y-m-d H:i:s'),
            ));
        }
        return $login;
    }

    /** Membre qui a relié son agenda */
    private function _linked($roles = array('auto_planchiste'))
    {
        $login = $this->_member($roles);
        $this->client->email = $login . '@gmail.com';
        $this->sync->activate($login, 'code-' . $login);
        return $login;
    }

    private function _liaison($login)
    {
        return $this->CI->db->get_where('agenda_google_liaisons', array('mlogin' => $login))->row_array();
    }

    private function _reservation($pilot, $instructor = NULL, $status = 'vol_local', $days = 30)
    {
        return $this->reservations->create_reservation(array(
            'aircraft_id'          => 'F-TAGS',
            'pilot_member_id'      => $pilot,
            'instructor_member_id' => $instructor,
            'start_datetime'       => date('Y-m-d 10:00:00', strtotime("+$days days")),
            'end_datetime'         => date('Y-m-d 12:00:00', strtotime("+$days days")),
            'status'               => $status,
            'notes'                => 'Tour de piste',
            'section_id'           => 1,
            'created_by'           => $pilot,
        ));
    }

    /** Événements présents dans l'agenda Google d'un membre */
    private function _agenda($login)
    {
        $liaison = $this->_liaison($login);
        $refresh = (new Secret_box(array('key_file' => $this->key_file)))->decrypt($liaison['jeton_renouvellement']);
        if ($refresh === FALSE) {
            // liaison arrêtée : le jeton est effacé, on retrouve l'agenda par les liens
            foreach ($this->client->events as $token => $events) {
                if (strpos($token, 'acces:renouvellement') === 0) {
                    foreach ($this->CI->db->get_where('agenda_google_evenements', array('liaison_id' => $liaison['id']))->result_array() as $link) {
                        if (isset($events[$link['google_event_id']])) {
                            return $events;
                        }
                    }
                }
            }
            return array();
        }
        return isset($this->client->events['acces:' . $refresh]) ? $this->client->events['acces:' . $refresh] : array();
    }

    private function _queue_row($reservation_id)
    {
        return $this->CI->db->get_where('agenda_google_file', array('reservation_id' => $reservation_id))->row_array();
    }

    // -------------------------------------------------------------------------
    // Création, mise à jour, suppression
    // -------------------------------------------------------------------------

    /**
     * Scénario: Une réservation créée apparaît dans l'agenda du pilote
     *   Étant donné un pilote qui a relié son agenda Google
     *   Quand il crée une réservation
     *   Et que la synchronisation passe
     *   Alors un événement apparaît dans son agenda, avec l'aéronef, les horaires, son nom, le commentaire et un lien vers GVV
     *   Et la réservation n'attend plus d'être synchronisée
     */
    public function test_created_reservation_appears_in_pilot_calendar()
    {
        $pilot = $this->_linked();
        $id = $this->_reservation($pilot);
        $this->assertNotEmpty($this->_queue_row($id), 'la réservation est inscrite dans la file');

        $stats = $this->sync->process_queue();

        $events = $this->_agenda($pilot);
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertStringContainsString('F-TAGS', $event['summary']);
        $this->assertStringContainsString('Tour de piste', $event['description']);
        $this->assertStringContainsString('Prenom', $event['description']);
        $this->assertStringContainsString('reservations/timeline', $event['description']);
        $this->assertSame('Europe/Paris', $event['start']['timeZone']);
        $this->assertSame(date('Y-m-d\T10:00:00', strtotime('+30 days')), $event['start']['dateTime']);
        $this->assertStringNotContainsString('solde', strtolower(json_encode($event)));
        $this->assertEmpty($this->_queue_row($id));
        $this->assertGreaterThanOrEqual(1, $stats['a_jour']);
        $this->assertNotNull($this->_liaison($pilot)['derniere_reussite_at']);
    }

    /**
     * Scénario: Une réservation n'est renvoyée à Google que si elle a changé
     *   Étant donné une réservation déjà présente dans l'agenda du pilote
     *   Quand elle est enregistrée sans changement puis la synchronisation passe
     *   Alors Google n'est pas sollicité pour cet événement
     *   Quand son commentaire est modifié puis la synchronisation passe
     *   Alors l'événement est mis à jour, sans doublon
     */
    public function test_update_only_when_content_changes()
    {
        $pilot = $this->_linked();
        $id = $this->_reservation($pilot);
        $this->sync->process_queue();
        $this->client->calls = array();

        $this->reservations->update_reservation($id, array('notes' => 'Tour de piste'));
        $this->sync->process_queue();
        $this->assertSame(array(), $this->client->calls_of('update_event'));
        $this->assertSame(array(), $this->client->calls_of('insert_event'));

        $this->reservations->update_reservation($id, array('notes' => 'Navigation vers Abbeville'));
        $this->sync->process_queue();
        $this->assertCount(1, $this->client->calls_of('update_event'));
        $events = $this->_agenda($pilot);
        $this->assertCount(1, $events);
        $this->assertStringContainsString('Abbeville', reset($events)['description']);
    }

    /**
     * Scénario: Une réservation supprimée disparaît de l'agenda
     *   Étant donné une réservation présente dans l'agenda du pilote
     *   Quand elle est supprimée dans GVV
     *   Et que la synchronisation passe
     *   Alors l'événement est retiré de l'agenda
     */
    public function test_deleted_reservation_is_removed_from_calendar()
    {
        $pilot = $this->_linked();
        $id = $this->_reservation($pilot);
        $this->sync->process_queue();

        $this->reservations->delete_reservation($id);
        $this->sync->process_queue();

        $this->assertCount(0, $this->_agenda($pilot));
        $this->assertSame(0, $this->CI->db->where('reservation_id', $id)->count_all_results('agenda_google_evenements'));
    }

    /**
     * Scénario: La réservation apparaît chez le pilote et chez l'instructeur
     *   Étant donné un pilote et un instructeur qui ont chacun relié leur agenda
     *   Quand une réservation avec cet instructeur est créée
     *   Et que la synchronisation passe
     *   Alors elle apparaît une fois dans chacun des deux agendas
     */
    public function test_reservation_in_pilot_and_instructor_calendars()
    {
        $pilot = $this->_linked();
        $instructor = $this->_linked(array('instructeur'));
        $this->_reservation($pilot, $instructor);

        $this->sync->process_queue();

        $this->assertCount(1, $this->_agenda($pilot));
        $this->assertCount(1, $this->_agenda($instructor));
    }

    /**
     * Scénario: Un changement d'instructeur déplace l'événement d'un agenda à l'autre
     *   Étant donné une réservation présente dans l'agenda de son instructeur
     *   Et un autre instructeur qui a relié son agenda
     *   Quand la réservation est confiée à cet autre instructeur
     *   Et que la synchronisation passe
     *   Alors elle disparaît de l'agenda du premier instructeur
     *   Et apparaît dans celui du nouveau
     */
    public function test_instructor_change_moves_event()
    {
        $pilot = $this->_member();
        $first = $this->_linked(array('instructeur'));
        $second = $this->_linked(array('instructeur'));
        $id = $this->_reservation($pilot, $first);
        $this->sync->process_queue();
        $this->assertCount(1, $this->_agenda($first));

        $this->reservations->update_reservation($id, array('instructor_member_id' => $second));
        $this->sync->process_queue();

        $this->assertCount(0, $this->_agenda($first));
        $this->assertCount(1, $this->_agenda($second));
    }

    /**
     * Scénario: Un membre qui n'est pas instructeur ne reçoit pas les réservations où il serait assigné
     *   Étant donné un membre sans rôle d'instructeur qui a relié son agenda
     *   Quand il est indiqué comme instructeur d'une réservation
     *   Et que la synchronisation passe
     *   Alors la réservation n'apparaît pas dans son agenda
     */
    public function test_non_instructor_does_not_receive_instructor_reservations()
    {
        $pilot = $this->_member();
        $not_instructor = $this->_linked(array('auto_planchiste'));
        $this->_reservation($pilot, $not_instructor);

        $this->sync->process_queue();

        $this->assertCount(0, $this->_agenda($not_instructor));
    }

    /**
     * Scénario: Les blocs d'indisponibilité ne sont pas copiés
     *   Étant donné un membre qui a relié son agenda
     *   Quand un bloc de maintenance d'un aéronef est créé à son nom
     *   Et que la synchronisation passe
     *   Alors rien n'apparaît dans son agenda
     */
    public function test_unavailability_blocks_are_ignored()
    {
        $pilot = $this->_linked();
        $this->_reservation($pilot, NULL, 'maintenance');

        $this->sync->process_queue();

        $this->assertCount(0, $this->_agenda($pilot));
    }

    // -------------------------------------------------------------------------
    // Arrêt, événement disparu, activation
    // -------------------------------------------------------------------------

    /**
     * Scénario: Après l'arrêt, les événements existants restent sans être mis à jour
     *   Étant donné une réservation présente dans l'agenda du pilote
     *   Quand le pilote arrête la synchronisation
     *   Et que la réservation est ensuite modifiée puis supprimée
     *   Alors l'événement reste dans son agenda tel qu'il était
     *   Et GVV ne s'adresse plus à son agenda
     */
    public function test_stopped_liaison_leaves_events_untouched()
    {
        $pilot = $this->_linked();
        $id = $this->_reservation($pilot);
        $this->sync->process_queue();
        $before = $this->client->events;
        $this->sync->stop($pilot);
        $this->client->calls = array();

        $this->reservations->update_reservation($id, array('notes' => 'Changé après l\'arrêt'));
        $this->sync->process_queue();
        $this->reservations->delete_reservation($id);
        $this->sync->process_queue();

        $this->assertSame($before, $this->client->events);
        $this->assertSame(array(), $this->client->calls_of('access_token'));
        $this->assertSame(1, $this->CI->db->where('reservation_id', $id)->count_all_results('agenda_google_evenements'));
    }

    /**
     * Scénario: Un événement supprimé à la main n'est recréé que si la réservation change
     *   Étant donné une réservation présente dans l'agenda du pilote
     *   Et que le pilote a supprimé cet événement à la main dans Google
     *   Quand la réservation est enregistrée sans changement
     *   Alors l'événement n'est pas recréé
     *   Quand la réservation est modifiée
     *   Alors l'événement est recréé avec le nouveau contenu
     */
    public function test_manually_deleted_event_recreated_only_on_change()
    {
        $pilot = $this->_linked();
        $id = $this->_reservation($pilot);
        $this->sync->process_queue();
        $token = array_keys($this->client->events)[0];
        $this->client->events[$token] = array();   // supprimé à la main

        $this->reservations->update_reservation($id, array('notes' => 'Tour de piste'));
        $this->sync->process_queue();
        $this->assertCount(0, $this->_agenda($pilot));

        $this->reservations->update_reservation($id, array('notes' => 'Modifié'));
        $this->sync->process_queue();
        $this->assertCount(1, $this->_agenda($pilot));
        $this->assertSame(1, $this->CI->db->where('reservation_id', $id)->count_all_results('agenda_google_evenements'));
    }

    /**
     * Scénario: À l'activation, les réservations à venir du membre sont ajoutées
     *   Étant donné un instructeur qui a des réservations à venir comme pilote et comme instructeur
     *   Et une réservation passée
     *   Quand il relie son agenda Google
     *   Alors le compte rendu annonce ses réservations à venir
     *   Et après le passage de la synchronisation elles sont dans son agenda, mais pas la réservation passée
     */
    public function test_activation_adds_upcoming_reservations()
    {
        $member = $this->_member(array('instructeur'));
        $other = $this->_member();
        $this->_reservation($member, NULL, 'vol_local', 10);
        $this->_reservation($other, $member, 'vol_local', 20);
        $this->_reservation($member, NULL, 'vol_local', -10);

        $this->client->email = $member . '@gmail.com';
        $result = $this->sync->activate($member, 'code');
        $this->sync->process_queue();

        $this->assertSame(2, $result['inscrites']);
        $this->assertCount(2, $this->_agenda($member));
    }

    /**
     * Scénario: Une réactivation avec le même compte ne duplique pas les événements
     *   Étant donné une réservation à venir déjà copiée dans l'agenda du pilote
     *   Et un arrêt de la synchronisation
     *   Quand le pilote relie à nouveau le même compte Google
     *   Et que la synchronisation passe
     *   Alors la réservation figure une seule fois dans son agenda
     */
    public function test_reactivation_does_not_duplicate()
    {
        $pilot = $this->_linked();
        $this->_reservation($pilot);
        $this->sync->process_queue();
        $this->sync->stop($pilot);

        $this->sync->activate($pilot, 'code-2');
        $this->sync->process_queue();

        $this->assertCount(1, $this->client->calls_of('insert_event'));
    }

    // -------------------------------------------------------------------------
    // Erreurs
    // -------------------------------------------------------------------------

    /**
     * Scénario: Google injoignable : la mise à jour est retentée plus tard
     *   Étant donné un pilote qui a relié son agenda
     *   Et Google injoignable
     *   Quand il crée une réservation et que la synchronisation passe
     *   Alors la réservation reste à synchroniser, avec un nouvel essai prévu plus tard
     *   Quand Google redevient joignable et que l'essai est dû
     *   Alors l'événement apparaît dans son agenda
     */
    public function test_transient_error_is_retried_later()
    {
        $pilot = $this->_linked();
        $refresh = 'renouvellement-' . count($this->client->calls_of('exchange_code'));
        $this->client->token_errors[$refresh] = Google_agenda_exception::PASSAGERE;
        $id = $this->_reservation($pilot);

        $stats = $this->sync->process_queue();

        $row = $this->_queue_row($id);
        $this->assertNotEmpty($row);
        $this->assertSame(1, (int) $row['tentatives']);
        $this->assertGreaterThan(date('Y-m-d H:i:s'), $row['prochain_essai_at']);
        $this->assertSame(1, $stats['reportees']);
        $this->assertSame(1, (int) $this->_liaison($pilot)['echecs_consecutifs']);
        $this->assertSame('active', $this->_liaison($pilot)['etat']);

        unset($this->client->token_errors[$refresh]);
        $this->CI->db->where('id', $row['id'])->update('agenda_google_file', array('prochain_essai_at' => date('Y-m-d H:i:s', time() - 1)));
        $this->sync->process_queue();
        $this->assertCount(1, $this->_agenda($pilot));
        $this->assertEmpty($this->_queue_row($id));
    }

    /**
     * Scénario: Une autorisation retirée suspend la synchronisation et prévient le membre
     *   Étant donné un pilote qui a relié son agenda
     *   Et qui a retiré l'accès de GVV dans son compte Google
     *   Quand une de ses réservations est modifiée et que la synchronisation passe
     *   Alors sa synchronisation est suspendue avec sa cause
     *   Et il reçoit un e-mail l'invitant à relier à nouveau son agenda
     *   Et la réservation n'attend plus d'être synchronisée
     */
    public function test_invalid_authorization_suspends_and_notifies()
    {
        $pilot = $this->_linked();
        $refresh = 'renouvellement-' . count($this->client->calls_of('exchange_code'));
        $this->client->token_errors[$refresh] = Google_agenda_exception::AUTORISATION;
        $id = $this->_reservation($pilot);

        $stats = $this->sync->process_queue();

        $liaison = $this->_liaison($pilot);
        $this->assertSame('suspendue', $liaison['etat']);
        $this->assertNotEmpty($liaison['cause_suspension']);
        $this->assertNull($liaison['jeton_renouvellement']);
        $this->assertSame(array($pilot), $this->sync->suspension_mails);
        $this->assertSame(1, $stats['suspendues']);
        $this->assertEmpty($this->_queue_row($id));

        $listed = array_column($this->CI->agenda_google_liaisons_model->suspended(), 'mlogin');
        $this->assertContains($pilot, $listed, 'l\'administrateur voit la liaison suspendue');
    }

    /**
     * Scénario: La réservation est enregistrée même si elle ne peut pas être inscrite pour la synchronisation
     *   Étant donné une panne de la file de synchronisation
     *   Quand un pilote crée une réservation
     *   Alors la réservation est enregistrée normalement
     */
    public function test_reservation_saved_even_if_queueing_fails()
    {
        $pilot = $this->_member();
        $this->reservations->set_agenda_queue(new class {
            public function enqueue($id, $author) { throw new RuntimeException('file indisponible'); }
        });

        $id = $this->_reservation($pilot);

        $this->assertGreaterThan(0, $id);
        $this->assertNotEmpty($this->CI->db->get_where('reservations', array('id' => $id))->row_array());
    }

    /**
     * Scénario: Un membre qui perd le droit de réserver voit sa synchronisation arrêtée
     *   Étant donné un pilote qui a relié son agenda
     *   Quand il perd tous ses droits de réservation
     *   Et qu'une de ses réservations est synchronisée
     *   Alors sa synchronisation est arrêtée
     *   Et son autorisation est retirée chez Google
     */
    public function test_member_losing_booking_right_is_stopped()
    {
        $pilot = $this->_linked();
        $user = $this->CI->db->get_where('users', array('username' => $pilot))->row_array();
        $this->CI->db->delete('user_roles_per_section', array('user_id' => $user['id']));
        $this->_reservation($pilot);

        $this->sync->process_queue();

        $this->assertSame('arretee', $this->_liaison($pilot)['etat']);
        $this->assertCount(1, $this->client->calls_of('revoke'));
        $this->assertSame(array(), $this->client->calls_of('insert_event'));
    }

    /**
     * Scénario: Fonction désactivée pour le club : plus aucune mise à jour
     *   Étant donné un pilote qui a relié son agenda
     *   Et une réservation à synchroniser
     *   Quand l'administrateur désactive la fonction pour le club
     *   Alors la synchronisation ne s'adresse plus à Google
     */
    public function test_disabled_module_sends_nothing()
    {
        $pilot = $this->_linked();
        $this->_reservation($pilot);
        $this->CI->config->set_item('agenda_google_sync', FALSE);

        $stats = $this->sync->process_queue();

        $this->assertTrue($stats['inactif']);
        $this->assertSame(array(), $this->client->calls_of('insert_event'));
    }

    /**
     * Scénario: La synchronisation suit le membre dont l'identifiant est renommé
     *   Étant donné un pilote qui a relié son agenda
     *   Quand son identifiant de connexion est renommé
     *   Alors sa synchronisation reste active sous son nouvel identifiant
     */
    public function test_rename_keeps_liaison()
    {
        $pilot = $this->_linked();
        $new_login = $pilot . 'r';
        $this->CI->load->model('membres_model');

        $result = $this->CI->membres_model->execute_rename($pilot, $new_login, 'test_user');

        $this->assertTrue($result['success'], $result['message']);
        $this->assertSame('active', $this->_liaison($new_login)['etat']);
        $this->assertEmpty($this->_liaison($pilot));
    }

    /**
     * Scénario: La synchronisation est arrêtée avec la fiche du membre
     *   Étant donné un pilote qui a relié son agenda
     *   Quand sa fiche de membre est supprimée
     *   Alors son autorisation est retirée chez Google
     *   Et GVV ne conserve plus rien de sa liaison
     */
    public function test_member_deletion_stops_and_removes_liaison()
    {
        $pilot = $this->_linked();
        $this->CI->load->model('membres_model');
        // la suppression charge sa propre synchronisation : on lui fournit celle du test
        $this->CI->agenda_google_sync = $this->sync;
        $loader = $this->CI->load;
        $this->CI->load = new class($loader) {
            private $inner;
            public function __construct($inner) { $this->inner = $inner; }
            public function library($name, $params = NULL, $object = NULL) {
                if (strtolower($name) === 'agenda_google_sync') return TRUE;
                return $this->inner->library($name, $params, $object);
            }
            public function __call($m, $a) { return $this->inner->$m(...$a); }
        };
        try {
            $deleted = $this->CI->membres_model->delete(array('mlogin' => $pilot));
        } finally {
            $this->CI->load = $loader;
        }

        $this->assertTrue($deleted);
        $this->assertEmpty($this->_liaison($pilot));
        $this->assertCount(1, $this->client->calls_of('revoke'));
    }

    /**
     * Scénario: Une modification pendant la synchronisation n'est pas perdue
     *   Étant donné une réservation en attente de synchronisation
     *   Quand elle est modifiée à nouveau pendant que la synchronisation la traite
     *   Alors elle reste à synchroniser pour prendre en compte la dernière modification
     */
    public function test_reenqueue_during_processing_is_kept()
    {
        $pilot = $this->_member();
        $id = $this->_reservation($pilot);
        $row = $this->_queue_row($id);

        $this->CI->agenda_google_file_model->enqueue($id, $pilot);   // nouvelle modification
        $this->CI->agenda_google_file_model->done($row);              // fin du traitement commencé avant

        $this->assertNotEmpty($this->_queue_row($id));
    }
}
