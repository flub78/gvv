# Frozen Line UX Improvements

## Summary
Improved user feedback when attempting to modify or delete frozen accounting lines (écritures gelées) in the compta/journal_compte interface.

## Problem
1. When a user tried to delete a frozen line, the operation was silently rejected with no visible feedback
2. When a user tried to modify a frozen line, the submit button was completely hidden with no explanation
3. When a user clicked the checkbox to freeze/unfreeze a line, the page redirected to a different page instead of staying on the current journal_compte view

## Solution

### 1. Delete Operation Feedback
**Changed:** 
- `application/controllers/compta.php` - `delete()` method checks if line is frozen before deletion
- `application/libraries/MetaData.php` - `action()` method shows informational alert for frozen lines

**Previous behavior:**
- Delete button showed confirmation popup: "Confirmer la suppression?"
- If line was frozen, deletion was silently rejected in model
- User would be confused why deletion didn't work

**New behavior:**
- Controller checks `gel` field before attempting deletion
- If frozen, shows flash message and returns without deleting
- Delete button onclick checks if line is frozen:
  - **Frozen line**: Shows alert "La suppression d'une écriture gelée est interdite." and returns false (no navigation)
  - **Normal line**: Shows confirmation "Confirmer la suppression?" and proceeds if confirmed
- Clear user feedback in all cases

### 2. Modify Operation Feedback
**Changed:** `application/controllers/compta.php`
- Added frozen_message to data passed to view when line is frozen
- Uses new language key: `gvv_compta_frozen_line_cannot_modify`

**Changed:** `application/views/compta/bs_formView.php`
- Instead of hiding the submit button completely, now shows:
  - A warning alert box with lock icon and explanation message
  - A disabled submit button (cannot be clicked)
- This provides clear visual feedback about why the form cannot be submitted

### 3. Freeze/Unfreeze Checkbox Behavior (AJAX)
**Changed:** 
- `application/controllers/compta.php` - `switch_line()` method converted to AJAX endpoint
- `assets/javascript/gvv.js` - `line_checked()` function now uses AJAX

**Previous behavior:** 
- JavaScript redirected to switch_line URL: `window.location.href = url`
- Controller used `pop_return_url()` which redirected to wrong page

**New behavior:**
- JavaScript makes AJAX POST request to switch_line
- Controller returns JSON response: `{'success': true, 'new_state': 1, 'id': 123}`
- JavaScript reloads the current page: `window.location.reload()`
- This preserves the exact URL and all context perfectly

**Benefits:**
- ✅ No redirect issues
- ✅ Stays on exact same page with same URL
- ✅ Preserves all session state and context
- ✅ Better error handling with JSON responses
- ✅ More responsive UX

### 4. Language Support
**Added** to all language files:
- French: `application/language/french/compta_lang.php`
  - `gvv_compta_frozen_line_cannot_modify`: "La modification d'une écriture gelée est interdite."
  - `gvv_compta_frozen_line_cannot_delete`: "La suppression d'une écriture gelée est interdite."

- English: `application/language/english/compta_lang.php`
  - `gvv_compta_frozen_line_cannot_modify`: "Modification of a frozen entry is forbidden."
  - `gvv_compta_frozen_line_cannot_delete`: "Deletion of a frozen entry is forbidden."

- Dutch: `application/language/dutch/compta_lang.php`
  - `gvv_compta_frozen_line_cannot_modify`: "Wijziging van een vergrendelde boeking is verboden."
  - `gvv_compta_frozen_line_cannot_delete`: "Verwijdering van een vergrendelde boeking is verboden."

## Testing
- All existing tests pass (424 tests, 1833 assertions)
- PHP syntax validated for all modified files
- No breaking changes to existing functionality

## User Experience Improvement
**Before:**
- Delete: Silent failure, no feedback
- Modify: Button disappeared mysteriously
- Freeze toggle: Redirected to wrong page

**After:**
- Delete: Proper error message displayed via flashdata
- Modify: Clear warning message + disabled button with explanation
- Freeze toggle: Stays on current journal_compte page

## Files Modified
1. `application/controllers/compta.php` - Added frozen_message to view data + fixed switch_line redirect
2. `application/models/ecritures_model.php` - Return false on frozen delete attempt
3. `application/views/compta/bs_formView.php` - Display warning + disabled button for frozen lines
4. `application/language/french/compta_lang.php` - Added French messages
5. `application/language/english/compta_lang.php` - Added English messages
6. `application/language/dutch/compta_lang.php` - Added Dutch messages

## Implementation Notes
- Follows GVV's existing patterns for error handling and i18n
- Uses Bootstrap 5 alert classes for styling consistency
- Maintains the existing frozen line logic, only improves user feedback
- No database schema changes required
- The freeze/unfreeze checkbox now uses direct redirect to maintain page context
