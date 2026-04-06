# Plan d'implémentation : Page Publique d'Achat de Vol de Découverte

**PRD :** `doc/prds/vols_decouverte_page_publique_prd.md`
**Dernière mise à jour :** 2026-04-05

---

## Architecture

### Flux de paiement public

```
GET  vols_decouverte/public[?section=N]
       → affiche les cartes section + formulaire

POST vols_decouverte/public
       → validation serveur + rate limit
       → crée transaction pending (type='decouverte') dans paiements_en_ligne
       → create_checkout HelloAsso
       → redirect HelloAsso

Webhook HelloAsso → paiements_en_ligne/helloasso_webhook
       → process_payment() [existant]
       → type='decouverte' → _create_decouverte_voucher() [existant]
       → _send_external_decouverte_email() [existant, à enrichir]
       → _notify_tresorier_decouverte() [existant]
```

### Réutilisation du code existant

| Composant existant | Utilisation |
|--------------------|-------------|
| `_create_decouverte_voucher()` | Crée le bon après paiement — enrichir avec tel/urgence/poids/nb_personnes |
| `_send_external_decouverte_email()` | Email bénéficiaire — réutilisé tel quel |
| `_notify_tresorier_decouverte()` | Copie aéroclub — réutilisé tel quel |
| `decouverte_qr_image()` | Modèle pour le QR Code section |
| `paiements_en_ligne_config` | Stockage du texte d'accueil et du quota mensuel par section |
| `tarifs` (`type_ticket=1`) | Catalogue produits VD par section |
| `has_vd_par_cb` (sections) | Filtre des sections activées |
| `vols_decouverte` | Source de vérité pour le comptage du quota |

### Nouveaux composants

- Table `public_rate_limit` pour limiter les soumissions par IP
- Helper `check_vd_quota($section_id)` pour la gestion du quota mensuel

---

## Tâches

### T1 — Migration : `nb_personnes_max` dans `tarifs` ✅

**Fichier :** `application/migrations/101_tarifs_nb_personnes_max.php`

Ajoute `nb_personnes_max TINYINT UNSIGNED NOT NULL DEFAULT 1` à la table `tarifs`.

Mettre à jour `application/config/migration.php` : `$config['migration_version'] = 101;`

### T2 — Migration : table `public_rate_limit` ✅

**Fichier :** `application/migrations/102_public_rate_limit.php`

```sql
CREATE TABLE public_rate_limit (
    ip          VARCHAR(45)  NOT NULL,
    endpoint    VARCHAR(50)  NOT NULL,
    attempts    INT          NOT NULL DEFAULT 1,
    window_start DATETIME    NOT NULL,
    PRIMARY KEY (ip, endpoint)
);
```

Mettre à jour `application/config/migration.php` : `$config['migration_version'] = 102;`

### T3 — Metadata : `nb_personnes_max` dans `Gvvmetadata` ✅

**Fichier :** `application/libraries/Gvvmetadata.php`

Dans le bloc `tarifs`, ajouter la définition de `nb_personnes_max` :
- Type : `int`, label multilingue, valeur min 1

### T4 — Clés de langue ✅

**Fichiers :** `application/language/french/gvv_lang.php`, `english/`, `dutch/`

Nouvelles clés à ajouter :

| Clé | FR |
|-----|----|
| `gvv_vd_public_title` | Réserver un vol de découverte |
| `gvv_vd_public_choose_section` | Choisissez votre vol |
| `gvv_vd_public_no_product` | Aucun vol disponible pour cette section |
| `gvv_vd_public_section_disabled` | Les paiements en ligne ne sont pas activés pour cette section |
| `gvv_vd_public_beneficiaire` | Nom et prénom du bénéficiaire |
| `gvv_vd_public_de_la_part` | De la part de |
| `gvv_vd_public_occasion` | Occasion |
| `gvv_vd_public_acheteur_email` | Email de l'acheteur |
| `gvv_vd_public_acheteur_tel` | Téléphone de l'acheteur |
| `gvv_vd_public_urgence` | Contact urgence (nom et téléphone) |
| `gvv_vd_public_poids` | Poids cumulé des passagers (kg) |
| `gvv_vd_public_nb_personnes` | Nombre de passagers |
| `gvv_vd_public_nb_personnes_max` | maximum : %d passager(s) |
| `gvv_vd_public_pay_btn` | Payer par carte bancaire |
| `gvv_vd_public_rate_limit` | Trop de tentatives. Réessayez dans une heure. |
| `gvv_vd_public_error_beneficiaire` | Le nom du bénéficiaire est obligatoire |
| `gvv_vd_public_error_email` | L'adresse email est invalide |
| `gvv_vd_public_error_tel` | Le téléphone est obligatoire |
| `gvv_vd_public_error_product` | Produit invalide ou indisponible |
| `gvv_vd_public_error_nb_personnes` | Le nombre de passagers dépasse la capacité du produit (%d max) |
| `gvv_vd_public_error_checkout` | Erreur lors de la création du paiement. Veuillez réessayer. |
| `gvv_vd_public_default_accueil` | Offrez une expérience inoubliable avec un vol de découverte. |
| `gvv_vd_accueil_text_label` | Texte d'accueil de la page publique (Markdown) |
| `gvv_vd_share_link_btn` | Partager la page d'achat |
| `gvv_vd_share_link_email_label` | Adresse email du destinataire |
| `gvv_vd_share_link_subject` | Offrez un vol de découverte |
| `gvv_vd_share_link_sent` | Lien envoyé avec succès |
| `gvv_nb_personnes_max` | Nb max passagers |
| `gvv_vd_quota_mensuel_label` | Quota mensuel de vols (0 = illimité) |
| `gvv_vd_quota_atteint_titre` | Vols de découverte temporairement indisponibles |
| `gvv_vd_quota_atteint_msg` | Le quota de vols de découverte pour cette section est atteint pour la période en cours. |
| `gvv_vd_quota_reset_dans` | Revenez dans %d jour(s) pour tenter votre chance. |
| `gvv_vd_quota_autres_sections` | D'autres formules sont disponibles : |
| `gvv_vd_quota_aucune_autre` | Aucune autre section n'est disponible pour le moment. |
| `gvv_vd_quota_complet_badge` | Complet |
| `gvv_vd_quota_complet_reset` | Disponible dans %d j |
| `gvv_vd_quota_erreur_post` | Cette section n'accepte plus de nouvelles réservations pour le moment. |

### T5 — Helper : quota mensuel ✅

**Fichier :** `application/helpers/vd_quota_helper.php`

Fonction `get_vd_quota_status($section_id)` — retourne un tableau :

```php
[
    'quota'      => int,   // quota configuré (0 = illimité)
    'vendu'      => int,   // bons vendus dans les 30 derniers jours
    'atteint'    => bool,  // TRUE si vendu >= quota > 0
    'jours_reset'=> int,   // jours avant libération d'un slot (0 si non atteint)
]
```

Implémentation :
- Lire `vd_quota_mensuel` depuis `paiements_en_ligne_config` pour la section
- Compter `SELECT COUNT(*) FROM vols_decouverte WHERE club = $section_id AND cancelled = 0 AND date_vente >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)`
- Si atteint : `jours_reset = 30 - DATEDIFF(CURDATE(), MIN(date_vente) dans la même fenêtre)`
- Si quota = 0 : retourner `atteint = false` sans requête de comptage

Fonction `get_sections_vd_disponibles()` — retourne la liste de toutes les sections avec `has_vd_par_cb = 1`, leur statut de quota, et le nombre de jours avant réarmement pour chacune. Utilisée pour construire la liste des alternatives.

### T5c — Rate limiter : helper ✅

**Fichier :** `application/helpers/rate_limit_helper.php`

Fonction `check_rate_limit($endpoint, $max = 10, $window_seconds = 3600)` :
- Lit l'IP de la requête (avec support proxy via `HTTP_X_FORWARDED_FOR` limité à la première IP)
- Interroge `public_rate_limit` pour l'IP + endpoint dans la fenêtre courante
- Retourne `TRUE` si sous la limite, `FALSE` sinon
- Incrémente le compteur ou crée l'entrée
- Purge les entrées expirées aléatoirement (1 chance sur 100 à chaque appel)

### T6 — Contrôleur : `vols_decouverte/public` ✅

**Fichier :** `application/controllers/vols_decouverte.php`

Nouvelle méthode publique `public_vd($section_id = 0)` :

**GET :**
1. Si `$section_id == 0`, lire `?section` depuis `$this->input->get('section')`
2. Appeler `get_sections_vd_disponibles()` pour obtenir toutes les sections avec leur statut de quota
3. Si `$section_id` forcé : vérifier que la section est valide (`has_vd_par_cb = 1`), sinon message d'erreur
4. Calculer le statut de quota de la section sélectionnée via `get_vd_quota_status($section_id)` — si atteint, passer à la vue le statut, le délai de réarmement, et la liste des sections alternatives disponibles
5. Si quota non atteint : charger les produits VD (`tarifs`, `type_ticket=1`) de la section avec `nb_personnes_max`
6. Charger le texte d'accueil depuis `paiements_en_ligne_config` clé `vd_accueil_text` pour la section
7. Rendre la vue `vols_decouverte/bs_public_vd`

**POST :**
1. Appeler `check_rate_limit('vd_public_form')` → si dépassé, afficher erreur 429
2. Vérifier le quota de la section via `get_vd_quota_status($section_id)` — si atteint, afficher l'écran quota (même rendu que GET avec quota atteint) sans créer de transaction
3. Valider les champs (bénéficiaire, email, téléphone, produit, nb_personnes ≤ max du produit)
4. En cas d'erreur : réafficher le formulaire avec les données saisies et les messages d'erreur
5. Vérifier que HelloAsso est activé pour la section (`enabled = '1'`)
6. Charger les détails du produit depuis `tarifs`
7. Construire les métadonnées :
   ```php
   $meta = [
       'type'                 => 'decouverte',
       'product_reference'    => $product['reference'],
       'product_description'  => $product['description'],
       'compte_destination_id'=> $product['compte'],
       'beneficiaire'         => $beneficiaire,
       'de_la_part'           => $de_la_part,
       'occasion'             => $occasion,
       'beneficiaire_email'   => $acheteur_email,
       'beneficiaire_tel'     => $acheteur_tel,
       'urgence'              => $urgence,
       'poids_passagers'      => $poids,
       'nb_personnes'         => $nb_personnes,
       'origine'              => 'public',
   ]
   ```
8. Créer la transaction pending dans `paiements_en_ligne` (`user_id = 0` pour les achats publics)
9. Créer le checkout HelloAsso via `$this->helloasso->create_checkout()` (return_url → `public_decouverte_confirmation`)
10. Persister le `session_id` HelloAsso via `attach_checkout_info()`
11. Rediriger vers `$checkout['redirect_url']`

**Sécurité :** Tous les champs POST passent par `htmlspecialchars()` / `filter_var()` avant utilisation. Aucune donnée n'est utilisée directement dans une requête SQL — Active Record uniquement.

### T7 — Contrôleur : `vols_decouverte/qrcode` ✅

**Fichier :** `application/controllers/vols_decouverte.php`

Nouvelle méthode `qrcode($section_id = 0)` — accessible aux rôles `gestion_vd`, `tresorier`, `club-admin`, admin :

1. Valider que `$section_id` correspond à une section avec `has_vd_par_cb = 1`
2. Construire l'URL : `site_url('vols_decouverte/public?section=' . $section_id)`
3. Générer le QR Code PNG via `phpqrcode` (même pattern que `decouverte_qr_image()`)
4. Retourner l'image avec `Content-Type: image/png`

### T8 — Contrôleur : `vols_decouverte/send_public_link` ✅

**Fichier :** `application/controllers/vols_decouverte.php`

Nouvelle méthode POST `send_public_link()` — même droits que `qrcode()` :

1. Valider l'email destinataire (`FILTER_VALIDATE_EMAIL`)
2. Construire le lien : `site_url('vols_decouverte/public?section=' . $section_id)`
3. Envoyer email via `$this->email` (pattern identique à `send_payment_link_email()` existant)
4. Flashdata succès/erreur, redirection vers `vols_decouverte/page`

### T9 — Enrichissement `_create_decouverte_voucher()` ✅

**Fichier :** `application/controllers/paiements_en_ligne.php`

Enrichir le tableau `$insert` pour propager les nouveaux champs metadata :

```php
'beneficiaire_tel' => isset($meta['beneficiaire_tel']) ? (string) $meta['beneficiaire_tel'] : '',
'urgence'          => isset($meta['urgence'])          ? (string) $meta['urgence']          : '',
'nb_personnes'     => isset($meta['nb_personnes'])     ? (int)    $meta['nb_personnes']     : 1,
```

Ajouter les guards `field_exists()` correspondants (même pattern que les champs existants).

### T10 — Configuration admin : texte d'accueil et quota ✅

**Fichiers :**
- `application/controllers/paiements_en_ligne.php` — `_save_admin_config()` et `_load_config()`
- `application/views/paiements_en_ligne/bs_admin_config.php`

Dans `_load_config()` : ajouter `vd_accueil_text` et `vd_quota_mensuel` à la liste des clés chargées.

Dans `_save_admin_config()` : ajouter au tableau `$keys` :
- `vd_accueil_text` (string brut, pas d'échappement à la sauvegarde)
- `vd_quota_mensuel` (cast en int, valeur minimale 0)

Dans la vue `bs_admin_config.php` :
- Carte « Page publique » avec un `<textarea>` pour `vd_accueil_text` (indication Markdown supporté)
- Champ `<input type="number" min="0">` pour `vd_quota_mensuel` avec libellé « Quota mensuel de vols (0 = illimité) » et affichage du compteur actuel : « X bons vendus dans les 30 derniers jours »

### T11 — Vue : page publique ✅

**Fichier :** `application/views/vols_decouverte/bs_public_vd.php`

Structure Bootstrap 5 :

1. **En-tête visuel** : bandeau avec image de fond identique à la vue existante des bons VD, titre et texte d'accueil (Markdown rendu via `nl2br(htmlspecialchars())` avec liste de remplacements Markdown basiques : `**bold**`, `*italic*`, `# titre`)
2. **Sélecteur de section** (masqué si section forcée) : cartes Bootstrap, une par section, avec nom et acronyme. Sélection active au clic via JS ou rechargement de page. Groupées par type d'aéronef si plusieurs sections du même type. Les sections ayant atteint leur quota affichent un badge « Complet » et la mention « Disponible dans X j » — la carte est cliquable mais mène à l'écran quota.
3. **Écran quota atteint** (affiché à la place du formulaire si `$quota_status['atteint'] === true`) :
   - Bloc `.alert-warning` avec titre et message explicatif
   - Mention « Revenez dans X jour(s) » (valeur de `jours_reset`)
   - Si d'autres sections sont disponibles : liste de liens `vols_decouverte/public?section=N` avec nom et prix indicatif
   - Si aucune autre section n'est disponible : message dédié
4. **Sélecteur de produit** : `<select>` mis à jour lors du changement de section (rechargement de page avec `?section=N`), affiche description + prix + capacité max
5. **Formulaire** :
   - `beneficiaire` (required)
   - `de_la_part`, `occasion`
   - `acheteur_email` (required, type=email)
   - `acheteur_tel` (required, type=tel)
   - `urgence`
   - `poids_passagers` (type=number, min=0)
   - `nb_personnes` (type=number, min=1, max=`nb_personnes_max` du produit, visible si `nb_personnes_max > 1`)
   - Token CSRF CodeIgniter
6. **Bouton paiement** : bouton Bootstrap primaire « Payer par carte bancaire »
7. **Messages d'erreur** : `.alert-danger` par champ invalide

Pas de menu GVV ni de bannière club (page publique). Header et footer Bootstrap uniquement.

### T12 — Vue : bouton de partage dans la liste VD ✅

**Fichier :** `application/views/vols_decouverte/bs_tableView.php`

Ajouter un bouton « Partager la page publique » dans la barre d'actions, visible uniquement si `has_vd_par_cb = 1` pour la section active. Le bouton ouvre une modale Bootstrap contenant :
- URL de la page publique (affichée en lecture seule, copiable)
- Champ email destinataire + bouton d'envoi (POST vers `vols_decouverte/send_public_link`)
- Lien « Télécharger le QR Code » → `vols_decouverte/qrcode/{section_id}`

### T13 — Routage ✅

**Fichier :** `application/config/routes.php`

Vérifier qu'aucune règle de routage n'entre en conflit avec les nouvelles URLs. Aucune nouvelle règle nécessaire a priori (routes CI par convention).

### T14 — Tests PHPUnit ✅

**Fichier :** `application/tests/integration/PaiementsEnLignePublicVdTest.php`

Tests :
- Migration 101 et 102 : vérifier que les colonnes/tables existent, rollback
- `nb_personnes_max` : valeur par défaut = 1 après migration
- Rate limiter : sous la limite → `TRUE`, au-delà → `FALSE`, fenêtre glissante correcte
- `get_vd_quota_status()` : quota = 0 → `atteint = false` sans requête de comptage
- `get_vd_quota_status()` : quota = 5, 4 bons dans la fenêtre → `atteint = false`
- `get_vd_quota_status()` : quota = 5, 5 bons dans la fenêtre → `atteint = true`, `jours_reset` correct
- `get_vd_quota_status()` : bon le plus ancien à J-15 → `jours_reset = 15`

### T15 — Tests Playwright ✅

**Fichier :** `playwright/tests/vols-decouverte-public.spec.js`

Tests :
- GET `/vols_decouverte/public` sans `?section` : affiche les cartes section (au moins 1 pour section 4 Général)
- GET `/vols_decouverte/public?section=4` : masque le sélecteur, affiche le formulaire
- GET `/vols_decouverte/public?section=999` : affiche un message d'erreur
- POST avec champs vides : erreurs de validation visibles, formulaire réaffiché
- POST avec `nb_personnes > nb_personnes_max` : erreur de validation
- `vols_decouverte/qrcode/4` : retourne une image PNG (status 200, Content-Type image/png)
- Bouton « Partager » visible dans la liste pour testadmin (section 4)
- Quota atteint (via config DB = 1 + 1 bon existant) : écran quota affiché, délai de réarmement visible
- Quota atteint : lien vers section alternative présent si une autre section est disponible
- POST sur section avec quota atteint : rejeté sans créer de transaction

---

## Dépendances et ordre d'exécution

```
T1 (migration 101)  → T3 (metadata) → T6 (contrôleur, champ nb_personnes_max)
T2 (migration 102)  → T5c (rate limiter)
T4 (langues)        → T11 (vue), T6 (contrôleur)
T5  (quota helper)  → T6 (contrôleur), T10 (admin_config)
T5c (rate limiter)  → T6 (contrôleur)
T6  (contrôleur)    → T11 (vue), T13 (routage)
T7, T8              → T12 (vue liste)
T9                  → [webhook existant enrichi]
T10                 → [admin_config existant enrichi]
T1..T12             → T14 (PHPUnit), T15 (Playwright)
```

---

## Points d'attention

- **`user_id = 0`** dans `paiements_en_ligne` pour les transactions publiques — vérifier que `_create_decouverte_voucher()` et le webhook tolèrent `user_id = 0` (déjà le cas : aucune jointure sur `users` dans ce flux)
- **Champ `urgence`** dans `vols_decouverte` est de type `varchar(128)` — le formulaire public concatène nom et téléphone urgence dans ce champ unique (même comportement que le formulaire interne existant)
- **Markdown** : utiliser une conversion minimaliste (paragraphes, gras, italique, listes) — pas de bibliothèque externe. La fonction de rendu est dans le contrôleur, pas dans la vue.
- **`nb_personnes`** dans le formulaire : le champ n'est affiché que si `nb_personnes_max > 1`. Pour les produits solo (`nb_personnes_max = 1`), le champ est masqué et sa valeur est toujours 1.
- **CSRF** : CodeIgniter génère automatiquement le token sur les formulaires POST. La page publique doit inclure le token CI ou désactiver la protection CSRF pour cette route spécifiquement (à vérifier dans `config/config.php` — option `csrf_exclude_uris`).
- **Idempotence webhook** : `_create_decouverte_voucher()` vérifie déjà `paiement = 'HelloAsso:$txid'` avant insertion — pas de modification nécessaire.
- **Quota et webhook** : la vérification du quota est faite avant la création de la transaction (côté formulaire). Le webhook ne recontôle pas le quota — le bon est créé dès qu'un paiement est confirmé. Ce comportement est intentionnel : un utilisateur qui a démarré son paiement avant que le quota soit atteint doit recevoir son bon.
- **Précision du `jours_reset`** : calculé en jours entiers (`DATEDIFF`). Une valeur de 0 est possible si le plus ancien bon expire aujourd'hui — afficher « disponible demain » dans ce cas.
- **Comptage du quota** : porte sur tous les bons non annulés de la section dans la fenêtre, indépendamment de l'origine (public, interne, pilote). Cela évite que les ventes internes court-circuitent le quota de la page publique.
