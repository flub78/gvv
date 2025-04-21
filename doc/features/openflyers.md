# Intégration OpenFLyers

La comptabilité de la section planeur qui gère ses comptes bancaires, ses comptes de produits, ses comptes clients et sa facturation est cohérente.

Néanmoins le sections avions utilisent OpenFlyers pour gérer les comptes clients et la facturation.

* Quand un pilote crédite son compte, son compte OpenFLyers est crédité.
* Quand vol est facturé le compte client est débité.

Le problème pour garantir la cohérence est qu'il faudrait qu'à chaque fois qu'un pilote crédite son compte ou est remboursé, le compte client devrait être ajusté.

De la même façon quand un vol est facturé, GVV devrait ête synchronisé.

## Extraction OF

* Pour les solde des comptes clients
  * Gestion - Comptes - Balance des comptes utilisateurs 

* Accés aux opérations de compte client
  * https://openflyers.com/abbeville/index.php?menuAction=account_journal&menuParameter=359 seulement en HTML
  * Il y a assez d'information rapport id = 116)

https://doc4-fr.openflyers.com/API-OpenFlyers


## Documentation OpenFlyers

https://doc4-fr.openflyers.com/Accueil

A noter que quelque soit le périmètre choisi, OpenFlyers pourra générer l'export comptable vers le logiciel comptable utilisé pour saisir les "autres" écritures. Il faut donc bien avoir conscience que la bonne et unique façon de fonctionner est la suivante :

Ce qui doit être saisi dans OpenFlyers n'est saisi que dans OpenFlyers
Ce qui doit être saisi dans OpenFlyers est saisi avant tout export
On n'importe jamais dans OpenFlyers des données saisies dans un logiciel de comptabilité
Cela peut se résumer au principe d'hygiène de "la marche en avant" appliqué dans les cuisines pour les aliments : les données ne doivent jamais rebrousser chemin et ne doivent jamais se croiser.

