# Plan d'implémentation — Remplissage Formulaires

Date : 30 mai 2026

## Références
- PRD : [doc/prds/remplissage_formulaires_prd.md](../prds/remplissage_formulaires_prd.md)
- Design : [doc/design_notes/remplissage_formulaires_design.md](../design_notes/remplissage_formulaires_design.md)
- Dépendance : archivage documentaire (PRD archivage_documentaire)

## Objectif
Mettre en place un module de formulaires HTML natifs dans GVV (inspiré Google Forms) avec lien public de réponse, gestion des réponses/fichiers, pré-remplissage depuis données GVV, import PDF -> HTML, génération PDF imprimable et archivage vers `archived_documents`.

## Hypothèses
- CodeIgniter 2.x reste la base d'implémentation.
- Le stockage fichiers GVV existant est utilisé (`uploads/`).
- Les workflows GVV pourront consommer des URL publiques de formulaires.
- Pas d'import PDF -> HTML, on peut demander la conversion à un outil d'IA.
- La première mise en production vise un socle autonome de formulaires HTML, sans pré-remplissage GVV.
- Évolution probable: ajouter une surcouche minimale d'orchestration (validation des documents + acceptation/rejet global) au-dessus des formulaires, plutôt qu'un moteur de procédures complet.

## Décisions différées

### Simplification : supprimer le mécanisme A au profit du mécanisme B seul

**Contexte** : deux mécanismes de pré-remplissage coexistent actuellement :
- **Mécanisme A** : attributs `data-gvv-source` dans le HTML de la page ; résolution serveur depuis les tables GVV.
- **Mécanisme B** : paramètres URL directs (`?champ=valeur&lock[]=champ`) ; stockage session par slug.

**Conclusion de la discussion (juillet 2026)** : le mécanisme A pourrait être supprimé sans perte fonctionnelle significative, car :

1. **Données sensibles** — les deux cas d'usage réels ne posent pas de problème de confidentialité dans les URLs :
   - Formulaires standalone/semi-privés : pré-remplissage minimal, question sans objet.
   - Formulaires en workflow GVV : l'opérateur est connecté et admin/instructeur, il a déjà accès aux données.

2. **Cas `date.today`, `config.*`, `club.*`** — pour les formulaires en workflow, il y a toujours un contrôleur de génération qui peut résoudre et injecter ces valeurs dans l'URL B. Pour les formulaires standalone, ces champs ne sont généralement pas pré-remplis.

3. **Complexité utilisateur** — deux mécanismes = double complexité pour des utilisateurs non-techniques. Un seul mécanisme (B, visible et prévisible dans l'URL) simplifie la compréhension, la mise en œuvre et le débogage.

**Décision** : différée à la fin du Lot 6, quand les workflows concrets (`briefing_passager_ulm`) seront opérationnels et permettront de valider que le mécanisme B couvre tous les cas réels avant de retirer A.

**Impact si décision prise** :
- Supprimer `_apply_gvv_prefill()`, `_collect_locked_gvv_fields()`, `_resolve_gvv_source()` dans `forms_public.php`.
- Les contrôleurs de génération (`generate_link`, page de génération admin) construisent des URLs B complètes en résolvant eux-mêmes membre/instructeur/date/config.
- Mettre à jour Lot 8 documentation (ne décrire que le mécanisme B).

---

## Tâches à réaliser

### Séquencement opérationnel (suite immédiate)

- [x] Étape 1 : ajouter `form_fields_model.php` (CRUD minimal + ordre + validations de base).
- [x] Étape 2 : ajouter `form_submissions_model.php` (création soumission + stockage valeurs).
- [x] Étape 3 : compléter `forms_admin` pour édition et suppression (dupliquer ensuite).
- [x] Étape 4 : créer le contrôleur public de consultation/soumission multi-pages (premier slice).
- [x] Étape 5 : brancher la validation serveur centralisée (règles par type de champ).


### Lot 1 — Socle formulaires autonome

- [x] Migration de début de lot : créer `116_forms_core.php` avec les tables minimales du socle :
  - `forms` (métadonnées, statut, slug/lien public, css_scope, rattachement section optionnel)
  - `form_pages` (pages HTML ordonnées)
  - `form_fields` (définition des champs et validations)
  - `form_submissions` (soumissions)
  - `form_submission_values` (valeurs par champ)
- [x] Ajouter index, contraintes d'unicité, clés étrangères, mise à jour `application/config/migration.php`.
- [x] Vérifier manuellement la disponibilité des tables requises après migration (`forms`, `form_pages`, `form_fields`, `form_submissions`, `form_submission_values`) et le `club` nullable.
- [x] Créer `form_submissions_model.php`.
- [x] Créer modèles `forms_model.php`, `form_pages_model.php`.
- [x] Compléter les modèles du socle (`form_fields_model.php`, `form_submissions_model.php`).
- [x] Compléter le CRUD admin : modifier, supprimer, dupliquer.
- [x] Implémenter le premier slice CRUD admin : lister, créer, publier.
- [x] Adapter le modèle `forms` pour le rattachement section optionnel (section ou global).
- [x] Implémenter les règles de listing section :
  - sans section active : tous les formulaires + affichage de la section de rattachement,
  - avec section active : formulaires de la section active + formulaires globaux.
- [x] Créer le contrôleur public pour affichage multi-pages et soumission anonyme (premier slice).
- [x] Implémenter moteur de rendu HTML et validation serveur centralisée (premier moteur opérationnel: validation centralisée, normalisation de rendu, CSS global appliqué).
- [x] Ajouter l'édition des pages puis l'import/export de page texte/HTML.
- [x] Ajouter CSS global formulaire et preview admin associée.
- [x] Auto-synchronisation des `form_fields` depuis le HTML natif (DOMDocument) : parsing à la sauvegarde/import de page, validation unicité des noms inter-pages, blocage en cas de conflit.
- [x] Soumission publique par nom de champ HTML natif (plus par `field_N`).
- [x] Rendu public : HTML natif inclus dans le `<form>` GVV, balises `<form>` du HTML stripées.
- [x] Vue admin des champs (`forms_admin/fields`) : liste read-only des champs auto-détectés, avec lien depuis la vue pages.

### Lot 2 — Réponses et fichiers

- [x] Migration de début de lot : créer `119_forms_files.php` avec les tables complémentaires :
  - `form_submission_files` (fichiers uploadés)
  - colonnes/flags complémentaires de suivi de soumission si nécessaire
- [x] Ajouter index, contraintes, mise à jour migration et test up/down.
- [x] Étendre les modèles de soumission pour supporter les fichiers.
- [x] Implémenter upload de fichiers sur soumission (type, taille, nommage sûr).
- [x] Implémenter visualisation admin des réponses et preview image/PDF inline.
- [x] Gérer téléchargement sécurisé et politique de rétention initiale.
- [x] Ajouter messages de confirmation explicites côté utilisateur.

### Lot 2-bis — Stockage fichier du contenu des formulaires

Remplace la conception précédente (sync bidirectionnelle par hash, base restant source de vérité), jamais implémentée — voir [Design stockage fichier](../design_notes/formulaires_sync_fichiers_design.md) et PRD EF2-bis/EF2-ter.

- [x] Créer le répertoire `uploads/formulaires/{code}/` par formulaire (protection contre l'exécution de scripts).
- [x] Adapter `forms_admin.php` : lecture/écriture du contenu HTML/CSS depuis/vers le fichier au lieu de `form_pages.content_html` / `forms.global_css`.
- [x] Ajouter le dépôt et le téléchargement d'un formulaire complet en une seule archive (HTML + CSS + images).
- [x] Supprimer la table `form_fields` et son mécanisme de synchronisation (`extract_html_fields` / `sync_fields_from_html`) ; remplacer par un parsing à la demande (vue admin des champs, validation de soumission, mapping `gvv_role`).
- [x] Ajouter la convention d'images de substitution pour les widgets dynamiques (signature, sous-formulaire ; paiement pas encore un type de widget implémenté, à traiter si/quand il existe) : détection dans `Forms_renderer` et remplacement par le composant réel au rendu.
- [x] Vérifier l'ouverture statique d'un formulaire exporté dans un navigateur nu (sans serveur), CSS résolu. `Forms_file_storage::write_page()` enveloppe désormais chaque page dans un document HTML5 autonome (`<!DOCTYPE>/<html>/<head><link rel="stylesheet" href="style.css"></head><body>...`) écrit directement sur disque ; `read_page()` désenveloppe symétriquement pour que le reste de l'application (rendu public, parsing des champs, PDF, `content_html` en base) continue de manipuler un simple fragment, inchangé. Les 8 formulaires existants ont été réenveloppés. L'écart entre l'archive ZIP exportée et le format réellement stocké a été résorbé au Lot 2-ter (`form_backup()` zippe désormais directement le répertoire de stockage).
- [x] Migration `1xx_forms_file_storage.php` (numéro définitif à la création) : convertit vers le nouveau stockage fichier tous les formulaires existants encore uniquement en base, de façon idempotente (no-op si le fichier existe déjà). Cette migration n'est jamais supprimée du projet : elle reste un no-op permanent une fois toutes les installations migrées (EF2-ter).
- [x] Test PHPUnit : `FormsFieldParserTest` (parsing à la demande — 12 tests), `FormsFileStorageTest` (lecture/écriture fichier, enveloppe HTML5, images, copy/rename/delete — 23 tests), `FormsFileStorageMigrationTest` (idempotence de la migration 165 — 5 tests). Migration 166 (non idempotente par nature, cutover unique) n'a pas de test de migration dédié : son effet est déjà exercé par le reste de la suite MySQL (tous les tests écrivant/lisant `field_name`/`widget_name`).
- [x] Test Playwright smoke : `forms-file-storage-smoke.spec.js` — création, édition (page_store), ré-édition (page_update, vérifie que le fichier est bien réécrit), publication, rendu public depuis le fichier, suppression avec vérification que `uploads/formulaires/{code}/` disparaît intégralement (régression `.htaccess`/`rmdir()`).
- [ ] Documentation utilisateur (`doc/users/fr/13_formulaires.md`) : mise à jour de « Gérer les pages » et « Convertir un formulaire PDF existant » (nouveau mode d'édition par fichier/archive), nouvelle section décrivant l'export/import d'un formulaire complet et la convention des images de substitution pour les widgets.

### Lot 2-ter — Formulaire = répertoire autonome (archive comme unique mode d'édition) et CSS partagé

Poursuit le Lot 2-bis : jusqu'ici le fichier est la source de vérité du contenu, mais l'admin édite encore ce contenu via des zones de saisie libre (page, CSS) et l'archive exportée diverge du format réellement stocké sur disque. Ce lot ferme ces deux écarts et ajoute le CSS partagé — voir [Design stockage fichier](../design_notes/formulaires_sync_fichiers_design.md) (sections « Métadonnées du formulaire » et « CSS partagé entre formulaires ») et PRD EF2-quater.

- [x] Introduire `meta.json` dans `uploads/formulaires/{code}/` (titre, description, css_scope, paramètres requis, options d'export, liste des pages avec titres) ; écrit à chaque `store()`/`update()` du formulaire, pas seulement à l'export.
- [x] Aligner l'archive ZIP sur le répertoire réel : `form_backup()` zippe directement le répertoire de stockage (déjà enveloppé par `write_page()`), `form_import_zip()`/`form_restore()` installent l'archive telle quelle via `Forms_file_storage::replace_all_from_dir()`, plus `meta.json` à la racine de l'archive — suppression du format d'archive spécifique précédent (`pages/NN.html` en sous-dossier, fragments nus).
- [x] Retirer les zones de saisie libre HTML/CSS de l'admin (`bs_form.php` : textarea `global_css` ; suppression de `page_create`/`page_store`/`page_edit`/`page_update`/`bs_page_form.php` et de `page_import()`) ; conservé uniquement `form_import_html()` (dépôt d'un fichier HTML unique, page 1 d'un nouveau formulaire) et le dépôt d'archive (création via `form_import_zip()`, remplacement via `form_restore()`).
- [x] `form_restore()` est le chemin de modification normal : carte "Contenu du formulaire (archive)" sur la fiche d'édition, CTA et aide reformulés en ce sens, sans changer la garantie déjà en place (code/statut/lien public non modifiés par une restauration).
- [x] CSS partagé : réservé `uploads/formulaires/.commun/style.css` (`Forms_file_storage::write_shared_css()`/`read_shared_css()`, hors `safe_code()`/`form_dir()` — pas de collision possible avec un code de formulaire), route publique dédiée `forms_public/shared_css`, convention `@import url("https://.../forms_public/shared_css")` documentée en tête d'un `style.css` de formulaire.
- [x] Contrôleur (`forms_admin.php`) adapté : la table `forms` ne reste que l'index (identifiant stable, statut, section, slug, timestamps/audit) — les champs de contenu/configuration vivent dans `meta.json` (`_sync_meta_file()`), la base en garde une copie best-effort comme avant pour `content_html`/`global_css`.
- [x] Test PHPUnit : lecture/écriture de `meta.json`, `page_numbers()`, symétrie archive ↔ répertoire (`replace_all_from_dir()`), CSS partagé — 11 tests ajoutés à `FormsFileStorageTest` (34 tests au total) ; suite MySQL forms (43 tests) et suite unitaire complète (537 tests) toujours au vert.
- [x] Test Playwright smoke : `forms-file-storage-smoke.spec.js` réécrit — création d'un formulaire par dépôt d'archive (`form_import_zip`, plus de formulaire de création par champs HTML/CSS), modification de contenu par `form_restore()`, rendu public avec CSS partagé chargé (vérifié via la route `forms_public/shared_css`).
- [x] Documentation utilisateur (`doc/users/fr/13_formulaires.md`) : instructions de collage HTML/CSS remplacées par le flux d'archive (nouvelle section « Gérer le contenu d'un formulaire (archive) »), CSS partagé documenté. [Tutoriel IA](../users/fr/13_formulaires_tutoriel.md) mis à jour : le flux de copier-coller par page devient un flux d'assemblage + dépôt d'archive unique à chaque étape.

### Lot 2-quater — Convention de référence des images et du CSS partagé (chemin relatif + réécriture au rendu)

Constat (9 août 2026) : un formulaire réel ouvert en `file://` a ses logos et le repère du widget signature invisibles — le fichier stocké référence ces images par une route applicative GVV (`/forms_public/image/{code}/{fichier}`, `/assets/images/forms-widgets/*.svg`), qui n'a de sens que servie par GVV. Corrige aussi la convention de CSS partagé introduite au Lot 2-ter (URL absolue avec domaine, non portable entre installations). Voir [Design stockage fichier](../design_notes/formulaires_sync_fichiers_design.md#ressources-locales-et-partagées--convention-de-référence-et-réécriture) et PRD EF2-quater.

- [x] `Forms_renderer::rewrite_local_image_urls($html, $code)` : réécrit tout `<img src="...">` relatif (`images/{fichier}`, ne commençant ni par un schéma, `//`, `/` ni `data:`) vers `site_url('forms_public/image/{code}/{fichier}')`, et tout `<img src=".commun/images/{fichier}">` vers `site_url('forms_public/shared_image/{fichier}')`.
- [x] `Forms_renderer::rewrite_shared_css_import($css)` : détecte `.commun/style.css` (dans un `@import url(...)` en tête du `style.css` d'un formulaire) et le réécrit vers `site_url('forms_public/shared_css')`, avant l'injection inline par `scope_css()`. Précision par rapport à la formulation initiale : la sortie est l'URL `site_url()` complète de l'installation courante (schéma+domaine), pas une forme strictement root-relative — la portabilité entre installations est garantie autrement, par le fait que cette URL n'est jamais réécrite dans le fichier stocké (recalculée à chaque lecture, avec le `site_url()` propre à l'installation qui sert la requête), voir design doc.
- [x] Réécriture centralisée dans les méthodes d'overlay (`_overlay_page_from_file()`/`_overlay_css_from_file()`, dans `forms_admin.php` et `forms_public.php`) plutôt que dupliquée par consommateur — `css_preview()`, `submission_view()`, `submission_pdf()` en bénéficient automatiquement puisqu'ils passent tous par `load_form_or_redirect()`/`load_page_for_form_or_redirect()`. `_embed_local_images_as_base64()` (PDF) complété avec un cas dédié pour `forms_public/shared_image/{fichier}`, cohérent avec la forme déjà réécrite.
- [x] `Forms_file_storage` : `shared_dir()`, `shared_images_dir()`, `shared_image_path()`, `read_shared_image()`/`write_shared_image()`, `list_shared_images()`. `.commun/` protégé par un `.htaccess` deny-all (gap corrigé : absent depuis l'introduction de `write_shared_css()` au Lot 2-ter).
- [x] `forms_public::shared_image($filename)` (miroir de `shared_css()`/`image()`).
- [x] `form_backup()` : deuxième invocation `zip -r` (depuis `uploads/formulaires/`) ajoutant `.commun/` (hors `.htaccess`) à l'archive déjà construite pour le formulaire.
- [x] `form_import_zip()`/`form_restore()` : déjà sans risque avant toute modification — `replace_all_from_dir()` ne touche que `page*.html`/`style.css`/`meta.json`, `_import_images_from_tmpdir()` ne regarde que `{tmp}/images` ; un `.commun/` présent dans une archive déposée n'est jamais lu. Documenté explicitement dans le docblock de `replace_all_from_dir()`.
- [x] Convention corrigée : `@import url("https://gvv.net/...")` → `@import url(".commun/style.css")` dans `doc/users/fr/13_formulaires.md`. Formulaire en production migré (`attestation_de_fin_de_formation_spl-planeur` : les deux logos passent de `/forms_public/image/{code}/{fichier}` à `images/{fichier}`) ; vérifié en conditions réelles sur gvv.net (image et page publique).
- [x] Carte **Images** de l'admin (`bs_form.php`) : le champ à copier affiche désormais `images/{fichier}` (chemin relatif) au lieu de l'URL applicative complète, cohérent avec la nouvelle convention — pas explicitement listé au départ mais nécessaire pour que l'UI n'enseigne pas l'ancienne convention cassée.
- [x] Test PHPUnit : `FormsRendererLocalUrlRewriteTest` (12 tests — image propre/partagée, quotes simples/doubles, URL absolue/root-relative/data: laissées intactes, `@import` partagé/externe) ; `FormsFileStorageTest` étendu (+5 tests — image partagée, `.htaccess` de `.commun/`).
- [x] Test Playwright smoke : `forms-file-storage-smoke.spec.js` étendu — création avec image propre + image partagée + CSS partagé (convention `.commun/...`), vérifie le fichier stocké reste relatif et que la page publique charge les trois via leurs routes de service respectives.
- [x] Documentation utilisateur : sections « CSS partagé entre formulaires » et « Images du formulaire » de `doc/users/fr/13_formulaires.md` mises à jour (convention relative, image partagée `.commun/images/`, prévisualisation locale hors périmètre GVV). Tutoriel IA non modifié : aucun exemple d'image n'y figure, rien à corriger.

### Lot 3 — Impression et archivage (approche simplifiée)

- [x] Implémenter rendu PDF imprimable d'une réponse.
- [x] Journalisation dans les fichiers de logs (considérée implémentée si déjà présente lors de la création d'un document archivé).

### Lot 4 — Documents inline dans les formulaires

A analyser, pas sûr que ce soit vraiment utile. C'était surtout prévu pour permettre la visualisation de document avant approbation. Si on décide d'utiliser des formulaires pour faire approuver des documents, ce lot devient inutile.

- [ ] Migration de début de lot : créer `09X_forms_documents.php` avec les tables complémentaires :
  - `form_document_refs` (références documents archivés)
  - structures de suivi d'import PDF -> HTML si nécessaires
- [ ] Permettre la sélection d'un document archivé existant dans un formulaire.
- [ ] Rendre les documents référencés inline dans une boîte scrollable.
- [ ] Implémenter le pipeline d'import PDF -> HTML.
- [ ] Prévoir la réédition manuelle post-import.

### Lot 4-bis — Paramètres de configuration formulaires

Table `form_config_params` (clé/valeur avec portée globale ou section) accessible depuis l'index admin des formulaires via une carte dédiée. Ces paramètres alimentent le namespace `config.*` du service de pré-remplissage.

Voir : [Design paramètres de configuration](../design_notes/remplissage_formulaires_design.md#5-paramètres-de-configuration-formulaires)

- [x] Migration `124_form_config_params.php` : table `form_config_params` (id, club_id nullable, param_key, param_value, param_label, param_description, audit fields) avec contrainte d'unicité `(club_id, param_key)`.
- [x] Mettre à jour `application/config/migration.php` à la version 124.
- [x] Créer `application/models/form_config_params_model.php` : CRUD, résolution avec fallback global→section.
- [x] Ajouter les méthodes `config`, `config_create`, `config_store`, `config_edit`, `config_update`, `config_delete` dans `forms_admin.php`.
- [x] Créer les vues `application/views/forms_admin/bs_config.php` (liste) et `bs_config_form.php` (create/edit).
- [x] Ajouter une carte "Configuration" sur `bs_index.php` pointant vers `forms_admin/config`.
- [x] Pré-charger le paramètre `organisme_formation` dans la migration (libellé + valeur vide).
- [x] Ajouter les traductions (`forms_config_*`) dans les fichiers de langue français, anglais, néerlandais.
- [x] Tests PHPUnit MySQL : migration up/down, CRUD modèle, résolution section > global (11 tests, tous verts).

### Lot 5 — Pré-remplissage GVV

Syntaxe : attributs `data-gvv-source`, `data-gvv-param`, `data-gvv-lock` sur les éléments HTML.
Paramètres transmis en query string de l'URL du formulaire.
Voir : [Design pré-remplissage](../design_notes/remplissage_formulaires_design.md#7-pré-remplissage-gvv)

- [x] Implémenter la résolution `config.*` dans `forms_public` : parsing `data-gvv-source="config.*"` + injection value/readonly au rendu + lock serveur sur soumission (`_apply_config_prefill`, `_collect_locked_config_fields`). Prérequis : Lot 4-bis.
- [x] Créer service de pré-remplissage complet (`_apply_gvv_prefill`, `_collect_locked_gvv_fields`, `_resolve_gvv_source`) : résolution des sources par liste blanche (club.*, member.*, instructor.*, member.event.*, instructor.event.*, user.*, date.*). Prérequis : Lot 4-bis.
- [x] Parser les attributs `data-gvv-*` depuis le HTML de chaque page (regex sur `<input>`, même pattern).
- [x] Lire les paramètres URL (`pilot_login`, `instructor_login`) en GET, les stocker en session par slug.
- [x] Injecter les valeurs résolues dans le rendu public avant affichage.
- [x] Appliquer le lock côté serveur : ignorer la valeur soumise pour les champs `data-gvv-lock="true"` et réinjecter la valeur GVV.
- [x] Permettre l'utilisation des liens formulaire dans les workflows GVV (paramètres encodés dans le lien).
- [ ] Ajouter la sauvegarde/reprise de saisie multi-session pour les utilisateurs externes (mode brouillon, token de reprise).
- [ ] Ajouter des règles de visibilité des pages/sections selon les réponses (conditions simples, liste blanche d'opérateurs).
- [ ] Recalculer la séquence des pages visibles à chaque étape côté serveur.
- [ ] Adapter la validation finale aux seules pages/sections effectivement visibles.

### Lot 5-ter — Page de génération et données events

Voir : [Design page de génération](../design_notes/remplissage_formulaires_design.md#6-formulaires-à-contexte-gvv--page-de-génération)
Voir : [Design table events](../design_notes/remplissage_formulaires_design.md#8-table-events--évolutions-requises)

#### Évolutions table events

- [x] Migration `125_lot5ter.php` : ajouter `signature_path VARCHAR(255) NULL` à la table `events`.
- [x] Migration `125_lot5ter.php` : ajouter `FI ULM` (activite=2, expirable=1, multiple=0) et `FE ULM` dans `events_types`.
- [x] Mettre à jour `application/config/migration.php` à la version 125.

#### Vérification et correction du formulaire membre

- [x] Vérifier que `events_types` est accessible depuis le tableau de bord admin (liste des types, ajout de nouvelles entrées).
- [x] Vérifier que le formulaire membre permet d'ajouter/modifier des événements de tous les types pertinents (ITP, ITV, FI Sailplane, FI ULM, FE Sailplane, FE ULM, visite médicale, contrôle de compétence).
- [x] Vérifier que les champs `ecomment` (numéro de qualification) et `date_expiration` sont bien éditables pour les types `expirable=1` (les deux champs sont toujours affichés dans le formulaire de saisie d'événement).
- [x] Corriger le formulaire membre si des types sont absents ou si les champs numéro/expiration ne sont pas proposés.

#### Extension taxonomie — sources events

- [x] Implémenter `_resolve_event_source` : requête `events WHERE emlogin=login AND etype={id} ORDER BY edate DESC LIMIT 1`, champs `ecomment` (numero), `date_expiration` (expiry), `edate` (date), `signature_path` (signature).
- [x] Implémenter `_resolve_member_source` : champs `membres` (nom, prenom, adresse, date/lieu naissance, etc.).
- [x] Table de correspondance `type_key` → `events_types.id` dans `_get_event_type_id` (itp=43, itv=44, fi_spl=51, fe_spl=52, fi_ulm/fe_ulm=lookup dynamique, controle_competence=30, visite_medicale=26, bpp=27, spl=50).

#### Page de génération

- [x] Colonne `required_params` ENUM(`none`,`pilot`,`instructor`,`pilot+instructor`) ajoutée à la table `forms` (migration 125).
- [x] `required_params` géré dans `forms_model` (create/update), dans `forms_admin` (store/update), et dans `bs_form.php` (select dropdown).
- [x] Méthodes `generate()` et `generate_submit()` dans `forms_admin` : sélecteurs membres/instructeurs, construction de l'URL pré-remplie, redirection.
- [x] Vue `bs_generate.php` : sélecteurs conditionnels selon `required_params`.
- [x] Bouton "Générer" dans `bs_index.php` pour les formulaires avec `required_params != 'none'`.

### Lot 5-bis — Signatures

Syntaxe : `<div data-gvv-type="signature" data-gvv-name="..." data-gvv-param="..." data-gvv-lock="...">`.
`sync_fields_from_html` enregistre automatiquement un champ de type `signature` dans `form_fields`.

| Priorité | Fonctionnalité | Complexité |
|---|---|---|
| 1 | Dessin canvas | Faible — `signature_pad.umd.min.js` déjà présent |
| 2 | Upload image | Faible — pipeline file existant |
| 3 | Saisie clavier (fonte Caveat) | Faible — canvas natif + Google Fonts CDN |
| 4 | Pré-remplissage profil GVV | Moyenne — colonne `membres.signature_path` déjà créée et déjà lue par `forms_public` ; reste l'écran d'alimentation et la garde d'authentification (voir sous-lot ci-dessous) |
| 5 | Signature PGP | Élevée — hors V1 |

- [x] Ajouter le type `signature` dans `form_fields_model::$allowed_field_types`.
- [x] Étendre `extract_html_fields` (forms_admin) pour détecter `<div data-gvv-type="signature" data-gvv-name="...">` et enregistrer le champ de type `signature`.
- [x] Implémenter `Forms_renderer::render_signature_widget(string $name, string $label, bool $required): string` : génère le HTML du widget avec trois onglets (canvas dessin, upload image, saisie clavier fonte Caveat).
- [x] Implémenter `Forms_renderer::inject_signature_widgets(string $html): string` : détecte les divs signature dans le HTML de la page et les remplace par le widget.
- [x] Mode canvas : `signature_pad.umd.min.js` → `toDataURL('image/png')` → strip préfixe → hidden input base64.
- [x] Mode upload : `<input type="file" accept="image/*" name="{field}_file">` → pipeline standard `form_submission_files`.
- [x] Mode clavier : texte rendu sur canvas avec fonte Caveat → export PNG base64 → pipeline identique au mode canvas.
- [x] Côté serveur `forms_public::submit()` : détecter les champs `signature`, dispatcher selon le type (`canvas|text` → `save_signature_canvas()` ; `file` → pipeline upload standard).
- [x] Repopulation du widget après échec de validation serveur : `pad.fromDataURL()` (API async SignaturePad v4) pour restaurer le canvas ; scan de `content_html` pour capturer les valeurs POST des widgets HTML-only dans la flashdata.
- [x] Support des widgets signature définis uniquement dans `content_html` (sans enregistrement `form_fields`) : migration 137 (`field_id` nullable + colonne `widget_name VARCHAR(100)` sur `form_submission_files`), capture des POST HTML-only dans `submit()`, stockage avec `widget_name` au lieu de `field_id`, `get_submission_files()` retourne `COALESCE(f.name, sf.widget_name) as field_name`.
- [x] Affichage graphique des signatures dans l'admin (`bs_submission.php`) : pour chaque champ `signature`, chercher le fichier associé dans `form_submission_files` et afficher l'image en ligne ; fallback libellé `widget_name` → `field_name` pour les fichiers sans `field_id`.
- [x] Migration : ajouter `signature_path VARCHAR(255) NULL` à la table `membres` (migration 121).
- [x] Ajouter les sources `member.signature` et `instructor.signature` (depuis `membres.signature_path`) à la taxonomie `form_prefill_service`.
- [x] Ajouter la source `instructor.event.{type_key}.signature` (depuis `events.signature_path`) à la taxonomie — prérequis : migration Lot 5-ter.
- [x] Pré-remplissage widget : afficher l'image depuis `membres.signature_path` ou `events.signature_path` selon la source déclarée ; remplaçable si `data-gvv-lock="false"` (`_collect_gvv_sig_prefill` + merge avec flashdata).

#### Sous-lot — Signature de référence instructeur (alimentation + garde)

Périmètre restreint à l'instructeur qui pré-remplit sa propre signature sur les attestations/fiches de test qu'il génère lui-même. Ne couvre ni `member.signature`, ni une éventuelle seconde signature (élève, représentant légal), laissée à la charge de l'instructeur sur chaque formulaire. Voir [Design signatures](../design_notes/remplissage_formulaires_design.md#9-signatures) et PRD EF6-bis (points 10-11). Pas de nouvelle migration : `membres.signature_path` (migration 121) et les colonnes d'audit `updated_by`/`updated_at` (migration 093) existent déjà.

- [x] Ajouter les métadonnées `membres.signature_path` dans `Gvvmetadata.php` (même pattern que `membres.photo`, `Subtype = 'upload_image'`).
- [x] Écran self-service « Ma signature » dans le profil de l'instructeur : réutilise `Forms_renderer::render_signature_widget()`/`make_signature_file()`/`upload_submitted_files()`, écrit dans son propre `signature_path` via `membres_model` (au lieu de `form_submission_files`). Route `membre/ma_signature`, gardée par `user_has_role('instructeur')`, entrée de menu dans `bs_menu.php`.
- [x] Import admin : même widget exposé depuis la fiche membre d'un instructeur, gardé par `user_has_role('club-admin')` (même pattern que les actions sensibles de `membre.php`) ; écrit via le même point d'entrée `membres_model` que le self-service. Route `membre/signature/$mlogin`, bouton ajouté dans `bs_formView.php`.
- [x] Garde d'authentification dans `forms_public.php::_resolve_gvv_source()` (case `instructor`) : ne résoudre `instructor.signature` que si `dx_auth->get_username() === instructor_login` ; sinon retourner `null` (widget vierge, saisie manuelle). `member.signature` inchangé. Test PHPUnit `application/tests/mysql/FormsInstructorSignaturePrefillGuardTest.php` (3 tests : personne connecté, un autre utilisateur connecté, l'instructeur connecté lui-même) — suite `Forms` complète rejouée sans régression (62/62).
- [x] Fichiers de langue FR/EN/NL pour l'écran self-service et le bloc admin.
- [ ] Tests PHPUnit pour `membre::ma_signature()`/`membre::signature($mlogin)` (mise à jour de `signature_path`, refus d'accès pour un non-instructeur/non-admin) : vérifié manuellement via HTTP (curl) sur gvv.net, non automatisé — les fichiers créés par ces routes appartiennent à `www-data` (serveur web réel), que l'utilisateur `frederic` exécutant phpunit ne peut pas supprimer en tearDown (pas de `sudo` sans mot de passe), contrairement au test du garde ci-dessus où le fixture est créé/supprimé directement par le test. À revoir si une solution de nettoyage est trouvée (ex. exécuter phpunit en `www-data`, ou endpoint de test dédié).
- [ ] Test Playwright : un instructeur définit sa signature, génère une attestation pour lui-même → signature pré-remplie ; un autre instructeur/membre ouvrant le même lien ne voit pas la signature pré-remplie. Playwright indisponible dans cet environnement (Chromium non installé, nécessite `sudo`) — smoke-testé manuellement via curl à la place (voir conversation).

### Lot 6 — Intégration workflow GVV (handlers post-soumission)

Objectif : permettre aux formulaires de catégorie 3 de déclencher des actions GVV après soumission. Cas de référence : migration de `briefing_passager_ulm` / `briefing_sign` vers le moteur de formulaires générique.

Chaque étape est conclue par une vérification de non-régression sur les formulaires de catégorie 1 (`inscription_club`) et de catégorie 2 (`attestation_de_formation_ulm`).

#### Étape 6.1 — Infrastructure pré-remplissage mécanisme B (non-breaking)

Prérequis : Lot 5 terminé.

- [x] Étendre `forms_public/index` : lire tous les paramètres GET, séparer les noms réservés (`token`, `vld_id`, `lock`, `page`, `pilot_login`, `instructor_login`) des noms de champs, stocker les deux groupes en session par slug (`forms_b_prefill_*`, `forms_b_lock_*`).
- [x] Injecter les valeurs de pré-remplissage dans les champs HTML dont le `name=` correspond (`Forms_renderer::inject_prefill_by_name`).
- [x] Appliquer `readonly` et enforcement serveur sur les champs listés dans `lock[]`.
- [x] **Validation non-régression** : PHPUnit 1528 tests verts + Playwright smoke `inscription_club` (soumission anonyme) + `briefing-passager-ulm` avec 5 champs pré-remplis dont 3 verrouillés → comportement correct.

#### Étape 6.2 — Référence générique au sujet (subject_type / subject_id)

Remplace l'approche `context_params` JSON initialement prévue (abandonnée, voir [Design — décisions actées](../design_notes/remplissage_formulaires_design.md#décisions-actées-juillet-2026--remplacement-du-briefing-passager)) : un couple générique, indexé et interrogeable, plutôt qu'un contexte opaque. Aucune colonne métier (`vld_id`) n'est ajoutée au module `forms`.

- [x] Migration : ajouter `subject_type VARCHAR(50) NULL` et `subject_id INT NULL` à `form_submissions`, index composite `(subject_type, subject_id)`.
- [x] Étendre `$b_reserved` dans `forms_public::index()` : remplacer le nom réservé `vld_id` par `subject_type`/`subject_id` génériques ; mémoriser en session par slug (même pattern que `pilot_login`/`instructor_login`).
- [x] Dans `forms_public::submit()` : relire `subject_type`/`subject_id` de la session, les transmettre à `Form_submissions_model::create_submission()`.
- [x] Nouvelle méthode `Form_submissions_model::get_current_for_subject($subject_type, $subject_id, $form_id = null)` : dernière soumission `status='submitted'` pour ce sujet, `ORDER BY created_at DESC LIMIT 1` (même logique que `archived_documents_model::get_briefing_by_vld()`).
- [x] **Validation non-régression** : PHPUnit migration up/down + smoke tests catégorie 1 et 2 (le couple `subject_type`/`subject_id` reste `NULL` pour ces catégories, sans impact). Suite complète (5 suites, 1558 tests) verte, mêmes 46 skips pré-existants. Validation fonctionnelle réelle sur gvv.net (curl + session `ci_sessions`) : `inscription-club` (catégorie 1) et `attestation-de-formation-ulm` (catégorie 2, chargement pré-rempli) inchangés ; `briefing-passager-ulm` avec `subject_type=vols_decouverte&subject_id=16143` en URL → capturé en session → transmis à la soumission → `form_submissions.subject_type`/`subject_id` correctement renseignés.

#### Étape 6.3 — Infrastructure handler post-soumission (optionnel, par formulaire)

- [x] Migration `141_forms_handler_class.php` : ajouter `handler_class VARCHAR(100) NULL` à `forms` (idempotente, pattern `add_column_if_missing`/`drop_column_if_exists`). `application/config/migration.php` mis à jour à la version 141.
- [x] Créer `application/libraries/form_handlers/GvvFormHandlerInterface.php` (`after_submit(int $submission_id, ?string $subject_type, ?int $subject_id): array`).
- [x] Dans `forms_public::submit()` : après création de la soumission, `_dispatch_handler()` instancie le handler si `handler_class` est défini (validation du nom de classe par liste blanche regex, chargement depuis `application/libraries/form_handlers/{Classe}.php`, vérification `implements GvvFormHandlerInterface`), appelle `after_submit($submission_id, $subject_type, $subject_id)`, redirige vers `redirect_url` si fourni sinon poursuit vers la page de remerciement standard.
- [x] Erreurs handler (classe absente, mauvaise interface, exception, `result['error']`) journalisées via `log_message('error', ...)` sans jamais interrompre la réponse : la soumission déjà créée reste accessible en admin.
- [x] Test PHPUnit `FormsHandlerClassMigrationTest` (mysql, 3 tests, 11 assertions) : colonne créée, up() idempotent, défaut `NULL`, down/up roundtrip.
- [x] **Validation non-régression** : suite complète (5 suites, 1561 tests, mêmes 46 skips pré-existants) verte après migration appliquée sur gvv.net (version DB 139→141 via `/migration`). Soumission anonyme réelle vérifiée sur `inscription-club` (catégorie 1) et `briefing-passager-ulm` avec `subject_type`/`subject_id` (catégorie 3, préfigure étape 6.4) : les deux aboutissent à la page de remerciement, `handler_class` NULL ne déclenche aucun effet de bord, aucune erreur journalisée. Soumissions de test supprimées après vérification.

#### Étape 6.4 — BriefingPassagerUlmHandler (périmètre réduit)

Périmètre volontairement réduit par rapport à la V0 de cette étape (décision juillet 2026, voir [Design — décisions actées](../design_notes/remplissage_formulaires_design.md#décisions-actées-juillet-2026--remplacement-du-briefing-passager)) : ni génération PDF, ni archivage `archived_documents`, ni invalidation de token — la détection d'existence est déjà couverte par l'étape 6.2 (`subject_type`/`subject_id`), et la protection du lien public est hors périmètre (voir étape 6.5).

- [x] Créer `application/libraries/form_handlers/BriefingPassagerUlmHandler.php`.
- [x] Implémenter `after_submit` : vérifie `$subject_type === 'vols_decouverte'`, récupère le VLD (`$subject_id`), met à jour `vols_decouverte` depuis les valeurs soumises (`beneficiaire` = `nom`+`prenom`, `participation` = `poids_declare`, `urgence` = `personne_a_prevenir`, `beneficiaire_tel` = `telephone`, `date_vol`), uniquement si la valeur soumise est non vide et diffère de la valeur actuelle (même garde que l'ancien `briefing_sign::submit()`). `site_decollage`/`identification_ulm` ne sont pas réécrits : verrouillés côté formulaire (pré-remplis depuis `aerodrome`/`airplane_immat`), donc déjà identiques.
- [x] Configurer `handler_class = 'BriefingPassagerUlmHandler'` sur le formulaire `briefing_passager_ulm` en base (pas d'UI admin pour ce champ — mis à jour directement en base sur gvv2, comme prévu par cette étape).
- [x] **Tests PHPUnit** : `BriefingPassagerUlmHandlerTest` (mysql, 3 tests, 10 assertions) — soumission valide → VLD mis à jour (`beneficiaire`, `participation`, `urgence`, `beneficiaire_tel`, `date_vol`) ; `subject_type` incorrect → erreur retournée, VLD inchangé ; VLD introuvable → erreur retournée, pas de crash.
- [x] **Validation non-régression** : suite complète (5 suites, 1564 tests, mêmes 46 skips pré-existants) verte. Soumission réelle sur gvv.net (curl + session, VLD de test dédié) : `briefing-passager-ulm?subject_type=vols_decouverte&subject_id=...` → soumission → `vols_decouverte` mis à jour (`beneficiaire`, `participation`, `urgence`, `beneficiaire_tel`, `date_vol`), page de remerciement affichée, aucune erreur journalisée. `inscription-club` (catégorie 1) et `attestation-de-formation-ulm` (catégorie 2) inchangés. Données de test supprimées après vérification.

#### Étape 6.5 — Point d'entrée depuis briefing_passager/upload ✅

Le bouton `link2` expérimental (derrière le flag `testing_form`) devient le seul point d'entrée vers le briefing passager, remplaçant `link` (ancien flux `briefing_sign`/`briefing_tokens`).

- [x] Retirer le flag `testing_form` : le bouton vers `forms/briefing-passager-ulm` devient permanent (guard `testing_form` retiré dans `bs_uploadView.php` et `briefing_passager::upload_submit()`, entrée `$config['testing_form']` retirée de `application/config/program.php`), l'ancien bouton « signer en ligne » (`briefing_sign`, `action=link`) est retiré de `bs_uploadView.php`. Le bouton restant reprend le libellé `briefing_passager_sign_online` (clé `_2` retirée des fichiers de langue fr/en/nl, devenue inutilisée).
- [x] Construire l'URL avec `subject_type=vols_decouverte&subject_id={vld_id}` + champs de pré-remplissage (voir étape 6.2), sans `token` — déjà en place depuis l'étape 6.4 dans la branche `action === 'link2'` de `upload_submit()`, inchangée ici à part le retrait du guard.
- [x] **Hors périmètre** : transfert du lien par QR code/email vers l'appareil du passager (`generate_link`, `briefing_tokens`) — utilité non confirmée (voir Design). Le lien reste ouvert depuis une session GVV authentifiée pour l'instant ; `generate_link`/`briefing_tokens` n'est ni supprimé ni modifié dans cette étape (méthode `generate_link()` et branche `action === 'link'` conservées telles quelles, seul le bouton qui la déclenchait dans la vue est retiré).
- [x] **Playwright** : `briefing-passager-smoke.spec.js` — nouveau test dédié (bouton unique `value="link2"`, absence de `value="link"`) + extension du test de bascule d'icône (Lot 6, étape 6.6) avec des assertions sur la mise à jour de `vols_decouverte` (`beneficiaire`, `participation`, `urgence`, `beneficiaire_tel`) après soumission. 12 tests du fichier verts. Nécessité de restaurer `forms.handler_class = 'BriefingPassagerUlmHandler'` sur `briefing-passager-ulm` (id=2) sur la base de dev gvv2, valeur perdue depuis l'étape 6.4 (probable réinitialisation locale, pas une régression de code) — mise à jour en base, hors migration comme prévu par l'étape 6.4.
- [x] **Validation non-régression** : suite PHPUnit complète (5 suites, 1568 tests) verte, mêmes 46 skips pré-existants. `inscription-club` et `attestation-de-formation-ulm` accessibles (200) sur gvv.net après les changements ; `forms-upload-response-smoke.spec.js` (Lot 9) vert.

#### Étape 6.6 — Bascule de la détection « briefing fait » et retrait de l'ancien mécanisme

Réalisée en mode transitoire (juillet 2026) : l'étape 6.5 n'étant pas encore faite (bouton « signer en ligne »/`briefing_sign` toujours actif en prod), la détection combine les deux mécanismes plutôt que de remplacer purement l'un par l'autre — pas de régression pour un briefing signé via l'ancien flux pendant la transition. Le retrait effectif de `briefing_sign` et la bascule pure (une seule source) restent à faire une fois l'étape 6.5 réalisée.

- [x] Ressaisie des briefings actifs anciens : vérifié en base gvv2, 0 cas — les 4 `archived_documents` de type `briefing_passager` existants ont tous `vld_id` NULL (non rattachés à un VLD actif). Rien à ressaisir.
- [x] `vols_decouverte_model::select_page()` : `has_briefing` additionne désormais la sous-requête historique (`archived_documents`/`document_types`) et une nouvelle sous-requête sur `form_submissions` (`subject_type='vols_decouverte' AND subject_id = vols_decouverte.id`, formulaire `briefing-passager-ulm`, `status='submitted'`) — combinaison transitoire, pas un remplacement pur.
- [x] Bouton `briefing_vd` (`MetaData::action()`) : inchangé, consomme directement `has_briefing` donc bascule automatiquement avec la requête ci-dessus. Page `briefing_passager/upload` : nouvelle détection via `Form_submissions_model::get_current_for_subject()`, affichée dans un second encart à côté de l'encart existant (`archived_documents`), sans les remplacer.
- [x] `briefing_passager/admin_list` et `export_pdf` : fusionnent désormais `archived_documents_model->get_briefings_recent()` et `Form_submissions_model::get_briefing_submissions_recent()` (nouvelle méthode), avec un badge « Formulaire en ligne » pour la nouvelle source.
- [x] Vérifier que `briefing_sign` peut être retiré sans casser d'autres dépendances (routes, vues, tests) — étape 6.5 étant faite (bouton « signer en ligne » déjà retiré de l'UI), seuls des accès directs par URL (tests) exerçaient encore le mécanisme : `briefing-passager-smoke.spec.js` (3 tests UC2 directs), `BriefingSignatureTest.php` (table `briefing_tokens` en isolation), et le code mort restant dans `briefing_passager.php` (`generate_link()`, `_build_public_sign_url()`, `_resolve_qrcode_ip()`, `_is_usable_qrcode_ip()`, branche `action === 'link'`).
- [x] Archiver ou supprimer `briefing_sign.php` et ses vues — supprimés : `application/controllers/briefing_sign.php`, `application/views/briefing_passager/{bs_linkView,bs_signView,bs_signConfirmView,bs_signErrorView}.php`. Code mort correspondant retiré de `briefing_passager.php` (méthode `generate_link()`, helpers QR, branche `action === 'link'`). Clés de langue orphelines (`briefing_passager_sign_*`, `briefing_passager_link_*`, `briefing_passager_public_share_*`, etc., 39 clés) retirées de fr/en/nl ; clés encore utilisées ailleurs (`briefing_passager_field_vld/aerodrome/appareil/date_vol/nom`, `gvv_button_cancel`) conservées. Table `briefing_tokens` (migration 088) conservée en base (historique), non supprimée.
- [x] Mettre à jour `routes.php` — les 3 routes `briefing_sign/*` retirées.
- [ ] **L'ancien mécanisme documentaire** (`briefing_passager::upload/delete`, `archived_documents` type `briefing_passager`) n'est retiré qu'une fois cette bascule validée en conditions réelles — décision de suppression effective traitée séparément, hors de ce lot.
- [x] **Playwright non-régression globale** : `briefing-passager-smoke.spec.js` — les 3 tests UC2 (génération de lien, accès anonyme par token, token invalide) supprimés avec le mécanisme qu'ils testaient. `login()` étendu pour sélectionner une section explicite (bug latent révélé à cette occasion : `forms_admin::submission_delete` refuse l'accès si la section active de l'admin ne correspond pas au club du formulaire ; le test ne sélectionnait aucune section, dépendant implicitement d'un cookie `gvv_remembered_section` absent en contexte de navigateur Playwright frais — corrigé en sélectionnant explicitement la section 2/ULM avant les opérations admin). 9 tests du fichier verts. `BriefingSignatureTest.php` (testait uniquement la table `briefing_tokens` en isolation) supprimé — mécanisme retiré, plus rien ne peuple cette table. `inscription-club` et `attestation-de-formation-ulm` non touchés par cette étape.
- [x] **PHPUnit non-régression** : `./run-all-tests.sh` — 1571 tests, 1525 passés, 0 échec, 46 skips (préexistants, identiques à avant). **Note environnement** : les tests de migration (`FormsHandlerClassMigrationTest`) font un aller-retour `down()`/`up()` réel sur la colonne `forms.handler_class` en base de dev, ce qui réinitialise à `NULL` la valeur positionnée manuellement sur `briefing-passager-ulm` (id=2) — déjà observé aux étapes 6.4/6.5. À restaurer en base après toute exécution de la suite complète, avant de rejouer les tests Playwright qui en dépendent (comme déjà noté aux étapes précédentes).

### Lot 7 — Cartes dynamiques dans les dashboards

Objectif : permettre aux club-admins d'ajouter des raccourcis de navigation dans les dashboards GVV sans développement. Indépendant des lots de formulaires — peut être réalisé dès que le socle (Lot 1) est terminé.

Voir : [Design cartes dynamiques](../design_notes/remplissage_formulaires_design.md#14-cartes-dynamiques-dans-les-dashboards)

**Écart avec le design initial** : celui-ci suppose 4 contrôleurs de dashboard séparés (`accueil`, `pilote`, `instructeur`, `formations`). En réalité GVV n'a qu'un seul contrôleur `welcome.php` avec une méthode `section($name)` dont les valeurs sont `user, flights, treasurer, formation, maintenance, admin_club, admin_sys, dev`, rendues par une seule vue `bs_sub_dashboard.php`. La colonne `dashboard` utilise directement ces 8 valeurs. Seul `bs_sub_dashboard.php` est instrumenté (pas le dashboard racine `bs_dashboard.php`, simple grille de tuiles de navigation). Le champ `icon` utilise des classes Font Awesome (`fas fa-...`), déjà utilisées partout dans les dashboards, plutôt que Bootstrap Icons (non chargé globalement).

- [x] Migration `167_dashboard_shortcuts.php` : table `dashboard_shortcuts` (id, dashboard, section, title_key, title, description_key, description, url, icon, color, role_required, sort_order, active, club_id, audit fields).
- [x] Mettre à jour `application/config/migration.php`.
- [x] Créer `application/models/dashboard_shortcuts_model.php` : `get_for_dashboard($dashboard, $club_id)` avec filtrage dashboard/actif/club/rôle (via le helper global `has_role()`), CRUD complet (`list_shortcuts`, `create`, `update`, `delete`, `toggle_active`).
- [x] Créer contrôleur `shortcuts_admin` + vues CRUD (liste, créer, modifier, supprimer, activer/désactiver). Accès réservé `ca`/`club-admin` (même garde que `forms_admin`).
- [x] Créer partial view `application/views/welcome/_dashboard_shortcuts.php` : rendu Bootstrap des cartes (`.sub-card`, cohérent avec le style des dashboards existants) groupées par `section`, résolution titre/description multi-langue (clé trouvée / repli sur texte brut), gestion URL interne vs externe.
- [x] Ajouter une carte "Raccourcis dashboard" dans le bloc `admin_club` de `bs_sub_dashboard.php` (gated `has_role('club-admin')`), pointant vers `shortcuts_admin` — emplacement plus cohérent que `forms_admin/index` avec le public ciblé.
- [x] Instrumenter les 8 sections de `welcome.php` (au lieu des 4 dashboards théoriques) : `Welcome::section()` appelle `dashboard_shortcuts_model::get_for_dashboard()`, `bs_sub_dashboard.php` inclut le partial après le bloc if/elseif.
- [x] Ajouter les traductions (`shortcuts_title`, `shortcuts_label_*`, etc.) dans `application/language/{french,english,dutch}/shortcuts_lang.php`.
- [x] **Tests Playwright existants scannant toutes les URLs** : la table démarre vide (pas de seed dans la migration), donc aucun lien nouveau n'apparaît tant qu'un admin ne crée pas de raccourci réel — pas de modification nécessaire des specs `*-recursive-authorizations.spec.js` dans ce lot.
- [x] Tests PHPUnit : `application/tests/mysql/Dashboard_shortcuts_test.php` — migration up/down + colonnes, CRUD modèle, `get_for_dashboard` (filtrage dashboard, actif/inactif, club_id global/section, rôle requis), tri section/sort_order (12 tests).
- [x] **Validation** : `playwright/tests/dashboard-shortcuts-smoke.spec.js` — création d'un raccourci pointant vers `forms_admin/generate/attestation-de-formation-ulm` dans la section `formation`, vérifié visible pour testadmin, invisible pour asterix une fois `role_required=ca` posé, réapparaît/disparaît avec le toggle actif/inactif, suppression. Vérifié aussi en conditions réelles sur gvv.net (`./run-all-tests.sh` : 1854 tests, 0 échec, mêmes skips préexistants).

### Lot 8 — Documentation et validation finale

- [x] Documenter le socle formulaire autonome et les fichiers uploadés. `doc/users/fr/13_formulaires.md` (existant) + nouvelle section « Consulter les réponses » (ouverture, aperçu image/PDF inline, téléchargement sécurisé, rétention).
- [x] Documenter la taxonomie des formulaires (catégories 1, 2, 3) et les exemples. Déjà couvert par `remplissage_formulaires_design.md` § « Taxonomie des formulaires » (exemples par catégorie).
- [x] Ajouter exemples complets de formulaires et de CSS global. Déjà couvert par `doc/users/fr/13_formulaires.md` § « Exemples de formulaires » (2 exemples complets HTML + CSS).
- [x] Documenter import PDF -> HTML et ses limites. Nouvelle section « Convertir un formulaire PDF existant » dans `13_formulaires.md` ; § 10 du design doc mis à jour avec renvoi + limites détaillées.
- [x] Documenter l'API de pré-remplissage GVV (mécanismes A et B) et les exemples workflow. Déjà couvert par `13_formulaires.md` §§ pré-remplissage A/B (taxonomie des sources, exemple briefing passager ULM).
- [x] Documenter le widget signature : modes canvas et upload, pré-remplissage depuis `membres.signature_path`, attributs `data-gvv-*`. Déjà couvert par `13_formulaires.md` § « Champ signature » et design doc § 9.
- [x] Documenter la création d'un handler post-soumission (interface, exemple BriefingPassagerUlm). Déjà couvert par design doc § 13 (interface `GvvFormHandlerInterface`, exemple `BriefingPassagerUlmHandler`).
- [ ] PHPUnit : modèles, validations, fichiers, archivage, pré-remplissage, handlers.
- [ ] Playwright : création admin, soumission anonyme, upload/preview, PDF imprimable, archivage, pré-remplissage GVV, signatures canvas et upload, workflow briefing end-to-end.
- [ ] Vérification sécurité : uploads, contrôle d'accès, anti-spam.

### Lot 9 — Soumission par téléchargement (formulaire scanné)

Objectif : permettre, en alternative au remplissage en ligne, de télécharger un formulaire imprimé puis rempli à la main (scan ou photo). Un seul fichier par réponse. Dépend de Lot 2 (réponses et fichiers) ; réutilise l'infrastructure de Lot 3 (impression/archivage) par analogie avec `archived_documents`. Indépendant des lots 4 à 7.

Voir : [Design soumission par téléchargement](../design_notes/remplissage_formulaires_design.md#15-soumission-par-téléchargement-scan)

Décisions retenues :
- Fonctionnalité opt-in par formulaire (`forms.allow_upload_response`), désactivée par défaut.
- Un seul fichier par réponse, nommé `reponses/{form_id}/reponse_{submission_id}.{ext}` (id de soumission, pas de compteur séquentiel à gérer).
- Types acceptés : PDF, jpg, jpeg, png, gif, webp (formats supportant rotation et miniature).
- Rotation via une librairie partagée `File_rotator`, extraite de la logique déjà présente dans `archived_documents::rotate()` (qpdf pour PDF, ImageMagick `convert` pour image), réutilisée par les deux contrôleurs.
- Réutilisation stricte de l'existant : `File_compressor` (compression), `Pdf_thumbnail` (miniature PDF), helper `attachment()` (rendu miniature cliquable image/PDF), pattern drag&drop natif de `archived_documents/bs_formView.php`, endpoint sécurisé `forms_admin/submission_file` (`?inline=1`).
- Pas de nouvelle table : le fichier est stocké dans `form_submission_files` avec `field_id = NULL` et `widget_name = 'uploaded_response'` (mécanisme déjà en place depuis la migration 137 pour les signatures HTML-only).

#### Étape 1 — Migration et modèles ✅

- [x] Migration `139_forms_upload_response.php` (idempotente, pattern `add_column_if_missing`/`drop_column_if_exists` de la migration 095) :
  - `forms.allow_upload_response TINYINT(1) NOT NULL DEFAULT 0`
  - `form_submissions.submission_method ENUM('online','upload') NOT NULL DEFAULT 'online'`
  - `form_submissions.upload_comment VARCHAR(255) NULL`
- [x] Mettre à jour `application/config/migration.php` à la version 139.
- [x] `forms_model.php` : gérer `allow_upload_response` en création/modification + case à cocher dans `bs_form.php` (`forms_admin.php` store()/update() transmettent le champ POST).
- [x] `form_submissions_model.php` :
  - `create_submission()` accepte `submission_method` et `upload_comment`.
  - `get_form_submissions()` : `response_identifier` devient `COALESCE(GROUP_CONCAT(...is_identifier...), s.upload_comment)`.
  - Nouvelle méthode `get_uploaded_response_file($submission_id)` (fetch par `widget_name = 'uploaded_response'`).
  - `delete_submission()` : supprime aussi la miniature associée (`Pdf_thumbnail::delete_thumbnail()`) quand le fichier supprimé est une réponse uploadée.
- [x] Test PHPUnit `FormsUploadResponseMigrationTest` : colonnes créées, up() idempotent, défauts corrects, down/up roundtrip (3 tests, 15 assertions, verts).
- [x] **Validation** : suite MySQL complète (660 tests) et suite unitaire (410 tests) exécutées sans régression après les changements.

#### Étape 2 — Extraction `File_rotator` (refactor à filet de sécurité) ✅

- [x] Créer `application/libraries/File_rotator.php` : `rotate($absolute_path, $mime, $direction)` retournant `['success','error_code','tool','detail']`, reprise exacte de la logique qpdf/convert précédemment inline dans `archived_documents::rotate()`.
- [x] Test PHPUnit `FileRotatorTest` (6 tests, 19 assertions) : direction invalide, fichier manquant, mime non supporté, rotation image cw/ccw (dimensions inversées vérifiées), rotation PDF avec skip gracieux constaté (qpdf absent dans cet environnement de dev).
- [x] Refactorer `archived_documents::rotate()` pour déléguer à `File_rotator` — messages, contrôle d'accès, redirections inchangés (switch sur `error_code`).
- [x] **Validation** : suite PHPUnit complète (5 suites : unit 416, url_helper 8, integration 451, enhanced 12, mysql 660) verte, mêmes skips pré-existants qu'avant le refactor. Vérification fonctionnelle réelle sur gvv.net via nouveau test Playwright `archived-documents-rotate-smoke.spec.js` : rotation d'un document image existant (id=1, GIF 500×377 → 377×500 confirmé), message de succès affiché, fichier restauré bit-à-bit après coup (checksum identique).

#### Étape 3 — Endpoint public de téléchargement ✅

- [x] `forms_public::upload_submit($slug)` (POST) :
  - vérifie formulaire publié + `allow_upload_response = 1` (sinon `forms_upload_error_disabled`, jamais un échec silencieux) ;
  - crée la ligne `form_submissions` (`submission_method='upload'`, `upload_comment` = commentaire du dialogue) avant l'upload, pour disposer de l'id de soumission ;
  - upload CI (`pdf|jpg|jpeg|png|gif|webp`) vers `uploads/reponses/{form_id}/reponse_{submission_id}.{ext}` (répertoire créé avec `umask(0)` + mode 0775, pattern repris de `archived_documents::_ensure_directory()`, pour rester réellement group-writable malgré l'umask du process web) ;
  - `File_compressor::compress()` puis, si PDF, `Pdf_thumbnail::generate()` ;
  - insère `form_submission_files` (`field_id=NULL`, `widget_name='uploaded_response'`) ;
  - upload refusé ou échoué → `delete_submission()` nettoie la ligne créée puis message d'erreur explicite ;
  - en cas de succès, rend directement la page de confirmation existante (`bs_thanks`), comme le fait déjà `submit()` pour la soumission en ligne (pas de redirect intermédiaire).
- [x] Route `forms/upload/(:any)` → `forms_public/upload_submit/$1` (avant la route catch-all `forms/(:any)`).
- [x] Vue `bs_show.php` : bouton "Télécharger un formulaire prérempli" à côté de "Envoyer ma réponse" (dernière page uniquement), visible seulement si `allow_upload_response` ; modale Bootstrap avec zone drag&drop (pattern `initDropZone` d'`archived_documents`) + champ commentaire + bouton de validation.
- [x] Clés de langue fr/en/nl : `forms_button_upload_response`, `forms_upload_modal_*`, `forms_upload_error_*`.
- [x] Test PHPUnit `FormsUploadSubmitTest` (mysql, 3 tests, 14 assertions) : upload valide (soumission + fichier `form_submission_files` créés, nom de fichier `reponse_{id}.pdf` vérifié), type de fichier refusé (aucune soumission orpheline en base), formulaire avec `allow_upload_response=0` (upload refusé, aucune soumission créée). Le contrôleur n'étant testable qu'en HTTP (redirect()/show_404()/$_FILES), les tests postent en multipart vers le vrai endpoint sur gvv.net via le wrapper `http` de PHP (pas de `curl` dans cet environnement).
- [x] Test Playwright `forms-upload-response-smoke.spec.js` : formulaire de test créé en base (comme le fait déjà `global-setup.js` du projet), upload d'un PDF avec commentaire depuis la page publique, puis vérification que la soumission apparaît dans `forms_admin/submissions` avec le commentaire comme identifiant.
- [x] **Validation** : suite PHPUnit complète (5 suites, 1550 tests) verte, mêmes 46 skips pré-existants. Test Playwright ci-dessus exécuté avec succès contre le vrai serveur de dev gvv.net (validation fonctionnelle réelle, pas seulement les tests automatisés).

#### Étape 4 — Liste admin des réponses ✅

- [x] Bouton "Télécharger un formulaire prérempli" en haut de `bs_submissions.php` (même modale drag&drop, même endpoint public `forms/upload/{slug}`), affiché seulement si `allow_upload_response`.
- [x] Par ligne, si `submission_method === 'upload'` :
  - bouton "Ouvrir" masqué ;
  - bouton "Générer PDF" remplacé par la miniature (helper `attachment()` + URL `submission_file?inline=1`), cliquable pour ouvrir en grand ;
  - deux boutons rotation (↺/↻, réutilisant les libellés/messages `archived_documents_rotate_*`) appelant la nouvelle méthode `forms_admin::submission_rotate($form_id, $submission_id, $direction)` (délègue à `File_rotator`, régénère la miniature PDF après rotation) ;
  - colonne "Identification" = `upload_comment` (déjà en place depuis l'étape 1).
  - requête groupée `get_uploaded_response_files_for_submissions()` pour éviter le N+1 sur la liste.
- [x] Suppression : réutilise `submission_delete` existant, complété à l'étape 1 pour la miniature.
- [x] Garde-fou : `submission()`, `submission_view()`, `submission_pdf()` redirigent directement vers le fichier (`_redirect_to_uploaded_response_file()`) si `submission_method === 'upload'`.
- [x] Test PHPUnit `FormsAdminSubmissionRotateTest` (mysql, 3 tests, 16 assertions) : rotation par un admin authentifié (dimensions inversées vérifiées), requête non authentifiée refusée (redirection login, fichier non modifié), direction invalide refusée. Authentification testée en HTTP réel (cookie de session capturé manuellement, pas de `curl` dans cet environnement).
- [x] Test Playwright `forms-upload-response-smoke.spec.js` étendu (Lot 9, étapes 3+4 dans un seul test — `fullyParallel` empêcherait le partage d'état entre tests séparés du même fichier) : absence du bouton "Ouvrir", présence de la miniature cliquable, clic sur rotation (tolérant à l'absence de `qpdf` dans cet environnement, comme `FileRotatorTest`), suppression puis vérification que le fichier a bien disparu du disque.
- [x] **Bug découvert et corrigé pendant cette étape** : `File_rotator::rotate_pdf()/rotate_image()` ne vérifiaient pas la valeur de retour de `rename()` — un échec (ex. `EXDEV`, rename cross-filesystem entre `sys_get_temp_dir()` et le répertoire cible) était rapporté comme un succès silencieux, laissant le fichier original inchangé. Corrigé en (1) vérifiant `rename()` et retournant `rotate_failed` en cas d'échec, et (2) créant le fichier temporaire dans le **même répertoire** que la cible (`dirname($absolute_path)`) plutôt que dans `sys_get_temp_dir()`, ce qui élimine structurellement le risque de rename cross-filesystem. Découvert via ce nouveau test PHPUnit puis confirmé en conditions réelles : la rotation d'un document existant (`archived_documents`, id=1) échouait silencieusement après le premier correctif (vérification seule) à cause d'un `/tmp` sur un filesystem différent de `uploads/` dans cet environnement de dev ; le second correctif (tmp file colocalisé) résout le problème pour de bon. Non-régression : `FileRotatorTest` (7 tests, ajout d'un test dédié à ce cas), suite complète (1554 tests) et les 13 tests Playwright concernés (dont `archived-documents-rotate-smoke.spec.js` re-vérifié en conditions réelles sur le document existant, restauré à son orientation d'origine ensuite).
- [x] **Validation** : suite PHPUnit complète (5 suites, 1554 tests) verte, mêmes 46 skips pré-existants. Parcours complet vérifié sur gvv.net via Playwright (upload public → miniature/rotation/suppression en admin) et via `archived-documents-rotate-smoke.spec.js` (régression du module documents archivés, dont dépend `File_rotator`).

#### Étape 5 — Traductions ✅

- [x] Nouvelles clés `forms_button_upload_response`, `forms_upload_modal_*`, `forms_upload_error_*` (français, anglais, néerlandais) — ajoutées dès l'étape 3, vérifiées complètes (9 clés × 3 langues) lors de cette étape.
- [x] Réutilisation directe des clés `archived_documents_rotate_*` existantes pour les messages de rotation (pas de duplication) — confirmée présente en fr/en/nl.

#### Étape 6 — Documentation ✅

- [x] Mettre à jour `doc/design_notes/remplissage_formulaires_design.md` (section 15, déjà ajoutée).
- [x] Mettre à jour `doc/prds/remplissage_formulaires_prd.md` (EF12, déjà ajoutée).
- [x] Mettre à jour `doc/users/fr/13_formulaires.md` : nouvelle section "Soumission par téléchargement (scan)" (sommaire, case à cocher admin, bouton/modale public, colonne Identification/miniature/rotation/suppression en admin), avec 3 captures d'écran (`admin_upload_checkbox.png`, `form_upload_modal.png`, `submissions_upload_thumbnail.png`) prises via un script Playwright temporaire (supprimé après capture).

**Lot 9 terminé.**

### Lot 10 — Paiement en ligne intégré (widget HelloAsso)

Objectif : permettre à un formulaire de déclencher un paiement HelloAsso (première cotisation, frais d'inscription BIA, ...) en complément de sa réponse, avec génération d'une écriture comptable dans un compte GVV. Réutilise autant que possible le pipeline `paiements_en_ligne` existant (création de checkout, webhook, écriture `compte_destination_id`) déjà éprouvé par les flux `paiement_generique`/`public_bar`/`public_decouverte`. Un seul paiement par formulaire (V1).

- [ ] Migration `1XX_forms_payment.php` : ajouter `payment_status ENUM('none','pending','paid','failed') NOT NULL DEFAULT 'none'` sur `form_submissions`, et un moyen de distinguer une réponse rejetée faute de paiement obligatoire confirmé (valeur de statut dédiée ou colonne équivalente).
- [ ] Mettre à jour `application/config/migration.php`.
- [ ] Ajouter le type de champ `payment` dans `form_fields_model::$allowed_field_types` (sur le modèle de `signature`).
- [ ] Étendre `extract_html_fields`/`sync_fields_from_html` pour détecter `<div data-gvv-type="payment" data-gvv-name="...">` et enregistrer le champ de type `payment`.
- [ ] Widget de rendu (`Forms_renderer`) : attributs `data-gvv-description`, `data-gvv-amounts` (liste, ou absent = montant libre avec bornes de section), `data-gvv-compte-id`, `data-gvv-required` (paiement obligatoire ou facultatif — même convention que `data-gvv-required` du widget signature).
- [ ] Handler `FormPaymentHandler implements GvvFormHandlerInterface` : relit la configuration du widget depuis `content_html`, revalide le montant soumis côté serveur (bornes de section, jamais confiance dans le POST), crée la transaction + le checkout HelloAsso en réutilisant `paiements_en_ligne_model`/`Helloasso` (`club_id` = `forms.club`), retourne `redirect_url` vers HelloAsso.
- [ ] Page de retour publique (`forms_public`) après passage par HelloAsso : réaffiche la page de remerciement standard (`bs_thanks`) — la confirmation reste asynchrone, portée par le webhook, jamais par le retour synchrone.
- [ ] Extension du webhook `paiements_en_ligne::helloasso_webhook`/`process_order_event` : au succès du paiement, met à jour `form_submissions.payment_status` de la soumission liée (référence portée dans les métadonnées de la transaction HelloAsso).
- [ ] Comportement paiement facultatif : la réponse reste acceptée quel que soit `payment_status` ; le statut est informatif.
- [ ] Comportement paiement obligatoire : une réponse dont le paiement n'est pas confirmé est marquée rejetée — critère exact de rejet (délai / échec explicite HelloAsso) à trancher, voir Questions ouvertes du PRD (EF13).
- [ ] Affichage du statut de paiement dans le détail admin d'une réponse (`bs_submission.php`) : badge explicite (payé / en attente / non payé / rejeté), montant, description, lien vers la transaction `paiements_en_ligne` correspondante.
- [ ] Affichage du statut de paiement dans le PDF imprimable de la réponse (`submission_view`/`submission_pdf`).
- [ ] Anti-spam : le déclenchement d'un checkout HelloAsso depuis un lien public réutilisable renforce le besoin de limitation de débit déjà identifié au Lot 8.
- [ ] Tests PHPUnit : validation du montant (bornes), création transaction/checkout depuis le handler, mise à jour `payment_status` par le webhook, comportement obligatoire vs facultatif.
- [ ] Test Playwright : parcours paiement facultatif (réponse acceptée sans payer) et paiement obligatoire (jusqu'au checkout HelloAsso, confirmation simulée côté webhook).
- [ ] **Validation non-régression** : formulaires sans widget paiement inchangés ; suite PHPUnit/Playwright complète verte.

### Lot 11 — Sous-formulaires (formulaires imbriqués)

Objectif : permettre à un formulaire d'inclure un widget de lien vers un autre formulaire GVV, ouvert dans un nouvel onglet, avec injection d'un résumé lecture seule de la réponse dans le formulaire maître. Dépend de Lot 6 (réutilisation du couple générique `subject_type`/`subject_id`) et reprend la convention de widget `data-gvv-type` introduite en Lot 5-bis/Lot 10.

Voir : [Design sous-formulaires](../design_notes/remplissage_formulaires_design.md#17-sous-formulaires-formulaires-imbriqués)

Décisions retenues :
- Sous-formulaire ouvert dans un nouvel onglet, jamais en iframe ni fusionné dans le DOM du maître — isolation CSS/JS complète.
- Corrélation avant soumission du maître via un jeton (`link_token`, colonne infrastructurelle sur `form_submissions`, sans signification métier), porté par le même circuit de session par slug que `subject_type`/`pilot_login`/`lock[]`.
- Vérification déclenchée par une action explicite de l'utilisateur (bouton « J'ai terminé, vérifier »), jamais par rechargement automatique ou `postMessage` silencieux.
- À la soumission finale du maître, bascule du jeton vers le rattachement générique définitif `subject_type='form_submission'`/`subject_id=<id maître>` — le jeton n'est qu'un échafaudage transitoire.
- Sous-formulaire soumis mais maître jamais validé : réponse conservée, marquée « non rattaché » en admin, jamais supprimée automatiquement.
- Resoumission = nouvelle soumission indépendante avec ses propres fichiers ; aucune suppression ni fusion avec la précédente.
- Hors périmètre V1 : sous-formulaires récursifs, sous-formulaires répétables (N réponses), édition en place d'une réponse déjà soumise.
- Point à trancher avant l'implémentation du backfill (voir Questions ouvertes du PRD, EF14) : conflit possible si le formulaire utilisé comme sous-formulaire est lui-même un formulaire de catégorie 3 avec son propre `subject_type`/`subject_id`.

- [x] Migration `144_forms_subform.php` : ajouter `link_token VARCHAR(64) NULL` (indexé `idx_link_token`) à `form_submissions`, pattern idempotent `add_column_if_missing`/`add_index_if_missing` (comme 140/141). `application/config/migration.php` mis à jour à la version 144.
- [x] Ajouter le type de champ `subform` dans `form_fields_model::$allowed_field_types` (sur le modèle de `signature`/`payment`).
- [x] Étendre `extract_html_fields` (`forms_admin`) pour détecter `<div data-gvv-type="subform" data-gvv-name="..." data-gvv-form-slug="..." data-gvv-required="...">` et enregistrer le champ de type `subform` (même limitation connue que `signature` : l'ENUM `form_fields.field_type` n'inclut pas `subform`, non corrigée ici — voir note Lot 13 ; le widget reste fonctionnel car entièrement piloté depuis le HTML brut, jamais depuis la ligne `form_fields`).
- [x] `Forms_renderer::inject_subform_widgets()`/`render_subform_widget()` : widget avec ses trois états (non rempli / en attente de vérification / rempli), lien vers le sous-formulaire portant `link_token`, bouton « J'ai terminé, vérifier » révélé au clic (JS partagé `build_subform_assets()`), résumé lecture seule + lien « Remplir à nouveau » une fois une réponse trouvée. Extension du script de validation partagé (`build_validation_script()`) pour bloquer la soumission du maître si un widget `data-gvv-required="true"` n'est pas au statut « submitted ».
- [x] `forms_public::index()`/`submit()` : `link_token` traité comme paramètre réservé supplémentaire (même pattern que `subject_type`/`pilot_login`), mémorisé en session par slug côté sous-formulaire (`forms_link_token_*`) et écrit sur sa propre soumission ; jetons par widget mémorisés côté maître (`forms_subform_tokens_*`) via `_apply_subform_widgets()`.
- [x] Point d'accès public (AJAX) `forms/subform-status/{token}` → `Forms_public::subform_status()` : retourne `{found, summary}` pour un `link_token`, sans authentification (même niveau d'exposition que les liens publics existants).
- [x] `forms_public::submit()` du formulaire maître : après création de la soumission, parcourt les jetons de sous-formulaires connus en session et appelle `Form_submissions_model::backfill_subject_from_link_token()` pour chacun (bascule `subject_type='form_submission'`/`subject_id=<id maître>`).
- [x] Conflit `subject_type`/`subject_id` (catégorie 3 vs sous-formulaire) tranché : `backfill_subject_from_link_token()` n'écrit que si `subject_type`/`subject_id` valent encore NULL — l'attachement catégorie 3 existant est prioritaire et n'est jamais écrasé (voir design § 17, PRD EF14).
- [x] Validation serveur des sous-formulaires requis (`Forms_public::_validate_required_subforms()`) sur toutes les pages du maître, pas seulement la page soumise.
- [x] Admin (`bs_submissions.php`) : badge « Non rattaché » (`forms_badge_subform_unattached`) pour les soumissions avec `link_token` renseigné mais `subject_type` toujours NULL.
- [x] Tests PHPUnit : `FormsSubformMigrationTest` (mysql, 6 tests, 29 assertions — migration up/down/idempotence, `get_by_link_token`, bascule avec/sans conflit, résumé excluant fichier/signature/subform) ; `FormsRendererSubformTest` (unit, 4 tests, 19 assertions — rendu des trois états, plusieurs widgets sur une même page). Suite complète (5 suites, 1658 tests) verte, mêmes 48 skips pré-existants.
- [ ] Test Playwright — **non réalisé** : pas de Chromium disponible dans cet environnement d'exécution (même limitation que Lot 13). Couverture équivalente obtenue par un parcours fonctionnel réel sur gvv.net (curl + session) : formulaire maître avec widget requis → soumission bloquée (message d'erreur explicite) → ouverture et soumission du sous-formulaire avec le jeton → `forms/subform-status/{token}` renvoie le résumé → rechargement du maître affiche le résumé et débloque la soumission → soumission du maître → rattachement `subject_type='form_submission'`/`subject_id=<id maître>` vérifié en base ; badge « Non rattaché » vérifié sur une soumission orpheline créée pour le test. Formulaires et soumissions de test supprimés après vérification.
- [x] Documentation utilisateur (`doc/users/fr/13_formulaires.md`) : nouvelle section « Sous-formulaires ».
- [x] **Validation non-régression** : `inscription-ulm` (catégorie 1), `attestation-de-formation-ulm` (catégorie 2) et `briefing-passager-ulm` (catégorie 3) accessibles (200) sur gvv.net après les changements, formulaires sans widget sous-formulaire inchangés ; suite PHPUnit complète verte (1658 tests, 0 échec, 48 skips pré-existants).

### Lot 12 — Export d'une réponse vers un formulaire de création GVV

Objectif : permettre, depuis la liste des réponses d'un formulaire, d'ouvrir un formulaire de création GVV standard (ex. création de membre) pré-rempli avec les valeurs d'une réponse. Dépend uniquement du socle (Lot 1) ; indépendant des lots 2 à 11.

Voir : [Design export vers formulaire de création](../design_notes/remplissage_formulaires_design.md#18-export-dune-réponse-vers-un-formulaire-de-création-gvv)

- [x] Migration `145_forms_export_target.php` : ajouter `target_url VARCHAR(255) NULL` et `target_label VARCHAR(100) NULL` à `forms`, pattern idempotent `add_column_if_missing`. `application/config/migration.php` mis à jour à la version 145.
- [x] `forms_model.php` : `target_url`/`target_label` gérés dans `create_form()`/`update_form()` (chaîne vide normalisée en NULL, comme `handler_class`).
- [x] `bs_form.php` : deux champs optionnels (URL cible, libellé du bouton) dans le formulaire admin d'édition d'un formulaire, avec texte d'aide ; `forms_admin::store()`/`update()` transmettent les champs POST (avec règles `max_length[255]`/`max_length[100]`).
- [x] `bs_submissions.php` : bouton `target_label` par ligne, pointant vers l'URL construite, affiché uniquement si `target_url` et `target_label` sont tous deux renseignés et la réponse n'est pas de type `upload` (pas de `form_submission_values` exploitable pour ce mode).
- [x] `Form_submissions_model::get_export_query_params()`/`build_export_url()` : concatène `target_url` (résolu via `site_url()` si ce n'est pas déjà une URL absolue) et une query string dérivée de `form_submission_values`, excluant les champs de type `file`, `signature`, `subform`, et les valeurs à forme JSON-array (champs à choix multiples, ex. `<select multiple>`).
- [x] Extension générique de `Gvv_Controller::create()` (`application/libraries/Gvv_Controller.php:233`) : fusionne les paramètres `$_GET` correspondant à une colonne connue de `defaults_list()` par-dessus celle-ci (valeurs tableau ignorées, aucune nouvelle clé introduite).
- [x] Tests PHPUnit : `FormsExportTargetMigrationTest` (mysql, 5 tests, 24 assertions — migration up/down/idempotence, exclusion fichier/signature/multi-valeurs, URL inchangée si aucun paramètre) ; `GvvControllerCreatePrefillTest` (mysql/HTTP, 3 tests — préremplissage par `$_GET` sur `membre/create`, comportement inchangé sans paramètre, paramètre inconnu ignoré). Suite complète (5 suites, 1656 tests) verte, mêmes 48 skips pré-existants.
- [ ] Test Playwright — **non réalisé** : pas de Chromium disponible dans cet environnement d'exécution (même limitation que Lots 11/13). Couverture équivalente obtenue par un parcours fonctionnel réel sur gvv.net (curl + session admin) : formulaire de test avec `target_url='membre/create'`/`target_label` configurés → soumission publique → bouton visible dans `forms_admin/submissions` avec l'URL exacte pré-remplie → `membre/create?mnom=...&memail=...` affiche bien les valeurs dans les champs. Formulaire et soumission de test supprimés après vérification.
- [x] Documentation utilisateur (`doc/users/fr/13_formulaires.md`) : nouvelle section « Exporter une réponse vers un formulaire de création ».
- [x] **Validation non-régression** : formulaire existant sans `target_url`/`target_label` (`forms_admin/submissions/10`) n'affiche aucun bouton d'export ; `membre/create` sans paramètre de requête inchangé (champ `mnom` vide) ; suite PHPUnit complète verte (1656 tests, 0 échec, 48 skips pré-existants).

### Lot 13 — Modification en place d'une réponse déjà soumise

Objectif : permettre à un admin de modifier une réponse en ligne déjà soumise, pour utiliser les formulaires comme support de gestion de procédure (compléter ou corriger une réponse après coup). Dépend uniquement du socle (Lot 1) et de Lot 2 (fichiers) ; réutilise le widget signature (Lot 5-bis) et l'autorisation par section déjà en place sur `submission_delete` (Lot 6, étape 6.6). Indépendant des lots 3, 4, 7, 9, 10, 11, 12.

Voir : [Design modification en place d'une réponse](../design_notes/remplissage_formulaires_design.md#19-modification-en-place-dune-réponse-déjà-soumise)

- [x] Pas de migration nécessaire : `form_submissions.updated_at`/`updated_by` sont déjà présents (champs d'audit standard), et `Form_submissions_model::save_submission_values()` est déjà un upsert par `(submission_id, field_id)`.
- [x] **Découverte en cours d'implémentation** : `form_fields.field_type` est un ENUM qui n'a jamais été migré pour inclure `'signature'` (`116_forms_core.php` ne liste que `text,email,date,number,textarea,select,radio,checkbox,file`). En pratique, tous les widgets signature sont donc du type "HTML-only" (déclarés uniquement via `data-gvv-type="signature"` dans `content_html`, sans ligne `form_fields`, identifiés par `widget_name` — mécanisme de la migration 137/Lot 5-bis), jamais du type `field_type='signature'` backé par une ligne `form_fields`. `submission_edit_submit()` a donc dû être étendu pour traiter ce cas HTML-only en plus des champs standards, sur le même modèle que `Forms_public::submit()`. Correction de l'ENUM non traitée ici (hors périmètre de ce lot, à trancher séparément si besoin).
- [x] Extraction de `Forms_public::process_uploaded_files()`/`save_signature_canvas()` vers `Forms_renderer::upload_submitted_files()`/`make_signature_file()` (refactor à filet de sécurité, même précédent que `File_rotator` au Lot 9) pour être réutilisables par `forms_admin` sans dupliquer la logique d'upload/signature.
- [x] `forms_admin::submission_edit($form_id, $submission_id)` : contrôle d'autorisation par section (même garde que `submission_delete`), refuse les réponses `submission_method != 'online'`, charge les valeurs (`form_submission_values`) et fichiers/signature existants (`form_submission_files`) de la soumission, réutilise le moteur de rendu multi-pages existant en mode édition. GVV prefill mécanismes A/B non ré-appliqués en mode édition (limitation connue, voir note ci-dessous).
- [x] Adapter la source de pré-remplissage du rendu (`Forms_renderer::normalize_fields_for_view`/`repopulate_html_fields`, déjà conçus pour prendre un `old_values` par `field_id`) pour accepter en alternative les valeurs d'une soumission existante — aucune modification de ces méthodes n'a été nécessaire, seule la construction de la map source diffère.
- [x] Champs fichier en mode édition : `Forms_renderer::inject_existing_file_hints()` (nouvelle méthode) affiche le nom du fichier déjà soumis ; champ laissé vide = conserver, nouveau fichier fourni = remplacer.
- [x] Widget signature en mode édition : `Forms_renderer::render_signature_widget()`/`inject_signature_widgets()` étendus (`$existing_preview_url`) pour afficher la signature existante en lecture seule (aperçu `<img>`, `required` désactivé côté widget) ; laisser le widget inchangé à la resoumission = conserver, dessiner/uploader/taper une nouvelle signature = remplacer.
- [x] `forms_admin::submission_edit_submit($form_id, $submission_id)` : réutilise la validation serveur centralisée existante, puis `save_submission_values()` pour les champs standard ; pour chaque fichier/signature effectivement remplacé (champ `form_fields` ou widget HTML-only), écrit le nouveau `form_submission_files` puis supprime l'ancien enregistrement et son fichier disque une fois l'écriture confirmée (nouvelle méthode modèle `delete_submission_file()`) ; met à jour `updated_at`/`updated_by` ; ne modifie jamais `id`, `submission_uuid`, `submitted_at`, `subject_type`/`subject_id`, `submission_method`.
- [x] Bouton "Modifier" dans `bs_submissions.php` (liste) et `bs_submission.php` (détail), visible uniquement pour les réponses `submission_method='online'`.
- [x] Affichage de la date de dernière modification dans le détail admin d'une réponse (`bs_submission.php`) quand `updated_at` diffère de `created_at`.
- [x] Traductions (fr/en/nl) : `forms_button_edit_submission`, `forms_edit_title`, `forms_edit_button_save`, `forms_label_last_modified`.
- [x] Tests PHPUnit — `FormsSubmissionEditTest.php` (mysql, 5 tests, 39 assertions), même harnais HTTP que `FormsAdminSubmissionRotateTest`/`FormsUploadSubmitTest` (contrôleur non testable sans round-trip HTTP réel) :
  - accès refusé sans authentification (redirection login) ;
  - accès refusé pour une réponse `submission_method='upload'` ;
  - pré-remplissage : la valeur déjà soumise apparaît dans le champ texte rendu, le nom du fichier existant et l'aperçu de la signature existante sont affichés ;
  - resoumission sans toucher au fichier ni à la signature : les deux restent inchangés en base et sur disque ;
  - resoumission avec remplacement du fichier et de la signature (widget HTML-only) : anciens supprimés (base + disque), nouveaux présents ; `id`/`submission_uuid`/`submitted_at` inchangés, `updated_by` renseigné.
- [x] **Bug découvert et corrigé pendant cette étape** : les cases à cocher n'étaient jamais pré-remplies en mode édition. Cause : `field_type='checkbox'` désigne en pratique une case unique (`value_text` = `"on"`/`""`), jamais un groupe multi-valeurs — `extract_html_fields()` dédoublonne par nom HTML exact, donc plusieurs cases partageant un nom ne peuvent jamais produire qu'une seule ligne `form_fields`. `Forms_admin::_old_values_from_submission_values()` forçait pourtant un décodage JSON systématique (→ tableau vide), et `Forms_renderer::repopulate_html_fields()` ne savait cocher qu'à partir d'un tableau. Corrigé aux deux endroits (décodage JSON uniquement si la valeur y ressemble ; case cochée si la valeur scalaire est non vide, tableau géré en plus pour compatibilité). Nouveau test unitaire `FormsRendererCheckboxTest.php` (4 tests), reproduisant la forme réelle du formulaire `attestation_de_fin_de_formation_spl-planeur`. Vérifié en conditions réelles sur la soumission existante (id=5, formulaire id=8) : les 14 cases se pré-remplissent exactement comme en base.
- [ ] Test Playwright — **non réalisé** : pas de Chromium disponible dans cet environnement d'exécution (`npx playwright install chrome` requis). Couverture équivalente obtenue via les tests PHPUnit HTTP ci-dessus, qui exercent le vrai endpoint sur le serveur de dev.
- [x] Documentation utilisateur (`doc/users/fr/13_formulaires.md`) : nouvelle section « Modifier une réponse déjà soumise » (déclenchement, pré-remplissage, conservation/remplacement fichiers et signature, enregistrement en place).
- [x] **Validation non-régression** : suite complète `./run-all-tests.sh` (5 suites, 1638 tests, 0 échec, 48 skips préexistants) verte après implémentation, y compris le refactor `Forms_renderer`/`Forms_public` (vérifié spécifiquement via `FormsUploadSubmitTest`/`FormsAdminSubmissionRotateTest`, qui exercent le code extrait) et le correctif checkbox.

**Limitation connue non traitée dans cette passe** : le mode édition ne ré-applique pas les mécanismes de pré-remplissage GVV A/B (`data-gvv-source`, verrouillage `lock[]`) — un champ verrouillé lors de la génération initiale n'est pas re-verrouillé en mode édition. Acceptable pour l'usage principal visé (compléter des réponses de catégorie 1/2 simples) ; à revisiter si l'édition doit un jour couvrir des formulaires de catégorie 3 avec champs verrouillés.

### Lot 14 — Lien de modification public à usage unique (EF16-bis)

Objectif : permettre à l'utilisateur d'origine de reprendre une réponse déjà soumise via un lien de modification public, généré à la demande depuis la liste admin, à usage unique et à expiration automatique. Dépend du socle (Lot 1), des fichiers (Lot 2) et de Lot 13 (réutilise le moteur de rendu/validation de la modification en place admin). Indépendant des lots 3, 4, 7, 9, 10, 11, 12.

Voir : [Design lien de modification public](../design_notes/remplissage_formulaires_design.md#20-lien-de-modification-public-à-usage-unique-ef16-bis)

Décisions retenues :
- Token dédié (`edit_token`), distinct de `submission_uuid`, un seul actif à la fois — toute régénération invalide l'ancien, utilisé ou non.
- Consommé uniquement à la resoumission réussie, jamais à la simple consultation.
- Consommation atomique (`UPDATE ... WHERE edit_token = ?`, 0 ligne affectée → échec explicite) pour gérer la concurrence sans verrou applicatif supplémentaire.
- Expiration fixe 7 jours après génération, indépendamment de l'usage.
- Génération à la demande depuis la liste admin des réponses (bouton "Modifier le formulaire"), affichage du lien pour transmission manuelle — pas d'envoi automatique par email, pas d'indicateur d'état de lien à maintenir dans la liste.

- [ ] Migration `1XX_forms_edit_token.php` : ajouter `edit_token VARCHAR(64) NULL` (indexé) et `edit_token_expires_at DATETIME NULL` à `form_submissions`, pattern idempotent `add_column_if_missing`/`add_index_if_missing`.
- [ ] Mettre à jour `application/config/migration.php`.
- [ ] `Form_submissions_model::generate_edit_token($submission_id)` : génère un token aléatoire (UUID v4), écrit `edit_token`/`edit_token_expires_at = NOW() + 7 jours`.
- [ ] `Form_submissions_model::get_by_edit_token($token)` : résout la soumission par token, vérifie la non-expiration.
- [ ] `Form_submissions_model::consume_edit_token($submission_id, $token)` : `UPDATE ... SET edit_token = NULL WHERE id = ? AND edit_token = ?`, retourne le nombre de lignes affectées.
- [ ] Bouton "Modifier le formulaire" dans `bs_submissions.php` : appelle la génération, affiche le lien construit (`site_url("forms/edit/{slug}/{token}")` ou équivalent) pour copie/transmission par l'admin. Visible uniquement pour les réponses `submission_method = 'online'`.
- [ ] `forms_public::edit($slug, $token)` : résout la soumission, refuse si token invalide/expiré/absent avec message explicite dédié (pas de formulaire vide ni de 404 générique), sinon réutilise le moteur de rendu multi-pages en mode édition (même source de pré-remplissage que `forms_admin::submission_edit`, section 19/Lot 13).
- [ ] `forms_public::edit_submit($slug, $token)` : consomme le token de façon atomique avant tout enregistrement ; si la consommation échoue (0 ligne), rend l'erreur "lien déjà utilisé" sans enregistrer ; sinon réutilise la logique d'enregistrement de `forms_admin::submission_edit_submit` (conserver/remplacer fichiers et signature, mise à jour `updated_at`/`updated_by`, `id`/`submission_uuid`/`submitted_at`/`subject_type`/`subject_id`/`submission_method` inchangés).
- [ ] Vue dédiée "lien invalide" (`forms_public`) : message explicite unique pour les trois causes (expiré, déjà consommé, remplacé), pas de distinction affichée à l'utilisateur.
- [ ] Traductions (fr/en/nl) : libellé bouton, message lien invalide, message double soumission concurrente.
- [ ] Tests PHPUnit : génération/régénération invalide l'ancien token, résolution par token valide/expiré/absent, consommation atomique (double appel simultané simulé → un seul succès), resoumission met à jour la réponse existante sans créer de nouvelle ligne.
- [ ] Test Playwright ou parcours fonctionnel réel équivalent (selon disponibilité Chromium dans l'environnement, cf. Lots 11-13) : génération du lien en admin → ouverture publique → formulaire pré-rempli → resoumission → lien redevenu invalide.
- [ ] Documentation utilisateur (`doc/users/fr/13_formulaires.md`) : nouvelle section « Lien de modification public ».
- [ ] **Validation non-régression** : la modification admin existante (Lot 13) reste inchangée ; formulaires sans réponse modifiée via lien public inchangés ; suite PHPUnit/Playwright complète verte.

### Lot 15 — Complétude des pièces obligatoires (EF17)

Objectif : permettre la soumission d'un formulaire même si des pièces obligatoires (fichier/signature) sont manquantes, tout en rendant cette incomplétude visible côté public et admin. Dépend du socle (Lot 1) ; s'articule avec Lot 14 (la liste de complétude s'affiche aussi en mode reprise) sans en dépendre techniquement. Indépendant des lots 3, 4, 7, 9, 10, 11, 12.

Voir : [Design complétude des pièces obligatoires](../design_notes/remplissage_formulaires_design.md#21-complétude-des-pièces-obligatoires-ef17)

Décisions retenues :
- Comportement non-bloquant déterminé par le type de champ (`file`/`signature`) uniquement — pas de flag configurable par champ en plus de `is_required`.
- `required_group` sur `form_fields` pour les exigences "un parmi plusieurs" (ex. carte d'identité OU passeport).
- Calcul de complétude à la volée, pas de colonne dénormalisée.
- Liste des pièces manquantes toujours affichée côté public (saisie initiale et reprise), par libellé de champ.

- [ ] Migration `1XX_forms_required_group.php` : ajouter `required_group VARCHAR(50) NULL` à `form_fields`, pattern idempotent `add_column_if_missing`.
- [ ] Mettre à jour `application/config/migration.php`.
- [ ] `Forms_validation::validate_field_value()` : ne plus bloquer la soumission pour `is_required` sur `field_type IN ('file', 'signature')` — les autres types inchangés.
- [ ] `form_fields_model.php`/vue admin des champs : `required_group` éditable à côté de `is_required` pour les champs fichier.
- [ ] `Form_submissions_model::get_missing_required_pieces($submission_id)` (ou équivalent) : calcule les exigences non satisfaites (champs isolés + groupes) par jointure `form_fields`/`form_submission_files`.
- [ ] `Forms_renderer` : injection de la liste des pièces manquantes en bas du formulaire (rendu public, saisie initiale et mode édition/reprise Lot 14), avec formulation "au moins un parmi" pour les groupes.
- [ ] `bs_submissions.php` (liste admin) : indicateur de complétude par ligne, calculé avec les mêmes règles.
- [ ] Traductions (fr/en/nl) : libellé de la liste de pièces manquantes, formulation "au moins un parmi", libellé de l'indicateur admin.
- [ ] Tests PHPUnit : soumission acceptée avec pièce obligatoire manquante (isolée et en groupe), calcul de complétude correct (isolé satisfait/non satisfait, groupe satisfait par un seul membre), champs non-fichier toujours bloquants.
- [ ] Test Playwright ou parcours fonctionnel réel équivalent : soumission incomplète acceptée, liste des pièces manquantes affichée, indicateur admin cohérent.
- [ ] Documentation utilisateur (`doc/users/fr/13_formulaires.md`) : nouvelle section « Pièces obligatoires et complétude ».
- [ ] **Validation non-régression** : formulaires sans champ fichier/signature obligatoire inchangés ; champs texte/select obligatoires restent bloquants ; suite PHPUnit/Playwright complète verte.

### Lot 16 — Modèle PDF vierge téléchargeable (EF18)

Objectif : sur un formulaire où la soumission par téléchargement est activée (Lot 9, `allow_upload_response`), permettre à l'admin d'associer un PDF vierge (le formulaire imprimable) que l'utilisateur télécharge avant de le remplir à la main et de le renvoyer scanné. Dépend de Lot 9 (colonne `allow_upload_response`) et de Lot 2-bis/2-ter (`Forms_file_storage`, `meta.json`). Indépendant des lots 3 à 8, 10 à 15.

Voir : [Design modèle PDF vierge téléchargeable](../design_notes/remplissage_formulaires_design.md#22-modèle-pdf-vierge-téléchargeable-ef18) et PRD EF18.

Décisions retenues :
- Pas de nouvelle colonne DB : la présence/absence du PDF est purement fichier (`uploads/formulaires/{code}/template.pdf`), même famille de pattern que les images du formulaire — pas de flag séparé à synchroniser avec la réalité du disque.
- Un seul fichier par formulaire, nom fixe (`template.pdf`, pas de suffixe/timestamp) : un nouveau dépôt écrase l'unique fichier existant, ce qui élimine par construction tout risque de fichier orphelin après remplacement ou suppression du formulaire.
- Stocké à la racine de `uploads/formulaires/{code}/` (pas dans `images/`) pour que `rename_form_dir()`/`copy_form_dir()`/`delete_form_dir()` et `form_backup()` le prennent en charge sans aucune modification (ils opèrent déjà sur les fichiers de premier niveau du répertoire).
- PDF optionnel même si `allow_upload_response = 1` : simple présence/absence de fichier, aucun message d'erreur ni blocage si absent.
- Comme les images, jamais touché par `form_import_zip()`/`form_restore()`/`replace_all_from_dir()` (qui ne remplacent que `page*.html`/`style.css`/`meta.json`) : géré exclusivement par son propre endpoint d'upload/suppression admin.
- Sauvegarde/restauration globale (`admin.php::backup_media()`/`restore_media_from_backup()`) : aucune modification nécessaire, tout `uploads/` est déjà tar'é/restauré tel quel.

#### Étape 1 — Stockage et métadonnées ✅

- [x] `Forms_file_storage` : ajouter `write_pdf_template($code, $content)`, `pdf_template_path($code)`, `read_pdf_template($code)`, `has_pdf_template($code)`, `delete_pdf_template($code)` — calqués sur `write_image()`/`image_path()`/`read_image()`/`delete_image()`.
- [x] `forms_admin::_sync_meta_file()` : ajouter `'pdf_template' => $this->forms_file_storage->has_pdf_template($form['code'])` dans le tableau `$meta`.
- [x] Vérifié (test, aucun nouveau code nécessaire) que `rename_form_dir()`/`copy_form_dir()`/`delete_form_dir()` déplacent/copient/suppriment bien `template.pdf` sans aucune modification, conformément au design (`form_backup()` non re-testé ici, déjà couvert par construction — même mécanisme `zip -r .` que pour les images).
- [x] Test PHPUnit `FormsFileStorageTest` étendu (10 tests ajoutés, 44 au total) : écriture/lecture/suppression/écrasement (pas d'accumulation) du template PDF, absence par défaut, présence après `copy_form_dir()` (source et copie), présence après `rename_form_dir()`, présence maintenue après `delete_form_dir()` d'un autre formulaire.
- [x] **Validation** : suite complète (`./run-all-tests.sh`) verte — 1930 tests, 0 échec, mêmes 63 skips pré-existants.

#### Étape 2 — Interface admin (dépôt, remplacement, suppression) ✅

- [x] `forms_admin::pdf_template_upload($form_id)` : `$_FILES` brut (pas la lib CI upload, même pattern que `image_upload()`), vérification de taille (10 Mo, alignée sur la limite déjà en place pour le fichier de réponse scannée) et de type (`mime_content_type()` + signature `%PDF-`, cohérent avec le reste du contrôleur qui utilise déjà `mime_content_type()` ailleurs plutôt que `finfo`), écrase l'éventuel fichier existant via `write_pdf_template()`, resynchronise `meta.json`.
- [x] `forms_admin::pdf_template_delete($form_id)` : même garde d'autorisation (`load_form_or_redirect()`) que `image_delete()`, supprime le fichier et resynchronise `meta.json`.
- [x] `bs_form.php` : nouvelle carte "Formulaire vierge (PDF)" (calquée sur la carte "Images") — si un PDF est présent : lien de téléchargement, bouton "Supprimer" (confirmation) ; sinon formulaire de dépôt seul.
- [x] Traductions fr/en/nl ajoutées avec le code (10 clés : titre/aide de la carte, boutons envoi/téléchargement/suppression, confirmation, messages succès/erreur).
- [x] Test PHPUnit `FormsPdfTemplateTest` (mysql, même harnais HTTP que `FormsUploadSubmitTest`/`FormsAdminSubmissionRotateTest`, 6 tests, 26 assertions) : dépôt valide (fichier + `meta.json` à jour), remplacement (ancien contenu remplacé, un seul `.pdf` sur disque — pas d'accumulation), type refusé (aucun fichier écrit), requête non authentifiée (redirection login, aucune écriture), suppression (fichier et `meta.json` mis à jour), suppression sans fichier existant (no-op, pas d'erreur).
- [x] **Validation** : suite complète (`./run-all-tests.sh`) verte — 1936 tests, 0 échec, mêmes 63 skips pré-existants. Vérification fonctionnelle réelle sur gvv.net (Chromium indisponible dans cet environnement — parcours HTTP équivalent via `curl` avec session admin, formulaire de test temporaire supprimé après vérification) : carte visible sur la fiche d'édition, dépôt d'un PDF → fichier écrit + `meta.json.pdf_template=true` + lien de téléchargement affiché (route publique pas encore implémentée à ce stade, 404 attendu — voir étape 3), suppression → fichier disparu + `meta.json.pdf_template=false`.

#### Étape 3 — Téléchargement public ✅

- [x] `forms_public::pdf_template($code)` : même principe que `image()` (vérification de confinement par `realpath()` sur `form_dir($code)`, `Content-Type: application/pdf`, `show_404()` si absent).
- [x] `bs_show.php` : lien "Télécharger le formulaire vierge (PDF)" en haut de la page 1 (bloc titre/description), visible si `allow_upload_response` est vrai et qu'un PDF est présent ; libellé volontairement distinct du bouton existant "Télécharger un formulaire prérempli" (qui ouvre la modale d'envoi du scan, pas un téléchargement).
- [x] `forms_public::index()` : passe `has_pdf_template` à la vue (`Forms_file_storage::has_pdf_template()`).
- [x] Test PHPUnit `FormsPublicPdfTemplateTest` (mysql, HTTP réel, 7 tests, 9 assertions) : téléchargement d'un template existant (Content-Type, contenu), 404 si absent, 404 sur un code de formulaire inconnu, 404 sur une tentative de path traversal, lien présent/absent selon présence du PDF, lien absent quand `allow_upload_response=0` même si un PDF existe.
- [x] **Validation** : suite complète (`./run-all-tests.sh`) verte — 1943 tests, 0 échec, mêmes 63 skips pré-existants. Parcours fonctionnel réel sur gvv.net (Chromium indisponible, `curl` utilisé à la place, cf. Lots 11-13) : page publique d'un formulaire de test → lien visible avec le bon libellé → téléchargement renvoie `Content-Type: application/pdf` et le bon contenu → 404 sur code inconnu et sur tentative de path traversal. Formulaire de test et fichiers supprimés après vérification.

#### Étape 4 — Traductions ✅

- [x] Clés fr/en/nl ajoutées au fil des étapes 2 et 3 (11 clés) : `forms_title_pdf_template`, `forms_help_pdf_template`, `forms_button_upload_pdf_template`, `forms_button_download_pdf_template`, `forms_confirm_delete_pdf_template`, `forms_success_pdf_template_uploaded`, `forms_success_pdf_template_deleted`, `forms_error_pdf_template_missing`, `forms_error_pdf_template_too_large`, `forms_error_pdf_template_invalid`, `forms_button_download_blank_pdf`.
- [x] **Validation** : vérification de complétude — chaque clé référencée dans le code (`forms_admin.php`, `forms_public.php`, `bs_form.php`, `bs_show.php`) présente exactement une fois dans les trois fichiers `application/language/{french,english,dutch}/forms_lang.php` (aucune clé manquante, aucun doublon) ; `php -l` propre sur les trois fichiers.

#### Étape 5 — Documentation et validation finale ✅

- [x] `doc/users/fr/13_formulaires.md` : nouvelle section « Associer un formulaire vierge téléchargeable » (dépôt/remplacement/suppression admin, condition d'apparition du lien public, portée du cycle de vie), à la suite de la section « Accepter une réponse déposée par scan ou photo », avec renvois vers `13_formulaires_creation.md` (« Ajouter une image », « Modifier le contenu d'un formulaire existant »).
- [x] **Validation non-régression** : suite PHPUnit complète verte (1943 tests, 0 échec, mêmes 63 skips pré-existants). Vérifié en conditions réelles sur gvv.net avec un formulaire de test temporaire (supprimé après vérification) : `form_backup()` inclut `template.pdf` dans le ZIP exporté sans aucune modification de code ; `duplicate()` copie le PDF à l'identique (contenu vérifié octet pour octet) vers le formulaire dupliqué ; `update()` avec changement de `code` déplace le PDF avec le reste du répertoire (`rename_form_dir()`) ; `delete()` supprime le répertoire et le PDF pour l'original comme pour la copie, aucun fichier orphelin.

**Lot 16 terminé.**

**Ajustement UX post-livraison** (24 août 2026) : la carte "Formulaire vierge (PDF)" a été déplacée juste sous la case "Autoriser la soumission par téléchargement (scan)" dans `bs_form.php`, avec visibilité conditionnée à l'état de la case (affichée si cochée, masquée sinon — état initial calculé côté serveur, bascule en direct via un petit script au `change` de la case). Elle apparaissait auparavant dans une carte séparée après "Images", sans lien visuel avec la case dont elle dépend fonctionnellement — source de confusion signalée après livraison (carte présente mais son utilité pas évidente sans dérouler toute la page). Contrainte technique résolue au passage : les boutons/l'input fichier de la carte ne peuvent pas vivre dans un `<form>` imbriqué dans le formulaire principal (HTML invalide) ; ils utilisent l'attribut `form="..."` pour cibler deux `<form>` vides et invisibles déclarés juste après la fermeture du formulaire principal, qui portent seuls `action`/`method`/`enctype` — aucun changement côté contrôleur. Vérifié en conditions réelles sur gvv.net (formulaires de test temporaires, supprimés après vérification) : bascule d'affichage au clic sur la case, dépôt/suppression du PDF toujours fonctionnels, soumission du formulaire principal (titre, case à cocher, etc.) inchangée. Suite complète verte (1943 tests, 0 échec, mêmes 63 skips).

## Stratégie de livraison

### Phase 1 — Socle formulaires autonome (catégorie 1)

Objectif : livrer rapidement une gestion de formulaire à la Google Forms, sans pré-remplissage GVV, mais avec support des fichiers.

Lots inclus : 1, 2, 3.

### Phase 2 — Documents inline dans les formulaires

Objectif : ajouter les compléments non bloquants pour le socle, notamment l'import PDF -> HTML.

Lots inclus : 4.

### Phase 3 — Intégration GVV contextuelle (catégorie 2)

Objectif : ajouter le pré-remplissage GVV (mécanisme A et B), les signatures (canvas + upload + pré-remplissage profil GVV), la sauvegarde/reprise de saisie multi-session, les pages conditionnelles.

Lots inclus : 5, 5-bis.

### Phase 4 — Intégration workflow GVV (catégorie 3)

Objectif : permettre aux formulaires de déclencher des actions GVV à la soumission. Migration `briefing_passager_ulm` comme cas de référence.

Lots inclus : 6.

### Phase 5 — Cartes dynamiques dans les dashboards

Objectif : exposer les formulaires et autres fonctionnalités dans les dashboards GVV via un mécanisme de configuration sans développement. Peut être réalisé en parallèle des phases 2 à 4.

Lots inclus : 7.

### Phase 6 — Documentation et validation globale

Objectif : stabiliser, documenter et valider l'ensemble des phases précédentes.

Lots inclus : 8.

### Phase 7 — Paiement en ligne intégré

Objectif : permettre à un formulaire de déclencher un paiement HelloAsso rattaché à un compte GVV, obligatoire ou facultatif. Indépendant des phases 2, 3 et 5 ; dépend du socle (phase 1) et de l'infrastructure handler du Lot 6 (phase 4).

Lots inclus : 10.

### Phase 8 — Sous-formulaires

Objectif : permettre à un formulaire d'inclure un lien vers un autre formulaire GVV, ouvert dans un nouvel onglet, avec injection de la réponse dans le formulaire maître. Dépend du socle (phase 1) et de l'infrastructure `subject_type`/`subject_id` (phase 4, Lot 6).

Lots inclus : 11.

### Phase 9 — Export d'une réponse vers un formulaire de création GVV

Objectif : permettre d'ouvrir un formulaire de création GVV standard pré-rempli avec les valeurs d'une réponse, depuis la liste des réponses. Dépend uniquement du socle (phase 1) ; indépendant de toutes les autres phases.

Lots inclus : 12.

### Phase 10 — Modification en place d'une réponse déjà soumise

Objectif : permettre à un admin de modifier une réponse en ligne déjà soumise depuis la liste des réponses, pour utiliser les formulaires comme support de gestion de procédure. Dépend du socle (phase 1) et des fichiers (Lot 2) ; réutilise le widget signature (phase 3) et l'autorisation par section (phase 4). Indépendant des phases 2, 5, 7, 8, 9.

Lots inclus : 13.

### Phase 11 — Lien de modification public à usage unique

Objectif : permettre à l'utilisateur d'origine de reprendre une réponse déjà soumise via un lien public à usage unique, sans intervention admin au-delà de sa génération. Dépend du socle (phase 1), des fichiers (phase 1, Lot 2) et de la modification en place admin (phase 10, Lot 13).

Lots inclus : 14.

### Phase 12 — Complétude des pièces obligatoires

Objectif : permettre la soumission d'un formulaire avec des pièces obligatoires manquantes, en rendant cette incomplétude visible et actionnable côté public et admin. Dépend uniquement du socle (phase 1) ; s'articule avec la phase 11 sans en dépendre techniquement.

Lots inclus : 15.

### Phase 13 — Modèle PDF vierge téléchargeable

Objectif : permettre à l'admin d'associer un PDF vierge téléchargeable à un formulaire où la soumission par téléchargement est activée. Dépend du socle (phase 1) et de la soumission par téléchargement (phase 1, Lot 9). Indépendant de toutes les autres phases.

Lots inclus : 16.

## Ordre de réalisation recommandé

1. Lot 1 (migration)
2. Lot 2 (réponses et fichiers)
3. Lot 3 (impression et archivage)
4. Lot 4 (documents inline dans les formulaires)
5. Lot 4-bis (paramètres de configuration formulaires)
6. Lot 5 (pré-remplissage GVV mécanisme A + workflows) — dépend de Lot 4-bis pour `config.*`
7. Lot 5-bis (signatures canvas + upload + pré-remplissage profil)
8. Lot 5-ter (page de génération + évolutions events)
9. Lot 6 (intégration workflow GVV — handlers + migration briefing_passager)
10. Lot 7 (cartes dynamiques dans les dashboards) — indépendant, réalisable dès Lot 1 terminé
11. Lot 9 (soumission par téléchargement) — dépend de Lot 2, indépendant des lots 4 à 7
12. Lot 10 (paiement en ligne intégré) — dépend du socle (Lot 1) et de l'infrastructure handler (Lot 6), indépendant des lots 4, 5, 7
13. Lot 11 (sous-formulaires) — dépend du socle (Lot 1) et de `subject_type`/`subject_id` (Lot 6), indépendant des lots 4, 5, 7, 9, 10
14. Lot 12 (export vers formulaire de création GVV) — dépend uniquement du socle (Lot 1), indépendant de tous les autres lots
15. Lot 13 (modification en place d'une réponse) — dépend du socle (Lot 1) et des fichiers (Lot 2), indépendant des lots 3, 4, 7, 9, 10, 11, 12
16. Lot 14 (lien de modification public à usage unique) — dépend du socle (Lot 1), des fichiers (Lot 2) et de Lot 13
17. Lot 15 (complétude des pièces obligatoires) — dépend uniquement du socle (Lot 1)
18. Lot 16 (modèle PDF vierge téléchargeable) — dépend de Lot 9 (`allow_upload_response`), indépendant des autres lots
19. Lot 8 (documentation et validation)

## Critères de fin

### Catégorie 1 (autonome)
- Un admin peut créer, modifier, supprimer et publier un formulaire multi-pages.
- Un utilisateur non authentifié peut remplir via lien public.
- Les admins consultent les réponses et visualisent images/PDF soumis.
- Les fichiers sont supportés dès la première phase de livraison.
- Un PDF imprimable est générable depuis une réponse.
- Une réponse est archivable dans `archived_documents` pour un pilote.
- Sur un formulaire où l'option est activée, un utilisateur peut télécharger un scan/photo du formulaire imprimé à la place du remplissage en ligne ; l'admin la retrouve dans la liste des réponses avec miniature, rotation et suppression fonctionnelles.
- Sur un formulaire où cette option est activée, un PDF vierge peut être associé par l'admin (dépôt, remplacement, suppression) et est proposé au téléchargement dès la page 1 de la page publique s'il est présent ; aucun fichier orphelin ne subsiste après remplacement ou suppression du formulaire.

### Catégorie 2 (contextuel GVV)
- Pré-remplissage mécanisme A (`data-gvv-source`) opérationnel et sécurisé.
- Pré-remplissage mécanisme B (paramètres URL directs + `lock[]`) opérationnel.
- Un champ signature peut être soumis en mode canvas ou upload image.
- La signature d'un profil GVV peut pré-remplir le widget.
- Un instructeur peut définir sa propre signature de référence (self-service) ; un club-admin peut l'importer pour son compte. Cette signature ne pré-remplit une attestation/fiche de test que lorsque l'instructeur connecté est celui désigné dans le formulaire.

### Catégorie 3 (intégré workflow)
- `subject_type`/`subject_id` opérationnel sur `form_submissions` : détection « réponse existante » et bascule à la suppression fonctionnelles sans dépendre d'`archived_documents`.
- `BriefingPassagerUlmHandler` opérationnel : VLD mis à jour depuis la réponse soumise.
- Icône « briefing fait » de `vols_decouverte` basée sur `form_submissions`, ancien mécanisme documentaire retiré du chemin de détection (mais pas supprimé du code tant que non décidé séparément).
- Non-régression catégorie 1 et 2 vérifiée à chaque étape du Lot 6.
- PHPUnit et Playwright verts sur les trois catégories.

### Paiement en ligne
- Un formulaire peut porter au maximum un paiement HelloAsso, obligatoire ou facultatif selon configuration.
- Paiement facultatif : la réponse est acceptée que le paiement soit effectué ou non.
- Paiement obligatoire : une réponse dont le paiement n'est pas confirmé est rejetée, tout en restant consultable en admin.
- Le statut du paiement est visible sans ambiguïté dans le détail admin d'une réponse et dans son PDF imprimable.

### Sous-formulaires
- Un formulaire peut inclure un widget de lien vers un autre formulaire GVV, ouvert dans un nouvel onglet.
- La réponse du sous-formulaire est vérifiable et affichée en lecture seule depuis le formulaire maître, sans rechargement de page.
- Le rattachement définitif (`subject_type`/`subject_id`) est effectif à la soumission du maître ; une réponse de sous-formulaire soumise mais dont le maître n'est jamais validé reste conservée et identifiable en admin.
- Une resoumission du sous-formulaire n'affecte pas les fichiers d'une soumission précédente.

### Export vers formulaire de création GVV
- Un formulaire avec `target_url`/`target_label` renseignés affiche un bouton par ligne dans la liste des réponses.
- Le clic ouvre le formulaire cible avec les champs correspondants pré-remplis (noms de champs identiques entre source et cible).
- Les champs fichier, signature et à choix multiples ne sont jamais inclus dans l'URL d'export.
- Un formulaire sans `target_url`/`target_label` n'affiche aucun bouton et son comportement est inchangé.

### Modification en place d'une réponse
- Un admin peut modifier une réponse en ligne déjà soumise depuis la liste des réponses.
- Le formulaire pré-rempli permet la resoumission sans créer de nouvelle réponse : `id` et `submission_uuid` inchangés.
- Une signature ou un fichier peut être conservé ou remplacé ; en cas de remplacement, l'ancien est supprimé du stockage une fois le nouveau enregistré.
- Une réponse de type téléchargement n'affiche pas le bouton "Modifier".

### Lien de modification public
- Un lien de modification généré depuis l'admin est à usage unique : consommé à la resoumission, invalidé par toute régénération ultérieure, expiré après 7 jours.
- Un accès avec un lien invalide affiche un message explicite dédié.
- Une double soumission concurrente avec le même lien n'aboutit qu'une seule fois.

### Complétude des pièces obligatoires
- Une réponse avec des pièces fichier/signature obligatoires manquantes est acceptée.
- La liste des pièces manquantes est affichée par libellé, en saisie initiale et en reprise.
- Un groupe de pièces alternatives est satisfait dès qu'un seul membre est fourni.
- L'indicateur de complétude est visible dans la liste admin des réponses.

### Qualité transversale
- Chaque lot commence par une migration explicite et testée.
- Les documents archivés référencés sont visibles inline avec scroll.
- L'import PDF -> HTML fonctionne.
