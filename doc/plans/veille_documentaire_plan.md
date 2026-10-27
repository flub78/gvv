# Plan — Veille documentaire (newsletter de veille sécurité)

Date : 25 octobre 2026

## Références
- PRD : [doc/prds/PRD_Veille_Securite_Automatisee.md](../prds/PRD_Veille_Securite_Automatisee.md)
- Conception : [doc/design_notes/veille_documentaire_design.md](../design_notes/veille_documentaire_design.md)

## Objectif

Livrer en quatre phases le système de newsletter de veille sécurité et réglementaire : validation de l'infrastructure (P0), abonnements et archive (P1), cycle de relecture dans GVV des brouillons produits par l'agent de rédaction (P2), ouverture à tous les abonnés (P3). L'agent de rédaction est configuré dans Claude Code, hors de GVV ; il n'est pas couvert par ce plan.

## Feature flag

Toute la fonctionnalité est protégée par `$config['veille']` dans `program.php`. Le flag est `false` par défaut dans `program.example.php`. Chaque contrôleur et script CLI vérifie ce flag avant toute logique.

## Hypothèses de réalisation

- L'agent de rédaction (Claude Code) porte ses consignes, l'état de la veille et son journal. GVV n'échange avec lui que des fichiers : brouillon (.md ou .zip avec `img/`) et consignes de modification (.md ou .zip avec `img/`).
- Le format du brouillon est celui des lettres produites pendant le spike : titre de niveau 1, ligne « Période : du … au … », mention « BROUILLON vN », section interne.
- La lecture de la boîte mail utilise `Newsletter_imap` (cURL, l'extension `imap_*` étant absente du serveur).
- L'envoi email s'appuie sur `MY_Email`, configuré avec le compte `veille@aeroclub-abbeville.fr`.
- La configuration spécifique à la veille est dans `application/config/newsletter.php` (non versionné), éditée depuis `newsletter_admin/configuration`.
- La génération PDF utilise TCPDF (déjà présent) ; les archives zip, l'extension PHP `zip`.

---

## P0 — Validation de l'infrastructure

**P0.0 — Feature flag** (0,5 j)
- [x] Ajouter `$config['veille'] = false;` dans `program.example.php` avec commentaire explicatif
- [x] Vérification du flag dans le constructeur de chaque contrôleur (`newsletter_admin`, `newsletter`, `newsletter_public`) : `if (!$this->config->item('veille')) show_404();`
- [x] Masquage des entrées de menu liées à la veille quand le flag est désactivé (toutes les entrées sont dans des blocs `if (config('veille'))`)
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
- [x] Spike de génération `newsletter_admin/test_generate` (appel API sans outil) : il ne satisfait pas les exigences F1.1 à F1.7 ; la rédaction est confiée à un agent Claude Code hors GVV, le spike est retiré (P2.1)
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
- [x] Liste des newsletters publiées (titre, période, date de publication au format local), ouverture du PDF dans un onglet
- [x] Suppression d'une newsletter publiée par un club-admin depuis l'archive (`newsletter_admin/delete_edition`), avec confirmation
- [x] Entrée dans la navigation GVV (menu documents ou communication)
- [x] Upload manuel d'un PDF depuis l'administration (`newsletter_admin/publish`), retiré en P2.9 : la publication se fait depuis l'édition

**P1.6 — Envoi aux abonnés** (0,5 j)
- [x] À la publication (manuelle ou automatique) : envoi groupé via `MY_Email` à tous les abonnés confirmés, pièce jointe PDF, texte personnalisé « Bonjour {prénom} {nom}, … » (fichiers de langue), invitation à écrire à l'adresse de veille de la configuration pour les remarques et les informations manquantes avec leur source, lien de désinscription dans le corps
- [x] Enregistrement de chaque envoi dans `newsletter_sendings`
- [x] Diffusion déclenchée par un administrateur : envoi aux adresses réelles des abonnés, sans redirection `test_email` (réservée aux tests automatiques)

**P1.7 — Tests** (1,5 j)
- [x] PHPUnit MySQL `NewsletterModelsTest.php` : CRUD newsletters, états, abonnements (inscription, double opt-in, désinscription, token unique, unicité email, accès admin), historique des envois ; fixtures créées et supprimées par les tests
- [x] PHPUnit migration 181 : `up()` / `down()` avec restauration complète
- [x] Playwright `newsletter-subscribe-smoke.spec.js` : parcours complet inscription → confirmation → désinscription, accès archive membre, accès admin

**Estimation P1 : ~7 jours (réalisé).**

**Validation P1** : un visiteur peut s'abonner via l'URL publique, recevoir l'email de confirmation, puis se désabonner. Un admin peut uploader une newsletter PDF et l'envoyer aux abonnés. Un membre connecté consulte l'archive.

---

## P2 — Cycle de relecture dans GVV des brouillons de l'agent

Le cycle est piloté par l'administrateur : import du brouillon d'une période → annotation → transmission aux relecteurs → relevé des réponses → tri des remarques → consignes de modification pour l'agent → import de la version suivante → nouveau tour ou validation → finalisation et diffusion.

**P2.1 — Retrait de l'agent IA de GVV** (0,5 j)
- [x] Suppression du spike de génération (`test_generate`, `bin/newsletter_generate.php`, `bin/claude-newsletter.sh`) et de ses paramètres de configuration
- [x] Suppression des consignes de l'agent, du kit, du suivi des exécutions et du rapport d'activité ; la page `test_ai` est conservée

**P2.2 — Migration et modèles** (1 j)
- [x] Migration `182_newsletter_review_cycle.php` : table `newsletter_annotations` ; colonnes de `newsletter_reviews` pour les remarques (`message_id`, `decision`, `admin_text`, `decided_by`, `decided_at`, `newsletter_id` nullable) ; `newsletters.review_sent_at` et `review_deadline` ; champs d'audit ; `down()` symétrique
- [x] `application/config/migration.php` → 182
- [x] `newsletter_annotations_model` : ajout, suppression, annotations d'une version
- [x] `newsletter_reviews_model` : enregistrement d'une remarque, anti-doublon par `message_id`, décisions, remarques retenues du tour courant, remarques reportées
- [x] Stockage des versions et de leurs images hors de l'espace web (`Newsletter_files`, `newsletter_storage_path`)

**P2.3 — Import d'un brouillon** (1,5 j)
- [x] Fichier `.md` ou archive `.zip` (un Markdown et ses images) ; titre, période et version lus dans le Markdown, absence signalée avec le repère attendu
- [x] Images référencées et absentes signalées ; chemins et formats contrôlés
- [x] Rattachement à l'édition non publiée de même période (version suivante, retour à `draft`) ou création d'une édition ; version déjà importée ou édition validée refusée
- [x] Page `newsletter_admin/import` réduite au choix du fichier
- [x] Suppression d'une édition quel que soit son état depuis la liste des éditions, avec confirmation

**P2.4 — Brouillon annoté** (1,5 j)
- [x] Affichage du brouillon par blocs, avec ses images ; ajout d'un commentaire sous un bloc (libellé « Commentaire du DTO » par défaut) et suppression
- [x] Brouillon annoté (annotations insérées en citations) utilisé pour l'affichage, le PDF de relecture et la publication
- [x] Versions conservées, différences avec la version précédente

**P2.5 — Transmission aux relecteurs** (0,5 j)
- [x] Action `send_for_review` (`draft` → `reviewing`) : email aux relecteurs configurés avec le brouillon annoté en PDF, une référence `[NL-<id> v<N>]` dans le sujet, la date limite de réponse et la demande de signaler les informations manquantes avec leur source (modèle d'email configurable)
- [x] Délai de réponse et liste des relecteurs dans `newsletter_admin/configuration`

**P2.6 — Relevé des réponses** (1 j)
- [x] Bouton « Relever les réponses » sur chaque brouillon transmis (page de l'édition et liste des éditions) : lecture de la boîte de veille, messages reçus depuis la création de l'édition, réponses portant la référence de l'édition (réponses tardives à une version précédente rattachées au tour en cours), expéditeur relecteur autorisé, anti-doublon par identifiant de message
- [x] Chaque réponse enregistrée comme remarque `en_attente`, citation du message d'origine retirée, pièces jointes conservées et téléchargeables
- [x] Compte rendu affiché : messages lus, réponses retenues, réponses écartées avec le motif

**P2.7 — Tri des remarques** (1 j)
- [x] Par remarque : accepter, modifier (texte corrigé), rejeter, reporter à une prochaine édition ; refus explicite d'une décision invalide
- [x] Remarque reportée gardée dans son édition, proposée à la reprise dans les autres éditions ; téléchargement des remarques reportées pour l'agent

**P2.8 — Consignes de modification** (1 j)
- [x] Bouton de téléchargement : Markdown des commentaires de la version et des remarques du tour, ou zip avec ce Markdown et `img/` (images des remarques)
- [x] Statuts de l'agent sur chaque élément : `APPLIQUER` (commentaires, remarques acceptées ou modifiées, texte de l'administrateur et texte d'origine pour mémoire), `REPORTER` (remarques reportées) ; remarques rejetées absentes

**P2.9 — Valider et publier** (0,5 j)
- [x] Action « Valider et publier » depuis `draft`, y compris sans tour de relecture : mentions entre crochets restantes listées et rappelées à la confirmation, transition → `approved` (`approved_by`, `approved_at`) puis publication (P2.10) ; en cas d'échec de la publication, l'édition reste `approved` et la publication peut être relancée
- [x] Retrait de la publication manuelle d'un PDF (`newsletter_admin/publish`, `send`) et de son bouton sur la page des abonnés

**P2.10 — Finalisation, mise en page et publication** (1,5 j)
- [x] Retrait déterministe de la section interne, de l'avertissement de brouillon et des notes de rédaction `{{ … }}`
- [x] Mention « Validé le jj/mm/aaaa par {prénom et nom du valideur} » en fin de newsletter ; aperçu PDF du texte publié dès la validation
- [x] PDF mis en page (`Newsletter_pdf`) : bandeau de titre, en-tête et pied de page, rubriques, encadrés, commentaires, images ; chaque rubrique commence sur une nouvelle page sauf si elle tient dans la place restante
- [x] PDF déposé dans l'archive membres existante, mise à jour `pdf_path`, transition `approved` → `published`, envoi aux abonnés (P1.6)
- [x] Envoi aux abonnés en échec ou interrompu : abonnés non atteints signalés, renvoi depuis l'édition publiée sans doublon

**P2.11 — Tests** (1,5 j)
- [x] PHPUnit MySQL `NewsletterMigrationTest.php` : migrations 181 et 182 `down()` / `up()`, avec sauvegarde et restauration des données existantes
- [x] PHPUnit MySQL `NewsletterImporterTest.php` : lecture des métadonnées, import .md et .zip, images, versions, refus sans effet de bord, retrait des notes internes, mentions
- [x] PHPUnit MySQL `NewsletterReviewCycleTest.php` : analyse des emails, tri des réponses, décisions, report et reprise, annotations, brouillon annoté, consignes de modification, différences
- [x] Playwright `newsletter-review-smoke.spec.js` : import zip, annotation, tri d'une remarque, consignes de modification, validation
- [ ] Essai réel de bout en bout sur gvv.net : transmission, réponses reçues sur la boîte de veille, nouvelle version produite par l'agent, publication (envoie de vrais emails, non automatisé)

**P2.12 — Documentation** (0,5 j)
- [x] Guide admin `doc/users/fr/20_newsletter_veille.md` : import, annotation, transmission, relevé et tri des remarques, consignes de modification, validation, publication ; abonnement et archive pour les membres ; relecture par email
- [ ] Guide admin : commandes acceptées par l'agent de rédaction (§1 du guide)
- [ ] Format du brouillon et des consignes de modification communiqué à l'agent de rédaction (dans ses consignes, hors GVV)

**Estimation P2 : ~11 jours.**

**Validation P2** : deux éditions générées par l'agent de rédaction, importées, annotées, relues par email, révisées par l'agent à partir des consignes de modification, validées et diffusées via GVV.

---

## P3 — Ouverture à tous les abonnés

P3 est conditionné par la validation de P2 sur au moins deux éditions réelles.

- [ ] Communication aux membres sur le mécanisme d'abonnement
- [ ] Ouverture de la possibilité pour les membres de proposer des articles (contribution par email, traitement dans le cycle de relecture)

**Estimation P3 : ~2 jours.**

---

## Récapitulatif

| Phase | Contenu | Estimation | État |
|---|---|---|---|
| P0 | Feature flag, configuration, validation infrastructure | 4 j | réalisé, hors tests |
| P1 | Abonnements et archive | 7 j | réalisé |
| P2 | Cycle de relecture dans GVV | 11 j | réalisé, hors essai réel et documentation |
| P3 | Ouverture aux membres | 2 j | à faire |

P2 dépend de P0 (IMAP/SMTP) et de P1 (tables, modèles, envoi aux abonnés). La génération sans intervention humaine et les alertes immédiates sont des évolutions envisagées par le PRD (§ 10), hors de ce plan.
