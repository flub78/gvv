# PRD : newsletter de veille sécurité et réglementaire

| | |
|---|---|
| **Version** | 0.5 (brouillon) |
| **Date** | 22/10/2026 |
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

La newsletter répond à ces quatre points : elle centralise la veille, elle est rédigée automatiquement par un agent IA à partir d'un fichier de contexte, puis relue et validée par des humains via email ou interface GVV, et chaque édition constitue une trace datée de la veille.

## 2. Objectifs

| # | Objectif | Indicateur |
|---|---|---|
| O1 | Collecter automatiquement les informations sécurité et réglementaires des sources configurées | Au moins 95 % des éléments pertinents retenus, mesuré par un test rétroactif contre une liste de référence établie par le RP |
| O2 | Produire à chaque période un brouillon de newsletter utile et agréable à lire | Validation en 30 min ou moins ; au moins 80 % des articles proposés jugés pertinents par le validateur |
| O3 | Ne jamais diffuser d'information fausse ou malveillante | Aucune newsletter envoyée sans validation humaine |
| O4 | Fournir une preuve de veille présentable en audit | Journal horodaté de chaque information : source, date, décision, édition de diffusion ; conservé au moins 36 mois |
| O5 | Permettre l'abonnement de personnes extérieures au club | Inscription/désinscription autonome ; envoi automatique à la publication |
| O6 | Valider les briques technologiques avant tout développement | Page de validation démontrant l'envoi d'email, la lecture de boîte, et l'accès à l'API IA |

### Hors objectifs (version 1)

- Remplacer la responsabilité du représentant du DTO et du RP pour la veille.
- Notifier des événements à l'autorité (ECCAIRS).
- Modifier automatiquement le manuel de sécurité.
- Gérer des sources payantes.
- Publier un article sans validation humaine.

## 3. Utilisateurs et acteurs

| Rôle | Besoin |
|---|---|
| **Administrateur** | Configurer les sources, désigner les relecteurs et validateurs, gérer les abonnés |
| **Abonnés** (membres et sympathisants) | S'abonner, se désabonner, recevoir la newsletter par email à chaque période |
| **Agent IA** (acteur automatique) | Analyser périodiquement les sources, filtrer les informations pertinentes, rédiger le brouillon, intégrer les remarques des relecteurs, générer la version finale |
| **Relecteurs** | Recevoir le brouillon par email, envoyer des remarques, des articles supplémentaires et des images par retour d'email ou via l'interface GVV |
| **Validateur** (CPS, RP en suppléance) | Recevoir la version finale du cycle de relecture, l'approuver ou la modifier par email ou via l'interface GVV |
| **RP / représentant du DTO** | Consulter les newsletters archivées pour l'audit |

## 4. Principes

1. **L'agent IA s'appuie sur un fichier de contexte** fourni par l'administrateur : liste des sources, critères de sélection, directives de rédaction, description du club.
2. **Le cycle de relecture est piloté par email** : les relecteurs répondent directement à l'email reçu ; l'agent IA consolide les retours d'une journée avant de créer une nouvelle version.
3. **Rien n'est diffusé sans validation humaine.** L'approbation est faite par le validateur via email ou l'interface GVV, après signature vérifiée.
4. **L'agent nettoie la version finale** : il supprime les remarques internes et la section de synthèse éditoriale avant publication.
5. **Le mécanisme d'abonnement est indépendant** de la génération automatique et peut servir des newsletters générées manuellement.

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

Les sources sont configurées par l'administrateur dans un fichier de contexte transmis à l'agent :

| Type | Exemples |
|---|---|
| Boîte de collecte | `veille@aeroclub-abbeville.fr` |
| Flux RSS | EASA, BEA, DSAC, fédérations |
| Sites web publics | veilledoc.dsac.fr, pages d'actualité DSAC, Légifrance |

### 6.2 Fonctionnalités

**F0. Validation de l'infrastructure**

Avant tout développement fonctionnel, une page de validation démontre que les briques technologiques sont correctement configurées :

- F0.1 **Email sortant** : envoi d'un email depuis `veille@aeroclub-abbeville.fr` et confirmation de réception.
- F0.2 **Lecture de boîte mail** : connexion à la boîte de réception, comptage des emails reçus depuis une date donnée, lecture des sujets et repérage d'emails par pattern dans le titre, lecture du contenu, accès aux pièces jointes.
- F0.3 **API IA** : réception d'une réponse à une requête simple, transmission de fichiers de contexte et d'images ou PDF, génération de documents en markdown ou PDF.

**F1. Génération automatique du brouillon**

- F1.1 L'agent est déclenché automatiquement chaque mois par un cron.
- F1.2 Il reçoit un fichier de contexte contenant la liste des sources, les critères de sélection, les directives de rédaction et la description du club.
- F1.3 Il analyse les sources pour identifier les nouveaux documents publiés depuis la dernière édition.
- F1.4 Il rédige un brouillon de newsletter selon la structure du § 5.
- F1.5 Un cron séparé, sans IA, surveille la création du brouillon et alerte l'administrateur en cas d'absence.

**F2. Relecture par email**

- F2.1 Une fois le brouillon créé, il est envoyé aux relecteurs par email.
- F2.2 Les relecteurs peuvent répondre à l'email avec des remarques, des articles supplémentaires et des images.
- F2.3 Ils peuvent également soumettre leurs retours via l'interface GVV.
- F2.4 Un agent IA consolide l'ensemble des remarques reçues dans la journée et élabore une nouvelle version intégrant ces retours.
- F2.5 La nouvelle version est renvoyée aux relecteurs pour un nouveau cycle.
- F2.6 Seules les remarques provenant de relecteurs autorisés sont prises en compte (vérification par adresse email ou authentification GVV).

**F3. Validation**

- F3.1 Une fois la phase de relecture terminée, la dernière version est envoyée aux validateurs.
- F3.2 Les validateurs peuvent encore modifier la newsletter.
- F3.3 L'un d'entre eux approuve la version finale, par email ou via l'interface GVV.
- F3.4 L'approbation par email doit provenir d'une adresse autorisée ; l'approbation via GVV nécessite une authentification.
- F3.5 Aucune newsletter n'est envoyée sans approbation explicite d'un validateur.

**F4. Finalisation et publication**

- F4.1 Une fois la version approuvée, l'agent IA supprime les remarques internes et la section de synthèse éditoriale, puis génère la version définitive.
- F4.2 La newsletter définitive est archivée sur le serveur dans un espace accessible aux membres actifs connectés (format PDF).
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

**F7. Alertes immédiates (optionnelles)**

- F7.1 L'agent peut détecter dans les sources des informations urgentes (consigne de navigabilité urgente, NOTAM local, etc.) nécessitant une diffusion immédiate hors du cycle newsletter.
- F7.2 Lorsque cette option est activée, une notification immédiate est envoyée aux validateurs par email ou SMS.
- F7.3 L'administrateur active ou désactive cette option et choisit le canal de notification (email, SMS, ou les deux) pour chaque validateur.

**F8. Administration**

- F8.1 L'administrateur configure le fichier de contexte de l'agent : liste des sources, critères de sélection, directives de rédaction, description du club.
- F8.2 Il désigne les relecteurs autorisés et les validateurs, avec leur canal de notification préféré (email, SMS) pour les alertes immédiates.
- F8.3 Il fixe la périodicité de la newsletter.
- F8.4 Il peut accéder à la liste des abonnés et supprimer des abonnements.

## 7. Workflow global

```
[Cron mensuel]
     │
     ▼
[Agent IA] ── fichier de contexte (sources, critères, directives)
     │ analyse des sources, rédaction
     ▼
[Brouillon] ── envoyé aux relecteurs par email
     │
     ▼
[Relecteurs] ── réponses par email ou interface GVV
     │              (remarques, articles, images)
     ▼
[Agent IA] ── consolidation des retours (1 fois/jour)
     │ nouvelle version
     ▼
[Nouvelle version] ── renvoyée aux relecteurs
     │ (plusieurs cycles possibles)
     ▼
[Validateurs] ── reçoivent la dernière version
     │ modifications possibles
     │ approbation (email ou GVV)
     ▼
[Agent IA] ── supprime remarques internes
     │ génère version définitive (PDF)
     ▼
[Archive GVV] + [Envoi aux abonnés]
```

## 8. Architecture technique (indicative)

- **Agent IA** : Claude Code en mode non interactif (`claude -p`) ou Agent SDK, lancé par un timer systemd mensuel.
- **Fichier de contexte** : fichier Markdown/JSON maintenu par l'administrateur, contenant les sources, critères et directives.
- **Lecture des sources** : accès direct aux flux RSS et sites web publics ; lecture de la boîte mail IMAP.
- **Cycle de relecture** : gestion des threads email entrants ; interface GVV pour la soumission de retours.
- **Surveillance** : cron séparé sans IA pour détecter l'absence de brouillon.
- **Envoi** : SMTP du domaine, email personnalisé avec pièce jointe PDF et lien de désinscription.
- **Archive** : fichiers PDF sur le serveur GVV, accessibles via l'interface membres.
- **Application** : module GVV pour l'administration, la validation, les abonnements et l'archive.

## 9. Plan de livraison

| Phase | Contenu | Critère de sortie |
|---|---|---|
| **P0. Validation infrastructure** | Page de validation : envoi email, lecture boîte, API IA | Toutes les briques fonctionnelles démontrées |
| **P1. Abonnements** | URL publique d'abonnement/désinscription, gestion admin, archive PDF membres | Un abonné peut s'inscrire, recevoir une newsletter envoyée manuellement, se désabonner |
| **P2. MVP génération automatique** | Cron mensuel, agent IA, cycle de relecture email, validation, finalisation | 2 éditions générées et validées automatiquement |
| **P3. Ouverture aux membres** | Envoi à tous les abonnés ; ouverture des propositions d'articles | Désabonnements < 5 % ; au moins une contribution publiée par édition |

## 10. RGPD et conformité

- **Traitement à inscrire au registre** du club. Finalité : veille sécurité et information. Base légale : intérêt légitime (sécurité des vols, obligation DTO.GEN.210) ; consentement pour l'abonnement. Sous-traitant : Anthropic (API).
- **Abonnés** : nom, prénom et adresse email, conservés jusqu'à la désinscription.
- **Anonymisation** des REX.
- **Droit à l'image** : une photo montrant des personnes identifiables n'est publiée qu'avec leur accord, vérifié par le validateur.

## 11. Questions ouvertes

Aucune question ouverte à ce stade.
