# Frozen Line UX Improvements

## Summary
Improved user feedback when attempting to modify or delete frozen accounting lines (écritures gelées) in the compta/journal_compte interface.

## Problem
1. When a user tried to delete a frozen line, the operation was silently rejected with no visible feedback
2. When a user tried to modify a frozen line, the submit button was completely hidden with no explanation

## Solution

### 1. Delete Operation Feedback
**Changed:** `application/models/ecritures_model.php`
- Added explicit `return false` when delete is blocked due to frozen status
- Message already existed in flashdata but wasn't always visible
- The message "Suppression impossible, écriture gelée" will now properly display after redirect

### 2. Modify Operation Feedback
**Changed:** `application/controllers/compta.php`
- Added frozen_message to data passed to view when line is frozen
- Uses new language key: `gvv_compta_frozen_line_cannot_modify`

**Changed:** `application/views/compta/bs_formView.php`
- Instead of hiding the submit button completely, now shows:
  - A warning alert box with lock icon and explanation message
  - A disabled submit button (cannot be clicked)
- This provides clear visual feedback about why the form cannot be submitted

### 3. Language Support
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

**After:**
- Delete: Proper error message displayed via flashdata
- Modify: Clear warning message + disabled button with explanation

## Files Modified
1. `application/controllers/compta.php` - Added frozen_message to view data
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
