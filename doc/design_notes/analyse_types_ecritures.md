# Analyse des types d'écritures comptables

**Date :** 2026-04-21  
**Périmètre :** Base de données `gvv2`, table `ecritures` (toutes sections, tous exercices)

---

## Objectif

Identifier tous les types d'écritures présents en base de données (paires codec débit / codec crédit),
vérifier lesquels sont couverts par une écriture guidée, et déterminer s'il est pertinent d'en ajouter.

Les écritures impliquant les comptes 102, 110, 119, 120, 129 (fonds propres, résultat, report à nouveau)
sont exclues de l'analyse : elles sont générées automatiquement lors de la clôture d'exercice.

---

## Écritures guidées existantes

### Via `compta.php` — méthodes `ecriture()` avec filtres de comptes

| Méthode | Débit | Crédit | Description |
|---------|-------|--------|-------------|
| `depenses()` | 6xx | 5xx | Règlement d'une charge |
| `recettes()` | 5xx | 7xx | Encaissement d'une recette |
| `factu_pilote()` | 411 | 7xx | Facturation d'un service à un membre |
| `credit_pilote()` | 6xx | 411 | Remboursement à un membre |
| `reglement_pilote()` | 5xx | 411 | Encaissement d'un paiement membre |
| `debit_pilote()` | 411 | 5xx | Avance à un membre |
| `avoir_fournisseur()` | 401 | 6xx | Avoir fournisseur |
| `utilisation_avoir_fournisseur()` | 6xx | 401 | Utilisation avoir fournisseur |
| `virement()` | 512 | 512 | Virement entre comptes bancaires |
| `depot_especes()` | 512 | 531 | Dépôt espèces en banque |
| `retrait_liquide()` | 531 | 512 | Retrait liquide |
| `amortissement()` | 68 | 281 | Dotation aux amortissements |
| `remb_capital()` | 164 | 512 | Remboursement capital emprunt |
| `mise_a_disposition_emprunt()` | 512 | 164 | Mise à disposition emprunt |
| `encaissement_pour_une_section()` | 512 | 467 | Encaissement pour section |
| `reversement_section()` | 467 | 512 | Reversement section |
| `saisie_cotisation()` | 411 → 7xx + 512 → 411 | — | Cotisation (double écriture) |

### Via `achats` — catalogue de tarifs

Le débit est toujours le compte membre (411), le crédit est le compte associé au tarif.

| Débit | Crédit | Produits typiques |
|-------|--------|-------------------|
| 411 | 706 | Heures de vol, remorqués, vols de découverte |
| 411 | 708 | Repas, T-shirts, cotisations, hangar |
| 411 | 753 | Licences FFVV |
| 411 | 756 | Cotisations club |
| 411 | 75 | Librairie aéro |
| 411 | 701 | Ventes de produits finis |

---

## Types d'écritures présents en base, non couverts par une écriture guidée

### Catégorie 1 — Opérations membres atypiques

| Débit | Crédit | Nb | Libellé |
|-------|--------|----|---------|
| **411** | **411** | 51 | Transfert entre comptes membres |
| **706** | **411** | 21 | Recette vol → membre (remboursement ?) |
| **74** | **411** | 15 | Subvention → membre |
| **411** | **606** | 13 | Membre → achats (remboursement de charges ?) |
| **467** | **411** | 10 | Section → membre |
| **441** | **411** | 6 | Subvention à recevoir → membre |

> **Observation** : `411 → 411` (51 entrées) représente des transferts entre comptes membres.
> Un formulaire guidé "virement entre membres" permettrait de saisir ces opérations
> avec moins de risque d'erreur.
> Les autres types sont trop rares ou trop spécialisés pour justifier un formulaire dédié.

---

### Catégorie 2 — Immobilisations

| Débit | Crédit | Nb | Libellé |
|-------|--------|----|---------|
| **215** | **512** | 11 | Achat installation par banque |
| **68** | **215** | 5 | Amortissement sur immobilisation spécifique |

> **Observation** : Volume faible. La saisie libre `compta` suffit dans la pratique.
> Un formulaire guidé "acquisition d'immobilisation" (2xx → 512) pourrait éviter
> des erreurs de compte mais ne s'impose pas compte tenu du volume.

---

### Catégorie 3 — Opérations diverses (rares)

| Débit | Crédit | Nb | Libellé |
|-------|--------|----|---------|
| 625 | 758 | 21 | Déplacements ↔ produits divers (compensation) |
| 512 | 615/616 | 14 | Banque → charges (direction inhabituelle) |
| 616 | 616 | 12 | Même compte (écriture de correction) |
| 708 | 512 | 8 | Recette directe → banque (sans compte membre) |
| 512 | 451 | 7 | Banque → compte groupe |
| 451 | 467 | 5 | Groupe → section |
| 467 | 706 | 5 | Section → recette vol |

> **Observation** : Ces écritures correspondent à des corrections ou situations
> exceptionnelles. Le formulaire de saisie libre suffit.

---

## Synthèse et recommandations

| Priorité | Écriture guidée à ajouter | Débit → Crédit | Volume justificatif |
|----------|--------------------------|----------------|---------------------|
| 🟠 Moyenne | **Virement entre membres** | 411 → 411 | 51 écritures |
| 🟡 Faible | **Acquisition d'immobilisation** | 2xx → 512 | 11 écritures |
| — | Autres types non couverts | — | < 25 chacune, saisie libre suffit |

### Notes de conception

**Virement entre membres** (`411 → 411`) : Formulaire avec deux sélecteurs de comptes
pilotes — le compte débiteur et le compte créditeur. Utile pour les transferts de solde
entre membres d'une même famille ou lors de changement de compte.

**Acquisition d'immobilisation** (`2xx → 512`) : Formulaire avec sélecteur de compte
d'immobilisation (classe 2) en débit et compte bancaire (512) en crédit. Volume faible,
à envisager seulement si des erreurs de saisie sont constatées en pratique.
