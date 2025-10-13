# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**GVV (Gestion Vol à voile)** is a web application for managing gliding clubs, developed in PHP since 2011. It handles member management, aircraft fleet management, flight logging, billing, basic accounting, flight calendars, and email communications. Currently used by 5-6 gliding associations.

- **Languages**: PHP 7.4, MySQL 5.x, HTML, CSS, JavaScript
- **Framework**: CodeIgniter 2.x (legacy version), Bootstrap 5 for UI
- **Database**: MySQL with migrations managed via CodeIgniter
- **Size**: ~50 controllers, extensive model layer, multi-language support (French, English, Dutch)
- **Status**: Deployed for 12 years, stable, in maintenance mode

## Critical: Environment Setup

**ALWAYS source the environment setup before running PHP commands:**

```bash
source setenv.sh
```

This sets PHP 7.4 as the active version. The project **requires PHP 7.4 specifically** - newer versions are not compatible.

## Common Development Commands

### Testing

```bash
# Fast tests (no coverage) - ~100-150ms
./run-tests.sh

# Tests with coverage - ~20 seconds
./run-coverage.sh

# All test suites with coverage - ~60 seconds
./run-all-tests.sh --coverage

# All test suites without coverage - ~2 seconds
./run-all-tests.sh

# Run specific test file
./run-tests.sh application/tests/unit/helpers/ValidationHelperTest.php

# Run specific test method
./run-tests.sh --filter testEmailValidation

# View coverage report
firefox build/coverage/index.html
```

### PHP Validation

```bash
# Validate individual file
php -l application/controllers/welcome.php

# Validate entire directory
find application/controllers -name "*.php" -exec php -l {} \;
```

### Test Organization

- **Unit Tests**: `application/tests/unit/` - Helpers, models, libraries, i18n
- **Integration Tests**: `application/tests/integration/` - Real database operations, metadata
- **Enhanced Tests**: `application/tests/enhanced/` - CI framework helpers/libraries
- **Controller Tests**: `application/tests/controllers/` - JSON/HTML/CSV output parsing
- **MySQL Tests**: `application/tests/mysql/` - Real database CRUD operations

Current status: ~128 tests across all suites

## High-Level Architecture

### Data-Driven Metadata System

GVV uses a **metadata-driven architecture** centered on `application/libraries/Gvvmetadata.php`. This class defines:

- Database field types, subtypes, and display properties
- Form element rendering (selectors, enumerations, currency, dates)
- Table view generation and formatting
- Input validation rules

**Critical**: When adding new features or modifying existing ones:
1. Define metadata in `Gvvmetadata.php` for proper field rendering
2. Use `$this->gvvmetadata->table()` for table views
3. Use metadata-aware helpers (`array_field`, `input_field`) for forms
4. Follow the established pattern: check logs for missing metadata definitions

### CodeIgniter 2.x MVC Pattern

```
/
├── index.php                    # Main entry point
├── setenv.sh                    # MUST run before PHP commands
├── application/
│   ├── controllers/             # Request handling (~50 controllers)
│   ├── models/                  # Database operations (data + metadata)
│   ├── views/                   # HTML templates
│   ├── libraries/               # Business logic (Gvvmetadata.php is central)
│   ├── helpers/                 # Utility functions
│   ├── config/                  # Configuration files
│   │   ├── config.php           # Base URL, Google settings
│   │   ├── database.php         # Copy from database.example.php
│   │   ├── migration.php        # Update version after creating migration
│   │   └── routes.php           # URL routing
│   ├── migrations/              # Database migrations (numbered files)
│   ├── tests/                   # PHPUnit tests
│   └── third_party/             # External libraries (TCPDF, phpqrcode, etc.)
├── system/                      # CodeIgniter core (DO NOT MODIFY)
├── assets/                      # CSS, JS, images
├── themes/                      # UI themes
└── uploads/                     # User-uploaded files (needs +wx permissions)
```

### Development Workflow for New Features

Following `doc/development/workflow.md`:

0. **Design**: 
   - Create markdown document in `doc/design_notes` to explain the new feature and its architecture.  
   - Create plantuml diagram to explain complex design

1. **Database Migration**:
   - Define table in phpMyAdmin and export schema
   - Create migration in `application/migrations/` (numbered, e.g., `042_feature_name.php`)
   - Update `application/config/migration.php` to latest version number

2. **Model Creation**:
   - Create in `application/models/`
   - Ensure `select_page()` returns primary key even if not displayed
   - Implement joins in `select_page()` for related data

3. **Metadata Definition**:
   - Add field definitions to `application/libraries/Gvvmetadata.php`
   - Define types, subtypes, selectors, enumerations
   - Check logs for missing metadata: `DEBUG - GVV: input_field(...) type=..., subtype=...`

4. **Controller Creation**:
   - Create in `application/controllers/`
   - Extend `CI_Controller` or `Gvv_Controller`
   - Use metadata for form/table generation

5. **Language Files**:
   - Add translations to `application/language/french/`, `english/`, `dutch/`

6. **Views**:
   - Use Bootstrap 5 classes
   - Leverage `$this->gvvmetadata->table()` for table views
   - Use `array_field()` and `input_field()` for proper field rendering

7. **Testing**:
   - Create PHPUnit tests in appropriate `application/tests/` directory
   - Aim for >70% code coverage
   - Update test configs if needed

### Migration and Legacy Code

- **Legacy migration ongoing**: Converting from CodeIgniter Unit_test to PHPUnit (see `PHPUNIT_MIGRATION_SUMMARY.md`)
- **32 controllers** still have old CI Unit_test methods (to be migrated)
- Avoid major architectural changes - this is a maintenance-mode project
- **Code reuse first**: Always check if similar functionality exists before writing new code

## Key Development Guidelines

1. **Environment**: ALWAYS `source setenv.sh` before running PHP commands
2. **PHP 7.4 Only**: Use `/usr/bin/php7.4` explicitly when needed
3. **Metadata-Driven**: Define field metadata in `Gvvmetadata.php` for proper rendering
4. **Code Reuse**: Check existing code before creating new implementations
5. **Bootstrap UI**: Use Bootstrap 5 classes for all UI components
6. **Testing**: Write PHPUnit tests, aim for >70% coverage
7. **Migrations**: Always update `config/migration.php` after creating migration
8. **Multi-language**: Support French, English, Dutch in language files
9. **Permissions**: Web-writable directories need `chmod +wx` (logs, uploads, assets/images)
10. **No Composer**: Project uses manual dependency management (predates Composer)
11. **Dababase access**: Use the credential from configuration/database.php to analyze the database schema or data.
12. **Diagrams**: Use plantuml to generate class diagrams and database schemas
13. **Mockups**: generate mockups in ASCII art, generate prototypes in self contained HTML files

## Important Integration Patterns

### Form Generation with Metadata
```php
// In controller
$data = $this->model->get_record($id);
// In view - metadata handles rendering
echo $this->gvvmetadata->input_field('table_name', 'field_name', $value, $mode);
```

### Table Views with Metadata
```php
// Controller prepares data
$rows = $this->model->select_page($filters);
// View uses metadata for table generation
echo $this->gvvmetadata->table('view_name', $attrs, $filters);
```

### Selectors and Enumerations
Define in Gvvmetadata.php:
```php
$this->field['table']['field']['Subtype'] = 'selector';
$this->field['table']['field']['Selector'] = 'selector_function_name';
```

## Testing Philosophy

- **Fast iteration**: Use `./run-tests.sh` (no coverage) during development
- **Pre-commit**: Use `./run-coverage.sh` for coverage before commits
- **Full suite**: Use `./run-all-tests.sh --coverage` for comprehensive validation
- **Unit tests**: No database dependencies, test helpers/libraries in isolation
- **Integration tests**: Real database operations, test component interactions
- **Coverage target**: Aim for 75% overall (currently establishing baseline)

## Common Pitfalls to Avoid

1. **Don't skip `source setenv.sh`** - PHP version mismatch causes failures
2. **Don't modify `system/`** - It's CodeIgniter core
3. **Don't create new patterns** - Follow established CodeIgniter 2.x conventions
4. **Don't skip metadata** - Tables/forms won't render correctly without it
5. **Don't forget migration version** - Update `config/migration.php` after creating migration
6. **Don't duplicate code** - Extensive codebase likely has what you need
7. **Don't use Composer** - Project uses manual dependency management

## Multi-Language Support

All user-facing strings must be defined in language files:
- `application/language/french/` (primary)
- `application/language/english/`
- `application/language/dutch/`

Use: `$this->lang->line('key_name')` in controllers/views

## File Permissions

Ensure these directories are web-writable:
```bash
chmod +wx application/logs
chmod +wx uploads
chmod +wx assets/images
```

## Third-Party Libraries

Located in `application/third_party/`:
- **TCPDF**: PDF generation
- **phpqrcode**: QR code generation
- **Google API**: Google integration
- **CIUnit**: Legacy testing framework (being phased out)

Handle with care - these are external dependencies.

## Prompt Guidelines

When working with AI assistants on GVV (see `doc/development/prompt_guideline.md`):

1. **Reference context**: "Context: GVV project (see copilot-instructions.md or CLAUDE.md)"
2. **Be specific**: Focus on exact requirements, not general architecture
3. **Show integration**: Explain how changes connect to existing GVV components
4. **Work incrementally**: Break complex changes into reviewable steps
5. **Ask when unclear**: Request clarification before proceeding
6. **Reuse first**: Check existing implementations before writing new code

## Documentation

Key documentation files:
- `README.md` - Project overview, installation, updates
- `TESTING.md` - Quick testing reference
- `.github/copilot-instructions.md` - Detailed technical context (mirrors this file)
- `doc/development/workflow.md` - Feature development workflow
- `doc/development/phpunit.md` - Testing details
- `PHPUNIT_MIGRATION_SUMMARY.md` - Migration status from CI Unit_test to PHPUnit

# Using Gemini CLI for Large Codebase Analysis

When analyzing large codebases or multiple files that might exceed context limits, use the Gemini CLI with its massive context window. Use `gemini -p` to leverage Google Gemini's large context capacity.

## File and Directory Inclusion Syntax

Use the `@` syntax to include files and directories in your Gemini prompts. Paths should be relative to the LOCATION where you run the gemini command:

### Examples:

**Single file analysis:**
```bash
gemini -p "@src/main.py Explain the purpose and structure of this file"
```

**Multiple files:**
```bash
gemini -p "@package.json @src/index.js Analyze the dependencies used in the code"
```

**Entire directory:**
```bash
gemini -p "@src/ Summarize the architecture of this codebase"
```

**Multiple directories:**
```bash
gemini -p "@src/ @tests/ Analyze test coverage for the source code"
```

**Current directory and subdirectories:**
```bash
gemini -p "@./ Give me an overview of this entire project"

# Or use the --all_files flag:
gemini --all_files -p "Analyze the project structure and dependencies"
```

## Implementation Verification Examples

**Check if a feature is implemented:**
```bash
gemini -p "@src/ @lib/ Has dark mode been implemented in this codebase? Show me the relevant files and functions"
```

**Check authentication implementation:**
```bash
gemini -p "@src/ @middleware/ Is JWT authentication implemented? List all authentication-related endpoints and middleware"
```

**Search for specific patterns:**
```bash
gemini -p "@src/ Are there any React hooks that handle WebSocket connections? List them with file paths"
```

**Check error handling:**
```bash
gemini -p "@src/ @api/ Is error handling properly implemented for all API endpoints? Show examples of try-catch blocks"
```

**Check rate limiting:**
```bash
gemini -p "@backend/ @middleware/ Is rate limiting implemented for the API? Show implementation details"
```

**Check caching strategy:**
```bash
gemini -p "@src/ @lib/ @services/ Is Redis caching implemented? List all cache-related functions and their usage"
```

**Check specific security measures:**
```bash
gemini -p "@src/ @api/ Are SQL injection protections implemented? Show how user inputs are sanitized"
```

**Check test coverage for features:**
```bash
gemini -p "@src/payment/ @tests/ Is the payment processing module fully tested? List all test cases"
```

## When to Use Gemini CLI

Use `gemini -p` when:
- You're analyzing entire codebases or large directories
- You're comparing multiple large files
- You need to understand project-wide patterns or architecture
- The current context window is insufficient for the task
- You're working with files totaling more than 100KB
- You're checking if specific features, patterns, or security measures are implemented
- You're verifying the presence of certain coding patterns throughout the codebase

Use Serena MCP when:

- Debugging specific issues
- Exploring particular modules/features
- Iterative code review within Claude
- Following code flow step-by-step

<!-- BACKLOG.MD GUIDELINES START -->
# Instructions for the usage of Backlog.md CLI Tool

## Backlog.md: Comprehensive Project Management Tool via CLI

### Assistant Objective

Efficiently manage all project tasks, status, and documentation using the Backlog.md CLI, ensuring all project metadata
remains fully synchronized and up-to-date.

### Core Capabilities

- ✅ **Task Management**: Create, edit, assign, prioritize, and track tasks with full metadata
- ✅ **Search**: Fuzzy search across tasks, documents, and decisions with `backlog search`
- ✅ **Acceptance Criteria**: Granular control with add/remove/check/uncheck by index
- ✅ **Board Visualization**: Terminal-based Kanban board (`backlog board`) and web UI (`backlog browser`)
- ✅ **Git Integration**: Automatic tracking of task states across branches
- ✅ **Dependencies**: Task relationships and subtask hierarchies
- ✅ **Documentation & Decisions**: Structured docs and architectural decision records
- ✅ **Export & Reporting**: Generate markdown reports and board snapshots
- ✅ **AI-Optimized**: `--plain` flag provides clean text output for AI processing

### Why This Matters to You (AI Agent)

1. **Comprehensive system** - Full project management capabilities through CLI
2. **The CLI is the interface** - All operations go through `backlog` commands
3. **Unified interaction model** - You can use CLI for both reading (`backlog task 1 --plain`) and writing (
   `backlog task edit 1`)
4. **Metadata stays synchronized** - The CLI handles all the complex relationships

### Key Understanding

- **Tasks** live in `backlog/tasks/` as `task-<id> - <title>.md` files
- **You interact via CLI only**: `backlog task create`, `backlog task edit`, etc.
- **Use `--plain` flag** for AI-friendly output when viewing/listing
- **Never bypass the CLI** - It handles Git, metadata, file naming, and relationships

---

# ⚠️ CRITICAL: NEVER EDIT TASK FILES DIRECTLY. Edit Only via CLI

**ALL task operations MUST use the Backlog.md CLI commands**

- ✅ **DO**: Use `backlog task edit` and other CLI commands
- ✅ **DO**: Use `backlog task create` to create new tasks
- ✅ **DO**: Use `backlog task edit <id> --check-ac <index>` to mark acceptance criteria
- ❌ **DON'T**: Edit markdown files directly
- ❌ **DON'T**: Manually change checkboxes in files
- ❌ **DON'T**: Add or modify text in task files without using CLI

**Why?** Direct file editing breaks metadata synchronization, Git tracking, and task relationships.

---

## 1. Source of Truth & File Structure

### 📖 **UNDERSTANDING** (What you'll see when reading)

- Markdown task files live under **`backlog/tasks/`** (drafts under **`backlog/drafts/`**)
- Files are named: `task-<id> - <title>.md` (e.g., `task-42 - Add GraphQL resolver.md`)
- Project documentation is in **`backlog/docs/`**
- Project decisions are in **`backlog/decisions/`**

### 🔧 **ACTING** (How to change things)

- **All task operations MUST use the Backlog.md CLI tool**
- This ensures metadata is correctly updated and the project stays in sync
- **Always use `--plain` flag** when listing or viewing tasks for AI-friendly text output

---

## 2. Common Mistakes to Avoid

### ❌ **WRONG: Direct File Editing**

```markdown
# DON'T DO THIS:

1. Open backlog/tasks/task-7 - Feature.md in editor
2. Change "- [ ]" to "- [x]" manually
3. Add notes directly to the file
4. Save the file
```

### ✅ **CORRECT: Using CLI Commands**

```bash
# DO THIS INSTEAD:
backlog task edit 7 --check-ac 1  # Mark AC #1 as complete
backlog task edit 7 --notes "Implementation complete"  # Add notes
backlog task edit 7 -s "In Progress" -a @agent-k  # Multiple commands: change status and assign the task when you start working on the task
```

---

## 3. Understanding Task Format (Read-Only Reference)

⚠️ **FORMAT REFERENCE ONLY** - The following sections show what you'll SEE in task files.
**Never edit these directly! Use CLI commands to make changes.**

### Task Structure You'll See

```markdown
---
id: task-42
title: Add GraphQL resolver
status: To Do
assignee: [@sara]
labels: [backend, api]
---

## Description

Brief explanation of the task purpose.

## Acceptance Criteria

<!-- AC:BEGIN -->

- [ ] #1 First criterion
- [x] #2 Second criterion (completed)
- [ ] #3 Third criterion

<!-- AC:END -->

## Implementation Plan

1. Research approach
2. Implement solution

## Implementation Notes

Summary of what was done.
```

### How to Modify Each Section

| What You Want to Change | CLI Command to Use                                       |
|-------------------------|----------------------------------------------------------|
| Title                   | `backlog task edit 42 -t "New Title"`                    |
| Status                  | `backlog task edit 42 -s "In Progress"`                  |
| Assignee                | `backlog task edit 42 -a @sara`                          |
| Labels                  | `backlog task edit 42 -l backend,api`                    |
| Description             | `backlog task edit 42 -d "New description"`              |
| Add AC                  | `backlog task edit 42 --ac "New criterion"`              |
| Check AC #1             | `backlog task edit 42 --check-ac 1`                      |
| Uncheck AC #2           | `backlog task edit 42 --uncheck-ac 2`                    |
| Remove AC #3            | `backlog task edit 42 --remove-ac 3`                     |
| Add Plan                | `backlog task edit 42 --plan "1. Step one\n2. Step two"` |
| Add Notes (replace)     | `backlog task edit 42 --notes "What I did"`              |
| Append Notes            | `backlog task edit 42 --append-notes "Another note"` |

---

## 4. Defining Tasks

### Creating New Tasks

**Always use CLI to create tasks:**

```bash
# Example
backlog task create "Task title" -d "Description" --ac "First criterion" --ac "Second criterion"
```

### Title (one liner)

Use a clear brief title that summarizes the task.

### Description (The "why")

Provide a concise summary of the task purpose and its goal. Explains the context without implementation details.

### Acceptance Criteria (The "what")

**Understanding the Format:**

- Acceptance criteria appear as numbered checkboxes in the markdown files
- Format: `- [ ] #1 Criterion text` (unchecked) or `- [x] #1 Criterion text` (checked)

**Managing Acceptance Criteria via CLI:**

⚠️ **IMPORTANT: How AC Commands Work**

- **Adding criteria (`--ac`)** accepts multiple flags: `--ac "First" --ac "Second"` ✅
- **Checking/unchecking/removing** accept multiple flags too: `--check-ac 1 --check-ac 2` ✅
- **Mixed operations** work in a single command: `--check-ac 1 --uncheck-ac 2 --remove-ac 3` ✅

```bash
# Examples

# Add new criteria (MULTIPLE values allowed)
backlog task edit 42 --ac "User can login" --ac "Session persists"

# Check specific criteria by index (MULTIPLE values supported)
backlog task edit 42 --check-ac 1 --check-ac 2 --check-ac 3  # Check multiple ACs
# Or check them individually if you prefer:
backlog task edit 42 --check-ac 1    # Mark #1 as complete
backlog task edit 42 --check-ac 2    # Mark #2 as complete

# Mixed operations in single command
backlog task edit 42 --check-ac 1 --uncheck-ac 2 --remove-ac 3

# ❌ STILL WRONG - These formats don't work:
# backlog task edit 42 --check-ac 1,2,3  # No comma-separated values
# backlog task edit 42 --check-ac 1-3    # No ranges
# backlog task edit 42 --check 1         # Wrong flag name

# Multiple operations of same type
backlog task edit 42 --uncheck-ac 1 --uncheck-ac 2  # Uncheck multiple ACs
backlog task edit 42 --remove-ac 2 --remove-ac 4    # Remove multiple ACs (processed high-to-low)
```

**Key Principles for Good ACs:**

- **Outcome-Oriented:** Focus on the result, not the method.
- **Testable/Verifiable:** Each criterion should be objectively testable
- **Clear and Concise:** Unambiguous language
- **Complete:** Collectively cover the task scope
- **User-Focused:** Frame from end-user or system behavior perspective

Good Examples:

- "User can successfully log in with valid credentials"
- "System processes 1000 requests per second without errors"
- "CLI preserves literal newlines in description/plan/notes; `\\n` sequences are not auto‑converted"

Bad Example (Implementation Step):

- "Add a new function handleLogin() in auth.ts"
- "Define expected behavior and document supported input patterns"

### Task Breakdown Strategy

1. Identify foundational components first
2. Create tasks in dependency order (foundations before features)
3. Ensure each task delivers value independently
4. Avoid creating tasks that block each other

### Task Requirements

- Tasks must be **atomic** and **testable** or **verifiable**
- Each task should represent a single unit of work for one PR
- **Never** reference future tasks (only tasks with id < current task id)
- Ensure tasks are **independent** and don't depend on future work

---

## 5. Implementing Tasks

### 5.1. First step when implementing a task

The very first things you must do when you take over a task are:

* set the task in progress
* assign it to yourself

```bash
# Example
backlog task edit 42 -s "In Progress" -a @{myself}
```

### 5.2. Create an Implementation Plan (The "how")

Previously created tasks contain the why and the what. Once you are familiar with that part you should think about a
plan on **HOW** to tackle the task and all its acceptance criteria. This is your **Implementation Plan**.
First do a quick check to see if all the tools that you are planning to use are available in the environment you are
working in.   
When you are ready, write it down in the task so that you can refer to it later.

```bash
# Example
backlog task edit 42 --plan "1. Research codebase for references\n2Research on internet for similar cases\n3. Implement\n4. Test"
```

## 5.3. Implementation

Once you have a plan, you can start implementing the task. This is where you write code, run tests, and make sure
everything works as expected. Follow the acceptance criteria one by one and MARK THEM AS COMPLETE as soon as you
finish them.

### 5.4 Implementation Notes (PR description)

When you are done implementing a tasks you need to prepare a PR description for it.
Because you cannot create PRs directly, write the PR as a clean description in the task notes.
Append notes progressively during implementation using `--append-notes`:

```
backlog task edit 42 --append-notes "Implemented X" --append-notes "Added tests"
```

```bash
# Example
backlog task edit 42 --notes "Implemented using pattern X because Reason Y, modified files Z and W"
```

**IMPORTANT**: Do NOT include an Implementation Plan when creating a task. The plan is added only after you start the
implementation.

- Creation phase: provide Title, Description, Acceptance Criteria, and optionally labels/priority/assignee.
- When you begin work, switch to edit, set the task in progress and assign to yourself
  `backlog task edit <id> -s "In Progress" -a "..."`.
- Think about how you would solve the task and add the plan: `backlog task edit <id> --plan "..."`.
- Add Implementation Notes only after completing the work: `backlog task edit <id> --notes "..."` (replace) or append progressively using `--append-notes`.

## Phase discipline: What goes where

- Creation: Title, Description, Acceptance Criteria, labels/priority/assignee.
- Implementation: Implementation Plan (after moving to In Progress and assigning to yourself).
- Wrap-up: Implementation Notes (Like a PR description), AC and Definition of Done checks.

**IMPORTANT**: Only implement what's in the Acceptance Criteria. If you need to do more, either:

1. Update the AC first: `backlog task edit 42 --ac "New requirement"`
2. Or create a new follow up task: `backlog task create "Additional feature"`

---

## 6. Typical Workflow

```bash
# 1. Identify work
backlog task list -s "To Do" --plain

# 2. Read task details
backlog task 42 --plain

# 3. Start work: assign yourself & change status
backlog task edit 42 -s "In Progress" -a @myself

# 4. Add implementation plan
backlog task edit 42 --plan "1. Analyze\n2. Refactor\n3. Test"

# 5. Work on the task (write code, test, etc.)

# 6. Mark acceptance criteria as complete (supports multiple in one command)
backlog task edit 42 --check-ac 1 --check-ac 2 --check-ac 3  # Check all at once
# Or check them individually if preferred:
# backlog task edit 42 --check-ac 1
# backlog task edit 42 --check-ac 2
# backlog task edit 42 --check-ac 3

# 7. Add implementation notes (PR Description)
backlog task edit 42 --notes "Refactored using strategy pattern, updated tests"

# 8. Mark task as done
backlog task edit 42 -s Done
```

---

## 7. Definition of Done (DoD)

A task is **Done** only when **ALL** of the following are complete:

### ✅ Via CLI Commands:

1. **All acceptance criteria checked**: Use `backlog task edit <id> --check-ac <index>` for each
2. **Implementation notes added**: Use `backlog task edit <id> --notes "..."`
3. **Status set to Done**: Use `backlog task edit <id> -s Done`

### ✅ Via Code/Testing:

4. **Tests pass**: Run test suite and linting
5. **Documentation updated**: Update relevant docs if needed
6. **Code reviewed**: Self-review your changes
7. **No regressions**: Performance, security checks pass

⚠️ **NEVER mark a task as Done without completing ALL items above**

---

## 8. Finding Tasks and Content with Search

When users ask you to find tasks related to a topic, use the `backlog search` command with `--plain` flag:

```bash
# Search for tasks about authentication
backlog search "auth" --plain

# Search only in tasks (not docs/decisions)
backlog search "login" --type task --plain

# Search with filters
backlog search "api" --status "In Progress" --plain
backlog search "bug" --priority high --plain
```

**Key points:**
- Uses fuzzy matching - finds "authentication" when searching "auth"
- Searches task titles, descriptions, and content
- Also searches documents and decisions unless filtered with `--type task`
- Always use `--plain` flag for AI-readable output

---

## 9. Quick Reference: DO vs DON'T

### Viewing and Finding Tasks

| Task         | ✅ DO                        | ❌ DON'T                         |
|--------------|-----------------------------|---------------------------------|
| View task    | `backlog task 42 --plain`   | Open and read .md file directly |
| List tasks   | `backlog task list --plain` | Browse backlog/tasks folder     |
| Check status | `backlog task 42 --plain`   | Look at file content            |
| Find by topic| `backlog search "auth" --plain` | Manually grep through files |

### Modifying Tasks

| Task          | ✅ DO                                 | ❌ DON'T                           |
|---------------|--------------------------------------|-----------------------------------|
| Check AC      | `backlog task edit 42 --check-ac 1`  | Change `- [ ]` to `- [x]` in file |
| Add notes     | `backlog task edit 42 --notes "..."` | Type notes into .md file          |
| Change status | `backlog task edit 42 -s Done`       | Edit status in frontmatter        |
| Add AC        | `backlog task edit 42 --ac "New"`    | Add `- [ ] New` to file           |

---

## 10. Complete CLI Command Reference

### Task Creation

| Action           | Command                                                                             |
|------------------|-------------------------------------------------------------------------------------|
| Create task      | `backlog task create "Title"`                                                       |
| With description | `backlog task create "Title" -d "Description"`                                      |
| With AC          | `backlog task create "Title" --ac "Criterion 1" --ac "Criterion 2"`                 |
| With all options | `backlog task create "Title" -d "Desc" -a @sara -s "To Do" -l auth --priority high` |
| Create draft     | `backlog task create "Title" --draft`                                               |
| Create subtask   | `backlog task create "Title" -p 42`                                                 |

### Task Modification

| Action           | Command                                     |
|------------------|---------------------------------------------|
| Edit title       | `backlog task edit 42 -t "New Title"`       |
| Edit description | `backlog task edit 42 -d "New description"` |
| Change status    | `backlog task edit 42 -s "In Progress"`     |
| Assign           | `backlog task edit 42 -a @sara`             |
| Add labels       | `backlog task edit 42 -l backend,api`       |
| Set priority     | `backlog task edit 42 --priority high`      |

### Acceptance Criteria Management

| Action              | Command                                                                     |
|---------------------|-----------------------------------------------------------------------------|
| Add AC              | `backlog task edit 42 --ac "New criterion" --ac "Another"`                  |
| Remove AC #2        | `backlog task edit 42 --remove-ac 2`                                        |
| Remove multiple ACs | `backlog task edit 42 --remove-ac 2 --remove-ac 4`                          |
| Check AC #1         | `backlog task edit 42 --check-ac 1`                                         |
| Check multiple ACs  | `backlog task edit 42 --check-ac 1 --check-ac 3`                            |
| Uncheck AC #3       | `backlog task edit 42 --uncheck-ac 3`                                       |
| Mixed operations    | `backlog task edit 42 --check-ac 1 --uncheck-ac 2 --remove-ac 3 --ac "New"` |

### Task Content

| Action           | Command                                                  |
|------------------|----------------------------------------------------------|
| Add plan         | `backlog task edit 42 --plan "1. Step one\n2. Step two"` |
| Add notes        | `backlog task edit 42 --notes "Implementation details"`  |
| Add dependencies | `backlog task edit 42 --dep task-1 --dep task-2`         |

### Multi‑line Input (Description/Plan/Notes)

The CLI preserves input literally. Shells do not convert `\n` inside normal quotes. Use one of the following to insert real newlines:

- Bash/Zsh (ANSI‑C quoting):
  - Description: `backlog task edit 42 --desc $'Line1\nLine2\n\nFinal'`
  - Plan: `backlog task edit 42 --plan $'1. A\n2. B'`
  - Notes: `backlog task edit 42 --notes $'Done A\nDoing B'`
  - Append notes: `backlog task edit 42 --append-notes $'Progress update line 1\nLine 2'`
- POSIX portable (printf):
  - `backlog task edit 42 --notes "$(printf 'Line1\nLine2')"`
- PowerShell (backtick n):
  - `backlog task edit 42 --notes "Line1`nLine2"`

Do not expect `"...\n..."` to become a newline. That passes the literal backslash + n to the CLI by design.

Descriptions support literal newlines; shell examples may show escaped `\\n`, but enter a single `\n` to create a newline.

### Implementation Notes Formatting

- Keep implementation notes human-friendly and PR-ready: use short paragraphs or
  bullet lists instead of a single long line.
- Lead with the outcome, then add supporting details (e.g., testing, follow-up
  actions) on separate lines or bullets.
- Prefer Markdown bullets (`-` for unordered, `1.` for ordered) so Maintainers
  can paste notes straight into GitHub without additional formatting.
- When using CLI flags like `--append-notes`, remember to include explicit
  newlines. Example:

  ```bash
  backlog task edit 42 --append-notes $'- Added new API endpoint\n- Updated tests\n- TODO: monitor staging deploy'
  ```

### Task Operations

| Action             | Command                                      |
|--------------------|----------------------------------------------|
| View task          | `backlog task 42 --plain`                    |
| List tasks         | `backlog task list --plain`                  |
| Search tasks       | `backlog search "topic" --plain`              |
| Search with filter | `backlog search "api" --status "To Do" --plain` |
| Filter by status   | `backlog task list -s "In Progress" --plain` |
| Filter by assignee | `backlog task list -a @sara --plain`         |
| Archive task       | `backlog task archive 42`                    |
| Demote to draft    | `backlog task demote 42`                     |

---

## Common Issues

| Problem              | Solution                                                           |
|----------------------|--------------------------------------------------------------------|
| Task not found       | Check task ID with `backlog task list --plain`                     |
| AC won't check       | Use correct index: `backlog task 42 --plain` to see AC numbers     |
| Changes not saving   | Ensure you're using CLI, not editing files                         |
| Metadata out of sync | Re-edit via CLI to fix: `backlog task edit 42 -s <current-status>` |

---

## Remember: The Golden Rule

**🎯 If you want to change ANYTHING in a task, use the `backlog task edit` command.**
**📖 Use CLI to read tasks, exceptionally READ task files directly, never WRITE to them.**

Full help available: `backlog --help`

<!-- BACKLOG.MD GUIDELINES END -->
