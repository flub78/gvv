# PRD : Refonte du Système d'Autorisation

**Version du Document :** 2.0
**Date :** 2025-01-24
**Statut :** En révision

---

## 1. Résumé Exécutif

Ce document décrit les exigences pour refondre le système d'autorisation de GVV. Le modèle actuel de contrôle d'accès basé sur les rôles hiérarchique (RBAC) sera remplacé par un système d'autorisation plat, basé sur les domaines et conscient des sections.

Le nouveau système supportera à la fois des rôles globaux et spécifiques aux sections, fournira une interface utilisateur améliorée pour la gestion des rôles utilisateur, et implémentera des contrôles d'accès aux données au niveau des lignes.

**Changement majeur (v2.0)** : Les permissions d'accès aux contrôleurs et méthodes seront gérées **directement dans le code** plutôt que configurées en base de données, afin de simplifier la maintenance et d'améliorer la cohérence entre le code et les autorisations.

---

## 2. Analyse de l'État Actuel

### 2.1. Système Existant (Architecture 2011)

- **Schéma :** Le système utilise un ensemble de rôles hiérarchiques (`membre` → `planchiste` → `ca` → `bureau` → `tresorier` → `admin`). Les permissions sont stockées sous forme d'URIs sérialisés assignés à chaque rôle.
- **Hiérarchie :** `admin` (2) → `tresorier` (9) → `bureau` (3) → `ca` (8) → `planchiste` (7) → `membre` (1).
- **Contrôleurs Affectés :** `backend.php`, `migration.php`, `presences.php`, `rapports.php`, `config.php`.

### 2.2. Problèmes Principaux du Système Actuel

1.  **Problème d'Héritage Hiérarchique :** Les rôles de niveau supérieur (ex. Trésoriers) héritent incorrectement des permissions des rôles de niveau inférieur (ex. accès aux données de vol du Planchiste).

2.  **Pas de Granularité par Section :** Les permissions sont globales. Un utilisateur ne peut pas avoir différents rôles dans différentes sections (ex. Planchiste pour 'Planeur' et simple 'Utilisateur' pour 'ULM').

3.  **Gestion Complexe des URI :** L'édition manuelle des chaînes URI sérialisées dans une zone de texte est difficile et source d'erreurs.

4.  **Pas de Sécurité au Niveau des Lignes :** Le système ne peut pas distinguer entre voir ses "propres données" versus "toutes les données" dans une table (ex. un utilisateur voyant seulement ses propres vols vs. un gestionnaire voyant tous les vols).

5.  **UX Médiocre :** Il n'y a pas d'interface intuitive pour que les administrateurs puissent voir ou gérer les permissions utilisateur d'un coup d'œil.

6.  **Maintenance Fastidieuse des Permissions (Nouveau problème identifié v2.0)** :
    -   L'implémentation actuelle (v1.0) utilise ~300 entrées de permissions en base de données (29 contrôleurs × multiples actions).
    -   Chaque permission nécessite une entrée manuelle : (rôle, contrôleur, action, section, type).
    -   Maintenance complexe lors de l'ajout de nouvelles fonctionnalités ou contrôleurs.
    -   Risque d'incohérences entre les permissions en base et le code réel.
    -   Difficulté à avoir une vue d'ensemble des autorisations d'un contrôleur.
    -   Performance : nécessite des requêtes SQL pour chaque vérification de permission.

---

## 3. Objectifs et Exigences

### 3.1. Exigences Fonctionnelles

1.  **EF1 : Modèle de Rôle Plat**
    -   Éliminer la hiérarchie rigide parent-enfant des rôles.
    -   Les rôles doivent être des domaines d'autorité indépendants (ex. "Gestionnaire de Vol", "Gestionnaire Financier").
    -   Les permissions ne doivent pas être héritées automatiquement.

2.  **EF2 : Rôles Conscients des Sections**
    -   Support des **Rôles Globaux** qui s'appliquent à l'ensemble de l'application (ex. `admin`, `bureau`).
    -   Support des **Rôles de Section** qui sont spécifiques à une seule section (ex. `tresorier`, `planchiste`).
    -   Un utilisateur doit pouvoir détenir différents rôles dans différentes sections.

3.  **EF3 : Interface de Gestion des Rôles Utilisateur Améliorée**
    -   Fournir une interface mono-page pour gérer les rôles utilisateur.
    -   L'interface doit présenter une liste d'utilisateurs avec des cases à cocher pour les attributions de rôles.
    -   Les administrateurs doivent pouvoir filtrer la liste d'utilisateurs par section et par statut actif/inactif.
    -   L'interface doit utiliser DataTables pour la recherche et le tri.
    -   Il doit être visuellement clair quels rôles sont globaux versus spécifiques à une section.

4.  **EF4 : Gestion des Permissions Basée sur le Code (Modifié v2.0)**
    -   **Approche :** Les permissions d'accès aux contrôleurs et méthodes seront définies **directement dans le code** des contrôleurs via des appels déclaratifs.
    -   **Niveau Contrôleur :** Chaque contrôleur définit dans son constructeur les rôles autorisés par défaut pour toutes ses méthodes.
    -   **Niveau Méthode :** Les méthodes individuelles peuvent ajuster (restreindre ou assouplir) les permissions définies au niveau du contrôleur.
    -   **Suppression de la table `role_permissions` :** La table de configuration des permissions (contrôleur, action, type) ne sera plus utilisée.
    -   **Conservation des rôles :** Les tables `types_roles`, `user_roles_per_section` et `data_access_rules` sont conservées pour gérer :
        -   Les rôles existants et leurs métadonnées
        -   L'affectation des rôles aux utilisateurs par section
        -   Les règles d'accès au niveau des lignes (own/section/all)
    -   **Avantages :**
        -   Réduction de ~300 permissions configurées → ~50 appels déclaratifs dans les constructeurs
        -   Permissions toujours cohérentes avec le code
        -   Meilleure lisibilité : les permissions sont visibles là où elles s'appliquent
        -   Facilite la maintenance et l'ajout de nouvelles fonctionnalités
        -   Versionning naturel via Git
        -   Meilleures performances (pas de requête SQL pour vérifier les permissions)

5.  **EF5 : Accès aux Données au Niveau des Lignes**
    -   Implémenter un mécanisme pour contrôler l'accès aux données au niveau des lignes, distinguant entre données "propres" et "toutes" les données.
    -   **Exemples :**
        -   Un `utilisateur` ne doit voir que ses propres factures.
        -   Un `tresorier` doit voir toutes les factures de sa section assignée.
        -   Un `auto_planchiste` peut éditer ses propres vols.
        -   Un `planchiste` peut éditer tous les vols de sa section.

6.  **EF6 : Dérogation Admin**
    -   Le rôle `admin` doit être maintenu comme super-utilisateur.
    -   Les utilisateurs admin doivent contourner toutes les vérifications de permissions et avoir un accès illimité.

7.  **EF7 : Framework de Tests**
    -   Des tests unitaires complets doivent être développés pour la nouvelle logique d'autorisation.
    -   Les tests doivent couvrir l'accès autorisé, le refus d'accès non autorisé, et les règles de données au niveau des lignes pour chaque rôle.

### 3.2. Exigences Non Fonctionnelles

1.  **ENF1 : Compatibilité Ascendante & Migration**
    -   Le système doit supporter une migration graduelle.
    -   Un flag de fonctionnalité doit être implémenté pour basculer entre l'ancien et le nouveau système d'autorisation.
    -   Un plan de retour en arrière clair doit être en place pour chaque phase de la migration.

2.  **ENF2 : Performance**
    -   Les vérifications individuelles de permissions doivent s'exécuter en moins de 10ms.
    -   Le système doit utiliser la mise en cache basée sur les sessions pour les permissions utilisateur afin de minimiser les requêtes de base de données.

3.  **ENF3 : Sécurité**
    -   Une piste d'audit doit être créée pour enregistrer tous les changements de rôles et permissions.
    -   Le système doit être sécurisé contre l'escalade de privilèges.
    -   La posture de sécurité par défaut doit être "refuser par défaut".

4.  **ENF4 : Maintenabilité**
    -   Le nouveau code doit être bien documenté et auto-explicatif.
    -   L'architecture doit faciliter l'ajout de nouveaux rôles et permissions à l'avenir.

5. **ENF5 : Documentation**
    -   Fournir un document pour les développeurs pour expliquer comment appliquer les contrôles de droit dans le code

---

## 4. Mapping des Rôles par Contrôleur (Nouveau v2.0)

Cette section définit les règles d'autorisation fonctionnelles pour chaque catégorie de contrôleurs. L'implémentation technique sera détaillée dans le plan d'implémentation.

### 4.1. Rôles Définis

Le système utilise 8 rôles organisés en 2 catégories :

**Rôles Globaux** (s'appliquent à toute l'application) :
- `club-admin` : Accès total à toutes les données et fonctions d'administration
- `super-tresorier` : Accès aux données financières de toutes les sections

**Rôles par Section** (s'appliquent à une section spécifique) :
- `bureau` : Accès à toutes les données d'une section, y compris les données financières personnelles
- `tresorier` : Modification des données financières d'une section
- `ca` : Accès à toutes les données d'une section, y compris les données financières globales
- `planchiste` : Création, modification et suppression des données de vols
- `auto_planchiste` : Création, modification et suppression de ses propres vols uniquement
- `user` : Accès de base pour consulter ses propres données

### 4.2. Règles d'Autorisation par Catégorie de Contrôleurs

#### 4.2.1. Contrôleurs Publics
**Contrôleurs :** `welcome`, `auth`
**Règle :** Login requis uniquement, pas de rôle spécifique

#### 4.2.2. Contrôleurs de Consultation Utilisateur
**Contrôleurs :** `membre`, `planeur`, `avion`, `event`, `factures`, `calendar`

**Règles par défaut :**
- Consultation : Rôle `user`
- Création/Modification/Suppression : Rôles selon le type de données

**Exceptions spécifiques :**
- `membre` :
  - Édition de ses propres données : `user`
  - Édition des données d'autres membres : `ca`
  - Création de nouveaux membres : `ca`
- `planeur`, `avion` :
  - Consultation : `user`
  - Création/modification/suppression : `planchiste`

#### 4.2.3. Contrôleurs de Gestion des Vols
**Contrôleurs :** `vols_planeur`, `vols_avion`, `vols_decouverte`

**Règles :**
- Création/modification de ses propres vols : `auto_planchiste`
- Consultation/création/modification de tous les vols : `planchiste`
- Suppression de vols : `planchiste`  (`auto_planchiste`, uniquement pour ses propres vols mais pendant une durée limitée)

**Sécurité niveau ligne :** Les règles `data_access_rules` déterminent si un `auto_planchiste` voit uniquement ses propres vols.

#### 4.2.4. Contrôleurs Comptabilité
**Contrôleurs :** `compta`, `comptes`, `achats`, `rapprochements`, `plan_comptable`

**Règles :**
- Consultation de son propre compte : `user`
- Consultation de tous les comptes d'une section : `bureau`
- Modification des données financières : `tresorier`
- Accès à toutes les sections : `super-tresorier`

**Exceptions spécifiques :**
- `compta/mon_compte` : `user`
- `compta/journal_compte` (son compte) : `user`
- `compta/journal_compte` (autre compte) : `bureau`

#### 4.2.5. Contrôleurs Administration Section
**Contrôleurs :** `sections`, `terrains`, `alarmes`, `presences`, `licences`, `tarifs`, `tickets` (gestion)

**Règle :** Rôle `ca` requis

**Exceptions :**
- `tickets` (consultation de ses propres tickets) : `user`

#### 4.2.6. Contrôleurs Administration Globale
**Contrôleurs :** `admin`, `backend`, `authorization`, `config`, `configuration`, `migration`, `dbchecks`

**Règle :** Rôle `club-admin` requis (super-utilisateur)

#### 4.2.7. Contrôleurs Rapports et Exports
**Contrôleurs :** `rapports`, `reports`, `historique`

**Règles :**
- Rapports de section : `ca`, `bureau`
- Rapports globaux : `club-admin`

#### 4.2.8. Contrôleurs Techniques
**Contrôleurs :** `attachments`, `mails`, `openflyers`, `FFVV`

**Règles :**
- `attachments` : `user` (propres fichiers), `ca` (tous)
- `mails` : `ca`
- `openflyers`, `FFVV` : `planchiste`

### 4.3. Patterns d'Exceptions Communs

Plusieurs patterns d'exceptions sont identifiés et doivent être supportés :

1. **Accès conditionnel "own vs all"** :
   - Exemple : `user` peut voir ses propres factures, `tresorier` voit toutes les factures
   - Implémentation : Vérification au niveau méthode avec contrôle d'ID

2. **Restriction d'actions spécifiques** :
   - Exemple : `auto_planchiste` peut créer/éditer mais pas supprimer
   - Implémentation : Vérification de rôle dans les méthodes `delete()`

3. **Consultation publique, modification restreinte** :
   - Exemple : `user` consulte, `planchiste` modifie
   - Implémentation : Rôle par défaut pour lecture, vérification renforcée pour écriture

---

## 5. Interface Utilisateur (Maquettes)

### 5.1. Page de Gestion des Rôles Utilisateur

**Concept :** Une grille basée sur DataTables où les lignes sont des utilisateurs et les colonnes sont des rôles, groupés par portée globale et spécifique à la section.

**Fonctionnalités :**
- Filtres pour Section et utilisateurs Actifs/Inactifs.
- Cases à cocher pour accorder/révoquer des rôles.
- Sauvegarde basée sur AJAX.

**Structure de la Maquette :**
```
[Filtre Section: Tous / Planeur / ULM / Avion] [Utilisateurs Actifs Seulement: ☑] [Recherche: ______]

+----------+---------+------------+------------------+------------------+
| Username | Email   | Rôles      | Section: Planeur | Section: ULM     |
|          |         | Globaux    | Rôles            | Rôles            |
+----------+---------+------------+------------------+------------------+
| fpeignot | f@...   | ☑ Admin    | ☑ CA             | ☐ Tresorier      |
|          |         | ☐ Bureau   | ☑ Planchiste     | ☐ Planchiste     |
+----------+---------+------------+------------------+------------------+
| agnes    | a@...   | ☐ Admin    | ☑ Tresorier      | ☐ Tresorier      |
|          |         | ☐ Bureau   | ☑ Utilisateur    | ☐ Utilisateur    |
+----------+---------+------------+------------------+------------------+
```

**Note v2.0 :** L'interface de gestion des permissions par rôle (contrôleur/action) est supprimée car les permissions sont maintenant gérées dans le code.

---

## 6. Stratégie de Migration (v2.0)

### 6.1. Principe de Migration Progressive

La migration vers le système d'autorisation basé sur le code doit être **progressive et réversible** avec une granularité par utilisateur :

#### 6.1.1. Mécanisme de Migration par Utilisateur

**Table `use_new_authorization`** :
- **Structure** :
  - `id` : INT AUTO_INCREMENT PRIMARY KEY
  - `username` : VARCHAR(255) NOT NULL UNIQUE
- **Objectif** : Permettre de tester le nouveau système avec des utilisateurs spécifiques
- **Fonctionnement** :
  - Si un utilisateur existe dans cette table → il utilise le **nouveau système**
  - Si un utilisateur n'existe pas dans cette table → comportement selon le flag global `use_new_authorization`
- **Gestion** : Ajout/suppression manuel via SQL (pas d'interface GUI nécessaire)

#### 6.1.2. Logique de Décision

L'ordre de priorité pour déterminer quel système d'autorisation utiliser :

1. **Vérification utilisateur spécifique** :
   - Si `username` existe dans la table `use_new_authorization` → **Nouveau système**
2. **Vérification flag global** :
   - Si `$config['use_new_authorization'] = TRUE` → **Nouveau système pour tous**
   - Si `$config['use_new_authorization'] = FALSE` → **Ancien système pour tous**

#### 6.1.3. Phases de Migration

1. **Phase 1 - Préparation** :
   - Implémentation de l'API d'autorisation dans le code (nouvelles méthodes dans `Gvv_Authorization`)
   - Création de la table `use_new_authorization`
   - Maintien du système actuel en parallèle via feature flag
   - Tests unitaires de l'API
   - Flag global = FALSE (tous utilisent l'ancien système par défaut)

2. **Phase 2 - Tests Développement (Utilisateurs Sélectionnés)** :
   - Ajout de 2-3 utilisateurs de test dans `use_new_authorization` (environnement dev)
   - Migration de 5-10 contrôleurs simples (ex: `sections`, `terrains`, `alarmes`)
   - Validation intensive avec utilisateurs de test uniquement
   - Documentation des patterns d'implémentation
   - Flag global = FALSE (autres utilisateurs non affectés)

3. **Phase 3 - Tests Production (Utilisateurs Pilotes)** :
   - Ajout de 5-10 utilisateurs pilotes dans `use_new_authorization` (environnement prod)
   - Migration des contrôleurs avec exceptions (ex: `membre`, `compta`, `vols_planeur`)
   - Vérification de la sécurité au niveau des lignes
   - Tests d'intégration en production réelle
   - Flag global = FALSE (majorité des utilisateurs non affectés)
   - Monitoring intensif des logs d'audit pour détecter les problèmes

4. **Phase 4 - Migration Globale** :
   - Activation du flag global : `$config['use_new_authorization'] = TRUE`
   - **Tous** les utilisateurs utilisent maintenant le nouveau système
   - La table `use_new_authorization` est désormais ignorée (le flag global prend le dessus)
   - Migration des contrôleurs restants
   - Monitoring continu

5. **Phase 5 - Finalisation** :
   - Suppression de la table `use_new_authorization` (devenue inutile)
   - Suppression de la table `role_permissions` (optionnel, peut être conservée pour audit)
   - Nettoyage du code legacy (après validation complète)

### 6.2. Coexistence des Deux Systèmes

Pendant la migration, les deux systèmes doivent coexister :

- **Tables conservées** : `types_roles`, `user_roles_per_section`, `data_access_rules`, `authorization_audit_log`
- **Table en transition** : `role_permissions` (utilisée uniquement par les contrôleurs non migrés)
- **Table de migration progressive** : `use_new_authorization` (liste des utilisateurs testant le nouveau système)
- **Feature flag global** : `$config['use_new_authorization']` contrôle quel système est actif par défaut
- **Priorité de décision** : Table utilisateur > Flag global
- **Marqueur par contrôleur** : Chaque contrôleur déclare explicitement s'il utilise le nouveau système

### 6.3. Critères de Succès

La migration d'un contrôleur est considérée réussie si :

1. Tous les scénarios d'autorisation existants fonctionnent correctement
2. Les tests unitaires et d'intégration passent
3. L'audit log continue de fonctionner
4. La performance est égale ou meilleure
5. Le code est plus lisible et maintenable

### 6.4. Plan de Retour Arrière

En cas de problème, le retour arrière doit être simple et granulaire :

#### 6.4.1. Rollback par Utilisateur (Phases 2-3)
Si un utilisateur pilote rencontre des problèmes :
```sql
-- Retirer l'utilisateur du nouveau système
DELETE FROM use_new_authorization WHERE username = 'username_problematique';
```
**Effet** : L'utilisateur retourne immédiatement à l'ancien système, les autres pilotes continuent avec le nouveau

#### 6.4.2. Rollback Global (Phase 4+)
En cas de problème généralisé :
```php
// Dans application/config/gvv_config.php
$config['use_new_authorization'] = FALSE;  // Retour à l'ancien système pour tous
```
**Effet** : Tous les utilisateurs retournent immédiatement à l'ancien système

---

## 7. Livrables

### 7.1. Livrables Techniques

1. **Code** :
   - API d'autorisation étendue dans `Gvv_Authorization.php`
   - Helpers dans `Gvv_Controller.php` avec logique de migration progressive
   - Migrations de contrôleurs avec annotations des rôles requis
   - Tests unitaires et d'intégration

2. **Base de Données** :
   - **Création** : Table `use_new_authorization` (id, username)
   - **Conservation** : Tables `types_roles`, `user_roles_per_section`, `data_access_rules`, `authorization_audit_log`
   - **Suppression future** : `role_permissions` (après migration complète), `use_new_authorization` (après Phase 4)

3. **Documentation** :
   - Guide développeur pour l'implémentation des autorisations dans le code
   - Documentation de la migration progressive par utilisateur
   - Documentation des patterns d'exceptions
   - Guide de gestion de la table `use_new_authorization` (SQL manuel)
   - Changelog détaillé

### 7.2. Livrables Fonctionnels

1. **Interface de Gestion des Rôles** :
   - Page de gestion des rôles utilisateur (comme décrit en section 5.1)
   - Filtrage et recherche
   - Affectation/révocation de rôles avec AJAX

2. **Sécurité et Audit** :
   - Log d'audit complet des changements de rôles
   - Traçabilité des accès refusés
   - Alertes sur les tentatives d'escalade de privilèges

3. **Tests** :
   - Suite de tests unitaires pour chaque rôle
   - Tests d'intégration pour les patterns d'exceptions
   - Tests de sécurité (tentatives d'accès non autorisé)

---

## 8. Risques et Mitigations

### 8.1. Risques Identifiés

1. **Risque : Incohérences pendant la migration**
   - **Mitigation** : Migration progressive contrôleur par contrôleur, tests intensifs

2. **Risque : Perte de permissions existantes**
   - **Mitigation** : Sauvegarde complète de la base, documentation du mapping ancien→nouveau

3. **Risque : Complexité des exceptions (own vs all)**
   - **Mitigation** : Patterns documentés, helpers réutilisables, tests unitaires

4. **Risque : Performance dégradée**
   - **Mitigation** : Benchmarks avant/après, cache des rôles utilisateur en session

### 8.2. Dépendances

- Complétion de la migration EF1-EF3 (rôles plats, sections, interface utilisateur)
- Disponibilité d'un environnement de test complet
- Formation des administrateurs à la nouvelle interface

---

## 9. Conclusion

La version 2.0 de ce PRD introduit deux **changements majeurs** :

### 9.1. Gestion des Permissions dans le Code

Le passage d'une configuration en base de données à une **déclaration dans le code** répond au problème critique de maintenance : la gestion de ~300 permissions en base de données est trop fastidieuse et source d'erreurs.

**Bénéfices attendus** :
- Réduction de ~300 permissions configurées → ~50 déclarations dans les constructeurs
- Cohérence garantie entre code et autorisations
- Maintenance simplifiée lors de l'ajout de fonctionnalités
- Meilleure lisibilité et maintenabilité du code
- Performance améliorée (moins de requêtes SQL)
- Versionning naturel via Git

### 9.2. Migration Progressive par Utilisateur

L'introduction de la table `use_new_authorization` permet une **granularité fine** dans la migration :

**Avantages** :
- Tests réels avec utilisateurs sélectionnés (dev et production)
- Réduction drastique du risque (impact limité en cas de problème)
- Rollback instantané par utilisateur ou global
- Validation progressive en conditions réelles
- Pas d'impact sur les utilisateurs non-pilotes

**Parcours de migration sécurisé** :
1. Tests développement (2-3 utilisateurs)
2. Tests production pilote (5-10 utilisateurs)
3. Activation globale (tous les utilisateurs)
4. Nettoyage (suppression du code legacy)

### 9.3. Conservation des Acquis

- Rôles plats et conscients des sections (EF1, EF2)s
- Interface de gestion des rôles utilisateur (EF3)
- Sécurité au niveau des lignes (EF5)
- Audit et traçabilité (ENF3)

L'implémentation technique détaillée sera décrite dans le plan d'implémentation associé.
