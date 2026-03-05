# Test Playwright - Utilisateurs Gaulois

## Description

Ce test Playwright vérifie que les utilisateurs gaulois créés par `bin/create_test_users.sh` peuvent :

1. ✅ Se connecter avec succès
2. ✅ Accéder à leurs comptes via `/compta/mon_compte/{section_id}`
3. ✅ Voir leurs comptes pour chaque section auxquelles ils appartiennent
4. ✅ Être redirigés lorsqu'ils tentent d'accéder à une section non autorisée

**Note importante** : Les utilisateurs ordinaires n'ont PAS accès à `/comptes/balance` (réservé aux administrateurs). Ils doivent utiliser `/compta/mon_compte/{section_id}` pour consulter leurs comptes.

**Utilisateur admin** : **panoramix** est un club-admin avec les rôles User + Club-Admin dans **toutes** les sections et a accès à `/comptes/balance`. Il n'a pas de comptes 411 créés.

## Utilisateurs Gaulois (nouveau système d'autorisation)

Ces utilisateurs sont dans `use_new_authorization` et utilisent le nouveau système de rôles.

| Utilisateur | Sections | Rôles (types_roles par section) | Bits mniveaux | Comptes 411 |
|---|---|---|---|---|
| **asterix** | Planeur, Général | User (toutes sections) | 0 | 2 |
| **obelix** | Planeur, ULM, Général | User (toutes) + Planchiste (Planeur) + Auto-planchiste (ULM) | REMORQUEUR* | 3 |
| **abraracourcix** | Planeur, Avion, ULM, Général | User (toutes) + CA (Avion seulement) + Instructeur (Avion seulement) | CA, REMORQUEUR, FI_AVION | 4 |
| **goudurix** | Avion, Général | User + Trésorier (toutes) + Auto-planchiste (Avion) | TRESORIER | 2 |
| **panoramix** | Toutes | User + Club-Admin (toutes sections) | 0 | 0 |

*Le bit REMORQUEUR d'Obelix est positionné dans `mniveaux` mais ne génère pas de `types_roles` car il n'est pas dans la section Avion (le bit REMORQUEUR n'ajoute TR_INSTRUCTEUR qu'en section Avion).

Mot de passe pour tous : `password`

### Logique d'assignation des rôles

Les bits `mniveaux` se traduisent en `types_roles` selon les règles suivantes :
- **BIT_CA (64)** → TR_CA dans **toutes** les sections
- **BIT_TRESORIER (8)** → TR_TRESORIER dans **toutes** les sections
- **BIT_FI_AVION (131072)** → TR_INSTRUCTEUR dans la section **Avion uniquement**
- **BIT_REMORQUEUR (8192)** → TR_INSTRUCTEUR dans la section **Avion uniquement**
- Les rôles spécifiques par section (Planchiste, Auto-planchiste) sont configurés dans `SECTION_ROLES_MAP`
- TR_USER (1) est toujours ajouté dans chaque section

## Utilisateurs Legacy (système DX_Auth)

Ces utilisateurs n'ont **pas** d'entrée dans `use_new_authorization`. Ils ont un `role_id` legacy et un seul rôle dans la section par défaut (section 1).

| Utilisateur | role_id | types_roles | Compte 411 |
|---|---|---|---|
| **testuser** | 1 (membre) | User | Oui (section 1) |
| **testadmin** | 2 (admin) | Club-Admin | Non |
| **testplanchiste** | 7 (planchiste) | Planchiste | Oui (section 1) |
| **testca** | 8 (ca) | CA | Oui (section 1) |
| **testbureau** | 3 (bureau) | Bureau | Oui (section 1) |
| **testtresorier** | 9 (tresorier) | Trésorier | Oui (section 1) |

Mot de passe pour tous : `password`

## Prérequis

1. Script `bin/create_test_users.sh` exécuté avec succès
2. Les 11 utilisateurs (5 gaulois + 6 legacy) doivent exister dans la base
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

- Les 4 utilisateurs gaulois non-admin existent dans la base de données
- Chaque utilisateur a ses comptes 411 créés pour ses sections
- Les rôles sont correctement assignés
- Les pages balance et journal_compte sont accessibles

## Dépannage

### Si les tests échouent

1. **Vérifier que les utilisateurs existent** :
   ```bash
   mysql -u gvv_user -p gvv2 -e "SELECT username FROM users WHERE username IN ('asterix', 'obelix', 'abraracourcix', 'goudurix', 'panoramix');"
   ```

2. **Vérifier les comptes** :
   ```bash
   mysql -u gvv_user -p gvv2 -e "SELECT pilote, COUNT(*) as compte_count FROM comptes WHERE pilote IN ('asterix', 'obelix', 'abraracourcix', 'goudurix') AND codec=411 GROUP BY pilote;"
   ```

3. **Vérifier que panoramix est club-admin dans toutes les sections** :
   ```bash
   mysql -u gvv_user -p gvv2 -e "SELECT u.username, urps.types_roles_id, urps.section_id FROM users u JOIN user_roles_per_section urps ON u.id=urps.user_id WHERE u.username='panoramix';"
   ```

4. **Vérifier les rôles d'un utilisateur gaulois** :
   ```bash
   mysql -u gvv_user -p gvv2 -e "SELECT u.username, urps.types_roles_id, urps.section_id FROM users u JOIN user_roles_per_section urps ON u.id=urps.user_id WHERE u.username='obelix';"
   ```

5. **Recréer les utilisateurs de test** :
   ```bash
   bash bin/create_test_users.sh
   ```

6. **Vérifier les logs Playwright** :
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
- Script de création : `bin/create_test_users.sh`
- Helper : `playwright/tests/helpers/LoginPage.js`
- Contrôleur : `application/controllers/admin.php` (méthode `_create_test_gaulois_users()`)
- Documentation : Ce fichier
