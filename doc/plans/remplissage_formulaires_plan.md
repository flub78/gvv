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

## Tâches à réaliser

### Lot 1 — Socle formulaires autonome

- [ ] Migration de début de lot : créer `09X_forms_core.php` avec les tables minimales du socle :
  - `forms` (métadonnées, statut, slug/lien public, css_scope)
  - `form_pages` (pages HTML ordonnées)
  - `form_fields` (définition des champs et validations)
  - `form_submissions` (soumissions)
  - `form_submission_values` (valeurs par champ)
- [ ] Ajouter index, contraintes d'unicité, clés étrangères, mise à jour `application/config/migration.php`.
- [ ] Écrire test migration up/down.
- [ ] Créer modèles `forms_model.php`, `form_pages_model.php`, `form_submissions_model.php`.
- [ ] Implémenter CRUD admin : créer, modifier, supprimer, dupliquer, publier.
- [ ] Créer contrôleurs/routes admin et public pour affichage multi-pages et soumission anonyme.
- [ ] Implémenter moteur de rendu HTML et validation serveur centralisée.
- [ ] Ajouter import/export de page texte/HTML.
- [ ] Ajouter CSS global formulaire et preview admin.

### Lot 2 — Réponses et fichiers

- [ ] Migration de début de lot : créer `09X_forms_files.php` avec les tables complémentaires :
  - `form_submission_files` (fichiers uploadés)
  - colonnes/flags complémentaires de suivi de soumission si nécessaire
- [ ] Ajouter index, contraintes, mise à jour migration et test up/down.
- [ ] Étendre les modèles de soumission pour supporter les fichiers.
- [ ] Implémenter upload de fichiers sur soumission (type, taille, nommage sûr).
- [ ] Implémenter visualisation admin des réponses et preview image/PDF inline.
- [ ] Gérer téléchargement sécurisé et politique de rétention initiale.
- [ ] Ajouter messages de confirmation explicites côté utilisateur.

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

Objectif : ajouter le pré-remplissage GVV et l'intégration fine dans les workflows.

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
