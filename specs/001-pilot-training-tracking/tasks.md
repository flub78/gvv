# Tasks: Pilot Training Tracking System

**Input**: Design documents from `/specs/001-pilot-training-tracking/`  
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: Tests will be included following GVV PHPUnit standards with 70%+ coverage target.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`
- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions
- **GVV Extension**: `application/controllers/`, `application/models/`, `application/views/training/`
- **Database**: `application/migrations/`, `application/config/migration.php`
- **Files**: `uploads/training/`, `application/language/`
- **Tests**: `application/tests/unit/`, `application/tests/integration/`, `application/tests/controllers/`

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and file structure for training system

- [ ] T001 Create training directory structure in uploads/training/ with programs/, students/, templates/ subdirectories
- [ ] T002 [P] Set file permissions for uploads/training/ (chmod 755) and verify web server write access
- [ ] T003 [P] Create training language files in application/language/french/training_lang.php, english/training_lang.php, dutch/training_lang.php
- [ ] T004 [P] Create training JavaScript file in assets/javascript/training.js for client-side functionality

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [ ] T005 Create database migration 043_create_training_tables.php with training_programs, student_progressions, training_sessions tables
- [ ] T006 Create database migration 044_training_competencies.php with training_competencies, competency_achievements tables  
- [ ] T007 Update application/config/migration.php to version 044
- [ ] T008 Run database migrations to create training tables structure
- [ ] T009 [P] Add training metadata definitions to application/libraries/Gvvmetadata.php for form rendering
- [ ] T010 [P] Create base Training_model extending Common_model in application/models/training_model.php
- [ ] T011 [P] Create authorization helper methods for instructor access control in Training_model
- [ ] T012 Create Markdown template files in uploads/training/templates/ (progress_template.md, session_template.md)

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Create and Manage Pilot Progression Records (Priority: P1) 🎯 MVP

**Goal**: Enable instructors to create, view, and update student progression records with Markdown progress files

**Independent Test**: Instructor can create a student progression record, view student list, update progress notes, and see complete training history

### Tests for User Story 1

**NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [ ] T013 [P] [US1] Create unit test for Training_model in application/tests/unit/Training_model_test.php
- [ ] T014 [P] [US1] Create integration test for progression CRUD operations in application/tests/integration/Training_integration_test.php
- [ ] T015 [P] [US1] Create controller test for training endpoints in application/tests/controllers/Training_controller_test.php

### Implementation for User Story 1

- [ ] T016 [P] [US1] Create training_programs_model.php extending Common_model for training program management
- [ ] T017 [P] [US1] Create training_sessions_model.php extending Common_model for session management
- [ ] T018 [US1] Implement Training_model core methods (get_instructor_students, get_student_progress, create_progression)
- [ ] T019 [US1] Create main Training controller in application/controllers/training.php with authentication and authorization
- [ ] T020 [US1] Implement training dashboard (index method) showing instructor's students
- [ ] T021 [US1] Implement student progression view (student method) with progress details and Markdown content
- [ ] T022 [US1] Create training dashboard view in application/views/training/index.php using Bootstrap 5 and metadata table
- [ ] T023 [US1] Create student progress view in application/views/training/student_progress.php with Markdown rendering
- [ ] T024 [US1] Create progression creation form in application/views/training/create_progression.php
- [ ] T025 [US1] Implement Markdown file creation and management for student progress files
- [ ] T026 [US1] Add progression record creation and update functionality with form validation
- [ ] T027 [US1] Add file upload security and access control for progression Markdown files

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently

---

## Phase 4: User Story 2 - Track and Validate Skill Competencies (Priority: P2)

**Goal**: Define competencies for training programs and track student achievement of specific skills

**Independent Test**: Instructor can define competencies for a program, assign them to students, mark achievements, and see competency status

### Tests for User Story 2

- [ ] T028 [P] [US2] Create unit test for training_competencies_model.php in application/tests/unit/Training_competencies_model_test.php
- [ ] T029 [P] [US2] Create integration test for competency achievement tracking in application/tests/integration/Competency_integration_test.php

### Implementation for User Story 2

- [ ] T030 [P] [US2] Create training_competencies_model.php extending Common_model for competency management
- [ ] T031 [US2] Implement competency definition and management in Training_competencies_model 
- [ ] T032 [US2] Create competency achievement tracking methods in Training_model
- [ ] T033 [US2] Add competency management endpoints to Training controller (competencies, assess methods)
- [ ] T034 [US2] Create competency management views in application/views/training/competencies/
- [ ] T035 [US2] Implement competency assessment form with status updates (not_started, in_progress, achieved, needs_review)
- [ ] T036 [US2] Add competency progress tracking to student progression view
- [ ] T037 [US2] Create competency prerequisite validation logic
- [ ] T038 [US2] Add training level advancement suggestions based on completed competencies

**Checkpoint**: At this point, User Stories 1 AND 2 should both work independently

---

## Phase 5: User Story 3 - Generate Training Progress Reports (Priority: P3)

**Goal**: Generate comprehensive reports for individual students and training program analytics

**Independent Test**: Administrator can generate various training reports (individual, instructor workload, program effectiveness) in multiple formats

### Tests for User Story 3

- [ ] T039 [P] [US3] Create controller test for reporting endpoints in application/tests/controllers/Training_reports_controller_test.php
- [ ] T040 [P] [US3] Create integration test for report generation in application/tests/integration/Training_reports_integration_test.php

### Implementation for User Story 3

- [ ] T041 [P] [US3] Create Training_reports controller in application/controllers/training_reports.php for report generation
- [ ] T042 [US3] Implement individual student progress report with PDF generation using existing TCPDF library
- [ ] T043 [US3] Implement instructor workload report showing assigned students and recent activity
- [ ] T044 [US3] Implement program effectiveness report with completion statistics and analytics
- [ ] T045 [US3] Create report views in application/views/training/reports/ with Bootstrap 5 styling
- [ ] T046 [US3] Add CSV export functionality for training data following existing GVV export patterns
- [ ] T047 [US3] Implement regulatory compliance export format for aviation authority submissions
- [ ] T048 [US3] Add report filtering and date range selection capabilities
- [ ] T049 [US3] Create report caching for performance optimization on large datasets

**Checkpoint**: All core user stories should now be independently functional

---

## Phase 6: User Story 4 - Multi-Instructor Collaboration (Priority: P4)

**Goal**: Enable multiple instructors to collaborate on student training with shared access and notes

**Independent Test**: Multiple instructors can access same student records, add collaborative notes, and see training history from all instructors

### Tests for User Story 4

- [ ] T050 [P] [US4] Create integration test for multi-instructor access in application/tests/integration/Multi_instructor_test.php

### Implementation for User Story 4

- [ ] T051 [P] [US4] Implement instructor collaboration features in Training_model (shared access, notes attribution)
- [ ] T052 [US4] Add instructor assignment and sharing functionality to progression records
- [ ] T053 [US4] Create instructor notes system with attribution and timestamps
- [ ] T054 [US4] Add collaborative view for multiple instructor access to student records
- [ ] T055 [US4] Implement instructor handoff functionality for transferring primary instructor responsibility
- [ ] T056 [US4] Add instructor communication features for sharing training observations

**Checkpoint**: All user stories should now be independently functional

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories and system integration

- [ ] T057 [P] Add training session management functionality linking sessions to progressions and competencies
- [ ] T058 [P] Create session creation form in application/views/training/session_form.php with aircraft integration
- [ ] T059 [P] Implement training session Markdown notes creation and management
- [ ] T060 [P] Add aircraft integration using existing avions table for session tracking
- [ ] T061 [P] Create comprehensive PHPUnit test coverage for all training models achieving 70%+ coverage
- [ ] T062 [P] Add member management integration for student/instructor role validation
- [ ] T063 [P] Implement training data archival and retention management per regulatory requirements
- [ ] T064 [P] Add search and filtering capabilities for students, sessions, and progressions
- [ ] T065 [P] Create training system administration interface for program and competency management
- [ ] T066 [P] Add performance optimization with database indexes and query optimization
- [ ] T067 [P] Implement error handling and logging following GVV patterns
- [ ] T068 [P] Create user documentation and help system for training features
- [ ] T069 Run complete test suite validation ensuring no regressions in existing GVV functionality
- [ ] T070 Create end-to-end playwright tests for complete training workflows

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3-6)**: All depend on Foundational phase completion
  - User stories can then proceed in parallel (if staffed)
  - Or sequentially in priority order (P1 → P2 → P3 → P4)
- **Polish (Phase 7)**: Depends on all desired user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Can start after Foundational (Phase 2) - No dependencies on other stories
- **User Story 2 (P2)**: Can start after Foundational (Phase 2) - Integrates with US1 progression records but independently testable
- **User Story 3 (P3)**: Can start after Foundational (Phase 2) - Uses data from US1/US2 but independently testable
- **User Story 4 (P4)**: Can start after Foundational (Phase 2) - Enhances US1 functionality but independently testable

### Within Each User Story

- Tests MUST be written and FAIL before implementation
- Models before controllers
- Controllers before views
- Core implementation before integration features
- Story complete before moving to next priority

### Parallel Opportunities

- All Setup tasks marked [P] can run in parallel
- All Foundational tasks marked [P] can run in parallel (within Phase 2)
- Once Foundational phase completes, all user stories can start in parallel (if team capacity allows)
- All tests for a user story marked [P] can run in parallel
- Models within a story marked [P] can run in parallel
- Different user stories can be worked on in parallel by different team members

---

## Parallel Example: User Story 1

```bash
# Launch all tests for User Story 1 together:
Task: "Create unit test for Training_model in application/tests/unit/Training_model_test.php"
Task: "Create integration test for progression CRUD operations in application/tests/integration/Training_integration_test.php"
Task: "Create controller test for training endpoints in application/tests/controllers/Training_controller_test.php"

# Launch all models for User Story 1 together:
Task: "Create training_programs_model.php extending Common_model"
Task: "Create training_sessions_model.php extending Common_model"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL - blocks all stories)
3. Complete Phase 3: User Story 1 (Create and Manage Pilot Progression Records)
4. **STOP and VALIDATE**: Test User Story 1 independently using progression record creation and viewing
5. Deploy/demo basic training tracking to instructors

### Incremental Delivery

1. Complete Setup + Foundational → Foundation ready with database and core infrastructure
2. Add User Story 1 → Test independently → Deploy/Demo (MVP: Basic progression tracking!)
3. Add User Story 2 → Test independently → Deploy/Demo (Enhanced: Competency validation!)
4. Add User Story 3 → Test independently → Deploy/Demo (Advanced: Comprehensive reporting!)
5. Add User Story 4 → Test independently → Deploy/Demo (Complete: Multi-instructor collaboration!)
6. Each story adds value without breaking previous stories

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together
2. Once Foundational is done:
   - Developer A: User Story 1 (progression records)
   - Developer B: User Story 2 (competencies)
   - Developer C: User Story 3 (reporting)
3. Stories complete and integrate independently
4. User Story 4 (collaboration) can be added by any developer after US1 is complete

---

## GVV-Specific Considerations

### Environment Requirements
- **CRITICAL**: Always run `source setenv.sh` before any PHP development
- Use PHP 7.4 specifically - newer versions not compatible
- Follow CodeIgniter 2.x patterns and conventions throughout

### Integration Points
- Leverage existing `application/models/common_model.php` patterns
- Integrate with existing `application/models/membres_model.php` for student/instructor data
- Use existing `application/models/avions_model.php` for aircraft assignments
- Follow established metadata-driven architecture in `application/libraries/Gvvmetadata.php`

### Testing Standards
- Achieve 70%+ PHPUnit test coverage following GVV standards
- Use existing test categories: unit, integration, controller, mysql
- Run `./run-all-tests.sh` frequently to check for regressions
- Create playwright end-to-end tests for complete workflows

### File Management
- Follow existing GVV upload security patterns
- Use established file permission management
- Integrate with existing backup and archival procedures
- Maintain directory structure consistency with GVV conventions

---

## Success Validation

### Functional Tests per User Story

**User Story 1 (MVP)**:
- Instructor can create student progression record in under 3 minutes
- Student progression list displays correctly with current status
- Markdown progress files are created and editable
- Training history is preserved and displayed

**User Story 2**:
- Competencies can be defined for training programs
- Student competency status tracking works correctly
- Achievement validation by instructors functions properly
- Training level advancement suggestions are accurate

**User Story 3**:
- Individual progress reports generate in under 30 seconds
- Multiple report formats (HTML, PDF, CSV) work correctly
- Report data accuracy matches database records
- Filtering and date range selection functions properly

**User Story 4**:
- Multiple instructors can access same student records
- Instructor attribution is maintained correctly
- Collaborative notes system works as expected
- Training continuity between instructors is preserved

### Code Quality Validation
- PHPUnit test coverage > 70% for all training modules
- No regressions in existing GVV functionality
- All PHP files pass syntax validation (`php -l`)
- Metadata-driven field rendering working correctly
- Database migrations run successfully without errors

### Integration Success
- Student data properly links to existing member records
- Aircraft assignments integrate with existing aircraft management
- Authorization follows existing GVV role system
- File uploads follow established security patterns
- Multi-language support works for French, English, Dutch

---

## Notes

- [P] tasks = different files, no dependencies on incomplete work
- [Story] label maps task to specific user story for independent development
- Each user story should be independently completable and testable
- Always verify tests fail before implementing features
- Commit after each task or logical group completion
- Stop at any checkpoint to validate story independently
- Follow GVV constitution and development guidelines throughout
- Maintain backward compatibility with existing GVV features