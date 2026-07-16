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

## Non-objectifs

- Remplacer l'ensemble du module workflow GVV en V1.
- Concevoir un éditeur visuel WYSIWYG complet type "no-code" avancé en V1.
- Ajouter la signature électronique qualifiée (eIDAS) en V1.
- Signature PGP (OpenPGP.js) en V1 — option avancée réservée aux extensions ultérieures (complexité, dépendances JS, valeur légale incertaine).

## Portée

### Inclus

- CRUD admin des formulaires (créer, modifier, supprimer, activer/désactiver).
- Formulaires composés d'une ou plusieurs pages HTML.
- Édition en ligne d'une page et import/export texte de page.
- Lien public de soumission, sans authentification GVV.
- Types de champs : texte, email, date, numérique, textarea, select, radio, checkbox, fichier.
- Prévisualisation admin des fichiers image/PDF soumis.
- Insertion de documents archivés GVV dans le formulaire avec visualisation intégrée (scroll si nécessaire).
- Liste admin des réponses + détail d'une réponse.
- Champ signature : widget composite (dessin canvas + upload image) avec stockage dans `form_submission_files`.
- Pré-remplissage d'une signature depuis le profil GVV (`membres.signature_path`, sources `member.signature` / `instructor.signature`).
- Génération d'un PDF imprimable de la réponse.
- Import d'un PDF formulaire pour produire une base HTML éditable.
- Création d'un document archivé depuis une réponse via le formulaire documentaire existant, avec PDF imprimable pré-rempli.
- Soumission par téléchargement d'un scan/photo du formulaire imprimé, en alternative au remplissage en ligne, activable par formulaire (EF12).
- Paiement en ligne HelloAsso intégré à un formulaire, obligatoire ou facultatif selon configuration (EF13).

### Exclu

- OCR avancé sur PDF scannés non structurés en V1.
- Rendu pixel-perfect garanti identique au PDF source importé.
- Sauvegarde/reprise multi-session du remplissage public en V1 (prévue en extension ultérieure).
- Pages/sections conditionnelles basées sur les réponses en V1 (prévu en extension ultérieure).
- Plusieurs paiements sur un même formulaire, ou choix entre plusieurs moyens de paiement, en V1 (EF13) — un seul widget de paiement HelloAsso par formulaire.

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
2. Chaque page est éditable en ligne.
3. Chaque page peut être importée depuis un fichier texte/HTML.
4. Chaque page peut être exportée en fichier texte/HTML.

### EF2-bis : Synchronisation fichiers disque

1. Le contenu HTML d'une page et le CSS global d'un formulaire peuvent être stockés sous forme de fichiers dans `application/forms_templates/`.
2. Nommage : `{public_slug}_pageN.html` pour les pages, `{public_slug}.css` pour le CSS global.
3. Un bouton "Actualiser depuis le disque" dans l'admin déclenche la synchronisation fichier → base (le fichier est prioritaire).
4. Toute sauvegarde via l'interface web écrit simultanément en base et sur disque.
5. La détection de modification repose sur un hash MD5 du contenu (insensible aux timestamps de déploiement).
6. La synchronisation n'est jamais déclenchée automatiquement au rendu public.

Voir : [Design synchronisation fichiers](../design_notes/formulaires_sync_fichiers_design.md)

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
2. Chaque raccourci est défini par : dashboard cible, section cible (optionnelle), titre, description (optionnelle), URL de destination, icône (Bootstrap Icons), couleur (classe Bootstrap ou valeur hex), ordre d'affichage, statut actif.
3. L'URL de destination peut être interne (chemin relatif GVV) ou externe (URL absolue) ; les URLs externes s'ouvrent dans un nouvel onglet (`target="_blank"`).
4. **Multi-langue** : chaque champ titre et description peut stocker une clé de fichier de langue GVV. Si la clé est reconnue par `$this->lang->line()`, la valeur traduite est utilisée ; sinon, le texte brut de la table est affiché.
5. Seuls les club-admins peuvent créer, modifier et supprimer des raccourcis.
6. Un raccourci peut être restreint à un rôle minimum (`role_required`) : les utilisateurs sans ce rôle ne voient pas la carte.
7. Les dashboards instrumentés au premier déploiement sont : `accueil`, `pilote`, `instructeur`, `formations`. Tout nouveau dashboard peut être instrumenté sans modification de la table.
8. Dans chaque dashboard instrumenté, les raccourcis actifs et visibles pour l'utilisateur courant sont récupérés via un appel modèle unique et rendus dans la section correspondante.
9. Les tests Playwright qui vérifient l'accessibilité de toutes les URLs visibles doivent être mis à jour pour couvrir les raccourcis dynamiques : soit en les excluant du test d'accessibilité automatique, soit en les testant séparément avec les paramètres d'authentification appropriés.

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

### Résolues

- **Stratégie de migration `briefing_sign` → handler** *(juillet 2026)* : remplacement complet, pas de cohabitation prolongée. Séquencement : construire et valider le nouveau mécanisme (`forms` + rattachement générique) sans toucher à l'ancien, ressaisir manuellement les briefings existants (peu nombreux) au moment de la bascule, puis basculer la détection d'un coup. La suppression effective du code de l'ancien mécanisme documentaire reste une décision séparée, ultérieure à la validation en conditions réelles.
