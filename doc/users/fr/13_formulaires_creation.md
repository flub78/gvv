# Rédiger et intégrer un formulaire (HTML/CSS)

Ce document s'adresse à qui **rédige ou modifie le contenu** d'un formulaire GVV (pages HTML, CSS, images, types de champs) et à qui **l'intègre à GVV** (page de génération, pré-remplissage à partir des données GVV, sous-formulaires, export vers un formulaire de création). Pour créer le conteneur d'un formulaire, le publier ou consulter/gérer les réponses, voir le document d'utilisation [Gestion des formulaires](13_formulaires.md).

> 💡 **Envie de construire un formulaire pas à pas ?** Voir le [tutoriel — Créer un formulaire avec l'aide d'un assistant IA](13_formulaires_tutoriel.md), qui construit un exemple complet (3 pages, upload, signature) en s'appuyant sur ChatGPT pour générer le HTML.

## Sommaire

1. [Principes](#principes)
2. [Gérer le contenu d'un formulaire (archive)](#gérer-le-contenu-dun-formulaire-archive)
3. [Images du formulaire](#images-du-formulaire)
4. [Convertir un formulaire PDF existant](#convertir-un-formulaire-pdf-existant)
5. [Champs détectés automatiquement](#champs-détectés-automatiquement)
6. [Types de champs](#types-de-champs)
7. [Règles CSS](#règles-css)
8. [Rôles de champs GVV](#rôles-de-champs-gvv)
9. [Page de génération](#page-de-génération)
10. [Pré-remplissage — mécanisme A (attributs `data-gvv-source`)](#pré-remplissage--mécanisme-a-attributs-data-gvv-source)
11. [Pré-remplissage — mécanisme B (paramètres d'URL)](#pré-remplissage--mécanisme-b-paramètres-durl)
12. [Sous-formulaires](#sous-formulaires)
13. [Exporter une réponse vers un formulaire de création](#exporter-une-réponse-vers-un-formulaire-de-création)
14. [Exporter / importer un formulaire complet (archive)](#exporter--importer-un-formulaire-complet-archive)
15. [Exemples de formulaires](#exemples-de-formulaires)
16. [À retenir](#à-retenir)

---

## Principes

J'ai envisagé plusieurs techniques pour permettre aux administrateurs de créer des formulaires sans coder, et j'ai choisi de rester sur du HTML. Ce n'est pas vraiment plus compliqué que de créer des formulaires en pdf ou avec un système de traitement de texte. Cela permet de rendre n'importe quel look, donc de copier assez exactement des formulaires fournis par l'administration.

Il aurait aussi été possible de faire définir les champs un peu comme la configuration des cartes de membres, en laissant les administrateurs définir les types de champs et leur position dans la page. Cela aurait été beaucoup plus compliqué et n'aurait pas permis à l'utilisateur de visualiser le rendu final du formulaire. Avec le HTML libre, l'administrateur voit exactement ce que verra l'utilisateur final.

De plus les agents IA comme ChatGPT, Claude ou Gemini sont assez compétents pour générer un formulaire en HTML à partir d'une description textuelle ou en convertissant un fichier word ou pdf en HTML.

---

## Gérer le contenu d'un formulaire (archive)

Le contenu d'un formulaire — pages HTML, CSS global, images, et les options qui en dépendent (portée CSS, paramètres requis, traitement après soumission...) — s'édite **exclusivement par dépôt d'archive ZIP**. Il n'y a pas de zone de texte HTML ou CSS dans l'interface d'administration : le seul moyen d'ajouter ou de corriger une page est de déposer un fichier ZIP construit en local (à la main ou avec l'aide d'un assistant IA — voir le [tutoriel](13_formulaires_tutoriel.md)).

C'est le fonctionnement normal pour faire évoluer un formulaire, pas seulement un mécanisme de secours : créer un formulaire, ajouter une page, corriger une coquille, changer le CSS passent tous par ce même geste — déposer une archive.

### Format de l'archive

Un fichier ZIP dont le contenu est un miroir exact du dossier de stockage du formulaire :

```
mon-formulaire.zip
├── meta.json        ← titre, description, portée CSS, paramètres requis, options d'export, liste des pages
├── page01.html       ← document HTML5 autonome (ouvrable tel quel dans un navigateur)
├── page02.html
├── style.css
└── images/
    └── logo.png
```

Chaque `pageNN.html` est un document HTML5 complet (`<!DOCTYPE>`, `<head>` avec `<link rel="stylesheet" href="style.css">`, `<body>`) : il s'ouvre directement dans un navigateur, même sans connexion au serveur GVV — pratique pour relire une mise en page ou dépanner un CSS en local avant de déposer l'archive. Seul le contenu du `<body>` est réellement utilisé par GVV — voir [Règles CSS](#règles-css).

`meta.json` porte le contenu/la configuration du formulaire (titre, description, CSS scope, paramètres requis, options d'export, titres des pages). Le **code** du formulaire (nom du dossier), son **statut** et son **lien public** n'y figurent jamais : ils restent pilotés uniquement depuis l'interface d'administration (voir [Créer un formulaire](13_formulaires.md#créer-un-formulaire)) et ne sont jamais modifiés par un dépôt d'archive.

### Créer un formulaire par archive

Depuis la liste des formulaires, le bouton **"Import depuis sauvegarde"** crée un **nouveau** formulaire à partir d'une archive. Le champ **Code** de la fenêtre d'import est optionnel : laissé vide, GVV en déduit un depuis le nom du fichier ZIP déposé ; en cas de collision avec un code existant, un suffixe (`_2`, `_3`, ...) est ajouté automatiquement.

### Modifier le contenu d'un formulaire existant

Dans la fiche d'édition d'un formulaire, la carte **"Contenu du formulaire (archive)"** permet :

- de **télécharger l'archive actuelle** (bouton "Sauvegarder (ZIP)"), pour la modifier en local puis la redéposer ;
- de **déposer une archive** (bouton à côté du champ de dépôt de fichier) qui **remplace intégralement** le contenu du formulaire (pages, CSS, images, métadonnées). Le code, le statut et le lien public ne sont **jamais** modifiés par un dépôt.

> Un dépôt remplace intégralement le contenu existant — les pages et images qui ne figurent pas dans l'archive déposée sont supprimées. Télécharger l'archive actuelle avant de déposer une modification si le contenu doit pouvoir être restauré en cas d'erreur.

### CSS partagé entre formulaires

Pour partager une base de style entre plusieurs formulaires (même charte graphique, déclinaison d'un même concours d'une année sur l'autre...) sans dupliquer le CSS dans chaque archive, placez en tête du `style.css` d'un formulaire :

```css
@import url(".commun/style.css");
```

GVV réécrit automatiquement cette référence vers la bonne adresse au moment d'afficher la page — le fichier stocké garde `.commun/style.css` tel quel, jamais une adresse en dur, ce qui le garde ouvrable directement dans un navigateur et transportable d'une installation GVV à une autre par simple export/import d'archive. Le CSS partagé lui-même est propre à l'installation GVV et modifiable uniquement par un administrateur ayant accès au serveur (fichier `uploads/formulaires/.commun/style.css`, hors du contenu de chaque formulaire).

---

## Images du formulaire

Un formulaire peut avoir besoin d'images propres (logo, en-tête, etc.), séparées du CSS et du HTML. Dans la fiche d'édition d'un formulaire, la carte **Images** permet :

- de **déposer** une image (PNG, JPEG, GIF ou WEBP, 2 Mo maximum) ;
- de consulter la **liste** des images déjà déposées, avec un aperçu miniature ;
- de **copier le chemin** de chaque image, à coller dans un attribut `src="..."` du contenu HTML de la page (ex. `<img src="{chemin copié}" alt="Logo du club">`) — un chemin relatif (`images/{fichier}`), pas une adresse GVV en dur, pour les mêmes raisons que le CSS partagé ci-dessus : le fichier stocké reste ouvrable en `file://` et déplaçable d'une installation à l'autre. GVV le réécrit vers la bonne adresse au moment de l'affichage ;
- de **supprimer** une image qui n'est plus utilisée.

Une image partagée entre plusieurs formulaires (logo de club commun, par exemple) suit la même logique que le CSS partagé : référencée par `.commun/images/{fichier}` (fichier réservé `uploads/formulaires/.commun/images/`, modifiable uniquement par un administrateur ayant accès au serveur — pas encore de carte dédiée dans l'admin pour ce cas).

> Une image collée en base64 directement dans le HTML (comme le fait déjà l'exemple `inscription_bia`) fonctionne toujours, mais alourdit le fichier de la page à chaque relecture. Pour un logo ou une image réutilisée, préférer le dépôt via la carte Images.

---

## Convertir un formulaire PDF existant

GVV n'intègre pas de convertisseur PDF → HTML automatique. Pour numériser un formulaire existant (papier ou PDF) :

1. Demander à un outil d'IA (Claude, ChatGPT, etc.) de convertir le PDF en HTML, en lui donnant les contraintes de ce document : Bootstrap 5, pas de `<head>`/`<style>` ni de balise `<form>` dans le contenu de page — voir [Règles CSS](#règles-css).
2. Relire et corriger le HTML généré : les champs ne sont pas détectés automatiquement par l'outil d'IA, les attributs `name="..."` doivent être vérifiés ou ajoutés à la main pour que GVV les reconnaisse ensuite — voir [Champs détectés automatiquement](#champs-détectés-automatiquement).
3. Enregistrer le résultat comme `page01.html`, l'assembler avec un `style.css` dans une archive ZIP (voir [Gérer le contenu d'un formulaire (archive)](#gérer-le-contenu-dun-formulaire-archive)), puis déposer cette archive. Aucune déclaration supplémentaire n'est nécessaire : GVV détecte les champs au dépôt de l'archive.
4. Vérifier le rendu sur la page publique : la fidélité visuelle au PDF d'origine n'est pas garantie et demande souvent des retouches CSS.

**Limites** : pas de détection automatique des champs du PDF source, pas de garantie de fidélité visuelle, relecture manuelle obligatoire avant publication.

---

## Champs détectés automatiquement

GVV analyse le HTML de chaque page à la volée (à l'affichage public, à l'enregistrement d'une réponse, dans la liste admin des champs) pour en extraire la liste des champs — il n'y a rien à déclarer séparément, ni de bouton "Ajouter un champ".

Sont détectés : tout `<input name="...">`, `<select name="...">` ou `<textarea name="...">` (hors `hidden`, `submit`, `reset`, `button`, `image`), ainsi que les widgets `<div data-gvv-type="signature">` et `<div data-gvv-type="subform">`.

| Propriété détectée | Provenance |
|---|---|
| **Nom technique** | Attribut `name` (ou `data-gvv-name` pour signature/sous-formulaire) |
| **Libellé** | Texte du `<label for="id_du_champ">` correspondant ; à défaut, le nom technique |
| **Type** | Type HTML de l'élément — voir [Types de champs](#types-de-champs) |
| **Obligatoire** | Attribut `required` sur l'élément (`data-gvv-required="true"` pour signature/sous-formulaire) |
| **Options** | Options du `<select>`, ou boutons `radio`/`checkbox` partageant le même `name` |

Deux attributs optionnels complètent le comportement d'un champ, sans équivalent visuel dans le rendu :

| Attribut | Effet |
|---|---|
| `data-gvv-identifier="true"` | La valeur de ce champ est concaténée avec celles des autres champs identifiants pour former l'identifiant affiché dans la [liste des réponses](13_formulaires.md#consulter-les-réponses) |
| `data-gvv-validation="regle1\|regle2"` | Règles de validation serveur supplémentaires, en plus du type. Reconnues : `max_length[n]`, `min_length[n]`, `valid_email`, `numeric` |

```html
<input type="text" name="numero_licence"
       data-gvv-identifier="true"
       data-gvv-validation="max_length[10]|numeric">
```

Depuis [Gérer les pages](13_formulaires.md#gérer-les-pages), le bouton **"Champs"** d'une page affiche la liste en lecture seule de ce que GVV a détecté — utile pour vérifier qu'un `name` n'a pas été oublié après un copier-coller. Toute correction se fait en modifiant le HTML dans une archive puis en la redéposant — voir [Gérer le contenu d'un formulaire (archive)](#gérer-le-contenu-dun-formulaire-archive) —, pas depuis cette liste.

> **Important** : c'est l'attribut `name` de l'élément HTML qui identifie le champ pour GVV — aucune correspondance à saisir ailleurs. Renommer ce `name` change l'identité du champ (les réponses déjà soumises sous l'ancien nom ne sont pas rattachées automatiquement au nouveau).

---

## Types de champs

| Type | Élément HTML | Notes |
|---|---|---|
| `text` | `<input type="text">` | — |
| `email` | `<input type="email">` | Format email RFC validé côté serveur |
| `date` | `<input type="date">` | Format `YYYY-MM-DD`, date réelle vérifiée |
| `number` | `<input type="number">` | Valeur numérique |
| `textarea` | `<textarea>` | — |
| `select` | `<select>` | Options = les `<option value="...">` du HTML |
| `radio` | `<input type="radio">` (groupe) | Options = les valeurs des boutons partageant le même `name` |
| `checkbox` | `<input type="checkbox">` (groupe) | `name="champ[]"` pour les valeurs multiples |
| `file` | `<input type="file">` | MIME et taille contrôlés |
| `signature` | `<div data-gvv-type="signature" ...>` | Widget interactif — voir ci-dessous |

### Exemples HTML par type

```html
<!-- text -->
<div class="mb-3">
  <label class="form-label" for="nom">Nom <span class="text-danger">*</span></label>
  <input type="text" class="form-control" id="nom" name="nom" required>
</div>

<!-- email -->
<div class="mb-3">
  <label class="form-label" for="email">Adresse email</label>
  <input type="email" class="form-control" id="email" name="email">
</div>

<!-- date -->
<div class="mb-3">
  <label class="form-label" for="date_naissance">Date de naissance</label>
  <input type="date" class="form-control" id="date_naissance" name="date_naissance">
</div>

<!-- number -->
<div class="mb-3">
  <label class="form-label" for="heures">Nombre d'heures</label>
  <input type="number" class="form-control" id="heures" name="heures" min="0" step="0.5">
</div>

<!-- textarea -->
<div class="mb-3">
  <label class="form-label" for="commentaire">Commentaire</label>
  <textarea class="form-control" id="commentaire" name="commentaire" rows="4"></textarea>
</div>

<!-- select (options = les <option> ci-dessous : Masculin / Féminin / Autre) -->
<div class="mb-3">
  <label class="form-label" for="genre">Genre</label>
  <select class="form-select" id="genre" name="genre">
    <option value="">-- Choisir --</option>
    <option value="Masculin">Masculin</option>
    <option value="Féminin">Féminin</option>
    <option value="Autre">Autre</option>
  </select>
</div>

<!-- radio (options = les valeurs des boutons partageant name="licencie" : Oui / Non) -->
<div class="mb-3">
  <label class="form-label d-block">Licencié FFVV ?</label>
  <div class="form-check form-check-inline">
    <input class="form-check-input" type="radio" name="licencie" id="lic_oui" value="Oui">
    <label class="form-check-label" for="lic_oui">Oui</label>
  </div>
  <div class="form-check form-check-inline">
    <input class="form-check-input" type="radio" name="licencie" id="lic_non" value="Non">
    <label class="form-check-label" for="lic_non">Non</label>
  </div>
</div>

<!-- checkbox — le [] dans name regroupe les valeurs cochées en tableau à la soumission -->
<div class="mb-3">
  <label class="form-label d-block">Disponibilités</label>
  <div class="form-check form-check-inline">
    <input class="form-check-input" type="checkbox" name="dispo[]" id="lundi" value="Lundi">
    <label class="form-check-label" for="lundi">Lundi</label>
  </div>
  <div class="form-check form-check-inline">
    <input class="form-check-input" type="checkbox" name="dispo[]" id="mardi" value="Mardi">
    <label class="form-check-label" for="mardi">Mardi</label>
  </div>
</div>

<!-- file -->
<div class="mb-3">
  <label class="form-label" for="photo">Photo d'identité</label>
  <input type="file" class="form-control" id="photo" name="photo" accept="image/jpeg,image/png">
  <div class="form-text">Formats acceptés : JPG, PNG. Taille maximale : 2 Mo.</div>
</div>
```

### Champ signature

Le champ signature est un widget interactif qui offre trois modes à l'utilisateur : dessin à la souris/tactile, import d'une image, ou frappe au clavier (rendue en écriture manuscrite). La valeur est transmise comme image PNG encodée en base64.

**Déclaration dans le HTML :**

```html
<div data-gvv-type="signature"
     data-gvv-name="signature_candidat"
     data-gvv-required="true">
  Signature du candidat
</div>
```

| Attribut | Rôle |
|---|---|
| `data-gvv-type="signature"` | Identifie le widget (obligatoire) |
| `data-gvv-name` | Nom technique du champ — voir [Champs détectés automatiquement](#champs-détectés-automatiquement) |
| `data-gvv-required` | `true` = champ obligatoire |

GVV remplace automatiquement ce `<div>` par le widget interactif lors du rendu public. Le texte contenu dans le div sert de libellé affiché au-dessus du widget.

**Aperçu hors ligne** : comme le fichier de la page est ouvrable directement dans un navigateur (voir [Développement local](#développement-local)), il est utile d'ajouter une image de repère à l'intérieur du `<div>` du widget, pour qu'il reste visuellement identifiable même sans passer par GVV. GVV ignore cette image au rendu réel (le `<div>` entier est remplacé par le widget fonctionnel) :

```html
<div data-gvv-type="signature"
     data-gvv-name="signature_candidat"
     data-gvv-required="true">
  <img src="/assets/images/forms-widgets/signature-placeholder.svg" alt="Zone de signature"><br>
  Signature du candidat
</div>
```

---

## Règles CSS

### Principe : la balise `<head>` est supprimée

Lors du rendu dans GVV, seul le contenu du `<body>` est utilisé. Les éléments suivants sont **supprimés automatiquement** :

- `<!DOCTYPE>`, `<html>`, `<head>`, `<body>`
- Tout le contenu de `<head>` — **`<style>` et `@import url(...)` placés dans `<head>` sont perdus**
- Les balises `<form>`, les boutons `type="submit"` et `type="reset"` (GVV gère la navigation)

### Ce qui fonctionne

**1. Classes Bootstrap 5 (recommandé)** — Bootstrap est chargé par GVV, ses classes sont disponibles directement.

Classes Bootstrap utiles :

| Usage | Classe |
|---|---|
| Grille 12 colonnes | `row`, `col-md-3`, `col-md-6`, `col-12` |
| Espacement de grille | `g-3` sur le `row` |
| Champ texte/date/number/file | `form-control` |
| Liste déroulante | `form-select` |
| Case à cocher / radio | `form-check`, `form-check-input`, `form-check-label` |
| Libellé | `form-label` |
| Texte d'aide | `form-text` |
| Champ obligatoire | `<span class="text-danger">*</span>` |
| Groupement visuel | `card`, `card-body` |

**2. CSS dans `style.css`** — pour les styles personnalisés, utiliser le fichier `style.css` de l'archive du formulaire (voir [Gérer le contenu d'un formulaire (archive)](#gérer-le-contenu-dun-formulaire-archive)). Ce CSS est injecté dans la page publique avant le formulaire.

Portée recommandée : `.forms-public-root` (classe appliquée automatiquement au conteneur).

```css
.forms-public-root .section-titre {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #2d465a;
  border-left: 3px solid #3b6f8f;
  padding-left: 8px;
  margin-bottom: 0.75rem;
}
```

### Ce qui ne fonctionne pas

| Pratique | Pourquoi ça échoue |
|---|---|
| `<style>` dans `<head>` | Supprimé avec `<head>` |
| `@import url(...)` de polices dans `<head>` | Supprimé avec `<head>` |
| Sélecteurs nus `input`, `label` sans portée | Conflits avec Bootstrap 5 |
| `<form>` dans le HTML | Supprimé ; GVV génère sa propre balise `<form>` |
| `<button type="submit">` | Supprimé ; GVV génère les boutons de navigation |

> Un `@import url(...)` placé en tête du fichier **`style.css`** de l'archive, lui, fonctionne — c'est le mécanisme utilisé pour le [CSS partagé entre formulaires](#css-partagé-entre-formulaires) ou pour charger une police externe. Seul un `@import` placé dans `<head>` du HTML de page est perdu.

### Développement local

Développer chaque page comme un fichier HTML autonome (`pageNN.html`, avec un `<link rel="stylesheet" href="style.css">`) accompagné de son `style.css`, exactement dans le format attendu par l'archive — voir [Format de l'archive](#gérer-le-contenu-dun-formulaire-archive). Avant de déposer l'archive dans GVV :

1. Vérifier que le CSS personnalisé est bien scopé avec `.forms-public-root`
2. Supprimer les `<form>`, les boutons `submit`/`reset` et tout `@import` de polices placé dans `<head>` (déplacer un tel `@import` en tête de `style.css` s'il doit être conservé)

---

## Rôles de champs GVV

L'attribut `data-gvv-role` sur un `<input>` ou `<textarea>` permet à GVV d'enregistrer la valeur saisie comme métadonnée de la réponse (nom et email du soumettant), visible dans la liste des réponses admin.

| Valeur `data-gvv-role` | Effet |
|---|---|
| `submitter_name` | La valeur est enregistrée comme **nom du soumettant** |
| `submitter_email` | La valeur est enregistrée comme **email du soumettant** |

```html
<input type="text" class="form-control" name="nom_complet"
       data-gvv-role="submitter_name">

<input type="email" class="form-control" name="email"
       data-gvv-role="submitter_email">
```

Quand un utilisateur GVV connecté soumet un formulaire, GVV complète ces métadonnées automatiquement avec ses informations de profil, même sans champs explicites.

---

## Page de génération

Pour les formulaires qui nécessitent un contexte GVV (membre, instructeur), l'administrateur peut utiliser la **page de génération** plutôt que de construire l'URL manuellement.

La page de génération est accessible via le bouton **"Générer"** dans la liste des formulaires (document d'utilisation, [Gestion des formulaires](13_formulaires.md)), disponible uniquement pour les formulaires dont le champ `Paramètres requis` est différent de `aucun`.

### Configuration du formulaire

Dans la fiche admin du formulaire (voir [Créer un formulaire](13_formulaires.md#créer-un-formulaire)), le champ **Paramètres requis** définit quels sélecteurs apparaissent sur la page de génération :

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

Ce pré-remplissage n'est actif que si les champs de la page ont été annotés lors de la rédaction du formulaire — voir [Pré-remplissage — mécanisme A](#pré-remplissage--mécanisme-a-attributs-data-gvv-source) ci-dessous.

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

⚠️ La page de génération (`forms_admin/generate/...`) reste réservée aux `ca`/club-admins, comme le reste de l'administration des formulaires. Un raccourci qui y pointe n'est donc réellement utilisable que placé sur un dashboard **admin_club** ou **admin_sys** (ou avec **Rôle requis** = `ca`/`club-admin`). Pour un raccourci destiné aux dashboards pilote/instructeur (`user`, `flights`, `formation`), pointer plutôt vers le lien public direct du formulaire (`forms/{slug}`), éventuellement déjà pré-rempli — voir [Pré-remplissage — mécanisme B](#pré-remplissage--mécanisme-b-paramètres-durl) ci-dessous.

---

## Pré-remplissage — mécanisme A (attributs `data-gvv-source`)

Le mécanisme A permet de pré-remplir des champs HTML avec des données issues de GVV (membres, événements, configuration club, date du jour) en déclarant des attributs `data-gvv-*` directement sur les éléments `<input>`, `<textarea>`, `<select>` ou sur les `<div data-gvv-type="signature">`.

### Attributs

| Attribut | Rôle |
|---|---|
| `data-gvv-source` | Source de la donnée à injecter (voir taxonomie ci-dessous) |
| `data-gvv-lock` | `true` = champ readonly à l'affichage **et** valeur GVV imposée à la soumission |

Le login du pilote et de l'instructeur sont transmis dans l'URL :
```
…/forms/mon-formulaire?pilot_login=dupont_j&instructor_login=martin_p
```

### Exemple

```html
<!-- Nom du candidat (verrouillé) -->
<input name="candidat_nom" type="text"
       data-gvv-source="member.nom_prenom"
       data-gvv-lock="true">

<!-- Numéro ITP de l'instructeur (verrouillé) -->
<input name="num_itp" type="text"
       data-gvv-source="instructor.event.itp.numero"
       data-gvv-lock="true">

<!-- Organisme de formation (paramètre de configuration) -->
<input name="organisme" type="text"
       data-gvv-source="config.organisme_formation">

<!-- Date du jour (automatique, pas de paramètre URL) -->
<input name="date_signature" type="date"
       data-gvv-source="date.today">

<!-- Signature de l'instructeur (pré-remplie, remplaçable) -->
<div data-gvv-type="signature"
     data-gvv-name="signature_instructeur"
     data-gvv-source="instructor.event.itp.signature"
     data-gvv-lock="false">Signature instructeur</div>
```

### Taxonomie des sources

#### Configuration et données club

| Source | Donnée |
|---|---|
| `config.<cle>` | Paramètre de configuration club (table `form_config_params`) |
| `club.nom` | Nom du club |
| `club.sigle` | Sigle du club |
| `club.adresse` | Adresse |
| `club.ville` | Ville |
| `club.email` | Email du club |

#### Membre / pilote — table `membres` (nécessite `pilot_login` dans l'URL)

| Source | Donnée |
|---|---|
| `member.nom` | Nom de famille |
| `member.prenom` | Prénom |
| `member.nom_prenom` | "Nom Prénom" |
| `member.email` | Email |
| `member.telephone` | Téléphone fixe, ou mobile si vide |
| `member.adresse` | Adresse |
| `member.code_postal` | Code postal |
| `member.ville` | Ville |
| `member.adresse_complete` | "Adresse, CP Ville" |
| `member.date_naissance` | Date de naissance (JJ/MM/AAAA) |
| `member.lieu_naissance` | Lieu de naissance |
| `member.date_lieu_naissance` | "JJ/MM/AAAA à Ville" |
| `member.signature` | Signature enregistrée dans le profil |

#### Événements / qualifications du pilote — table `events` (nécessite `pilot_login`)

| Source | Donnée |
|---|---|
| `member.event.{type_key}.numero` | Numéro de qualification (`ecomment`) |
| `member.event.{type_key}.expiry` | Date d'expiration |
| `member.event.{type_key}.date` | Date de l'événement |
| `member.event.{type_key}.signature` | Signature associée à l'événement |

#### Instructeur — table `membres` (nécessite `instructor_login` dans l'URL)

Mêmes sources que `member.*`, préfixées par `instructor.*` :
`instructor.nom_prenom`, `instructor.email`, `instructor.signature`, etc.

#### Événements / qualifications de l'instructeur — table `events`

| Source | Donnée |
|---|---|
| `instructor.event.{type_key}.numero` | Numéro de qualification |
| `instructor.event.{type_key}.expiry` | Date d'expiration |
| `instructor.event.{type_key}.date` | Date de l'événement |
| `instructor.event.{type_key}.signature` | Signature associée à l'événement |

#### Utilisateur connecté — même champs que `member.*` sans paramètre URL

Préfixe `user.*` : résolu depuis la session GVV courante.

#### Dates calculées

| Source | Donnée |
|---|---|
| `date.today` | Date du jour au format `YYYY-MM-DD` |
| `date.today_fr` | Date du jour au format `JJ/MM/AAAA` |
| `date.year` | Année en cours |

#### Clés `{type_key}` disponibles

| `{type_key}` | Qualification |
|---|---|
| `itp` | ITP (Instructeur de Planeur) |
| `itv` | ITV |
| `fi_spl` | FI Sailplane |
| `fe_spl` | FE Sailplane |
| `fi_ulm` | FI ULM |
| `fe_ulm` | FE ULM |
| `controle_competence` | Contrôle de compétence |
| `visite_medicale` | Visite médicale |
| `bpp` | BPP |
| `spl` | SPL |

---

## Pré-remplissage — mécanisme B (paramètres d'URL)

Le mécanisme B permet de pré-remplir des champs à partir de **valeurs passées directement dans l'URL**, sans aucun attribut `data-gvv-*` dans le HTML. C'est le mécanisme naturel quand le contexte vient d'une entité GVV autre qu'un membre (vol de découverte, réservation, dossier).

### Principe

Tout paramètre d'URL dont le nom correspond à un `name=` d'un champ du formulaire est injecté comme valeur par défaut. Les valeurs sont stockées en session par slug et persistent sur toutes les pages du formulaire.

```
/forms/{slug}
  ?{nom_champ}={valeur}    ← injecté dans le champ correspondant
  &lock[]={nom_champ}      ← champ readonly + valeur imposée à la soumission
```

**Noms réservés** (jamais injectés dans un champ) : `page`, `token`, `vld_id`, `pilot_login`, `instructor_login`, `lock`.

### Rôle de `lock[]`

Sans `lock[]`, la valeur est une **suggestion** : le champ est pré-rempli mais l'utilisateur peut le modifier.

Avec `lock[]`, la protection est double :
- **Affichage** : le champ reçoit l'attribut `readonly` — l'utilisateur voit la valeur mais ne peut pas la changer.
- **Soumission** : même si la valeur POST est falsifiée (modification du HTML côté client), le serveur réinjecte la valeur stockée en session. Il est impossible de soumettre une valeur différente.

### Exemple — briefing passager ULM

Un workflow GVV (vol de découverte) génère ce lien et l'envoie au passager :

```
/forms/briefing-passager-ulm
  ?date_vol=2026-07-15
  &site_decollage=Abbeville
  &identification_ulm=F-JXXX
  &nom=Dupont
  &personne_a_prevenir=Marie+Dupont
  &lock[]=date_vol
  &lock[]=site_decollage
  &lock[]=identification_ulm
```

Résultat :

| Champ | Valeur | Comportement |
|---|---|---|
| `date_vol` | 2026-07-15 | Readonly — valeur imposée (données VLD) |
| `site_decollage` | Abbeville | Readonly — valeur imposée |
| `identification_ulm` | F-JXXX | Readonly — valeur imposée |
| `nom` | Dupont | Pré-rempli — modifiable (suggestion) |
| `personne_a_prevenir` | Marie Dupont | Pré-rempli — modifiable (suggestion) |

### Coexistence avec le mécanisme A

Les deux mécanismes peuvent coexister dans le même formulaire. Les sources automatiques (`date.today`, `config.*`, `club.*`) utilisent toujours le mécanisme A. Le mécanisme B cible les champs par `name=` sans aucun attribut HTML supplémentaire.

Priorité appliquée (du plus au moins prioritaire) :
1. **Erreur de validation** (re-affichage après soumission refusée)
2. **Mécanisme A** (`data-gvv-source`)
3. **Mécanisme B** (paramètres d'URL)

---

## Sous-formulaires

Un formulaire peut inclure un lien vers un **autre** formulaire GVV, ouvert dans un **nouvel onglet** — jamais en iframe ni fusionné dans la page, pour que chaque formulaire garde son propre CSS/JS. Une fois le sous-formulaire rempli, un résumé lecture seule de sa réponse s'affiche dans le formulaire maître.

### Déclaration dans le HTML

```html
<div data-gvv-type="subform"
     data-gvv-name="briefing_passager"
     data-gvv-form-slug="briefing-passager-ulm"
     data-gvv-required="true">
  Briefing passager
</div>
```

| Attribut | Rôle |
|---|---|
| `data-gvv-type="subform"` | Identifie le widget (obligatoire) |
| `data-gvv-name` | Nom technique du widget |
| `data-gvv-form-slug` | Lien public (`public_slug`) du formulaire à ouvrir comme sous-formulaire |
| `data-gvv-required` | `true` = le formulaire maître ne peut être soumis sans réponse liée au sous-formulaire |

Comme pour le [champ signature](#champ-signature), une image de repère peut être ajoutée dans le `<div>` pour que le widget reste identifiable à l'ouverture directe du fichier de la page :

```html
<div data-gvv-type="subform"
     data-gvv-name="briefing_passager"
     data-gvv-form-slug="briefing-passager-ulm"
     data-gvv-required="true">
  <img src="/assets/images/forms-widgets/subform-placeholder.svg" alt="Sous-formulaire à compléter"><br>
  Briefing passager
</div>
```

### Déroulement pour l'utilisateur

1. **Remplir le sous-formulaire** — clic sur le lien, ouverture dans un nouvel onglet.
2. **J'ai terminé, vérifier** — de retour sur l'onglet du maître, ce bouton devient visible ; un clic vérifie si la réponse a bien été enregistrée, sans recharger la page (la saisie déjà en cours sur les autres champs du maître n'est jamais perdue).
3. **Résumé affiché** — une fois la réponse trouvée, un résumé lecture seule des valeurs saisies remplace le lien, avec un bouton **Remplir à nouveau** pour recommencer une réponse indépendante.

Si le widget est obligatoire, la soumission du formulaire maître est bloquée tant que le sous-formulaire n'a pas été vérifié comme rempli.

### Rattachement au formulaire maître

Avant que le maître ne soit soumis, la réponse du sous-formulaire n'est reliée à rien de définitif : un jeton technique (`link_token`) assure la correspondance le temps de la saisie. À la soumission finale du maître, cette réponse est rattachée à lui de façon durable.

Si l'utilisateur remplit le sous-formulaire mais ne termine jamais le formulaire maître, la réponse du sous-formulaire est **conservée** (jamais supprimée automatiquement) et apparaît dans la liste des réponses — voir [Consulter les réponses](13_formulaires.md#consulter-les-réponses) — avec le badge **Non rattaché**.

> **Cas particulier** : si le formulaire utilisé comme sous-formulaire est par ailleurs rattaché directement à un enregistrement GVV (ex. `briefing-passager-ulm` rattaché à un vol de découverte), cet attachement d'origine est toujours prioritaire et n'est jamais remplacé par le rattachement au formulaire maître.

### Limites (V1)

- Un seul niveau d'imbrication : un sous-formulaire ne peut pas lui-même contenir un widget sous-formulaire.
- Une seule réponse liée par widget (pas de sous-formulaire répétable).
- Pas d'édition en place d'une réponse de sous-formulaire déjà soumise — seulement une nouvelle soumission complète (« Remplir à nouveau »).

---

## Exporter une réponse vers un formulaire de création

Un formulaire peut déclarer une **cible d'export** : un formulaire de création GVV standard (ex. création de membre) à ouvrir, pré-rempli, à partir des valeurs d'une réponse. Contrairement au pré-remplissage GVV (mécanismes A et B), le sens du flux est ici inversé : c'est une réponse `forms` qui alimente un formulaire GVV situé en dehors du module.

### Configurer un formulaire

Dans l'admin d'édition d'un formulaire (voir [Créer un formulaire](13_formulaires.md#créer-un-formulaire)), deux champs optionnels :

- **Formulaire de création cible** : chemin relatif du contrôleur/méthode GVV à ouvrir (ex. `membre/create`).
- **Libellé du bouton export** : texte affiché sur le bouton dans la liste des réponses.

Le bouton n'apparaît que si **les deux** champs sont renseignés.

### Fonctionnement

Depuis la liste des réponses (voir [Consulter les réponses](13_formulaires.md#consulter-les-réponses)), un clic sur le bouton ouvre le formulaire cible avec un paramètre par champ de la réponse :

```
membre/create?mnom=Dupont&memail=dupont%40example.com
```

### Noms de champs

Règles de construction à respecter en rédigeant le HTML source :

- un paramètre est généré par champ, nommé comme le **nom technique** du champ source — il doit être identique au nom de colonne attendu côté formulaire cible (ex. `mnom` pour le nom d'un membre) ;
- les champs de type **fichier**, **signature** et **sous-formulaire** sont toujours exclus (pas de valeur exploitable en paramètre d'URL) ;
- les champs à **choix multiples** (ex. liste déroulante à sélection multiple) sont exclus en V1.

> Il n'y a pas de correspondance configurable entre noms de champs : nommer un champ du formulaire source comme la colonne GVV attendue est à la charge de qui rédige le formulaire.

### Sécurité

Le bouton n'est visible que dans la liste admin des réponses, déjà protégée par l'authentification GVV. Ouvrir le lien pré-rempli ne fait qu'afficher un formulaire de création déjà soumis à la validation standard : aucune donnée n'est enregistrée tant que l'administrateur ne valide pas explicitement ce formulaire.

---

## Exporter / importer un formulaire complet (archive)

Un formulaire (pages HTML, CSS, images, métadonnées) se manipule comme un seul fichier ZIP téléchargeable — le même mécanisme sert à la fois d'**édition courante** (voir [Gérer le contenu d'un formulaire (archive)](#gérer-le-contenu-dun-formulaire-archive)), de **transfert entre installations GVV** et de **partage d'un formulaire entre clubs** : télécharger l'archive d'un formulaire sur une installation, l'importer comme nouveau formulaire sur une autre.

---

## Exemples de formulaires

### Exemple 1 — Formulaire minimaliste

Un formulaire d'une page avec trois champs. Aucun CSS personnalisé.

**Champs détectés automatiquement dans le HTML ci-dessous :**

| Nom technique | Type | Obligatoire |
|---|---|---|
| `nom` | text | Oui |
| `email` | email | Oui |
| `message` | textarea | Non |

**Contenu HTML :**

```html
<div class="mb-3">
  <label class="form-label" for="nom">Nom <span class="text-danger">*</span></label>
  <input type="text" class="form-control" id="nom" name="nom" required>
</div>

<div class="mb-3">
  <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
  <input type="email" class="form-control" id="email" name="email" required>
</div>

<div class="mb-3">
  <label class="form-label" for="message">Message</label>
  <textarea class="form-control" id="message" name="message" rows="5"></textarea>
</div>
```

---

### Exemple 2 — Formulaire d'inscription membre avec signature

Formulaire réaliste couvrant tous les types de champs supportés, y compris la signature.

![Formulaire d'inscription avec signature](../screenshots/formulaires/form_avec_signature.png)

**Champs détectés automatiquement dans le HTML ci-dessous :**

| Nom technique | Type | Obligatoire |
|---|---|---|
| `nom` | text | Oui |
| `prenom` | text | Oui |
| `date_naissance` | date | Non |
| `lieu_naissance` | text | Non |
| `genre` | select | Non |
| `licencie` | radio | Non |
| `disponibilites` | checkbox | Non |
| `photo` | file | Non |
| `email` | email | Oui |
| `telephone` | text | Non |
| `commentaire` | textarea | Non |
| `signature_candidat` | signature | Oui |

**Contenu HTML de la page :**

```html
<!-- Section Identité -->
<div class="bloc-section">
  <div class="section-titre">Identité</div>
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label class="form-label" for="nom">Nom <span class="text-danger">*</span></label>
      <input type="text" class="form-control" id="nom" name="nom" required>
    </div>
    <div class="col-md-6">
      <label class="form-label" for="prenom">Prénom <span class="text-danger">*</span></label>
      <input type="text" class="form-control" id="prenom" name="prenom" required>
    </div>
  </div>
  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <label class="form-label" for="date_naissance">Date de naissance</label>
      <input type="date" class="form-control" id="date_naissance" name="date_naissance">
    </div>
    <div class="col-md-4">
      <label class="form-label" for="lieu_naissance">Lieu de naissance</label>
      <input type="text" class="form-control" id="lieu_naissance" name="lieu_naissance">
    </div>
    <div class="col-md-4">
      <label class="form-label" for="genre">Genre</label>
      <select class="form-select" id="genre" name="genre">
        <option value="">-- Choisir --</option>
        <option value="Masculin">Masculin</option>
        <option value="Féminin">Féminin</option>
        <option value="Autre">Autre</option>
      </select>
    </div>
  </div>
  <div class="mb-3">
    <label class="form-label d-block">Licencié FFVV ?</label>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="licencie" id="lic_oui" value="Oui">
      <label class="form-check-label" for="lic_oui">Oui</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="licencie" id="lic_non" value="Non">
      <label class="form-check-label" for="lic_non">Non</label>
    </div>
  </div>
  <div class="mb-3">
    <label class="form-label d-block">Disponibilités</label>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="checkbox" name="disponibilites[]" id="lundi" value="Lundi">
      <label class="form-check-label" for="lundi">Lundi</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="checkbox" name="disponibilites[]" id="mardi" value="Mardi">
      <label class="form-check-label" for="mardi">Mardi</label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="checkbox" name="disponibilites[]" id="mercredi" value="Mercredi">
      <label class="form-check-label" for="mercredi">Mercredi</label>
    </div>
  </div>
</div>

<!-- Section Photo -->
<div class="bloc-section">
  <div class="section-titre">Photo</div>
  <div class="mb-3">
    <label class="form-label" for="photo">Photo d'identité</label>
    <input type="file" class="form-control" id="photo" name="photo" accept="image/jpeg,image/png">
    <div class="form-text">JPG ou PNG, 2 Mo maximum.</div>
  </div>
</div>

<!-- Section Contact -->
<div class="bloc-section">
  <div class="section-titre">Contact</div>
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
      <input type="email" class="form-control" id="email" name="email" required>
    </div>
    <div class="col-md-6">
      <label class="form-label" for="telephone">Téléphone</label>
      <input type="text" class="form-control" id="telephone" name="telephone">
    </div>
  </div>
  <div class="mb-3">
    <label class="form-label" for="commentaire">Commentaire</label>
    <textarea class="form-control" id="commentaire" name="commentaire" rows="4"></textarea>
  </div>
</div>

<!-- Signature -->
<div class="bloc-section">
  <div class="section-titre">Signature</div>
  <div data-gvv-type="signature"
       data-gvv-name="signature_candidat"
       data-gvv-required="true">
    Signature du candidat
  </div>
</div>
```

**Contenu de `style.css` :**

```css
.forms-public-root .bloc-section {
  border: 1px solid #c9d4dd;
  border-radius: 8px;
  padding: 1rem 1.2rem;
  margin-bottom: 1rem;
  background: #f9fbfc;
}

.forms-public-root .section-titre {
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: #2d465a;
  border-left: 3px solid #3b6f8f;
  padding-left: 8px;
  margin-bottom: 0.85rem;
}
```

---

## À retenir

| ✅ Recommandé | ❌ À éviter |
|---|---|
| Classes Bootstrap 5 pour la grille et les champs | CSS dans `<head>` du HTML de page |
| CSS personnalisé dans le fichier `style.css` de l'archive | `@import url(...)` de polices dans `<head>` |
| Portée CSS avec `.forms-public-root` | Sélecteurs nus `input`, `label` sans portée |
| `name="champ[]"` pour les checkboxes | Balise `<form>` dans le HTML de page |
| `<div data-gvv-type="signature">` pour les signatures | Boutons `submit`/`reset` dans le HTML de page |
| Carte **Images** de la fiche formulaire pour un logo réutilisé | Image en base64 collée directement dans le HTML (alourdit le fichier) |
