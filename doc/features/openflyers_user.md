# Intégration OpenFLyers

La comptabilité de la section planeur qui gère ses comptes bancaires, ses comptes de produits, ses comptes clients et sa facturation est cohérente.

Néanmoins les sections avion et ULM utilisent OpenFlyers pour gérer les comptes clients et la facturation.

* Quand un pilote crédite son compte, son compte OpenFLyers est crédité.
* Quand vol est facturé le compte client OpenFLyers est débité.

Lors de l'utilisation conjointe d'OpenFlyers et de GVV il est possible de de synchroniser OpenFlyers et GVV. Dans ce cas c'est OpenFlyers qui gère les comptes de resource (avions et ULM) et les comptes pilotes.

Il est possible de synchronisez OpenFlyers et GVV pour que GVV prenne en compte les informations d'OpenFLyers et d'éviter la double saisie.

## Association des comptes OpenFLyers et GVV

Les comptes resources et les comptes clients doivent exister à la fois dans OpenFlyers et dans GVV.

Il faut associer chaque compte OpenFLyers qu'on envisage d'importer au compte correspondant dans GVV.

Si vous connaissez l'identifiant du compte OpenFlyers il suffit de lui associer le compte GVV correspondant dans la table des associations. Il est possible de réaliser l'association de façon plus pratique lors de l'import des balances initiales.

### Table des associations
[Table des associations](./images/table_association_of.png)

### Saisie/modification d'une association

## Import des balances initiales des comptes clients

## Import des écritures entre deux dates
