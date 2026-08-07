# Design Notes — Stockage fichier du contenu des formulaires

Date de création : 2 juin 2026 — révisé le 6 août 2026 (voir PRD EF2-bis/EF2-ter) — révisé le 7 août 2026 (voir PRD EF2-quater : répertoire autonome, édition par archive uniquement, CSS partagé)

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
| CSS partagé entre formulaires | Un formulaire référence un CSS commun via `@import url(...)` en tête de son propre `style.css`, vers une URL absolue stable — pas de duplication ni de chemin relatif vers un autre répertoire de formulaire. Voir « CSS partagé entre formulaires » |
| Migration des formulaires existants | Procédure de conversion base → fichier, idempotente ; reste en place indéfiniment comme no-op une fois toutes les installations migrées (jamais supprimée — voir Sécurité) |

## Emplacement des fichiers

```
uploads/
└── formulaires/               ← web-writable (chmod +wx), protégé contre l'exécution de scripts
    ├── .commun/                       ← réservé (nom hors alphabet des codes de formulaire), CSS partagé — voir plus bas
    │   └── style.css
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

## CSS partagé entre formulaires

**Besoin** : plusieurs formulaires (ex. la déclinaison d'un même concours d'une année sur l'autre, ou une charte graphique de club commune à plusieurs formulaires) partagent une base de style, sans vouloir la dupliquer dans chaque `style.css`.

**Mécanisme retenu** : `@import url(...)` en tête du `style.css` d'un formulaire, vers une **URL absolue** — pas un chemin relatif vers le répertoire d'un autre formulaire. Deux raisons techniques, vérifiées dans le code :

1. Le CSS d'un formulaire n'est jamais chargé par le navigateur via un `<link>` externe sur la page publique réelle : `bs_show.php` l'injecte **inline** dans un `<style>` à même la page (`$this->forms_renderer->scope_css(...)`). Un `@import` à l'intérieur de ce `<style>` se résout par rapport à l'URL de la page publique (`/forms/{slug}`), pas par rapport à un chemin de fichier sur le serveur — un `../commun/style.css` relatif au répertoire de stockage ne pointerait donc nulle part d'utile une fois rendu.
2. `Forms_renderer::scope_css()` laisse déjà passer les instructions `@import`/`@charset` sans les toucher (`_scope_css_blocks()`, pass-through explicite) — le mécanisme fonctionne donc avec le code existant, sans modification de rendu. C'est d'ailleurs déjà le procédé utilisé par le widget signature pour charger sa police (`build_signature_assets()`, `@import url('https://fonts.googleapis.com/...')`).

**Emplacement du CSS partagé** : réservé dans `uploads/formulaires/.commun/style.css` (le préfixe `.` l'exclut des énumérations de répertoires de formulaires par `glob()`/`scandir()`, et n'est pas un caractère autorisé par la validation du `code`, donc pas de collision possible avec un vrai formulaire) — pas dans `assets/css/`, pour rester éditable depuis l'admin comme le reste du contenu formulaire (cohérent avec EF2-bis point 5 : aucun accès au système de fichiers serveur requis). Servi par une route publique dédiée (même principe que `forms_public/image/{code}/{filename}` pour les images de formulaire), référencée par chaque `style.css` sous une forme stable, ex. :

```css
@import url("https://gvv.net/index.php/forms_public/shared_css");
```

Une URL absolue avec schéma et domaine (pas juste un chemin racine `/...`) résout aussi correctement en aperçu local `file://` (réseau disponible) qu'en production — même logique que le lien CDN Bootstrap du tutoriel IA, qui n'a de sens qu'en dehors du fichier stocké par GVV.

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
- `.commun/` (CSS partagé) est un nom réservé, hors de l'alphabet validé pour un `code` de formulaire (`alpha_dash`, pas de `.`) : aucune collision possible avec un répertoire de formulaire, aucun formulaire ne peut se faire passer pour lui.
- Écriture réservée aux admins authentifiés autorisés sur le formulaire.
- La migration base → fichier ne doit jamais être supprimée du projet : le runner de migration GVV (`system/libraries/Migration.php`) s'arrête silencieusement à la première étape numérotée manquante lors d'une montée de version — une suppression bloquerait la mise à niveau de toute installation cliente n'ayant pas encore atteint ce numéro.
