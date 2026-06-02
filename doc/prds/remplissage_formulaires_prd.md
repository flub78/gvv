# PRD — Remplissage Formulaires

Date : 30 mai 2026

## Contexte

La gestion actuelle orientée conversion de documents est jugée trop lourde et dépendante d'outils externes. Le besoin cible est un module de formulaires natifs HTML, inspiré de Google Forms, mais intégré à GVV et à son système documentaire.

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
- Archiver les réponses (et leur PDF imprimable si demandé) pour un pilote.

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
- Archivage d'une réponse vers `archived_documents` liée à un pilote.

### Exclu

- OCR avancé sur PDF scannés non structurés en V1.
- Rendu pixel-perfect garanti identique au PDF source importé.
- Sauvegarde/reprise multi-session du remplissage public en V1 (prévue en extension ultérieure).
- Pages/sections conditionnelles basées sur les réponses en V1 (prévu en extension ultérieure).

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
4. Il archive la réponse pour un pilote dans `archived_documents`.

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

### EF6 : Données GVV et pré-remplissage

1. Les champs pré-remplis sont déclarés dans le HTML via des attributs `data-gvv-*` directement sur les éléments de saisie.
2. Trois attributs : `data-gvv-source` (source de donnée), `data-gvv-param` (paramètre URL d'identification), `data-gvv-lock` (verrouillage serveur).
3. Les paramètres d'identification (`pilot_login`, `instructor_login`) sont transmis dans l'URL du formulaire.
4. Les sources autorisées couvrent : données du club (config), données d'un membre, données d'un instructeur, utilisateur de session, dates calculées.
5. Le verrouillage est appliqué côté serveur : pour `data-gvv-lock="true"`, GVV ignore la valeur soumise et impose la valeur résolue.
6. Une liste blanche stricte des sources autorisées est définie — pas d'accès libre à la base.
7. Le paramètre d'identification transmis en URL est validé (existence + appartenance à la section active).
8. Cette exigence est hors du périmètre de la première livraison et intervient après le socle autonome de formulaires.
9. La taxonomie des sources inclut `member.signature` → `membres.signature_path` (param : `pilot_login`) et `instructor.signature` → `membres.signature_path` (param : `instructor_login`).

Voir : [Design pré-remplissage](../design_notes/remplissage_formulaires_design.md#5-pré-remplissage-gvv)

### EF6-bis : Champ signature

1. Un champ signature est déclaré dans le HTML via `<div data-gvv-type="signature" data-gvv-name="..." data-gvv-param="..." data-gvv-lock="...">`. GVV remplace ce div par le widget lors du rendu public ; le texte du div reste visible en prévisualisation standalone.
2. Le widget expose trois onglets : dessin canvas, upload image, signature PGP (option avancée exclue de V1).
3. En mode canvas : la signature est normalisée (600×200 px), exportée en PNG base64, transmise via un champ caché, décodée côté serveur et stockée dans `form_submission_files` (`mime_type = image/png`).
4. En mode upload : `<input type="file" accept="image/*">` dans le widget, pipeline standard `form_submission_files`.
5. Deux valeurs cachées sont transmises à chaque soumission : le contenu et le type (`canvas|file|pgp`), pour audit côté serveur.
6. Le champ signature peut être pré-rempli depuis `membres.signature_path` (voir EF6, sources `member.signature` / `instructor.signature`).
7. Si `data-gvv-lock="false"`, l'utilisateur peut remplacer la signature pré-remplie.

Voir : [Design signatures](../design_notes/remplissage_formulaires_design.md#6-signatures)

### EF7 : Réponses et supervision

1. Liste admin des réponses par formulaire (filtre date/statut).
2. Consultation du détail d'une réponse et de ses fichiers.
3. Export des réponses (CSV/JSON) en option.

### EF8 : PDF imprimable et import PDF

1. À partir d'une réponse, génération d'un PDF imprimable.
2. Import d'un document PDF pour initialiser une version HTML éditable.
3. Le système doit signaler clairement les éléments non convertis lors de l'import.

### EF9 : Archivage

1. Une réponse peut être archivée dans `archived_documents`.
2. L'archivage peut être lié à un pilote (obligatoire pour le cas d'usage demandé).
3. Métadonnées minimales : formulaire source, date, auteur soumission, admin archiveur.

## Exigences non fonctionnelles

- **UX** : résultat explicite après chaque action (création, soumission, échec, archivage).
- **Sécurité** : validation stricte des entrées et des fichiers, isolation du stockage.
- **Performance** : ouverture formulaire < 2s en usage nominal ; soumission < 5s hors upload volumineux.
- **Traçabilité** : journalisation des opérations admin et soumissions.
- **Compatibilité** : rendu responsive desktop/mobile.

## Documentation attendue

- Exemples de formulaires prêts à l'emploi.
- Exemple de CSS global de personnalisation.
- Guide import PDF -> HTML et limites connues.
- Guide génération PDF imprimable à partir d'une réponse.

## Mesures de succès

- 80% des nouveaux besoins gérés sans développement spécifique de formulaire.
- Réduction du temps de mise en place d'un formulaire admin > 50%.
- 100% des réponses archivables vers un pilote quand le contexte GVV est fourni.

## Questions ouvertes

- V1 : éditeur strictement HTML structuré ou blocs UI intermédiaires ?
- Politique de conservation des fichiers uploadés non archivés ?
- Niveau d'automatisation d'archivage depuis les workflows ?
