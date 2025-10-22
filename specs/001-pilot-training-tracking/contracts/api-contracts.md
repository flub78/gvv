# Training System API Contracts

**Feature**: Pilot Training Tracking System  
**Date**: October 21, 2025  
**Framework**: CodeIgniter 2.x REST-like endpoints

## Training Controller Endpoints

### Base URL Pattern
All training endpoints follow GVV convention: `/training/[action]/[id]`

### Core Training Management

#### GET /training/index
**Purpose**: Training dashboard with student overview
**Parameters**: None (session-based authentication)
**Response**: HTML view with Bootstrap 5 layout
**Data**: List of current student progressions for logged-in instructor

#### GET /training/student/[progression_id]
**Purpose**: Individual student progress view
**Parameters**: 
- `progression_id` (required): Student progression identifier
**Response**: HTML view with progress details
**Data**: Student info, competency status, recent sessions, next objectives

#### POST /training/session/create
**Purpose**: Create new training session record
**Parameters**:
```php
$_POST = [
    'progression_id' => int,      // Required
    'session_date' => 'YYYY-MM-DD',  // Required
    'aircraft_id' => int,         // Required
    'session_type' => string,     // dual|solo|briefing|examination
    'flight_duration' => float,   // Hours (nullable)
    'ground_duration' => float,   // Hours (nullable)
    'objectives' => string,       // Session objectives (nullable)
    'outcomes' => string,         // Session results (nullable)
    'weather_conditions' => string, // Weather notes (nullable)
    'competencies' => array       // Competency assessments
];
```
**Response**: JSON status + redirect
**Validation**: Date validation, instructor authorization, aircraft availability

#### PUT /training/session/[session_id]
**Purpose**: Update existing training session
**Parameters**: Same as create + session_id
**Response**: JSON status
**Authorization**: Instructor must own session or have admin role

### Competency Management

#### GET /training/competencies/[program_id]
**Purpose**: List competencies for training program
**Parameters**: 
- `program_id` (required): Training program identifier
**Response**: JSON array of competencies
**Data Structure**:
```json
{
  "competencies": [
    {
      "competency_id": 1,
      "competency_code": "PRE_SOLO_001",
      "competency_name": "Straight and Level Flight",
      "category": "pre_solo",
      "description": "Maintain altitude and heading...",
      "prerequisites": [2, 3],
      "sort_order": 1
    }
  ],
  "categories": ["pre_solo", "solo", "cross_country"]
}
```

#### POST /training/competency/assess
**Purpose**: Record competency assessment
**Parameters**:
```php
$_POST = [
    'progression_id' => int,      // Required
    'competency_id' => int,       // Required  
    'session_id' => int,          // Required
    'status' => string,           // not_started|in_progress|achieved|needs_review
    'notes' => string,            // Assessment notes (nullable)
    'attempts' => int             // Attempt number (default 1)
];
```
**Response**: JSON status
**Validation**: Instructor authorization, valid status transitions

### Progress Tracking

#### GET /training/progress/[progression_id]
**Purpose**: Detailed progress view with Markdown content
**Parameters**: 
- `progression_id` (required): Student progression identifier
**Response**: HTML view with progress narrative
**Content**: 
- Database progression data
- Rendered Markdown progress file content
- Recent session summaries
- Competency achievement timeline

#### POST /training/progress/update
**Purpose**: Update student progress narrative
**Parameters**:
```php
$_POST = [
    'progression_id' => int,      // Required
    'progress_content' => string, // Markdown content
    'notes' => string,            // Database notes (nullable)
    'current_category' => string  // Training level update
];
```
**Response**: JSON status
**File Operations**: Updates Markdown progress file
**Authorization**: Instructor must be assigned to student

### Reporting Endpoints

#### GET /training/reports/student/[progression_id]
**Purpose**: Individual student progress report
**Parameters**: 
- `progression_id` (required)
- `format` (optional): html|pdf|csv (default: html)
**Response**: Report in requested format
**Content**: Complete training history, competency status, recommendations

#### GET /training/reports/instructor
**Purpose**: Instructor workload and student overview
**Parameters**:
- `instructor_id` (optional): specific instructor (admin only)
- `date_from` (optional): report period start
- `date_to` (optional): report period end
**Response**: HTML report with instructor statistics
**Content**: Assigned students, recent sessions, pending assessments

#### GET /training/reports/program/[program_id]
**Purpose**: Program effectiveness and completion statistics  
**Parameters**:
- `program_id` (required)
- `date_from` (optional): analysis period
- `date_to` (optional): analysis period
**Response**: HTML report with program metrics
**Content**: Completion rates, average progression time, competency analysis

## File Management Endpoints

### Markdown File Operations

#### GET /training/files/progress/[progression_id]
**Purpose**: Retrieve student progress Markdown content
**Parameters**: 
- `progression_id` (required)
**Response**: Raw Markdown content or 404
**Authorization**: Instructor access to assigned students only

#### POST /training/files/progress/[progression_id]  
**Purpose**: Update student progress Markdown file
**Parameters**:
```php
$_POST = [
    'content' => string  // Markdown content
];
```
**Response**: JSON status
**File Operations**: Create/update progress.md file
**Backup**: Previous version archived with timestamp

#### GET /training/files/session/[session_id]
**Purpose**: Retrieve session notes Markdown content
**Parameters**: 
- `session_id` (required)
**Response**: Raw Markdown content or 404
**Authorization**: Session instructor or student access

#### POST /training/files/session/[session_id]
**Purpose**: Update session notes Markdown file  
**Parameters**:
```php
$_POST = [
    'content' => string  // Markdown content
];
```
**Response**: JSON status
**File Operations**: Create/update session notes file

## Data Export Endpoints

### CSV Export

#### GET /training/export/progressions
**Purpose**: Export student progressions as CSV
**Parameters**:
- `program_id` (optional): filter by program
- `instructor_id` (optional): filter by instructor  
- `status` (optional): filter by progression status
**Response**: CSV download
**Columns**: Student name, program, instructor, start date, status, completion %

#### GET /training/export/sessions
**Purpose**: Export training sessions as CSV
**Parameters**:
- `progression_id` (optional): specific student
- `date_from` (required): session date range
- `date_to` (required): session date range
**Response**: CSV download
**Columns**: Date, student, instructor, aircraft, duration, session type, objectives

### JSON API

#### GET /training/api/dashboard
**Purpose**: JSON data for training dashboard
**Response**:
```json
{
  "summary": {
    "active_students": 15,
    "pending_assessments": 7,
    "recent_sessions": 23,
    "completion_rate": 85.5
  },
  "students": [
    {
      "progression_id": 1,
      "student_name": "John Doe",
      "program_name": "Basic Glider Training",
      "current_category": "pre_solo",
      "completion_percentage": 60,
      "last_session": "2025-10-20",
      "next_objective": "Emergency procedures"
    }
  ],
  "pending_assessments": [
    {
      "student_name": "Jane Smith",
      "competency_name": "Solo flight preparation",
      "session_date": "2025-10-21",
      "instructor": "Mike Wilson"
    }
  ]
}
```

## Error Handling

### Standard Error Responses
```json
{
  "status": "error",
  "message": "User-friendly error message",
  "code": "ERROR_CODE",
  "details": {
    "field": "validation error details"
  }
}
```

### Common Error Codes
- `UNAUTHORIZED`: Insufficient permissions
- `VALIDATION_FAILED`: Form validation errors
- `STUDENT_NOT_FOUND`: Invalid progression_id
- `SESSION_NOT_FOUND`: Invalid session_id
- `FILE_NOT_FOUND`: Missing Markdown file
- `COMPETENCY_INVALID`: Invalid competency assessment
- `AIRCRAFT_UNAVAILABLE`: Aircraft booking conflict

## Authentication & Authorization

### Session-Based Authentication
All endpoints require valid GVV user session following existing authentication patterns.

### Role-Based Authorization
- **Students**: Read access to own progression data only
- **Instructors**: Full access to assigned student progressions, read access to other progressions  
- **Administrators**: Full access to all training data and management functions

### Data Access Patterns
```php
// Instructor authorization check
if (!$this->training_model->instructor_has_access($instructor_id, $progression_id)) {
    show_error('Unauthorized access', 403);
}

// Student self-access check
if ($this->session->userdata('membre_id') == $progression->student_member_id) {
    // Allow student to view own data
}
```

## Integration with Existing GVV Systems

### Member Management Integration
- Leverages existing `membres` table for student/instructor data
- Uses existing role system for authorization
- Maintains existing session management

### Aircraft Management Integration  
- Uses existing `avions` table for aircraft assignments
- Respects aircraft availability and booking systems
- Integrates with flight logging if applicable

### Reporting Integration
- Follows existing GVV report generation patterns
- Uses established TCPDF library for PDF reports
- Maintains consistent styling with Bootstrap 5