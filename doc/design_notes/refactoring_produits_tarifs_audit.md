# Audit des données — refactoring `tarifs` → `produits` + `tarifs`

> Étape 2 du plan [`doc/plans/refactoring_produits_tarifs_plan.md`](../plans/refactoring_produits_tarifs_plan.md).
> Requête exécutée directement sur `gvv2` (base de test), aucun script conservé.

## Méthode

Pour chaque groupe `(reference, club)` de la table `tarifs`, comparaison des valeurs de
`description`, `compte`, `is_cotisation`, `nb_personnes_max`, `public`, `type_ticket` entre
toutes les lignes du groupe (`COUNT(DISTINCT ...)` par colonne).

## Périmètre couvert (tous les clubs/sections utilisant GVV sur cette base)

| club | section | nb lignes tarifs | nb références |
|---|---|---|---|
| 1 | Planeur | 148 | 78 |
| 2 | ULM | 13 | 10 |
| 3 | Avion | 8 | 8 |
| 4 | Général | 7 | 6 |

Seul le club **1 (Planeur)** contient des groupes divergents. Les clubs 2, 3 et 4 sont propres
(aucune divergence détectée).

## Groupes divergents (club 1 — Planeur)

| Référence | Colonne divergente | Anciennes valeurs | Valeur retenue (ligne la plus récente) | Explication |
|---|---|---|---|---|
| Déjeuner | `description` | "Repas de midi organisé par le club" / "Stage Troyes" (2 lignes ponctuelles) | "Repas de midi organisé par le club" (ligne du 2026-07-19) | Description alternative saisie pour des tarifs de stage ponctuels ; la ligne courante est revenue à la description standard. |
| Diner | `description` | idem Déjeuner | "Repas du soir organisé par le club" (ligne du 2026-07-19) | Même cas que Déjeuner. |
| Heure de vol Dynamic | `compte` | 55 (ligne 2010) puis 169 (depuis 2012) | 169 | Compte générique 55 utilisé à la création, corrigé dès 2012-11-05 vers un compte dédié. Stable depuis. |
| Nuitée Troyes | `description` | "" (vide, ligne 2014) puis "Nuitée Troyes" (depuis 2016) | "Nuitée Troyes" | Description non renseignée à la création, complétée ensuite. |
| Remorqué 100m | `compte` | 55 (2010) puis 168 (depuis 2012) | 168 | Même schéma que "Heure de vol Dynamic". |
| Remorqué 300m | `compte` | 55 (2010) puis 168 (depuis 2012, 3 lignes) | 168 | Idem. |
| Remorqué 500m | `compte` | 55 (2010) puis 168 (depuis 2012, 5 lignes) | 168 | Idem. |
| Treuillé | `compte` | 55 (2010) puis 168 (depuis 2012) | 168 | Idem. |
| Toutes | `is_cotisation`, `nb_personnes_max`, `public`, `type_ticket` | — | — | Aucune divergence sur ces colonnes. |

**Lecture** : la divergence dominante (6 des 8 références) est un compte générique `55` utilisé
à la mise en service en 2010, corrigé courant 2012 vers un compte dédié par référence. La règle
« la ligne avec la `date` la plus récente fait foi » sélectionne systématiquement la valeur
corrigée — comportement attendu.

## Anomalie annexe (non bloquante)

`Diner` / club 1 a deux lignes avec la **même date** `2023-07-27` (`id` 106 et 125). Les deux
lignes ont des valeurs de colonnes produit identiques (`description`="Stage Troyes",
`compte`=107) — l'ambiguïté sur laquelle des deux devient "la plus récente" n'a donc aucun
effet sur le résultat de l'agrégation. Pré-existant, non introduit par le refactoring ; signalé
pour information, aucune action requise.

Une inconsistance mineure `type_ticket` = `0` (lignes anciennes) vs `NULL` (lignes récentes) est
présente sur `Déjeuner`/`Diner`, sans effet fonctionnel : aucune des deux valeurs ne correspond
à un type de ticket réel pour ces références (repas), et `COUNT(DISTINCT)` ne la signale pas
comme divergence car `NULL` est ignoré par cet agrégat.

## Conclusion

Aucune divergence anormale. La règle de résolution « ligne à la `date` la plus récente fait
foi » (validée en §6 du plan) produit dans tous les cas la valeur correcte et attendue. **Aucune
correction manuelle des données sources n'est nécessaire avant la migration 146.**
