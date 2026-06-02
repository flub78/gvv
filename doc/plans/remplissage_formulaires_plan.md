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
- L'import PDF -> HTML est "best effort" en V1.
- La première mise en production vise un socle autonome de formulaires HTML, sans pré-remplissage GVV.
- Évolution probable: ajouter une surcouche minimale d'orchestration (validation des documents + acceptation/rejet global) au-dessus des formulaires, plutôt qu'un moteur de procédures complet en première intention.

## Tâches à réaliser

## État d'avancement

- Lot 1 entamé.
- Migration coeur formulaires créée et appliquée sur la base de test.
- Migration `117_forms_club_nullable` ajoutée pour autoriser les formulaires globaux (`club` nullable).
- Migration `118_forms_global_css` ajoutée pour supporter le CSS global formulaire.
- Schéma SQL vérifié en base pour les 5 tables du socle.
- Modèles `forms_model.php` et `form_pages_model.php` ajoutés.
- Modèles `form_fields_model.php` et `form_submissions_model.php` ajoutés.
- Contrôleur admin minimal créé pour lister, créer et publier un formulaire.
- CRUD admin étendu avec édition, suppression et duplication.
- Modèle `forms_model.php` adapté pour gérer section active + formulaires globaux dans le listing.
- Contrôleur public `forms_public` ajouté avec routes publiques (`forms/{slug}`, `forms/submit/{slug}`) pour affichage multi-pages et soumission anonyme (premier slice).
- Validation serveur centralisée introduite via la librairie `Forms_validation` et branchée dans `forms_public`.
- Préparation de rendu HTML centralisée via la librairie `Forms_renderer` (normalisation des champs/options/valeurs pour la vue publique).
- Gestion admin des pages ajoutée (liste, création, édition, suppression) avec import texte/HTML et export HTML/TXT.
- CSS global de formulaire ajouté avec preview admin dédiée et application au rendu public.
- Lot 2 démarré : migration `119_forms_files` ajoutée, stockage métadonnées fichiers dans `form_submission_files`, upload public sécurisé branché.
- Vue admin des réponses ajoutée (liste + détail), preview inline image/PDF et téléchargement sécurisé des fichiers de soumission.

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
- [ ] Vérifier et documenter le scénario install from scratch / upgrade complet (upgrade validé: table `migrations` présente, version atteinte = `119`, tables formulaires présentes; replay from scratch sur base vide restant à faire).
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
- [ ] Implémenter rendu PDF imprimable d'une réponse.
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

### Lot 5 — Intégration GVV avancée

- [ ] Migration de début de lot : créer `09X_forms_prefill.php` avec les tables dédiées :
  - `form_prefill_bindings` (liaisons champs <-> API GVV)
  - extensions éventuelles pour paramètres runtime workflow
- [ ] Ajouter test migration up/down.
- [ ] Définir la liste blanche des attributs GVV exposables.
- [ ] Créer `form_prefill_service` et l'encodage HTML des champs dynamiques.
- [ ] Gérer champs verrouillés/modifiables après pré-remplissage.
- [ ] Permettre l'utilisation des liens formulaire dans les workflows GVV.
- [ ] Définir les paramètres runtime passables au formulaire.
- [ ] Ajouter la sauvegarde/reprise de saisie multi-session pour les utilisateurs externes (mode brouillon).
- [ ] Définir un mécanisme de reprise sécurisé (token + contrôle email/PIN) et la politique d'expiration.
- [ ] Reprendre la navigation sur la dernière étape valide en tenant compte des sections conditionnelles.
- [ ] Ajouter des règles de visibilité des pages/sections selon les réponses (conditions simples en liste blanche d'opérateurs).
- [ ] Recalculer la séquence des pages visibles à chaque étape (suivant/précédent/reprise) côté serveur.
- [ ] Adapter la validation finale aux seules pages/sections effectivement visibles.

### Lot 6 — Documentation et validation finale

- [ ] Migration de début de lot : vérifier et consolider les migrations précédentes dans un scénario complet (install from scratch + upgrade).
- [ ] Documenter le socle formulaire autonome et les fichiers uploadés.
- [ ] Ajouter exemples complets de formulaires et de CSS global.
- [ ] Documenter import PDF -> HTML et ses limites.
- [ ] Documenter l'API de pré-remplissage GVV et les exemples workflow.
- [ ] PHPUnit : modèles, validations, fichiers, archivage, puis pré-remplissage.
- [ ] Playwright : création admin, soumission anonyme, upload/preview, PDF imprimable, archivage, puis pré-remplissage GVV.
- [ ] Vérification sécurité : uploads, contrôle d'accès, anti-spam.

## Stratégie de livraison

### Phase 1 — Socle formulaires autonome

Objectif : livrer rapidement une gestion de formulaire à la Google Forms, sans pré-remplissage GVV, mais avec support des fichiers.

Lots inclus : 1, 2, 3.

### Phase 2 — Extensions documentaires

Objectif : ajouter les compléments non bloquants pour le socle, notamment l'import PDF -> HTML.

Lots inclus : 4.

### Phase 3 — Intégration GVV avancée

Objectif : ajouter le pré-remplissage GVV, la sauvegarde/reprise de saisie multi-session, les pages conditionnelles et l'intégration fine dans les workflows.

Lots inclus : 5.

### Phase 4 — Documentation et validation globale

Objectif : stabiliser, documenter et valider l'ensemble des phases précédentes.

Lots inclus : 6.

## Ordre de réalisation recommandé

1. Lot 1 (migration)
2. Lot 2 (réponses et fichiers)
3. Lot 3 (impression et archivage)
4. Lot 4 (extensions documentaires)
5. Lot 5 (pré-remplissage GVV + workflows)
6. Lot 6 (documentation et validation)

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
- Une réponse est archivable dans `archived_documents` pour un pilote.
