# PRD : newsletter de veille sécurité et réglementaire

| | |
|---|---|
| **Version** | 0.4 (brouillon) |
| **Date** | 15/10/2026 |
| **Auteur** | Frédéric Peignot, avec Claude |
| **Statut** | À valider |
| **Premier client** | Aéroclub d'Abbeville-Buigny-Baie de Somme (ACS), DTO FR.DTO.0645 |

---

## 1. Contexte et problème

Un DTO doit assurer une veille réglementaire et sécurité (DTO.GEN.210, § B-9 du manuel de sécurité) et pouvoir la prouver en audit. L'audit DSAC de juin 2026 a d'ailleurs relevé une remarque sur ce point (#1406200).

Aujourd'hui, cette veille est manuelle et dispersée :

- **Les informations arrivent par de nombreux canaux** : la boîte `info@aeroclub-abbeville.fr` (IONOS), les boîtes personnelles du président, du RP et du CPS, les sites et les flux des organismes officiels (veilledoc.dsac.fr, EASA, BEA, Légifrance), les lettres et les espaces licenciés des fédérations (FFA, FFVP, FFPLUM), et des REX transmis par des pilotes ou des amis.
- **Personne n'a le temps de tout lire, trier et résumer** à chaque période.
- **La diffusion aux membres est irrégulière** et peu attractive.
- **La preuve de veille n'est pas archivée** de façon structurée.

La newsletter répond à ces quatre points : elle centralise la veille, elle est rédigée automatiquement puis validée par un humain, elle est rendue attractive par des REX, des articles de membres et un quiz, et chaque édition constitue une trace datée de la veille.

## 2. Objectifs

| # | Objectif | Indicateur |
|---|---|---|
| O1 | Collecter automatiquement les informations sécurité et réglementaires de toutes les sources | Au moins 95 % des éléments pertinents retenus, mesuré par un test rétroactif contre une liste de référence établie par le RP |
| O2 | Produire à chaque période une newsletter utile et agréable à lire pour les membres | Validation en 30 min ou moins ; au moins 80 % des articles proposés jugés pertinents par le validateur |
| O3 | Ne jamais diffuser d'information fausse ou malveillante | 0 lien hors liste autorisée ; aucune newsletter envoyée sans validation humaine |
| O4 | Fournir une preuve de veille présentable en audit | Journal horodaté de chaque information : source, date, décision, édition de diffusion ; conservé au moins 36 mois |
| O5 | Garantir la sécurité et la confidentialité | L'agent IA ne peut que lire les sources et créer des fichiers de proposition : il ne peut ni envoyer, ni supprimer, ni modifier un mail, ni ouvrir une URL libre ; le traitement est déclaré au registre RGPD |
| O6 | Faire participer les membres | Au moins une contribution de membre publiée par édition, après la phase de lancement |

### Hors objectifs (version 1)

- Remplacer la responsabilité du représentant du DTO et du RP pour la veille.
- Notifier des événements à l'autorité (ECCAIRS) : le système **signale** les événements à notifier, il ne les déclare pas.
- Modifier automatiquement le manuel de sécurité : le système **propose** des modifications, il ne les fait pas.
- Gérer des sources payantes (à prévoir en V2 si besoin).
- Publier un article sans validation humaine.

## 3. Utilisateurs et acteurs

| Rôle | Besoin |
|---|---|
| **Administrateur** | Définir les sources et leurs identifiants d'accès, la périodicité de la newsletter, la description du DTO, les contributeurs autorisés ; gérer les abonnés (ajout, liste noire, validation des abonnements en option) ; surveiller le fonctionnement |
| **Abonnés** (membres et sympathisants) | S'abonner, se désabonner, recevoir la newsletter par email à chaque période |
| **Agent IA** (acteur automatique) | Analyser périodiquement les sources, filtrer les informations pertinentes, rédiger les articles et la proposition de newsletter |
| **Validateur** (CPS, RP en suppléance) | Être informé qu'une proposition est disponible ; la valider globalement, valider ou rejeter chaque article, ou la modifier |
| **Contributeurs** (personnes informées du mécanisme et, en option, abonnés) | Proposer un article par simple email, éventuellement illustré de photos |
| **RP / représentant du DTO** | Voir les informations réglementaires qui ont un impact sur le DTO ; consulter le journal de veille pour l'audit |
| **Auditeur DSAC** (indirect) | Constater que la veille existe, qu'elle est tracée et suivie d'effets |

## 4. Principes

1. **L'agent IA n'a que deux capacités** : consulter les sources en lecture seule et créer des fichiers de proposition dans un dossier dédié. Même compromis (par exemple par un mail piégé), il ne peut ni supprimer un mail, ni envoyer quoi que ce soit, ni agir ailleurs que dans ce dossier.
2. **Les pouvoirs sont limités par les outils, pas par les consignes.** La lecture seule est garantie par le serveur MCP, pas par le prompt de l'agent.
3. **Rien n'est diffusé sans validation humaine.** L'envoi est réalisé par un composant sans IA, déclenché uniquement par le validateur.
4. **Pas de lecture web libre.** L'agent ne lit que des sources fixées à l'avance ; il ne peut pas ouvrir une URL de son choix.
5. **Les garanties fortes sont déterministes** : contrôles par script, isolement des utilisateurs système, filtrage réseau. L'IA améliore la qualité, elle ne porte pas la sécurité.

## 5. Contenu de la newsletter

Chaque édition comprend, dans cet ordre :

1. **Un titre.**
2. **La période couverte**, selon la périodicité fixée par l'administrateur, par exemple « mars-avril 2027 ».
3. **Une introduction** présentant la newsletter : elle rassemble les informations sur les évolutions réglementaires et la sécurité des vols, elle est préparée avec une assistance automatisée et validée par le club.
4. **Veille réglementaire** : une série d'articles. Chaque article contient :
   - un titre ;
   - une synthèse de l'information ;
   - une description de sa source (émetteur, nature du document, date, lien).
5. **REX et technique** : au choix de l'agent IA pour l'édition (F4.2),
   - une sélection de REX ou de rapports d'accident, par exemple un REX planeur, un REX avion et un REX ULM ;
   - ou un article technique sur une des activités pratiquées au club.
6. **Articles des contributeurs** : les articles rédigés à partir des propositions reçues par email (F5), illustrés des photos jointes.
7. **Quiz** : quelques questions courtes, soit sur l'article technique de l'édition, soit issues des programmes théoriques PPL, SPL et théorique ULM. Les réponses figurent en fin de newsletter.
8. **Pied de page** : mention « Synthèse indicative, préparée avec une assistance automatisée et validée par le club. Seuls les textes officiels font foi. », lien de désinscription en un clic.

Une section vide est omise (par exemple aucune contribution reçue sur la période).

## 6. Périmètre fonctionnel

### 6.1 Sources

| Type | Exemples | Accès |
|---|---|---|
| Boîte de collecte | `veille@aeroclub-abbeville.fr` sur ratus80 (Dovecot) | Lecture seule via le serveur MCP mail |
| Copie de info@ | Tout le courrier de `info@` (IONOS), copié vers veille@ par `imapsync` | Idem |
| Contributions | Emails envoyés à la boîte surveillée avec « proposition d'article » dans le sujet, par un contributeur autorisé (F5) | Idem |
| Boîtes personnelles | RP, président, CPS | Règles de transfert vers veille@, filtrées par expéditeur, définies par chaque titulaire |
| Flux RSS | EASA, BEA, DSAC, fédérations (selon disponibilité) | Lecture seule via le serveur MCP RSS, à partir d'une liste fermée |
| Sites web publics | veilledoc.dsac.fr, pages d'actualité DSAC, Légifrance | Lecture seule via le serveur MCP web, à partir d'une liste fermée de pages |
| Sites web avec identification | Espaces licenciés des fédérations (REX FFA, FFVP), espaces DTO | Idem, avec les identifiants saisis par l'administrateur, jamais accessibles à l'agent |

La liste des sources et leurs identifiants d'accès sont définis par l'administrateur (F11).

### 6.2 Fonctionnalités

**F1. Accès en lecture seule aux sources (serveurs MCP, sans IA)**

Les serveurs MCP sont les seules interfaces entre l'agent IA et les sources.

- F1.1 **Serveur MCP mail** : il permet de lister les mails d'une période, de lire leur texte normalisé (HTML retiré, métadonnées : expéditeur, date, sujet, `Message-ID`) et le texte extrait de leurs pièces jointes.
- F1.2 Il garantit la lecture seule : la boîte est ouverte sans droit d'écriture, les mails ne sont jamais marqués lus, déplacés ou supprimés, et aucun outil d'écriture, d'envoi ou de suppression n'est exposé.
- F1.3 **Serveur MCP RSS** : il permet de lister les flux configurés et de lire leurs entrées sur une période, à partir d'une archive locale relevée périodiquement.
- F1.4 **Serveur MCP web** : il permet de lister les pages configurées et de lire leur contenu et leurs changements sur une période, à partir de relevés périodiques archivés localement. Pour les sites avec identification, la connexion est faite par la collecte avec les identifiants de l'administrateur ; l'agent ne voit que le texte des pages relevées.
- F1.5 Aucun outil n'accepte une URL fournie par l'agent : seules les sources configurées sont accessibles.
- F1.6 Les serveurs acceptent une période passée, pour le test rétroactif : ils ne renvoient alors que les éléments datés de cette période.

**F2. Pièces jointes (sans IA)**
- F2.1 Seuls les types autorisés sont traités (PDF, DOCX, ODT, XLSX, PNG, JPG) dans une taille maximale configurable (20 Mo par défaut).
- F2.2 Chaque pièce jointe est analysée par un antivirus ; une pièce infectée n'est pas exposée à l'agent et une alerte est envoyée à l'administrateur.
- F2.3 Les documents sont convertis en texte dans un environnement isolé, sans réseau.
- F2.4 Les photos sont mises à disposition comme illustrations : l'agent les désigne par leur identifiant, il ne manipule pas leur contenu. Elles sont redimensionnées et leurs métadonnées (EXIF, géolocalisation) sont supprimées avant publication.
- F2.5 L'original de chaque pièce jointe retenue est conservé avec son empreinte SHA-256, comme preuve de veille.

**F3. Analyse et filtrage par l'agent IA**
- F3.1 L'agent est lancé périodiquement selon un calendrier fixé par l'administrateur (par défaut une fois par semaine), et à la demande pour les tests.
- F3.2 À chaque exécution, il parcourt les nouveaux éléments des sources et retient ceux qui concernent l'évolution de la réglementation ou la sécurité des vols, pour les activités du club (avion, planeur, ULM).
- F3.3 Pour chaque information pertinente, il rédige une synthèse et l'ajoute comme article de la proposition de newsletter de la période en cours.
- F3.4 Il ne crée pas de doublon : une information déjà traitée dans la période, ou déjà publiée, n'est pas reprise.
- F3.5 Il signale à part, sans les publier :
  - les informations qui pourraient appeler une notification à l'autorité (§ H-6 du manuel) ;
  - les informations qui pourraient appeler une modification du manuel, avec le chapitre concerné.

**F4. Rédaction de la proposition de newsletter par l'agent IA**
- F4.1 En fin de période, selon la périodicité fixée par l'administrateur, l'agent complète la proposition : titre, période couverte, introduction, sélection de REX ou article technique, quiz (§ 5).
- F4.2 L'agent choisit entre une sélection de REX et un article technique selon ce qui lui semble le plus pertinent pour la période. Son choix s'appuie sur la description des activités et de l'historique du DTO saisie par l'administrateur (F11.4).
- F4.3 Les articles de veille sont classés par impact pour le club, pas par date.
- F4.4 Les REX sont anonymisés : pas de nom, pas d'immatriculation, pas de lieu précis, sauf accord de l'auteur. Ils sont présentés comme des témoignages, pas comme des règles.
- F4.5 Le quiz comprend de 3 à 6 questions, avec leurs réponses et, pour chacune, la référence : article technique de l'édition, ou programme théorique PPL, SPL ou théorique ULM.
- F4.6 Les liens cités doivent provenir des sources utilisées.
- F4.7 Les propositions sont enregistrées comme fichiers dans un dossier dédié. L'agent ne peut que **créer** des fichiers dans ce dossier : il ne peut ni écraser, ni supprimer un fichier existant. Le nombre de fichiers créés par exécution est plafonné.

**F5. Propositions d'articles par email**
- F5.1 Un contributeur autorisé propose un article en écrivant à la boîte surveillée avec les mots « proposition d'article » dans le sujet (sans tenir compte des majuscules ni des accents).
- F5.2 Sont autorisées les adresses de la liste des contributeurs, c'est-à-dire des personnes informées du mécanisme, tenue par l'administrateur. En option, tous les abonnés sont aussi autorisés. Une adresse en liste noire n'est jamais autorisée.
- F5.3 Une proposition venant d'une adresse non autorisée, ou dont l'authenticité de l'expéditeur n'est pas confirmée (SPF, DKIM, DMARC quand ils sont disponibles), n'est pas transmise à l'agent. Elle est tracée et l'administrateur peut la consulter.
- F5.4 L'agent rédige l'article à partir du corps de l'email, en respectant le propos de l'auteur ; il peut le raccourcir et le mettre en forme.
- F5.5 Les photos jointes à l'email illustrent l'article.
- F5.6 L'article mentionne son auteur, sauf demande contraire de celui-ci.
- F5.7 Un accusé de réception est envoyé au contributeur autorisé par un composant sans IA, puis il est informé de la publication ou du rejet de son article.
- F5.8 Une proposition reçue trop tard pour la période en cours est reportée à l'édition suivante.

**F6. Contrôles automatiques (sans IA)**

Ils sont appliqués à chaque proposition créée par l'agent, puis à nouveau après chaque modification du validateur et avant l'envoi.

- F6.1 La proposition respecte le format attendu (sections du § 5, champs obligatoires de chaque article).
- F6.2 Liens : seules sont acceptées les URL présentes dans les sources citées **et** appartenant à la liste de domaines autorisés (annexe A).
- F6.3 Chaque article de veille cite au moins une source existante dans la période.
- F6.4 La taille est limitée : nombre maximal d'articles par section et longueur maximale par champ.
- F6.5 Les données personnelles résiduelles (adresses mail, téléphones, immatriculations F-XXXX) sont repérées et signalées au validateur ; elles bloquent l'envoi tant qu'elles ne sont pas levées ou justifiées (auteur d'une contribution, par exemple).
- F6.6 Une liste de motifs suspects est contrôlée : texte invisible, caractères de contrôle, sollicitations du type « cliquez », « identifiants », « virement ».
- F6.7 Les illustrations référencées existent et ont passé l'antivirus (F2).

**F7. Validation**
- F7.1 Le validateur est informé par email qu'une nouvelle proposition de newsletter est disponible.
- F7.2 L'écran de validation présente la newsletter mise en page, et pour chaque article ses sources et le résultat des contrôles F6. Les signalements F3.5 sont présentés à part.
- F7.3 Le validateur peut :
  - valider la newsletter globalement ;
  - valider ou rejeter chaque article individuellement ;
  - modifier la proposition : texte d'un article, ordre, ajout ou suppression d'un article, remplacement d'une question du quiz, choix des illustrations.
- F7.4 Seuls les articles validés sont publiés. Un article rejeté reste tracé dans le journal (F10) avec la raison du rejet.
- F7.5 Les contrôles F6 sont relancés après chaque modification ; l'envoi est impossible tant qu'un contrôle bloquant échoue.
- F7.6 Une proposition non validée sous 15 jours déclenche une relance au validateur et à son suppléant.

**F8. Diffusion (sans IA)**
- F8.1 L'envoi n'est déclenché que par l'action de validation du validateur.
- F8.2 La newsletter est mise en page avec un modèle fixe (HTML et texte), photos comprises.
- F8.3 Les destinataires viennent uniquement de la liste des abonnés actifs (confirmés, validés si l'option est active, hors liste noire), jamais de la proposition. Le sujet suit un format fixe, du type `[Veille sécurité ACS] <période>`.
- F8.4 Chaque envoi comprend un lien de désinscription en un clic.
- F8.5 Chaque édition est archivée et consultable (page web ou PDF) sur le site de gestion.

**F9. Abonnements**
- F9.1 Inscription par double confirmation (opt-in).
- F9.2 En option (F11.6), l'abonnement doit être validé par l'administrateur. Il comporte alors une adresse email et un nom, et l'abonné n'est actif qu'après validation. Le demandeur est informé de l'acceptation ou du refus.
- F9.3 Désinscription en un clic depuis la newsletter, ou depuis la page d'abonnement.
- F9.4 Une adresse en liste noire ne peut pas s'abonner ; sa demande est refusée sans révéler la raison.
- F9.5 Version 1 : liste propre au service. Version 2 : synchronisation avec les membres GVV.

**F10. Journal de veille (pour l'audit)**
- F10.1 Pour chaque information : date de réception, source, décision de l'agent (retenue ou non, avec la raison), décision du validateur, édition de diffusion, suite donnée (aucune, information, modification du manuel, notification).
- F10.2 Pour chaque édition : propositions successives, résultats des contrôles, modifications et validation (qui et quand), date d'envoi, nombre de destinataires.
- F10.3 Export PDF ou CSV par période, présentable à la DSAC.
- F10.4 Conservation d'au moins 36 mois (cohérent avec l'AltMOC Alt/26/0006).

**F11. Administration**
- F11.1 **Sources** : l'administrateur ajoute, modifie, désactive les sources (boîtes mail, flux RSS, pages web publiques ou avec identification) et peut tester l'accès à chacune.
- F11.2 **Identifiants d'accès** : il saisit les identifiants des boîtes et des sites. Ils sont stockés chiffrés, ne sont jamais réaffichés en clair et ne sont accessibles ni à l'agent ni au validateur.
- F11.3 **Périodicité** : il fixe la périodicité de la newsletter (par exemple mensuelle, bimestrielle, trimestrielle), la date de clôture de chaque période et la fréquence d'exécution de l'agent.
- F11.4 **Description du DTO** : il saisit une description des activités du DTO (avion, planeur, ULM, types de formation, terrain) et de son historique (incidents, thèmes de sécurité récurrents). Elle est fournie à l'agent pour orienter le filtrage et le choix entre REX et article technique.
- F11.5 **Contributeurs** : il tient la liste des contributeurs autorisés et choisit si les abonnés sont également autorisés à contribuer.
- F11.6 **Abonnés** : il peut ajouter ou retirer un abonné, mettre une adresse en liste noire ou l'en retirer, et activer l'option de validation des abonnements. Il valide ou refuse les demandes en attente.
- F11.7 **Validateurs** : il désigne le validateur principal et son suppléant.
- F11.8 **Supervision** : il consulte les journaux d'exécution de l'agent, les propositions non autorisées (F5.3), les alertes antivirus et la consommation du budget.
- F11.9 Chaque modification de configuration est tracée (qui, quand, quoi), sans enregistrer les identifiants.

## 7. Exigences de sécurité

| # | Exigence |
|---|---|
| S1 | **Séparation des utilisateurs système** : un utilisateur pour les serveurs MCP et la collecte, un pour l'agent IA, un pour l'application de validation et la diffusion. Chacun n'a que ses propres secrets. |
| S2 | **Agent IA** : outils intégrés désactivés ; seuls les serveurs MCP de lecture (mail, RSS, web) et l'outil de création de proposition sont disponibles ; tout autre appel est refusé sans demande de confirmation ; durée et coût plafonnés par exécution. |
| S3 | **Lecture seule garantie par le serveur MCP mail** : accès à la boîte sans droit d'écriture ; aucun outil d'envoi, de suppression, de déplacement ou de marquage n'existe. |
| S4 | **Écriture limitée au dossier des propositions** : création de nouveaux fichiers uniquement, sans écrasement ni suppression, avec un nom choisi par le serveur et un quota par exécution. |
| S5 | Les serveurs MCP tournent comme services locaux sous leur propre utilisateur, avec un jeton d'accès. Ils ne sont pas lancés par l'agent, pour qu'il ne puisse pas lire leurs secrets. |
| S6 | **Pas de lecture web libre** : aucun outil n'accepte d'URL fournie par l'agent ; les flux RSS et les pages web viennent d'une liste fermée relevée par la collecte. |
| S7 | **Réseau sortant** : l'agent ne peut joindre que l'API du modèle d'IA et les serveurs MCP locaux. Seule la collecte peut joindre les sources ; seul le composant de diffusion peut joindre le serveur SMTP. |
| S8 | Aucun identifiant (IMAP, SMTP, sites web) n'est accessible à l'agent. Les identifiants des sources sont stockés chiffrés et ne sont lisibles que par la collecte. |
| S9 | Le contenu des sources est transmis comme une **donnée délimitée**, avec la consigne d'ignorer toute instruction qu'il contient. Protection secondaire : S1 à S8 suffisent à elles seules. |
| S10 | Les pièces jointes passent par l'antivirus, sont filtrées par type et converties dans un environnement isolé (F2). |
| S11 | Chaque exécution de l'agent est journalisée (outils appelés, refus, coût). Le contenu n'est pas conservé au-delà de 30 jours. |
| S12 | Un budget mensuel global plafonné coupe l'agent en cas de dépassement. |

**Modèle de menace résiduel :**
- un agent compromis (par exemple par un mail ou une contribution piégés) peut lire les sources de la période et écrire une mauvaise proposition. Il ne peut ni envoyer, ni supprimer ou modifier un mail, ni modifier une proposition existante, ni joindre une URL externe ;
- une mauvaise proposition est arrêtée par les contrôles F6 et par la validation humaine, qui reste obligatoire ;
- une adresse d'expéditeur peut être usurpée, et un contributeur autorisé peut lui-même transmettre un contenu piégé : une contribution reste un contenu non fiable, publiée seulement après validation humaine ;
- les identifiants des sites avec identification sont la donnée la plus sensible du service : ils ne sont jamais exposés à l'agent (S8), et un compte dédié en lecture seule est utilisé quand le site le permet.

## 8. Architecture technique (indicative)

### 8.1 Vue d'ensemble

```
info@ (IONOS) ──imapsync──┐
contributions ────────────┼──► veille@ (Dovecot, ratus80)
transferts personnels ────┘
flux RSS (liste fermée) ──────┐
sites web (liste fermée, ─────┼──► archive locale
  avec ou sans identification)┘
                │
   [collecte] identifiants · antivirus · conversion isolée · photos nettoyées
              · filtrage des contributeurs
                │
   serveurs MCP locaux :
     mail · rss · web (lecture seule) · propositions (création de fichiers)
                │
   [agent IA périodique] analyse · filtre · rédige
                │ création de fichiers uniquement
          propositions/<période>/
                │
   contrôles F6 (script) ──► notification au validateur
                │
   écran de validation : globale · par article · modification
                │
   [diffusion] (script) ──► abonnés · archives · journal

   écran d'administration : sources · identifiants · périodicité ·
     description du DTO · contributeurs · abonnés · liste noire
```

### 8.2 Format d'une proposition (extrait)

```json
{
  "periode": "2027-03/2027-04",
  "titre": "…",
  "introduction": "…",
  "veille_reglementaire": [
    {
      "id_sources": ["msg:abc123", "rss:easa:456"],
      "titre": "…",
      "synthese": "…",
      "source": "EASA, décision de l'agence du …",
      "liens": ["https://www.easa.europa.eu/…"]
    }
  ],
  "rex_technique": {
    "type": "rex",
    "articles": [
      {"activite": "planeur", "titre": "…", "synthese": "…", "source": "…", "id_sources": ["…"]}
    ]
  },
  "contributions": [
    {"id_source": "msg:def789", "auteur": "…", "titre": "…", "texte": "…", "illustrations": ["pj:def789:1"]}
  ],
  "quiz": [
    {"question": "…", "choix": ["…", "…", "…"], "reponse": 1, "reference": "programme SPL, météorologie"}
  ],
  "signalements": {
    "notification_possible": [],
    "modification_manuel": [{"chapitre": "E-9", "objet": "…"}]
  }
}
```

### 8.3 Pile envisagée

- **Agent IA** : Claude Code en mode non interactif (`claude -p`) ou Agent SDK, lancé par un timer systemd, avec uniquement les serveurs MCP du service (`--tools ""`, `--strict-mcp-config`, `--allowedTools`, `--permission-mode dontAsk`, `--max-turns`, `--max-budget-usd`).
- **Serveurs MCP** : Python (SDK MCP officiel) ou PHP, en HTTP local avec jeton.
- **Application** : administration, validation, abonnements, diffusion et journal (Laravel sur ratus80, ou module GVV).
- **Relevé des sites web** : client HTTP pour les pages publiques ; navigateur automatisé (Playwright) pour les sites avec identification.
- **Messagerie** : Dovecot pour veille@, `imapsync` pour la copie de info@ ; envoi par le SMTP du domaine ou listmonk.
- **Antivirus et conversion** : ClamAV ; `pdftotext` et `pandoc` dans `bubblewrap` ou `firejail`.

### 8.4 Volumétrie et coûts estimés

- info@ : quelques centaines de mails par mois.
- Coût de l'IA : estimé à quelques euros par mois au plus. À confirmer avec les tarifs en vigueur de l'API.

## 9. RGPD et conformité

- **Traitement à inscrire au registre** du club. Finalité : veille sécurité et information des membres. Base légale : intérêt légitime (sécurité des vols, obligation DTO.GEN.210) ; consentement pour l'abonnement. Sous-traitant : Anthropic (API). Durées : 30 jours pour les journaux d'exécution, 36 mois pour le journal de veille et les éditions.
- **Décision du bureau** pour la copie intégrale de info@ ; **information des membres**.
- **Minimisation** : l'agent ne lit que la boîte de collecte et les flux et pages configurés.
- **Abonnés** : nom (si la validation des abonnements est active) et adresse email, conservés jusqu'à la désinscription. La liste noire ne conserve que l'adresse et la date d'inscription sur la liste.
- **Sites avec identification** : respect des conditions d'utilisation de chaque site ; le contenu réservé n'est que résumé, jamais reproduit.
- **Anonymisation** des REX ; accord de l'auteur avant de publier un REX venu d'un membre.
- **Contributions** : l'envoi d'une proposition vaut accord de publication après validation ; l'auteur peut demander à ne pas être nommé ou retirer son article avant l'envoi.
- **Droit à l'image** : une photo montrant des personnes identifiables n'est publiée qu'avec leur accord, vérifié par le validateur.
- **Droits sur les contenus** : résumer et renvoyer vers la source. Pas de republication intégrale, en particulier pour les contenus réservés aux licenciés (REX FFA). Les questions de quiz sont rédigées, pas copiées de banques de questions protégées.

## 10. Plan de livraison

| Phase | Contenu | Critère de sortie |
|---|---|---|
| **P0. Test rétroactif** (2 semaines) | Copie de l'historique de info@ (juillet à septembre 2026), serveurs MCP en mode période, rédaction de 2 newsletters de test, comparaison avec la liste de référence du RP | Rappel ≥ 90 %, précision ≥ 70 %, aucune erreur de fond, aucun refus de permission inattendu dans les journaux |
| **P1. MVP ACS** (1 à 2 mois) | Veille réglementaire, REX, quiz, validation, diffusion, journal ; envoi à un groupe pilote (bureau et instructeurs) | 2 éditions validées, validation ≤ 30 min chacune |
| **P2. Ouverture aux membres** | Envoi à tous les abonnés ; ouverture des propositions d'articles par email | Désabonnements < 5 % ; au moins une contribution publiée par édition |
| **P3. Service mutualisé** (option) | Service central (sources publiques, multi-clubs) et module club (sources privées) ; module GVV | 3 à 5 clubs pilotes satisfaits ; décision sur la monétisation |

## 11. Risques

| Risque | Impact | Parade |
|---|---|---|
| Information fausse ou mal résumée | Élevé | Description et lien de la source dans chaque article, validation humaine, mention « seuls les textes officiels font foi » |
| Mail ou contribution piégés (injection de prompt) | Moyen | Capacités de l'agent limitées par conception (S2 à S8), pas de lecture web libre, contrôles F6, validation humaine |
| Contribution malveillante, diffamatoire ou hors sujet | Moyen | Contributeurs autorisés uniquement, vérification de l'expéditeur, liste noire, validation humaine article par article |
| Fuite des identifiants d'un site avec identification | Élevé | Stockage chiffré, lisibles par la collecte seule (S8), compte dédié en lecture seule si possible |
| Site web modifié qui casse le relevé | Moyen | Test d'accès par source (F11.1), alerte à l'administrateur en cas d'échec ou de page vide |
| Photo inappropriée ou portant atteinte au droit à l'image | Moyen | Antivirus, nettoyage des métadonnées, choix des illustrations par le validateur |
| Quiz avec une réponse fausse | Moyen | Référence de chaque question, relecture du validateur |
| Agent qui contourne ses permissions | Faible | Aucun outil dangereux n'existe (F1.2, F1.5, F4.7) ; utilisateurs système séparés (S1, S5) ; filtrage réseau (S7) |
| Fuite de données par URL | Faible | Aucun outil n'accepte d'URL libre (S6) ; réseau sortant limité (S7) |
| Pièce jointe malveillante | Moyen | Antivirus, liste de types, conversion isolée |
| Information importante non retenue par l'agent | Moyen | Journal des éléments non retenus consultable par le RP, test rétroactif P0 |
| Fuite de données personnelles dans la newsletter | Élevé | Anonymisation, contrôle F6.5, validation humaine, RGPD (§ 9) |
| Validateur indisponible | Moyen | Relance à 15 jours, suppléant (RP) |
| Coût de l'IA qui dérape | Faible | Plafond par exécution et budget mensuel global (S12) |
| Changement d'API, de modèle ou de CLI | Faible | Tests de non-régression sur le jeu P0 ; version figée et mise à jour contrôlée |

## 12. Questions ouvertes

1. Qui est le validateur principal : le CPS, avec le RP en suppléance ?
2. Faut-il une alerte immédiate, hors newsletter, pour les informations urgentes (consigne de navigabilité urgente, NOTAM local) ? Si oui, avec quel mode de validation ?
3. Les archives sont-elles publiques ou réservées aux membres ?
4. Quand faut-il synchroniser les abonnés avec GVV : dès la version 1, ou en version 2 ?
5. Le bureau valide-t-il la copie intégrale de info@ ?
6. Quels flux RSS et quelles pages web retenir ? Il faut inventorier les sources effectivement disponibles.
7. Les conditions d'utilisation des espaces licenciés (FFA, FFVP, FFPLUM) autorisent-elles un relevé automatisé avec les identifiants du club ?
8. Faut-il ajouter au § B-9 du manuel une mention de la newsletter comme moyen de veille ?

## Annexe A : domaines autorisés (première liste)

`aviation-civile.gouv.fr`, `ecologie.gouv.fr`, `sia.aviation-civile.gouv.fr`, `veilledoc.dsac.fr`, `legifrance.gouv.fr`, `eur-lex.europa.eu`, `easa.europa.eu`, `bea.aero`, `ffa-aero.fr`, `ffvp.fr`, `ffplum.fr`, `aeroclub-abbeville.fr`

## Annexe B : critères de la newsletter

- Elle se lit en 10 minutes au plus, quiz compris.
- Chaque article de veille répond à trois questions : *quoi ?*, *qui est concerné ?*, *que dois-je faire ?*
- Les articles de veille sont classés par impact pour le club, pas par date.
- Les REX sont présentés comme des témoignages, pas comme des règles.
- Le ton est factuel pour la veille, plus libre pour les contributions et le quiz.

## Annexe C : références techniques

- Claude Code en mode non interactif : https://code.claude.com/docs/en/headless
- Options de la CLI (`--tools`, `--allowedTools`, `--strict-mcp-config`, `--permission-mode`, `--max-budget-usd`) : https://code.claude.com/docs/en/cli-reference
- Configuration des permissions et règles MCP : https://code.claude.com/docs/en/permissions
