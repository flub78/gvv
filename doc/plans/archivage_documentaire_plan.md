# Plan d’implémentation — Archivage Documentaire

Date : 3 février 2026

## Références
- PRD : [doc/prds/archivage_documentaire_prd.md](doc/prds/archivage_documentaire_prd.md)
- Analyse existante : [doc/design_notes/reuse_pilot_documents_attachments.md](doc/design_notes/reuse_pilot_documents_attachments.md)

## Objectif
Livrer un module d’archivage documentaire conforme au PRD, réutilisant les mécanismes d’attachements existants, avec versionning, validation, expiration et notifications.

## Hypothèses
- Réutilisation de la table et du stockage existants pour les attachements.
- Types de documents initialement supportés : visite médicale, assurance, brevet (pilotes), documents club/sections.
- Rôles : pilotes et administrateurs (CA).

## Découpage en lots

### Lot 1 — Modèle de données & migration
1. Cartographier les structures existantes (table `attachments`, stockage uploads/documents, helpers de compression).
2. Définir la stratégie de réutilisation/extension : nouveaux champs, table de liaison, ou nouvelle table dédiée avec continuité.
3. Concevoir la migration (schéma, index, contraintes, compatibilité). 
4. Mettre à jour `application/config/migration.php`.
5. Créer tests de migration (up/down) et validation du schéma.

### Lot 2 — Modèles & métadonnées
1. Implémenter/étendre le modèle pour :
   - association à pilote/section/club
   - statuts (en attente/validé)
   - dates de validité et détection d’expiration
   - versionning (liens entre versions)
2. Ajouter les métadonnées dans `application/libraries/Gvvmetadata.php`.
3. Définir les règles de validation (types de fichiers, champs requis).

### Lot 3 — Contrôleurs & permissions
1. Créer/étendre les contrôleurs pour :
   - dépôt document par pilote
   - validation admin
   - suppression conditionnelle (en attente uniquement)
   - listes “à valider” et “expirés”
2. Vérifier l’accès par rôle (pilote/admin).
3. Ajouter les routes nécessaires dans `application/config/routes.php`.

### Lot 4 — Vues & UX
1. Liste documents pilote (statuts, expiration, versions).
2. Liste admin “à valider”.
3. Liste admin “expirés”.
4. Détail document avec historique de versions.
5. Indicateurs visuels (expiré, en attente, validé) via Bootstrap 5.

### Lot 5 — Notifications
1. Modèle de préférences d’abonnement (par type de document et délai).
2. Tâche d’envoi d’alertes (cron/script existant ou nouveau).
3. Notification à la connexion (bannière ou alertes en UI).

### Lot 6 — Internationalisation
1. Ajouter les libellés FR/EN/NL.
2. Vérifier que tous les libellés UI utilisent `$this->lang->line()`.

### Lot 7 — Tests & validation
1. Tests unitaires : modèles, helpers, expiration.
2. Tests intégration : listes admin, workflow validation, versionning.
3. Tests UI Playwright : dépôt, validation, affichage expiré.
4. Smoke tests : phpunit + playwright.

## Plan de tâches détaillé (statuts)
1. Étudier la table `attachments` et le stockage actuel (not-started).
2. Choisir la stratégie d’extension (not-started).
3. Rédiger la migration et la rollback (not-started).
4. Implémenter/étendre le modèle d’archivage (not-started).
5. Ajouter la métadonnée pour les champs (not-started).
6. Implémenter les contrôleurs et actions (not-started).
7. Ajouter les vues avec indicateurs (not-started).
8. Mettre en place les notifications email et à la connexion (not-started).
9. Ajouter i18n FR/EN/NL (not-started).
10. Écrire et exécuter les tests (not-started).

## Critères de fin
- Workflow complet : dépôt → validation → versionning → expiration.
- Listes admin fonctionnelles (à valider, expirés).
- Notifications envoyées et affichées.
- Tests unitaires et Playwright green.
