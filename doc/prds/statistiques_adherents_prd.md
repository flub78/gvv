# PRD — Statistiques adhérents

Date : 10 octobre 2026

## Contexte
GVV propose déjà un rapport « Adhérents par année et classe d'âge » (`adherents_report`), accessible depuis le tableau de bord *Administration du club*. Ce rapport compte les adhérents d'une année (cotisation enregistrée pour l'année) par section et pour le club, répartis en trois classes d'âge réglementaires : moins de 25 ans, 25 à 59 ans, 60 ans et plus.

Ces classes répondent aux besoins de la fédération (FFVV) et des dossiers de subvention, mais elles sont trop grossières pour piloter le club : le conseil d'administration ne peut pas voir finement la pyramide des âges, son évolution, ni la fidélisation des membres.

Par ailleurs, les membres dont la date de naissance n'est pas renseignée sont actuellement exclus des comptages sans que cela soit signalé.

## Objectifs
- Afficher la répartition des adhérents par tranches d'âge de 10 ans, par section et pour le club.
- Rendre visibles les adhérents dont l'âge est inconnu.
- Compléter le rapport avec des statistiques démographiques, de fidélisation et de répartition géographique utiles au conseil d'administration.
- Regrouper ces statistiques sur une page unique « Statistiques adhérents », en conservant le rapport réglementaire existant.

## Non-objectifs
- Modifier la page de liste des membres (`membre`). Au plus, un lien vers la page de statistiques peut y être ajouté.
- Modifier la définition d'« adhérent » utilisée par le reste de l'application (rôles par section).
- Fournir un outil de requêtes libres (déjà couvert par les rapports SQL `reports`).
- Statistiques financières (couvertes par les rapports comptables).
- Export des statistiques (CSV, xlsx, PDF) : hors périmètre de cette version.
- Calcul de distances routières ou de temps de trajet.
- Statistiques d'activité croisées avec l'âge (heures de vol par tranche, adhérents sans vol dans l'année) : elles relèvent des rapports de vol, pas de cette page.

## Personae & rôles
- **Membre du CA / bureau** : consulte les statistiques, compare les années, prépare l'assemblée générale et les dossiers de subvention.
- **Administrateur club** : utilise la liste des membres à l'âge inconnu pour compléter les fiches.
- L'accès est réservé aux mêmes rôles que le rapport actuel (niveau CA).

## Définitions
- **Adhérent de l'année N** : membre ayant une cotisation enregistrée pour l'année N (même règle que le rapport existant).
- **Adhérent d'une section** : adhérent de l'année N possédant un compte pilote (411) dans la section. L'appartenance à une section n'est pas datée : un membre ayant des comptes dans plusieurs sections est rattaché à chacune d'elles.
- **Total club** : nombre d'adhérents distincts ; un membre inscrit dans plusieurs sections n'est compté qu'une fois.
- **Âge** : âge au 1er janvier de l'année N (même convention que le rapport existant).
- **Âge inconnu** : date de naissance non renseignée ou invalide.
- **Distance club-domicile** : distance à vol d'oiseau, en kilomètres, entre le terrain du club et la commune du domicile du membre (code postal et ville de sa fiche).
- **Localisation inconnue** : code postal ou ville absents, ou commune qui ne peut pas être localisée.

## Parcours clés
1. Un membre du CA ouvre « Statistiques adhérents » depuis le tableau de bord Administration du club.
2. Il choisit une année ; toutes les statistiques de la page se mettent à jour pour cette année.
3. Il consulte la répartition par tranches de 10 ans et l'histogramme associé.
4. Il constate que des adhérents ont un âge inconnu, clique sur le compteur et obtient la liste des membres concernés avec un lien vers leur fiche.
5. Il consulte l'évolution des effectifs sur plusieurs années et le bilan nouveaux / renouvellements / départs, pour le club et pour chaque section.
6. Il consulte la répartition des adhérents par distance au terrain et par commune.

## Exigences fonctionnelles

### EF1 — Page « Statistiques adhérents »
- Le rapport `adherents_report` devient la page « Statistiques adhérents ». Le lien du tableau de bord est conservé, son libellé est mis à jour.
- Le sélecteur d'année existant s'applique à toutes les statistiques de la page.
- Les statistiques sont présentées en sections distinctes et clairement titrées.

### EF2 — Classes d'âge réglementaires (existant)
- Le tableau actuel (moins de 25 ans, 25-59 ans, 60 ans et plus, par section et total club) est conservé à l'identique.
- Une ligne « Âge inconnu » y est ajoutée (voir EF4).

### EF3 — Répartition par tranches de 10 ans
- Tranches : moins de 20 ans, 20-29, 30-39, 40-49, 50-59, 60-69, 70-79, 80 ans et plus.
- Tableau : une ligne par tranche, une colonne par section et une colonne total club, une ligne total.
- Chaque cellule affiche l'effectif ; le pourcentage par rapport au total de la colonne est également affiché.
- Un histogramme horizontal représente la répartition pour le club (total).
- Les tranches vides sont affichées avec un effectif de 0.

### EF4 — Âge inconnu
- Dans chaque tableau ventilé par âge, une ligne « Âge inconnu » compte les adhérents sans date de naissance exploitable.
- Les totaux incluent ces adhérents : le total d'une colonne est égal au nombre d'adhérents de la section, quelle que soit la ventilation.
- Lorsque le compteur est non nul, il permet d'afficher la liste des membres concernés (nom, prénom, section), chaque nom renvoyant à la fiche membre.

### EF5 — Répartition hommes / femmes
- Tableau des adhérents par sexe, par section et total club, avec une ligne « Non renseigné ».
- Croisement sexe × tranche de 10 ans pour le club, présenté sous forme de pyramide des âges (hommes d'un côté, femmes de l'autre).

### EF6 — Indicateurs synthétiques
- Pour l'année sélectionnée, par section et pour le club : effectif total, âge moyen, âge médian.
- Les adhérents d'âge inconnu sont exclus du calcul de l'âge moyen et médian ; leur nombre est indiqué à côté de ces indicateurs.

### EF7 — Évolution pluriannuelle
- Tableau et graphique de l'effectif club sur les 10 dernières années disponibles (jusqu'à l'année sélectionnée incluse) : effectif total, âge moyen, effectif par classe réglementaire.
- Les années sans aucune cotisation enregistrée ne sont pas affichées.

### EF8 — Fidélisation
Pour l'année sélectionnée N, par section et pour le club (une colonne par section et une colonne total club) :
- **Nouveaux** : adhérents en N qui n'étaient adhérents à aucune année antérieure.
- **Retours** : adhérents en N, non adhérents en N-1, mais adhérents à une année antérieure.
- **Renouvellements** : adhérents en N et en N-1.
- **Départs** : adhérents en N-1 non adhérents en N.
- **Taux de rétention des nouveaux** : proportion des nouveaux adhérents de N-1 qui sont encore adhérents en N.
- Au niveau d'une section, les mêmes règles s'appliquent aux seuls membres rattachés à la section. Les changements de section d'une année à l'autre ne sont pas détectés, l'appartenance à une section n'étant pas datée.
- Chaque compteur permet d'afficher la liste des membres concernés, avec un lien vers leur fiche.
- Pour l'année en cours, un avertissement indique que les départs sont provisoires (les cotisations de l'année peuvent ne pas être toutes enregistrées).

### EF9 — Ancienneté
- Répartition des adhérents de l'année N par ancienneté au club : moins de 2 ans, 2 à 5 ans, 5 à 10 ans, plus de 10 ans, inconnue.
- L'ancienneté est calculée à partir de la plus ancienne des deux dates suivantes : date d'inscription de la fiche membre, première année de cotisation connue. Elle est inconnue si aucune des deux n'est disponible.

### EF10 — Répartition géographique (phase 2)
- **Terrain de référence** : l'administrateur renseigne la position du terrain du club (latitude, longitude). Une position propre à une section peut être renseignée lorsque la section opère depuis un autre terrain ; à défaut, la position du club s'applique.
- **Localisation des membres** : la position du domicile est déterminée à partir du code postal et de la ville de la fiche membre, au niveau de la commune. L'adresse exacte (rue) n'est pas utilisée.
- **Tranches de distance** : moins de 10 km, 10-25 km, 25-50 km, 50-100 km, 100 km et plus, localisation inconnue. Tableau par section et total club, avec effectifs et pourcentages.
- **Indicateurs** : distance moyenne et distance médiane par section et pour le club, en excluant les localisations inconnues (dont le nombre est indiqué).
- **Communes** : liste des communes de résidence des adhérents de l'année, avec code postal, effectif et distance au terrain, triée par effectif décroissant.
- **Localisation inconnue** : le compteur permet d'afficher la liste des membres concernés (nom, prénom, code postal, ville), chaque nom renvoyant à la fiche membre.
- Si la position du terrain n'est pas renseignée, la section géographique affiche un message invitant l'administrateur à la renseigner, avec un lien vers l'écran de configuration.
- Seuls le code postal et le nom de la commune peuvent être transmis à un service extérieur de localisation ; aucun nom, adresse ni identifiant de membre n'est transmis.
- La localisation d'une commune n'est recherchée qu'une fois : une modification de fiche membre (code postal ou ville) est prise en compte au prochain affichage, sans relocaliser les autres communes.

### EF11 — Multilingue
- Tous les libellés sont disponibles en français, anglais et néerlandais.

## Exigences non fonctionnelles
- La page s'affiche en moins de 2 secondes sur un club d'environ 300 membres et 15 ans d'historique.
- Affichage lisible sur mobile (tableaux défilants horizontalement, graphiques redimensionnés).
- Les graphiques restent compréhensibles sans couleur (valeurs affichées ou accessibles au survol, légende).
- Phase 2 : la page reste utilisable si le service de localisation est indisponible : les communes non encore localisées sont comptées en « localisation inconnue » et un message l'indique.
- Aucune donnée personnelle n'est affichée en dehors des listes de membres accessibles par drill-down, réservées aux rôles autorisés.
- Compatible PHP 7.4 et 8.4.

## Critères de succès
- Pour une année donnée, la somme des tranches de 10 ans (y compris « Âge inconnu ») est égale au total club et au total de chaque section.
- Les chiffres du tableau réglementaire sont identiques à ceux du rapport actuel pour les adhérents d'âge connu.
- Pour le club et pour chaque section, les compteurs nouveaux + retours + renouvellements sont égaux à l'effectif de l'année N, et renouvellements + départs sont égaux à l'effectif de l'année N-1.
- Phase 2 : la somme des tranches de distance (y compris « Localisation inconnue ») est égale au total club et au total de chaque section.
- Un membre du CA peut identifier en un clic les fiches membres à compléter (date de naissance).
- Tests PHPUnit couvrant les calculs (tranches, âge inconnu, fidélisation, puis distances en phase 2) et test Playwright vérifiant l'accès à la page et l'affichage des sections.

## Phasage et priorités
**Phase 1** — statistiques sans dépendance extérieure, par ordre de priorité :
1. EF1, EF2, EF3, EF4 — tranches de 10 ans et âge inconnu.
2. EF5, EF6, EF7 — démographie et évolution.
3. EF8, EF9 — fidélisation et ancienneté.

EF11 (multilingue) s'applique à chaque lot livré.

**Phase 2** — EF10, répartition géographique et distance club-domicile. Elle sera spécifiée en détail (source de localisation des communes, configuration des terrains) au lancement de la phase.
