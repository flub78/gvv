# Plan d'Implémentation : Paiements en Ligne

**Fonctionnalité :** Provisionnement de Compte par Paiement en Ligne
**PRD :** `doc/prds/paiements_en_ligne_prd.md`
**Spike de référence :** `doc/plan/HelloAssoSpike.md`
**Statut :** En cours (étapes 1–3 terminées)

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
| 1 | — | Audit système notes de bar existant | — | ✅ |
| 2 | UC5 | Paiement notes de bar — débit de solde pilote | HAUTE | ✅ |
| 3 | — | Migration de base de données | — | ✅ |
| 4 | — | Bibliothèque HelloAsso | — | ☐ |
| 5 | EF5 | Configuration des plateformes par section | MOYENNE | ☐ |
| 6 | — | Contrôleur et modèle de base | — | ☐ |
| 7 | EF2 | Webhook + écriture comptable (infrastructure partagée) | HAUTE | ☐ |
| 8 | UC1 | Paiement notes de bar — pilote authentifié par carte | HAUTE | ☐ |
| 9 | EF1 | Provisionnement en ligne par le pilote | HAUTE | ☐ |
| 10 | EF3 | Vérification du paiement / Mon Compte | HAUTE | ☐ |
| 11 | EF4 | Liste des provisionnements pour le trésorier | HAUTE | ☐ |
| 12 | UC2 | Paiement notes de bar — personne externe via QR Code | MOYENNE | ☐ |
| 13 | UC3 | Renouvellement de cotisation en ligne | MÉDIUM | ☐ |
| 14 | UC4 | Paiement bon de découverte — lien/QR Code public | MÉDIUM | ☐ |
| 15 | — | Tests de recette et validation finale | — | ☐ |

---

## Étape 1 : Audit du système notes de bar existant

**Objectif :** Comprendre le mécanisme existant dans GVV pour les notes de bar avant toute implémentation, afin de ne pas repartir de zéro ni créer de doublon.

**Résultat de l'audit :**

- Il n'existe pas de table "notes de bar" ni de mécanisme de notes en attente dans GVV.
- Le modèle actuel est basé sur la confiance : c'est le trésorier qui facture manuellement un pilote en créant une écriture comptable (débit compte 411 pilote, crédit compte recette bar 7xx).
- Il n'y a pas de gérant de bar établissant des notes : le pilote est responsable de déclarer ce qu'il a consommé.
- Le compte de recette bar (7xx) varie selon le club et doit être configurable par section.
- UC5 et UC1 reproduisent exactement ce que le trésorier fait manuellement, mais à l'initiative du pilote lui-même : saisie d'un descriptif de consommation + montant, génération de l'écriture comptable correspondante.
- **Toutes les sections n'ont pas de bar.** La fonctionnalité de paiement bar (UC5, UC1) ne doit être visible que dans les sections qui ont un bar. Un flag `has_bar` (booléen, défaut `false`) sera ajouté à la table `sections`. L'option de règlement bar n'est affichée au pilote que si sa section active a `has_bar = true`.

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

**Objectif :** Créer les tables `paiements_en_ligne` et `paiements_en_ligne_config` nécessaires aux paiements par carte (UC1 et suivants).

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

**Validation :**
- Test unitaire : `get_oauth_token()` retourne un token avec un mock cURL (pas de dépendance réseau)
- Test unitaire : `verify_webhook_signature()` accepte une signature valide et rejette une signature invalide
- Test unitaire : logging produit un fichier daté avec les mots-clés `[HELLOASSO]` et `txid=`, secrets masqués

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
- **Compte de recette bar (7xx)** : sélecteur de compte parmi les comptes 7xx du plan comptable de la section — utilisé comme contrepartie crédit pour UC5 et UC1 (visible uniquement si bar activé)
- Montant minimum (10€) et maximum (500€) par transaction
- Activation/désactivation par section
- Bouton "Tester la connexion" : appelle HelloAsso OAuth2 et affiche le résultat
- Log d'audit de chaque changement de configuration

**Sécurité :** Accès réservé au rôle `admin`.

**Validation :**
- Test Playwright : accès refusé pour un non-admin, formulaire sauvegardé et rechargé correctement pour un admin
- `[SKIP SI SANDBOX]` Test du bouton "Tester la connexion" : succès retourné en mode sandbox

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

**Validation :**
- Test PHPUnit du modèle : CRUD complet sur les tables créées à l'étape 3
- Accès à `/paiements_en_ligne` retourne HTTP 200 pour un pilote connecté

---

## Étape 7 : Webhook + écriture comptable (EF2 — infrastructure partagée)

**Objectif :** Implémenter le handler webhook HelloAsso qui sera utilisé par UC1, EF1 et tous les cas d'usage CB suivants. Le dispatch vers la bonne logique métier est basé sur le champ `type` dans les `metadata` de la transaction.

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
   - `type=cotisation` : écriture compte cotisation 417
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

---

## Étape 8 : Paiement bar par carte — pilote (UC1)

**Objectif :** Permettre au pilote connecté de déclarer et régler ses consommations de bar par carte via HelloAsso, selon le même modèle de confiance que UC5.

**Prérequis :** Étapes 4, 5, 6, 7 complétées.

**Flux :**
1. Pilote accède à "Mon Compte" → "Régler mes consommations de bar par carte"
2. Formulaire : montant (min 0,50€), descriptif libre obligatoire (ex. "Consommations bar – 25/03/2026")
3. Vérification section active (pas "Toutes") via `_require_active_section()`
4. Création transaction `pending` avec `metadata.type=bar` et `metadata.description`
5. Appel `Helloasso::create_checkout()` → redirection vers HelloAsso
6. Retour webhook → handler étape 7 → écriture (débit 411 pilote, crédit compte bar 7xx configuré)
7. Confirmation email + historique

**Sécurité :**
- Token CSRF sur le formulaire
- `_require_active_section()` : refus si section "Toutes" avec message explicite
- Le pilote ne peut régler que pour son propre compte
- L'URL est inaccessible et l'option invisible si la section active a `has_bar = false`

**Validation :**
- `[SKIP SI SANDBOX]` Test Playwright en sandbox : flow complet pilote → paiement bar → écriture créée
- Test PHPUnit : tentative avec section "Toutes" → refus, aucun checkout créé
- Test PHPUnit : tentative sur section avec `has_bar = false` → refus

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

**Validation :**
- `[SKIP SI SANDBOX]` Test Playwright en sandbox : formulaire soumis → redirection HelloAsso → retour confirmation
- Test PHPUnit : montant hors limites (9€, 501€) → erreur de validation
- Test PHPUnit : section "Toutes" → refus avec message, aucun appel HelloAsso

---

## Étape 10 : Intégration "Mon Compte" (EF3)

**Objectif :** Afficher les paiements en ligne dans la page Mon Compte du pilote.

**Modifications :**
- `compta/mon_compte` : badge distinctif sur les lignes d'écriture issues de paiements en ligne (identification via `num_cheque LIKE 'HelloAsso:%'`)
- Lien "Provisionner mon compte en ligne" dans le menu Mon Compte
- Page `paiements_en_ligne/index` : historique des transactions du pilote connecté (date, montant, statut, référence)

**Validation :**
- `[SKIP SI SANDBOX]` Test Playwright : après provisionnement, la nouvelle écriture apparaît dans Mon Compte avec le badge "en ligne"
- Test PHPUnit : `get_transactions()` filtré par `user_id` retourne uniquement les transactions du pilote

---

## Étape 11 : Liste trésorier (EF4)

**Objectif :** Vue centralisée des paiements en ligne pour le trésorier.

**Méthode :** `paiements_en_ligne::liste()` — réservé aux rôles `tresorier`, `bureau`, `admin`

**Tableau :** Date/heure, nom du pilote, montant, plateforme, référence de transaction, statut, lien vers l'écriture comptable, commission

**Filtres :** Période, plateforme, section, statut, pilote

**Exports :** CSV (rapprochement bancaire), PDF (archivage/audit)

**Statistiques :** Nombre de provisionnements du mois, montant total, commissions totales

**Validation :**
- Test Playwright : accès refusé pour un pilote simple, accessible pour un trésorier
- Test PHPUnit : filtres par période et statut retournent les bons enregistrements
- Export CSV généré sans erreur

---

## Étape 12 : Paiement externe bar via QR Code (UC2)

**Objectif :** Permettre à une personne extérieure de payer ses consommations via un QR Code affiché au bar, sans compte GVV.

**Fichiers :**
- Route publique : `paiements_en_ligne/public_bar`
- Vue : formulaire sans connexion (nom, prénom, email, description, montant libre min 2€)
- QR Code généré et géré par l'admin : URL pointant vers `public_bar?club=X`

**Flux :**
1. Scan QR Code → page publique
2. Remplir formulaire → création transaction avec `metadata.type=bar_externe`
3. Checkout HelloAsso → webhook → handler étape 7 → écriture recette bar
4. Email de confirmation à l'adresse fournie

**Sécurité :** CSRF, validation montant minimum. Le paramètre `club` doit être un identifiant de section valide — tout accès sans `club` valide est rejeté avec message d'erreur explicite.

**Validation :**
- `[SKIP SI SANDBOX]` Test Playwright : accès sans connexion avec club valide, formulaire soumis, redirection HelloAsso
- Test PHPUnit : accès sans paramètre `club` (ou `club=0`) → refus, aucun checkout créé
- Test PHPUnit : webhook `type=bar_externe` → écriture de recette bar créée sans compte pilote

---

## Étape 13 : Renouvellement de cotisation (UC3)

**Objectif :** Permettre au pilote connecté de renouveler sa cotisation en ligne.

**Prérequis :** Configuration des "produits de cotisation" par le trésorier (libellé, montant, validité, compte comptable).

**Flux :**
1. Pilote accède à "Mon Compte" → "Gérer ma Cotisation"
2. Affichage des produits disponibles
3. Sélection + paiement via HelloAsso avec `metadata.type=cotisation`
4. Webhook → handler étape 7 → écriture compte 417, marquage pilote "cotisant à jour", attestation PDF par email, notification trésorier

**Configuration requise :** Interface admin pour créer/modifier les produits de cotisation.

**Validation :**
- Test PHPUnit : webhook `type=cotisation` → écriture 417 créée, statut cotisant mis à jour
- `[SKIP SI SANDBOX]` Test Playwright : pilote voit son statut "à jour" après paiement

---

## Étape 14 : Bon de découverte via lien/QR Code (UC4)

**Objectif :** Permettre à un gestionnaire de générer un lien de paiement public pour un bon de vol de découverte.

**Flux :**
1. Gestionnaire génère un lien avec montant préconfiguré et type de vol (ex. "30 min – 120€")
2. Personne externe remplit nom/prénom/email → checkout HelloAsso avec `metadata.type=decouverte`
3. Webhook → handler étape 7 → création du bon de vol (logique identique à la création manuelle), recette comptable, email à l'externe + notification boîte mail du club

**Configuration requise :** Interface pour générer et gérer les liens (montant, libellé, durée de validité). Le lien encode obligatoirement l'identifiant de section.

**Validation :**
- Test PHPUnit : webhook `type=decouverte` → bon créé, recette enregistrée
- `[SKIP SI SANDBOX]` Test Playwright : flow complet depuis lien public → confirmation + email

---

## Étape 15 : Tests de recette et validation finale

**Objectif :** Valider l'ensemble du module en conditions proches de la production.

**Checklist :**
- [ ] Tests PHPUnit : couverture ≥ 70% sur le contrôleur, le modèle et la bibliothèque HelloAsso
- [ ] Test de migration : `up()` + `down()` sans erreur sur la BDD de test
- [ ] Test Playwright smoke : UC5 débit de solde bar (sans sandbox)
- [ ] `[SKIP SI SANDBOX]` Test Playwright smoke : UC1 paiement bar par carte (EF2 → confirmation)
- [ ] `[SKIP SI SANDBOX]` Test Playwright smoke : EF1 provisionnement pilote (EF1 → EF2 → EF3)
- [ ] Test Playwright smoke : accès liste trésorier (EF4)
- [ ] Test Playwright smoke : page config admin (EF5)
- [ ] Vérification sécurité : signature webhook invalide rejetée, CSRF actif, accès rôles respectés
- [ ] Vérification section obligatoire : tentative de paiement CB avec section "Toutes" → refus sur tous les UC CB (UC1, EF1, UC2, UC3, UC4)
- [ ] Vérification logs : fichier `helloasso_payments_YYYY-MM-DD.log` créé, secrets masqués, `txid` présent
- [ ] Vérification idempotence : webhook envoyé deux fois → une seule écriture comptable

**Fichiers de tests à conserver dans la suite de régression :**
- `application/tests/mysql/PaiementsEnLigneMySqlTest.php`
- `application/tests/integration/PaiementsEnLigneWebhookTest.php`
- `playwright/tests/paiements-en-ligne-smoke.spec.js`
