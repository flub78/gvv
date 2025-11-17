# AI Instructions for GVV Project

This file provides comprehensive guidance for AI assistants (Claude Code, GitHub Copilot, Gemini CLI, and others) when working with code in this repository.

---

## Project Overview

**GVV (Gestion Vol à voile)** is a web application for managing gliding clubs, developed in PHP since 2011. It handles member management, aircraft fleet management, flight logging, billing, basic accounting, flight calendars, and email communications. Currently used by 5-6 gliding associations.

- **Languages**: PHP 7.4, MySQL 5.x, HTML, CSS, JavaScript
- **Framework**: CodeIgniter 2.x (legacy version), Bootstrap 5 for UI
- **Database**: MySQL with migrations managed via CodeIgniter
- **Size**: ~50 controllers, extensive model layer, multi-language support (French, English, Dutch)
- **Status**: Deployed for 12 years, stable, in maintenance mode

---

## Critical: Environment Setup

**ALWAYS source the environment setup before running PHP commands:**

```bash
source setenv.sh
```

This sets PHP 7.4 as the active version. The project **requires PHP 7.4 specifically** - newer versions are not compatible.

---

## Common Development Commands

### Testing

```bash
# All phpunit test suites with no coverage - fast
./run-all-tests.sh

# All phpunit test suites with coverage - ~60 seconds
./run-all-tests.sh --coverage


# View coverage report
firefox build/coverage/index.html

# End to end tests
cd playwright; npx playwright test --reporter=line
```
### Local test server

It is installed at http://gvv.net/
Do not use localhost, it is not configured. 

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

---

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

---

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
11. **Database access**: Use the credential from configuration/database.php to analyze the database schema or data.
12. **Mockups**: Generate mockups in ASCII art, generate prototypes in self contained HTML files
13. **GUI**: Keep it as user friendly as possible. Never reject an action silently. The result of every action must be obvious to the user.
15. **Documentation**: Do not clutter the project root directory, generate markdown files under doc appropriate subdirectories
16. **PRD**: Limit the PRD documents to the description of requirements, no design elements.
17. **Design notes**: Stay minimalist and focused on architecture, do not describe the implementation. Only include code when it is necessary to understand the design. Use plantuml diagrams for database schemas and classes relationship when it is useful. Enforce clear separation of concerns between components.
18. **Progress tracking**: When working on an implementation plan avoid to create new documents. Do not create additional summaries, todo lists or work description, just update the plan. When adding a new feature or modification to a plan, restructure if necessary, so it does not appeared to be patched.
19. **Propagation**: When asked to modify a PRD also update the design document and plan if they exists. When asked to modify a design also update the PRD and plan if necessary. When asked to modify a plan, update design and PRD if they are impacted.
20. **Do only what instructed**: When you are asked to do something, only do what is requested, do not create additional documents, refactor, change design, implement code if not explicitly requested. If you have suggestions about improvements, refactoring, missing parts or documentations, ask for permission
21. **Plantuml**: when generating diagrams, usually for design notes, manage them into separate files .puml, put them into a subdirectory named diagrams, generate the images and put a link in the design document. The images must appears embedded in the document on github.
22. **Implementation completion**: When you are working on a whole feature (PRP), before to claim completion, run a phpunit smoke test and a playwright smoke test demonstrating that you can access the feature. In case of failure, enable the development mode in index.php, reproduce the error and fix it. Keep the tests in the test suite for future regression testing. Create a test to run the migrations if any and verify that the database schema is correct then rollback the database.
23. **Test users**: on the development platform, use the test users defined in the bin/create_test_users.sql script to do testing. If the users are not defined ask the permission to run the script.

---

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

---

## Testing Philosophy

- **Fast iteration**: Use `./run-tests.sh` (no coverage) during development
- **Pre-commit**: Use `./run-coverage.sh` for coverage before commits
- **Full suite**: Use `./run-all-tests.sh --coverage` for comprehensive validation
- **Unit tests**: No database dependencies, test helpers/libraries in isolation
- **Integration tests**: Real database operations, test component interactions
- **Coverage target**: Aim for 75% overall (currently establishing baseline)
- **Temporary tests**: Every times that you write a test to validate a modification evaluate if it is worth creating a phpunit test and adding it to the test base. Keep all phpunit tests that demonstrate that a bug has been fixed.

---

## Common Pitfalls to Avoid

1. **Don't skip `source setenv.sh`** - PHP version mismatch causes failures
2. **Don't modify `system/`** - It's CodeIgniter core
3. **Don't create new patterns** - Follow established CodeIgniter 2.x conventions
4. **Don't skip metadata** - Tables/forms won't render correctly without it
5. **Don't forget migration version** - Update `config/migration.php` after creating migration
6. **Don't duplicate code** - Extensive codebase likely has what you need
7. **Don't use Composer** - Project uses manual dependency management
8. **Only build what explicitly asked for** - Never assume, add or change features, infra or logic without a clear request in the PRD or plan to do so.
9. **No Over-Engineering** - Do not introduce features, logs, collections or automation unless directly specified.
10. **Do not generate additional documents when not required** - Restrict the documentation to the minimal, a PRD for the feature description and an implementation plan for the design , breakdown into tasks and status progress. Do not generate summaries of implementation, external todo lists or subpart design documents.

---

## Multi-Language Support

All user-facing strings must be defined in language files:
- `application/language/french/` (primary)
- `application/language/english/`
- `application/language/dutch/`

Use: `$this->lang->line('key_name')` in controllers/views

---

## File Permissions

Ensure these directories are web-writable:
```bash
chmod +wx application/logs
chmod +wx uploads
chmod +wx assets/images
```

---

## Third-Party Libraries

Located in `application/third_party/`:
- **TCPDF**: PDF generation
- **phpqrcode**: QR code generation
- **Google API**: Google integration
- **CIUnit**: Legacy testing framework (being phased out)

Handle with care - these are external dependencies.

---

## Prompt Guidelines

When working with AI assistants on GVV:

1. **Reference context**: "Context: GVV project (see AI_INSTRUCTIONS.md)"
2. **Be specific**: Focus on exact requirements, not general architecture
3. **Show integration**: Explain how changes connect to existing GVV components
4. **Work incrementally**: Break complex changes into reviewable steps
5. **Ask when unclear**: Request clarification before proceeding
6. **Reuse first**: Check existing implementations before writing new code
7. **Cleanup after yourself**: Delete all temporary tests

---

## Documentation

Key documentation files:
- `README.md` - Project overview, installation, updates
- `TESTING.md` - Quick testing reference
- `doc/AI_INSTRUCTIONS.md` - This file - comprehensive AI assistant guidance
- `doc/gemini-cli.md` - Gemini CLI usage guide for large codebase analysis
- `doc/development/workflow.md` - Feature development workflow
- `doc/development/phpunit.md` - Testing details
- `PHPUNIT_MIGRATION_SUMMARY.md` - Migration status from CI Unit_test to PHPUnit

---

# LLM-Specific Instructions

## Claude Code Specific Instructions

When working with Claude Code (claude.ai/code):

### Large Codebase Analysis

When analyzing large codebases or multiple files that might exceed context limits, refer to **`doc/gemini-cli.md`** for comprehensive guidance on using the Gemini CLI with its massive context window for:

- Analyzing entire codebases or large directories
- Comparing multiple large files  
- Understanding project-wide patterns or architecture
- Checking if specific features, patterns, or security measures are implemented
- Verifying the presence of certain coding patterns throughout the codebase

The Gemini CLI document provides detailed syntax, examples, and GVV-specific use cases for effective large-scale code analysis.

---

## GitHub Copilot Specific Instructions

**No specific instructions for GitHub Copilot at this time.**

Follow the general guidelines and project conventions outlined in this document.

---

## Gemini CLI Specific Instructions

For comprehensive Gemini CLI usage instructions, refer to **`doc/gemini-cli.md`**.

This includes:
- File and directory inclusion syntax using `@` notation
- Implementation verification examples
- GVV-specific use cases and patterns
- When to use Gemini CLI vs other tools
- Best practices for large codebase analysis

---

## Other AI Assistants

**No specific instructions for other AI assistants at this time.**

Follow the general guidelines and project conventions outlined in this document.

---

# Project Task Management

**For project task management using Backlog.md CLI:**

If you need to create, update, or manage project tasks, please read the comprehensive instructions in `doc/BACKLOG_INSTRUCTIONS.md`.

This keeps the AI instructions concise and loads backlog-specific guidance only when needed.
