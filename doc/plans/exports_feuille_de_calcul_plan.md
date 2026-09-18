# Implementation Plan: Exports feuille de calcul (xlsx)

**Related design note:** `doc/design_notes/exports_feuille_de_calcul_design.md`
**Status:** Phase 0 terminée (branche `feature/exports-xlsx-phase-0`) — Phase 1 non démarrée
**Created:** 2026-09-18
**Last Updated:** 2026-09-18 (Phase 0 implémentée et testée)
**Complexity:** Moyenne à élevée (dépend fortement de la phase)

---

## Résumé

Remplace le duo actuel CSV (mal étiqueté « Excel ») + PDF par un vrai export feuille de calcul (.xlsx). Le mécanisme générique existant (`csv_table()`/`pdf_table()` de `application/libraries/MetaData.php`) couvre les listes simples, mais **la vraie justification du chantier est le soin apporté aux rapports financiers structurés** (bilan, résultat, balance, journal, trésorerie), qui ne passent pas par ce mécanisme et demandent un travail sur mesure. Ce sont donc eux qui sont traités en priorité, avant l'extension de couverture aux listes qui n'ont jamais eu d'export.

---

## Phase 0 — Fondations (renommage + librairie + méthode générique) ✅ Terminée (2026-09-18)

**Objectif** : poser l'infrastructure technique commune, nécessaire à toutes les phases suivantes.

1. ✅ Librairie vendorisée : `shuchkin/simplexlsxgen` v1.5.17 (MIT, mono-fichier, seule dépendance = extension `zlib` déjà utilisée par le projet) dans `application/third_party/simplexlsxgen/` (voir `VENDORED.md` pour la provenance).
2. ✅ Méthode générique ajoutée : `GVVMetadata::xlsx_table($table, $data, $attrs)` dans `application/libraries/MetaData.php`, calquée sur `csv_table()`/`pdf_table()`. Séparée en deux : `xlsx_workbook()` construit le classeur (réutilisable par les rapports sur mesure des phases 1/4, testable sans en-têtes HTTP) et `xlsx_table()` l'envoie au navigateur.
3. ✅ Mode `"xlsx"` ajouté dans `array_field()` : valeurs typées (float pour currency/decimal, int pour boolean/checkbox, dates ISO brutes auto-détectées par la librairie, images/couleurs non représentables renvoyées vides).
4. ✅ Renommage : 49 occurrences du libellé `Excel` → `CSV` dans 41 vues (`button_bar4()`, `button_bar2()`, `button_bar()`).
5. ✅ Test PHPUnit : `application/tests/integration/XlsxTableExportTest.php` (4 tests — typage currency/boolean, valeur vide, validité du classeur produit).

**Effet de bord corrigé** : `compta::export_journal()` comparait `$_POST['button'] == 'Excel'` (soumis via `button_bar2()`, où le libellé affiché est aussi la valeur POSTée) — cassé par le renommage, corrigé en `'CSV'`. Repéré par un test manuel dans le navigateur, pas par la suite automatisée (aucun test ne couvrait ce chemin) ; à garder en tête si d'autres `button_bar2()`/`button_bar()` sont retouchés plus tard.

**Bug latent repéré, hors périmètre** : `comptes/resultatCategorie.php` appelle `button_bar()`, une fonction qui n'existe nulle part dans le code — page cassée (fatal error) si elle est visitée, indépendamment de ce chantier. Non corrigé ici (non demandé).

**Sortie de phase** : mécanisme générique + librairie prêts, réutilisables aussi bien par les rapports financiers (phase 1) que par les listes simples (phase 2). Suite complète `./run-all-tests.sh` verte (2021 tests, 0 échec) + vérification manuelle dans le navigateur sur `avion` (rendu du bouton renommé) et `compta/page` (export CSV du journal toujours fonctionnel après correction).

## Phase 1 — Rapports financiers/comptables structurés (priorité maximale)

**Objectif** : la vraie raison d'être du chantier. Ces pages construisent aujourd'hui leur CSV/PDF à la main (ex. `comptes::bilan_csv()`, ~150 lignes de composition manuelle) — pas de passage par `csv_table()`/`pdf_table()`. L'export xlsx demande donc un code de mise en page dédié par rapport, réutilisant la librairie de la phase 0.

Pages concernées, dans l'ordre : `comptes::bilan` puis `comptes::resultat` (valeur métier la plus haute — trésoriers/comptables), puis `resultat_avec_depreciation`, `resultat_par_sections` (+ détail), `balance`, `compta::journal` (+ journal par compte), `tresorerie`, `resultatCategorie`.

Exigences de qualité (le "soin" attendu, au-delà du simple fonctionnel) :
1. **Vraies valeurs numériques**, pas de texte formaté — un montant doit rester une cellule de type nombre pour permettre sommes/formules côté tableur, objectif même du chantier.
2. **Mise en forme par style de cellule** (gras, bordures, format monétaire natif) plutôt que par convention textuelle comme en CSV.
3. **En-têtes de colonnes figés** (freeze pane) sur les tableaux longs.
4. **Une feuille par axe de comparaison** quand le rapport en a un (ex. bilan N vs N-1 : soit deux feuilles, soit deux blocs de colonnes clairement séparés — à trancher par rapport, pas de règle unique).
5. Titre de classeur et de feuille cohérents avec le titre du rapport affiché à l'écran.

Chaque rapport est une unité de livraison indépendante (pas de dépendance entre eux) — possibilité de livrer `bilan` seul dans une première PR, puis les suivants.

**Test** : un test PHPUnit par rapport migré, vérifiant que le classeur produit est valide et que les cellules de montant sont numériques (pas de chaîne). Smoke Playwright sur `bilan` au minimum (téléchargement + content-type xlsx).

## Phase 2 — Rollout sur les listes simples déjà en CSV+PDF (19 pages)

**Objectif** : gain rapide à faible coût — pages qui utilisent déjà `csv_table()`/`pdf_table()` via leur méthode `export($mode)`, une fois la phase 0 posée.

Pages concernées (section 1 de la note de design, sous-ensemble "Liste simple") : `associations_of`, `avion`, `comptes` (vue simple), `configuration`, `events_types`, `forms_admin/bs_submissions`, `membre` (liste + licences), `plan_comptable`, `planeur`, `sections`, `terrains`, `tickets`, `types_ticket`, `vols_avion`, `vols_decouverte`, `vols_planeur`, `carnets_route`, `relances`.

Pour chacune :
1. Ajouter la branche `elseif ($mode === 'xlsx')` dans la méthode `export()` du contrôleur.
2. Ajouter l'entrée `'label' => "Xlsx"` dans le `button_bar4()` de la vue.

Mécanique et répétitive — candidate à un traitement par lot plutôt qu'à une revue ligne à ligne de chaque page.

**Test** : étendre le test Playwright existant qui vérifie la présence des boutons d'export (`playwright/tests/rapprochements-export.spec.js` sert de modèle) à au moins une page de cette phase.

## Phase 3 — Corriger les exports incomplets/cassés (3 pages)

Faible volume, indépendant du reste, peut être fait à tout moment après la phase 0 :

1. `paiements_en_ligne/bs_liste.php` : ajouter l'export PDF manquant. **Attention** : ce contrôleur est explicitement exempté de `gvvmetadata` (AI_INSTRUCTIONS.md) — ne pas y introduire `array_field`/`table()`, construire l'export directement.
2. `formation_rapports/conformite.php` : ajouter l'export PDF manquant sur `export_conformite_csv`.
3. `authorization/migration/comparison_log.php` (et `statistics.php`, tableau de bord associé) : le bouton "Exporter CSV" appelle `alert('non implémenté')` — soit l'implémenter, soit le retirer si l'export n'est pas jugé utile sur ces pages de migration/diagnostic. **Ce point est un bug indépendant du chantier xlsx** ; à traiter séparément ou à valider explicitement avec l'utilisateur avant d'y toucher dans ce lot.

## Phase 4 — Autres rapports structurés non financiers

Même nature de travail que la phase 1 (code de mise en page dédié), mais sur des rapports jugés moins prioritaires : `rapports/bs_dgac`, statistiques de vol (`vols_planeur`/`vols_avion` — stats, cumuls, par pilote, pages multi-onglets), `adherents_report`, `formation_rapports/index`, `maintenance_synthese/tableau`.

À ne démarrer qu'après la phase 1, sur confirmation que la valeur en justifie l'effort (ces rapports sont plus consultés à titre informatif qu'à des fins de calcul).

## Backlog optionnel — Listes simples sans aucun export

**Déclassé, pas une phase active de ce chantier.** Les 62 listes de la section 3 de la note de design n'ont jamais eu d'export CSV/PDF en douze ans d'existence du projet ; l'absence de demande signalée indique une valeur faible. À ne traiter qu'à la demande explicite, page par page, si un besoin réel émerge — pas en campagne systématique. Si repris un jour, suivre le patron de la phase 2 (CSV + PDF + Xlsx dès la création, méthode `export()` + `button_bar4()`), en commençant par `maintenance_*`, `formation_*` et `authorization/*` qui avaient été identifiés comme les plus susceptibles d'avoir un usage réel.

---

## Tests

- **PHPUnit** : test unitaire sur `xlsx_table()` (phase 0), conservé dans la suite. Un test par rapport financier migré (phase 1) vérifiant que le classeur produit est valide et que les valeurs numériques sont bien typées.
- **Playwright** : étendre le smoke test d'export existant (modèle : `rapprochements-export.spec.js`) — priorité sur `bilan`/`resultat` (phase 1), puis au moins une page de la phase 2.
- Avant de déclarer une phase terminée : `./run-all-tests.sh` + smoke Playwright sur les pages modifiées, conformément à la politique de complétion du projet (AI_INSTRUCTIONS.md point 22).

## Risques

- Librairie xlsx non éprouvée dans ce projet : prévoir un test de charge sur l'export le plus volumineux existant (vols_planeur, ~10 000 lignes, phase 4) avant de généraliser.
- Rapports financiers (phase 1) : risque de sous-estimation, chaque rapport a sa propre mise en page manuelle (bilan hiérarchique notamment) — traiter `bilan` seul d'abord et réévaluer l'effort des suivants une fois ce premier livré.
- `paiements_en_ligne` : ne pas introduire de dépendance à `gvvmetadata` par erreur en copiant le patron des autres pages (cf. exemption explicite AI_INSTRUCTIONS.md).
