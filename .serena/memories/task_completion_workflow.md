# Task Completion Workflow for GVV

## Before Starting
1. **Source environment**: `source setenv.sh`
2. **Check existing code**: Always look for similar functionality before writing new code
3. **Review documentation**: Check `doc/development/workflow.md` for feature development

## Development Workflow for New Features

### 0. Design Phase
- Create markdown document in `doc/design_notes/` explaining the feature
- Create PlantUML diagrams for complex designs

### 1. Database Migration
- Define table in phpMyAdmin and export schema
- Create migration in `application/migrations/` (e.g., `042_feature_name.php`)
- Update `application/config/migration.php` to latest version number

### 2. Model Creation
- Create in `application/models/`
- Ensure `select_page()` returns primary key even if not displayed
- Implement joins in `select_page()` for related data

### 3. Metadata Definition
- Add field definitions to `application/libraries/Gvvmetadata.php`
- Define types, subtypes, selectors, enumerations

### 4. Controller Creation
- Create in `application/controllers/`
- Extend `CI_Controller` or `Gvv_Controller`
- Use metadata for form/table generation

### 5. Language Files
- Add translations to `application/language/french/`, `english/`, `dutch/`

### 6. Views
- Use Bootstrap 5 classes
- Leverage `$this->gvvmetadata->table()` for table views
- Use `array_field()` and `input_field()` for proper field rendering

### 7. Testing
- Create PHPUnit tests in appropriate `application/tests/` directory
- Aim for >70% code coverage
- Run tests before committing

## When Task is Complete

1. **Run tests**:
   ```bash
   source setenv.sh
   ./run-tests.sh                    # Fast check
   ./run-coverage.sh                 # With coverage
   ```

2. **Validate PHP syntax**:
   ```bash
   php -l path/to/modified_file.php
   ```

3. **Check logs** for missing metadata definitions:
   ```bash
   grep "GVV: input_field" application/logs/log-*.php
   ```

4. **Review changes**:
   ```bash
   git status
   git diff
   ```

5. **Commit** (if tests pass):
   ```bash
   git add .
   git commit -m "Descriptive message"
   ```

## Common Pitfalls to Avoid
1. Don't skip `source setenv.sh` - PHP version mismatch causes failures
2. Don't modify `system/` - It's CodeIgniter core
3. Don't create new patterns - Follow established CodeIgniter 2.x conventions
4. Don't skip metadata - Tables/forms won't render correctly
5. Don't forget migration version - Update `config/migration.php`
6. Don't duplicate code - Check existing implementations first
7. Don't use Composer - Project uses manual dependency management
