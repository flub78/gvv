# Plan d'implémentation — Renommage de l'identifiant membre (mlogin)

**Statut global** : ✅ Implémentation complète avec i18n
**Dernière mise à jour** : 2026-05-11

---

## Architecture

### Composants principaux

1. **Contrôleur** : `application/controllers/Membres.php` (méthode `renommer()`)
2. **Modèle** : `application/models/Membre_model.php` (logique de propagation atomique)
3. **Traçabilité** : Fichiers journaux (`application/logs/`) via `log_message()`
4. **Vues** : Formulaire de sélection, rapport de prévisualisation, confirmation
5. **Tests** : PHPUnit (atomicité, exhaustivité, validation)

### Flux de traitement

```
[Formulaire sélection]
    ↓
[Validation identifiant cible]
    ↓
[Génération rapport prévisualisation]
    ↓
[Confirmation utilisateur]
    ↓
[Transaction atomique]
    ├── UPDATE membres
    ├── UPDATE toutes tables référençantes (18 tables)
    ├── UPDATE dx_auth (si existe)
    └── log_message() traçabilité
    ↓
[Affichage résultat + log]
```

### Transaction atomique

- Une seule transaction SQL englobant tous les UPDATE
- En cas d'erreur : ROLLBACK complet
- Ordre de mise à jour : d'abord les tables référençantes, puis `membres`, enfin `dx_auth`
- Traçabilité via `log_message()` avec détails de l'opération

---

## Étapes d'implémentation

### Étape 1 : Modèle — Méthodes de prévisualisation

**Objectif** : Analyser l'impact du renommage sans modifier les données.

**Actions** :
1. Ajouter dans `application/models/Membre_model.php` :
   - `preview_rename($old_mlogin, $new_mlogin)` : retourne tableau avec :
     - Fiche membre complète
     - Compte par table référençante
     - Liste des 5 vols les plus récents
     - Liste des 3 cotisations les plus récentes
     - Existence d'un compte `dx_auth`
   - `validate_new_mlogin($new_mlogin, $old_mlogin)` : validation format et unicité
   - Constante `REFERENCING_TABLES` : tableau associatif table => colonnes

**Validation** :
- [ ] `validate_new_mlogin()` rejette identifiants vides, numériques, existants, caractères invalides
- [ ] `preview_rename()` retourne les comptes corrects pour chaque table
- [ ] Test PHPUnit vérifie le comportement avec données de test
- [ ] Pas de modification en base lors de la prévisualisation

**Statut** : ✅ Terminé

---

### Étape 2 : Modèle — Transaction atomique de renommage

**Objectif** : Implémenter la propagation atomique dans toutes les tables.

**Actions** :
1. Ajouter dans `application/models/Membre_model.php` :
   - `execute_rename($old_mlogin, $new_mlogin, $performed_by)` :
     - Ouvre transaction : `$this->db->trans_start()`
     - UPDATE pour chaque table de `REFERENCING_TABLES`
     - UPDATE `membres` SET `mlogin` = $new_mlogin WHERE `mlogin` = $old_mlogin
     - UPDATE `dx_auth` SET `username` = $new_mlogin WHERE `username` = $old_mlogin (si existe)
     - Ferme transaction : `$this->db->trans_complete()`
     - Log de traçabilité : `log_message('info', 'RENAME_MEMBER: ...')` avec JSON détaillé
     - Retourne succès ou échec avec message et statistiques

**Validation** :
- [ ] Transaction réussit et propage dans toutes les tables
- [ ] En cas d'erreur simulée (constraint violation), ROLLBACK complet vérifié
- [ ] L'ancien `mlogin` n'existe plus dans aucune table après succès
- [ ] Les données métier (montants, dates, etc.) restent identiques
- [ ] Log créé avec détails corrects (utilisateur, ancien/nouveau mlogin, tables_updated)
- [ ] Test PHPUnit d'atomicité avec échec forcé

**Statut** : ✅ Terminé

---

### Étape 3 : Contrôleur — Gestion du workflow

**Objectif** : Implémenter les 3 écrans (formulaire, prévisualisation, confirmation).

**Actions** :
1. Ajouter dans `application/controllers/Membres.php` :
   - `renommer()` : méthode principale
     - Vérifier que l'utilisateur est dans `dev_users` (sinon 403)
     - Si GET : afficher formulaire de sélection
     - Si POST étape 1 (sélection) : valider et afficher prévisualisation
     - Si POST étape 2 (confirmation) : exécuter renommage et afficher résultat
   - Utiliser flashdata pour messages de succès/erreur
   - Validation des entrées utilisateur (CSRF, XSS)

**Validation** :
- [ ] Utilisateur non `dev_user` reçoit erreur 403
- [ ] Utilisateur `dev_user` accède au formulaire
- [ ] Soumission formulaire affiche rapport de prévisualisation
- [ ] Confirmation exécute le renommage et affiche succès/échec
- [ ] Messages d'erreur clairs pour validation échouée
- [ ] Test Playwright vérifie le workflow complet

**Statut** : ✅ Terminé

---

### Étape 4 : Vues — Interface utilisateur

**Objectif** : Créer les 3 vues du workflow.

**Actions** :
1. Créer `application/views/membres/renommer_form.php` :
   - Sélecteur de membre (autocomplete ou dropdown)
   - Champ texte pour nouveau `mlogin`
   - Bouton "Prévisualiser"
   - Messages d'aide sur format attendu

2. Créer `application/views/membres/renommer_preview.php` :
   - Tableau comparatif ancien/nouveau `mlogin`
   - Fiche membre avec champs impactés surlignés
   - Tableau récapitulatif par table (nom table, colonne, nb enregistrements)
   - Détails significatifs (vols, cotisations)
   - Compte `dx_auth` si existe
   - Boutons "Annuler" et "Confirmer le renommage" (gros, rouge pour confirmer)

3. Créer `application/views/membres/renommer_result.php` :
   - Message de succès ou d'erreur
   - Détails de l'opération (utilisateur, ancien/nouveau mlogin)
   - Tableau récapitulatif des enregistrements modifiés par table
   - Note : "Opération enregistrée dans les journaux système"
   - Lien pour revenir au dashboard ou à la fiche membre

**Validation** :
- [ ] Interface Bootstrap 5 cohérente avec le reste de l'application
- [ ] Accessibilité : labels, ARIA, navigation clavier
- [ ] Responsive design (mobile/desktop)
- [ ] Messages clairs et en français (avec support i18n pour anglais/néerlandais)
- [ ] Test Playwright vérifie affichage de chaque vue

**Statut** : ✅ Terminé

---

### Étape 5 : Traductions i18n

**Objectif** : Ajouter les clés de traduction dans les 3 langues.

**Actions** :
1. Ajouter dans `application/language/french/gvv_lang.php` :
   - `rename_member_title`, `rename_member_select`, `rename_member_new_login`
   - `rename_member_preview_title`, `rename_member_confirm_action`
   - Messages d'erreur de validation
   - Messages de succès/échec

2. Traduire dans `application/language/english/gvv_lang.php`
3. Traduire dans `application/language/dutch/gvv_lang.php`

**Validation** :
- [ ] Toutes les chaînes sont traduites dans les 3 langues
- [ ] Interface affiche correctement selon langue sélectionnée
- [ ] Pas de clés manquantes (vérifier logs)

**Statut** : ✅ Terminé

---

### Étape 6 : Carte d'accès au dashboard

**Objectif** : Ajouter l'accès dans la section "Développement & Tests".

**Actions** :
1. Modifier `application/views/dashboard.php` ou le contrôleur correspondant
2. Ajouter carte visible uniquement si `$this->dx_auth->is_dev_user()` :
   - Icône : 🔧 ou similaire
   - Titre : "Renommer un membre"
   - Description : "Modifier l'identifiant (mlogin) d'un membre existant"
   - Lien : `membres/renommer`

**Validation** :
- [ ] Carte visible uniquement pour `dev_users`
- [ ] Clic redirige vers formulaire de renommage
- [ ] Carte absente du dashboard pour utilisateurs normaux

**Statut** : ✅ Terminé

---

### Étape 7 : Tests unitaires et d'intégration

**Objectif** : Garantir la fiabilité de la fonctionnalité.

**Actions** :
1. Créer `application/tests/integration/RenameMembreTest.php` :
   - Test de validation d'identifiant (valide/invalide)
   - Test de prévisualisation (comptes corrects)
   - Test de renommage complet avec vérification d'exhaustivité
   - Test d'atomicité avec échec forcé (constraint violation)
   - Test de traçabilité (vérification des logs)
   - Test de mise à jour `dx_auth`

2. Créer `playwright/tests/rename_membre.spec.ts` :
   - Test du workflow complet (sélection → prévisualisation → confirmation)
   - Test d'accès refusé pour non-dev-user
   - Test d'erreur de validation (identifiant existant)

**Validation** :
- [ ] Tous les tests unitaires passent
- [ ] Couverture de code > 75% pour les nouvelles méthodes
- [ ] Tests Playwright passent
- [ ] Tests ajoutés à la suite de régression

**Statut** : ✅ Terminé

---

### Étape 8 : Documentation technique

**Objectif** : Documenter l'implémentation pour maintenance future.

**Actions** :
1. Créer `doc/design_notes/renommage_mlogin_design.md` :
   - Architecture de la solution
   - Schéma de transaction atomique
   - Liste des tables référençantes avec colonnes
   - Décisions de design (ordre UPDATE, gestion erreurs)
   - Instructions de rollback manuel si nécessaire

2. Mettre à jour `README.md` ou documentation utilisateur si pertinent

**Validation** :
- [ ] Documentation claire et complète
- [ ] Diagrammes PlantUML si nécessaire
- [ ] Revue par l'utilisateur

**Statut** : ✅ Terminé

---

### Étape 9 : Tests de fumée et déploiement

**Objectif** : Vérifier la fonctionnalité en conditions réelles sur environnement de développement.

**Actions** :
1. Activer mode développement dans `index.php`
2. Créer utilisateur de test `dev_user` avec `bin/create_test_users.sql`
3. Créer membre de test avec données référencées (vols, cotisations, compte dx_auth)
4. Exécuter renommage complet et vérifier :
   - Prévisualisation correcte
   - Propagation exhaustive
   - Log créé dans `application/logs/`
   - Ancien `mlogin` n'existe plus nulle part
5. Exécuter `./run-all-tests.sh --coverage`
6. Exécuter tests Playwright

**Validation** :
- [ ] Renommage complet réussi en environnement de développement
- [ ] Log de traçabilité présent dans `application/logs/` avec détails corrects
- [ ] Aucune erreur dans logs PHP
- [ ] Tous les tests PHPUnit passent
- [ ] Tous les tests Playwright passent
- [ ] Couverture de code globale maintenue ou améliorée
- [ ] Pas de régression sur fonctionnalités existantes

**Statut** : ✅ Terminé

---

## Risques et mitigations

| Risque | Probabilité | Impact | Mitigation |
|--------|-------------|--------|------------|
| Référence manquante (table oubliée) | Moyen | Critique | Tests exhaustifs avec requêtes de vérification post-rename |
| Erreur transaction non catchée | Faible | Critique | Gestion erreurs robuste + tests d'atomicité |
| Utilisateur annule pendant transaction | Faible | Moyen | Transaction auto-commit, pas d'interruption possible |

---

## Points de validation finale

Avant de considérer la fonctionnalité comme complète :

- [ ] Toutes les étapes 1-9 sont marquées comme complètes
- [ ] Tous les tests passent (PHPUnit + Playwright)
- [ ] Couverture de code > 75% sur code nouveau
- [ ] Renommage testé en environnement de développement avec données réelles
- [ ] Traçabilité vérifiée dans `application/logs/`
- [ ] Documentation technique rédigée
- [ ] Revue de code effectuée
- [ ] Aucune régression détectée
- [ ] Logs d'application propres (pas d'erreurs, pas de warnings)
- [ ] Validation utilisateur final (démo à l'utilisateur `dev_user`)

---

## Notes techniques

### Tables référençantes identifiées

Liste complète des tables à mettre à jour (extraite du PRD) :

```php
const REFERENCING_TABLES = [
    'events' => ['emlogin'],
    'vols_avion' => ['vapilid'],
    'vols_planeur' => ['vppilid'],
    'tickets' => ['pilote'],
    'achats' => ['pilote'],
    'pompes' => ['ppilid'],
    'calendar' => ['mlogin'],
    'reservations' => ['pilot_member_id', 'instructor_member_id'],
    'formation_seances' => ['pilote_id', 'instructeur_id'],
    'formation_autorisations_solo' => ['eleve_id', 'instructeur_id'],
    'formation_seances_theoriques' => ['membre'],
    'acceptance_records' => ['user_login', 'linked_pilot_login'],
    'acceptance_items' => ['created_by'],
    'archived_documents' => ['membre'],
    'email_list_members' => ['membre_id'],
    'paiements_en_ligne' => ['membre_login'],
    'dx_auth' => ['username']
];
```

### Commandes utiles

```bash
# Tests complets avec couverture
./run-all-tests.sh --coverage

# Tests Playwright
cd playwright && npx playwright test

# Vérifier exhaustivité post-rename (requête SQL manuelle)
SELECT table_name, column_name
FROM information_schema.columns
WHERE column_name LIKE '%mlogin%' OR column_name LIKE '%login%';

# Consulter les logs de renommage
tail -f application/logs/log-*.php | grep RENAME_MEMBER
```
