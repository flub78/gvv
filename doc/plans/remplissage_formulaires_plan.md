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

### Lot 2-bis — Synchronisation fichiers disque (option)

A analyser, pas sûr que ce soit vraiment utile.

- [ ] Migration : ajouter `content_hash VARCHAR(32) NULL` sur `form_pages` et `css_hash VARCHAR(32) NULL` sur `forms`.
- [ ] Créer le répertoire `application/forms_templates/` avec `.htaccess` de protection (si nécessaire selon config serveur).
- [ ] Lors de la sauvegarde web d'une page (`page_create` / `page_update`) : calculer MD5, stocker `content_hash`, écrire le fichier disque.
- [ ] Lors de la sauvegarde web du CSS global d'un formulaire : calculer MD5, stocker `css_hash`, écrire le fichier disque.
- [ ] Ajouter le bouton "Actualiser depuis le disque" sur la vue page admin : lire le fichier, comparer le hash, mettre à jour la base si différent, afficher le résultat.
- [ ] Ajouter le bouton "Exporter vers le disque" sur la vue page admin : écrire le fichier depuis le contenu en base (même si le fichier existe).
- [ ] Même logique pour le CSS global : boutons "Actualiser" et "Exporter" sur la vue formulaire.
- [ ] Afficher le chemin du fichier attendu dans l'admin pour guider le développeur.
- [ ] Test PHPUnit : hash calculé à la sauvegarde, fichier écrit, sync file→DB met à jour le contenu et le hash.

### Lot 3 — Impression et archivage (approche simplifiée)

- [x] Implémenter rendu PDF imprimable d'une réponse.
- [ ] Ajouter dans la liste des réponses un bouton qui ouvre le formulaire existant de création de document archivé.
- [ ] Pré-remplir le formulaire de création de document avec le PDF imprimable de la réponse à la place du sélecteur de fichier.
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
| 4 | Pré-remplissage profil GVV | Moyenne — nouveau champ `membres.signature_path` |
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

A faire lorsque le premier cas d'utilisation se présentera.

Voir : [Design cartes dynamiques](../design_notes/remplissage_formulaires_design.md#14-cartes-dynamiques-dans-les-dashboards)

- [ ] Migration `1XX_dashboard_shortcuts.php` : table `dashboard_shortcuts` (id, dashboard, section, title_key, title, description_key, description, url, icon, color, role_required, sort_order, active, club_id, audit fields).
- [ ] Mettre à jour `application/config/migration.php`.
- [ ] Créer `application/models/shortcuts_model.php` : `get_for_dashboard($dashboard, $role, $club_id)` avec filtrage rôle et club, CRUD complet, résolution multi-langue.
- [ ] Créer contrôleur `shortcuts_admin` + vues CRUD (liste, créer, modifier, supprimer, activer/désactiver). Accès réservé club-admin.
- [ ] Créer partial view `application/views/common/_shortcuts.php` : rendu Bootstrap des cartes groupées par section, résolution titre/description multi-langue, gestion URL interne vs externe.
- [ ] Ajouter une carte "Raccourcis dashboard" sur `forms_admin/index` pointant vers `shortcuts_admin`.
- [ ] Instrumenter les dashboards `accueil`, `pilote`, `instructeur`, `formations` : appel `shortcuts_model::get_for_dashboard()` dans les contrôleurs + inclusion du partial dans les vues.
- [ ] Ajouter les traductions (`shortcuts_title`, `shortcuts_description`, etc.) dans les fichiers de langue français, anglais, néerlandais.
- [ ] **Mettre à jour les tests Playwright** : adapter les tests qui parcourent toutes les URLs visibles pour exclure ou traiter correctement les raccourcis dynamiques (URLs de contexte, paramètres d'authentification requis).
- [ ] Tests PHPUnit : migration up/down, `get_for_dashboard` (filtrage rôle, filtrage club, actif/inactif), résolution multi-langue (clé trouvée / clé absente).
- [ ] **Validation** : créer un raccourci pointant vers `forms_admin/generate/attestation-de-formation-ulm`, vérifier qu'il apparaît dans le dashboard cible pour un club-admin et qu'il est invisible pour un rôle sans accès.

### Lot 8 — Documentation et validation finale

- [ ] Documenter le socle formulaire autonome et les fichiers uploadés.
- [ ] Documenter la taxonomie des formulaires (catégories 1, 2, 3) et les exemples.
- [ ] Ajouter exemples complets de formulaires et de CSS global.
- [ ] Documenter import PDF -> HTML et ses limites.
- [ ] Documenter l'API de pré-remplissage GVV (mécanismes A et B) et les exemples workflow.
- [ ] Documenter le widget signature : modes canvas et upload, pré-remplissage depuis `membres.signature_path`, attributs `data-gvv-*`.
- [ ] Documenter la création d'un handler post-soumission (interface, exemple BriefingPassagerUlm).
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
12. Lot 8 (documentation et validation)

## Critères de fin

### Catégorie 1 (autonome)
- Un admin peut créer, modifier, supprimer et publier un formulaire multi-pages.
- Un utilisateur non authentifié peut remplir via lien public.
- Les admins consultent les réponses et visualisent images/PDF soumis.
- Les fichiers sont supportés dès la première phase de livraison.
- Un PDF imprimable est générable depuis une réponse.
- Une réponse est archivable dans `archived_documents` pour un pilote.
- Sur un formulaire où l'option est activée, un utilisateur peut télécharger un scan/photo du formulaire imprimé à la place du remplissage en ligne ; l'admin la retrouve dans la liste des réponses avec miniature, rotation et suppression fonctionnelles.

### Catégorie 2 (contextuel GVV)
- Pré-remplissage mécanisme A (`data-gvv-source`) opérationnel et sécurisé.
- Pré-remplissage mécanisme B (paramètres URL directs + `lock[]`) opérationnel.
- Un champ signature peut être soumis en mode canvas ou upload image.
- La signature d'un profil GVV peut pré-remplir le widget.

### Catégorie 3 (intégré workflow)
- `subject_type`/`subject_id` opérationnel sur `form_submissions` : détection « réponse existante » et bascule à la suppression fonctionnelles sans dépendre d'`archived_documents`.
- `BriefingPassagerUlmHandler` opérationnel : VLD mis à jour depuis la réponse soumise.
- Icône « briefing fait » de `vols_decouverte` basée sur `form_submissions`, ancien mécanisme documentaire retiré du chemin de détection (mais pas supprimé du code tant que non décidé séparément).
- Non-régression catégorie 1 et 2 vérifiée à chaque étape du Lot 6.
- PHPUnit et Playwright verts sur les trois catégories.

### Qualité transversale
- Chaque lot commence par une migration explicite et testée.
- Les documents archivés référencés sont visibles inline avec scroll.
- L'import PDF -> HTML fonctionne.
