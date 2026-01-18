<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL integration tests for Reservations conflict detection
 *
 * Tests that reservation conflicts are properly detected and rejected:
 * - Aircraft conflicts (same aircraft, overlapping time)
 * - Pilot conflicts (same pilot, overlapping time)
 * - Instructor conflicts (same instructor, overlapping time)
 *
 * @package tests
 */
class ReservationsConflictTest extends TestCase
{
    private $CI;
    private $model;
    private $test_aircraft_id;
    private $test_pilot_id;
    private $test_instructor_id;
    private $test_pilot_id_2;
    private $created_reservation_ids = array();

    protected function setUp(): void
    {
        // Get CodeIgniter instance
        $this->CI = &get_instance();

        // Load database and models
        $this->CI->load->database();
        $this->CI->load->model('reservations_model');
        $this->model = $this->CI->reservations_model;

        // Get test data from database
        // Find an active aircraft
        $aircraft_result = $this->CI->db
            ->select('macimmat')
            ->from('machinesa')
            ->where('actif', 1)
            ->limit(1)
            ->get()
            ->row_array();

        if (empty($aircraft_result)) {
            $this->markTestSkipped('No active aircraft found in database');
        }
        $this->test_aircraft_id = $aircraft_result['macimmat'];

        // Find two active members to use as pilot and instructor
        $members = $this->CI->db
            ->select('mlogin')
            ->from('membres')
            ->where('actif', 1)
            ->limit(3)
            ->get()
            ->result_array();

        if (count($members) < 3) {
            $this->markTestSkipped('Not enough active members found in database (need at least 3)');
        }

        $this->test_pilot_id = $members[0]['mlogin'];
        $this->test_instructor_id = $members[1]['mlogin'];
        $this->test_pilot_id_2 = $members[2]['mlogin'];
    }

    protected function tearDown(): void
    {
        // Clean up all created test reservations
        foreach ($this->created_reservation_ids as $id) {
            $this->CI->db->delete('reservations', array('id' => $id));
        }
        $this->created_reservation_ids = array();
    }

    /**
     * Helper method to create a test reservation
     */
    private function createTestReservation($aircraft_id, $pilot_id, $instructor_id, $start, $end, $status = 'reservation')
    {
        $data = array(
            'aircraft_id' => $aircraft_id,
            'pilot_member_id' => $pilot_id,
            'instructor_member_id' => $instructor_id,
            'start_datetime' => $start,
            'end_datetime' => $end,
            'purpose' => 'Test reservation',
            'notes' => 'Created by unit test',
            'status' => $status,
            'created_by' => 'test'
        );

        $id = $this->model->create_reservation($data);
        if ($id > 0) {
            $this->created_reservation_ids[] = $id;
        }
        return $id;
    }

    /**
     * Test that a reservation can be created without conflicts
     */
    public function testCreateReservationWithoutConflict()
    {
        $start = date('Y-m-d 10:00:00', strtotime('+7 days'));
        $end = date('Y-m-d 11:00:00', strtotime('+7 days'));

        $result = $this->model->check_reservation_conflicts(
            $this->test_aircraft_id,
            $this->test_pilot_id,
            $this->test_instructor_id,
            $start,
            $end
        );

        $this->assertTrue($result['valid'], 'Should be no conflict for first reservation');
        $this->assertEmpty($result['conflicts'], 'Should have no conflicts');

        // Actually create the reservation
        $id = $this->createTestReservation(
            $this->test_aircraft_id,
            $this->test_pilot_id,
            $this->test_instructor_id,
            $start,
            $end
        );

        $this->assertGreaterThan(0, $id, 'Reservation should be created successfully');
    }

    /**
     * Test aircraft conflict detection
     */
    public function testAircraftConflict()
    {
        // Create first reservation
        $id1 = $this->createTestReservation(
            $this->test_aircraft_id,
            $this->test_pilot_id,
            null,
            date('Y-m-d') . ' 10:00:00',
            date('Y-m-d') . ' 12:00:00'
        );

        $this->assertGreaterThan(0, $id1, "First reservation should be created successfully");

        // Try to create overlapping reservation with SAME aircraft (should conflict)
        $conflict_check = $this->model->check_reservation_conflicts(
            $this->test_aircraft_id,    // Same aircraft
            $this->test_pilot_id_2,      // Different pilot
            null,                         // No instructor
            date('Y-m-d') . ' 10:30:00', // Overlaps
            date('Y-m-d') . ' 11:30:00', // Overlaps
            null
        );

        $this->assertFalse($conflict_check['valid'], "Should detect aircraft conflict");
        $this->assertContains('aircraft_conflict', $conflict_check['conflicts'], "Should report aircraft conflict");
    }

    /**
     * Test that pilot conflicts are detected
     */
    public function testPilotConflictDetection()
    {
        // Create initial reservation
        $id1 = $this->createTestReservation(
            $this->test_aircraft_id,
            $this->test_pilot_id,
            null,
            '2026-01-20 10:00:00',
            '2026-01-20 11:00:00'
        );
        $this->assertGreaterThan(0, $id1, "First reservation should be created successfully");

        // Try to create overlapping reservation with same pilot but different aircraft
        // Get a second aircraft
        $aircraft_result = $this->CI->db
            ->select('macimmat')
            ->from('machinesa')
            ->where('actif', 1)
            ->where('macimmat !=', $this->test_aircraft_id)
            ->limit(1)
            ->get()
            ->row_array();

        if (empty($aircraft_result)) {
            $this->markTestSkipped('Need at least 2 active aircraft for this test');
        }
        $other_aircraft_id = $aircraft_result['macimmat'];

        // Check for pilot conflict - same pilot, different aircraft, overlapping time
        $conflict_check = $this->model->check_reservation_conflicts(
            $other_aircraft_id,
            $this->test_pilot_id,      // Same pilot as first reservation
            null,
            '2026-01-20 10:30:00',     // Overlapping time with first reservation
            '2026-01-20 11:30:00',
            null
        );

        $this->assertFalse($conflict_check['valid'], "Should detect pilot conflict");
        $this->assertContains('pilot_conflict', $conflict_check['conflicts'], "Should report pilot conflict");
    }

    /**
     * Test instructor conflict detection
     */
    public function testInstructorConflictDetection()
    {
        // Create first reservation with instructor
        $id1 = $this->createTestReservation(
            $this->test_aircraft_id,
            $this->test_pilot_id,
            $this->test_instructor_id,
            '2026-06-15 14:00:00',
            '2026-06-15 15:00:00'
        );
        $this->created_reservation_ids[] = $id1;

        // Get another aircraft for testing
        $aircraft_result = $this->CI->db
            ->select('macimmat')
            ->from('machinesa')
            ->where('actif', 1)
            ->where('macimmat !=', $this->test_aircraft_id)
            ->limit(1)
            ->get()
            ->row_array();

        if (empty($aircraft_result)) {
            $this->markTestSkipped('Need at least 2 active aircraft in database');
        }
        $other_aircraft_id = $aircraft_result['macimmat'];

        // Check for instructor conflict - different pilot, different aircraft, same instructor, overlapping time
        $conflict_check = $this->model->check_reservation_conflicts(
            $other_aircraft_id,
            $this->test_pilot_id_2,
            $this->test_instructor_id,
            '2026-06-15 14:30:00',
            '2026-06-15 15:30:00',
            null
        );

        $this->assertFalse($conflict_check['valid'], "Should detect instructor conflict");
        $this->assertContains('instructor_conflict', $conflict_check['conflicts'], "Should report instructor conflict");
    }

    /**
     * Test that reservations without conflicts are accepted
     */
    public function testNoConflictAllowed()
    {
        // Create first reservation
        $id1 = $this->createTestReservation(
            $this->test_aircraft_id,
            $this->test_pilot_id,
            $this->test_instructor_id,
            '2026-06-15 08:00:00',
            '2026-06-15 09:00:00'
        );
        $this->created_reservation_ids[] = $id1;

        // Check for a completely different time slot - should be valid
        $conflict_check = $this->model->check_reservation_conflicts(
            $this->test_aircraft_id,
            $this->test_pilot_id,
            $this->test_instructor_id,
            '2026-06-15 12:00:00',
            '2026-06-15 13:00:00',
            null
        );

        $this->assertTrue($conflict_check['valid'], "Should allow reservation without conflicts");
        $this->assertEmpty($conflict_check['conflicts'], "Should have no conflicts");
    }

    /**
     * Test that updating own reservation does not cause conflict
     */
    public function testUpdateOwnReservationNoConflict()
    {
        // Create reservation
        $id1 = $this->createTestReservation(
            $this->test_aircraft_id,
            $this->test_pilot_id,
            $this->test_instructor_id,
            '2026-06-15 16:00:00',
            '2026-06-15 17:00:00'
        );
        $this->created_reservation_ids[] = $id1;

        // Check for conflict when updating the same reservation (should exclude itself)
        $conflict_check = $this->model->check_reservation_conflicts(
            $this->test_aircraft_id,
            $this->test_pilot_id,
            $this->test_instructor_id,
            '2026-06-15 16:15:00',  // Slightly different time
            '2026-06-15 17:15:00',
            $id1  // Exclude this reservation from conflict check
        );

        $this->assertTrue($conflict_check['valid'], "Should allow updating own reservation");
        $this->assertEmpty($conflict_check['conflicts'], "Should have no conflicts when updating own reservation");
    }

    /**
     * Test deleted reservations are ignored in conflict detection
     */
    public function testDeletedReservationsIgnored()
    {
        // Create a reservation then delete it
        $id1 = $this->createTestReservation(
            $this->test_aircraft_id,
            $this->test_pilot_id,
            $this->test_instructor_id,
            '2026-06-15 18:00:00',
            '2026-06-15 19:00:00'
        );

        // Delete the reservation
        $this->CI->db->delete('reservations', array('id' => $id1));
        // Remove from cleanup list since it's already deleted
        $this->created_reservation_ids = array_diff($this->created_reservation_ids, array($id1));

        // Try to create another reservation in the same slot - should be valid since first is deleted
        $conflict_check = $this->model->check_reservation_conflicts(
            $this->test_aircraft_id,
            $this->test_pilot_id,
            $this->test_instructor_id,
            '2026-06-15 18:30:00',
            '2026-06-15 19:30:00',
            null
        );

        $this->assertTrue($conflict_check['valid'], "Should ignore deleted reservations");
        $this->assertEmpty($conflict_check['conflicts'], "Should have no conflicts with deleted reservations");
    }

    /**
     * Test multiple conflicts are all reported
     */
    public function testMultipleConflictsReported()
    {
        // Create first reservation
        $id1 = $this->createTestReservation(
            $this->test_aircraft_id,
            $this->test_pilot_id,
            $this->test_instructor_id,
            '2026-06-15 20:00:00',
            '2026-06-15 21:00:00'
        );
        $this->created_reservation_ids[] = $id1;

        // Try to create reservation with same aircraft, pilot, and instructor - should report all conflicts
        $conflict_check = $this->model->check_reservation_conflicts(
            $this->test_aircraft_id,
            $this->test_pilot_id,
            $this->test_instructor_id,
            '2026-06-15 20:30:00',
            '2026-06-15 21:30:00',
            null
        );

        $this->assertFalse($conflict_check['valid'], "Should detect multiple conflicts");
        $this->assertCount(3, $conflict_check['conflicts'], "Should report all three conflicts");
        $this->assertContains('aircraft_conflict', $conflict_check['conflicts']);
        $this->assertContains('pilot_conflict', $conflict_check['conflicts']);
        $this->assertContains('instructor_conflict', $conflict_check['conflicts']);
    }
}

/* End of file ReservationsConflictTest.php */
/* Location: ./application/tests/mysql/ReservationsConflictTest.php */
