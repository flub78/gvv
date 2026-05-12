# Design — Renommage d'identifiant membre (mlogin)

**Date** : 2026-05-11
**Auteur** : Système
**Statut** : Implémenté

---

## Vue d'ensemble

Fonctionnalité permettant aux utilisateurs `dev_user` de modifier l'identifiant (clé primaire `mlogin`) d'un membre existant. Le changement est propagé atomiquement dans toutes les tables référençant ce membre.

**Cas d'usage** : Corriger des identifiants mal saisis initialement (purement numériques, fautes d'orthographe, formats non conformes).

---

## Architecture

### Composants

```
[Contrôleur: Membre::renommer()]
           ↓
[Modèle: Membres_model]
    ├── validate_new_mlogin()     → Validation format et unicité
    ├── preview_rename()          → Analyse d'impact (lecture seule)
    └── execute_rename()          → Transaction atomique SQL
           ↓
[Logs: log_message()]             → Traçabilité
```

### Workflow utilisateur

1. **Sélection** : Choix du membre + saisie nouvel identifiant
2. **Prévisualisation** : Affichage détaillé de l'impact (tables, nb enregistrements, exemples)
3. **Confirmation** : Double confirmation (formulaire + alert JS)
4. **Exécution** : Transaction atomique + affichage résultat

---

## Décisions de design

### 1. Propagation atomique

**Problème** : La clé primaire `mlogin` est référencée dans 18 tables. Une propagation partielle laisserait la base incohérente.

**Solution** : Transaction SQL unique englobant tous les UPDATE.

```sql
START TRANSACTION;

-- Mise à jour des tables référençantes AVANT la clé primaire
UPDATE volsp SET vppilid = 'new' WHERE vppilid = 'old';
UPDATE tickets SET pilote = 'new' WHERE pilote = 'old';
-- ... 16 autres tables

-- Mise à jour de la clé primaire (membres) EN DERNIER
UPDATE membres SET mlogin = 'new' WHERE mlogin = 'old';

-- Mise à jour dx_auth si compte existe
UPDATE users SET username = 'new' WHERE username = 'old';

COMMIT; -- ou ROLLBACK en cas d'erreur
```

**Ordre critique** : Tables référençantes → table primaire → dx_auth.
**Garantie** : En cas d'erreur, ROLLBACK complet, aucune donnée modifiée.

### 2. Table d'audit vs logs

**Décision** : Utiliser `log_message()` plutôt qu'une table `rename_audit`.

**Raison** :
- Opération rare (quelques fois par an)
- Cohérent avec le reste de GVV
- Pas de maintenance de table supplémentaire
- Traçabilité suffisante avec logs JSON structurés

**Format du log** :
```json
{
  "user": "dev_username",
  "old_mlogin": "oldlogin",
  "new_mlogin": "newlogin",
  "tables_updated": {"volsp": {"vppilid": 5}, "tickets": {"pilote": 3}},
  "total_records": 15,
  "timestamp": "2026-05-11 14:30:00"
}
```

### 3. Validation multi-niveaux

**Sécurité en profondeur** :
1. **Client** : JavaScript (validation format, interdiction numérique pur)
2. **Serveur** : PHP `validate_new_mlogin()` (format + unicité)
3. **Base** : Contraintes d'intégrité référentielle (dernier filet)

Chaque niveau peut bloquer indépendamment. Le client améliore l'UX, le serveur assure la sécurité.

### 4. Prévisualisation obligatoire

**Pas de "fast path"** : L'utilisateur doit TOUJOURS voir la prévisualisation avant confirmation.

**Contenu affiché** :
- Fiche membre complète (vérifier identité)
- Tableau des tables impactées + comptage
- Exemples de vols/tickets récents (vérifier cohérence)
- Compte dx_auth si existe

**But** : Éviter erreurs humaines (mauvais membre sélectionné).

### 5. Restriction d'accès

**Contrôle** : `_is_dev_user()` vérifie `config['dev_users']`.

**Niveau** : Contrôleur (pas d'autorisation framework, contrôle manuel).

**Erreur** : HTTP 403 si non autorisé.

---

## Tables référençantes

18 tables + table primaire + dx_auth :

| Table | Colonnes |
|-------|----------|
| `events` | `emlogin` |
| `volsa` | `vapilid`, `vainst` |
| `volsp` | `vppilid`, `vpinst`, `pilote_remorqueur` |
| `tickets` | `pilote` |
| `achats` | `pilote` |
| `pompes` | `ppilid` |
| `calendar` | `mlogin` |
| `reservations` | `pilot_member_id`, `instructor_member_id` |
| `formation_seances` | `pilote_id`, `instructeur_id` |
| `formation_autorisations_solo` | `eleve_id`, `instructeur_id` |
| `formation_seances_theoriques` | `membre` |
| `acceptance_records` | `user_login`, `linked_pilot_login` |
| `acceptance_items` | `created_by` |
| `archived_documents` | `membre` |
| `email_list_members` | `membre_id` |
| `paiements_en_ligne` | `membre_login` |
| `dx_auth_users` | `username` |
| **membres** | **`mlogin`** (PK) |

**Note** : Liste maintenue dans `Membres_model::REFERENCING_TABLES`.

---

## Gestion d'erreurs

### Erreurs anticipées

1. **Identifiant invalide** :
   - Vide, purement numérique, caractères spéciaux
   - → Message utilisateur clair, retour au formulaire

2. **Identifiant déjà existant** :
   - Conflit avec membre ou dx_auth existant
   - → Message utilisateur, retour au formulaire

3. **Constraint violation** (ex: FK manquante) :
   - Transaction ROLLBACK automatique
   - → Message d'erreur générique, log détaillé

### Rollback automatique

CodeIgniter gère automatiquement le rollback :
- `$this->db->trans_start()` / `trans_complete()`
- Si `trans_status() === FALSE` → rollback déjà effectué
- Base garantie cohérente

---

## Tests

### PHPUnit (`RenameMembreTest`)

- ✅ Validation identifiants (valides/invalides)
- ✅ Prévisualisation (comptages corrects)
- ✅ Renommage exhaustif (ancien login absent partout)
- ✅ Atomicité (rollback sur erreur)
- ✅ Mise à jour dx_auth
- ✅ Préservation données métier

### Playwright (`membre-renommer-smoke.spec.js`)

- ✅ Accès dev_user seulement (403 pour autres)
- ✅ Affichage formulaire
- ✅ Workflow complet sélection → prévisualisation
- ✅ Validation client-side (numérique pur bloqué)

---

## Points d'attention pour maintenance

### Ajout d'une nouvelle table référençant `mlogin`

1. Ajouter dans `Membres_model::REFERENCING_TABLES`
2. Vérifier test exhaustivité (`test_execute_rename_exhaustive`)
3. Tester manuellement un renommage

### Consultation des logs

```bash
tail -f application/logs/log-*.php | grep RENAME_MEMBER
```

Ou recherche manuelle dans les fichiers logs par date.

### Rollback manuel (si nécessaire)

**Scénario** : Bug découvert après renommage, besoin de revenir en arrière.

**Procédure** :
1. Retrouver le log de l'opération (ancien/nouveau login)
2. Exécuter manuellement la transaction inverse :
   ```sql
   START TRANSACTION;
   UPDATE volsp SET vppilid = 'OLD' WHERE vppilid = 'NEW';
   -- ... toutes les tables
   UPDATE membres SET mlogin = 'OLD' WHERE mlogin = 'NEW';
   UPDATE users SET username = 'OLD' WHERE username = 'NEW';
   COMMIT;
   ```
3. Vérifier exhaustivité avec requête de recherche

**Important** : Aucun mécanisme de rollback automatisé, opération manuelle uniquement.

---

## Évolutions possibles (hors périmètre actuel)

- Renommage en lot (plusieurs membres)
- Interface de recherche dans l'historique des renommages
- Commande CLI pour rollback automatisé
- Notification email après renommage

Ces évolutions ne sont pas prioritaires étant donné la rareté de l'opération.
