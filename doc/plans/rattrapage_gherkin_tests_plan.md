# Plan de rattrapage — Scénarios Gherkin des tests existants

Date : 30 octobre 2026

## Objectif

Donner à chaque test existant une description Gherkin de son comportement, conformément à la règle « Behavior description » de [doc/AI_INSTRUCTIONS.md](../AI_INSTRUCTIONS.md). Le code des tests n'est pas modifié.

## État de départ

- 3 260 tests documentés dans [doc/testing/test_docs.html](../testing/test_docs.html), dont 10 avec scénario : **3 250 à rédiger**, ramenés à 3 238 après le retrait de 12 tests de débogage (étape 0.2).
- Les étapes font environ 100 tests, 120 au maximum, pour rester relisibles en une fois.
- Les nombres sont ceux du 30 octobre 2026. Ils servent à dimensionner les étapes : la référence vivante est la colonne « avec scénario » de la page. Les tests ajoutés depuis ont déjà leur scénario.

## Règles du rattrapage

- **Comportement, pas code** : la description reste valide si l'implémentation change. Elle ne contient ni classe, méthode, table, colonne, route, sélecteur ou valeur technique.
- **Relire le test, pas son nom** : le scénario décrit ce que le test vérifie vraiment. Un nom trompeur ne se recopie pas.
- **Feature** : un seul `@feature` par fichier, dans un commentaire attaché à aucun test. Un test d'une autre feature déclare la sienne.
- **Test dans une boucle** : un seul scénario décrit toute la famille.
- **Commentaires uniquement** : aucune ligne de code de test ne change pendant le rattrapage.
- **Test impossible à décrire** : s'il ne vérifie aucun comportement, ou si le scénario révèle un défaut, ne rien corriger et l'inscrire dans la section « Anomalies relevées ».

## Critères de fin d'une étape

Une case se coche quand :

1. tous les tests de l'étape ont un scénario, visible dans la page ;
2. le diff ne touche que des commentaires, et `php -l` passe sur les fichiers PHP modifiés ;
3. `php bin/gen_test_docs.php` a régénéré la page, commitée avec l'étape ;
4. l'étape fait l'objet d'un commit dédié.

## Phase 0 — Préparation

- [x] **0.1 Contrôle du vocabulaire** : le générateur signale les scénarios contenant des traces de code (appels de fonction, variables, noms techniques, sélecteurs, noms de fichiers).
- [x] **0.2 Tri des tests de débogage** : retrait de `DebugExampleTest`, `example`, `diagnostic-remote` (avec `playwright/diagnose-remote.sh`), `debug-balance-page` et `email-lists-create-debug`, soit 12 tests. `balance-search-debug` est conservé : malgré son nom, il vérifie le filtre de la balance.
- [x] **0.3 Pilote, relu et validé** (42 tests) : `AcceptanceItemsModelTest`. Ce lot fixe le ton et le niveau de détail attendus pour toutes les étapes suivantes.

## Phase 1 — Argent et vols

Priorité aux fonctions où une régression coûte le plus cher.

- [ ] **1.1 Paiements en ligne, cœur** (86) : `HelloassoLibraryTest`, `PaiementsEnLigneModelTest`, `PaiementsEnLigneWebhookTest`, `paiements-en-ligne-webhook`, `PaiementsEnLigneProvisionTest`, `PaiementGeneriqueTest`, `paiement-generique-smoke`, `SectionsHelloAssoFlagsRegressionTest`
- [ ] **1.2 Paiements en ligne, parcours** (79) : `PaiementsEnLignePublicVdTest`, `PaiementsEnLigneBarTest`, `PaiementsEnLigneBarExterneTest`, `PaiementsEnLigneCotisationPiloteTest`, `PaiementsEnLigneHistoriqueTest`, `PaiementsEnLigneListeTest`, `paiements-en-ligne-base`, `paiements-en-ligne-admin-config`, `paiements-en-ligne-ef1-demande`, `paiements-en-ligne-ef3-mon-compte`, `paiements-en-ligne-ef4-liste`, `paiements-en-ligne-uc2-bar-externe`, `paiements-en-ligne-uc3-cotisation-pilote`, `paiements-en-ligne-uc4-decouverte`
- [ ] **1.3 Facturation et tarifs** (78) : tous les fichiers de la feature, sauf `TarifsIsCotisationMigrationTest`
- [ ] **1.4 Cotisations, licences et relances** (67) : features « Cotisations et licences » et « Relances »
- [ ] **1.5 Comptabilité, écritures et journaux** (98) : `EcritureUpdateFreezeDateTest`, `EcritureAttachmentDeletionTest`, `compta_journal_serverside`, `grand-journal-compte-selector`, `EcrituresModelSoldeCompteGestionMySqlTest`, `ComptaExportAuthorizationTest`, `ComptaAttachmentsAuthorizationTest`, `ComptaEditAuthorizationTest`, `EcrituresBalanceTest`, `EcritureSelectorCompteMasqueTest`, `journal-compte-soldes-pagination`, `journal-compte-soldes-simple`, `ComptesModelSectionTest`, `compta-attachments-non-tresorier`, `compta_frozen_entry_buttons`, `compta_journal_search`, `compta_modification_date_gel`, `compte-411-pilote-required`, `migrated/compta`, `compta_inverser_comptes`, `compta-journal-xlsx-export`
- [ ] **1.6 Comptabilité, bilans et rapprochements** (98) : `BalanceHelperTest`, `CategorieModelMySqlTest`, `CategorieModelIntegrationTest`, `ReportsModelMySqlTest`, `SmartAdjustorCorrelationIntegrationTest`, `BalanceAccordionSearchTest`, `ComptesResultatPdfExportTest`, `rapprochements-*`, `comptes-*-xlsx-export`, `resultat-*`, `resultat_par_sections_detail_links`, `balance-search-debug`
- [ ] **1.7 Saisie des vols avion** (100) : `VolsAvionValidationTest`, `HorametreHelperTest`, `VolsAvionHorametreTest`, `VolsAvionCategoriesTest`, `vols-avion-horametre-widget`, `bugfix-vols-avion-form-validation`, `VolsAvionCreateAndContinueTest`, `VolsAvionSumRoundingTest`, `VolsAvionVolsPlaneurAuditMySqlTest`, `audit-fields-vols-avion-create`, `migrated/plane_flight`
- [ ] **1.8 Vols planeur et carnets de route** (61) : `migrated/glider-flights`, `migrated/planeur`, `VolsPlaneurCreateAndContinueTest`, `PreparationCardsModelTest`, feature « Carnets de route »
- [ ] **1.9 Vols de découverte et briefing passager** (99) : les deux features, sauf `VolsDecouverteMigrationTest` et `VolsDecouverteLooksMigrationTest`
- [ ] **1.10 Réservations** (111) : toute la feature

## Phase 2 — Membres et droits

- [ ] **2.1 Gestion des membres** (85) : `MembresFusionTest`, `membres-fusion-smoke`, `RenameMembreTest`, `membre-renommer-smoke`, `MembreMnumeroModelTest`, `MembresSelectorActifTest`, `MembresSelectorAllSectionsTest`, `membre-create-smoke`, `membre-checkboxes-smoke`, `membre-signature-status-smoke`, `membre-email-vide-smoke`, `actif-par-section-smoke`, `MembreAdminAuthorizationTest`
- [ ] **2.2 Statistiques d'adhérents et cartes de membre** (76) : `AdherentsStatsTest`, `AdherentsStatsIntegrationTest`, `adherents_report`, `cartes-membre-smoke`, `CartesMemberModelTest`
- [ ] **2.3 Autorisations, règles** (99) : `Gvv_AuthorizationTest`, `Authorization_modelTest`, `TypesRolesModelMySqlTest`, `AuthorizationSmokeTest`, `AuthorizationIntegrationTest`, `AuthorizationHelperTest`, `AuthorizationControllerTest`, `LoginAttemptsWindowTest`, `DXAuthIsPasswordTest`
- [ ] **2.4 Autorisations, parcours et comptes de test** (95) : `obelix-authorization`, `asterix-authorization`, `migrated/access-control`, `gaulois-users-accounts`, `legacy-users-accounts`, `migrated/login`, `navigation-policy`, `auth-login`, `*-recursive-authorizations`
- [ ] **2.5 Autorisations, comptes restants** (81) : `abraracourcix-authorization`, `goudurix-authorization`. Reprendre les formulations de l'étape 2.4 quand le comportement vérifié est le même.
- [ ] **2.6 Sections, tableau de bord et configuration** (87) : features « Sections », « Tableau de bord » et « Configuration »

## Phase 3 — Documents, communication et autres fonctions

- [ ] **3.1 Acceptation, suite, et messages du jour** (102) : le reste de la feature « Acceptation de documents » et la feature « Messages du jour », hors migrations
- [ ] **3.2 Documents archivés** (79) : toute la feature, hors migrations
- [ ] **3.3 Alarmes et procédures** (91) : features « Alarmes et notifications » et « Procédures », hors migrations
- [ ] **3.4 Listes de diffusion, adresses** (70) : `EmailHelperTest`, `EmailExtractTest`, `EmailListsPrivacyTest`, `Email_lists_model_smoke_test`
- [ ] **3.5 Listes de diffusion, gestion** (87) : `EmailListsModelTest`, `EmailListsSublistsModelTest`, `EmailListsDeletionTest`, `EmailListsResolutionTest`, tous les fichiers Playwright de la feature
- [ ] **3.6 Newsletter** (73) : toute la feature, hors migrations
- [ ] **3.7 Formulaires, fichiers et champs** (83) : `FormsFileStorageTest`, `FormsFieldParserTest`, `FormsRendererLocalUrlRewriteTest`, `PdfFormsScriptTest`
- [ ] **3.8 Formulaires, affichage et soumissions** (103) : le reste de la feature, hors migrations
- [ ] **3.9 Formation, programmes et suivi** (90) : `FormationMarkdownParserTest`, `SuiviSeanceModelTest`, `SuiviProgrammeModelTest`, `FormationProgrammeSectionFilterMySqlTest`, `formation/programmes`, `formation/progressions`, `FormationProgressionTest`, `FormationInscriptionMysqlTest`, `formation/inscriptions`, `FormationEleveRoleTest`
- [ ] **3.10 Formation, séances** (86) : le reste de la feature, hors migrations
- [ ] **3.11 Maintenance des aéronefs** (73) : toute la feature, hors migrations
- [ ] **3.12 Calendrier, terrains et événements** (101) : features « Calendrier et présences », « Terrains » et « Événements et qualifications », hors migrations
- [ ] **3.13 Pièces jointes, exports et sauvegarde** (76) : features « Pièces jointes » et « Exports », `DatabaseRestoreTest`, `FileRotatorTest`

## Phase 4 — Socle technique

Ces tests ne décrivent pas une fonction du club. Le scénario se place au niveau de ce que voit l'utilisateur ou l'administrateur (« un montant s'affiche avec deux décimales »), jamais de la fonction appelée.

- [ ] **4.1 Affichage** (92) : `MyHtmlHelperIntegrationTest`, `FormElementsIntegrationTest`, `GvvmetadataTest`
- [ ] **4.2 Éléments de formulaire et boutons** (91) : `FormElementsHelperTest`, `ButtonSubclassesTest`, `ButtonNewTest`, `ButtonTest`, `ButtonLibraryTest`
- [ ] **4.3 Journaux et sécurité** (94) : `LogHelperIntegrationTest`, `LogHelperTest`, `LogLibraryTest`, `CryptoHelperTest`, `WsseHelperTest`
- [ ] **4.4 Validation et composants** (112) : `ValidationHelperTest`, `AssetsHelperTest` (2 fichiers), `AssetsHelperIntegrationTest`, `WidgetTest`, `WidgetLibraryTest`, `CsvHelperTest` (2 fichiers), `BitfieldsHelperTest`, `BitfieldTest`
- [ ] **4.5 Reste du socle** (85) : tous les fichiers restants de la feature

## Phase 5 — Migrations de base de données

Traitées à part : leur scénario décrit ce que la mise à jour apporte et garantit (données conservées, retour arrière possible), pas la structure créée.

- [ ] **5.1 Migrations des documents et de la communication** (74) : acceptation (41), documents archivés (21), alarmes (6), newsletter (3), messages du jour (3)
- [ ] **5.2 Migrations des modules fonctionnels** (76) : formulaires (26), maintenance (22), paiements (15), listes de diffusion (13)
- [ ] **5.3 Autres migrations** (58) : vols de découverte (13), sauvegarde et migrations (13), membres (10), calendrier (8), formation (6), facturation (2), comptabilité (2), saisie des vols (2), autorisations (2)

## Clôture

- [ ] **6.1 Vérification finale** : la page indique autant de tests avec scénario que de tests, et un test échoue dorénavant si un test n'a pas de scénario.
- [ ] **6.2 Décision sur les doublons** : proposer un traitement des tests en double relevés pendant le rattrapage, par exemple entre `unit/enhanced/` et `unit/helpers/`.

## Anomalies relevées

Tests sans comportement vérifiable, noms trompeurs ou défauts révélés pendant la rédaction.

| Étape | Test | Constat | Suite proposée |
|---|---|---|---|
| 0.3 | `testSelectPage_WithFilter` | Vérifie que l'élément filtré est présent, mais pas que les autres catégories sont exclues : le filtrage n'est pas prouvé. | Ajouter l'élément d'une autre catégorie et vérifier son absence. |
| 0.3 | `testSyncTargetMotd_UnrestrictedTargetsEveryActiveMember` | Le nom annonce tous les membres, le test n'en contrôle qu'un. Le scénario décrit ce qui est vérifié. | Renommer le test, ou comparer à toutes les personnes attendues. |
| 0.3 | `testCreate_AllCategories`, `testCreate_BothTargetTypes` | Couvrent la catégorie « autorisation » et les destinataires externes, sortis du périmètre lors de la révision du plan d'acceptation, mais toujours présents dans le code. | Décider de les garder tant que le code existe, ou de les retirer avec lui. |
