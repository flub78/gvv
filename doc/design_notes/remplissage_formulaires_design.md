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

#### Table `dashboard_shortcuts`

| Colonne | Type | Contrainte |
|---|---|---|
| `id` | INT AUTO_INCREMENT | PRIMARY KEY |
| `dashboard` | VARCHAR(50) NOT NULL | Identifiant du dashboard cible (`accueil`, `pilote`, `instructeur`, `formations`, …) |
| `section` | VARCHAR(50) NULL | Section cible dans le dashboard, NULL = non catégorisé |
| `title_key` | VARCHAR(100) NULL | Clé de langue GVV (optionnelle) |
| `title` | VARCHAR(100) NOT NULL | Texte affiché si `title_key` absent ou clé non trouvée |
| `description_key` | VARCHAR(255) NULL | Clé de langue GVV (optionnelle) |
| `description` | TEXT NULL | Texte affiché si clé absente ou non trouvée |
| `url` | VARCHAR(255) NOT NULL | URL relative (interne GVV) ou absolue (externe) |
| `icon` | VARCHAR(50) NULL | Nom Bootstrap Icons, ex. `bi-file-earmark-check` |
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
- Accès réservé au rôle club-admin.
- Accessible depuis une carte "Raccourcis dashboard" sur `forms_admin/index`.

#### Intégration dans les dashboards

Chaque dashboard instrumenté charge ses raccourcis via un appel unique au modèle, puis inclut un partial view commun :

```php
// Contrôleur dashboard
$data['shortcuts'] = $this->shortcuts_model
    ->get_for_dashboard('pilote', $user_role, $club_id);
// Vue dashboard
$this->load->view('common/_shortcuts', $data);
```

Le partial `common/_shortcuts.php` parcourt les raccourcis par section et rend chaque carte Bootstrap.

**Dashboards à instrumenter** : `accueil`, `pilote`, `instructeur`, `formations`. Tout contrôleur de dashboard peut être instrumenté en ajoutant l'appel modèle et en incluant le partial dans sa vue.

#### Impact sur les tests Playwright

Les tests d'accessibilité qui parcourent toutes les URLs visibles doivent être adaptés : les raccourcis dynamiques pointent vers des URLs de contexte (formulaires pré-remplis, pages admin spécifiques) qui peuvent ne pas être accessibles sans les bons paramètres. Deux options : exclure les cartes dynamiques du parcours automatique, ou ajouter un test dédié avec les paramètres d'authentification appropriés.

### 10. Import PDF -> HTML

- Pas de service de conversion, demander à Claude ou ChatGPT de réaliser la conversion
- Détection des champs du PDF source quand possible
- Génération d'une page HTML initiale

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

Le commentaire saisi dans la modale de téléchargement sert donc directement de colonne "Identification" dans la liste des réponses.

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
