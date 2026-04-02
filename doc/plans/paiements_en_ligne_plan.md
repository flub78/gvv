# Plan d'Implémentation : Paiements en Ligne

**Fonctionnalité :** Provisionnement de Compte par Paiement en Ligne
**PRD :** `doc/prds/paiements_en_ligne_prd.md`
**Spike de référence :** `doc/plan/HelloAssoSpike.md`
**Statut :** En cours (étapes 1–7 terminées, étape 8 supprimée)

---

## Contrainte de déploiement : visibilité conditionnelle via `dev_users`

Le serveur de production est le seul serveur accessible depuis Internet, ce qui est nécessaire pour recevoir les webhooks HelloAsso. Il n'existe pas d'environnement de préproduction public. En conséquence, les tests de la fonctionnalité de paiement en ligne se font directement en production.

**Pendant toute la phase de test**, tous les boutons, liens et écrans liés au paiement en ligne (boutons HelloAsso dans les formulaires de cotisation et de crédit de compte, menus de navigation, pages du module) sont **masqués aux utilisateurs ordinaires** et uniquement visibles pour les utilisateurs listés dans la configuration `dev_users`.

La levée de cette restriction, pour rendre la fonctionnalité accessible à tous, est une action de mise en production explicite, décidée une fois la validation complète effectuée.

---

## Convention : Tests Playwright nécessitant HelloAsso Sandbox

Les tests Playwright qui déclenchent un vrai appel HelloAsso (création de checkout, redirection, webhook) doivent être **skippés automatiquement** si les crédentiels sandbox ne sont pas configurés dans `application/config/helloasso.php`.

**Mécanisme :**

```js
// En début de chaque spec HelloAsso
const helloassoConfigured = await page.request.get('/paiements_en_ligne/sandbox_available');
test.skip(helloassoConfigured.status() !== 200, 'HelloAsso sandbox non configuré — test ignoré');
```

Le endpoint `paiements_en_ligne/sandbox_available` retourne HTTP 200 uniquement si `client_id` et `client_secret` sont définis et non vides pour la section de test dans `helloasso.php`. Il retourne HTTP 503 sinon.

Les tests signalés **`[SKIP SI SANDBOX]`** dans ce plan sont concernés par cette convention. Les tests d'accès, de permissions et de formulaires (sans appel HelloAsso réel) s'exécutent toujours.

---

## Périmètre et ordre d'implémentation

| Ordre | ID | Description | Priorité | Statut |
|-------|----|-------------|----------|--------|
| 1 | — | Audit système bar existant | — | ✅ |
| 2 | UC5 | Règlement consommations bar — débit de solde pilote | HAUTE | ✅ |
| 3 | — | Migration de base de données | — | ✅ |
| 4 | — | Bibliothèque HelloAsso | — | ✅ |
| 5 | EF5 | Configuration des plateformes par section | MOYENNE | ✅ |
| 6 | — | Contrôleur et modèle de base | — | ✅ |
| 7 | EF2 | Webhook + écriture comptable (infrastructure partagée) | HAUTE | ✅ |
| 8b | EF6 | Navigation dashboard — section "Mes paiements" | HAUTE | ✅ |
| 9 | EF1 | Provisionnement en ligne par le pilote | HAUTE | ✅ |
| 10 | EF3 | Vérification du paiement / Mon Compte | HAUTE | ✅ |
| 11 | EF4 | Liste des provisionnements pour le trésorier | HAUTE | ✅ |
| 14 | UC2 | Règlement consommations bar — personne externe via QR Code | MOYENNE | ✅ |
| 15 | UC3 | Renouvellement de cotisation en ligne | MÉDIUM | ✅ |
| 16 | UC4 | Paiement bon de découverte — lien/QR Code public | MÉDIUM | ✅ |
| 17 | — | Tests de recette et validation finale | — | ☐ |

---

## Étape 1 : Audit du système bar existant

**Objectif :** Comprendre le mécanisme existant dans GVV pour les règlements de bar avant toute implémentation, afin de ne pas repartir de zéro ni créer de doublon.

**Résultat de l'audit :**

- Il n'existe pas de table dédiée aux consommations de bar dans GVV, ni de mécanisme de notes préétablies.
- Le modèle est basé sur la confiance : c'est le trésorier qui enregistre manuellement une écriture comptable sur demande du pilote (débit compte 411 pilote, crédit compte recette bar 7xx). Personne n'établit de note pour le pilote — il déclare lui-même ses consommations.
- Aucun "gérant de bar" n'intervient : le pilote est seul responsable de déclarer le montant et la description de ce qu'il a consommé.
- Le compte de recette bar (7xx) varie selon le club et doit être configurable par section.
- UC5 reproduit exactement ce que le trésorier fait manuellement, mais à l'initiative du pilote lui-même : saisie d'un descriptif de consommation + montant, génération de l'écriture comptable correspondante.
- **Toutes les sections n'ont pas de bar.** La fonctionnalité de paiement bar (UC5) ne doit être visible que dans les sections qui ont un bar. Un flag `has_bar` (booléen, défaut `false`) sera ajouté à la table `sections`. L'option de règlement bar n'est affichée au pilote que si sa section active a `has_bar = true`.

**Validation :** ✅ Audit terminé — étape 2 débloquée.

---

## Étape 2 : Paiement bar par débit de solde (UC5)

**Objectif :** Permettre au pilote de déclarer et régler ses consommations de bar en débitant son solde existant, sans carte bancaire. Aucune intégration HelloAsso requise.

**Modèle :** Le pilote saisit lui-même le descriptif et le montant de ses consommations (modèle de confiance). Le système génère l'écriture comptable identique à ce que ferait le trésorier manuellement : débit compte 411 pilote, crédit compte recette bar (7xx configurable par section).

**Flux :**
1. Pilote accède à "Mon Compte" → "Régler mes consommations de bar"
2. Formulaire : montant (min 0,50€), descriptif libre obligatoire (ex. "2 cafés, 1 sandwich – 28/03/2026")
3. Vérification : `solde_disponible >= montant` — refus avec message explicite si insuffisant :
   > "Solde insuffisant : vous avez X€ disponibles."
4. Transaction DB atomique : écriture comptable (débit 411 pilote, crédit compte bar 7xx configuré pour la section)
5. Confirmation affichée et solde mis à jour immédiatement

**Configuration requise :** Le compte de recette bar (7xx) est configurable par section dans la page d'administration (à ajouter à EF5, étape 5).

**Visibilité :** Le lien "Régler mes consommations de bar" n'est affiché dans "Mon Compte" que si la section active du pilote a `has_bar = true`. Il est invisible (et l'URL inaccessible) pour les sections sans bar.

**Règles :** Aucun paiement à crédit (solde minimum 0€), transaction atomique, le pilote ne peut régler que pour son propre compte.

**Fichiers :**
- Nouveau contrôleur `application/controllers/paiements_en_ligne.php` ou méthode dans le contrôleur `compta` existant (à décider selon cohérence de l'interface)
- Vue formulaire de saisie bar

**Validation :** ✅ Complète
- ✅ PHPUnit (11 tests) : migration 096, écriture créée, solde insuffisant, validation montant/description, flag has_bar — `application/tests/mysql/PaiementsEnLigneBarTest.php`
- ✅ Playwright (7 tests) : flow complet, lien absent sans bar, redirection avec erreur, formulaire, validations — `playwright/tests/paiements-en-ligne-smoke.spec.js`

**Fichiers créés/modifiés :**
- `application/migrations/096_add_has_bar_to_sections.php`
- `application/controllers/paiements_en_ligne.php` (extends MY_Controller)
- `application/views/paiements_en_ligne/bs_bar_form.php`
- `application/language/{french,english,dutch}/paiements_en_ligne_lang.php`
- `application/controllers/compta.php` (has_bar dans journal_data)
- `application/views/compta/bs_journalCompteView.php` (lien bar conditionnel)
- `application/tests/mysql/PaiementsEnLigneBarTest.php`
- `playwright/tests/paiements-en-ligne-smoke.spec.js`

---

## Étape 3 : Migration de base de données

**Objectif :** Créer les tables `paiements_en_ligne` et `paiements_en_ligne_config` nécessaires aux paiements par carte.

**Fichiers :**
- `application/migrations/NNN_paiements_en_ligne.php`
- `application/config/migration.php` (incrémenter la version)

**Contenu de la migration :**
- Table `paiements_en_ligne` : `id`, `user_id`, `montant`, `plateforme`, `transaction_id` (UNIQUE), `ecriture_id`, `statut` (ENUM : pending/completed/failed/cancelled), `date_demande`, `date_paiement`, `metadata` (JSON), `commission`, `club`, `created_at`, `updated_at`, `created_by`, `updated_by`
- Table `paiements_en_ligne_config` : `id`, `plateforme`, `param_key`, `param_value` (chiffré), `club`, `created_at`, `updated_at`, `created_by`, `updated_by`
- Index sur `user_id`, `statut`, `transaction_id`, `date_paiement`
- Clé étrangère `ecriture_id → ecritures.id ON DELETE RESTRICT` : la suppression d'une écriture liée à un paiement est physiquement impossible — toute correction comptable passe par une contre-écriture d'annulation
- **Ajout colonne `has_bar TINYINT(1) NOT NULL DEFAULT 0`** à la table `sections` existante : indique si la section dispose d'un bar. Valeur par défaut `false` — aucune section n'a le bar activé sans action explicite de l'admin.

**Validation :** ✅ Complète
- ✅ PHPUnit (8 tests) : `up()` crée tables/colonnes/index/FK, `down()` supprime, idempotence — `application/tests/mysql/PaiementsEnLigneMigrationTest.php`
- ✅ Migration appliquée en base (niveau 97)

**Fichiers créés/modifiés :**
- `application/migrations/097_paiements_en_ligne.php`
- `application/config/migration.php` (version 97)
- `application/tests/mysql/PaiementsEnLigneMigrationTest.php`

---

## Étape 4 : Bibliothèque HelloAsso

**Objectif :** Extraire et adapter la logique HelloAsso du spike (`payments.php`) en une bibliothèque réutilisable `application/libraries/Helloasso.php`, avec support multi-section.

**Base :** Réutiliser le code de `application/controllers/payments.php` (spike validé).

**Contenu de la bibliothèque :**
- `get_oauth_token($club_id)` — OAuth2 client credentials, crédentiels récupérés par section depuis `paiements_en_ligne_config`
- `create_checkout($club_id, $params)` — Création d'un checkout HelloAsso, retourne l'URL de redirection
- `verify_webhook_signature($payload, $signature, $club_id)` — Vérification HMAC-SHA256
- `sandbox_available($club_id)` — Vérifie que les crédentiels sandbox sont définis et non vides
- `log($level, $txid, $type, $message)` — Logs structurés dans `helloasso_payments_YYYY-MM-DD.log` avec format `[HELLOASSO] txid=... STATUS=...`
- Les secrets (client_secret, tokens) sont masqués (`***`) dans les logs

**Multi-section :** Les crédentiels (client_id, client_secret, slug) sont récupérés depuis `paiements_en_ligne_config` filtrés par `club`, jamais partagés entre sections.

**Validation :** ✅ Complète
- ✅ PHPUnit (19 tests) : `get_oauth_token` succès/échec/crédentiels manquants, `verify_webhook_signature` valide/invalide/préfixé/secret absent, `log` format/mots-clés/masquage secret/multi-lignes, `sandbox_available`, `create_checkout` succès/échec OAuth/slug manquant/log PENDING+SUCCESS — `application/tests/unit/libraries/HelloassoLibraryTest.php`

**Fichiers créés :**
- `application/libraries/Helloasso.php`
- `application/tests/unit/libraries/HelloassoLibraryTest.php`

---

## Étape 5 : Configuration admin (EF5)

**Objectif :** Page d'administration pour configurer HelloAsso par section.

**Fichiers :**
- Méthode `admin_config()` dans `application/controllers/paiements_en_ligne.php`
- Vue `application/views/paiements_en_ligne/admin_config.php`

**Fonctionnalités :**
- Formulaire par section : Client ID, Client Secret (chiffré en BDD), slug organisation, mode sandbox/production
- URL de webhook générée automatiquement et affichée pour copie dans l'interface HelloAsso
- Compte comptable de passage par défaut (467)
- **Activation bar** : case à cocher "Cette section dispose d'un bar" — modifie le flag `has_bar` dans la table `sections`
- **Compte de recette bar (7xx)** : sélecteur de compte parmi les comptes 7xx du plan comptable de la section — utilisé comme contrepartie crédit pour UC5 (visible uniquement si bar activé)
- Montant minimum (10€) et maximum (500€) par transaction
- Activation/désactivation par section
- Bouton "Tester la connexion" : appelle HelloAsso OAuth2 et affiche le résultat
- Log d'audit de chaque changement de configuration

**Sécurité :** Accès réservé au rôle `admin`.

**Validation :**
- Test Playwright : accès refusé pour un non-admin, formulaire sauvegardé et rechargé correctement pour un admin
- `[SKIP SI SANDBOX]` Test du bouton "Tester la connexion" : succès retourné en mode sandbox
- ✅ Secrets HelloAsso (`client_secret`, `webhook_secret`) chiffrés en BDD (AES-256-GCM) ; clé applicative lue depuis `application/config/helloasso_crypto.php` (fichier local non versionné) ou variable d'environnement `GVV_HELLOASSO_CRYPTO_KEY`

---

## Étape 6 : Contrôleur et modèle de base

**Objectif :** Créer le squelette du contrôleur `paiements_en_ligne` et du modèle associé, incluant le endpoint de détection sandbox.

**Fichiers :**
- `application/controllers/paiements_en_ligne.php`
- `application/models/paiements_en_ligne_model.php`

**Contrôleur — méthodes créées à cette étape :**
- `index()` — page d'accueil du module (liste des transactions du pilote connecté)
- `confirmation($transaction_id)` — page de confirmation après paiement réussi
- `annulation()` — page après annulation
- `erreur()` — page après échec
- `sandbox_available()` — HTTP 200 si crédentiels sandbox définis, HTTP 503 sinon (utilisé par les specs Playwright)

**Modèle — méthodes :**
- `create_transaction($data)` — crée une transaction `pending`
- `update_transaction_status($transaction_id, $status, $metadata)` — met à jour le statut
- `get_by_transaction_id($id)` — récupère une transaction par son ID externe
- `get_transactions($filters)` — liste avec filtres (pilote, statut, dates, section)
- `get_pending_transactions()` — transactions `pending` plus vieilles de 30 minutes
- `get_config($plateforme, $key, $club_id)` — lit la configuration

**Validation :** ✅ Complète
- ✅ PHPUnit (21 tests) : CRUD complet sur les tables, filtres, statuts, chiffrement config — `application/tests/mysql/PaiementsEnLigneModelTest.php`
- ✅ Playwright (6 tests) : index HTTP 200, confirmation/annulation/erreur accessibles, sandbox_available JSON, accès non connecté redirige — `playwright/tests/paiements-en-ligne-base.spec.js`

**Fichiers créés/modifiés :**
- `application/controllers/paiements_en_ligne.php` (méthodes index, confirmation, annulation, erreur, sandbox_available)
- `application/models/paiements_en_ligne_model.php`
- `application/views/paiements_en_ligne/bs_index.php`
- `application/views/paiements_en_ligne/bs_confirmation.php`
- `application/views/paiements_en_ligne/bs_annulation.php`
- `application/views/paiements_en_ligne/bs_erreur.php`
- `application/tests/mysql/PaiementsEnLigneModelTest.php`
- `playwright/tests/paiements-en-ligne-base.spec.js`

---

## Étape 7 : Webhook + écriture comptable (EF2 — infrastructure partagée)

**Objectif :** Implémenter le handler webhook HelloAsso qui sera utilisé par EF1 et tous les cas d'usage CB. Le dispatch vers la bonne logique métier est basé sur le champ `type` dans les `metadata` de la transaction.

**Méthode :** `paiements_en_ligne::helloasso_webhook()` (endpoint public, sans session)

**Algorithme :**
1. Récupérer le payload brut et la signature HTTP
2. Vérifier la signature HMAC-SHA256 via `Helloasso::verify_webhook_signature()` — rejeter HTTP 401 si invalide
3. Décoder le JSON, ignorer silencieusement tout `eventType` autre que `'Order'` (HTTP 200)
4. Extraire `gvv_transaction_id` depuis `metadata`
5. Vérifier idempotence : si transaction déjà `completed`, retourner HTTP 200 sans action
6. Si `payment.state !== 'Authorized'` → marquer `failed`, log `STATUS=FAILED`, retourner HTTP 200
7. Transaction DB atomique selon `metadata.type` :
   - `type=provisionnement` : crédit compte pilote 411, débit compte de passage 467
   - `type=bar` : débit compte pilote 411, crédit compte bar
   - `type=bar_externe` : crédit compte bar (sans compte pilote)
   - `type=decouverte` : écriture recette bon de découverte
8. Mettre à jour la transaction : statut `completed`, `ecriture_id`, `date_paiement`, `metadata` complète
9. Log `STATUS=SUCCESS montant=X`
10. Envoyer email de confirmation
11. Retourner HTTP 200

**Validation :**
- Test PHPUnit : webhook valide `type=provisionnement` → écriture créée, transaction `completed`
- Test PHPUnit : webhook valide `type=bar` → bonne écriture de débit bar
- Test PHPUnit : signature invalide → HTTP 401, aucune écriture créée
- Test PHPUnit : webhook reçu deux fois avec le même `transaction_id` → idempotence, une seule écriture

**✅ Complète** — 10 tests PHPUnit (`application/tests/mysql/PaiementsEnLigneWebhookTest.php`, 62 assertions) + 8 tests Playwright (`playwright/tests/paiements-en-ligne-webhook.spec.js`), tous verts.

---

## Étape 8b : Navigation dashboard — section "Mes paiements" (EF6)

**Objectif :** Afficher dans "Mon espace personnel" une sous-section "Mes paiements" avec des cartes d'accès rapide aux fonctionnalités de paiement, conditionnées par la configuration de chaque section.

**Règles de visibilité :**
- La sous-section n'apparaît que si au moins une section du pilote a `paiements_en_ligne_config.enabled = '1'`
- Carte "Payer mes notes de bar" : visible si `has_bar = true` ET paiements activés pour la section — redirige directement vers `bar_debit_solde`
- Carte "Approvisionner mon compte [section] (CB)" : une par section avec paiements activés
- Carte "Payer ma cotisation" : visible dès qu'une section a les paiements activés

**Validation :** ✅ Complète
- ✅ Implémenté : contrôleur `welcome::index`, vue `bs_dashboard.php`

**Fichiers créés/modifiés :**
- `application/controllers/welcome.php` (calcul `$payment_sections`)
- `application/views/bs_dashboard.php` (sous-section "Mes paiements")
- `application/language/{french,english,dutch}/paiements_en_ligne_lang.php` (nouvelles clés)

---

## Étape 9 : Provisionnement pilote (EF1)

**Objectif :** Permettre à un pilote authentifié de provisionner son compte via HelloAsso.

**Méthode ajoutée :** `demande()` dans le contrôleur existant (GET/POST)

**Flux :**
1. Pilote accède à "Mon Compte" → "Provisionner mon compte en ligne"
2. Formulaire : montant (min 10€, max 500€), confirmation des CGU
3. Vérification section active via `_require_active_section()`
4. Création transaction `pending` avec `metadata.type=provisionnement`
5. Appel `Helloasso::create_checkout()` → redirection HelloAsso
6. Retour webhook → handler étape 7 → crédit compte pilote 411

**Sécurité :**
- Token CSRF, limite de 5 transactions par jour par utilisateur
- `_require_active_section()` centralisé

**Validation :** ✅ Complète
- ✅ PHPUnit (8 tests) : validation montant min/max, garde section "Toutes", `count_pending_today()` (comptage, filtre statut, filtre date), limite 5/jour — `application/tests/mysql/PaiementsEnLigneProvisionTest.php`
- ✅ Playwright (5 tests, 1 `[SKIP SI SANDBOX]`) : sans session → login, avec session → formulaire/mon_compte, attributs min/max, validation serveur montant invalide, flow sandbox — `playwright/tests/paiements-en-ligne-ef1-demande.spec.js`

**Fichiers créés/modifiés :**
- `application/controllers/paiements_en_ligne.php` (méthodes `demande`, `_process_demande`)
- `application/models/paiements_en_ligne_model.php` (méthode `count_pending_today`)
- `application/views/paiements_en_ligne/bs_demande_form.php` (nouveau)
- `application/language/{french,english,dutch}/paiements_en_ligne_lang.php` (clés `gvv_provision_*`, `gvv_button_cancel`)
- `application/tests/mysql/PaiementsEnLigneProvisionTest.php` (nouveau)
- `playwright/tests/paiements-en-ligne-ef1-demande.spec.js` (nouveau)

---

## Étape 10 : Intégration "Mon Compte" (EF3)

**Objectif :** Afficher les paiements en ligne dans la page Mon Compte du pilote.

**Modifications :**
- `compta/mon_compte` : badge distinctif sur les lignes d'écriture issues de paiements en ligne (identification via `num_cheque LIKE 'HelloAsso:%'`)
- Lien "Provisionner mon compte en ligne" dans le menu Mon Compte
- Page `paiements_en_ligne/index` : historique des transactions du pilote connecté (date, montant, statut, référence)

**✅ Complète**
- ✅ PHPUnit (6 tests) : filtrage par user_id, filtrage par club, structure retournée, liste vide, badge HelloAsso identifiable — `application/tests/mysql/PaiementsEnLigneHistoriqueTest.php`
- ✅ Playwright (3 tests, 1 skipped) : historique accessible, mon_compte accessible, absence du lien si HelloAsso non activé — `playwright/tests/paiements-en-ligne-ef3-mon-compte.spec.js`

**Fichiers créés/modifiés :**
- `application/controllers/compta.php` : badge HelloAsso dans `datatable_journal_compte()`, `helloasso_enabled` dans `journal_data()`
- `application/views/compta/bs_journalCompteView.php` : lien "Provisionner mon compte en ligne" conditionnel
- `application/language/{french,english,dutch}/paiements_en_ligne_lang.php` : clé `gvv_provision_button_link`
- `application/tests/mysql/PaiementsEnLigneHistoriqueTest.php` (nouveau)
- `playwright/tests/paiements-en-ligne-ef3-mon-compte.spec.js` (nouveau)

---

## Étape 11 : Liste trésorier (EF4)

**Objectif :** Vue centralisée des paiements en ligne pour le trésorier.

**Méthode :** `paiements_en_ligne::liste()` — réservé aux rôles `tresorier`, `bureau`, `admin`

**Tableau :** Date/heure, nom du pilote, montant, commission, plateforme, référence de transaction, statut, lien vers l'écriture comptable

**Filtres :** Période (date_from/date_to), plateforme, section, statut

**Exports :** CSV avec BOM UTF-8 (rapprochement bancaire) — via `liste_csv()`

**Statistiques :** Transactions complétées, montant total, commissions totales

**Navigation :**
- Carte trésorier dans le dashboard (section Comptabilité)
- Entrée dans le menu Comptabilité → sous-menu trésorier

**✅ Complète**
- ✅ PHPUnit (5 tests) : filtres statut, période, plateforme, club, jointure membres — `application/tests/mysql/PaiementsEnLigneListeTest.php`
- ✅ Playwright (4 tests) : accès refusé pilote, accès trésorier, filtres+tableau, lien CSV — `playwright/tests/paiements-en-ligne-ef4-liste.spec.js`

**Fichiers créés/modifiés :**
- `application/controllers/paiements_en_ligne.php` (méthodes `liste`, `liste_csv`)
- `application/models/paiements_en_ligne_model.php` (méthode `get_transactions_with_user`)
- `application/views/paiements_en_ligne/bs_liste.php` (nouveau)
- `application/views/bs_dashboard.php` (carte trésorier activée)
- `application/views/bs_menu.php` (lien menu trésorier)
- `application/language/{french,english,dutch}/paiements_en_ligne_lang.php` (clés `gvv_liste_*`)
- `application/tests/mysql/PaiementsEnLigneListeTest.php` (nouveau)
- `playwright/tests/paiements-en-ligne-ef4-liste.spec.js` (nouveau)

---

## Étape 14 : Règlement consommations bar — personne externe via QR Code (UC2)

**Objectif :** Permettre à une personne extérieure de régler ses consommations de bar via un QR Code affiché au bar, sans compte GVV. La personne saisit elle-même le montant et la description (modèle de confiance, identique aux pilotes).

**Fichiers :**
- Route publique : `paiements_en_ligne/public_bar`
- Vue : formulaire sans connexion (nom, prénom, email, description libre, montant libre min 2€)
- QR Code généré et géré par le trésorier : URL pointant vers `public_bar?club=X`

**Flux :**
1. Scan QR Code → page publique
2. La personne saisit : nom, prénom, email, description de ses consommations, montant
3. Création transaction avec `metadata.type=bar_externe` → Checkout HelloAsso → webhook → handler étape 7 → écriture recette bar
4. Email de confirmation à l'adresse fournie

**Sécurité :** CSRF, validation montant minimum. Le paramètre `club` doit être un identifiant de section valide — tout accès sans `club` valide est rejeté avec message d'erreur explicite.

**Validation :** ✅
- ✅ Test PHPUnit `PaiementsEnLigneBarExterneTest` (3 tests) : transaction créée avec user_id=0, webhook `type=bar_externe` → écriture créée (débit 467, crédit 7xx), idempotence
- ✅ Test Playwright `paiements-en-ligne-uc2-bar-externe.spec.js` (4 tests) : accès sans club → erreur, club=0 → erreur, club valide → page chargée, `[SKIP SI SANDBOX]` soumission formulaire → redirection HelloAsso

**Fichiers créés/modifiés :**
- `application/controllers/paiements_en_ligne.php` : constructeur (public_bar/public_bar_confirmation), `public_bar()`, `_render_public_bar()`, `_process_public_bar()`, `public_bar_confirmation()`, `_send_external_bar_email()`, webhook handler (appel `_send_external_bar_email` pour bar_externe)
- `application/views/paiements_en_ligne/bs_public_bar.php` : formulaire public
- `application/views/paiements_en_ligne/bs_public_bar_confirmation.php` : page de confirmation
- `application/language/{french,english,dutch}/paiements_en_ligne_lang.php` : clés `gvv_public_bar_*`
- `application/tests/mysql/PaiementsEnLigneBarExterneTest.php`
- `playwright/tests/paiements-en-ligne-uc2-bar-externe.spec.js`

---

## Étape 15 : Renouvellement de cotisation (UC3, débit de solde)

**Objectif :** Permettre au pilote connecté de renouveler sa cotisation par débit de son compte pilote, sans CB ni HelloAsso.

**Prérequis :** Le trésorier marque un ou plusieurs tarifs comme "produit de cotisation" via le flag `is_cotisation` dans la gestion des tarifs.

**Flux :**
1. Pilote accède à "Mon Compte" → "Payer ma cotisation"
2. Affichage des tarifs marqués `is_cotisation=1` et valides à la date du jour, avec le solde disponible
3. Sélection + confirmation
4. Vérification : solde ≥ montant (sinon refus), pas de doublon pour l'année (sinon refus)
5. Écriture : débit 411 pilote → crédit 417 recette cotisation + création licence

**✅ Complète**
- ✅ PHPUnit (4 tests) : flag is_cotisation, débit succès (écriture + licence), solde insuffisant (garde), doublon (garde) — `application/tests/mysql/PaiementsEnLigneCotisationPiloteTest.php`
- ✅ PHPUnit (2 tests) : migration 099 up/down — `application/tests/mysql/TarifsIsCotisationMigrationTest.php`
- ✅ Playwright (3 tests) : sans session → login, pilote → page accessible, solde affiché — `playwright/tests/paiements-en-ligne-uc3-cotisation-pilote.spec.js`

**Fichiers créés/modifiés :**
- `application/migrations/098_cotisation_produits.php`
- `application/migrations/099_tarifs_is_cotisation.php`
- `application/models/tarifs_model.php` (méthodes `get_cotisation_products_for_section`, `get_cotisation_product_by_id`)
- `application/libraries/Gvvmetadata.php` (metadata boolean is_cotisation)
- `application/controllers/paiements_en_ligne.php` (méthodes `cotisation`, `_process_cotisation`)
- `application/controllers/welcome.php` (visibilité carte cotisation découplée de HelloAsso)
- `application/views/tarifs/bs_formView.php` (champ is_cotisation)
- `application/views/tarifs/bs_tableView.php` (colonne is_cotisation)
- `application/views/paiements_en_ligne/bs_cotisation_form.php` (affichage solde, bouton débit)
- `application/views/bs_menu.php` (lien admin_cotisations)
- `application/language/{french,english,dutch}/paiements_en_ligne_lang.php` (clés UC3)
- `application/tests/mysql/PaiementsEnLigneCotisationPiloteTest.php`
- `playwright/tests/paiements-en-ligne-uc3-cotisation-pilote.spec.js`

---

## Étape 16 : Paiement CB bon de découverte depuis vols_decouverte/create (UC4)

**Objectif :** Intégrer le paiement CB pour les bons de découverte directement dans le formulaire de création `vols_decouverte/create`, en supprimant la page `decouverte_manager` dédiée.

**Flux :**
1. L'utilisateur accède à `vols_decouverte/create` (accessible aux trésoriers, gestionnaires vd et pilotes vd)
2. Il remplit le formulaire et clique sur "Payer par CB (HelloAsso)"
3. Un checkout HelloAsso est créé avec les données du formulaire (produit, bénéficiaire, email)
4. Redirection vers la page QR/lien (`paiements_en_ligne/decouverte_qr`)
   - Cette page inclut un lien direct + un petit QR de transfert vers téléphone
   - Le QR est masqué si le paiement est initié par le même utilisateur
5. Webhook → handler existant → création du bon de vol, recette comptable, email bénéficiaire + club

**Règles de visibilité :**
- "Créer" : trésorier/bureau/admin uniquement
- "Payer par CB" : tous les utilisateurs avec accès à la page (tresorier, gestion_vd, pilote_vd) si HelloAsso activé et dev_user
- "Créer et continuer" : supprimé

**Validation :** ✅
- ✅ Test PHPUnit : webhook `type=decouverte` → bon créé, recette enregistrée — `application/tests/mysql/PaiementsEnLigneWebhookTest.php`
- ✅ Test Playwright (4 tests) : pilote ordinaire refusé, trésorier autorisé, QR invalide redirect, confirmation publique sans login — `playwright/tests/paiements-en-ligne-uc4-decouverte.spec.js`

**Fichiers créés/modifiés :**
- `application/controllers/vols_decouverte.php` : `create()` étendu (pilote_vd, is_tresorier, helloasso_enabled), `formValidation()` override, `_initiate_decouverte_helloasso()`
- `application/views/vols_decouverte/bs_formView.php` : boutons personnalisés en mode création
- `application/controllers/paiements_en_ligne.php` : suppression de `decouverte_manager()`, `_process_decouverte_manager()`, `_get_decouverte_products()` ; `decouverte_qr()` élargi aux rôles gestion_vd/pilote_vd
- `application/views/paiements_en_ligne/bs_decouverte_qr.php` : back URL → `vols_decouverte/create`
- `application/views/bs_menu.php` : suppression entrée `gvv_decouverte_menu`
- `application/language/{french,english,dutch}/paiements_en_ligne_lang.php` : clé `gvv_decouverte_payer_cb_button`
- `application/tests/mysql/PaiementsEnLigneWebhookTest.php` : inchangé
- `playwright/tests/paiements-en-ligne-uc4-decouverte.spec.js` : mis à jour
- Suppression : `application/views/paiements_en_ligne/bs_decouverte_manager_form.php`

---

## Étape 17 : Tests de recette et validation finale

**Objectif :** Valider l'ensemble du module en conditions proches de la production.

**Checklist :**
- [ ] Tests PHPUnit : couverture ≥ 70% sur le contrôleur, le modèle et la bibliothèque HelloAsso
- [ ] Test de migration : `up()` + `down()` sans erreur sur la BDD de test
- [ ] Test Playwright smoke : UC5 débit de solde bar (sans sandbox)
- [ ] `[SKIP SI SANDBOX]` Test Playwright smoke : EF1 provisionnement pilote (EF1 → EF2 → EF3)
- [ ] Vérification visibilité `dev_users` : boutons HelloAsso absents pour utilisateur ordinaire, présents pour `dev_users`
- [ ] Test Playwright smoke : accès liste trésorier (EF4)
- [ ] Test Playwright smoke : page config admin (EF5)
- [ ] Vérification sécurité : signature webhook invalide rejetée, CSRF actif, accès rôles respectés
- [ ] Vérification section obligatoire : tentative de paiement CB avec section "Toutes" → refus sur tous les UC CB (EF1, UC2, UC4)
- [ ] Vérification logs : fichier `helloasso_payments_YYYY-MM-DD.log` créé, secrets masqués, `txid` présent
- [ ] Vérification idempotence : webhook envoyé deux fois → une seule écriture comptable

**Fichiers de tests à conserver dans la suite de régression :**
- `application/tests/mysql/PaiementsEnLigneMySqlTest.php`
- `application/tests/integration/PaiementsEnLigneWebhookTest.php`
- `playwright/tests/paiements-en-ligne-smoke.spec.js`
