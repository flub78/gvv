# PRD : newsletter de veille sécurité et réglementaire

| | |
|---|---|
| **Version** | 0.7 (brouillon) |
| **Date** | 25/10/2026 |
| **Auteur** | Frédéric Peignot, avec Claude |
| **Statut** | À valider |
| **Premier client** | Aéroclub d'Abbeville-Buigny-Baie de Somme (ACS), DTO FR.DTO.0645 |

---

## 1. Contexte et problème

Un DTO doit assurer une veille réglementaire et sécurité (DTO.GEN.210, § B-9 du manuel de sécurité) et pouvoir la prouver en audit. L'audit DSAC de juin 2026 a d'ailleurs relevé une remarque sur ce point (#1406200).

Aujourd'hui, cette veille est manuelle et dispersée :

- **Les informations arrivent par de nombreux canaux** : la boîte `veille@aeroclub-abbeville.fr`, les sites et les flux des organismes officiels (veilledoc.dsac.fr, EASA, BEA, Légifrance), les lettres et les espaces licenciés des fédérations (FFA, FFVP, FFPLUM).
- **Personne n'a le temps de tout lire, trier et résumer** à chaque période.
- **La diffusion aux membres est irrégulière** et peu attractive.
- **La preuve de veille n'est pas archivée** de façon structurée.

La newsletter répond à ces quatre points : elle centralise la veille, elle est rédigée par un agent IA qui suit ses propres consignes, puis annotée, relue par email et validée par un administrateur dans GVV ; chaque édition, avec le journal tenu par l'agent, constitue une trace datée de la veille.

## 2. Objectifs

| # | Objectif | Indicateur |
|---|---|---|
| O1 | Collecter automatiquement les informations sécurité et réglementaires des sources configurées | Au moins 95 % des éléments pertinents retenus, mesuré par un test rétroactif contre une liste de référence établie par le RP |
| O2 | Produire à chaque période un brouillon de newsletter utile et agréable à lire | Validation en 30 min ou moins ; au moins 80 % des articles proposés jugés pertinents par l'administrateur qui valide |
| O3 | Ne jamais diffuser d'information fausse ou malveillante | Aucune newsletter envoyée sans validation humaine ; chaque article cite une source réelle consultable |
| O4 | Fournir une preuve de veille présentable en audit | Journal de l'agent pour chaque exécution (sources consultées, résultat, documents détectés, décision et motif), conservé par l'agent, et newsletters publiées dans GVV, conservés au moins 36 mois |
| O5 | Permettre l'abonnement de personnes extérieures au club | Inscription/désinscription autonome ; envoi automatique à la publication |
| O6 | Valider les briques technologiques avant tout développement | Page de validation démontrant l'envoi d'email, la lecture de boîte, et l'accès à l'API IA |
| O7 | Publier une newsletter d'aspect professionnel | PDF mis en page (bandeau de titre, rubriques, encadrés, images), lisible à l'écran et à l'impression |

### Hors objectifs (version 1)

- Remplacer la responsabilité du représentant du DTO et du RP pour la veille.
- Notifier des événements à l'autorité (ECCAIRS).
- Modifier automatiquement le manuel de sécurité.
- Gérer des sources payantes.
- Publier un article sans validation humaine.
- Lancer l'agent IA depuis GVV, ou générer automatiquement le brouillon sans intervention humaine (voir § 10).

## 3. Utilisateurs et acteurs

| Rôle | Besoin |
|---|---|
| **Administrateur** (administrateur de la veille, CPS ou personne désignée) | Lancer l'agent IA, importer ses brouillons dans GVV, les annoter, les transmettre aux relecteurs, relever et trier leurs remarques, fournir à l'agent les consignes de modification, valider la newsletter ; désigner les relecteurs, gérer les abonnés |
| **Abonnés** (membres et sympathisants) | S'abonner, se désabonner, recevoir la newsletter par email à chaque période |
| **Agent IA** (configuré dans Claude Code, hors GVV) | Explorer les sources, identifier les documents nouveaux et modifiés, les trier selon ses consignes, rédiger la version initiale du brouillon d'une période ; tenir l'état de la veille et le journal des accès et des décisions ; produire une nouvelle version du brouillon à partir des consignes de modification fournies par l'administrateur |
| **Relecteurs** | Recevoir le brouillon par email, répondre par email avec des remarques, des suggestions d'articles et des images |
| **RP / représentant du DTO** | Consulter les newsletters archivées et le journal de l'agent pour l'audit |

## 4. Principes

1. **Deux systèmes séparés.** L'agent IA rédige ; GVV gère la relecture, l'approbation et la diffusion. Ils échangent des fichiers par l'intermédiaire de l'administrateur : le brouillon dans un sens, les consignes de modification dans l'autre.
2. **L'agent IA porte ses consignes et son état.** Le registre des sources, les critères de sélection, la description du club et de sa flotte, les directives de rédaction, l'inventaire des documents suivis et le journal des accès et des décisions sont gérés avec l'agent, pas dans GVV.
3. **Le brouillon se suffit à lui-même.** Le titre, la période et le numéro de version sont lus dans le brouillon ; l'administrateur n'a rien à ressaisir à l'import.
4. **La relecture se fait par email, le pilotage dans GVV.** Les relecteurs répondent à l'email reçu ; l'administrateur relève les réponses dans GVV et décide du sort de chaque remarque. Aucune nouvelle version n'est produite sans sa décision.
5. **Rien n'est diffusé sans validation humaine.** La validation est une action explicite de l'administrateur dans GVV.
6. **Les notes internes ne sortent pas du club** : les remarques destinées au CPS et aux relecteurs sont supprimées avant la mise en forme PDF et la diffusion.
7. **Le mécanisme d'abonnement est indépendant** de la rédaction : les abonnements se gèrent sans lien avec le cycle des éditions.
8. **La publication se fait depuis l'édition.** Une newsletter n'est publiée que depuis la page de son édition, par l'action « Valider et publier ».

## 5. Contenu de la newsletter

Chaque édition comprend, dans cet ordre :

1. **Un titre.**
2. **La période couverte**, selon la périodicité fixée par l'administrateur.
3. **Une introduction** présentant la newsletter.
4. **Veille réglementaire** : une série d'articles. Chaque article contient :
   - un titre ;
   - une synthèse de l'information ;
   - une description de sa source (émetteur, nature du document, date, lien).
5. **REX et technique** : une sélection de REX ou un article technique.
6. **Articles des contributeurs** : articles proposés par des membres ou relecteurs.
7. **Pied de page** : mention « Synthèse indicative, préparée avec une assistance automatisée et validée par le club. Seuls les textes officiels font foi. », lien de désinscription en un clic.

Une section vide est omise. La newsletter peut aussi contenir des **commentaires de l'administrateur**, insérés sous un paragraphe et présentés comme tels (par exemple « Commentaire du DTO : le plafond à 400 ft de ces zones fait qu'on n'a pas vraiment de raisons d'y pénétrer »).

## 6. Périmètre fonctionnel

### 6.1 Sources

Les sources sont décrites dans le registre des sources des consignes de l'agent, hors GVV :

| Type | Exemples |
|---|---|
| Boîte de collecte | `veille@aeroclub-abbeville.fr` (VEILLEDOC, newsletter BEA, courriels FFA transférés) |
| Flux RSS et Atom | EASA (règlements, décisions, AMC/GM, actualités), LegiFRSS |
| Sites web publics | SIA, Prévol DSAC, BEA, pages sécurité et REX des fédérations |
| Sites nécessitant un navigateur | Sites entièrement générés en JavaScript (FFA, liste des rapports BEA), bibliothèques Calaméo (CNVP, Planeur Info) |
| Outils de recherche publics | Outil EASA des consignes de navigabilité, interrogé par type d'aéronef de la flotte |

### 6.2 Fonctionnalités

**F0. Validation de l'infrastructure**

Avant tout développement fonctionnel, une page de validation démontre que les briques technologiques sont correctement configurées :

- F0.1 **Email sortant** : envoi d'un email depuis `veille@aeroclub-abbeville.fr` et confirmation de réception.
- F0.2 **Lecture de boîte mail** : connexion à la boîte de réception, comptage des emails reçus depuis une date donnée, lecture des sujets et repérage d'emails par pattern dans le titre, lecture du contenu, accès aux pièces jointes.
- F0.3 **API IA** : réception d'une réponse à une requête simple, transmission de fichiers de contexte et d'images ou PDF, génération de documents en markdown ou PDF.

**F1. Génération du brouillon (agent IA, hors GVV)**

*Exigences sur le résultat d'une exécution de l'agent :*

- F1.1 Toutes les sources dues du registre sont explorées : boîte de collecte, sites web, y compris ceux qui nécessitent un navigateur, et flux RSS.
- F1.2 Les documents nouveaux depuis l'exécution précédente sont identifiés.
- F1.3 Les documents modifiés depuis l'exécution précédente sont identifiés, y compris quand la source ne change pas la date de publication.
- F1.4 Chaque document nouveau ou modifié est analysé selon les critères des consignes (activités du club, flotte, impact) et fait l'objet d'une décision : retenu, à vérifier ou écarté, avec un motif.
- F1.5 Le brouillon suit la structure du § 5 et ne contient que des articles fondés sur des documents réels, avec leur lien. Un document dont la date de publication n'a pas pu être établie n'est pas présenté comme une nouveauté de la période.
- F1.6 L'agent tient un journal : chaque tentative d'accès à une source et son résultat (succès, partiel, indisponible, non accessible), les documents détectés, et pour chacun la décision et son motif, ainsi qu'un bilan de l'exécution.
- F1.7 L'agent tient l'état de la veille (inventaire des documents suivis), mis à jour à chaque exécution et servant de référence à l'exécution suivante.
- F1.8 L'agent génère la version initiale du brouillon d'une période.
- F1.9 L'agent génère une nouvelle version du brouillon à partir des consignes de modification produites par GVV (F2.9), images comprises.
- F1.10 Le brouillon indique son titre (premier titre du document), sa période (ligne « Période : du … au … ») et son numéro de version (mention « BROUILLON vN »). Il est livré sous forme d'un fichier Markdown, ou d'une archive zip contenant le Markdown et un sous-répertoire d'images lorsqu'il en référence.

**F2. Import, annotation et relecture dans GVV**

- F2.1 L'administrateur importe un brouillon : un fichier Markdown, ou une archive zip contenant un fichier Markdown et un sous-répertoire d'images. GVV lit le titre, la période et la version dans le brouillon et signale explicitement ce qui manque ; il signale aussi les images référencées absentes de l'archive.
- F2.2 Un brouillon dont la période correspond à une édition en cours en devient la version suivante ; sinon, une nouvelle édition est créée. Une version déjà importée, ou une édition déjà validée ou publiée, est refusée avec un message explicite.
- F2.3 L'administrateur peut insérer des commentaires sous n'importe quel paragraphe du brouillon, avec un libellé (« Commentaire du DTO » par défaut) et un texte, et les supprimer. Les commentaires apparaissent dans le brouillon affiché, dans le PDF transmis aux relecteurs et dans la newsletter publiée.
- F2.4 L'administrateur transmet le brouillon annoté aux relecteurs par email, en PDF. L'email indique la date limite de réponse, deux jours après l'envoi par défaut, et demande surtout aux relecteurs de signaler toute information qui aurait dû figurer dans la newsletter et qui n'y est pas, avec sa source s'ils la connaissent.
- F2.5 Les relecteurs répondent à l'email avec des remarques, des suggestions d'articles et des images, reçues sur l'adresse de veille.
- F2.6 Un bouton, sur chaque brouillon transmis, relève les réponses reçues sur l'adresse de veille pour cette newsletter. Une réponse déjà relevée ne l'est pas une seconde fois. Seules les réponses provenant de relecteurs autorisés sont retenues ; les autres sont signalées à l'administrateur avec le motif.
- F2.7 Pour chaque remarque, l'administrateur décide : l'accepter, la modifier (c'est alors le texte modifié qui est retenu), la rejeter (elle n'est pas transmise à l'agent) ou la reporter à une prochaine édition.
- F2.8 Une remarque reportée est reproposée lors du tri des éditions suivantes ; l'administrateur peut télécharger les remarques reportées pour les fournir à l'agent qui génère la version initiale d'une prochaine édition.
- F2.9 Un bouton télécharge les consignes de modification destinées à l'agent IA : un fichier Markdown, ou une archive zip contenant ce fichier et un sous-répertoire avec les images à intégrer. Chaque élément y porte un statut dans le vocabulaire de l'agent : **APPLIQUER** pour les commentaires de l'administrateur et les remarques acceptées ou modifiées (pour une remarque modifiée, le texte à appliquer est celui de l'administrateur, le texte du relecteur est cité pour mémoire), **REPORTER** pour les remarques reportées à une prochaine édition. Les remarques rejetées n'y figurent pas.
- F2.10 À l'import de la nouvelle version (F2.1), l'administrateur peut la retransmettre aux relecteurs pour un nouveau tour, ou la valider. GVV conserve toutes les versions et affiche les différences avec la précédente.

**F3. Validation**

- F3.1 L'administrateur peut valider le brouillon courant à tout moment : après un ou plusieurs tours de relecture, ou directement s'il n'y a pas de remarques. La validation et la publication (F4) forment une seule action, « Valider et publier », confirmée par l'administrateur ; si la publication échoue, l'édition reste validée et la publication peut être relancée.
- F3.2 Avant la validation, GVV signale les mentions restant à compléter dans le brouillon.
- F3.3 Aucune newsletter n'est diffusée sans validation.
- F3.4 La validation enregistre qui a validé, quand, et la version validée.
- F3.5 La newsletter publiée se termine par la mention « Validé le jj/mm/aaaa par {prénom et nom du valideur} ».

**F4. Finalisation et publication**

- F4.1 Une fois le brouillon validé, les notes internes destinées au CPS et aux relecteurs sont supprimées, y compris les notes de rédaction entre doubles accolades ({{ … }}), et la mention de validation (F3.5) est ajoutée.
- F4.2 La newsletter est mise en forme en PDF, avec une présentation moderne et professionnelle : bandeau de titre avec le nom du club, la période et le numéro, rubriques et articles hiérarchisés visuellement, encadrés pour les avertissements et les commentaires de l'administrateur, images intégrées, pied de page avec la pagination. Chaque section commence sur une nouvelle page, sauf si plusieurs sections tiennent sur la même page.
- F4.3 La newsletter est archivée dans l'espace accessible aux membres actifs connectés.
- F4.4 La newsletter est envoyée aux abonnés en pièce jointe par email, accompagnée d'un texte d'annonce, d'une invitation à envoyer remarques et informations manquantes (avec leur source) à l'adresse de veille, et du lien de désinscription.

**F5. Abonnements**

- F5.1 Une URL publique permet aux personnes extérieures de s'abonner ou se désabonner à la newsletter, indépendamment du compte GVV.
- F5.2 L'inscription demande le nom, le prénom et l'adresse email. Le nom et le prénom sont utilisés pour personnaliser l'email d'envoi.
- F5.3 Désinscription en un clic depuis la newsletter ou depuis la page d'abonnement.
- F5.4 Les administrateurs club peuvent accéder à la liste des abonnés et supprimer des abonnements.
- F5.6 La page d'administration des abonnés donne le lien de la page publique d'abonnement, à diffuser.

**F6. Archive et consultation**

- F6.1 Les newsletters sont stockées sous forme de PDF sur le serveur GVV.
- F6.2 Les membres actifs connectés peuvent consulter l'archive des newsletters. Cliquer sur une newsletter l'ouvre dans un onglet séparé. Les dates (période, publication) sont affichées au format local.
- F6.4 Les administrateurs club peuvent supprimer une newsletter de l'archive, après confirmation : le PDF publié et les données de l'édition sont supprimés.
- F6.3 Le journal de l'agent et l'état de la veille sont conservés par l'agent au moins 36 mois, pour l'audit.

**F7. Administration**

- F7.1 L'administrateur désigne les relecteurs autorisés et le délai de réponse.
- F7.2 Il configure la boîte de veille (lecture) et le compte d'envoi.
- F7.3 Il peut accéder à la liste des abonnés et supprimer des abonnements.
- F7.4 Il peut supprimer une édition, quel que soit son état, depuis la liste des éditions, après confirmation : ses versions, commentaires, remarques et, si elle est publiée, son PDF sont supprimés.

## 7. Workflow global

```
[Agent IA, Claude Code]                        consignes, sources, état, journal
     │ version initiale v1 d'une période
     ▼
[Admin] ── importe le brouillon (.md ou .zip) dans GVV
     │
     ▼
[Brouillon vN] ── admin annote (« Commentaire du DTO : … »)
     │
     ├──────────────── pas de remarques ──────────────┐
     ▼                                                 │
[Admin] ── transmet aux relecteurs (PDF, email)        │
     │                                                 │
     ▼                                                 │
[Relecteurs] ── répondent par email (veille@)          │
     │                                                 │
     ▼                                                 │
[Admin] ── relève les réponses dans GVV                │
     │ accepte / modifie / rejette / reporte           │
     │ télécharge les consignes de modification        │
     ▼                                                 │
[Agent IA] ── produit la version vN+1                  │
     │                                                 │
     ▼                                                 │
[Admin] ── importe vN+1 : nouveau tour, ou ────────────┤
                                                       ▼
                                    [Admin valide et publie le brouillon]
                                                       │
                                                       ▼
                              suppression des notes internes → PDF mis en page
                                                       │
                                                       ▼
                                     [Archive GVV] + [Envoi aux abonnés]
```

## 8. Architecture technique (indicative)

- **Agent IA** : Claude Code, configuré avec les consignes de la veille, avec accès au disque et navigation web. Il conserve l'état de la veille et le journal.
- **Échanges avec GVV** : fichiers transmis par l'administrateur (brouillon vers GVV, consignes de modification vers l'agent), au format Markdown ou zip avec images.
- **Cycle de relecture** : relevé à la demande des réponses reçues sur l'adresse de veille ; tri et décisions dans GVV.
- **Envoi** : SMTP du domaine, email personnalisé avec pièce jointe PDF et lien de désinscription.
- **Archive** : fichiers PDF sur le serveur GVV, accessibles via l'interface.
- **Application** : module GVV pour l'import, l'annotation, la relecture, la validation, les abonnements et l'archive.

## 9. Plan de livraison

| Phase | Contenu | Critère de sortie |
|---|---|---|
| **P0. Validation infrastructure** | Page de validation : envoi email, lecture boîte, API IA | Toutes les briques fonctionnelles démontrées |
| **P1. Abonnements** | URL publique d'abonnement/désinscription, gestion admin, archive PDF membres | Un abonné peut s'inscrire, recevoir une newsletter envoyée manuellement, se désabonner |
| **P2. Cycle de relecture dans GVV** | Import du brouillon (.md ou .zip), annotation, transmission aux relecteurs, relevé et tri des remarques, consignes de modification, validation, PDF mis en page, diffusion | 2 éditions générées par l'agent, relues, révisées, validées et diffusées via GVV |
| **P3. Ouverture aux membres** | Envoi à tous les abonnés ; ouverture des propositions d'articles | Désabonnements < 5 % ; au moins une contribution publiée par édition |

## 10. Évolutions envisagées après la version 1

- Génération du brouillon sans intervention humaine : agent installé sur le serveur d'exploitation, déclenché à date fixe, résultats importés et transmis automatiquement aux relecteurs ; échecs signalés à l'administrateur.
- Alertes immédiates : information urgente détectée par l'agent (consigne de navigabilité urgente, NOTAM local) diffusée hors du cycle newsletter, par email ou SMS.
- Retours des relecteurs saisis directement dans GVV, en plus de l'email.
- Approbation par email d'un validateur distinct de l'administrateur, avec relances en l'absence de réponse.
- Relevé automatique et quotidien des réponses des relecteurs.

## 11. RGPD et conformité

- **Traitement à inscrire au registre** du club. Finalité : veille sécurité et information. Base légale : intérêt légitime (sécurité des vols, obligation DTO.GEN.210) ; consentement pour l'abonnement. Sous-traitant : Anthropic (Claude Code).
- **Abonnés** : nom, prénom et adresse email, conservés jusqu'à la désinscription.
- **Anonymisation** des REX.
- **Droit à l'image** : une photo montrant des personnes identifiables n'est publiée qu'avec leur accord, vérifié par l'administrateur avant la validation.

## 12. Questions ouvertes

Aucune à ce stade.
