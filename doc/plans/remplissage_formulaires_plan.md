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

### Lot 2-bis — Synchronisation fichiers disque

- [ ] Migration : ajouter `content_hash VARCHAR(32) NULL` sur `form_pages` et `css_hash VARCHAR(32) NULL` sur `forms`.
- [ ] Créer le répertoire `application/forms_templates/` avec `.htaccess` de protection (si nécessaire selon config serveur).
- [ ] Lors de la sauvegarde web d'une page (`page_create` / `page_update`) : calculer MD5, stocker `content_hash`, écrire le fichier disque.
- [ ] Lors de la sauvegarde web du CSS global d'un formulaire : calculer MD5, stocker `css_hash`, écrire le fichier disque.
- [ ] Ajouter le bouton "Actualiser depuis le disque" sur la vue page admin : lire le fichier, comparer le hash, mettre à jour la base si différent, afficher le résultat.
- [ ] Ajouter le bouton "Exporter vers le disque" sur la vue page admin : écrire le fichier depuis le contenu en base (même si le fichier existe).
- [ ] Même logique pour le CSS global : boutons "Actualiser" et "Exporter" sur la vue formulaire.
- [ ] Afficher le chemin du fichier attendu dans l'admin pour guider le développeur.
- [ ] Test PHPUnit : hash calculé à la sauvegarde, fichier écrit, sync file→DB met à jour le contenu et le hash.

### Lot 3 — Impression et archivage

- [ ] Migration de début de lot : créer `09X_forms_archive.php` pour les besoins de rattachement et ajouter le type de document `formulaire_rempli` si nécessaire.
- [ ] Ajouter test migration up/down.
- [x] Implémenter rendu PDF imprimable d'une réponse.
- [ ] Ajouter endpoint admin de génération/téléchargement PDF.
- [ ] Archiver une réponse et son PDF imprimable dans `archived_documents`.
- [ ] Associer l'archive à un pilote et journaliser l'opération.

### Lot 4 — Extensions documentaires

- [ ] Migration de début de lot : créer `09X_forms_documents.php` avec les tables complémentaires :
  - `form_document_refs` (références documents archivés)
  - structures de suivi d'import PDF -> HTML si nécessaires
- [ ] Ajouter test migration up/down.
- [ ] Permettre la sélection d'un document archivé existant dans un formulaire.
- [ ] Rendre les documents référencés inline dans une boîte scrollable.
- [ ] Implémenter le pipeline d'import PDF -> HTML.
- [ ] Générer un rapport de conversion et prévoir la réédition manuelle post-import.

### Lot 5 — Pré-remplissage GVV

Syntaxe : attributs `data-gvv-source`, `data-gvv-param`, `data-gvv-lock` sur les éléments HTML.
Paramètres transmis en query string de l'URL du formulaire.
Voir : [Design pré-remplissage](../design_notes/remplissage_formulaires_design.md#5-pré-remplissage-gvv)

- [ ] Créer `form_prefill_service` : résolution des sources par liste blanche (club.*, member.*, instructor.*, user.*, date.*).
- [ ] Parser les attributs `data-gvv-*` depuis le HTML de chaque page (DOMDocument, même pipeline que `sync_fields_from_html`).
- [ ] Valider les paramètres URL (`pilot_login`, `instructor_login`) : existence + appartenance à la section active.
- [ ] Injecter les valeurs résolues dans le rendu public avant affichage.
- [ ] Appliquer le lock côté serveur : ignorer la valeur soumise pour les champs `data-gvv-lock="true"` et réinjecter la valeur GVV.
- [ ] Permettre l'utilisation des liens formulaire dans les workflows GVV (paramètres encodés dans le lien).
- [ ] Ajouter la sauvegarde/reprise de saisie multi-session pour les utilisateurs externes (mode brouillon, token de reprise).
- [ ] Ajouter des règles de visibilité des pages/sections selon les réponses (conditions simples, liste blanche d'opérateurs).
- [ ] Recalculer la séquence des pages visibles à chaque étape côté serveur.
- [ ] Adapter la validation finale aux seules pages/sections effectivement visibles.

### Lot 5-bis — Signatures

Syntaxe : `<div data-gvv-type="signature" data-gvv-name="..." data-gvv-param="..." data-gvv-lock="...">`.
`sync_fields_from_html` enregistre automatiquement un champ de type `signature` dans `form_fields`.

| Priorité | Fonctionnalité | Complexité |
|---|---|---|
| 1 | Dessin canvas | Faible — `signature_pad.umd.min.js` déjà présent |
| 2 | Upload image | Faible — pipeline file existant |
| 3 | Pré-remplissage profil GVV | Moyenne — nouveau champ `membres.signature_path` |
| 4 | Signature PGP | Élevée — hors V1 |

- [ ] Créer `render_signature_widget(array $field, array $prefill_data): string` : génère le HTML du widget (onglets canvas + upload ; onglet PGP désactivé en V1).
- [ ] Mode canvas : `signature_pad.umd.min.js` → `toDataURL('image/png')` → strip préfixe → hidden input base64 → `process_signature_input` → `base64_decode` → PNG dans `uploads/forms/signatures/` → entrée `form_submission_files`.
- [ ] Mode upload : `<input type="file" accept="image/*">` dans le widget → pipeline standard `form_submission_files`.
- [ ] Migration : ajouter `signature_path VARCHAR(255) NULL` à la table `membres`.
- [ ] Ajouter les sources `member.signature` et `instructor.signature` à la taxonomie `form_prefill_service`.
- [ ] Pré-remplissage widget : afficher l'image depuis `membres.signature_path` si disponible ; remplaçable si `data-gvv-lock="false"`.
- [ ] Créer `process_signature_input(string $name, string $content, string $type): array` : valider et dispatcher selon le type (`canvas|file|pgp`).

### Lot 6 — Documentation et validation finale

- [ ] Migration de début de lot : vérifier et consolider les migrations précédentes dans un scénario complet (install from scratch + upgrade).
- [ ] Documenter le socle formulaire autonome et les fichiers uploadés.
- [ ] Ajouter exemples complets de formulaires et de CSS global.
- [ ] Documenter import PDF -> HTML et ses limites.
- [ ] Documenter l'API de pré-remplissage GVV et les exemples workflow.
- [ ] Documenter le widget signature : modes canvas et upload, pré-remplissage depuis `membres.signature_path`, attributs `data-gvv-*`.
- [ ] PHPUnit : modèles, validations, fichiers, archivage, puis pré-remplissage.
- [ ] Playwright : création admin, soumission anonyme, upload/preview, PDF imprimable, archivage, puis pré-remplissage GVV, signatures canvas et upload.
- [ ] Vérification sécurité : uploads, contrôle d'accès, anti-spam.

## Stratégie de livraison

### Phase 1 — Socle formulaires autonome

Objectif : livrer rapidement une gestion de formulaire à la Google Forms, sans pré-remplissage GVV, mais avec support des fichiers.

Lots inclus : 1, 2, 3.

### Phase 2 — Extensions documentaires

Objectif : ajouter les compléments non bloquants pour le socle, notamment l'import PDF -> HTML.

Lots inclus : 4.

### Phase 3 — Intégration GVV avancée

Objectif : ajouter le pré-remplissage GVV, les signatures (canvas + upload + pré-remplissage profil GVV), la sauvegarde/reprise de saisie multi-session, les pages conditionnelles et l'intégration fine dans les workflows.

Lots inclus : 5, 5-bis.

### Phase 4 — Documentation et validation globale

Objectif : stabiliser, documenter et valider l'ensemble des phases précédentes.

Lots inclus : 6.

## Ordre de réalisation recommandé

1. Lot 1 (migration)
2. Lot 2 (réponses et fichiers)
3. Lot 3 (impression et archivage)
4. Lot 4 (extensions documentaires)
5. Lot 5 (pré-remplissage GVV + workflows)
6. Lot 5-bis (signatures canvas + upload + pré-remplissage profil)
7. Lot 6 (documentation et validation)

## Critères de fin

- Un admin peut créer, modifier, supprimer et publier un formulaire multi-pages.
- Un utilisateur non authentifié peut remplir via lien public.
- Les admins consultent les réponses et visualisent images/PDF soumis.
- Les fichiers sont supportés dès la première phase de livraison.
- Chaque lot commence par une migration explicite et testée.
- Les documents archivés référencés sont visibles inline avec scroll.
- L'import PDF -> HTML fonctionne avec rapport de conversion.
- Un PDF imprimable est générable depuis une réponse.
- Pré-remplissage GVV par paramètres est opérationnel et sécurisé.
- Un champ signature peut être soumis en mode canvas ou upload image et est stocké dans `form_submission_files`.
- La signature d'un profil GVV (`membres.signature_path`) peut pré-remplir le widget.
- Une réponse est archivable dans `archived_documents` pour un pilote.
