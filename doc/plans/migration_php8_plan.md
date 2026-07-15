# Plan d'implémentation — Migration PHP 8 (Option C : CI2 patché)

**PRD de référence** : `doc/prds/migration_php8.md`
**Statut global** : ⬜ Non démarré
**Dernière mise à jour** : 2026-07-15

Ce plan couvre l'exécution de l'Option C retenue dans le PRD : rendre CI2 + GVV compatibles PHP 8.x sans changer d'architecture, valider par la suite de tests existante, et déployer. Les décisions et risques sont documentés dans le PRD (§3 à §5) et ne sont pas reproduits ici.

---

## Constat environnement (préalable au plan)

Le PRD cible PHP 8.4 (LTS jusqu'en décembre 2028). L'environnement de développement local propose PHP 7.4 et 8.3/8.4/8.5. Avant de démarrer :

- [ ] Confirmer auprès de l'hébergeur que PHP 8.4 est disponible pour GVV
- [ ] Ce plan teste contre **PHP 8.4** ; à ajuster si l'hébergeur ne propose pas encore cette version

---

## Étapes

### Étape 1 : Environnement de test PHP 8 en parallèle du 7.4

**Objectif** : Pouvoir basculer entre PHP 7.4 et PHP 8 sans casser l'environnement de développement existant.

**Actions** :
1. Créer `bin/php8` (lien symbolique vers `/usr/bin/php8.4`), sur le modèle de `bin/php` → `/usr/bin/php7.4`
2. Créer `setenv-php8.sh` sur le modèle de `setenv.sh`, plaçant `bin/` en tête de `PATH` avec `php` pointant vers `bin/php8`
3. Créer une branche git dédiée (`php8-migration`) pour isoler les correctifs jusqu'à validation complète
4. Vérifier qu'une copie de la base de données de développement est disponible pour les tests sous PHP 8 (ne pas travailler sur une base de production)

**Validation** :
- [ ] `source setenv-php8.sh && php -v` affiche PHP 8.4
- [ ] `source setenv.sh && php -v` affiche toujours PHP 7.4 (aucune régression sur l'environnement existant)
- [ ] Branche `php8-migration` créée

**Statut** : ⬜ À faire

---

### Étape 2 : Baseline de référence sous PHP 7.4

**Objectif** : Disposer d'un état de référence (tests, couverture) avant toute modification, pour détecter toute régression introduite par les correctifs.

**Actions** :
1. `source setenv.sh`
2. `./run-all-tests.sh --coverage` — noter le nombre de tests, succès/échecs, couverture globale
3. `cd playwright && npx playwright test --reporter=line` — noter le résultat
4. Consigner ces résultats dans ce document (section Notes techniques) comme référence de non-régression

**Validation** :
- [ ] Baseline PHPUnit consignée (nombre de tests, taux de succès, couverture %)
- [ ] Baseline Playwright consignée

**Statut** : ⬜ À faire

---

### Étape 3 : Audit de compatibilité PHP 8

**Objectif** : Produire l'inventaire complet des incompatibilités PHP 8 dans `system/`, `application/` et `application/third_party/`, avant de corriger quoi que ce soit.

**Actions** :
1. Lint de masse avec le binaire PHP 8 (détecte les erreurs fatales de syntaxe/parse) :
   ```bash
   find system application -name "*.php" -exec bin/php8 -l {} \; 2>&1 | grep -v "No syntax errors"
   ```
2. Recherche ciblée des patterns d'incompatibilité connus listés au PRD §3.2 :
   - `FILTER_SANITIZE_STRING` (supprimé PHP 8.1)
   - `create_function(` (supprimé PHP 8.0)
   - Passages par référence implicites (paramètres appelés avec `&$var` côté appelant sans `&` dans la signature)
   - `each(` (supprimé PHP 8.0)
   - Comparaisons `==`/`switch` sensibles au changement de comportement string-to-number PHP 8
   - Accès `$str{0}` (syntaxe accolade sur chaîne, supprimée PHP 8.0)
3. Vérifier spécifiquement le pilote `mysqli` de CI2 (`system/database/drivers/mysqli/`) : PHP 8.1 active `mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT)` par défaut, qui lève des exceptions au lieu de retourner `false`. Le driver CI2 s'appuie probablement sur un retour `false` — point de rupture potentiel majeur, à vérifier en priorité.
4. Comparer avec les correctifs déjà publiés par les forks communautaires CI2-PHP8 mentionnés au PRD (référence pour accélérer, pas copie aveugle — revue de chaque changement avant application)
5. Produire un inventaire structuré (fichier, ligne, catégorie d'incompatibilité, correctif envisagé) en annexe de ce document

**Validation** :
- [ ] Lint de masse exécuté, liste des erreurs fatales consignée
- [ ] Recherche par pattern exécutée pour chaque catégorie du PRD §3.2
- [ ] Comportement `mysqli_report` sous PHP 8 vérifié et documenté
- [ ] Inventaire complet ajouté en annexe de ce plan

**Statut** : ⬜ À faire

---

### Étape 4 : Correctifs `system/` (noyau CI2)

**Objectif** : Rendre le noyau CodeIgniter 2.x exécutable sous PHP 8 sans modifier son architecture.

**Actions** :
1. Corriger dans l'ordre les incompatibilités identifiées à l'Étape 3 qui bloquent le démarrage de l'application (erreurs fatales en premier)
2. Traiter le point `mysqli_report` : soit forcer `mysqli_report(MYSQLI_REPORT_OFF)` au chargement du driver pour préserver le comportement historique (retour `false` + `$this->db->error()`), soit adapter la gestion d'erreur du driver — choisir l'option la moins intrusive
3. Corriger les passages par référence implicites et les fonctions supprimées dans `system/`
4. Ne pas toucher aux fichiers `system/` au-delà des incompatibilités PHP 8 identifiées (pas de refactoring, conformément au PRD §6.2 et à la politique du projet)

**Validation** :
- [ ] `find system -name "*.php" -exec bin/php8 -l {} \;` ne remonte plus aucune erreur
- [ ] L'application démarre sous PHP 8 (page de login accessible) sans erreur fatale
- [ ] Aucune modification hors du périmètre des incompatibilités PHP 8

**Statut** : ⬜ À faire

---

### Étape 5 : Correctifs `application/` (contrôleurs, modèles, librairies, helpers)

**Objectif** : Corriger les incompatibilités PHP 8 dans le code applicatif.

**Actions** :
1. Traiter les incompatibilités par catégorie (regrouper par pattern plutôt que par fichier, pour cohérence des correctifs) :
   - `helpers/` en premier (utilisés partout, effet de levier le plus large)
   - `libraries/` — attention particulière à `Gvvmetadata.php` (central) et `Gvv_Controller`
   - `models/`
   - `controllers/`
2. Porter une attention particulière aux comparaisons `null` en mode strict PHP 8.1 (passage de `null` à des fonctions `string` typées, déprécié) — fréquent dans du code CI2 ancien qui ne type pas ses paramètres
3. Ne corriger que les incompatibilités PHP 8 — pas de refactoring ni de changement de comportement fonctionnel (PRD §6.4, hors périmètre)

**Validation** :
- [ ] `find application -path application/third_party -prune -o -name "*.php" -print -exec bin/php8 -l {} \;` ne remonte plus aucune erreur
- [ ] Chaque correctif correspond à une entrée de l'inventaire de l'Étape 3 (pas de changement hors périmètre)
- [ ] Aucune régression fonctionnelle visible en navigation manuelle rapide

**Statut** : ⬜ À faire

---

### Étape 6 : Dépendances tierces (`application/third_party/`)

**Objectif** : Vérifier et, si nécessaire, mettre à jour les bibliothèques vendues pour leur compatibilité PHP 8.

**Actions** :
1. `tcpdf` : vérifier la version bundlée contre la matrice de compatibilité PHP 8 officielle TCPDF ; noter que l'API a changé sur les versions natives PHP 8 (PRD §3.3) — évaluer si une mise à jour de version est nécessaire ou si la version actuelle fonctionne telle quelle
2. `phpqrcode` : bibliothèque non maintenue, tester directement sous PHP 8 (lint + génération d'un QR code de test) et corriger localement si besoin
3. `google-api-php-client` : vérifier compatibilité PHP 8 de la version vendue ; c'est la dépendance la plus susceptible de nécessiter un remplacement de fichiers vendorisés (le SDK Google évolue vite)
4. `pChart`, `Requests`, `fpdf.php` : lint PHP 8 + test fonctionnel minimal
5. Documenter pour chaque bibliothèque : version actuelle, statut de compatibilité, action prise

**Validation** :
- [ ] Chaque bibliothèque de `application/third_party/` lintée sous PHP 8 sans erreur fatale
- [ ] Génération PDF (TCPDF) fonctionnelle sous PHP 8
- [ ] Génération QR code fonctionnelle sous PHP 8
- [ ] Intégration Google (si utilisée en test) fonctionnelle sous PHP 8
- [ ] Tableau de statut des dépendances tierces consigné dans ce document

**Statut** : ⬜ À faire

---

### Étape 7 : Validation par la suite de tests existante

**Objectif** : Confirmer l'absence de régression fonctionnelle par rapport à la baseline PHP 7.4 (Étape 2).

**Actions** :
1. `source setenv-php8.sh`
2. `./run-all-tests.sh --coverage`
3. Comparer avec la baseline de l'Étape 2 : même nombre de tests, même taux de succès, couverture équivalente ou supérieure
4. Pour chaque échec : déterminer si la cause est une incompatibilité PHP 8 non corrigée (retour à l'Étape 4/5) ou un test à adapter marginalement (ex. message d'erreur PHP différent entre 7.4 et 8)
5. Itérer jusqu'à zéro régression

**Validation** :
- [ ] Tous les tests PHPUnit passent sous PHP 8
- [ ] Aucun test n'a été supprimé ou affaibli pour faire passer la suite (seule une adaptation au comportement PHP 8 légitime est acceptable)
- [ ] Couverture globale maintenue ou améliorée par rapport à la baseline

**Statut** : ⬜ À faire

---

### Étape 8 : Tests Playwright end-to-end

**Objectif** : Valider le comportement de l'application dans un navigateur réel sous PHP 8, y compris les erreurs PHP 8 qui ne se manifestent qu'à l'exécution (warnings/deprecations injectés dans le HTML de sortie).

**Actions** :
1. Démarrer l'application sous PHP 8 (`source setenv-php8.sh`)
2. `cd playwright && npx playwright test --reporter=line`
3. Vérifier spécifiquement qu'aucun warning/deprecation PHP 8 ne fuite dans le rendu HTML des pages (activer temporairement le mode développement dans `index.php` pour repérer les notices, cf. CLAUDE.md règle 22)
4. Corriger les régressions trouvées (retour Étape 5) et itérer

**Validation** :
- [ ] Tous les tests Playwright existants passent sous PHP 8
- [ ] Aucune notice/warning/deprecation PHP visible dans le HTML rendu sur les parcours testés
- [ ] Comparaison avec la baseline Playwright de l'Étape 2 : pas de régression

**Statut** : ⬜ À faire

---

### Étape 9 : Test de fumée sur les parcours critiques

**Objectif** : Validation manuelle finale des parcours métier essentiels (PRD §6.3), au-delà de ce que couvrent les suites automatisées.

**Actions** :
1. Utiliser les utilisateurs de test définis dans `bin/create_test_users.sql`
2. Sous PHP 8, dérouler manuellement :
   - Connexion (login/logout, mot de passe oublié)
   - Saisie d'un vol (planeur et avion/ULM)
   - Facturation (génération d'une facture/décompte)
   - Comptabilité (consultation journal, export)
3. Consigner tout comportement anormal (erreur, rendu cassé, warning PHP visible)

**Validation** :
- [ ] Connexion : OK sous PHP 8
- [ ] Saisie de vol : OK sous PHP 8
- [ ] Facturation : OK sous PHP 8
- [ ] Comptabilité : OK sous PHP 8
- [ ] Aucune erreur PHP visible dans les logs (`application/logs/`) pendant le parcours

**Statut** : ⬜ À faire

---

### Étape 10 : Documentation et bascule de l'environnement par défaut

**Objectif** : Documenter les correctifs pour la maintenance future et basculer l'environnement de développement sur PHP 8 par défaut une fois la validation complète.

**Actions** :
1. Créer `doc/design_notes/migration_php8_design.md` : liste des zones patchées (system/, application/, third_party/), nature des correctifs par catégorie, points d'attention pour les futures montées de version PHP (8.5…)
2. Mettre à jour `setenv.sh` pour pointer vers PHP 8 comme version par défaut du projet (une fois la bascule décidée) ; conserver `setenv-php7.sh` en secours le temps de la période de transition
3. Mettre à jour la section "Environnement Setup" de `CLAUDE.md`/`doc/AI_INSTRUCTIONS.md` pour refléter la nouvelle version cible

**Validation** :
- [ ] Design note rédigée et revue
- [ ] `setenv.sh` et documentation à jour et cohérents avec la version réellement déployée
- [ ] Revue par l'utilisateur avant bascule définitive

**Statut** : ⬜ À faire

---

### Étape 11 : Déploiement

**Objectif** : Basculer les instances de production sur PHP 8 sans interruption de service ni perte de données.

**Actions** :
1. Confirmer avec l'hébergeur la procédure de changement de version PHP par instance
2. Sauvegarder la base de données de chaque instance avant bascule (`mysqldump`)
3. Déployer sur une première instance pilote, surveiller les logs applicatifs et serveur pendant 48h
4. Si stable, déployer sur les instances restantes
5. Définir la procédure de rollback : conserver l'accès PHP 7.4 disponible côté hébergeur le temps de la période de stabilisation ; en cas de blocage, revenir à PHP 7.4 avec le code pré-migration (tag git dédié)

**Validation** :
- [ ] Sauvegarde de chaque instance réalisée avant bascule
- [ ] Instance pilote stable pendant 48h sans erreur
- [ ] Toutes les instances basculées
- [ ] Tag git `pre-php8-migration` créé sur le dernier commit PHP 7.4 pour rollback rapide si nécessaire

**Statut** : ⬜ À faire

---

## Risques et mitigations

| Risque | Probabilité | Impact | Mitigation |
|--------|-------------|--------|------------|
| `mysqli_report` strict par défaut (PHP 8.1+) fait lever des exceptions non catchées là où CI2 attendait un retour `false` | Élevée | Critique | Traité en priorité à l'Étape 4 ; forcer `MYSQLI_REPORT_OFF` si le driver n'est pas adapté |
| Incompatibilité subtile non détectée par lint ni par les tests (comportement silencieux modifié) | Moyenne | Élevé | Test de fumée manuel (Étape 9) + surveillance renforcée post-déploiement (Étape 11) |
| Dépendance tierce (Google API client notamment) incompatible et nécessitant remplacement de fichiers vendorisés | Moyenne | Moyen | Audit dédié Étape 6 ; prévoir une marge de temps si remplacement nécessaire |
| Version PHP 8.x cible localement testée (8.4) différente de celle réellement fournie par l'hébergeur | Moyenne | Moyen | Clarifier la version hébergeur avant l'Étape 1 ; retester si écart |
| Déploiement multi-instances (5-6 associations) avec bascules décalées dans le temps | Faible | Moyen | Instance pilote puis déploiement progressif (Étape 11) |

---

## Points de validation finale

Avant de considérer la migration comme complète :

- [ ] Toutes les étapes 1 à 11 sont marquées comme complètes
- [ ] Tous les tests PHPUnit passent sous PHP 8, sans régression par rapport à la baseline PHP 7.4
- [ ] Tous les tests Playwright passent sous PHP 8
- [ ] Test de fumée des parcours critiques validé manuellement
- [ ] Aucune notice/warning/deprecation PHP visible en usage normal
- [ ] Documentation technique (`design_notes`) rédigée
- [ ] Toutes les instances de production basculées et stables
- [ ] Tag de rollback PHP 7.4 disponible

---

## Notes techniques

### Commandes utiles

```bash
# Basculer sur PHP 8 pour la session courante
source setenv-php8.sh

# Revenir à PHP 7.4 (référence projet)
source setenv.sh

# Lint de masse (hors third_party, audité séparément à l'Étape 6)
find system application -path application/third_party -prune -o -name "*.php" -print -exec bin/php8 -l {} \; 2>&1 | grep -v "No syntax errors"

# Suite de tests complète avec couverture
./run-all-tests.sh --coverage

# Tests Playwright
cd playwright && npx playwright test --reporter=line
```

### Baseline PHP 7.4 (Étape 2 — à compléter lors de l'exécution)

- PHPUnit : _à renseigner_ (nombre de tests / succès / couverture globale)
- Playwright : _à renseigner_ (nombre de tests / succès)

### Inventaire des incompatibilités PHP 8 (Étape 3 — à compléter lors de l'exécution)

_À renseigner : liste fichier / ligne / catégorie / correctif envisagé, produite par l'audit de l'Étape 3._

### Statut des dépendances tierces (Étape 6 — à compléter lors de l'exécution)

| Bibliothèque | Version actuelle | Statut PHP 8 | Action |
|---|---|---|---|
| tcpdf | _à renseigner_ | _à renseigner_ | _à renseigner_ |
| phpqrcode | _à renseigner_ | _à renseigner_ | _à renseigner_ |
| google-api-php-client | _à renseigner_ | _à renseigner_ | _à renseigner_ |
| pChart | _à renseigner_ | _à renseigner_ | _à renseigner_ |
| Requests | _à renseigner_ | _à renseigner_ | _à renseigner_ |
| fpdf.php | _à renseigner_ | _à renseigner_ | _à renseigner_ |
