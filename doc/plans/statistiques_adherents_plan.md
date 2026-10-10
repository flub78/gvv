# Plan d'implémentation - Statistiques adhérents (phase 1)

Date : 10 octobre 2026
Référence PRD : `doc/prds/statistiques_adherents_prd.md` (EF1 à EF9 et EF11 ; EF10 répartition géographique hors périmètre, phase 2)

## Objectif
Faire évoluer le rapport `adherents_report` en page « Statistiques adhérents » en trois lots livrables indépendamment, chacun testé et documenté avant de passer au suivant.

## Point de départ
- Contrôleur `application/controllers/adherents_report.php`, modèle `application/models/adherents_report_model.php`, vue `application/views/adherents_report/bs_page.php`.
- Tests existants : `application/tests/unit/AdherentsReportModelTest.php` (calcul des classes d'âge), `playwright/tests/adherents_report.spec.js` (accès à la page).
- Le modèle actuel exécute une requête par classe d'âge et par section ; cette approche ne passe pas à l'échelle avec 9 tranches, le sexe, 10 années d'historique et la fidélisation.
- Bibliothèques graphiques déjà présentes : jqPlot (local, rapports de vol) et Chart.js 4.3.0 (CDN, statistiques d'autorisation).
- Pas de migration de base de données prévue : toutes les statistiques se calculent à partir de `membres`, `licences` et `comptes`.

## Ressources
- 1 développeur PHP / CodeIgniter 2.x.
- Environnement de développement `http://gvv.net/` (copie de la base de production, à manipuler en lecture seule pour la recette) et utilisateurs de test de `bin/create_test_users.sql`.
- Un membre du CA pour la recette des chiffres (comparaison avec ses propres décomptes).

## Estimation globale
| Étape | Estimation |
|---|---|
| 0. Conception | 0,5 j |
| Lot 1 — tranches de 10 ans et âge inconnu | 2 j |
| Lot 2 — démographie et évolution | 2 j |
| Lot 3 — fidélisation et ancienneté | 2,5 j |
| Finalisation | 0,5 j |
| **Total** | **7,5 j** |

## Suivi d'avancement

- [x] 0. Conception (0,5 j)
  - Actions :
    - Note de conception `doc/design_notes/statistiques_adherents.md` : modèle limité à l'accès aux données, calculs dans une nouvelle bibliothèque `Adherents_stats` à fonctions pures, Chart.js 4.3.0 copié en local, listes de membres repliables générées côté serveur.
    - Qualité des données vérifiée : historique des cotisations lacunaire (2014-2024 presque vide), 16 % de dates de naissance manquantes en 2026, sexe toujours renseigné, doublons de cotisation pour une même année.
    - Décisions : fidélisation par section sur l'appartenance non datée (compte 411), ancienneté calculée sur la plus ancienne entre date d'inscription et première cotisation. PRD mis à jour.
  - Validation :
    - Note de conception relue et approuvée.

- [x] 1. Lot 1 — Tranches de 10 ans et âge inconnu (EF1, EF2, EF3, EF4, EF11) (2 j)
  - Actions :
    - Modèle : remplacer les comptages requête par requête par le chargement unique des cotisations, membres, comptes 411 et sections.
    - Bibliothèque `Adherents_stats` : âge au 1er janvier, classes réglementaires, tranches de 10 ans, âge inconnu, pourcentages.
    - Copier Chart.js 4.3.0 dans `assets/javascript/`.
    - Modèle : liste des membres d'âge inconnu pour l'année (nom, prénom, section).
    - Vue : renommer la page en « Statistiques adhérents », conserver le tableau réglementaire en lui ajoutant la ligne « Âge inconnu », ajouter le tableau par tranches de 10 ans et l'histogramme horizontal.
    - Vue : affichage de la liste des membres d'âge inconnu avec lien vers la fiche membre.
    - Mettre à jour le libellé du lien dans le tableau de bord Administration du club et dans le menu (fichiers de langue).
    - Traductions FR / EN / NL.
    - Contrôle d'accès : ajout de `require_roles(['ca'])`, absent jusqu'ici. Avec la nouvelle gestion des autorisations, `check_uri_permissions()` ne filtre plus rien et tout membre connecté accédait à la page.
  - Validation :
    - Chiffres identiques à la version précédente pour les âges connus (2012, 2025, 2026). Écarts expliqués : l'ancien rapport ignorait les dates de naissance nulles (8 adhérents en 2026) et classait `0000-00-00` en « 60 ans et plus » (1 adhérent).
    - Somme des tranches (âge inconnu inclus) = total de chaque section et total club, vérifiée par les tests.
    - Page générée en environ 0,06 s ; pas de défilement horizontal de la page à 390 px de large.

- [ ] 2. Lot 2 — Démographie et évolution (EF5, EF6, EF7, EF11) (2 j)
  - Actions :
    - Bibliothèque : répartition par sexe (avec « Non renseigné »), croisement sexe × tranche de 10 ans, âge moyen et médian par section et club.
    - Bibliothèque : série pluriannuelle sur les 10 dernières années ayant des cotisations (effectif, âge moyen, classes réglementaires).
    - Vue : tableau H/F, pyramide des âges, bloc d'indicateurs synthétiques, tableau et graphique d'évolution.
    - Traductions FR / EN / NL.
  - Validation :
    - Somme H + F + non renseigné = total de chaque colonne.
    - L'effectif de l'année sélectionnée dans la série pluriannuelle est égal au total club du lot 1.
    - Âge médian vérifié à la main sur une section de petite taille.

- [ ] 3. Lot 3 — Fidélisation et ancienneté (EF8, EF9, EF11) (2,5 j)
  - Actions :
    - Bibliothèque : pour l'année N, par section et club, nouveaux / retours / renouvellements / départs, taux de rétention des nouveaux de N-1.
    - Bibliothèque : ancienneté par tranches, à partir de la plus ancienne entre date d'inscription et première cotisation.
    - Vue : tableau de fidélisation (une colonne par section + club), listes de membres par compteur avec lien vers la fiche, avertissement « départs provisoires » pour l'année en cours, tableau d'ancienneté.
    - Traductions FR / EN / NL.
  - Validation :
    - Pour le club et chaque section : nouveaux + retours + renouvellements = effectif N ; renouvellements + départs = effectif N-1.
    - Contrôle croisé avec la page « Licences/Cotisations par année » sur une année passée.

- [ ] 4. Finalisation (0,5 j)
  - Actions :
    - Documentation utilisateur (voir ci-dessous).
    - Passage complet des suites PHPUnit et Playwright, en PHP 7.4 et PHP 8.4.
    - Proposer `/create-pr` une fois les trois lots validés.
  - Validation :
    - Recette par un membre du CA sur la base de développement.
    - Aucun test existant en régression.

## Tests

### PHPUnit
- **Unit** (nouveau `application/tests/unit/AdherentsStatsTest.php` pour la bibliothèque ; `AdherentsReportModelTest.php` adapté au nouveau modèle) : fonctions de calcul pures, sans base de données.
  - Tranche de 10 ans aux frontières (19/20 ans, 79/80 ans au 1er janvier), date de naissance nulle, vide ou `0000-00-00` → âge inconnu.
  - Âge moyen et médian (effectif pair, impair, vide, uniquement des âges inconnus).
  - Classement fidélisation à partir d'historiques construits à la main : nouveau, retour après interruption, renouvellement, départ, membre rattaché à plusieurs sections.
  - Tranches d'ancienneté.
- **Intégration** (nouveau `application/tests/integration/AdherentsStatsIntegrationTest.php`) : crée ses propres membres, comptes 411 et cotisations sur une année fictive éloignée (par exemple 1995) pour ne pas interférer avec les données réelles, vérifie les comptages et les égalités de totaux, puis supprime tout ce qu'il a créé, y compris en cas d'échec.

### Playwright
- Compléter `playwright/tests/adherents_report.spec.js` :
  - Accès à la page avec un utilisateur CA ; présence des sections de chaque lot livré.
  - Changement d'année : les tableaux se mettent à jour.
  - Ouverture d'une liste de drill-down et navigation vers une fiche membre.
  - Refus d'accès pour un utilisateur sans rôle CA.
- Remplacer le mot de passe codé en dur par la configuration de test si le helper le permet (règle « pas d'identifiants dans les fichiers suivis »).

## Documentation utilisateur
- Ajouter une section « Statistiques adhérents » dans `doc/users/fr/08_rapports.md` : accès, définition d'un adhérent de l'année et d'un adhérent de section, convention d'âge au 1er janvier, lecture des tableaux de fidélisation, avertissement sur l'année en cours et sur les années dont les cotisations ne sont pas toutes saisies, utilisation de la liste « âge inconnu » pour compléter les fiches.
- Captures d'écran dans `doc/users/screenshots/`.
- Mention de la nouvelle page dans les notes de version (`doc/release_notes.md`).

## Définition de fini
- EF1 à EF9 et EF11 implémentées et conformes aux critères de succès du PRD (hors critères phase 2).
- Tests PHPUnit et Playwright ajoutés à la suite de non-régression et au vert en PHP 7.4 et 8.4.
- Note de conception et documentation utilisateur à jour.
