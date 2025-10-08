# Aircraft Booking System - Implementation Plan

**Document Version:** 1.0
**Date:** 2025-10-08
**Status:** Planning Phase
**Author:** Claude Code Analysis
**PRD Reference:** doc/prds/aircraft_booking_prd.md

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Architecture Overview](#architecture-overview)
3. [Prerequisites & Clarifications](#prerequisites--clarifications)
4. [Implementation Phases](#implementation-phases)
5. [Progress Tracker](#progress-tracker)
6. [Database Schema Design](#database-schema-design)
7. [Detailed Implementation Steps](#detailed-implementation-steps)
8. [Testing Strategy](#testing-strategy)
9. [Deployment Plan](#deployment-plan)
10. [Rollback Strategy](#rollback-strategy)
11. [Risk Assessment](#risk-assessment)

---

## Executive Summary

This plan details the step-by-step implementation of an aircraft booking system for GVV. The system will enable members to reserve aircraft through a visual calendar interface, with conflict prevention for both aircraft and instructor availability. The implementation will reuse the existing FullCalendar infrastructure currently used for member presence tracking.

**Key Features:**
- Visual drag-and-drop calendar interface
- Aircraft reservation management (create, modify, delete)
- Instructor availability tracking
- Aircraft unavailability blocks (maintenance, etc.)
- Role-based permissions (Pilot, Instructor, Administrator)
- Credit limit enforcement
- Conflict prevention (aircraft double-booking, instructor conflicts)

**Estimated Timeline:** 6-8 weeks
**Estimated Effort:** 120-160 hours

---

## Architecture Overview

### Technology Stack
- **Backend:** PHP 7.4, CodeIgniter 2.x
- **Database:** MySQL 5.x (existing: gvv2)
- **Frontend:** Bootstrap 5, jQuery
- **Calendar:** FullCalendar.js (already in use)
- **Data Source:** Local database (replacing Google Calendar for bookings)

### Existing Components to Reuse
1. **Calendar Controller** (`application/controllers/calendar.php`)
   - Currently displays member presence intentions from Google Calendar
   - Will be extended to support aircraft bookings from local database

2. **Presences Controller** (`application/controllers/presences.php`)
   - Pattern for AJAX CRUD operations on calendar events
   - Authorization logic (`modification_allowed`, `creation_allowed`)

3. **Calendar View** (`application/views/bs_calendar.php`)
   - FullCalendar integration
   - Event form dialog pattern

4. **FullCalendar Libraries**
   - `assets/js/fullcalendar.js`
   - `assets/js/gcal.js` (for Google Calendar)
   - Multi-language support (fr, nl, en)

### New Components to Create
1. **Database Tables:**
   - `aircraft_bookings` - Main reservation table
   - `aircraft_unavailability` - Maintenance/blocking periods

2. **Backend:**
   - `Bookings` controller
   - `Bookings_model` model
   - `Bookings` library (business rules)

3. **Frontend:**
   - Calendar view for bookings
   - Booking form dialog
   - JavaScript for drag-and-drop operations

4. **Language Files:**
   - French, English, Dutch translations

---

## Prerequisites & Clarifications

### Questions Requiring Clarification

Before proceeding with implementation, the following points need clarification:

#### 1. **Section Assignment**
- **Question:** Should aircraft bookings be section-specific (Planeur, ULM, Avion)?
- **Impact:** Database schema (add section_id field), filtering logic, permissions
- **Recommendation:** Yes, align with existing GVV section architecture
- **Decision Needed:** ☐ Confirm section-based bookings

#### 2. **Instructor Identification**
- **Question:** How are instructors identified in the system?
  - Option A: By role in `membres` table (inst_glider, inst_airplane fields)
  - Option B: By authorization role (needs new role type)
  - Option C: Flag in membres table
- **Current State:** `membres` table has `inst_glider` and `inst_airplane` fields (varchar(25))
- **Decision Needed:** ☐ Confirm instructor identification method

#### 3. **Credit Limit Integration**
- **Question:** Where is the member's credit balance stored?
  - Current state: `membres.compte` field exists (int(11))
  - How to calculate booking cost?
  - Should we validate against balance or just flag?
- **Decision Needed:** ☐ Confirm credit limit validation approach

#### 4. **Time Slot Granularity**
- **Question:** What is the minimum booking duration?
  - 15 minutes? 30 minutes? 1 hour?
- **Impact:** Calendar display, validation rules
- **Decision Needed:** ☐ Define minimum booking slot

#### 5. **Maximum Advance Booking**
- **Question:** How far in advance can members book aircraft?
  - 7 days? 30 days? 90 days? Unlimited?
- **Decision Needed:** ☐ Define maximum advance booking period

#### 6. **Cancellation Policy**
- **Question:** Can users cancel/modify bookings up until start time?
  - Or should there be a minimum advance notice (e.g., 24 hours)?
- **Decision Needed:** ☐ Define cancellation policy

#### 7. **Recurring Bookings**
- **Question:** Out of scope per PRD, but should we design the schema to support it later?
- **Decision Needed:** ☐ Confirm schema should be future-proof for recurrence

#### 8. **Multiple Aircraft Selection**
- **Question:** Can a user book multiple aircraft for the same time slot?
  - Use case: Training flights with multiple aircraft
- **Decision Needed:** ☐ Confirm single vs multiple aircraft per booking

#### 9. **Authorization Integration**
- **Question:** Should this use the new authorization system (per 2025_authorization_refactoring_prd.md) or current DX_Auth?
- **Current State:** Authorization refactoring is in planning phase
- **Recommendation:** Start with DX_Auth, migrate later
- **Decision Needed:** ☐ Confirm authorization approach

#### 10. **Existing Calendar Coexistence**
- **Question:** Should aircraft bookings display alongside presence intentions in the same calendar?
  - Option A: Separate calendar views (recommended)
  - Option B: Combined view with filtering
- **Decision Needed:** ☐ Confirm calendar integration approach

---

## Implementation Phases

### Phase 0: Planning & Design (Week 1)
**Duration:** 5 days
**Effort:** 20 hours

**Objectives:**
- Finalize requirements based on clarifications
- Complete database schema design
- Create detailed wireframes
- Set up development environment
- Create test data sets

### Phase 1: Database Schema & Migration (Week 2)
**Duration:** 5 days
**Effort:** 16 hours

**Objectives:**
- Create migration files
- Implement database tables
- Add indexes and constraints
- Populate seed data
- Test migration rollback

### Phase 2: Backend - Models & Business Logic (Week 3)
**Duration:** 5 days
**Effort:** 24 hours

**Objectives:**
- Create Bookings_model
- Implement CRUD operations
- Create Bookings library for business rules
- Implement conflict detection logic
- Write model unit tests

### Phase 3: Backend - Controller & API (Week 4)
**Duration:** 5 days
**Effort:** 20 hours

**Objectives:**
- Create Bookings controller
- Implement AJAX endpoints
- Implement authorization checks
- Create JSON responses for calendar
- Write controller tests

### Phase 4: Frontend - Calendar Integration (Week 5)
**Duration:** 5 days
**Effort:** 24 hours

**Objectives:**
- Adapt calendar view for bookings
- Implement drag-and-drop functionality
- Create booking form dialog
- Implement client-side validation
- Add multi-language support

### Phase 5: Advanced Features (Week 6)
**Duration:** 5 days
**Effort:** 20 hours

**Objectives:**
- Implement instructor availability view
- Create aircraft unavailability management
- Add credit limit checking
- Implement advanced filters
- Create administrator tools

### Phase 6: Testing & Bug Fixes (Week 7)
**Duration:** 5 days
**Effort:** 24 hours

**Objectives:**
- Execute test plan
- Fix identified bugs
- Performance testing
- Security testing
- User acceptance testing

### Phase 7: Documentation & Deployment (Week 8)
**Duration:** 3 days
**Effort:** 12 hours

**Objectives:**
- Complete user documentation
- Create admin guide
- Deploy to production
- Monitor initial usage
- Address deployment issues

---

## Progress Tracker

### Phase 0: Planning & Design
- [ ] Obtain clarifications on all prerequisite questions
- [ ] Review and approve database schema design
- [ ] Create PlantUML architecture diagram
- [ ] Create detailed wireframes/mockups
- [ ] Set up local test environment
- [ ] Create test data generation script
- [ ] Review plan with stakeholders

### Phase 1: Database Schema & Migration
- [ ] Create migration 040_aircraft_booking_schema.php
- [ ] Define `aircraft_bookings` table structure
- [ ] Define `aircraft_unavailability` table structure
- [ ] Add foreign key constraints
- [ ] Create indexes for performance
- [ ] Update config/migration.php to version 40
- [ ] Test migration on clean database
- [ ] Test migration rollback (down() method)
- [ ] Generate seed data for testing
- [ ] Validate schema with sample queries
- [ ] Document schema in design_notes

### Phase 2: Backend - Models & Business Logic
- [ ] Create application/models/Bookings_model.php
- [ ] Implement select_page() method
- [ ] Implement get_booking() method
- [ ] Implement create_booking() method
- [ ] Implement update_booking() method
- [ ] Implement delete_booking() method
- [ ] Implement get_aircraft_bookings() method
- [ ] Implement get_instructor_bookings() method
- [ ] Create application/libraries/Gvv_Bookings.php
- [ ] Implement conflict detection: check_aircraft_conflict()
- [ ] Implement conflict detection: check_instructor_conflict()
- [ ] Implement conflict detection: check_unavailability_conflict()
- [ ] Implement credit limit validation
- [ ] Implement booking duration validation
- [ ] Implement advance booking limit check
- [ ] Create application/tests/unit/models/BookingsModelTest.php
- [ ] Write tests for all CRUD operations
- [ ] Write tests for conflict detection scenarios
- [ ] Write tests for edge cases (past dates, etc.)
- [ ] Execute unit tests (target: >75% coverage)

### Phase 3: Backend - Controller & API
- [ ] Create application/controllers/bookings.php
- [ ] Implement __construct() with authorization
- [ ] Implement index() method (calendar view)
- [ ] Implement events() AJAX endpoint (JSON feed)
- [ ] Implement create() AJAX endpoint
- [ ] Implement update() AJAX endpoint
- [ ] Implement delete() AJAX endpoint
- [ ] Implement get_instructor_availability() endpoint
- [ ] Implement unavailability_blocks CRUD endpoints
- [ ] Implement authorization: can_create_booking()
- [ ] Implement authorization: can_modify_booking()
- [ ] Implement authorization: can_delete_booking()
- [ ] Implement authorization: can_manage_unavailability()
- [ ] Add error handling and logging
- [ ] Create application/tests/controllers/BookingsControllerTest.php
- [ ] Write tests for each endpoint
- [ ] Write tests for authorization scenarios
- [ ] Execute controller tests

### Phase 4: Frontend - Calendar Integration
- [ ] Create application/views/bookings_calendar.php
- [ ] Adapt FullCalendar initialization for bookings
- [ ] Configure event data source (local DB, not Google)
- [ ] Implement calendar rendering by aircraft
- [ ] Create booking form dialog HTML
- [ ] Add aircraft selector to form
- [ ] Add instructor selector to form (optional)
- [ ] Add date/time pickers
- [ ] Add duration selector
- [ ] Implement drag-and-drop event creation
- [ ] Implement drag-to-resize duration
- [ ] Implement drag-to-move booking
- [ ] Implement click-to-edit booking
- [ ] Implement client-side validation
- [ ] Add AJAX save/update/delete handlers
- [ ] Add conflict error display
- [ ] Add loading indicators
- [ ] Create assets/js/bookings_calendar.js
- [ ] Implement event rendering customization
- [ ] Add color coding (by aircraft, by user, by type)
- [ ] Add tooltips with booking details
- [ ] Create language files: french/bookings_lang.php
- [ ] Create language files: english/bookings_lang.php
- [ ] Create language files: dutch/bookings_lang.php
- [ ] Test on multiple browsers
- [ ] Test on mobile devices (responsive)

### Phase 5: Advanced Features
- [ ] Create instructor availability view
- [ ] Show instructor schedule in calendar format
- [ ] Add filtering by instructor
- [ ] Create unavailability management view
- [ ] Implement unavailability block creation form
- [ ] Add reason field (required)
- [ ] Add date/time range selector
- [ ] Display unavailability blocks in calendar
- [ ] Prevent booking during unavailability
- [ ] Implement credit limit check
- [ ] Query member's current balance
- [ ] Calculate booking estimated cost
- [ ] Display warning if insufficient balance
- [ ] (Optional) Prevent booking if over limit
- [ ] Add advanced filters
- [ ] Filter by aircraft type
- [ ] Filter by date range
- [ ] Filter by member
- [ ] Filter by instructor
- [ ] Create admin dashboard
- [ ] Show booking statistics
- [ ] Show aircraft utilization
- [ ] Show popular time slots
- [ ] Add export functionality (CSV, PDF)

### Phase 6: Testing & Bug Fixes
- [ ] Execute comprehensive test plan
- [ ] Test all CRUD operations
- [ ] Test conflict scenarios (20+ test cases)
- [ ] Test authorization (pilots vs instructors vs admins)
- [ ] Test edge cases (midnight boundaries, etc.)
- [ ] Test concurrent booking attempts
- [ ] Performance testing
- [ ] Load test with 1000+ bookings
- [ ] Measure calendar load time
- [ ] Optimize slow queries
- [ ] Security testing
- [ ] Test SQL injection prevention
- [ ] Test XSS prevention
- [ ] Test CSRF protection
- [ ] Test authorization bypass attempts
- [ ] User acceptance testing
- [ ] Test with real pilots
- [ ] Gather feedback on UX
- [ ] Test on actual devices used by club
- [ ] Bug fixing
- [ ] Prioritize and fix critical bugs
- [ ] Fix medium priority bugs
- [ ] Document known minor issues
- [ ] Regression testing after fixes

### Phase 7: Documentation & Deployment
- [ ] Create user documentation
- [ ] Write user guide (PDF)
- [ ] Create video tutorials (optional)
- [ ] Document common workflows
- [ ] Create administrator documentation
- [ ] Document unavailability management
- [ ] Document conflict resolution procedures
- [ ] Document backup/restore procedures
- [ ] Create technical documentation
- [ ] Document API endpoints
- [ ] Document database schema
- [ ] Document configuration options
- [ ] Update CLAUDE.md with booking system info
- [ ] Pre-deployment checklist
- [ ] Database backup created
- [ ] Test environment validated
- [ ] Rollback plan prepared
- [ ] Deployment window scheduled
- [ ] Deployment execution
- [ ] Run database migration
- [ ] Deploy code to production
- [ ] Update configuration
- [ ] Clear caches
- [ ] Verify deployment
- [ ] Post-deployment monitoring
- [ ] Monitor error logs
- [ ] Monitor user feedback
- [ ] Monitor performance metrics
- [ ] Address urgent issues within 24h

---

## Database Schema Design

### Table: aircraft_bookings

**Purpose:** Store all aircraft reservation records

```sql
CREATE TABLE `aircraft_bookings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `aircraft_id` VARCHAR(10) NOT NULL COMMENT 'Foreign key to machinesp.mpimmat or machinesa.macimmat',
  `aircraft_type` ENUM('planeur', 'avion', 'ulm') NOT NULL COMMENT 'Type of aircraft',
  `section_id` INT(11) DEFAULT NULL COMMENT 'Foreign key to sections table (if applicable)',
  `member_id` VARCHAR(25) NOT NULL COMMENT 'Foreign key to membres.mlogin',
  `instructor_id` VARCHAR(25) DEFAULT NULL COMMENT 'Optional instructor, foreign key to membres.mlogin',
  `start_datetime` DATETIME NOT NULL COMMENT 'Booking start date and time',
  `end_datetime` DATETIME NOT NULL COMMENT 'Booking end date and time',
  `duration_minutes` INT(11) NOT NULL COMMENT 'Booking duration in minutes',
  `booking_type` ENUM('solo', 'instruction', 'check_flight', 'maintenance_flight', 'demo') DEFAULT 'solo',
  `status` ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'confirmed',
  `comments` TEXT DEFAULT NULL COMMENT 'Optional notes from member',
  `admin_notes` TEXT DEFAULT NULL COMMENT 'Internal notes, only visible to admins',
  `estimated_cost` DECIMAL(10,2) DEFAULT NULL COMMENT 'Estimated cost based on duration',
  `actual_flight_id` INT(11) DEFAULT NULL COMMENT 'Link to actual flight record (volsp.vpid or volsa.vaid) after flight',
  `created_by` VARCHAR(25) NOT NULL COMMENT 'User who created the booking',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_by` VARCHAR(25) DEFAULT NULL COMMENT 'User who last updated',
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `cancelled_by` VARCHAR(25) DEFAULT NULL,
  `cancelled_at` DATETIME DEFAULT NULL,
  `cancellation_reason` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_aircraft` (`aircraft_id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_instructor` (`instructor_id`),
  KEY `idx_datetime` (`start_datetime`, `end_datetime`),
  KEY `idx_status` (`status`),
  KEY `idx_section` (`section_id`),
  CONSTRAINT `fk_booking_member` FOREIGN KEY (`member_id`) REFERENCES `membres` (`mlogin`) ON DELETE RESTRICT,
  CONSTRAINT `fk_booking_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `membres` (`mlogin`) ON DELETE SET NULL,
  CONSTRAINT `chk_datetime` CHECK (`end_datetime` > `start_datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Aircraft booking reservations';
```

**Key Design Decisions:**
- **aircraft_id:** VARCHAR(10) to match existing aircraft tables (mpimmat, macimmat)
- **aircraft_type:** Explicit enum to determine source table (machinesp vs machinesa)
- **section_id:** Nullable for future multi-section support (CLARIFICATION NEEDED)
- **Timestamps:** Created/Updated tracking for audit trail
- **Status tracking:** For future workflow (pending approval, completed flights, etc.)
- **Soft links:** actual_flight_id links to completed flight but doesn't enforce FK (flights may be deleted)

### Table: aircraft_unavailability

**Purpose:** Store maintenance periods and other blocks when aircraft cannot be booked

```sql
CREATE TABLE `aircraft_unavailability` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `aircraft_id` VARCHAR(10) NOT NULL COMMENT 'Foreign key to machinesp.mpimmat or machinesa.macimmat',
  `aircraft_type` ENUM('planeur', 'avion', 'ulm') NOT NULL,
  `start_datetime` DATETIME NOT NULL,
  `end_datetime` DATETIME NOT NULL,
  `reason_type` ENUM('maintenance', 'inspection', 'repair', 'weather', 'administrative', 'other') NOT NULL,
  `reason_description` TEXT NOT NULL COMMENT 'Mandatory description',
  `created_by` VARCHAR(25) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_by` VARCHAR(25) DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_aircraft_unavail` (`aircraft_id`),
  KEY `idx_datetime_unavail` (`start_datetime`, `end_datetime`),
  CONSTRAINT `chk_unavail_datetime` CHECK (`end_datetime` > `start_datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Aircraft unavailability periods';
```

### View: vue_aircraft_bookings

**Purpose:** Convenient view for displaying bookings with member names and aircraft details

```sql
CREATE VIEW `vue_aircraft_bookings` AS
SELECT
  ab.id,
  ab.aircraft_id,
  ab.aircraft_type,
  ab.start_datetime,
  ab.end_datetime,
  ab.duration_minutes,
  ab.booking_type,
  ab.status,
  ab.member_id,
  CONCAT(m.mprenom, ' ', m.mnom) AS member_name,
  m.memail AS member_email,
  ab.instructor_id,
  CONCAT(i.mprenom, ' ', i.mnom) AS instructor_name,
  ab.comments,
  ab.estimated_cost,
  CASE
    WHEN ab.aircraft_type = 'planeur' THEN mp.mpmodele
    WHEN ab.aircraft_type IN ('avion', 'ulm') THEN ma.macmodele
  END AS aircraft_model,
  CASE
    WHEN ab.aircraft_type = 'planeur' THEN mp.mpconstruc
    WHEN ab.aircraft_type IN ('avion', 'ulm') THEN ma.macconstruc
  END AS aircraft_constructor,
  ab.created_at,
  ab.updated_at
FROM aircraft_bookings ab
INNER JOIN membres m ON ab.member_id = m.mlogin
LEFT JOIN membres i ON ab.instructor_id = i.mlogin
LEFT JOIN machinesp mp ON ab.aircraft_type = 'planeur' AND ab.aircraft_id = mp.mpimmat
LEFT JOIN machinesa ma ON ab.aircraft_type IN ('avion', 'ulm') AND ab.aircraft_id = ma.macimmat
WHERE ab.status != 'cancelled';
```

### Database Migration File

**File:** `application/migrations/040_aircraft_booking_schema.php`

```php
<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * GVV Migration 040: Aircraft Booking System
 *
 * Creates tables and views for aircraft reservation system
 *
 * @author Claude Code
 * @date 2025-10-08
 */
class Migration_Aircraft_booking_schema extends CI_Migration {

    public function up() {
        // Create aircraft_bookings table
        $this->db->query("
            CREATE TABLE `aircraft_bookings` (
              -- [Full SQL from above]
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Aircraft booking reservations';
        ");

        // Create aircraft_unavailability table
        $this->db->query("
            CREATE TABLE `aircraft_unavailability` (
              -- [Full SQL from above]
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Aircraft unavailability periods';
        ");

        // Create view
        $this->db->query("
            CREATE VIEW `vue_aircraft_bookings` AS
            -- [Full SQL from above]
        ");

        echo "Migration 040: Aircraft booking schema created successfully.\n";
    }

    public function down() {
        $this->db->query("DROP VIEW IF EXISTS `vue_aircraft_bookings`");
        $this->db->query("DROP TABLE IF EXISTS `aircraft_unavailability`");
        $this->db->query("DROP TABLE IF EXISTS `aircraft_bookings`");

        echo "Migration 040: Aircraft booking schema rolled back.\n";
    }
}
```

---

## Detailed Implementation Steps

### Phase 1: Database Schema & Migration

#### Step 1.1: Create Migration File
```bash
# File: application/migrations/040_aircraft_booking_schema.php
# Copy the migration file content from above
```

#### Step 1.2: Update Migration Version
```php
// File: application/config/migration.php
$config['migration_version'] = 40;
```

#### Step 1.3: Test Migration
```bash
# Navigate to migration controller (if exists) or create temporary script
# Test up migration
php index.php migration/current

# Test down migration
php index.php migration/version/39
php index.php migration/version/40
```

#### Step 1.4: Verify Schema
```sql
-- Check tables exist
SHOW TABLES LIKE 'aircraft_%';

-- Check structure
DESCRIBE aircraft_bookings;
DESCRIBE aircraft_unavailability;

-- Check view
SELECT * FROM vue_aircraft_bookings LIMIT 1;
```

#### Step 1.5: Create Seed Data
```sql
-- Insert test aircraft (if not exists)
-- Insert test members (if not exists)
-- Insert sample bookings for testing
INSERT INTO aircraft_bookings
  (aircraft_id, aircraft_type, member_id, start_datetime, end_datetime, duration_minutes, created_by)
VALUES
  ('F-BLIT', 'planeur', 'fpeignot', '2025-10-15 09:00:00', '2025-10-15 11:00:00', 120, 'fpeignot'),
  ('F-BLIT', 'planeur', 'agnes', '2025-10-15 14:00:00', '2025-10-15 16:00:00', 120, 'agnes');
```

### Phase 2: Backend - Models & Business Logic

#### Step 2.1: Create Bookings Model

**File:** `application/models/Bookings_model.php`

```php
<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * GVV - Aircraft Bookings Model
 *
 * Handles CRUD operations for aircraft bookings
 *
 * @package GVV
 * @author Claude Code
 * @date 2025-10-08
 */
class Bookings_model extends CI_Model {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get paginated bookings list with filters
     *
     * @param array $filters Optional filters (date_from, date_to, aircraft_id, member_id, status)
     * @param int $limit Number of records
     * @param int $offset Starting offset
     * @return array
     */
    public function select_page($filters = array(), $limit = 100, $offset = 0) {
        $this->db->select('*');
        $this->db->from('vue_aircraft_bookings');

        // Apply filters
        if (!empty($filters['date_from'])) {
            $this->db->where('start_datetime >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('start_datetime <=', $filters['date_to']);
        }
        if (!empty($filters['aircraft_id'])) {
            $this->db->where('aircraft_id', $filters['aircraft_id']);
        }
        if (!empty($filters['member_id'])) {
            $this->db->where('member_id', $filters['member_id']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('status', $filters['status']);
        }

        $this->db->order_by('start_datetime', 'ASC');
        $this->db->limit($limit, $offset);

        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get single booking by ID
     *
     * @param int $id Booking ID
     * @return array|null
     */
    public function get_booking($id) {
        $query = $this->db->get_where('vue_aircraft_bookings', array('id' => $id));
        return $query->row_array();
    }

    /**
     * Create new booking
     *
     * @param array $data Booking data
     * @return int|bool Booking ID or false on failure
     */
    public function create_booking($data) {
        // Calculate duration if not provided
        if (empty($data['duration_minutes'])) {
            $start = new DateTime($data['start_datetime']);
            $end = new DateTime($data['end_datetime']);
            $data['duration_minutes'] = ($end->getTimestamp() - $start->getTimestamp()) / 60;
        }

        // Set defaults
        $data['status'] = $data['status'] ?? 'confirmed';
        $data['booking_type'] = $data['booking_type'] ?? 'solo';
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = $data['created_by'] ?? $this->dx_auth->get_username();

        if ($this->db->insert('aircraft_bookings', $data)) {
            return $this->db->insert_id();
        }
        return false;
    }

    /**
     * Update existing booking
     *
     * @param int $id Booking ID
     * @param array $data Updated data
     * @return bool
     */
    public function update_booking($id, $data) {
        // Recalculate duration if dates changed
        if (isset($data['start_datetime']) && isset($data['end_datetime'])) {
            $start = new DateTime($data['start_datetime']);
            $end = new DateTime($data['end_datetime']);
            $data['duration_minutes'] = ($end->getTimestamp() - $start->getTimestamp()) / 60;
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['updated_by'] = $this->dx_auth->get_username();

        $this->db->where('id', $id);
        return $this->db->update('aircraft_bookings', $data);
    }

    /**
     * Delete (cancel) booking
     *
     * @param int $id Booking ID
     * @param string $reason Cancellation reason
     * @return bool
     */
    public function delete_booking($id, $reason = '') {
        $data = array(
            'status' => 'cancelled',
            'cancelled_by' => $this->dx_auth->get_username(),
            'cancelled_at' => date('Y-m-d H:i:s'),
            'cancellation_reason' => $reason
        );

        $this->db->where('id', $id);
        return $this->db->update('aircraft_bookings', $data);
    }

    /**
     * Get all bookings for a specific aircraft in date range
     *
     * @param string $aircraft_id Aircraft ID
     * @param string $start_date Start datetime
     * @param string $end_date End datetime
     * @return array
     */
    public function get_aircraft_bookings($aircraft_id, $start_date, $end_date) {
        $this->db->select('*');
        $this->db->from('aircraft_bookings');
        $this->db->where('aircraft_id', $aircraft_id);
        $this->db->where('status !=', 'cancelled');
        $this->db->where("(
            (start_datetime <= '$end_date' AND end_datetime >= '$start_date')
        )");
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get all bookings for a specific instructor in date range
     *
     * @param string $instructor_id Instructor member ID
     * @param string $start_date Start datetime
     * @param string $end_date End datetime
     * @return array
     */
    public function get_instructor_bookings($instructor_id, $start_date, $end_date) {
        $this->db->select('*');
        $this->db->from('aircraft_bookings');
        $this->db->where('instructor_id', $instructor_id);
        $this->db->where('status !=', 'cancelled');
        $this->db->where("(
            (start_datetime <= '$end_date' AND end_datetime >= '$start_date')
        )");
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get unavailability blocks for aircraft in date range
     *
     * @param string $aircraft_id Aircraft ID
     * @param string $start_date Start datetime
     * @param string $end_date End datetime
     * @return array
     */
    public function get_unavailability_blocks($aircraft_id, $start_date, $end_date) {
        $this->db->select('*');
        $this->db->from('aircraft_unavailability');
        $this->db->where('aircraft_id', $aircraft_id);
        $this->db->where("(
            (start_datetime <= '$end_date' AND end_datetime >= '$start_date')
        )");
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get member's booking count/statistics
     *
     * @param string $member_id Member login
     * @param string $period 'today', 'week', 'month', 'all'
     * @return array
     */
    public function get_member_booking_stats($member_id, $period = 'month') {
        $date_filter = '';
        switch($period) {
            case 'today':
                $date_filter = "DATE(start_datetime) = CURDATE()";
                break;
            case 'week':
                $date_filter = "start_datetime >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $date_filter = "start_datetime >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
        }

        $sql = "
            SELECT
                COUNT(*) as total_bookings,
                SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(duration_minutes) as total_minutes,
                SUM(estimated_cost) as total_cost
            FROM aircraft_bookings
            WHERE member_id = ?
        ";

        if ($date_filter) {
            $sql .= " AND " . $date_filter;
        }

        $query = $this->db->query($sql, array($member_id));
        return $query->row_array();
    }
}
```

#### Step 2.2: Create Bookings Library (Business Rules)

**File:** `application/libraries/Gvv_Bookings.php`

```php
<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * GVV - Aircraft Bookings Business Logic Library
 *
 * Handles validation and conflict detection for bookings
 *
 * @package GVV
 * @author Claude Code
 * @date 2025-10-08
 */
class Gvv_Bookings {

    protected $CI;

    // Configuration (TODO: Move to config file after clarifications)
    private $min_booking_minutes = 30;           // CLARIFICATION NEEDED
    private $max_advance_days = 90;              // CLARIFICATION NEEDED
    private $min_cancellation_hours = 2;         // CLARIFICATION NEEDED

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('Bookings_model');
        $this->CI->load->model('Membres_model');
    }

    /**
     * Validate booking data and check for conflicts
     *
     * @param array $data Booking data
     * @param int $booking_id Optional ID if updating existing booking
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validate_booking($data, $booking_id = null) {
        $errors = array();

        // Basic field validation
        if (empty($data['aircraft_id'])) {
            $errors[] = 'Aircraft ID is required';
        }
        if (empty($data['member_id'])) {
            $errors[] = 'Member ID is required';
        }
        if (empty($data['start_datetime'])) {
            $errors[] = 'Start datetime is required';
        }
        if (empty($data['end_datetime'])) {
            $errors[] = 'End datetime is required';
        }

        if (!empty($errors)) {
            return array('valid' => false, 'errors' => $errors);
        }

        // Date/time validation
        $start = new DateTime($data['start_datetime']);
        $end = new DateTime($data['end_datetime']);
        $now = new DateTime();

        // Cannot book in the past
        if ($start < $now) {
            $errors[] = 'Cannot create booking in the past';
        }

        // End must be after start
        if ($end <= $start) {
            $errors[] = 'End datetime must be after start datetime';
        }

        // Check minimum duration
        $duration_minutes = ($end->getTimestamp() - $start->getTimestamp()) / 60;
        if ($duration_minutes < $this->min_booking_minutes) {
            $errors[] = "Minimum booking duration is {$this->min_booking_minutes} minutes";
        }

        // Check maximum advance booking
        $days_advance = ($start->getTimestamp() - $now->getTimestamp()) / (60 * 60 * 24);
        if ($days_advance > $this->max_advance_days) {
            $errors[] = "Cannot book more than {$this->max_advance_days} days in advance";
        }

        // Check for conflicts
        $conflict_result = $this->check_conflicts($data, $booking_id);
        if (!$conflict_result['valid']) {
            $errors = array_merge($errors, $conflict_result['errors']);
        }

        // Check credit limit (if configured)
        $credit_result = $this->check_credit_limit($data['member_id'], $duration_minutes);
        if (!$credit_result['valid']) {
            $errors[] = $credit_result['error'];
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }

    /**
     * Check for booking conflicts
     *
     * @param array $data Booking data
     * @param int $booking_id Optional ID to exclude (when updating)
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function check_conflicts($data, $booking_id = null) {
        $errors = array();

        // Check aircraft conflict
        $aircraft_conflict = $this->check_aircraft_conflict(
            $data['aircraft_id'],
            $data['start_datetime'],
            $data['end_datetime'],
            $booking_id
        );

        if ($aircraft_conflict) {
            $errors[] = "Aircraft {$data['aircraft_id']} is already booked during this time";
        }

        // Check instructor conflict (if instructor specified)
        if (!empty($data['instructor_id'])) {
            $instructor_conflict = $this->check_instructor_conflict(
                $data['instructor_id'],
                $data['start_datetime'],
                $data['end_datetime'],
                $booking_id
            );

            if ($instructor_conflict) {
                $errors[] = "Instructor is already booked during this time";
            }
        }

        // Check aircraft unavailability
        $unavailability_conflict = $this->check_unavailability_conflict(
            $data['aircraft_id'],
            $data['start_datetime'],
            $data['end_datetime']
        );

        if ($unavailability_conflict) {
            $errors[] = "Aircraft is unavailable during this period: " . $unavailability_conflict['reason'];
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }

    /**
     * Check if aircraft is already booked
     *
     * @param string $aircraft_id Aircraft ID
     * @param string $start_datetime Start datetime
     * @param string $end_datetime End datetime
     * @param int $exclude_booking_id Optional booking to exclude
     * @return bool True if conflict exists
     */
    public function check_aircraft_conflict($aircraft_id, $start_datetime, $end_datetime, $exclude_booking_id = null) {
        $bookings = $this->CI->Bookings_model->get_aircraft_bookings(
            $aircraft_id,
            $start_datetime,
            $end_datetime
        );

        foreach ($bookings as $booking) {
            // Skip the booking being updated
            if ($exclude_booking_id && $booking['id'] == $exclude_booking_id) {
                continue;
            }

            // Check for overlap
            if ($this->check_time_overlap(
                $start_datetime,
                $end_datetime,
                $booking['start_datetime'],
                $booking['end_datetime']
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if instructor is already booked
     *
     * @param string $instructor_id Instructor member ID
     * @param string $start_datetime Start datetime
     * @param string $end_datetime End datetime
     * @param int $exclude_booking_id Optional booking to exclude
     * @return bool True if conflict exists
     */
    public function check_instructor_conflict($instructor_id, $start_datetime, $end_datetime, $exclude_booking_id = null) {
        $bookings = $this->CI->Bookings_model->get_instructor_bookings(
            $instructor_id,
            $start_datetime,
            $end_datetime
        );

        foreach ($bookings as $booking) {
            if ($exclude_booking_id && $booking['id'] == $exclude_booking_id) {
                continue;
            }

            if ($this->check_time_overlap(
                $start_datetime,
                $end_datetime,
                $booking['start_datetime'],
                $booking['end_datetime']
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if aircraft is unavailable (maintenance, etc.)
     *
     * @param string $aircraft_id Aircraft ID
     * @param string $start_datetime Start datetime
     * @param string $end_datetime End datetime
     * @return array|false Array with reason if conflict, false if no conflict
     */
    public function check_unavailability_conflict($aircraft_id, $start_datetime, $end_datetime) {
        $blocks = $this->CI->Bookings_model->get_unavailability_blocks(
            $aircraft_id,
            $start_datetime,
            $end_datetime
        );

        foreach ($blocks as $block) {
            if ($this->check_time_overlap(
                $start_datetime,
                $end_datetime,
                $block['start_datetime'],
                $block['end_datetime']
            )) {
                return array(
                    'reason' => $block['reason_description'],
                    'type' => $block['reason_type']
                );
            }
        }

        return false;
    }

    /**
     * Check if two time ranges overlap
     *
     * @param string $start1 First range start
     * @param string $end1 First range end
     * @param string $start2 Second range start
     * @param string $end2 Second range end
     * @return bool True if overlap exists
     */
    private function check_time_overlap($start1, $end1, $start2, $end2) {
        $start1_ts = strtotime($start1);
        $end1_ts = strtotime($end1);
        $start2_ts = strtotime($start2);
        $end2_ts = strtotime($end2);

        // Check if ranges overlap
        // Range1 starts before Range2 ends AND Range1 ends after Range2 starts
        return ($start1_ts < $end2_ts && $end1_ts > $start2_ts);
    }

    /**
     * Check if member has sufficient credit for booking
     *
     * CLARIFICATION NEEDED: How to calculate cost and enforce limit
     *
     * @param string $member_id Member ID
     * @param int $duration_minutes Booking duration
     * @return array ['valid' => bool, 'error' => string]
     */
    public function check_credit_limit($member_id, $duration_minutes) {
        // TODO: Implement credit limit logic after clarification
        // Current implementation: Always allow (warning only)

        $member = $this->CI->Membres_model->get_by_login($member_id);
        if (!$member) {
            return array('valid' => false, 'error' => 'Member not found');
        }

        // Get member's current balance
        $current_balance = floatval($member['compte'] ?? 0);

        // TODO: Calculate estimated cost based on aircraft pricing
        // For now, skip validation

        return array('valid' => true, 'error' => '');
    }

    /**
     * Check if user can modify/cancel booking
     *
     * @param int $booking_id Booking ID
     * @param string $user_id User attempting modification
     * @return array ['allowed' => bool, 'reason' => string]
     */
    public function can_modify_booking($booking_id, $user_id = null) {
        $user_id = $user_id ?? $this->CI->dx_auth->get_username();
        $booking = $this->CI->Bookings_model->get_booking($booking_id);

        if (!$booking) {
            return array('allowed' => false, 'reason' => 'Booking not found');
        }

        // Admins and instructors can modify any booking
        if ($this->CI->dx_auth->is_admin() || $this->CI->dx_auth->is_role('instructeur')) {
            return array('allowed' => true, 'reason' => '');
        }

        // Users can only modify their own bookings
        if ($booking['member_id'] != $user_id) {
            return array('allowed' => false, 'reason' => 'You can only modify your own bookings');
        }

        // Check if booking is too close to start time
        $start = new DateTime($booking['start_datetime']);
        $now = new DateTime();
        $hours_until = ($start->getTimestamp() - $now->getTimestamp()) / 3600;

        if ($hours_until < $this->min_cancellation_hours) {
            return array(
                'allowed' => false,
                'reason' => "Cannot modify booking less than {$this->min_cancellation_hours} hours before start"
            );
        }

        return array('allowed' => true, 'reason' => '');
    }
}
```

#### Step 2.3: Create Unit Tests

**File:** `application/tests/unit/libraries/BookingsLibraryTest.php`

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Unit tests for Gvv_Bookings library
 *
 * Run with: ./run-tests.sh application/tests/unit/libraries/BookingsLibraryTest.php
 */
class BookingsLibraryTest extends PHPUnit\Framework\TestCase {

    private $CI;
    private $bookings_lib;

    protected function setUp(): void {
        $this->CI =& get_instance();
        $this->CI->load->library('Gvv_Bookings');
        $this->bookings_lib = $this->CI->gvv_bookings;
    }

    public function testCheckTimeOverlap() {
        // Use reflection to test private method
        $method = new ReflectionMethod('Gvv_Bookings', 'check_time_overlap');
        $method->setAccessible(true);

        // Test overlapping ranges
        $result = $method->invoke(
            $this->bookings_lib,
            '2025-10-15 09:00:00',
            '2025-10-15 11:00:00',
            '2025-10-15 10:00:00',
            '2025-10-15 12:00:00'
        );
        $this->assertTrue($result, 'Should detect overlap');

        // Test non-overlapping ranges
        $result = $method->invoke(
            $this->bookings_lib,
            '2025-10-15 09:00:00',
            '2025-10-15 11:00:00',
            '2025-10-15 14:00:00',
            '2025-10-15 16:00:00'
        );
        $this->assertFalse($result, 'Should not detect overlap');
    }

    public function testValidateBookingRequiredFields() {
        $data = array();
        $result = $this->bookings_lib->validate_booking($data);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    // Add more tests...
}
```

### Phase 3: Backend - Controller & API

#### Step 3.1: Create Bookings Controller

**File:** `application/controllers/bookings.php`

```php
<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * GVV - Aircraft Bookings Controller
 *
 * Handles aircraft reservation calendar and AJAX operations
 *
 * @package GVV
 * @author Claude Code
 * @date 2025-10-08
 */
class Bookings extends CI_Controller {

    public function __construct() {
        parent::__construct();

        // Check authentication
        $this->load->library('DX_Auth');
        if (!$this->dx_auth->is_logged_in()) {
            redirect("auth/login");
        }

        // Load dependencies
        $this->load->model('Bookings_model');
        $this->load->model('Membres_model');
        $this->load->library('Gvv_Bookings');
        $this->lang->load('bookings');
    }

    /**
     * Display booking calendar
     */
    public function index() {
        // Get aircraft list for section
        $this->load->model('Machinesp_model'); // Gliders
        $this->load->model('Machinesa_model'); // Airplanes/ULM

        $section = $this->session->userdata('section') ?? 'planeur';

        if ($section == 'planeur') {
            $aircraft = $this->Machinesp_model->selector(array('actif' => 1, 'club' => 1));
        } else {
            $aircraft = $this->Machinesa_model->selector(array('actif' => 1, 'club' => 1));
        }

        // Get instructor list
        $instructors = $this->Membres_model->selector_instructors($section);

        $data = array(
            'aircraft_selector' => $aircraft,
            'instructor_selector' => $instructors,
            'current_user' => $this->dx_auth->get_username(),
            'is_admin' => $this->dx_auth->is_admin(),
            'is_instructor' => $this->dx_auth->is_role('instructeur'),
            'section' => $section
        );

        load_last_view('bookings_calendar', $data);
    }

    /**
     * AJAX: Get bookings as JSON feed for FullCalendar
     *
     * @return JSON
     */
    public function events() {
        $start = $this->input->get('start');
        $end = $this->input->get('end');
        $aircraft_id = $this->input->get('aircraft_id');

        $filters = array(
            'date_from' => $start,
            'date_to' => $end
        );

        if ($aircraft_id) {
            $filters['aircraft_id'] = $aircraft_id;
        }

        $bookings = $this->Bookings_model->select_page($filters);

        // Convert to FullCalendar event format
        $events = array();
        foreach ($bookings as $booking) {
            $events[] = array(
                'id' => $booking['id'],
                'title' => $booking['aircraft_id'] . ' - ' . $booking['member_name'],
                'start' => $booking['start_datetime'],
                'end' => $booking['end_datetime'],
                'resourceId' => $booking['aircraft_id'],
                'backgroundColor' => $this->get_booking_color($booking),
                'borderColor' => $this->get_booking_border_color($booking),
                'editable' => $this->can_modify_booking($booking),
                'extendedProps' => array(
                    'member_id' => $booking['member_id'],
                    'member_name' => $booking['member_name'],
                    'instructor_name' => $booking['instructor_name'] ?? null,
                    'booking_type' => $booking['booking_type'],
                    'status' => $booking['status'],
                    'comments' => $booking['comments']
                )
            );
        }

        // Get unavailability blocks
        $unavailability = $this->get_unavailability_events($aircraft_id, $start, $end);
        $events = array_merge($events, $unavailability);

        header('Content-Type: application/json');
        echo json_encode($events);
    }

    /**
     * AJAX: Create new booking
     *
     * @return JSON
     */
    public function create() {
        $data = array(
            'aircraft_id' => $this->input->post('aircraft_id'),
            'aircraft_type' => $this->input->post('aircraft_type'),
            'member_id' => $this->input->post('member_id') ?? $this->dx_auth->get_username(),
            'instructor_id' => $this->input->post('instructor_id'),
            'start_datetime' => $this->input->post('start_datetime'),
            'end_datetime' => $this->input->post('end_datetime'),
            'booking_type' => $this->input->post('booking_type'),
            'comments' => $this->input->post('comments'),
            'created_by' => $this->dx_auth->get_username()
        );

        // Validate booking
        $validation = $this->gvv_bookings->validate_booking($data);

        if (!$validation['valid']) {
            echo json_encode(array(
                'status' => 'error',
                'errors' => $validation['errors']
            ));
            return;
        }

        // Create booking
        $booking_id = $this->Bookings_model->create_booking($data);

        if ($booking_id) {
            echo json_encode(array(
                'status' => 'success',
                'booking_id' => $booking_id,
                'message' => $this->lang->line('booking_created')
            ));
        } else {
            echo json_encode(array(
                'status' => 'error',
                'errors' => array($this->lang->line('booking_create_failed'))
            ));
        }
    }

    /**
     * AJAX: Update existing booking
     *
     * @return JSON
     */
    public function update() {
        $booking_id = $this->input->post('id');

        // Check permission
        $can_modify = $this->gvv_bookings->can_modify_booking($booking_id);
        if (!$can_modify['allowed']) {
            echo json_encode(array(
                'status' => 'error',
                'errors' => array($can_modify['reason'])
            ));
            return;
        }

        $data = array(
            'start_datetime' => $this->input->post('start_datetime'),
            'end_datetime' => $this->input->post('end_datetime'),
            'instructor_id' => $this->input->post('instructor_id'),
            'comments' => $this->input->post('comments')
        );

        // Remove null values
        $data = array_filter($data, function($value) { return $value !== null; });

        // Validate
        $validation = $this->gvv_bookings->validate_booking($data, $booking_id);

        if (!$validation['valid']) {
            echo json_encode(array(
                'status' => 'error',
                'errors' => $validation['errors']
            ));
            return;
        }

        // Update
        $success = $this->Bookings_model->update_booking($booking_id, $data);

        if ($success) {
            echo json_encode(array(
                'status' => 'success',
                'message' => $this->lang->line('booking_updated')
            ));
        } else {
            echo json_encode(array(
                'status' => 'error',
                'errors' => array($this->lang->line('booking_update_failed'))
            ));
        }
    }

    /**
     * AJAX: Delete (cancel) booking
     *
     * @return JSON
     */
    public function delete() {
        $booking_id = $this->input->post('id');
        $reason = $this->input->post('reason');

        // Check permission
        $can_modify = $this->gvv_bookings->can_modify_booking($booking_id);
        if (!$can_modify['allowed']) {
            echo json_encode(array(
                'status' => 'error',
                'errors' => array($can_modify['reason'])
            ));
            return;
        }

        $success = $this->Bookings_model->delete_booking($booking_id, $reason);

        if ($success) {
            echo json_encode(array(
                'status' => 'success',
                'message' => $this->lang->line('booking_cancelled')
            ));
        } else {
            echo json_encode(array(
                'status' => 'error',
                'errors' => array($this->lang->line('booking_cancel_failed'))
            ));
        }
    }

    /**
     * Get unavailability events for calendar
     *
     * @param string $aircraft_id
     * @param string $start
     * @param string $end
     * @return array
     */
    private function get_unavailability_events($aircraft_id, $start, $end) {
        $blocks = $this->Bookings_model->get_unavailability_blocks($aircraft_id, $start, $end);

        $events = array();
        foreach ($blocks as $block) {
            $events[] = array(
                'id' => 'unavail_' . $block['id'],
                'title' => '🔧 ' . $block['reason_type'],
                'start' => $block['start_datetime'],
                'end' => $block['end_datetime'],
                'resourceId' => $block['aircraft_id'],
                'backgroundColor' => '#ff0000',
                'borderColor' => '#cc0000',
                'editable' => false,
                'display' => 'background',
                'extendedProps' => array(
                    'type' => 'unavailability',
                    'reason' => $block['reason_description']
                )
            );
        }

        return $events;
    }

    /**
     * Determine if current user can modify booking
     *
     * @param array $booking
     * @return bool
     */
    private function can_modify_booking($booking) {
        if ($this->dx_auth->is_admin() || $this->dx_auth->is_role('instructeur')) {
            return true;
        }

        return $booking['member_id'] == $this->dx_auth->get_username();
    }

    /**
     * Get background color for booking based on type/status
     *
     * @param array $booking
     * @return string
     */
    private function get_booking_color($booking) {
        if ($booking['status'] == 'cancelled') {
            return '#999999';
        }

        // Color by booking type
        $colors = array(
            'solo' => '#3788d8',
            'instruction' => '#ff9800',
            'check_flight' => '#4caf50',
            'maintenance_flight' => '#f44336',
            'demo' => '#9c27b0'
        );

        return $colors[$booking['booking_type']] ?? '#3788d8';
    }

    /**
     * Get border color for booking
     *
     * @param array $booking
     * @return string
     */
    private function get_booking_border_color($booking) {
        // Darken the background color for border
        $bg_color = $this->get_booking_color($booking);
        return $bg_color; // Could implement color darkening if needed
    }
}
```

### Phase 4: Frontend - Calendar Integration

#### Step 4.1: Create Calendar View

**File:** `application/views/bookings_calendar.php`

```php
<?php
/**
 * GVV - Aircraft Booking Calendar View
 *
 * Visual calendar for aircraft reservations
 */

$this->load->view('bs_header', array('new_layout' => true));
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('bookings');
?>

<section class="container-fluid">
    <article>
        <input type="hidden" name="base_url" value="<?= base_url() ?>" />
        <input type="hidden" name="current_user" value="<?= $current_user ?>" />
        <input type="hidden" name="section" value="<?= $section ?>" />

        <!-- Include FullCalendar -->
        <?php
        e_html_script(array('type' => "text/javascript", 'src' => js_url('fullcalendar')));

        $lang = $this->config->item('language');
        if ($lang == "french") {
            e_html_script(array('type' => "text/javascript", 'src' => js_url('lang/fr')));
        } elseif ($lang == "dutch") {
            e_html_script(array('type' => "text/javascript", 'src' => js_url('lang/nl')));
        }
        ?>

        <!-- Filters -->
        <div class="booking-filters mb-3">
            <div class="row">
                <div class="col-md-4">
                    <label for="aircraft-filter"><?= $this->lang->line('bookings_filter_aircraft') ?></label>
                    <?= form_dropdown('aircraft_filter', $aircraft_selector, '', 'id="aircraft-filter" class="form-control"') ?>
                </div>
                <div class="col-md-4">
                    <label>
                        <input type="checkbox" id="show-my-bookings" checked />
                        <?= $this->lang->line('bookings_show_my_bookings') ?>
                    </label>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary" id="new-booking-btn">
                        <?= $this->lang->line('bookings_new_booking') ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Calendar Container -->
        <div id="booking-calendar"></div>

        <!-- Booking Form Dialog -->
        <div id="booking-dialog" style="display:none;">
            <?= form_open('', array('id' => 'booking-form')) ?>

            <?= form_hidden('booking_id', '') ?>

            <div class="form-group">
                <label for="booking-aircraft"><?= $this->lang->line('bookings_aircraft') ?> *</label>
                <?= form_dropdown('aircraft_id', $aircraft_selector, '', 'id="booking-aircraft" class="form-control" required') ?>
            </div>

            <div class="form-group">
                <label for="booking-member"><?= $this->lang->line('bookings_member') ?> *</label>
                <input type="text" id="booking-member" class="form-control" value="<?= $current_user ?>" readonly />
                <?= form_hidden('member_id', $current_user) ?>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="booking-start-date"><?= $this->lang->line('bookings_start_date') ?> *</label>
                        <input type="date" id="booking-start-date" class="form-control" required />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="booking-start-time"><?= $this->lang->line('bookings_start_time') ?> *</label>
                        <input type="time" id="booking-start-time" class="form-control" required />
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="booking-end-date"><?= $this->lang->line('bookings_end_date') ?> *</label>
                        <input type="date" id="booking-end-date" class="form-control" required />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="booking-end-time"><?= $this->lang->line('bookings_end_time') ?> *</label>
                        <input type="time" id="booking-end-time" class="form-control" required />
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="booking-type"><?= $this->lang->line('bookings_type') ?></label>
                <select id="booking-type" name="booking_type" class="form-control">
                    <option value="solo"><?= $this->lang->line('bookings_type_solo') ?></option>
                    <option value="instruction"><?= $this->lang->line('bookings_type_instruction') ?></option>
                    <option value="check_flight"><?= $this->lang->line('bookings_type_check') ?></option>
                    <option value="demo"><?= $this->lang->line('bookings_type_demo') ?></option>
                </select>
            </div>

            <div class="form-group">
                <label for="booking-instructor"><?= $this->lang->line('bookings_instructor') ?></label>
                <?= form_dropdown('instructor_id', $instructor_selector, '', 'id="booking-instructor" class="form-control"') ?>
            </div>

            <div class="form-group">
                <label for="booking-comments"><?= $this->lang->line('bookings_comments') ?></label>
                <textarea id="booking-comments" name="comments" class="form-control" rows="3"></textarea>
            </div>

            <div class="form-errors alert alert-danger" style="display:none;"></div>

            <?= form_close() ?>
        </div>

        <!-- Legend -->
        <div class="booking-legend mt-3">
            <h4><?= $this->lang->line('bookings_legend') ?></h4>
            <div class="row">
                <div class="col-md-2">
                    <span class="legend-box" style="background-color: #3788d8;"></span>
                    <?= $this->lang->line('bookings_type_solo') ?>
                </div>
                <div class="col-md-2">
                    <span class="legend-box" style="background-color: #ff9800;"></span>
                    <?= $this->lang->line('bookings_type_instruction') ?>
                </div>
                <div class="col-md-2">
                    <span class="legend-box" style="background-color: #4caf50;"></span>
                    <?= $this->lang->line('bookings_type_check') ?>
                </div>
                <div class="col-md-2">
                    <span class="legend-box" style="background-color: #ff0000;"></span>
                    <?= $this->lang->line('bookings_unavailable') ?>
                </div>
            </div>
        </div>

    </article>
</section>

<script type="text/javascript" src="<?= js_url('bookings_calendar') ?>"></script>

<style>
    #booking-calendar {
        max-width: 1200px;
        margin: 20px auto;
        background: white;
        padding: 20px;
        border-radius: 8px;
    }

    .booking-filters {
        background: white;
        padding: 15px;
        border-radius: 8px;
    }

    .legend-box {
        display: inline-block;
        width: 20px;
        height: 20px;
        margin-right: 5px;
        border: 1px solid #ccc;
    }

    .booking-legend {
        background: white;
        padding: 15px;
        border-radius: 8px;
    }

    #booking-dialog {
        border: 1px solid #9e9e9e;
        padding: 20px;
        background: white;
        border-radius: 8px;
    }

    .form-errors {
        margin-top: 10px;
    }
</style>

<?php $this->load->view('bs_footer'); ?>
```

#### Step 4.2: Create Calendar JavaScript

**File:** `assets/js/bookings_calendar.js`

```javascript
/**
 * GVV - Aircraft Booking Calendar JavaScript
 *
 * Handles FullCalendar initialization and booking interactions
 */

$(document).ready(function() {
    const baseUrl = $('input[name="base_url"]').val();
    const currentUser = $('input[name="current_user"]').val();

    let calendar;
    let bookingDialog;

    // Initialize FullCalendar
    initCalendar();

    // Initialize dialog
    bookingDialog = $('#booking-dialog').dialog({
        autoOpen: false,
        modal: true,
        width: 600,
        buttons: {
            'Save': saveBooking,
            'Delete': deleteBooking,
            'Cancel': function() {
                $(this).dialog('close');
            }
        }
    });

    /**
     * Initialize FullCalendar
     */
    function initCalendar() {
        const calendarEl = document.getElementById('booking-calendar');

        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            slotMinTime: '07:00:00',
            slotMaxTime: '20:00:00',
            slotDuration: '00:30:00', // 30-minute slots
            allDaySlot: false,
            editable: true,
            selectable: true,
            selectMirror: true,
            weekends: true,

            // Event source - load from database
            events: function(info, successCallback, failureCallback) {
                const aircraftFilter = $('#aircraft-filter').val();

                $.ajax({
                    url: baseUrl + 'bookings/events',
                    type: 'GET',
                    data: {
                        start: info.startStr,
                        end: info.endStr,
                        aircraft_id: aircraftFilter
                    },
                    success: function(data) {
                        successCallback(data);
                    },
                    error: function() {
                        failureCallback();
                        alert('Failed to load bookings');
                    }
                });
            },

            // Click on empty slot to create booking
            select: function(info) {
                openBookingDialog({
                    start: info.start,
                    end: info.end,
                    allDay: info.allDay
                });
            },

            // Click on existing event to edit
            eventClick: function(info) {
                const event = info.event;

                // Don't edit unavailability blocks
                if (event.extendedProps.type === 'unavailability') {
                    alert(event.extendedProps.reason);
                    return;
                }

                // Check if user can edit
                if (!event.editable && !confirm('You can only view this booking. OK to continue?')) {
                    return;
                }

                openBookingDialog({
                    id: event.id,
                    aircraft_id: event.resourceId,
                    start: event.start,
                    end: event.end,
                    ...event.extendedProps
                });
            },

            // Drag to move booking
            eventDrop: function(info) {
                updateBookingTime(info.event);
            },

            // Drag to resize booking
            eventResize: function(info) {
                updateBookingTime(info.event);
            },

            // Event rendering
            eventContent: function(arg) {
                const event = arg.event;

                let html = '<div class="fc-event-content">';
                html += '<div class="fc-event-time">' + arg.timeText + '</div>';
                html += '<div class="fc-event-title">' + event.title + '</div>';

                if (event.extendedProps.instructor_name) {
                    html += '<div class="fc-event-instructor">👨‍🏫 ' + event.extendedProps.instructor_name + '</div>';
                }

                html += '</div>';

                return { html: html };
            }
        });

        calendar.render();
    }

    /**
     * Open booking dialog for create/edit
     */
    function openBookingDialog(data) {
        // Reset form
        $('#booking-form')[0].reset();
        $('.form-errors').hide().empty();

        // Set dialog title
        const title = data.id ? 'Edit Booking' : 'New Booking';
        bookingDialog.dialog('option', 'title', title);

        // Populate form
        if (data.id) {
            $('input[name="booking_id"]').val(data.id);
            $('#booking-aircraft').val(data.aircraft_id || '');
            $('#booking-type').val(data.booking_type || 'solo');
            $('#booking-instructor').val(data.instructor_id || '');
            $('#booking-comments').val(data.comments || '');
        }

        // Set dates/times
        if (data.start) {
            const start = new Date(data.start);
            $('#booking-start-date').val(formatDate(start));
            $('#booking-start-time').val(formatTime(start));
        }

        if (data.end) {
            const end = new Date(data.end);
            $('#booking-end-date').val(formatDate(end));
            $('#booking-end-time').val(formatTime(end));
        }

        // Show/hide delete button
        if (data.id) {
            bookingDialog.dialog('option', 'buttons')['Delete'].show = true;
        } else {
            bookingDialog.dialog('option', 'buttons')['Delete'].show = false;
        }

        bookingDialog.dialog('open');
    }

    /**
     * Save booking (create or update)
     */
    function saveBooking() {
        const bookingId = $('input[name="booking_id"]').val();
        const isUpdate = bookingId !== '';

        // Build datetime strings
        const startDate = $('#booking-start-date').val();
        const startTime = $('#booking-start-time').val();
        const endDate = $('#booking-end-date').val();
        const endTime = $('#booking-end-time').val();

        const formData = {
            id: bookingId,
            aircraft_id: $('#booking-aircraft').val(),
            aircraft_type: 'planeur', // TODO: Detect from aircraft
            member_id: currentUser,
            start_datetime: startDate + ' ' + startTime + ':00',
            end_datetime: endDate + ' ' + endTime + ':00',
            booking_type: $('#booking-type').val(),
            instructor_id: $('#booking-instructor').val() || null,
            comments: $('#booking-comments').val()
        };

        const url = baseUrl + 'bookings/' + (isUpdate ? 'update' : 'create');

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    bookingDialog.dialog('close');
                    calendar.refetchEvents();
                    alert(response.message);
                } else {
                    displayErrors(response.errors);
                }
            },
            error: function() {
                alert('Failed to save booking');
            }
        });
    }

    /**
     * Delete booking
     */
    function deleteBooking() {
        const bookingId = $('input[name="booking_id"]').val();

        if (!bookingId) {
            alert('No booking to delete');
            return;
        }

        const reason = prompt('Cancellation reason (optional):');
        if (reason === null) return; // User cancelled

        $.ajax({
            url: baseUrl + 'bookings/delete',
            type: 'POST',
            data: {
                id: bookingId,
                reason: reason
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    bookingDialog.dialog('close');
                    calendar.refetchEvents();
                    alert(response.message);
                } else {
                    displayErrors(response.errors);
                }
            },
            error: function() {
                alert('Failed to delete booking');
            }
        });
    }

    /**
     * Update booking time after drag/resize
     */
    function updateBookingTime(event) {
        $.ajax({
            url: baseUrl + 'bookings/update',
            type: 'POST',
            data: {
                id: event.id,
                start_datetime: formatDateTime(event.start),
                end_datetime: formatDateTime(event.end)
            },
            dataType: 'json',
            success: function(response) {
                if (response.status !== 'success') {
                    displayErrors(response.errors);
                    // Revert the event
                    event.revert();
                }
            },
            error: function() {
                alert('Failed to update booking');
                event.revert();
            }
        });
    }

    /**
     * Display form errors
     */
    function displayErrors(errors) {
        const errorDiv = $('.form-errors');
        errorDiv.empty();

        if (errors && errors.length > 0) {
            errors.forEach(function(error) {
                errorDiv.append('<div>' + error + '</div>');
            });
            errorDiv.show();
        }
    }

    /**
     * Format date for input field (YYYY-MM-DD)
     */
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    /**
     * Format time for input field (HH:MM)
     */
    function formatTime(date) {
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${hours}:${minutes}`;
    }

    /**
     * Format datetime for database (YYYY-MM-DD HH:MM:SS)
     */
    function formatDateTime(date) {
        return formatDate(date) + ' ' + formatTime(date) + ':00';
    }

    // Filter change handler
    $('#aircraft-filter').change(function() {
        calendar.refetchEvents();
    });

    // New booking button
    $('#new-booking-btn').click(function() {
        openBookingDialog({});
    });
});
```

#### Step 4.3: Create Language Files

**File:** `application/language/french/bookings_lang.php`

```php
<?php
$lang['bookings_title'] = 'Réservation d\'Appareils';
$lang['bookings_filter_aircraft'] = 'Filtrer par appareil';
$lang['bookings_show_my_bookings'] = 'Afficher mes réservations';
$lang['bookings_new_booking'] = 'Nouvelle Réservation';
$lang['bookings_aircraft'] = 'Appareil';
$lang['bookings_member'] = 'Membre';
$lang['bookings_start_date'] = 'Date de début';
$lang['bookings_start_time'] = 'Heure de début';
$lang['bookings_end_date'] = 'Date de fin';
$lang['bookings_end_time'] = 'Heure de fin';
$lang['bookings_type'] = 'Type de vol';
$lang['bookings_type_solo'] = 'Solo';
$lang['bookings_type_instruction'] = 'Instruction';
$lang['bookings_type_check'] = 'Vol de contrôle';
$lang['bookings_type_demo'] = 'Démonstration';
$lang['bookings_instructor'] = 'Instructeur';
$lang['bookings_comments'] = 'Commentaires';
$lang['bookings_legend'] = 'Légende';
$lang['bookings_unavailable'] = 'Indisponible';
$lang['booking_created'] = 'Réservation créée avec succès';
$lang['booking_updated'] = 'Réservation mise à jour';
$lang['booking_cancelled'] = 'Réservation annulée';
$lang['booking_create_failed'] = 'Échec de la création de la réservation';
$lang['booking_update_failed'] = 'Échec de la mise à jour';
$lang['booking_cancel_failed'] = 'Échec de l\'annulation';
```

**(Similar files needed for English and Dutch)**

---

## Testing Strategy

### Unit Tests
- **Models:** Test all CRUD operations, SQL queries
- **Libraries:** Test validation logic, conflict detection
- **Coverage Target:** >75%

### Integration Tests
- **Controller Tests:** Test all AJAX endpoints with mock data
- **Database Tests:** Test with real database operations
- **Authorization Tests:** Verify role-based access control

### Functional Tests
- **Calendar Operations:** Create, edit, delete, drag-drop
- **Conflict Detection:** Aircraft conflicts, instructor conflicts, unavailability
- **Edge Cases:** Midnight boundaries, DST transitions, concurrent bookings

### User Acceptance Testing
- **Pilot Workflow:** Book, modify, cancel own bookings
- **Instructor Workflow:** Manage all bookings
- **Admin Workflow:** Manage unavailability blocks

### Performance Tests
- **Load Testing:** 1000+ bookings in calendar
- **Concurrent Users:** Multiple users booking simultaneously
- **Query Performance:** Calendar load time < 2 seconds

---

## Deployment Plan

### Pre-Deployment Checklist
- [ ] All tests passing
- [ ] Database migration tested
- [ ] Rollback plan prepared
- [ ] Backup created
- [ ] Documentation complete
- [ ] Stakeholder approval obtained

### Deployment Steps
1. **Backup Database** (15 minutes)
2. **Deploy Code** (10 minutes)
3. **Run Migration** (5 minutes)
4. **Clear Caches** (2 minutes)
5. **Smoke Test** (15 minutes)
6. **Monitor Logs** (30 minutes)

### Post-Deployment
- Monitor error logs for 24 hours
- Gather initial user feedback
- Address critical issues within 4 hours
- Schedule follow-up meeting in 1 week

---

## Rollback Strategy

### Rollback Triggers
- Critical bugs preventing bookings
- Data corruption detected
- Performance degradation >50%
- Security vulnerabilities discovered

### Rollback Steps
1. **Stop Application** (if necessary)
2. **Restore Database** from backup
3. **Revert Code** to previous version
4. **Clear Caches**
5. **Verify Rollback** with smoke tests
6. **Notify Users**

### Rollback Time
- **Target:** < 30 minutes
- **Maximum:** < 2 hours

---

## Risk Assessment

### High Risk Items
| Risk | Impact | Mitigation |
|------|--------|------------|
| Calendar library incompatibility | High | Test early with FullCalendar version used in presences |
| Database performance with many bookings | Medium | Add proper indexes, implement pagination |
| Concurrent booking conflicts | High | Use database transactions, implement optimistic locking |
| Credit limit calculation unclear | Medium | Get clarification early, implement stub for later |

### Medium Risk Items
| Risk | Impact | Mitigation |
|------|--------|------------|
| Instructor identification method | Medium | Confirm approach in Phase 0 |
| Section-based permissions | Medium | Design schema flexibly |
| Mobile responsiveness | Medium | Test on actual devices used by club |

### Low Risk Items
| Risk | Impact | Mitigation |
|------|--------|------------|
| Language translations incomplete | Low | Start with French, add others incrementally |
| Advanced features delayed | Low | Prioritize core functionality first |

---

## Next Steps

1. **Review this plan** with stakeholders
2. **Obtain clarifications** on all prerequisite questions (Section II)
3. **Approve budget and timeline**
4. **Assign responsibilities**
5. **Schedule kickoff meeting**
6. **Begin Phase 0** implementation

---

## Document Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2025-10-08 | Claude Code | Initial draft |

---

## Appendices

### Appendix A: Database ER Diagram
*TODO: Create PlantUML diagram after schema is finalized*

### Appendix B: API Endpoint Reference
*TODO: Complete after controller implementation*

### Appendix C: Configuration Options
*TODO: Document after clarifications received*

---

**END OF IMPLEMENTATION PLAN**
