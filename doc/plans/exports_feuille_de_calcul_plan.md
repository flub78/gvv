# Implementation Plan: Exports feuille de calcul (xlsx)

**Related design note:** `doc/design_notes/exports_feuille_de_calcul_design.md`
**Status:** Phase 0 terminée — Phase 1 en cours, 5 rapports sur 8 (branche `feature/exports-xlsx-phase-0`)
**Created:** 2026-09-18
**Last Updated:** 2026-09-30 (`balance` terminée)
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

Pages concernées, dans l'ordre : `comptes::bilan` ✅ **terminé (2026-09-18)**, `comptes::resultat` ✅ **terminé (2026-09-18)**, `resultat_avec_depreciation` ✅ **terminé (2026-09-18)**, `resultat_par_sections` (+ détail) ✅ **terminé (2026-09-30)**, `balance` ✅ **terminée (2026-09-30)**, puis `compta::journal` (+ journal par compte), `tresorerie`, `resultatCategorie`.

Exigences de qualité (le "soin" attendu, au-delà du simple fonctionnel) :
1. **Vraies valeurs numériques**, pas de texte formaté — un montant doit rester une cellule de type nombre pour permettre sommes/formules côté tableur, objectif même du chantier.
2. **Mise en forme par style de cellule** (gras, bordures, format monétaire natif) plutôt que par convention textuelle comme en CSV.
3. **En-têtes de colonnes figés** (freeze pane) sur les tableaux longs.
4. **Une feuille par axe de comparaison** quand le rapport en a un (ex. bilan N vs N-1 : soit deux feuilles, soit deux blocs de colonnes clairement séparés — à trancher par rapport, pas de règle unique).
5. Titre de classeur et de feuille cohérents avec le titre du rapport affiché à l'écran.

Chaque rapport est une unité de livraison indépendante (pas de dépendance entre eux) — possibilité de livrer `bilan` seul dans une première PR, puis les suivants.

**Test** : les contrôleurs GVV ne sont instanciés nulle part directement en PHPUnit dans ce projet (aucun précédent trouvé) — le format établi pour tester un export au niveau contrôleur est Playwright (cycle HTTP complet). Chaque rapport migré reçoit donc un spec Playwright dédié (bouton visible, fichier téléchargé valide, au moins une cellule numérique réelle dans le XML de la feuille), pas un test PHPUnit contrôleur. `xlsx_table()`/`array_field('xlsx')` eux-mêmes restent couverts par `XlsxTableExportTest.php` (phase 0).

**Retour d'expérience `bilan`** :
- Construction des lignes extraite dans une méthode privée partagée (`bilan_report_rows($year)`, `['bold' => bool, 'fill' => primary|secondary|null, 'cells' => [...]]`) consommée à la fois par `bilan_csv()` (comportement vérifié **strictement identique** via diff octet-à-octet d'un export avant/après refactor — précaution nécessaire avant de toucher un export financier existant) et la nouvelle `bilan_xlsx()`. À reproduire pour les rapports suivants.
- Le gras/la couleur de fond ne s'appliquent qu'aux cellules texte (libellés, cellules vides) des lignes de titre/section/total, jamais aux cellules numériques passées en PHP natif (`is_int($cell)||is_float($cell)`) — c'est la seule voie garantie de rester typé. *Nuance découverte sur `resultat`* : SimpleXLSXGen type quand même en nombre une valeur **chaîne** passée à travers `<b>`/`<style>` si son contenu, une fois les balises retirées, correspond encore à un de ses motifs numériques (entier, décimal à ≤2 décimales, devise, date...) — mais c'est fragile et dépendant du format exact de la représentation texte (espaces, nombre de décimales, notation scientifique sur les grands nombres...). Continuer à ne **jamais** compter dessus pour les montants : ne passer que des `int`/`float` PHP natifs, jamais des chaînes numériques, même mises en forme "proprement".
- La librairie auto-détecte aussi les dates au format `jj/mm/aaaa` dans les libellés et les type nativement (constaté sur les en-têtes "31/12/2026" du bilan, devenues de vraies dates Excel sans code dédié) — bonus, pas un effort supplémentaire.
- Couleurs de fond reprises du PDF (`pagesBilan()` dans `Document.php`) à la demande explicite de l'utilisateur : `table-primary` Bootstrap (CFE2FF) pour en-têtes/totaux généraux, `table-secondary` (E2E3E5) pour sous-titres/sous-totaux, via `<style bgcolor="HEX">` — même contrainte que le gras (texte uniquement, jamais les montants). Sur une ligne de total, seul le libellé est donc coloré, pas les montants : le typage prime sur la fidélité visuelle à 100 % avec le PDF.
- **Piège découvert** : renommer un bouton `button_bar2()`/`button_bar()` (où le libellé affiché est aussi la valeur POST) sans grep le contrôleur pour un `$_POST['button'] == 'ancien_libellé'` casse silencieusement l'export ciblé. Après un renommage de ce type, greper aussi les tests Playwright pour le texte renommé — la phase 0 avait cassé 3 specs (`rapprochements-export`, `journal-compte-soldes-pagination`, `resultat_par_sections_detail_links`) non détectées avant de lancer la suite Playwright complète (seul PHPUnit + vérification manuelle au navigateur avaient été faits).

**Retour d'expérience `resultat`** :
- Structurellement différent de `bilan` : les lignes ne sont pas construites à la main dans le contrôleur mais par une méthode de **modèle** partagée avec la vue HTML et le PDF, `ecritures_model::resultat_table($resultat, $links, $tab, $decimal_sep, $target)`, qui appelle `euro($montant, $decimal_sep, $target)` pour chaque montant.
- Plutôt que dupliquer `resultat_table()` ou y ajouter un branchement xlsx, **`euro()` lui-même** (`application/helpers/validation_helper.php`) a reçu un cinquième mode : `euro($montant, $sep, 'xlsx')` renvoie directement un float natif au lieu d'une chaîne formatée. Résultat : `resultat_xlsx()` appelle `resultat_table(..., 'xlsx')` sans **aucune** modification de `resultat_table()` — tout rapport qui formate déjà ses montants via `euro()` gagnera le support xlsx de la même façon, pour le prix d'un seul point de modification central. Candidats identifiés pour ce même levier : `resultat_avec_depreciation_table()`, et vraisemblablement `balance`/`journal`/`tresorerie` — à vérifier à chaque rapport plutôt qu'à supposer.
- PDF de ce rapport (`pagesResultats()`) sans aucune couleur de fond (juste un en-tête en gras via le renderer de table générique `Pdf::table()`) — rien à reprendre côté couleurs ici ; seuls la ligne d'en-tête et les 2 dernières lignes (totaux, bénéfice/perte) sont mises en gras côté xlsx, sur le même principe libellé-seul que le bilan.
- Vérifié : sortie CSV strictement identique avant/après (comparaison par `curl` + jar de cookies, le MCP Playwright ayant momentanément perdu sa connexion — fonctionne aussi bien pour ce genre de vérification, à garder en tête si l'outil navigateur est indisponible).

**Retour d'expérience `resultat_avec_depreciation`** :
- Même levier que `resultat` (mode `xlsx` de `euro()`, aucune modification de `resultat_avec_depreciation_table()`), mais la mise en gras ne peut pas se faire par position : ce rapport a deux blocs de totaux/bénéfice-perte ("avant" et "après" dépréciations) séparés par un nombre variable de lignes de comptes 68x/78x. Repérage par **contenu** à la place : liste des libellés connus (`comptes_label_total_charges_hd`, `comptes_label_resultat_avant_dep`, etc.) obtenue une fois via `lang->line()`, puis `array_intersect($row, $bold_labels)` par ligne. Généralisable à tout rapport dont les totaux ne sont pas à une position fixe.
- `resultat_avec_depreciation_table()` retourne des lignes de données en **tableau creux** (clé PHP `6` absente, gardée `5`→séparateur puis `7..11`→colonnes produits) — un `foreach` classique les parcourt sans problème (11 valeurs quel que soit le jeu de clés), donc aucune précaution particulière à prendre au-delà de rester sur `foreach ($row as $cell)` plutôt que d'indexer par clé numérique attendue.

**Retour d'expérience `resultat_par_sections`** :
- Montants typés à la source : `format_numeric_columns()` (modèle `comptes`) a reçu un mode `'xlsx'` (float natif), et `select_resultat_par_sections_deux_annees()` un paramètre `$number_format` distinct de `$html` (qui ne pilote plus que les liens `<a>` sur le codec). Même levier que `euro()` pour `resultat`, mais côté modèle `comptes`.
- Premier rapport à trois tableaux : **une feuille par tableau** (Charges, Produits, Total) plutôt qu'une feuille unique, pour pouvoir figer l'en-tête à deux lignes (sections / années) et les colonnes Code/Libellé de chaque tableau. Noms de section fusionnés (`mergeCells`) au-dessus de leurs colonnes d'années ; couleur d'en-tête de section du PDF (`DAE3EC`) reprise sur les en-têtes uniquement.
- **Bug CSV existant corrigé** : le tableau des totaux gardait la colonne Code (vide) alors que son en-tête l'omet — les montants étaient décalés d'une colonne par rapport aux noms de section. Les lignes sont désormais tronquées comme dans le PDF (`array_slice($row, 1)`) ; seules ces 4 lignes du CSV changent (vérifié par diff), test de non-régression dans le spec xlsx du rapport.
- CSV non refactoré par ailleurs (identique avant/après hors ce correctif) : le xlsx réutilise directement `transform_to_two_line_header()`, sans méthode de lignes partagée — la construction est déjà factorisée là.

**Retour d'expérience `balance`** :
- La page n'exporte que la balance **hiérarchique** (`balance_hierarchical_csv/pdf` ; `balance_csv()`/`balance_pdf()` ne sont plus appelées par la vue) : seul ce format reçoit un xlsx, `balance_hierarchical_xlsx()`.
- Même patron que `bilan` : construction des lignes extraite dans `balance_hierarchical_rows()`, avec un champ `level` (general|detail|total) ; l'indentation par espaces des comptes détaillés est propre au CSV, le xlsx rend la hiérarchie par le gras (comptes généraux, totaux) et reprend le gris d'en-tête du PDF.
- `xlsx_workbook()` générique (phase 0) non utilisé : pas de mise en forme par ligne, et son nom de feuille dérivé du titre contiendrait ici la date (`/` interdit dans un nom de feuille Excel). Nom de feuille et de fichier fixes, le titre complet (classe, date, section) figure en ligne 1.
- **Vérification CSV** : une comparaison octet à octet échoue même **sans** modification — l'ordre des comptes détaillés de même codec varie d'un appel à l'autre (tri SQL non total). Comparaison faite sur les lignes triées (identiques), sur la balance complète et sur une plage de classes. Défaut d'ordre existant, non corrigé.

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
