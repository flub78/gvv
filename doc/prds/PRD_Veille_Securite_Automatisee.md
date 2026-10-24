# PRD : newsletter de veille sécurité et réglementaire

| | |
|---|---|
| **Version** | 0.6 (brouillon) |
| **Date** | 24/10/2026 |
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

La newsletter répond à ces quatre points : elle centralise la veille, elle est rédigée automatiquement par un agent IA à partir d'un fichier de consignes, puis relue par email et validée par un administrateur dans GVV, et chaque édition, accompagnée du rapport d'activité de l'agent, constitue une trace datée de la veille.

## 2. Objectifs

| # | Objectif | Indicateur |
|---|---|---|
| O1 | Collecter automatiquement les informations sécurité et réglementaires des sources configurées | Au moins 95 % des éléments pertinents retenus, mesuré par un test rétroactif contre une liste de référence établie par le RP |
| O2 | Produire à chaque période un brouillon de newsletter utile et agréable à lire | Validation en 30 min ou moins ; au moins 80 % des articles proposés jugés pertinents par l'administrateur qui valide |
| O3 | Ne jamais diffuser d'information fausse ou malveillante | Aucune newsletter envoyée sans validation humaine ; chaque article cite une source réelle consultable |
| O4 | Fournir une preuve de veille présentable en audit | Rapport d'activité de chaque exécution (sources consultées, résultat, documents détectés, décision et motif) et newsletters publiées, conservés au moins 36 mois |
| O5 | Permettre l'abonnement de personnes extérieures au club | Inscription/désinscription autonome ; envoi automatique à la publication |
| O6 | Valider les briques technologiques avant tout développement | Page de validation démontrant l'envoi d'email, la lecture de boîte, et l'accès à l'API IA |
| O7 | Produire le brouillon sans dépendre d'une intervention humaine | En régime établi, le brouillon est généré et envoyé aux relecteurs à la date prévue sans action humaine ; tout échec est signalé à l'administrateur |

### Hors objectifs (version 1)

- Remplacer la responsabilité du représentant du DTO et du RP pour la veille.
- Notifier des événements à l'autorité (ECCAIRS).
- Modifier automatiquement le manuel de sécurité.
- Gérer des sources payantes.
- Publier un article sans validation humaine.

## 3. Utilisateurs et acteurs

| Rôle | Besoin |
|---|---|
| **Administrateur** (administrateur de la veille, CPS ou personne désignée) | Importer ou recevoir le brouillon, le transmettre aux relecteurs, trier leurs réponses, demander une nouvelle version, valider la newsletter ; adapter les consignes de l'agent, désigner les relecteurs, fixer le calendrier, suivre les exécutions, gérer les abonnés |
| **Administrateur technique** | Installer le serveur d'exploitation, recevoir les alertes d'échec d'exécution |
| **Abonnés** (membres et sympathisants) | S'abonner, se désabonner, recevoir la newsletter par email à chaque période |
| **Agent IA** (acteur automatique) | Explorer les sources, identifier les documents nouveaux et modifiés, les trier selon les consignes, rédiger le brouillon et le rapport d'activité ; produire une nouvelle version du brouillon à partir des modifications retenues par l'administrateur |
| **Relecteurs** | Recevoir le brouillon par email, répondre par email avec des remarques, des suggestions d'articles et des images |
| **RP / représentant du DTO** | Consulter les newsletters archivées et les rapports d'activité pour l'audit |

## 4. Principes

1. **L'agent IA suit un fichier de consignes** maintenu par l'administrateur : registre des sources, critères de sélection, description du club et de sa flotte, directives de rédaction.
2. **Les consignes se pilotent depuis GVV.** Elles sont modifiables depuis l'interface d'administration, sur le serveur d'exploitation, sans machine de développement. Chaque version est conservée.
3. **La relecture se fait par email, le pilotage dans GVV.** Les relecteurs répondent à l'email reçu ; l'administrateur récupère les réponses dans GVV, décide du sort de chaque suggestion et demande une nouvelle version à l'agent IA. Aucune nouvelle version n'est produite sans sa décision.
4. **Rien n'est diffusé sans validation humaine.** La validation est une action explicite de l'administrateur dans GVV.
5. **Les notes internes ne sortent pas du club** : les remarques destinées au CPS et aux relecteurs sont supprimées avant la mise en forme PDF et la diffusion.
6. **Le mécanisme d'abonnement est indépendant** de la génération automatique et peut servir des newsletters générées manuellement.
7. **Déploiement en deux étapes.**
   - *Étape 1* : le brouillon est généré hors GVV par l'administrateur, avec un agent IA de bureau (Claude Desktop sous Windows), puis importé dans GVV qui gère la relecture, l'approbation et la diffusion. Cette étape sert à affiner les consignes et à valider la partie approbation et diffusion.
   - *Étape 2* : le brouillon est généré par un agent IA installé sur le serveur d'exploitation, déclenché automatiquement, et ses résultats sont importés automatiquement dans GVV. Le cycle de relecture, d'approbation et de diffusion est le même qu'à l'étape 1.
8. **Le rapport d'activité de l'agent fait partie de la preuve de veille.** Il accompagne chaque brouillon et reste consultable après la publication.

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

Une section vide est omise.

## 6. Périmètre fonctionnel

### 6.1 Sources

Les sources sont décrites dans le registre des sources des consignes de l'agent :

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

**F1. Génération du brouillon**

*Exigences sur le résultat d'une exécution (étapes 1 et 2) :*

- F1.1 Toutes les sources dues du registre sont explorées : boîte de collecte, sites web, y compris ceux qui nécessitent un navigateur, et flux RSS.
- F1.2 Les documents nouveaux depuis l'exécution précédente sont identifiés.
- F1.3 Les documents modifiés depuis l'exécution précédente sont identifiés, y compris quand la source ne change pas la date de publication.
- F1.4 Chaque document nouveau ou modifié est analysé selon les critères des consignes (activités du club, flotte, impact) et fait l'objet d'une décision : retenu, à vérifier ou écarté, avec un motif.
- F1.5 Le brouillon suit la structure du § 5 et ne contient que des articles fondés sur des documents réels, avec leur lien. Un document dont la date de publication n'a pas pu être établie n'est pas présenté comme une nouveauté de la période.
- F1.6 Un rapport d'activité accompagne le brouillon : chaque tentative d'accès à une source et son résultat (succès, partiel, indisponible, non accessible), les documents détectés, et pour chacun la décision et son motif, ainsi qu'un bilan de l'exécution.
- F1.7 L'inventaire des documents suivis est mis à jour à chaque exécution et sert de référence à l'exécution suivante.

*Étape 1 — génération sur un agent IA de bureau :*

- F1.8 L'administrateur télécharge depuis GVV les consignes en vigueur, l'inventaire courant et les suggestions d'articles reportées, pour les fournir à l'agent de bureau.
- F1.9 L'administrateur importe dans GVV le brouillon de newsletter d'une période. Il y joint le rapport d'activité et l'inventaire mis à jour produits par l'agent ; GVV signale leur absence. L'import crée la newsletter à l'état brouillon et enregistre l'exécution.

*Étape 2 — génération sur le serveur d'exploitation :*

- F1.10 L'exécution est déclenchée automatiquement à la date fixée par l'administrateur, sans intervention humaine.
- F1.11 L'administrateur peut aussi lancer une exécution depuis GVV.
- F1.12 Les résultats de l'exécution sont importés automatiquement dans GVV, comme à l'étape 1, puis le brouillon est transmis automatiquement aux relecteurs (F2.1). La suite du cycle est la même qu'à l'étape 1.
- F1.13 Une exécution est limitée en durée et en coût ; les plafonds sont fixés par l'administrateur.
- F1.14 Une exécution en échec, interrompue par un plafond, ou absente à la date prévue est signalée par email à l'administrateur et à l'administrateur technique.

**F2. Relecture par email**

- F2.1 L'administrateur transmet le brouillon aux relecteurs par email. L'email indique la date limite de réponse, deux jours après l'envoi par défaut.
- F2.2 Les relecteurs répondent à l'email avec des remarques, des suggestions d'articles et des images, reçues sur l'adresse de veille.
- F2.3 L'administrateur déclenche dans GVV la récupération des réponses reçues sur l'adresse de veille, en général après la date limite. Une réponse déjà récupérée ne l'est pas une seconde fois.
- F2.4 Seules les réponses provenant de relecteurs autorisés sont retenues ; les autres sont signalées à l'administrateur.
- F2.5 Pour chaque suggestion, l'administrateur décide : accepter, modifier puis accepter, ou rejeter.
- F2.6 Pour une suggestion d'article, il peut aussi la reporter à l'édition suivante. Une suggestion reportée est reproposée lors du tri de l'édition suivante et fournie à l'agent qui générera le prochain brouillon.
- F2.7 L'administrateur transmet à l'agent IA le brouillon et les modifications acceptées ; l'agent retourne une nouvelle version du brouillon, que GVV conserve avec les précédentes.
- F2.8 À la réception d'une nouvelle version, l'administrateur peut la retransmettre aux relecteurs pour un nouveau tour, ou la valider.

**F3. Validation**

- F3.1 L'administrateur peut valider le brouillon courant à tout moment : après un ou plusieurs tours de relecture, ou directement s'il n'y a pas de remarques.
- F3.2 Avant la validation, GVV signale les mentions restant à compléter dans le brouillon.
- F3.3 Aucune newsletter n'est diffusée sans validation.
- F3.4 La validation enregistre qui a validé, quand, et la version validée.

**F4. Finalisation et publication**

- F4.1 Une fois le brouillon validé, les notes internes destinées au CPS et aux relecteurs sont supprimées.
- F4.2 La newsletter est mise en forme en PDF et archivée dans l'espace accessible aux membres actifs connectés.
- F4.3 La newsletter est envoyée aux abonnés en pièce jointe par email, accompagnée d'un texte d'annonce et du lien de désinscription.

**F5. Abonnements**

- F5.1 Une URL publique permet aux personnes extérieures de s'abonner ou se désabonner à la newsletter, indépendamment du compte GVV.
- F5.2 L'inscription demande le nom, le prénom et l'adresse email. Le nom et le prénom sont utilisés pour personnaliser l'email d'envoi.
- F5.3 Désinscription en un clic depuis la newsletter ou depuis la page d'abonnement.
- F5.4 Les administrateurs club peuvent accéder à la liste des abonnés et supprimer des abonnements.
- F5.5 Ce mécanisme est indépendant de la génération automatique et peut servir des newsletters générées manuellement.

**F6. Archive et consultation**

- F6.1 Les newsletters sont stockées sous forme de PDF sur le serveur GVV.
- F6.2 Les membres actifs connectés peuvent consulter l'archive des newsletters. Cliquer sur une newsletter l'ouvre dans un onglet séparé.
- F6.3 Le RP, le représentant du DTO et l'administrateur consultent le rapport d'activité de chaque exécution, relié à la newsletter qui en est issue. Les rapports sont conservés au moins 36 mois.

**F7. Alertes immédiates (optionnelles)**

- F7.1 L'agent peut détecter dans les sources des informations urgentes (consigne de navigabilité urgente, NOTAM local, etc.) nécessitant une diffusion immédiate hors du cycle newsletter.
- F7.2 Lorsque cette option est activée, une notification immédiate est envoyée aux destinataires désignés par email ou SMS.
- F7.3 L'administrateur active ou désactive cette option et choisit le canal de notification (email, SMS, ou les deux) pour chaque destinataire.

**F8. Administration**

- F8.1 L'administrateur modifie les consignes de l'agent depuis GVV : registre des sources, critères de sélection, description du club et de la flotte, directives de rédaction.
- F8.2 Chaque modification des consignes crée une nouvelle version ; les versions précédentes restent consultables et peuvent être restaurées. Chaque exécution indique la version des consignes utilisée.
- F8.3 Il désigne les relecteurs autorisés, et les destinataires des alertes immédiates avec leur canal de notification préféré (email, SMS).
- F8.4 Il fixe la périodicité de la newsletter, la date d'exécution, le modèle d'IA et les plafonds de durée et de coût d'une exécution.
- F8.5 Il suit les exécutions : date, origine (bureau, calendrier, lancement manuel), statut, durée, coût, rapport d'activité, newsletter produite.
- F8.6 Il peut accéder à la liste des abonnés et supprimer des abonnements.

## 7. Workflow global

```
Étape 1                                  Étape 2
[Administrateur]                         [Calendrier GVV ou « Lancer maintenant »]
 │ télécharge consignes + inventaire      │
 ▼                                        ▼
[Agent IA de bureau]                     [Agent IA du serveur d'exploitation]
 │ exploration, tri, rédaction            │ exploration, tri, rédaction
 ▼                                        ▼
[Import manuel dans GVV]                 [Import automatique dans GVV]
 └────────────────┬───────────────────────┘
                  ▼
[Brouillon vN + rapport d'activité]
     │
     ├──────────── pas de remarques ─────────────┐
     ▼                                            │
[Admin] ── transmet aux relecteurs (email)        │
     │                                            │
     ▼                                            │
[Relecteurs] ── répondent par email (veille@)     │
     │                                            │
     ▼                                            │
[Admin, après la date limite]                     │
     │ récupère les réponses dans GVV             │
     │ accepte / modifie / rejette / reporte      │
     ▼                                            │
[Agent IA] ── brouillon + modifications acceptées │
     │ retourne la version vN+1                   │
     ▼                                            │
[Admin] ── nouveau tour de relecture, ou ─────────┤
                                                  ▼
                                     [Admin valide le brouillon]
                                                  │
                                                  ▼
                              suppression des notes internes → PDF
                                                  │
                                                  ▼
[Archive GVV] + [Envoi aux abonnés]
```

## 8. Architecture technique (indicative)

- **Agent IA, étape 1** : Claude Desktop sous Windows, avec accès au disque et navigation web.
- **Agent IA, étape 2** : Claude Code en mode non interactif, avec navigateur, installé sur le serveur d'exploitation (machine virtuelle Oracle Cloud sous Ubuntu) et déclenché par un timer.
- **Environnements** : le mécanisme de l'étape 2 est mis au point sur la machine de développement, puis le serveur d'exploitation est configuré de la même manière.
- **Consignes** : document Markdown versionné dans GVV.
- **Cycle de relecture** : récupération à la demande des réponses reçues sur l'adresse de veille ; tri et décisions dans GVV ; nouvelle version produite par l'API IA.
- **Envoi** : SMTP du domaine, email personnalisé avec pièce jointe PDF et lien de désinscription.
- **Archive** : fichiers PDF et rapports d'activité sur le serveur GVV, accessibles via l'interface.
- **Application** : module GVV pour l'administration, le pilotage de l'agent, la validation, les abonnements et l'archive.

## 9. Plan de livraison

| Phase | Contenu | Critère de sortie |
|---|---|---|
| **P0. Validation infrastructure** | Page de validation : envoi email, lecture boîte, API IA | Toutes les briques fonctionnelles démontrées |
| **P1. Abonnements** | URL publique d'abonnement/désinscription, gestion admin, archive PDF membres | Un abonné peut s'inscrire, recevoir une newsletter envoyée manuellement, se désabonner |
| **P2. Étape 1 : brouillon de bureau, cycle GVV** | Consignes dans GVV, import du brouillon d'une période, rapport d'activité, transmission aux relecteurs, récupération et tri des réponses, nouvelle version par l'agent IA, validation, finalisation, diffusion | 2 éditions générées avec l'agent de bureau, puis relues, validées et diffusées via GVV |
| **P3. Étape 2 : agent sur le serveur** | Exécution automatique sur le serveur, pilotage depuis GVV, import et envoi automatiques, alertes d'échec ; mise au point sur la machine de développement puis mise en production | 2 éditions générées sans intervention humaine, satisfaisant F1.1 à F1.7 |
| **P4. Ouverture aux membres** | Envoi à tous les abonnés ; ouverture des propositions d'articles | Désabonnements < 5 % ; au moins une contribution publiée par édition |

## 10. Évolutions envisagées après la version 1

- Retours des relecteurs saisis directement dans GVV, en plus de l'email.
- Approbation par email d'un validateur distinct de l'administrateur, avec relances en l'absence de réponse.
- Récupération automatique et quotidienne des réponses des relecteurs.

## 11. RGPD et conformité

- **Traitement à inscrire au registre** du club. Finalité : veille sécurité et information. Base légale : intérêt légitime (sécurité des vols, obligation DTO.GEN.210) ; consentement pour l'abonnement. Sous-traitant : Anthropic (API, Claude Code et Claude Desktop).
- **Abonnés** : nom, prénom et adresse email, conservés jusqu'à la désinscription.
- **Anonymisation** des REX.
- **Droit à l'image** : une photo montrant des personnes identifiables n'est publiée qu'avec leur accord, vérifié par l'administrateur avant la validation.

## 12. Questions ouvertes

- **Capacité du serveur d'exploitation** : la forme de la machine virtuelle Oracle Cloud (Ampere ARM ou AMD micro) et sa mémoire disponible conditionnent l'exécution d'un navigateur par l'agent de l'étape 2. À vérifier avant P3.
