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
5. Export PDF imprimable
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
- Insertion d'un document archivé dans une page formulaires
- Rendu dans une boîte déroulante (iframe/viewer)

### 5. Paramètres de configuration formulaires

Un écran admin dédié (`forms_admin/config`) permet de gérer des paramètres clé/valeur utilisables dans tous les formulaires. Ces paramètres constituent un référentiel stable de valeurs configurables qui ne sont ni des données membres ni des constantes de la config GVV globale.

#### Table `form_config_params`

| Colonne | Type | Contrainte |
|---|---|---|
| `id` | INT AUTO_INCREMENT | PRIMARY KEY |
| `club_id` | INT NULL | FK → clubs(id), NULL = portée globale |
| `param_key` | VARCHAR(100) NOT NULL | Unique par (club_id, param_key) |
| `param_value` | TEXT NOT NULL | Valeur brute |
| `param_label` | VARCHAR(255) NOT NULL | Libellé lisible en admin |
| `param_description` | TEXT NULL | Aide contextuelle optionnelle |
| `created_at` | TIMESTAMP | Audit |
| `updated_at` | TIMESTAMP | Audit |
| `created_by` | INT NULL | FK → membres(mlogin) |
| `updated_by` | INT NULL | FK → membres(mlogin) |

#### Portée et résolution

- **Portée globale** : `club_id = NULL` — disponible pour tous les formulaires quelle que soit la section active.
- **Portée section** : `club_id = id_section` — surcharge le paramètre global de même clé pour les formulaires de cette section.
- **Ordre de résolution** : section active → global. Si aucune valeur trouvée, le champ reste vide (pas d'erreur bloquante).

#### Accès admin

La page d'index de l'administration des formulaires (`forms_admin/index`) expose une carte "Configuration" pointant vers `forms_admin/config`. L'écran de config offre un CRUD simple (liste + formulaire create/edit/delete) sans pagination.

#### Source taxonomy

Nouveau namespace `config.*` dans le `form_prefill_service` :

```
config.organisme_formation  → form_config_params.param_value  (param_key = 'organisme_formation')
config.<cle>                → form_config_params.param_value  (param_key = '<cle>')
```

Pas de `data-gvv-param` pour les sources `config.*` : la résolution utilise uniquement la section active de la session courante, pas un paramètre URL.

#### Premier paramètre défini

| Clé | Libellé | Usage |
|---|---|---|
| `organisme_formation` | Organisme de formation | Nom/identification de l'organisme dans les attestations et certificats |

### 6. Formulaires à contexte GVV — Page de génération

Les formulaires qui exploitent des données GVV (table `membres`, table `events`) ne s'ouvrent jamais via un lien public brut. Ils sont toujours générés dans un contexte GVV authentifié depuis une **page de génération** dédiée.

#### Principe

La page de génération est une page admin GVV (contrôleur `forms_admin`, méthode `generate`) qui :
1. Présente les sélecteurs nécessaires selon les paramètres attendus par le formulaire (`pilot_login`, `instructor_login`, ou les deux).
2. À la validation, construit l'URL pré-remplie et redirige vers le formulaire public avec les paramètres encodés.

#### Exemple — Attestation de formation

```
┌──────────────────────────────────────────────────────┐
│  Générer une attestation de formation                │
├──────────────────────────────────────────────────────┤
│  Instructeur : [sélecteur instructeurs de section ▼] │
│  Candidat    : [sélecteur membres ▼]                 │
│                                                      │
│              [Remplir l'attestation]                 │
└──────────────────────────────────────────────────────┘
```

Le bouton construit l'URL :
```
/forms/attestation-formation?pilot_login=duvollet_f&instructor_login=peignot_f
```

Le formulaire s'ouvre avec tous les champs GVV pré-remplis et verrouillés.

#### Configuration des paramètres requis

Chaque formulaire déclare dans ses métadonnées (`forms.required_params`) les paramètres GVV nécessaires :
- `none` : formulaire public autonome, pas de page de génération.
- `pilot` : sélecteur membre requis → paramètre `pilot_login`.
- `instructor` : sélecteur instructeur requis → paramètre `instructor_login`.
- `pilot+instructor` : les deux sélecteurs requis.

La page de génération s'adapte automatiquement selon cette configuration.

### 7. Pré-remplissage GVV

Service : `form_prefill_service`

Les champs pré-remplis sont déclarés dans le HTML via des attributs `data-gvv-*` sur les éléments `<input>`, `<textarea>` et `<select>`. Ces attributs sont ignorés par le navigateur et parsés côté serveur par DOMDocument (même pipeline que `sync_fields_from_html`).

#### Attributs

| Attribut | Rôle | Valeur |
|---|---|---|
| `data-gvv-source` | Source de la donnée GVV | voir taxonomie ci-dessous |
| `data-gvv-param` | Paramètre URL qui identifie l'entité | `pilot_login`, `instructor_login` |
| `data-gvv-lock` | Verrouillage côté serveur | `true` / `false` (défaut : `false`) |

#### Syntaxe des sources — principe de distinction des tables

La syntaxe `data-gvv-source` indique explicitement la table d'origine :
- **`member.*`** et **`instructor.*`** → données de la table **`membres`** (identité, coordonnées, dates de naissance).
- **`member.event.{type_key}.*`** et **`instructor.event.{type_key}.*`** → données de la table **`events`** (qualifications, brevets, numéros de licence, dates de validité, signature de qualification).

Cette distinction est intentionnelle et visible dans le HTML du formulaire : un développeur qui lit le formulaire sait immédiatement d'où vient chaque donnée.

#### Exemple complet — Attestation de formation

```html
<!-- Données membres — table membres -->
<input name="candidat_nom" type="text"
       data-gvv-source="member.nom_prenom"
       data-gvv-param="pilot_login"
       data-gvv-lock="true">

<input name="candidat_adresse" type="text"
       data-gvv-source="member.adresse_complete"
       data-gvv-param="pilot_login"
       data-gvv-lock="true">

<input name="instructeur_nom" type="text"
       data-gvv-source="instructor.nom_prenom"
       data-gvv-param="instructor_login"
       data-gvv-lock="true">

<!-- Données events — table events (qualification instructeur) -->
<input name="instructeur_num_itp" type="text"
       data-gvv-source="instructor.event.itp.numero"
       data-gvv-param="instructor_login"
       data-gvv-lock="true">

<input name="instructeur_itp_expiry" type="date"
       data-gvv-source="instructor.event.itp.expiry"
       data-gvv-param="instructor_login"
       data-gvv-lock="true">

<!-- Signature instructeur depuis son événement ITP — table events -->
<div data-gvv-type="signature"
     data-gvv-name="signature_instructeur"
     data-gvv-source="instructor.event.itp.signature"
     data-gvv-param="instructor_login"
     data-gvv-lock="false">Signature instructeur</div>

<!-- Source globale (config) -->
<input name="organisme" type="text"
       data-gvv-source="config.organisme_formation">

<input name="date_signature" type="date"
       data-gvv-source="date.today">
```

#### Taxonomie des sources

```
── Table form_config_params ──────────────────────────────────────────────
config.<cle>               → form_config_params.param_value
                             (résolution section → global, sans param URL)

── Config GVV globale ────────────────────────────────────────────────────
club.nom                   → $config['nom_club']
club.sigle                 → $config['sigle_club']
club.adresse               → $config['adresse_club']
club.ville                 → $config['ville_club']
club.email                 → $config['email_club']

── Table membres (pilote) ────────────────────────────────────────────────
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
member.signature           → membres.signature_path

── Table events (pilote) ─────────────────────────────────────────────────
member.event.{type_key}.numero    → events.ecomment    (plus récent)
member.event.{type_key}.expiry    → events.date_expiration
member.event.{type_key}.date      → events.edate
member.event.{type_key}.signature → events.signature_path

── Table membres (instructeur) ───────────────────────────────────────────
instructor.*               → mêmes champs que member.*  param: instructor_login
instructor.signature       → membres.signature_path

── Table events (instructeur) ────────────────────────────────────────────
instructor.event.{type_key}.numero    → events.ecomment    (plus récent)
instructor.event.{type_key}.expiry    → events.date_expiration
instructor.event.{type_key}.date      → events.edate
instructor.event.{type_key}.signature → events.signature_path

── Utilisateur de session ────────────────────────────────────────────────
user.*                     → membre de la session courante (sans param)

── Dates calculées ───────────────────────────────────────────────────────
date.today                 → date('Y-m-d')
date.today_fr              → date('d/m/Y')
date.year                  → date('Y')
```

#### Clés `{type_key}` définies

| `type_key` | `events_types.id` | Nom affiché | Activité |
|---|---|---|---|
| `itp` | 43 | ITP | Planeur |
| `itv` | 44 | ITV | Planeur |
| `fi_spl` | 51 | FI Sailplane | Planeur |
| `fe_spl` | 52 | FE Sailplane | Planeur |
| `fi_ulm` | à créer | FI ULM | ULM |
| `fe_ulm` | à créer | FE ULM | ULM |
| `controle_competence` | 30 | Contrôle de compétence | Planeur |
| `visite_medicale` | 26 | Visite médicale | Tous |
| `bpp` | 27 | BPP | Planeur |
| `spl` | 50 | SPL | Planeur |

Pour les types `multiple=1` (ex. `visite_medicale`, `controle_competence`), le service prend l'entrée la plus récente (`ORDER BY edate DESC LIMIT 1`).

#### Règles de sécurité

- **Liste blanche stricte** : seules les sources déclarées dans la taxonomie sont autorisées.
- **Validation du paramètre** : le login fourni en URL doit exister et appartenir à la section active.
- **Lock côté serveur** : pour `data-gvv-lock="true"`, GVV ignore la valeur soumise et réinjecte la valeur résolue — le verrou HTML seul ne suffit pas.
- **Pas d'accès direct à la base** : le service passe exclusivement par la liste blanche.

### 8. Table events — évolutions requises

#### Colonne signature_path

Ajouter `signature_path VARCHAR(255) NULL` à la table `events` pour permettre le stockage d'une signature image associée à un événement de qualification (ex. signature numérisée de l'instructeur associée à son ITP ou son FI Sailplane).

```sql
ALTER TABLE events ADD COLUMN signature_path VARCHAR(255) NULL
    COMMENT 'Chemin vers la signature image associée à cet événement';
```

Cette colonne est alimentée soit par upload admin depuis la fiche membre, soit par pré-remplissage depuis `membres.signature_path` lors de la génération d'un formulaire.

#### Types ULM à créer

Les qualifications instructeur ULM manquent dans `events_types`. Ajouter :

| name | activite | expirable | multiple | annual |
|---|---|---|---|---|
| FI ULM | 2 | 1 | 0 | 0 |
| FE ULM | 2 | 1 | 0 | 0 |

#### Vérifications à réaliser

- **Dashboard events_types** : vérifier que les types d'événements sont accessibles depuis le tableau de bord admin (consultation et ajout de nouvelles entrées).
- **Formulaire membre** : vérifier que l'interface de saisie des événements d'un membre couvre tous les types pertinents (ITP, FI Sailplane, FI ULM, etc.) avec saisie du numéro (`ecomment`) et de la date d'expiration (`date_expiration`). Corriger si certains types sont manquants ou si le formulaire ne permet pas la saisie de ces champs.

### 9. Signatures

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
│  [Dessiner]  [Importer une image]  [Taper]      │
├─────────────────────────────────────────────────┤
│                                                 │
│   canvas | prévisualisation image | canvas      │
│           (dessin à la main)      (fonte manus.) │
│                                                 │
├─────────────────────────────────────────────────┤
│  [Effacer]                                      │
└─────────────────────────────────────────────────┘
<input type="hidden" name="signature_instructeur"      value="...base64...">
<input type="hidden" name="signature_instructeur_type" value="canvas|file|text">
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

#### Mode 3 — Saisie au clavier (fonte manuscrite)

L'utilisateur tape son nom ou sa signature. Le texte est rendu en temps réel sur un canvas en fonte **Caveat** (Google Fonts, ~30 KB). À la soumission, le canvas est exporté en PNG base64 et suit exactement le même pipeline serveur que le mode canvas dessiné (type = `text`).

- Fonte chargée via `@import url('https://fonts.googleapis.com/css2?family=Caveat&display=swap')`.
- La prévisualisation canvas (600×80 px) se met à jour à chaque frappe.
- À la soumission, normalisation vers 600×200 px avant envoi.
- Aucune dépendance JS supplémentaire.

**Option future** : signature PGP (OpenPGP.js + clé membre, hors V1 pour cause de complexité d'usage, ~500 KB de JS supplémentaire et valeur légale incertaine hors eIDAS qualifié).

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
| 3 | Saisie clavier (fonte Caveat) | Faible | Google Fonts CDN, canvas natif |
| 4 | Pré-remplissage profil GVV | Moyenne | Nouveau champ `membres.signature_path` |
| 5 | Signature PGP | Élevée | OpenPGP.js + clé membre + vérif serveur — hors V1 |

### 10. Import PDF -> HTML

- Pas de service de conversion, demander à Claude ou ChatGPT de réaliser la conversion
- Détection des champs du PDF source quand possible
- Génération d'une page HTML initiale + rapport des champs non convertis

### 11. Export PDF imprimable

- Rendu imprimé d'une soumission
- Génération d'un PDF lisible et téléchargeable
- Utilisable pour archivage documentaire

### 12. Archivage documentaire

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
