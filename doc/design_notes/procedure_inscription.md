# Procédure d'inscription — Instance du moteur de procédures guidées

Date : 24 mars 2026

Référence : [PRD — Moteur de procédures guidées](../prds/gestion_procedures_prd.md)

---

## Contexte

La procédure d'inscription est l'instance la plus complexe du moteur de procédures guidées. Elle guide un candidat externe (non encore membre) depuis sa première prise de contact jusqu'à l'intégration dans le club, en collectant données personnelles, documents réglementaires et acceptations obligatoires.

Sa particularité principale est le **branchement par discipline** : le parcours, les documents demandés et les informations affichées diffèrent selon que le candidat s'inscrit en planeur, avion ou ULM.

---

## Parcours général

```
[Accueil + email + PIN]
        ↓
[Informations personnelles]
        ↓
[Choix de la section / discipline]  ←── point de branchement
        ↓
[Informations spécifiques à la discipline]
        ↓
[Documents à fournir (selon discipline)]
        ↓
[Acceptation règlement intérieur]
        ↓
[Acceptation RGPD]
        ↓
[Récapitulatif et soumission]
        ↓
[waiting_validation] → [completed]
```

---

## Branchement par discipline

Le choix de la discipline à l'étape 3 conditionne :

| Discipline | Pages spécifiques | Documents requis |
|------------|------------------|-----------------|
| Planeur | Présentation activité planeur, prérequis | Photo d'identité, certificat médical FFVV, autorisation parentale (si mineur) |
| Avion | Présentation activité avion, prérequis | Photo d'identité, certificat médical aéronautique, copie licence (si existante) |
| ULM | Présentation activité ULM, déclaration de début de formation | Photo d'identité, attestation d'assurance personnelle |

---

## Étapes détaillées

### Étape 1 — Identification

Affichage d'un texte de bienvenue. Saisie de l'adresse email du candidat. Le système génère un code PIN de 4 chiffres et l'envoie par email. Ce couple email + PIN permet de reprendre la procédure ultérieurement.

### Étape 2 — Informations personnelles

Collecte des données de base :
- Nom, prénom, date de naissance
- Adresse postale
- Téléphone, email de contact
- Niveau d'expérience déclaré

### Étape 3 — Choix de la section

Le candidat sélectionne la discipline souhaitée. Ce choix déclenche le branchement vers le sous-parcours correspondant.

Si le club propose une section mixte ou si plusieurs disciplines sont proposées dans une même section, le candidat peut sélectionner son choix principal.

### Étapes 4–5 — Sous-parcours disciplinaire

Pages d'information spécifiques à la discipline choisie (présentation, conditions, tarifs, contacts de la section). Ces pages sont des fichiers Markdown gérés par la section.

### Étape 6 — Dépôt de documents

Selon la discipline, upload des documents requis. Certains documents nécessitent une validation administrative (`upload_validate`), d'autres sont simplement stockés (`upload`).

### Étape 7 — Acceptations

- Lecture et acceptation du règlement intérieur (PDF de la section)
- Acceptation de la politique de protection des données (RGPD)
- Acceptation des conditions d'adhésion et de cotisation

### Étape 8 — Récapitulatif

Affichage de toutes les informations saisies. Le candidat confirme et soumet le dossier.

Après soumission, l'état passe à `waiting_validation` si des documents nécessitent une validation admin, ou directement à `completed` si tous les documents sont en upload simple.

---

## États spécifiques à l'inscription

En complément des états génériques du moteur, l'inscription peut distinguer :

- **`waiting_validation`** : dossier en attente de vérification des documents par le secrétariat
- **`rejected`** : document(s) non conformes, avec motif affiché au candidat
- **`completed`** : dossier complet et validé → déclenchement de l'intégration membre

---

## Intégration avec la création de membre

À l'état `completed`, une action d'intégration est déclenchée :

1. Création d'une fiche membre provisoire dans la table `membres` avec statut `candidat`
2. Association des documents uploadés au dossier du membre (via la plateforme documentaire)
3. Notification par email au secrétariat et au candidat
4. La fiche membre reste en statut `candidat` jusqu'à validation manuelle finale par un administrateur (vérification paiement, entretien, etc.)

Le mapping entre les données saisies dans la procédure et les champs de la table `membres` est défini dans la configuration de la procédure.

---

## Documents de définition

La procédure d'inscription est définie par les fichiers suivants (à créer) :

```
procedures/
└── inscription/
    ├── 01_accueil.md
    ├── 02_informations_personnelles.md
    ├── 03_choix_section.md
    ├── branches/
    │   ├── planeur/
    │   │   ├── 04_presentation_planeur.md
    │   │   └── 05_documents_planeur.md
    │   ├── avion/
    │   │   ├── 04_presentation_avion.md
    │   │   └── 05_documents_avion.md
    │   └── ulm/
    │       ├── 04_presentation_ulm.md
    │       └── 05_documents_ulm.md
    ├── 06_acceptations.md
    └── 07_recapitulatif.md
```

Les fichiers PDF référencés (règlement intérieur, etc.) sont versionnés par section et accessibles via la plateforme documentaire.

---

## Questions ouvertes

- **Procédure vs section** : une procédure par section (inscription_planeur, inscription_avion) ou une procédure unique avec branchement interne ? La procédure unique avec branchement est plus maintenable si le tronc commun est important.
- **Mineur** : le parcours pour un candidat mineur nécessite une autorisation parentale. Ce branchement doit-il être une condition sur la date de naissance ou une question explicite ?
- **Cotisation** : le paiement est-il intégré dans la procédure ou traité séparément après validation du dossier ?
- **Multi-discipline** : un candidat qui souhaite s'inscrire à la fois en planeur et en ULM lance-t-il deux procédures distinctes ?
