# Plan — Veille documentaire (newsletter de veille sécurité)

Date : 23 octobre 2026

## Références
- PRD : [doc/prds/PRD_Veille_Securite_Automatisee.md](../prds/PRD_Veille_Securite_Automatisee.md)
- Conception : [doc/design_notes/veille_documentaire_design.md](../design_notes/veille_documentaire_design.md)

## Objectif

Livrer en quatre phases le système de newsletter de veille sécurité et réglementaire : validation de l'infrastructure (P0), mécanisme d'abonnement et d'archive indépendant (P1), génération automatique par agent IA avec cycle de relecture email (P2), ouverture à tous les abonnés (P3).

## Feature flag

Toute la fonctionnalité est protégée par `$config['veille']` dans `program.php`. Le flag est `false` par défaut dans `program.example.php`. Chaque contrôleur et script CLI vérifie ce flag avant toute logique.

## Hypothèses de réalisation

- L'agent IA est `claude` CLI en mode non interactif (`claude -p`), déjà disponible sur le serveur.
- La lecture de la boîte mail utilise les fonctions natives `imap_*` de PHP (extension activée sur le serveur).
- L'envoi email s'appuie sur `MY_Email` (bibliothèque existante), configuré avec le compte `veille@aeroclub-abbeville.fr`.
- Les timers systemd (cron mensuel, quotidien, surveillance) sont créés sur ratus80 ; les scripts CLI sont placés dans `bin/`.
- La configuration spécifique à la veille (compte IMAP, relecteurs, validateurs, chemin du fichier de contexte, périodicité) est ajoutée à `program.php` / `program.example.php`.
- La prochaine migration disponible est la 181.
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

**P0.2 — Pages de test email et IMAP** (2 j)
- [x] `newsletter_admin/test_email` : envoi d'un email de test depuis l'adresse configurée, résultat affiché
- [x] `newsletter_admin/test_imap` : connexion, comptage depuis une date, liste sujets avec filtre, corps du message le plus récent, pièces jointes — résultats vert/rouge
- [x] Pages accessibles depuis le menu Dev (protégées par `dev_users`)
- [ ] Test 3 — **API IA** : requête simple, fichier de contexte, génération Markdown/PDF (P0 partie IA — à faire)

**P0.3 — Tests et documentation** (0,5 j)
- [ ] PHPUnit `NewsletterImapTest.php` : curl bouchonné, comptage, filtre pattern, décodage MIME header
- [ ] PHPUnit `NewsletterConfigTest.php` : lecture et écriture de `newsletter.php`, encodage/décodage du mot de passe
- [ ] Playwright `newsletter-infra-smoke.spec.js` : accès config, test email, test IMAP (à faire après que les identifiants soient configurés)

**Estimation P0 email : ~3 jours (réalisé). Reste : partie IA (~1,5 j).**

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
- [ ] Métadonnées dans `Gvvmetadata.php` pour les listes d'administration (non requis pour P1 — les listes utilisent du HTML Bootstrap direct)

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

**P1.7 — Tests** (1,5 j)
- [x] PHPUnit MySQL `NewsletterModelsTest.php` : CRUD newsletters, états, abonnements (inscription, double opt-in, désinscription, token unique, unicité email, accès admin), historique des envois ; fixtures créées et supprimées par les tests
- [x] PHPUnit migration 181 : `up()` / `down()` avec restauration complète
- [x] Playwright `newsletter-subscribe-smoke.spec.js` : parcours complet inscription → confirmation → désinscription, accès archive membre, accès admin

**Estimation P1 : ~7 jours.**

**Validation P1** : un visiteur peut s'abonner via l'URL publique, recevoir l'email de confirmation, puis se désabonner. Un admin peut uploader une newsletter PDF et l'envoyer aux abonnés. Un membre connecté consulte l'archive.

---

## P2 — Génération automatique (MVP)

**P2.1 — Fichier de contexte** (0,5 j)
- [ ] Interface d'édition du fichier de contexte dans `newsletter_admin/context` : éditeur de texte (Markdown), sauvegarde sur le serveur dans `newsletter_context_path` (défini en P0.1)
- [ ] Le fichier contient : liste des sources (IMAP, RSS, URL), critères de sélection, directives de rédaction, description du club et des activités
- [ ] Le chemin du fichier de contexte est lu depuis `newsletter.php`, jamais codé en dur

**P2.2 — Script de génération (`bin/newsletter_generate.php`)** (2 j)
- [ ] Script CLI bootstrappant CodeIgniter (comme les scripts existants dans `bin/`)
- [ ] Lit le fichier de contexte depuis `newsletter_context_path` et les sources via `Newsletter_imap`
- [ ] Prépare le répertoire de travail (`newsletter_output_path/YYYY-MM/`) et y dépose les sources pré-collectées en fichiers texte lisibles par l'agent
- [ ] Appelle `claude` avec `--bare --print --permission-mode bypassPermissions --allowedTools "Write,Read" --max-budget-usd X --system-prompt "$(cat context.md)"` dans ce répertoire de travail ; enveloppe dans `timeout 300`
- [ ] Capture stdout (résumé de l'agent) et exit code ; une sortie non-zéro est journalisée et remonte une alerte admin
- [ ] Lit `draft.md` depuis le répertoire de travail, crée l'entrée `newsletters` en base avec le statut `draft`
- [ ] Journalise l'exécution (succès, durée, nombre de sources, coût estimé extrait du résumé)

**P2.3 — Timer systemd mensuel** (0,5 j)
- [ ] Timer systemd `newsletter-generate.timer` déclenchant `newsletter_generate.php` le premier lundi de chaque mois (ou la date configurée)
- [ ] Documentation de l'installation dans `README` ou guide admin

**P2.4 — Cron de surveillance** (0,5 j)
- [ ] Script `bin/newsletter_watchdog.php` : vérifie qu'une newsletter en état `draft` ou supérieur a été créée dans la période en cours ; envoie une alerte email à l'administrateur sinon
- [ ] Timer systemd hebdomadaire `newsletter-watchdog.timer`

**P2.5 — Envoi aux relecteurs** (0,5 j)
- [ ] Dans `newsletter_admin.php` : action `send_for_review` (transition `draft` → `reviewing`) — envoi du brouillon par email aux relecteurs configurés, avec lien vers la page de retour GVV et instruction pour répondre directement à l'email
- [ ] Liste des relecteurs lue depuis `newsletter_reviewers` dans `newsletter.php` (configuré en P0.1)

**P2.6 — Retours de relecture via GVV** (1 j)
- [ ] Contrôleur `newsletter_review.php` : formulaire de retour (texte libre, pièce jointe), accès par lien sécurisé (token par relecteur) ou authentification GVV
- [ ] Enregistrement dans `newsletter_reviews` avec `submitted_via = 'gvv'`, `review_iteration` courant
- [ ] Confirmation visuelle à l'envoi

**P2.7 — Collecte des retours email et nouvelles versions** (2 j)
- [ ] Script `bin/newsletter_collect_reviews.php` : lit les réponses au thread de relecture dans la boîte IMAP, identifie les expéditeurs autorisés (adresse dans la liste `newsletter_reviewers`), enregistre les retours dans `newsletter_reviews` avec `submitted_via = 'email'`
- [ ] Si des retours ont été collectés : appelle `claude -p` avec le brouillon courant et les retours, produit une nouvelle version Markdown, incrémente `review_iteration`
- [ ] Envoie la nouvelle version aux relecteurs et les informe des retours intégrés
- [ ] Timer systemd quotidien `newsletter-collect.timer` (exécution en fin de journée)

**P2.8 — Passage en validation** (0,5 j)
- [ ] Dans `newsletter_admin.php` : action `send_for_validation` (transition `reviewing` → `validating`) — envoi de la dernière version aux validateurs (clé `newsletter_validators` dans `program.php`)
- [ ] Les validateurs reçoivent la version finale du cycle de relecture avec les mêmes options (réponse email ou GVV)

**P2.9 — Approbation** (1 j)
- [ ] Collecte des approbations email : script `bin/newsletter_collect_reviews.php` détecte un sujet ou corps contenant un mot-clé d'approbation configurable, provenant d'un validateur autorisé
- [ ] Approbation via GVV : action dans `newsletter_review.php` réservée aux validateurs authentifiés
- [ ] Transition `validating` → `approved`, enregistrement de `approved_by` et `approved_at`

**P2.10 — Finalisation et publication** (1,5 j)
- [ ] Script `bin/newsletter_finalize.php` : appelle `claude -p` avec la version approuvée pour supprimer les remarques internes et générer la version propre en Markdown
- [ ] Conversion Markdown → PDF via TCPDF
- [ ] Archivage du PDF, mise à jour `pdf_path`, transition `approved` → `published`
- [ ] Déclenchement de l'envoi aux abonnés (P1.6)
- [ ] Déclenchement depuis `newsletter_admin.php` ou automatiquement après l'approbation

**P2.11 — Alertes immédiates (optionnel)** (1 j)
- [ ] L'agent lors de la génération peut produire un fichier `alerts.json` signalant des informations urgentes
- [ ] `newsletter_generate.php` lit ce fichier et envoie une notification email ou SMS (via `Brevo_sms_adapter`) aux validateurs selon le canal configuré dans `newsletter.php` (`newsletter_alert_channel`: `email`, `sms`, `both`)
- [ ] Option activable/désactivable par `newsletter_alerts_enabled` dans `newsletter.php`, configurable depuis la page de configuration (P0.1)

**P2.12 — Tests** (2 j)
- [ ] PHPUnit MySQL `NewsletterWorkflowTest.php` : toutes les transitions d'état, envoi aux relecteurs et validateurs (bouchonné), collecte des retours, nouvelles versions, approbation, finalisation ; fixtures auto-nettoyées
- [ ] PHPUnit `NewsletterAgentRunnerTest.php` : script CLI bouchonné (sans appel réel à `claude`), vérification des entrées/sorties
- [ ] Playwright `newsletter-review-smoke.spec.js` : soumission d'un retour via GVV, approbation via GVV, finalisation depuis l'interface admin

**P2.13 — Documentation** (0,5 j)
- [ ] Guide admin : configuration des clés `program.php`, installation des timers systemd, gestion du fichier de contexte
- [ ] Guide utilisateur `doc/users/fr/20_newsletter_veille.md` : abonnement, archive, retours de relecture, approbation

**Estimation P2 : ~14 jours.**

**Validation P2** : un cycle complet est exécuté en environnement de test — déclenchement manuel du script de génération, envoi aux relecteurs, soumission d'un retour par email et via GVV, collecte et intégration par l'agent, approbation, finalisation PDF, réception par un abonné de test.

---

## P3 — Ouverture à tous les abonnés

P3 est conditionné par la validation complète de P2 sur au moins deux éditions réelles.

- [ ] Passage en production : activation du timer mensuel sur ratus80
- [ ] Communication aux membres sur le mécanisme d'abonnement
- [ ] Ouverture de la possibilité pour les membres de proposer des articles (contribution par email, traitement dans le cycle de relecture)

**Estimation P3 : ~2 jours.**

---

## Récapitulatif

| Phase | Contenu | Estimation |
|---|---|---|
| P0 | Feature flag + configuration newsletter + validation infrastructure | 4,5 j |
| P1 | Abonnements et archive | 7 j |
| P2 | Génération automatique MVP | 14 j |
| P3 | Ouverture production | 2 j |
| **Total** | | **~27,5 jours** |

Les phases P0 et P1 sont indépendantes et peuvent être menées en parallèle. P2 dépend de P0 (IMAP/SMTP) et de P1 (tables, modèles, envoi aux abonnés).
