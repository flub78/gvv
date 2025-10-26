# Per-User Authorization Migration Guide

**Document Version:** 1.0
**Date:** 2025-10-26
**Audience:** System Administrators

---

## Overview

The new per-user migration system allows you to test the new authorization system with specific users before rolling it out to everyone. This provides a safe, gradual migration path with instant rollback capabilities.

---

## Migration Table

### Table Structure

```sql
CREATE TABLE use_new_authorization (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes VARCHAR(500) NULL
);
```

### Purpose

Users listed in this table will use the **new authorization system**, while all others continue using the **legacy system**.

---

## Decision Logic

The system determines which authorization to use based on this priority:

1. **Per-User Check**: If username exists in `use_new_authorization` → **New System**
2. **Global Flag**: If `$config['use_new_authorization'] = TRUE` → **New System for All**
3. **Default**: If flag = FALSE and user not in table → **Legacy System**

---

## Running the Migration

### Step 1: Apply Database Migration

1. Login as administrator
2. Navigate to: **Admin → Migration**
3. Select migration level **48**
4. Click **Migrate**

This creates the `use_new_authorization` table.

---

## Phase M2: Development Testing (2-3 days)

### Add Dev Users

```sql
INSERT INTO use_new_authorization (username, notes) VALUES
    ('dev_user1', 'Phase M2 - Development testing'),
    ('dev_user2', 'Phase M2 - Development testing');
```

### Verify

```sql
SELECT username, notes, created_at
FROM use_new_authorization
ORDER BY created_at;
```

### Test

1. Login as `dev_user1`
2. Navigate the application
3. Check logs: `tail -f application/logs/log-*.php | grep "GVV_Controller"`
4. You should see: `User 'dev_user1' using NEW authorization (per-user migration)`

### Rollback (if needed)

Remove single user:
```sql
DELETE FROM use_new_authorization WHERE username = 'dev_user1';
```

Remove all dev users:
```sql
TRUNCATE use_new_authorization;
```

---

## Phase M3: Production Pilot Testing (1-2 weeks)

### Select Pilot Users

Choose 5-10 experienced users with different roles:
- 2-3 basic users (role: user)
- 2-3 flight loggers (role: planchiste)
- 1-2 board members (role: ca)
- 1 administrator (role: club-admin)

### Add Pilot Users

```sql
INSERT INTO use_new_authorization (username, notes) VALUES
    ('fpeignot', 'Phase M3 - Production pilot (admin)'),
    ('agnes', 'Phase M3 - Production pilot (treasurer)'),
    ('pilot_user3', 'Phase M3 - Production pilot (planchiste)'),
    ('pilot_user4', 'Phase M3 - Production pilot (ca)'),
    ('pilot_user5', 'Phase M3 - Production pilot (user)');
```

### Notify Pilot Users

Email template:
```
Subject: Testing New Authorization System

Bonjour,

You have been selected to test the new authorization system for GVV.

What this means:
- You will use the new system while others continue with the current one
- Functionality should be identical - you shouldn't notice any difference
- If you encounter any access issues, please report them immediately

Contact: [your email]

Thank you for helping us improve GVV!
```

### Monitor Daily

```sql
-- Check pilot user activity
SELECT username, notes, created_at
FROM use_new_authorization;

-- Check for access denials
SELECT * FROM authorization_audit_log
WHERE decision = 'denied'
  AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY created_at DESC;

-- Check application logs
tail -f application/logs/log-*.php | grep -E "GVV_Controller|authorization"
```

### Rollback Individual User

If one pilot user has issues:
```sql
DELETE FROM use_new_authorization WHERE username = 'problematic_user';
```

The user immediately reverts to legacy system, other pilots continue testing.

### Rollback All Pilots

If systemic issues found:
```sql
TRUNCATE use_new_authorization;
```

All pilot users revert to legacy system.

---

## Phase M4: Global Migration (1 week)

### Pre-Cutover Checklist

- [ ] Phase M3 completed successfully (1-2 weeks)
- [ ] All pilot user feedback addressed
- [ ] No access denial issues in logs
- [ ] All users have roles in `user_roles_per_section`
- [ ] Database backup completed
- [ ] Rollback plan ready

### Enable Global Flag

Edit `application/config/gvv_config.php`:

```php
$config['use_new_authorization'] = TRUE;
```

**Effect**: ALL users immediately switch to new system. The `use_new_authorization` table is now ignored.

### Post-Cutover Monitoring

**Day 1-2**: Check logs every 2 hours
```bash
tail -f application/logs/log-*.php | grep -E "GVV_Controller|authorization"
```

**Day 3-7**: Check logs daily
```sql
SELECT COUNT(*) as denials, DATE(created_at) as date
FROM authorization_audit_log
WHERE decision = 'denied'
GROUP BY DATE(created_at)
ORDER BY date DESC
LIMIT 7;
```

### Emergency Rollback

If major issues arise:

Edit `application/config/gvv_config.php`:
```php
$config['use_new_authorization'] = FALSE;
```

**Effect**: ALL users immediately revert to legacy system.
**Time**: < 1 minute

---

## Phase M5: Cleanup (After 30 days)

Once the new system has been stable for 30 days:

### Drop Migration Table

```sql
-- No longer needed - global flag controls everything
DROP TABLE use_new_authorization;
```

### Archive Legacy Permissions

```sql
-- Keep for reference but not used
RENAME TABLE role_permissions TO role_permissions_legacy_backup;
```

---

## Troubleshooting

### User Reports "Access Denied"

1. **Check which system they're using**:
   ```sql
   SELECT * FROM use_new_authorization WHERE username = 'username';
   ```

2. **Check their roles**:
   ```sql
   SELECT u.username, s.nom as section, tr.nom as role
   FROM users u
   JOIN user_roles_per_section urps ON u.id = urps.user_id
   JOIN sections s ON urps.section_id = s.id
   JOIN types_roles tr ON urps.types_roles_id = tr.id
   WHERE u.username = 'username' AND urps.revoked_at IS NULL;
   ```

3. **Check audit log**:
   ```sql
   SELECT * FROM authorization_audit_log
   WHERE user_id = (SELECT id FROM users WHERE username = 'username')
   ORDER BY created_at DESC
   LIMIT 20;
   ```

### How to Check Current Authorization Mode

Check logs when user logs in:
```bash
tail -f application/logs/log-*.php | grep "GVV_Controller"
```

You'll see one of:
- `using NEW authorization (per-user migration)` - User in table
- `using NEW authorization (global flag)` - Global flag = TRUE
- `using LEGACY authorization` - Global flag = FALSE, user not in table

---

## SQL Quick Reference

### View pilot users
```sql
SELECT username, notes, created_at FROM use_new_authorization;
```

### Add pilot user
```sql
INSERT INTO use_new_authorization (username, notes)
VALUES ('username', 'Phase M3 pilot');
```

### Remove pilot user (rollback)
```sql
DELETE FROM use_new_authorization WHERE username = 'username';
```

### Remove all pilot users (abort pilot)
```sql
TRUNCATE use_new_authorization;
```

### Count users by authorization system
```sql
SELECT
    CASE
        WHEN u.username IN (SELECT username FROM use_new_authorization)
        THEN 'New System (Pilot)'
        ELSE 'Legacy System'
    END as system,
    COUNT(*) as user_count
FROM users u
GROUP BY system;
```

---

## Support

For questions or issues:
- Check: `doc/plans_and_progress/2025_authorization_refactoring_plan.md`
- Check: `doc/prds/2025_authorization_refactoring_prd.md`
- Logs: `application/logs/log-*.php`
- Audit: Table `authorization_audit_log`

---

**Remember**: The per-user migration table provides safe, granular testing. Start small (2-3 dev users), expand cautiously (5-10 pilots), then flip the global flag when confident.
