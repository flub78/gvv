# GVV Code Documentation Standards
**Version**: 1.0
**Date**: 2025-10-09
**Status**: Active

## Purpose

This document defines documentation standards for the GVV codebase to ensure consistency, maintainability, and ease of understanding across all PHP files. Following these standards will help new developers understand the code and make it easier to maintain the 12-year-old codebase.

---

## Table of Contents

1. [File Header Standards](#file-header-standards)
2. [Function/Method Documentation](#functionmethod-documentation)
3. [Inline Comments](#inline-comments)
4. [PHPDoc Tags Reference](#phpdoc-tags-reference)
5. [Code Examples and Samples](#code-examples-and-samples)
6. [Quality Checklist](#quality-checklist)

---

## File Header Standards

### Required Components

Every PHP file must include:

1. **GPL License Header** (standard across GVV)
2. **Package/Subpackage Tags** (PHPDoc)
3. **Category** (functional area)
4. **File Purpose Statement** (2-3 sentences explaining what and why)

### Template

```php
<?php
/**
 * GVV Gestion vol à voile
 * Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    GVV
 * @subpackage Controllers|Models|Libraries|Helpers
 * @category   [Functional Category - see categories below]
 * @author     [Original author(s)]
 * @license    GPL-3.0
 * @link       https://github.com/flub78/gvv
 *
 * File Purpose:
 * [2-3 sentence description of what this file does and why it exists.
 * Focus on the role it plays in the application, not implementation details.]
 */
```

### Functional Categories

Use appropriate category based on file's primary purpose:

**Controllers**:
- Flight Management - Flight logging and records
- Member Management - Pilot/member administration
- Billing - Invoicing and pricing
- Accounting - Financial records
- Reporting - Report generation and export
- Configuration - System settings
- Aircraft Management - Fleet management

**Models**:
- Data Access - Database CRUD operations
- Business Logic - Domain-specific operations
- Configuration - Settings management

**Libraries**:
- Metadata - Field/table metadata system
- Utilities - General-purpose functionality
- PDF Generation - Document creation
- Email - Communication
- Security - Authentication/Authorization

**Helpers**:
- Form Rendering - Form element generation
- Validation - Input validation
- Data Transformation - Format conversions
- Utilities - General helpers

---

## Function/Method Documentation

### Required Components

Every public function/method must have:

1. **Brief Description** (one line)
2. **Extended Description** (optional, for complex logic)
3. **`@param` Tags** (all parameters with types and descriptions)
4. **`@return` Tag** (return type and description)
5. **`@example` Tags** (for non-trivial functions)
6. **`@uses`/`@see` Tags** (for important dependencies)

### Template

```php
/**
 * Brief one-line description of what the function does
 *
 * Optional extended description explaining:
 * - The purpose and use case
 * - Important algorithms or business logic
 * - Why this approach was chosen (not just how it works)
 * - Any non-obvious behavior or edge cases
 *
 * @param type  $param1 Description of first parameter, including constraints
 * @param type  $param2 Description of second parameter (optional if default provided)
 * @param array $param3 Associative array with keys: 'key1', 'key2'
 * @return type Description of return value, including possible types/values
 *
 * @throws ExceptionType Description of when and why exception is thrown
 *
 * @example
 * // Example usage with realistic data
 * $result = function_name('value', 42, ['key1' => 'val']);
 *
 * @uses DependencyClass::method() For specific functionality
 * @see RelatedFunction() For related operations
 *
 * @todo Optional TODO for future improvements
 * @deprecated Optional deprecation notice
 */
```

### Type Hints

Use these standard PHP types in `@param` and `@return`:

**Scalar Types**:
- `string` - Text values
- `int` - Integer numbers
- `float` - Decimal numbers
- `bool` - Boolean true/false

**Compound Types**:
- `array` - Arrays (specify structure if complex)
- `object` - Generic objects
- `ClassName` - Specific class instances
- `resource` - Resource handles

**Special Types**:
- `mixed` - Multiple possible types
- `void` - No return value
- `null` - Null value
- `type|null` - Nullable type

**Arrays with Structure**:
```php
@param array $options {
    @type string $name  User's name
    @type int    $age   User's age
    @type bool   $active Whether user is active
}
```

---

## Inline Comments

### When to Comment

Add inline comments for:

1. **Complex Algorithms** - Explain non-obvious logic
2. **Business Rules** - Document domain-specific requirements
3. **Workarounds** - Explain why unusual approaches were taken
4. **TODOs** - Mark future improvements
5. **Edge Cases** - Document special handling

### When NOT to Comment

Don't comment:

1. **Obvious Code** - `$i++; // increment i` is noise
2. **What Code Does** - Code should be self-documenting
3. **Redundant Info** - Don't repeat function documentation

### Comment Style

Use `//` for inline comments explaining WHY, not WHAT:

```php
// GOOD - Explains why
// User timezone set to Paris for French gliding club requirements
date_default_timezone_set('Europe/Paris');

// BAD - Explains what (obvious from code)
// Set timezone to Europe/Paris
date_default_timezone_set('Europe/Paris');
```

For multi-line explanations, use `/* */`:

```php
/*
 * The configuration priority system uses multiple queries because
 * the database may contain global, language-specific, and section-specific
 * values for the same key. We need to find the most specific match.
 * A single query with JOINs proved complex due to the optional nature
 * of language and section constraints.
 */
```

---

## PHPDoc Tags Reference

### Common Tags

| Tag | Purpose | Example |
|-----|---------|---------|
| `@param` | Parameter documentation | `@param string $name User's full name` |
| `@return` | Return value documentation | `@return int User ID or 0 if not found` |
| `@throws` | Exception documentation | `@throws Exception When database connection fails` |
| `@var` | Property documentation | `@var string Controller name for routing` |
| `@uses` | Dependency documentation | `@uses Database::query() For data retrieval` |
| `@see` | Cross-reference | `@see array2int() For the inverse operation` |
| `@example` | Usage example | `@example $user = get_user(42);` |
| `@todo` | Future improvement | `@todo Refactor to use single query` |
| `@deprecated` | Deprecation notice | `@deprecated Use PHPUnit tests instead` |
| `@since` | Version added | `@since 1.5.0` |
| `@author` | Author name | `@author John Doe` |

### GVV-Specific Tags

| Tag | Purpose | Example |
|-----|---------|---------|
| `@package` | Always "GVV" | `@package GVV` |
| `@subpackage` | Type of file | `@subpackage Controllers` |
| `@category` | Functional area | `@category Flight Management` |
| `@reviewed` | Review history | `@reviewed 2025-10-09 (Claude Code)` |
| `@license` | License type | `@license GPL-3.0` |

---

## Code Examples and Samples

### Sample Files

The following files are fully documented according to these standards and serve as templates:

1. **Helper**: `application/helpers/bitfields_helper.php`
   - Demonstrates utility function documentation
   - Shows how to explain algorithms (bitwise operations)
   - Examples of cross-referencing related functions

2. **Model**: `application/models/configuration_model.php`
   - Demonstrates class and method documentation
   - Shows how to document inherited behavior
   - Examples of documenting business logic complexity

3. **Controller**: `application/controllers/reports.php`
   - Demonstrates controller documentation
   - Shows how to document request/response flow
   - Examples of deprecation notices for legacy tests

### Quick Examples

#### Simple Function

```php
/**
 * Validates email address format
 *
 * @param string $email Email address to validate
 * @return bool True if valid, false otherwise
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
```

#### Function with Multiple Parameters

```php
/**
 * Creates a new flight record with automatic pricing calculation
 *
 * Calculates flight cost based on duration, aircraft, and pilot category.
 * Applies discounts for members under 25 or with season passes.
 *
 * @param int    $aircraft_id    Aircraft ID from machinesa/machinesp table
 * @param int    $pilot_id       Pilot ID from membres table
 * @param float  $duration       Flight duration in hours (decimals allowed)
 * @param string $date           Flight date in YYYY-MM-DD format
 * @param array  $options        Optional settings: ['discount_pct' => 0-100]
 * @return int   Newly created flight ID
 *
 * @throws Exception If aircraft or pilot not found
 *
 * @example
 * $flight_id = create_flight(5, 42, 1.5, '2025-10-09', ['discount_pct' => 10]);
 */
function create_flight($aircraft_id, $pilot_id, $duration, $date, $options = []) {
    // Implementation
}
```

#### Model Method

```php
/**
 * Retrieves paginated member list with filters
 *
 * Fetches active members with optional filtering by category,
 * section, or license status. Results are sorted by last name.
 *
 * @param int   $limit   Maximum records to return (default: 50)
 * @param int   $offset  Starting offset for pagination (default: 0)
 * @param array $filters Optional filters: ['category' => 'string', 'section_id' => int]
 * @return object Database result object with member records
 *
 * @uses Gvvmetadata::store_table() Stores result for table generation
 */
public function get_members_list($limit = 50, $offset = 0, $filters = []) {
    // Implementation
}
```

#### Controller Action

```php
/**
 * Displays the member edit form
 *
 * Retrieves member data by ID and renders the edit form with pre-populated
 * fields. Handles access control to ensure only authorized users can edit.
 * Form submission is handled by update() method.
 *
 * @param int $id Member ID to edit
 * @return void Renders the edit view with member data
 *
 * @uses membres_model::get_by_id() To fetch member data
 * @uses authorization_helper::check_edit_permission() For access control
 *
 * @example
 * URL: /membre/edit/42
 * Displays edit form for member ID 42
 */
public function edit($id) {
    // Implementation
}
```

---

## Quality Checklist

Before considering a file "documented", verify:

### File Level
- [ ] GPL license header present
- [ ] `@package GVV` tag present
- [ ] Appropriate `@subpackage` tag (Controllers/Models/Libraries/Helpers)
- [ ] Meaningful `@category` tag
- [ ] File purpose statement (2-3 sentences) explains **why** file exists
- [ ] No TODO or FIXME in file header without tracking in refactoring doc

### Class Level (Models, Controllers, Libraries)
- [ ] Class has PHPDoc comment block
- [ ] Class purpose explained
- [ ] Key responsibilities listed
- [ ] Important dependencies noted with `@uses` or `@see`
- [ ] Protected/private properties have `@var` documentation

### Function/Method Level
- [ ] All public functions have PHPDoc comments
- [ ] Brief description (one line) present
- [ ] All parameters documented with `@param` including type
- [ ] Return value documented with `@return` including type
- [ ] Exceptions documented with `@throws` if applicable
- [ ] At least one `@example` for non-trivial functions
- [ ] Cross-references with `@see` for related functions

### Inline Comments
- [ ] Complex algorithms explained
- [ ] Business rules documented
- [ ] Comments explain **why**, not **what**
- [ ] No commented-out code (move to version control instead)
- [ ] TODOs reference tracking document: `doc/reviews/documentation_refactoring_suggestions.md`

### Code Quality
- [ ] No obvious refactoring opportunities left undocumented
- [ ] Consistent indentation (tabs, as per GVV standard)
- [ ] Functions are reasonably sized (<100 lines ideally)
- [ ] Naming is clear and self-documenting

---

## Common Patterns in GVV

### Metadata-Driven Operations

When documenting functions that use the metadata system:

```php
/**
 * Generates form input element based on field metadata
 *
 * Renders HTML input element using metadata definitions from Gvvmetadata.
 * Element type, validation, and appearance are driven by metadata configuration
 * rather than hardcoded in this function.
 *
 * Why metadata-driven: Allows centralized field definitions and reduces
 * duplication across forms.
 *
 * @param string $table Field's table name (metadata lookup key)
 * @param string $field Field name (metadata lookup key)
 * @param mixed  $value Current field value for pre-population
 * @param string $mode  Render mode: 'edit', 'view', or 'create'
 * @return string HTML markup for form input element
 *
 * @uses Gvvmetadata::field For field metadata lookup
 */
```

### CodeIgniter Patterns

When documenting CodeIgniter-specific code:

```php
/**
 * Constructor for Members controller
 *
 * Initializes controller by loading required models and libraries.
 * Sets up access control and validation rules. Most CRUD functionality
 * is inherited from Gvv_Controller.
 *
 * Note: $CI =& get_instance() pattern is CodeIgniter's way of accessing
 * the superobject from contexts where $this is not available.
 *
 * @uses membres_model For member data access
 * @uses authorization_helper For permission checks
 */
```

### SQL Injection Prevention

When documenting database queries:

```php
/**
 * Executes user-defined report SQL query
 *
 * Security: SQL is validated by callback_safe_sql() validation rule
 * before execution to prevent SQL injection attacks. Only authorized
 * users (board members) can create/modify reports.
 *
 * @param string $sql SQL query from report definition
 * @return array Query results as associative array
 *
 * @uses Database::sql() For parameterized query execution
 */
```

---

## Documentation Workflow

### For New Files

1. Copy appropriate template from sample files
2. Fill in file header with purpose statement
3. Document class (if applicable)
4. Document all public methods with full PHPDoc
5. Add inline comments for complex sections
6. Run quality checklist

### For Existing Files

1. Read the file to understand its purpose
2. Add/update file header if missing or incomplete
3. Add/update class documentation
4. Document public methods (prioritize complex ones first)
5. Add inline comments where needed
6. Note refactoring opportunities in `doc/reviews/documentation_refactoring_suggestions.md`
7. Run quality checklist

### Ongoing Maintenance

- Update documentation when code changes
- Mark deprecated methods with `@deprecated` tag
- Add `@reviewed` tag with date when documentation is verified
- Keep examples current with actual usage

---

## Tools and Resources

### Recommended Tools

- **PHPDoc Generator**: Can generate API documentation from PHPDoc comments
- **VS Code Extensions**: PHP DocBlocker, PHP Intelephense
- **PHPStorm**: Built-in PHPDoc support and validation

### References

- [PHPDoc Official Documentation](https://docs.phpdoc.org/)
- [PSR-5 PHPDoc Standard](https://github.com/php-fig/fig-standards/blob/master/proposed/phpdoc.md)
- [GVV Documentation Assessment](doc/reviews/documentation_assessment_2025.md)
- [GVV Refactoring Suggestions](doc/reviews/documentation_refactoring_suggestions.md)

---

## Version History

| Version | Date       | Changes | Author |
|---------|------------|---------|--------|
| 1.0     | 2025-10-09 | Initial version | Claude Code |

---

**Questions?** See the sample files or consult the documentation assessment for examples.
