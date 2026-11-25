# Suggestions d'amélioration

Mis à jour le 2026-11-25. Fiches classées par section ; index trié par rapport
bénéfice/coût. Statuts : proposée · acceptée · en cours · faite · reportée · refusée.

Ce fichier contient les idées et propositions qui ne sont pas encore du travail décidé.
Le travail décidé est dans `doc/todo.md`, le travail réalisé dans `doc/done.md`.
Règles de tenue et commandes associées : section « Improvement Suggestions » de `CLAUDE.md`.

Règles de tenue :
- Identifiants stables (`S-001`, `S-002`…), jamais réutilisés.
- Bénéfices et coûts toujours décrits, pas seulement notés. Coût : S, moins d'une
  session ; M, quelques sessions ; L, un chantier.
- Rapport bénéfice/coût : élevé, moyen ou faible. Il classe l'index.
- Pas de doublon : si une idée revient, compléter la fiche existante
  (« revue le … : toujours pertinente parce que … »).
- Une suggestion refusée garde sa raison. On ne la repropose que si un fait nouveau
  change la donne.
- Une suggestion faite ou refusée depuis plus de 3 mois passe dans l'archive.
- À chaque mise à jour, changer la date en tête et l'index.

Modèle de fiche :

```markdown
### S-NNN · Titre
- **Statut** : proposée · **Date** : AAAA-MM-JJ · **Origine** : au fil de l'eau | /suggestions | audit | revue
- **Constat** :
- **Proposition** :
- **Bénéfices** :
- **Coûts** : S/M/L. Ce qu'il faut faire, risques, ce qu'on perd.
- **Rapport** :
- **Premier pas** :
- **Décision** : (date, choix, raison)
```

## Index

| ID | Section | Suggestion | Bénéfice | Coût | Rapport | Statut |
|---|---|---|---|---|---|---|
| S-004 | Système | Cron testable en ligne de commande sur la plateforme de développement | testabilité des entrées cron | S | élevé | proposée |
| S-005 | Méthodes | Exécuter en série les tests Playwright qui modifient program.php | fiabilité de la suite | S | moyen | proposée |
| S-003 | Architecture | Un seul mécanisme de chiffrement des secrets (Secret_box) | une clé, une méthode | M | moyen | proposée |
| S-001 | Saisie des vols | Accès à un carnet de vol ULM pour les membres | à évaluer | à évaluer | à évaluer | proposée |
| S-002 | Membres | Seconde adresse email | à évaluer | à évaluer | à évaluer | proposée |

## 1. Système et organisation du projet
Artefacts, chaînes de fabrication, doublons, répartition des rôles entre outils.

### S-004 · Cron testable en ligne de commande sur la plateforme de développement
- **Statut** : proposée · **Date** : 2026-11-25 · **Origine** : au fil de l'eau (synchronisation Google Agenda)
- **Constat** : le `config.php` local de gvv.net fixe `uri_protocol = REQUEST_URI`. En ligne de commande, CodeIgniter ne route alors pas les arguments : `php index.php reservation_scheduler cron` et `php index.php agenda_google_traitement cron` ne font rien, sans message. `config.example.php` utilise `AUTO`, qui route la ligne de commande.
- **Proposition** : passer le `config.php` de développement à `AUTO`, comme l'exemple.
- **Bénéfices** : les entrées cron (rappels, alarmes, Google Agenda) deviennent testables localement, comme en production.
- **Coûts** : S. Risque : une adresse du site qui dépendrait de `REQUEST_URI` ; à vérifier par la suite Playwright.
- **Rapport** : élevé.
- **Premier pas** : passer à `AUTO`, lancer la suite Playwright et une cron en ligne de commande.
- **Décision** :

## 2. Architecture
Découpage en modules, flux de données, dépendances, choix techniques.

### S-003 · Un seul mécanisme de chiffrement des secrets
- **Statut** : proposée · **Date** : 2026-11-25 · **Origine** : au fil de l'eau (synchronisation Google Agenda)
- **Constat** : les paiements en ligne chiffrent leurs secrets avec openssl et une clé saisie à la main (`helloasso_crypto.php`), dans leur modèle. La synchronisation Google Agenda utilise `Secret_box` (sodium, clé générée automatiquement dans `agenda_google.php`). Deux méthodes, deux clés à sauvegarder.
- **Proposition** : faire utiliser `Secret_box` par les paiements en ligne, avec une clé commune.
- **Bénéfices** : une seule clé à sauvegarder, une seule méthode à maintenir, plus de clé par défaut identique d'une installation à l'autre.
- **Coûts** : M. Migrer les secrets HelloAsso déjà chiffrés (déchiffrer avec l'ancienne clé, rechiffrer) ; risque de perdre l'accès HelloAsso si la migration échoue.
- **Rapport** : moyen.
- **Premier pas** : relever les lectures et écritures de secrets chiffrés dans `paiements_en_ligne_model.php`.
- **Décision** :

## 3. Méthodes de développement
Workflow git, tests, CI, revues, documentation, usage de l'assistant.

### S-005 · Exécuter en série les tests Playwright qui modifient program.php
- **Statut** : proposée · **Date** : 2026-11-25 · **Origine** : au fil de l'eau (synchronisation Google Agenda)
- **Constat** : `agenda-google-sync.spec.js` écrit des clés dans `program.php` puis les restaure. Les tests qui enregistrent la page Configuration écrivent le même fichier, et la suite tourne en parallèle : une écriture peut en écraser une autre.
- **Proposition** : regrouper les tests qui modifient les fichiers de configuration dans un projet Playwright exécuté en série, après les autres.
- **Bénéfices** : suite plus fiable, pas de configuration laissée dans un état inattendu.
- **Coûts** : S. Un projet de plus dans `playwright.config.js` ; durée de la suite légèrement allongée.
- **Rapport** : moyen.
- **Premier pas** : lister les tests qui écrivent `program.php` ou passent par la page Configuration.
- **Décision** :

## 4. Interface et parcours
Cohérence, accessibilité, parcours naturels, mobile.

## 5. Par fonctionnalité
Une sous-section par fonctionnalité, nommée comme dans le tableau des fonctionnalités
de `CLAUDE.md`.

### 5.1 Saisie des vols

### S-001 · Accès à un carnet de vol ULM pour les membres
- **Statut** : proposée · **Date** : 2026-11-09 · **Origine** : `doc/todo.md`, section « Idées et suggestions »
- **Constat** : idée notée sans détail dans `doc/todo.md`.
- **Proposition** : donner aux membres l'accès à leur carnet de vol ULM.
- **Bénéfices** : à évaluer.
- **Coûts** : à évaluer.
- **Rapport** : à évaluer.
- **Premier pas** : préciser le besoin (contenu du carnet, différences avec le carnet planeur existant).
- **Décision** :

### 5.2 Membres

### S-002 · Seconde adresse email
- **Statut** : proposée · **Date** : 2026-11-09 · **Origine** : `doc/todo.md`, section « Idées et suggestions »
- **Constat** : idée notée sans détail dans `doc/todo.md`.
- **Proposition** : permettre d'enregistrer une seconde adresse email pour un membre.
- **Bénéfices** : à évaluer.
- **Coûts** : à évaluer.
- **Rapport** : à évaluer.
- **Premier pas** : préciser l'usage de la seconde adresse (envois en copie, listes de diffusion, connexion).
- **Décision** :

## 6. Transverses
Performances, sécurité, qualité, documentation.

## 7. Idées pour d'autres projets
Suggestions hors du périmètre actuel, gardées pour l'inspiration.

## Archive
Suggestions faites ou refusées depuis plus de 3 mois (fiche complète, statut final).
