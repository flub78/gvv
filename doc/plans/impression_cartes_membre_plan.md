# Plan d'implémentation — Impression cartes membre

Date: 29 avril 2026 — Design: 1 mai 2026
Source PRD: `doc/prds/impression_cartes_membre_prd.md`

## Décisions de conception

| Point | Décision |
|-------|----------|
| Numéro membre | Champ `mnumero` ajouté à la table `membres` via migration |
| Signature président | Texte seul (`mnom` + `mprenom` du membre ayant `mniveaux & PRESIDENT`) |
| Fond absent | Impression sans fond avec bordure 1 px, génération non bloquée |
| Ordre verso planche | Miroir horizontal : pour N cartes par page, verso imprimé en ordre inverse |
| Mise en page | Configuration JSON par saison ; moteur de rendu séparé du contrôleur |
| Stockage config | Clé `carte_layout_{annee}` dans la table `configuration`, fichier JSON dans `uploads/configuration/` |
| Réutilisabilité | Le moteur de rendu configurable sera réutilisé pour les bons de vols de découverte |

## Design technique

### Bibliothèque PDF : TCPDF

TCPDF (déjà dans `application/third_party/tcpdf/`) est utilisé pour les cartes plutôt que `Pdf.php` (tFPDF), car il permet le placement absolu en mm d'images et de texte sur page blanche — même pattern que `vols_decouverte::generate_pdf()`.

### Format des fonds de carte

- Dimensions : **85,6 × 54 mm** (ISO ID-1, format carte bancaire) à 300 dpi → **1011 × 638 px**
- Formats acceptés : JPEG ou PNG
- Stockage : clés `carte_recto_{annee}` et `carte_verso_{annee}` dans la table `configuration`, fichiers dans `uploads/configuration/`
- Fallback fond absent : carte avec fond blanc + bordure 1 px, génération autorisée

### Gabarit Avery C32016-10

10 cartes par A4, 2 colonnes × 5 lignes :

```
Marges (mm) : haut = 13,0   gauche = 15,0
Carte        : 85,6 × 54 mm
Gouttière H  : 10,0 mm       Gouttière V : 0 mm

Col 0 : X = 15,0    Col 1 : X = 110,6
Lig 0 : Y = 13,0    Lig 1 : Y = 67,0    Lig 2 : Y = 121,0
Lig 3 : Y = 175,0   Lig 4 : Y = 229,0
```

### Format JSON de configuration de mise en page

Une configuration de mise en page est un fichier JSON structuré ainsi :

```json
{
  "version": 1,
  "recto": {
    "variable_fields": [
      {
        "id": "nom_prenom",
        "enabled": true,
        "x": 3, "y": 28,
        "font": "helvetica", "bold": true, "size": 9,
        "color": [0, 0, 0]
      },
      { "id": "saison",         "enabled": true,  "x": 55, "y": 3,  "font": "helvetica", "bold": false, "size": 7,  "color": [0,0,0] },
      { "id": "activites",      "enabled": false, "x": 3,  "y": 42, "font": "helvetica", "bold": false, "size": 6,  "color": [0,0,0] },
      { "id": "numero_membre",  "enabled": true,  "x": 3,  "y": 36, "font": "helvetica", "bold": false, "size": 7,  "color": [0,0,0] },
      { "id": "numero_carte",   "enabled": false, "x": 3,  "y": 48, "font": "helvetica", "bold": false, "size": 7,  "color": [0,0,0] }
    ],
    "static_fields": [
      { "text": "Aéroclub d'Abbeville",    "x": 3,  "y": 3,  "font": "helvetica", "bold": true,  "size": 7, "color": [0,0,0] }
    ],
    "photo": { "enabled": true, "x": 62, "y": 14, "w": 20, "h": 25 }
  },
  "verso": {
    "variable_fields": [
      { "id": "nom_president",  "enabled": true,  "x": 3,  "y": 36, "font": "helvetica", "bold": true,  "size": 7, "color": [0,0,0] }
    ],
    "static_fields": [
      { "text": "Le Président", "x": 3, "y": 32, "font": "helvetica", "bold": false, "size": 6, "color": [0,0,0] }
    ],
    "photo": null
  }
}
```

**Champs variables disponibles :**

| id | Source |
|----|--------|
| `nom_prenom` | `mprenom` + `mnom` du membre |
| `saison` | Année de la carte |
| `activites` | Libellé(s) de section(s) du membre |
| `numero_membre` | `mnumero` du membre |
| `numero_carte` | Numéro séquentiel dans le lot |
| `nom_president` | `mprenom` + `mnom` du président actif |

### Architecture

```
Controller : cartes_membre
├── index()                  → redirige vers lot()
├── lot()                    → sélection lot (admin)
├── lot_pdf()                → PDF planches recto/verso (admin)
├── config()                 → fonds + export/import JSON mise en page (admin)
├── layout_save()            → sauvegarde configuration JSON (POST, admin)
├── layout_export($annee)    → télécharge le fichier JSON (admin)
├── layout_import()          → import depuis fichier JSON uploadé (admin)
└── carte($mlogin, $year)    → PDF individuel (Lot 3)

Model : cartes_membre_model
├── get_membre($mlogin)
├── get_years_with_cotisation($mlogin)
├── get_membres_actifs_annee($year)
├── get_president()
├── get_photo_path($photo)
├── get_fond_path($annee, $face)
├── save_fond_path($annee, $face, $valeur)
├── get_layout($annee)           → retourne la config JSON décodée (défaut embarqué si absente)
└── save_layout($annee, $layout) → encode et stocke le JSON

Library : Cartes_membre_pdf extends TCPDF
├── render_recto($data, $layout, $fond, $ox, $oy)   ← layout injecté
├── render_verso($data, $layout, $fond, $ox, $oy)
├── render_recto_page(array $cards, $layout, $fond)
├── render_verso_page(array $cards, $layout, $president, $fond)
├── generate_lot(array $membres, $layout, $president, $fond_recto, $fond_verso)
└── generate_individuelle($data, $layout, $president, $fond_recto, $fond_verso)
```

La bibliothèque reçoit la configuration de mise en page par injection ; elle ne contient aucune position codée en dur après la refonte.

### Rendu d'un champ avec la configuration

Pour chaque champ activé dans le layout, le moteur applique :

```
SetFont(font, bold ? 'B' : '', size)
SetTextColor(r, g, b)
SetXY(ox + field.x, oy + field.y)
Cell(w, h, valeur_resolue, ...)
```

La `valeur_resolue` est obtenue par un résolveur qui mappe l'`id` du champ variable vers la donnée membre correspondante.

### Planche recto/verso (lot)

Pour N membres, par tranches de 10 :

```
Page 2k-1 : rectos des cartes [10k+1 .. 10k+10]   (ordre naturel)
Page 2k   : versos des cartes [10k+10 .. 10k+1]   (ordre miroir horizontal)
```

---

## Stratégie de livraison

| Lot | Contenu | Statut |
|-----|---------|--------|
| Lot 1 | Planches en lot, layout statique, fonds, menu, dashboard | ✅ Livré |
| Lot 2 | Moteur de mise en page configurable (JSON) + UI de configuration | ✅ Livré |
| Lot 3 | Cartes individuelles (membre + administrateur) | À faire |

Lot 2 doit être terminé avant Lot 3 car Lot 3 réutilise le moteur configurable.

## Definition of Done globale

- EF3, EF4, EF5, EF6, EF7, EF8 (partie lot + config) couverts pour les lots 1 et 2.
- EF1, EF2, EF7 (partie individuelle) couverts pour le lot 3.
- PDF imprimables sur A4, alignement recto/verso cohérent.
- Configuration JSON exportable, importable, reproductible.
- Tests automatisés ajoutés et passants dans la suite projet.
- Smoke Playwright disponible pour chaque parcours critique.

---

## Lot 1 (livré) — Planches A4 recto/verso, layout statique ✅

Réalisations :
- Migration 105 (`mnumero` sur `membres`), métadonnée `Gvvmetadata`
- `cartes_membre_model` : accès données membres, cotisations, président, fonds
- `Cartes_membre_pdf` : layout statique codé en dur (positions, polices, couleurs)
- Contrôleur `cartes_membre` : `lot()`, `lot_pdf()`, `config()` (upload fonds)
- Vues `bs_lot.php`, `bs_config.php`
- Menu Membres (club-admin), dashboard Administration Club
- Tests PHPUnit migration + modèle, smoke Playwright 9 tests

---

## Lot 2 — Moteur de mise en page configurable ✅

### Étape A — Modèle et format JSON ✅

Réalisations :
- `get_layout($annee)` et `save_layout($annee, $layout)` dans `cartes_membre_model`
- `_default_layout()` embarquée : reproduit exactement le comportement statique du Lot 1
- Tests PHPUnit : `get_layout` retourne le défaut, round-trip `save_layout`/`get_layout`, upsert sans doublon

### Étape B — Refonte du moteur PDF ✅

Réalisations :
- `Cartes_membre_pdf` entièrement refactorisé : layout injecté en paramètre sur toutes les méthodes publiques
- Résolveur `resolve_variable($id, $data)` : mappe les 7 champs variables vers `$data`
- `render_face()` générique, `render_field()` et `render_background()` extraits
- `lot_pdf()` injecte `nom_club`, `nom_president`, `numero_carte`, `annee` dans chaque entrée membre
- Smoke Playwright : génération PDF lot, 16 tests passants

### Étape C — UI de configuration mise en page ✅

Réalisations :
- Vue `bs_layout.php` : onglets Recto/Verso, tableau champs variables, tableau champs statiques dynamique (ajout/suppression JS), section photo
- Actions contrôleur : `layout()`, `layout_save()`, `layout_export()`, `layout_import()`, `layout_reset()`
- Helpers privés : `_hex_to_rgb()`, `_parse_layout_from_post()`
- Lien « Configurer la mise en page » ajouté à `bs_config.php`
- Strings multilingues (fr/en/nl) pour toute l'UI layout
- Smoke Playwright : 7 nouveaux tests (accès, onglets, sauvegarde avec confirmation, export/import, lien depuis config)

### Gate de fin Lot 2 ✅

- Layout configurable appliqué sur la génération lot.
- Export JSON produit un fichier valide ; import du même fichier produit un résultat identique.
- Aucune régression : 1 257 tests PHPUnit passants, 16 smoke Playwright passants.

---

## Lot 3 — Cartes individuelles

### Étape D — Impression individuelle membre

Implémentation :
1. Ajouter entrée « Imprimer ma carte » dans l'espace membre.
2. Proposer par défaut la dernière année avec cotisation.
3. Permettre la sélection parmi les années cotisées uniquement.
4. Générer via `generate_individuelle()` avec le layout de la saison.

Validation :
- Tests autorisation : membre limité à sa propre carte.
- Smoke Playwright membre jusqu'au téléchargement PDF.

### Étape E — Impression individuelle administrateur

Implémentation :
1. Implémenter `cartes_membre/carte($mlogin, $year)` : recherche/sélection de membre, génération sans contrainte de cotisation, layout de la saison.

Validation :
- Tests intégration : génération admin avec et sans cotisation.
- Tests autorisation : réservé admin.

### Gate de fin Lot 3

- EF1, EF2, EF7 (partie individuelle) démontrables.
- Parcours membre et admin individuels passent en smoke test.

---

## Plan de tests transverse

```bash
source setenv.sh
./run-all-tests.sh
cd playwright && npx playwright test --reporter=line
```

## Risques et parades

- **Décalage recto/verso** : verrouiller l'ordre carte dans le modèle, recette physique obligatoire.
- **Président introuvable** : bloquer la génération avec message explicite.
- **Volume lot élevé** : génération en tranches de 10, alerter si > 30 secondes.
- **Config JSON invalide à l'import** : valider le JSON et les champs obligatoires avant d'accepter l'import ; rejeter avec message d'erreur explicite.
- **Régression layout** : la config par défaut embarquée doit reproduire exactement le comportement du Lot 1 — couvrir par des tests de non-régression avant de supprimer le code statique.
