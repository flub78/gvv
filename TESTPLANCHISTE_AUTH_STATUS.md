# testplanchiste Authorization Test Status

## Current Configuration

- **Global Flag**: `$config['use_new_authorization'] = false;` (in `application/config/gvv_config.php`)
- **Per-User Migration Table**: testplanchiste IS in `use_new_authorization` table (id=3)
- **User Roles**: testplanchiste has ONLY `planchiste` role (id=5), does NOT have `user` role (id=1)

## Expected Behavior

When testplanchiste logs in:

1. ✅ Global flag is FALSE → should check per-user migration table
2. ✅ testplanchiste IS in migration table → should use NEW authorization system
3. ✅ NEW system checks for 'user' role (id=1)
4. ✅ testplanchiste does NOT have 'user' role → **LOGIN SHOULD BE DENIED**

## What You Should See

### Login Denial Screen
User should see: `application/views/authorization/login_denied.php`

### Log Messages (in `application/logs/log-YYYY-MM-DD.php`)

```
DEBUG - GVV_Controller: Global flag is FALSE, checking per-user migration table for 'testplanchiste'
DEBUG - GVV_Controller: Per-user table query returned 1 rows
DEBUG - GVV_Controller: User 'testplanchiste' (ID: 313) using NEW authorization (per-user migration)
ERROR - GVV_Controller: User 'testplanchiste' (ID: 313) denied login - no 'user' role (id=1) for section 1
ERROR - GVV_Controller: User has other roles: 5 but NOT 'user' role (1)
INFO  - GVV: Logout: testplanchiste
```

## How to Test

1. **Logout** if currently logged in as testplanchiste
2. **Login** as testplanchiste
3. **Check logs** immediately:
   ```bash
   tail -30 application/logs/log-$(date +%Y-%m-%d).php | grep -i "testplanchiste\|GVV_Controller"
   ```

## Database Verification

Run this to verify database state:
```bash
php test_testplanchiste_auth.php
```

Expected output:
```
✓ Step 1: testplanchiste IS in use_new_authorization table
  → Should use NEW authorization system

✗ Step 2: testplanchiste does NOT have 'user' role (id=1) for section 1
  → Login should be DENIED

  Roles testplanchiste DOES have for section 1:
    - Role ID 5: planchiste

=== FINAL VERDICT ===
Authorization System: NEW (per-user migration)
Login Status: DENIED (missing 'user' role)
```

## Troubleshooting

If login is NOT denied, check:

1. **Config file not cached**: `application/config/gvv_config.php` shows `$config['use_new_authorization'] = false;`
2. **Table exists**: `mysql -u gvv_user -p gvv2 -e "SELECT * FROM use_new_authorization WHERE username='testplanchiste'"`
3. **Welcome controller extends Gvv_Controller**: Check line 29 of `application/controllers/welcome.php`
4. **Logs show execution**: Check for debug messages in logs

## Next Steps

After confirming login denial works for testplanchiste:

1. **Grant 'user' role** to testplanchiste:
   ```sql
   INSERT INTO user_roles_per_section (user_id, section_id, types_roles_id, granted_at)
   VALUES (313, 1, 1, NOW());
   ```

2. **Test login succeeds** (should now work)

3. **Remove from migration table** to test legacy system:
   ```sql
   DELETE FROM use_new_authorization WHERE username = 'testplanchiste';
   ```

4. **Test legacy login** (should work with legacy system)
