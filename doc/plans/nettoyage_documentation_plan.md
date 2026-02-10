# Plan de nettoyage de la documentation

**Statut:** Appliqué

## 1. Fichiers à la racine du projet

| Fichier | Action | Justification |
|---------|--------|---------------|
| `DEBUG_SESSION_BALANCE_SEARCH.md` | Supprimer | Session de debug ponctuelle |
| `RESULTAT_SECTIONS_DETAIL_FIX.md` | Supprimer | Rapport de correction ponctuel |
| `TIMELINE_IMPLEMENTATION_SUMMARY.md` | Supprimer | Résumé d'implémentation terminée |
| `TESTING-SUMMARY.txt` | Supprimer | Snapshot daté oct 2025, supplanté par `doc/testing/TESTING-UPDATED.md` |
| `COVERAGE-SUMMARY.txt` | Supprimer | Snapshot daté, se régénère avec `run-all-tests.sh --coverage` |
| `QUICK_START_TEST_DATABASE.md` | Déplacer → `doc/development/` | Utile mais mal placé |
| `TEST_DATABASE_ENCRYPTED_IMPLEMENTATION.md` | Déplacer → `doc/development/` | Utile mais mal placé |
| `test_import_valid.md` | Déplacer → `doc/test-data/` | Fichier de test d'import égaré |
| `test_import_invalid.md` | Déplacer → `doc/test-data/` | Fichier de test d'import égaré |

## 2. Répertoire `doc/summaries/` — supprimer en entier

23 fichiers, tous des rapports de complétion ponctuels générés par LLM. Aucun ne contient de décision architecturale ou d'information non disponible dans le code ou les tests.

| Fichier | Action | Justification |
|---------|--------|---------------|
| `ALL_TESTS_PASSING_SUMMARY.md` | Supprimer | Snapshot de tests |
| `TEST_COMPLETION_REPORT.md` | Supprimer | Snapshot de tests |
| `ATTACHMENTS_TESTS_FIX_SUMMARY.md` | Supprimer | Rapport de correction ponctuel |
| `ATTACHMENTS_TESTS_SUMMARY.md` | Supprimer | Rapport de correction ponctuel |
| `AUTHORIZATION_ROLES_CRUD_SUMMARY.md` | Supprimer | Rapport de complétion |
| `BADGES_COLOR_FIX_SUMMARY.md` | Supprimer | Rapport de correction ponctuel |
| `BADGES_FINAL_STYLE_SUMMARY.md` | Supprimer | Rapport de correction ponctuel |
| `BADGES_SECTIONS_IMPLEMENTATION_SUMMARY.md` | Supprimer | Rapport de complétion |
| `BONS_CADEAUX_CONFIGURATION_SUMMARY.md` | Supprimer | Rapport de complétion |
| `CLICKABLE_THUMBNAILS_FIX_SUMMARY.md` | Supprimer | Rapport de correction ponctuel |
| `COMPLETE_EXPORT_TESTING_SUMMARY.md` | Supprimer | Rapport de tests |
| `COMPREHENSIVE_EXPORT_TESTING_PLAN.md` | Supprimer | Plan de tests terminé |
| `CONFIGURATION_FILE_SUPPORT_FINAL.md` | Supprimer | Rapport de complétion |
| `CONFIGURATION_FILE_SUPPORT_SUMMARY.md` | Supprimer | Doublon du précédent |
| `EXPORT_TESTING_FIX_SUMMARY.md` | Supprimer | Rapport de correction ponctuel |
| `IMAGE_DUPLICATION_FIX_SUMMARY.md` | Supprimer | Rapport de correction ponctuel |
| `IMPLEMENTATION_VOLS_DECOUVERTE_FILTER.md` | Supprimer | Rapport de complétion |
| `MEMBER_PHOTO_UPLOAD_FIX_SUMMARY.md` | Supprimer | Rapport de correction ponctuel |
| `PDF_EXPORT_TESTING_SUMMARY.md` | Supprimer | Rapport de tests |
| `PHOTO_DISPLAY_CRITICAL_FIX_SUMMARY.md` | Supprimer | Rapport de correction ponctuel |
| `PHOTO_SIZING_CONSTRAINT_SUMMARY.md` | Supprimer | Rapport de correction ponctuel |
| `PLAYWRIGHT_FIXES_SUMMARY.md` | Supprimer | Rapport de correction ponctuel |
| `SKIPPED_TESTS_FIXED_SUMMARY.md` | Supprimer | Rapport de correction ponctuel |

## 3. Documentation testing — doublons

| Fichier | Action | Justification |
|---------|--------|---------------|
| `doc/testing/TESTING.md` | Supprimer | Ancienne version, supplanté par `TESTING-UPDATED.md` |
| `doc/testing/TESTING-UPDATED.md` | Renommer → `TESTING.md` | Devient la référence unique |
| `doc/testing/TEST-COMPARISON.md` | Supprimer | Comparaison old vs new, transitoire |
| `doc/testing/playwright_errors_analysis.md` | Supprimer | Analyse ponctuelle de bugs |
| `doc/testing/playwright_failures_analysis.md` | Supprimer | Analyse ponctuelle de bugs |
| `doc/testing/playwright_phase1_fixes.md` | Supprimer | Rapport de phase terminée |
| `doc/development/ci_to_phpunit_migration.md` | Vérifier doublon avec `PHPUNIT_MIGRATION_SUMMARY.md` | Consolider si doublon |

## 4. `doc/design_notes/` — fichiers hors sujet

| Fichier | Action | Justification |
|---------|--------|---------------|
| `accordion-persistence-summary.md` | Supprimer | Résumé d'implémentation, pas un design |
| `licences_cleanup_summary.md` | Supprimer | Liste de fichiers temporaires supprimés |
| `MARKDOWN_SUPPORT_IMPLEMENTATION.md` | Supprimer | Résumé d'implémentation, pas un design |

## 5. Plans terminés — archiver

Déplacer dans `doc/plans/archive/` :

| Fichier | Action | Justification |
|---------|--------|---------------|
| `UI_IMPLEMENTATION_GUIDE.md` | Archiver | Status: IMPLEMENTATION COMPLETE |
| `playwright_migration_summary.md` | Archiver | Résumé de migration terminée |
| `TEST_COVERAGE_STATUS.md` | Archiver | Snapshot statique, pas un plan |
| `procedures_todo.md` | Supprimer | Todo list terminée |

## 6. `doc/bugs/` — nettoyage partiel

| Fichier | Action | Justification |
|---------|--------|---------------|
| `journal_compte_soldes_fix_plan.md` | Supprimer | Toutes les tâches cochées, bug corrigé |
| `FIX_CONCURRENT_EDIT_DOCUMENTATION.md` | Conserver | Documentation de référence |
| `disconnection_issue.md` | Archiver | Problème résolu |

## 7. `doc/ai/agents/` — rôles d'agents IA non référencés

12 fichiers de rôles (code_developer.md, security_auditor.md, etc.) non référencés dans `AI_INSTRUCTIONS.md` ni dans le code.

A garder pour utilisation future

## 8. Répertoires vides ou quasi-vides

| Répertoire | Contenu | Action |
|------------|---------|--------|
| `doc/analysis/` | Vide | Supprimer |
| `doc/test_cases/` | 1 fichier (`navigation_phase1_test_cases.md`) | Déplacer dans `doc/testing/`, supprimer le répertoire |
| `doc/diagrams/` | 1 fichier (`phase6_dual_mode_architecture.puml`) | Déplacer dans `doc/plans/diagrams/`, supprimer le répertoire |
| `doc/devops/` | 1 fichier (`ci_cd_plan.md`) | Déplacer dans `doc/plans/`, supprimer le répertoire |

## 9. Fichiers `doc/` racine

| Fichier | Action | Justification |
|---------|--------|---------------|
| `doc/todo.md` | Supprimer | Fichier vide (juste un titre), le backlog le remplace |
| `doc/GLIDER_FLIGHT_TEST_REFACTORING.md` | Supprimer | Résumé de refactoring ponctuel |
| `doc/test-database-encrypted.md` | Vérifier doublon avec `TEST_DATABASE_ENCRYPTED_IMPLEMENTATION.md` racine | Consolider |

## 10. `doc/development/` — nettoyage

| Fichier | Action | Justification |
|---------|--------|---------------|
| `prompt_guideline.md` | Supprimer | Guide Copilot, doublon partiel avec `AI_INSTRUCTIONS.md` |
| `gvv_structure_compta.sql` | Déplacer → `doc/compta/` | Fichier SQL mal placé |
| `development_status.md` | Mettre à jour | Mentionne un successeur Laravel, est-ce toujours d'actualité ? | oui

## Résumé chiffré

| Action | Nb fichiers | Détail |
|--------|-------------|--------|
| Supprimer fichiers | 41 | racine (5) + summaries (23) + testing (5) + design_notes (3) + plans (1) + bugs (1) + doc/ racine (2) + development (1) |
| Archiver fichiers | 4 | plans (3) + bugs (1) |
| Déplacer fichiers | 8 | racine (4) + répertoires quasi-vides (3) + development (1) |
| Renommer | 1 | `TESTING-UPDATED.md` → `TESTING.md` |
| Vérifier/Consolider | 2 | `ci_to_phpunit_migration.md`, `test-database-encrypted.md` |
| Mettre à jour | 1 | `development_status.md` |
| Supprimer répertoires | 5 | `summaries/`, `analysis/`, `test_cases/`, `diagrams/`, `devops/` |
| **Total** | **57 fichiers, 5 répertoires** | |
