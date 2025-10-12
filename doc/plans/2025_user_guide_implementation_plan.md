# GVV User Guide - Implementation Plan

## Overview

This document outlines the step-by-step plan to create a comprehensive user guide for GVV (Gestion Vol à voile) with screenshots captured using Playwright. The guide will be structured around user workflows and stored in `doc/users/` for easy GitHub viewing.

## Documentation Structure

The documentation is organized to support internationalization (i18n). Initial version will be in French, with structure ready for English and Dutch translations.

```
doc/users/
├── fr/                                 # French documentation (primary)
│   ├── README.md                       # Main user guide index
│   ├── 01_demarrage.md                 # Login, navigation, basic concepts
│   ├── 02_gestion_membres.md           # CRUD example with detailed screenshots
│   ├── 03_gestion_aeronefs.md          # Fleet management (gliders & towplanes)
│   ├── 04_saisie_vols.md               # Flight recording workflows
│   ├── 05_calendrier.md                # Calendar and flight intentions
│   ├── 06_facturation.md               # Invoicing and client accounts
│   ├── 07_comptabilite.md              # Double-entry accounting features
│   └── 08_rapports.md                  # Report generation and exports
├── en/                                 # English documentation (future)
│   └── (same structure, to be translated)
├── nl/                                 # Dutch documentation (future)
│   └── (same structure, to be translated)
├── screenshots/                        # Shared screenshots (language-neutral where possible)
│   ├── 01_getting_started/
│   ├── 02_member_management/
│   ├── 03_aircraft_management/
│   ├── 04_flight_logging/
│   ├── 05_flight_calendar/
│   ├── 06_billing/
│   ├── 07_accounting/
│   └── 08_reports/
├── openflyers_user.md                  # Existing integration doc (French)
└── README.md                           # Language selector / index
```

## Key Features Identified

Based on analysis of the codebase, GVV provides:

1. **Member Management** - Users, roles, licenses, sections
2. **Aircraft Fleet** - Gliders (planeur) and towplanes (avion)
3. **Flight Logging** - Manual/automatic flight recording, discovery flights
4. **Flight Calendar** - Flight intentions and scheduling
5. **Billing** - Invoicing, client accounts, tariffs
6. **Accounting** - Double-entry bookkeeping, chart of accounts, bank reconciliation
7. **Reporting** - Various reports and exports
8. **Configuration** - Association settings, fields, permissions
9. **Email Communications** - Member notifications
10. **OpenFlyers Integration** - Account synchronization (already documented)

## User Workflows to Document

### 1. Getting Started (Essential for all users)
- **Login & Authentication** - First connection, password recovery
- **Navigation** - Main menu, sections, responsive design
- **Basic Concepts** - Sections, roles, active/inactive entities

### 2. Member Management (CRUD Example - Detailed)
This will serve as the comprehensive CRUD example:
- **View Members List** - Filters, search, pagination
- **Create New Member** - Form fields, required data, validation
- **View Member Details** - Profile, licenses, history
- **Edit Member** - Updating information, managing licenses
- **Delete/Deactivate Member** - Soft delete vs hard delete
- **Member Roles & Permissions** - Assigning roles per section

### 3. Aircraft Fleet Management
- **View Aircraft List** - Active/inactive filter
- **Add New Aircraft** - Basic workflow only (similar to member creation)
- **Activate/Deactivate Aircraft** - Managing fleet visibility

### 4. Flight Logging
- **Recording Glider Flights** - Manual entry workflow
- **Recording Towplane Flights** - Towplane-specific fields
- **Discovery Flights** - Special workflow for "vols de découverte"
- **Flight List & Filters** - Viewing and searching flights

### 5. Flight Calendar
- **View Calendar** - Monthly/weekly views
- **Add Flight Intention** - Booking workflow
- **Manage Attendance** - Presence tracking

### 6. Billing
- **Generate Invoices** - Billing workflow
- **Client Account Operations** - Credits, debits
- **Tariff Management** - Overview only

### 7. Accounting
- **Chart of Accounts** - Navigation
- **Manual Journal Entries** - Transaction recording
- **Bank Reconciliation** - Matching operations
- **Reports** - Balance sheet, profit/loss statement

### 8. Reporting Features
- **Available Reports** - Overview of report types
- **Export Options** - CSV, PDF formats

## Documentation Principles

1. **Workflow-Oriented** - Focus on "how to accomplish X" rather than "what button Y does"
2. **Minimal Text** - GVV is intuitive, screenshots with brief captions are sufficient
3. **One Detailed CRUD** - Member management shows full CRUD cycle in detail
4. **Other Features** - High-level overview with key screenshots
5. **Progressive Disclosure** - Start simple, add complexity only when needed
6. **i18n Ready** - Initial version in French, structure supports English & Dutch translations
7. **Shared Screenshots** - Where UI text is minimal, screenshots can be shared across languages

## Implementation Steps

### Phase 1: Setup & Infrastructure (Steps 1-3)

#### Step 1: Create Directory Structure
```bash
mkdir -p doc/users/{fr,en,nl}
mkdir -p doc/users/screenshots/{01_getting_started,02_member_management,03_aircraft_management,04_flight_logging,05_flight_calendar,06_billing,07_accounting,08_reports}
```

#### Step 2: Verify Playwright Setup
- Playwright is installed in the playwright directory
- Test browser automation
- Configure screenshot settings (viewport size, format)

#### Step 3: Prepare Test Environment
- URL: http://gvv.net/
- Credentials: testadmin / password
- Test browser connection and login
- Verify application is in French language

### Phase 2: Core Documentation (Steps 4-5)

#### Step 4: Getting Started Guide
**File**: `doc/users/fr/01_demarrage.md`

**Screenshots to capture**:
- Login page
- Home dashboard
- Main navigation menu
- Section selector
- User profile menu
- Password recovery form

**Content**:
- Comment se connecter
- Comprendre l'interface principale
- Bases de la navigation
- Concept de sections
- Responsive design (ordinateur/tablette/mobile)

#### Step 5: Member Management (CRUD Example)
**File**: `doc/users/fr/02_gestion_membres.md`

**Screenshots to capture**:
- Member list view (with filters)
- Create member form (all sections)
- Member details view
- Edit member form
- License management
- Role assignment
- Deactivate confirmation dialog

**Content** (detailed):
- **CREATE**: Complete walkthrough of adding a new member
- **READ**: Viewing member list and details
- **UPDATE**: Editing member information and licenses
- **DELETE**: Deactivating vs deleting members
- **Search & Filter**: Finding members quickly
- **Roles & Permissions**: Assigning access rights

### Phase 3: Feature Documentation (Steps 6-11)

#### Step 6: Aircraft Fleet Management
**File**: `doc/users/fr/03_gestion_aeronefs.md`

**Screenshots to capture**:
- Gliders list
- Towplanes list
- Add aircraft form (brief)
- Activate/deactivate toggle

**Content** (high-level):
- Viewing fleet (gliders and towplanes)
- Adding new aircraft (reference CRUD pattern from members)
- Managing active/inactive status

#### Step 7: Flight Logging
**File**: `doc/users/fr/04_saisie_vols.md`

**Screenshots to capture**:
- Flight list with filters
- Add glider flight form
- Add towplane flight form
- Discovery flight form
- Flight details view

**Content**:
- Recording different flight types
- Required fields explanation
- Flight list and search

#### Step 8: Flight Calendar
**File**: `doc/users/fr/05_calendrier.md`

**Screenshots to capture**:
- Calendar main view
- Add flight intention dialog
- Attendance/presence tracking

**Content**:
- Consulter le calendrier des vols
- Ajouter une intention de vol
- Gérer les présences

#### Step 9: Billing Features
**File**: `doc/users/fr/06_facturation.md`

**Screenshots to capture**:
- Invoice generation
- Client account operations
- Tariff list (overview)

**Content**:
- Générer des factures
- Gérer les comptes clients
- Comprendre les tarifs

#### Step 10: Accounting Features
**File**: `doc/users/fr/07_comptabilite.md`

**Screenshots to capture**:
- Chart of accounts
- Manual journal entry form
- Bank reconciliation interface
- Balance sheet report
- Profit/loss statement

**Content**:
- Navigation dans le plan comptable
- Enregistrer des transactions
- Rapprochement bancaire
- Générer les rapports financiers

#### Step 11: Reporting Features
**File**: `doc/users/fr/08_rapports.md`

**Screenshots to capture**:
- Reports menu/list
- Report generation form
- Export options (CSV, PDF)
- Sample report output

**Content**:
- Types de rapports disponibles
- Générer des rapports
- Formats d'export

### Phase 4: Integration & Finalization (Steps 12-14)

#### Step 12: Main Index Files
**Files**:
- `doc/users/README.md` (language selector)
- `doc/users/fr/README.md` (French index)

**Content**:
- Language selector in root README
- French index: Vue d'ensemble des capacités GVV, structure du guide, liens rapides

#### Step 13: Update Existing Documentation
- Review existing `openflyers_user.md` (already good)
- Ensure consistency with new guide structure
- Cross-link where appropriate

#### Step 14: Review & Polish
- Verify all screenshots are clear and properly sized
- Check all links work
- Ensure consistent formatting
- Proofread all text
- Test navigation flow

## Screenshot Standards

### Technical Settings
- **Browser**: Chromium (Playwright default)
- **Viewport**: 1280x720 (desktop), 768x1024 (tablet)
- **Format**: PNG (lossless)
- **Naming**: Descriptive, numbered: `01_login_page.png`, `02_member_list.png`
- **Language**: French (primary language of the application)

### Quality Guidelines
- Clean test data (no personal information)
- Highlight key UI elements when needed
- Consistent navigation state (same user, same section)
- Full page vs viewport: Use full page for overviews, viewport for specific actions

## Execution Strategy

### Sequential Execution
Execute steps 1-14 sequentially. Each step builds on the previous one.

### Time Estimates
- Phase 1 (Setup): 30 minutes
- Phase 2 (Core): 2-3 hours
- Phase 3 (Features): 3-4 hours
- Phase 4 (Finalization): 1 hour
- **Total**: 6-8 hours

### Prerequisites for Execution
1. GVV application running and accessible (local or remote)
2. Test account with admin privileges
3. Test database with sample data
4. Playwright installed and configured

## Success Criteria

The user guide will be considered complete when:
1. All 8 main documentation files are created
2. All key workflows are documented with screenshots
3. Member management (CRUD) is comprehensively detailed
4. Other features have high-level overviews with key screenshots
5. Navigation between sections is clear
6. Documentation is readable directly on GitHub
7. Screenshots are clear, consistent, and properly organized

## Execution Configuration

**Confirmed settings**:
- **Environment**: http://gvv.net/
- **Credentials**: testadmin / password
- **Language**: French (initial), i18n structure for EN/NL
- **Storage**: Plan in `doc/plans/`, documentation in `doc/users/`

## Next Steps

Ready to execute:
1. Phase 1: Setup & Infrastructure (Steps 1-3)
2. Phase 2: Core Documentation (Steps 4-5)
3. Phase 3: Feature Documentation (Steps 6-11)
4. Phase 4: Integration & Finalization (Steps 12-14)

Total estimated time: 6-8 hours
