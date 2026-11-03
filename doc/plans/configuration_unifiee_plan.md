# Plan d'implémentation : Configuration unifiée

**PRD :** `doc/prds/configuration_unifiee_prd.md`
**Dernière mise à jour :** 2026-11-03
**Statut :** lots 1 et 2 réalisés (branches `feature/configuration_unifiee_lot1` et `feature/configuration_unifiee_lot2`), lot 3 à faire

Ce plan ne reprend pas les exigences : il les cite par leur identifiant (EX-…) du PRD.

---

## 1. Constat technique de départ

| Élément existant | Constat | Conséquence pour le plan |
| :--- | :--- | :--- |
| `controllers/config.php::saveConfig()` | Réécrit **tout** `club.php` à partir de sa propre liste de champs : les clés écrites par l'installeur et absentes de cette liste (`copie_a`, `mod`) sont perdues. Les valeurs sont écrites entre guillemets doubles, sans échapper `$` (interpolation PHP possible). | Ne pas réutiliser. Un nouvel écrivain de fichiers de configuration modifie une clé sans toucher aux autres. |
| `install/index.php` (`cfg_read`, `cfg_write`, `cfg_write_bool`, `cfg_write_int`) | Modification ciblée d'une clé par expression régulière, guillemets simples, échappement correct, le reste du fichier préservé. | Sert de modèle à l'écrivain de fichiers (porté dans une bibliothèque CodeIgniter). |
| Table `configuration` | `valeur` est un `varchar(255)`, trop court pour les textes des pages publiques. Pas de champs d'audit. Variantes par langue (`lang`) et par section (`club`) déjà gérées par `get_param()`. | Une migration passe `valeur` en `TEXT` et ajoute les champs d'audit. |
| `controllers/config.php::saveConfig()` (suite) | Reste en service pour les réglages pas encore repris (calendrier Google, FFVV…). | Au lot 2, l'identité du club en est retirée (un seul chemin, tracé), et il écrit clé par clé avec l'écrivain de fichiers, pour ne plus effacer les clés qu'il ne connaît pas. |
| Contrôle `dev_users` | Dupliqué dans une dizaine de contrôleurs (`explode` + `in_array`). | Le nouveau code passe par un helper unique `is_dev_user()`. Les contrôleurs existants ne sont pas modifiés (hors périmètre). |
| Section « Administration du club » du tableau de bord (`welcome/section/admin_club`) | Affichée seulement aux membres du CA (`is_ca_any_section`). | Ajouter la tuile Configuration, et afficher la section aussi aux dev_users (EX-TRA-01). |
| `newsletter.php` | Redirige vers la connexion dans son constructeur. | Lever cette redirection pour `index` et `pdf` (EX-PUB-05). |
| Parsedown (`libraries/Parsedown.php`, `MY_Parsedown.php`) | Déjà utilisé. | Rendu Markdown des pages publiques. |

## 2. Architecture

La note de conception (tâche L1-T1) détaille ces choix. Elle comprendra un diagramme PlantUML.

```
                   ┌──────────────────────────────┐
  page Paramètres  │ controllers/parametres.php   │  onglets filtrés par profil
  (tuile + menu) ─►│  index/{onglet}, save, hist. │
                   └──────┬───────────────┬───────┘
                          │               │
         ┌────────────────▼──┐     ┌──────▼──────────────────┐
         │ config/parametres │     │ libraries/              │
         │ _registry.php     │     │  Parametres_store.php   │──► fichiers application/config/*.php
         │ (déclaration des  │     │   (lecture, écriture,   │       (écriture ciblée par clé)
         │  paramètres)      │     │    validation)          │──► table configuration
         └───────────────────┘     └──────┬──────────────────┘
                                          │
                                   ┌──────▼──────────────────┐
                                   │ models/                 │
                                   │ parametres_history_model│──► table parametres_history
                                   └─────────────────────────┘

  pages publiques : controllers/legal.php ──► Parametres_store (textes) + gabarits Markdown par langue
```

- **Registre des paramètres**, `application/config/parametres_registry.php`. C'est un tableau PHP qui déclare, pour chaque paramètre :
  - son onglet, son stockage (`file:club`, `file:program`, `file:email`, `file:newsletter`, `file:google` ou `db`) et son type ;
  - ses clés de libellé et d'aide, son caractère obligatoire ou secret, sa valeur par défaut ;
  - ses variantes par section ou par langue ;
  - le profil minimal pour le lire et pour le modifier ;
  - sa règle de validation.

  La page est entièrement générée à partir de ce registre. Pour ajouter un paramètre, on ajoute une entrée, sans écrire de code d'écran. Le tableau ne dépend pas de CodeIgniter : l'installeur pourra l'inclure plus tard pour partager les libellés (EX-NAV-01).
- **`Parametres_store`** lit la valeur en vigueur (le défaut est signalé comme tel, EX-NAV-03) et valide avec les règles du registre. Il écrit dans le bon support et rend un compte rendu par paramètre (EX-ENR-02 à 04) : enregistré, refusé et pourquoi, ou non modifiable (fichier en lecture seule). Il gère les secrets (EX-SEC) et enregistre chaque écriture dans l'historique.
- **Métadonnées.** Le formulaire de chaque onglet est produit à partir du registre. Les types de champs sont rendus par les helpers existants (`input_field`/`dropdown_field`) quand le type s'y prête. L'historique est affiché par `$this->gvvmetadata->table()` (métadonnées de `parametres_history` dans `Gvvmetadata.php`).
- **Nom du contrôleur : `parametres`.** `config` et `configuration` existent déjà. Ils deviennent des redirections vers l'onglet correspondant, à la fin du lot 3 (EX-TRA-02).

## 3. Lots et tâches

Les lots suivent l'ordre de livraison de EX-TRA-04. Chaque lot est livré sur sa propre branche et fait l'objet d'une PR (`/branch`, `/create-pr`), car tous comportent une migration ou plus de 5 fichiers.

### Lot 1 — Socle minimal et pages publiques (≈ 6 j) ✅

| # | Tâche | Exigences | Estimation |
| :--- | :--- | :--- | :--- |
| L1-T1 ✅ | Note de conception `doc/design_notes/configuration_unifiee_design.md` et diagramme `diagrams/configuration_unifiee.puml` (registre, store, profils ; l'historique y sera ajouté au lot 2) | — | 0,5 j |
| L1-T2 ✅ | Migration 184 : `configuration.valeur` en `TEXT`, ajout de `created_at`, `updated_at`, `created_by`, `updated_by` à `configuration`. Mise à jour de `config/migration.php` | EX-PUB-02, §8 | 0,5 j |
| L1-T3 ✅ | Registre (structure et entrées de l'onglet Pages publiques ; celles de Club au lot 2) ; écrivain de fichiers de configuration (portage des fonctions de l'installeur) ; `Parametres_store` (lecture, validation, écriture, compte rendu) ; helper `is_dev_user()` ; résolution des profils et des onglets visibles | EX-PRO-01 à 04, EX-ENR-01 à 05, EX-VAR-02, EX-VAR-03 | 2 j |
| L1-T4 ✅ | Contrôleur `parametres` et vue à onglets (Bootstrap 5) : état par onglet, onglet dans l'adresse, avertissement si des modifications ne sont pas enregistrées, refus explicite pour un onglet non autorisé. Seul l'onglet **Pages publiques** est livré, avec saisie Markdown par langue, aperçu et adresses publiques à copier | EX-NAV-01 à 05, EX-PUB-04 | 1 j |
| L1-T5 ✅ | Paramètre `contact_rgpd` (stocké dans `club.php`), ajouté provisoirement au formulaire existant `config` en attendant l'onglet Club du lot 2 | §6 bis | 0,25 j |
| L1-T6 ✅ | Contrôleur public `legal` (`confidentialite`, `conditions`) : texte du club s'il existe, sinon gabarit générique `application/views/legal/{french,english,dutch}/*.md` ; insertion des informations du club ; mention HelloAsso si le module est actif ; date de mise à jour. Liens sur `auth/bs_login_form.php` | EX-PUB-01 à 03 | 1 j |
| L1-T7 ✅ | Newsletter publique : `newsletter/index` et `newsletter/pdf` sans connexion (toujours conditionnés à `veille`), bouton de suppression inchangé (club-admin) | EX-PUB-05 | 0,25 j |
| L1-T8 ✅ | Tuile « Configuration » dans la section Administration du club ; section visible aussi des dev_users ; entrée de menu dans le sous-menu « Club » (réservé au CA, voir L3-T5) | EX-TRA-01 | 0,25 j |
| L1-T9 ✅ | Traductions fr/en/nl (`parametres_lang.php`, `legal_lang.php`) | §8 | 0,25 j |

**Contenu à saisir après déploiement (hors code)** : texte de confidentialité d'Abbeville (Google Drive, BIA Amiens, durées de conservation), saisi sur le serveur de production dans l'onglet Pages publiques. Suivi de la mise en place Google : URL de la page d'accueil = page de connexion, URL des règles = `legal/confidentialite`, domaine de production dans « Domaines autorisés », puis publication de l'application OAuth et `rclone config reconnect gdrive:`.

### Lot 2 — Socle complet, Club, Modules, Messagerie (≈ 6 j) ✅

| # | Tâche | Exigences | Estimation |
| :--- | :--- | :--- | :--- |
| L2-T1 ✅ | Migration 185 : table `parametres_history` (paramètre, onglet, section, langue, ancienne et nouvelle valeur — secrets non conservés —, action, champs d'audit). Métadonnées dans `Gvvmetadata.php` | EX-HIS-01 | 0,5 j |
| L2-T2 ✅ | Historique par onglet et retour à la valeur précédente (revalidé, tracé, interdit pour les secrets) ; traçage des écritures du lot 1 | EX-HIS-01 à 03 | 1,5 j |
| L2-T3 ✅ | Mode lecture seule (CA : valeurs affichées sans champ) ; secrets masqués avec effacement explicite ; formulaire sans validation du navigateur, pour que le motif d'un refus s'affiche toujours à côté du champ | EX-PRO-05, EX-PRO-06, EX-SEC-01 à 03 | 0,5 j |
| L2-T4 ✅ | Onglet **Club** (identité, logo, contact RGPD) ; retrait du champ provisoire de L1-T5 ; identité du club retirée de l'ancien écran `config` (lien vers l'onglet Club), qui reste en service pour ses autres réglages et écrit clé par clé | EX-PRO-06 | 1 j |
| L2-T5 ✅ | Onglet **Modules** (drapeaux de `program.php`) avec avertissement si un module est activé alors que des paramètres obligatoires manquent (déclarés par `requires` ; au lot 2 : serveur SMTP pour les vols de découverte et la newsletter) | EX-ENR-06, §8 (la page Configuration n'est jamais masquée) | 1 j |
| L2-T6 ✅ | Onglet **Messagerie** (SMTP, e-mail et téléphone de test) et test d'envoi ; `email.php` absent créé à la première écriture ; expéditeur par défaut partagé avec `admin/test_email` | EX-TST-01, EX-TST-02 | 1 j |
| L2-T7 ✅ | Traductions | §8 | 0,5 j |

### Lot 3 — Autres onglets et fin de transition (≈ 7 j)

| # | Tâche | Exigences | Estimation |
| :--- | :--- | :--- | :--- |
| L3-T1 | Onglets **Apparence**, **Communication** (CA), **Vols de découverte** (CA) : reprise des clés `vd.*` de la table `configuration` | §6 | 1,5 j |
| L3-T2 | Onglet **Finances** (CA en lecture seule) : comptes de cotisation, seuils de relance, `facturation.php`, contrôle de solde à la réservation | §6, EX-VAR-01 | 1 j |
| L3-T3 | Onglet **Paiements en ligne** : intégration du formulaire de `paiements_en_ligne/admin_config` par section, ouvert au club-admin ; déclarer les paramètres requis par le module `gestion_paiements` (`requires`). Le contrôleur HelloAsso garde sa logique sans métadonnées (exception CLAUDE.md) : seul son écran de réglages est déplacé | §6, EX-VAR-01 | 1 j |
| L3-T4 | Onglets **Documents et cartes** et **Référentiels** : réglages des cartes et des formulaires, liens vers les écrans de listes existants | §3.1 | 1 j |
| L3-T5 | Onglet **Système** (dev_users) : newsletter (déclarée requise par le module `veille`) (IMAP, IA, relecteurs, tests existants), SMS, ordonnanceurs, Google, `dev_users` ; lecture seule pour la base de données, l'URL, la version et la migration ; liste clé/valeur brute de la table `configuration` pour les clés non déclarées dans le registre. Entrée de menu accessible à un dev_user non membre du CA, hors du sous-menu « Club » qui contient des écrans réservés au CA | §3.2, EX-TST-01 | 1,5 j |
| L3-T6 | Redirection des anciens écrans (`config`, `configuration`, `cartes_membre/config`, `forms_admin/config`, `newsletter_admin/configuration`, `paiements_en_ligne/admin_config`) vers leur onglet ; nettoyage du menu | EX-TRA-01 à 03 | 0,5 j |
| L3-T7 | Passage de la tâche « Unifier la configuration » de `doc/todo.md` à `doc/done.md` | EX-TRA-05 | — |

**Total estimé : ≈ 19 jours**, tests et documentation compris (§4, §5).

## 4. Tests

Toutes les descriptions de tests suivent la règle Gherkin en français de CLAUDE.md. Fonctionnalités de rattachement dans le tableau de CLAUDE.md : ajouter le mot-clé `parametres` à la ligne « Configuration » (les fichiers `parametres*` ne contiennent pas `config`), et une nouvelle ligne « Pages publiques » (`legal`) placée au-dessus des lignes génériques. Les tests de la newsletter publique restent rattachés à « Newsletter ».

### PHPUnit

| Suite | Contenu | Lot |
| :--- | :--- | :--- |
| unit | Registre : chaque entrée est complète et cohérente (onglet existant, profils valides, libellés présents dans les trois langues) | 1 |
| unit | Écrivain de fichiers : modifie une clé sans toucher aux autres ni aux commentaires ; échappe guillemets, `\` et `$` ; ajoute une clé absente ; booléens et entiers. Travaille sur des **fichiers temporaires**, jamais sur `application/config` | 1 |
| unit | Pages publiques : insertion des informations du club, repli sur le texte générique, repli sur le français, mention HelloAsso selon le module | 1 |
| unit | Profils : onglets visibles et modifiables selon CA / club-admin / dev_user, cumul des profils | 1-2 |
| integration | `Parametres_store` en base : lecture, écriture, variantes par langue et par section, valeur par défaut signalée. Tests de migration 184/185 seulement s'ils ne sont pas déjà couverts par ces tests | 1-2 |
| integration | Historique : trace de chaque écriture, secrets non conservés, retour arrière revalidé et tracé ; obligation conditionnelle ; avertissement de module ; page indépendante des modules | 2 |

Règles d'état (CLAUDE.md) : chaque test crée ses propres clés de configuration (préfixe dédié) et les supprime en teardown. Un test qui touche un fichier de configuration réel le sauvegarde d'abord et le restaure à l'identique dans un `finally`.

### Playwright

| Scénario | Lot |
| :--- | :--- |
| Un visiteur non connecté ouvre les règles de confidentialité et les conditions d'utilisation depuis la page de connexion | 1 |
| Un visiteur non connecté consulte la liste des newsletters et ouvre un PDF | 1 |
| Un club-admin modifie le texte de confidentialité, voit le message de succès, puis le nouveau texte sur la page publique. Le texte d'origine est restauré en `afterAll` | 1 |
| Un membre simple ne voit pas la tuile Configuration ; un accès direct à la page affiche un refus explicite | 1 |
| Un membre du CA voit Club et Modules en lecture seule, et ne voit ni Messagerie ni Pages publiques ✅ | 2 |
| Une valeur invalide est refusée à côté du champ, sans perte des autres saisies ✅ | 2 |
| Retour à la valeur précédente depuis l'historique ✅ | 2 |
| Le mot de passe SMTP n'est jamais réaffiché ✅ | 2 |
| Un membre du CA voit aussi Finances en lecture seule | 3 |
| Un dev_user non membre du CA accède à la page par le tableau de bord et voit l'onglet Système | 3 |
| Les anciennes adresses mènent au bon onglet | 3 |

Utilisateurs de test : ceux de `bin/create_test_users.sql`. Les scénarios multi-utilisateurs se déconnectent entre deux comptes.

## 5. Validation et documentation

**À la fin de chaque lot :**
1. `php -l` sur les fichiers modifiés, `./run-all-tests.sh`, puis les tests Playwright du lot.
2. Test fonctionnel sur gvv.net avec les utilisateurs de test (smoke test PHPUnit et Playwright, CLAUDE.md §22).
3. Revue de la PR (`/pr-review`), fusion après accord (`/merge-pr`).

**Documentation utilisateur :**
- Nouveau chapitre `doc/users/fr/21_configuration.md` : accès, onglets par profil, historique, pages publiques. Enrichi à chaque lot.
- Mise à jour de `doc/users/fr/17_sauvegarde_hors_site.md` §1.2 : publication de l'application OAuth, avec l'URL de la page d'accueil et celle des règles de confidentialité de GVV (lot 1) ✅.
- Mise à jour de `doc/installation.md` : après installation, les paramètres se modifient dans la page Configuration (lot 3).

## 6. Risques

| Risque | Parade |
| :--- | :--- |
| Fichiers de configuration non modifiables par le serveur web en production (`program.php`, `email.php`…) | Compte rendu explicite par paramètre (EX-ENR-04), avec la commande à exécuter. Vérification des droits avant le déploiement de chaque lot. |
| Une écriture mal formée rend un fichier de configuration invalide et bloque GVV | Vérification syntaxique du fichier produit (`php -l` sur une copie temporaire) avant de remplacer l'original, et conservation de la version précédente. |
| Désactivation d'un module dont dépend la page | La page Paramètres ne dépend d'aucun module (§8). Test dédié au lot 2. |
| Divergence entre les libellés de l'installeur et ceux de la page | Libellés repris de l'installeur au lot 1. Le partage effectif du registre avec l'installeur sera une évolution ultérieure, proposée séparément. |
| Pages publiques indexées avant que le club ait saisi son texte | Le texte générique est valable par lui-même (EX-PUB-01). |

## 7. Ressources

- Développement : un développeur (avec assistance IA), ≈ 19 jours.
- Relecture des textes juridiques génériques : bureau d'Abbeville (responsable RGPD).
- Tests de recette par profil : un membre du CA, un club-admin, un dev_user, sur gvv.net.
- Accès au serveur de production d'Abbeville, pour les droits des fichiers et la saisie du texte de confidentialité du club.
