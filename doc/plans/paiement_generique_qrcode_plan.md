# Plan d'implémentation : Paiement Générique par QR Code

**PRD :** `doc/prds/paiement_generique_qrcode_prd.md`
**Statut :** À faire

---

## Contraintes techniques préalables

- Aucune migration de base de données requise : la table `paiements_en_ligne` existante convient. Le type est stocké dans la colonne JSON `metadata`, comme pour les autres flux.
- Le compte cible suit le même pattern que `decouverte` : stocké dans `metadata['compte_destination_id']`.
- La génération de QR code utilise `phpqrcode` déjà présent dans le contrôleur (`paiements_en_ligne.php` lignes 205, 1834).
- Le handler webhook est un `switch($type)` dans `paiements_en_ligne_model.php` (ligne 734) : ajouter `case 'paiement_generique'` suffit.
- Les tests Playwright nécessitant un vrai appel HelloAsso sont skippés si le sandbox n'est pas configuré (convention existante).

---

## Étapes

| # | Description | Statut |
|---|-------------|--------|
| 1 | Fichiers de langue | ✅ |
| 2 | Formulaire de création (contrôleur + vue) | ✅ |
| 3 | Génération checkout HelloAsso + affichage QR | ✅ |
| 4 | Handler webhook (écriture comptable) | ✅ |
| 5 | Listing et filtre par type | ✅ |
| 6 | Navigation et permissions | ✅ |
| 7 | Tests PHPUnit | ✅ |
| 8 | Tests Playwright | ✅ |

---

## Étape 1 : Fichiers de langue

**Objectif :** Ajouter les clés nécessaires dans les trois fichiers de langue (french, english, dutch).

Clés à ajouter dans `paiements_en_ligne_lang.php` :
- Titre de la page, libellés des champs (montant, description, compte, email payeur)
- Messages de succès/erreur
- Labels du listing (type, statut, colonne description)

**Validation :** ✅ quand `php -l` passe sur les trois fichiers.

---

## Étape 2 : Formulaire de création

**Objectif :** Méthode `paiement_generique()` dans `paiements_en_ligne.php` — GET affiche le formulaire, POST valide et crée la transaction en attente.

Le formulaire contient :
- Montant (obligatoire, entre `montant_min` et `montant_max` de la config HelloAsso)
- Description (obligatoire, max 255 caractères)
- Compte comptable cible (sélecteur parmi les comptes actifs de la section)
- Email du payeur (optionnel)

À la soumission POST :
- Générer un `gvv_transaction_id` au format `gen-{club}-{user}-{time}-{random}`
- Insérer une ligne `statut=pending` dans `paiements_en_ligne` avec les métadonnées `type=paiement_generique`, `description`, `compte_destination_id`, `payer_email`
- Rediriger vers l'étape 3 (affichage QR)

**Validation :** ✅ quand le formulaire s'affiche, que les validations rejettent les saisies incorrectes, et qu'une ligne `pending` est insérée en base.

---

## Étape 3 : Génération checkout HelloAsso + affichage QR

**Objectif :** Méthode `paiement_generique_checkout($transaction_id)` — appelle `Helloasso::create_checkout()` et affiche le lien + QR code.

- `item_name` = description saisie
- `metadata` = `['type' => 'paiement_generique', 'description' => ..., 'compte_destination_id' => ..., 'gvv_transaction_id' => ...]`
- En cas de succès : stocker `helloasso_checkout_intent_id` dans les métadonnées de la transaction, afficher le lien et le QR code (endpoint `paiement_generique_qr/$transaction_id` qui émet le PNG via `QRcode::png()`)
- En cas d'échec HelloAsso : marquer `statut=failed`, afficher erreur

**Validation :** ✅ quand le QR code s'affiche sur la page et pointe vers l'URL HelloAsso correcte (vérifiable sans sandbox).

---

## Étape 4 : Handler webhook (écriture comptable)

**Objectif :** Ajouter `case 'paiement_generique'` dans le `switch($type)` du modèle (ligne 734 de `paiements_en_ligne_model.php`).

L'écriture créée est identique à ce que le trésorier ferait manuellement :
- Débit : compte de passage HelloAsso (`compte_passage` de la config)
- Crédit : `compte_destination_id` issu des métadonnées
- Libellé : `description` issue des métadonnées
- Montant : montant de la transaction

Après l'écriture : marquer `statut=completed`, envoyer email de confirmation au trésorier (adresse configurée dans la section).

**Validation :** ✅ quand un appel webhook simulé (POST avec payload JSON valide + signature HMAC) crée l'écriture comptable attendue et passe le transaction à `completed`.

---

## Étape 5 : Listing et filtre par type

**Objectif :** Ajouter un filtre `type` à la méthode `liste()` existante et à la vue `bs_liste.php`, pour permettre d'isoler les paiements génériques.

- Ajouter `?type=paiement_generique` comme filtre GET dans la méthode `liste()`
- Ajouter la colonne **Description** dans la vue (extraite de `metadata['description']`)
- Ajouter un lien direct "Paiements génériques" dans le listing pointant vers `liste?type=paiement_generique`

**Validation :** ✅ quand la liste filtrée n'affiche que les paiements génériques et que la colonne description est visible.

---

## Étape 6 : Navigation et permissions

**Objectif :** Rendre la fonctionnalité accessible depuis le menu.

- Ajouter une entrée de menu "Paiement générique" sous la section paiements en ligne, visible uniquement pour les rôles trésorier/admin et si HelloAsso est activé pour la section
- Ajouter la route dans `routes.php` si nécessaire
- Vérifier les permissions dans `authorization/routes_and_permissions.md`

**Validation :** ✅ quand le lien apparaît dans le menu pour un trésorier et est absent pour un pilote ordinaire.

---

## Étape 7 : Tests PHPUnit

**Objectif :** Couvrir les cas critiques sans appel HelloAsso.

Tests à écrire dans `application/tests/mysql/PaiementGeneriqueTest.php` :
- Création d'une transaction `pending` avec les bonnes métadonnées
- Webhook simulé → écriture comptable créée avec le bon compte et la bonne description
- Idempotence du webhook (second appel ignoré)
- Annulation d'une transaction pending
- Validation : montant hors limites rejeté, description vide rejetée

**Validation :** ✅ quand `./run-all-tests.sh` passe sans régression.

---

## Étape 8 : Tests Playwright

**Objectif :** Smoke tests end-to-end.

Tests à écrire dans `playwright/tests/paiement-generique-smoke.spec.js` :
- Accès à la page formulaire (trésorier) ✅ / (pilote ordinaire) → accès refusé
- Soumission avec champs invalides → messages d'erreur visibles
- `[SKIP SI SANDBOX]` Flux complet : formulaire → QR affiché → webhook simulé → écriture créée → listing mis à jour

**Validation :** ✅ quand les tests non-sandbox passent sur le serveur de dev.
