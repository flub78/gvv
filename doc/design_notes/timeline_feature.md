# Timeline Feature Implementation

## Overview

**Status**: ✅ Implemented and tested  
**Date**: January 9, 2026  
**Scope**: Timeline view for aircraft reservations with resource-based organization, interactive drag-drop, and callback tracing.

## Architecture

### Data Model

**Database Table: `reservations` (Migration 059)**

```sql
CREATE TABLE reservations (
  id INT PRIMARY KEY AUTO_INCREMENT,
  aircraft_id VARCHAR(10) NOT NULL,
  start_datetime DATETIME NOT NULL,
  end_datetime DATETIME NOT NULL,
  pilot_member_id INT,
  instructor_member_id INT,
  purpose VARCHAR(255),
  status ENUM('pending', 'confirmed', 'completed', 'no_show') DEFAULT 'pending',
  notes TEXT,
  section_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Key Design Decisions**:
- No foreign key constraints (to avoid MySQL collation issues across legacy tables)
- Status enum for reservation lifecycle tracking
- `start_datetime` and `end_datetime` for easy filtering and duration calculation

### Model Layer (`reservations_model.php`)

#### Core Methods

1. **`get_aircraft_list()`**
   - Returns all active aircraft indexed by aircraft_id
   - Used for populating resource list in timeline

2. **`get_day_reservations($date)`**
   - Retrieves reservations for a specific date
   - Returns data organized by aircraft_id
   - Format:
     ```php
     [
       'F-BLIT' => [
         ['id' => 8, 'title' => 'Flight...', 'start_datetime' => '...', 'end_datetime' => '...'],
         ...
       ],
       'F-BSDH' => [...]
     ]
     ```

3. **`get_timeline_events($date)`**
   - Returns events in FullCalendar Timeline API format
   - Format:
     ```json
     [
       {
         "id": "8",
         "title": "F-BLIT Flight",
         "start": "2026-01-09T14:00:00",
         "end": "2026-01-09T15:30:00",
         "resourceId": "F-BLIT",
         "status": "confirmed"
       }
     ]
     ```

4. **`get_reservation($id)`** & **`create_reservation()`, `update_reservation()`**
   - Basic CRUD operations for reservations

### Controller Layer (`reservations.php`)

#### Core Methods

1. **`index()`**
   - Displays FullCalendar v6 month/week/day view
   - Route: `GET /reservations/`

2. **`timeline()`**
   - Displays timeline view organized by aircraft
   - Supports date navigation via `?date=YYYY-MM-DD`
   - Route: `GET /reservations/timeline`

3. **`get_timeline_data()`** - JSON API
   - Returns timeline data for a specific date
   - Accepts: `?date=YYYY-MM-DD` (default: today)
   - Response:
     ```json
     {
       "date": "2026-01-09",
       "resources": [{"id": "F-BLIT", "title": "Aircraft Name"}],
       "events": [...]
     }
     ```
   - Route: `GET /reservations/get_timeline_data`

#### Callback Tracing Methods (for UI interaction logging)

1. **`on_event_click()`** - POST
   - Traces when user clicks on an event
   - Parameters: `event_id`
   - Logs: "Timeline: User clicked on event ID X"

2. **`on_event_drop()`** - POST
   - Traces when user drag-drops an event
   - Parameters: `event_id`, `start_datetime`, `end_datetime`, `resource_id`
   - Logs: "Timeline: User dragged event ID X to aircraft Y from Z to W"
   - **Note**: Current implementation only logs; actual persistence would require update logic

3. **`on_slot_click()`** - POST
   - Traces when user clicks on an empty time slot
   - Parameters: `resource_id`, `clicked_time`
   - Logs: "Timeline: User clicked empty slot for aircraft X at HH:MM:SS"
   - **Note**: Could be extended to open create reservation dialog

### View Layer (`timeline.php`)

#### Layout Structure

```
┌─────────────────────────────────────────────────────────┐
│  Timeline Header (Title, Date Picker, Navigation)      │
├──────────────┬──────────────────────────────────────────┤
│   Aircraft   │  Time Header (00:00 - 23:00)             │
│   Resources  ├──────────────────────────────────────────┤
│   (F-BLIT,   │  Time Slots with Events                  │
│    F-BSDH,   │  (Draggable, clickable, colored by      │
│    etc)      │   status)                                │
│              ├──────────────────────────────────────────┤
│              │  ...more resources...                    │
└──────────────┴──────────────────────────────────────────┘
```

#### Key Features

1. **Resource Column** (Left sidebar)
   - Lists all active aircraft
   - Sticky, scrollable

2. **Time Grid** (Main area)
   - 24-hour day view (00:00 - 23:59)
   - Time slots clickable for creating new reservations
   - Events displayed with width proportional to duration

3. **Events**
   - Color-coded by status:
     - **Pending** (Yellow): #FFC107
     - **Confirmed** (Green): #28A745
     - **Completed** (Gray): #6C757D
     - **No-show** (Pink): #E83E8C
   - Interactive: Click to view details, drag to reschedule
   - Tooltip on hover

4. **Date Navigation**
   - Previous/Today/Next buttons
   - Display current date in human-readable format
   - Updates timeline when date changes

#### JavaScript Functionality

1. **`loadTimelineData()`** - Fetches data from server
2. **`renderTimeline()`** - Renders complete view
3. **`renderTimeHeader()`** - Draws hourly headers
4. **`renderResources()`** - Draws aircraft list
5. **`renderEvents()`** - Draws reservation events
6. **`handleEventClick(e, event)`** - Event click handler
7. **`startDragging(e, event)`** - Drag initialization
8. **`handleSlotClick(slotEl)`** - Empty slot click handler
9. **`navigateToDate(date)`** - Date navigation handler

## Testing

### Smoke Tests

Created: `application/tests/unit/timeline_smoke_test.php`

**Test Cases**:
1. ✓ Model has required methods (get_aircraft_list, get_day_reservations, get_timeline_events)
2. ✓ Controller has required methods (timeline, get_timeline_data, callbacks)
3. ✓ View file exists with required DOM elements
4. ✓ Migration file exists with correct table structure

**Execution**:
```bash
./run-all-tests.sh --coverage
```

## Future Enhancements

### For FullCalendar Premium Migration

When transitioning to FullCalendar Premium:

1. **Resource API Compatibility**
   - Current model methods already return FullCalendar-compatible format
   - Resource field: `resourceId` (aircraft_id)
   - Time format: ISO 8601 (2026-01-09T14:00:00)

2. **Event Callbacks**
   - Current placeholder methods (on_event_click, on_event_drop, on_slot_click) demonstrate expected signatures
   - Ready for premium API event handlers: `eventClick`, `eventDrop`, `select`

3. **Premium Features to Add**
   - Multi-day reservations with day borders
   - Drag-drop across aircraft and time
   - Resource grouping by aircraft type
   - Recurring reservations
   - Slot selection for new reservation creation
   - Timezone support for international clubs

### Suggested Implementation Path

1. Evaluate FullCalendar Premium licensing requirements
2. Update view to load Premium library instead of custom timeline
3. Replace JavaScript rendering with Premium API calls
4. Update controller callbacks to handle premium event objects
5. Add resource grouping, filtering, and advanced scheduling
6. Implement conflict detection and auto-resolve logic

## Database Example

**Test Data** (created with migration):
```
ID  | Aircraft | Start                | End                  | Pilot | Status
----|----------|----------------------|----------------------|-------|----------
8   | F-BLIT   | 2026-01-08 14:00:00 | 2026-01-08 15:30:00 | 9992  | confirmed
9   | F-FTHT   | 2026-01-08 10:00:00 | 2026-01-08 11:00:00 | 9993  | pending
10  | F-BLIT   | 2026-01-15 16:00:00 | 2026-01-15 17:00:00 | 9994  | confirmed
11  | F-BSDH   | 2026-01-12 08:00:00 | 2026-01-12 09:30:00 | 9995  | completed
```

## Deployment Checklist

- [x] Database migration (059) applied
- [x] Model methods implemented
- [x] Controller methods implemented
- [x] View template created
- [x] JavaScript timeline rendering functional
- [x] Callback tracing endpoints working
- [x] Smoke tests passing
- [x] Test data available
- [ ] Language files updated (future)
- [ ] Accessibility audit (future)
- [ ] Performance optimization (future)

## Related Documentation

- Design: `/doc/design_notes/calendar_database.md`
- Testing: `/TESTING.md`
- Architecture: `/doc/development/workflow.md`
- Authorization: Checks user login; follows DX_Auth pattern
