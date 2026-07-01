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

#### Étape 6.2 — Stockage du contexte GVV sur les soumissions

- [ ] Migration : ajouter `context_params TEXT NULL` à `form_submissions`.
- [ ] Dans `forms_public/submit` : extraire les paramètres contexte de la session, les sérialiser en JSON et les stocker dans `context_params` à la création de la soumission.
- [ ] **Validation non-régression** : PHPUnit migration up/down + smoke tests catégorie 1 et 2.

#### Étape 6.3 — Infrastructure handler post-soumission

- [ ] Migration : ajouter `handler_class VARCHAR(100) NULL` à `forms`.
- [ ] Créer `application/libraries/form_handlers/GvvFormHandlerInterface.php`.
- [ ] Dans `forms_public/submit` : après création de la soumission, instancier le handler si `handler_class` est défini, appeler `after_submit($submission_id, $context_params)`, rediriger selon le retour.
- [ ] Journaliser les erreurs handler sans crasher (la soumission reste accessible en admin).
- [ ] **Validation non-régression** : smoke tests catégorie 1 et 2 — le `handler_class` NULL ne change rien au comportement existant.

#### Étape 6.4 — BriefingPassagerUlmHandler

- [ ] Créer `application/libraries/form_handlers/BriefingPassagerUlmHandler.php`.
- [ ] Implémenter `after_submit` : validation token, génération PDF (réutilise logique `submission_pdf`), archivage dans `archived_documents` (lié à `vld_id`), mise à jour `vols_decouverte`, invalidation `briefing_tokens`.
- [ ] Configurer `handler_class = 'BriefingPassagerUlmHandler'` sur le formulaire `briefing_passager_ulm` en base.
- [ ] **Tests PHPUnit** : BriefingPassagerUlmHandlerTest — token valide → PDF archivé + VLD mis à jour + token invalidé ; token expiré → erreur journalisée.

#### Étape 6.5 — Migration briefing_passager/generate_link

- [ ] Modifier `briefing_passager/generate_link` : construire l'URL `/forms/fiche-passager?token=...&vld_id=...&date_vol=...&...&lock[]=...` au lieu de `briefing_sign/{token}`.
- [ ] Vérifier que le QR code et le lien email utilisent la nouvelle URL.
- [ ] **Playwright** : smoke test briefing complet — génération lien → formulaire pré-rempli → soumission → PDF archivé → confirmation.

#### Étape 6.6 — Validation finale et retrait briefing_sign

- [ ] Vérifier que `briefing_sign` peut être retiré sans casser d'autres dépendances (routes, vues, tests).
- [ ] Archiver ou supprimer `briefing_sign.php` et ses vues si aucun autre consommateur.
- [ ] Mettre à jour `routes.php` si nécessaire.
- [ ] **Playwright non-régression globale** : `inscription_club`, `attestation_de_formation_ulm`, `briefing_passager_ulm`.
- [ ] **PHPUnit non-régression** : suite complète verte.

### Lot 7 — Cartes dynamiques dans les dashboards

Objectif : permettre aux club-admins d'ajouter des raccourcis de navigation dans les dashboards GVV sans développement. Indépendant des lots de formulaires — peut être réalisé dès que le socle (Lot 1) est terminé.

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

- [ ] Migration de début de lot : vérifier et consolider les migrations précédentes dans un scénario complet (install from scratch + upgrade).
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
11. Lot 8 (documentation et validation)

## Critères de fin

### Catégorie 1 (autonome)
- Un admin peut créer, modifier, supprimer et publier un formulaire multi-pages.
- Un utilisateur non authentifié peut remplir via lien public.
- Les admins consultent les réponses et visualisent images/PDF soumis.
- Les fichiers sont supportés dès la première phase de livraison.
- Un PDF imprimable est générable depuis une réponse.
- Une réponse est archivable dans `archived_documents` pour un pilote.

### Catégorie 2 (contextuel GVV)
- Pré-remplissage mécanisme A (`data-gvv-source`) opérationnel et sécurisé.
- Pré-remplissage mécanisme B (paramètres URL directs + `lock[]`) opérationnel.
- Un champ signature peut être soumis en mode canvas ou upload image.
- La signature d'un profil GVV peut pré-remplir le widget.

### Catégorie 3 (intégré workflow)
- `BriefingPassagerUlmHandler` opérationnel : PDF archivé, VLD mis à jour, token invalidé.
- `briefing_passager/generate_link` construit l'URL mécanisme B vers `forms/fiche-passager`.
- Non-régression catégorie 1 et 2 vérifiée à chaque étape du Lot 6.
- PHPUnit et Playwright verts sur les trois catégories.

### Qualité transversale
- Chaque lot commence par une migration explicite et testée.
- Les documents archivés référencés sont visibles inline avec scroll.
- L'import PDF -> HTML fonctionne.
