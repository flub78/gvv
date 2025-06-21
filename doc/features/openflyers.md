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

## Récupération des vols par un logiciel tiers

https://doc4-fr.openflyers.com/R%C3%A9cup%C3%A9ration-des-vols-par-un-logiciel-tiers

Il faut enregistrer une clé de service WEB dans Admin - Structure - Paramétrage - Général

Puis on peut récupérer les vols.

```
<!DOCTYPE html>
<html>

<head>
    <title>Test récupération des vols</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>

<body>
    <form action="https://openflyers.com/abbeville/actionOnDemand.php" method="post">

        <input type="hidden" name="arguments[0]" value="getActivityList" />
        <label>Clé :</label>
        <input type="text" name="key" value="cleopenflyers" />
        <label>Limite :</label>
        <input type="text" name="maxNumber" value="1" />
        <label>Date de début (AAAA-MM-JJ hh:mm:ss) :</label>
        <input type="text" name="startDate" value="2020-04-01 00:00:00" />
        <label>Date de fin (AAAA-MM-JJ hh:mm:ss) :</label>
        <input type="text" name="endDate" value="2020-05-01 00:00:00" />
        <input type="submit" value="Test" />
    </form>
</body>

</html>
```

```
[
  {
    "flight_id": "16546",
    "first_person": "Rémy Frédéric",
    "start_date": "2025-06-21 08:48:00",
    "second_person": "PEIGNOT ULM Frédéric",
    "departure_location_name": "LFOI",
    "end_date": "2025-06-21 09:46:00",
    "arrival_location_name": "",
    "duration": "0:58",
    "landing_number": "1",
    "resource_name": "F-JTVA"
  },
  {
    "flight_id": "16547",
    "first_person": "LARTISIEN ulm Xavier",
    "start_date": "2025-06-21 08:16:00",
    "second_person": "PRUVOST Guillaume ULM",
    "departure_location_name": "LFOI",
    "end_date": "2025-06-21 09:16:00",
    "arrival_location_name": "",
    "duration": "1:00",
    "landing_number": "3",
    "resource_name": "F-JHRV"
  },
  {
    "flight_id": "16545",
    "first_person": "MICHALCZYK PASCAL",
    "start_date": "2025-06-21 07:30:00",
    "second_person": "PRUVOST Guillaume ULM",
    "departure_location_name": "LFOI",
    "end_date": "2025-06-21 08:14:00",
    "arrival_location_name": "",
    "duration": "0:44",
    "landing_number": "6",
    "resource_name": "F-JTVA"
  },
  {
    "flight_id": "16543",
    "first_person": "DUVOLLET François ULM",
    "start_date": "2025-06-19 15:36:00",
    "second_person": "",
    "departure_location_name": "LFOI",
    "end_date": "2025-06-19 16:06:00",
    "arrival_location_name": "",
    "duration": "0:30",
    "landing_number": "1",
    "resource_name": "F-JHRV"
  },
...
```

C'est facile à mettre en oeuvre mais on a pas assez d'informations pour refacturer les vols, entre autre on ne sait pas si un vol est un vol de découverte.

## Démonstration de client OAuth 2.0
