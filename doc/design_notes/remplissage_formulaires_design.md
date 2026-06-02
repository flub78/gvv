# Design Notes — Remplissage Formulaires

Date : 30 mai 2026

## Contexte

Le module passe d'une logique centrée « template PDF » à une logique « formulaires HTML natifs » avec lien public anonyme, pré-remplissage GVV et archivage documentaire.

La stratégie d'implémentation privilégie d'abord un socle autonome de formulaires HTML avec gestion des fichiers, puis ajoute dans un second temps le pré-remplissage GVV et l'intégration workflow avancée.

## Architecture cible

Pipeline principal :

1. Définition formulaire (admin)
2. Publication lien public
3. Soumission anonyme utilisateur
4. Consultation admin des réponses
5. Export PDF imprimable (optionnel)
6. Archivage vers `archived_documents` (optionnel)

## Note d'évolution probable

Le module formulaires est la base fonctionnelle retenue. Pour les cas d'usage proches des procédures, l'orientation privilégiée est d'ajouter une orchestration légère (état de dossier, validation documentaire, décision finale) au-dessus des soumissions de formulaires, sans séparer prématurément deux moteurs techniques.

## Phasage recommandé

### Phase 1 — Socle autonome

- gestion admin des formulaires
- rendu public multi-pages
- soumission anonyme
- support des fichiers
- consultation admin des réponses
- export PDF imprimable
- archivage d'une réponse vers pilote

### Phase 2 — Extensions documentaires

- references documentaires inline
- import PDF -> HTML

### Phase 3 — Extensions GVV

- pré-remplissage GVV
- paramètres runtime depuis workflows
- automatisations liées aux workflows
- sauvegarde/reprise de saisie multi-session (brouillon, reprise sécurisée, retour sur la dernière étape valide)
- pages/sections conditionnelles selon les réponses (règles de visibilité + navigation conditionnelle)
- signatures (canvas + upload image, puis pré-remplissage profil, puis PGP optionnel)

## Composants

### 1. Gestion des formulaires

- Entités : `forms`, `form_pages`, `form_fields`
- `forms` : table racine d'un formulaire, avec ses métadonnées globales, son statut, son identifiant public, et un rattachement optionnel à une section.
- `form_pages` : pages ordonnées rattachées à un formulaire, chacune portant un contenu HTML ou texte et un numéro de page.
- `form_fields` : champs élémentaires d'une page, reliés à un formulaire et à une page, avec leur type, règles et attributs de rendu.
- Capacités : CRUD, activation/désactivation, duplication
- Édition de pages : inline + import/export texte/HTML

Règles de filtrage section (listing admin) :

- sans section active : afficher tous les formulaires, avec la section de rattachement visible dans la liste ;
- avec section active : afficher les formulaires de la section active + les formulaires globaux (sans section) ;
- ne pas afficher les formulaires des autres sections quand une section active est sélectionnée.

### 2. Rendu et validation publique

- Contrôleur public dédié
- Rendu multi-pages HTML
- Validation serveur de tous les types
- Soumission sans authentification GVV

### 3. Réponses et fichiers

- Entités : `form_submissions`, `form_submission_values`, `form_submission_files`
- `form_submissions` : en-tête d'une réponse reçue, rattachée à un formulaire publié et portant les informations de contexte de soumission.
- `form_submission_values` : valeurs normalisées champ par champ pour une soumission donnée, avec liaison vers le champ source.
- `form_submission_files` : fichiers attachés à une soumission, référencés par champ ou par usage métier, avec leurs métadonnées de stockage.
- Support upload fichiers avec contrôles
- Prévisualisation admin image/PDF inline

### 4. Références documentaires

- Entité : `form_document_refs`
- `form_document_refs` : table de liaison entre un formulaire ou une page et un document archivé, utilisée pour référence et afficher le document dans le contexte du formulaire.
- Insertion d'un document archivé dans une page formulaire
- Rendu dans une boîte déroulante (iframe/viewer)

### 5. Pré-remplissage GVV

Service : `form_prefill_service`

Les champs pré-remplis sont déclarés dans le HTML via des attributs `data-gvv-*` sur les éléments `<input>`, `<textarea>` et `<select>`. Ces attributs sont ignorés par le navigateur et parsés côté serveur par DOMDocument (même pipeline que `sync_fields_from_html`).

#### Attributs

| Attribut | Rôle | Valeur |
|---|---|---|
| `data-gvv-source` | Source de la donnée GVV | voir taxonomie ci-dessous |
| `data-gvv-param` | Paramètre URL qui identifie l'entité | `pilot_login`, `instructor_login` |
| `data-gvv-lock` | Verrouillage côté serveur | `true` / `false` (défaut : `false`) |

#### Exemple

```html
<!-- Champ verrouillé : valeur imposée par GVV -->
<input name="candidat_nom" type="text"
       data-gvv-source="member.nom_prenom"
       data-gvv-param="pilot_login"
       data-gvv-lock="true">

<!-- Champ éditable : pré-rempli mais modifiable -->
<input name="candidat_adresse" type="text"
       data-gvv-source="member.adresse_complete"
       data-gvv-param="pilot_login">

<!-- Source globale (pas de paramètre) -->
<input name="organisme" type="text"
       data-gvv-source="club.nom">

<input name="date_signature" type="date"
       data-gvv-source="date.today">
```

Les paramètres sont transmis via l'URL du formulaire :

```
/forms/attestation-formation-procedures?pilot_login=duvollet_f&instructor_login=peignot_f
```

#### Taxonomie des sources

```
club.nom                   → $config['nom_club']
club.sigle                 → $config['sigle_club']
club.adresse               → $config['adresse_club']
club.ville                 → $config['ville_club']
club.email                 → $config['email_club']

member.nom                 → mnom                      param: pilot_login
member.prenom              → mprenom
member.nom_prenom          → "mnom mprenom"
member.email               → memail
member.telephone           → mtelf (ou mtelm si vide)
member.adresse             → madresse
member.code_postal         → cp
member.ville               → ville
member.adresse_complete    → "madresse, cp ville"
member.date_naissance      → mdaten (YYYY-MM-DD)
member.lieu_naissance      → place_of_birth
member.date_lieu_naissance → "JJ/MM/AAAA à lieu"

instructor.*               → mêmes champs              param: instructor_login

user.*                     → membre de la session courante (lien authentifié, sans param)

date.today                 → date('Y-m-d')
date.today_fr              → date('d/m/Y')
date.year                  → date('Y')
```

#### Règles de sécurité

- **Liste blanche stricte** : seules les sources déclarées ci-dessus sont autorisées.
- **Validation du paramètre** : le login fourni en URL doit exister et appartenir à la section active.
- **Lock côté serveur** : pour `data-gvv-lock="true"`, GVV ignore la valeur soumise et réinjecte la valeur résolue — le verrou HTML seul ne suffit pas.
- **Pas d'accès direct à la base** : le service passe exclusivement par la liste blanche.

### 6. Signatures

#### Déclaration dans le HTML

Un champ signature se déclare via un `<div>` avec l'attribut `data-gvv-type="signature"`, cohérent avec la syntaxe `data-gvv-*` existante. GVV remplace ce div au rendu public par le widget complet. `sync_fields_from_html` enregistre automatiquement un champ de type `signature` dans `form_fields`.

```html
<div class="sig-area"
     data-gvv-type="signature"
     data-gvv-name="signature_instructeur"
     data-gvv-param="instructor_login"
     data-gvv-lock="false">Signature</div>
```

Le div reste lisible en prévisualisation standalone (le texte s'affiche) ; le widget n'apparaît que dans GVV.

#### Widget composite (trois onglets)

```
┌─────────────────────────────────────────────────┐
│  [Dessiner]  [Importer une image]  [PGP]        │
├─────────────────────────────────────────────────┤
│                                                 │
│   canvas | prévisualisation image | ASCII PGP   │
│                                                 │
├─────────────────────────────────────────────────┤
│  [Effacer]                                      │
└─────────────────────────────────────────────────┘
<input type="hidden" name="signature_instructeur"      value="...base64 ou PGP...">
<input type="hidden" name="signature_instructeur_type" value="canvas|file|pgp">
```

Deux hidden inputs transmis à chaque soumission : le contenu et le type, pour audit côté serveur.

#### Mode 1 — Dessin canvas

Réutilise `assets/js/signature_pad.umd.min.js` déjà présent (même pattern que `briefing_passager/bs_signView.php`) :
- Canvas → `toDataURL('image/png')` → strip préfixe (CI2 filtre `data:...base64,...`) → hidden input base64
- Normalisation à 600×200px avant envoi
- Côté serveur : `base64_decode()` → PNG dans `uploads/forms/signatures/`
- Référence dans `form_submission_files` (`mime_type = image/png`)

#### Mode 2 — Upload image

`<input type="file" accept="image/*">` dans le widget, pipeline file standard déjà géré par `form_submission_files`. Prévisualisation inline dans le cadre du widget.

#### Mode 3 — Signature PGP (option avancée)

**Côté client** : [OpenPGP.js](https://openpgpjs.org/). Workflow :
1. Hash SHA-256 des valeurs soumises côté JS
2. L'utilisateur entre sa passphrase (clé privée jamais transmise)
3. Signature ASCII-armored produite → hidden input

**Côté serveur PHP 7.4** : extension `gnupg` ou `exec('gpg --verify')`. La clé publique du membre est stockée dans `membres.pgp_public_key` (nouveau champ).

**Stockage** : dans `form_submission_files` avec `mime_type = application/pgp-signature`, ou dans `form_submission_values.value_text` si le texte ASCII est court.

**Limites** : complexité d'usage élevée (gestion de clé PGP par l'utilisateur), ~500 KB de JS supplémentaire, valeur légale incertaine (hors eIDAS qualifié). Réservé aux cas avancés.

#### Pré-remplissage depuis GVV

Nouveau champ `membres.signature_path` → chemin vers l'image PNG sur disque (même pattern que `membres.photo`).

Sources à ajouter à la taxonomie :
```
member.signature     → membres.signature_path   param: pilot_login
instructor.signature → membres.signature_path   param: instructor_login
```

Si une signature GVV est disponible, elle est affichée directement dans le widget. Si `data-gvv-lock="false"`, l'utilisateur peut la remplacer.

#### Priorité de mise en œuvre

| Priorité | Fonctionnalité | Complexité | Prérequis |
|---|---|---|---|
| 1 | Dessin canvas | Faible | `signature_pad.umd.min.js` déjà présent |
| 2 | Upload image | Faible | Pipeline file existant |
| 3 | Pré-remplissage profil GVV | Moyenne | Nouveau champ `membres.signature_path` |
| 4 | Signature PGP | Élevée | OpenPGP.js + clé membre + vérif serveur |

### 7. Import PDF -> HTML

- Pas de service de conversion, demander à Claude ou ChatGPT de réaliser la conversion
- Détection des champs du PDF source quand possible
- Génération d'une page HTML initiale + rapport des champs non convertis

### 7. Export PDF imprimable

- Rendu imprimé d'une soumission
- Génération d'un PDF lisible et téléchargeable
- Utilisable pour archivage documentaire

### 8. Archivage documentaire

- Entite : `archived_documents`
- `archived_documents` : table d'archive finale des documents, avec métadonnées de fichier, liens vers pilote/section/type de document et suivi des versions et de la validation.
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

### Service préfill

- `resolve_prefill(array $params, array $field_bindings): array`
- `validate_allowed_sources(array $bindings): array`

### Service impression/archivage

- `render_submission_pdf(int $submission_id): string`
- `archive_submission(int $submission_id, string $pilot_login): int`

### Service signature

- `render_signature_widget(array $field, array $prefill_data): string` — génère le HTML du widget
- `process_signature_input(string $name, string $content, string $type): array` — valide et sauvegarde
- `verify_pgp_signature(string $login, string $content, string $signature): bool`

## Sécurité

- Validation serveur stricte de tous les champs
- Contrôle MIME/taille sur upload
- Désinfection HTML des contenus admin importés
- Protection CSRF
- Limitation de débit sur soumissions publiques
- Logs d'audit admin et soumissions

## Intégration workflows GVV

- Un workflow peut pointer vers un `public_slug`
- Les paramètres runtime du workflow alimentent les params pré-remplissage
- Une étape workflow peut déclencher l'archivage d'une réponse

## Documentation à produire

- Exemples de formulaires (inscription, briefing, demande interne)
- Exemple de CSS global
- Guide pré-remplissage GVV
- Guide import PDF → HTML et limites
- Guide export PDF imprimable
