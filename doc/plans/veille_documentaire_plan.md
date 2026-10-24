# Plan — Veille documentaire (newsletter de veille sécurité)

Date : 24 octobre 2026

## Références
- PRD : [doc/prds/PRD_Veille_Securite_Automatisee.md](../prds/PRD_Veille_Securite_Automatisee.md)
- Conception : [doc/design_notes/veille_documentaire_design.md](../design_notes/veille_documentaire_design.md)

## Objectif

Livrer en cinq phases le système de newsletter de veille sécurité et réglementaire : validation de l'infrastructure (P0), abonnements et archive (P1), étape 1 avec un brouillon généré sur Claude Desktop puis relu, validé et diffusé via GVV (P2), étape 2 avec un agent autonome sur le serveur d'exploitation piloté depuis GVV (P3), ouverture à tous les abonnés (P4).

## Feature flag

Toute la fonctionnalité est protégée par `$config['veille']` dans `program.php`. Le flag est `false` par défaut dans `program.example.php`. Chaque contrôleur et script CLI vérifie ce flag avant toute logique.

## Hypothèses de réalisation

- Étape 1 : l'administrateur génère le brouillon avec Claude Desktop sous Windows, à partir du kit téléchargé depuis GVV. Le fichier de contexte mis au point pendant le spike (`tmp/Veille/veille_agent_contexte.md`, v0.8) sert de version initiale des consignes.
- Étape 2 : l'agent est `claude` CLI en mode non interactif, avec le serveur MCP Playwright, installé pour tout le système sous l'identité `veille-agent`. Il est mis au point sur la machine de développement (gvv.net), puis le serveur d'exploitation (VM Oracle Cloud sous Ubuntu) est configuré par le même script.
- La révision du brouillon (intégration des suggestions acceptées) appelle l'API Anthropic depuis GVV ; le retrait des notes internes est déterministe.
- La lecture de la boîte mail utilise `Newsletter_imap` (cURL, l'extension `imap_*` étant absente du serveur).
- L'envoi email s'appuie sur `MY_Email`, configuré avec le compte `veille@aeroclub-abbeville.fr`.
- La configuration spécifique à la veille est dans `application/config/newsletter.php` (non versionné), éditée depuis `newsletter_admin/configuration`.
- La prochaine migration disponible est la 182.
- Le SMS optionnel (alertes immédiates) réutilise `Brevo_sms_adapter`.
- La génération PDF des newsletters finalisées utilise TCPDF (déjà présent).

---

## P0 — Validation de l'infrastructure

**P0.0 — Feature flag** (0,5 j)
- [x] Ajouter `$config['veille'] = false;` dans `program.example.php` avec commentaire explicatif
- [x] Vérification du flag dans le constructeur de chaque contrôleur (`newsletter_admin`, `newsletter`, `newsletter_public`) : `if (!$this->config->item('veille')) show_404();`
- [x] Masquage des entrées de menu liées à la veille quand le flag est désactivé (toutes les entrées sont dans des blocs `if (config('veille'))`)
- [ ] Les scripts CLI `bin/newsletter_*.php` vérifient le flag et s'arrêtent proprement (exit 0 avec message de log) s'il est désactivé
- [x] Activer `$config['veille'] = true;` dans `program.php` de l'environnement de développement pour la suite du développement

**P0.1 — Page de configuration newsletter** (1 j)
- [x] Créer `application/config/newsletter.example.php` avec toutes les clés commentées
- [x] Créer `application/config/newsletter.php` de développement
- [x] Page `newsletter_admin/configuration` : formulaire de saisie, sauvegarde dans `newsletter.php`
- [x] Mot de passe IMAP stocké en base64 ; champ masqué, non réaffiché en clair
- [x] Créer la bibliothèque `Newsletter_imap` via PHP cURL (extension `imap_*` absente du serveur) : connexion, comptage depuis une date, liste sujets avec filtre, corps, structure MIME et pièces jointes
- [x] `newsletter.example.php` dans le dépôt ; `newsletter.php` dans `.gitignore`

**P0.2 — Pages de test email, IMAP et IA** (2 j)
- [x] `newsletter_admin/test_email` : envoi d'un email de test depuis l'adresse configurée vers les destinataires saisis (pas de redirection `test_email`), résultat affiché
- [x] `newsletter_admin/test_imap` : connexion, comptage depuis une date, liste sujets avec filtre, corps du message le plus récent, pièces jointes — résultats vert/rouge
- [x] `newsletter_admin/test_ai` : requête simple à l'API Anthropic
- [x] Spike de génération `newsletter_admin/test_generate` (appel API sans outil) : conclusion, il ne satisfait pas les exigences F1.1 à F1.7, il est remplacé par l'agent de P3 (voir conception, « Choix de l'agent IA »)
- [x] Pages accessibles depuis le menu Dev (protégées par `dev_users`)

**P0.3 — Tests** (0,5 j)
- [ ] PHPUnit `NewsletterImapTest.php` : curl bouchonné, comptage, filtre pattern, décodage MIME header
- [ ] PHPUnit `NewsletterConfigTest.php` : lecture et écriture de `newsletter.php`, encodage/décodage du mot de passe
- [ ] Playwright `newsletter-infra-smoke.spec.js` : accès config, test email, test IMAP

**Estimation P0 : ~4 jours (réalisé, hors tests P0.3).**

**Validation P0** : un club-admin peut accéder à la page de validation, déclencher les trois tests et constater que chacun réussit sur l'environnement de production.

---

## P1 — Abonnements et archive (indépendant de la génération automatique)

**P1.1 — Migrations** (1 j)
- [x] Migration `181_newsletter.php` : tables `newsletters`, `newsletter_reviews`, `newsletter_subscribers`, `newsletter_sendings` (champs d'audit inclus — cf. conception)
- [x] `application/config/migration.php` → 181
- [x] `down()` symétrique

**P1.2 — Modèles** (1 j)
- [x] `newsletter_model` : CRUD newsletters, gestion des états (`draft`, `reviewing`, `validating`, `approved`, `published`), chemin brouillon et PDF
- [x] `newsletter_subscribers_model` : inscription, confirmation, désinscription, accès admin, unicité de l'email, génération des tokens

**P1.3 — Page publique abonnement/désinscription** (1,5 j)
- [x] Contrôleur `newsletter_public.php`, sans authentification, routes `newsletter/subscribe` et `newsletter/unsubscribe/:token`
- [x] Formulaire d'inscription : nom, prénom, email ; envoi d'un email de confirmation avec lien contenant `confirmation_token`
- [x] Page de confirmation et activation de l'abonnement au clic
- [x] Page de désinscription via `unsubscribe_token` (lien dans chaque newsletter envoyée) ou formulaire email
- [x] Libellés FR/EN/NL

**P1.4 — Administration des abonnés** (0,5 j)
- [x] Dans `newsletter_admin.php` : liste des abonnés (nom, email, date, statut confirmé), suppression d'un abonnement, accès `club-admin`
- [x] Carte « Newsletter » dans le tableau de bord Administration système

**P1.5 — Archive membres** (1 j)
- [x] Contrôleur `newsletter.php`, action `archive`, accès membres actifs connectés
- [x] Liste des newsletters publiées (titre, période, date de publication), ouverture du PDF dans un onglet
- [x] Entrée dans la navigation GVV (menu documents ou communication)
- [x] Upload manuel d'un PDF depuis l'administration (`newsletter_admin/publish`) pour les newsletters créées par un humain

**P1.6 — Envoi aux abonnés** (0,5 j)
- [x] À la publication (manuelle ou automatique) : envoi groupé via `MY_Email` à tous les abonnés confirmés, pièce jointe PDF, texte personnalisé « Cher(e) {prénom} {nom}, … », lien de désinscription dans le corps
- [x] Enregistrement de chaque envoi dans `newsletter_sendings`
- [x] Diffusion déclenchée par un administrateur : envoi aux adresses réelles des abonnés, sans redirection `test_email` (réservée aux tests automatiques)

**P1.7 — Tests** (1,5 j)
- [x] PHPUnit MySQL `NewsletterModelsTest.php` : CRUD newsletters, états, abonnements (inscription, double opt-in, désinscription, token unique, unicité email, accès admin), historique des envois ; fixtures créées et supprimées par les tests
- [x] PHPUnit migration 181 : `up()` / `down()` avec restauration complète
- [x] Playwright `newsletter-subscribe-smoke.spec.js` : parcours complet inscription → confirmation → désinscription, accès archive membre, accès admin

**Estimation P1 : ~7 jours (réalisé).**

**Validation P1** : un visiteur peut s'abonner via l'URL publique, recevoir l'email de confirmation, puis se désabonner. Un admin peut uploader une newsletter PDF et l'envoyer aux abonnés. Un membre connecté consulte l'archive.

---

## P2 — Étape 1 : brouillon généré sur Claude Desktop, cycle de relecture, validation et diffusion dans GVV

Le cycle est piloté par l'administrateur : import du brouillon d'une période → transmission aux relecteurs → récupération des réponses → tri des suggestions → nouvelle version par l'agent IA → nouveau tour ou validation → finalisation et diffusion.

**P2.1 — Migration et modèles** (1,5 j)
- [ ] Migration `182_newsletter_runs.php` : tables `newsletter_contexts` et `newsletter_runs` ; colonnes de `newsletter_reviews` pour les suggestions (`message_id`, `kind`, `decision`, `admin_text`, `decided_by`, `decided_at`, `newsletter_id` nullable) ; champs d'audit — cf. conception ; `down()` symétrique
- [ ] `application/config/migration.php` → 182
- [ ] `newsletter_contexts_model` : création d'une version, version active, liste, restauration
- [ ] `newsletter_runs_model` : création, changement de statut, rattachement à une newsletter, liste pour le suivi
- [ ] `newsletter_reviews_model` : enregistrement d'une suggestion, anti-doublon par `message_id`, décisions, suggestions acceptées d'une version, suggestions reportées en attente
- [ ] Conservation des versions successives du brouillon d'une newsletter

**P2.2 — Consignes dans GVV** (1,5 j)
- [ ] Page `newsletter_admin/context` : édition Markdown des consignes, enregistrement en nouvelle version avec commentaire
- [ ] Historique des versions : consultation, comparaison avec la version active, restauration
- [ ] Chargement initial à partir du fichier de contexte v0.8 du spike

**P2.3 — Kit pour l'agent de bureau** (0,5 j)
- [ ] Téléchargement en une archive des consignes actives, de l'inventaire courant (celui du dernier paquet importé, ou vide) et des suggestions d'articles reportées

**P2.4 — Import du brouillon d'une période** (1,5 j)
- [ ] Importeur commun aux deux étapes : brouillon obligatoire ; rapport d'activité et inventaire joints, leur absence signalée ; archivage hors de l'espace servi directement par le serveur web ; création de la newsletter `draft` (période, version 1) et de l'exécution associée (version des consignes, origine, statut)
- [ ] Formulaire d'import manuel (origine `desktop`) : période, brouillon, rapport, inventaire, modèle et coût ; résultat affiché, erreurs explicites
- [ ] L'inventaire importé devient l'inventaire courant

**P2.5 — Suivi des exécutions et rapport d'activité** (1 j)
- [ ] Page `newsletter_admin/runs` : liste des exécutions (date, origine, statut, durée, coût, version des consignes, newsletter produite)
- [ ] Affichage du rapport d'activité d'une exécution, accessible depuis la newsletter ; accès RP, représentant du DTO et `club-admin`
- [ ] Conservation des paquets 36 mois au moins

**P2.6 — Transmission aux relecteurs** (1 j)
- [ ] Action `send_for_review` (`draft` → `reviewing`) : email aux relecteurs configurés avec le brouillon, une référence édition/version dans le sujet et la date limite de réponse (délai configurable, deux jours par défaut)
- [ ] Délai de réponse et liste des relecteurs dans `newsletter_admin/configuration`
- [ ] Option de transmission automatique à l'import (utilisée par P3)
- [ ] La page de la newsletter affiche la date de transmission et la date limite

**P2.7 — Récupération des réponses** (1,5 j)
- [ ] Action « Récupérer les réponses » : lecture de la boîte de veille via `Newsletter_imap`, réponses portant la référence de la version transmise, expéditeur relecteur autorisé, anti-doublon par identifiant de message
- [ ] Enregistrement de chaque réponse comme suggestion `en_attente`, pièces jointes conservées
- [ ] Compte rendu affiché : réponses retenues, réponses écartées avec le motif (expéditeur non autorisé, sans référence, déjà récupérée)

**P2.8 — Tri des suggestions** (1,5 j)
- [ ] Liste des suggestions de la version courante et des suggestions reportées des éditions précédentes
- [ ] Par suggestion : nature (remarque ou article), décision accepter, modifier (texte corrigé), rejeter, reporter (articles seulement)
- [ ] Une suggestion reportée et reprise est rattachée à l'édition courante

**P2.9 — Nouvelle version par l'agent IA** (1,5 j)
- [ ] Action « Demander une nouvelle version » : appel à l'API Anthropic avec le brouillon courant et les suggestions acceptées ou modifiées ; consignes de révision dans les consignes de l'agent
- [ ] Enregistrement de la version N+1, `review_iteration` incrémenté, retour à `draft`
- [ ] Affichage des différences avec la version précédente
- [ ] Erreur d'appel (clé absente, quota, réponse vide) affichée, version courante inchangée

**P2.10 — Validation** (0,5 j)
- [ ] Action « Valider » depuis `draft`, y compris sans tour de relecture : liste des mentions entre crochets restantes, confirmation, transition → `approved`, enregistrement de `approved_by`, `approved_at` et de la version validée

**P2.11 — Finalisation et publication** (1,5 j)
- [ ] Retrait déterministe de la section interne du brouillon validé
- [ ] Conversion Markdown → PDF via TCPDF
- [ ] Archivage du PDF, mise à jour `pdf_path`, transition `approved` → `published`
- [ ] Envoi aux abonnés (P1.6)

**P2.12 — Tests** (2 j)
- [ ] PHPUnit MySQL `NewsletterContextsRunsTest.php` : versions des consignes, restauration, exécutions ; migration 182 `up()` / `down()` avec restauration
- [ ] PHPUnit `NewsletterImporterTest.php` : brouillon seul, paquet complet, inventaire courant ; fichiers créés supprimés par le test
- [ ] PHPUnit MySQL `NewsletterReviewCycleTest.php` : transmission (envoi bouchonné), récupération (IMAP bouchonné, anti-doublon, expéditeur non autorisé), décisions, report à l'édition suivante, nouvelle version (API bouchonnée), validation, retrait de la section interne ; fixtures auto-nettoyées
- [ ] Playwright `newsletter-review-smoke.spec.js` : édition des consignes, import d'un brouillon, transmission, tri d'une suggestion, validation, publication

**P2.13 — Documentation** (0,5 j)
- [ ] Guide admin `doc/users/fr/20_newsletter_veille.md` : consignes, kit, import, transmission, tri des réponses, nouvelle version, validation, publication ; abonnement et archive pour les membres

**Estimation P2 : ~16 jours.**

**Validation P2** : deux éditions générées avec Claude Desktop à partir du kit GVV, importées, relues par email, révisées par l'agent IA, validées et diffusées via GVV ; le rapport d'activité de chacune est consultable.

---

## P3 — Étape 2 : agent autonome sur le serveur, piloté depuis GVV

Prérequis : vérifier la forme de la VM Oracle (Ampere ARM ou AMD micro) et la mémoire disponible pour le navigateur (PRD, question ouverte).

**P3.1 — Script d'installation commun** (1 j)
- [ ] `bin/veille/install.sh`, idempotent : identité `veille-agent`, dossier d'échange `/srv/veille` (`config/`, `inbox/`, `runs/`) avec droits séparés entre `www-data` et `veille-agent`
- [ ] Node, `claude` CLI et Chromium pour Playwright installés pour tout le système (pas dans `~/.nvm` ni `~/.cache` d'un utilisateur)
- [ ] Unités systemd : timer quotidien, surveillance des demandes (unité `.path`), enchaînement extraction → agent → import sous les bonnes identités
- [ ] Valeurs propres à la machine (URL GVV, clé d'API) dans un fichier non versionné ; exemple versionné
- [ ] Exécuté et vérifié sur la machine de développement

**P3.2 — Publication de la configuration par GVV** (1 j)
- [ ] À chaque enregistrement des consignes ou des paramètres : écriture dans `config/` des consignes actives, de l'inventaire courant et des paramètres (date d'exécution, modèle, plafonds)
- [ ] Paramètres date d'exécution, modèle et plafonds dans `newsletter_admin/configuration`
- [ ] Bouton « Lancer maintenant » : dépôt d'une demande, exécution `requested` visible dans le suivi

**P3.3 — Extraction des mails** (0,5 j)
- [ ] Script `bin/newsletter_extract_mail.php` (`www-data`) : nouveaux messages de veille@ en lecture seule vers `inbox/`, depuis la dernière extraction

**P3.4 — Lanceur d'agent** (1 j)
- [ ] Préparation du répertoire de l'exécution (consignes, inventaire, mails), lancement de `claude` non interactif avec liste fermée d'outils, plafonds de durée et de coût, trace des appels d'outils
- [ ] Écriture des métadonnées (version des consignes, modèle, durée, coût, statut) ; échec enregistré si le paquet est incomplet

**P3.5 — Import automatique** (0,5 j)
- [ ] Unité d'import (`www-data`) : réutilise l'importeur de P2.4 (origine `schedule` ou `manual`), puis transmission automatique aux relecteurs (P2.6)
- [ ] Affichage de la trace à côté du rapport d'activité

**P3.6 — Alertes d'échec** (0,5 j)
- [ ] Script `bin/newsletter_watchdog.php`, timer quotidien : exécution en échec ou interrompue par un plafond, aucune exécution réussie après la date prévue → email à l'administrateur et à l'administrateur technique

**P3.7 — Consignes pour l'exécution autonome** (0,5 j)
- [ ] Nouvelle version des consignes : exécution réelle et non simulée, emplacement des fichiers, lecture de `inbox/` et des suggestions reportées, calcul des empreintes, règle « pas de nouveauté sans date établie »

**P3.8 — Retrait du spike** (0,5 j)
- [ ] Suppression de `newsletter_admin/test_generate` et de `bin/newsletter_generate.php`, remplacés par l'exécution réelle

**P3.9 — Alertes immédiates (optionnel)** (1 j)
- [ ] Signal d'alerte dans le paquet d'exécution, lu par l'importeur ; notification email ou SMS (via `Brevo_sms_adapter`) aux destinataires désignés selon `newsletter_alert_channel`
- [ ] Option `newsletter_alerts_enabled` dans la page de configuration

**P3.10 — Tests et exécution pilote** (2 j)
- [ ] PHPUnit : publication de la configuration, extraction des mails (IMAP bouchonné), import automatique, alertes ; fichiers créés supprimés par les tests
- [ ] Playwright `newsletter-agent-smoke.spec.js` : modification des consignes, « Lancer maintenant », suivi de l'exécution (lanceur bouchonné)
- [ ] Exécution pilote réelle sur la machine de développement, sans intervention humaine ; vérification des exigences F1.1 à F1.7 (sources, nouveautés, modifications, analyse, références réelles, rapport) avec la même méthode que l'évaluation du spike

**P3.11 — Mise en production** (0,5 j)
- [ ] Exécution de `install.sh` sur la VM Oracle, configuration propre à la machine, première exécution lancée depuis GVV

**Estimation P3 : ~9 jours.**

**Validation P3** : deux éditions générées sur le serveur d'exploitation sans intervention humaine, satisfaisant F1.1 à F1.7, importées et transmises automatiquement aux relecteurs.

---

## P4 — Ouverture à tous les abonnés

P4 est conditionné par la validation de P3 sur au moins deux éditions réelles.

- [ ] Communication aux membres sur le mécanisme d'abonnement
- [ ] Ouverture de la possibilité pour les membres de proposer des articles (contribution par email, traitement dans le cycle de relecture)

**Estimation P4 : ~2 jours.**

---

## Récapitulatif

| Phase | Contenu | Estimation | État |
|---|---|---|---|
| P0 | Feature flag, configuration, validation infrastructure | 4 j | réalisé, hors tests |
| P1 | Abonnements et archive | 7 j | réalisé |
| P2 | Étape 1 : brouillon Claude Desktop, cycle GVV | 16 j | à faire |
| P3 | Étape 2 : agent autonome sur le serveur | 9 j | à faire |
| P4 | Ouverture aux membres | 2 j | à faire |
| **Total restant** | | **~27 jours** | |

P2 dépend de P0 (IMAP/SMTP) et de P1 (tables, modèles, envoi aux abonnés). P3 dépend de P2 (consignes, importeur, cycle de relecture).
