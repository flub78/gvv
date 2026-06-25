# Plan de développement — Relances des comptes débiteurs

> Référence PRD : [`doc/prds/relances_prd.md`](../prds/relances_prd.md)

---

## 1. Vue d'ensemble

Le projet est découpé en deux phases indépendantes et livrables séparément :

- **Phase 1** — Tableau de pilotage des débiteurs (prioritaire)
- **Phase 2** — Relances par email (optionnelle, dépend de la phase 1)

---

## 2. Dépendances techniques

| Dépendance | Localisation | Usage |
|---|---|---|
| Modèle de comptes | `application/models/ecritures_model.php` | Calcul des soldes par compte (`solde_compte`) |
| Modèle de configuration | `application/models/configuration_model.php` | Lecture/écriture des seuils et du template |
| Contrôleur comptable | `application/controllers/compta.php` | Pattern d'autorisation (`require_roles`) |
| Menu principal | `application/views/bs_menu.php` | Ajout de l'entrée Relances dans Compta |
| Dashboard Trésorerie | `application/views/comptes/bs_dashboardView.php` | Ajout d'une carte de raccourci |
| Migration précédente | `134_reminder_channel_add_none.php` | Numérotation : prochaine migration = 135 |

---

## 3. Phase 1 — Tableau de pilotage des débiteurs

### 3.1 Migration base de données (135)

Fichier : `application/migrations/135_create_relances_config.php`

Ajoute dans la table `configuration` les clés :
- `relances.seuil_alarme` (défaut : 300)
- `relances.seuil_critique` (défaut : 500)

Pas de nouvelle table : les seuils sont stockés via le modèle de configuration existant.

Mise à jour de `application/config/migration.php` → version 135.

### 3.2 Requête SQL des débiteurs

Nouveau modèle : `application/models/relances_model.php`

Méthode `get_debiteurs($date_ref = null)` qui retourne pour chaque membre :
- `membre_id`, `nom`, `prenom`, `email`
- `total` — solde agrégé tous comptes 411 toutes sections
- `cg` — solde compte général (section 0)
- `avion`, `planeur`, `ulm` — soldes par section
- `total_6m` — même calcul à J−180
- `total_1an` — même calcul à J−365
- `nb_relances` — 0 en phase 1 (sera alimenté en phase 2)
- `date_derniere_relance` — null en phase 1

S'appuie sur `ecritures_model::solde_compte()` per membre et section, ou sur une requête SQL directe regroupant les écritures par compte 411 et section pour la performance.

La liste exclut les membres avec `total >= 0` (pas débiteurs, CL-001).

### 3.3 Contrôleur

Fichier : `application/controllers/relances.php`

Étend `MY_Controller`. Méthodes :

| Méthode | Route | Rôle |
|---|---|---|
| `index()` | `relances/index` | Affiche la liste avec seuils |
| `update_seuils()` | `relances/update_seuils` (POST) | Sauvegarde les seuils via configuration_model |

Autorisation : `$this->require_roles(['tresorier', 'bureau', 'club-admin'])`.

Lecture des seuils depuis `configuration_model::get_param()`. Sauvegarde par upsert.

### 3.4 Vue principale

Fichier : `application/views/relances/bs_relancesView.php`

- Champs seuils éditables en haut du tableau avec bouton « Appliquer ».
- Case à cocher « Mode anonyme » (activée par défaut via `localStorage` ou session).
- Tableau Bootstrap 5 trié par `total` décroissant (colonnes : Nom, Total, CG, Avion, Planeur, ULM, 6 mois, 1 an, Relances, Dernière relance).
- Mise en couleur CSS par ligne : `table-danger` (≥ seuil critique), `table-warning` (≥ seuil alarme), neutre sinon (EF-036).
- Mode anonyme : filtre CSS `blur(5px)` sur les cellules `nom`/`prenom`.
- Colonne Relances : affiche « 0 » avec un bouton désactivé en phase 1.

### 3.5 Intégration menu et dashboard

**Menu** (`application/views/bs_menu.php`) :
Ajouter dans le bloc Compta une entrée `relances/index` visible pour les rôles trésorier, bureau, club-admin.

**Dashboard Trésorerie** (`application/views/comptes/bs_dashboardView.php`) :
Ajouter une carte Bootstrap pointant vers `relances/index`.

**Fichiers de langue** :
Ajouter les clés dans `french/`, `english/`, `dutch/`.

### 3.6 Tests Phase 1

| Type | Fichier | Contenu |
|---|---|---|
| Intégration | `application/tests/integration/RelancesModelTest.php` | `get_debiteurs()` retourne les membres débiteurs triés ; exclusion des soldes ≥ 0 |
| Intégration | `application/tests/integration/RelancesConfigTest.php` | Lecture/écriture des seuils via configuration_model |
| Playwright | `playwright/tests/relances.spec.js` | Accès à la page, affichage du tableau, case anonyme, modification des seuils |

---

## 4. Phase 2 — Relances par email

> Dépend de la Phase 1. Peut être reportée sans impact sur la Phase 1.

### 4.1 Migration base de données (136)

Fichier : `application/migrations/136_create_relances_log.php`

Nouvelle table `relances_log` :

```sql
CREATE TABLE relances_log (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    membre_id    INT UNSIGNED NOT NULL,
    sent_at      DATETIME NOT NULL,
    recipient    VARCHAR(255) NOT NULL,
    cc_list      TEXT DEFAULT NULL,
    subject      VARCHAR(255) DEFAULT NULL,
    body         LONGTEXT NOT NULL,
    sent_by      VARCHAR(255) NOT NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by   VARCHAR(255) DEFAULT NULL,
    updated_by   VARCHAR(255) DEFAULT NULL,
    KEY idx_membre (membre_id),
    KEY idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

Mise à jour de `config/migration.php` → version 136.

Également : ajouter dans `configuration` la clé `relances.email_template` (texte long).

### 4.2 Modèle relances_log

Étend `relances_model.php` (ou fichier séparé `relances_log_model.php`) :

| Méthode | Usage |
|---|---|
| `get_relances_membre($membre_id)` | Historique d'un membre |
| `save_relance($data)` | Insère une entrée après envoi |
| `get_relance($id)` | Retourne une relance pour visualisation |

Comptage et date de dernière relance dans `get_debiteurs()` alimentés depuis `relances_log`.

### 4.3 Contrôleur — nouvelles méthodes

Dans `application/controllers/relances.php` :

| Méthode | Route | Rôle |
|---|---|---|
| `detail($membre_id)` | `relances/detail/N` | Page de détail d'un membre débiteur |
| `send($membre_id)` | `relances/send/N` (POST) | Envoie l'email et enregistre dans `relances_log` |
| `view_relance($id)` | `relances/view/N` | Affiche le contenu d'une relance envoyée |
| `save_template()` | `relances/save_template` (POST) | Sauvegarde le template par défaut |

Logique d'envoi email :
1. Charger `email` CI library.
2. Résoudre l'email du membre (bloquer si absent, CL-002).
3. Résoudre les emails des trésoriers pour la copie (EF-066).
4. Construire le corps avec les données de dette du membre.
5. Envoyer.
6. Insérer dans `relances_log` (recipient, cc_list JSON, body).

### 4.4 Vues Phase 2

**`application/views/relances/bs_relances_detailView.php`**
- En-tête : nom, dette totale et par section.
- Datatable des relances passées (colonnes : date, destinataire, bouton Voir).
- Bloc « Nouvelle relance » : textarea préremplie avec le template, bouton Envoyer, aperçu (QO-003).
- Bouton retour vers la liste.

**`application/views/relances/bs_relances_viewView.php`**
- Affiche le contenu brut d'une relance archivée (date, destinataire, cc, corps).

**Génération du template par défaut** :
Fonction PHP `relances_build_default_body($membre, $dettes)` dans le contrôleur ou un helper dédié. Produit le texte du PRD §6.7 avec substitutions des montants.

### 4.5 Template email — clés de configuration

| Clé | Valeur par défaut |
|---|---|
| `relances.email_template` | Texte du PRD §6.7 avec marqueurs `{nom}`, `{prenom}`, `{total}`, `{cg}`, `{avion}`, `{planeur}`, `{ulm}`, `{total_6m}` |
| `relances.email_subject` | `"Relance — solde débiteur au club"` |

Éditable depuis la page de configuration existante ou depuis la page de détail relance.

### 4.6 Tests Phase 2

| Type | Fichier | Contenu |
|---|---|---|
| Intégration | `application/tests/integration/RelancesLogModelTest.php` | CRUD `relances_log`, comptage, date dernière relance |
| Intégration | `application/tests/integration/RelancesEmailTest.php` | Construction du template, substitutions, validation email (CL-002, CL-005) |
| Playwright | `playwright/tests/relances_phase2.spec.js` | Ouverture détail, visu historique, aperçu, envoi (mock SMTP ou vérification de trace) |

---

## 5. Cas limites à traiter explicitement

| Référence | Traitement |
|---|---|
| CL-001 | Filtre SQL `total < 0` — seuls les débiteurs apparaissent |
| CL-002 | Vérifier `membre.email` avant envoi ; afficher un message d'erreur si manquant |
| CL-003 | Détail par section toujours affiché même si une section est à 0 |
| CL-004 | Datatable avec pagination côté client (DataTable Bootstrap) |
| CL-005 | Si `relances.email_template` vide, utiliser le texte minimal du PRD §6.7 |
| CL-006 | Chaque relance est une entrée distincte dans `relances_log`, même si même jour |

---

## 6. Séquence de livraison recommandée

```
Phase 1 :
  1. Migration 135 (seuils)
  2. relances_model (get_debiteurs)
  3. Contrôleur relances (index, update_seuils)
  4. Vue bs_relancesView
  5. Intégration menu + dashboard
  6. Fichiers de langue
  7. Tests PHPUnit Phase 1
  8. Tests Playwright Phase 1

Phase 2 (après validation Phase 1) :
  9.  Migration 136 (relances_log + template config)
  10. relances_log_model
  11. Contrôleur — méthodes detail, send, view_relance, save_template
  12. Vues détail et view
  13. Helper template + substitutions
  14. Tests PHPUnit Phase 2
  15. Tests Playwright Phase 2
```

---

## 7. Critères de complétion

### Phase 1 ✅ Livré
- [x] Migration 135 appliquée, version config à jour
- [x] Page `relances/index` accessible aux rôles autorisés
- [x] Tableau trié par dette décroissante, colonnes complètes
- [x] Mise en couleur par seuil fonctionnelle
- [x] Seuils modifiables et persistés
- [x] Mode anonyme actif par défaut, basculable
- [x] Entrée menu Compta présente
- [x] Carte dashboard Trésorerie présente
- [x] Tests PHPUnit (11/11) et Playwright (8/8) verts

### Phase 2
- [ ] Migration 136 appliquée, version config à jour
- [ ] Page détail affiche l'historique et le formulaire de relance
- [ ] Email envoyé au membre avec copie trésoriers
- [ ] Contenu archivé dans `relances_log` (recipient, cc, body)
- [ ] Visualisation d'une relance passée fonctionnelle
- [ ] Template sauvegardable et rechargeable
- [ ] CL-002 (email manquant) signalé clairement à l'utilisateur
- [ ] Tests PHPUnit et Playwright verts
