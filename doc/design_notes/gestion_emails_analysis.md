# Analysis - User Authorization Architecture for Email Lists

**Date:** 2025-10-31
**Context:** Email management system role-based selection mechanism
**Related Documents:**
- [PRD](../prds/gestion_emails.md)
- [Design](gestion_emails_design.md)
- [Implementation Plan](../plans_and_progress/gestion_emails_plan.md)

---

## Database Schema Analysis

### Current Architecture

GVV uses a **dual-table user system**:

1. **`membres`** (294 records) - Member profile data
   - Primary key: `mlogin` (VARCHAR(25)) - member login ID
   - Contains: name, email, address, qualifications, status (`actif`)
   - Email field: `memail` (can be NULL)

2. **`users`** (298 records) - Authentication accounts
   - Primary key: `id` (INT auto_increment)
   - Contains: username, password, email, login tracking
   - Username field: `username` (VARCHAR(25))

3. **Relationship**: `membres.mlogin = users.username` (284 matches)
   - Not all membres have user accounts (some are inactive/external)
   - Some users may not have membre records (system accounts)

### Authorization System

**Table: `user_roles_per_section`**
```sql
- id (INT PK)
- user_id (INT) → users.id
- types_roles_id (INT) → types_roles.id
- section_id (INT) → sections.id
- granted_by (INT, nullable) → users.id
- granted_at (DATETIME)
- revoked_at (DATETIME, nullable)
- notes (TEXT, nullable)
```

**Key insight:** Authorization is attached to `users.id` (INT), NOT to `membres.mlogin` (VARCHAR).

### Role Types (`types_roles`)

System roles with scope and hierarchy:

| ID | Name | Scope | Description |
|----|------|-------|-------------|
| 1 | user | section | Login and view own data |
| 2 | auto_planchiste | section | Create/modify own flight data |
| 5 | planchiste | section | Create/modify/delete flight data |
| 6 | ca | section | View all section data + global financials |
| 7 | bureau | section | View all section data + personal financials |
| 8 | tresorier | section | Edit financial data for section |
| 9 | super-tresorier | global | Edit financial data for all sections |
| 10 | club-admin | global | Full access to everything |

**Scope:**
- `section` - Role applies within one section
- `global` - Role applies across all sections

### Sections (`sections`)

| ID | Name | Acronym | Color |
|----|------|---------|-------|
| 1 | Planeur | | #b6dafb |
| 2 | ULM | | #fdc16d |
| 3 | Avion | | #d9d9d9 |
| 4 | Général | | #d2fd81 |

---

## Issue: Primary Key Type Mismatch

### The Problem

**You are correct** - there's an architectural inconsistency:

- `membres.mlogin` is **VARCHAR(25)** (string)
- `users.id` is **INT** (integer)
- `user_roles_per_section.user_id` references `users.id` (INT)

**Implication for email lists:**
- To get emails of members with specific roles, we need to:
  1. Query `user_roles_per_section` by role/section → get `user_id` (INT)
  2. Join with `users` on `id` → get `username` (VARCHAR)
  3. Join with `membres` on `mlogin = username` → get `memail`

This is a **3-table join** instead of a direct 2-table join.

### Why This Architecture Exists

Historical evolution:
1. Original system used `membres` as the single source of truth
2. Authentication layer (`users`) added later with auto-increment INT PK (standard practice)
3. Authorization system (`user_roles_per_section`) built on `users.id` for referential integrity
4. Link maintained via `membres.mlogin = users.username` (VARCHAR to VARCHAR)

### Should We Change It?

**No, for these reasons:**

1. **Production system** - 284 active user-membre relationships exist
2. **Foreign key constraints** - `user_roles_per_section` has FK to `users.id`
3. **Migration risk** - Changing PKs requires cascading updates across multiple tables
4. **Code impact** - Entire codebase assumes `users.id` is INT
5. **No functional limitation** - The 3-table join works fine for email selection

---

## Email List Selection Strategy

### Recommended Approach

**Use the existing authorization structure** via `user_roles_per_section`:

```sql
-- Get emails of all users with role "tresorier" in section "Planeur"
SELECT DISTINCT u.email, m.mnom, m.mprenom
FROM user_roles_per_section urps
INNER JOIN users u ON urps.user_id = u.id
LEFT JOIN membres m ON u.username = m.mlogin
INNER JOIN types_roles tr ON urps.types_roles_id = tr.id
INNER JOIN sections s ON urps.section_id = s.id
WHERE urps.revoked_at IS NULL
  AND tr.nom = 'tresorier'
  AND s.id = 1;
```

**Key points:**
- Use `users.email` as primary email source (always populated, required field)
- LEFT JOIN to `membres` to get `memail` as fallback if needed
- Filter by `revoked_at IS NULL` to exclude revoked roles
- Can filter by role name, section, or scope

### Criteria JSON Structure (Updated)

The original design needs adjustment to work with roles per section:

```json
{
  "roles": [
    {
      "types_roles_id": 8,
      "section_id": 1
    },
    {
      "types_roles_id": 7,
      "section_id": 1
    }
  ],
  "member_status": ["actif"],
  "logic": "OR"
}
```

**Fields:**
- `roles` (array of objects): Each with `types_roles_id` and `section_id`
- `member_status` (array): Values from `membres.actif` (1=active, 0=inactive)
- `logic` (string): "AND" or "OR" for combining criteria

### Email Priority

When a user has both `users.email` and `membres.memail`:

1. **Primary:** `users.email` (authentication email, always current)
2. **Secondary:** `membres.memail` (profile email, may be outdated)

**Rationale:** Users actively manage their authentication email for login, so it's more reliable.

---

## Implementation Changes Required

### 1. Update Design Document

**File:** `doc/design_notes/gestion_emails_design.md`

**Section 2.3 - Format JSON des critères** - Replace with:

```json
{
  "roles": [
    {
      "types_roles_id": 8,
      "section_id": 1,
      "role_name": "tresorier",
      "section_name": "Planeur"
    }
  ],
  "member_status": [1],
  "logic": "OR"
}
```

**Section 3.2 - Model methods** - Update:

```php
// Remove these (wrong approach):
public function get_members_by_role($role)
public function get_members_by_section($section_id)

// Add these (correct approach):
public function get_users_by_role_and_section($types_roles_id, $section_id)
public function resolve_role_criteria($roles_array)
```

### 2. Database Schema

**No changes needed** to existing tables. The email list tables remain as designed:

```sql
CREATE TABLE email_lists (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) UNIQUE NOT NULL,
  description TEXT,
  criteria TEXT COMMENT 'JSON: role-based selection',
  created_by INT NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE email_list_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email_list_id INT NOT NULL,
  user_id INT DEFAULT NULL COMMENT 'FK to users.id',
  external_email VARCHAR(255) DEFAULT NULL,
  added_at DATETIME NOT NULL,
  FOREIGN KEY (email_list_id) REFERENCES email_lists(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT chk_member_type CHECK (
    (user_id IS NOT NULL AND external_email IS NULL) OR
    (user_id IS NULL AND external_email IS NOT NULL)
  )
);
```

**Key:** `email_list_members.user_id` correctly references `users.id` (INT).

### 3. Model Implementation

**File:** `application/models/email_lists_model.php`

**Method: `apply_criteria($criteria_json)`**

```php
public function apply_criteria($criteria_json) {
    $criteria = json_decode($criteria_json, true);
    $emails = [];

    if (!empty($criteria['roles'])) {
        foreach ($criteria['roles'] as $role) {
            $this->db->select('DISTINCT u.email, u.id as user_id, m.mnom, m.mprenom');
            $this->db->from('user_roles_per_section urps');
            $this->db->join('users u', 'urps.user_id = u.id', 'inner');
            $this->db->join('membres m', 'u.username = m.mlogin', 'left');
            $this->db->where('urps.types_roles_id', $role['types_roles_id']);
            $this->db->where('urps.section_id', $role['section_id']);
            $this->db->where('urps.revoked_at IS NULL');

            // Filter by member status if specified
            if (!empty($criteria['member_status'])) {
                $this->db->where_in('m.actif', $criteria['member_status']);
            }

            $query = $this->db->get();
            $emails = array_merge($emails, $query->result_array());
        }
    }

    return $emails;
}
```

### 4. UI Selection Interface

**File:** `application/views/email_lists/_criteria_tab.php`

Display roles grouped by section with checkboxes:

```
┌─────────────────────────────────────────────┐
│ Sélection par rôles et sections             │
├─────────────────────────────────────────────┤
│                                              │
│ Section: Planeur                            │
│   ☐ club-admin (Administrateur club)        │
│   ☐ bureau (Membre du bureau)               │
│   ☐ tresorier (Trésorier)                   │
│   ☐ ca (Membre CA)                           │
│   ☐ planchiste (Planchiste)                 │
│   ☐ auto_planchiste (Auto-planchiste)       │
│   ☐ user (Utilisateur)                       │
│                                              │
│ Section: ULM                                │
│   ☐ club-admin (Administrateur club)        │
│   ☐ bureau (Membre du bureau)               │
│   ...                                        │
│                                              │
│ Rôles globaux (toutes sections)             │
│   ☐ super-tresorier (Super trésorier)       │
│   ☐ club-admin (Administrateur club)        │
│                                              │
│ Statut des membres:                         │
│   ☐ Actifs uniquement                       │
│   ☐ Inactifs uniquement                     │
│   ☐ Tous                                     │
│                                              │
│ Nombre de destinataires: 12                 │
└─────────────────────────────────────────────┘
```

### 5. Update Implementation Plan

**File:** `doc/plans_and_progress/gestion_emails_plan.md`

**Phase 2 tasks** need updating:

```markdown
### 2.1 Analyze authorization structure
- [ ] Query user_roles_per_section structure
- [ ] Query types_roles and sections
- [ ] Understand users ↔ membres relationship (mlogin = username)
- [ ] Test 3-table join query performance

### 2.2 Role-based selection
- [ ] Method `get_users_by_role_and_section($types_roles_id, $section_id)`
- [ ] Method `get_all_active_roles()` - for UI population
- [ ] Method `resolve_role_criteria($roles_array)` - apply JSON criteria
- [ ] Handle global vs section-scoped roles

### 2.3 UI for role selection
- [ ] Load types_roles and sections for checkboxes
- [ ] Group roles by section in UI
- [ ] Display global roles separately
- [ ] AJAX preview count with role selections
```

---

## Performance Considerations

### Index Analysis

**Existing indexes needed:**
- `user_roles_per_section(user_id)` - Already exists (FK)
- `user_roles_per_section(types_roles_id)` - Already exists (FK)
- `user_roles_per_section(section_id)` - Already exists (FK)
- `users(username)` - **Check if exists** for membres join
- `membres(mlogin)` - Already exists (PK)

**Check index on users.username:**

```sql
SHOW INDEX FROM users WHERE Column_name = 'username';
```

If missing, add:
```sql
CREATE INDEX idx_users_username ON users(username);
```

### Query Performance

Expected performance for 300 users:
- Single role + section: < 50ms
- Multiple roles (5): < 200ms
- With membre join: +10-20ms
- With deduplication: +5ms

**Total:** < 250ms for complex multi-role selection - acceptable.

---

## Recommendations

### 1. Accept the Architecture

**Do not attempt to change primary key types.** The current structure works and is production-proven.

### 2. Use users.email as Primary Source

**Priority:** `users.email` > `membres.memail`

Rationale: Authentication email is actively maintained, profile email may be stale.

### 3. Update Design Documents

Modify the design to reflect:
- Role selection via `user_roles_per_section`
- 3-table join pattern (urps → users → membres)
- JSON criteria with role/section pairs

### 4. Add Index if Missing

Ensure `users(username)` has an index for efficient joins to `membres`.

### 5. Test with Real Data

Before implementation, test the query pattern with actual data:
- 10 roles across 4 sections
- Verify deduplication works (users with multiple roles)
- Check performance with 300 users

---

## Next Steps

1. ✅ Analysis complete (this document)
2. [ ] Update design document with corrected architecture
3. [ ] Update implementation plan Phase 2 tasks
4. [ ] Verify `users(username)` index exists
5. [ ] Create test query for validation
6. [ ] Proceed with implementation

---

**Conclusion:** The existing authorization structure via `user_roles_per_section` is perfectly suitable for email list selection. The INT vs VARCHAR PK difference is a non-issue - it simply requires a 3-table join which is performant and standard practice.
