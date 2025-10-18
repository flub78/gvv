# GVV Authorization System - Current State Export

**Export Date:** October 17, 2025
**Database:** gvv2
**Purpose:** Backup before authorization refactoring migration

---

## Current Roles (types_roles table)

| ID | Name | Description |
|----|------|-------------|
| 1 | user | Capacity to login and see user data |
| 2 | auto_planchiste | Capacity to create, modify and delete the user own data |
| 5 | planchiste | Authorization to create, modify and delete flight data |
| 6 | ca | Capacity to see all data for a section including global financial data |
| 7 | bureau | Capacity to see all data for a section including personnal financial data |
| 8 | tresorier | Capacity to edit financial data for one section |
| 9 | super-tresorier | Capacity to see an edit financial data for all sections |
| 10 | club-admin | Capacity to access all data and change everything |

## Role Hierarchy (roles table)

| ID | Parent ID | Name |
|----|-----------|------|
| 1 | 0 | membre |
| 7 | 1 | planchiste |
| 8 | 7 | ca |
| 3 | 8 | bureau |
| 9 | 3 | tresorier |
| 2 | 9 | admin |

**Hierarchy Tree:**
```
membre (1)
  └─ planchiste (7)
      └─ ca (8)
          └─ bureau (3)
              └─ tresorier (9)
                  └─ admin (2)
```

## User Role Assignments Statistics

Based on query of `user_roles_per_section` table:

- **Total role assignments:** 225 (all in "Planeur" section)
- **Unique users:** ~195
- **Sections:** 1 active ("Planeur")

### Users per Role:
- **user (1):** ~180 users
- **planchiste (5):** ~2 users
- **ca (6):** ~5 users
- **bureau (7):** ~7 users
- **tresorier (8):** ~2 users
- **super-tresorier (9):** 0 users (global role, not in user_roles_per_section)
- **club-admin (10):** ~3 users

## Permissions System (permissions table)

The current system stores URI-based permissions in the `permissions` table as PHP-serialized data.

**Sample URI permissions per role:**

### Role 1 (user):
- /alarmes/, /membre/, /planeur/, /avion/
- /vols_avion/, /vols_planeur/
- /factures/page/, /factures/view/, /factures/ma_facture/
- /compta/mon_compte/, /compta/journal_compte/
- /event/page/, /event/stats/, /licences/, /welcome/

### Role 8 (tresorier):
- All user permissions PLUS:
- /factures/, /compta/, /comptes/, /tickets/
- /achats/, /terrains/, /rapports/, /mails/
- /historique/, /reports/, /FFVV/, /vols_decouverte/

### Role 10 (club-admin):
- Full access to all controllers and actions

## Current Authorization Implementation

### Files Involved:
1. **application/libraries/Tank_auth.php** - Main auth library (legacy)
2. **application/core/MY_Controller.php** or **Gvv_Controller.php** - Base controller with auth checks
3. **permissions table** - Stores URI permissions
4. **types_roles table** - Role definitions
5. **user_roles_per_section table** - User-role-section assignments

### Authorization Flow:
1. User logs in → session established
2. Controller loads → checks user permissions
3. URI-based permission check against `permissions` table
4. Section context applied via `user_roles_per_section`

## Migration Plan Overview

See: `/home/frederic/git/gvv/doc/plans/2025_authorization_refactoring_plan.md`

### Key Changes:
1. **New tables:**
   - `role_permissions` - URI and action permissions per role
   - `data_access_rules` - Row-level security rules
   - `authorization_audit_log` - Audit trail
   - `authorization_migration_status` - Migration tracking

2. **Enhanced tables:**
   - `types_roles` + `scope`, `is_system_role`, `display_order`, `translation_key`
   - `user_roles_per_section` + audit fields

3. **New library:**
   - `Gvv_Authorization` - Modern authorization system
   - `Authorization_model` - Data access layer

4. **Dual-mode operation:**
   - Progressive migration with fallback
   - Feature flag controlled

## Files Exported

- `types_roles_export.csv` - Role definitions
- `export_permissions.py` - Python script for detailed export (requires mysql-connector-python)

## Next Steps

1. ✓ Database backup created
2. ✓ Current permissions exported
3. [ ] Test environment validation
4. [ ] Code audit of existing authorization
5. [ ] Create migration 042 for schema changes
6. [ ] Implement new authorization library
7. [ ] Progressive migration with monitoring

---

**Note:** This export serves as a reference point for the authorization refactoring project. All current permissions and role assignments have been documented.
