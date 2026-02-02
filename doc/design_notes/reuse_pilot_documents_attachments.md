# Réutilisation du mécanisme d’attachements pour documents pilotes

## Contexte
Le projet GVV dispose déjà d’un mécanisme générique d’attachements avec compression automatique, utilisé pour les justificatifs comptables et d’autres usages. L’objectif est de réutiliser ce même mécanisme pour archiver des documents liés aux pilotes, en conservant la compression existante mais en stockant les fichiers dans des espaces distincts.

## Conclusion principale
La compression est centralisée et réutilisable via la librairie `File_compressor`. La séparation des espaces de stockage doit être gérée au niveau de la construction du chemin d’upload, pas dans la librairie de compression.

## Composants clés identifiés
- **Compression** : [application/libraries/File_compressor.php](application/libraries/File_compressor.php)
  - Images (JPEG/PNG/GIF/WebP) : redimensionnement + recompression GD, format d’origine conservé.
  - PDF : optimisation Ghostscript `/ebook`.
  - Autres types : non implémentés (retourne “Compression not implemented…”).
- **Configuration compression** : [application/config/attachments.php](application/config/attachments.php)
- **Mécanisme justificatifs existant** : contrôleur [application/controllers/attachments.php](application/controllers/attachments.php)
- **Upload avec compression en compta** : [application/controllers/compta.php](application/controllers/compta.php)
- **Upload photo pilote avec compression** : [application/controllers/membre.php](application/controllers/membre.php)
- **Gestion générique de fichiers** : [application/libraries/File_manager.php](application/libraries/File_manager.php)

## Options de réutilisation

### Option A — Réutiliser la table `attachments` et le CRUD existant
**Principe** : conserver la table `attachments` et les écrans actuels, mais isoler les documents pilotes dans un sous-espace dédié au niveau du chemin de stockage.

Exemple de structure :

```
uploads/attachments/<année>/<section>/pilotes/<pilot_id>/...
```

**Avantages**
- Réutilise l’UI et le CRUD existants pour les attachements.
- Compression déjà branchée via `File_compressor`.
- Pas de nouvelle table.

**Points d’attention**
- Le filtrage par année dans [application/models/attachments_model.php](application/models/attachments_model.php) suppose la présence de `/attachments/<year>/` dans le chemin. Il faut donc rester sous `uploads/attachments/<année>/`.

### Option B — Créer un espace dédié hors `attachments`
**Principe** : utiliser `File_manager` pour un espace autonome (ex. `uploads/pilotes_docs/…`), puis appliquer `File_compressor` après upload.

**Avantages**
- Séparation claire du stockage.
- Moins de dépendance au modèle `attachments`.

**Inconvénients**
- Nécessite un CRUD et/ou une table spécifique pour lister/relier les fichiers aux pilotes.

## Recommandation
Si l’objectif est d’avoir **un stockage séparé mais réutilisable avec le même outil de gestion et la même logique d’archivage**, l’option A est la plus rapide et la plus cohérente avec l’existant. Elle garantit la réutilisation maximale du mécanisme actuel (UI + DB + compression).

## Points à clarifier avant implémentation
- **Clé d’identification du pilote** : `membres.id` ou `membres.mlogin`.
- **Besoin d’un écran dédié** pour les documents pilotes ou simple réutilisation de l’écran “attachements”.
- **Structure finale souhaitée** du chemin de stockage (ex. inclure `pilotes/<id>` ou pas).
