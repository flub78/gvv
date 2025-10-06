# Plan: Attachments Directory Reorganization by Section

**Created:** 2025-10-06
**Status:** Planning
**Complexity:** Medium

## Executive Summary

Reorganize the `uploads/attachments/` directory structure to organize files by year AND section (club), making it easier to manage attachments for different clubs (ULM, Avion, CG, Planeur). This plan covers code changes, database migration, and file system reorganization.

---

## Current State

### Current Directory Structure
```
uploads/
├── attachments/
│   └── 2025/
│       ├── file1.pdf
│       ├── file2.pdf
│       └── ...
└── restore/
```

### Current Database Schema
```sql
CREATE TABLE attachments (
    id                 BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    referenced_table   VARCHAR(128),
    referenced_id      VARCHAR(128),
    user_id            VARCHAR(25),
    filename           VARCHAR(128),
    description        VARCHAR(124),
    file               VARCHAR(255),    -- Path: ./uploads/attachments/YYYY/filename
    club               TINYINT(1)       -- Section ID (references sections.id)
);
```

### Sections Table
```sql
CREATE TABLE sections (
    id          INT PRIMARY KEY,
    nom         VARCHAR(...),  -- Values: 'ULM', 'Avion', 'CG', etc.
    description TEXT
);
```

### Current Code Behavior
- **Upload location:** `./uploads/attachments/{YEAR}/` (see `attachments.php:102`)
- **File path stored:** `./uploads/attachments/{YEAR}/{random}_{filename}`
- **Year filtering:** Extracts year from file path using SQL `LIKE` (see `attachments_model.php:38`)
- **Section filtering:** Only attachements associated to accounting lines related to the active section are visible.
- **Section association:** Via `club` field (foreign key to `sections.id`)

---

## Target State

### Target Directory Structure
```
uploads/
├── attachments/
│   ├── 2025/
│   │   ├── ULM/
│   │   │   ├── 567941_file1.pdf
│   │   │   └── ...
│   │   ├── Avion/
│   │   │   ├── 183841_file2.pdf
│   │   │   └── ...
│   │   ├── CG/
│   │   │   ├── 297186_file3.pdf
│   │   │   └── ...
│   │   ├── Planeur/
│   │   └── Unknown/  (for attachments without section)
│   ├── 2026/
│   │   ├── ULM/
│   │   ├── Avion/
│   │   ├── CG/
│   │   ├── Planeur/
│   │   └── Unknown/
│   └── ...
└── restore/
```

### Updated File Path Pattern
- **New path:** `./uploads/attachments/{YEAR}/{SECTION_NOM}/{random}_{filename}`
- **Example:** `./uploads/attachments/2025/CG/583478_facture.pdf`

---

## Implementation Plan

### Phase 1: Code Modifications

#### 1.1 Model Changes (`attachments_model.php`)

**File:** `application/models/attachments_model.php`

**Changes needed:**

1. **Add method to get section name from club ID:**
   ```php
   /**
    * Get section name (nom) from club ID
    * @param int $club_id
    * @return string Section name or 'Unknown'
    */
   public function get_section_name($club_id) {
       if (empty($club_id)) {
           return 'Unknown';
       }
       $this->db->select('nom');
       $this->db->from('sections');
       $this->db->where('id', $club_id);
       $query = $this->db->get();
       if ($query && $query->num_rows() > 0) {
           $row = $query->row_array();
           return $row['nom'];
       }
       return 'Unknown';
   }
   ```

2. **Update `select_page()` method:**
   - Already joins with sections table ✓
   - No changes needed

3. **Update `get_available_years()` method:**
   - Currently extracts year from path: `LEFT(SUBSTRING_INDEX(file, 'attachments/', -1), 4)`
   - **Change:** Update to handle new path structure `attachments/YYYY/SECTION/file`
   ```php
   // From: LEFT(SUBSTRING_INDEX(file, 'attachments/', -1), 4)
   // To:   SUBSTRING(SUBSTRING_INDEX(file, 'attachments/', -1), 1, 4)
   ```

**Location:** Lines 67-89

---

#### 1.2 Controller Changes (`attachments.php`)

**File:** `application/controllers/attachments.php`

**Changes needed:**

1. **Update `formValidation()` method - upload path generation:**

   **Current code (lines 100-105):**
   ```php
   $year = date('Y');
   $dirname = './uploads/attachments/' . $year . '/';
   if (!file_exists($dirname)) {
       mkdir($dirname, 0777, true);
   }
   ```

   **New code:**
   ```php
   $year = date('Y');

   // Get section name from club field
   $club_id = $this->input->post('club');
   $section_name = $this->gvv_model->get_section_name($club_id);

   $dirname = './uploads/attachments/' . $year . '/' . $section_name . '/';
   if (!file_exists($dirname)) {
       mkdir($dirname, 0777, true);
   }
   ```

2. **Update file path stored in POST:**

   **Current code (line 132):**
   ```php
   $_POST['file'] = $dirname . $storage_file;
   ```

   **New code (no change needed, but verify):**
   ```php
   // Path will now be: ./uploads/attachments/2025/CG/123456_file.pdf
   $_POST['file'] = $dirname . $storage_file;
   ```

3. **Ensure `club` field is required:**
   - Add validation to ensure `club` field is set before upload
   - If missing, default to 'Unknown' section

**Location:** Lines 99-146

---

#### 1.3 Form View Changes (`bs_formView.php`)

**File:** `application/views/attachments/bs_formView.php`

**Changes needed:**

1. **Add club/section field to form:**

   **Current hidden fields (lines 46-48):**
   ```php
   <input type="hidden" name="referenced_table" value="<?= $referenced_table ?>" />
   <input type="hidden" name="referenced_id" value="<?= $referenced_id ?>" />
   <input type="hidden" name="user_id" value="<?= $user_id ?>" />
   ```

   **Add club field (if not already in form metadata):**
   ```php
   <?= $this->gvvmetadata->form('attachments', array(
       'club' => $club,           // Add this line
       'description' => $description,
       'file' => $file
   )); ?>
   ```

   OR if club should be passed from the referring page (preferd implementation):
   ```php
   <input type="hidden" name="club" value="<?= $club ?>" />
   ```

**Location:** Lines 45-53

---

#### 1.4 Metadata Changes (`Gvvmetadata.php`)

**File:** `application/libraries/Gvvmetadata.php`

**Changes needed:**

1. **Active section is already managed on all pages **

So there is no need for another way to determine the active section.

```
<select name="section" class="" onchange="updateSection(this.value)">
    <option value="2">ULM</option>
    <option value="1">Planeur</option>
    <option value="4" selected="selected">Général</option>
    <option value="3">Avion</option>
    <option value="5">Toutes</option>
</select>
   ```


---

### Phase 2: Database Migration

#### 2.1 Create Migration Script

**File:** `application/migrations/0XX_reorganize_attachments_by_section.php`

**Purpose:**
1. Create backup of current file paths
2. Update file paths to new structure
3. Verify integrity

**Migration code:**

```php
<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Migration_Reorganize_Attachments_By_Section extends CI_Migration {

    protected $migration_number;

    function __construct() {
        parent::__construct();
        $this->migration_number = XX; // Update to next available number
    }

    /**
     * Apply the migration
     */
    public function up() {
        $errors = 0;

        // Step 1: Add a backup column for original file paths
        $sql = "ALTER TABLE `attachments` ADD COLUMN `file_backup` VARCHAR(255) DEFAULT NULL";
        gvv_info("Migration sql: " . $sql);
        if (!$this->db->query($sql)) {
            gvv_error("Migration error: " . $this->db->error()['message']);
            $errors++;
        }

        // Step 2: Backup all current file paths
        $sql = "UPDATE `attachments` SET `file_backup` = `file`";
        gvv_info("Migration sql: " . $sql);
        if (!$this->db->query($sql)) {
            gvv_error("Migration error: " . $this->db->error()['message']);
            $errors++;
        }

        // Step 3: Update file paths to include section subdirectory
        // This will be handled by the PHP file reorganization script
        // (See Phase 3 below)

        gvv_info("Migration database up to " . $this->migration_number . ", errors=$errors");
        return !$errors;
    }

    /**
     * Reverse the migration
     */
    public function down() {
        $errors = 0;

        // Restore original file paths
        $sql = "UPDATE `attachments` SET `file` = `file_backup`";
        gvv_info("Migration sql: " . $sql);
        if (!$this->db->query($sql)) {
            gvv_error("Migration error: " . $this->db->error()['message']);
            $errors++;
        }

        // Remove backup column
        $sql = "ALTER TABLE `attachments` DROP COLUMN `file_backup`";
        gvv_info("Migration sql: " . $sql);
        if (!$this->db->query($sql)) {
            gvv_error("Migration error: " . $this->db->error()['message']);
            $errors++;
        }

        gvv_info("Migration database down to " . ($this->migration_number - 1) . ", errors=$errors");
        return !$errors;
    }
}
```

**Actions:**
1. Create file with next migration number
2. Update `application/config/migration.php` to new version number

---

### Phase 3: File System Reorganization Script

#### 3.1 Create PHP CLI Script

**File:** `scripts/reorganize_attachments.php`

**Purpose:**
- Move existing files from `uploads/attachments/YYYY/` to `uploads/attachments/YYYY/SECTION/`
- Update database `file` column to reflect new paths
- Generate report of moved files
- Handle errors gracefully

**Script code:**

```php
<?php
/**
 * Script to reorganize attachments by section
 *
 * Usage: php scripts/reorganize_attachments.php [--dry-run] [--verbose]
 *
 * Options:
 *   --dry-run   : Preview changes without actually moving files
 *   --verbose   : Show detailed progress
 */

// Bootstrap CodeIgniter
define('BASEPATH', TRUE);
require_once __DIR__ . '/../index.php';

class AttachmentsReorganizer {

    private $CI;
    private $dry_run = false;
    private $verbose = false;
    private $stats = [
        'total' => 0,
        'moved' => 0,
        'errors' => 0,
        'skipped' => 0
    ];

    public function __construct($dry_run = false, $verbose = false) {
        $this->CI =& get_instance();
        $this->CI->load->model('attachments_model');
        $this->CI->load->model('sections_model');
        $this->dry_run = $dry_run;
        $this->verbose = $verbose;
    }

    public function run() {
        echo "=== Attachments Reorganization Script ===\n";
        echo "Mode: " . ($this->dry_run ? "DRY RUN" : "LIVE") . "\n\n";

        // Get all attachments
        $this->CI->db->select('id, file, club');
        $this->CI->db->from('attachments');
        $this->CI->db->where('file IS NOT NULL');
        $this->CI->db->where("file != ''");
        $query = $this->CI->db->get();

        if (!$query) {
            echo "Error: Could not fetch attachments\n";
            return false;
        }

        $attachments = $query->result_array();
        $this->stats['total'] = count($attachments);

        echo "Found {$this->stats['total']} attachments to process\n\n";

        // Get sections mapping
        $sections = $this->get_sections_map();

        // Process each attachment
        foreach ($attachments as $attachment) {
            $this->process_attachment($attachment, $sections);
        }

        // Print summary
        $this->print_summary();

        return $this->stats['errors'] == 0;
    }

    private function get_sections_map() {
        $this->CI->db->select('id, nom');
        $this->CI->db->from('sections');
        $query = $this->CI->db->get();

        $map = [];
        if ($query) {
            foreach ($query->result_array() as $row) {
                $map[$row['id']] = $row['nom'];
            }
        }
        return $map;
    }

    private function process_attachment($attachment, $sections) {
        $id = $attachment['id'];
        $old_path = $attachment['file'];
        $club_id = $attachment['club'];

        // Skip if already in new format (contains section subdirectory)
        if (preg_match('#/attachments/\d{4}/[^/]+/[^/]+$#', $old_path)) {
            if ($this->verbose) {
                echo "SKIP: ID $id already in new format\n";
            }
            $this->stats['skipped']++;
            return;
        }

        // Determine section name
        $section_name = isset($sections[$club_id]) ? $sections[$club_id] : 'Unknown';

        // Extract year and filename from old path
        if (!preg_match('#/attachments/(\d{4})/([^/]+)$#', $old_path, $matches)) {
            echo "ERROR: ID $id - Invalid path format: $old_path\n";
            $this->stats['errors']++;
            return;
        }

        $year = $matches[1];
        $filename = $matches[2];

        // Build new path
        $new_path = "./uploads/attachments/{$year}/{$section_name}/{$filename}";

        // Check if source file exists
        if (!file_exists($old_path)) {
            echo "ERROR: ID $id - Source file not found: $old_path\n";
            $this->stats['errors']++;
            return;
        }

        if ($this->verbose) {
            echo "Processing ID $id: $old_path -> $new_path\n";
        }

        if (!$this->dry_run) {
            // Create target directory
            $target_dir = "./uploads/attachments/{$year}/{$section_name}";
            if (!is_dir($target_dir)) {
                if (!mkdir($target_dir, 0777, true)) {
                    echo "ERROR: ID $id - Failed to create directory: $target_dir\n";
                    $this->stats['errors']++;
                    return;
                }
            }

            // Move file
            if (!rename($old_path, $new_path)) {
                echo "ERROR: ID $id - Failed to move file\n";
                $this->stats['errors']++;
                return;
            }

            // Update database
            $this->CI->db->where('id', $id);
            if (!$this->CI->db->update('attachments', ['file' => $new_path])) {
                echo "ERROR: ID $id - Failed to update database\n";
                // Try to move file back
                rename($new_path, $old_path);
                $this->stats['errors']++;
                return;
            }
        }

        $this->stats['moved']++;
    }

    private function print_summary() {
        echo "\n=== Summary ===\n";
        echo "Total attachments: {$this->stats['total']}\n";
        echo "Successfully moved: {$this->stats['moved']}\n";
        echo "Skipped (already migrated): {$this->stats['skipped']}\n";
        echo "Errors: {$this->stats['errors']}\n";

        if ($this->dry_run) {
            echo "\nThis was a DRY RUN - no changes were made\n";
        }
    }
}

// Parse command line arguments
$dry_run = in_array('--dry-run', $argv);
$verbose = in_array('--verbose', $argv);

// Run reorganization
$reorganizer = new AttachmentsReorganizer($dry_run, $verbose);
$success = $reorganizer->run();

exit($success ? 0 : 1);
```

**Actions:**
1. Create `scripts/` directory if it doesn't exist
2. Test with `--dry-run` first
3. Run actual migration
4. Verify results

---

### Phase 4: Testing & Validation

#### 4.1 Pre-Migration Testing

**Test Cases:**

1. **Upload new attachment with section**
   - Verify file goes to `uploads/attachments/YYYY/SECTION/`
   - Verify database stores correct path

2. **View attachments list**
   - Verify table displays correctly with section column
   - Verify year filter works
   - Verify section filter works

3. **Download/view attachment**
   - Verify file can be accessed
   - Verify correct file is served

4. **Delete attachment**
   - Verify file is deleted from filesystem
   - Verify database record is deleted

#### 4.2 Migration Testing

**Steps:**

1. **Backup database and files:**
   ```bash
   # Database backup
   mysqldump -u root gvv > backup_attachments_pre_migration.sql

   # File backup
   cp -r uploads/attachments uploads/attachments_backup
   ```

2. **Run dry-run:**
   ```bash
   source setenv.sh
   php scripts/reorganize_attachments.php --dry-run --verbose
   ```

3. **Run actual migration:**
   ```bash
   php scripts/reorganize_attachments.php --verbose
   ```

4. **Verify results:**
   - Check directory structure
   - Spot-check file locations
   - Query database to verify path updates
   - Test viewing/downloading attachments in UI

#### 4.3 Post-Migration Testing

**Test Cases:**

1. **View existing attachments**
   - Verify all old attachments display correctly
   - Verify files can be downloaded

2. **Upload new attachments**
   - Verify new files go to correct location

3. **Year filter**
   - Verify year selector works
   - Verify filtering displays correct attachments

4. **Section filter** (if implemented)
   - Verify filtering by section works

---

## Implementation Progress Tracker

Use this table to track implementation progress. Mark tasks as:
- ⬜ Not started
- 🔄 In progress
- ✅ Completed
- ❌ Blocked/Issue

| # | Phase | Task | Status | Notes | Date |
|---|-------|------|--------|-------|------|
| **PRE-MIGRATION** |||||
| 1 | Setup | Review and understand current code | ⬜ | | |
| 2 | Setup | Backup database (`mysqldump -u root gvv > backup_pre_migration.sql`) | ⬜ | | |
| 3 | Setup | Backup uploads directory (`cp -r uploads/attachments uploads/attachments_backup`) | ⬜ | | |
| 4 | Setup | Document current attachment count per section | ⬜ | | |
| **CODE CHANGES - MODEL** |||||
| 6 | Model | Add `get_section_name()` method to `attachments_model.php` | ⬜ | Lines ~90+ | |
| 7 | Model | Update `get_available_years()` to handle new path format | ⬜ | Lines 67-89 | |
| 8 | Model | Test model changes in isolation | ⬜ | | |
| **CODE CHANGES - CONTROLLER** |||||
| 9 | Controller | Update `formValidation()` - get section name | ⬜ | Line ~101 | |
| 10 | Controller | Update `formValidation()` - build new dirname path | ⬜ | Line ~102 | |
| 11 | Controller | Add club field validation (ensure not empty) | ⬜ | Lines 99-106 | |
| 12 | Controller | Test controller upload with new structure | ⬜ | | |
| **CODE CHANGES - VIEWS** |||||
| 13 | Views | Ensure club field is in form (`bs_formView.php`) | ⬜ | Lines 45-53 | |
| 14 | Views | Verify club field is visible/required in form | ⬜ | | |
| 15 | Views | Test form submission with club selection | ⬜ | | |
| **MIGRATION SCRIPTS** |||||
| 19 | Migration | Create `0XX_reorganize_attachments_by_section.php` | ⬜ | | |
| 20 | Migration | Update migration number in script | ⬜ | | |
| 21 | Migration | Update `config/migration.php` to new version | ⬜ | | |
| 22 | Migration | Test migration up/down in dev environment | ⬜ | | |
| **FILE REORGANIZATION SCRIPT** |||||
| 23 | Script | Create `scripts/` directory if needed | ⬜ | | |
| 24 | Script | Create `scripts/reorganize_attachments.php` | ⬜ | | |
| 25 | Script | Add dry-run functionality | ⬜ | | |
| 26 | Script | Add verbose logging | ⬜ | | |
| 27 | Script | Add error handling and rollback logic | ⬜ | | |
| **TESTING - DRY RUN** |||||
| 28 | Test | Run reorganization script with `--dry-run --verbose` | ⬜ | | |
| 29 | Test | Review dry-run output for errors | ⬜ | | |
| 30 | Test | Verify dry-run counts match database | ⬜ | | |
| 31 | Test | Fix any issues found in dry-run | ⬜ | | |
| **TESTING - NEW UPLOAD** |||||
| 32 | Test | Test uploading attachment with ULM section | ⬜ | | |
| 33 | Test | Verify file lands in `uploads/attachments/YYYY/ULM/` | ⬜ | | |
| 34 | Test | Test uploading attachment with Avion section | ⬜ | | |
| 35 | Test | Test uploading attachment with CG section | ⬜ | | |
| 36 | Test | Test uploading without section (should use Unknown) | ⬜ | | |
| 37 | Test | Verify database stores correct path format | ⬜ | | |
| **MIGRATION EXECUTION** |||||
| 38 | Migrate | Run database migration (`php index.php migrate`) | ⬜ | | |
| 39 | Migrate | Verify `file_backup` column created | ⬜ | | |
| 40 | Migrate | Run file reorganization script (LIVE) | ⬜ | `php scripts/reorganize_attachments.php --verbose` | |
| 41 | Migrate | Review migration output for errors | ⬜ | | |
| 42 | Migrate | Verify file count (before vs after) | ⬜ | | |
| 43 | Migrate | Spot-check 10 random files moved correctly | ⬜ | | |
| **POST-MIGRATION TESTING** |||||
| 44 | Test | View attachments list page | ⬜ | | |
| 45 | Test | Test year filter functionality | ⬜ | | |
| 46 | Test | Test viewing old attachment (pre-migration) | ⬜ | | |
| 47 | Test | Test downloading old attachment | ⬜ | | |
| 48 | Test | Test deleting old attachment | ⬜ | | |
| 49 | Test | Test uploading new attachment post-migration | ⬜ | | |
| 50 | Test | Test editing attachment | ⬜ | | |
| 51 | Test | Check application logs for errors | ⬜ | `application/logs/` | |
| 52 | Test | Run PHPUnit tests for attachments | ⬜ | `./run-tests.sh application/tests/*/attachments*` | |
| **POST-MIGRATION CLEANUP** |||||
| 53 | Cleanup | Verify no files left in old `uploads/attachments/YYYY/` | ⬜ | | |
| 54 | Cleanup | Remove empty year directories | ⬜ | Optional | |
| 55 | Cleanup | Decide: keep or remove `file_backup` column | ⬜ | Recommend keeping | |
| 56 | Cleanup | Update documentation | ⬜ | | |
| 58 | Cleanup | Create pull request / merge | ⬜ | | |
| 59 | Cleanup | Tag release | ⬜ | | |
| **ROLLBACK (IF NEEDED)** |||||
| R1 | Rollback | Restore database: `mysql -u root gvv < backup_pre_migration.sql` | ⬜ | Only if needed | |
| R2 | Rollback | Restore files: `rm -rf uploads/attachments && mv uploads/attachments_backup uploads/attachments` | ⬜ | Only if needed | |
| R3 | Rollback | Revert code changes: `git checkout main && git branch -D feature/attachments-section-dirs` | ⬜ | Only if needed | |

---

## Quick Migration Checklist (Simplified)

For a quick overview, check these milestones:

- [ ] **PREPARATION:** Backups created (tasks 2-4)
- [ ] **CODE COMPLETE:** All code changes done and tested (tasks 6-18)
- [ ] **SCRIPTS READY:** Migration and reorganization scripts created (tasks 19-27)
- [ ] **DRY-RUN PASSED:** Test run successful with no errors (tasks 28-31)
- [ ] **MIGRATION COMPLETE:** Files moved and database updated (tasks 38-43)
- [ ] **TESTING PASSED:** All functionality verified (tasks 44-52)
- [ ] **DEPLOYED:** Changes committed and deployed (tasks 57-59)

---

## Risk Assessment

### Low Risk
- **Code changes are isolated:** Only affects attachments module
- **Backward compatible:** Migration preserves original paths in backup column
- **Reversible:** Clear rollback procedure available

### Medium Risk
- **File system operations:** Moving files could fail mid-operation
- **Mitigation:** Transaction-like approach (move file, then update DB, rollback on error)

### High Risk
- **None identified**

---

## Timeline Estimate

| Phase | Duration | Notes |
|-------|----------|-------|
| Code changes | 2-3 hours | Controller, model, views, metadata |
| Migration script creation | 2-3 hours | Including testing |
| Testing (dry-run) | 1 hour | Verify logic |
| Migration execution | 30 min - 1 hour | Depends on file count |
| Post-migration testing | 1-2 hours | Comprehensive testing |
| **Total** | **7-10 hours** | |

---

## Dependencies

1. **Database access:** Write access to `attachments` and `sections` tables
2. **File system access:** Read/write permissions on `uploads/attachments/`
3. **PHP 7.4:** Environment setup via `source setenv.sh`
4. **Sections data:** Must have valid sections with `nom` values

---

## Success Criteria

1. ✅ All existing attachments moved to section-based subdirectories
2. ✅ Database paths updated to reflect new structure
3. ✅ No files lost during migration (count verification)
4. ✅ New uploads go to correct section subdirectory
5. ✅ All existing attachments viewable/downloadable via UI
6. ✅ Year filter continues to work
7. ✅ No errors in application logs
8. ✅ Zero test failures

---

## Future Enhancements (Out of Scope)

1. **Disk usage reports:** Show storage per section
2. **Attachment metadata:** Store file hash for integrity verification
3. **Orphaned file cleanup:** Script to find files not in database
4. **Section rename handling:** Automatic file moves if section name changes

---

## References

- Current code: `application/controllers/attachments.php`
- Current model: `application/models/attachments_model.php`
- Migration example: `application/migrations/023_attachments.php`
- Sections model: `application/models/sections_model.php`
- Workflow documentation: `doc/development/workflow.md`

---

## Notes

- **Club field:** Currently exists in DB (`club TINYINT(1)`) but may not be properly enforced in forms
- **Unknown section:** Files without a valid `club` ID will go to `Unknown/` subdirectory
- **Permissions:** Ensure `uploads/attachments/YYYY/SECTION/` directories have correct permissions (0777 during development, 0755 in production)
- **File naming:** Maintains existing random prefix + original filename pattern

---

**End of Plan**
