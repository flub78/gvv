# Analyse des types d'écritures comptables

**Date :** 2026-04-21, actualisée le 2026-09-14  
**Périmètre :** Base de données `gvv2`, table `ecritures` (toutes sections, tous exercices)

> Mise à jour 2026-09-14 : les comptages ont été rejoués sur la base `gvv2` actuelle
> (requête `compte1`/`compte2` → `comptes.codec`, mêmes exclusions et mêmes règles de
> couverture que l'analyse d'origine — vérifiées à nouveau une à une contre les méthodes
> `ecriture()` de `compta.php`). Les catégories et la méthode restent valables ; les volumes
> ont évolué avec 5 mois d'usage réel supplémentaire. Deux écarts notables sont apparus
> (voir Catégorie 1) et une lecture par nombre de comptes distincts a été ajoutée pour
> distinguer une vraie opération récurrente d'un simple transfert ponctuel répété entre
> deux comptes fixes.

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

| Débit | Crédit | Nb (2026-09) | Nb (2026-04) | Comptes distincts (débit×crédit) | Libellé |
|-------|--------|----|----|----|---------|
| **411** | **411** | 53 | 51 | 21 × 35 | Transfert entre comptes membres |
| **467** | **411** | **51** | 10 | 4 × 32 | Section → membre (dont 44 depuis le compte **HelloAsso**) |
| **706** | **411** | 21 | 21 | — × 18 | Recette vol → membre (remboursement ?) |
| **74** | **411** | 15 | 15 | — | Subvention → membre |
| **411** | **606** | 14 | 13 | 7 × 2 | Membre → achats (remboursement de charges ?) |
| **441** | **411** | 6 | 6 | — | Subvention à recevoir → membre |

> **Observation** : `411 → 411` (53 entrées, réparties sur 21 comptes débiteurs et 35
> comptes créditeurs) représente des transferts entre comptes membres. Un formulaire
> guidé "virement entre membres" permettrait de saisir ces opérations avec moins de
> risque d'erreur.
>
> **Écart notable depuis l'analyse d'avril** : `467 → 411` est passé de 10 à 51 entrées.
> 44 de ces 51 écritures partent du compte **467 "HelloAsso"** vers 32 comptes membres
> distincts : il s'agit du deuxième mouvement du workflow de paiement en ligne
> (l'encaissement HelloAsso arrive d'abord sur le compte de passage 467 via
> `encaissement_pour_une_section()`, puis doit être réparti vers le compte du membre
> concerné). Ce n'est pas une catégorie fourre-tout mais un besoin métier précis et
> récurrent, qui justifie désormais un formulaire guidé dédié.
>
> `706 → 411` (21 entrées, 18 comptes membres distincts) reste un remboursement ponctuel
> mais réel, réparti sur suffisamment de membres différents pour être un candidat valable.
>
> `411 → 606` (14 entrées, 7 comptes membres × 2 comptes de charge) : volume modeste mais
> réparti sur plusieurs membres, cohérent avec un remboursement de charges avancées.
>
> Les autres types restent trop rares ou trop spécialisés pour justifier un formulaire dédié.

---

### Catégorie 2 — Immobilisations

| Débit | Crédit | Nb (2026-09) | Nb (2026-04) | Libellé |
|-------|--------|----|----|---------|
| **215** | **512** | 11 | 11 | Achat installation par banque |
| **215** | **781** | 5 | — | Cession d'immobilisation (produit de cession) |
| **68** | **215** | 5 | 5 | Amortissement sur immobilisation spécifique |
| **512** | **215** | 3 | — | Remboursement / annulation d'achat d'immobilisation |

> **Observation** : Sur l'ensemble de la catégorie (22 écritures, réparties sur 10 comptes
> débiteurs et 7 comptes créditeurs distincts), le volume reste faible mais régulier.
> La saisie libre `compta` suffit dans la pratique. Un formulaire guidé "acquisition /
> cession d'immobilisation" (2xx ↔ 512/781) pourrait éviter des erreurs de compte mais
> ne s'impose pas compte tenu du volume.

---

### Catégorie 3 — Opérations diverses (rares ou ponctuelles)

| Débit | Crédit | Nb (2026-09) | Nb (2026-04) | Comptes distincts | Libellé |
|-------|--------|----|----|----|---------|
| **46** | **46** | **45** | — (non analysé) | **1 × 1** | Transfert ponctuel "Aide COVID" → "ALBERT Concours" |
| 467 | 706/707 | 22 | 5 | 7 × 4 | Section → recette vol |
| 625 | 758 | 21 | 21 | 1 × 1 | Déplacements ↔ produits divers (compensation) |
| 616 | 616 | 13 | 12 | 1 × 1 | Même compte (écriture de correction) |
| 512 | 615/616 | 14 | 14 | 2 × 5 | Banque → charges (direction inhabituelle) |
| 708 | 512 | 7 | 8 | 2 × 2 | Recette directe → banque (sans compte membre) |
| 512 | 451 | 0 | 7 | — | Banque → compte groupe *(disparu depuis avril)* |
| 451 | 467 | 0 | 5 | — | Groupe → section *(disparu depuis avril)* |

> **Découverte** : `46 → 46` (45 entrées) n'avait pas été repérée en avril (probablement
> apparue depuis, ou sous le seuil de l'analyse d'origine). Vérification faite, ce n'est
> **pas** un type d'opération récurrent comme `411 → 411` : les 45 écritures relient
> toujours les deux **mêmes** comptes ("Aide COVID" → "ALBERT Concours"), un seul compte
> débiteur et un seul compte créditeur. C'est un transfert ponctuel (probablement une
> réaffectation de subvention COVID vers un compte concours, saisie ligne à ligne), pas
> un besoin général de "virement entre comptes divers". **Ne justifie pas** de formulaire
> guidé — la logique serait identique à un formulaire "virement entre comptes membres"
> mais pour un usage qui ne s'est produit qu'une fois.
>
> `625 → 758` (21) et `616 → 616` (13) sont dans le même cas : toujours la même paire de
> comptes, donc une correction récurrente propre à un club plutôt qu'un type d'écriture
> général. Le formulaire de saisie libre suffit — c'est un usage normal une fois identifié.
>
> `467 → 706/707` (22, répartie sur 7 comptes 467 et 4 comptes 706/707) a grossi depuis
> avril et concerne plusieurs sections ; sa sémantique comptable exacte (recette de vol
> enregistrée via le compte de section) mérite d'être confirmée avec un trésorier avant
> d'envisager un formulaire dédié.
>
> `512 → 451` et `451 → 467` ont disparu (0 écriture en base actuellement) : les
> écritures d'avril ont dû être corrigées ou reclassées depuis.

---

## Synthèse et recommandations (mise à jour 2026-09-14)

Le critère de sélection retenu n'est plus seulement le volume, mais aussi la **largeur**
(nombre de comptes distincts impliqués) : un pattern répété entre toujours les deux mêmes
comptes (46→46, 625→758, 616→616) est un usage ponctuel propre à un club, pas un type
d'écriture général — il ne justifie pas de formulaire dédié même à volume élevé.

| Priorité | Écriture guidée à ajouter | Débit → Crédit | Volume | Largeur (comptes distincts) |
|----------|--------------------------|----------------|--------|------------------------------|
| 🔴 Haute | **Répartition HelloAsso → membre** | 467 (HelloAsso) → 411 | 44 écritures | 4 × 32 |
| 🟠 Moyenne | **Virement entre membres** | 411 → 411 | 53 écritures | 21 × 35 |
| 🟡 Faible | **Remboursement recette vol à un membre** | 706 → 411 | 21 écritures | — × 18 |
| 🟡 Faible | **Remboursement de charges par un membre** | 411 → 606 | 14 écritures | 7 × 2 |
| 🟡 Faible | **Acquisition / cession d'immobilisation** | 2xx ↔ 512/781 | 22 écritures | 10 × 7 |
| — | Autres types non couverts (paires fixes, un club) | — | jusqu'à 45 chacune | 1 × 1, saisie libre suffit |

### Notes de conception

**Répartition HelloAsso → membre** (`467 → 411`) : Formulaire avec sélecteur de compte
467 filtré sur les comptes de passage HelloAsso en débit, et sélecteur de compte pilote
(411) en crédit. C'est la suite logique de `encaissement_pour_une_section()` (512 → 467) :
une fois l'encaissement HelloAsso posé sur le compte de passage de la section, cette
écriture permet de le répartir vers le membre concerné. Priorité haute car directement
lié à l'usage croissant du module de paiement en ligne.

**Virement entre membres** (`411 → 411`) : Formulaire avec deux sélecteurs de comptes
pilotes — le compte débiteur et le compte créditeur. Utile pour les transferts de solde
entre membres d'une même famille ou lors de changement de compte.

**Remboursement recette vol à un membre** (`706 → 411`) : Formulaire avec sélecteur de
compte de recette (7xx) en débit et compte pilote (411) en crédit. Correspond à
l'annulation ou au remboursement partiel d'une facturation de vol.

**Remboursement de charges par un membre** (`411 → 606`) : Formulaire avec compte pilote
(411) en débit et compte d'achat (606) en crédit. Utile quand un membre rembourse une
dépense avancée par le club.

**Acquisition / cession d'immobilisation** (`2xx ↔ 512/781`) : Formulaire avec sélecteur
de compte d'immobilisation (classe 2) d'un côté et compte bancaire (512) ou compte de
cession (781) de l'autre, sens configurable. Volume faible, à envisager seulement si des
erreurs de saisie sont constatées en pratique.

### Écartés (paires fixes, usage ponctuel d'un club)

`46 → 46`, `625 → 758`, `616 → 616` : volume parfois élevé (jusqu'à 45 écritures) mais
toujours entre les deux **mêmes** comptes. Un formulaire guidé n'apporterait rien de plus
que la saisie libre déjà utilisée pour ces corrections ou réaffectations propres à un
club particulier.

`467 → 706/707` (Section → recette vol, 22 écritures, 7 × 4 comptes) : pattern réel et en
croissance, mais sa sémantique comptable doit être confirmée avec un trésorier avant de
concevoir un formulaire — non retenu dans cette itération.
