# Gestion des formulaires

Un formulaire GVV sert à collecter des informations en ligne — comme un formulaire papier, mais archivé et retrouvable — et à s'interfacer avec GVV (pré-remplissage à partir des données du club, déclenchement d'actions). Ce document guide l'administrateur qui **fait remplir** un formulaire déjà existant : le créer, publier un lien, consulter les réponses, les corriger, accepter un dépôt scanné.

Pour **rédiger le contenu HTML/CSS d'un formulaire** ou **l'intégrer aux données GVV** (pré-remplissage, sous-formulaires, export), voir [Rédiger et intégrer un formulaire (HTML/CSS)](13_formulaires_creation.md).

> 💡 **Envie de construire un formulaire pas à pas ?** Voir le [tutoriel — Créer un formulaire avec l'aide d'un assistant IA](13_formulaires_tutoriel.md), qui construit un exemple complet (3 pages, upload, signature) en s'appuyant sur ChatGPT pour générer le HTML.

## Sommaire

- [Gestion des formulaires](#gestion-des-formulaires)
  - [Sommaire](#sommaire)
  - [Vue d'ensemble](#vue-densemble)
  - [Créer un nouveau formulaire](#créer-un-nouveau-formulaire)
  - [Générer un lien pré-rempli pour un membre ou un instructeur](#générer-un-lien-pré-rempli-pour-un-membre-ou-un-instructeur)
  - [Consulter les réponses reçues](#consulter-les-réponses-reçues)
  - [Corriger une réponse déjà soumise](#corriger-une-réponse-déjà-soumise)
  - [Accepter une réponse déposée par scan ou photo](#accepter-une-réponse-déposée-par-scan-ou-photo)
  - [Référence — champs et métadonnées du formulaire](#référence--champs-et-métadonnées-du-formulaire)
  - [Pour aller plus loin](#pour-aller-plus-loin)

---

## Vue d'ensemble

Un formulaire GVV est un conteneur (métadonnées + statut) associé à une ou plusieurs pages HTML stockées dans `uploads/formulaires/{code}/`, publié via un lien public anonyme de la forme `http://gvv.net/index.php/forms/{slug-public}`. Les réponses soumises sont archivées et consultables depuis l'administration ; les fichiers joints (photos, PDF, signatures) sont stockés de manière sécurisée.

Flux de travail :

```
Créer le conteneur → Déposer une archive (pages HTML, CSS, images)
→ Publier → Partager le lien (brut ou pré-rempli) → Consulter les réponses
```

![Liste des formulaires](../screenshots/formulaires/admin_liste_formulaires.png)

---

## Créer un nouveau formulaire

1. **Formulaires → Nouveau formulaire**.
2. Renseigner au minimum le **Code** et le **Titre** (voir la [référence des champs](#référence--champs-et-métadonnées-du-formulaire) pour le rôle de chaque champ).
3. Valider : le conteneur est créé, sans contenu.
4. Déposer une archive HTML/CSS pour donner du contenu au formulaire — voir [Créer le contenu d'un nouveau formulaire](13_formulaires_creation.md#créer-le-contenu-dun-nouveau-formulaire). Sans cette étape, le formulaire reste vide.
5. Vérifier les pages détectées : la fiche du formulaire liste, en lecture seule, les pages du dossier de stockage (numéro, titre, aperçu, bouton **"Champs"** pour voir les champs détectés sur la page).
6. Passer le **Statut** à `publié` quand le formulaire est prêt à être partagé.

![Création d'un formulaire](../screenshots/formulaires/admin_creation_formulaire.png)
![Gestion des pages](../screenshots/formulaires/admin_pages.png)

Pour sauvegarder ou transférer un formulaire complet vers une autre installation GVV ou vers un autre club, voir [Sauvegarder ou transférer un formulaire complet](13_formulaires_creation.md#sauvegarder-ou-transférer-un-formulaire-complet).

---

## Générer un lien pré-rempli pour un membre ou un instructeur

Si le formulaire a été configuré avec un **Contexte GVV** (voir référence ci-dessous), il peut être ouvert déjà rempli avec les informations d'un pilote et/ou d'un instructeur :

1. Dans la liste des formulaires, cliquer sur **"Générer"** — visible seulement si le formulaire a un contexte GVV configuré.
2. Sélectionner le pilote et/ou l'instructeur.
3. Cliquer sur **"Ouvrir le formulaire"** : GVV ouvre le lien avec les bons paramètres.

Un club-admin peut aussi épingler cette page de génération comme raccourci sur un tableau de bord GVV, pour l'atteindre en un clic.

Ce pré-remplissage suppose que les champs de la page ont été annotés à la rédaction — voir [Pré-remplir un champ avec les données GVV](13_formulaires_creation.md#pré-remplir-un-champ-avec-les-données-gvv) et [Générer un lien pré-rempli (page de génération)](13_formulaires_creation.md#générer-un-lien-pré-rempli-page-de-génération) dans le document de rédaction.

---

## Consulter les réponses reçues

1. **Formulaires → [nom du formulaire] → Réponses**.
2. La liste indique, pour chaque soumission, l'**Identification** (champs marqués comme identifiants, ou le commentaire pour un dépôt scanné) et **Soumis par** (nom/email captés, ou "Anonyme").
3. **"Ouvrir"** affiche le détail : toutes les valeurs saisies, et un aperçu intégré des fichiers/signatures joints (image affichée directement, PDF dans un cadre de prévisualisation) ; "Aperçu" et "Télécharger" restent disponibles.
4. **"PDF"** ouvre une version imprimable de la réponse.
5. **"Supprimer"** retire définitivement la réponse, ses valeurs et ses fichiers — il n'y a pas d'expiration automatique, la rétention est illimitée tant qu'une réponse n'est pas supprimée manuellement.

Les fichiers joints ne sont jamais accessibles par une URL prévisible côté public : seul un administrateur authentifié ayant accès à la section peut les consulter, et le navigateur n'est pas autorisé à les mettre en cache.

---

## Corriger une réponse déjà soumise

Pour suivre une procédure (attestation à compléter, dossier à mettre à jour...), une réponse déjà envoyée peut être rouverte et corrigée sans créer de nouvelle réponse.

1. Depuis la liste des réponses ou le détail d'une réponse, cliquer sur **"Modifier"** — disponible uniquement pour les réponses saisies en ligne (pas pour un [dépôt par scan](#accepter-une-réponse-déposée-par-scan-ou-photo), où seule la rotation du fichier est possible) et réservé à un administrateur de la section (ce n'est pas un lien renvoyé à la personne qui a rempli le formulaire).
2. Le formulaire public se rouvre, page par page, avec les valeurs déjà soumises. Pour un champ **fichier** ou **signature**, laisser le champ inchangé **conserve** la valeur existante ; en fournir une nouvelle la **remplace**.
3. Cliquer sur **"Enregistrer les modifications"** : la réponse est mise à jour en place (même identifiant, même date de soumission initiale, même rattachement GVV), avec une **date de dernière modification** affichée en plus.

---

## Accepter une réponse déposée par scan ou photo

Pour les documents à imprimer, signer à la main puis renumériser (attestations, documents administratifs), GVV propose un dépôt de fichier en complément — jamais à la place — de la saisie en ligne.

1. Activer l'option sur la fiche du formulaire : cocher **"Autoriser la soumission par téléchargement (scan)"** (désactivée par défaut).
2. Côté public, un bouton **"Télécharger un formulaire prérempli"** apparaît sur la dernière page à côté du bouton d'envoi habituel : dépôt d'un seul fichier (PDF, JPG, PNG, GIF, WEBP) avec un commentaire optionnel qui sert d'identifiant dans la liste admin.
3. Un administrateur peut aussi déposer une réponse au nom d'un usager, avec le même bouton en haut de la liste des réponses.
4. Dans la liste, une réponse déposée se reconnaît à sa miniature cliquable (à la place du bouton PDF) et aux boutons de **rotation** (↺ / ↻) pour redresser une photo mal orientée — il n'y a pas de bouton "Ouvrir" puisqu'il n'y a pas de champs à afficher, seulement le fichier. La suppression retire le fichier et sa miniature.

![Case à cocher "Autoriser la soumission par téléchargement"](../screenshots/formulaires/admin_upload_checkbox.png)
![Modale de téléchargement d'un formulaire prérempli](../screenshots/formulaires/form_upload_modal.png)
![Réponse par téléchargement dans la liste admin — miniature et rotation](../screenshots/formulaires/submissions_upload_thumbnail.png)

---

## Référence — champs et métadonnées du formulaire

Champs de la fiche de création/modification d'un formulaire (**Formulaires → Nouveau formulaire**, ou fiche d'un formulaire existant) :

| Champ | Rôle |
|---|---|
| **Code** | Identifiant interne (lettres, chiffres, tirets). Sert de nom de dossier de stockage (`uploads/formulaires/{code}/`) et de clé unique en base ; renommer le Code renomme aussi le dossier physique. |
| **Titre** | Affiché en en-tête du formulaire public |
| **Description** | Texte optionnel affiché sous le titre |
| **Lien public** | Segment d'URL public (ex. `inscription-club`) vu par les visiteurs (`forms/{slug}`) — indépendant du Code, modifiable séparément |
| **CSS scope** | Préfixe optionnel pour isoler le CSS global de ce formulaire des autres |
| **Contexte GVV** | Sélecteur(s) de pré-remplissage nécessaires : aucun, membre, instructeur, ou les deux — active le bouton "Générer" (voir [Générer un lien pré-rempli](#générer-un-lien-pré-rempli-pour-un-membre-ou-un-instructeur)) |
| **Formulaire global** | Rend le formulaire visible dans toutes les sections plutôt que la seule section active |
| **Autoriser la soumission par téléchargement (scan)** | Active le dépôt de fichier — voir [Accepter une réponse déposée par scan ou photo](#accepter-une-réponse-déposée-par-scan-ou-photo) |
| **Traitement après soumission** | Déclenche une action GVV (ex. mise à jour d'un vol de découverte) juste après l'enregistrement de la réponse |
| **Formulaire de création cible (export)** + **Libellé du bouton export** | Si les deux sont renseignés, un bouton apparaît sur chaque réponse pour ouvrir un formulaire GVV pré-rempli avec les valeurs de la réponse — voir [Exporter une réponse vers un formulaire de création](13_formulaires_creation.md#exporter-une-réponse-vers-un-formulaire-de-création) |
| **Statut** *(en modification uniquement)* | `brouillon` : non accessible ; `publié` : accessible via le lien public ; `archivé` |

Le formulaire de création ne définit que ces métadonnées du conteneur — pas de champ pour saisir le HTML ou le CSS des pages, qui s'ajoute par dépôt d'archive (voir [Créer un nouveau formulaire](#créer-un-nouveau-formulaire)).

Pour la liste des types de champs de saisie possibles dans les pages (texte, date, fichier, signature...) et des attributs `data-gvv-*` qui les enrichissent, voir [Référence — champs, types et métadonnées](13_formulaires_creation.md#référence--champs-types-et-métadonnées) dans le document de rédaction.

---

## Pour aller plus loin

- [Rédiger et intégrer un formulaire (HTML/CSS)](13_formulaires_creation.md) — rédiger le contenu HTML/CSS d'un formulaire et l'intégrer aux données GVV (pré-remplissage, sous-formulaires, export).
- [Tutoriel — Créer un formulaire avec l'aide d'un assistant IA](13_formulaires_tutoriel.md) — construction pas à pas d'un exemple complet.
