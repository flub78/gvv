# Navigation Phase 1 Bug Fix - Test Cases

**Date**: 2025-11-27  
**Bug**: Accounting line modification return navigation (compta.php line 351)  
**Status**: Pre-fix testing guide

---

## Bug Description

When editing an accounting line and clicking "Enregistrer" (Save), the user is redirected to an unexpected page instead of returning to the journal view where they started.

**Root Cause**: `pop_return_url(1)` uses skip parameter that causes incorrect stack unwinding.

**Code Location**: `application/controllers/compta.php` line 351

---

## Test Case 1: Edit from Journal - Single Navigation

### Setup
1. Login to GVV
2. Navigate to **Comptabilité → Journal d'un compte**
3. Select account **512 - Banque** (or any account)
4. Note the URL: `http://gvv.net/compta/journal_compte/512`

### Steps
1. Locate any accounting line in the journal
2. Click the **Edit** (✏️) button for that line
3. The edit form opens with URL: `http://gvv.net/compta/edit/12345`
4. Modify any field (e.g., change the amount or description)
5. Click **"Enregistrer"** (Save)

### Current (Buggy) Behavior
- User is redirected to an **unexpected page**
- Possible redirections:
  - May go to a different account's journal
  - May go to compta/page (general accounting page)
  - May go to a completely unrelated page if stack is polluted

### Expected (Correct) Behavior
- User is redirected back to **`compta/journal_compte/512`**
- The journal view shows the updated line
- User can verify their change in the same context where they started

### Why It Fails
```php
// Current code (line 351)
$this->pop_return_url(1);  // Skip=1 causes wrong URL to be popped
```

The stack state:
```
Stack before edit:
  [0] = compta/journal_compte/512

User clicks Edit → pushes edit URL:
  [0] = compta/journal_compte/512
  [1] = compta/edit/12345          ← Current page

User clicks Save → pop_return_url(1):
  - Pops once without redirecting (skip=1)
  - Stack becomes: [0] = compta/journal_compte/512
  - Then enters while loop, may find wrong URL or redirect incorrectly
```

---

## Test Case 2: Edit from Journal - Multiple Navigation Levels

### Setup
1. Login to GVV
2. Navigate through menu: **Comptabilité → Ecritures**
3. URL: `http://gvv.net/compta/page`
4. Click filter/search to find specific entries
5. Click on an entry to view details or navigate to its journal
6. URL: `http://gvv.net/compta/journal_compte/411`

### Steps
1. Click Edit on an accounting line
2. Modify a field
3. Click **"Enregistrer"**

### Current (Buggy) Behavior
- May return to `compta/page` instead of `journal_compte/411`
- Navigation history is confused
- User loses their filtered/specific account context

### Expected (Correct) Behavior
- Return to **`compta/journal_compte/411`**
- User sees their edit in the journal context

---

## Test Case 3: Edit from Balance Sheet Search

### Setup
1. Navigate to **Comptabilité → Balance**
2. URL: `http://gvv.net/comptes/balance`
3. Search/filter for specific accounts
4. Click **"Journal"** link for account 512
5. URL: `http://gvv.net/compta/journal_compte/512`

### Steps
1. Click Edit on any line
2. Modify data
3. Click **"Enregistrer"**

### Current (Buggy) Behavior
- Stack may contain: `[comptes/balance, compta/journal_compte/512, compta/edit/12345]`
- With `pop_return_url(1)`, behavior is unpredictable
- May return to balance instead of journal

### Expected (Correct) Behavior
- Return to **`compta/journal_compte/512`**
- This is where the edit was initiated, not balance (which was 2 levels back)

---

## Test Case 4: Create and Continue (Control Test)

### Purpose
Verify that "Créer et continuer" button still works correctly after the fix.

### Setup
1. Navigate to **Comptabilité → Nouvelle écriture**
2. URL: `http://gvv.net/compta/create`

### Steps
1. Fill in all required fields for a new accounting line:
   - Date
   - Account 1 (e.g., 512)
   - Account 2 (e.g., 411)
   - Amount
   - Description
2. Click **"Créer et continuer"**

### Expected Behavior (Before AND After Fix)
- The entry is created successfully
- Form is **cleared** and ready for next entry
- Success message displayed: "Écriture [details] créée avec succés."
- User remains on create form
- URL stays: `http://gvv.net/compta/create`

### Why This Should Still Work
```php
// Code at line 333-344 (unaffected by our fix)
if ($button != "Créer") {
    // Créer et continuer, on reste sur la page de création
    $this->data['message'] = '<div class="text-success">' . $msg . '</div>';
    $this->form_static_element($action);
    load_last_view($this->form_view, $this->data);
    return;  // Doesn't use pop_return_url at all
}
```

---

## Test Case 5: Create and Return (Control Test)

### Setup
1. Navigate to **Comptabilité → Journal d'un compte → 512**
2. Click **"Nouvelle écriture"** button
3. URL: `http://gvv.net/compta/create`

### Steps
1. Fill in the form
2. Click **"Créer"** (not "Créer et continuer")

### Current Behavior
```php
// Code at line 345-347
$target = "compta/journal_compte/" . $processed_data['compte1'];
redirect($target);
```

### Expected Behavior (Before AND After Fix)
- Entry is created
- User is redirected to **`compta/journal_compte/512`** (compte1 from the entry)
- This is **hardcoded** and doesn't use URL stack, so should work correctly

---

## Test Case 6: Multi-Tab Scenario

### Purpose
Verify that fix doesn't break multi-tab workflows.

### Setup
1. **Tab 1**: Open journal for account 512
   - URL: `http://gvv.net/compta/journal_compte/512`
2. **Tab 2**: Open journal for account 411
   - URL: `http://gvv.net/compta/journal_compte/411`

### Steps
1. **Tab 1**: Click Edit on a line from account 512
2. **Tab 2**: Click Edit on a line from account 411
3. **Tab 1**: Modify and click "Enregistrer"
4. **Tab 2**: Modify and click "Enregistrer"

### Expected Behavior (After Fix)
- **Tab 1**: Returns to `compta/journal_compte/512` ✅
- **Tab 2**: Returns to `compta/journal_compte/411` ✅
- Each tab maintains its own navigation context

### Current (Buggy) Behavior
- Unpredictable - stack may be shared/polluted across tabs
- Tabs may interfere with each other's navigation

---

## Test Case 7: Frozen Line Edit Attempt

### Setup
1. Navigate to **Comptabilité → Journal**
2. Find or create a line that is **frozen** (gel checkbox checked)

### Steps
1. Click Edit on the frozen line
2. Form opens in **read-only mode** (Visualisation)
3. Try to modify (fields are disabled)
4. Click browser back button or breadcrumb

### Expected Behavior (Before AND After Fix)
- Form opens in read-only mode
- No save button available (frozen lines cannot be modified)
- Message displayed: "Écriture gelée - modification impossible"
- Navigation should still work correctly

### Code Reference
```php
// Line 84-89
if ($is_frozen) {
    $this->form_static_element(VISUALISATION);
    $this->data['frozen_message'] = $this->lang->line('gvv_compta_frozen_line_cannot_modify');
} else {
    $this->form_static_element(MODIFICATION);
}
```

---

## How to Verify the Fix

### Before Fix (Expected Failures)
Run Test Cases 1, 2, 3, and 6:
- Document where each test redirects (screenshot URLs)
- Note which tests fail to return to the correct page

### After Fix (Expected Success)
Apply the fix:
```php
// Change line 351 from:
$this->pop_return_url(1);

// To:
$this->pop_return_url();
```

Re-run all test cases:
- ✅ Test Cases 1, 2, 3: Should now return to journal correctly
- ✅ Test Case 4, 5: Should still work (unaffected by change)
- ✅ Test Case 6: Multi-tab should work correctly
- ✅ Test Case 7: Read-only frozen lines still work

---

## Stack State Analysis

### Understanding the URL Stack

The URL stack is stored in session: `$_SESSION['return_url_stack']`

To debug, add this temporarily in the controller:
```php
// Add before pop_return_url() to see stack state
gvv_debug("URL Stack before pop: " . print_r($this->session->userdata('return_url_stack'), true));
```

### Expected Stack States

**Scenario: Edit from Journal**
```
User at:     compta/journal_compte/512
Stack:       []

Clicks Edit (page() method pushes):
Stack:       [compta/journal_compte/512]

Edit page loads (edit() method pushes):
Stack:       [compta/journal_compte/512, compta/edit/12345]

User saves (pop_return_url(1) - BUGGY):
- Pops once: compta/edit/12345 (discarded due to skip=1)
- Stack:     [compta/journal_compte/512]
- While loop pops: compta/journal_compte/512
- BUT: Logic may be confused and redirect wrong!

User saves (pop_return_url() - FIXED):
- Pops once: compta/edit/12345
- Stack:     [compta/journal_compte/512]  
- While loop pops: compta/journal_compte/512
- Redirects to: compta/journal_compte/512 ✅
```

---

## Regression Testing

After applying the fix, also test these workflows to ensure nothing broke:

1. **Aircraft Management** (uses pop_return_url):
   - Navigate to Aéronefs → Edit → Save
   - Should return to aircraft list

2. **Glider Flights** (uses pop_return_url):
   - Navigate to Vols planeur → Edit → Save
   - Should return to flight list

3. **Purchases** (uses pop_return_url):
   - Navigate to Achats → Edit → Save
   - Should return to purchase list

---

## Success Criteria

- [ ] All test cases 1-7 pass
- [ ] No regression in other controllers using pop_return_url
- [ ] Multi-tab navigation works correctly
- [ ] URL stack doesn't grow unbounded (verify with session inspector)
- [ ] Users report navigation is now predictable and correct

---

## Notes

- Test with different user roles (planchiste, ca, tresorier) to ensure permissions don't affect navigation
- Test on different browsers (Firefox, Chrome) to rule out session handling differences
- Clear browser cache between tests to avoid stale JavaScript
- Monitor `application/logs/log-YYYY-MM-DD.php` for any navigation-related errors
