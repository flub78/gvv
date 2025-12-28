# Migration des anciennes versions de GVV (pré-GitHub)

Ce document décrit une stratégie sûre pour migrer une base de données GVV très ancienne (non mise à jour depuis >2 ans, avant la migration vers GitHub) vers la version actuelle du code. L’objectif est de minimiser les risques (perte de données, indisponibilité) en passant par un environnement de staging et en validant chaque étape.

## Contexte et risques

- Le code actuel cible PHP 7.4, MySQL 5.x et CodeIgniter 2.x. Voir `setenv.sh` qui force l’utilisation de `/usr/bin/php7.4`.
- Les migrations de schéma vont jusqu’à la version 58 (`application/config/migration.php`). Le dossier `application/migrations/` contient les migrations 001→058.
- Baseline déploiement légacy: la version de migration de référence était `20` (le mécanisme de migration était déjà actif sur ces déploiements). Cela signifie que les instances anciennes ont très probablement une table `migrations` avec `version=20`, ou au minimum un schéma cohérent avec les migrations ≤20.

- Les instances très anciennes peuvent:
  - manquer des tables clés ajoutées récemment (ex. système d’autorisations refactoré vers 042, listes de diffusion 049→054),
  - dépendre de fichiers de configuration SVN (procédure de migration GitHub exige de réinstaller les configs et de recréer `uploads/`).
- Restaurer un dump très ancien et démarrer le site sans migration provoque des erreurs applicatives (modèles et vues attendent des colonnes/tables inexistantes).

## Pré-requis

- Environnement de staging isolé (VM ou serveur de test) avec:
  - PHP 7.4 confirmé (`source setenv.sh`, `php -v`),
  - MySQL 5.x,
  - serveur web (Apache/Nginx) opérationnel.
- Accès au code GVV actuel (GitHub) et à la sauvegarde de la base ancienne.
- Fichiers de configuration réinstallés après clonage GitHub (voir `doc/migration_github.md`).
- Espace disque et `memory_limit` suffisants pour backup/restore (voir `doc/installation.md`).

## Stratégie recommandée (staging + migrations par lots)

1. Préparer le staging
   - Cloner GVV depuis GitHub, réinstaller les configs (`application/config/*`), recréer `uploads/` et `uploads/restore/`.
   - Vérifier l’accès à `/install` et la page d’accueil avec la base vide.
   - 
2. Importer la base ancienne dans une base de test
   - Créer une base MySQL dédiée au staging et y restaurer le dump ancien.
   - Ne pas pointer la prod vers cette base à ce stade.
   - 
3. Lire la version de schéma (mécanisme actif)
   - Lire `SELECT version FROM migrations ORDER BY version DESC LIMIT 1` (la table existe car le mécanisme de migration était actif en légacy).
   - Baseline attendue pour un déploiement légacy: `version=20`.
   - 
4. Appliquer les migrations par batches
   - Mettre `application/config/migration.php` vers une cible intermédiaire (ex. 042, puis 049, puis 058), ou utiliser l’outil interne si disponible.
   - Entre chaque batch:
     - sauvegarder la base,
     - vérifier la création/modification des tables attendues,
     - lancer les tests rapides.
5. Valider l’application
   - Se connecter et vérifier les écrans sensibles (rôles/autorisation, membres, vols, facturation, listes email).
   - Exécuter `./run-all-tests.sh` (unit/integration) et un smoke test Playwright (`run/run-playwright.sh` si configuré).
6. Basculer vers la prod
   - Après validations, basculer les configs (URL, SSL, club.php, etc.) et planifier la bascule (fenêtre de maintenance courte).

## Alternative "ETL" (export/import de données)

Utiliser une approche d’extraction et de réimportation dans une base neuve au dernier schéma:
- Restaurer la base ancienne dans un environnement isolé.
- Exporter les entités (membres, machines, vols, écritures, produits, etc.) en CSV.
- Importer dans une base vierge déjà migrée à 058, via scripts d’import et contrôles de correspondance (mapping champs anciens→nouveaux, sections, rôles).
- Avantages: réduit les échecs liés à des écarts de schéma importants; inconvénients: plus de travail de mapping et de vérification.

## Incompatibilités fréquentes à anticiper

- Système d’autorisations (042→047): tables `types_roles`, `user_roles_per_section`, `role_permissions`, `authorization_audit_log` et index associés.
- Listes de diffusion (049→054): tables `email_lists`, `email_list_roles`, `email_list_members`, `email_list_external` + dossiers `uploads/email_lists/<id>/`.
- Champs étendus membres/sections (036, 040, 041, 056): colonnes nouvellement obligatoires ou index.
- Valeurs par défaut et contraintes (019, 027, 055, 057, 058): not null, timestamps, longueurs.
- Fichiers de configuration: sous GitHub, réinstaller configs et ne pas écraser ceux spécifiques au club.

## Procédure détaillée (pas-à-pas conseillé)

1. Sauvegardes
   - `mysqldump` complet avant toute action.
   - Backup après chaque batch de migrations.
2. Mise en place
   - `source setenv.sh` (PHP 7.4), configs (`config.php`, `club.php`), `uploads/`.
3. Table `migrations`
   - Lire la version courante via `SELECT version FROM migrations ORDER BY version DESC LIMIT 1` (attendue ≥ 20 sur un déploiement légacy).
4. Batches de migration
   - Batch A: depuis 20 (baseline légacy) monter vers 042 (autorisation). Corriger manuellement si des colonnes manquent dans `types_roles` et `user_roles_per_section`.
   - Batch B: monter vers 049→054 (email lists). Vérifier la création des 4 tables et des dossiers.
   - Batch C: monter vers 058 (vols découverte timestamps, affichage sections, etc.).
5. Vérifications fonctionnelles
   - Rôles: assignation/révocation dans l’UI; présence des index et dates (`granted_at`).
   - Listes email: création de liste, ajout de membres/externes, upload de fichier, génération d’adresses.
   - Données cœur (vols, membres, facturation): création/modif/suppression, cohérence des FK.
6. Tests
   - `./run-all-tests.sh` pour une validation rapide.
   - Lancer les tests ciblés (authorization, exports, helpers).
7. Bascule
   - Mettre à jour `base_url` et paramètres club, activer SSL, surveiller les logs.

## Validation et contrôle qualité

- Contrôles SQL ciblés:
  - Comptage des lignes dans tables nouvellement créées,
  - Présence des colonnes ajoutées par les migrations (ex. `granted_at`, `scope`, `translation_key`).
- Journaux applicatifs:
  - Absence d’erreurs liées à tables/colonnes manquantes,
  - Traçabilité des opérations d’autorisation et des listes email.
- Tests automatisés:
  - Unit/integration OK,
  - Smoke E2E OK sur pages clés.

## Rollback

- En cas d’échec, restaurer le dump pris juste avant le batch en cours.
- Documenter l’erreur et corriger avant de rejouer le batch.
- Conserver un journal des migrations appliquées (version et date).

## Checklists

- Environnement
  - [ ] `source setenv.sh`, PHP 7.4 actif
  - [ ] Base MySQL dédiée au staging
  - [ ] Configs réinstallées, `uploads/` recréés
- Base
   - [ ] Version de migration lue (attendue ≥ 20 en légacy)
  - [ ] Backups avant/après chaque batch
- Fonctionnel
  - [ ] Rôles: assignation/révocation OK
  - [ ] Listes email: création, ajout, upload, comptage OK
  - [ ] Vols/membres/facturation CRUD OK
- Qualité
  - [ ] `./run-all-tests.sh` OK
  - [ ] Smoke E2E OK
  - [ ] Logs sans erreurs de schéma

## Notes

- La procédure de migration GitHub (voir `doc/migration_github.md`) reste applicable: réinstaller les configs et les répertoires d’uploads après le clone.
- La mémoire PHP (`memory_limit`) peut nécessiter d’être augmentée pour les opérations de sauvegarde/restauration.
