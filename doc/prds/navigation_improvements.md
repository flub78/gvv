# PRD: Navigation and Return URL Improvements

**Status**: Draft  
**Created**: 2025-10-21  
**Author**: AI Analysis  
**Priority**: High  
**Complexity**: Medium

## Executive Summary

The current navigation system in GVV uses a URL stack mechanism (`push_return_url`/`pop_return_url`) that produces unexpected and confusing return behavior in several scenarios. This PRD proposes improvements to make navigation predictable and consistent across all resources.

## Problem Statement

### Current Issues

1. **Accounting Lines (écritures) modification** - After modifying an accounting line, the return page is unexpected and may not return to where the user came from
2. **Freeze checkbox (gel) toggle** - When toggling the freeze checkbox on an accounting line, the user is redirected to an incorrect page
3. **Inconsistent URL stack behavior** - The current implementation has 26 push operations but only 12 pop operations, leading to stack imbalance
4. **Missing breadcrumbs** - Only a few resources (procedures, authorization) have breadcrumb navigation

### Root Causes

#### 1. Stack Imbalance
- **26 `push_return_url()` calls** across controllers
- **12 `pop_return_url()` calls** across controllers
- The stack grows indefinitely when navigations don't have matching pops
- `page()` method in `Gvv_Controller` always pushes to stack (line 630) even for simple list views

#### 2. Problematic `pop_return_url(1)` Usage
In `compta.php` line 322:
```php
// Modification
$this->change_ecriture($processed_data);
$this->pop_return_url(1);  // Skip parameter = 1
```

The `pop_return_url($skip)` implementation (Gvv_Controller.php lines 678-705) has confusing logic:
- When `$skip = 1`, it pops once without redirecting
- Then enters a while loop trying to find a valid URL
- May redirect to wrong page if stack is unbalanced

#### 3. Checkbox Toggle Navigation
In `compta.php` line 1706-1709:
```php
function switch_line($id, $state, $compte, $premier) {
    $new_state = ($state == 0) ? 1 : 0;
    $this->gvv_model->switch_line($id, $new_state);
    $this->pop_return_url();  // Wrong! Should stay on same page
}
```

When a user toggles a freeze checkbox from a list view, they should **stay on that list view**, not be redirected via the stack.

#### 4. Edit Form Push Timing
In `compta.php` line 76 and `Gvv_Controller.php` line 161:
```php
function edit($id = "", $load_view = TRUE, $action = MODIFICATION) {
    // ... load data ...
    $this->push_return_url("edit ecriture");  // Pushes edit URL, not origin!
    // ... display form ...
}
```

This pushes the **edit page URL** to the stack, not the originating page. When the user saves, they may end up back at the edit page instead of the list.

## Navigation Principles (Restated)

1. **After successful form submission**: Return to where the form was opened
   - Exception: "Create and Continue" button should reload empty create form
2. **After validation failure**: Stay in form with error messages displayed
3. **After changing value in list view**: Stay on the list view (refresh data)
4. **Menu navigation**: Show appropriate list/landing page for the resource
5. **Breadcrumb navigation**: Provide clear navigation hierarchy for all resources

## Current Status Analysis

### Controllers Using push/pop Return URLs

13 controllers currently use the URL stack mechanism:
- `achats.php` (1 push)
- `compta.php` (5 push, 5 pop) - **Most problematic**
- `comptes.php` (5 push, 2 pop) - **Imbalanced**
- `event.php` (4 push, 2 pop) - **Imbalanced**
- `FFVV.php` (1 push commented out)
- `licences.php` (1 push)
- `membre.php` (1 push)
- `rapports.php` (2 push)
- `tarifs.php` (1 push)
- `tickets.php` (1 push)
- `vols_avion.php` (2 push, 1 pop) - **Imbalanced**
- `vols_decouverte.php` (2 push)
- `vols_planeur.php` (2 push, 1 pop) - **Imbalanced**

### Controllers with Breadcrumbs

Only 2 areas have implemented breadcrumbs:
- `procedures/` views (bs_view.php, bs_formView.php, bs_attachments.php)
- `authorization/` views (multiple views)

## Proposed Solution

### Phase 1: Fix Critical Navigation Bugs (High Priority)

#### 1.1 Fix Accounting Line Modification Return
**File**: `application/controllers/compta.php` line 322

**Current**:
```php
} else {
    // Modification
    $this->change_ecriture($processed_data);
    $this->pop_return_url(1);  // PROBLEMATIC
}
```

**Proposed**:
```php
} else {
    // Modification
    $this->change_ecriture($processed_data);
    $this->pop_return_url();  // Remove skip parameter
}
```

**Alternative** (if edit is pushing wrong URL):
```php
} else {
    // Modification  
    $this->change_ecriture($processed_data);
    // Return to list view or previous page
    $back_url = $this->session->userdata('back_url');
    if ($back_url && strpos($back_url, 'compta/edit') === false) {
        redirect($back_url);
    } else {
        redirect('compta/page');
    }
}
```

#### 1.2 Fix Freeze Checkbox Toggle
**File**: `application/controllers/compta.php` line 1706-1709

**Current**:
```php
function switch_line($id, $state, $compte, $premier) {
    $new_state = ($state == 0) ? 1 : 0;
    $this->gvv_model->switch_line($id, $new_state);
    $this->pop_return_url();  // WRONG!
}
```

**Proposed**:
```php
function switch_line($id, $state, $compte, $premier) {
    $new_state = ($state == 0) ? 1 : 0;
    $this->gvv_model->switch_line($id, $new_state);
    // Stay on the same page - redirect to the referring page or account journal
    $back_url = $this->session->userdata('back_url');
    if ($back_url) {
        redirect($back_url);
    } else {
        redirect("compta/journal_compte/$compte");
    }
}
```

**Better approach** (AJAX):
Convert checkbox toggle to AJAX to avoid full page reload:
```php
function switch_line_ajax() {
    if (!$this->input->is_ajax_request()) {
        show_404();
        return;
    }
    
    $id = $this->input->post('id');
    $state = $this->input->post('state');
    $new_state = ($state == 0) ? 1 : 0;
    $this->gvv_model->switch_line($id, $new_state);
    
    $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode(['success' => true, 'new_state' => $new_state]));
}
```

### Phase 2: Refactor URL Stack Mechanism (Medium Priority)

#### 2.1 Remove Automatic Push from page() Method
**File**: `application/libraries/Gvv_Controller.php` line 630

**Current**:
```php
function page($premier = 0, $message = '', $selection = array()) {
    $this->push_return_url("GVV controller page");  // AUTOMATIC PUSH
    // ... display page ...
}
```

**Proposed**:
```php
function page($premier = 0, $message = '', $selection = array()) {
    // Don't automatically push - let controllers decide
    // Only push if explicitly needed for navigation context
    // ... display page ...
}
```

**Impact**: This is a **breaking change** that requires reviewing all 26 push_return_url() calls.

#### 2.2 Standardize Edit Push Behavior
**File**: `application/libraries/Gvv_Controller.php` line 161

**Proposed**: Don't push edit URL to stack. Instead:

```php
function edit($id = "", $load_view = TRUE, $action = MODIFICATION) {
    // DON'T push edit URL - we want to return to the page before edit
    // The calling page (list/detail) should have already pushed its URL
    
    $this->data = $this->gvv_model->get_by_id($this->kid, $id);
    // ... rest of method ...
}
```

#### 2.3 Simplify pop_return_url Logic
**File**: `application/libraries/Gvv_Controller.php` lines 678-705

**Current**: Complex logic with skip parameter that's only used once

**Proposed**:
```php
/**
 * Return to a previously saved URL
 * @param int $levels Number of levels to go back (default 1)
 */
function pop_return_url($levels = 1) {
    $this->clean_old_url_stack();
    
    $url_stack = $this->session->userdata('return_url_stack');
    if (empty($url_stack)) {
        redirect($this->controller . "/page");
        return;
    }
    
    // Pop requested number of levels
    for ($i = 0; $i < $levels && !empty($url_stack); $i++) {
        $url = array_pop($url_stack);
    }
    
    $this->session->set_userdata('return_url_stack', $url_stack);
    
    // Ensure we don't redirect to current URL (infinite loop prevention)
    $current_url = current_url();
    if ($url && $url != $current_url) {
        redirect($url);
    } else {
        redirect($this->controller . "/page");
    }
}
```

### Phase 3: Implement Breadcrumbs (Lower Priority)

#### 3.1 Add Breadcrumb Helper
**File**: `application/helpers/breadcrumb_helper.php` (new)

```php
<?php
/**
 * Build breadcrumb navigation
 * 
 * @param array $crumbs Array of ['label' => 'Label', 'url' => 'url'] items
 * @return string HTML breadcrumb
 */
function build_breadcrumb($crumbs) {
    if (empty($crumbs)) {
        return '';
    }
    
    $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    
    $last_index = count($crumbs) - 1;
    foreach ($crumbs as $index => $crumb) {
        if ($index === $last_index) {
            // Last item - active, no link
            $html .= '<li class="breadcrumb-item active" aria-current="page">';
            $html .= htmlspecialchars($crumb['label']);
            $html .= '</li>';
        } else {
            // Regular item with link
            $html .= '<li class="breadcrumb-item">';
            $html .= '<a href="' . site_url($crumb['url']) . '">';
            $html .= htmlspecialchars($crumb['label']);
            $html .= '</a></li>';
        }
    }
    
    $html .= '</ol></nav>';
    return $html;
}
```

#### 3.2 Add Breadcrumbs to Key Resources

Priority resources for breadcrumb implementation:
1. **Accounting (compta)** - High complexity navigation
2. **Members (membre)** - Member → Licenses → Details
3. **Flights (vols_avion, vols_planeur)** - Flight → Edit → Attachments
4. **Purchases (achats)** - Purchase → Details → Accounting lines

Example for accounting line:
```php
// In compta controller edit method
$breadcrumbs = [
    ['label' => 'Accueil', 'url' => 'welcome'],
    ['label' => 'Comptabilité', 'url' => 'compta/page'],
    ['label' => 'Journal', 'url' => 'compta/journal_compte/' . $compte],
    ['label' => 'Écriture #' . $id, 'url' => '']  // Current page
];
$this->data['breadcrumbs'] = build_breadcrumb($breadcrumbs);
```

## Implementation Strategy

### Quick Wins (1-2 hours)
1. Fix `pop_return_url(1)` in compta.php line 322
2. Fix `switch_line` navigation in compta.php line 1709
3. Test accounting line workflows

### Short Term (1 day)
1. Review all 26 push_return_url calls for correctness
2. Add missing pop_return_url calls to balance stack
3. Document expected navigation flow for each controller

### Medium Term (2-3 days)
1. Remove automatic push from `page()` method
2. Refactor URL stack logic for clarity
3. Create breadcrumb helper
4. Add breadcrumbs to compta module

### Long Term (1 week)
1. Add breadcrumbs to all major resources
2. Consider replacing URL stack with breadcrumb-based navigation
3. Add automated tests for navigation flows

## Testing Strategy

### Manual Testing Scenarios

#### Test 1: Accounting Line Edit Return
1. Navigate to compta/journal_compte/512
2. Click "Edit" on an accounting line
3. Modify the line and click "Enregistrer"
4. **Expected**: Return to compta/journal_compte/512
5. **Current**: May return to unexpected page

#### Test 2: Freeze Checkbox Toggle
1. Navigate to compta/journal_compte/512
2. Click the freeze checkbox on a line
3. **Expected**: Stay on compta/journal_compte/512 with updated checkbox
4. **Current**: Redirected to wrong page

#### Test 3: Create and Continue
1. Navigate to compta/page
2. Click "New accounting line"
3. Fill form and click "Créer et continuer"
4. **Expected**: Empty form reloaded for next entry
5. **Current**: Should work (verify)

#### Test 4: Navigation from Menu
1. Click "Comptabilité" in menu
2. **Expected**: compta/page list view
3. **Current**: Should work (verify)

### Automated Testing

Create PHPUnit tests in `application/tests/integration/NavigationTest.php`:

```php
<?php
class NavigationTest extends TestCase {
    
    public function test_accounting_line_edit_returns_to_journal() {
        // Simulate: journal → edit → save
        $this->session->set_userdata('return_url_stack', [
            site_url('compta/journal_compte/512')
        ]);
        
        // Simulate form submission
        $_POST['button'] = 'Enregistrer';
        // ... set form data ...
        
        $this->controller->formValidation(MODIFICATION);
        
        // Should redirect to journal
        $this->assertRedirect('compta/journal_compte/512');
    }
    
    public function test_freeze_checkbox_stays_on_page() {
        $this->session->set_userdata('back_url', site_url('compta/journal_compte/512'));
        
        $this->controller->switch_line(123, 0, 512, 0);
        
        // Should stay on same page
        $this->assertRedirect('compta/journal_compte/512');
    }
    
    public function test_url_stack_balance() {
        // Verify push/pop are balanced after common workflows
        $this->resetUrlStack();
        
        // Simulate: page → edit → save
        $this->controller->page();
        $stack_after_page = $this->getStackSize();
        
        $this->controller->edit(1);
        $stack_after_edit = $this->getStackSize();
        
        $this->controller->formValidation(MODIFICATION);
        $stack_after_save = $this->getStackSize();
        
        $this->assertEquals($stack_after_page, $stack_after_save,
            "Stack should return to original size after save");
    }
}
```

## Success Metrics

1. **User Confusion**: Reduction in support requests about "wrong page after save"
2. **Navigation Predictability**: 100% of edit→save flows return to expected page
3. **Stack Balance**: All major workflows maintain balanced push/pop
4. **Breadcrumb Coverage**: 80% of resources have breadcrumb navigation
5. **Performance**: No degradation in page load times

## Risks and Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| Breaking existing workflows | Medium | High | Thorough testing of all affected controllers |
| Session bloat from large stacks | Low | Medium | Implement stack size limit (max 10 items) |
| Breadcrumb maintenance overhead | Medium | Low | Create reusable components and helpers |
| User adaptation to new navigation | Low | Low | Changes restore expected behavior |

## Open Questions

1. Should we implement a maximum stack size to prevent session bloat?
2. Should AJAX be used for all checkbox toggles to avoid page reloads?
3. Should breadcrumb navigation eventually replace the URL stack entirely?
4. How to handle deep navigation chains (5+ levels)?
5. Should we add visual indicators for "where am I going back to"?

## References

- Design document: `/doc/design_notes/breadcrum.md`
- Design document: `/doc/design_notes/codeigniter-return-url-stack.php`
- Implementation: `application/libraries/Gvv_Controller.php`
- Bootstrap breadcrumb docs: https://getbootstrap.com/docs/5.0/components/breadcrumb/

## Appendix: Navigation Flow Diagrams

### Current Flow (Problematic)

```
List View (journal_compte)
  ├─ push(journal_compte_url)
  ├─ click Edit
  │
Edit Form
  ├─ push(edit_url)          ← PROBLEM: Pushes edit URL!
  ├─ modify data
  ├─ click Save
  │
Save Handler
  ├─ pop_return_url(1)       ← PROBLEM: Skip logic confusing
  └─ redirect to ??? (unexpected page)
```

### Proposed Flow (Fixed)

```
List View (journal_compte)
  ├─ push(journal_compte_url)  ← Push BEFORE navigating to edit
  ├─ click Edit
  │
Edit Form
  ├─ (no push - just display)
  ├─ modify data
  ├─ click Save
  │
Save Handler
  ├─ pop_return_url()          ← Simple pop
  └─ redirect to journal_compte (expected!)
```

### Alternative: Breadcrumb-Based Flow

```
List View
  ├─ breadcrumb: [Home > Accounting > Journal]
  ├─ click Edit
  │
Edit Form
  ├─ breadcrumb: [Home > Accounting > Journal > Edit #123]
  ├─ click Save
  │
Save Handler
  ├─ extract parent from breadcrumb trail
  └─ redirect to Journal (from breadcrumb)
```

## Conclusion

The current URL stack mechanism has fundamental design flaws:
1. **Imbalanced push/pop** leading to stack growth
2. **Wrong URLs pushed** (edit pages instead of origin pages)
3. **Confusing skip logic** that's hard to reason about
4. **Missing visual navigation** (no breadcrumbs for most resources)

The proposed fixes are surgical and low-risk, starting with the two reported bugs and progressively improving the overall navigation system. The phased approach allows for incremental testing and validation without disrupting production.

Priority should be given to **Phase 1** (fix critical bugs) which can be completed in 1-2 hours with high confidence and immediate user benefit.
