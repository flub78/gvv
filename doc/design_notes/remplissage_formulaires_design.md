# Design Notes — Remplissage Formulaires

Date : 30 mai 2026

## Contexte

Le module passe d'une logique centrée « template PDF » à une logique « formulaires HTML natifs » avec lien public anonyme, pré-remplissage GVV et archivage documentaire.

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
  Lien pré-rempli + token → forms_public → form_submissions + handler
  Handler → archivage PDF + mise à jour entité GVV + invalidation token
  Exemples : briefing_passager_ulm
```

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
  &{context_key}={valeur}    ← paramètre de contexte GVV (token, vld_id, etc.)
```

`forms_public` sépare les paramètres en trois catégories :
- **Contexte** : noms réservés (`token`, `vld_id`, `lock`, `page`, `pilot_login`, `instructor_login`) → stockés dans `context_params` JSON de la soumission, jamais injectés dans les champs
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

### 13. Intégration workflow GVV — handler post-soumission

#### Principe

Pour les formulaires de catégorie 3, `forms_public/submit` appelle un handler après avoir créé la soumission. Le handler exécute les actions GVV métier.

#### Évolutions de schéma

```sql
-- Déclarer un handler sur un formulaire
ALTER TABLE forms ADD COLUMN handler_class VARCHAR(100) NULL
    COMMENT 'Classe PHP du handler post-soumission, NULL = aucun';

-- Stocker le contexte GVV avec la soumission
ALTER TABLE form_submissions ADD COLUMN context_params TEXT NULL
    COMMENT 'JSON : token, vld_id, etc. — paramètres de contexte non liés aux champs';
```

#### Interface des handlers

```php
// application/libraries/form_handlers/GvvFormHandlerInterface.php
interface GvvFormHandlerInterface {
    // Appelé après création de la soumission.
    // Retourne : ['redirect_url' => string|null, 'error' => string|null]
    public function after_submit(int $submission_id, array $context_params): array;
}
```

Les handlers sont placés dans `application/libraries/form_handlers/`. `forms_public` instancie la classe déclarée dans `forms.handler_class` si elle implémente l'interface.

#### Handler de référence : BriefingPassagerUlmHandler

```
BriefingPassagerUlmHandler::after_submit($submission_id, $context)
  ├── Valide le token ($context['token']) → non expiré, non utilisé
  ├── Récupère le VLD ($context['vld_id'])
  ├── Génère le PDF (réutilise la logique submission_pdf)
  ├── Archive dans archived_documents (lié au vld_id)
  ├── Met à jour vols_decouverte (beneficiaire, participation, urgence)
  ├── Marque briefing_tokens.used_at
  └── Retourne redirect_url → page de confirmation briefing
```

#### Construction de l'URL par briefing_passager/generate_link

```
/forms/fiche-passager
  ?token=<64hex>                    ← contexte (→ context_params)
  &vld_id=<id>                      ← contexte (→ context_params)
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
