# Phase 6: Migration Dashboard Mockups

**Document Version:** 1.0
**Date:** 2025-10-21
**Status:** Planning
**Author:** Claude Code

---

## Overview

The Migration Dashboard provides administrators with a comprehensive view of the progressive migration from legacy DX_Auth to the new Gvv_Authorization system. It enables per-user migration management, real-time monitoring, and validation.

---

## Dashboard Structure

### Main Navigation

```
┌────────────────────────────────────────────────────────────────────┐
│ GVV  │  Admin  >  Gestion des autorisations  >  Migration          │
└────────────────────────────────────────────────────────────────────┘
```

**Tabs**:
1. **Vue d'ensemble** (Overview) - Migration status summary
2. **Utilisateurs pilotes** (Pilot Users) - Manage test user migrations
3. **Journal de comparaison** (Comparison Log) - Authorization mismatches
4. **Statistiques** (Statistics) - Migration metrics and progress

---

## Tab 1: Vue d'ensemble (Overview)

### ASCII Mockup

```
┌────────────────────────────────────────────────────────────────────────┐
│  MIGRATION DU SYSTÈME D'AUTORISATION                                   │
│  ════════════════════════════════════════════════════════════════════  │
│                                                                         │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐    │
│  │ Utilisateurs     │  │ En Migration     │  │ Migrés           │    │
│  │ Total            │  │                  │  │                  │    │
│  │                  │  │                  │  │                  │    │
│  │      156         │  │       3          │  │       0          │    │
│  └──────────────────┘  └──────────────────┘  └──────────────────┘    │
│                                                                         │
│  Progression globale                                                   │
│  ▓▓░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░  2%                    │
│                                                                         │
│  ┌───────────────────────────────────────────────────────────────┐    │
│  │ UTILISATEURS PILOTES (TEST)                                   │    │
│  ├───────────────┬──────────────┬────────────┬──────────────────┤    │
│  │ Utilisateur   │ Rôle         │ Statut     │ Depuis           │    │
│  ├───────────────┼──────────────┼────────────┼──────────────────┤    │
│  │ testuser      │ Membre       │ ⏸ En attente │ -              │    │
│  │ testplanchiste│ Planchiste   │ ⏸ En attente │ -              │    │
│  │ testadmin     │ Admin        │ ⏸ En attente │ -              │    │
│  └───────────────┴──────────────┴────────────┴──────────────────┘    │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐  │
│  │  ⚠️  AVERTISSEMENTS ET ERREURS                                   │  │
│  ├─────────────────────────────────────────────────────────────────┤  │
│  │  Aucun problème détecté                                          │  │
│  └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│  [ 🚀 Démarrer la migration pilote ]                                   │
│                                                                         │
└────────────────────────────────────────────────────────────────────────┘
```

**Features**:
- **Summary Cards**: Total users, in-progress migrations, completed
- **Progress Bar**: Visual representation of overall migration
- **Pilot Users Table**: Quick view of test user status
- **Alerts Panel**: System warnings and errors
- **Action Button**: Start pilot migration wizard

---

## Tab 2: Utilisateurs pilotes (Pilot Users)

### ASCII Mockup

```
┌────────────────────────────────────────────────────────────────────────┐
│  GESTION DES UTILISATEURS PILOTES                                      │
│  ════════════════════════════════════════════════════════════════════  │
│                                                                         │
│  [ Filtrer: ▼ Tous les statuts ]  [ 🔍 Rechercher... ]                │
│                                                                         │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │ Utilisateur      │ Email              │ Rôle       │ Statut      │  │
│  │                  │                    │            │ Migration   │  │
│  ├──────────────────┼────────────────────┼────────────┼─────────────┤  │
│  │ 👤 testuser      │ testuser@free.fr   │ 🔵 Membre  │ ⏸ En attente│  │
│  │                  │                    │            │             │  │
│  │                  │                    │            │ [ ▶️ Migrer ]│  │
│  ├──────────────────┼────────────────────┼────────────┼─────────────┤  │
│  │ 👤 testplanchiste│ testplanchiste@... │ 📋 Planch. │ ⏸ En attente│  │
│  │                  │                    │            │             │  │
│  │                  │                    │            │ [ ▶️ Migrer ]│  │
│  ├──────────────────┼────────────────────┼────────────┼─────────────┤  │
│  │ 👤 testadmin     │ frederic.peignot...│ 👑 Admin   │ ⏸ En attente│  │
│  │                  │                    │            │             │  │
│  │                  │                    │            │ [ ▶️ Migrer ]│  │
│  ├──────────────────┼────────────────────┼────────────┼─────────────┤  │
│  │ 👤 testca        │ testca@free.fr     │ 🏛️ CA      │ ⏸ En attente│  │
│  │                  │                    │            │             │  │
│  │                  │                    │            │ [ ▶️ Migrer ]│  │
│  ├──────────────────┼────────────────────┼────────────┼─────────────┤  │
│  │ 👤 testbureau    │ testbureau@free.fr │ 📄 Bureau  │ ⏸ En attente│  │
│  │                  │                    │            │             │  │
│  │                  │                    │            │ [ ▶️ Migrer ]│  │
│  ├──────────────────┼────────────────────┼────────────┼─────────────┤  │
│  │ 👤 testtresorier │ testresorier@...   │ 💰 Trésor. │ ⏸ En attente│  │
│  │                  │                    │            │             │  │
│  │                  │                    │            │ [ ▶️ Migrer ]│  │
│  └──────────────────┴────────────────────┴────────────┴─────────────┘  │
│                                                                         │
│  Affichage de 1 à 6 sur 6 utilisateurs                                 │
│                                                                         │
└────────────────────────────────────────────────────────────────────────┘
```

**Features**:
- **Filtering**: By migration status (pending, in-progress, completed, failed)
- **Search**: Find specific test users
- **Role Badges**: Visual identification of user roles
- **Status Icons**: Clear migration status indicators
- **Action Buttons**: Per-user migration controls

**Status Icons**:
- ⏸ En attente (Pending) - Gray
- ▶️ En cours (In Progress) - Blue
- ✅ Terminé (Completed) - Green
- ❌ Échoué (Failed) - Red

---

## Tab 3: Journal de comparaison (Comparison Log)

### ASCII Mockup

```
┌────────────────────────────────────────────────────────────────────────┐
│  JOURNAL DE COMPARAISON DES AUTORISATIONS                              │
│  ════════════════════════════════════════════════════════════════════  │
│                                                                         │
│  🔍 Recherche avancée:                                                  │
│  [ Utilisateur: ▼ Tous ] [ Contrôleur: _______ ] [ Action: _______ ]  │
│  [ 📅 Du: __________ ] [ Au: __________ ]  [ 🔎 Rechercher ]           │
│                                                                         │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │ Date/Heure   │ Utilisateur  │ Contrôleur │ Action │ Ancien │ Nouv │ │
│  ├──────────────┼──────────────┼────────────┼────────┼────────┼──────┤ │
│  │ 2025-10-21   │ testuser     │ membres    │ edit   │   ✅   │  ❌  │ │
│  │ 14:32:18     │              │            │        │        │      │ │
│  │              │              │            │        │ [ Détails ]   │ │
│  ├──────────────┼──────────────┼────────────┼────────┼────────┼──────┤ │
│  │ 2025-10-21   │ testuser     │ vols_plan..│ page   │   ❌   │  ❌  │ │
│  │ 14:28:45     │              │            │        │        │      │ │
│  │              │              │            │        │ [ Détails ]   │ │
│  ├──────────────┼──────────────┼────────────┼────────┼────────┼──────┤ │
│  │ 2025-10-21   │ testplanch.. │ planning   │ create │   ✅   │  ✅  │ │
│  │ 13:15:22     │              │            │        │        │      │ │
│  │              │              │            │        │ [ Détails ]   │ │
│  └──────────────┴──────────────┴────────────┴────────┴────────┴──────┘ │
│                                                                         │
│  ⚠️ 1 divergence détectée dans les dernières 24h                       │
│                                                                         │
│  [ 📥 Exporter CSV ] [ 🗑️ Purger anciens logs ]                         │
│                                                                         │
└────────────────────────────────────────────────────────────────────────┘
```

**Features**:
- **Advanced Search**: Filter by user, controller, action, date range
- **Comparison Results**: Side-by-side view of old vs new system
- **Divergence Highlighting**: Red background for mismatches
- **Detail Modal**: Click to see full context (roles, permissions, rules)
- **Export**: CSV download for external analysis
- **Cleanup**: Purge old comparison logs

**Detail Modal** (when clicking "Détails"):
```
┌─────────────────────────────────────────────────────────────┐
│  DÉTAILS DE LA COMPARAISON D'AUTORISATION                   │
│  ═══════════════════════════════════════════════════════════ │
│                                                              │
│  Utilisateur: testuser (ID: 1234)                           │
│  Contrôleur: membres                                         │
│  Action: edit                                                │
│  Date: 2025-10-21 14:32:18                                   │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ ANCIEN SYSTÈME (DX_Auth)                              │ │
│  ├────────────────────────────────────────────────────────┤ │
│  │ Résultat: ✅ ACCÈS ACCORDÉ                             │ │
│  │                                                         │ │
│  │ Rôle: membre (ID: 1)                                   │ │
│  │ Permissions sérialisées:                               │ │
│  │   - membres/view: true                                 │ │
│  │   - membres/edit: true                                 │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ NOUVEAU SYSTÈME (Gvv_Authorization)                   │ │
│  ├────────────────────────────────────────────────────────┤ │
│  │ Résultat: ❌ ACCÈS REFUSÉ                              │ │
│  │                                                         │ │
│  │ Rôles: user (ID: 1, Section: 1)                       │ │
│  │ Permissions:                                           │ │
│  │   - membres/view (portée: section)                    │ │
│  │   ❌ PAS DE PERMISSION pour membres/edit              │ │
│  │                                                         │ │
│  │ Règles d'accès:                                        │ │
│  │   - membres (portée: own, champ: user_id)             │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
│  ⚠️ ANALYSE:                                                 │
│  Permission "membres/edit" manquante dans le nouveau        │
│  système pour le rôle "user". Action recommandée:           │
│  ajouter cette permission via l'interface de gestion.       │
│                                                              │
│  [ 🔧 Corriger maintenant ] [ ⏭️ Ignorer ] [ ✖️ Fermer ]      │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## Tab 4: Statistiques (Statistics)

### ASCII Mockup

```
┌────────────────────────────────────────────────────────────────────────┐
│  STATISTIQUES DE MIGRATION                                             │
│  ════════════════════════════════════════════════════════════════════  │
│                                                                         │
│  📊 PROGRESSION PAR VAGUE                                               │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │ Vague 1 (testuser)           ▓▓▓▓▓▓▓▓▓▓░░░░░░░░░░░  50% (3/7j) │    │
│  │ Vague 2 (testplanchiste)     ░░░░░░░░░░░░░░░░░░░░░   0% (0/7j) │    │
│  │ Vague 3 (testadmin)          ░░░░░░░░░░░░░░░░░░░░░   0% (0/7j) │    │
│  └────────────────────────────────────────────────────────────────┘    │
│                                                                         │
│  📈 COMPARAISONS D'AUTORISATION (7 derniers jours)                      │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │                                                                 │    │
│  │  100 ┤                                                          │    │
│  │      │                                                          │    │
│  │   80 ┤                                             ●●           │    │
│  │      │                                           ●●             │    │
│  │   60 ┤                                    ●●●●●                 │    │
│  │      │                              ●●●●●                       │    │
│  │   40 ┤                         ●●●●                             │    │
│  │      │                   ●●●●●                                  │    │
│  │   20 ┤            ●●●●●●                                        │    │
│  │      │      ●●●●●                                               │    │
│  │    0 ┼─────┴─────┴─────┴─────┴─────┴─────┴─────┴─────┴─────   │    │
│  │      L   M   M   J   V   S   D   L   M   M   J   V   S   D    │    │
│  │                                                                 │    │
│  │  ─── Comparaisons totales   ─── Divergences                    │    │
│  └────────────────────────────────────────────────────────────────┘    │
│                                                                         │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐    │
│  │ Comparaisons     │  │ Divergences      │  │ Taux de          │    │
│  │ Totales          │  │ Détectées        │  │ Concordance      │    │
│  │                  │  │                  │  │                  │    │
│  │      847         │  │       12         │  │    98.6%         │    │
│  └──────────────────┘  └──────────────────┘  └──────────────────┘    │
│                                                                         │
│  🎯 TOP 5 CONTRÔLEURS AVEC DIVERGENCES                                  │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │ 1. membres (8 divergences)                                      │    │
│  │ 2. vols_planeur (3 divergences)                                 │    │
│  │ 3. planning (1 divergence)                                      │    │
│  └────────────────────────────────────────────────────────────────┘    │
│                                                                         │
│  [ 📥 Exporter rapport complet ]                                        │
│                                                                         │
└────────────────────────────────────────────────────────────────────────┘
```

**Features**:
- **Wave Progress**: Visual progress bars for each pilot user wave
- **Comparison Trends**: Line chart showing authorization checks over time
- **Summary Metrics**: Total comparisons, divergences, concordance rate
- **Hot Spots**: Controllers with most authorization divergences
- **Export**: Generate comprehensive PDF/CSV report

---

## Migration Wizard Flow

When clicking "▶️ Migrer" button on a pilot user:

### Step 1: Pre-Migration Validation

```
┌─────────────────────────────────────────────────────────────┐
│  MIGRATION - ÉTAPE 1/4: VALIDATION                          │
│  ═══════════════════════════════════════════════════════════ │
│                                                              │
│  Utilisateur: testuser                                       │
│  Rôle actuel: membre (user)                                 │
│                                                              │
│  ✅ Vérifications automatiques:                              │
│   ✓ Rôle user existe dans types_roles                       │
│   ✓ Section assignée (Section 1: Default)                   │
│   ✓ Permissions anciennes sauvegardées                      │
│   ✓ Aucune session active (utilisateur déconnecté)          │
│                                                              │
│  ⚠️ Permissions à migrer:                                    │
│   • membres/view → role_permissions (user, membres, view)   │
│   • membres/edit → role_permissions (user, membres, edit)   │
│   • vols_planeur/view → (NOUVELLE permission)               │
│                                                              │
│  [ ◀ Annuler ]                           [ Continuer ▶ ]    │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Step 2: Permission Mapping

```
┌─────────────────────────────────────────────────────────────┐
│  MIGRATION - ÉTAPE 2/4: MAPPAGE DES PERMISSIONS             │
│  ═══════════════════════════════════════════════════════════ │
│                                                              │
│  Les permissions suivantes seront créées:                    │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ Ancien système        →  Nouveau système             │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │ membres/view          →  user: membres/view          │   │
│  │                          (portée: section)           │   │
│  │                                                       │   │
│  │ membres/edit          →  user: membres/edit          │   │
│  │                          (portée: section)           │   │
│  │                                                       │   │
│  │ vols_planeur/view     →  user: vols_planeur/view     │   │
│  │   (NOUVELLE)             (portée: section)           │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                              │
│  Règles d'accès aux données:                                │
│  • membres: portée "own" (champ user_id)                    │
│  • vols_planeur: portée "section" (champ club)              │
│                                                              │
│  [ ◀ Retour ]                            [ Continuer ▶ ]    │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Step 3: Confirmation

```
┌─────────────────────────────────────────────────────────────┐
│  MIGRATION - ÉTAPE 3/4: CONFIRMATION                        │
│  ═══════════════════════════════════════════════════════════ │
│                                                              │
│  ⚠️ ATTENTION: Cette action va:                              │
│                                                              │
│  1. Basculer testuser vers le nouveau système               │
│  2. Activer la journalisation des comparaisons              │
│  3. Exiger une surveillance pendant 7 jours                 │
│                                                              │
│  ✓ Sauvegarde des permissions actuelles effectuée           │
│  ✓ Rollback possible à tout moment                          │
│                                                              │
│  Notes (optionnel):                                          │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ Migration vague 1 - utilisateur basique                │ │
│  │                                                         │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
│  [ ◀ Retour ]  [ ✖️ Annuler ]         [ ✅ MIGRER ]          │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Step 4: Migration Complete

```
┌─────────────────────────────────────────────────────────────┐
│  MIGRATION - ÉTAPE 4/4: TERMINÉE                            │
│  ═══════════════════════════════════════════════════════════ │
│                                                              │
│  ✅ Migration réussie pour testuser!                         │
│                                                              │
│  Statut: En cours (in_progress)                             │
│  Depuis: 2025-10-21 15:30:00                                │
│  Superviseur: frederic.peignot (ID: 15)                     │
│                                                              │
│  Actions suivantes:                                          │
│                                                              │
│  1. ⏰ Surveiller pendant 7 jours (jusqu'au 28/10/2025)      │
│  2. 🔍 Vérifier le journal de comparaison quotidiennement   │
│  3. ✅ Marquer comme terminé si aucun problème              │
│                                                              │
│  ⚠️ L'utilisateur peut maintenant se connecter et utiliser  │
│     le nouveau système d'autorisation.                       │
│                                                              │
│  [ 📊 Voir les statistiques ]  [ ↩️ Rollback ]  [ ✖️ Fermer ]│
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## Rollback Interface

When clicking "↩️ Rollback" button:

```
┌─────────────────────────────────────────────────────────────┐
│  ROLLBACK DE LA MIGRATION                                    │
│  ═══════════════════════════════════════════════════════════ │
│                                                              │
│  ⚠️ ATTENTION: Vous êtes sur le point de revenir            │
│     l'utilisateur testuser au système d'autorisation        │
│     legacy (DX_Auth).                                        │
│                                                              │
│  Raison du rollback (obligatoire):                           │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ Divergences importantes détectées dans l'accès aux    │ │
│  │ pages de membres/edit.                                 │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
│  Cette action va:                                            │
│  • Désactiver use_new_system (0)                            │
│  • Restaurer les permissions depuis old_permissions         │
│  • Marquer le statut comme "failed"                         │
│  • Conserver l'historique dans l'audit log                  │
│                                                              │
│  ℹ️ Les données du nouveau système ne seront PAS supprimées │
│     (pour permettre une future tentative de migration)       │
│                                                              │
│  [ ✖️ Annuler ]                    [ ⚠️ CONFIRMER ROLLBACK ] │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## Implementation Notes

### Bootstrap 5 Components Used
- **Cards**: Summary metrics, status panels
- **Badges**: Role indicators, status labels
- **Progress Bars**: Migration progress, wave tracking
- **Modals**: Migration wizard, detail views, confirmations
- **Tables**: DataTables for pilot users, comparison log
- **Alerts**: Warnings, errors, success messages
- **Charts**: Chart.js for statistics visualization

### Color Scheme
- **Primary (Blue)**: In-progress status, action buttons
- **Success (Green)**: Completed migrations, concordance
- **Warning (Orange)**: Pending migrations, alerts
- **Danger (Red)**: Failed migrations, divergences, rollback
- **Secondary (Gray)**: Disabled states, neutral info

### Icons (Bootstrap Icons / Font Awesome)
- 👤 `bi-person`: User
- 🔵 `bi-circle-fill`: Role badge
- ⏸ `bi-pause-circle`: Pending status
- ▶️ `bi-play-circle`: In-progress / Start migration
- ✅ `bi-check-circle`: Completed
- ❌ `bi-x-circle`: Failed / Denied
- 🔍 `bi-search`: Search
- 📊 `bi-graph-up`: Statistics
- 📥 `bi-download`: Export
- 🗑️ `bi-trash`: Delete
- ⚠️ `bi-exclamation-triangle`: Warning
- ↩️ `bi-arrow-counterclockwise`: Rollback

### Responsive Design
- **Desktop (>1200px)**: Full dashboard with all columns
- **Tablet (768-1199px)**: Stacked cards, scrollable tables
- **Mobile (<768px)**: Single column layout, collapsed navigation

---

## Controller Implementation

**Route**: `/authorization/migration`

**Methods**:
- `index()` - Dashboard overview (Tab 1)
- `pilot_users()` - Pilot user management (Tab 2)
- `comparison_log()` - Comparison log viewer (Tab 3)
- `statistics()` - Migration statistics (Tab 4)
- `migrate_user()` - AJAX endpoint for migration wizard
- `rollback_user()` - AJAX endpoint for rollback
- `export_comparison()` - CSV export of comparison log
- `export_report()` - PDF report generator

---

## Next Steps

1. ✅ Review mockups with user
2. 📝 Create HTML/CSS prototypes
3. 💻 Implement backend controller methods
4. 🎨 Integrate Bootstrap 5 components
5. 🧪 Test migration workflow with testuser
6. 📊 Implement Chart.js visualizations
7. 📱 Validate responsive design on mobile

---

**Document History**:
- v1.0 (2025-10-21): Initial mockups and wireframes
