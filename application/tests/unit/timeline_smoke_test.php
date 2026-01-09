<?php

use PHPUnit\Framework\TestCase;

/**
 * Smoke Test for Timeline Feature
 * Tests the timeline functionality by verifying model methods
 */
class TimelineSmokeTest extends TestCase {
    
    /**
     * Test that reservations model has required methods
     */
    public function testReservationsModelHasTimelineMethods() {
        $model_content = file_get_contents(APPPATH . 'models/reservations_model.php');
        
        // Verify get_aircraft_list method exists
        $this->assertStringContainsString(
            'function get_aircraft_list()',
            $model_content,
            'Reservations model should have get_aircraft_list method'
        );
        
        // Verify get_day_reservations method exists
        $this->assertStringContainsString(
            'function get_day_reservations($date)',
            $model_content,
            'Reservations model should have get_day_reservations method'
        );
        
        // Verify get_timeline_events method exists
        $this->assertStringContainsString(
            'function get_timeline_events($date)',
            $model_content,
            'Reservations model should have get_timeline_events method'
        );
    }
    
    /**
     * Test that controller has required methods
     */
    public function testReservationsControllerHasTimelineMethods() {
        $controller_content = file_get_contents(APPPATH . 'controllers/reservations.php');
        
        // Verify timeline method exists
        $this->assertStringContainsString(
            'function timeline()',
            $controller_content,
            'Reservations controller should have timeline method'
        );
        
        // Verify get_timeline_data method exists
        $this->assertStringContainsString(
            'function get_timeline_data()',
            $controller_content,
            'Reservations controller should have get_timeline_data method'
        );
        
        // Verify callback methods exist
        $this->assertStringContainsString(
            'function on_event_click()',
            $controller_content,
            'Reservations controller should have on_event_click method'
        );
        
        $this->assertStringContainsString(
            'function on_event_drop()',
            $controller_content,
            'Reservations controller should have on_event_drop method'
        );
        
        $this->assertStringContainsString(
            'function on_slot_click()',
            $controller_content,
            'Reservations controller should have on_slot_click method'
        );
    }
    
    /**
     * Test that timeline view exists
     */
    public function testTimelineViewExists() {
        $view_path = APPPATH . 'views/reservations/timeline.php';
        
        $this->assertFileExists(
            $view_path,
            'Timeline view should exist at ' . $view_path
        );
        
        $view_content = file_get_contents($view_path);
        
        // Verify view has key elements for our custom timeline implementation
        $this->assertStringContainsString(
            'resource-timeline',
            $view_content,
            'Timeline view should have resource timeline container for aircraft'
        );
        
        $this->assertStringContainsString(
            'loadTimelineData',
            $view_content,
            'Timeline view should have loadTimelineData function'
        );
        
        $this->assertStringContainsString(
            'time-slot',
            $view_content,
            'Timeline view should have time slots for the day'
        );
    }
    
    /**
     * Test that migrations have created the reservations table
     */
    public function testReservationsTableMigrationExists() {
        $migration_dir = APPPATH . 'migrations/';
        
        $migration_file = $migration_dir . '059_create_aircraft_reservations_table.php';
        
        $this->assertFileExists(
            $migration_file,
            'Migration file should exist: ' . $migration_file
        );
        
        $migration_content = file_get_contents($migration_file);
        
        // Verify table creation
        $this->assertStringContainsString(
            'reservations',
            $migration_content,
            'Migration should create reservations table'
        );
        
        $this->assertStringContainsString(
            'start_datetime',
            $migration_content,
            'Reservations table should have start_datetime column'
        );
        
        $this->assertStringContainsString(
            'end_datetime',
            $migration_content,
            'Reservations table should have end_datetime column'
        );
    }
}
