# PRD: Authorization System Refactoring

**Document Version:** 1.0
**Date:** 2025-01-08
**Status:** Proposed
**Author:** Extracted from Refactoring Plan by Gemini

---

## 1. Executive Summary

This document outlines the requirements to refactor GVV's authorization system. The current hierarchical role-based access control (RBAC) model will be replaced with a flat, domain-based, section-aware authorization system. The new system will support both global and section-specific roles, provide an improved UI for permission management, and implement row-level data access controls.

---

## 2. Current State Analysis

### 2.1. Existing System (2011 Architecture)

- **Schema:** The system uses a set of hierarchical roles (`membre` → `planchiste` → `ca` → `bureau` → `tresorier` → `admin`). Permissions are stored as serialized URIs assigned to each role.
- **Hierarchy:** `admin` (2) → `tresorier` (9) → `bureau` (3) → `ca` (8) → `planchiste` (7) → `membre` (1).
- **Controllers Affected:** `backend.php`, `migration.php`, `presences.php`, `rapports.php`, `config.php`.

### 2.2. Key Issues with Current System

1.  **Hierarchical Inheritance Problem:** Higher-level roles (e.g., Tresoriers) incorrectly inherit permissions from lower-level roles (e.g., Planchiste flight data access).
2.  **No Section Granularity:** Permissions are global. A user cannot have different roles in different sections (e.g., Planchiste for 'Planeur' and basic 'User' for 'ULM').
3.  **Complex URI Management:** Manually editing serialized URI strings in a textarea is difficult and error-prone.
4.  **No Row-Level Security:** The system cannot distinguish between viewing one's "own data" versus "all data" within a table (e.g., a user seeing only their own flights vs. a manager seeing all flights).
5.  **Poor UX:** There is no intuitive interface for administrators to see or manage user permissions at a glance.

---

## 3. Goals and Requirements

### 3.1. Functional Requirements

1.  **FR1: Flat Role Model**
    -   Eliminate the rigid parent-child role hierarchy.
    -   Roles should be independent domains of authority (e.g., "Flight Manager", "Finance Manager").
    -   Permissions should not be inherited automatically.

2.  **FR2: Section-Aware Roles**
    -   Support **Global Roles** that apply across the entire application (e.g., `admin`, `bureau`).
    -   Support **Section Roles** that are specific to a single section (e.g., `tresorier`, `planchiste`).
    -   A user must be able to hold different roles in different sections.

3.  **FR3: Improved User Role Management UI**
    -   Provide a single-page interface for managing user roles.
    -   The UI should feature a user list with checkboxes for role assignments.
    -   Administrators must be able to filter the user list by section and by active/inactive status.
    -   The interface should use DataTables for searching and sorting.
    -   It must be visually clear which roles are global versus section-specific.

4.  **FR4: Improved Permission Management UI**
    -   Create a UI for managing the permissions assigned to each role.
    -   Permissions (URIs) should be organized by application feature/domain (e.g., "Members", "Flights").
    -   Replace the textarea with a visual checklist for assigning permissions.
    -   The UI should clearly show which roles have which permissions.

5.  **FR5: Row-Level Data Access**
    -   Implement a mechanism to control data access at the row level, distinguishing between "own" data and "all" data.
    -   **Examples:**
        -   A `user` should only see their own invoices.
        -   A `tresorier` should see all invoices within their assigned section.
        -   An `auto_planchiste` can edit their own flights.
        -   A `planchiste` can edit all flights within their section.

6.  **FR6: Admin Override**
    -   The `admin` role must be maintained as a super-user.
    -   Admin users must bypass all permission checks and have unrestricted access.

7.  **FR7: Testing Framework**
    -   Comprehensive unit tests must be developed for the new authorization logic.
    -   Tests must cover authorized access, unauthorized access denial, and row-level data rules for each role.

### 3.2. Non-Functional Requirements

1.  **NFR1: Backward Compatibility & Migration**
    -   The system must support a gradual migration.
    -   A feature flag must be implemented to switch between the old and new authorization systems.
    -   A clear rollback plan must be in place for each phase of the migration.

2.  **NFR2: Performance**
    -   Individual permission checks should execute in under 10ms.
    -   The system should leverage session-based caching for user permissions to minimize database queries.

3.  **NFR3: Security**
    -   An audit trail must be created to log all changes to roles and permissions.
    -   The system must be secure against privilege escalation.
    -   The default security posture must be "deny by default."

4.  **NFR4: Maintainability**
    -   The new code must be well-documented and self-explanatory.
    -   The architecture should make it easy to add new roles and permissions in the future.

---

## 4. New UI Components (Mockups)

### 4.1. User Role Management Page

**Concept:** A DataTables-based grid where rows are users and columns are roles, grouped by global and section-specific scopes.

**Features:**
- Filters for Section and Active/Inactive users.
- Checkboxes to grant/revoke roles.
- AJAX-based saving.

**Mockup Structure:**
```
[Section Filter: All / Planeur / ULM / Avion] [Active Users Only: ☑] [Search: ______]

+----------+---------+------------+------------------+------------------+
| Username | Email   | Global     | Section: Planeur | Section: ULM     |
|          |         | Roles      | Roles            | Roles            |
+----------+---------+------------+------------------+------------------+
| fpeignot | f@...   | ☑ Admin    | ☑ CA             | ☐ Tresorier      |
|          |         | ☐ Bureau   | ☑ Planchiste     | ☐ Planchiste     |
+----------+---------+------------+------------------+------------------+
| agnes    | a@...   | ☐ Admin    | ☑ Tresorier      | ☐ Tresorier      |
|          |         | ☐ Bureau   | ☑ User           | ☐ User           |
+----------+---------+------------+------------------+------------------+
```

### 4.2. Role Permission Management Page

**Concept:** An interface to configure the specific URI permissions for each role.

**Features:**
- Dropdowns to select the Role and (if applicable) the Section.
- Permissions grouped by controller/feature.
- Checkboxes for permission types (View, Create, Edit, Delete).

**Mockup Structure:**
```
[Select Role: Planchiste ▼]  [Section: Planeur ▼]

Domain: Vols Planeur
┌──────────────────┬──────┬────────┬──────┬────────┐
│ Action           │ View │ Create │ Edit │ Delete │
├──────────────────┼──────┼────────┼──────┼────────┤
│ vols_planeur/    │  ☑   │   ☑    │  ☑   │   ☑    │
│ vols_planeur/pdf │  ☑   │   ☐    │  ☐   │   ☐    │
└──────────────────┴──────┴────────┴──────┴────────┘

Domain: Membres
┌──────────────────┬──────┬────────┬──────┬────────┐
│ Action           │ View │ Create │ Edit │ Delete │
├──────────────────┼──────┼────────┼──────┼────────┤
│ membre/          │  ☑   │   ☐    │  ☐   │   ☐    │
│ membre/view      │  ☑   │   ☐    │  ☐   │   ☐    │
└──────────────────┴──────┴────────┴──────┴────────┘

[Save Permissions]  [Cancel]
```
