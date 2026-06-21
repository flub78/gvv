<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for Reservation_reminder_model
 *
 * Validates CRUD on reservation_reminder_log and the idempotency guarantee.
 * All tests run inside a transaction that is rolled back in tearDown.
 *
 * Relies on reservation id=51 (section_id=3, status='vld', future date) existing in the DB.
 * Member login '9992' (LACOFFRETTE) is used for preference tests.
 */
class ReservationReminderModelTest extends TestCase
{
    /** @var object CI instance */
    private $CI;
    /** @var Reservation_reminder_model */
    private $model;

    private $test_reservation_id = 51;
    private $test_member_login   = '9992';

    protected function setUp(): void
    {
        $this->CI = &get_instance();

        if (!class_exists('Common_Model')) {
            require_once APPPATH . 'models/common_model.php';
        }
        if (!class_exists('Reservation_reminder_model')) {
            require_once APPPATH . 'models/reservation_reminder_model.php';
        }

        $this->CI->db->trans_start();
        $this->model = new Reservation_reminder_model();
    }

    protected function tearDown(): void
    {
        $this->CI->db->trans_rollback();
    }

    // -------------------------------------------------------------------------
    // log_attempt / already_sent
    // -------------------------------------------------------------------------

    public function testLogAttemptInsertsRecord()
    {
        $key = 'test_key_' . uniqid();

        $id = $this->model->log_attempt(array(
            'idempotency_key' => $key,
            'reservation_id'  => $this->test_reservation_id,
            'trigger_source'  => 'cron',
            'action_type'     => 'scheduled_reminder',
            'status'          => 'success',
            'channel'         => 'email',
            'provider'        => 'smtp',
            'message_body'    => 'Test body',
        ));

        $this->assertNotFalse($id, 'log_attempt doit retourner un id après insertion');
        $this->assertGreaterThan(0, $id);
    }

    public function testAlreadySentReturnsFalseForUnknownKey()
    {
        $this->assertFalse(
            $this->model->already_sent('nonexistent_key_' . uniqid()),
            'already_sent doit retourner FALSE pour une clé inconnue'
        );
    }

    public function testAlreadySentReturnsTrueAfterSuccessInsert()
    {
        $key = 'test_sent_' . uniqid();

        $this->model->log_attempt(array(
            'idempotency_key' => $key,
            'reservation_id'  => $this->test_reservation_id,
            'trigger_source'  => 'event_create',
            'action_type'     => 'event_notification',
            'status'          => 'success',
            'channel'         => 'email',
        ));

        $this->assertTrue(
            $this->model->already_sent($key),
            'already_sent doit retourner TRUE après un envoi success'
        );
    }

    public function testAlreadySentIgnoresSkippedStatus()
    {
        $key = 'test_skipped_' . uniqid();

        $this->model->log_attempt(array(
            'idempotency_key' => $key,
            'reservation_id'  => $this->test_reservation_id,
            'trigger_source'  => 'cron',
            'action_type'     => 'scheduled_reminder',
            'status'          => 'skipped',
        ));

        // skipped ne compte pas comme "déjà envoyé"
        $this->assertFalse(
            $this->model->already_sent($key),
            'already_sent doit retourner FALSE pour un statut skipped'
        );
    }

    public function testUniqueConstraintOnIdempotencyKey()
    {
        $key = 'test_unique_' . uniqid();

        $data = array(
            'idempotency_key' => $key,
            'reservation_id'  => $this->test_reservation_id,
            'trigger_source'  => 'cron',
            'action_type'     => 'scheduled_reminder',
            'status'          => 'success',
        );

        $id1 = $this->model->log_attempt($data);
        $id2 = $this->model->log_attempt($data); // INSERT IGNORE → doit retourner false (0 rows)

        $this->assertNotFalse($id1, 'Premier insert doit réussir');
        $this->assertFalse($id2, 'Second insert avec même clé doit retourner false (INSERT IGNORE)');

        // Vérifier qu'une seule ligne existe
        $rows = $this->CI->db
            ->from('reservation_reminder_log')
            ->where('idempotency_key', $key)
            ->get()
            ->result_array();
        $this->assertCount(1, $rows, 'Une seule entrée doit exister pour la même clé');
    }

    // -------------------------------------------------------------------------
    // get_pending_reservations
    // -------------------------------------------------------------------------

    public function testGetPendingReservationsReturnsActiveFutureReservations()
    {
        // Insérer une réservation de vol dans la section du test (section_id=1)
        $future = date('Y-m-d H:i:s', strtotime('+2 hours'));
        $this->CI->db->insert('reservations', array(
            'aircraft_id'     => 'F-GSRP',
            'start_datetime'  => $future,
            'end_datetime'    => date('Y-m-d H:i:s', strtotime('+3 hours')),
            'pilot_member_id' => $this->test_member_login,
            'status'          => 'vol_local',
            'section_id'      => 1,
            'created_by'      => 'test',
        ));
        $flight_id = $this->CI->db->insert_id();

        $rows = $this->model->get_pending_reservations(9999);

        $this->assertIsArray($rows);
        $ids = array_column($rows, 'id');
        $this->assertContains(
            (string) $flight_id,
            $ids,
            'La réservation de vol insérée doit apparaître dans les rappels en attente'
        );

        foreach ($rows as $row) {
            $this->assertArrayHasKey('id', $row);
            $this->assertArrayHasKey('start_datetime', $row);
            $this->assertNotContains($row['status'], array('maintenance', 'unavailable'));
        }
    }

    public function testGetPendingReservationsExcludesNonFlightStatus()
    {
        // Les statuts maintenance et unavailable ne doivent pas déclencher de rappels
        $future = date('Y-m-d H:i:s', strtotime('+2 hours'));
        $this->CI->db->insert('reservations', array(
            'aircraft_id'    => 'F-GSRP',
            'start_datetime' => $future,
            'end_datetime'   => date('Y-m-d H:i:s', strtotime('+3 hours')),
            'status'         => 'maintenance',
            'section_id'     => 1,
            'created_by'     => 'test',
        ));
        $maintenance_id = $this->CI->db->insert_id();

        $rows = $this->model->get_pending_reservations(9999);
        $ids  = array_column($rows, 'id');

        $this->assertNotContains(
            (string) $maintenance_id,
            $ids,
            'Les réservations maintenance ne doivent pas déclencher de rappels'
        );
    }

    // -------------------------------------------------------------------------
    // get_log_for_reservation
    // -------------------------------------------------------------------------

    public function testGetLogForReservationReturnsEntriesForThatReservation()
    {
        $key = 'test_log_res_' . uniqid();
        $this->model->log_attempt(array(
            'idempotency_key' => $key,
            'reservation_id'  => $this->test_reservation_id,
            'trigger_source'  => 'cron',
            'action_type'     => 'scheduled_reminder',
            'status'          => 'success',
        ));

        $logs = $this->model->get_log_for_reservation($this->test_reservation_id);
        $this->assertIsArray($logs);
        $this->assertGreaterThan(0, count($logs));

        foreach ($logs as $entry) {
            $this->assertEquals($this->test_reservation_id, $entry['reservation_id']);
        }
    }

    // -------------------------------------------------------------------------
    // Member preferences
    // -------------------------------------------------------------------------

    public function testGetMemberPreferencesReturnsDefaults()
    {
        $prefs = $this->model->get_member_preferences('nonexistent_login_xyz');
        $this->assertEquals('email', $prefs['reminder_channel']);
        $this->assertEquals(24,      $prefs['reminder_period_hours']);
    }

    public function testSaveAndReloadMemberPreferences()
    {
        $ok = $this->model->save_member_preferences($this->test_member_login, 'email+sms', 12);
        $this->assertTrue($ok, 'save_member_preferences doit retourner TRUE');

        $prefs = $this->model->get_member_preferences($this->test_member_login);
        $this->assertEquals('email+sms', $prefs['reminder_channel']);
        $this->assertEquals(12,          $prefs['reminder_period_hours']);
    }

    public function testSavePreferencesRejectsInvalidChannel()
    {
        $ok = $this->model->save_member_preferences($this->test_member_login, 'telegram', 24);
        $this->assertFalse($ok, 'Un canal invalide doit être rejeté');
    }
}
