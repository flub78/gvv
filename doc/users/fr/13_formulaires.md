# Gestion des formulaires

Le module formulaires permet de créer des formulaires HTML publiables via un lien public anonyme, de collecter les réponses et de les consulter depuis l'interface d'administration.

Ce mécanisme inspiré des formulaires Google est un moyen pratique d'étendre les fonctionnalités de GVV sans modifier le code. Par rapport à un formulaire papier, il permet la saisie en ligne et gère l'archivage des réponses. Toutes les réponses peuvent être retrouvées facilement dans GVV.

Ce document s'adresse à qui **fait remplir** un formulaire déjà existant : le créer en tant que conteneur, le publier, générer des liens pré-remplis, consulter et gérer les réponses. Pour **rédiger ou modifier le contenu HTML/CSS** d'un formulaire (pages, champs, mise en forme), voir le document séparé [Rédiger le contenu d'un formulaire (HTML/CSS)](13_formulaires_creation.md).

> 💡 **Envie de construire un formulaire pas à pas ?** Voir le [tutoriel — Créer un formulaire avec l'aide d'un assistant IA](13_formulaires_tutoriel.md), qui construit un exemple complet (3 pages, upload, signature) en s'appuyant sur ChatGPT pour générer le HTML.

## Sommaire

1. [Vue d'ensemble](#vue-densemble)
2. [Créer un formulaire](#créer-un-formulaire)
3. [Gérer les pages](#gérer-les-pages)
4. [Page de génération](#page-de-génération)
5. [Consulter les réponses](#consulter-les-réponses)
6. [Modifier une réponse déjà soumise](#modifier-une-réponse-déjà-soumise)
7. [Soumission par téléchargement (scan)](#soumission-par-téléchargement-scan)
8. [Sous-formulaires](#sous-formulaires)
9. [Exporter une réponse vers un formulaire de création](#exporter-une-réponse-vers-un-formulaire-de-création)
10. [Pour aller plus loin — rédiger le contenu d'un formulaire](#pour-aller-plus-loin--rédiger-le-contenu-dun-formulaire)

---

## Vue d'ensemble

Un formulaire est un ensemble de pages HTML contenant des champs de saisie. Il est publié via un lien public anonyme, et les réponses sont collectées dans GVV. L'administrateur peut consulter les réponses, les exporter en PDF et supprimer celles qui ne sont plus utiles.

Un formulaire permet également de collecter des fichiers (photos, documents PDF, etc.) et des signatures électroniques. Les fichiers sont stockés de manière sécurisée et ne sont accessibles que depuis l'interface d'administration.

Il est possible de remplir un formulaire en une ou plusieurs fois, et de modifier une réponse déjà soumise si le formulaire est conçu pour cela. Cela permet par exemple de suivre la mise en place d'une procédure ou de compléter un dossier. La procédure n'est considérée comme terminées que lorsque tous les documents ont été fournis et touts les champs obligatoires remplis.

Un formulaire GVV est composé de :

- **Métadonnées** : titre, code interne, slug public (URL d'accès anonyme), CSS global, statut (brouillon / publié / archivé)
- **Pages** : un formulaire peut comporter plusieurs pages ; chaque page contient du HTML libre et des champs
- **Champs** : éléments de saisie détectés automatiquement dans le HTML de la page (voir [Champs détectés automatiquement](13_formulaires_creation.md#champs-détectés-automatiquement) dans le document de rédaction) — rien à déclarer séparément côté administration
- **Réponses** : soumissions anonymes, consultables et exportables en PDF

Flux de travail :

```
Créer le conteneur du formulaire → Déposer une archive (pages HTML, CSS, images)
→ Publier → Partager le lien public → Consulter les réponses
```

La deuxième étape (« Déposer une archive ») est le travail de la personne qui rédige le contenu — voir [Rédiger le contenu d'un formulaire (HTML/CSS)](13_formulaires_creation.md). Les autres étapes sont couvertes dans ce document.

Le lien public a la forme : `http://gvv.net/index.php/forms/{slug-public}`

Le contenu de chaque page (HTML + CSS) est stocké sous forme de fichiers dans `uploads/formulaires/{code}/` et s'édite exclusivement par dépôt d'archive — voir [Gérer le contenu d'un formulaire (archive)](13_formulaires_creation.md#gérer-le-contenu-dun-formulaire-archive).

![Liste des formulaires](../screenshots/formulaires/admin_liste_formulaires.png)

---

## Créer un formulaire

Navigation : **Formulaires → Nouveau formulaire**

![Création d'un formulaire](../screenshots/formulaires/admin_creation_formulaire.png)

| Champ | Rôle |
|---|---|
| **Code** | Identifiant interne (lettres, chiffres, tirets) |
| **Titre** | Affiché en en-tête du formulaire public |
| **Description** | Texte optionnel affiché sous le titre |
| **Lien public** | Segment d'URL (ex. `inscription-club`) — voir la distinction avec **Code** ci-dessous |
| **CSS scope** | Préfixe optionnel pour isoler le CSS global de ce formulaire des autres |
| **Contexte GVV** | Sélecteur(s) de pré-remplissage nécessaires : aucun, membre, instructeur, ou les deux — voir [Pré-remplissage — mécanisme A](13_formulaires_creation.md#pré-remplissage--mécanisme-a-attributs-data-gvv-source) dans le document de rédaction |
| **Formulaire global** | Rend le formulaire visible dans toutes les sections plutôt que la seule section active |
| **Autoriser la soumission par téléchargement (scan)** | Active le bouton "Télécharger un formulaire prérempli" — voir [Soumission par téléchargement (scan)](#soumission-par-téléchargement-scan) |
| **Traitement après soumission** | Déclenche une action GVV (ex. mise à jour d'un vol de découverte) juste après l'enregistrement de la réponse |
| **Formulaire de création cible (export)** + **Libellé du bouton export** | Si les deux sont renseignés, un bouton apparaît sur chaque réponse pour ouvrir ce formulaire GVV pré-rempli avec les valeurs de la réponse (ex. `membre/create`) — voir [Exporter une réponse vers un formulaire de création](#exporter-une-réponse-vers-un-formulaire-de-création) |
| **Statut** *(en modification uniquement)* | `brouillon` : non accessible ; `publié` : accessible via le lien public ; `archivé` |

Le **Code** sert de nom de dossier de stockage (`uploads/formulaires/{code}/` : pages HTML, CSS, images) et de clé unique en base pour retrouver le formulaire côté admin. Si l'admin le renomme, GVV renomme aussi le dossier physique correspondant. Il est distinct du **Lien public**, qui est le segment d'URL vu par les visiteurs externes (`forms/{slug}`) : les deux sont indépendants et modifiables séparément une fois le formulaire créé.

Ce formulaire de création ne définit que les métadonnées du conteneur : il n'y a pas de champ pour saisir le HTML ou le CSS des pages. Une fois le formulaire créé, son contenu (pages, CSS, images) s'ajoute par **dépôt d'archive**, une opération de rédaction — voir [Rédiger le contenu d'un formulaire (HTML/CSS)](13_formulaires_creation.md).

Pour sauvegarder ou transférer un formulaire complet (pages, CSS, images, métadonnées) vers une autre installation GVV ou vers un autre club, voir [Exporter / importer un formulaire complet (archive)](13_formulaires_creation.md#exporter--importer-un-formulaire-complet-archive).

## Gérer les pages

Chaque formulaire comporte une ou plusieurs pages affichées séquentiellement. GVV gère automatiquement la navigation Précédent / Suivant et le bouton de soumission finale.

![Gestion des pages](../screenshots/formulaires/admin_pages.png)

Cette liste est une vue **en lecture seule** de ce que contient le dossier de stockage du formulaire (`uploads/formulaires/{code}/pageNN.html`) : numéro, titre, aperçu du texte et bouton **"Champs"** (voir [Champs détectés automatiquement](13_formulaires_creation.md#champs-détectés-automatiquement)). Le contenu d'une page ne se modifie pas depuis cette liste : toute correction du HTML ou du CSS se fait via un dépôt d'archive — voir [Gérer le contenu d'un formulaire (archive)](13_formulaires_creation.md#gérer-le-contenu-dun-formulaire-archive).

---

## Page de génération

Pour les formulaires qui nécessitent un contexte GVV (membre, instructeur), l'administrateur peut utiliser la **page de génération** plutôt que de construire l'URL manuellement.

La page de génération est accessible via le bouton **"Générer"** dans la liste des formulaires, disponible uniquement pour les formulaires dont le champ `Paramètres requis` est différent de `aucun`.

### Configuration du formulaire

Dans la fiche admin du formulaire, le champ **Paramètres requis** définit quels sélecteurs apparaissent sur la page de génération :

| Valeur | Sélecteurs affichés |
|---|---|
| `aucun` | Aucun — pas de bouton "Générer" |
| `pilote` | Sélecteur membre (pilote) |
| `instructeur` | Sélecteur membre (instructeur) |
| `pilote + instructeur` | Les deux sélecteurs |

### Utilisation

1. Cliquer sur **"Générer"** dans la liste des formulaires.
2. Sélectionner le pilote et/ou l'instructeur selon la configuration.
3. Cliquer sur **"Ouvrir le formulaire"** : GVV construit l'URL avec `pilot_login` et/ou `instructor_login` et ouvre le formulaire pré-rempli.

Ce pré-remplissage n'est actif que si les champs de la page ont été annotés lors de la rédaction du formulaire — voir [Pré-remplissage — mécanisme A](13_formulaires_creation.md#pré-remplissage--mécanisme-a-attributs-data-gvv-source).

### Exposer la génération dans un dashboard (raccourcis)

Plutôt que de naviguer jusqu'à **Formulaires → Générer** à chaque fois, un club-admin peut ajouter une carte de raccourci directement dans un tableau de bord GVV, pointant vers la page de génération d'un formulaire précis.

Navigation : tableau de bord **Administration club** → carte **"Raccourcis dashboard"**.

| Champ | Rôle |
|---|---|
| **Dashboard** | Tableau de bord cible (`user`, `flights`, `treasurer`, `formation`, `maintenance`, `admin_club`, `admin_sys`, `dev`) |
| **Section** | Sous-titre de regroupement optionnel dans le dashboard (ex. "Formation") |
| **Titre** / **Clé de langue (titre)** | Texte de la carte ; si la clé de langue est renseignée et reconnue par GVV, elle prime sur le titre brut |
| **Description** / **Clé de langue (description)** | Texte secondaire, même logique de priorité que le titre |
| **URL** | Chemin relatif GVV (ex. `forms_admin/generate/attestation-de-formation-ulm`) ou URL externe complète (ouverte dans un nouvel onglet) |
| **Icône** | Classe Font Awesome, ex. `fa-file-signature` |
| **Couleur** | Classe Bootstrap (`text-primary`, …) |
| **Rôle requis** | Si renseigné, la carte n'est visible que pour les utilisateurs ayant ce rôle dans la section active |
| **Ordre d'affichage** | Ordre croissant au sein d'un même regroupement |
| **Actif** | Une carte désactivée n'apparaît plus, sans être supprimée |
| **Portée** | Globale (tous les clubs) ou limitée à la section active au moment de la création |

⚠️ La page de génération (`forms_admin/generate/...`) reste réservée aux `ca`/club-admins, comme le reste de l'administration des formulaires. Un raccourci qui y pointe n'est donc réellement utilisable que placé sur un dashboard **admin_club** ou **admin_sys** (ou avec **Rôle requis** = `ca`/`club-admin`). Pour un raccourci destiné aux dashboards pilote/instructeur (`user`, `flights`, `formation`), pointer plutôt vers le lien public direct du formulaire (`forms/{slug}`), éventuellement déjà pré-rempli — voir [Pré-remplissage — mécanisme B](13_formulaires_creation.md#pré-remplissage--mécanisme-b-paramètres-durl).

---

## Consulter les réponses

Navigation : **Formulaires → [nom du formulaire] → Réponses**

La liste affiche, pour chaque soumission, deux colonnes distinctes en plus de la date et des actions :

- **Identification** : valeur des champs marqués `data-gvv-identifier="true"` (voir [Champs détectés automatiquement](13_formulaires_creation.md#champs-détectés-automatiquement)), concaténés ; pour une [réponse déposée par scan](#soumission-par-téléchargement-scan), c'est le commentaire saisi au dépôt.
- **Soumis par** : nom et/ou email captés via [`data-gvv-role`](13_formulaires_creation.md#rôles-de-champs-gvv), ou "Anonyme" si aucun champ n'est ainsi marqué.

### Ouvrir une réponse

Le bouton **"Ouvrir"** affiche le détail d'une réponse en ligne :

- toutes les valeurs saisies, champ par champ, avec leur libellé et leur type ;
- les fichiers joints (champs de type `file` et signatures) avec **aperçu intégré** : une image est affichée directement, un PDF s'affiche dans un cadre de prévisualisation intégré à la page ; les boutons "Aperçu" (nouvel onglet) et "Télécharger" restent disponibles pour tout type de fichier.

### Export PDF imprimable

Le bouton **"PDF"** ouvre une version imprimable de la réponse (page HTML avec un bouton "Imprimer / Enregistrer en PDF"), reprenant le CSS global du formulaire.

### Téléchargement sécurisé des fichiers

Les fichiers joints (uploads et signatures) ne sont accessibles que depuis l'interface d'administration, à un utilisateur authentifié ayant accès à la section du formulaire — jamais par une URL prévisible côté public. Le navigateur n'est pas autorisé à mettre ces fichiers en cache.

### Rétention

Les réponses et leurs fichiers sont conservés sans limite de durée ; il n'y a pas d'expiration automatique. La suppression (bouton "Supprimer") est manuelle et retire à la fois la réponse, ses valeurs et les fichiers associés (y compris les miniatures).

---

## Modifier une réponse déjà soumise

Pour utiliser un formulaire comme support de suivi de procédure (attestation à compléter, dossier à mettre à jour...), une réponse déjà envoyée peut être rouverte et corrigée sans créer une nouvelle réponse.

### Déclencher la modification

Le bouton **"Modifier"** apparaît à côté de "Ouvrir" et "PDF" :

- dans la liste des réponses d'un formulaire ;
- dans le détail d'une réponse (bouton en haut à droite).

Il n'est disponible que pour les réponses saisies en ligne. Une réponse envoyée par [téléchargement de scan](#soumission-par-téléchargement-scan) ne peut pas être modifiée par ce mécanisme — seule la rotation du fichier déposé est possible pour ce type de réponse.

Seul un administrateur ayant accès à la section du formulaire peut déclencher une modification ; ce n'est pas un lien renvoyé à la personne qui a rempli le formulaire.

### Formulaire pré-rempli

Le bouton rouvre le formulaire public, page par page, avec les valeurs déjà soumises :

- les champs texte, date, nombre, case à cocher, liste déroulante, etc. affichent leur valeur enregistrée ;
- un champ **fichier** déjà soumis affiche le nom du fichier existant avec un lien pour le consulter ; laisser le champ vide à la resoumission **conserve** le fichier, en choisir un nouveau le **remplace** ;
- un champ **signature** déjà soumis affiche la signature existante en aperçu ; ne pas y toucher **conserve** la signature initiale, dessiner/téléverser/taper une nouvelle signature la **remplace**.

### Enregistrer les modifications

Le bouton **"Enregistrer les modifications"** valide et enregistre la réponse en place :

- l'identifiant de la réponse ne change pas — ce n'est pas une nouvelle réponse ;
- la date de soumission initiale, le rattachement éventuel à une entité GVV et le mode de soumission ne sont pas modifiés ;
- quand un fichier ou une signature est remplacé, l'ancien est supprimé du stockage une fois le nouveau enregistré avec succès ;
- le détail de la réponse affiche alors une **date de dernière modification**, en plus de la date de soumission initiale.

---

## Soumission par téléchargement (scan)

Pour certains formulaires (attestations à signer, documents administratifs), il est plus simple pour l'utilisateur d'imprimer le formulaire, de le remplir à la main, puis de téléverser une photo ou un scan de la page remplie plutôt que de ressaisir chaque champ en ligne. GVV propose cette alternative en complément — jamais à la place — de la saisie en ligne habituelle.

### Activer la fonctionnalité sur un formulaire

Dans la fiche admin du formulaire (création ou modification), cocher **"Autoriser la soumission par téléchargement (scan)"** :

![Case à cocher "Autoriser la soumission par téléchargement"](../screenshots/formulaires/admin_upload_checkbox.png)

Cette option est désactivée par défaut : chaque formulaire décide individuellement s'il accepte ce mode de réponse.

### Côté public

Quand l'option est active, un bouton **"Télécharger un formulaire prérempli"** apparaît à côté du bouton d'envoi habituel, sur la dernière page du formulaire. Il ouvre une fenêtre de dépôt de fichier (glisser-déposer ou sélection sur le disque) avec un champ de commentaire libre :

![Modale de téléchargement d'un formulaire prérempli](../screenshots/formulaires/form_upload_modal.png)

- **Formats acceptés** : PDF, JPG, PNG, GIF, WEBP.
- **Un seul fichier par réponse.**
- Le commentaire est optionnel ; il sert d'**identifiant** de la réponse dans la liste admin (voir ci-dessous).
- Cette soumission est indépendante de la saisie en ligne : les champs de la page ne sont pas utilisés pour ce mode.

### Côté admin — liste des réponses

Le bouton "Télécharger un formulaire prérempli" est aussi disponible en haut de la liste des réponses, pour qu'un administrateur puisse déposer une réponse au nom d'un usager.

Dans la liste, une réponse envoyée par téléchargement se distingue des réponses classiques :

![Réponse par téléchargement dans la liste admin — miniature et rotation](../screenshots/formulaires/submissions_upload_thumbnail.png)

- **Colonne Identification** : le commentaire saisi lors du dépôt.
- **Miniature cliquable** à la place du bouton "Générer PDF" — un clic ouvre le fichier en grand.
- **Rotation** (boutons ↺ / ↻) : pour redresser un scan ou une photo qui n'a pas été prise droite. La rotation modifie le fichier stocké.
- Pas de bouton "Ouvrir" : il n'y a pas de champs à afficher pour ce type de réponse, seulement le fichier déposé.
- La **suppression** de la réponse supprime aussi le fichier et sa miniature.

---

## Sous-formulaires

Un formulaire peut inclure un lien vers un **autre** formulaire GVV, ouvert dans un **nouvel onglet**. Une fois le sous-formulaire rempli, un résumé lecture seule de sa réponse s'affiche dans le formulaire maître. La déclaration de ce widget dans le HTML est une opération de rédaction — voir [Sous-formulaires (déclaration HTML)](13_formulaires_creation.md#sous-formulaires-déclaration-html).

### Déroulement pour l'utilisateur

1. **Remplir le sous-formulaire** — clic sur le lien, ouverture dans un nouvel onglet.
2. **J'ai terminé, vérifier** — de retour sur l'onglet du maître, ce bouton devient visible ; un clic vérifie si la réponse a bien été enregistrée, sans recharger la page (la saisie déjà en cours sur les autres champs du maître n'est jamais perdue).
3. **Résumé affiché** — une fois la réponse trouvée, un résumé lecture seule des valeurs saisies remplace le lien, avec un bouton **Remplir à nouveau** pour recommencer une réponse indépendante.

Si le widget est obligatoire, la soumission du formulaire maître est bloquée tant que le sous-formulaire n'a pas été vérifié comme rempli.

### Rattachement au formulaire maître

Avant que le maître ne soit soumis, la réponse du sous-formulaire n'est reliée à rien de définitif : un jeton technique (`link_token`) assure la correspondance le temps de la saisie. À la soumission finale du maître, cette réponse est rattachée à lui de façon durable.

Si l'utilisateur remplit le sous-formulaire mais ne termine jamais le formulaire maître, la réponse du sous-formulaire est **conservée** (jamais supprimée automatiquement) et apparaît dans la liste des réponses avec le badge **Non rattaché**.

> **Cas particulier** : si le formulaire utilisé comme sous-formulaire est par ailleurs rattaché directement à un enregistrement GVV (ex. `briefing-passager-ulm` rattaché à un vol de découverte), cet attachement d'origine est toujours prioritaire et n'est jamais remplacé par le rattachement au formulaire maître.

---

## Exporter une réponse vers un formulaire de création

Un formulaire peut déclarer une **cible d'export** : un formulaire de création GVV standard (ex. création de membre) à ouvrir, pré-rempli, à partir des valeurs d'une réponse. Contrairement au pré-remplissage GVV (mécanismes A et B), le sens du flux est ici inversé : c'est une réponse `forms` qui alimente un formulaire GVV situé en dehors du module.

### Configurer un formulaire

Dans l'admin d'édition d'un formulaire, deux champs optionnels — voir [Créer un formulaire](#créer-un-formulaire) :

- **Formulaire de création cible** : chemin relatif du contrôleur/méthode GVV à ouvrir (ex. `membre/create`).
- **Libellé du bouton export** : texte affiché sur le bouton dans la liste des réponses.

Le bouton n'apparaît que si **les deux** champs sont renseignés.

### Fonctionnement

Depuis la liste des réponses, un clic sur le bouton ouvre le formulaire cible avec un paramètre par champ de la réponse :

```
membre/create?mnom=Dupont&memail=dupont%40example.com
```

Le nommage des champs du formulaire source doit correspondre aux colonnes attendues par le formulaire cible — c'est une règle de conception à la charge de qui rédige le HTML, voir [Export vers un formulaire de création — noms de champs](13_formulaires_creation.md#export-vers-un-formulaire-de-création--noms-de-champs).

### Sécurité

Le bouton n'est visible que dans la liste admin des réponses, déjà protégée par l'authentification GVV. Ouvrir le lien pré-rempli ne fait qu'afficher un formulaire de création déjà soumis à la validation standard : aucune donnée n'est enregistrée tant que l'administrateur ne valide pas explicitement ce formulaire.

---

## Pour aller plus loin — rédiger le contenu d'un formulaire

Ce document couvre le cycle de vie administratif d'un formulaire : conteneur, publication, génération de liens, réponses. La rédaction du contenu (pages HTML, CSS, types de champs, pré-remplissage, images, export/import d'archive complète, exemples) est documentée séparément :

- [Rédiger le contenu d'un formulaire (HTML/CSS)](13_formulaires_creation.md) — document de référence pour qui construit ou modifie le HTML/CSS d'un formulaire.
- [Tutoriel — Créer un formulaire avec l'aide d'un assistant IA](13_formulaires_tutoriel.md) — construction pas à pas d'un exemple complet.
