# Email List Duplicate Name Validation - Implementation Summary

## Issue Description

When attempting to create an email list with an existing name at `http://gvv.net/email_lists/create`:
1. The page was not reloaded immediately
2. A generic error message was shown: "Erreur lors de la création de la liste"
3. The user had no indication that the problem was a duplicate name

## Solution Implemented

Added CodeIgniter form validation to check for duplicate names **before** attempting database insertion, with a specific error message indicating the name is already taken.

## Changes Made

### 1. Model: `application/models/email_lists_model.php`

Added new method to check if a list name exists:

```php
/**
 * Check if a list name already exists
 *
 * @param string $name List name to check
 * @param int $exclude_id Optional ID to exclude (for updates)
 * @return bool TRUE if name exists, FALSE otherwise
 */
public function name_exists($name, $exclude_id = NULL)
```

This method:
- Checks if a name exists in the `email_lists` table
- Supports exclusion of a specific ID (for update operations)
- Returns TRUE if duplicate found, FALSE otherwise

### 2. Controller: `application/controllers/email_lists.php`

#### Added validation callback method:

```php
/**
 * Validation callback - Check if list name is unique
 *
 * @param string $name List name to validate
 * @return bool TRUE if unique, FALSE if duplicate
 */
public function check_name_unique($name)
```

#### Updated `store()` method:
Changed validation rule from:
```php
$this->form_validation->set_rules('name', ..., 'required|max_length[255]');
```

To:
```php
$this->form_validation->set_rules('name', ..., 'required|max_length[255]|callback_check_name_unique');
```

#### Updated `update()` method:
- Added same validation callback to prevent duplicates during updates
- Uses `$this->_update_list_id` to exclude current list when checking for duplicates
- Allows keeping the same name when updating other fields

### 3. Language Files

Added specific error message in all three languages:

**French** (`application/language/french/email_lists_lang.php`):
```php
$lang['email_lists_name_duplicate'] = 'Ce nom de liste existe déjà. Veuillez choisir un nom différent.';
```

**English** (`application/language/english/email_lists_lang.php`):
```php
$lang['email_lists_name_duplicate'] = 'This list name already exists. Please choose a different name.';
```

**Dutch** (`application/language/dutch/email_lists_lang.php`):
```php
$lang['email_lists_name_duplicate'] = 'Deze lijstnaam bestaat al. Kies een andere naam.';
```

## How It Works

### Before Fix:
1. User submits form with duplicate name
2. Controller attempts database INSERT
3. Database rejects due to UNIQUE constraint
4. Generic error returned: "Erreur lors de la création de la liste"
5. User doesn't know what went wrong

### After Fix:
1. User submits form with duplicate name
2. Form validation runs `check_name_unique()` callback
3. Model checks database for existing name
4. Validation fails with specific message
5. Form re-displays immediately with:
   - Specific error: "Ce nom de liste existe déjà. Veuillez choisir un nom différent."
   - Name field highlighted in red
   - All form values preserved
6. Database INSERT never attempted

## Benefits

1. **Immediate feedback**: Validation happens before database attempt
2. **Specific error message**: User knows exactly what the problem is
3. **Better UX**: Form stays on page with values preserved
4. **Works for both create and update**: Validation applied consistently
5. **Smart update handling**: Can keep same name when updating other fields

## Testing

### Manual Test
Run the smoke test script:
```bash
./test_email_list_duplicate_name_manual.sh
```

### Test Scenarios

1. **Create with duplicate name** → Shows specific error
2. **Create with unique name** → Succeeds
3. **Update keeping same name** → Succeeds (exclusion works)
4. **Update to duplicate name** → Shows specific error

## Database Schema Reference

The `email_lists` table has a UNIQUE constraint on the `name` field:
```sql
ALTER TABLE email_lists ADD UNIQUE INDEX idx_name (name);
ALTER TABLE email_lists MODIFY name VARCHAR(100) NOT NULL COLLATE utf8_bin;
```

This ensures case-sensitive uniqueness at the database level, but our validation provides better UX by catching it earlier.

## Files Modified

- `application/controllers/email_lists.php` - Added validation callback
- `application/models/email_lists_model.php` - Added name_exists() method
- `application/language/french/email_lists_lang.php` - Added error message
- `application/language/english/email_lists_lang.php` - Added error message
- `application/language/dutch/email_lists_lang.php` - Added error message

## Validation

All files validated with PHP linter:
```bash
✓ No syntax errors detected
```
