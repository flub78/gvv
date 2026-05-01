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

## Design technique

### Bibliothèque PDF : TCPDF

TCPDF (déjà dans `application/third_party/tcpdf/`) est utilisé pour les cartes plutôt que `Pdf.php` (tFPDF), car il permet le placement absolu en mm d'images et de texte sur page blanche — même pattern que `vols_decouverte::generate_pdf()`.

### Format des fonds de carte

- Dimensions : **85,6 × 54 mm** (ISO ID-1, format carte bancaire) à 300 dpi → **1011 × 638 px**
- Formats acceptés : JPEG ou PNG
- Stockage : clés `carte_recto_{annee}` et `carte_verso_{annee}` dans la table `configuration`, fichiers dans `uploads/configuration/`
- Upload via le mécanisme existant du contrôleur `configuration` (CI `upload` library)
- Fallback fond absent : carte avec fond blanc + bordure 1 px, génération autorisée

### Gabarit Avery C32016-10

10 cartes par A4, 2 colonnes × 5 lignes :

```
Marges (mm) : haut = 13,0   gauche = 7,2
Carte        : 85,6 × 54 mm
Gouttière H  : 2,5 mm        Gouttière V : 0 mm

Col 0 : X = 7,2     Col 1 : X = 95,3
Lig 0 : Y = 13,0    Lig 1 : Y = 67,0    Lig 2 : Y = 121,0
Lig 3 : Y = 175,0   Lig 4 : Y = 229,0
```

### Architecture

```
Controller : cartes_membre
├── index()               → liste membres (admin)
├── carte($mlogin, $year) → PDF individuel
├── lot()                 → écran sélection lot (admin)
├── lot_pdf()             → PDF planches recto/verso (admin)
└── config()              → upload fonds par année (admin)

Model : cartes_membre_model
├── get_membre($mlogin)
├── get_years_with_cotisation($mlogin)  ← table cotisation_produits
├── get_membres_actifs_annee($year)     ← membres WHERE actif = 1
└── get_president()                     ← WHERE (mniveaux & 2) != 0 AND actif = 1

Library : Cartes_membre_pdf extends TCPDF
├── render_card($data, $x, $y)         ← position absolue sur la page
├── render_recto_page(array $cards)    ← 1 page A4, jusqu'à 10 cartes
└── render_verso_page(array $cards)    ← ordre miroir horizontal
```

La bibliothèque est instanciée depuis les actions `carte()` et `lot_pdf()` du contrôleur ; elle ne connaît que la mise en page, pas les règles métier.

### Composition d'une carte

**Recto** — positions relatives au coin supérieur gauche de la carte (en mm) :

| Zone | Position | Dimensions |
|------|----------|------------|
| Fond recto | (0, 0) | 85,6 × 54 (pleine carte) |
| Nom association | (3, 3) | texte, police 7 pt bold |
| Année validité | (55, 3) | texte, police 7 pt, aligné droite |
| Photo membre | (62, 14) | 20 × 25 mm — absente : espace vide |
| Nom + Prénom | (3, 28) | texte, police 9 pt bold |
| Numéro membre | (3, 36) | texte `mnumero`, police 7 pt |
| Bordure (fond absent) | (0, 0) | rect 85,6 × 54, trait 0,35 mm |

**Verso** — positions relatives :

| Zone | Position | Dimensions |
|------|----------|------------|
| Fond verso | (0, 0) | 85,6 × 54 |
| Nom président | (3, 36) | `mprenom mnom`, police 7 pt |
| Libellé « Le Président » | (3, 32) | texte, police 6 pt |
| Bordure (fond absent) | (0, 0) | rect 85,6 × 54 |

### Planche recto/verso (lot)

Pour N membres, par tranches de 10 :

```
Page 2k-1 : rectos des cartes [10k+1 .. 10k+10]   (ordre naturel)
Page 2k   : versos des cartes [10k+10 .. 10k+1]   (ordre miroir horizontal)
```

Le miroir garantit l'alignement physique lors de l'impression recto-verso par rapport au bord long.

### Migration — champ `mnumero`

Nouvelle migration `application/migrations/0NN_mnumero_membre.php` :
- Ajout colonne `mnumero INT UNSIGNED NULL` dans la table `membres`
- Peuplement initial optionnel (séquence à définir lors de l'implémentation)
- Mise à jour de `application/config/migration.php`
- Ajout de la métadonnée `mnumero` dans `Gvvmetadata.php`

---

## Stratégie de livraison

Priorité produit :
1. Planches A4 recto/verso (administration, campagne annuelle)
2. Carte individuelle (membre puis administrateur)

Approche : livraison incrémentale en 2 lots fonctionnels. Chaque étape se termine par une preuve exécutable. Aucun développement du lot 2 tant que le lot 1 n'est pas validé en bout en bout.

## Definition of Done globale

- EF3, EF4, EF5, EF6, EF7 (partie lot) couverts pour le lot 1.
- EF1, EF2, EF7 (partie individuelle) couverts pour le lot 2.
- PDF imprimables sur A4, alignement recto/verso cohérent.
- Tests automatisés ajoutés et passants dans la suite projet.
- Smoke Playwright disponible pour chaque parcours critique.

---

## Lot 1 (prioritaire) — Planches A4 recto/verso administrateur

### Étape 1 — Migration et sources de données

Implémentation :
1. Créer la migration `mnumero` dans la table `membres` et mettre à jour `config/migration.php`.
2. Ajouter la métadonnée `mnumero` dans `Gvvmetadata.php`.
3. Identifier et documenter les sources de données pour la génération : `membres` (mnom, mprenom, mnumero, photo, actif), `cotisation_produits` (années), `configuration` (fonds, nom_club), président via `mniveaux & PRESIDENT`.
4. Définir les règles de sélection du lot par défaut (membres avec `actif = 1` pour l'année N-1) et de surcharge manuelle.

Validation :
- Test PHPUnit : migration crée `mnumero`, rollback restaure l'état initial.
- Checklist champs EF5 complète et vérifiée.

### Étape 2 — Gestion des fonds recto/verso (admin)

Implémentation :
1. Ajouter sous-écran `cartes_membre/config` : upload recto et verso pour une année donnée.
2. Stocker via le mécanisme `configuration` existant (clés `carte_recto_{annee}` et `carte_verso_{annee}`).
3. Contrôle d'accès : réservé aux administrateurs (même garde que les autres écrans admin).

Validation :
- Test PHPUnit : persistance configuration saisonnière, refus non-admin.
- Test manuel : uploader recto + verso, recharger, vérifier fond affiché.

### Étape 3 — Moteur de composition carte (library Cartes_membre_pdf)

Implémentation :
1. Créer `application/libraries/Cartes_membre_pdf.php` étendant TCPDF.
2. Implémenter `render_card($data, $x, $y)` : fond si disponible sinon blanc + bordure, photo conditionnelle, textes positionnés.
3. Implémenter `render_recto_page()` et `render_verso_page()` (ordre miroir).
4. Recto et verso synchronisés sur la même clé d'ordre (`mnumero` ou `mlogin`).

Validation :
- Tests PHPUnit sur `render_card()` : photo présente, photo absente, fond absent (bordure).
- Vérification visuelle de 3 cartes échantillon (champs présents et positionnés).

### Étape 4 — Génération des planches A4 en lot

Implémentation :
1. Créer `cartes_membre_model` avec `get_membres_actifs_annee()` et `get_president()`.
2. Implémenter `cartes_membre/lot()` : sélection par défaut (actifs N-1), sélection manuelle, ajout d'un membre hors actif.
3. Implémenter `cartes_membre/lot_pdf()` : constitution des tranches de 10, génération des pages recto/verso alternées, sortie PDF.

Validation :
- Tests intégration : constitution du lot (défaut, manuel, ajout hors actif).
- Test non-régression ordre recto/verso : même séquence d'identifiants entre faces.
- Test performance : lot représentatif (50 membres) dans un délai acceptable.

### Étape 5 — UX admin et vérification d'impression

Implémentation :
1. Finaliser l'écran `lot()` : filtres, liste finale, récapitulatif avant génération (année, nombre de membres).
2. Messages d'erreur explicites (fond manquant mais génération autorisée, président introuvable → bloquant avec message).
3. Documenter la procédure d'impression recommandée (recto-verso bord long, papier cartonné).

Validation :
- Smoke Playwright : parcours complet admin lot jusqu'au téléchargement PDF.
- Recette manuelle avec impression réelle, contrôle alignement recto/verso.

### Gate de fin Lot 1

Lot 1 validé si :
- EF3, EF4, EF5, EF6, EF7 (partie lot) démontrables.
- Smoke Playwright passe.
- Test d'impression physique conforme.

---

## Lot 2 (second temps) — Cartes individuelles

### Étape 6 — Impression individuelle membre

Implémentation :
1. Ajouter entrée « Imprimer ma carte » dans l'espace membre.
2. Proposer par défaut la dernière année avec cotisation (`cotisation_produits`).
3. Permettre la sélection parmi les années cotisées uniquement.
4. Générer le PDF individuel A4 via le même `Cartes_membre_pdf` que le lot.

Validation :
- Tests autorisation : membre limité à sa propre carte.
- Tests fonctionnels : année par défaut + changement d'année autorisée.
- Smoke Playwright membre jusqu'au téléchargement PDF.

### Étape 7 — Impression individuelle administrateur

Implémentation :
1. Ajouter recherche/sélection de membre côté admin dans `cartes_membre/carte()`.
2. Autoriser la génération même sans cotisation payée.
3. Permettre le choix de l'année de carte (toutes années, pas seulement cotisées).
4. Réutiliser strictement `Cartes_membre_pdf`.

Validation :
- Tests intégration : génération admin avec et sans cotisation.
- Tests autorisation : réservé admin.
- Vérification manuelle de 2 cas réels (membre cotisant / non cotisant).

### Gate de fin Lot 2

Lot 2 validé si :
- EF1, EF2, EF7 (partie individuelle) démontrables.
- Parcours membre et admin individuels passent en smoke test.

---

## Plan de tests transverse

Pour chaque étape :
1. Tests PHPUnit unitaires (logique métier, composition, ordre recto/verso).
2. Tests PHPUnit intégration sur données réelles (modèle / contrôleur).
3. Tests E2E Playwright sur parcours critiques.
4. Validation manuelle d'impression à la fin des lots 1 et 2.

```bash
source setenv.sh
./run-all-tests.sh
cd playwright && npx playwright test --reporter=line
```

## Risques et parades

- **Décalage recto/verso** : verrouiller l'ordre carte dans le modèle, recette physique obligatoire au gate lot 1.
- **Président introuvable** : bloquer la génération avec message explicite si `(mniveaux & 2) = 0` sur tous les membres actifs.
- **Volume lot élevé** : génération en tranches de 10, mesurer le temps, alerter l'utilisateur si > 30 secondes.
- **Photo de mauvaise dimension** : recadrer/redimensionner à 20 × 25 mm dans TCPDF sans déformation (mode `fit`).
