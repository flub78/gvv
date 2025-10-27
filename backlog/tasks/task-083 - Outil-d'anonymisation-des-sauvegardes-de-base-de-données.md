---
id: task-083
title: Outil d'anonymisation des sauvegardes de base de données
status: Done
assignee: []
created_date: '2025-10-13 09:50'
updated_date: '2025-10-27 17:55'
labels:
  - feature
  - security
  - anonymization
  - database
dependencies: []
priority: medium
ordinal: 1000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Création d'un script Python pour anonymiser les données personnelles dans les sauvegardes de base de données GVV tout en préservant l'intégrité référentielle et la structure de la base.

**Fonctionnalités implémentées:**
- Anonymisation complète des données personnelles (noms, emails, téléphones, adresses)
- Préservation de l'intégrité référentielle (même personne = même identité fictive partout)
- Support de multiples formats (SQL, SQL.gz, ZIP)
- Données françaises réalistes (prénoms, noms, villes, téléphones français)
- Structure de base de données préservée
- Utilisable pour développement et tests

**Fichiers créés:**
- `bin/anonymize.py` - Script principal d'anonymisation
- `doc/database_anonymization.md` - Documentation complète

**Parent Story:** [task-082](task-082) - Documentation et configuration
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Script fonctionnel pour tous les formats de sauvegarde
- [ ] #2 Documentation complète disponible
- [ ] #3 Tests validés sur échantillons
- [ ] #4 Données personnelles complètement anonymisées
- [ ] #5 Intégrité référentielle préservée
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
## Améliorations robustesse

✅ **Gestion des encodages multiples**
- Détection automatique d'encodage (UTF-8, Latin-1, CP1252, ISO-8859-1)
- Fallback gracieux en cas d'échec de détection
- Support des caractères français accentués

✅ **Gestion d'erreurs améliorée**
- Récupération gracieuse en cas d'échec d'anonymisation
- Messages d'erreur informatifs avec conseils
- Traitement ligne par ligne pour les gros fichiers

✅ **Dépendances optionnelles**
- Chardet pour une meilleure détection d'encodage
- Fichier requirements-anonymize.txt
- Instructions d'installation dans l'aide

✅ **Tests validés**
- Fichiers avec encodages multiples
- Fichiers compressés problématiques
- Caractères spéciaux français
- Récupération d'erreurs

## Clarification du périmètre

✅ **Type de fichiers supportés clarifiés**
- Script conçu pour les sauvegardes SQL (base de données)
- Détection automatique du type de fichier
- Messages d'erreur clairs pour les types non supportés

✅ **Formats supportés**
- .sql (dumps SQL)
- .sql.gz (dumps SQL compressés)
- .zip (contenant des fichiers SQL)

❌ **Formats NON supportés**
- .tar.gz (archives média/attachements)
- .tar (archives)
- Archives de médias (PDF, images, etc.)

✅ **Documentation mise à jour**
- Aide contextuelle améliorée
- Exemples d'utilisation clarifiés
- Portée du script précisée dans la documentation

## Correction erreurs SQL

✅ **Problème résolu : erreur SQL 1064**
- Mapping correct des colonnes de la table membres (33 colonnes)
- Gestion appropriée des villes avec espaces (Le Mans, Le Havre)
- Amélioration de l'échappement des valeurs SQL
- Validation de la syntaxe SQL générée

✅ **Structure database réelle intégrée**
- Analyse du vrai schéma de base de données
- Ordre des colonnes corrigé pour INSERT sans spécification de colonnes
- Support complet des champs réels (mtelm, mtelf, memailparent, etc.)

✅ **Tests validés**
- Script fonctionne sur la vraie sauvegarde db444903897_backup_a__roclub_d_abbeville_20251012_081529_migration_39.zip
- SQL généré syntaxiquement correct
- Import en base possible sans erreurs
<!-- SECTION:NOTES:END -->
