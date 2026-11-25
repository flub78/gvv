<?php

require_once(__DIR__ . '/TransactionalTestCase.php');

/**
 * @feature Réservations
 *
 * Point d'écriture unique des réservations : toute création, modification
 * ou suppression passe par le modèle, qui prévient les rappels avec le bon
 * type d'événement et le bon auteur. La fusion et le renommage de membres
 * réaffectent les réservations sans prévenir les membres.
 *
 * Chaque test s'exécute dans une transaction annulée en tearDown().
 */
class ReservationsWritePointTest extends TransactionalTestCase
{
    /** @var Reservations_model */
    private $model;

    /** @var ReservationsWritePointReminderSpy */
    private $spy;

    private static $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->CI->load->model('reservations_model');
        $this->model = $this->CI->reservations_model;
        $this->spy = new ReservationsWritePointReminderSpy($this->CI->db);
        $this->model->set_reminder($this->spy);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function _login(string $prefix): string
    {
        return $prefix . '_' . getmypid() . '_' . (++self::$seq);
    }

    private function _create_membre(string $prefix = 'wp'): string
    {
        $login = $this->_login($prefix);
        $this->CI->db->insert('membres', [
            'mlogin'  => $login,
            'mnom'    => 'Nom_' . $login,
            'mprenom' => 'Prenom_' . $login,
            'memail'  => $login . '@test.invalid',
            'msexe'   => 'M',
            'actif'   => 1,
            'ext'     => 0,
            'm25ans'  => 0,
        ]);
        return $login;
    }

    private function _reservation_data(string $pilot, ?string $instructor = null): array
    {
        return [
            'aircraft_id'          => 'F-TWPT',
            'pilot_member_id'      => $pilot,
            'instructor_member_id' => $instructor,
            'start_datetime'       => date('Y-m-d 10:00:00', strtotime('+3 years')),
            'end_datetime'         => date('Y-m-d 11:00:00', strtotime('+3 years')),
            'status'               => 'reservation',
            'section_id'           => 1,
            'created_by'           => $pilot,
        ];
    }

    private function _get(int $id): ?array
    {
        $row = $this->CI->db->get_where('reservations', ['id' => $id])->row_array();
        return $row ?: null;
    }

    // -------------------------------------------------------------------------
    // Création, modification, suppression
    // -------------------------------------------------------------------------

    /**
     * Scénario: La création d'une réservation prévient les rappels
     *   Étant donné un pilote du club
     *   Quand il crée une réservation
     *   Alors la réservation est enregistrée
     *   Et les rappels sont prévenus d'une création faite par ce pilote
     */
    public function test_create_notifies_create_with_author()
    {
        $pilot = $this->_create_membre();
        $id = $this->model->create_reservation($this->_reservation_data($pilot));

        $this->assertGreaterThan(0, $id);
        $this->assertNotNull($this->_get($id));
        $this->assertSame([[$id, 'create', $pilot]], $this->spy->calls());
    }

    /**
     * Scénario: La modification d'une réservation prévient les rappels
     *   Étant donné une réservation existante
     *   Quand un utilisateur en change les horaires
     *   Alors les nouveaux horaires sont enregistrés
     *   Et les rappels sont prévenus d'une modification faite par cet utilisateur
     */
    public function test_update_notifies_update_with_author()
    {
        $pilot = $this->_create_membre();
        $id = $this->model->create_reservation($this->_reservation_data($pilot));
        $this->spy->reset();

        $new_start = date('Y-m-d 14:00:00', strtotime('+3 years'));
        $ok = $this->model->update_reservation($id, [
            'start_datetime' => $new_start,
            'end_datetime'   => date('Y-m-d 15:00:00', strtotime('+3 years')),
        ]);

        $this->assertTrue($ok);
        $this->assertSame($new_start, $this->_get($id)['start_datetime']);
        $this->assertSame([[$id, 'update', $this->CI->dx_auth->get_username()]], $this->spy->calls());
    }

    /**
     * Scénario: Une modification qui ne change rien reste une réussite
     *   Étant donné une réservation existante
     *   Quand elle est enregistrée sans changement
     *   Alors l'enregistrement réussit
     *   Et les rappels sont prévenus d'une modification
     */
    public function test_update_without_change_is_a_success()
    {
        $pilot = $this->_create_membre();
        $data = $this->_reservation_data($pilot);
        $id = $this->model->create_reservation($data);
        $this->spy->reset();

        $ok = $this->model->update_reservation($id, [
            'start_datetime' => $data['start_datetime'],
            'end_datetime'   => $data['end_datetime'],
        ]);

        $this->assertTrue($ok);
        $this->assertCount(1, $this->spy->calls());
    }

    /**
     * Scénario: La modification d'une réservation inexistante est refusée
     *   Étant donné une réservation qui n'existe pas
     *   Quand on tente de la modifier
     *   Alors la modification est refusée
     *   Et personne n'est prévenu
     */
    public function test_update_of_missing_reservation_fails_without_notification()
    {
        $ok = $this->model->update_reservation(999999999, ['notes' => 'x']);

        $this->assertFalse($ok);
        $this->assertSame([], $this->spy->calls());
    }

    /**
     * Scénario: La suppression d'une réservation prévient les rappels avant de la retirer
     *   Étant donné une réservation existante
     *   Quand un utilisateur la supprime
     *   Alors les rappels sont prévenus d'une annulation alors que la réservation est encore lisible
     *   Et la réservation n'existe plus ensuite
     */
    public function test_delete_notifies_cancel_before_deletion()
    {
        $pilot = $this->_create_membre();
        $id = $this->model->create_reservation($this->_reservation_data($pilot));
        $this->spy->reset();

        $ok = $this->model->delete_reservation($id);

        $this->assertTrue($ok);
        $this->assertNull($this->_get($id));
        $this->assertSame([[$id, 'cancel', $this->CI->dx_auth->get_username()]], $this->spy->calls());
        $this->assertSame([true], $this->spy->existed_when_notified());
    }

    /**
     * Scénario: Supprimer une réservation inexistante ne prévient personne
     *   Étant donné une réservation qui n'existe pas
     *   Quand on tente de la supprimer
     *   Alors la suppression échoue
     *   Et personne n'est prévenu
     */
    public function test_delete_of_missing_reservation_fails_without_notification()
    {
        $this->assertFalse($this->model->delete_reservation(999999999));
        $this->assertSame([], $this->spy->calls());
    }

    /**
     * Scénario: Une panne des rappels n'empêche pas l'enregistrement
     *   Étant donné que l'envoi des rappels échoue
     *   Quand un pilote crée une réservation
     *   Alors la réservation est quand même enregistrée
     */
    public function test_reminder_failure_does_not_cancel_the_write()
    {
        $this->spy->fail = true;
        $pilot = $this->_create_membre();
        $id = $this->model->create_reservation($this->_reservation_data($pilot));

        $this->assertGreaterThan(0, $id);
        $this->assertNotNull($this->_get($id));
    }

    // -------------------------------------------------------------------------
    // Fusion et renommage de membres
    // -------------------------------------------------------------------------

    /**
     * Scénario: Le renommage d'un membre suit ses réservations
     *   Étant donné un membre pilote d'une réservation et instructeur d'une autre
     *   Quand son identifiant est renommé
     *   Alors les deux réservations portent le nouvel identifiant
     *   Et le compte rendu du renommage compte une réservation comme pilote et une comme instructeur
     */
    public function test_rename_reassigns_reservations()
    {
        $member = $this->_create_membre('ren');
        $other = $this->_create_membre('oth');
        $as_pilot = $this->model->create_reservation($this->_reservation_data($member));
        $as_instructor = $this->model->create_reservation($this->_reservation_data($other, $member));
        $this->spy->reset();

        $this->CI->load->model('membres_model');
        $new_login = $this->_login('renamed');
        $result = $this->CI->membres_model->execute_rename($member, $new_login, 'test_user');

        $this->assertTrue($result['success'], $result['message']);
        $this->assertSame($new_login, $this->_get($as_pilot)['pilot_member_id']);
        $this->assertSame($new_login, $this->_get($as_instructor)['instructor_member_id']);
        $this->assertSame($other, $this->_get($as_instructor)['pilot_member_id']);
        $this->assertEquals(1, $result['stats']['reservations']['pilot_member_id']);
        $this->assertEquals(1, $result['stats']['reservations']['instructor_member_id']);
    }

    /**
     * Scénario: La fusion de deux fiches transfère les réservations
     *   Étant donné deux fiches en doublon pour le même membre
     *   Et une réservation sur la fiche qui va disparaître
     *   Quand les deux fiches sont fusionnées
     *   Alors la réservation appartient à la fiche conservée
     */
    public function test_merge_reassigns_reservations()
    {
        $source = $this->_create_membre('src');
        $destination = $this->_create_membre('dst');
        $id = $this->model->create_reservation($this->_reservation_data($source));
        $this->spy->reset();

        $this->CI->load->model('membres_fusion_model');
        $result = $this->CI->membres_fusion_model->fusionner($source, $destination);

        $this->assertTrue($result['success'], $result['error']);
        $this->assertSame($destination, $this->_get($id)['pilot_member_id']);
    }

    /**
     * Scénario: Seules les réservations du membre concerné sont réaffectées
     *   Étant donné deux membres ayant chacun une réservation
     *   Quand l'un des deux est réaffecté à un nouvel identifiant
     *   Alors seule sa réservation est signalée comme modifiée
     *   Et la réservation de l'autre membre est inchangée
     */
    public function test_reassign_member_returns_only_touched_reservations()
    {
        $member = $this->_create_membre('re');
        $other = $this->_create_membre('ot');
        $mine = $this->model->create_reservation($this->_reservation_data($member));
        $theirs = $this->model->create_reservation($this->_reservation_data($other));

        $new_login = $this->_login('new');
        $ids = $this->model->reassign_member($member, $new_login);

        $this->assertSame([$mine], $ids);
        $this->assertSame($other, $this->_get($theirs)['pilot_member_id']);
    }
}

/**
 * Remplace le mécanisme de rappel : enregistre les événements reçus.
 */
class ReservationsWritePointReminderSpy
{
    public $fail = false;
    private $db;
    private $calls = [];
    private $existed = [];

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function handle_event($reservation_id, $event_type, $triggered_by)
    {
        $this->calls[] = [(int) $reservation_id, $event_type, $triggered_by];
        $this->existed[] = $this->db->where('id', $reservation_id)->count_all_results('reservations') > 0;
        if ($this->fail) {
            throw new Exception('reminder failure');
        }
        return true;
    }

    public function calls(): array
    {
        return $this->calls;
    }

    public function existed_when_notified(): array
    {
        return $this->existed;
    }

    public function reset(): void
    {
        $this->calls = [];
        $this->existed = [];
    }
}
