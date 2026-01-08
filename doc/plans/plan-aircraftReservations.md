# Aircraft Reservations System - Implementation Plan

## Overview
Implement a database-backed aircraft reservations system for GVV using FullCalendar v6, replacing the current hardcoded sample data with real database storage.

## Components to Create

### 1. Database Migration (058_create_aircraft_reservations_table.php)

**Location:** `/home/frederic/git/gvv/application/migrations/058_create_aircraft_reservations_table.php`

**Table Schema:**
```sql
CREATE TABLE `reservations` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `aircraft_id` VARCHAR(10) NOT NULL,  -- FK to machinesa.macimmat
  `start_datetime` DATETIME NOT NULL,
  `end_datetime` DATETIME NOT NULL,
  `pilot_member_id` VARCHAR(25) NOT NULL,  -- FK to membres.mlogin
  `instructor_member_id` VARCHAR(25) DEFAULT NULL,  -- FK to membres.mlogin (optional)
  `purpose` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'pending',
  `notes` TEXT DEFAULT NULL,
  `section_id` INT(11) NOT NULL,  -- FK to sections.id
  `created_by` VARCHAR(25) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_by` VARCHAR(25) DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`aircraft_id`) REFERENCES `machinesa` (`macimmat`),
  FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`)
)
```

**Features:**
- Auto-increment ID
- Aircraft reference (registration)
- DateTime range for booking
- Pilot and optional instructor references
- Status tracking (pending, confirmed, completed, cancelled, no_show)
- Section-based filtering support
- Audit fields (created_by, created_at, updated_by, updated_at)
- Indexes on key search fields

### 2. Reservations Model (reservations_model.php)

**Location:** `/home/frederic/git/gvv/application/models/reservations_model.php`

**Key Methods:**

```php
get_calendar_events($start_date, $end_date)
```
- Returns array of events formatted for FullCalendar v6
- Joins with machinesa and membres tables for display names
- Filters by date range and section
- Excludes cancelled reservations
- Applies status-based colors:
  - pending: Amber (#FFC107)
  - confirmed: Green (#28A745)
  - completed: Gray (#6C757D)
  - cancelled: Red (#DC3545)
  - no_show: Pink (#E83E8C)

```php
get_reservation($reservation_id)
```
- Retrieves single reservation with full details
- Includes aircraft model and member names
- Section-aware

```php
is_aircraft_available($aircraft_id, $start_datetime, $end_datetime, $exclude_reservation_id)
```
- Checks for booking conflicts
- Excludes cancelled reservations
- Can exclude specific reservation ID (for updates)

```php
create_reservation($data)
```
- Validates required fields
- Sets defaults (section_id, status, created_by)
- Returns inserted ID

```php
update_reservation($reservation_id, $data)
```
- Updates reservation
- Auto-sets updated_by field

```php
get_aircraft_list()
```
- Returns active aircraft for selection
- Filtered by section
- Format: [aircraft_id => "Model (Registration)"]

**Extends:** Common_Model (for standard CRUD operations)

### 3. Controller Update (reservations.php)

**Location:** `/home/frederic/git/gvv/application/controllers/reservations.php`

**Update get_events() method:**

```php
function get_events() {
    header('Content-Type: application/json');
    
    // Get date range from FullCalendar request
    $start = isset($_GET['start']) ? $_GET['start'] : null;
    $end = isset($_GET['end']) ? $_GET['end'] : null;
    
    // Load model and fetch events
    $this->load->model('reservations_model');
    $events = $this->reservations_model->get_calendar_events($start, $end);
    
    echo json_encode($events);
}
```

**Changes:**
- Replace hardcoded sample data
- Accept start/end date parameters from FullCalendar
- Use model to fetch real database events
- Maintain JSON output format

### 4. Configuration Update (migration.php)

**Location:** `/home/frederic/git/gvv/application/config/migration.php`

**Update migration version:**
```php
$config['migration_version'] = 58;  // from 57
```

## Implementation Steps

1. **Create migration file** (058_create_aircraft_reservations_table.php)
   - Follow GVV migration pattern
   - Include up() and down() methods
   - Use run_queries() helper
   - Add logging with gvv_info() and gvv_error()

2. **Create model file** (reservations_model.php)
   - Extend Common_Model
   - Set table and primary_key properties
   - Implement calendar event formatting
   - Add conflict checking logic
   - Include section filtering

3. **Update controller** (reservations.php)
   - Replace get_events() method
   - Load reservations_model
   - Pass date range parameters

4. **Update migration config**
   - Increment version to 58

5. **Run migration**
   - Execute via application or database
   - Verify table creation
   - Check foreign key constraints

6. **Test**
   - Access http://gvv.net/reservations
   - Verify empty calendar loads
   - Add test reservations via database
   - Confirm events display correctly

## Data Flow

```
FullCalendar (Frontend)
    ↓ GET request with start/end dates
reservations/get_events (Controller)
    ↓ Calls model
reservations_model->get_calendar_events()
    ↓ Queries database with joins
MySQL reservations table
    ↓ Returns raw data
Model formats as FullCalendar events
    ↓ JSON array
Controller outputs JSON
    ↓ 
FullCalendar renders events
```

## Event Format (FullCalendar v6)

```json
{
  "id": "123",
  "title": "PA-28 - John Doe",
  "start": "2026-01-15T09:00:00",
  "end": "2026-01-15T11:00:00",
  "backgroundColor": "#28A745",
  "borderColor": "#28A745",
  "extendedProps": {
    "aircraft": "F-ABCD",
    "aircraft_model": "PA-28",
    "pilot": "John Doe",
    "instructor": "Jane Smith",
    "purpose": "Training",
    "status": "confirmed",
    "notes": "Cross-country navigation practice"
  }
}
```

## Database Conventions (GVV Pattern)

- **Table names:** English, descriptive
- **Field names:** Descriptive snake_case
- **IDs:** BIGINT(20) UNSIGNED auto_increment
- **Foreign keys:** Match referenced field types exactly
  - Aircraft: VARCHAR(10) - machinesa.macimmat
  - Members: VARCHAR(25) - membres.mlogin
  - Sections: INT(11) - sections.id
- **Timestamps:** DATETIME for user-specified times, TIMESTAMP for audit
- **Status fields:** ENUM with explicit values
- **Charset:** utf8mb4_unicode_ci for new tables
- **Engine:** InnoDB for transaction support
- **Comments:** On table and all fields (French acceptable)
- **Indexes:** On foreign keys and search fields

## Security Considerations

- Section-based filtering enforced in model
- Authorization handled in controller constructor (dx_auth)
- SQL injection protection via CodeIgniter query builder
- Foreign key constraints prevent orphaned records

## Future Enhancements (Not in this phase)

- CRUD UI for creating/editing reservations
- Drag-and-drop event editing in FullCalendar
- Email notifications
- Conflict detection UI warnings
- Recurring reservations
- Cancellation/modification policies
- Integration with flight logging
- Mobile app support
- Statistics and reporting

## Testing Checklist

- [ ] Migration runs without errors
- [ ] Table created with correct schema
- [ ] Foreign keys properly constrained
- [ ] Model loads without errors
- [ ] get_calendar_events() returns empty array initially
- [ ] Manual INSERT creates valid reservation
- [ ] FullCalendar displays test reservation
- [ ] Status colors render correctly
- [ ] Date filtering works (change calendar view)
- [ ] Section filtering works (multi-section users)
- [ ] Extended props accessible in event log
- [ ] Cancelled reservations excluded from display

## File Checklist

- [x] `/application/migrations/058_create_aircraft_reservations_table.php`
- [x] `/application/models/reservations_model.php`
- [x] `/application/controllers/reservations.php` (updated)
- [x] `/application/config/migration.php` (already at version 58)
