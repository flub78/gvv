# Design Notes — Remplissage Formulaires

Date : 30 mai 2026

## Contexte

Le module passe d'une logique centree "template PDF" a une logique "formulaires HTML natifs" avec lien public anonyme, pre-remplissage GVV et archivage documentaire.

La strategie d'implementation privilegie d'abord un socle autonome de formulaires HTML avec gestion des fichiers, puis ajoute dans un second temps le pre-remplissage GVV et l'integration workflow avancee.

## Architecture cible

Pipeline principal :

1. Definition formulaire (admin)
2. Publication lien public
3. Soumission anonyme utilisateur
4. Consultation admin des reponses
5. Export PDF imprimable (optionnel)
6. Archivage vers `archived_documents` (optionnel)

## Note d'evolution probable

Le module formulaires est la base fonctionnelle retenue. Pour les cas d'usage proches des procedures, l'orientation privilegiee est d'ajouter une orchestration legere (etat de dossier, validation documentaire, decision finale) au-dessus des soumissions de formulaires, sans separer prematurement deux moteurs techniques.

## Phasage recommande

### Phase 1 — Socle autonome

- gestion admin des formulaires
- rendu public multi-pages
- soumission anonyme
- support des fichiers
- consultation admin des reponses
- export PDF imprimable
- archivage d'une reponse vers pilote

### Phase 2 — Extensions documentaires

- references documentaires inline
- import PDF -> HTML

### Phase 3 — Extensions GVV

- pre-remplissage GVV
- parametres runtime depuis workflows
- automatisations liees aux workflows

## Composants

### 1. Gestion des formulaires

- Entites : `forms`, `form_pages`, `form_fields`
- `forms` : table racine d'un formulaire, avec ses metadonnees globales, son statut, son identifiant public, et un rattachement optionnel a une section.
- `form_pages` : pages ordonnees rattachees a un formulaire, chacune portant un contenu HTML ou texte et un numero de page.
- `form_fields` : champs elementaires d'une page, relies a un formulaire et a une page, avec leur type, regles et attributs de rendu.
- Capacites : CRUD, activation/desactivation, duplication
- Edition de pages : inline + import/export texte/HTML

Regles de filtrage section (listing admin) :

- sans section active : afficher tous les formulaires, avec la section de rattachement visible dans la liste ;
- avec section active : afficher les formulaires de la section active + les formulaires globaux (sans section) ;
- ne pas afficher les formulaires des autres sections quand une section active est selectionnee.

### 2. Rendu et validation publique

- Controleur public dedie
- Rendu multi-pages HTML
- Validation serveur de tous les types
- Soumission sans authentification GVV

### 3. Reponses et fichiers

- Entites : `form_submissions`, `form_submission_values`, `form_submission_files`
- `form_submissions` : en-tete d'une reponse recue, rattachee a un formulaire publie et portant les informations de contexte de soumission.
- `form_submission_values` : valeurs normalisees champ par champ pour une soumission donnee, avec liaison vers le champ source.
- `form_submission_files` : fichiers attaches a une soumission, references par champ ou par usage metier, avec leurs metadonnees de stockage.
- Support upload fichiers avec controles
- Preview admin image/PDF inline

### 4. References documentaires

- Entite : `form_document_refs`
- `form_document_refs` : table de liaison entre un formulaire ou une page et un document archive, utilisee pour reference et afficher le document dans le contexte du formulaire.
- Insertion d'un document archive dans une page formulaire
- Rendu dans une boite scrollable (iframe/viewer)

### 5. Pre-remplissage GVV

- Service : `form_prefill_service`
- Parametres autorises : `pilot_login`, `instructeur_login`, `section_id`, etc.
- Liste blanche des champs exposables
- Encodage des champs dynamiques via attributs `data-gvv-*`

Exemple de champ dynamique :

```html
<input
  name="pilot_email"
  type="email"
  data-gvv-source="member.email"
  data-gvv-param="pilot_login"
  data-gvv-lock="true"
/>
```

### 6. Import PDF -> HTML

- Service de conversion best effort
- Detection des champs du PDF source quand possible
- Generation d'une page HTML initiale + rapport des champs non convertis

### 7. Export PDF imprimable

- Rendu imprime d'une soumission
- Generation d'un PDF lisible et telechargeable
- Utilisable pour archivage documentaire

### 8. Archivage documentaire

- Entite : `archived_documents`
- `archived_documents` : table d'archive finale des documents, avec metadonnees de fichier, liens vers pilote/section/type de document et suivi des versions et de la validation.
- Stockage persistant du fichier produit par export ou transfert depuis une soumission
- Reference reutilisable dans les ecrans documentaires existants

## API interne proposee

### Service formulaire

- `create_form(array $meta): int`
- `publish_form(int $form_id): string`
- `save_page(int $form_id, int $page_no, string $html): void`
- `import_page(int $form_id, int $page_no, string $content): void`
- `export_page(int $form_id, int $page_no): string`

### Service soumission

- `submit(string $public_slug, array $payload, array $files): int`
- `get_submission(int $submission_id): array`
- `list_submissions(int $form_id, array $filters): array`

### Service prefill

- `resolve_prefill(array $params, array $field_bindings): array`
- `validate_allowed_sources(array $bindings): array`

### Service impression/archivage

- `render_submission_pdf(int $submission_id): string`
- `archive_submission(int $submission_id, string $pilot_login): int`

## Securite

- Validation serveur stricte de tous les champs
- Controle MIME/taille sur upload
- Sanitization HTML des contenus admin importes
- Protection CSRF
- Rate limiting sur soumissions publiques
- Logs d'audit admin et soumissions

## Integration workflows GVV

- Un workflow peut pointer vers un `public_slug`
- Les parametres runtime du workflow alimentent les params prefill
- Une etape workflow peut declencher l'archivage d'une reponse

## Documentation a produire

- Exemples de formulaires (inscription, briefing, demande interne)
- Exemple de CSS global
- Guide pre-remplissage GVV
- Guide import PDF -> HTML et limites
- Guide export PDF imprimable
