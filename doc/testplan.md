# Plan de test de GVV

Il s'agit d'une reconstitution de plan de test à posteriori. Il est possible que certains tests soient manquants ou incomplets.

[Tests manuels](#tests-manuels)

[Après installation](#tests-manuels-après-installation)

[Sur une version déployée](#tests-manuels-sur-une-version-déployée)

[Tests automatiques](#tests-automatiques)

[Tests intégrés](#tests-automatiques-intégrés)

[Tests automatiques sur le PC de développement](#tests-automatiques-sur-le-pc-de-développement)

[Tests automatiques dans le Cloud](#tests-automatiques-dans-le-cloud)

## Tests manuels

### Tests manuels après installation

Après installation vous avez un environment minimal avec quelques utilisateurs et quelques tables pré-remplie.

C'est important de respecter les dépendances. Il faut qu'il existe des pilotes et des planeurs pour tester les vols, il faut des comptes avant de créer les planeurs, etc.

Tests:

Les tests cochés sont ceux qui ont été passés avec succès lors de la rédaction de cette documentation (passage sous Github).

- [x] Vérification de la navigation (admin)
- [ ] Vérification de la navigation (user)
- [x] Vérification de l'écriture dans les journaux
- [x] Configuration club
- [X] Responsive interface, menu, formulaire
- [X] Terrains
- [ ] Calendrier
  - [X] Lecture
  - [ ] Ecriture 
- [X] Sauvegarde de la base de données
- [ ] Restauration de la base de données
- [ ] Plan comptable
- [ ] Comptes
- [ ] Produits
- [ ] Planeurs
- [ ] Pilotes
- [ ] Vols
- [ ] Facturation

Si tout fonctionne jusque la, cela fonctionne globalement.

### Tests manuels sur une version déployée

## Test Automatiques

### Tests intégrés

https://gvvg.flub78.net/index.php/tests

Naviguez sur les différentes pages et cherchez "Failed" dans le navigateur.

### Test Automatiques sur le PC de développement

### Test Automatiques dans le Cloud

Ils sont activés sur modification des sources.

Analyse statique    https://jenkins2.flub78.net:8443/

Tests de bout en bout   https://jenkins2.flub78.net:8443/job/GVV_Dusk/

