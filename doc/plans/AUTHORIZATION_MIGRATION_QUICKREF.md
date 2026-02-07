# Authorization Migration - Quick Reference

**Last Updated**: 2025-10-26  
**Current Phase**: M2 (User Role Setup)  
**Target**: Production in 2-3 weeks via feature flag

---

## Current Status

✅ **Infrastructure Complete** (Phases 0-7):
- Database tables created
- Authorization library implemented
- Code-based API ready (`require_roles()`, `allow_roles()`)
- 213/213 tests passing
- Feature flag configured (`use_new_authorization = FALSE`)

⏳ **Next Step**: Grant user roles to all existing users

---

## Migration Path: Feature Flag Approach

### Phase M2: User Role Setup (THIS WEEK - 1-2 days)

**Action 1**: Run SQL script to grant 'user' role
```bash
mysql -h localhost -u gvv_user -p gvv2 < grant_user_roles_simple.sql
```

**Expected Result**:
- Section 1 (Planeur): 289 users (already done)
- Section 2 (ULM): +61 users
- Section 3 (Avion): +45 users
- Section 4 (Général): 278 users (already done)

**Action 2**: Assign specialized roles via UI
- Navigate: Admin → Club Admin → Gestion des autorisations
- Assign: planchiste, ca, bureau, tresorier, club-admin roles
- Estimated time: 2-3 hours

---

### Phase M3: Testing on Staging (NEXT WEEK - 3-5 days)

**Action 1**: Enable flag on test environment
```php
// In application/config/gvv_config.php (TEST SERVER ONLY)
$config['use_new_authorization'] = TRUE;
```

**Action 2**: Test with different user roles
- [ ] Basic user (role: user)
- [ ] Flight logger (role: planchiste)
- [ ] Board member (role: ca)
- [ ] Treasurer (role: tresorier)
- [ ] Administrator (role: club-admin)

**Action 3**: Verify
- [ ] Authorized access works
- [ ] Unauthorized access denied
- [ ] Audit log populated
- [ ] No errors in logs
- [ ] Performance acceptable

---

### Phase M4: Production Pilot (OPTIONAL - 1 weekend)

**Friday evening** (low traffic):
```php
// In application/config/gvv_config.php (PRODUCTION)
$config['use_new_authorization'] = TRUE;
```

Monitor for 2-4 hours. If issues arise:
```php
// Quick rollback - just flip the flag
$config['use_new_authorization'] = FALSE;
```

---

### Phase M5: Full Production Cutover (WEEK 3-4)

**Pre-cutover checklist**:
- [ ] All users have roles in `user_roles_per_section`
- [ ] Test environment fully validated
- [ ] Database backup completed
- [ ] Rollback plan documented

**Monday morning**:
```php
// In application/config/gvv_config.php (PRODUCTION - PERMANENT)
$config['use_new_authorization'] = TRUE;
```

**Monitor intensively**: 48 hours

---

## Rollback Procedures

### Emergency Rollback (< 5 minutes)
```php
// Just flip the flag back
$config['use_new_authorization'] = FALSE;
```

**No data loss** - both systems use same tables!

---

## Key SQL Queries

### Check user role distribution
```sql
SELECT s.nom, tr.nom, COUNT(*) as user_count
FROM user_roles_per_section urps
JOIN sections s ON urps.section_id = s.id
JOIN types_roles tr ON urps.types_roles_id = tr.id
WHERE urps.revoked_at IS NULL
GROUP BY s.nom, tr.nom
ORDER BY s.id, tr.id;
```

### Check recent authorization decisions
```sql
SELECT * FROM authorization_audit_log 
WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
ORDER BY created_at DESC
LIMIT 100;
```

### Preview users who need roles
```sql
SELECT DISTINCT u.id, u.username, c.club as section_id
FROM comptes c
JOIN users u ON c.pilote = u.username
WHERE c.codec = '411' AND c.actif = 1 AND c.pilote IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM user_roles_per_section urps
    WHERE urps.user_id = u.id AND urps.types_roles_id = 1 
    AND urps.section_id = c.club AND urps.revoked_at IS NULL
)
ORDER BY c.club, u.username;
```

---

## Timeline

| Week | Phase | Action | Flag Status |
|------|-------|--------|-------------|
| **Week 1** | M2 | Grant user roles, assign specialized roles | FALSE |
| **Week 2** | M3 | Test on staging environment | TRUE (test only) |
| **Week 3** | M4 | Optional production pilot (weekend) | TRUE (prod, trial) |
| **Week 3-4** | M5 | Full production cutover | TRUE (prod, permanent) |

**Total Time to Production**: 2-3 weeks

---

## Role Reference

| Role ID | Role Name | Description |
|---------|-----------|-------------|
| 1 | user | Basic member access |
| 2 | auto_planchiste | Auto-authorized flight logger |
| 5 | planchiste | Flight logger (full permissions) |
| 6 | ca | Board member (Conseil d'Administration) |
| 7 | bureau | Office member |
| 8 | tresorier | Treasurer |
| 9 | super-tresorier | Super treasurer |
| 10 | club-admin | Full administrator |

---

## Files Reference

- **Feature Flag Config**: `application/config/gvv_config.php`
- **SQL Script**: `grant_user_roles_simple.sql`
- **Authorization UI**: http://gvv.net/authorization/user_roles
- **Full Plan**: `doc/plans_and_progress/2025_authorization_refactoring_plan.md`
- **Developer Guide**: `doc/development/code_based_permissions.md`

---

## Support

Questions? Check:
1. Full plan document (this directory)
2. PRD: `doc/prds/2025_authorization_refactoring_prd.md`
3. Code: `application/libraries/Gvv_Authorization.php`

---

**Status Dashboard**: See full plan for detailed metrics and progress tracking.
