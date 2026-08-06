# Design Notes — Stockage fichier du contenu des formulaires

Date de création : 2 juin 2026 — révisé le 6 août 2026 (voir PRD EF2-bis/EF2-ter)

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
| Contenu du dossier | `index.html`, `style.css`, images associées (logo, etc.) |
| Édition | Depuis l'admin web : dépôt d'un fichier ou d'une archive (HTML + CSS + images) — aucun accès au système de fichiers serveur requis pour l'admin |
| Export / Import | Un formulaire complet s'exporte/s'importe comme une seule archive téléchargeable |
| Prévisualisation | Le fichier exporté s'ouvre directement dans un navigateur standard (`file://`), sans serveur applicatif |
| Widgets dynamiques (signature, sous-formulaire, paiement) | Représentés dans le fichier statique par une image de substitution dédiée par type de widget ; remplacée par le composant réel au rendu serveur |
| Migration des formulaires existants | Procédure de conversion base → fichier, idempotente ; reste en place indéfiniment comme no-op une fois toutes les installations migrées (jamais supprimée — voir Sécurité) |

## Emplacement des fichiers

```
uploads/
└── formulaires/               ← web-writable (chmod +wx), protégé contre l'exécution de scripts
    ├── inscription-membre/
    │   ├── index.html
    │   ├── style.css
    │   └── logo.jpg
    └── attestation-formation-procedures/
        ├── index.html
        └── style.css
```

## Convention des images de substitution

Un widget dynamique reste déclaré par les mêmes attributs `data-gvv-type` / `data-gvv-name` / `data-gvv-required` qu'aujourd'hui, mais son contenu statique est une image reconnaissable plutôt qu'un simple texte :

```html
<div data-gvv-type="signature" data-gvv-name="signature_membre" data-gvv-required="true">
  <img src="/assets/forms-widgets/signature-placeholder.png" alt="Zone de signature">
</div>
```

Au rendu serveur (`Forms_renderer`), ce nœud est repéré par ses attributs `data-gvv-*` — comme aujourd'hui — et son contenu remplacé par le composant fonctionnel réel (canvas de signature, lien de sous-formulaire, etc.). L'image de substitution n'est qu'un repère visuel pour la prévisualisation statique ; la logique de détection des widgets ne change pas.

## Flux

### Édition (admin web)

1. L'admin dépose un fichier HTML/CSS, ou une archive complète, depuis l'interface web.
2. GVV écrit le contenu dans `uploads/formulaires/{code}/`.
3. Le rendu public (`forms_public`) lit ce fichier et applique l'injection des widgets dynamiques, exactement comme il lisait `content_html` en base auparavant.

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
- Écriture réservée aux admins authentifiés autorisés sur le formulaire.
- La migration base → fichier ne doit jamais être supprimée du projet : le runner de migration GVV (`system/libraries/Migration.php`) s'arrête silencieusement à la première étape numérotée manquante lors d'une montée de version — une suppression bloquerait la mise à niveau de toute installation cliente n'ayant pas encore atteint ce numéro.
