<?php

use PHPUnit\Framework\TestCase;

require_once APPPATH . 'libraries/Reservation_reminder.php';

/**
 * Testable subclass — bypasses CI bootstrap and DB to allow pure unit testing.
 *
 * Overrides:
 *   - __construct()        : skip get_instance() / model loading
 *   - _dispatch()          : capture calls, never actually send
 *   - _load_reservation()  : return injected fixture
 *   - _log_skipped()       : capture calls
 */
class ReservationReminderTestable extends Reservation_reminder
{
    public $dispatch_calls = array();
    public $skipped_calls  = array();
    public $fake_reservation = null;

    public function __construct()
    {
        // Intentionally skip parent — avoids get_instance() and model
    }

    public function _dispatch(
        $reservation, $recipient, $action_type, $source, $event_type, $idempotency_key
    ) {
        $this->dispatch_calls[] = compact(
            'reservation', 'recipient', 'action_type', 'source', 'event_type', 'idempotency_key'
        );
        return true;
    }

    protected function _load_reservation($reservation_id)
    {
        return $this->fake_reservation;
    }

    protected function _log_skipped($key, $reservation_id, $source, $action_type, $reason)
    {
        $this->skipped_calls[] = compact('key', 'reservation_id', 'source', 'action_type', 'reason');
    }
}

/**
 * Unit tests for Reservation_reminder — pure logic, no DB, no SMTP.
 *
 * Covers (from plan §11.1):
 *   - _get_recipients()       : routing rules for all creator scenarios
 *   - _build_idempotency_key(): determinism and uniqueness
 *   - _compose_email_body()   : required fields present (EF-023)
 *   - _compose_sms_body()     : conciseness (EF-036)
 *   - handle_event()          : skips when reservation not found
 */
class ReservationReminderTest extends TestCase
{
    /** @var ReservationReminderTestable */
    private $lib;

    private $reservation_with_instructor;
    private $reservation_pilot_only;

    protected function setUp(): void
    {
        $this->lib = new ReservationReminderTestable();

        $this->reservation_with_instructor = array(
            'id'                               => 42,
            'aircraft_id'                      => 'F-GSRP',
            'macimmat'                         => 'F-GSRP',
            'macmodele'                        => 'DR400',
            'start_datetime'                   => '2026-07-01 10:00:00',
            'end_datetime'                     => '2026-07-01 12:00:00',
            'status'                           => 'vol_local',
            'purpose'                          => null,
            'notes'                            => null,
            'section_id'                       => 1,
            'reservation_created_by'           => 'admin',
            'pilot_member_id'                  => 'pilote1',
            'pilot_prenom'                     => 'Jean',
            'pilot_nom'                        => 'Dupont',
            'pilot_email'                      => 'jean.dupont@example.com',
            'pilot_phone'                      => '0600000001',
            'pilot_reminder_channel'           => 'email',
            'pilot_reminder_period_hours'      => 24,
            'instructor_member_id'             => 'instr1',
            'instructor_prenom'                => 'Marie',
            'instructor_nom'                   => 'Martin',
            'instructor_email'                 => 'marie.martin@example.com',
            'instructor_phone'                 => '0600000002',
            'instructor_reminder_channel'      => 'email',
            'instructor_reminder_period_hours' => 24,
        );

        $this->reservation_pilot_only = array_merge($this->reservation_with_instructor, array(
            'instructor_member_id'             => null,
            'instructor_prenom'                => null,
            'instructor_nom'                   => null,
            'instructor_email'                 => null,
            'instructor_phone'                 => null,
            'instructor_reminder_channel'      => null,
            'instructor_reminder_period_hours' => null,
        ));
    }

    // =========================================================================
    // _get_recipients — routing rules
    // =========================================================================

    public function testCreatorIsPilotNotifiesInstructor()
    {
        $recipients = $this->lib->_get_recipients(
            $this->reservation_with_instructor,
            'pilote1'  // creator = pilot
        );

        $this->assertCount(1, $recipients);
        $this->assertEquals('instr1',     $recipients[0]['login']);
        $this->assertEquals('instructor', $recipients[0]['role']);
    }

    public function testCreatorIsInstructorNotifiesPilot()
    {
        $recipients = $this->lib->_get_recipients(
            $this->reservation_with_instructor,
            'instr1'   // creator = instructor
        );

        $this->assertCount(1, $recipients);
        $this->assertEquals('pilote1', $recipients[0]['login']);
        $this->assertEquals('pilot',   $recipients[0]['role']);
    }

    public function testCreatorIsThirdPartyNotifiesBothCrew()
    {
        $recipients = $this->lib->_get_recipients(
            $this->reservation_with_instructor,
            'admin'    // third party
        );

        $this->assertCount(2, $recipients);
        $logins = array_column($recipients, 'login');
        $this->assertContains('pilote1', $logins);
        $this->assertContains('instr1',  $logins);
    }

    public function testSoloPilotCreatorYieldsNoRecipients()
    {
        // Creator is the only crew member → nobody else to notify
        $recipients = $this->lib->_get_recipients(
            $this->reservation_pilot_only,
            'pilote1'
        );

        $this->assertCount(0, $recipients);
    }

    public function testThirdPartyOnSoloPilotReservationNotifiesOnlyPilot()
    {
        $recipients = $this->lib->_get_recipients(
            $this->reservation_pilot_only,
            'admin'
        );

        $this->assertCount(1, $recipients);
        $this->assertEquals('pilote1', $recipients[0]['login']);
    }

    // =========================================================================
    // _build_idempotency_key
    // =========================================================================

    public function testIdempotencyKeyIsDeterministic()
    {
        $key1 = $this->lib->_build_idempotency_key('scheduled', 42, 'pilote1', '2026-07-01');
        $key2 = $this->lib->_build_idempotency_key('scheduled', 42, 'pilote1', '2026-07-01');

        $this->assertEquals($key1, $key2);
        $this->assertEquals(40, strlen($key1), 'Doit être un SHA-1 (40 chars hex)');
    }

    public function testIdempotencyKeyDiffersOnDifferentDate()
    {
        $key1 = $this->lib->_build_idempotency_key('scheduled', 42, 'pilote1', '2026-07-01');
        $key2 = $this->lib->_build_idempotency_key('scheduled', 42, 'pilote1', '2026-07-02');

        $this->assertNotEquals($key1, $key2);
    }

    public function testIdempotencyKeyDiffersOnDifferentEventType()
    {
        $key1 = $this->lib->_build_idempotency_key('event', 42, 'pilote1', 'create', 100);
        $key2 = $this->lib->_build_idempotency_key('event', 42, 'pilote1', 'update', 100);

        $this->assertNotEquals($key1, $key2);
    }

    // =========================================================================
    // _compose_email_body — EF-023 required fields
    // =========================================================================

    public function testEmailBodyContainsAllRequiredFields()
    {
        $body = $this->lib->_compose_email_body(
            $this->reservation_with_instructor,
            'scheduled_reminder',
            'pilot',
            null,
            'cron'
        );

        $this->assertStringContainsString('F-GSRP',     $body, 'Aéronef');
        $this->assertStringContainsString('Jean',       $body, 'Prénom pilote');
        $this->assertStringContainsString('Dupont',     $body, 'Nom pilote');
        $this->assertStringContainsString('Marie',      $body, 'Instructeur');
        $this->assertStringContainsString('vol_local',  $body, 'Statut');
        $this->assertStringContainsString('01/07/2026', $body, 'Date');
        $this->assertStringContainsString('cron',       $body, 'Source déclenchement');
    }

    public function testEmailBodyEventNotificationLabelsModification()
    {
        $body = $this->lib->_compose_email_body(
            $this->reservation_with_instructor,
            'event_notification',
            'instructor',
            'update',
            'event_update'
        );

        $this->assertStringContainsString('modifi', strtolower($body));
    }

    public function testEmailBodyEventCancelLabelsAnnulation()
    {
        $body = $this->lib->_compose_email_body(
            $this->reservation_with_instructor,
            'event_notification',
            'pilot',
            'cancel',
            'event_cancel'
        );

        $this->assertStringContainsString('annul', strtolower($body));
    }

    // =========================================================================
    // _compose_sms_body — EF-036 conciseness
    // =========================================================================

    public function testSmsBodyFitsIn160Chars()
    {
        $body = $this->lib->_compose_sms_body(
            $this->reservation_with_instructor,
            'scheduled_reminder',
            'pilot'
        );

        $this->assertLessThanOrEqual(160, strlen($body));
        $this->assertStringContainsString('F-GSRP', $body);
    }

    public function testSmsBodyMentionsRoleAndAircraft()
    {
        $body = $this->lib->_compose_sms_body(
            $this->reservation_with_instructor,
            'event_notification',
            'instructor',
            'cancel'
        );

        $this->assertStringContainsString('F-GSRP',   $body);
        $this->assertStringContainsString('instructor', $body);
        $this->assertStringContainsString('Annul',      $body);
    }

    // =========================================================================
    // _compose_daily_email_body — daily summary
    // =========================================================================

    public function testDailyEmailBodyContainsDateHeader()
    {
        $body = $this->lib->_compose_daily_email_body(
            array('login' => 'pilote1', 'role' => 'pilot'),
            '2026-07-04',
            array($this->reservation_with_instructor)
        );

        $this->assertStringContainsString('04/07/2026', $body);
        $this->assertStringContainsString('F-GSRP',     $body);
        $this->assertStringContainsString('Jean',       $body);
        $this->assertStringContainsString('Marie',      $body);
    }

    public function testDailyEmailBodyListsMultipleFlights()
    {
        $second = $this->reservation_with_instructor;
        $second['id']             = 43;
        $second['start_datetime'] = '2026-07-04 14:00:00';
        $second['end_datetime']   = '2026-07-04 16:00:00';
        $second['pilot_prenom']   = 'Pierre';
        $second['pilot_nom']      = 'Leclerc';

        $body = $this->lib->_compose_daily_email_body(
            array('login' => 'instr1', 'role' => 'instructor'),
            '2026-07-04',
            array($this->reservation_with_instructor, $second)
        );

        $this->assertStringContainsString('Jean',   $body);
        $this->assertStringContainsString('Pierre', $body);
    }

    public function testDailyEmailBodyShowsDashWhenNoInstructor()
    {
        $body = $this->lib->_compose_daily_email_body(
            array('login' => 'pilote1', 'role' => 'pilot'),
            '2026-07-04',
            array($this->reservation_pilot_only)
        );

        $this->assertStringContainsString('–', $body, 'Cellule instructeur doit afficher –');
    }

    // =========================================================================
    // _compose_daily_sms_body — single vs multiple flights
    // =========================================================================

    public function testDailySmsBodySingleFlightContainsDetails()
    {
        $recipient = array('login' => 'pilote1', 'role' => 'pilot');
        $body = $this->lib->_compose_daily_sms_body(
            $recipient,
            '2026-07-04',
            array($this->reservation_with_instructor)
        );

        $this->assertLessThanOrEqual(160, strlen($body), 'SMS doit tenir en 160 chars');
        $this->assertStringContainsString('F-GSRP', $body);
        $this->assertStringContainsString('04/07',  $body);
        $this->assertStringContainsString('10:00',  $body);
    }

    public function testDailySmsBodySingleFlightWithInstructorMentionsInstructor()
    {
        $recipient = array('login' => 'pilote1', 'role' => 'pilot');
        $body = $this->lib->_compose_daily_sms_body(
            $recipient,
            '2026-07-04',
            array($this->reservation_with_instructor)
        );

        $this->assertStringContainsString('Marie', $body);
    }

    public function testDailySmsBodyMultipleFlightsContainsLinkNotDetails()
    {
        $second = $this->reservation_with_instructor;
        $second['id']             = 43;
        $second['start_datetime'] = '2026-07-04 14:00:00';
        $second['end_datetime']   = '2026-07-04 16:00:00';

        $recipient = array('login' => 'instr1', 'role' => 'instructor');
        $body = $this->lib->_compose_daily_sms_body(
            $recipient,
            '2026-07-04',
            array($this->reservation_with_instructor, $second)
        );

        $this->assertStringContainsString('2 réservations', $body);
        $this->assertStringContainsString('mes_reservations', $body);
        $this->assertStringNotContainsString('F-GSRP', $body, 'Le SMS multi ne doit pas lister les aéronefs');
    }

    // =========================================================================
    // handle_event — guard: missing reservation
    // =========================================================================

    public function testHandleEventSkipsWhenReservationNotFound()
    {
        $this->lib->fake_reservation = null;  // simulate missing reservation

        $result = $this->lib->handle_event(999, 'create', 'admin');

        $this->assertFalse($result);
        $this->assertCount(0, $this->lib->dispatch_calls, 'Aucun envoi ne doit être tenté');
        $this->assertCount(1, $this->lib->skipped_calls,  'Un log skipped doit être écrit');
        $this->assertStringContainsString('not found', $this->lib->skipped_calls[0]['reason']);
    }

    public function testHandleEventDispatchesToRecipients()
    {
        $this->lib->fake_reservation = $this->reservation_with_instructor;

        // Creator is third party → both crew should be notified
        $result = $this->lib->handle_event(42, 'create', 'admin');

        $this->assertTrue($result);
        $this->assertCount(2, $this->lib->dispatch_calls);
        $logins = array_column(array_column($this->lib->dispatch_calls, 'recipient'), 'login');
        $this->assertContains('pilote1', $logins);
        $this->assertContains('instr1',  $logins);
    }
}
