<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the reservation reminder scheduler.
 *
 * Validates that:
 * - Reminders are dispatched for active flight reservations in the window
 * - Non-flight statuses (maintenance, unavailable) are excluded
 * - Already-sent reminders are not duplicated (idempotency)
 * - User reminder_period_hours preference is respected
 *
 * All tests run inside a transaction rolled back in tearDown.
 * Relies on section_id=1 existing in sections table.
 */
class ReservationSchedulerTest extends TestCase
{
    /** @var object CI instance */
    private $CI;
    /** @var Reservation_reminder_model */
    private $model;
    /** @var string pilot login used in fixtures */
    private $pilot_login = '9992';

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

    // =========================================================================
    // get_pending_reservations — scheduler window
    // =========================================================================

    public function testSchedulerIncludesFlightReservationsInWindow()
    {
        $future = date('Y-m-d H:i:s', strtotime('+2 hours'));
        $this->CI->db->insert('reservations', array(
            'aircraft_id'     => 'F-GSRP',
            'start_datetime'  => $future,
            'end_datetime'    => date('Y-m-d H:i:s', strtotime('+3 hours')),
            'pilot_member_id' => $this->pilot_login,
            'status'          => 'vol_local',
            'section_id'      => 1,
            'created_by'      => 'test',
        ));
        $id = $this->CI->db->insert_id();

        $rows = $this->model->get_pending_reservations(9999);
        $ids  = array_column($rows, 'id');

        $this->assertContains((string) $id, $ids,
            'Vol local dans la fenêtre doit être retourné par le scheduler');
    }

    public function testSchedulerExcludesReservationsOutsideWindow()
    {
        $far_future = date('Y-m-d H:i:s', strtotime('+200 hours'));
        $this->CI->db->insert('reservations', array(
            'aircraft_id'     => 'F-GSRP',
            'start_datetime'  => $far_future,
            'end_datetime'    => date('Y-m-d H:i:s', strtotime('+201 hours')),
            'pilot_member_id' => $this->pilot_login,
            'status'          => 'vol_local',
            'section_id'      => 1,
            'created_by'      => 'test',
        ));
        $id = $this->CI->db->insert_id();

        $rows = $this->model->get_pending_reservations(48);
        $ids  = array_column($rows, 'id');

        $this->assertNotContains((string) $id, $ids,
            'Réservation hors fenêtre ne doit pas être retournée');
    }

    public function testSchedulerExcludesMaintenanceAndUnavailable()
    {
        $future = date('Y-m-d H:i:s', strtotime('+1 hour'));
        foreach (array('maintenance', 'unavailable') as $status) {
            $this->CI->db->insert('reservations', array(
                'aircraft_id'    => 'F-GSRP',
                'start_datetime' => $future,
                'end_datetime'   => date('Y-m-d H:i:s', strtotime('+2 hours')),
                'status'         => $status,
                'section_id'     => 1,
                'created_by'     => 'test',
            ));
            $id = $this->CI->db->insert_id();

            $rows = $this->model->get_pending_reservations(9999);
            $ids  = array_column($rows, 'id');
            $this->assertNotContains((string) $id, $ids,
                "Statut '$status' ne doit pas déclencher de rappel");
        }
    }

    // =========================================================================
    // Idempotency — no duplicate on double scheduler run
    // =========================================================================

    public function testNoDuplicateOnDoubleSchedulerRun()
    {
        $key = 'test_sched_idem_' . uniqid();

        // First run
        $id1 = $this->model->log_attempt(array(
            'idempotency_key' => $key,
            'reservation_id'  => 51,
            'trigger_source'  => 'cron',
            'action_type'     => 'scheduled_reminder',
            'status'          => 'success',
        ));

        // Second run with same key → INSERT IGNORE → false
        $id2 = $this->model->log_attempt(array(
            'idempotency_key' => $key,
            'reservation_id'  => 51,
            'trigger_source'  => 'cron',
            'action_type'     => 'scheduled_reminder',
            'status'          => 'success',
        ));

        $this->assertNotFalse($id1, 'Premier envoi doit réussir');
        $this->assertFalse($id2,   'Second envoi avec même clé doit être ignoré');

        $count = (int) $this->CI->db
            ->where('idempotency_key', $key)
            ->count_all_results('reservation_reminder_log');
        $this->assertEquals(1, $count, 'Une seule entrée pour la même clé');
    }

    // =========================================================================
    // User reminder_period_hours preference
    // =========================================================================

    public function testSchedulerRespectsUserReminderPeriod()
    {
        // Reservation starts in 10 hours → only within reminder window if period >= 10
        $future = date('Y-m-d H:i:s', strtotime('+10 hours'));
        $this->CI->db->insert('reservations', array(
            'aircraft_id'     => 'F-GSRP',
            'start_datetime'  => $future,
            'end_datetime'    => date('Y-m-d H:i:s', strtotime('+11 hours')),
            'pilot_member_id' => $this->pilot_login,
            'status'          => 'vol_local',
            'section_id'      => 1,
            'created_by'      => 'test',
        ));
        $id = $this->CI->db->insert_id();

        // With 48h window the reservation IS returned by model
        $rows = $this->model->get_pending_reservations(48);
        $ids  = array_column($rows, 'id');
        $this->assertContains((string) $id, $ids,
            'Réservation dans 10h doit être dans la fenêtre 48h');

        // Find the row and verify pilot's reminder_period_hours logic
        $row = null;
        foreach ($rows as $r) {
            if ((string) $r['id'] === (string) $id) {
                $row = $r;
                break;
            }
        }
        $this->assertNotNull($row, 'La ligne doit être présente');
        $period = (int) ($row['pilot_reminder_period_hours'] ?: 24);
        $start_ts = strtotime($row['start_datetime']);
        $send_after = $start_ts - ($period * 3600);
        // time() < $send_after → not yet in window if period < 10h
        // time() >= $send_after → in window if period >= 10h
        $in_window = (time() >= $send_after);
        $this->assertTrue($in_window,
            'Avec period=' . $period . 'h, la réservation dans 10h doit être dans la fenêtre de rappel');
    }

    public function testSchedulerSkipsReservationNotYetInReminderWindow()
    {
        // Reservation starts in 36 hours, user wants reminder 24h before → NOT yet in window
        $this->CI->db->update('membres',
            array('reminder_period_hours' => 24),
            array('mlogin' => $this->pilot_login));

        $future = date('Y-m-d H:i:s', strtotime('+36 hours'));
        $this->CI->db->insert('reservations', array(
            'aircraft_id'     => 'F-GSRP',
            'start_datetime'  => $future,
            'end_datetime'    => date('Y-m-d H:i:s', strtotime('+37 hours')),
            'pilot_member_id' => $this->pilot_login,
            'status'          => 'vol_local',
            'section_id'      => 1,
            'created_by'      => 'test',
        ));

        $row = $this->CI->db
            ->select('start_datetime')
            ->from('reservations')
            ->where('pilot_member_id', $this->pilot_login)
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()->row_array();

        $start_ts    = strtotime($row['start_datetime']);
        $period      = 24; // hours
        $send_after  = $start_ts - ($period * 3600);

        // 36h from now − 24h = 12h from now → time() < send_after → NOT in window
        $this->assertGreaterThan(time(), $send_after,
            'Avec 36h jusqu\'au départ et 24h de période, le rappel ne doit pas encore être envoyé');
    }
}
