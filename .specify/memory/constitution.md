<!--
Sync Impact Report:
- Version change: Template → 1.0.0
- Added principles: Metadata-Driven Architecture, Legacy-First Development, Test Coverage Excellence, Environment-Specific Development, Code Reuse Priority
- Added sections: Technology Constraints, Development Workflow
- Templates requiring updates: ⚠ plan-template.md, spec-template.md (pending manual review)
- Follow-up TODOs: None - all placeholders filled
-->

# GVV Project Constitution

## Core Principles

### I. Metadata-Driven Architecture (NON-NEGOTIABLE)
All database fields and form elements MUST be defined through the centralized metadata system in `application/libraries/Gvvmetadata.php`. No hardcoded field rendering is permitted. Form generation MUST use `$this->gvvmetadata->table()` for table views and metadata-aware helpers (`array_field`, `input_field`) for forms. Missing metadata definitions MUST be added to support proper field rendering and validation.

**Rationale**: This 12-year-old system ensures consistent UI rendering and reduces duplication across 50+ controllers. Breaking this pattern would fragment the codebase.

### II. Legacy-First Development
All changes MUST preserve compatibility with PHP 7.4 and CodeIgniter 2.x framework. New features MUST follow established CodeIgniter 2.x MVC patterns. The `system/` directory MUST NOT be modified. Before creating new implementations, existing codebase MUST be searched for similar functionality to reuse. Major architectural changes are prohibited - this is a maintenance-mode project.

**Rationale**: The application is deployed in production for 5-6 gliding associations. Stability and compatibility take precedence over modernization.

### III. Test Coverage Excellence
All new code MUST include PHPUnit tests with minimum 70% coverage target. Tests MUST be categorized appropriately: unit tests (no database), integration tests (real database operations), controller tests (response parsing), or MySQL tests (CRUD operations). Legacy CodeIgniter Unit_test methods MUST be migrated to PHPUnit. Test execution MUST use the fast `./run-all-tests.sh` for development and `./run-all-tests.sh --coverage` for pre-commit validation. All complex feature must be covered by a e2e playwright test.

**Rationale**: The project has accumulated ~128 tests across multiple suites. Testing discipline ensures reliability for production deployments.

### IV. Environment-Specific Development (NON-NEGOTIABLE)
All PHP operations MUST be preceded by `source setenv.sh` to ensure PHP 7.4 compatibility. Development commands MUST use the explicit `/usr/bin/php7.4` path when needed. File permissions MUST be verified for web-writable directories (`chmod +wx` for logs, uploads, assets/images). Database migrations MUST update `application/config/migration.php` version after creation.

**Rationale**: Version conflicts cause silent failures. The application requires specific PHP 7.4 features and database migration discipline.

### V. Code Reuse Priority
Before implementing new functionality, the extensive codebase (~50 controllers, comprehensive model layer) MUST be searched for existing implementations. Similar patterns MUST be identified and reused rather than duplicated. Manual dependency management (no Composer) MUST be maintained. Third-party libraries in `application/third_party/` MUST be handled with care as external dependencies.

**Rationale**: This mature codebase likely contains solutions for most common requirements. Duplication increases maintenance burden.

## Technology Constraints

### Mandatory Technology Stack
- **PHP Version**: 7.4 specifically (newer versions not compatible)
- **Framework**: CodeIgniter 2.x (legacy version, do not upgrade)
- **Database**: MySQL 5.x with CodeIgniter-managed migrations
- **UI Framework**: Bootstrap 5 for all user interface components
- **Testing**: PHPUnit (replacing legacy CodeIgniter Unit_test) and playwright
- **Dependency Management**: Manual (predates Composer, do not introduce)

### Multi-Language Requirements
All user-facing strings MUST be defined in language files for French (primary), English, and Dutch support. Language files are located in `application/language/[french|english|dutch]/`. Controllers and views MUST use `$this->lang->line('key_name')` for text display.

### Documentation Standards
All new code MUST include comprehensive PHPDoc documentation following `doc/DOCUMENTATION_STANDARDS.md`. File headers MUST include GPL license, package/subpackage tags, and functional category. Public methods MUST document parameters, return values, examples, and cross-references.

## Development Workflow 

### Feature Development Process
0. **Run the tests**: Before any significant development run the phpunit and playwright test suite to establish the current test status and be able to detect regressions.
1. **Design Phase**: Create markdown document in `doc/design_notes` explaining architecture; Create PlantUML diagrams for complex designs
2. **Database Migration**: Define schema in phpMyAdmin, export, create numbered migration file, update `migration.php` version
3. **Model Creation**: Implement in `application/models/` with proper `select_page()` method returning primary keys
4. **Metadata Definition**: Add field definitions to `Gvvmetadata.php` for proper rendering
5. **Controller Implementation**: Create in `application/controllers/` extending appropriate base class
6. **Internationalization**: Add translations to all three language files
7. **View Creation**: Use Bootstrap 5 classes and metadata-driven rendering
8. **Testing**: Create PHPUnit tests with appropriate categorization and coverage
9. **Run the tests**: Run the tests suites again to check for regressions 

### Quality Gates
- Environment setup verification (`source setenv.sh`)
- PHP syntax validation (`php -l` on modified files)
- Test execution with coverage (`./run-all-tests.sh --coverage`)
- Documentation completeness check (PHPDoc standards)
- Migration version update verification
- Multi-language string definition verification

### Code Review Requirements
All changes MUST verify compliance with metadata-driven architecture, legacy compatibility, test coverage standards, and documentation requirements. Complex implementations MUST be justified against simpler alternatives. Refactoring opportunities MUST be documented in `doc/reviews/` rather than implemented immediately.

## Governance

This constitution supersedes all other development practices for the GVV project. All pull requests and code reviews MUST verify compliance with these principles. Complexity that violates these principles MUST be explicitly justified with technical rationale. Runtime development guidance is provided in `doc/AI_INSTRUCTIONS.md` for AI assistants and human developers.

Amendment of this constitution requires documentation of impact analysis, approval from project maintainers, and migration plan for existing code that may be affected. Version changes follow semantic versioning: MAJOR for backward incompatible changes, MINOR for new principles/sections, PATCH for clarifications.

**Version**: 1.0.0 | **Ratified**: 2025-10-21 | **Last Amended**: 2025-10-21
