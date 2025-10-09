# GVV Documentation & Refactoring Suggestions
**Date**: 2025-10-09
**Status**: In Progress

## Overview

This document tracks refactoring suggestions discovered during the code documentation process. The goal is to improve code maintainability, reduce complexity, and enhance documentation without breaking existing functionality.

## Guiding Principles

1. **Maintenance Mode**: GVV is in maintenance mode - avoid major architectural changes
2. **Code Reuse First**: Check for existing patterns before creating new ones
3. **Incremental Changes**: Small, testable refactorings are preferred
4. **Backward Compatible**: Maintain existing APIs and behavior
5. **Test Coverage**: Ensure >70% coverage for refactored code

---

## Refactoring Suggestions by Category

### 1. Gvvmetadata.php - Constructor Complexity

**File**: `application/libraries/Gvvmetadata.php`
**Lines**: 1-865
**Priority**: High
**Complexity**: The constructor has **865 lines** of metadata initialization

#### Current Issues
- Single massive constructor with all metadata definitions
- Hard to navigate and maintain
- All metadata loaded even when only subset is needed
- Violates Single Responsibility Principle

#### Suggestion
Extract metadata initialization into separate methods by domain:

```php
class GVVMetadata extends Metadata {
    function __construct() {
        parent::__construct();
        $CI = &get_instance();
        $CI->lang->load('gvv');

        // Load metadata by domain
        $this->init_achats_metadata();
        $this->init_categories_metadata();
        $this->init_comptes_metadata();
        $this->init_ecritures_metadata();
        $this->init_events_metadata();
        $this->init_planeurs_metadata();
        $this->init_avions_metadata();
        $this->init_membres_metadata();
        $this->init_vols_planeur_metadata();
        $this->init_vols_avion_metadata();
        $this->init_facturation_metadata();
        $this->init_tickets_metadata();
        $this->init_pompes_metadata();
        $this->init_attachments_metadata();
        $this->init_configuration_metadata();
    }

    private function init_achats_metadata() {
        $this->field['achats']['id']['Name'] = 'Id';
        // ... rest of achats metadata
    }

    // ... other init methods
}
```

#### Benefits
- Improved readability and navigation
- Easier to find and modify specific metadata
- Potential for lazy loading in future
- Better organization by domain

#### Implementation Checklist
- [ ] Create backup of Gvvmetadata.php
- [ ] Extract achats metadata to `init_achats_metadata()`
- [ ] Extract categories metadata to `init_categories_metadata()`
- [ ] Extract comptes metadata to `init_comptes_metadata()`
- [ ] Extract ecritures metadata to `init_ecritures_metadata()`
- [ ] Extract events metadata to `init_events_metadata()`
- [ ] Extract planeurs metadata to `init_planeurs_metadata()`
- [ ] Extract avions metadata to `init_avions_metadata()`
- [ ] Extract membres metadata to `init_membres_metadata()`
- [ ] Extract vols_planeur metadata to `init_vols_planeur_metadata()`
- [ ] Extract vols_avion metadata to `init_vols_avion_metadata()`
- [ ] Extract facturation metadata to `init_facturation_metadata()`
- [ ] Extract tickets metadata to `init_tickets_metadata()`
- [ ] Extract pompes metadata to `init_pompes_metadata()`
- [ ] Extract attachments metadata to `init_attachments_metadata()`
- [ ] Extract configuration metadata to `init_configuration_metadata()`
- [ ] Run tests to ensure no regression
- [ ] Update documentation

#### Estimated Effort
- **Time**: 3-4 hours
- **Risk**: Low (pure refactoring, no logic changes)
- **Testing**: Run existing test suite

---

### 2. Code Duplication in Flight Controllers

**Files**:
- `application/controllers/vols_planeur.php`
- `application/controllers/vols_avion.php`

**Priority**: Medium
**Issue**: Likely significant code duplication between glider and airplane flight controllers

#### Investigation Needed
- [ ] Compare vols_planeur.php and vols_avion.php
- [ ] Identify common patterns and duplicated code
- [ ] Determine if common base class would help
- [ ] Check if metadata-driven approach could reduce duplication

#### Potential Solutions
1. **Extract common flight controller base class**
   ```php
   abstract class Flight_Controller extends Gvv_Controller {
       protected abstract function get_flight_type();
       protected abstract function get_model_name();
       // ... common flight operations
   }
   ```

2. **Use metadata to drive differences**
   - Flight-type-specific logic driven by metadata
   - Reduce conditional logic in controllers

#### Deferred
This refactoring should wait until we document both controllers and understand the full scope of duplication.

---

### 3. Inconsistent Comment Styles

**Files**: Multiple across codebase
**Priority**: Low
**Issue**: Mix of `/* */`, `/** */`, and `//` comment styles

#### Current State
- Some files use C-style comments: `/* Comment */`
- Others use PHPDoc style: `/** Comment */`
- Some use inline: `// Comment`

#### Standard to Adopt
```php
// Use PHPDoc for all file and function headers
/**
 * PHPDoc style for documentation
 */

// Use inline comments for code explanations
// This explains why we do something, not what we do

/* C-style comments should be avoided */
```

#### Implementation
This will be addressed naturally during the documentation phase as files are updated to follow the standard template.

---

### 4. Reports Controller - Duplicated Export Logic

**File**: `application/controllers/reports.php`
**Lines**: 81-197
**Priority**: Medium

#### Current Issues
Looking at the code:
- `export($type, $request)` method handles both PDF and CSV
- `csv($request)` method duplicates export logic
- `pdf($request)` method duplicates export logic
- Same data fetching code in three places

#### Current Code Pattern
```php
public function export($type, $request) {
    $elt = $this->gvv_model->get_by_id('nom', $request);
    $sql = $elt['sql'];
    $select = $this->database->sql($sql, true);
    // ... processing
    if ($type == 'pdf') {
        $this->gen_pdf(...);
    } else {
        $this->gen_csv(...);
    }
}

public function csv($request) {
    $elt = $this->gvv_model->get_by_id('nom', $request);
    $sql = $elt['sql'];
    $select = $this->database->sql($sql, true);
    // ... same processing
    $this->gen_csv(...);
}

public function pdf($request) {
    $elt = $this->gvv_model->get_by_id('nom', $request);
    $sql = $elt['sql'];
    // ... same processing
    $this->gen_pdf(...);
}
```

#### Suggested Refactoring
```php
/**
 * Fetch and prepare report data
 *
 * @param string $request Report identifier
 * @return array Report data with metadata
 */
private function prepare_report_data($request) {
    $elt = $this->gvv_model->get_by_id('nom', $request);
    $sql = $elt['sql'];
    $select = $this->database->sql($sql, true);

    return [
        'data' => $select[0],
        'title' => $elt['titre'],
        'fields' => explode(",", $elt['fields_list']),
        'align' => explode(",", $elt['align']),
        'width' => explode(",", $elt['width']),
        'landscape' => $elt['landscape']
    ];
}

public function export($type, $request) {
    $report = $this->prepare_report_data($request);

    if ($type == 'pdf') {
        $this->gen_pdf($report['title'], $report['data'],
                       $report['fields'], $report['align'],
                       $report['width'], $report['landscape']);
    } else {
        $this->gen_csv($request, $report['title'],
                       $report['data'], $report['fields']);
    }
}

public function csv($request) {
    $report = $this->prepare_report_data($request);
    $this->gen_csv($request, $report['title'],
                   $report['data'], $report['fields']);
}

public function pdf($request) {
    $report = $this->prepare_report_data($request);
    $this->gen_pdf($report['title'], $report['data'],
                   $report['fields'], $report['align'],
                   $report['width'], $report['landscape']);
}
```

#### Benefits
- DRY principle - data fetching logic in one place
- Easier to maintain and test
- Clear separation of concerns

#### Implementation Checklist
- [ ] Read full reports.php controller
- [ ] Create `prepare_report_data()` private method
- [ ] Refactor `export()` to use new method
- [ ] Refactor `csv()` to use new method
- [ ] Refactor `pdf()` to use new method
- [ ] Write unit tests for `prepare_report_data()`
- [ ] Run existing tests to ensure no regression
- [ ] Update documentation

#### Estimated Effort
- **Time**: 1-2 hours
- **Risk**: Low (well-contained refactoring)
- **Testing**: Unit tests + existing controller tests

---

### 5. Configuration Model - Complex get_param Logic

**File**: `application/models/configuration_model.php`
**Lines**: 56-84
**Priority**: Low

#### Current Issue
The `get_param()` method has cascading database queries:

```php
public function get_param($key, $lang = null) {
    // First query
    $this->db->where('cle', $key);
    $query = $this->db->get($this->table);

    // If multiple results, try again with language
    if ($query->num_rows() > 1) {
        $this->db->where('cle', $key);
        $this->db->where('lang', $lang);
        $query = $this->db->get($this->table);
    }

    // If still multiple, try with section
    if ($query->num_rows() > 1) {
        $this->db->where('cle', $key);
        $section = $this->gvv_model->section();
        $this->db->where('club', $section['id']);
        $query = $this->db->get($this->table);
    }

    return $query->num_rows() > 0 ? $query->row()->valeur : null;
}
```

#### Issues
- Multiple database queries for same data
- Could be done in single query with proper WHERE clause
- Not clear what the priority order is without reading code

#### Suggested Refactoring
```php
/**
 * Retrieves a configuration parameter by its key
 *
 * Priority order for matching:
 * 1. key + lang + section (most specific)
 * 2. key + lang (language-specific)
 * 3. key only (global default)
 *
 * @param string $key The configuration key identifier
 * @param string|null $lang Optional language code (defaults to current language)
 * @return mixed The configuration value, or null if not found
 */
public function get_param($key, $lang = null) {
    if ($lang === null) {
        $lang = $this->config->item('language');
    }

    $section = $this->gvv_model->section();

    // Single query with ORDER BY for priority
    $this->db->where('cle', $key);
    $this->db->where('(lang IS NULL OR lang = ' . $this->db->escape($lang) . ')');
    $this->db->where('(club IS NULL OR club = ' . $this->db->escape($section['id']) . ')');
    $this->db->order_by('club IS NOT NULL DESC, lang IS NOT NULL DESC');
    $this->db->limit(1);
    $query = $this->db->get($this->table);

    return $query->num_rows() > 0 ? $query->row()->valeur : null;
}
```

#### Benefits
- Single database query instead of up to 3
- Better performance
- Explicit priority order in ORDER BY clause
- Clearer intent with documentation

#### Deferred
This refactoring should wait for:
1. Better understanding of configuration table structure
2. Analysis of query performance impact
3. Comprehensive testing of configuration system

---

### 6. Bitfields Helper - Missing Parameter Documentation

**File**: `application/helpers/bitfields_helper.php`
**Lines**: 24-38, 42-58
**Priority**: High (will be addressed in sample documentation)

#### Current State
```php
/**
 * Encode an array used as selectbox array into an integer
 *
 * @return int
 */
function array2int($boxes) {
    // ...
}
```

#### Needs Improvement
- Missing `@param` documentation
- Return documentation doesn't explain what the int represents
- No example of usage
- Doesn't explain the bit manipulation algorithm

#### Will Be Addressed
This will be fixed when creating the sample documented helper file.

---

## Summary of Action Items

### Immediate (This Session)
- [x] Create this refactoring suggestions document
- [ ] Document sample files with proper PHPDoc
- [ ] Create documentation standards guide

### Short Term (Next 1-2 Sessions)
- [ ] Extract Gvvmetadata.php constructor into domain methods
- [ ] Refactor reports.php to eliminate duplication
- [ ] Document all helper files (17 files)

### Medium Term (Next 5-10 Sessions)
- [ ] Investigate and reduce flight controller duplication
- [ ] Standardize comment styles across codebase
- [ ] Document all models (44 files)

### Long Term (Future Work)
- [ ] Optimize configuration_model.php queries
- [ ] Consider lazy-loading for metadata
- [ ] Evaluate metadata-driven approach for similar controllers

---

## Notes

- **Keep this document updated** as you discover new refactoring opportunities
- **Link to this doc** from code comments using: `// TODO: See doc/reviews/documentation_refactoring_suggestions.md#section`
- **Track progress** by checking off implementation checklist items
- **Re-evaluate priorities** after each major documentation phase

---

**Last Updated**: 2025-10-09
**Next Review**: After completing Phase 2 (Core Infrastructure Documentation)
