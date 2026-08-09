# Design Notes — Stockage fichier du contenu des formulaires

Date de création : 2 juin 2026 — révisé le 6 août 2026 (voir PRD EF2-bis/EF2-ter) — révisé le 7 août 2026 (voir PRD EF2-quater : répertoire autonome, édition par archive uniquement, CSS partagé) — révisé le 9 août 2026 (convention de référence locale + réécriture au rendu pour les images et le CSS partagé, corrige l'URL absolue avec domaine du CSS partagé introduite le 7 août)

## Contexte

L'édition du contenu HTML d'une page de formulaire via le textarea de l'interface admin est insuffisante pour les formulaires complexes (mise en page document, CSS scoped, prévisualisation). Le stockage en base (`form_pages.content_html`, `forms.global_css`) pose en plus deux problèmes récurrents observés en exploitation :

- le filtre anti-XSS de CodeIgniter (`global_xss_filtering`) altère silencieusement tout contenu posté contenant une URI `data:` (ex. logo encodé en base64), imposant des contournements SQL directs pour toute modification de contenu concerné ;
- la table `form_fields`, dérivée du HTML par synchronisation automatique à la sauvegarde, peut diverger silencieusement du contenu réel si la synchronisation échoue partiellement (cf. incident migration 164 — ENUM `field_type` incomplet, deux types de widgets jamais synchronisés).

**Décision** : le fichier devient la source de vérité du contenu (HTML + CSS + images), remplaçant le stockage en base. La table `form_fields` est supprimée ; toute information sur les champs est dérivée du fichier à la demande. Voir PRD [EF2-bis](../prds/remplissage_formulaires_prd.md#ef2-bis--stockage-fichier-du-contenu-htmlcss) et [EF2-ter](../prds/remplissage_formulaires_prd.md#ef2-ter--migration-des-formulaires-existants).

> Ce document remplace la conception précédente (synchronisation bidirectionnelle par hash MD5, base de données restant source de vérité), jamais implémentée. Cette révision reste provisoire : une mise à jour finale interviendra après validation du refactoring, pour clore explicitement les options abandonnées.

## Décisions d'architecture

| Question | Décision |
|---|---|
| Source de vérité du contenu | Le fichier (HTML + CSS + images). La base ne garde que les métadonnées du formulaire (statut, section, slug, titre, options de soumission). |
| Table `form_fields` | Supprimée. Les champs sont déterminés par parsing à la demande du fichier HTML (affichage admin, validation de soumission, mapping `gvv_role`). |
| Emplacement des fichiers | `uploads/formulaires/{code}/` (un dossier par formulaire) |
| Contenu du dossier | `pageNN.html` (une par page), `style.css`, `images/` (logo, etc.) |
| Édition | Exclusivement par dépôt d'archive depuis l'admin web — pas de zone de saisie libre pour le HTML ou le CSS. Créer un formulaire dépose une nouvelle archive ; modifier son contenu dépose une archive de remplacement sur le formulaire existant (voir « Flux ») — aucun accès au système de fichiers serveur requis pour l'admin |
| Export / Import | Un formulaire complet s'exporte/s'importe comme une seule archive téléchargeable, qui est un miroir direct du répertoire de stockage (mêmes fichiers, mêmes noms, même enveloppe HTML) — pas de format d'archive distinct à maintenir en parallèle |
| Métadonnées du formulaire | Un fichier de métadonnées (`meta.json`) dans le répertoire de stockage porte le contenu/la configuration (titre, description, portée CSS, paramètres requis, options d'export...) ; il est écrit à chaque modification, pas seulement à l'export — voir « Métadonnées du formulaire » |
| Prévisualisation | Chaque `pageNN.html` stocké est un document HTML5 autonome (`<!DOCTYPE>/<html>/<head><link rel="stylesheet" href="style.css"></head><body>`) — ouvrable directement dans un navigateur standard (`file://`), CSS résolu, sans serveur applicatif. L'enveloppe est ajoutée à l'écriture (`Forms_file_storage::write_page()`) et retirée symétriquement à la lecture (`read_page()`) : le reste de l'application (rendu public, parsing des champs, PDF, `content_html` en base) continue de manipuler un simple fragment, inchangé. |
| Widgets dynamiques (signature, sous-formulaire, paiement) | Représentés dans le fichier statique par une image de substitution dédiée par type de widget ; remplacée par le composant réel au rendu serveur |
| Images propres à un formulaire | Référencées par un chemin relatif au dossier de stockage (`images/{fichier}`) dans le fichier stocké ; réécrites par GVV vers `forms_public/image/{code}/{fichier}` au moment du rendu — jamais de route applicative écrite dans le fichier lui-même. Voir « Ressources locales et partagées » |
| CSS et images partagés entre formulaires | Un formulaire référence des ressources communes (`.commun/style.css`, `.commun/images/{fichier}`) par un chemin relatif, réécrit par GVV en URL relative à la racine du site (jamais un domaine figé, pour rester portable entre installations) au moment du rendu. Voir « Ressources locales et partagées » |
| Migration des formulaires existants | Procédure de conversion base → fichier, idempotente ; reste en place indéfiniment comme no-op une fois toutes les installations migrées (jamais supprimée — voir Sécurité) |

## Emplacement des fichiers

```
uploads/
└── formulaires/               ← web-writable (chmod +wx), protégé contre l'exécution de scripts
    ├── .commun/                       ← réservé (nom hors alphabet des codes de formulaire), ressources partagées — voir plus bas
    │   ├── style.css
    │   └── images/
    │       └── logo-club.jpg
    ├── inscription-membre/
    │   ├── meta.json
    │   ├── page01.html
    │   ├── style.css
    │   └── images/
    │       └── logo.jpg
    └── attestation-formation-procedures/
        ├── meta.json
        ├── page01.html
        └── style.css
```

## Métadonnées du formulaire (`meta.json`)

Le contenu/la configuration du formulaire — tout ce qui ne relève pas du statut opérationnel — est porté par un fichier `meta.json` à la racine du répertoire du formulaire, au même titre que les pages et le CSS :

```json
{
  "title": "Inscription au concours régional Hauts de France 2026",
  "description": "Formulaire d'inscription au concours régional de vol à voile...",
  "css_scope": "",
  "required_params": "none",
  "allow_upload_response": false,
  "handler_class": null,
  "target_url": null,
  "target_label": null,
  "pages": [
    { "page_number": 1, "title": "Informations pilote" },
    { "page_number": 2, "title": "Informations planeur" },
    { "page_number": 3, "title": "Engagement" }
  ]
}
```

Ce fichier est écrit à chaque modification depuis l'admin (pas seulement à l'export) : le répertoire reste auto-descriptif en permanence, cohérent avec le principe déjà en place pour `pageNN.html`/`style.css`.

Restent hors de ce fichier, pilotés uniquement depuis l'admin (jamais modifiés par un dépôt d'archive) : `code` (nom du répertoire lui-même), `status`, `public_slug`, la section de rattachement, les identifiants et dates d'audit. Cette limite reprend celle déjà appliquée par `form_restore()` aujourd'hui (une restauration ne touche ni le code, ni le statut, ni le lien public) — elle évite qu'un rechargement de contenu dépublie ou déplace un formulaire déjà partagé publiquement.

## Ressources locales et partagées : convention de référence et réécriture

### Constat

En ouvrant un formulaire réel (`attestation_de_fin_de_formation_spl-planeur`) directement en `file://`, les logos sont invisibles, et le repère visuel du widget signature ne l'est qu'accidentellement (uniquement si le serveur local sert le dépôt GVV entier depuis sa racine). Une seule cause pour les deux : le fichier stocké référence ces images par une **route applicative GVV**, jamais par un chemin de fichier :

```html
<img src="/forms_public/image/attestation_de_fin_de_formation_spl-planeur/cnvv-logo.jpg">
<img src="/assets/images/forms-widgets/signature-placeholder.svg">
```

Le second cas concerne **tous** les formulaires existants (le repère visuel des widgets signature/sous-formulaire), pas seulement celui-ci — voir « Convention des images de substitution » ci-dessus, à corriger avec la même règle.

### Principe retenu : le fichier stocké reste relatif, GVV réécrit au rendu

Aucune route applicative n'est jamais écrite dans le fichier stocké lui-même — seulement des chemins relatifs, résolus par GVV au moment de préparer l'affichage (public, aperçu admin, export PDF), jamais avant :

| Catégorie | Convention dans le fichier stocké | Réécrite par GVV vers |
|---|---|---|
| Image propre au formulaire | `images/{fichier}` (relatif au dossier du formulaire) | `forms_public/image/{code}/{fichier}` |
| CSS partagé entre formulaires | `.commun/style.css` (en `@import`, en tête du `style.css` du formulaire) | `forms_public/shared_css` |
| Image partagée entre formulaires | `.commun/images/{fichier}` | `forms_public/shared_image/{fichier}` |

C'est ce qui rend le dossier d'un formulaire directement ouvrable et modifiable — `file://`, éditeur de texte, IDE — avant même tout contact avec GVV : le contenu déposé et le contenu prévisualisé en local sont un seul et même fichier, sans étape de traduction manuelle par l'admin.

### Pourquoi une URL relative à la racine du site (`/...`), jamais un domaine figé

La réécriture produit une URL commençant par `/`, **sans schéma ni domaine** (`/forms_public/shared_css`, pas `https://gvv.net/forms_public/shared_css`). Un navigateur résout une telle URL par rapport à l'**origine de la page qui la charge** — la même règle fait qu'un formulaire déposé sur l'installation `club-a.fr` charge son CSS partagé depuis `club-a.fr`, et le même formulaire réimporté tel quel sur `club-b.org` le charge depuis `club-b.org`, sans rien à corriger dans le fichier stocké ou dans l'archive échangée entre les deux installations. Une URL absolue avec domaine figé casserait cette portabilité — c'est pourtant ce que proposait la révision précédente de ce document (7 août 2026), corrigé ici : le formulaire est conçu comme un répertoire autonome, exportable/importable entre installations (voir PRD EF2-quater) ; rien dans son contenu ne doit dépendre du domaine d'une installation particulière.

(Le lien CDN Bootstrap du tutoriel IA reste une exception légitime : c'est une ressource **externe** à GVV — le problème de portabilité entre installations GVV ne le concerne pas.)

### Pourquoi le rendu ne peut pas se contenter d'un `<link>` externe classique

Pour le CSS, `bs_show.php` injecte le contenu **inline** dans un `<style>` (pas via un `<link>`) — mais ce n'est pas ce choix de délivrance qui protège un formulaire de polluer visuellement le reste de GVV, ou l'inverse : un `<link>` externe applique tout aussi bien ses règles à l'ensemble du document. Le formulaire est rendu comme un **fragment à l'intérieur de la même page** que le header/menu GVV, jamais comme une page isolée (voir [Design isolation CSS](formulaires_css_isolation_design.md), qui documente un incident réel de pollution croisée). C'est `Forms_renderer::scope_css()` (préfixage de chaque sélecteur par `.forms-public-root`) qui isole le CSS d'un formulaire, indépendamment du mode de délivrance.

L'inline est la conséquence d'une autre contrainte : le CSS scopé est recalculé à **chaque affichage** (le `css_scope` du formulaire peut être modifié indépendamment du contenu CSS) — ce n'est jamais un fichier statique servable tel quel. GVV l'assemble dans la même passe de rendu qui gère déjà les widgets et le script de validation, plutôt que de créer une route dédiée pour un fragment de texte aussi petit.

Conséquence directe pour le CSS partagé : une fois le CSS inline, un `@import` qu'il contient est résolu par le navigateur par rapport à l'URL de la **page publique** (`/forms/{slug}`), jamais par rapport à un chemin de fichier sur le serveur — un chemin relatif type `../.commun/style.css` ne pointerait donc nulle part d'utile une fois rendu. D'où la nécessité d'une réécriture vers une URL racine plutôt qu'un simple passage tel quel du chemin stocké.

### Prévisualisation locale hors GVV : convention côté poste de l'admin, hors périmètre GVV

Pour que `.commun/style.css` et `.commun/images/{fichier}` résolvent aussi dans un aperçu `file://` local, avant tout dépôt dans GVV, l'admin maintient de son côté un dossier ou un lien symbolique `.commun` à côté du dossier qu'il édite, avec sa propre copie des ressources partagées. C'est une **convention recommandée à l'admin, pas un mécanisme fourni ou vérifié par GVV** : GVV n'a besoin de rien connaître de cette organisation locale, il ne voit jamais que le fichier final déposé, avec la référence relative `.commun/...` telle quelle — voir « Décision différée » ci-dessous pour la variante côté serveur écartée.

### Décision différée : lien symbolique côté serveur GVV

**Alternative envisagée** (discussion août 2026) : GVV créerait lui-même un lien symbolique `{code}/.commun -> ../.commun` dans le dossier de chaque formulaire importé, pour que la convention `.commun/...` résolve aussi par accès fichier direct côté serveur, en plus de la réécriture au rendu.

**Écartée pour l'instant** : la réécriture au rendu suffit à couvrir tous les besoins réels identifiés (page publique, aperçu admin, export PDF) sans dépendre d'un lien symbolique — le seul gain resterait la fidélité d'un accès fichier direct côté serveur, un cas d'usage non identifié à ce jour. En contrepartie, un lien symbolique introduit une classe de bug réelle à corriger dans plusieurs méthodes existantes : `Forms_file_storage::delete_form_dir()` et `copy_form_dir()` itèrent aujourd'hui sur le contenu du dossier d'un formulaire sans distinguer un lien d'un fichier réel — un `.commun` mal traité par une suppression (`rmdir()` au lieu d'`unlink()` sur le lien lui-même) supprimerait le **vrai dossier partagé**, avec un impact sur tous les autres formulaires de l'installation, pas seulement celui supprimé. À reconsidérer seulement si un besoin concret d'accès fichier direct apparaît.

### Export / import et ressources partagées

- **Export** (`form_backup()`) : les ressources de `.commun/` réellement présentes sur le serveur sont copiées telles quelles dans l'archive (sous un sous-dossier `.commun/`), pour que l'archive reste consultable et fidèle hors de GVV (autre poste, réimport ultérieur, partage à un collègue) — même logique que la copie du contenu propre au formulaire. Simplification retenue : copier tout `.commun/` plutôt que de détecter précisément les fichiers réellement référencés — `.commun/` reste petit par nature (un CSS, quelques logos, pas une médiathèque), pas de justification à construire un analyseur de dépendances pour un gain marginal.
- **Import** (`form_import_zip()`/`form_restore()`) : le contenu `.commun/` livré dans une archive déposée n'est **jamais** écrit dans le `uploads/formulaires/.commun/` réel de l'installation cible — cette ressource est partagée par tous les formulaires de l'installation ; un import ne doit jamais l'écraser avec une copie potentiellement obsolète venue d'un seul formulaire. Le `.commun/` livré dans une archive ne sert qu'à la consultation hors GVV.

### Emplacement et service

Deux routes publiques dédiées, sur le même principe que `forms_public/image/{code}/{fichier}` :

- `forms_public/shared_css` → sert `uploads/formulaires/.commun/style.css`
- `forms_public/shared_image/{fichier}` → sert `uploads/formulaires/.commun/images/{fichier}`

```css
/* Dans le style.css d'un formulaire */
@import url("/forms_public/shared_css");
```

```html
<!-- Dans une page d'un formulaire -->
<img src="images/logo-formulaire.jpg" alt="Logo du concours">
<img src=".commun/images/logo-club.jpg" alt="Logo du club">
```

## Convention des images de substitution

Un widget dynamique reste déclaré par les mêmes attributs `data-gvv-type` / `data-gvv-name` / `data-gvv-required` qu'aujourd'hui, mais son contenu statique inclut une image reconnaissable en plus du texte (le texte reste le libellé affiché par le widget rendu — voir plus bas) :

```html
<div data-gvv-type="signature" data-gvv-name="signature_membre" data-gvv-required="true">
  <img src="/assets/images/forms-widgets/signature-placeholder.svg" alt="Zone de signature">
  Signature du membre
</div>
```

Une image SVG par type de widget est fournie sous `assets/images/forms-widgets/` (convention alignée sur `assets/images/` déjà utilisé par le reste de GVV) : `signature-placeholder.svg`, `subform-placeholder.svg`. SVG plutôt que PNG : fichier texte auto-descriptif, redimensionnable sans perte, pas d'outillage binaire pour le modifier.

Au rendu serveur (`Forms_renderer`), ce nœud est repéré par ses attributs `data-gvv-*` — comme aujourd'hui — et son contenu (image + texte) remplacé par le composant fonctionnel réel (canvas de signature, lien de sous-formulaire, etc.) ; le texte visible du `<div>` devient le libellé du composant (`strip_tags()` sur le contenu retire l'`<img>` et ne garde que le texte). L'image de substitution n'est qu'un repère visuel pour la prévisualisation statique ; la logique de détection des widgets ne change pas.

> `assets/images/forms-widgets/` fait partie du code GVV lui-même, pas du contenu d'un formulaire ni de `uploads/formulaires/` : la règle « chemin relatif réécrit par GVV » ci-dessous (« Ressources locales et partagées ») ne s'y applique pas. Le chemin racine `/assets/images/forms-widgets/...` reste tel quel — déjà portable entre installations (aucun domaine figé), il n'échoue qu'en aperçu `file://` pur, sur une image de toute façon jetée dès que GVV rend réellement le widget. Compromis jugé acceptable plutôt que de dupliquer ces SVG dans chaque formulaire ou d'inventer une convention dédiée pour un repère purement cosmétique.

## Flux

### Édition (admin web ou édition directe du fichier)

1. **Création** : l'admin dépose une archive (pages HTML + `style.css` + `meta.json` + `images/`, éventuellement produite avec l'aide d'un assistant IA) — GVV crée le répertoire, en dérive la ligne d'index en base, et publie le contenu tel quel. Il n'y a plus de formulaire de création à base de champs HTML/CSS en texte libre.
2. **Modification du contenu** : l'admin dépose une nouvelle archive sur le formulaire existant, qui remplace intégralement pages/CSS/images/métadonnées — sans toucher au code, au statut, au lien public ni à la section (même garde-fou qu'aujourd'hui pour `form_restore()`). C'est le chemin normal d'itération, pas une opération de secours occasionnelle.
3. **Édition directe du fichier** reste possible et immédiatement effective (le fichier est la source de vérité) pour un admin ayant accès au serveur — sans passer par une archive, ex. corriger une coquille avec un éditeur de texte sur le serveur.
4. Le rendu public (`forms_public`) et l'admin (`forms_admin` : liste des pages, champs détectés, réponses, export PDF/ZIP, prévisualisation CSS) lisent tous deux le fichier en priorité (repli sur la base seulement si le fichier est absent), afin qu'une édition directe du fichier soit immédiatement visible partout — pas seulement côté public. Chaque contrôleur applique ce principe via ses propres méthodes d'overlay (`_overlay_pages_from_file()` / `_overlay_css_from_file()`), une par contrôleur ; la base reste un mirroir best-effort qui peut rester en retard sans que ça affecte le comportement observable.

### Migration des formulaires existants

1. Pour chaque formulaire dont le contenu est encore uniquement en base (`content_html`/`global_css` non vides, fichier absent), écrire le fichier correspondant dans `uploads/formulaires/{code}/`.
2. Idempotente : un formulaire déjà migré (fichier déjà présent) est ignoré.
3. Reste disponible indéfiniment comme no-op une fois toutes les installations migrées — voir PRD EF2-ter pour la justification (numérotation séquentielle des migrations, installations clientes à des niveaux de migration différents).

*Diagramme à refaire pour cette révision (l'ancien diagramme, `diagrams/formulaires_sync_fichiers.png`, décrit la synchronisation bidirectionnelle par hash désormais abandonnée) — prévu lors de la mise à jour finale après validation du refactoring.*

## Ce que ça ne fait pas

- Pas de synchronisation bidirectionnelle fichier ↔ base : le fichier est la seule source de vérité du contenu, la base ne le duplique plus.
- Pas de rendu public directement depuis un serveur de fichiers statique (Apache/Nginx) : le rendu passe toujours par `forms_public`/`Forms_renderer` pour l'injection des widgets dynamiques ; seule la copie locale utilisée pour l'édition/prévisualisation s'ouvre en `file://`.
- Pas de versioning intégré à GVV des fichiers (Git peut jouer ce rôle si les fichiers sont aussi conservés hors serveur de production).

## Sécurité

- `uploads/formulaires/` reste protégé contre l'exécution de scripts : aucun fichier déposé ne doit pouvoir être interprété comme du PHP.
- Le nom de dossier est dérivé du `code` du formulaire, jamais d'une entrée utilisateur libre → pas de path traversal.
- `.commun/` (ressources partagées) est un nom réservé, hors de l'alphabet validé pour un `code` de formulaire (`alpha_dash`, pas de `.`) : aucune collision possible avec un répertoire de formulaire, aucun formulaire ne peut se faire passer pour lui.
- Le dépôt d'une archive (`form_import_zip()`/`form_restore()`) n'écrit jamais dans `uploads/formulaires/.commun/` : le `.commun/` éventuellement présent dans une archive est ignoré à l'import, pour qu'un formulaire ne puisse pas modifier — par erreur ou intentionnellement — une ressource partagée par tous les autres formulaires de l'installation.
- Écriture réservée aux admins authentifiés autorisés sur le formulaire.
- La migration base → fichier ne doit jamais être supprimée du projet : le runner de migration GVV (`system/libraries/Migration.php`) s'arrête silencieusement à la première étape numérotée manquante lors d'une montée de version — une suppression bloquerait la mise à niveau de toute installation cliente n'ayant pas encore atteint ce numéro.
