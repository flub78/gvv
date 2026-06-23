# Analyse de la Planification du Projet GVV

*Générée le 2026-06-23 — Analyse de 2201 commits entre juin 2024 et juin 2026*

## Méthodologie

L'analyse compare les dates de premier apparition des éléments dans `doc/todo.md` avec les dates de commit correspondants. La correspondance est calculée par similarité Jaccard sur les mots-clés. Un commit est considéré :

- **Planifié long terme** : l'item était dans todo.md plus de 3 mois avant le commit
- **Planifié moyen terme** : l'item était dans todo.md entre 1 et 3 mois avant le commit
- **Planifié court terme / émergent** : l'item est apparu dans todo.md moins d'un mois avant le commit, ou n'a pas de correspondance identifiable

La classification par type (feature/bug fix/refactoring) est basée sur des mots-clés dans les messages de commit.

**Données sources** :
- 192 versions de `doc/todo.md` analysées
- 301 items uniques extraits de l'historique todo.md
- 2201 commits analysés

---

## Vue d'ensemble

| Métrique | Valeur |
|----------|--------|
| Total commits | 2201 |
| Features | 1215 (55.2%) |
| Bug fixes | 429 (19.5%) |
| Refactoring/docs | 557 (25.3%) |
| Planifiés long terme (>3 mois) | 661 (30.0%) |
| Planifiés moyen terme (1-3 mois) | 200 (9.1%) |
| Planifiés court terme (<1 mois) | 210 (9.5%) |
| Non planifiés / émergents | 1130 (51.3%) |

---

## Répartition par catégorie

| Catégorie | Nb commits | % |
|-----------|-----------|---|
| Feature planifiée court terme (<1 mois) ou émergente | 807 | 36.7% |
| Feature planifiée long terme (>3 mois) | 326 | 14.8% |
| Refactoring planifié court terme (<1 mois) ou émergent | 284 | 12.9% |
| Bug fix planifié court terme (<1 mois) ou non planifié | 249 | 11.3% |
| Refactoring planifié long terme (>3 mois) | 195 | 8.9% |
| Bug fix planifié long terme (>3 mois) | 140 | 6.4% |
| Feature planifiée moyen terme (1-3 mois) | 82 | 3.7% |
| Refactoring planifié moyen terme (1-3 mois) | 78 | 3.5% |
| Bug fix planifié moyen terme (1-3 mois) | 40 | 1.8% |

---

## Analyse par type

### Features (1215 commits, 55.2%)

| Horizon | Nb | % des features |
|---------|-----|----------------|
| Long terme (>3 mois) | 326 | 26.8% |
| Moyen terme (1-3 mois) | 82 | 6.7% |
| Court terme / émergent | 807 | 66.4% |

### Bug Fixes (429 commits, 19.5%)

| Horizon | Nb | % des bug fixes |
|---------|-----|-----------------|
| Long terme (>3 mois) | 140 | 32.6% |
| Moyen terme (1-3 mois) | 40 | 9.3% |
| Court terme / non planifié | 249 | 58.0% |

### Refactoring & Documentation (557 commits, 25.3%)

| Horizon | Nb | % du refactoring |
|---------|-----|------------------|
| Long terme (>3 mois) | 195 | 35.0% |
| Moyen terme (1-3 mois) | 78 | 14.0% |
| Court terme / émergent | 284 | 51.0% |

---

## Interprétation

### Taux de planification global

Sur 2201 commits, **861 (39.1%)**
correspondent à des éléments qui étaient dans la todo list depuis au moins un mois.
Ce taux représente le travail "anticipé" vs le travail "réactif/émergent".

### Caractéristiques notables

- **Bug fixes** : par nature peu planifiables, la plupart (58.0%)
  sont des réponses à des problèmes découverts en cours de développement ou de production
- **Features** : 33.6%
  des features avaient une entrée dans la todo list depuis plus d'un mois
- **Refactoring** : 49.0%
  était planifié à au moins moyen terme, reflétant une gestion active de la dette technique

### Limites de la méthode

- La correspondance commit↔todo est imparfaite (similarité textuelle) ; les commits courts ou techniques
  peuvent être mal classés
- La todo list n'était pas maintenue depuis le début du projet : les commits avant décembre 2024
  n'ont pas de historique todo correspondant
- Certains commits concernent des sous-tâches d'une feature qui apparaît dans todo.md sous une formulation différente
- Le seuil de similarité (Jaccard > 0.15) peut manquer des correspondances réelles

---

*Analyse générée automatiquement par `tmp/analyze_planning.py`*
