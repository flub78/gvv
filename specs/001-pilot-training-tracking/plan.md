# Implementation Plan: Pilot Training Tracking System

**Branch**: `001-pilot-training-tracking` | **Date**: October 21, 2025 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from `/specs/001-pilot-training-tracking/spec.md` + user requirement for MD files to store training descriptions and student progress sheets

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

Primary requirement: Create a pilot training tracking system for gliding clubs that replaces manual paper-based tracking with systematic digital progression records, skill competency validation, and multi-instructor collaboration.

Technical approach: Extend the existing GVV CodeIgniter 2.x application with new controllers, models, and views following the established metadata-driven architecture. Use Markdown files to store structured training descriptions and student progress sheets, enabling version control and easy text-based editing while maintaining database relationships for querying and reporting.

## Technical Context

**Language/Version**: PHP 7.4 (CodeIgniter 2.x framework - NON-NEGOTIABLE legacy requirement)  
**Primary Dependencies**: CodeIgniter 2.x, Bootstrap 5, MySQL 5.x, existing GVV metadata system (`application/libraries/Gvvmetadata.php`)  
**Storage**: MySQL database with existing member management integration + Markdown files for training descriptions and progress sheets (user requirement)  
**Testing**: PHPUnit (replacing legacy CodeIgniter Unit_test), categorized test suites (unit/integration/controller/mysql)  
**Target Platform**: Web application (existing GVV club management system extension)
**Project Type**: Web application extension - integrates into existing CodeIgniter MVC structure  
**Performance Goals**: <3 minutes for progression record updates, <30 seconds for report generation, <24 hours for session documentation  
**Constraints**: MUST follow metadata-driven architecture, MUST integrate with existing member management, MUST support multi-language (French/English/Dutch)  
**Scale/Scope**: Support 5-6 gliding associations, ~50-200 students per club, multiple instructors, regulatory compliance requirements

**Key Technical Decisions**:
- **Markdown Storage**: Training descriptions and student progress sheets stored as .md files for version control and easy editing
- **Hybrid Approach**: Database for structured queries/relationships + file system for rich content and progress narratives
- **File Organization**: Structured directory layout under `/uploads/training/` with club/student/session organization
- **Integration Pattern**: Leverage existing `Common_model` patterns and metadata-driven field rendering

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

✅ **I. Metadata-Driven Architecture**: All database fields will be defined through `Gvvmetadata.php`. Form generation will use `$this->gvvmetadata->table()` and metadata-aware helpers.

✅ **II. Legacy-First Development**: Maintains PHP 7.4 and CodeIgniter 2.x compatibility. Follows established MVC patterns. Will search existing codebase for reusable components.

✅ **III. Test Coverage Excellence**: Will include PHPUnit tests with 70%+ coverage. Tests will be categorized appropriately (unit/integration/controller).

✅ **IV. Environment-Specific Development**: Will use `source setenv.sh` for PHP 7.4. Database migrations will update `migration.php` version.

✅ **V. Code Reuse Priority**: Will search existing models and controllers for similar patterns. Will maintain manual dependency management.

**Technology Stack Compliance**:
- ✅ PHP 7.4 specifically  
- ✅ CodeIgniter 2.x (legacy version)
- ✅ MySQL 5.x with CI migrations
- ✅ Bootstrap 5 for UI
- ✅ PHPUnit testing
- ✅ Manual dependency management
- ✅ Multi-language support (French/English/Dutch)

## Constitution Check (Post-Design Review)

*GATE: Re-checked after Phase 1 design completion.*

✅ **I. Metadata-Driven Architecture**: 
- **Compliance**: All training fields defined in `Gvvmetadata.php` (see quickstart.md section 3.1)
- **Implementation**: Uses `$this->gvvmetadata->table()` for table views and metadata-aware helpers
- **Validation**: Form rendering follows established metadata patterns

✅ **II. Legacy-First Development**: 
- **Compliance**: Extends existing CodeIgniter 2.x patterns without framework modifications
- **Reuse**: Leverages existing `Common_model`, member management, and aircraft tables
- **Architecture**: Follows established MVC structure in `/application/controllers|models|views/training/`

✅ **III. Test Coverage Excellence**: 
- **Framework**: PHPUnit tests planned for unit/integration/controller categories
- **Coverage**: 70%+ target with tests for models, file operations, and authorization
- **Structure**: Tests organized following existing GVV test suite patterns

✅ **IV. Environment-Specific Development**: 
- **PHP 7.4**: All development requires `source setenv.sh` 
- **Migrations**: Database migrations increment version to 043+ with config updates
- **Permissions**: File structure follows existing upload security patterns

✅ **V. Code Reuse Priority**: 
- **Integration**: Reuses existing member management, aircraft tables, authorization
- **Patterns**: Follows `Common_model` patterns and established controller structures  
- **Dependencies**: No new external dependencies, uses existing TCPDF and file handling

**Design-Specific Validation**:
- ✅ Markdown file integration follows existing upload patterns
- ✅ Database schema extends existing tables without modification
- ✅ API contracts follow CodeIgniter 2.x REST-like patterns
- ✅ Multi-language support structure maintained
- ✅ Bootstrap 5 UI consistency preserved
- ✅ No constitutional violations introduced during design phase

**Ready for Phase 2 (Implementation)**: All constitutional requirements satisfied.

## Project Structure

### Documentation (this feature)

```
specs/001-pilot-training-tracking/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)

```
application/
├── controllers/
│   ├── training.php           # Main training controller
│   ├── training_programs.php   # Training program management
│   └── training_reports.php    # Reporting controller
├── models/
│   ├── training_model.php      # Main training model
│   ├── training_sessions_model.php
│   ├── training_competencies_model.php
│   └── training_programs_model.php
├── views/
│   └── training/
│       ├── index.php          # Training dashboard
│       ├── student_progress.php
│       ├── session_form.php
│       └── reports/
├── libraries/
│   └── Training_manager.php   # Business logic library
├── migrations/
│   ├── 043_create_training_tables.php
│   └── 044_training_competencies.php
└── language/
    ├── french/training_lang.php
    ├── english/training_lang.php
    └── dutch/training_lang.php

uploads/
└── training/               # Markdown files storage
    ├── programs/          # Training program descriptions (.md)
    ├── students/          # Student progress sheets (.md)
    │   └── [student_id]/
    │       ├── progress.md
    │       └── sessions/
    └── templates/         # Standard training templates (.md)

assets/
└── javascript/
    └── training.js        # Training-specific JS functionality
```

**Structure Decision**: Extending existing GVV CodeIgniter structure with training-specific controllers, models, and views. Markdown files organized under `/uploads/training/` following GVV's established upload patterns. Integration with existing member management through foreign keys and shared authentication.

## Complexity Tracking

*No constitutional violations detected. All requirements align with GVV development principles.*

**Implementation follows established GVV patterns**:
- Extending CodeIgniter 2.x MVC architecture
- Metadata-driven field definitions
- Integration with existing member management
- Bootstrap 5 UI components
- Multi-language support structure
- PHPUnit testing framework

