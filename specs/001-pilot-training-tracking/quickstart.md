# Quickstart Guide: Pilot Training Tracking System

**Feature**: Pilot Training Tracking System  
**Date**: October 21, 2025  
**Target Audience**: Developers implementing the training system

## Prerequisites

### Environment Setup (CRITICAL)
```bash
# ALWAYS run before any PHP development
source setenv.sh

# Verify PHP 7.4 is active
php --version  # Must show PHP 7.4.x
```

### Required GVV Knowledge
- Understand CodeIgniter 2.x MVC patterns
- Familiar with GVV metadata-driven architecture (`application/libraries/Gvvmetadata.php`)
- Knowledge of existing member management (`application/models/membres_model.php`)
- Bootstrap 5 styling conventions used in GVV

### Documentation References
- Read `/doc/development/workflow.md` for GVV feature development process
- Review `application/models/common_model.php` for base model patterns
- Check existing controllers for CodeIgniter 2.x conventions

## Implementation Phases

### Phase 1: Database Foundation (Priority: P1)

#### 1.1 Create Database Migration
```bash
# Create numbered migration file
touch application/migrations/043_create_training_tables.php
```

Migration content (based on data-model.md):
```php
<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_training_tables extends CI_Migration {
    
    public function up() {
        // training_programs table
        $this->dbforge->add_field([
            'program_id' => [
                'type' => 'INT',
                'auto_increment' => TRUE
            ],
            'program_name' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => FALSE
            ],
            // ... (see data-model.md for complete schema)
        ]);
        $this->dbforge->add_key('program_id', TRUE);
        $this->dbforge->create_table('training_programs');
        
        // Add other tables following same pattern
    }
    
    public function down() {
        $this->dbforge->drop_table('training_programs');
        // Drop other tables in reverse order
    }
}
```

#### 1.2 Update Migration Configuration
```php
// application/config/migration.php
$config['migration_version'] = 43; // Update to latest version
```

#### 1.3 Run Migration
```bash
# Test migration on development database
php run_migrations.php
```

### Phase 2: Model Implementation (Priority: P1)

#### 2.1 Create Base Training Model
```php
// application/models/training_model.php
class Training_model extends Common_model {
    
    public function __construct() {
        parent::__construct();
        $this->table = 'student_progressions';
        $this->key = 'progression_id';
    }
    
    // Override select_page for training-specific queries
    public function select_page($where = "", $table = "") {
        // Include joins for student names, instructor names
        // Return progression data with related information
    }
    
    // Training-specific methods
    public function get_student_progress($progression_id) {
        // Get progression with student and instructor details
    }
    
    public function instructor_has_access($instructor_id, $progression_id) {
        // Authorization check for instructor access
    }
}
```

#### 2.2 Create Supporting Models
Follow same pattern for:
- `training_sessions_model.php`
- `training_competencies_model.php`  
- `training_programs_model.php`

### Phase 3: Metadata Configuration (Priority: P1)

#### 3.1 Add Training Metadata
```php
// application/libraries/Gvvmetadata.php - add to constructor

// Training program fields
$this->field['training_programs']['program_name']['Type'] = 'varchar';
$this->field['training_programs']['program_name']['Size'] = 100;

$this->field['training_programs']['aircraft_category']['Type'] = 'varchar';
$this->field['training_programs']['aircraft_category']['Subtype'] = 'enumeration';
$this->field['training_programs']['aircraft_category']['Enumeration'] = [
    'glider' => 'Glider',
    'motor_glider' => 'Motor Glider', 
    'tow_plane' => 'Tow Plane'
];

// Session status enumeration
$this->field['training_sessions']['session_type']['Type'] = 'varchar';
$this->field['training_sessions']['session_type']['Subtype'] = 'enumeration';
$this->field['training_sessions']['session_type']['Enumeration'] = [
    'dual' => 'Dual Instruction',
    'solo' => 'Solo Flight',
    'briefing' => 'Ground Briefing',
    'examination' => 'Assessment'
];

// Add selectors for member/instructor dropdowns
public function instructor_selector() {
    // Return array of instructors for dropdown
}

public function student_selector() {
    // Return array of students for dropdown
}
```

### Phase 4: Controller Implementation (Priority: P1)

#### 4.1 Create Main Training Controller
```php
// application/controllers/training.php
class Training extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('training_model');
        $this->load->library('gvvmetadata');
        
        // Ensure user is logged in
        if (!$this->session->userdata('membre_id')) {
            redirect('login');
        }
    }
    
    public function index() {
        // Training dashboard
        $data['progressions'] = $this->training_model->get_instructor_students(
            $this->session->userdata('membre_id')
        );
        $this->load->view('training/index', $data);
    }
    
    public function student($progression_id) {
        // Individual student view
        if (!$this->training_model->instructor_has_access(
            $this->session->userdata('membre_id'), $progression_id)) {
            show_error('Unauthorized', 403);
        }
        
        $data['progression'] = $this->training_model->get_student_progress($progression_id);
        $this->load->view('training/student_progress', $data);
    }
}
```

### Phase 5: File System Setup (Priority: P2)

#### 5.1 Create Directory Structure
```bash
# Create training file directories
mkdir -p uploads/training/programs
mkdir -p uploads/training/students  
mkdir -p uploads/training/templates

# Set appropriate permissions
chmod 755 uploads/training
chmod 755 uploads/training/programs
chmod 755 uploads/training/students
chmod 755 uploads/training/templates
```

#### 5.2 Create Template Files
```markdown
<!-- uploads/training/templates/progress_template.md -->
# Student Progress: {STUDENT_NAME}

## Training Program: {PROGRAM_NAME}
**Start Date**: {START_DATE}  
**Primary Instructor**: {INSTRUCTOR_NAME}

## Current Status
- **Training Level**: {CURRENT_CATEGORY}
- **Flight Hours**: {TOTAL_HOURS}
- **Last Session**: {LAST_SESSION_DATE}

## Progress Narrative
{Instructor notes about student's development, challenges, achievements}

## Upcoming Objectives
- {Next competencies to work on}
- {Areas requiring focus}

## Instructor Comments
{Regular updates from training sessions}
```

### Phase 6: Basic Views (Priority: P1)

#### 6.1 Create Training Dashboard View
```php
<!-- application/views/training/index.php -->
<?php $this->load->view('include/header'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2>Training Dashboard</h2>
            
            <?php echo $this->gvvmetadata->table(
                'training_dashboard', 
                $progressions, 
                ['student_name', 'program_name', 'current_category', 'last_session']
            ); ?>
        </div>
    </div>
</div>

<?php $this->load->view('include/footer'); ?>
```

### Phase 7: Testing Setup (MANDATORY)

#### 7.1 Create PHPUnit Tests
```php
// application/tests/unit/Training_model_test.php
class Training_model_test extends PHPUnit\Framework\TestCase {
    
    protected function setUp(): void {
        // Set up test environment
        $this->CI = &get_instance();
        $this->CI->load->model('training_model');
    }
    
    public function test_instructor_has_access() {
        // Test instructor authorization
    }
    
    public function test_get_student_progress() {
        // Test progress retrieval
    }
}
```

#### 7.2 Run Initial Tests
```bash
# Run training-specific tests
./run-all-tests.sh

# Check for any regressions in existing functionality
./run-all-tests.sh --coverage
```

## Development Workflow

### Daily Development Cycle
1. **Environment setup**: `source setenv.sh`
2. **Pull latest changes**: `git pull origin main`
3. **Run tests**: `./run-all-tests.sh` (check baseline)
4. **Implement feature**: Follow P1 → P2 → P3 → P4 priority order
5. **Test changes**: `./run-all-tests.sh` (check for regressions)
6. **Commit changes**: Include test coverage

### Feature Implementation Order
1. **P1 - Core Progression Tracking**: Database, models, basic CRUD
2. **P2 - Competency System**: Skill definitions and assessments  
3. **P3 - Reporting System**: Progress reports and analytics
4. **P4 - Multi-Instructor Features**: Collaboration and shared access

### Integration Testing
```bash
# Test database connectivity
php test_real_db_connection.php

# Test file upload functionality  
php test_attachment_section_dirs.php

# Validate PHP syntax
find application/controllers/training* -name "*.php" -exec php -l {} \;
```

## Markdown File Management

### File Creation Pattern
```php
// Helper for creating student progress files
public function create_progress_file($progression_id) {
    $progression = $this->training_model->get($progression_id);
    $template = file_get_contents(FCPATH . 'uploads/training/templates/progress_template.md');
    
    // Replace template variables
    $content = str_replace(
        ['{STUDENT_NAME}', '{PROGRAM_NAME}', '{START_DATE}'],
        [$progression->student_name, $progression->program_name, $progression->start_date],
        $template
    );
    
    $file_path = "uploads/training/students/{$progression->student_member_id}/progress.md";
    
    // Ensure directory exists
    $dir = dirname(FCPATH . $file_path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    file_put_contents(FCPATH . $file_path, $content);
    
    // Update database with file path
    $this->training_model->update($progression_id, ['progress_file' => $file_path]);
}
```

### File Access Security
```php
// Secure file access through controller
public function progress_file($progression_id) {
    // Verify instructor access
    if (!$this->training_model->instructor_has_access(
        $this->session->userdata('membre_id'), $progression_id)) {
        show_error('Unauthorized', 403);
    }
    
    // Serve file through application (not direct web access)
    $progression = $this->training_model->get($progression_id);
    if ($progression->progress_file && file_exists(FCPATH . $progression->progress_file)) {
        $content = file_get_contents(FCPATH . $progression->progress_file);
        
        header('Content-Type: text/markdown');
        header('Content-Disposition: attachment; filename="progress.md"');
        echo $content;
    } else {
        show_404();
    }
}
```

## Common Pitfalls & Solutions

### Environment Issues
- **Problem**: PHP version mismatch errors
- **Solution**: Always `source setenv.sh` before any PHP commands

### Database Issues  
- **Problem**: Migration fails
- **Solution**: Check existing table structure, verify migration version in config

### File Permission Issues
- **Problem**: Cannot create/write training files
- **Solution**: `chmod +w uploads/training` and verify web server permissions

### Metadata Issues
- **Problem**: Form fields not rendering correctly
- **Solution**: Check metadata definitions in `Gvvmetadata.php`, look for missing field definitions in logs

## Next Steps

After completing basic implementation:
1. **User Acceptance Testing**: Deploy to staging environment
2. **Instructor Training**: Create user documentation and training materials  
3. **Performance Optimization**: Add database indexes and caching
4. **Integration Testing**: Verify compatibility with existing GVV features
5. **Documentation**: Update user guides and API documentation

## Success Validation

### Functional Tests
- Create student progression record: < 3 minutes
- Generate progress report: < 30 seconds  
- Update session notes: Markdown files created/updated
- Multi-instructor access: Authorization working correctly

### Code Quality
- PHPUnit test coverage: > 70%
- No regressions in existing functionality
- Metadata-driven field rendering working
- All PHP files pass syntax validation

### Integration Success
- Student data links to existing member records
- Aircraft assignments work with existing aircraft table
- Authorization integrates with existing role system
- File uploads follow established GVV patterns