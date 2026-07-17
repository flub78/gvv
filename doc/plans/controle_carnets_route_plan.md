# Plan de développement — Contrôle des carnets de route

Référence PRD : [`doc/prds/controle_carnets_route_prd.md`](../prds/controle_carnets_route_prd.md)

---

## 1. Vue d'ensemble

Nouvelle fonctionnalité en lecture seule : pas de migration de base de données. Elle s'appuie exclusivement sur les tables existantes `volsa`, `membres` et `machinesa`. Le développement suit le workflow GVV standard (controller → model → view → langues → menu/dashboard → tests).

---

## 2. Fichiers à créer ou modifier

| Fichier | Action | État |
|---|---|---|
| `application/controllers/carnets_route.php` | Créer | ✅ |
| `application/models/carnets_route_model.php` | Créer | ✅ |
| `application/helpers/carnets_route_helper.php` | Créer | ✅ |
| `application/views/carnets_route/bs_page.php` | Créer | ✅ |
| `application/language/french/carnets_route_lang.php` | Créer | ✅ |
| `application/language/english/carnets_route_lang.php` | Créer | ✅ |
| `application/language/dutch/carnets_route_lang.php` | Créer | ✅ |
| `application/views/bs_menu.php` | Modifier — entrée menu Avion | ✅ |
| `application/views/bs_sub_dashboard.php` | Modifier — carte admin_club | ✅ |

---

## 3. Étapes de développement

### ✅ Étape 1 — Modèle de données

**Fichier** : `application/models/carnets_route_model.php`

Requête principale : vols d'une machine sur une période, triés chronologiquement par `vadate ASC, vacdeb ASC`.

```
SELECT vadate, vapilid, vamacid, vacdeb, vacfin, vaduree, valieudeco, valieuatt,
       vaobs, concat(mprenom,' ',mnom) as pilote, machinesa.horametre_mode
FROM volsa
JOIN membres ON volsa.vapilid = membres.mlogin
JOIN machinesa ON volsa.vamacid = machinesa.macimmat
WHERE vamacid = ? AND vadate BETWEEN ? AND ?
  AND volsa.club = ?
ORDER BY vadate ASC, vacdeb ASC
```

Méthodes :
- `get_flights($macid, $date_debut, $date_fin, $club_id)` — retourne les vols bruts
- `get_avions($club_id)` — liste des avions de la section (utilise `machinesa_model` existant)

### ✅ Étape 2 — Logique de contrôle de continuité

**Fichier** : `application/helpers/carnets_route_helper.php` + `application/tests/unit/helpers/CarnetRouteContinuiteTest.php` (16 tests, 38 assertions).

Implémentée en helper PHP pur (sans dépendance CI) pour permettre les tests unitaires sans base de données.

Algorithme : itérer les vols triés, comparer `vacfin[i]` avec `vacdeb[i+1]`.

Pour chaque paire successive :
- **Continuité exacte** (`vacfin[i] == vacdeb[i+1]`) → marquer les deux vols `status = 'ok'`
- **Écart** (`vacfin[i] < vacdeb[i+1]`) → marquer les deux vols `status = 'gap'`, insérer une ligne intermédiaire `type = 'gap'` avec `duree = vacdeb[i+1] - vacfin[i]`
- **Recouvrement** (`vacfin[i] > vacdeb[i+1]`) → marquer les deux vols `status = 'overlap'`, insérer une ligne intermédiaire `type = 'overlap'` avec `duree = vacfin[i] - vacdeb[i+1]`
- **Horamètre manquant** (`vacdeb == 0 && vacfin == 0`) → marquer le vol `status = 'missing'`, insérer une ligne intermédiaire `type = 'missing'`

Premier et dernier vol : pas de comparaison entrante/sortante respectivement ; ils héritent du statut de leur seule transition.

Le résultat est un tableau plat `$rows` avec pour chaque entrée : `type` (`flight` ou `gap`/`overlap`/`missing`), les données du vol, et `status` (`ok`, `gap`, `overlap`, `missing`).

Calcul du résumé : compter les lignes intermédiaires par type pour l'encart en tête de page (QO-002 du PRD).

### ✅ Étape 3 — Contrôleur

**Fichier** : `application/controllers/carnets_route.php`

- Étend `Gvv_Controller`
- Rôle requis : `club-admin` ou `ca` (même pattern que `bs_menu.php` ligne 154)
- Méthodes :
  - `page()` — affichage principal, filtres, appel modèle, construction `$rows`, rendu vue
  - `csv()` — export CSV (génération manuelle car format non standard avec lignes intermédiaires)
  - `pdf()` — export PDF via `Pdf` library (TCPDF), conserve la mise en couleur

Filtres gérés en session (pattern identique à `vols_avion`) :
- `carnet_macid` — avion sélectionné
- `carnet_date_debut` — défaut : `date('Y') . '-01-01'`
- `carnet_date_fin` — défaut : `date('Y-m-d')`

### ✅ Étape 4 — Vue

**Fichier** : `application/views/carnets_route/bs_page.php`

Structure :
1. **Zone filtres** — sélecteur avion, date début, date fin, bouton Appliquer
2. **Résumé des anomalies** — encart Bootstrap alert si anomalies détectées : `X écart(s)`, `Y recouvrement(s)`, `Z horamètre(s) manquant(s)`
3. **Tableau principal** — DataTables (déjà utilisé dans le projet)
   - Colonnes : Date, Pilote, Immat, Hora. début, Hora. fin, Durée, Départ, Arrivée, Observation
   - Lignes de vol : classe Bootstrap `table-success` (ok) ou `table-danger` (gap/overlap/missing)
   - Lignes intermédiaires : fond `table-warning` pour écart, `table-danger` pour recouvrement, `table-secondary` pour manquant ; affichent le type et la durée de l'anomalie
4. **Boutons export** CSV et PDF

L'horamètre est affiché selon `horametre_mode` de la machine (décimal ou HH:MM), en réutilisant les helpers existants.

### ✅ Étape 5 — Entrée de menu

**Fichier** : `application/views/bs_menu.php` — dans le bloc `gestion_avions`, après la ligne `vols_avion/page`, sous condition `has_role('club-admin') || has_role('ca')` :

```php
<?php if (has_role('club-admin') || has_role('ca')): ?>
<li><a class="dropdown-item" href="<?= controller_url('carnets_route/page') ?>">
    <i class="fas fa-book text-warning"></i> <?= translation('gvv_menu_carnets_route') ?>
</a></li>
<?php endif; ?>
```

### ✅ Étape 6 — Carte dashboard

**Fichier** : `application/views/bs_sub_dashboard.php` — dans la section `admin_club`, sous condition `has_role('club-admin')` :

```php
<?php if (has_role('club-admin') && $show_avions): ?>
<div class="col-6 col-md-4 col-lg-3 col-xl-2">
    <div class="sub-card text-center">
        <i class="fas fa-book text-warning"></i>
        <div class="card-title"><?= $this->lang->line('db_card_carnets_route') ?></div>
        <div class="card-text text-muted"><?= $this->lang->line('db_desc_carnets_route') ?></div>
        <a href="<?= controller_url('carnets_route/page') ?>" class="btn btn-warning btn-sm">
            <?= $this->lang->line('db_btn_controle') ?>
        </a>
    </div>
</div>
<?php endif; ?>
```

### ✅ Étape 7 — Fichiers de langue

Clés à définir dans les trois langues :

| Clé | Français |
|---|---|
| `gvv_menu_carnets_route` | Carnets de route |
| `carnets_route_title` | Contrôle des carnets de route |
| `carnets_route_filter_machine` | Avion |
| `carnets_route_filter_date_debut` | Date de début |
| `carnets_route_filter_date_fin` | Date de fin |
| `carnets_route_col_date` | Date |
| `carnets_route_col_pilote` | Pilote |
| `carnets_route_col_immat` | Immatriculation |
| `carnets_route_col_hora_deb` | Hora. début |
| `carnets_route_col_hora_fin` | Hora. fin |
| `carnets_route_col_duree` | Durée |
| `carnets_route_col_obs` | Observation |
| `carnets_route_gap` | Écart |
| `carnets_route_overlap` | Recouvrement |
| `carnets_route_missing` | Horamètre manquant |
| `carnets_route_summary_ok` | Aucune anomalie détectée |
| `carnets_route_summary_anomalies` | Anomalies détectées |
| `db_card_carnets_route` | Carnets de route |
| `db_desc_carnets_route` | Contrôle de continuité horamètre |
| `db_btn_controle` | Contrôler |

---

## 4. Exports

### CSV
Génération manuelle via `header()` + `fputcsv()` dans le contrôleur. Les lignes intermédiaires sont incluses avec un marqueur dans la colonne Pilote (ex. `[ÉCART]`). Pas de couleur possible en CSV.

### PDF
Utilisation de `Pdf` (wrapper TCPDF existant). Coloration des lignes :
- Appel `$pdf->SetFillColor(r,g,b)` avant chaque ligne
- Vert pour continuité (`198, 239, 206`), rouge pour anomalie (`255, 199, 206`), orange pour ligne intermédiaire (`255, 235, 156`)

---

## 5. Tests

### ✅ Tests unitaires
**Fichier** : `application/tests/unit/helpers/CarnetRouteContinuiteTest.php` — 16 tests, 38 assertions (100% pass)

Tester la fonction de calcul de continuité isolément (sans base de données) :
- Vol unique → aucune comparaison, statut neutre
- Deux vols en continuité → les deux en `ok`
- Deux vols avec écart → ligne intermédiaire `gap` insérée
- Deux vols avec recouvrement → ligne intermédiaire `overlap` insérée
- Vol avec horamètre à zéro → statut `missing`
- Date début > date fin → liste vide
- Séquence longue avec plusieurs anomalies entremêlées

### ✅ Tests d'intégration (smoke)
**Fichier** : `application/tests/integration/CarnetRouteSmokeTest.php`

- Vérifier que `GET /carnets_route/page` répond 200 pour un utilisateur `club-admin`
- Vérifier que la page est refusée (403/redirect) pour un utilisateur sans rôle
- Vérifier que `GET /carnets_route/csv` retourne un Content-Type `text/csv`
- Vérifier que `GET /carnets_route/pdf` retourne un Content-Type `application/pdf`

### ✅ Test Playwright (smoke)
**Fichier** : `playwright/tests/carnets_route.spec.js`

- Connexion en tant qu'admin
- Navigation vers Avion > Carnets de route
- Sélection d'un avion et application du filtre
- Vérification de la présence du tableau
- Vérification de la présence du résumé d'anomalies
- Clic sur export CSV (vérifie le téléchargement)

---

## 6. Cas limites à couvrir explicitement

Voir CL-001 à CL-006 du PRD. Traitement prévu :
- **CL-001 / CL-002** : afficher un message d'information si 0 ou 1 vol
- **CL-003** : horamètre nul → ligne `missing` + vols adjacents en rouge
- **CL-004** : plusieurs vols même jour → ordre `vadate ASC, vacdeb ASC` garantit le bon enchaînement
- **CL-005** : `horametre_mode` lu depuis `machinesa`, appliqué uniformément
- **CL-006** : validation côté contrôleur, message d'erreur Bootstrap si date_debut > date_fin

---

## 7. Ordre d'implémentation recommandé

1. ✅ Modèle (`carnets_route_model.php`) + helper de continuité (`carnets_route_helper.php`) + tests unitaires
2. ✅ Contrôleur (`carnets_route.php`) + vue (`bs_page.php`) avec filtres, tableau coloré, résumé anomalies, export CSV/PDF + fichiers de langue (fr/en/nl)
3. ✅ Menu + dashboard card
4. ✅ Tests smoke intégration + Playwright
