# Test Playwright - Utilisateurs Gaulois

## Description

Ce test Playwright vérifie que les 4 utilisateurs gaulois créés par la procédure `/admin/generate_test_database` peuvent :

1. ✅ Se connecter avec succès
2. ✅ Accéder à leurs comptes via `/compta/mon_compte/{section_id}`
3. ✅ Voir leurs comptes pour chaque section auxquelles ils appartiennent
4. ✅ Être redirigés lorsqu'ils tentent d'accéder à une section non autorisée

**Note importante** : Les utilisateurs ordinaires n'ont PAS accès à `/comptes/balance` (réservé aux administrateurs). Ils doivent utiliser `/compta/mon_compte/{section_id}` pour consulter leurs comptes.

## Utilisateurs testés

| Utilisateur | Sections | Rôles | Comptes attendus |
|---|---|---|---|
| **asterix** | Planeur, Général | Utilisateur | 2 |
| **obelix** | Planeur, ULM, Général | Remorqueur | 3 |
| **abraracourcix** | Planeur, Avion, ULM, Général | CA + Instructeur Avion | 4 |
| **goudurix** | Avion, Général | Trésorier | 2 |

Mot de passe pour tous : `password`

## Prérequis

1. Base de données de test générée via `/admin/generate_test_database`
2. Les 4 utilisateurs gaulois doivent exister dans la base
3. Playwright configuré et opérationnel

## Exécution

### Exécuter tous les tests pour les utilisateurs gaulois

```bash
cd playwright
npx playwright test tests/gaulois-users-accounts.spec.js
```

### Exécuter les tests pour un utilisateur spécifique

```bash
# Pour asterix uniquement
npx playwright test tests/gaulois-users-accounts.spec.js -g "asterix"

# Pour obelix uniquement
npx playwright test tests/gaulois-users-accounts.spec.js -g "obelix"
```

### Mode debug avec UI

```bash
npx playwright test tests/gaulois-users-accounts.spec.js --ui
```

### Avec rapport HTML

```bash
npx playwright test tests/gaulois-users-accounts.spec.js --reporter=html
npx playwright show-report
```

## Tests inclus

### Par utilisateur (4 utilisateurs × 4 tests)

1. **Login test** : Vérifie que l'utilisateur peut se connecter
2. **Account cards test** : Vérifie que les comptes sont visibles sur la page balance
3. **Account detail test** : Vérifie l'accès à la page journal_compte
4. **Sections verification** : Vérifie l'accès aux sections

### Tests globaux

5. **Sequential login test** : Teste les 4 utilisateurs en séquence (login/logout)
6. **Account names test** : Vérifie que les noms d'utilisateurs apparaissent dans leurs comptes

**Total : 18 tests**

## Caractéristiques

- ✅ **Indépendant du domaine** : utilise des chemins relatifs
- ✅ **Indépendant du déploiement** : pas de hardcoded URLs/IDs
- ✅ **Flexible** : s'adapte aux différentes structures UI (accordions, cards, tables)
- ✅ **Logging détaillé** : messages console pour debug
- ✅ **Gestion des erreurs** : alternatives si structure UI change

## Résultats attendus

Tous les tests doivent passer (✓ en vert) si :

- Les 4 utilisateurs existent dans la base de données
- Chaque utilisateur a ses comptes 411 créés pour ses sections
- Les rôles sont correctement assignés
- Les pages balance et journal_compte sont accessibles

## Dépannage

### Si les tests échouent

1. **Vérifier que les utilisateurs existent** :
   ```bash
   mysql -u gvv_user -p gvv2 -e "SELECT username FROM users WHERE username IN ('asterix', 'obelix', 'abraracourcix', 'goudurix');"
   ```

2. **Vérifier les comptes** :
   ```bash
   mysql -u gvv_user -p gvv2 -e "SELECT pilote, COUNT(*) as compte_count FROM comptes WHERE pilote IN ('asterix', 'obelix', 'abraracourcix', 'goudurix') AND codec=411 GROUP BY pilote;"
   ```

3. **Regénérer la base de test** :
   - Accéder à `http://gvv.net/admin/generate_test_database` (en tant que fpeignot)
   - Cocher "Keep anonymized" pour tester ensuite
   - Lancer la génération

4. **Vérifier les logs Playwright** :
   ```bash
   npx playwright test tests/gaulois-users-accounts.spec.js --debug
   ```

## Intégration CI/CD

Pour intégrer dans Jenkins ou autre CI :

```bash
#!/bin/bash
cd playwright
npx playwright test tests/gaulois-users-accounts.spec.js --reporter=junit --reporter-options="outputFile=gaulois-test-results.xml"
```

## Fichiers associés

- Test : `playwright/tests/gaulois-users-accounts.spec.js`
- Helper : `playwright/tests/helpers/LoginPage.js`
- Contrôleur : `application/controllers/admin.php` (méthode `_create_test_gaulois_users()`)
- Documentation : Ce fichier
