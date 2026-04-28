# PRD : Migration PHP 8

**Version du Document :** 1.1
**Date :** 2026-04-28
**Statut :** En cours d'évaluation

---

## 1. Contexte

PHP 7.4 est en fin de vie depuis novembre 2022 — plus aucun correctif de sécurité n'est publié. L'hébergeur de GVV facture des frais supplémentaires pour maintenir le support de ce runtime EOL. CodeIgniter 2.x, le framework utilisé par GVV, n'est pas officiellement compatible avec PHP 8.

---

## 2. Options Identifiées

### Option A — Migrer CodeIgniter 2.x vers 4.x

CI4 est compatible PHP 8.1/8.2/8.3. C'est la voie officielle mais la migration est cassante : CI4 n'est pas une mise à jour de CI2, c'est une réécriture complète.

**Conséquences sur GVV :**
- ~50 contrôleurs à réécrire (namespace, classes, injection de dépendances)
- Tous les modèles à réécrire (nouvelle API Active Record)
- Le système de migrations à convertir
- `Gvv_Controller`, `Gvv_Metadata`, toutes les librairies custom à porter
- Les helpers et les vues partiellement compatibles
- Les tests PHPUnit à adapter
- Estimation réaliste : plusieurs mois de travail pour un projet de cette taille

### Option B — Migrer vers un autre framework (Slim, Laravel, Symfony…)

Encore plus coûteux que l'option A. À écarter.

### Option C — Patcher CI2 pour PHP 8

Des forks communautaires de CI2 compatibles PHP 8 existent. CI2 présente plusieurs incompatibilités PHP 8 connues et relativement localisées :

- `FILTER_SANITIZE_STRING` supprimé (PHP 8.1)
- Passages par référence implicites supprimés (PHP 8.0)
- `create_function()` supprimé (PHP 8.0)
- Certains comportements `null` dans les comparaisons (PHP 8.1 strict)
- `mysqli` : changements de comportement sur les erreurs

**Effort estimé : quelques jours** pour les corrections dans `system/` et `application/`, suivi de tests de régression.

### Option D — Rester sur PHP 7.4

**Conséquences :**
- Surcoût hébergeur croissant dans le temps
- Aucun correctif de sécurité PHP depuis fin 2022 — exposition aux CVE non patchées
- Dépendances tierces (TCPDF, Google API…) qui cessent progressivement de supporter PHP 7.4
- Recrutement et contribution externe difficile sur un stack aussi ancien
- Risque de fin de support forcé par l'hébergeur à terme

---

## 3. Risques de l'Option C (CI2 patché)

### 3.1 Risques de sécurité — le plus sérieux

CI2 n'a plus de mainteneur officiel depuis 2015. Les vulnérabilités découvertes dans le framework lui-même ne sont pas patchées :
- Injections, CSRF, XSS au niveau du framework — pas de correction officielle
- Les CVE publiées contre CI2 s'accumulent sans réponse
- Un patch PHP 8 ne corrige que la compatibilité syntaxique, **pas les failles de sécurité du framework**

Pour GVV, le risque est atténué par le fait que l'application tourne sur un réseau relativement restreint (associations), mais elle gère des données personnelles (membres) et financières.

### 3.2 Risques liés au patch lui-même

- **Qualité inconnue** : un fork communautaire peut introduire des régressions ou des failles
- **Divergence** : le fork évolue indépendamment, difficile de suivre les corrections ultérieures
- **Mainteneur unique** : si le fork est abandonné, on se retrouve dans la même situation
- **Compatibilité partielle** : certaines incompatibilités PHP 8 peuvent être subtiles et passer à travers les tests (comportements silencieux modifiés)

### 3.3 Risques techniques à long terme

- PHP 8.3 → 8.4 → 9.x : chaque version majeure peut introduire de nouvelles incompatibilités, nécessitant de nouveaux patches
- Les dépendances tierces (`application/third_party/`) évoluent indépendamment et peuvent devenir incompatibles (TCPDF a migré vers PHP 8 nativement, mais avec une API modifiée)
- Pas de support pour les nouvelles fonctionnalités PHP — pas bloquant pour GVV mais creuse la dette technique

### 3.4 Facteurs atténuants pour GVV

- La suite de tests (~120 PHPUnit + Playwright) permet de détecter les régressions
- L'application est en maintenance mode — peu de code nouveau à risque
- La surface d'attaque est limitée (accès restreint, pas d'API publique)
- La base de code est disciplinée et relativement petite

---

## 4. Comparaison des Options

| Critère | PHP 7.4 EOL | CI2 patché PHP 8 | CI4 PHP 8 |
|---|---|---|---|
| Sécurité PHP runtime | ❌ CVE non patchées | ✅ | ✅ |
| Sécurité framework | ❌ | ❌ idem | ✅ maintenu |
| Coût hébergeur | ❌ surcoût | ✅ | ✅ |
| Effort migration | ✅ nul | 🟡 jours | ❌ mois |
| Pérennité | ❌ | 🟡 quelques années | ✅ |
| Risque de régression | ✅ nul | 🟡 faible | ❌ élevé |

---

## 5. Recommandation

Le patch CI2 résout le problème immédiat (coût hébergeur, runtime EOL) mais **ne résout pas le risque de sécurité framework**. C'est une solution qui achète du temps — 3 à 5 ans réalistement — avant que la pression devienne intenable.

Pour un projet en maintenance mode utilisé par 5-6 associations, **l'option C** est la plus réaliste à court terme :

1. Tester GVV sur PHP 8.1 ou 8.2 dans un environnement de développement
2. Corriger les erreurs dans `system/` (CI2 core) et `application/` qui remontent
3. Valider avec la suite de tests existante (~120 tests PHPUnit + Playwright)
4. Déployer

Si l'application doit continuer au-delà de cet horizon ou si des exigences de conformité (RGPD, assurances) imposent un framework maintenu, la migration CI4 devient inévitable. Le patch CI2 est acceptable comme étape intermédiaire, pas comme stratégie finale.

---

## 6. Exigences (Option C)

### 6.1 Compatibilité cible

- PHP 8.1 minimum (LTS jusqu'en décembre 2025)
- PHP 8.2 souhaitable (LTS jusqu'en décembre 2026)
- Maintien de la compatibilité avec MySQL 5.x / MariaDB existant

### 6.2 Périmètre

- Corriger les incompatibilités dans `system/` (CI2 core) sans modifier l'architecture
- Corriger les incompatibilités dans `application/` (contrôleurs, modèles, librairies, helpers)
- Les dépendances tierces dans `application/third_party/` (TCPDF, phpqrcode…) doivent être vérifiées et mises à jour si nécessaire
- Aucune régression fonctionnelle acceptable

### 6.3 Validation

- Tous les tests PHPUnit existants doivent passer
- Les tests Playwright existants doivent passer
- Un test de smoke sur les parcours critiques (connexion, saisie de vol, facturation, comptabilité) doit être défini et exécuté

### 6.4 Hors périmètre

- Refactoring du code applicatif non lié aux incompatibilités PHP 8
- Migration vers CI4 ou autre framework
- Ajout de nouvelles fonctionnalités
