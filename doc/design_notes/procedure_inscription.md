# Gestion des procédures (entre autre de la procédure d'inscription)

Ce fichier est une description fonctionnelle de la gestion des procédures.

## Cas d'utilisation

* En tant qu'admin je peux définir une procédure
  * définir le texte et l’enchaînement des pages
  * charger les fichiers images a valider.
  * Définir les informations à collecter

* En tant qu'admin je peux consulter le suivi des procédures
  * Connaître les procédures en cours
  * Consulter les procédures validées/terminées.
  * Supprimer des suivis de procédure

* En tant qu'admin je peux valider des documents soumis par un utilisateur
  * les visualiser
  * les valider
  * les rejeter en expliquant pourquoi.
   
* En tant qu'utilisateur je peux commencer une procédure
  * Je reçoit un identifiant aléatoire qui me permettra de continuer la procédure
  * Je peux saisir les informations demandées
  * Je peux accepter et valider des documents
  * je peux télécharger des documents.
  * J'ai accès à l'état d'avancement de ma procédure et je peux revenir en arrière
  * Une fois ma procédure soumise, je peux consulter l'état de validation des documents fournis.

## Implémentation

Une procédure est constituée de plusieurs éléments.

* Des pages d'informations qui s’enchaînent
* Des fichiers pdf a visualiser et accepter.
* Des fichiers pdf générés pendant la procédure
* Des sous-procédures
* Un mécanisme de navigation 

## Utilisation de markdown pour la définition des procédures

Les admins doivent pouvoir définir des procédures sans connaissance de programmation.

L'idée est leur laisser définir la procédure en markdown avec des meta balises pour controller la logique de la procédure.

Une procédure de base sans intervention de l'utilisateur n'est qu'un fichier markdown visualisé en HTML.

### Metabalises

* {page} définit un saut de page. L'utilisateur peut avancer ou reculer d'une page lors qu'il execute la procédure

* {pdf:filename] un fichier pdf à visualiser dans un ascenseur. 

* {acceptation:text} Un bouton pour demander à l'utilisateur d'accepter valider quelque 

* {date} {varchar(20)} {text(250)}

* {upload:"Téléchargez une photo de vous"},{upload:"Votre certificat médical"}
 
* {upload_to_validate:"Télécharger l'autorisation parentale"}


### Persistence

On aura en base de données
* une table de procédure
* une table d'execution de procédure

Et sur le serveur
procedures/
├── example_procedure
│   └── procedure_example_procedure.md
├── inscription
│   └── procedure_inscription.md
├── inscription_avion
│   └── procedure_inscription_avion.md
└── maintenance_planeur
    └── procedure_maintenance_planeur.md
suivi_procedure/
└── inscription_avion
    └── suivi1
        ├── certificat_medical.png
        ├── jean_dupont_data.json
        └── photo.png


### Questions

* Sachant que les procédures vont être définies par les admins, comment faire pour qu'il soit possible de créer automatiquement une fiche de membre à partir des informations saisies dans la procédure.
 