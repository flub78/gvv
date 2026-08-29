# Design Notes — Remplissage Formulaires

Date : 30 mai 2026

## Contexte

Le remplissage de formulaire est basé sur une logique « formulaires HTML natifs » avec lien public anonyme, pré-remplissage GVV et archivage documentaire. Cela a été préféré à l'approche initiale basée sur des formulaires pdf.

La stratégie d'implémentation privilégie d'abord un socle autonome de formulaires HTML avec gestion des fichiers, puis ajoute dans un second temps le pré-remplissage GVV et l'intégration workflow avancée.

## Taxonomie des formulaires

Trois catégories selon le degré d'intégration avec GVV :

```
Catégorie 1 — Autonome
  Lien public brut → forms_public → form_submissions
  Exemples : inscription_club

Catégorie 2 — Contextuel GVV
  Lien pré-rempli (pilot_login / instructor_login ou valeurs VLD) → forms_public → form_submissions
  Exemples : attestation_de_formation_ulm

Catégorie 3 — Intégré workflow
  Lien pré-rempli, rattaché à une entité GVV (subject_type/subject_id) → forms_public → form_submissions [+ handler optionnel]
  Détection générique : « une réponse soumise existe-t-elle pour ce sujet ? » sans dépendre d'archived_documents.
  Handler (optionnel) → effets de bord métier légers (ex. mise à jour d'une entité GVV existante)
  Exemples : briefing_passager_ulm
```

**Refactoring en cours (juillet 2026)** : le mécanisme de rattachement à une entité GVV (`subject_type`/`subject_id`, voir section 13) est conçu comme un **socle standalone du module `forms`**, indépendant de tout workflow particulier — n'importe quel formulaire catégorie 3 futur s'en sert de la même manière. `briefing_passager_ulm` en est le premier consommateur, dans le cadre du remplacement complet, à terme, de l'actuel mécanisme de briefing passager (`briefing_passager` controller, upload/signature, `archived_documents` type `briefing_passager`). L'archivage automatique d'un document depuis une soumission reste une **extension future optionnelle** du module `forms` (voir section 13 et « Réflexion en cours ») — elle ne conditionne pas la bascule du briefing passager vers `forms`.

**Invariant de non-régression** : toute évolution d'intégration GVV (mécanisme B, handlers) est additive. Les formulaires de catégorie 1 ne sont jamais impactés.

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

Il est probable qu'on ajoute un support pour le téléchargement des formulaires sous forme pdf ou image. Les formulaires pourront être remplis en ligne ou scanné et téléchargés. Bien sûr dans le second, ce ne seront que des images et ils ne pourront pas être intégrés dans les workflow GVV. L'application n'aura pas accès au contenu. Et elle ne sera même pas capable de vérifier que c'est bien un formulaire qui a été téléchargé.

## Phasage recommandé

### Phase 1 — Socle autonome

- gestion admin des formulaires
- rendu public multi-pages
- soumission anonyme
- support des fichiers
- consultation admin des réponses
- export PDF imprimable
- archivage d'une réponse vers pilote

### Phase 2 — Documents inline dans les formulaires

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
1. Présente les sélecteurs nécessaires selon les paramètres attendus par le formulaire (`pilot_login`, `instructor_login`, `machine_immat`, ou une combinaison).
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

Chaque formulaire déclare dans ses métadonnées (`forms.required_params`, ENUM) les paramètres GVV nécessaires. Un formulaire peut dépendre d'un pilote, d'un instructeur et/ou d'une machine, d'où les 8 combinaisons (produit cartésien des 3 dimensions) :
- `none` : formulaire public autonome, pas de page de génération.
- `pilot` : sélecteur membre requis → paramètre `pilot_login`.
- `instructor` : sélecteur instructeur requis → paramètre `instructor_login`.
- `machine` : sélecteur machine requis → paramètre `machine_immat` (liste des `machinesa`, seule table que `machine.*` sait résoudre à ce jour).
- `pilot+instructor`, `pilot+machine`, `instructor+machine`, `pilot+instructor+machine` : combinaisons des sélecteurs ci-dessus.

La page de génération s'adapte automatiquement selon cette configuration (helpers `forms_requires_pilot()`/`forms_requires_instructor()`/`forms_requires_machine()`, `application/helpers/forms_params_helper.php`, pour éviter de dupliquer la liste des combinaisons dans le contrôleur et les vues).

### 7. Pré-remplissage GVV — deux mécanismes

#### Mécanisme A : attributs `data-gvv-source` (contexte membre/instructeur)

Service : `form_prefill_service`

Les champs pré-remplis sont déclarés dans le HTML via des attributs `data-gvv-*` sur les éléments `<input>`, `<textarea>` et `<select>`. Ces attributs sont ignorés par le navigateur et parsés côté serveur par DOMDocument (même pipeline que `sync_fields_from_html`).

Applicable quand la source est la table `membres` ou `events` (identifié par `pilot_login` / `instructor_login`). Les sources `date.*`, `club.*`, `config.*` utilisent également ce mécanisme (elles sont auto-résolues sans paramètre d'identification).

#### Mécanisme B : paramètres URL directs (contexte entité GVV)

Pour les formulaires dont le contexte provient d'une entité GVV autre qu'un membre (vol de découverte, dossier, réservation), le contrôleur appelant passe les valeurs directement en paramètres URL :

```
/forms/{slug}
  ?{field_name}={valeur}     ← pré-remplissage du champ correspondant
  &lock[]={field_name}       ← verrouillage serveur de ce champ
  &subject_type={valeur}     ← référence générique à l'entité GVV d'origine (section 13)
  &subject_id={valeur}
```

`forms_public` sépare les paramètres en trois catégories :
- **Contexte** : noms réservés (`subject_type`, `subject_id`, `lock`, `page`, `pilot_login`, `instructor_login`) → mémorisés en session par slug, jamais injectés dans les champs ; `subject_type`/`subject_id` sont stockés avec la soumission (section 13), les autres ne sont pas persistés au-delà de la session de remplissage
- **Pré-remplissage** : tout paramètre dont le nom correspond à un `form_fields.name` → injecté comme valeur par défaut dans le champ HTML
- **Verrouillage** : paramètres listés dans `lock[]` → champ `readonly` + enforcement serveur à la soumission

Le formulaire HTML ne porte aucun attribut `data-gvv-source` pour les champs pré-remplis via mécanisme B. Les attributs statiques (`date.today`, `config.*`, `club.*`) peuvent coexister dans le même formulaire.

**Exemple — briefing_passager_ulm** :

| Champ formulaire | Source VLD | Verrouillé |
|---|---|---|
| `date_vol` | `vols_decouverte.date_vol` | Oui |
| `site_decollage` | `vols_decouverte.aerodrome` | Oui |
| `identification_ulm` | `vols_decouverte.airplane_immat` | Oui |
| `nom` | `vols_decouverte.beneficiaire` (1re partie) | Non |
| `prenom` | `vols_decouverte.beneficiaire` (2e partie) | Non |
| `poids_declare` | `vols_decouverte.participation` | Non |
| `personne_a_prevenir` | `vols_decouverte.urgence` | Non |
| `telephone` | `vols_decouverte.beneficiaire_tel` | Non |

#### Attributs

| Attribut | Rôle | Valeur |
|---|---|---|
| `data-gvv-source` | Source de la donnée GVV | voir taxonomie ci-dessous |
| `data-gvv-param` | Paramètre URL qui identifie l'entité | `pilot_login`, `instructor_login` |
| `data-gvv-lock` | Verrouillage côté serveur | `true` / `false` (défaut : `false`) |
| `data-gvv-label` | Libellé explicite du champ (en-tête de colonne dans la liste des réponses, libellés admin) | texte libre |

#### Résolution du libellé d'un champ (`Forms_field_parser`)

Le libellé d'un champ est résolu dans cet ordre, le premier non vide gagne :
1. attribut `data-gvv-label` sur le champ ;
2. `<label for="{id}">` correspondant à l'`id` du champ ;
3. `<label>` (sans `for`) qui englobe le champ ;
4. `<label>` (sans `for`) frère précédent immédiat du champ ;
5. repli : l'attribut `name` du champ.

Un élément autre que `<label>` (`<span>`, `<td>`…) n'est jamais utilisé comme source de libellé : un champ dont l'en-tête doit être maîtrisé alors qu'aucun `<label>` ne lui est rattaché porte `data-gvv-label`. L'astérisque « requis » et les espaces multiples sont normalisés.

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

── Table machinesa ────────────────────────────────────────────────────────
machine.numero_identification → machinesa.numero_identification  param: machine_immat

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
- **Portée `instructor.signature`** : cette source n'est résolue que si l'utilisateur authentifié (`dx_auth->get_username()`) correspond à `instructor_login` — sinon elle retourne `null` (widget vierge, saisie manuelle). Le use-case visé est l'instructeur qui génère lui-même une attestation ou une fiche de test ; dans tous les autres cas (personne connecté, ou connecté sous une autre identité), aucune signature n'est pré-remplie. Restriction propre à `instructor.signature` — `member.signature` n'est pas concerné (hors périmètre de cette évolution).

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

#### Alimentation de membres.signature_path (signature de référence instructeur)

Ce use-case est restreint à la signature de référence d'un **instructeur**, utilisée pour pré-remplir les attestations et fiches de test qu'il génère lui-même (cf. règle de portée dans « Règles de sécurité » ci-dessus). Il ne couvre pas la gestion d'une éventuelle seconde signature (élève, représentant légal), laissée à la charge de l'instructeur sur chaque formulaire.

Deux voies d'écriture dans `signature_path`, toutes deux via `membres_model` — donc tracées par les colonnes d'audit déjà présentes sur `membres` (`updated_by`/`updated_at`, migration 093) :

1. **Self-service** : page « Ma signature » dans le profil de l'instructeur, réutilisant le widget composite (dessiner/importer/taper). Il ne modifie que son propre `signature_path`.
2. **Import admin** : même widget exposé depuis la fiche membre d'un instructeur, réservé au rôle `club-admin` (`user_has_role('club-admin')`, même garde que les actions sensibles de `membre.php`) — permet à un administrateur du club d'associer une signature (par ex. un scan de signature papier) pour le compte d'un instructeur.

Dans les deux cas, le fichier remplace le précédent au même chemin (même pattern que `membres.photo`) — pas de table d'historique des versions.

#### Priorité de mise en œuvre

| Priorité | Fonctionnalité | Complexité | Prérequis |
|---|---|---|---|
| 1 | Dessin canvas | Faible | `signature_pad.umd.min.js` déjà présent |
| 2 | Upload image | Faible | Pipeline file existant |
| 3 | Saisie clavier (fonte Caveat) | Faible | Google Fonts CDN, canvas natif |
| 4 | Pré-remplissage profil GVV | Moyenne | Colonne `membres.signature_path` déjà créée (migration 121) et déjà lue par `forms_public`. Restent à livrer : écran d'alimentation self-service + import admin, et garde d'authentification sur `instructor.signature` |
| 5 | Signature PGP | Élevée | OpenPGP.js + clé membre + vérif serveur — hors V1 |

### 13. Intégration workflow GVV — référence générique au sujet et handler post-soumission

#### Principe

Deux mécanismes distincts, l'un générique et systématique, l'autre optionnel :

1. **Référence au sujet (`subject_type`/`subject_id`)** — rattache une soumission à l'entité GVV qui l'a fait naître (ex. un vol de découverte). C'est un socle standalone du module `forms` : n'importe quel contrôleur GVV peut l'utiliser pour poser la question « existe-t-il une réponse soumise pour cette entité ? » et pour faire retomber cet état à la suppression de la réponse — sans dépendre d'un handler ni d'`archived_documents`.
2. **Handler post-soumission (optionnel)** — pour les formulaires qui doivent déclencher un effet de bord métier léger après soumission (ex. mettre à jour un champ de l'entité GVV d'origine). Un formulaire sans besoin métier particulier n'a pas de handler.

Ces deux mécanismes sont indépendants : un formulaire catégorie 3 peut n'utiliser que la référence au sujet (détection + retour de suppression), sans aucun handler.

#### Référence générique au sujet

```sql
ALTER TABLE form_submissions ADD COLUMN subject_type VARCHAR(50) NULL
    COMMENT 'Type d''entité GVV rattachée, ex. vols_decouverte — générique, aucun sens métier propre au module forms';
ALTER TABLE form_submissions ADD COLUMN subject_id INT NULL
    COMMENT 'Identifiant de l''entité GVV rattachée';
-- index composite (subject_type, subject_id)
```

Référence polymorphe classique, volontairement portée par `form_submissions` (et non par `forms`) : chaque *soumission* est rattachée à une entité, pas le formulaire lui-même (un même formulaire catégorie 3 peut être réutilisé par plusieurs types de sujets si le besoin apparaît). Aucune colonne métier (`vld_id`, `stage_id`, ...) n'est ajoutée au module `forms` — c'est précisément ce que ce couple générique évite.

`forms_public` traite `subject_type`/`subject_id` comme des paramètres de contexte réservés (au même titre que `pilot_login`/`instructor_login`) : lus en GET sur la première page, mémorisés en session par slug, jamais injectés comme valeur de champ, transmis à `Form_submissions_model::create_submission()` à la soumission finale.

Détection et retour de suppression :

```php
// Form_submissions_model
public function get_current_for_subject($subject_type, $subject_id, $form_id = null) {
    // dernière soumission status='submitted' pour ce sujet (et ce formulaire si précisé)
    // ORDER BY created_at DESC LIMIT 1 — même logique que archived_documents_model::get_briefing_by_vld()
}
```

Cette méthode est une requête *live*, pas un indicateur mis en cache : la suppression d'une soumission (`delete_submission()`, déjà existant) fait automatiquement disparaître le résultat, sans code de synchronisation supplémentaire à écrire. C'est le même principe que l'actuelle sous-requête `has_briefing` sur `archived_documents`.

**Décision (juillet 2026)** : ce couple remplace l'usage initialement envisagé d'un `context_params TEXT` JSON pour porter `vld_id`. `context_params` est abandonné — la seule autre valeur de contexte envisagée (`token`, pour protéger le lien public) est elle-même hors périmètre actuel (voir « Décisions actées » ci-dessous). Si un besoin de contexte non structuré et non interrogeable réapparaît, il pourra être réintroduit à ce moment-là.

#### Interface des handlers (optionnel, par formulaire)

```php
// application/libraries/form_handlers/GvvFormHandlerInterface.php
interface GvvFormHandlerInterface {
    // Appelé après création de la soumission, uniquement si forms.handler_class est défini.
    // Retourne : ['redirect_url' => string|null, 'error' => string|null]
    public function after_submit(int $submission_id, ?string $subject_type, ?int $subject_id): array;
}
```

```sql
ALTER TABLE forms ADD COLUMN handler_class VARCHAR(100) NULL
    COMMENT 'Classe PHP du handler post-soumission, NULL = aucun';
```

Les handlers sont placés dans `application/libraries/form_handlers/`. `forms_public` instancie la classe déclarée dans `forms.handler_class` si elle implémente l'interface.

#### Handler de référence : BriefingPassagerUlmHandler

Périmètre volontairement réduit par rapport à la V0 de cette section : plus de génération PDF, plus d'archivage, plus d'invalidation de token — ces responsabilités sont retirées du handler (voir « Réflexion en cours »).

```
BriefingPassagerUlmHandler::after_submit($submission_id, $subject_type, $subject_id)
  ├── Vérifie $subject_type === 'vols_decouverte'
  ├── Récupère le VLD ($subject_id)
  ├── Met à jour vols_decouverte depuis les valeurs soumises (date_vol, beneficiaire, participation, urgence, ...)
  └── Retourne redirect_url → page de confirmation générique du module forms
```

#### Construction de l'URL par briefing_passager

```
/forms/briefing-passager-ulm
  ?subject_type=vols_decouverte     ← référence générique (→ form_submissions.subject_type/subject_id)
  &subject_id=<vld_id>
  &date_vol=2024-06-10              ← pré-remplissage mécanisme B
  &site_decollage=LFOG
  &identification_ulm=F-JXXX
  &nom=Dupont&prenom=Jean
  &poids_declare=75
  &personne_a_prevenir=Marie+Dupont
  &telephone=0612345678
  &lock[]=date_vol                  ← verrouillage mécanisme B
  &lock[]=site_decollage
  &lock[]=identification_ulm
```

Pas de `token` dans cette URL : le lien n'est pas protégé contre le devinage/rejeu à ce stade (voir « Réflexion en cours »). Utilisable tel quel pour un usage interne (pilote/instructeur connecté qui ouvre le formulaire depuis `briefing_passager/upload`), pas encore pour un envoi externe non supervisé (SMS/QR code au passager).

#### Comportement en cas d'erreur handler

La soumission est déjà créée avant l'appel du handler. En cas d'erreur :
- L'erreur est journalisée (`log_message('error', ...)`)
- La soumission reste accessible depuis l'admin pour retraitement manuel
- L'utilisateur voit un message d'erreur générique (pas de détails techniques)

### 14. Cartes dynamiques dans les dashboards

#### Principe

Un mécanisme piloté par données permet aux club-admins d'injecter des cartes de raccourci dans n'importe quel dashboard GVV sans modifier le code. Le cas d'usage principal est l'exposition de formulaires (génération d'attestation, briefing passager) depuis les dashboards pilote et instructeur.

**Implémentation réelle (Lot 7)** : GVV n'a pas de contrôleurs de dashboard séparés — un seul contrôleur `welcome.php` avec une méthode `section($name)` dont les valeurs sont `user, flights, treasurer, formation, maintenance, admin_club, admin_sys, dev`, rendues par une unique vue `bs_sub_dashboard.php` (un bloc `if/elseif` par section). La colonne `dashboard` ci-dessous utilise directement ces 8 valeurs plutôt que des noms de contrôleurs. Seule `bs_sub_dashboard.php` est instrumentée (pas le dashboard racine `bs_dashboard.php`, simple grille de tuiles de navigation vers les sections). L'icône utilise des classes Font Awesome (`fas fa-...`), déjà utilisées partout dans ces dashboards, plutôt que Bootstrap Icons (non chargé globalement dans l'application).

#### Table `dashboard_shortcuts`

| Colonne | Type | Contrainte |
|---|---|---|
| `id` | INT AUTO_INCREMENT | PRIMARY KEY |
| `dashboard` | VARCHAR(50) NOT NULL | Valeur `welcome.php` section() : `user`, `flights`, `treasurer`, `formation`, `maintenance`, `admin_club`, `admin_sys`, `dev` |
| `section` | VARCHAR(100) NULL | Sous-titre de regroupement dans le dashboard, NULL = non catégorisé |
| `title_key` | VARCHAR(100) NULL | Clé de langue GVV (optionnelle) |
| `title` | VARCHAR(100) NOT NULL | Texte affiché si `title_key` absent ou clé non trouvée |
| `description_key` | VARCHAR(255) NULL | Clé de langue GVV (optionnelle) |
| `description` | TEXT NULL | Texte affiché si clé absente ou non trouvée |
| `url` | VARCHAR(255) NOT NULL | URL relative (interne GVV) ou absolue (externe) |
| `icon` | VARCHAR(50) NULL | Classe Font Awesome, ex. `fa-file-signature` |
| `color` | VARCHAR(20) NULL | Classe Bootstrap (`primary`, `success`, …) ou hex `#3d6b84` |
| `role_required` | VARCHAR(50) NULL | NULL = tous ; sinon rôle GVV minimum requis pour voir la carte |
| `sort_order` | INT DEFAULT 0 | Ordre dans la section, croissant |
| `active` | TINYINT(1) DEFAULT 1 | 0 = désactivé (non affiché) |
| `club_id` | INT NULL | FK → clubs(id), NULL = toutes sections |
| `created_at` | TIMESTAMP | Audit |
| `updated_at` | TIMESTAMP | Audit |
| `created_by` | INT NULL | FK → membres(mlogin) |
| `updated_by` | INT NULL | FK → membres(mlogin) |

#### Résolution multi-langue

La résolution s'effectue au rendu, dans la langue active de la session :

```php
$title = ($title_key && $this->lang->line($title_key) !== false)
    ? $this->lang->line($title_key)
    : $title;
// idem pour description_key / description
```

#### URL interne vs externe

- URL interne (ne commence pas par `http`) : rendue avec `site_url($url)`, ouverte dans l'onglet courant.
- URL externe (commence par `http`) : attribut `target="_blank" rel="noopener noreferrer"` sur le lien.

#### Administration

Contrôleur dédié `shortcuts_admin` :
- CRUD complet : liste, créer, modifier, supprimer, activer/désactiver.
- Accès réservé `ca`/`club-admin` (même garde que `forms_admin`).
- Accessible depuis une carte "Raccourcis dashboard" dans le bloc `admin_club` de `bs_sub_dashboard.php` (public cible cohérent, plutôt que `forms_admin/index`).

#### Intégration dans les dashboards

`Welcome::section($name)` charge les raccourcis via un appel unique au modèle, puis `bs_sub_dashboard.php` inclut un partial view commun :

```php
// Welcome::section($name)
$data['shortcuts'] = $this->dashboard_shortcuts_model
    ->get_for_dashboard($name, $club_id);
// bs_sub_dashboard.php, après le bloc if/elseif par section
$this->load->view('welcome/_dashboard_shortcuts', array('shortcuts' => $shortcuts));
```

Le partial `welcome/_dashboard_shortcuts.php` parcourt les raccourcis (déjà filtrés par dashboard/actif/club/rôle) groupés par `section` et rend chaque carte au même format `.sub-card` que les cartes existantes.

**Sections instrumentées** : les 8 valeurs `welcome.php` (`user`, `flights`, `treasurer`, `formation`, `maintenance`, `admin_club`, `admin_sys`, `dev`) — un seul point d'intégration (`Welcome::section()`/`bs_sub_dashboard.php`) couvre l'ensemble, contrairement à l'hypothèse initiale de plusieurs contrôleurs à instrumenter individuellement.

#### Impact sur les tests Playwright

La table démarre vide (pas de seed dans la migration) : aucun lien nouveau n'apparaît dans les dashboards tant qu'un admin ne crée pas de raccourci réel, donc les tests d'accessibilité qui parcourent toutes les URLs visibles (`*-recursive-authorizations.spec.js`) n'ont pas nécessité de modification pour ce lot. Le filtrage par rôle (`role_required` non satisfait ⇒ carte non rendue) suit le même principe que les cartes actuellement codées en dur dans `bs_sub_dashboard.php`, donc pas de régression attendue si un raccourci réel est créé plus tard.

### 10. Import PDF -> HTML

Pas de service de conversion intégré à GVV. Processus manuel, assisté par un outil d'IA externe (Claude, ChatGPT, ...) : voir [guide de rédaction — Convertir un formulaire PDF existant](../users/fr/13_formulaires_creation.md#convertir-un-formulaire-pdf-existant).

**Limites** :
- pas de détection automatique des champs du PDF source — les `name="..."` sont ajoutés/vérifiés manuellement après conversion ;
- pas de garantie de fidélité visuelle au document d'origine ;
- relecture manuelle obligatoire avant publication (contraintes de rendu — voir [Règles CSS](../users/fr/13_formulaires_creation.md#règles-css) du guide de rédaction).

### 11. Export PDF imprimable

- Rendu imprimé d'une soumission
- Génération d'un PDF lisible et téléchargeable
- Utilisable pour archivage documentaire

### 12. Archivage documentaire

- Entite : `archived_documents`
- Réutiliser le formulaire existant de création de document archivé.
- Depuis le détail d'une réponse, un bouton ouvre ce formulaire avec le PDF imprimable déjà pré-rempli à la place du sélecteur de fichier.
- Journalisation dans les fichiers de logs.

## Comparaison forms vs archived_documents

**Statut : analyse de cadrage, sert de base à la section suivante.**

### Points communs

- Rattachement à une entité GVV via FK (`archived_documents` : `pilot_login`/`section_id`/`vld_id`/`machine_immat` ; `forms`/`form_submissions` : `club` sur le formulaire, rattachement générique `subject_type`/`subject_id` sur la soumission).
- Champs d'audit complets (`created_at`/`updated_at`/`created_by`/`updated_by`).
- Support de fichiers et consultation admin (listes, filtres, prévisualisation).
- Peuvent aboutir à un PDF affichable/imprimable.

### Différences

| Dimension | `archived_documents` | `forms` / `form_submissions` |
|---|---|---|
| Cardinalité fichier | 1 fichier courant, avec chaîne de versions (`previous_version_id`, `is_current_version`) | N fichiers par soumission (`form_submission_files`), sans notion de version |
| Rapport au temps | Objet vivant : `valid_from`/`valid_until` + `alarm_disabled` → rappels d'expiration | Objet instantané : `submitted_at` fige un état, pas d'expiration |
| Remplacement | Explicite et exclusif : une nouvelle version remplace la précédente comme « courante » | Aucune relation entre soumissions : deux soumissions coexistent sans hiérarchie |
| Circuit de validation | `validation_status` (pending/approved/rejected) + `validated_by/at` + `rejection_reason` | Statut purement technique (`started`/`submitted`/`archived`), pas de circuit métier natif |
| Portée/confidentialité | `document_types.scope` (pilot/section/club) + `is_private` + `is_admin_only` | Rattachement club/section simple sur `forms`, pas de notion de propriétaire individuel |
| Nature de la donnée | Fichier opaque (scan/photo), contenu non interprété par GVV | Données structurées champ par champ (`form_submission_values`), interrogeables individuellement |
| Recherche | Transversale par type de document × entité | Par formulaire × soumission |
| Fréquence typique | Rare, longue durée de vie (licence, certificat médical) | Peut être fréquente et éphémère (un briefing par vol) |

Point de cohérence avec l'existant : les qualifications (`events` + `events.date_expiration`, `events.signature_path`) constituent déjà une troisième représentation, indépendante d'`archived_documents` — la donnée structurée (numéro, expiration) vit dans `events`, la preuve scannée optionnelle dans `archived_documents`. C'est déjà le pattern « donnée structurée / preuve documentaire découplées » que l'idée d'image optionnelle pour les qualifications appelle de ses vœux.

### Stratégie d'utilisation

**Utiliser `archived_documents` quand :**
- le document a une durée de validité et doit déclencher une alerte d'expiration ;
- le document provient de l'extérieur (autorité, scan papier) sans besoin de contenu structuré ;
- une notion de version courante remplaçant la précédente a du sens (renouvellement) ;
- un circuit d'approbation admin (pending/approved/rejected) est nécessaire ;
- le document est attaché à une entité durable (pilote, machine, section) plutôt qu'à un événement ponctuel.

**Utiliser `forms` quand :**
- la donnée doit être exploitée champ par champ (recherche, export, alimentation d'une autre table GVV) ;
- le parcours de collecte a de la valeur en soi (multi-pages, validation de saisie, signature, pré-remplissage GVV) ;
- il n'y a pas de notion d'expiration — événement transactionnel instantané (inscription, déclaration, engagement) ;
- plusieurs soumissions successives et indépendantes sont normales, sans notion de remplacement.

### Association dans les workflows GVV

`archived_documents` est le référentiel de vérité documentaire durable ; `forms` est un mécanisme de collecte parmi d'autres (à côté de l'upload direct). Quatre patterns d'association :

1. **Formulaire comme mode de saisie alternatif d'un document archivé** — pattern historique, encore en place sur l'actuel `bs_uploadView.php` (`briefing_passager`, mécanisme appelé à disparaître) : bouton « upload » (scan) et bouton « signer en ligne » aboutissaient tous deux, in fine, au même `archived_documents` rattaché au VLD.
2. **Formulaire comme détecteur d'état autonome, rattaché à une entité GVV** — pattern retenu pour `briefing_passager_ulm` (juillet 2026) : la soumission porte `subject_type`/`subject_id` (section 13), interrogeable directement pour savoir « une réponse existe-t-elle pour cette entité ? », sans passer par `archived_documents`. C'est le pattern par défaut pour tout futur formulaire catégorie 3 qui n'a pas de besoin d'archivage documentaire durable.
3. **Formulaire comme flux transactionnel autonome, jamais rattaché** — catégories 1/2 (inscription club, demande interne) : pas d'entité GVV à rattacher, pas de document durable à produire.
4. **Formulaire référençant un document archivé en lecture** — composant 4 (`form_document_refs`) : le document est affiché *dans* le formulaire (ex. règlement intérieur à lire avant signature) sans que le formulaire ne le génère.

Ces rôles ne sont pas concurrents : un même formulaire de catégorie 3 peut cumuler plusieurs patterns (ex. référencer un document existant en lecture tout en étant rattaché à une entité via `subject_type`/`subject_id`). Le pattern 1 reste documenté pour mémoire le temps que l'ancien mécanisme de briefing passager soit effectivement retiré ; il n'est plus le modèle à suivre pour de nouveaux formulaires.

### Grille de décision

| Besoin métier | Mécanisme recommandé |
|---|---|
| Certificat médical, licence fédérale, assurance | `archived_documents` seul — upload direct, alerte d'expiration native |
| Attestation générée par GVV après une formation | `forms` (catégorie 3) → `archived_documents` généré automatiquement (flag) |
| Inscription club, demande de contact | `forms` seul (catégorie 1) — pas d'archivage |
| Briefing passager VLD | `forms` (catégorie 3), détection via `subject_type`/`subject_id` ; archivage vers `archived_documents` non requis (option future générique, non activée pour ce cas) |
| Règlement intérieur à consulter avant signature | `archived_documents` référencé en lecture dans une page de formulaire (composant 4) |
| Qualification (ITP, FI, brevet…) | `events` pour la donnée structurée (déjà en place) + `archived_documents` optionnel pour la preuve scannée |

Le fil conducteur : `forms` répond à « comment collecter », `archived_documents` répond à « où vit la vérité durable et consultable ». Un formulaire de catégorie 3 n'est pas une alternative à `archived_documents`, c'est une deuxième porte d'entrée vers lui.

### 15. Soumission par téléchargement (scan)

#### Principe

Alternative au remplissage en ligne : sur un formulaire où l'option est activée, l'utilisateur peut télécharger un scan ou une photo du formulaire imprimé puis rempli à la main, à la place de la saisie champ par champ. GVV n'a pas accès au contenu de ce fichier et ne peut pas vérifier qu'il s'agit effectivement du bon formulaire — cohérent avec la limite déjà anticipée en "Note d'évolution probable".

Un seul fichier par réponse.

#### Activation par formulaire

Colonne `forms.allow_upload_response` (booléen, défaut faux). Le bouton "Télécharger un formulaire prérempli" n'apparaît sur la page publique et dans la liste admin que si cette option est activée. Choix délibéré d'un opt-in plutôt qu'une disponibilité systématique : un formulaire de catégorie 3 qui met à jour une entité GVV à la soumission (ex. `briefing_passager_ulm`) n'a pas nécessairement de sens à accepter un simple scan opaque.

#### Modèle de données

Pas de nouvelle table. Réutilisation de `form_submissions` et `form_submission_files` :

```sql
ALTER TABLE forms ADD COLUMN allow_upload_response TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE form_submissions ADD COLUMN submission_method ENUM('online','upload') NOT NULL DEFAULT 'online';
ALTER TABLE form_submissions ADD COLUMN upload_comment VARCHAR(255) NULL;
```

Le fichier téléchargé est stocké comme une ligne `form_submission_files` avec `field_id = NULL` et `widget_name = 'uploaded_response'` — le même mécanisme que celui introduit en migration 137 pour les widgets de signature définis uniquement en HTML (sans `form_fields` associé). Aucune valeur n'est stockée dans `form_submission_values` : une réponse de type `upload` n'a pas de champs remplis.

`response_identifier` (calculé dans `form_submissions_model::get_form_submissions()`) devient :

```sql
COALESCE(
  (SELECT GROUP_CONCAT(...) FROM form_submission_values ... WHERE is_identifier = 1),
  s.upload_comment
) AS response_identifier
```

`response_identifier` reste utilisé pour le libellé de la modale de suppression et la vue détail d'une réponse. La **liste** des réponses (`forms_admin/submissions`), elle, n'affiche plus une colonne "Identification" fusionnée : elle rend **une colonne par champ marqué `data-gvv-identifier`**, chacune intitulée avec le libellé résolu du champ (voir « Résolution du libellé d'un champ »), dans l'ordre d'apparition dans le formulaire (`form_submissions_model::get_identifier_values()` fournit la valeur de chaque champ par soumission). Un formulaire sans aucun champ identifiant n'a donc aucune colonne de ce type. Pour une réponse de type `upload` (sans `form_submission_values`), le commentaire de téléchargement est affiché dans la première de ces colonnes.

#### Stockage fichier

```
uploads/reponses/{form_id}/reponse_{submission_id}.{ext}
```

L'identifiant de soumission (auto-increment, déjà unique) sert de numéro de séquence — pas de compteur par formulaire à gérer, pas de risque de collision en cas de téléchargements concurrents.

Types acceptés : `pdf`, `jpg`, `jpeg`, `png`, `gif`, `webp` — les seuls formats supportant à la fois la rotation et la génération de miniature dans l'existant.

#### Réutilisation stricte de l'infrastructure `archived_documents`

| Besoin | Composant réutilisé |
|---|---|
| Compression | `File_compressor` (GD pour image, Ghostscript pour PDF) — inchangé |
| Miniature PDF | `Pdf_thumbnail` (`thumb_<nom>.jpg` à côté du fichier) — inchangé |
| Miniature cliquable dans la liste | Helper `attachment($id, $filename, $url)` — inchangé, gère déjà image/PDF/fallback |
| Zone de dépôt drag&drop | Pattern natif `initDropZone()` de `archived_documents/bs_formView.php` — dupliqué dans la modale de téléchargement |
| Service sécurisé du fichier | `forms_admin/submission_file/{form_id}/{submission_id}/{file_id}?inline=1` — déjà protégé contre le path traversal, déjà existant |

#### Rotation — extraction de `File_rotator`

La rotation (qpdf pour PDF page 1, ImageMagick `convert` pour image) existe déjà, mais inline dans `Archived_documents::rotate()`. Elle est extraite dans une librairie partagée `application/libraries/File_rotator.php` (`rotate($absolute_path, $mime, $direction)`), utilisée à la fois par `archived_documents` (refactor, comportement inchangé) et par le nouveau `forms_admin::submission_rotate()`. Un test PHPUnit est ajouté pour cette librairie avant le refactor, aucun test ne couvrant la rotation aujourd'hui.

#### Liste admin des réponses

Pour une ligne dont `submission_method = 'upload'` :
- bouton "Ouvrir" masqué (pas de vue "champs" pertinente — aucune valeur structurée) ;
- bouton "Générer PDF" remplacé par la miniature cliquable (ouvre le fichier en grand dans un nouvel onglet) ;
- boutons de rotation (↺/↻), visibles uniquement pour ce type de réponse ;
- suppression : supprime aussi le fichier et sa miniature du disque.

Accès direct par URL aux vues `submission()`/`submission_view()`/`submission_pdf()` pour une réponse de type `upload` : redirection directe vers le fichier (pas de gabarit de champs à remplir).

### 16. Paiement en ligne intégré (widget HelloAsso)

#### Principe

Un formulaire peut porter au maximum un paiement HelloAsso (V1), déclenché à la soumission plutôt que dans une page dédiée. Le choix d'architecture est de **ne quasiment rien ajouter au socle `forms`** : le pipeline de paiement (`paiements_en_ligne` : création de checkout, webhook, écriture comptable) existe déjà et sert aujourd'hui des flux authentifiés (`paiement_generique`) et anonymes (`public_bar`, `public_decouverte`) — voir `application/controllers/paiements_en_ligne.php`. Le paiement de formulaire n'est qu'un nouveau consommateur de ce pipeline, initié par un handler post-soumission (section 13), pas un sous-système parallèle.

Réutilisations directes, sans modification :
- `Helloasso::create_checkout($club_id, $params)` (`Helloasso.php:192`) — résout la config HelloAsso (compte, environnement) par `club_id`. Le paiement de formulaire réutilise `forms.club` comme `club_id` : le rattachement section d'un formulaire (déjà en place depuis le Lot 1) désigne à la fois la section GVV et l'organisation HelloAsso à débiter — pas de paramètre `data-gvv-account` (général/ULM/avion/planeur) distinct à introduire.
- `paiements_en_ligne_model::_ecriture_paiement_generique()` (`paiements_en_ligne_model.php:865`) — débite 467, crédite le compte destination. C'est déjà l'écriture "dans un compte GVV" demandée ; le paiement de formulaire réutilise le type `paiement_generique` (ou un type dédié équivalent) plutôt que réinventer une logique comptable.
- `helloasso_webhook()` (`paiements_en_ligne.php:1647`) — traitement asynchrone et idempotent du retour HelloAsso, déjà club-scopé et déjà utilisé par des flux anonymes. Aucune nouvelle route/authentification à concevoir.

#### Déclaration dans le HTML

Même pattern que le widget signature (section 9) : un `<div data-gvv-type="payment">` détecté par `sync_fields_from_html`/`extract_html_fields`, remplacé par GVV au rendu.

```html
<div data-gvv-type="payment"
     data-gvv-name="paiement_cotisation"
     data-gvv-description="Première cotisation 2026"
     data-gvv-amounts="90,120,150"
     data-gvv-compte-id="123"
     data-gvv-required="true">
  Réglez votre cotisation
</div>
```

| Attribut | Rôle |
|---|---|
| `data-gvv-name` | Nom technique du champ (valeur soumise = montant choisi) |
| `data-gvv-description` | Libellé transmis à HelloAsso (`item_name`) et affiché dans les résultats |
| `data-gvv-amounts` | Liste de montants proposés (radio) ; absent = montant libre, borné par la configuration HelloAsso de la section (`montant_min`/`montant_max`, déjà utilisés par `paiement_generique`) |
| `data-gvv-compte-id` | Compte comptable GVV crédité (`compte_destination_id`) |
| `data-gvv-required` | `true` = paiement obligatoire, `false`/absent = facultatif (même convention que le widget signature) |

Ces paramètres vivent dans le contenu HTML de la page, pas dans de nouvelles colonnes de `forms` — cohérent avec le principe déjà établi (section 13) de ne pas ajouter de colonnes métier au socle générique.

#### Cycle de vie et handler

```
FormPaymentHandler::after_submit($submission_id, $subject_type, $subject_id)
  ├── Reparse le content_html de la page pour retrouver description/amounts/compte_id
  ├── Relit le montant choisi dans les valeurs soumises
  ├── Revalide le montant côté serveur (bornes de section — jamais confiance dans le POST)
  ├── Crée la transaction paiements_en_ligne + le checkout HelloAsso (club_id = forms.club)
  │     metadata additionnelle : form_submission_id (lien retour)
  └── Retourne redirect_url → checkout HelloAsso
```

`forms_public::submit()` n'a besoin d'aucune modification : le mécanisme « redirige vers `redirect_url` si fourni, sinon page de remerciement standard » existe déjà depuis le Lot 6 étape 6.3.

Le retour HelloAsso (`return_url`) pointe vers une route publique légère qui réaffiche `bs_thanks` sans présumer du résultat — la confirmation reste **asynchrone**, portée uniquement par le webhook, comme pour tous les autres flux HelloAsso du projet.

Au webhook, en plus de l'écriture comptable habituelle, `form_submissions.payment_status` de la soumission liée est mis à jour (`none` → `pending` → `paid`/`failed`).

#### Paiement obligatoire vs facultatif

- **Facultatif** : `payment_status` est purement informatif, la réponse reste acceptée dans tous les cas.
- **Obligatoire** : la réponse n'est considérée valide qu'une fois `payment_status = paid`. Sans confirmation, elle est marquée rejetée — conservée pour traçabilité admin, mais pas traitée comme une réponse valide en aval (ex. pas de déclenchement d'un éventuel autre handler métier). Le critère exact de bascule vers "rejetée" (délai d'attente vs échec/annulation explicite côté HelloAsso) reste ouvert — voir Questions ouvertes du PRD (EF13).

#### Affichage du statut de paiement

- **Admin** (`bs_submission.php`) : badge explicite (payé / en attente / non payé / rejeté), montant, description, lien vers la transaction `paiements_en_ligne` correspondante — même logique d'affichage que la miniature de signature déjà en place dans cette vue.
- **PDF imprimable** (`submission_view`/`submission_pdf`) : le statut de paiement doit apparaître dans le rendu, pas seulement en admin — une réponse imprimée sans cette information serait trompeuse (ex. archivage d'un document qui semble complet alors que le paiement obligatoire n'a jamais été confirmé).

#### Sécurité

Un paiement de formulaire transforme un lien public réutilisable (risque déjà identifié) en générateur de checkouts HelloAsso à la demande. La limitation de débit sur soumissions publiques (déjà listée en « Sécurité » ci-dessous, toujours non implémentée) devient un prérequis plus pressant dès qu'un widget de paiement est en jeu, même si l'abus direct est limité (un rejeu du lien crée des opportunités de paiement légitimes, pas de l'argent gratuit).

### 17. Sous-formulaires (formulaires imbriqués)

#### Principe

Un widget `data-gvv-type="subform"` permet d'insérer, dans une page de formulaire, un lien vers le remplissage d'un autre formulaire GVV. Le sous-formulaire s'ouvre dans un **nouvel onglet** — pas de fusion DOM ni d'iframe, chaque formulaire garde son propre CSS/JS, sans risque de collision entre les deux. Une fois soumis, un résumé lecture seule de la réponse est injecté dans le widget du formulaire maître.

Cette capacité est orthogonale à la taxonomie des catégories 1/2/3 (voir « Taxonomie des formulaires ») : elle décrit comment deux formulaires se composent entre eux, indépendamment du fait que l'un ou l'autre soit par ailleurs rattaché à une entité GVV.

#### Déclaration dans le HTML

Même convention que les widgets signature (section 9) et paiement (section 16) :

```html
<div data-gvv-type="subform"
     data-gvv-name="briefing_passager"
     data-gvv-form-slug="briefing-passager-ulm"
     data-gvv-required="true">
  Briefing passager
</div>
```

| Attribut | Rôle |
|---|---|
| `data-gvv-name` | Nom technique du widget (identifie la valeur associée dans la soumission maître) |
| `data-gvv-form-slug` | `public_slug` du formulaire à ouvrir en sous-formulaire |
| `data-gvv-required` | `true` = le formulaire maître ne peut être soumis sans une réponse liée au sous-formulaire ; `false`/absent = facultatif (même convention que le widget paiement) |

#### États du widget

```
Non rempli                    En attente de vérification      Rempli
┌───────────────────────┐    ┌───────────────────────┐        ┌───────────────────────┐
│ [Remplir le            │    │ [Remplir le            │        │ résumé lecture seule    │
│  sous-formulaire] ↗    │ →  │  sous-formulaire] ↗    │   →    │ [Voir la réponse]      │
│                        │    │ [J'ai terminé, vérifier]│        │ [Remplir à nouveau] ↗  │
└───────────────────────┘    └───────────────────────┘        └───────────────────────┘
```

Le passage vers « en attente de vérification » se fait dès le premier clic sur le lien (ouverture du sous-formulaire dans le nouvel onglet). Le passage vers « rempli » se fait par une action explicite de l'utilisateur (bouton « J'ai terminé, vérifier ») qui déclenche une requête ciblée limitée au widget — jamais un rechargement complet de la page maître ni un mécanisme silencieux type `postMessage`/polling automatique : le formulaire maître n'est pas persisté côté serveur page par page pendant sa saisie (voir « Mécanisme de corrélation » ci-dessous), un rechargement complet ferait perdre la saisie en cours de l'utilisateur sur sa page courante, et une vérification automatique qui échoue silencieusement irait à l'encontre de la règle GVV « le résultat d'une action doit toujours être visible ».

« Remplir à nouveau » (depuis l'état « rempli ») rouvre le sous-formulaire vierge : il n'y a pas d'édition en place d'une réponse déjà soumise, seulement une resoumission complète.

#### Mécanisme de corrélation avant soumission du maître

`forms_public` ne persiste aujourd'hui aucune valeur en base tant que le formulaire maître n'est pas soumis (navigation multi-pages portée par des champs cachés reconduits de page en page ; une seule ligne `form_submissions` créée à la validation finale). Le sous-formulaire ne peut donc pas être rattaché au maître via le couple générique `subject_type`/`subject_id` (section 13) tant que le maître n'existe pas encore en base — ce couple suppose un `subject_id` déjà connu.

La corrélation, avant soumission du maître, passe par un jeton porté par le même circuit que les paramètres réservés déjà mémorisés en session par slug (`subject_type`, `pilot_login`, `lock[]`, etc.) :

1. À la génération du widget, un `link_token` est créé (ou réutilisé s'il existe déjà en session pour ce slug) et transmis en paramètre réservé à l'URL du sous-formulaire.
2. `forms_public` stocke ce jeton sur la ligne `form_submissions` du sous-formulaire (nouvelle colonne `link_token`, infrastructurelle — comme `submission_uuid`, sans signification métier).
3. Le bouton « Vérifier » du widget interroge un point d'accès de consultation par jeton, qui renvoie un résumé de la réponse si elle existe.
4. À la soumission finale du formulaire maître, `create_submission()` retrouve le(s) sous-formulaire(s) lié(s) par jeton et leur écrit `subject_type='form_submission'` / `subject_id=<id du maître>` : le rattachement générique déjà décrit en section 13 redevient alors la seule référence durable — le jeton n'est qu'un échafaudage pour la phase de saisie anonyme.

**Décision actée (Lot 11)** : le couple `subject_type`/`subject_id` d'une soumission ne peut porter qu'une seule référence à la fois. Si le formulaire utilisé comme sous-formulaire est par ailleurs un formulaire de catégorie 3 rattaché en direct à une entité GVV (ex. un `briefing_passager_ulm` ouvert à la fois seul, avec son propre `subject_type='vols_decouverte'`, et comme sous-formulaire d'un autre formulaire), la bascule décrite ci-dessus est **ignorée** : `Form_submissions_model::backfill_subject_from_link_token()` ne met à jour `subject_type`/`subject_id` que si les deux valent encore NULL. L'attachement direct du sous-formulaire à son entité GVV d'origine a donc toujours priorité sur son usage en tant que sous-formulaire ; le `link_token` porté par la ligne suffit à lui seul à prouver la relation sous-formulaire/maître, sans avoir besoin d'écraser le couple générique. La liste blanche des formulaires utilisables comme sous-formulaire (deuxième question ouverte du PRD) reste non tranchée — n'importe quel formulaire publié est éligible en V1.

#### Sous-formulaire non rattaché (formulaire maître jamais soumis)

Si le formulaire maître n'est jamais validé, la réponse du sous-formulaire est **conservée**, pas supprimée : c'est une soumission autonome et valide au même titre que n'importe quelle autre réponse de ce formulaire ; la supprimer détruirait une donnée réellement saisie sans bénéfice clair. Elle reste simplement sans `subject_type`/`subject_id` (jamais rattachée, faute de maître à référencer).

La liste admin des réponses affiche un badge « non rattaché » pour ces soumissions, afin que l'admin les distingue des réponses effectivement exploitées par un workflow, sans purge automatique.

#### Resoumission et fichiers déjà téléchargés

Une resoumission crée une nouvelle ligne `form_submissions` avec ses propres `form_submission_files`, exactement comme deux soumissions indépendantes du même formulaire (voir « Différences » dans la comparaison `forms` vs `archived_documents` : « Aucune relation entre soumissions »). Les fichiers de la soumission précédente ne sont ni supprimés ni fusionnés avec la nouvelle — ils restent consultables en admin, rattachés à leur soumission d'origine. Le widget du maître n'affiche que la soumission liée la plus récente (même règle que `get_current_for_subject()`).

#### Hors périmètre V1

- Sous-formulaires récursifs (un sous-formulaire contenant lui-même un widget sous-formulaire) — un seul niveau d'imbrication en V1.
- Sous-formulaires répétables (N instances, ex. liste de passagers) — le widget V1 gère une seule réponse liée par formulaire maître.
- Édition en place d'une réponse de sous-formulaire déjà soumise — resoumission complète uniquement.

### 18. Export d'une réponse vers un formulaire de création GVV

#### Principe

Un formulaire peut déclarer une cible d'export : une URL de formulaire de création GVV standard (ex. `membre/create`) et un libellé de bouton. Quand les deux sont renseignés, un bouton apparaît sur chaque ligne de la liste des réponses (`bs_submissions.php`) et ouvre l'URL cible avec les valeurs de la réponse en paramètres de requête, un paramètre par champ.

Contrairement aux mécanismes de pré-remplissage GVV (section 7, `data-gvv-source`), le sens du flux est inversé ici : une réponse `forms` alimente un formulaire de création GVV situé en dehors du module, pas l'inverse.

#### Colonnes `forms`

```sql
ALTER TABLE forms ADD COLUMN target_url VARCHAR(255) NULL
    COMMENT 'URL du formulaire de création GVV à préremplir depuis une réponse, NULL = pas de bouton export';
ALTER TABLE forms ADD COLUMN target_label VARCHAR(100) NULL
    COMMENT 'Libellé du bouton export, affiché sur la liste des réponses';
```

Le bouton n'apparaît que si les deux colonnes sont renseignées — pas de valeur par défaut pour `target_label` qui rendrait un bouton sans texte.

#### Construction de l'URL

Pour une réponse donnée, l'URL est construite en concaténant `target_url` et une query string dérivée de `form_submission_values` :

```
{target_url}?{champ1}={valeur1}&{champ2}={valeur2}...
```

Règles de construction :
- un paramètre par ligne de `form_submission_values` de la soumission, nommé `form_fields.name` ;
- valeurs urlencodées ;
- champs de type `file` et `signature` exclus (pas de `value_text` exploitable — ces champs vivent dans `form_submission_files`, jamais dans `form_submission_values`) ;
- champs à choix multiples (checkbox multi-valeurs) exclus en V1 — pas d'encodage `champ[]=` pour ce mécanisme.

Aucune correspondance configurable entre noms de champs : le nom du champ source (`form_fields.name`) doit être identique au nom de champ attendu par le formulaire cible. C'est une contrainte de configuration assumée à la charge de l'admin qui déclare `target_url`, pas une limite technique à lever plus tard par un mapping.

#### Réception côté formulaire de création GVV — extension générique de `Gvv_Controller::create()`

`Gvv_Controller::create()` (`application/libraries/Gvv_Controller.php:233`) initialise aujourd'hui `$this->data` uniquement depuis `$this->gvvmetadata->defaults_list($table)` — aucun paramètre de requête n'est lu. Pour que l'URL construite ci-dessus produise réellement un formulaire pré-rempli, `create()` doit fusionner par-dessus ces valeurs par défaut les paramètres `$_GET` dont le nom correspond à une colonne connue de la table cible :

```php
function create() {
    ...
    $this->data = $this->gvvmetadata->defaults_list($table);
    // Surcharge par les paramètres de requête correspondant à une colonne connue
    foreach ($this->input->get() as $key => $value) {
        if (array_key_exists($key, $this->data)) {
            $this->data[$key] = $value;
        }
    }
    ...
}
```

Cette évolution est volontairement générique et non spécifique au module `forms` : elle bénéficie à tout `create()` metadata-driven appelé avec des paramètres de requête, cohérent avec le principe déjà appliqué au mécanisme B (section 7) où le pré-remplissage est piloté par la correspondance de noms plutôt que par une déclaration explicite côté récepteur.

#### Sécurité

Le bouton est exposé uniquement dans la liste admin des réponses (`forms_admin`), déjà protégée par l'authentification GVV — pas d'exposition publique de `target_url` ni des valeurs de la réponse. La fusion `$_GET` dans `Gvv_Controller::create()` ne fait que pré-remplir un formulaire déjà soumis à la validation standard (`formValidation()`, unicité, règles metadata) : aucune donnée n'est enregistrée tant que l'admin ne valide pas explicitement le formulaire de création.

### 19. Modification en place d'une réponse déjà soumise

#### Principe

Cas d'usage : utiliser un formulaire comme support de gestion de procédure, où une réponse doit pouvoir être complétée ou corrigée après sa soumission initiale (ex. une pièce ajoutée plus tard, un champ laissé vide à compléter). L'édition est déclenchée depuis `forms_admin`, jamais via un lien public envoyé à l'utilisateur d'origine — c'est une action admin, au même titre que la suppression d'une réponse.

Aucune nouvelle table n'est nécessaire. `form_submissions` porte déjà `updated_at`/`updated_by` (champs d'audit standard), et `Form_submissions_model::save_submission_values()` est déjà un upsert par couple `(submission_id, field_id)` — la resoumission d'une valeur déjà présente la met simplement à jour, elle ne duplique rien.

#### Point d'entrée : `forms_admin`, pas `forms_public`

Contrairement au reste du rendu de formulaire (moteur multi-pages, validation, widgets), qui vit dans le contrôleur public anonyme `forms_public`, l'édition est portée par deux nouvelles méthodes authentifiées de `forms_admin` :

- `submission_edit($form_id, $submission_id)` : affiche le formulaire pré-rempli.
- `submission_edit_submit($form_id, $submission_id)` : valide et enregistre.

Les deux réutilisent le moteur de rendu et de validation existant (`Forms_renderer`, `form_fields_model`, `forms_validation`, pagination multi-pages) — pas de duplication de la logique par type de champ. Seule la source de pré-remplissage change : au lieu de la flashdata "anciennes valeurs après échec de validation" utilisée par `forms_public::index()`, le pré-remplissage vient de `form_submission_values` et `form_submission_files` de la soumission éditée.

L'autorisation réutilise le contrôle déjà en place sur `submission_delete` : refus si la section active de l'admin ne correspond pas au club du formulaire.

#### Garde-fous sur la réponse éditable

- Seules les réponses `submission_method = 'online'` sont éditables : une réponse de type téléchargement (Lot 9) n'a pas de champs de saisie à pré-remplir.
- `form_submissions.id` et `submission_uuid` ne changent jamais.
- `submitted_at`, `subject_type`/`subject_id`, `submission_method` ne sont pas réécrits par une édition — seuls `updated_at`/`updated_by` le sont.
- Pas d'historique des versions : une édition écrase la valeur précédente, comme le reste du CRUD GVV.

#### Fichiers et signature : conserver ou remplacer

Un champ fichier ou signature déjà soumis affiche sa valeur actuelle (nom de fichier, ou image pour une signature) au lieu d'un champ de saisie vide :

- **Fichier** : laisser le champ vide conserve le fichier existant ; fournir un nouveau fichier le remplace.
- **Signature** : le widget s'ouvre en mode lecture seule sur la signature existante, avec une action explicite "Modifier la signature" pour repasser aux trois onglets de saisie (dessin, upload, clavier) déjà décrits en section 9. Tant que cette action n'est pas déclenchée, la signature initiale est conservée telle quelle.

Dans les deux cas, le remplacement suit le même ordre d'opérations : le nouveau fichier est écrit et son enregistrement `form_submission_files` créé, puis — seulement une fois cette écriture confirmée — l'ancien enregistrement et son fichier disque sont supprimés. Jamais l'inverse, pour ne pas perdre le fichier initial si l'écriture du remplaçant échoue.

### 20. Lien de modification public à usage unique (EF16-bis)

#### Principe

Extension de la modification en place (section 19) : en plus du déclenchement admin authentifié, une réponse peut être rouverte par l'utilisateur d'origine via un lien portant un token dédié. Contrairement au token de corrélation `link_token` des sous-formulaires (section 17), ce token autorise une écriture, pas seulement une lecture/corrélation — il doit donc être à usage unique et expirer.

#### Schéma

```sql
ALTER TABLE form_submissions ADD COLUMN edit_token VARCHAR(64) NULL
    COMMENT 'Token d''autorisation de modification publique à usage unique, NULL = aucun lien actif';
ALTER TABLE form_submissions ADD COLUMN edit_token_expires_at DATETIME NULL
    COMMENT 'Expiration du token, 7 jours après génération';
-- index sur edit_token pour la résolution par lien
```

Un seul token actif par soumission : la colonne est simplement écrasée à chaque génération, pas de table d'historique.

#### Cycle de vie du token

1. **Génération** — bouton "Modifier le formulaire" dans `bs_submissions.php` (liste admin) : appelle `Form_submissions_model::generate_edit_token($submission_id)`, qui écrit un token aléatoire (UUID v4) et `edit_token_expires_at = NOW() + 7 jours`, remplaçant tout token précédent — sans distinction entre "déjà utilisé" et "jamais utilisé", cohérent avec le principe "un seul lien valide à la fois". Le lien est affiché à l'admin pour transmission manuelle (ouverture en direct ou copie vers un autre canal) ; GVV n'envoie aucun email.
2. **Consultation** — `forms_public::edit($slug, $token)` résout la soumission par `edit_token`, vérifie la non-expiration, et rend le formulaire pré-rempli en réutilisant le même moteur que `forms_admin::submission_edit()` (section 19) : valeurs, fichiers et signature existants prévisualisés. La simple consultation ne consomme pas le token.
3. **Consommation** — à la resoumission réussie, le token est invalidé dans la même opération que l'enregistrement des valeurs :

```sql
UPDATE form_submissions
   SET edit_token = NULL
 WHERE id = ? AND edit_token = ?
```

Si cette requête affecte 0 ligne (token déjà consommé ou remplacé par une génération plus récente entre-temps), la resoumission est refusée avec un message explicite — c'est le garde-fou contre la double soumission concurrente (deux onglets sur le même lien) sans verrou applicatif supplémentaire.

4. **Expiration** — un accès après `edit_token_expires_at` est traité comme un lien invalide, même si le token n'a jamais été consommé.

#### Lien invalide

Trois causes possibles (jamais consommé mais expiré, déjà consommé, remplacé par une génération plus récente) convergent vers le même état "lien invalide" côté utilisateur — pas de distinction utile à faire, dans tous les cas la réponse est de demander un nouveau lien à l'admin. Message dédié, jamais un formulaire vide ni une 404 générique.

#### Différences avec la modification admin (section 19)

- Point d'entrée public (`forms_public`) au lieu d'authentifié (`forms_admin`), mais moteur de rendu, validation et remplacement fichiers/signature strictement identiques (section 19, "Fichiers et signature : conserver ou remplacer").
- Mêmes garde-fous : seules les réponses `submission_method = 'online'` sont éligibles ; `id`/`submission_uuid`/`submitted_at`/`subject_type`/`subject_id`/`submission_method` ne sont jamais modifiés.
- Pas d'indicateur d'état de token affiché dans la liste admin : le bouton "Modifier le formulaire" régénère systématiquement, sans qu'il soit utile de savoir si un lien précédent était encore actif.

### 21. Complétude des pièces obligatoires (EF17)

#### Principe

Pour les pièces à fournir (champs `file`/`signature`), `is_required` cesse d'être bloquant à la soumission : une réponse incomplète est acceptée, mais son incomplétude reste visible et actionnable (via le lien de modification, section 20). Les autres types de champs conservent le comportement bloquant existant (`Forms_validation::validate_field_value()`, inchangé pour `text`/`email`/`date`/`number`/`textarea`/`select`/`radio`/`checkbox`).

Ce comportement est déterminé par le type de champ, pas par une propriété distincte configurable — pas de nouveau flag "bloquant/non-bloquant" à gérer par champ en plus de `is_required`.

#### Groupes d'alternatives

Certaines pièces sont interchangeables (ex. carte d'identité OU passeport) : exiger les deux serait incorrect, mais l'absence des deux doit compter comme une seule pièce manquante.

```sql
ALTER TABLE form_fields ADD COLUMN required_group VARCHAR(50) NULL
    COMMENT 'Regroupe des champs fichier alternatifs ; satisfait si au moins un membre du groupe est renseigné';
```

- Les champs `is_required = 1` partageant le même `required_group` (et le même `form_id`, la portée est toujours un seul formulaire) forment une seule exigence, satisfaite dès qu'un membre a une valeur.
- Un champ `is_required = 1` sans `required_group` reste une exigence individuelle, comme aujourd'hui.
- Configuré depuis l'admin d'édition des champs (`forms_admin`), au même endroit que `is_required`.

#### Calcul de la complétude

Calcul à la volée (pas de colonne dénormalisée), par jointure entre les exigences du formulaire (`form_fields` où `field_type IN ('file','signature')` et `is_required=1`, regroupées par `required_group`) et les valeurs soumises (`form_submission_files` pour la soumission courante). Choix cohérent avec le volume attendu (formulaires de club, pas de gros volumes concurrents) et évite tout risque de désynchronisation qu'un compteur mis en cache introduirait.

Une exigence (champ isolé ou groupe) est satisfaite si au moins un des champs concernés a un fichier associé dans `form_submission_files` pour cette soumission.

#### Affichage

- **Public** (formulaire de saisie et reprise via lien, section 20) : liste des pièces manquantes en bas du formulaire, toujours affichée (pas seulement en mode reprise). Un champ isolé manquant est cité par son libellé (`form_fields.label`) ; un groupe manquant est cité par l'ensemble des libellés de ses membres, avec la formulation "au moins un parmi : label A, label B...".
- **Admin** (`bs_submissions.php`) : indicateur de complétude par ligne (nombre de pièces manquantes, ou équivalent visuel), calculé avec les mêmes règles.

### 22. Modèle PDF vierge téléchargeable (EF18)

#### Principe

Complément à la soumission par téléchargement (section 15) : l'admin associe un PDF vierge (le formulaire imprimable) au formulaire, que l'utilisateur peut télécharger avant de le remplir à la main et de le renvoyer scanné (EF12). Un seul fichier par formulaire, pas de gestion de versions — un nouveau dépôt remplace simplement le précédent.

#### Activation

Pas de nouvelle colonne, pas de nouveau flag : la présence du lien de téléchargement sur la page publique dépend uniquement de deux conditions déjà représentées ailleurs — `forms.allow_upload_response` (EF12) et la présence effective du fichier sur disque. Le PDF reste facultatif même quand la soumission par téléchargement est activée : aucun message d'erreur, le lien est simplement absent tant qu'aucun fichier n'a été déposé.

#### Stockage — extension de `Forms_file_storage`

Le fichier suit exactement le même régime que les images d'un formulaire (voir [Design stockage fichier](formulaires_sync_fichiers_design.md)), avec un nom fixe plutôt qu'une liste :

```
uploads/formulaires/{code}/template.pdf
```

Stocké à la racine du répertoire du formulaire (pas dans `images/`) : `rename_form_dir()`, `copy_form_dir()` et `delete_form_dir()` de `Forms_file_storage` itèrent déjà les fichiers de premier niveau du répertoire sans distinction de type — un renommage de code, une duplication ou une suppression de formulaire déplace/copie/supprime `template.pdf` sans aucune modification de ces méthodes. Même raisonnement pour `form_backup()`, qui zippe déjà l'intégralité du répertoire : le PDF est inclus dans l'export d'un formulaire sans changement de code.

Un nom de fichier fixe (`template.pdf`, pas de suffixe/timestamp) élimine par construction le risque de fichier orphelin évoqué dans le PRD (EF18 #4) : `write_pdf_template()` écrase l'unique fichier possible, il n'y a jamais qu'un ancien et un nouveau, jamais d'historique à purger.

Nouvelles méthodes sur `Forms_file_storage`, calquées sur `write_image()`/`image_path()`/`read_image()`/`delete_image()` :

- `write_pdf_template($code, $content)`
- `pdf_template_path($code)`
- `read_pdf_template($code)`
- `has_pdf_template($code)`
- `delete_pdf_template($code)`

#### Import/export par formulaire — hors du remplacement de contenu par archive

Comme les images (voir « Ressources locales et partagées »), le PDF vierge n'est **jamais** touché par `form_import_zip()`/`form_restore()`/`Forms_file_storage::replace_all_from_dir()` : ces méthodes ne remplacent que `page*.html`/`style.css`/`meta.json`. Il se gère exclusivement par son propre endpoint d'upload/suppression admin, indépendant du dépôt d'archive de contenu — même séparation que pour les images.

`form_backup()` l'inclut malgré tout dans l'archive téléchargeable (elle zippe tout le répertoire), pour que l'export d'un formulaire reste un instantané complet et fidèle — cohérent avec l'inclusion déjà en place des images.

#### `meta.json`

Ajout d'un indicateur booléen, cohérent avec `allow_upload_response` déjà présent (voir [Métadonnées du formulaire](formulaires_sync_fichiers_design.md)) :

```json
{
  "allow_upload_response": true,
  "pdf_template": true
}
```

#### Admin

Nouvelle carte "Formulaire vierge (PDF)" sur `bs_form.php`, calquée sur la carte "Images" (section 1) mais à fichier unique : nom du fichier actuel + lien de téléchargement + bouton "Supprimer" si un PDF est présent, sinon formulaire de dépôt seul. Contrôleur `forms_admin::pdf_template_upload($form_id)`/`pdf_template_delete($form_id)`, même pattern que `image_upload()`/`image_delete()` (`$_FILES` brut, pas la lib d'upload CI) : vérification de type (`application/pdf`, via `finfo` + signature `%PDF-`) et de taille (limite à trancher, proposition 10 Mo — alignée sur la limite déjà en place pour le fichier de réponse scannée, EF12).

#### Page publique

Lien "Télécharger le formulaire vierge (PDF)" affiché en haut de la page 1 (avant les champs), visible dès que `allow_upload_response` est vrai et qu'un PDF est présent — emplacement choisi pour être visible avant même que l'utilisateur commence à remplir en ligne, puisque l'usage réel est d'imprimer le PDF puis de le remplir à la main avant de le renvoyer scanné. Libellé délibérément distinct du bouton existant "Télécharger un formulaire prérempli" (qui ouvre en réalité la modale d'*envoi* du scan rempli, EF12) pour ne pas laisser croire qu'il s'agit de la même action.

Nouvelle route `forms_public::pdf_template($code)`, sur le même principe que `image()`/`shared_image()` : vérification de confinement par `realpath()`, `Content-Type: application/pdf`, pas d'exécution possible (le répertoire reste protégé par le `.htaccess Require all denied` déjà en place).

#### Sécurité

Mêmes garanties que pour les images : le fichier n'est jamais servi de façon statique, toujours via la route applicative avec vérification de confinement ; le nom de fichier sur disque (`template.pdf`) ne dérive jamais d'une entrée utilisateur.

## Décisions actées (juillet 2026) — remplacement du briefing passager

**Statut : tranché pour la migration `briefing_passager` → `forms`. Remplace la discussion ouverte précédente sur ce sujet.**

Point de départ : l'expérimentation d'un second bouton de signature sur `briefing_passager/upload` (derrière le flag `testing_form`) qui redirige vers `/forms/briefing-passager-ulm` a fait remonter plusieurs questions non résolues par la V0 de la section 13. Discussion tranchée comme suit.

### Rattachement à l'entité GVV

**Décision** : couple générique `subject_type`/`subject_id` sur `form_submissions` (section 13), pas de colonne métier dédiée. Renversement de la position précédente (« `archived_documents.vld_id` reste dédié, pas de généralisation tant qu'un seul cas d'usage existe ») : le module `forms` doit rester intégrable dans d'autres workflows futurs sans jamais avoir à ajouter de colonne spécifique à chacun. `briefing_passager_ulm` est le premier cas d'usage de ce socle générique, pas un cas particulier qui le justifierait a posteriori.

### Faut-il archiver dans `archived_documents` à chaque soumission ?

**Décision : non, pas pour cette migration.** L'archivage automatique (flag `generate_archived_document` + `document_type_id`, esquissé ci-dessous) reste une extension future possible du module `forms`, mais elle est retirée du chemin critique de la migration du briefing passager :
- la détection « une réponse existe pour ce sujet » ne dépend plus d'`archived_documents` (voir `subject_type`/`subject_id` ci-dessus) ;
- `briefing_passager/admin_list` et `export_pdf`, qui listent aujourd'hui les briefings via `archived_documents_model->get_briefings_recent()`, seront adaptés pour lire `form_submissions` directement lors de la bascule — pas de dépendance à un archivage automatique pour rester fonctionnels.

Si le besoin d'archivage réapparaît plus tard (pour ce formulaire ou un autre), l'esquisse reste valable et **doit rester générique** — pas de logique spécifique au briefing passager dans le module `forms` :

```sql
ALTER TABLE forms ADD COLUMN generate_archived_document TINYINT(1) DEFAULT 0
    COMMENT 'Si vrai, chaque soumission génère automatiquement un archived_documents';
ALTER TABLE forms ADD COLUMN document_type_id INT NULL
    COMMENT 'FK document_types, type utilisé pour l''archivage automatique';
```

### Handler synchrone vs callback URL

**Décision : handler synchrone** (section 13), conservé mais recentré sur le seul effet de bord retenu pour ce cas d'usage : la mise à jour de `vols_decouverte` depuis les valeurs soumises (ex. `date_vol`). Le handler s'exécute dans la requête de soumission elle-même — il n'y a pas de callback URL séparé à construire, et pas de dépendance à la survie d'un navigateur après la soumission.

### Protection du lien public (transfert vers le passager)

**Hors périmètre de cette migration (juillet 2026).** L'utilité même du transfert du lien de briefing par QR code/SMS vers l'appareil du passager (mécanisme actuel `briefing_passager/generate_link` + `briefing_tokens`) est remise en question — le besoin réel n'est pas confirmé. `briefing_tokens` n'est pas touché par cette migration et continue, si nécessaire, de protéger l'ancien flux `briefing_sign` le temps qu'il existe encore.

**Si le besoin est confirmé plus tard**, la protection de lien devra être une **fonctionnalité générique du module `forms`** (ex. `forms.is_transferable` ou équivalent, avec un mécanisme de token générique commun à tous les formulaires marqués transférables) — pas un mécanisme propre au briefing passager. Non conçu ni chiffré ici, faute de besoin confirmé.

### Exclusif vs cumulatif (ancien mécanisme upload vs nouveau formulaire)

Sans objet pour la migration : le nouveau mécanisme ne produit plus d'`archived_documents`, donc pas de concurrence à arbitrer entre bouton d'upload et lien de formulaire sur le même document. Le remplacement se fait par bascule nette de la détection (voir plan, Lot 6 étape 6.6), pas par cumul des deux sources.

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
- Réutilisation du flux existant de création de document archivé (pas de service `archive_submission` dédié en V1 simplifiée).

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
- Journalisation dans les fichiers de logs
- `link_token` de sous-formulaire (section 17) : même limite déjà acceptée que les liens publics non protégés (section 13) — pas de protection contre le devinage/rejeu à ce stade
- `edit_token` de modification publique (section 20) : traité différemment car il autorise une écriture — usage unique, expiration 7 jours, consommation atomique, régénération qui invalide tout lien précédent

## Intégration workflows GVV

- Un workflow peut pointer vers un `public_slug`
- Les paramètres runtime du workflow alimentent les params pré-remplissage
- Une étape workflow peut déclencher l'archivage d'une réponse

## Documentation

Guide utilisateur/admin complet, en deux documents : [doc/users/fr/13_formulaires.md](../users/fr/13_formulaires.md) — vue d'ensemble, création du conteneur, page de génération, consultation des réponses et fichiers, soumission par téléchargement ; et [doc/users/fr/13_formulaires_creation.md](../users/fr/13_formulaires_creation.md) — types de champs, règles CSS, exemples de formulaires et de CSS global, rôles de champs GVV, pré-remplissage (mécanismes A et B).

Taxonomie, architecture, handlers post-soumission : ce document (sections « Taxonomie des formulaires », « 13. Intégration workflow GVV »).
