# PRD : veille sécurité et réglementaire automatisée

| | |
|---|---|
| **Version** | 0.1 (brouillon) |
| **Date** | 14/10/2026 |
| **Auteur** | Frédéric Peignot, avec Claude |
| **Statut** | À valider |
| **Premier client** | Aéroclub d'Abbeville-Buigny-Baie de Somme (ACS), DTO FR.DTO.0645 |

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
| O3 | Ne jamais diffuser d'information fausse ou malveillante | 0 lien hors liste autorisée ; 100 % des lettres validées par un humain avant envoi |
| O4 | Fournir une preuve de veille présentable en audit | Journal horodaté de chaque élément : source, date, décision, diffusion ; conservé au moins 36 mois |
| O5 | Garantir la sécurité et la confidentialité | L'IA n'a aucun moyen technique d'agir (envoi, suppression, réseau) ; le traitement est déclaré au registre RGPD |

### Hors objectifs (version 1)

- Remplacer la responsabilité du représentant du DTO et du RP pour la veille.
- Notifier des événements à l'autorité (ECCAIRS) : le système **signale** les événements à notifier, il ne les déclare pas.
- Modifier automatiquement le manuel de sécurité : le système **propose** des modifications, il ne les fait pas.
- Gérer des sources payantes ou sous identifiant (à prévoir en V2 si besoin).

## 3. Utilisateurs

| Rôle | Besoin |
|---|---|
| **CPS** (validateur principal) | Relire et corriger le brouillon de lettre, puis le valider |
| **RP / représentant du DTO** | Voir les éléments réglementaires qui ont un impact sur le DTO ; consulter le journal de veille pour l'audit |
| **Administrateur technique** (Frédéric) | Configurer les sources, les règles et les clés ; surveiller le fonctionnement |
| **Membres abonnés** | Recevoir une lettre courte et claire ; pouvoir se désabonner |
| **Contributeurs** (pilotes, amis) | Envoyer facilement un REX ou une information à `securite@` |
| **Auditeur DSAC** (indirect) | Constater que la veille existe, qu'elle est tracée et suivie d'effets |

## 4. Périmètre fonctionnel

### 4.1 Sources

| Type | Exemples | Mode de collecte |
|---|---|---|
| Boîte de collecte | `veille@aeroclub-abbeville.fr` sur ratus80 | IMAP en lecture seule ou lecture directe du Maildir |
| Copie de info@ | Tout le courrier de `info@` (IONOS) | `imapsync` de info@ vers veille@, historique compris |
| Contributions volontaires | `securite@aeroclub-abbeville.fr`, formulaire web | Transfert vers veille@ |
| Boîtes personnelles | RP, président, CPS | Règles de transfert filtrées par expéditeur, définies par chaque titulaire |
| Flux RSS | EASA, BEA, fédérations (si disponibles) | Relevé périodique et archivage local |
| Pages web | veilledoc.dsac.fr, pages d'actualité DSAC, Légifrance | Relevé périodique, détection des changements |

### 4.2 Fonctionnalités

**F1. Collecte (sans IA)**
- F1.1 Relève les boîtes et les flux selon un calendrier configurable (par défaut toutes les heures).
- F1.2 Ouvre les boîtes en lecture seule (`EXAMINE` et `BODY.PEEK`) et ne modifie jamais la boîte source.
- F1.3 Normalise chaque mail en texte brut (HTML retiré) avec ses métadonnées : expéditeur, date, sujet, `Message-ID`.
- F1.4 Évite les doublons grâce au `Message-ID` ou à une empreinte du contenu.
- F1.5 Archive les flux RSS localement, pour constituer progressivement un historique.

**F2. Pièces jointes**
- F2.1 Accepte uniquement les types autorisés (PDF, DOCX, XLSX, ODT, PNG, JPG) et une taille maximale configurable (20 Mo par défaut).
- F2.2 Analyse chaque pièce jointe avec ClamAV (`clamdscan`). Une pièce infectée est mise en quarantaine et une alerte est envoyée à l'administrateur.
- F2.3 Convertit en texte (`pdftotext`, `pandoc`) dans un environnement isolé, sans réseau et sans droits.
- F2.4 Conserve l'original, avec son empreinte SHA-256, comme preuve de veille.
- F2.5 (Option) Désarme les fichiers en convertissant les PDF en images puis de nouveau en PDF.

**F3. Pré-tri (sans IA)**
- F3.1 S'appuie sur une liste de retenue (expéditeurs officiels : DSAC, fédérations, EASA, BEA) et une liste d'exclusion (fournisseurs, banque, assurance, publicités).
- F3.2 Seuls les cas indéterminés passent au classement par IA.

**F4. Classement par IA, mail par mail**
- F4.1 Envoie à l'IA uniquement l'expéditeur, le sujet et les 1 000 premiers caractères du texte.
- F4.2 Reçoit une réponse JSON : `{securite: bool, categorie, confiance, raison}`.
- F4.3 Un script applique la décision. Un mail écarté va dans le dossier « écartés », conservé 30 jours puis supprimé.
- F4.4 Envoie chaque mois au RP la liste des sujets écartés, pour un contrôle rapide.
- F4.5 Les catégories sont : `reglementation`, `info_securite_officielle`, `rex`, `notam_aerodrome`, `navigabilite`, `formation`, `autre_securite`.

**F5. Rédaction de la lettre par IA**
- F5.1 Une fois par mois (date configurable), regroupe les éléments retenus sur la période.
- F5.2 L'IA reçoit les textes et produit un **JSON structuré** (voir § 6.2), jamais du HTML.
- F5.3 Chaque élément contient : titre, résumé de 3 à 6 lignes, « ce que ça change pour nous », public concerné (avion, planeur, ULM, instructeurs, tous), urgence, source ou sources (URL et identifiant interne).
- F5.4 La lettre est organisée en rubriques :
  1. À retenir ce mois-ci ;
  2. Réglementation ;
  3. Informations sécurité officielles ;
  4. Retours d'expérience (non officiels) ;
  5. Vie du club / sécurité locale.
- F5.5 Les REX sont anonymisés : pas de nom, pas d'immatriculation, pas de lieu précis, sauf mention d'accord.
- F5.6 L'IA signale à part, en dehors de la lettre :
  - les éléments qui pourraient appeler une notification à l'autorité (§ H-6 du manuel) ;
  - les éléments qui pourraient appeler une modification du manuel, avec le chapitre concerné.

**F6. Contrôles automatiques après rédaction**
- F6.1 Le JSON est validé par rapport à son schéma ; s'il est invalide, la rédaction est relancée une fois, puis une alerte est envoyée.
- F6.2 Liens : seules sont acceptées les URL présentes dans les sources d'entrée **et** appartenant à la liste de domaines autorisés. Les autres sont retirées et signalées au validateur.
- F6.3 La lettre est limitée en taille (par défaut 12 éléments).
- F6.4 Les données personnelles résiduelles (adresses mail, téléphones, immatriculations F-XXXX) sont repérées par expression régulière et signalées.

**F7. Validation humaine**
- F7.1 Le brouillon s'affiche dans un écran web : chaque élément, avec ses sources et les alertes des contrôles F6.
- F7.2 Le validateur peut modifier, supprimer ou ajouter un élément, puis cliquer sur « Valider ».
- F7.3 Rien n'est envoyé sans validation explicite. Un brouillon non validé sous 15 jours déclenche une relance.

**F8. Diffusion (sans IA)**
- F8.1 La lettre est mise en page avec un modèle fixe (HTML et texte).
- F8.2 Les destinataires viennent uniquement de la base des abonnés.
- F8.3 Envoi par SMTP ou par listmonk, avec un lien de désinscription en un clic.
- F8.4 La lettre est publiée en PDF ou en page web dans les archives (site de gestion).

**F9. Journal de veille (pour l'audit)**
- F9.1 Pour chaque élément : date de réception, source, décision (retenu ou écarté, avec la raison), lettre de diffusion, suite donnée (aucune, information, modification du manuel, notification).
- F9.2 Export PDF ou CSV par période, présentable à la DSAC.
- F9.3 Conservation d'au moins 36 mois (cohérent avec l'AltMOC Alt/26/0006).

**F10. Abonnements**
- F10.1 Inscription et désinscription par double confirmation (opt-in).
- F10.2 Version 1 : liste propre au service. Version 2 : synchronisation avec les membres GVV.

## 5. Exigences de sécurité

| # | Exigence |
|---|---|
| S1 | **Séparation des rôles** : collecteur, rédacteur et diffuseur sont trois processus distincts, sous trois utilisateurs système distincts. |
| S2 | **IA sans outils** : les appels à l'API Claude ne déclarent aucun outil (*tools*). Le rédacteur ne peut que produire du texte. |
| S3 | Le rédacteur n'a **aucun identifiant** IMAP ou SMTP dans son environnement. Un pare-feu sortant ne lui laisse joindre que `api.anthropic.com`. |
| S4 | Le contenu des mails est transmis à l'IA comme une **donnée délimitée** (balises), avec la consigne d'ignorer toute instruction qu'il contient. Cette protection est secondaire : S1 à S3 suffisent à elles seules. |
| S5 | Le classement se fait **mail par mail** : un mail piégé ne peut influencer que sa propre décision. |
| S6 | Les liens sont limités à une liste de domaines autorisés (F6.2). |
| S7 | Les pièces jointes passent par l'antivirus, sont converties dans un environnement isolé et filtrées par type (F2). |
| S8 | Les secrets (IMAP, SMTP, clé API) sont stockés dans le `.env` du seul processus concerné, avec des droits 600. |
| S9 | Chaque appel à l'IA est journalisé : identifiant de la requête, tokens consommés et empreinte des entrées. Le contenu n'est pas conservé au-delà de 30 jours. |
| S10 | Un budget mensuel plafonné de tokens coupe le système en cas de dépassement. |

## 6. Architecture technique

### 6.1 Vue d'ensemble

```
info@ (IONOS) ──imapsync──┐
securite@ ────────────────┤
transferts personnels ────┼──► veille@ (Dovecot, ratus80)
                          │
RSS / pages web ──────────┴──► [Collecteur] ──► [ClamAV + conversion isolée]
                                     │
                              [Pré-tri par règles]
                                     │
                         [Classement IA, mail par mail] ──► écartés (30 j)
                                     │ retenus
                              base « éléments »
                                     │ (mensuel)
                              [Rédacteur IA] ──► JSON ──► [Contrôles F6]
                                                               │
                                                    [Écran de validation]
                                                               │ validé
                                                    [Diffuseur] ──► abonnés
                                                               │
                                                    [Journal de veille]
```

### 6.2 Format de sortie du rédacteur (extrait)

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

### 6.3 Pile technique

- **Application** : Laravel (base Laraskel), sur ratus80 (Hestia, PHP-FPM).
- **Commandes** : `veille:collecter`, `veille:classer`, `veille:rediger`, `veille:diffuser`, `veille:purger`, toutes lancées par le planificateur Laravel ou par cron.
- **Messagerie** : Dovecot sous Hestia pour veille@ ; `imapsync` pour la copie de info@ ; `webklex/laravel-imap` en lecture.
- **IA** : API Claude. Un petit modèle (Haiku) pour le classement, un modèle plus capable (Sonnet) pour la rédaction.
- **Antivirus et conversion** : ClamAV (`clamd`, `freshclam`) ; `pdftotext` et `pandoc` lancés dans `bubblewrap` ou `firejail`.
- **Envoi** : SMTP du domaine, ou listmonk.
- **Données** : MySQL ou SQLite. Tables : `sources`, `elements`, `pieces_jointes`, `decisions`, `lettres`, `abonnes`, `journal_ia`.

### 6.4 Volumétrie et coûts estimés

- info@ : quelques centaines de mails par mois, dont environ 10 à 20 % classés par l'IA après le pré-tri.
- Coût de l'IA : estimé à quelques euros par mois au plus. À confirmer avec les tarifs en vigueur de l'API.

## 7. RGPD et conformité

- **Traitement à inscrire au registre** du club. Finalité : veille sécurité et information des membres. Base légale : intérêt légitime (sécurité des vols, obligation DTO.GEN.210). Sous-traitant : Anthropic (API). Durées : 30 jours pour les mails écartés, 36 mois pour les éléments retenus et le journal.
- **Décision du bureau** pour la copie intégrale de info@, et **information des membres**.
- **Minimisation** : seuls des extraits sont envoyés à l'IA pour le classement.
- **Anonymisation** des REX ; accord de l'auteur avant de publier un REX venu d'un membre.
- **Droits sur les contenus** : résumer et renvoyer vers la source. Pas de republication intégrale, en particulier pour les contenus réservés aux licenciés (REX FFA).
- **Mention dans chaque lettre** : « Synthèse indicative, préparée avec une assistance automatisée et validée par le CPS. Seuls les textes officiels font foi. »

## 8. Plan de livraison

| Phase | Contenu | Critère de sortie |
|---|---|---|
| **P0. Test rétroactif** (2 semaines) | `imapsync` de l'historique de info@ (juillet à septembre 2026), rédaction de 3 lettres de test à partir des seules sources du mois, comparaison avec la liste de référence du RP | Rappel ≥ 90 %, précision ≥ 70 %, aucune erreur de fond |
| **P1. MVP ACS** (1 à 2 mois) | F1 à F9 pour l'ACS, envoi à un petit groupe pilote (bureau et instructeurs) | 3 lettres mensuelles validées, relecture ≤ 30 min chacune |
| **P2. Ouverture aux membres** | Envoi à tous les abonnés ; ajout de `securite@` et du formulaire de contribution | Taux de désabonnement < 5 % ; retours positifs du CPS |
| **P3. Service mutualisé** (option) | Séparation entre service central (sources publiques, multi-locataire) et module club (sources privées) ; module GVV ; profils DTO | 3 à 5 clubs pilotes satisfaits ; décision sur la monétisation |

## 9. Risques

| Risque | Impact | Parade |
|---|---|---|
| Information fausse ou mal résumée | Élevé | Validation humaine obligatoire, lien vers la source, mention « seuls les textes officiels font foi » |
| Mail piégé (injection de prompt) | Moyen | S1 à S6 : IA sans outils, classement mail par mail, liens filtrés, validation humaine |
| Pièce jointe malveillante | Moyen | ClamAV, liste de types, conversion isolée, désarmement en option |
| Élément important écarté à tort | Moyen | Liste de retenue des expéditeurs officiels, dossier « écartés » sur 30 jours, contrôle mensuel |
| Fuite de données personnelles | Élevé | Minimisation, anonymisation, contrôle F6.4, RGPD (§ 7) |
| Validateur indisponible, lettre jamais envoyée | Faible | Relance à 15 jours, validateur suppléant (RP) |
| Coût de l'IA qui dérape | Faible | Budget plafonné (S10) |
| Changement d'API ou de modèle | Faible | Couche d'abstraction LLM (Laraskel), tests de non-régression sur le jeu P0 |

## 10. Questions ouvertes

1. Qui est le validateur principal : le CPS, ou le RP en suppléance ?
2. Quel jour d'envoi chaque mois ?
3. Faut-il une alerte immédiate, hors lettre mensuelle, pour les informations urgentes (consigne de navigabilité urgente, NOTAM local) ?
4. Sera-t-elle publiée sur le site de gestion, en accès public ou réservé aux membres ?
5. Quand faut-il synchroniser les abonnés avec GVV : dès la version 1, ou en version 2 ?
6. Le bureau valide-t-il la copie intégrale de info@ ?
7. Quels flux RSS et pages web retenir ? Il faut inventorier les sources effectivement disponibles.
8. Faut-il ajouter au § B-9 du manuel une mention de l'outil comme moyen de veille ?

## Annexe A : domaines autorisés (première liste)

`aviation-civile.gouv.fr`, `ecologie.gouv.fr`, `sia.aviation-civile.gouv.fr`, `veilledoc.dsac.fr`, `legifrance.gouv.fr`, `eur-lex.europa.eu`, `easa.europa.eu`, `bea.aero`, `ffa-aero.fr`, `ffvp.fr`, `ffplum.fr`, `aeroclub-abbeville.fr`

## Annexe B : critères de la lettre

- Elle se lit en 5 minutes au plus.
- Chaque élément répond à trois questions : *quoi ?*, *qui est concerné ?*, *que dois-je faire ?*
- Les éléments sont classés par impact pour l'ACS, pas par date.
- Les REX sont présentés comme des témoignages, pas comme des règles.
