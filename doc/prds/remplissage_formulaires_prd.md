# PRD — Remplissage Formulaires

Date : 30 mai 2026

## Contexte

Le besoin cible est un module de formulaires natifs HTML, inspiré de Google Forms, mais intégré à GVV et à son système documentaire.

Le module doit permettre :
- la création et l'administration de formulaires par les admins ;
- le remplissage public via lien non authentifié ;
- l'exploitation des données GVV pour pré-remplir certains champs ;
- l'archivage des formulaires remplis dans les documents archivés.

## Stratégie de livraison

La première livraison doit prioriser un socle de formulaires HTML de type Google Forms, avec support des fichiers et sans pré-remplissage GVV. Le pré-remplissage GVV et l'intégration workflow avancée sont prévus dans un second temps, une fois le socle autonome stabilisé.

### Note d'orientation (évolution probable)

Le module formulaires est considéré comme le socle de collecte et de reprise d'état. Une orchestration légère (états de validation documentaire + décision globale) pourra être ajoutée au-dessus de ce socle pour couvrir les besoins de type "procédure" sans introduire immédiatement un moteur workflow complexe.

Une extension future probable du module consiste à permettre la sauvegarde et la reprise de saisie multi-session pour les utilisateurs externes (brouillon, lien/token de reprise, reprise sur la dernière étape valide).

Une autre extension future probable consiste à gérer des pages/sections conditionnelles selon les réponses déjà fournies (règles de visibilité et navigation conditionnelle).

## Objectifs

- Fournir un moteur de formulaires HTML multi-pages administrable dans GVV.
- Permettre l'accès public via liens de réponse partageables.
- Gérer les réponses, les fichiers soumis et leur prévisualisation admin.
- Permettre l'import/export de pages formulaire au format texte/HTML.
- Supporter un CSS global de formulaire et documenter des exemples.
- Permettre l'import d'un document PDF vers une base HTML éditable.
- Permettre la génération d'un PDF imprimable à partir d'une réponse.
- Intégrer un mécanisme de champs dynamiques pré-remplis depuis GVV.
- Permettre depuis une réponse la création d'un document archivé avec le PDF imprimable pré-rempli.
- Permettre à un formulaire de déclencher un paiement en ligne (HelloAsso) rattaché à un compte comptable GVV.
- Permettre à un formulaire d'inclure un lien vers un autre formulaire GVV (sous-formulaire), avec injection de la réponse dans le formulaire maître.
- Permettre, depuis une réponse, d'ouvrir un formulaire de création GVV standard (ex. création de membre) pré-rempli avec ses valeurs.
- Permettre à un admin de modifier en place une réponse déjà soumise, pour utiliser les formulaires comme support de gestion de procédure.
- Permettre à l'utilisateur d'origine de compléter ou corriger sa réponse après soumission, via un lien de modification public à usage unique.
- Signaler explicitement les pièces obligatoires manquantes, sans bloquer la soumission, pour permettre un remplissage en plusieurs fois.

## Non-objectifs

- Remplacer l'ensemble du module workflow GVV en V1.
- Concevoir un éditeur visuel WYSIWYG complet type "no-code" avancé en V1.
- Ajouter la signature électronique qualifiée (eIDAS) en V1.
- Signature PGP (OpenPGP.js) en V1 — option avancée réservée aux extensions ultérieures (complexité, dépendances JS, valeur légale incertaine).

## Portée

### Inclus

- CRUD admin des formulaires (créer, modifier, supprimer, activer/désactiver).
- Formulaires composés d'une ou plusieurs pages HTML.
- Édition exclusivement par dépôt d'archive (création et remplacement de contenu) — voir EF2-quater.
- Lien public de soumission, sans authentification GVV.
- Types de champs : texte, email, date, numérique, textarea, select, radio, checkbox, fichier.
- Prévisualisation admin des fichiers image/PDF soumis.
- Insertion de documents archivés GVV dans le formulaire avec visualisation intégrée (scroll si nécessaire).
- Liste admin des réponses + détail d'une réponse.
- Champ signature : widget composite (dessin canvas + upload image) avec stockage dans `form_submission_files`.
- Pré-remplissage d'une signature depuis le profil GVV (`membres.signature_path`, sources `member.signature` / `instructor.signature`).
- Génération d'un PDF imprimable de la réponse.

- Soumission par téléchargement d'un scan/photo du formulaire imprimé, en alternative au remplissage en ligne, activable par formulaire (EF12).
- Paiement en ligne HelloAsso intégré à un formulaire, obligatoire ou facultatif selon configuration (EF13).
- Sous-formulaires : widget de lien vers un autre formulaire, ouvert dans un nouvel onglet, avec injection de la réponse dans le formulaire maître (EF14).
- Bouton d'export d'une réponse, configurable par formulaire (URL cible + libellé), ouvrant un formulaire de création GVV pré-rempli avec les valeurs de la réponse (EF15).
- Bouton de modification d'une réponse déjà soumise, depuis la liste admin des réponses, rechargeant le formulaire pré-rempli et permettant une resoumission qui met à jour la réponse en place (EF16).
- Lien de modification public à usage unique, généré à la demande depuis la liste admin des réponses, permettant à l'utilisateur d'origine de compléter ou corriger sa réponse (EF16-bis).
- Comportement non bloquant des pièces obligatoires de type fichier/signature à la soumission, avec liste explicite des pièces manquantes et indicateur de complétude en admin (EF17).

### Exclu

- OCR avancé sur PDF scannés non structurés en V1.
- Sauvegarde/reprise multi-session du remplissage public en V1 (prévue en extension ultérieure).
- Pages/sections conditionnelles basées sur les réponses en V1 (prévu en extension ultérieure).
- Plusieurs paiements sur un même formulaire, ou choix entre plusieurs moyens de paiement, en V1 (EF13) — un seul widget de paiement HelloAsso par formulaire.
- Sous-formulaires récursifs (un sous-formulaire contenant lui-même un widget sous-formulaire) en V1 (EF14) — un seul niveau d'imbrication.
- Sous-formulaires répétables (N réponses liées, ex. liste de passagers) en V1 (EF14) — une seule réponse liée par widget.
- Édition en place d'une réponse de sous-formulaire déjà soumise en V1 (EF14) — resoumission complète uniquement.
- Mapping configurable entre les noms de champs du formulaire source et ceux du formulaire cible en V1 (EF15) — les noms doivent correspondre exactement.
- Export des champs fichier, signature et à choix multiples (checkbox) vers le formulaire cible en V1 (EF15).
- Modification d'une réponse de type téléchargement (`submission_method = 'upload'`, EF12) en V1 (EF16) — cette catégorie de réponse n'a pas de champs de saisie à compléter.
- Historique des versions successives d'une réponse modifiée en V1 (EF16) — seule la dernière version est conservée, sans piste d'audit détaillée par champ.
- Protection contre la modification concurrente de la même réponse en V1 (EF16) — dernier enregistrement gagnant, comme le reste de GVV.
- Envoi automatique par email du lien de modification public (EF16-bis) — la transmission à l'utilisateur reste manuelle, à la charge de l'admin.
- Distinction visuelle de l'état d'un lien de modification (actif/consommé/expiré) dans la liste admin (EF16-bis) — l'action de génération est toujours valide et régénère systématiquement.
- Expiration ou usage unique configurable par formulaire (EF16-bis) — durée fixe de 7 jours et usage unique pour tous les formulaires.
- Flag "bloquant/non bloquant" configurable par champ (EF17) — le comportement non bloquant est déterminé uniquement par le type de champ (fichier/signature), pas configurable individuellement.

## Taxonomie des formulaires

Les formulaires se répartissent en trois catégories selon leur degré d'intégration avec GVV :

| Catégorie | Description | Pré-remplissage | Post-soumission | Exemple |
|---|---|---|---|---|
| **1 — Autonome** | Formulaire public sans contexte GVV | Aucun | Stockage `form_submissions` uniquement | `inscription_club` |
| **2 — Contextuel GVV** | Formulaire pré-rempli depuis les données GVV | `data-gvv-source` ou params URL | Stockage `form_submissions` uniquement, PDF manuel | `attestation_de_formation_ulm` |
| **3 — Intégré workflow** | Formulaire rattaché à une entité GVV, déclenche optionnellement une action à la soumission | Params URL (valeurs VLD) | Rattachement générique (`subject_type`/`subject_id`) + handler optionnel (mise à jour entité) | `briefing_passager_ulm` |

Cette taxonomie guide les décisions d'architecture : les formulaires de catégorie 1 ne sont jamais affectés par les évolutions d'intégration GVV.

## Personae & rôles

- **Administrateur** : gère les formulaires, styles, liens, réponses, export PDF, archivage.
- **Utilisateur public** : remplit un formulaire via lien sans compte GVV.
- **Pilote/Membre** : entité cible potentielle d'archivage d'une réponse.
- **Workflow GVV** : consommateur de liens/formulaires et des réponses collectées.

## Parcours clés

### Parcours 1 : Création d'un formulaire (Admin)

1. L'admin crée un formulaire et renseigne titre/description.
2. Il ajoute une ou plusieurs pages HTML (édition en ligne ou import texte).
3. Il configure les champs (types, validations, obligatoire).
4. Il publie le formulaire et récupère son lien public.

### Parcours 2 : Réponse publique

1. Un utilisateur ouvre le lien public.
2. Il saisit les données et charge des fichiers si nécessaire.
3. Il valide le formulaire et obtient une confirmation explicite.

### Parcours 3 : Exploitation admin

1. L'admin consulte la liste des réponses d'un formulaire.
2. Il ouvre une réponse, visualise les pièces jointes (image/PDF) et les documents référencés.
3. Il génère le PDF imprimable de la réponse.
4. Il utilise le bouton de la réponse pour ouvrir la création de document archivé avec le PDF imprimable pré-rempli à la place du sélecteur de fichier.

### Parcours 4 : Modification d'une réponse déjà soumise (Admin)

1. Depuis la liste des réponses d'un formulaire, l'admin clique sur "Modifier" pour une réponse en ligne.
2. Le formulaire multi-pages se recharge avec les valeurs déjà soumises pré-remplies.
3. L'admin complète ou corrige des champs, conserve ou redéfinit la signature, conserve ou remplace des fichiers.
4. Il valide : la réponse existante est mise à jour, sans création d'une nouvelle réponse.

### Parcours 5 : Reprise d'une réponse incomplète via lien de modification (utilisateur public)

1. Un utilisateur soumet un formulaire sans fournir toutes les pièces obligatoires ; sa réponse est acceptée mais apparaît "incomplète" dans la liste admin.
2. Depuis la liste des réponses, l'admin clique sur "Modifier le formulaire" : un lien de modification à usage unique est généré et affiché.
3. L'admin transmet ce lien à l'utilisateur (ouverture en direct devant lui, ou envoi par un canal externe).
4. L'utilisateur ouvre le lien : le formulaire se recharge avec les valeurs déjà soumises, les fichiers et la signature prévisualisés, et la liste des pièces encore manquantes affichée.
5. Il complète les pièces manquantes et valide : la réponse existante est mise à jour, le lien devient inutilisable.

## Exigences fonctionnelles

### EF1 : Gestion des formulaires

1. CRUD complet des formulaires en interface admin.
2. Chaque formulaire possède un identifiant stable, un statut, et un lien public.
3. Suppression logique recommandée (désactivation) pour préserver l'historique.
4. Un formulaire peut être rattaché à une section ou être global (sans section).

### EF1-bis : Visibilité des formulaires par section active

1. Sans section active, la liste admin affiche tous les formulaires.
2. Dans ce mode global, la liste affiche explicitement la section de rattachement de chaque formulaire (ou "Global" si non rattaché).
3. Avec une section active, la liste admin affiche :
	- les formulaires rattachés à la section active ;
	- les formulaires globaux (sans section).
4. Les formulaires rattachés à une autre section ne sont pas affichés quand une section active est sélectionnée.

### EF2 : Structure des pages

1. Un formulaire contient 1..N pages HTML.
2. Le contenu d'une page se dépose par fichier ou archive — pas de saisie en ligne du HTML (superseded par EF2-quater).
3. Chaque page peut être exportée en fichier HTML.

### EF2-bis : Stockage fichier du contenu (HTML/CSS)

1. Le contenu HTML de chaque page et le CSS global d'un formulaire sont stockés sous forme de fichiers, qui constituent la source de vérité du contenu — ce stockage fichier remplace le stockage exclusif en base utilisé jusqu'ici.
2. Le formulaire reste identifié et administré via un enregistrement en base (statut, section, slug, titre, options de soumission) : seul le contenu HTML/CSS quitte la base.
3. Le format de stockage permet l'ouverture directe d'un formulaire dans un navigateur standard, sans serveur applicatif, pour prévisualisation, avec un rendu fidèle à la mise en page réelle.
4. Les widgets dynamiques (signature, sous-formulaire, paiement en ligne, etc.) sont représentés dans le fichier statique par un visuel de substitution reconnaissable (image/icône dédiée par type de widget), remplacé par le composant fonctionnel réel au moment du rendu serveur.
5. L'ajout, la mise à jour ou le remplacement du contenu d'un formulaire (HTML, CSS, images associées) reste possible entièrement depuis l'interface d'administration web, sans nécessiter d'accès au système de fichiers serveur.
6. Un formulaire complet (HTML + CSS + images) peut être exporté et importé comme un seul artefact téléchargeable, qui reflète fidèlement le contenu du répertoire de stockage — pas de format d'archive distinct à maintenir en parallèle.
7. La structure des champs d'un formulaire (liste, type, obligatoire, rôle) n'est plus persistée en base : elle est déterminée à la lecture du contenu HTML, à la demande (affichage admin, validation de soumission, mapping des notifications).

Voir : [Design synchronisation fichiers](../design_notes/formulaires_sync_fichiers_design.md)

### EF2-ter : Migration des formulaires existants

1. Une procédure permet de convertir vers le nouveau stockage fichier les formulaires actuellement stockés uniquement en base (`content_html`/`global_css`), sans perte de contenu ni interruption du service public des formulaires déjà publiés.
2. Cette procédure reste en place indéfiniment une fois tous les formulaires existants migrés et vérifiés : elle devient alors un no-op sans coût (rien à convertir), plutôt que d'être retirée du projet. Elle ne doit pas être supprimée du fait de sa dépendance à l'ordre des migrations : chaque installation cliente peut se trouver à un niveau de migration différent, et une suppression casserait la mise à niveau de toute installation n'ayant pas encore atteint cette étape.

### EF2-quater : Formulaire = répertoire autonome et CSS partagé

1. Le répertoire de stockage d'un formulaire (`uploads/formulaires/{code}/`) est autosuffisant : pages HTML, CSS et images qu'il contient, plus un fichier de métadonnées, suffisent à reconstituer le formulaire sans dépendre de la base.
2. Ce fichier de métadonnées est écrit à chaque modification du formulaire depuis l'admin (pas seulement au moment d'un export) : le répertoire reste à tout moment auto-descriptif, pas seulement au moment d'un instantané de sauvegarde.
3. La base conserve son rôle d'index (identifiant stable référencé par les réponses, listage/filtrage rapide dans l'admin) mais n'est plus la référence pour les champs de contenu/configuration couverts par le fichier de métadonnées — seuls le statut, le lien public et le rattachement à une section restent pilotés depuis l'admin et ne sont jamais modifiés par un dépôt d'archive, pour ne pas dépublier ou déplacer un formulaire déjà partagé par ce biais.
4. Le dépôt d'une archive est le seul mode d'édition du contenu (HTML, CSS, métadonnées, images) : il n'y a plus de zone de saisie libre pour le HTML ou le CSS dans l'interface admin. Créer un formulaire dépose une nouvelle archive ; modifier son contenu dépose une archive de remplacement sur le formulaire existant, sans toucher au statut, au lien public ni à la section.
5. Le CSS d'un formulaire peut référencer un CSS partagé entre plusieurs formulaires (charte graphique commune, styles réutilisés d'un concours à l'autre, etc.) sans dupliquer son contenu ni le recopier dans chaque formulaire ; de même pour des images partagées (logo de club commun à plusieurs formulaires).
6. Le fichier stocké (page HTML, `style.css`) ne référence jamais directement une route applicative GVV pour ses images (propres au formulaire ou partagées) : uniquement des chemins relatifs (`images/{fichier}`, `.commun/style.css`, `.commun/images/{fichier}`), réécrits par GVV au moment du rendu. C'est ce qui garde le répertoire d'un formulaire ouvrable et modifiable directement (`file://`, éditeur de texte) avant tout dépôt dans GVV, et déplaçable d'une installation GVV à l'autre sans rien à corriger dans son contenu — voir [Design stockage fichier](../design_notes/formulaires_sync_fichiers_design.md#ressources-locales-et-partagées--convention-de-référence-et-réécriture).

### EF3 : Champs et validations

1. Support des champs : text, email, date, number, textarea, select, radio, checkbox, file, signature.
2. Validation serveur obligatoire, avec messages explicites.
3. Gestion des champs obligatoires et formats (email, bornes numériques, etc.).

### EF4 : Fichiers et documents

1. Upload de fichiers sur réponse (avec contrôles type/taille).
2. Prévisualisation admin des images et PDF.
3. Possibilité de référencer un document du système documentaire dans un formulaire.
4. Les documents référencés sont visualisés inline dans une zone scrollable.

### EF5 : Liens publics et sécurité

1. Réponse possible sans authentification GVV.
2. Les liens peuvent être intégrés dans des workflows GVV.
3. Option de lien tokenisé/expirable selon configuration.
4. Protection CSRF, anti-spam/rate-limit et audit des soumissions.

### EF5-bis : Paramètres de configuration formulaires

1. Un écran admin dédié permet de gérer des paramètres clé/valeur utilisables dans les formulaires.
2. Chaque paramètre possède une clé technique, une valeur, un libellé lisible et une description optionnelle.
3. La portée d'un paramètre est soit globale (sans section), soit restreinte à une section.
4. Lors de la résolution, un paramètre de section est prioritaire sur le paramètre global de même clé.
5. L'écran de configuration est accessible depuis la page d'index de l'administration des formulaires via une carte dédiée.
6. Le premier paramètre à configurer est l'identification de l'organisme de formation (`organisme_formation`).
7. Dans les formulaires, ces paramètres sont référencés via la source `config.cle_parametre`.

### EF6 : Données GVV et pré-remplissage

Deux mécanismes coexistent selon la nature du contexte :

**Mécanisme A — `data-gvv-source` (contexte membre/instructeur)** : les champs pré-remplis depuis la table `membres` ou `events` utilisent des attributs HTML déclaratifs. Les paramètres d'identification (`pilot_login`, `instructor_login`) sont transmis dans l'URL.

**Mécanisme B — paramètres URL directs (contexte entité GVV)** : pour les formulaires dont le contexte provient d'une entité GVV autre qu'un membre (ex. vol de découverte), les valeurs de pré-remplissage sont passées directement en paramètres URL par le contrôleur appelant. Le formulaire n'embarque aucun attribut `data-gvv-source` pour ces champs. Un mécanisme `lock[]` permet au contrôleur de verrouiller côté serveur les champs dont GVV est autoritaire.

1. Les champs pré-remplis via mécanisme A sont déclarés dans le HTML via des attributs `data-gvv-*` directement sur les éléments de saisie.
2. Trois attributs : `data-gvv-source` (source de donnée), `data-gvv-param` (paramètre URL d'identification), `data-gvv-lock` (verrouillage serveur).
3. Les paramètres d'identification (`pilot_login`, `instructor_login`) sont transmis dans l'URL du formulaire.
4. Les sources autorisées couvrent deux tables GVV distinctes, avec une syntaxe explicite :
   - `member.*` et `instructor.*` → données de la table `membres` (identité, coordonnées, dates) ;
   - `member.event.*` et `instructor.event.*` → données de la table `events` (qualifications, brevets, licences, visites médicales).
   - `config.*` → paramètres de configuration formulaires ;
   - `club.*` → données du club depuis la configuration GVV ;
   - `user.*` → membre de la session courante ;
   - `date.*` → dates calculées.
5. Le verrouillage est appliqué côté serveur : pour `data-gvv-lock="true"`, GVV ignore la valeur soumise et impose la valeur résolue.
6. Une liste blanche stricte des sources autorisées est définie — pas d'accès libre à la base.
7. Le paramètre d'identification transmis en URL est validé (existence + appartenance à la section active).
8. Cette exigence est hors du périmètre de la première livraison et intervient après le socle autonome de formulaires.
9. La taxonomie des sources inclut `member.signature` → `membres.signature_path` et `instructor.signature` → `membres.signature_path`, ainsi que `instructor.event.{type}.signature` → `events.signature_path` pour la signature stockée dans un événement de qualification.
10. Pour tout champ pré-rempli GVV, le champ de saisie du formulaire est remplacé par la valeur pré-remplie affichée en lecture seule ; l'utilisateur ne peut pas la remplacer.

Voir : [Design pré-remplissage](../design_notes/remplissage_formulaires_design.md#6-pré-remplissage-gvv)

### EF6-ter : Page de génération pour formulaires à contexte GVV

Les formulaires qui exploitent des données GVV (données membre, données instructeur, événements/qualifications) sont toujours générés dans un contexte GVV authentifié. Ils ne s'ouvrent pas via un lien public brut.

1. Chaque formulaire GVV-contextuel dispose d'une **page de génération** accessible depuis l'interface admin GVV.
2. Cette page présente les sélecteurs nécessaires selon les paramètres attendus par le formulaire : sélecteur de membre (`pilot_login`) et/ou sélecteur d'instructeur (`instructor_login`).
3. Un bouton de confirmation construit l'URL pré-remplie et ouvre le formulaire avec tous les champs GVV résolus côté serveur.
4. Exemple pour une attestation de formation :

```
Page : Générer une attestation de formation

  Instructeur : [sélecteur membres avec rôle instructeur ▼]
  Candidat    : [sélecteur membres ▼]

  [Remplir l'attestation]
```

5. Le formulaire s'ouvre avec tous les champs issus de `membres` et de `events` déjà pré-remplis et verrouillés.
6. La page de génération est accessible depuis la liste des formulaires admin ou depuis une fiche de formation existante.

### EF6-quater : Gestion des types d'événements et données events

1. Les qualifications, brevets et informations instructeur non présents dans `membres` sont stockés dans la table `events` (champ `ecomment` pour le numéro, `date_expiration` pour la validité).
2. La table `events_types` doit être accessible depuis le dashboard pour consultation et administration.
3. Le formulaire membre doit permettre d'ajouter et modifier des événements de tous les types pertinents, y compris les qualifications instructeur.
4. Des types d'événements ULM (FI ULM, FE ULM) doivent être créés dans `events_types` pour couvrir les qualifications ULM instructeur.
5. La table `events` doit être enrichie d'une colonne `signature_path VARCHAR(255) NULL` pour permettre le stockage d'une signature associée à un événement de qualification (ex. signature d'un instructeur pour son ITP ou son FI Sailplane).

### EF6-bis : Champ signature

1. Un champ signature est déclaré dans le HTML via `<div data-gvv-type="signature" data-gvv-name="..." data-gvv-param="..." data-gvv-lock="...">`. GVV remplace ce div par le widget lors du rendu public ; le texte du div reste visible en prévisualisation standalone.
2. Le widget expose trois onglets : dessin canvas (écran tactile ou souris), upload image, saisie au clavier (fonte d'écriture manuscrite).
3. En mode canvas : la signature est normalisée (600×200 px), exportée en PNG base64, transmise via un champ caché, décodée côté serveur et stockée dans `form_submission_files` (`mime_type = image/png`).
4. En mode upload : `<input type="file" accept="image/*">` dans le widget, pipeline standard `form_submission_files`.
5. En mode clavier : le texte saisi est rendu en temps réel sur un canvas avec une fonte d'écriture manuscrite (Caveat) ; à la soumission, le canvas est exporté en PNG base64 et suit le même pipeline que le mode canvas.
6. Deux valeurs cachées sont transmises à chaque soumission : le contenu et le type (`canvas|file|text`), pour audit côté serveur.
7. La visualisation d'une signature soumise dans l'interface admin est graphique : l'image est affichée en ligne dans le détail de la soumission.
8. Le champ signature peut être pré-rempli depuis `membres.signature_path` (voir EF6, sources `member.signature` / `instructor.signature`).
9. Si la signature est pré-remplie depuis GVV, elle est affichée en lecture seule et l'utilisateur ne peut pas la remplacer.

Voir : [Design signatures](../design_notes/remplissage_formulaires_design.md#6-signatures)

### EF7 : Réponses et supervision

1. Liste admin des réponses par formulaire (filtre date/statut).
2. Consultation du détail d'une réponse et de ses fichiers.
3. Export des réponses (CSV/JSON) en option.

### EF8 : PDF imprimable et import PDF

1. À partir d'une réponse, génération d'un PDF imprimable.
2. Import d'un document PDF pour initialiser une version HTML éditable.

### EF9 : Archivage

1. Depuis le détail d'une réponse, un bouton permet d'ouvrir le formulaire existant de création de document archivé.
2. Le PDF imprimable de la réponse est pré-rempli dans ce formulaire à la place du sélecteur de fichier.
3. L'association au pilote reste gérée par le formulaire documentaire existant.
4. Journalisation dans les fichiers de logs.

### EF10 : Intégration workflow GVV — rattachement à une entité et handler post-soumission

Pour les formulaires de catégorie 3 (intégrés dans un workflow GVV), deux besoins distincts :

**A. Rattachement générique à une entité GVV** — savoir, pour une entité GVV donnée (ex. un vol de découverte), si une réponse a déjà été soumise, et faire disparaître cet état si la réponse est supprimée. Ce besoin est couvert nativement par le module `forms`, sans dépendre du système documentaire (`archived_documents`) ni d'un handler.

1. Une soumission peut être rattachée à une entité GVV via une référence générique (type + identifiant), transmise dans l'URL d'ouverture du formulaire et stockée avec la soumission.
2. Ce rattachement est générique : un même mécanisme sert n'importe quel workflow GVV qui intègre un formulaire, sans ajout de champ spécifique à ce workflow dans le module formulaires.
3. La suppression d'une réponse fait immédiatement redevenir l'entité GVV d'origine "sans réponse" — sans action de synchronisation supplémentaire à prévoir.

**B. Handler post-soumission (optionnel)** — pour les formulaires qui doivent en plus déclencher un effet de bord métier léger après soumission (ex. reporter une valeur saisie sur l'entité GVV d'origine).

4. Chaque formulaire peut déclarer un handler de post-soumission via un champ de configuration `handler_class` ; un formulaire sans besoin métier particulier n'en a pas.
5. Le handler est instancié par `forms_public` après la création de la soumission et appelé avec l'identifiant de la soumission et la référence d'entité (A).
6. Le handler retourne une URL de redirection pour personnaliser la page de confirmation.
7. Sur erreur du handler, la soumission reste stockée et peut être retraitée ; l'erreur est journalisée.
8. Les handlers sont des classes PHP localisées dans `application/libraries/form_handlers/` implémentant une interface commune.
9. La génération et l'archivage automatique d'un document (`archived_documents`) depuis une soumission n'est pas une action de handler : si ce besoin est retenu un jour, ce sera une option générique du module `forms` (activable par formulaire), pas une responsabilité codée dans un handler métier.

**Cas d'usage de référence** : le formulaire `briefing_passager_ulm` est intégré dans le workflow de vol de découverte, en remplacement complet, à terme, de l'actuel mécanisme (contrôleur `briefing_passager`, upload/signature, `archived_documents`). Il utilise le rattachement générique (A) pour piloter l'indicateur "briefing fait" du vol de découverte, et un handler `BriefingPassagerUlmHandler` (B) pour reporter les valeurs saisies (date du vol, etc.) sur le vol de découverte. Ni génération PDF, ni archivage automatique, ni protection du lien de transfert vers le passager ne sont couverts par cette migration (voir Questions ouvertes).

### EF11 : Cartes dynamiques dans les dashboards

Un mécanisme de configuration piloté par données permet aux club-admins d'ajouter des raccourcis de navigation sous forme de cartes dans n'importe quel dashboard GVV, sans développement. Le cas d'usage principal est l'exposition de formulaires (génération d'attestation, briefing passager) depuis les dashboards pilote et instructeur.

1. Un club-admin peut créer, modifier, désactiver et supprimer des raccourcis de dashboard via une interface CRUD dédiée.
2. Chaque raccourci est défini par : dashboard cible, section cible (optionnelle), titre, description (optionnelle), URL de destination, icône (Font Awesome, cohérent avec le reste des dashboards GVV), couleur (classe Bootstrap ou valeur hex), ordre d'affichage, statut actif.
3. L'URL de destination peut être interne (chemin relatif GVV) ou externe (URL absolue) ; les URLs externes s'ouvrent dans un nouvel onglet (`target="_blank"`).
4. **Multi-langue** : chaque champ titre et description peut stocker une clé de fichier de langue GVV. Si la clé est reconnue par `$this->lang->line()`, la valeur traduite est utilisée ; sinon, le texte brut de la table est affiché.
5. Seuls les club-admins peuvent créer, modifier et supprimer des raccourcis.
6. Un raccourci peut être restreint à un rôle minimum (`role_required`) : les utilisateurs sans ce rôle ne voient pas la carte.
7. Les dashboards instrumentés sont les 8 sections du contrôleur unique `welcome.php` (`user`, `flights`, `treasurer`, `formation`, `maintenance`, `admin_club`, `admin_sys`, `dev` — GVV n'a pas de contrôleurs de dashboard séparés par profil). Tout nouveau dashboard peut être instrumenté sans modification de la table.
8. Dans chaque dashboard instrumenté, les raccourcis actifs et visibles pour l'utilisateur courant sont récupérés via un appel modèle unique et rendus dans la section correspondante.
9. Les tests Playwright qui vérifient l'accessibilité de toutes les URLs visibles n'ont pas besoin d'être adaptés tant que la table `dashboard_shortcuts` ne contient aucun raccourci réel (elle démarre vide) ; le filtrage par rôle des cartes suit le même principe que les cartes existantes codées en dur.

### EF12 : Soumission par téléchargement (scan)

Sur un formulaire où l'option est explicitement activée par l'admin, l'utilisateur peut télécharger un scan ou une photo du formulaire imprimé puis rempli à la main, à la place de la saisie en ligne. Un seul fichier par réponse. GVV n'a pas accès au contenu du fichier et ne peut pas vérifier qu'il s'agit du bon formulaire.

1. L'admin active l'option de téléchargement individuellement par formulaire (désactivée par défaut).
2. Sur la page publique du formulaire, un bouton "Télécharger un formulaire prérempli" apparaît à côté du bouton d'envoi lorsque l'option est activée ; il ouvre une fenêtre de dépôt de fichier (glisser-déposer ou sélection sur le disque) avec un champ commentaire et un bouton de validation.
3. Le fichier est compressé selon le même mécanisme que les documents archivés (image : redimensionnement + recompression au format d'origine ; PDF : Ghostscript).
4. Dans la liste admin des réponses d'un formulaire, une réponse de ce type n'affiche pas de bouton "Ouvrir" ; le bouton "Générer PDF" est remplacé par une miniature du fichier, cliquable pour l'ouvrir en grand.
5. Le commentaire saisi lors du téléchargement est affiché dans la colonne "Identification" de la liste des réponses.
6. La suppression d'une réponse de ce type supprime également le fichier téléchargé (et sa miniature) du stockage.
7. Il est possible de faire pivoter une image ou un PDF téléchargé qui n'a pas été numérisé verticalement.
8. Le bouton "Télécharger un formulaire prérempli" est également disponible depuis la vue liste des réponses, en plus de la page publique du formulaire.

### EF13 : Paiement en ligne intégré à un formulaire

Un formulaire peut proposer un paiement HelloAsso à l'utilisateur, en complément de sa réponse (ex. première cotisation à l'inscription, frais d'inscription BIA).

1. Un formulaire comporte au maximum un paiement (V1).
2. Le paiement est défini par : une description, un montant fixe ou une liste de montants proposés — si aucun montant n'est proposé, l'utilisateur saisit librement un montant, dans des bornes configurées — et le compte comptable GVV sur lequel l'écriture correspondante doit être générée.
3. Le paiement s'effectue dans le contexte (section/organisation) auquel le formulaire est rattaché.
4. L'admin configure le paiement comme **obligatoire** ou **facultatif** :
   - **Facultatif** : la réponse est acceptée que l'utilisateur paie ou non.
   - **Obligatoire** : une réponse n'est considérée acceptée qu'une fois le paiement confirmé ; si le paiement échoue ou n'est jamais confirmé, la réponse est marquée rejetée. Elle reste consultable par l'admin (traçabilité), mais n'est pas traitée comme une réponse valide.
5. Le statut du paiement (payé / en attente / non payé / rejeté) est affiché de façon explicite et non ambiguë dans le détail d'une réponse côté admin.
6. Le statut du paiement apparaît également dans le PDF imprimable généré à partir de la réponse.
7. La confirmation du paiement provient de la plateforme de paiement et peut être différée par rapport à l'instant de la soumission ; une réponse à paiement obligatoire peut donc transiter par un état "en attente" avant d'être acceptée ou rejetée.

### EF14 : Sous-formulaires (formulaires imbriqués)

Un formulaire peut inclure un widget de lien vers un autre formulaire GVV ("sous-formulaire"), ouvert dans un nouvel onglet, dont la réponse est ensuite rattachée et affichée dans le formulaire maître.

1. Le widget est déclaré dans le HTML d'une page (`data-gvv-type="subform"`), au même titre que les widgets signature et paiement.
2. Le widget peut être configuré obligatoire ou facultatif : s'il est obligatoire, le formulaire maître ne peut être soumis sans une réponse liée au sous-formulaire.
3. Le widget expose trois états : non rempli (lien "Remplir le sous-formulaire"), en attente de vérification (après ouverture du lien, action explicite "J'ai terminé, vérifier"), rempli (résumé lecture seule de la réponse + lien "Voir la réponse" + "Remplir à nouveau").
4. Aucun mécanisme silencieux (rechargement automatique de la page maître, `postMessage`) : la vérification est déclenchée par une action explicite de l'utilisateur, et ne modifie que la zone du widget concerné, sans perturber la saisie en cours sur le reste du formulaire maître.
5. "Remplir à nouveau" ouvre une nouvelle réponse vierge du sous-formulaire ; il n'y a pas d'édition en place d'une réponse déjà soumise.
6. Une resoumission du sous-formulaire crée une nouvelle réponse indépendante, avec ses propres fichiers ; les fichiers d'une réponse précédente ne sont ni supprimés ni fusionnés.
7. Si le formulaire maître n'est jamais soumis, la réponse du sous-formulaire est conservée (non supprimée), affichée dans la liste admin du sous-formulaire avec un indicateur "non rattaché".
8. Une réponse de sous-formulaire est rattachée au formulaire maître via le mécanisme générique `subject_type`/`subject_id` (EF10-A) dès que le formulaire maître est effectivement soumis.

Voir : [Design sous-formulaires](../design_notes/remplissage_formulaires_design.md#17-sous-formulaires-formulaires-imbriqués)

### EF15 : Export d'une réponse vers un formulaire de création GVV

Depuis la liste des réponses d'un formulaire, un bouton optionnel permet d'ouvrir un formulaire de création GVV standard (ex. création de membre) pré-rempli avec les valeurs d'une réponse, pour éviter la ressaisie manuelle.

1. Un formulaire peut déclarer deux paramètres optionnels : une URL cible et un libellé de bouton.
2. Quand les deux paramètres sont renseignés, un bouton portant le libellé apparaît sur chaque ligne de la liste des réponses.
3. Le bouton ouvre l'URL cible avec les valeurs de la réponse transmises en paramètres de requête, un paramètre par champ.
4. Les noms des champs du formulaire source doivent correspondre exactement aux noms attendus par le formulaire cible ; aucun mapping n'est configurable.
5. Les champs de type fichier et signature sont exclus de l'export.
6. Les champs à choix multiples (checkbox) sont exclus de l'export.
7. Le mécanisme est générique : n'importe quel formulaire de création GVV standard peut être ciblé, sans développement spécifique par cas d'usage.

Voir : [Design export vers formulaire de création](../design_notes/remplissage_formulaires_design.md#18-export-dune-réponse-vers-un-formulaire-de-création-gvv)

### EF16 : Modification en place d'une réponse déjà soumise

Pour utiliser les formulaires comme support de gestion de procédure, une réponse déjà soumise doit pouvoir être complétée ou corrigée sans perdre son identité ni son historique de rattachement.

1. Depuis la liste admin des réponses d'un formulaire, un bouton "Modifier" est disponible pour chaque réponse en ligne (`submission_method = 'online'`).
2. Le bouton recharge le formulaire multi-pages public avec les valeurs déjà soumises pré-remplies dans les champs de saisie standard.
3. La resoumission met à jour la réponse existante : `form_submissions.id` et `submission_uuid` restent inchangés, aucune nouvelle réponse n'est créée.
4. La date de soumission initiale (`submitted_at`), le rattachement générique (`subject_type`/`subject_id`) et le mode de soumission ne sont pas modifiés par une édition ; seule la date de dernière modification est mise à jour et visible dans le détail admin de la réponse.
5. Pour un champ signature, l'utilisateur peut conserver la signature initiale ou la redéfinir (dessin, upload ou saisie clavier).
6. Pour un champ fichier, l'utilisateur peut conserver le fichier déjà soumis ou le remplacer.
7. Si un fichier ou une signature est remplacé, le fichier initial est supprimé du stockage une fois le remplaçant enregistré avec succès.
8. Seul un administrateur authentifié, avec accès à la section du formulaire, peut déclencher une modification.

Voir : [Design modification en place d'une réponse](../design_notes/remplissage_formulaires_design.md#19-modification-en-place-dune-réponse-déjà-soumise)

### EF16-bis : Lien de modification public à usage unique

Extension d'EF16 : en plus du déclenchement admin, l'utilisateur d'origine peut reprendre sa réponse via un lien public généré à la demande, à usage unique.

1. Depuis la liste admin des réponses, un bouton "Modifier le formulaire" génère (ou régénère) un lien de modification et l'affiche pour l'admin.
2. Ce lien porte un token dédié, distinct de `submission_uuid`, à usage unique : toute nouvelle génération invalide immédiatement le lien précédent, qu'il ait été utilisé ou non.
3. Le token reste valable à la simple consultation (navigation, rafraîchissement) ; il n'est consommé qu'au moment d'une resoumission réussie.
4. Le token expire automatiquement 7 jours après sa génération, indépendamment de son usage.
5. Un accès avec un token invalide (déjà consommé, remplacé ou expiré) affiche un message explicite dédié, jamais un formulaire vide ou une erreur générique.
6. En cas de double soumission quasi simultanée avec le même token (ex. deux onglets), une seule aboutit ; l'autre échoue avec un message explicite, sans double enregistrement.
7. La transmission du lien à l'utilisateur reste manuelle, à la charge de l'admin (ouverture en direct ou transmission par un canal externe) — GVV n'envoie aucun email automatique.
8. Le formulaire rouvert via ce lien suit les mêmes règles de pré-remplissage et de remplacement de fichiers/signature que la modification en place déclenchée par un admin (EF16, points 5 à 7).

Voir : [Design lien de modification public](../design_notes/remplissage_formulaires_design.md#20-lien-de-modification-public-à-usage-unique-ef16-bis)

### EF17 : Complétude des pièces obligatoires

1. Pour les champs de type fichier ou signature, le caractère obligatoire (`is_required`) n'empêche plus la validation du formulaire : une réponse peut être soumise avec des pièces obligatoires manquantes.
2. Un formulaire peut définir qu'un ensemble de plusieurs champs fichier constitue une seule exigence, satisfaite dès qu'un seul des champs du groupe est renseigné (ex. carte d'identité OU passeport).
3. La liste des pièces manquantes est affichée explicitement, par libellé de champ, en bas du formulaire — visible aussi bien lors de la saisie initiale que lors d'une reprise via le lien de modification (EF16-bis).
4. Pour un groupe de pièces alternatives, la liste indique l'ensemble des libellés du groupe avec la règle "au moins un parmi".
5. La liste admin des réponses affiche un indicateur de complétude par réponse (ex. nombre de pièces manquantes), calculé à partir des mêmes règles.
6. Ce comportement ne modifie pas le caractère bloquant de `is_required` pour les autres types de champs (texte, select, etc.), qui continuent d'empêcher la soumission comme aujourd'hui.

Voir : [Design complétude des pièces obligatoires](../design_notes/remplissage_formulaires_design.md#21-complétude-des-pièces-obligatoires-ef17)

## Exigences non fonctionnelles

- **UX** : résultat explicite après chaque action (création, soumission, échec, archivage).
- **Sécurité** : validation stricte des entrées et des fichiers, isolation du stockage.
- **Performance** : ouverture formulaire < 2s en usage nominal ; soumission < 5s hors upload volumineux.
- **Traçabilité** : journalisation dans les fichiers de logs.
- **Compatibilité** : rendu responsive desktop/mobile.

## Documentation attendue

- Exemples de formulaires prêts à l'emploi.
- Exemple de CSS global de personnalisation.
- Guide import PDF -> HTML.
- Guide génération PDF imprimable à partir d'une réponse.

## Mesures de succès

- 80% des nouveaux besoins gérés sans développement spécifique de formulaire.
- Réduction du temps de mise en place d'un formulaire admin > 50%.
- 100% des réponses archivables vers un pilote quand le contexte GVV est fourni.

## Questions ouvertes

- V1 : éditeur strictement HTML structuré ou blocs UI intermédiaires ?
- Politique de conservation des fichiers uploadés non archivés ?
- Niveau d'automatisation d'archivage depuis les workflows ? *(Tranché pour le briefing passager, juillet 2026 : pas d'archivage automatique — reste une option générique future du module `forms` si le besoin réapparaît pour un autre workflow.)*
- Autres entités GVV à intégrer en catégorie 3 au-delà du briefing passager ?
- Protection du lien public envoyé au passager (remplace le `briefing_tokens` actuel) : l'utilité même du transfert par QR code/SMS est remise en question (juillet 2026). Si confirmée plus tard, ce sera une fonctionnalité générique de formulaires "transférables", pas propre au briefing passager. Non traitée dans la migration en cours.
- EF13 : délai/critère exact de rejet d'une réponse à paiement obligatoire non confirmé (rejet différé après un délai, ou rejet immédiat sur échec/annulation explicite côté HelloAsso) ?
- EF13 : une réponse rejetée pour défaut de paiement peut-elle être régularisée a posteriori (nouveau lien de paiement envoyé à l'utilisateur) ou l'utilisateur doit-il resoumettre le formulaire ?
- EF13 : notification (email) à l'utilisateur et/ou à l'admin selon l'issue du paiement ?
- EF14 : *(Tranché, Lot 11)* un formulaire de catégorie 3 (déjà rattaché à une entité GVV via son propre `subject_type`/`subject_id`, ex. `briefing_passager_ulm`) peut aussi être utilisé comme sous-formulaire ; en cas de conflit, l'attachement direct existant est prioritaire et la bascule vers `subject_type='form_submission'` est simplement ignorée (voir design § 17, « Décision actée »).
- EF14 : un formulaire admin doit-il pouvoir restreindre quels formulaires publiés sont utilisables comme sous-formulaire (liste blanche), ou n'importe quel formulaire publié est-il éligible ? *(Non tranché : n'importe quel formulaire publié est éligible en V1.)*
- EF16 : la modification en place doit-elle un jour être proposée aussi pour les réponses de type téléchargement (remplacement de scan), au-delà de la rotation déjà couverte par EF12 ?
- EF16-bis : faut-il à terme permettre l'envoi automatique du lien de modification par email, ou la transmission manuelle par l'admin reste-t-elle suffisante ?

### Résolues

- **Stratégie de migration `briefing_sign` → handler** *(juillet 2026)* : remplacement complet, pas de cohabitation prolongée. Séquencement : construire et valider le nouveau mécanisme (`forms` + rattachement générique) sans toucher à l'ancien, ressaisir manuellement les briefings existants (peu nombreux) au moment de la bascule, puis basculer la détection d'un coup. La suppression effective du code de l'ancien mécanisme documentaire reste une décision séparée, ultérieure à la validation en conditions réelles.
- **EF16 : lien de modification envoyé à l'utilisateur d'origine** *(août 2026)* : tranché — oui, sous forme d'un lien à usage unique (token dédié, régénéré à chaque demande, expirant après 7 jours), généré à la demande depuis la liste admin des réponses. Pas d'envoi automatique par email : la transmission reste manuelle, à la charge de l'admin. Voir EF16-bis.
- **EF2-quater : le formulaire reste indexé en base, pas purement filesystem** *(août 2026)* : tranché — la table `forms` est conservée comme index (identifiant stable référencé par `form_submissions`, listage/filtrage admin), mais cesse d'être la référence pour le contenu/la configuration une fois le fichier de métadonnées introduit. Écarté : liste des formulaires dérivée uniquement d'un `scandir()` de `uploads/formulaires/` — coût de requêtage à chaque affichage, absence de verrouillage concurrent, et surtout couplage fragile entre `form_submissions` et un identifiant (`code`) qui reste volontairement renommable.
- **EF2-quater : emplacement et mode de service du CSS partagé** *(août 2026, révisé le 9 août 2026)* : fichier réservé `uploads/formulaires/.commun/style.css` (nom hors alphabet `alpha_dash` des codes de formulaire, aucune collision possible), servi par la route publique `forms_public/shared_css` — implémenté et inchangé (Lot 2-ter). **Révisé** : la convention de référence initialement documentée (`@import url("https://gvv.net/index.php/forms_public/shared_css")`, URL absolue avec domaine) est remplacée par une URL relative à la racine du site (`/forms_public/shared_css`, sans domaine) pour rester portable d'une installation GVV à l'autre — un domaine figé dans le contenu casserait l'export/import entre installations. Voir « EF2-quater : convention de référence des images et du CSS partagé » ci-dessous.
- **EF2-quater : convention de référence des images et du CSS partagé** *(9 août 2026)* : tranché — le fichier stocké référence toujours ses ressources locales/partagées par un **chemin relatif** (`images/{fichier}` pour une image propre au formulaire, `.commun/style.css`/`.commun/images/{fichier}` pour les ressources partagées), jamais par une route applicative GVV écrite en dur. GVV réécrit ces références en URL relative à la racine du site au moment du rendu (public, aperçu admin, export PDF) — jamais en base ni dans le fichier stocké. Un lien symbolique côté serveur GVV (`{code}/.commun -> ../.commun`) a été envisagé pour que la convention résolve aussi par accès fichier direct côté serveur, en plus de la réécriture au rendu — écarté : la réécriture au rendu couvre déjà tous les besoins identifiés, et un lien symbolique introduirait une classe de bug réelle dans les méthodes de suppression/duplication existantes (`Forms_file_storage::delete_form_dir()`/`copy_form_dir()`) pour un gain non identifié à ce jour. La prévisualisation hors GVV en `file://` de ces ressources reste une convention recommandée à l'admin (dossier/lien `.commun` de son côté), pas un mécanisme fourni ou vérifié par GVV.
