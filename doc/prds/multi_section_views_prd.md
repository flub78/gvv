# PRD: Vues et Menus Multi-Sections pour les Utilisateurs

**Date:** 2025-11-08
**Version:** 1.0
**Statut:** Draft

---

## 1. Contexte et Problématique

### 1.1 Situation Actuelle

Dans le système GVV, les utilisateurs peuvent être membres de plusieurs sections (Planeur, Avion, ULM) et exercer des rôles différents dans chacune d'elles. Actuellement:

- Les utilisateurs sont authentifiés au niveau global
- Ils doivent sélectionner une section active lors de la connexion
- **Pour consulter leurs données dans une autre section, ils doivent se déconnecter et se reconnecter** en choisissant une section différente
- Cette contrainte limite fortement l'utilisabilité pour les membres multi-sections

### 1.2 Exemples d'Utilisation

**Cas 1: Pilote planeur et ULM**
- Jean est pilote dans la section Planeur et dans la section ULM
- Il voudrait consulter ses vols de planeur, puis ses vols ULM
- Actuellement, il doit se déconnecter/reconnecter pour changer de section

**Cas 2: Membre du CA multi-sections**
- Marie est membre du CA pour les sections Planeur et Avion
- Elle doit vérifier les comptes des deux sections
- Elle doit actuellement basculer plusieurs fois entre les sections

**Cas 3: Instructeur multi-activités**
- Pierre est instructeur planeur et instructeur avion
- Il doit gérer les carnets de progression dans les deux sections
- Les allers-retours nécessitent des déconnexions/reconnexions répétées

### 1.3 Architecture Technique Existante

**Base de données:**
- Table `sections`: (id, nom, description, acronyme, couleur)
  - 1 = Planeur
  - 2 = ULM
  - 3 = Avion
  - 4 = Général
- Table `user_roles_per_section`: Associe users × roles × sections
  - Champs: user_id, types_roles_id, section_id, granted_at, revoked_at

**Modèle Common_Model:**
- `$this->section`: Objet section active courante
- `$this->section_id`: ID de la section active
- La section est initialisée au niveau du modèle et persiste en session

**Contrôleurs:**
- Les contrôleurs étendent `Gvv_Controller`
- Filtrage des données selon `section_id` dans les requêtes SQL

---

## 2. Objectifs

### 2.1 Objectif Principal

Permettre aux utilisateurs d'accéder facilement à leurs informations dans toutes les sections auxquelles ils appartiennent, **sans se déconnecter/reconnecter**.

### 2.2 Objectifs Secondaires

1. **Navigation fluide:** Le simple choix d'une page fait basculer de section
2. **Dashboard contextuel:** Afficher des cartes adaptées aux sections de l'utilisateur
3. **Sécurité:** Respecter les autorisations par section (nouveau système v2.0)
4. **Expérience utilisateur:** Interface claire montrant la section active
5. **Performance:** Éviter les rechargements complets de page si possible

---

## 3. Exigences Fonctionnelles

### 3.1 Dashboard Multi-Sections (Page d'Accueil)

**FR-01: Cartes d'Activité par Section**

Le dashboard (`welcome/index`) doit afficher des cartes regroupées par fonction, ces cartes peuvent le faire basculer dans différentes sections:

**Exemple pour un utilisateur membre Planeur + ULM:**

```
┌─────────────────────────────────────────────────────┐
│  Dashboard - Bienvenue Jean Dupont                  │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│  📊 Mes vols                                        │
├─────────────────────────────────────────────────────┤
│  [Mes vols avion]      [Mes vols ULM]               │
│  [Réserver un avion]       [Réserver un ULM]        │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│  🪂 Mes comptes                                     │
├─────────────────────────────────────────────────────┤
│  [Mon compte avion]          [Mon compte ULM]       │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│  ⚙️ Administration (si CA/Bureau)                   │
├─────────────────────────────────────────────────────┤
│  [Gestion membres]      [Comptabilité]              │
└─────────────────────────────────────────────────────┘
```

**Critères d'acceptation FR-01:**
- Les cartes sont affichées uniquement pour les sections où l'utilisateur a des rôles actifs
- Les liens dans les cartes mènent vers les contrôleurs appropriés, ces liens spéciaux entraînent le basculement de section
- L'ordre d'affichage: Mon espace personnel, Gestion des vols, Trésorerie, puis Général/Administration

---

**FR-02: Indicateur de Section Active**

Le menu principal doit indiquer clairement quelle section est actuellement active:

```
┌─────────────────────────────────────────────────────┐
│  GVV  |  Section: Planeur ▼  |  Jean Dupont  [⚙️]   │
└─────────────────────────────────────────────────────┘
```

**Critères d'acceptation FR-02:**
- Badge ou dropdown indiquant la section active
- Couleur de fond correspondant à `sections.couleur`
- Accessible sur toutes les pages (menu principal)

---

### 3.2 Basculement de Section

**FR-03: Routes de Basculement**

Créer des routes permettant de changer la section active:

```
/sections/switch/{section_id}
/sections/switch/{section_id}/redirect/{controller}/{action}
```

**Comportements:**

1. **Sans redirect:** Retour au dashboard avec la nouvelle section active
2. **Avec redirect:** Charge le contrôleur/action spécifié dans le contexte de la nouvelle section

**Exemples:**
```
/sections/switch/1                    → Dashboard section Planeur
/sections/switch/2/redirect/vols/page → Liste des vols en section ULM
```

**Critères d'acceptation FR-03:**
- Validation: Vérifier que l'utilisateur a des rôles actifs dans la section cible
- Erreur 403 si tentative d'accès à une section non autorisée
- Mise à jour de la session: `$this->session->set_userdata('active_section_id', $section_id)`
- Message flash de confirmation: "Section active: [Nom Section]"

---

**FR-04: Menu Déroulant de Sélection de Section**

Ajouter un dropdown dans le menu principal:

```html
<div class="dropdown">
  <button class="btn dropdown-toggle">Section: Planeur</button>
  <ul class="dropdown-menu">
    <li><a href="/sections/switch/1">🪂 Planeur</a></li>
    <li><a href="/sections/switch/2">🛩️ ULM</a></li>
    <li class="disabled"><a>✈️ Avion</a></li>
  </ul>
</div>
```

**Critères d'acceptation FR-04:**
- Liste uniquement les sections où l'utilisateur a des rôles actifs
- Section active indiquée visuellement (badge, coche)
- Options désactivées stylisées en gris
- Responsive (mobile-friendly)

---

### 3.3 Liens Contextualisés dans le Dashboard

**FR-05: Cartes avec Liens Directs par Section**

Chaque carte du dashboard doit contenir des liens intelligents vers les ressources de la section concernée.

**Exemples de cartes:**

**Carte "Mes Vols" (Section Planeur):**
- Titre: "Mes vols planeur"
- Lien: `/vols_planeur/page?pilote=[user_id]&section=1`
- Comportement: Liste filtrée des vols du pilote en section planeur

**Carte "Mon Compte" (Section ULM):**
- Titre: "Mon compte ULM"
- Lien: `/comptes/view/[compte_id]?section=2`
- Comportement: Affiche le solde du compte dans la section ULM

**Carte "Réserver un Appareil":**
- Titre: "Réserver un planeur"
- Lien: `/calendar?section=1`
- Comportement: Calendrier de réservation filtré sur les planeurs

**Critères d'acceptation FR-05:**
- Chaque lien passe le `section_id` approprié
- Les contrôleurs cibles respectent le filtrage par section
- Liens désactivés (grisés) si l'utilisateur n'a pas les droits requis dans la section

---

### 3.4 Permissions et Autorisations

**FR-06: Intégration avec le Nouveau Système d'Autorisations (v2.0)**

Le système de basculement de section doit s'intégrer avec le système d'autorisations refactoré:

- Utiliser `user_roles_per_section` pour déterminer les sections accessibles
- Vérifier que `revoked_at IS NULL` pour les rôles actifs
- Appliquer les permissions par section (table `role_permissions`)

**Critères d'acceptation FR-06:**
- Appel à `Gvv_Authorization::get_user_sections($user_id)` pour lister les sections autorisées
- Vérification des permissions avant d'afficher les cartes/liens
- Logs d'audit (`authorization_audit_log`) pour les changements de section

---

**FR-07: Filtrage des Éléments de Menu par Section**

Les éléments du menu principal doivent être filtrés selon:
1. La section active
2. Les rôles de l'utilisateur dans cette section

**Exemple:**
- Utilisateur = "Planchiste" en section Planeur
- Menu actif en section Planeur:
  - ✅ Vols
  - ✅ Mon compte
  - ✅ Machines (lecture seule)
  - ❌ Comptabilité (réservé CA/Trésorier)

**Critères d'acceptation FR-07:**
- Menu généré dynamiquement selon section active + rôles
- Respect des niveaux d'autorisation (lecture, modification, admin)
- Liens vers d'autres sections toujours accessibles via le dropdown

---

## 4. Exigences Non Fonctionnelles

### 4.1 Performance

**NFR-01: Temps de Basculement**
- Basculement de section < 500ms
- Pas de rechargement complet de page (utiliser sessions)

**NFR-02: Charge Base de Données**
- Cache des sections utilisateur en session (éviter requêtes répétées)
- Index sur `user_roles_per_section(user_id, section_id, revoked_at)`

### 4.2 Sécurité

**NFR-03: Validation Stricte**
- Aucune manipulation d'URL pour accéder à une section non autorisée
- Logs d'audit des changements de section
- Messages d'erreur génériques (pas de fuite d'infos sur les sections existantes)

**NFR-04: Protection CSRF**
- Routes de basculement protégées par token CSRF si POST
- Routes GET acceptables (lecture seule, pas de modification état)

### 4.3 Compatibilité

**NFR-05: Navigateurs**
- Support: Chrome, Firefox, Safari, Edge (2 dernières versions)
- Dégradation gracieuse sur anciens navigateurs (pas de JavaScript bloquant)

**NFR-06: Mobile**
- Interface responsive (Bootstrap 5)
- Menu dropdown adapté aux écrans tactiles

### 4.4 Maintenabilité

**NFR-07: Architecture**
- Code réutilisable (helper `get_user_sections()`)
- Séparation des préoccupations (modèle, vue, contrôleur)
- Documentation des nouvelles routes

---

## 5. Cas d'Usage Détaillés

### 5.1 UC-01: Consulter Mes Vols dans Plusieurs Sections

**Acteur:** Jean (pilote planeur + ULM)

**Pré-conditions:**
- Jean est connecté
- Jean a des rôles actifs dans les sections Planeur et ULM

**Scénario principal:**
1. Jean accède au dashboard (`/welcome`)
2. Le système affiche deux cartes: "Section Planeur" et "Section ULM"
3. Jean clique sur "Mes vols planeur"
4. Le système charge `/vols_planeur/page` avec `section_id=1`
5. Jean voit ses vols de planeur
6. Jean clique sur le dropdown "Section: Planeur" dans le menu
7. Jean sélectionne "ULM"
8. Le système redirige vers `/sections/switch/2`
9. Le dashboard s'affiche avec la section ULM active
10. Jean clique sur "Mes vols ULM"
11. Le système charge `/vols_avion/page?type=ULM&section_id=2`
12. Jean voit ses vols ULM

**Post-conditions:**
- La session indique `active_section_id = 2`
- Jean peut continuer à naviguer en contexte ULM

---

### 5.2 UC-02: Tentative d'Accès à une Section Non Autorisée

**Acteur:** Marie (membre planeur uniquement)

**Pré-conditions:**
- Marie est connectée
- Marie a des rôles uniquement dans la section Planeur

**Scénario principal:**
1. Marie accède au dashboard
2. Le système affiche uniquement la carte "Section Planeur"
3. Marie tente de manipuler l'URL: `/sections/switch/3` (Avion)
4. Le système détecte l'absence de rôles pour la section Avion
5. Le système retourne une erreur 403: "Accès refusé à cette section"
6. Un log d'audit est créé: "Tentative d'accès non autorisé - Section 3"

**Post-conditions:**
- Marie reste sur la section Planeur
- L'incident est logué

---

### 5.3 UC-03: Basculer de Section avec Redirection Contextuelle

**Acteur:** Pierre (instructeur planeur + avion)

**Pré-conditions:**
- Pierre consulte les vols planeur (`/vols_planeur/page`)

**Scénario principal:**
1. Pierre veut vérifier les vols avion sans passer par le dashboard
2. Pierre clique sur le dropdown "Section: Planeur"
3. Le dropdown affiche: "Planeur ✓", "Avion", "ULM" (désactivé)
4. Pierre clique sur "Avion"
5. Le système détecte qu'il est sur `/vols_planeur/page`
6. Le système redirige vers `/sections/switch/3/redirect/vols_avion/page`
7. La section active devient "Avion"
8. Pierre voit la liste des vols avion

**Post-conditions:**
- Session: `active_section_id = 3`
- Pierre reste dans le contexte "Liste des vols"

---

## 6. Interface Utilisateur

### 6.1 Maquettes Textuelles

**Dashboard Multi-Sections (welcome/index):**

```
╔═══════════════════════════════════════════════════════════════╗
║ GVV | Section: Planeur ▼ | Jean Dupont ⚙️ [Déconnexion]       ║
╚═══════════════════════════════════════════════════════════════╝

┌─────────────────────────────────────────────────────────────┐
│  📊 Bienvenue Jean Dupont                                    │
│  Vous êtes membre de 2 sections : Planeur, ULM              │
└─────────────────────────────────────────────────────────────┘

╔═══════════════════════════════════════════════════════════════╗
║  🪂 SECTION PLANEUR                                            ║
╠═══════════════════════════════════════════════════════════════╣
║  ┌──────────────────┐  ┌──────────────────┐  ┌─────────────┐ ║
║  │ 📋 Mes Vols      │  │ 💰 Mon Compte    │  │ 📅 Calendrier│ ║
║  │ 15 vols en 2025  │  │ Solde: -45.50 €  │  │ Réserver    │ ║
║  └──────────────────┘  └──────────────────┘  └─────────────┘ ║
║                                                                ║
║  ┌──────────────────┐  ┌──────────────────┐                  ║
║  │ ✈️ Machines       │  │ 🏆 Progression   │                  ║
║  │ 5 planeurs       │  │ Carnet de vol    │                  ║
║  └──────────────────┘  └──────────────────┘                  ║
╚═══════════════════════════════════════════════════════════════╝

╔═══════════════════════════════════════════════════════════════╗
║  🛩️ SECTION ULM                                                ║
╠═══════════════════════════════════════════════════════════════╣
║  ┌──────────────────┐  ┌──────────────────┐  ┌─────────────┐ ║
║  │ 📋 Mes Vols      │  │ 💰 Mon Compte    │  │ 📅 Calendrier│ ║
║  │ 8 vols en 2025   │  │ Solde: +120.00 € │  │ Réserver    │ ║
║  └──────────────────┘  └──────────────────┘  └─────────────┘ ║
║                                                                ║
║  ┌──────────────────┐                                         ║
║  │ ✈️ Machines       │                                         ║
║  │ 2 ULM            │                                         ║
║  └──────────────────┘                                         ║
╚═══════════════════════════════════════════════════════════════╝

┌─────────────────────────────────────────────────────────────┐
│  📰 Message du Jour                                          │
│  Assemblée générale le 15 décembre 2025                      │
└─────────────────────────────────────────────────────────────┘
```

---

**Menu Déroulant de Sélection de Section:**

```
╔═══════════════════════════════════════════════════════════════╗
║ GVV | [Section: Planeur ▼] | Jean Dupont ⚙️                   ║
╚═══════════════╦══════════════════════════════════════════════╝
                ║
                ▼
         ┌────────────────────┐
         │ 🪂 Planeur       ✓ │ ← Section active
         │ 🛩️ ULM             │
         │ ✈️ Avion          │ (grisé si pas de rôle)
         │──────────────────  │
         │ ⚙️ Administration  │ (si CA/Bureau)
         └────────────────────┘
```

---

### 6.2 Éléments d'Interface

**Composants Bootstrap 5:**
- Cards (`class="card"`) pour les groupes de sections
- Badges (`class="badge"`) pour les indicateurs
- Dropdown (`class="dropdown"`) pour le sélecteur de section
- Alert (`class="alert alert-info"`) pour les messages de confirmation

**Codes Couleur (de `sections.couleur`):**
- Planeur: `#bdd3ff` (bleu clair)
- ULM: `#f7ca97` (orange clair)
- Avion: `#d1f4c8` (vert clair)
- Général: `#c9c9c9` (gris)

---

## 7. Impacts Techniques

### 7.1 Modifications de Code

**Nouveau Contrôleur: `sections.php`**
```php
class Sections extends Gvv_Controller {

    /**
     * Basculer vers une section
     * @param int $section_id
     */
    public function switch($section_id) {
        // Vérifier autorisation
        // Mettre à jour session
        // Rediriger
    }

    /**
     * Obtenir les sections de l'utilisateur
     */
    public function get_user_sections($user_id) {
        // Requête user_roles_per_section
        // Filtrer revoked_at IS NULL
        // Retourner array de sections
    }
}
```

**Modifications: `welcome.php` (Dashboard)**
- Ajouter logique de génération des cartes par section
- Intégrer `get_user_sections()` pour filtrer
- Passer les données aux vues

**Modifications: `application/views/theme/bs_menu.php`**
- Ajouter dropdown de sélection de section
- Afficher indicateur de section active
- Générer liste des sections autorisées

**Nouveau Helper: `section_helper.php`**
```php
function get_user_active_sections($user_id) { }
function switch_section($section_id) { }
function get_section_color($section_id) { }
```

### 7.2 Modifications de Base de Données

**Aucune modification structurelle requise.**

Les tables existantes suffisent:
- `sections`
- `user_roles_per_section`
- `role_permissions`

**Index recommandé (si absent):**
```sql
CREATE INDEX idx_user_section_role
ON user_roles_per_section(user_id, section_id, revoked_at);
```

### 7.3 Modifications de Session

**Nouvelles clés de session:**
```php
$this->session->set_userdata('active_section_id', $section_id);
$this->session->set_userdata('user_sections', $sections_array);
```

**Chargement au login:**
- Lors de l'authentification, charger toutes les sections autorisées
- Stocker en session pour éviter requêtes répétées
- Invalider à la déconnexion

---

## 8. Migration et Déploiement

### 8.1 Stratégie de Migration

**Phase 1: Préparation (1 jour)**
- Créer index sur `user_roles_per_section`
- Tester performances des requêtes multi-sections

**Phase 2: Développement (3-5 jours)**
- Développer contrôleur `sections.php`
- Développer helper `section_helper.php`
- Modifier dashboard `welcome.php`
- Modifier menu `bs_menu.php`

**Phase 3: Tests (2 jours)**
- Tests unitaires (basculement, autorisations)
- Tests d'intégration (navigation multi-sections)
- Tests de sécurité (tentatives d'accès non autorisés)

**Phase 4: Déploiement (1 jour)**
- Déploiement en production
- Monitoring des logs d'audit
- Communication aux utilisateurs

**Phase 5: Support (1 semaine)**
- Support utilisateurs
- Corrections de bugs mineurs
- Ajustements UX si nécessaire

### 8.2 Compatibilité Ascendante

**Le système reste compatible avec le comportement actuel:**
- Si un utilisateur n'a qu'une seule section → comportement identique
- Pas de changement obligatoire de workflow
- Les anciens liens fonctionnent toujours

---

## 9. Tests d'Acceptation

### 9.1 Tests Fonctionnels

| ID | Test | Critère de Succès |
|----|------|-------------------|
| TA-01 | Dashboard affiche cartes par section | Cartes visibles pour Planeur, ULM, pas pour Avion |
| TA-02 | Clic sur "Mes vols planeur" | Redirection vers `/vols_planeur/page?section=1` |
| TA-03 | Basculement section via dropdown | Section active change, message de confirmation |
| TA-04 | Tentative d'accès section non autorisée | Erreur 403, log d'audit créé |
| TA-05 | Menu filtré par section | Éléments de menu adaptés selon section active |
| TA-06 | Liens contextualisés fonctionnent | Chaque lien charge les bonnes données de section |
| TA-07 | Performance basculement | Basculement < 500ms |
| TA-08 | Mobile responsive | Interface utilisable sur smartphone |

### 9.2 Tests de Sécurité

| ID | Test | Critère de Succès |
|----|------|-------------------|
| TS-01 | Manipulation URL section_id | Accès refusé si non autorisé |
| TS-02 | Session expirée | Redirection vers login |
| TS-03 | Rôle révoqué pendant session | Détection au basculement, section retirée |
| TS-04 | Injection SQL sur section_id | Requêtes préparées, pas d'injection |
| TS-05 | CSRF sur routes de basculement | Routes GET safe, ou CSRF token vérifié |

---

## 10. Métriques de Succès

### 10.1 Métriques Quantitatives

- **Réduction des déconnexions/reconnexions:** -80% (mesure via logs)
- **Temps de basculement moyen:** < 500ms
- **Taux d'erreurs 403:** < 1% des basculements
- **Adoption:** 90% des utilisateurs multi-sections utilisent le dashboard

### 10.2 Métriques Qualitatives

- **Satisfaction utilisateur:** Enquête post-déploiement (cible: 4/5)
- **Clarté de l'interface:** Pas de demandes de support sur "Comment changer de section?"
- **Feedback positif:** Au moins 5 retours positifs dans le premier mois

---

## 11. Risques et Mitigation

| Risque | Impact | Probabilité | Mitigation |
|--------|--------|-------------|------------|
| Performances dégradées avec nombreuses sections | Moyen | Faible | Cache en session, index DB optimisés |
| Confusion utilisateurs sur section active | Élevé | Moyen | Indicateur visuel clair, couleurs de section |
| Bugs d'autorisation (accès non autorisé) | Critique | Faible | Tests de sécurité approfondis, audit logs |
| Incompatibilité mobile | Moyen | Faible | Tests responsive, dégradation gracieuse |
| Migration cassant le workflow existant | Élevé | Faible | Tests de régression, compatibilité ascendante |

---

## 12. Documentation Requise

### 12.1 Documentation Technique

- **Guide développeur:** Architecture du système multi-sections
- **API routes:** Documentation des nouvelles routes `/sections/*`
- **Helper functions:** Documentation de `section_helper.php`

### 12.2 Documentation Utilisateur

- **Guide utilisateur:** "Comment naviguer entre les sections"
- **FAQ:** Questions fréquentes sur le dashboard multi-sections
- **Vidéo tutoriel:** Démonstration du basculement de section (2 min)

---

## 13. Planning et Ressources

### 13.1 Estimation

| Phase | Durée | Ressources |
|-------|-------|------------|
| Préparation DB | 1 jour | 1 Dev backend |
| Développement backend | 3 jours | 1 Dev backend |
| Développement frontend | 2 jours | 1 Dev frontend |
| Tests | 2 jours | 1 QA + 1 Dev |
| Documentation | 1 jour | 1 Dev + 1 Rédacteur |
| Déploiement | 1 jour | 1 Dev + 1 Ops |
| **Total** | **10 jours** | **~8 jours-personnes** |

### 13.2 Dépendances

- **Bloquant:** Nouveau système d'autorisations v2.0 doit être déployé
- **Souhaitable:** Refactoring du menu principal (peut être fait en parallèle)

---

## 14. Évolutions Futures (Hors Scope v1.0)

### 14.1 Fonctionnalités Avancées

- **Dashboard personnalisable:** Glisser-déposer les cartes
- **Notifications multi-sections:** Badge de notifications par section
- **Thèmes de couleur par section:** Interface complète colorée selon section active
- **Basculement rapide clavier:** Raccourcis Ctrl+1, Ctrl+2, etc.
- **Historique de navigation:** Breadcrumb multi-sections

### 14.2 Optimisations

- **API REST:** Endpoints pour basculement sans rechargement (AJAX)
- **WebSockets:** Notifications temps réel par section
- **Progressive Web App:** Application mobile dédiée

---

## 15. Validation et Approbation

| Rôle | Nom | Date | Signature |
|------|-----|------|-----------|
| Product Owner | | | |
| Tech Lead | | | |
| Responsable Sécurité | | | |
| Utilisateur Référent (Multi-sections) | | | |

---

## 16. Annexes

### 16.1 Annexe A: Exemples de Requêtes SQL

**Récupérer les sections d'un utilisateur:**
```sql
SELECT DISTINCT s.id, s.nom, s.acronyme, s.couleur
FROM sections s
JOIN user_roles_per_section urps ON urps.section_id = s.id
WHERE urps.user_id = ?
  AND urps.revoked_at IS NULL
ORDER BY s.id;
```

**Vérifier autorisation section:**
```sql
SELECT COUNT(*)
FROM user_roles_per_section
WHERE user_id = ?
  AND section_id = ?
  AND revoked_at IS NULL;
```

### 16.2 Annexe B: Structure de Session

```php
Array (
    'user_id' => 42,
    'username' => 'jdupont',
    'active_section_id' => 1,  // Section courante
    'user_sections' => Array (  // Cache des sections autorisées
        1 => Array (
            'nom' => 'Planeur',
            'acronyme' => 'PLA',
            'couleur' => '#bdd3ff',
            'roles' => Array('planchiste', 'pilote')
        ),
        2 => Array (
            'nom' => 'ULM',
            'acronyme' => 'ULM',
            'couleur' => '#f7ca97',
            'roles' => Array('pilote')
        )
    )
)
```

### 16.3 Annexe C: Glossaire

- **Section:** Groupement d'activités (Planeur, Avion, ULM, Général)
- **Section active:** Section dans laquelle l'utilisateur navigue actuellement
- **Multi-sections:** Utilisateur ayant des rôles dans plusieurs sections
- **Basculement:** Action de changer de section active
- **Dashboard:** Page d'accueil présentant un résumé des activités
- **Carte:** Composant visuel regroupant des liens contextuels

---

**Fin du PRD**
