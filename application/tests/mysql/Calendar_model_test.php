<?php

/**
 * PHPUnit Tests for Calendar Model
 *
 * Tests CRUD operations, full_day logic, FullCalendar formatting, and conflict checking
 * for the calendar (pilot presences) functionality.
 *
 * @covers Calendar_model
 */

use PHPUnit\Framework\TestCase;

class Calendar_model_test extends TestCase {

    protected static $CI;
    protected $calendar_model;
    protected $initial_count;
    protected $test_record_ids = [];

    /**
     * Set up test environment - initialize CodeIgniter
     */
    public static function setUpBeforeClass(): void {
        if (!isset(self::$CI)) {
            self::$CI = &get_instance();
            self::$CI->load->model('calendar_model');

            // Ensure migration 060 is applied
            self::$CI->load->database();
            $query = self::$CI->db->query("SHOW COLUMNS FROM calendar LIKE 'full_day'");
            if ($query->num_rows() == 0) {
                self::markTestSkipped('Migration 060 not applied - full_day column missing');
            }
        }
    }

    /**
     * Helper: Count records in calendar table
     */
    private function countRecords() {
        $query = self::$CI->db->select('COUNT(*) as cnt')->from('calendar')->get();
        $row = $query->row();
        return isset($row->cnt) ? $row->cnt : 0;
    }

    /**
     * Set up before each test
     */
    protected function setUp(): void {
        $this->calendar_model = self::$CI->calendar_model;
        $this->initial_count = $this->countRecords();
    }

    /**
     * Tear down after each test - clean up test records
     */
    protected function tearDown(): void {
        // Delete all test records created during this test
        foreach ($this->test_record_ids as $id) {
            self::$CI->db->delete('calendar', array('id' => $id));
        }
        $this->test_record_ids = [];
    }

    /**
     * Test: Create event with full_day default
     */
    public function test_create_event_full_day_default(): void {
        $data = array(
            'start_date' => '2025-06-15',
            'end_date' => '2025-06-15',
            'mlogin' => 'asterix',
            'role' => 'Instructeur',
            'commentaire' => 'Test presence',
            'status' => 'confirmed',
            'created_by' => 'test_user'
        );

        $event_id = $this->calendar_model->create_event($data);
        $this->test_record_ids[] = $event_id;

        $this->assertGreaterThan(0, $event_id, "Event ID should be greater than 0");

        // Verify the event was created with full_day = 1
        $event = $this->calendar_model->get_event($event_id);
        $this->assertEquals(1, $event['full_day'], "full_day should default to 1");
        $this->assertEquals('2025-06-15 00:00:00', $event['start_datetime'], "start_datetime should be normalized to 00:00:00");
        $this->assertEquals('2025-06-15 23:59:59', $event['end_datetime'], "end_datetime should be normalized to 23:59:59");
    }

    /**
     * Test: Create multi-day event
     */
    public function test_create_multi_day_event(): void {
        $data = array(
            'start_date' => '2025-06-15',
            'end_date' => '2025-06-18',
            'mlogin' => 'obelix',
            'role' => 'Solo',
            'commentaire' => 'Stage 4 jours',
            'status' => 'confirmed',
            'created_by' => 'test_user'
        );

        $event_id = $this->calendar_model->create_event($data);
        $this->test_record_ids[] = $event_id;

        $this->assertGreaterThan(0, $event_id);

        // Verify dates
        $event = $this->calendar_model->get_event($event_id);
        $this->assertEquals('2025-06-15 00:00:00', $event['start_datetime']);
        $this->assertEquals('2025-06-18 23:59:59', $event['end_datetime']);
        $this->assertEquals(1, $event['full_day']);
    }

    /**
     * Test: Get event by ID
     */
    public function test_get_event(): void {
        // Create test event
        $data = array(
            'start_date' => '2025-07-01',
            'end_date' => '2025-07-01',
            'mlogin' => 'asterix',
            'role' => 'Remorqueur',
            'commentaire' => '',
            'status' => 'confirmed',
            'created_by' => 'test_user'
        );

        $event_id = $this->calendar_model->create_event($data);
        $this->test_record_ids[] = $event_id;

        // Retrieve event
        $event = $this->calendar_model->get_event($event_id);

        $this->assertNotEmpty($event, "Event should be retrieved");
        $this->assertEquals($event_id, $event['id']);
        $this->assertEquals('asterix', $event['mlogin']);
        $this->assertEquals('Remorqueur', $event['role']);
    }

    /**
     * Test: Get non-existent event returns empty array
     */
    public function test_get_nonexistent_event(): void {
        $event = $this->calendar_model->get_event(999999);
        $this->assertEmpty($event, "Non-existent event should return empty array");
    }

    /**
     * Test: Get events by date range
     */
    public function test_get_events_date_range(): void {
        // Create events in June and July
        $june_data = array(
            'start_date' => '2025-06-10',
            'end_date' => '2025-06-10',
            'mlogin' => 'asterix',
            'role' => 'Instructeur',
            'commentaire' => '',
            'status' => 'confirmed',
            'created_by' => 'test'
        );
        $june_id = $this->calendar_model->create_event($june_data);
        $this->test_record_ids[] = $june_id;

        $july_data = array(
            'start_date' => '2025-07-10',
            'end_date' => '2025-07-10',
            'mlogin' => 'obelix',
            'role' => 'Solo',
            'commentaire' => '',
            'status' => 'confirmed',
            'created_by' => 'test'
        );
        $july_id = $this->calendar_model->create_event($july_data);
        $this->test_record_ids[] = $july_id;

        // Query only June
        $june_events = $this->calendar_model->get_events('2025-06-01', '2025-06-30');

        // Find our test event in results
        $found_june = false;
        $found_july = false;
        foreach ($june_events as $event) {
            if ($event['id'] == $june_id) $found_june = true;
            if ($event['id'] == $july_id) $found_july = true;
        }

        $this->assertTrue($found_june, "June event should be in June results");
        $this->assertFalse($found_july, "July event should NOT be in June results");
    }

    /**
     * Test: Get user events
     */
    public function test_get_user_events(): void {
        // Create events for different users
        $asterix_data = array(
            'start_date' => '2025-08-01',
            'end_date' => '2025-08-01',
            'mlogin' => 'asterix',
            'role' => 'Instructeur',
            'commentaire' => '',
            'status' => 'confirmed',
            'created_by' => 'test'
        );
        $asterix_id = $this->calendar_model->create_event($asterix_data);
        $this->test_record_ids[] = $asterix_id;

        $obelix_data = array(
            'start_date' => '2025-08-02',
            'end_date' => '2025-08-02',
            'mlogin' => 'obelix',
            'role' => 'Solo',
            'commentaire' => '',
            'status' => 'confirmed',
            'created_by' => 'test'
        );
        $obelix_id = $this->calendar_model->create_event($obelix_data);
        $this->test_record_ids[] = $obelix_id;

        // Get asterix events
        $asterix_events = $this->calendar_model->get_user_events('asterix');

        // Check results
        $found_asterix = false;
        $found_obelix = false;
        foreach ($asterix_events as $event) {
            if ($event['id'] == $asterix_id) $found_asterix = true;
            if ($event['id'] == $obelix_id) $found_obelix = true;
        }

        $this->assertTrue($found_asterix, "Asterix event should be in results");
        $this->assertFalse($found_obelix, "Obelix event should NOT be in Asterix results");
    }

    /**
     * Test: Update event
     */
    public function test_update_event(): void {
        // Create event
        $data = array(
            'start_date' => '2025-09-01',
            'end_date' => '2025-09-01',
            'mlogin' => 'asterix',
            'role' => 'Elève',
            'commentaire' => 'Original comment',
            'status' => 'pending',
            'created_by' => 'test_user'
        );
        $event_id = $this->calendar_model->create_event($data);
        $this->test_record_ids[] = $event_id;

        // Update event
        $update_data = array(
            'role' => 'Instructeur',
            'commentaire' => 'Updated comment',
            'status' => 'confirmed',
            'updated_by' => 'test_admin'
        );
        $result = $this->calendar_model->update_event($event_id, $update_data);
        $this->assertTrue($result, "Update should succeed");

        // Verify update
        $event = $this->calendar_model->get_event($event_id);
        $this->assertEquals('Instructeur', $event['role']);
        $this->assertEquals('Updated comment', $event['commentaire']);
        $this->assertEquals('confirmed', $event['status']);
    }

    /**
     * Test: Update event dates (still full_day)
     */
    public function test_update_event_dates(): void {
        // Create event
        $data = array(
            'start_date' => '2025-10-01',
            'end_date' => '2025-10-01',
            'mlogin' => 'asterix',
            'role' => 'Instructeur',
            'commentaire' => '',
            'status' => 'confirmed',
            'created_by' => 'test'
        );
        $event_id = $this->calendar_model->create_event($data);
        $this->test_record_ids[] = $event_id;

        // Update dates
        $update_data = array(
            'start_date' => '2025-10-05',
            'end_date' => '2025-10-07',
            'updated_by' => 'test_admin'
        );
        $this->calendar_model->update_event($event_id, $update_data);

        // Verify updated dates
        $event = $this->calendar_model->get_event($event_id);
        $this->assertEquals('2025-10-05 00:00:00', $event['start_datetime']);
        $this->assertEquals('2025-10-07 23:59:59', $event['end_datetime']);
    }

    /**
     * Test: Delete event
     */
    public function test_delete_event(): void {
        // Create event
        $data = array(
            'start_date' => '2025-11-01',
            'end_date' => '2025-11-01',
            'mlogin' => 'asterix',
            'role' => 'Solo',
            'commentaire' => '',
            'status' => 'confirmed',
            'created_by' => 'test'
        );
        $event_id = $this->calendar_model->create_event($data);

        // Delete event
        $result = $this->calendar_model->delete_event($event_id);
        $this->assertTrue($result, "Delete should succeed");

        // Verify deletion
        $event = $this->calendar_model->get_event($event_id);
        $this->assertEmpty($event, "Deleted event should not be retrievable");
    }

    /**
     * Test: Check conflict detection
     */
    public function test_check_conflict(): void {
        // Create event
        $data = array(
            'start_date' => '2025-12-15',
            'end_date' => '2025-12-17',
            'mlogin' => 'asterix',
            'role' => 'Instructeur',
            'commentaire' => '',
            'status' => 'confirmed',
            'created_by' => 'test'
        );
        $event_id = $this->calendar_model->create_event($data);
        $this->test_record_ids[] = $event_id;

        // Check for conflict (overlapping dates)
        $conflict_result = $this->calendar_model->check_conflict(
            'asterix',
            '2025-12-16 00:00:00',
            '2025-12-16 23:59:59'
        );

        $this->assertTrue($conflict_result['has_conflict'], "Should detect conflict");
        $this->assertEquals(1, $conflict_result['conflict_count']);

        // Check no conflict (different dates)
        $no_conflict_result = $this->calendar_model->check_conflict(
            'asterix',
            '2025-12-20 00:00:00',
            '2025-12-20 23:59:59'
        );

        $this->assertFalse($no_conflict_result['has_conflict'], "Should not detect conflict");
        $this->assertEquals(0, $no_conflict_result['conflict_count']);
    }

    /**
     * Test: Check conflict with exclusion
     */
    public function test_check_conflict_with_exclusion(): void {
        // Create event
        $data = array(
            'start_date' => '2026-01-10',
            'end_date' => '2026-01-12',
            'mlogin' => 'obelix',
            'role' => 'Solo',
            'commentaire' => '',
            'status' => 'confirmed',
            'created_by' => 'test'
        );
        $event_id = $this->calendar_model->create_event($data);
        $this->test_record_ids[] = $event_id;

        // Check conflict excluding this event itself (e.g., when updating)
        $result = $this->calendar_model->check_conflict(
            'obelix',
            '2026-01-10 00:00:00',
            '2026-01-12 23:59:59',
            $event_id
        );

        $this->assertFalse($result['has_conflict'], "Should not detect conflict when excluding self");
    }

    /**
     * Test: Format for FullCalendar with allDay
     */
    public function test_format_for_fullcalendar_with_full_day(): void {
        // Create test events
        $events = array(
            array(
                'id' => 1,
                'mlogin' => 'asterix',
                'mprenom' => 'Astérix',
                'mnom' => 'Le Gaulois',
                'role' => 'Instructeur',
                'commentaire' => 'Test comment',
                'start_datetime' => '2025-06-15 00:00:00',
                'end_datetime' => '2025-06-15 23:59:59',
                'full_day' => 1,
                'status' => 'confirmed',
                'created_by' => 'test',
                'updated_by' => null
            ),
            array(
                'id' => 2,
                'mlogin' => 'obelix',
                'mprenom' => 'Obélix',
                'mnom' => 'Le Livreur',
                'role' => 'Solo',
                'commentaire' => '',
                'start_datetime' => '2025-06-20 00:00:00',
                'end_datetime' => '2025-06-22 23:59:59',
                'full_day' => 1,
                'status' => 'pending',
                'created_by' => 'test',
                'updated_by' => null
            )
        );

        $formatted = $this->calendar_model->format_for_fullcalendar($events);

        $this->assertCount(2, $formatted);

        // Check first event
        $event1 = $formatted[0];
        $this->assertEquals(1, $event1['id']);
        $this->assertStringContainsString('Astérix Le Gaulois', $event1['title']);
        $this->assertEquals('2025-06-15', $event1['start']); // Date only for allDay
        $this->assertEquals('2025-06-16', $event1['end']); // Next day for FullCalendar
        $this->assertTrue($event1['allDay'], "Event should be marked as allDay");
        $this->assertEquals('asterix', $event1['extendedProps']['mlogin']);

        // Check second event (multi-day)
        $event2 = $formatted[1];
        $this->assertEquals(2, $event2['id']);
        $this->assertEquals('2025-06-20', $event2['start']);
        $this->assertEquals('2025-06-23', $event2['end']); // 2025-06-22 + 1 day
        $this->assertTrue($event2['allDay']);
    }

    /**
     * Test: Full_day normalizes timestamps even with datetime input
     */
    public function test_full_day_normalizes_timestamps(): void {
        // Create event with datetime (should be normalized to full day)
        $data = array(
            'start_datetime' => '2025-05-10 14:30:00',
            'end_datetime' => '2025-05-10 18:45:00',
            'full_day' => 1, // Explicitly set to full_day
            'mlogin' => 'asterix',
            'role' => 'Instructeur',
            'commentaire' => '',
            'status' => 'confirmed',
            'created_by' => 'test'
        );

        $event_id = $this->calendar_model->create_event($data);
        $this->test_record_ids[] = $event_id;

        // Verify timestamps were normalized
        $event = $this->calendar_model->get_event($event_id);
        $this->assertEquals('2025-05-10 00:00:00', $event['start_datetime'], "Start should be normalized to 00:00:00");
        $this->assertEquals('2025-05-10 23:59:59', $event['end_datetime'], "End should be normalized to 23:59:59");
    }

    /**
     * Test: Event title formatting
     */
    public function test_format_event_title(): void {
        $title1 = $this->calendar_model->format_event_title('Astérix Le Gaulois', 'Instructeur', '');
        $this->assertEquals('Astérix Le Gaulois - Instructeur', $title1);

        $title2 = $this->calendar_model->format_event_title('Obélix', '', 'Stage 3 jours');
        $this->assertEquals('Obélix', $title2);

        $title3 = $this->calendar_model->format_event_title('', '', 'Terrain fermé');
        $this->assertEquals('Terrain fermé', $title3);
    }
}

/* End of file Calendar_model_test.php */
/* Location: ./application/tests/mysql/Calendar_model_test.php */
