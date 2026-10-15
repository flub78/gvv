# PRD : veille sécurité et réglementaire automatisée

| | |
|---|---|
| **Version** | 0.2 (brouillon) |
| **Date** | 14/10/2026 |
| **Auteur** | Frédéric Peignot, avec Claude |
| **Statut** | À valider |
| **Premier client** | Aéroclub d'Abbeville-Buigny-Baie de Somme (ACS), DTO FR.DTO.0645 |

**Changements depuis la version 0.1 :**
- la rédaction est confiée à un **agent périodique** qui n'accède aux données que par des serveurs MCP en lecture seule et n'a qu'une seule sortie, `save_draft` ;
- un **relecteur IA sans outils** est ajouté ;
- l'envoi passe par un **diffuseur déterministe** qui exige plusieurs conditions ;
- un **mode « validation par veto »** est prévu pour après la période de rodage.

---

## 1. Contexte et problème

Un DTO doit assurer une veille réglementaire et sécurité (DTO.GEN.210, § B-9 du manuel de sécurité) et pouvoir la prouver en audit. L'audit DSAC de juin 2026 a d'ailleurs relevé une remarque sur ce point (#1406200).

Aujourd'hui, cette veille est manuelle et dispersée :

- **Les informations arrivent par de nombreux canaux** : la boîte `info@aeroclub-abbeville.fr` (IONOS), les boîtes personnelles du président, du RP et du CPS, les sites web (veilledoc.dsac.fr, EASA, BEA, Légifrance), les lettres des fédérations (FFA, FFVP, FFPLUM), et des REX transmis par des pilotes ou des amis.
- **Personne n'a le temps de tout lire, trier et résumer** chaque mois.
- **La diffusion aux membres est irrégulière.**
- **La preuve de veille n'est pas archivée** de façon structurée.

## 2. Objectifs

| # | Objectif | Indicateur |
|---|---|---|
| O1 | Collecter automatiquement les informations sécurité et réglementaires de toutes les sources | Au moins 95 % des éléments pertinents retenus, mesuré par un test rétroactif contre une liste de référence établie par le RP |
| O2 | Produire chaque mois une lettre de synthèse utile pour les membres | Relecture en 30 min ou moins ; au moins 80 % des éléments jugés pertinents par le CPS |
| O3 | Ne jamais diffuser d'information fausse ou malveillante | 0 lien hors liste autorisée ; aucune lettre envoyée sans que toutes les conditions de diffusion soient réunies (F9) |
| O4 | Fournir une preuve de veille présentable en audit | Journal horodaté de chaque élément : source, date, décision, diffusion ; conservé au moins 36 mois |
| O5 | Garantir la sécurité et la confidentialité | Aucun composant IA ne peut envoyer, supprimer, modifier une boîte ni ouvrir une URL libre ; le traitement est déclaré au registre RGPD |

### Hors objectifs (version 1)

- Remplacer la responsabilité du représentant du DTO et du RP pour la veille.
- Notifier des événements à l'autorité (ECCAIRS) : le système **signale** les événements à notifier, il ne les déclare pas.
- Modifier automatiquement le manuel de sécurité : le système **propose** des modifications, il ne les fait pas.
- Gérer des sources payantes ou sous identifiant (à prévoir en V2 si besoin).

## 3. Utilisateurs

| Rôle | Besoin |
|---|---|
| **CPS** (validateur principal) | Relire, corriger, valider ou mettre son veto sur la lettre |
| **RP / représentant du DTO** | Voir les éléments réglementaires qui ont un impact sur le DTO ; consulter le journal de veille pour l'audit ; valider en suppléance |
| **Administrateur technique** (Frédéric) | Configurer les sources, les règles et les clés ; surveiller le fonctionnement |
| **Membres abonnés** | Recevoir une lettre courte et claire ; pouvoir se désabonner |
| **Contributeurs** (pilotes, amis) | Envoyer facilement un REX ou une information à `securite@` |
| **Auditeur DSAC** (indirect) | Constater que la veille existe, qu'elle est tracée et suivie d'effets |

## 4. Principes d'architecture

1. **Chaque composant a le minimum de pouvoirs.** Un composant qui lit du contenu non fiable (mails, REX, PDF) ne peut pas agir sur le monde extérieur.
2. **Les pouvoirs sont limités par les outils, pas par les consignes.** Un agent ne peut faire que ce que ses outils permettent ; les consignes dans les prompts ne sont qu'une protection secondaire.
3. **Celui qui vérifie n'envoie pas, celui qui envoie ne lit pas de contenu non fiable.** Le relecteur IA n'a aucun outil, et le diffuseur est un script sans IA.
4. **Pas de lecture web libre.** Les agents ne peuvent lire que des sources fixées à l'avance, ce qui supprime le canal de fuite par URL.
5. **Les garanties fortes sont déterministes.** Contrôles par script, isolement des utilisateurs système, filtrage réseau. Les composants IA améliorent la qualité, ils ne portent pas la sécurité.

## 5. Périmètre fonctionnel

### 5.1 Sources

| Type | Exemples | Mode de collecte |
|---|---|---|
| Boîte de collecte | `veille@aeroclub-abbeville.fr` sur ratus80 (Dovecot) | Lecture seule (`EXAMINE` et `BODY.PEEK`) ou lecture directe du Maildir |
| Copie de info@ | Tout le courrier de `info@` (IONOS) | `imapsync` de info@ vers veille@, historique compris |
| Contributions volontaires | `securite@aeroclub-abbeville.fr`, formulaire web | Transfert vers veille@ |
| Boîtes personnelles | RP, président, CPS | Règles de transfert filtrées par expéditeur, définies par chaque titulaire |
| Flux RSS | EASA, BEA, fédérations (si disponibles) | Relevé périodique et archivage local, à partir d'une liste fermée |
| Pages web | veilledoc.dsac.fr, pages d'actualité DSAC, Légifrance | Relevé périodique, détection des changements, à partir d'une liste fermée |

### 5.2 Fonctionnalités

**F1. Collecte (sans IA)**
- F1.1 Relève veille@, les flux et les pages selon un calendrier configurable (par défaut toutes les heures).
- F1.2 N'ouvre jamais une boîte en écriture et ne modifie jamais la boîte source.
- F1.3 Normalise chaque mail en texte brut (HTML retiré) avec ses métadonnées : expéditeur, date, sujet, `Message-ID`.
- F1.4 Évite les doublons grâce au `Message-ID` ou à une empreinte du contenu.
- F1.5 Archive localement les flux RSS et les pages, pour constituer progressivement un historique.

**F2. Pièces jointes (sans IA)**
- F2.1 Accepte uniquement les types autorisés (PDF, DOCX, XLSX, ODT, PNG, JPG) et une taille maximale configurable (20 Mo par défaut).
- F2.2 Analyse chaque pièce jointe avec ClamAV (`clamdscan`). Une pièce infectée est mise en quarantaine et une alerte est envoyée à l'administrateur.
- F2.3 Convertit en texte (`pdftotext`, `pandoc`) dans un environnement isolé, sans réseau et sans droits.
- F2.4 Conserve l'original, avec son empreinte SHA-256, comme preuve de veille.
- F2.5 (Option) Désarme les fichiers en convertissant les PDF en images puis de nouveau en PDF.

**F3. Pré-tri (sans IA)**
- F3.1 S'appuie sur une liste de retenue (expéditeurs officiels : DSAC, fédérations, EASA, BEA) et une liste d'exclusion (fournisseurs, banque, assurance, publicités).
- F3.2 Seuls les cas indéterminés passent au classement par IA (F4).

**F4. Classement par IA, mail par mail (IA sans outils)**
- F4.1 Envoie à l'IA uniquement l'expéditeur, le sujet et les 1 000 premiers caractères du texte, **un mail par appel**.
- F4.2 Reçoit une réponse JSON : `{securite: bool, categorie, confiance, raison}`.
- F4.3 Un script applique la décision : le mail va dans le dossier `retenus` ou dans le dossier `écartés`, conservé 30 jours puis supprimé.
- F4.4 Envoie chaque mois au RP la liste des sujets écartés, pour un contrôle rapide.
- F4.5 Les catégories sont : `reglementation`, `info_securite_officielle`, `rex`, `notam_aerodrome`, `navigabilite`, `formation`, `autre_securite`.

**F5. Serveurs MCP (sans IA)**

Ce sont les seules interfaces entre l'agent rédacteur et les données.

- F5.1 **`veille_mail`, en lecture seule.** Il n'expose que les éléments du dossier `retenus`, jamais la boîte complète. Outils :
  - `list_items(since, until, category?)` : identifiant, date, expéditeur, sujet, catégorie ;
  - `get_item(id)` : texte normalisé et liste des pièces jointes ;
  - `get_attachment_text(id, n)` : texte extrait d'une pièce jointe saine (F2).
- F5.2 **`veille_feeds`, en lecture seule.** Outils :
  - `list_feeds()` ;
  - `get_feed_items(feed_id, since, until)` : depuis l'archive locale (F1.5) ;
  - `get_page_snapshot(page_id, date)`.

  Aucun outil n'accepte une URL libre.
- F5.3 **`veille_out`, en écriture contrainte.** Un seul outil, `save_draft(periode, json)` :
  - il valide le JSON par rapport au schéma (§ 7.2) et applique les contrôles F7 ;
  - il écrit dans `brouillons/` sous un nom de fichier choisi par le serveur (`AAAA-MM_vN.json`) ;
  - il n'écrase rien et refuse au-delà de 5 brouillons par période ;
  - il renvoie à l'agent la liste des erreurs, pour qu'il corrige.
- F5.4 Aucun serveur MCP ne propose d'outil d'envoi, de suppression, de déplacement, de marquage ou de lecture web libre.

**F6. Agent rédacteur (agent périodique)**
- F6.1 Il est lancé par un timer systemd, une fois par mois (date configurable), et à la demande pour les tests.
- F6.2 Il parcourt les éléments de la période avec `veille_mail` et `veille_feeds`, puis rédige la lettre et l'enregistre avec `save_draft`.
- F6.3 Chaque élément de la lettre contient :
  - un titre ;
  - un résumé de 3 à 6 lignes ;
  - « ce que ça change pour nous » ;
  - le public concerné (avion, planeur, ULM, instructeurs, tous) ;
  - l'urgence ;
  - la ou les sources, avec l'identifiant interne et l'URL.
- F6.4 La lettre est organisée en rubriques :
  1. À retenir ce mois-ci ;
  2. Réglementation ;
  3. Informations sécurité officielles ;
  4. Retours d'expérience (non officiels) ;
  5. Vie du club / sécurité locale.
- F6.5 Les REX sont anonymisés : pas de nom, pas d'immatriculation, pas de lieu précis, sauf mention d'accord.
- F6.6 L'agent signale à part, dans la section `alertes` du JSON :
  - les éléments qui pourraient appeler une notification à l'autorité (§ H-6 du manuel) ;
  - les éléments qui pourraient appeler une modification du manuel, avec le chapitre concerné.
- F6.7 Mode test rétroactif : l'agent reçoit une période passée, et les serveurs MCP ne renvoient que des éléments datés de cette période.

**F7. Contrôles automatiques (sans IA, dans `save_draft` puis à nouveau avant diffusion)**
- F7.1 Le JSON doit respecter le schéma.
- F7.2 Liens : seules sont acceptées les URL présentes dans les éléments sources cités **et** appartenant à la liste de domaines autorisés (annexe A).
- F7.3 Chaque élément doit citer au moins une source existante dans la période.
- F7.4 La lettre est limitée en taille : 12 éléments au plus, et une longueur maximale par champ.
- F7.5 Les données personnelles résiduelles (adresses mail, téléphones, immatriculations F-XXXX) sont repérées par expression régulière et bloquent la lettre.
- F7.6 Une liste de motifs suspects est contrôlée : texte invisible, caractères de contrôle, sollicitations du type « cliquez », « identifiants », « virement ».

**F8. Relecteur IA (IA sans aucun outil)**
- F8.1 Il est lancé par un script quand un brouillon passe les contrôles F7.
- F8.2 Il reçoit le brouillon et les extraits de sources cités, et renvoie un JSON :
  - `avis` : `ok` ou `a_revoir` ;
  - `motifs` : la liste des problèmes relevés ;
  - `elements_douteux` : les identifiants concernés.
- F8.3 Il vérifie :
  - la fidélité des résumés aux sources ;
  - le ton ;
  - l'absence de consignes adressées aux lecteurs qui ne viennent pas d'une source officielle ;
  - l'absence de contenu promotionnel ou de manipulation ;
  - l'anonymisation des REX.
- F8.4 Il utilise un modèle ou un prompt différent de ceux du rédacteur. Il n'a aucun outil, aucun accès au réseau autre que l'API, aucun droit d'écriture : c'est le script qui enregistre son avis.
- F8.5 Son avis est **une condition parmi d'autres**. Il ne déclenche jamais un envoi à lui seul.

**F9. Validation et diffusion (diffuseur sans IA)**
- F9.1 L'écran de validation présente la lettre mise en page, les sources, les résultats des contrôles F7 et l'avis du relecteur F8.
- F9.2 Le validateur peut modifier, supprimer ou ajouter un élément. Après une modification, les contrôles F7 sont relancés.
- F9.3 Il y a deux modes de diffusion, choisis par l'administrateur :
  - **Validation explicite** (par défaut, obligatoire en phases P0 à P2) : la lettre part quand trois conditions sont réunies : les contrôles F7 sont OK, l'avis F8 est `ok`, et le CPS ou le RP a cliqué sur « Valider ».
  - **Validation par veto** (à partir de la phase P3, sur décision du bureau) : les contrôles F7 sont OK et l'avis F8 est `ok`. Le brouillon est notifié au CPS et au RP, et part automatiquement après **48 h sans veto**. Si les contrôles ou le relecteur signalent un problème, le mode bascule en validation explicite.
- F9.4 Le diffuseur met la lettre en page avec un modèle fixe (HTML et texte). Les destinataires viennent uniquement de la base des abonnés, jamais du brouillon. Le sujet suit un format fixe, du type `[Veille sécurité ACS] <mois>`.
- F9.5 Envoi par SMTP ou par listmonk, avec un lien de désinscription en un clic.
- F9.6 La lettre est publiée en PDF ou en page web dans les archives (site de gestion).
- F9.7 Un brouillon non validé sous 15 jours, en mode explicite, déclenche une relance.

**F10. Journal de veille (pour l'audit)**
- F10.1 Pour chaque élément : date de réception, source, décision (retenu ou écarté, avec la raison), lettre de diffusion, suite donnée (aucune, information, modification du manuel, notification).
- F10.2 Pour chaque lettre : versions des brouillons, résultats des contrôles, avis du relecteur, validation ou veto (qui et quand), date d'envoi.
- F10.3 Export PDF ou CSV par période, présentable à la DSAC.
- F10.4 Conservation d'au moins 36 mois (cohérent avec l'AltMOC Alt/26/0006).

**F11. Abonnements**
- F11.1 Inscription et désinscription par double confirmation (opt-in).
- F11.2 Version 1 : liste propre au service. Version 2 : synchronisation avec les membres GVV.

## 6. Exigences de sécurité

| # | Exigence |
|---|---|
| S1 | **Séparation des rôles et des utilisateurs système** : `veille-collect` (collecte, pré-tri, pièces jointes), `veille-mcp` (serveurs MCP), `veille-agent` (rédacteur, classeur, relecteur), `veille-send` (diffuseur). Chacun n'a que ses propres secrets. |
| S2 | **Agent rédacteur** : outils intégrés désactivés (`--tools ""`) ; seuls les serveurs MCP `veille_mail`, `veille_feeds` et `veille_out` sont chargés (`--bare`, `--strict-mcp-config`) ; tout le reste est refusé (`--permission-mode dontAsk`, `--permission-prompts none`) ; durée et coût plafonnés (`--max-turns`, `--max-budget-usd`). |
| S3 | **Classeur et relecteur** : appels à l'API sans aucun outil déclaré. |
| S4 | **Les serveurs MCP tournent comme services HTTP locaux** (`127.0.0.1`) sous l'utilisateur `veille-mcp`, avec un jeton d'accès. Ils ne sont **pas** lancés en stdio par l'agent, sinon ils tourneraient sous son utilisateur et il pourrait lire leurs secrets. |
| S5 | **Pas de lecture web libre** : aucun outil n'accepte d'URL fournie par l'agent. Les flux et les pages viennent d'une liste fermée relevée par le collecteur. |
| S6 | **Réseau sortant** : `veille-agent` ne peut joindre que l'API Anthropic et `127.0.0.1` (proxy en liste blanche ou nftables par utilisateur). Seul `veille-send` peut joindre le serveur SMTP. |
| S7 | Aucun identifiant IMAP ou SMTP n'est accessible à `veille-agent` : secrets dans des fichiers `.env` aux droits 600, propriété de l'utilisateur concerné. |
| S8 | Le contenu des sources est transmis comme une **donnée délimitée**, avec la consigne d'ignorer toute instruction qu'il contient. Protection secondaire : S1 à S7 suffisent à elles seules. |
| S9 | Le classement se fait **mail par mail** (F4) : un mail piégé ne peut influencer que sa propre décision. L'agent rédacteur ne voit que les éléments retenus. |
| S10 | Les pièces jointes passent par l'antivirus, sont converties dans un environnement isolé et filtrées par type (F2). |
| S11 | Chaque exécution d'un agent est journalisée (sortie `--output-format json` : outils appelés, refus, coût). Le contenu n'est pas conservé au-delà de 30 jours. |
| S12 | Un budget mensuel global plafonné coupe le système en cas de dépassement. |

**Modèle de menace résiduel :**
- un agent rédacteur compromis peut lire les éléments retenus de la période et écrire un mauvais brouillon. Il ne peut ni envoyer, ni supprimer, ni joindre une URL externe ;
- un relecteur compromis peut rendre un faux avis `ok`. Les contrôles F7 et la validation humaine, ou la fenêtre de veto, restent en place ;
- une injection conçue pour tromper à la fois le rédacteur et le relecteur est possible, car deux IA ne sont pas des protections indépendantes. D'où la validation humaine obligatoire tant que le système n'a pas fait ses preuves.

## 7. Architecture technique

### 7.1 Vue d'ensemble

```
info@ (IONOS) ──imapsync──┐
securite@ ────────────────┤
transferts personnels ────┼──► veille@ (Dovecot, ratus80)
RSS / pages (liste fermée)┘
                │
   [veille-collect] collecte · pré-tri · ClamAV · conversion isolée
                │
   [classeur IA, sans outils, mail par mail] ──► écartés (30 j)
                │ retenus
                │
   [veille-mcp] serveurs MCP HTTP locaux :
     veille_mail (lecture) · veille_feeds (lecture) · veille_out (save_draft)
                │
   [veille-agent] agent rédacteur périodique (claude -p, timer systemd)
                │ save_draft
          brouillons/AAAA-MM_vN.json
                │
   contrôles F7 (script) ──► [relecteur IA, sans outils] ──► avis
                │
   écran de validation : validation explicite ou veto 48 h
                │
   [veille-send] diffuseur (script) ──► abonnés · archives · journal
```

### 7.2 Format de sortie du rédacteur (extrait)

```json
{
  "periode": "2026-09",
  "elements": [
    {
      "id_sources": ["msg:abc123", "rss:easa:456"],
      "rubrique": "reglementation",
      "titre": "Règlement (UE) 2024/2076 : remise à niveau SEP précisée",
      "resume": "…",
      "impact_acs": "…",
      "public": ["avion", "instructeurs"],
      "urgence": "normale",
      "liens": ["https://www.ecologie.gouv.fr/…"]
    }
  ],
  "alertes": {
    "notification_possible": [],
    "modification_manuel": [{"chapitre": "E-9", "objet": "…"}]
  }
}
```

### 7.3 Lancement de l'agent rédacteur (exemple)

```bash
# /etc/systemd/system/veille-rediger.service (User=veille-agent), déclenché par un timer mensuel
claude --bare -p "$(cat /opt/veille/prompts/redacteur.md) Période : ${PERIODE}" \
  --tools "" \
  --strict-mcp-config --mcp-config /opt/veille/mcp.json \
  --allowedTools "mcp__veille_mail__*" "mcp__veille_feeds__*" "mcp__veille_out__save_draft" \
  --permission-mode dontAsk --permission-prompts none \
  --max-turns 60 --max-budget-usd 3 \
  --no-session-persistence --output-format json \
  > /var/log/veille/rediger-${PERIODE}.json
```

Le fichier `mcp.json` déclare les trois serveurs en HTTP local (`http://127.0.0.1:87xx/mcp`), chacun avec son jeton. L'authentification à l'API se fait par `ANTHROPIC_API_KEY`, propre à `veille-agent`. Variante possible : un script Agent SDK (Python ou TypeScript) avec les mêmes restrictions (`allowed_tools`, `disallowed_tools`, `permission_mode`).

### 7.4 Pile technique

- **Application** : Laravel (base Laraskel) sur ratus80 (Hestia, PHP-FPM), pour la base, l'écran de validation, le diffuseur, les abonnés et le journal.
- **Commandes** : `veille:collecter`, `veille:classer`, `veille:controler`, `veille:relire`, `veille:diffuser`, `veille:purger`.
- **Serveurs MCP** : Python (SDK MCP officiel, FastMCP) ou PHP. Transport HTTP local, jeton par serveur, sous l'utilisateur `veille-mcp`.
- **Agent rédacteur** : Claude Code en mode non interactif (`claude -p`) ou Agent SDK, lancé par un timer systemd.
- **IA** : un petit modèle (Haiku) pour le classement ; un modèle plus capable (Sonnet) pour la rédaction ; un modèle ou un prompt différent pour la relecture.
- **Messagerie** : Dovecot sous Hestia pour veille@ ; `imapsync` pour la copie de info@.
- **Antivirus et conversion** : ClamAV (`clamd`, `freshclam`) ; `pdftotext` et `pandoc` dans `bubblewrap` ou `firejail`.
- **Envoi** : SMTP du domaine, ou listmonk.
- **Données** : MySQL ou SQLite. Tables : `sources`, `elements`, `pieces_jointes`, `decisions`, `brouillons`, `controles`, `avis_relecteur`, `validations`, `lettres`, `abonnes`, `journal_ia`.

### 7.5 Volumétrie et coûts estimés

- info@ : quelques centaines de mails par mois, dont environ 10 à 20 % classés par l'IA après le pré-tri.
- Coût de l'IA (classement, rédaction et relecture) : estimé à quelques euros par mois au plus. À confirmer avec les tarifs en vigueur de l'API.

## 8. RGPD et conformité

- **Traitement à inscrire au registre** du club. Finalité : veille sécurité et information des membres. Base légale : intérêt légitime (sécurité des vols, obligation DTO.GEN.210). Sous-traitant : Anthropic (API). Durées : 30 jours pour les mails écartés et les journaux d'exécution, 36 mois pour les éléments retenus et le journal de veille.
- **Décision du bureau** pour la copie intégrale de info@ et pour le passage en validation par veto ; **information des membres**.
- **Minimisation** : seuls des extraits sont envoyés à l'IA pour le classement, et l'agent rédacteur ne voit que les éléments retenus.
- **Anonymisation** des REX ; accord de l'auteur avant de publier un REX venu d'un membre.
- **Droits sur les contenus** : résumer et renvoyer vers la source. Pas de republication intégrale, en particulier pour les contenus réservés aux licenciés (REX FFA).
- **Mention dans chaque lettre** : « Synthèse indicative, préparée avec une assistance automatisée et validée par le CPS. Seuls les textes officiels font foi. »

## 9. Plan de livraison

| Phase | Contenu | Critère de sortie |
|---|---|---|
| **P0. Test rétroactif** (2 semaines) | `imapsync` de l'historique de info@ (juillet à septembre 2026), serveurs MCP en mode période, rédaction de 3 lettres de test, comparaison avec la liste de référence du RP | Rappel ≥ 90 %, précision ≥ 70 %, aucune erreur de fond, aucun refus de permission inattendu dans les journaux |
| **P1. MVP ACS** (1 à 2 mois) | F1 à F10 ; validation explicite ; envoi à un groupe pilote (bureau et instructeurs) | 3 lettres validées, relecture ≤ 30 min chacune |
| **P2. Ouverture aux membres** | Envoi à tous les abonnés ; ajout de `securite@` et du formulaire ; validation explicite maintenue | Désabonnements < 5 % ; avis du relecteur concordant avec le CPS sur au moins 90 % des éléments |
| **P3. Validation par veto** (sur décision du bureau) | Passage au mode veto 48 h (F9.3) | 6 mois sans correction de fond par le CPS ; aucun incident de sécurité |
| **P4. Service mutualisé** (option) | Service central (sources publiques, multi-locataire) et module club (sources privées) ; module GVV ; profils DTO | 3 à 5 clubs pilotes satisfaits ; décision sur la monétisation |

## 10. Risques

| Risque | Impact | Parade |
|---|---|---|
| Information fausse ou mal résumée | Élevé | Relecteur IA, validation humaine ou veto, lien vers la source, mention « seuls les textes officiels font foi » |
| Mail piégé (injection de prompt) | Moyen | Outils limités par conception (F5, S2 à S7), classement mail par mail, pas de lecture web libre, contrôles F7, relecteur, validation humaine |
| Injection qui trompe à la fois le rédacteur et le relecteur | Moyen | Contrôles déterministes F7 ; validation explicite tant que P3 n'est pas atteint ; relecteur avec un modèle ou un prompt différent |
| Agent qui contourne ses permissions | Faible | Aucun outil dangereux n'existe (F5.4) ; utilisateurs système séparés (S1, S4) ; filtrage réseau (S6) |
| Fuite de données par URL | Faible | Aucun outil n'accepte d'URL libre (S5) ; réseau sortant limité à l'API (S6) |
| Pièce jointe malveillante | Moyen | ClamAV, liste de types, conversion isolée, désarmement en option |
| Élément important écarté à tort | Moyen | Liste de retenue des expéditeurs officiels, dossier « écartés » sur 30 jours, contrôle mensuel |
| Fuite de données personnelles dans la lettre | Élevé | Minimisation, anonymisation, contrôle F7.5 bloquant, relecteur, RGPD (§ 8) |
| Validateur indisponible | Faible | Relance à 15 jours, suppléant (RP), mode veto en phase P3 |
| Coût de l'IA qui dérape | Faible | `--max-budget-usd` par exécution et budget mensuel global (S12) |
| Changement d'API, de modèle ou de CLI | Faible | Tests de non-régression sur le jeu P0 ; version de Claude Code figée et mise à jour contrôlée |

## 11. Questions ouvertes

1. Qui est le validateur principal : le CPS, ou le RP en suppléance ?
2. Quel jour d'envoi chaque mois ?
3. Faut-il une alerte immédiate, hors lettre mensuelle, pour les informations urgentes (consigne de navigabilité urgente, NOTAM local) ? Si oui, avec quel mode de validation ?
4. Sera-t-elle publiée sur le site de gestion, en accès public ou réservé aux membres ?
5. Quand faut-il synchroniser les abonnés avec GVV : dès la version 1, ou en version 2 ?
6. Le bureau valide-t-il la copie intégrale de info@ ?
7. Quels flux RSS et pages web retenir ? Il faut inventorier les sources effectivement disponibles.
8. Faut-il ajouter au § B-9 du manuel une mention de l'outil comme moyen de veille ?
9. En quel langage écrire les serveurs MCP : Python, plus simple avec le SDK officiel, ou PHP, pour rester proche de Laraskel ?
10. Faut-il passer au mode veto (P3), et à quelles conditions exactes ?

## Annexe A : domaines autorisés (première liste)

`aviation-civile.gouv.fr`, `ecologie.gouv.fr`, `sia.aviation-civile.gouv.fr`, `veilledoc.dsac.fr`, `legifrance.gouv.fr`, `eur-lex.europa.eu`, `easa.europa.eu`, `bea.aero`, `ffa-aero.fr`, `ffvp.fr`, `ffplum.fr`, `aeroclub-abbeville.fr`

## Annexe B : critères de la lettre

- Elle se lit en 5 minutes au plus.
- Chaque élément répond à trois questions : *quoi ?*, *qui est concerné ?*, *que dois-je faire ?*
- Les éléments sont classés par impact pour l'ACS, pas par date.
- Les REX sont présentés comme des témoignages, pas comme des règles.

## Annexe C : références techniques

- Claude Code en mode non interactif : https://code.claude.com/docs/en/headless
- Options de la CLI (`--tools`, `--allowedTools`, `--strict-mcp-config`, `--permission-mode`, `--max-budget-usd`) : https://code.claude.com/docs/en/cli-reference
- Configuration des permissions et règles MCP : https://code.claude.com/docs/en/permissions
