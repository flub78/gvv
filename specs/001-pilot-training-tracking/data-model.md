# Data Model: Pilot Training Tracking System

**Feature**: Pilot Training Tracking System  
**Date**: October 21, 2025  
**Status**: Complete

## Entity Definitions

### Core Entities

#### training_programs
**Purpose**: Define structured training curricula with progression requirements

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| program_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique program identifier |
| program_name | VARCHAR(100) | NOT NULL | Program display name |
| program_code | VARCHAR(20) | UNIQUE, NOT NULL | Short code (e.g., "GLI_BASIC") |
| aircraft_category | VARCHAR(50) | NOT NULL | Aircraft type (glider, motor_glider, tow_plane) |
| description_file | VARCHAR(255) | NULL | Path to Markdown description file |
| min_flight_hours | DECIMAL(5,1) | DEFAULT 0 | Minimum flight hours requirement |
| active | TINYINT(1) | DEFAULT 1 | Program availability status |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP | Last modification |

**Relationships**: 
- One-to-many with training_competencies
- One-to-many with student_progressions

**Validation Rules**:
- program_code must be uppercase alphanumeric with underscores
- aircraft_category must be valid enum value
- description_file path must exist if provided

**State Transitions**: active ↔ inactive (deactivation preserves historical data)

---

#### training_competencies
**Purpose**: Define specific skills and knowledge requirements within programs

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| competency_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique competency identifier |
| program_id | INT | FOREIGN KEY(training_programs) | Associated training program |
| competency_code | VARCHAR(30) | NOT NULL | Unique code within program |
| competency_name | VARCHAR(150) | NOT NULL | Human-readable competency name |
| description | TEXT | NULL | Detailed competency description |
| category | VARCHAR(50) | NOT NULL | Competency grouping (pre_solo, solo, advanced) |
| prerequisite_competencies | JSON | NULL | Array of required competency_ids |
| sort_order | INT | DEFAULT 0 | Display ordering within category |
| active | TINYINT(1) | DEFAULT 1 | Competency availability |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |

**Relationships**:
- Many-to-one with training_programs
- Self-referencing for prerequisites (via JSON array)
- One-to-many with competency_achievements

**Validation Rules**:
- competency_code unique within program scope
- prerequisite_competencies must reference valid competency_ids
- category must be valid enum value

---

#### student_progressions  
**Purpose**: Track individual student training journeys and status

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| progression_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique progression identifier |
| student_member_id | INT | FOREIGN KEY(membres) | Student (existing member) |
| program_id | INT | FOREIGN KEY(training_programs) | Enrolled training program |
| primary_instructor_id | INT | FOREIGN KEY(membres) | Primary assigned instructor |
| start_date | DATE | NOT NULL | Training program start date |
| current_category | VARCHAR(50) | NOT NULL | Current training level |
| status | VARCHAR(20) | NOT NULL | active, suspended, completed, withdrawn |
| progress_file | VARCHAR(255) | NULL | Path to student progress Markdown file |
| total_flight_hours | DECIMAL(5,1) | DEFAULT 0 | Accumulated flight time |
| last_session_date | DATE | NULL | Most recent training session |
| notes | TEXT | NULL | General progression notes |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Enrollment timestamp |
| updated_at | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP | Last update |

**Relationships**:
- Many-to-one with membres (student)
- Many-to-one with membres (instructor)  
- Many-to-one with training_programs
- One-to-many with training_sessions
- One-to-many with competency_achievements

**Validation Rules**:
- student_member_id must exist in membres table
- primary_instructor_id must exist and have instructor role
- start_date cannot be future date
- status must be valid enum value
- Unique constraint on (student_member_id, program_id) for active progressions

**State Transitions**: active → suspended → active, active → completed, active → withdrawn

---

#### training_sessions
**Purpose**: Record individual training activities and outcomes

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| session_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique session identifier |
| progression_id | INT | FOREIGN KEY(student_progressions) | Associated student progression |
| instructor_id | INT | FOREIGN KEY(membres) | Conducting instructor |
| session_date | DATE | NOT NULL | Training session date |
| aircraft_id | INT | FOREIGN KEY(avions) | Aircraft used (existing table) |
| flight_duration | DECIMAL(3,1) | NULL | Flight time in hours |
| ground_duration | DECIMAL(3,1) | NULL | Ground instruction time |
| session_type | VARCHAR(30) | NOT NULL | dual, solo, briefing, examination |
| objectives | TEXT | NULL | Session learning objectives |
| outcomes | TEXT | NULL | Session results and observations |
| session_file | VARCHAR(255) | NULL | Path to detailed session notes (MD) |
| weather_conditions | VARCHAR(100) | NULL | Weather during session |
| instructor_signature | VARCHAR(100) | NULL | Digital signature/validation |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation |

**Relationships**:
- Many-to-one with student_progressions
- Many-to-one with membres (instructor)
- Many-to-one with avions (aircraft)
- One-to-many with competency_achievements (competencies addressed in session)

**Validation Rules**:
- session_date cannot be future date
- flight_duration + ground_duration must be > 0
- session_type must be valid enum value
- instructor_id must have instructor role
- aircraft_id must exist and be active

---

#### competency_achievements
**Purpose**: Track student progress on specific competencies

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| achievement_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique achievement identifier |
| progression_id | INT | FOREIGN KEY(student_progressions) | Student progression |
| competency_id | INT | FOREIGN KEY(training_competencies) | Specific competency |
| session_id | INT | FOREIGN KEY(training_sessions) | Session where addressed |
| status | VARCHAR(20) | NOT NULL | not_started, in_progress, achieved, needs_review |
| assessment_date | DATE | NOT NULL | When competency was assessed |
| validating_instructor_id | INT | FOREIGN KEY(membres) | Instructor validating achievement |
| notes | TEXT | NULL | Assessment notes and feedback |
| attempts | INT | DEFAULT 1 | Number of assessment attempts |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation |
| updated_at | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP | Last status change |

**Relationships**:
- Many-to-one with student_progressions
- Many-to-one with training_competencies  
- Many-to-one with training_sessions
- Many-to-one with membres (validating instructor)

**Validation Rules**:
- Unique constraint on (progression_id, competency_id) - one achievement record per competency per student
- status must be valid enum value
- assessment_date cannot be future date
- validating_instructor_id required when status = 'achieved'

**State Transitions**: not_started → in_progress → achieved, any_status → needs_review

## File System Integration

### Markdown File Structure

#### Training Program Descriptions
```
uploads/training/programs/
├── [program_id]_[program_code].md
│   ├── Program overview and objectives
│   ├── Prerequisites and requirements  
│   ├── Competency category descriptions
│   └── Assessment criteria
```

#### Student Progress Files  
```
uploads/training/students/[student_member_id]/
├── progress.md                    # Overall progress narrative
└── sessions/
    ├── [session_date]_[session_id].md   # Detailed session notes
    └── [session_date]_[session_id].md
```

#### File Naming Conventions
- Program files: `{program_id}_{program_code}.md` (e.g., `1_GLI_BASIC.md`)
- Progress files: `progress.md` (one per student)
- Session files: `{YYYY-MM-DD}_{session_id}.md` (e.g., `2025-10-21_127.md`)

### Database-File Relationship
- Database stores file paths in `description_file`, `progress_file`, `session_file` columns
- Files contain rich content, database contains structured metadata
- File operations managed through CodeIgniter controllers
- No direct web access to files (served through application logic)

## Integration Points

### Existing GVV Tables
- **membres**: Student and instructor authentication/authorization
- **avions**: Aircraft assignment for training sessions  
- **sections**: Training program organization (if applicable)
- **gvv_roles**: Instructor role validation

### Metadata Integration
Required metadata definitions in `application/libraries/Gvvmetadata.php`:

```php
// Training program fields
$this->field['training_programs']['program_name']['Type'] = 'varchar';
$this->field['training_programs']['aircraft_category']['Subtype'] = 'selector';
$this->field['training_programs']['aircraft_category']['Selector'] = 'aircraft_categories';

// Competency status selector
$this->field['competency_achievements']['status']['Subtype'] = 'enumeration';
$this->field['competency_achievements']['status']['Enumeration'] = 
    ['not_started' => 'Not Started', 'in_progress' => 'In Progress', 
     'achieved' => 'Achieved', 'needs_review' => 'Needs Review'];

// Session type selector
$this->field['training_sessions']['session_type']['Subtype'] = 'enumeration';
$this->field['training_sessions']['session_type']['Enumeration'] = 
    ['dual' => 'Dual Instruction', 'solo' => 'Solo Flight', 
     'briefing' => 'Ground Briefing', 'examination' => 'Assessment'];
```

## Performance Considerations

### Database Indexes
```sql
-- Query optimization indexes
CREATE INDEX idx_progression_student ON student_progressions(student_member_id, status);
CREATE INDEX idx_sessions_progression_date ON training_sessions(progression_id, session_date);
CREATE INDEX idx_achievements_progression ON competency_achievements(progression_id, status);
CREATE INDEX idx_competencies_program ON training_competencies(program_id, category, sort_order);
```

### Caching Strategy
- Training program descriptions cached after first load
- Student progress summary cached with TTL
- Competency definitions cached per program
- File existence validation cached

## Migration Dependencies

### Required Database Migrations
1. **043_create_training_tables.php**: Core training tables
2. **044_training_competencies.php**: Competency system
3. **045_training_file_paths.php**: File system integration
4. **046_training_indexes.php**: Performance optimization

### Migration Order Dependencies
- Requires existing `membres` table for foreign keys
- Requires existing `avions` table for aircraft references
- Must update `application/config/migration.php` to version 046

### Data Seeding Requirements
- Default training programs for common glider training
- Standard competency definitions for basic/advanced programs
- Example progress and session templates
- Test data for development and validation