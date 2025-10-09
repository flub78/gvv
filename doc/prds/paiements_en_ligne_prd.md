# PRD : Paiements en Ligne pour Provisionnement de Compte

**Produit :** GVV (Gestion Vol à Voile)
**Fonctionnalité :** Provisionnement de Compte par Paiement en Ligne
**Version :** 1.0
**Statut :** Proposition
**Créé :** 2025-01-09
**Auteur :** Équipe Produit GVV

---

## 1. Résumé Exécutif

Ce PRD décrit les exigences pour permettre aux membres des clubs de vol à voile d'approvisionner leurs comptes pilotes en ligne via carte bancaire. Le système intégrera des plateformes de paiement en ligne (priorité : HelloAsso) et générera automatiquement les écritures comptables correspondantes dans GVV, exactement comme si le trésorier les avait saisies manuellement.

Cette fonctionnalité améliorera l'expérience utilisateur, réduira la charge de travail du trésorier, et modernisera la gestion financière des clubs en permettant des paiements instantanés 24/7.

---

## 2. Contexte et Arrière-plan

### 2.1 État Actuel

Le système GVV gère actuellement les comptes pilotes (compte 411 du plan comptable) avec les fonctionnalités suivantes :

**Provisionnement Manuel par le Trésorier :**
- Le trésorier crée une écriture comptable via `compta/credit_pilote`
- Écriture de type : Compte de charge (6xx) → Compte pilote (411)
- Génère une ligne dans la table `ecritures` avec :
  - `compte1` : compte de charge
  - `compte2` : compte pilote (codec 411)
  - `montant` : montant crédité
  - `date_op` : date de l'opération
  - `description` : libellé de l'opération
  - `num_cheque` : référence du paiement

**Consultation par le Pilote :**
- Chaque pilote peut consulter son compte via `compta/mon_compte`
- Affichage de l'historique des mouvements (débits/crédits)
- Calcul du solde en temps réel basé sur les écritures comptables
- Vue détaillée des opérations avec dates, montants, descriptions

### 2.2 Problèmes Identifiés

**P1 : Délai de Provisionnement (Pilote)**
- Le pilote doit attendre que le trésorier saisisse manuellement le versement
- Pas de provisionnement possible en dehors des heures d'ouverture du club
- Risque d'oubli ou de retard dans la saisie manuelle

**P2 : Charge de Travail du Trésorier**
- Saisie manuelle répétitive de toutes les recharges de compte
- Rapprochement bancaire complexe avec de nombreux paiements individuels
- Gestion des références de paiement (chèques, virements, espèces)

**P3 : Expérience Utilisateur Limitée**
- Pas d'autonomie pour le provisionnement
- Nécessite un déplacement au club ou un paiement différé
- Pas de confirmation immédiate du crédit

**P4 : Modernisation**
- Les membres s'attendent à pouvoir payer en ligne comme pour d'autres services
- Absence de paiement par carte alors que c'est devenu la norme

---

## 3. Objectifs et Buts

### 3.1 Objectifs Métier

1. **Améliorer l'Autonomie des Pilotes** : Permettre le provisionnement 24/7 sans intervention du trésorier
2. **Réduire la Charge du Trésorier** : Automatiser la saisie des écritures de provisionnement
3. **Moderniser le Club** : Offrir des moyens de paiement modernes attendus par les membres
4. **Garantir la Transparence** : Assurer la traçabilité complète des paiements en ligne

### 3.2 Objectifs Utilisateur

**Pilote/Membre :**
- Provisionner son compte à tout moment via carte bancaire
- Voir immédiatement le crédit apparaître sur son compte
- Recevoir une confirmation de paiement
- Consulter l'historique de ses provisionnements en ligne

**Trésorier :**
- Consulter tous les provisionnements en ligne effectués par les membres
- Vérifier que les écritures comptables sont correctement générées
- Suivre les commissions prélevées par la plateforme de paiement
- Rapprocher automatiquement les virements de la plateforme de paiement

**Administrateur Système :**
- Configurer les paramètres d'intégration avec la plateforme de paiement
- Surveiller le bon fonctionnement des webhooks et notifications
- Gérer les comptes de la plateforme de paiement (HelloAsso, etc.)

---

## 4. Utilisateurs Cibles et Personas

### Persona 1 : Marc - Pilote Actif

**Contexte :**
- Âge : 35 ans, pilote depuis 3 ans
- Vole régulièrement (2-3 fois par mois)
- Habitué aux services en ligne et paiements mobiles
- Travaille en semaine, vole le week-end

**Points de Douleur :**
- Doit prévoir d'apporter du liquide ou un chèque au club
- Oublie parfois de provisionner son compte avant de voler
- Trouve contraignant de dépendre des horaires du trésorier

**Résultat Souhaité :**
- Recharger son compte depuis son smartphone avant de venir au club
- Voir immédiatement le crédit disponible
- Pouvoir voler sans se soucier de la logistique de paiement

### Persona 2 : Sophie - Trésorière de Club

**Contexte :**
- Âge : 58 ans, trésorière depuis 7 ans
- Gère les comptes de 80 membres actifs
- Utilise GVV toutes les semaines
- Bénévole, cherche à optimiser son temps

**Points de Douleur :**
- Saisie manuelle de 20-30 provisionnements par mois
- Rapprochement bancaire fastidieux avec multiples modes de paiement
- Demandes fréquentes des pilotes pour connaître leur solde

**Résultat Souhaité :**
- Réduction drastique de la saisie manuelle
- Écritures comptables générées automatiquement et correctement
- Vue centralisée de tous les paiements en ligne
- Traçabilité complète pour l'audit annuel

### Persona 3 : Thomas - Jeune Pilote Étudiant

**Contexte :**
- Âge : 22 ans, pilote en formation
- Budget limité, recharge fréquemment de petits montants
- N'a pas de chéquier, préfère le paiement par carte
- Génération "tout digital"

**Points de Douleur :**
- Difficile de payer en espèces ou par chèque
- Voudrait recharger 50€ à la fois plutôt que des grosses sommes
- Trouve le système actuel archaïque

**Résultat Souhaité :**
- Payer par carte comme pour n'importe quel service en ligne
- Recharger de petits montants facilement et fréquemment
- Interface mobile-friendly

---

## 5. Exigences Fonctionnelles

### 5.1 EF1 : Provisionnement en Ligne par le Pilote (Priorité : HAUTE)

**Description :** Permettre à un membre authentifié de provisionner son compte pilote en ligne via carte bancaire.

**User Story :**
> En tant que pilote, je veux provisionner mon compte en ligne avec ma carte bancaire, afin de pouvoir créditer mon compte à tout moment sans intervention du trésorier.

**Critères d'Acceptation :**
- CA1.1 : Page accessible via menu "Mon Compte" → "Provisionner mon compte en ligne"
- CA1.2 : Formulaire demandant :
  - Montant à créditer (minimum 10€, maximum 500€ par transaction)
  - Sélection du mode de paiement (HelloAsso en priorité)
  - Confirmation des conditions d'utilisation
- CA1.3 : Redirection vers la plateforme de paiement sécurisée (HelloAsso)
- CA1.4 : Après paiement réussi :
  - Génération automatique d'une écriture comptable dans `ecritures`
  - Notification à l'utilisateur (page de confirmation + email optionnel)
  - Mise à jour immédiate du solde visible dans "Mon Compte"
- CA1.5 : En cas d'échec de paiement :
  - Message d'erreur clair
  - Aucune écriture comptable créée
  - Possibilité de réessayer
- CA1.6 : Historique des provisionnements en ligne accessible au pilote

**Règles Métier :**
- Un pilote ne peut provisionner que son propre compte
- Le montant minimum est de 10€ pour limiter les frais de transaction
- Le montant maximum est de 500€ par transaction pour sécurité
- La plateforme de paiement prélève une commission (ex: HelloAsso 0% pour associations)

---

### 5.2 EF2 : Génération Automatique d'Écriture Comptable (Priorité : HAUTE)

**Description :** Lors d'un paiement en ligne réussi, générer automatiquement une écriture comptable identique à celle que le trésorier aurait créée manuellement.

**User Story :**
> En tant que système, je dois générer une écriture comptable lors d'un paiement en ligne réussi, afin que le compte pilote soit crédité automatiquement comme si le trésorier l'avait fait manuellement.

**Critères d'Acceptation :**
- CA2.1 : Écriture créée via `ecritures_model->create_ecriture()` avec :
  ```php
  $data = [
      'annee_exercise' => current_year,
      'date_creation' => today,
      'date_op' => payment_date,
      'compte1' => account_charge_id,  // Compte de passage (ex: 467 - Attente paiement en ligne)
      'compte2' => pilot_account_id,    // Compte pilote (411)
      'montant' => payment_amount,
      'description' => "Provisionnement en ligne - [Plateforme] - Réf: [transaction_id]",
      'num_cheque' => "[Plateforme]: [transaction_id]",
      'saisie_par' => 'online_payment_system',
      'club' => section_id
  ]
  ```
- CA2.2 : Le compte de charge utilisé doit être configurable (par défaut : compte 467 "Autres comptes créditeurs ou débiteurs divers")
- CA2.3 : La description contient :
  - Type d'opération ("Provisionnement en ligne")
  - Plateforme utilisée (ex: "HelloAsso")
  - Référence unique de transaction
- CA2.4 : Le champ `num_cheque` contient : `"HelloAsso: [transaction_id]"` pour traçabilité
- CA2.5 : La section (club) du pilote est correctement assignée
- CA2.6 : L'écriture est générée dans une transaction atomique avec mise à jour des soldes de comptes
- CA2.7 : En cas d'erreur lors de la création de l'écriture, le paiement est marqué comme "en attente de rapprochement manuel"

**Règles Métier :**
- L'écriture ne doit être créée qu'une seule fois (idempotence basée sur transaction_id)
- Le montant crédité est le montant brut payé par le pilote (avant commission de la plateforme)
- Une seconde écriture peut être créée pour comptabiliser les commissions si nécessaire

---

### 5.3 EF3 : Vérification du Paiement par le Pilote (Priorité : HAUTE)

**Description :** Permettre au pilote de vérifier que son paiement en ligne a été correctement pris en compte et apparaît dans son historique de compte.

**User Story :**
> En tant que pilote, je veux vérifier que mon paiement en ligne apparaît dans l'historique de mon compte, afin d'avoir la confirmation que mon compte a bien été crédité.

**Critères d'Acceptation :**
- CA3.1 : Page "Mon Compte" (`compta/mon_compte`) affiche :
  - Solde actuel du compte
  - Liste des mouvements récents (débits et crédits)
  - Indication visuelle pour les provisionnements en ligne (icône ou badge)
- CA3.2 : Les provisionnements en ligne sont clairement identifiables :
  - Description : "Provisionnement en ligne - HelloAsso - Réf: XXX"
  - Date et heure du paiement
  - Montant crédité
- CA3.3 : Vue détaillée de chaque mouvement disponible au clic
- CA3.4 : Filtre optionnel pour n'afficher que les provisionnements en ligne
- CA3.5 : Export PDF de l'historique de compte incluant les provisionnements en ligne

**Règles Métier :**
- L'affichage utilise la même logique que pour les autres écritures comptables
- Les provisionnements en ligne apparaissent immédiatement après confirmation du paiement
- Le solde est recalculé en temps réel incluant tous les mouvements

---

### 5.4 EF4 : Liste des Provisionnements par le Trésorier (Priorité : HAUTE)

**Description :** Fournir au trésorier une vue centralisée de tous les provisionnements en ligne effectués par les membres.

**User Story :**
> En tant que trésorier, je veux lister tous les provisionnements en ligne avec les détails des transactions, afin de vérifier la cohérence comptable et suivre l'activité de paiement en ligne.

**Critères d'Acceptation :**
- CA4.1 : Nouvelle page accessible : `paiements_en_ligne/liste` (réservée trésorier et admin)
- CA4.2 : Tableau affichant :
  - Date/heure du paiement
  - Nom du pilote
  - Montant payé
  - Plateforme utilisée (HelloAsso, Stripe, etc.)
  - Référence de transaction
  - Statut (réussi, en attente, échoué)
  - Lien vers l'écriture comptable générée
  - Commission prélevée (si disponible)
- CA4.3 : Filtres disponibles :
  - Période (date début - date fin)
  - Plateforme de paiement
  - Section/club
  - Statut
  - Pilote spécifique
- CA4.4 : Export CSV/Excel de la liste avec tous les détails
- CA4.5 : Export PDF pour archivage/audit
- CA4.6 : Statistiques récapitulatives :
  - Nombre de provisionnements du mois
  - Montant total provisionné
  - Commissions totales
  - Comparaison avec mois précédent

**Règles Métier :**
- Seuls les rôles `tresorier`, `bureau`, et `admin` ont accès à cette page
- Les données affichées respectent les filtres de section si activés
- L'export inclut toutes les informations pour rapprochement bancaire

---

### 5.5 EF5 : Configuration de la Plateforme de Paiement (Priorité : MOYENNE)

**Description :** Permettre aux administrateurs de configurer les paramètres d'intégration avec une ou plusieurs plateformes de paiement en ligne.

**User Story :**
> En tant qu'administrateur, je veux configurer les paramètres de connexion à la plateforme de paiement, afin d'activer le provisionnement en ligne pour mon club.

**Critères d'Acceptation :**
- CA5.1 : Page d'administration : `admin/paiements_en_ligne/config`
- CA5.2 : Configuration par plateforme :
  - **HelloAsso** (priorité 1) :
    - Clé API Client ID
    - Clé API Client Secret
    - Mode sandbox/production
    - ID de l'organisation HelloAsso
    - URL de webhook
  - **Autres plateformes** (optionnel futur) :
    - Stripe, Lydia, PayPal
- CA5.3 : Configuration du compte comptable :
  - Compte de passage par défaut (ex: 467)
  - Compte de commission (ex: 627 - Frais bancaires)
  - Libellé personnalisé pour les écritures
- CA5.4 : Paramètres généraux :
  - Montant minimum de provisionnement
  - Montant maximum par transaction
  - Activation/désactivation du module par section
- CA5.5 : Test de connexion disponible :
  - Bouton "Tester la connexion HelloAsso"
  - Affichage du résultat du test (succès/erreur avec détails)
- CA5.6 : Génération automatique de l'URL de webhook pour copie dans l'interface HelloAsso

**Règles Métier :**
- Seuls les administrateurs (`admin`) peuvent modifier la configuration
- Les clés API doivent être stockées de manière sécurisée (chiffrées en base de données)
- Un log d'audit enregistre tous les changements de configuration

---

## 6. Spécifications Techniques

### 6.1 Architecture d'Intégration

**Flow de Paiement (HelloAsso) :**

```
1. Pilote → GVV : Demande de provisionnement (montant)
2. GVV → HelloAsso : Création d'un checkout (montant, pilote_id, metadata)
3. HelloAsso → GVV : Retour URL de paiement sécurisé
4. GVV → Pilote : Redirection vers HelloAsso
5. Pilote → HelloAsso : Saisie des informations de carte
6. HelloAsso → Pilote : Paiement effectué
7. HelloAsso → GVV : Webhook de confirmation (transaction_id, montant, statut)
8. GVV : Vérification signature webhook
9. GVV : Création écriture comptable
10. GVV → Pilote : Redirection page de confirmation
```

**Composants Techniques :**

**Contrôleur : `application/controllers/paiements_en_ligne.php`**
- `index()` : Page d'accueil module paiement en ligne
- `demande($montant)` : Initiation d'une demande de provisionnement
- `helloasso_checkout()` : Création checkout HelloAsso et redirection
- `helloasso_webhook()` : Réception notifications HelloAsso
- `confirmation($transaction_id)` : Page de confirmation après paiement
- `annulation()` : Page en cas d'annulation du paiement
- `liste()` : Liste des provisionnements (trésorier)
- `admin_config()` : Configuration des plateformes (admin)

**Modèle : `application/models/paiements_en_ligne_model.php`**
- `create_transaction($user_id, $amount, $platform)` : Crée une transaction en attente
- `update_transaction_status($transaction_id, $status, $metadata)` : Met à jour le statut
- `get_transactions($filters)` : Liste les transactions avec filtres
- `get_transaction_by_id($id)` : Détails d'une transaction
- `get_pending_transactions()` : Transactions en attente de webhook

**Nouvelle Table : `paiements_en_ligne`**
```sql
CREATE TABLE `paiements_en_ligne` (
  `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
  `user_id` VARCHAR(50) NOT NULL,
  `montant` DECIMAL(10,2) NOT NULL,
  `plateforme` VARCHAR(50) NOT NULL, -- 'helloasso', 'stripe', etc.
  `transaction_id` VARCHAR(255) UNIQUE, -- ID de la plateforme externe
  `ecriture_id` BIGINT, -- Référence vers l'écriture comptable créée
  `statut` ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
  `date_demande` DATETIME NOT NULL,
  `date_paiement` DATETIME,
  `metadata` TEXT, -- JSON avec détails plateforme
  `commission` DECIMAL(10,2),
  `club` TINYINT NOT NULL,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_status` (`statut`),
  INDEX `idx_transaction` (`transaction_id`),
  INDEX `idx_date` (`date_paiement`),
  FOREIGN KEY (`ecriture_id`) REFERENCES `ecritures`(`id`) ON DELETE SET NULL
);
```

**Nouvelle Table de Configuration : `paiements_en_ligne_config`**
```sql
CREATE TABLE `paiements_en_ligne_config` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `plateforme` VARCHAR(50) NOT NULL,
  `param_key` VARCHAR(100) NOT NULL,
  `param_value` TEXT,
  `club` TINYINT,
  UNIQUE KEY `unique_config` (`plateforme`, `param_key`, `club`)
);
```

### 6.2 Intégration HelloAsso

**API HelloAsso v5 :**
- Base URL Production : `https://api.helloasso.com/v5`
- Base URL Sandbox : `https://api.helloasso-sandbox.com/v5`

**Authentification OAuth 2.0 :**
```php
// Obtention du token
POST /oauth2/token
Content-Type: application/x-www-form-urlencoded

client_id={CLIENT_ID}
&client_secret={CLIENT_SECRET}
&grant_type=client_credentials

// Réponse
{
  "access_token": "...",
  "token_type": "Bearer",
  "expires_in": 1800
}
```

**Création d'un Checkout (Formulaire de Paiement) :**
```php
POST /organizations/{organizationSlug}/forms/quickdonation/{formSlug}/checkout-intents
Authorization: Bearer {access_token}
Content-Type: application/json

{
  "totalAmount": 5000, // en centimes (50.00€)
  "initialAmount": 5000,
  "itemName": "Provisionnement compte pilote",
  "backUrl": "https://club.gvv.fr/paiements_en_ligne/confirmation",
  "errorUrl": "https://club.gvv.fr/paiements_en_ligne/erreur",
  "returnUrl": "https://club.gvv.fr/paiements_en_ligne/retour",
  "containsDonation": false,
  "payer": {
    "email": "pilote@example.com",
    "firstName": "Marc",
    "lastName": "Dupont"
  },
  "metadata": {
    "gvv_user_id": "123",
    "gvv_club": "2",
    "gvv_transaction_id": "TXN_20250109_001"
  }
}

// Réponse
{
  "id": 12345,
  "url": "https://www.helloasso.com/associations/...",
  "expirationDate": "2025-01-09T15:30:00"
}
```

**Webhook de Notification :**
HelloAsso envoie une notification POST à l'URL configurée :
```php
POST /paiements_en_ligne/helloasso_webhook
Content-Type: application/json
X-HelloAsso-Signature: {HMAC_SHA256_signature}

{
  "eventType": "Order",
  "data": {
    "order": {
      "id": 12345,
      "date": "2025-01-09T14:25:00",
      "amount": {
        "total": 5000,
        "vat": 0,
        "discount": 0
      },
      "payer": {
        "email": "pilote@example.com",
        "firstName": "Marc",
        "lastName": "Dupont"
      },
      "items": [...],
      "payments": [
        {
          "id": 67890,
          "date": "2025-01-09T14:25:30",
          "amount": 5000,
          "paymentMeans": "Card",
          "state": "Authorized"
        }
      ],
      "metadata": {
        "gvv_user_id": "123",
        "gvv_club": "2",
        "gvv_transaction_id": "TXN_20250109_001"
      }
    }
  }
}
```

**Vérification de la Signature :**
```php
function verify_helloasso_signature($payload, $signature, $secret) {
    $computed_signature = hash_hmac('sha256', $payload, $secret);
    return hash_equals($signature, $computed_signature);
}
```

### 6.3 Traitement du Webhook

**Algorithme de Traitement :**
```php
public function helloasso_webhook() {
    // 1. Récupérer le payload brut
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_HELLOASSO_SIGNATURE'] ?? '';

    // 2. Vérifier la signature
    $secret = $this->config->item('helloasso_webhook_secret');
    if (!$this->verify_signature($payload, $signature, $secret)) {
        log_message('error', 'Invalid HelloAsso webhook signature');
        return $this->output->set_status_header(401);
    }

    // 3. Décoder le JSON
    $data = json_decode($payload, true);

    // 4. Vérifier le type d'événement
    if ($data['eventType'] !== 'Order') {
        return $this->output->set_status_header(200); // OK mais non traité
    }

    // 5. Extraire les informations
    $order = $data['data']['order'];
    $transaction_id = $order['metadata']['gvv_transaction_id'];
    $amount = $order['amount']['total'] / 100; // Convertir centimes en euros
    $helloasso_order_id = $order['id'];

    // 6. Vérifier si déjà traité (idempotence)
    $existing = $this->paiements_en_ligne_model->get_by_transaction_id($transaction_id);
    if ($existing && $existing['statut'] === 'completed') {
        return $this->output->set_status_header(200); // Déjà traité
    }

    // 7. Vérifier le statut du paiement
    $payment_state = $order['payments'][0]['state'];
    if ($payment_state !== 'Authorized') {
        $this->paiements_en_ligne_model->update_status($transaction_id, 'failed');
        return $this->output->set_status_header(200);
    }

    // 8. Créer l'écriture comptable
    $this->db->trans_start();

    $user_id = $order['metadata']['gvv_user_id'];
    $club_id = $order['metadata']['gvv_club'];

    // Récupérer le compte pilote
    $pilot_account = $this->comptes_model->get_pilot_account($user_id, $club_id);

    // Compte de passage (configurable)
    $passage_account = $this->paiements_en_ligne_model->get_config('helloasso', 'compte_passage', $club_id);

    // Créer l'écriture
    $ecriture_data = [
        'annee_exercise' => date('Y'),
        'date_creation' => date('Y-m-d H:i:s'),
        'date_op' => $order['date'],
        'compte1' => $passage_account,
        'compte2' => $pilot_account,
        'montant' => $amount,
        'description' => "Provisionnement en ligne - HelloAsso - Réf: {$helloasso_order_id}",
        'num_cheque' => "HelloAsso: {$helloasso_order_id}",
        'saisie_par' => 'online_payment_system',
        'club' => $club_id
    ];

    $ecriture_id = $this->ecritures_model->create_ecriture($ecriture_data);

    // Mettre à jour la transaction
    $this->paiements_en_ligne_model->update_transaction([
        'transaction_id' => $transaction_id,
        'statut' => 'completed',
        'date_paiement' => $order['date'],
        'ecriture_id' => $ecriture_id,
        'metadata' => json_encode($order)
    ]);

    $this->db->trans_complete();

    // 9. Envoyer notification email au pilote (optionnel)
    $this->send_confirmation_email($user_id, $amount, $transaction_id);

    // 10. Retourner 200 OK
    return $this->output->set_status_header(200);
}
```

### 6.4 Sécurité

**Mesures de Sécurité :**
1. **Authentification** : Seuls les utilisateurs authentifiés peuvent demander un provisionnement
2. **Vérification de Signature** : Tous les webhooks doivent avoir une signature valide
3. **Idempotence** : Chaque transaction a un ID unique et ne peut être traitée qu'une fois
4. **HTTPS Obligatoire** : Toutes les communications avec les API de paiement en HTTPS
5. **Stockage Sécurisé** : Clés API chiffrées dans la base de données
6. **Logs d'Audit** : Toutes les transactions enregistrées pour traçabilité
7. **Validation des Montants** : Montants min/max configurables
8. **Protection CSRF** : Tokens CSRF sur tous les formulaires
9. **Limitation de Taux** : Limite du nombre de tentatives de paiement par utilisateur (ex: 5/heure)
10. **Conformité PCI-DSS** : Aucune donnée de carte stockée côté GVV (gérées par HelloAsso)

---

## 7. Exigences Non Fonctionnelles

### 7.1 Performance

- Génération d'écriture comptable en < 2 secondes après réception du webhook
- Affichage de la liste des provisionnements en < 3 secondes pour 1000 transactions
- Temps de redirection vers HelloAsso < 1 seconde
- Traitement webhook asynchrone (ne bloque pas l'utilisateur)

### 7.2 Disponibilité

- Module de paiement disponible 24/7 (99.5% uptime)
- Gestion des pannes de la plateforme de paiement :
  - Message d'erreur clair si HelloAsso indisponible
  - Retry automatique des webhooks échoués (3 tentatives avec backoff exponentiel)
- Mode dégradé : paiement temporairement désactivé si problème technique

### 7.3 Fiabilité

- Transactions atomiques pour création d'écriture comptable
- Rollback automatique en cas d'erreur
- Queue de retry pour webhooks échoués
- Aucune perte de transaction (tous les paiements réussis doivent générer une écriture)
- Rapprochement quotidien automatique entre HelloAsso et GVV

### 7.4 Compatibilité

- Fonctionne avec PHP 7.4
- Compatible avec CodeIgniter 2.x
- Pas de modification du schéma de table `ecritures` existant
- Compatible avec le système de sections/clubs existant
- Support des navigateurs modernes (Chrome, Firefox, Safari, Edge)
- Interface responsive (mobile, tablette, desktop)

### 7.5 Maintenabilité

- Code bien documenté avec PHPDoc
- Tests unitaires pour la logique métier critique
- Tests d'intégration pour le flow de paiement complet
- Configuration centralisée et facile à modifier
- Logs détaillés pour debugging

### 7.6 Conformité

- **RGPD** :
  - Données personnelles minimales envoyées à HelloAsso
  - Consentement explicite avant paiement
  - Droit d'accès et de suppression des données de transaction
- **Comptabilité** :
  - Respect du plan comptable associatif français
  - Traçabilité complète pour audit
  - Numérotation séquentielle des écritures

---

## 8. Plateformes de Paiement Supportées

### 8.1 Priorité 1 : HelloAsso ⭐

**Pourquoi HelloAsso ?**
- **Gratuit pour les associations** : 0% de commission (financement par contribution volontaire des donateurs)
- **Conçu pour les associations françaises** : Comprend les besoins spécifiques
- **Simplicité d'utilisation** : Interface claire et support client
- **API complète** : Documentation v5 bien fournie
- **Confiance** : Utilisé par 150 000+ associations en France
- **Powered by Stripe** : Infrastructure de paiement sécurisée

**Fonctionnalités HelloAsso :**
- Paiement par carte bancaire (Visa, Mastercard, Amex)
- Webhooks en temps réel
- Mode sandbox pour tests
- Back-office pour l'association
- Rapprochement bancaire facilité

**Coûts :**
- **0% de frais** pour l'association (modèle unique en France)
- Contribution volontaire demandée au payeur (peut être désactivée)

### 8.2 Priorité 2 : Stripe (Futur)

**Avantages :**
- API robuste et bien documentée
- Support de nombreux moyens de paiement (carte, SEPA, wallets)
- Webhooks fiables
- Dashboard complet

**Inconvénients :**
- Frais : 1.4% + 0.25€ par transaction (cartes européennes)
- Plus complexe que HelloAsso pour associations

**Implémentation :**
- Phase 2 du projet
- Alternative si HelloAsso ne convient pas à certains clubs

### 8.3 Autres Plateformes (Suggestions)

**Lydia :**
- Populaire en France pour paiements entre particuliers
- Frais : ~1% pour associations
- API disponible mais moins documentée

**PayPal :**
- Connu internationalement
- Frais : 1.8% + 0.35€ (tarif associations)
- Interface utilisateur lourde

**Wero :**
- Nouvelle solution européenne (lancée 2024)
- Alternative à PayPal et Lydia
- À suivre pour le futur

**Recommandation :** Commencer avec HelloAsso exclusivement, puis évaluer Stripe en Phase 2 si besoin.

---

## 9. Métriques de Succès

| Métrique | Baseline | Cible 6 mois | Comment Mesurer |
|----------|----------|--------------|-----------------|
| % de provisionnements en ligne vs manuel | 0% | >50% | Ratio (paiements en ligne) / (total provisionnements) |
| Temps moyen de saisie trésorier | 5 min/provisionnement | 0 min | Temps économisé * nb provisionnements |
| Délai de crédit du compte | 1-3 jours | < 5 minutes | Temps entre paiement et crédit visible |
| Satisfaction pilotes | N/A | >80% satisfaits | Enquête de satisfaction |
| Satisfaction trésoriers | N/A | >90% satisfaits | Feedback trésoriers |
| Taux d'erreur de paiement | N/A | <5% | (Paiements échoués) / (Tentatives) |
| Montant moyen provisionné | Variable | Suivi | Moyenne des montants en ligne |
| Nombre de provisionnements/mois | Baseline actuel | +50% | Volume mensuel |

---

## 10. Risques et Atténuations

| Risque | Impact | Probabilité | Atténuation |
|--------|--------|-------------|-------------|
| Webhook HelloAsso non reçu | ÉLEVÉ | FAIBLE | Queue de retry + rapprochement quotidien automatique |
| Double facturation (webhook reçu 2x) | ÉLEVÉ | MOYEN | Vérification idempotence basée sur transaction_id unique |
| Panne de HelloAsso | MOYEN | FAIBLE | Message d'erreur clair + mode dégradé + retry automatique |
| Erreur lors de création écriture comptable | ÉLEVÉ | FAIBLE | Transaction atomique + logs détaillés + alerte admin |
| Fraude/paiement contesté | MOYEN | TRÈS FAIBLE | Validation d'identité + logs d'audit + gestion des chargebacks |
| Commission HelloAsso changée | FAIBLE | FAIBLE | Suivi des conditions HelloAsso + notification des changements |
| Incompatibilité PHP 7.4 avec API | MOYEN | FAIBLE | Tests complets + librairie HTTP compatible (cURL) |
| Dépassement limite API HelloAsso | FAIBLE | FAIBLE | Throttling côté GVV + monitoring des quotas |
| Données personnelles non conformes RGPD | ÉLEVÉ | FAIBLE | Audit RGPD + minimisation des données + consentement explicite |

---

## 11. Dépendances et Prérequis

### 11.1 Prérequis Techniques

**PHP :**
- PHP 7.4 avec extensions : `curl`, `json`, `openssl`
- Fonction `hash_hmac` disponible pour vérification signature

**Base de Données :**
- Création de 2 nouvelles tables (`paiements_en_ligne`, `paiements_en_ligne_config`)
- Migration CodeIgniter à créer

**Serveur Web :**
- HTTPS obligatoire (certificat SSL valide)
- Webhook accessible depuis Internet (pas de firewall bloquant HelloAsso)
- URL publique stable pour webhooks

**Commandes de Vérification :**
```bash
# Vérifier extensions PHP
php7.4 -m | grep -E 'curl|json|openssl'

# Vérifier OpenSSL pour HTTPS
openssl version

# Tester accessibilité webhook (depuis un serveur externe)
curl -X POST https://votre-club.gvv.fr/paiements_en_ligne/helloasso_webhook
```

### 11.2 Prérequis HelloAsso

**Compte HelloAsso :**
- Association inscrite sur HelloAsso
- Compte validé et actif
- Formulaire de type "Don" ou "Paiement" créé

**Clés API :**
- Client ID et Client Secret générés depuis le dashboard HelloAsso
- URL de webhook configurée dans HelloAsso pointant vers GVV

**Configuration Webhook :**
- Dans HelloAsso : Paramètres → API → Webhooks
- Ajouter URL : `https://votre-club.gvv.fr/paiements_en_ligne/helloasso_webhook`
- Activer événements : `Order`

### 11.3 Prérequis Comptables

**Plan Comptable :**
- Compte 411 : Comptes pilotes (existant)
- Compte 467 : Autres comptes créditeurs ou débiteurs (compte de passage)
- Compte 627 : Frais bancaires (optionnel, pour comptabiliser les commissions)

**Permissions :**
- Rôle `tresorier` et `admin` peuvent accéder à la liste des provisionnements
- Tous les membres authentifiés peuvent provisionner leur propre compte

### 11.4 Migration

**Migration Base de Données :**
```php
// application/migrations/XXX_paiements_en_ligne.php

class Migration_Paiements_en_ligne extends CI_Migration {
    public function up() {
        // Table paiements_en_ligne
        $this->dbforge->add_field([
            'id' => ['type' => 'BIGINT', 'auto_increment' => TRUE],
            'user_id' => ['type' => 'VARCHAR', 'constraint' => 50],
            'montant' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'plateforme' => ['type' => 'VARCHAR', 'constraint' => 50],
            'transaction_id' => ['type' => 'VARCHAR', 'constraint' => 255],
            'ecriture_id' => ['type' => 'BIGINT', 'null' => TRUE],
            'statut' => ['type' => 'ENUM', 'constraint' => ['pending', 'completed', 'failed', 'cancelled'], 'default' => 'pending'],
            'date_demande' => ['type' => 'DATETIME'],
            'date_paiement' => ['type' => 'DATETIME', 'null' => TRUE],
            'metadata' => ['type' => 'TEXT', 'null' => TRUE],
            'commission' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => TRUE],
            'club' => ['type' => 'TINYINT']
        ]);
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('user_id');
        $this->dbforge->add_key('transaction_id');
        $this->dbforge->create_table('paiements_en_ligne');

        // Table configuration
        $this->dbforge->add_field([
            'id' => ['type' => 'INT', 'auto_increment' => TRUE],
            'plateforme' => ['type' => 'VARCHAR', 'constraint' => 50],
            'param_key' => ['type' => 'VARCHAR', 'constraint' => 100],
            'param_value' => ['type' => 'TEXT', 'null' => TRUE],
            'club' => ['type' => 'TINYINT', 'null' => TRUE]
        ]);
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('paiements_en_ligne_config');

        // Mise à jour config/migration.php requis
    }

    public function down() {
        $this->dbforge->drop_table('paiements_en_ligne');
        $this->dbforge->drop_table('paiements_en_ligne_config');
    }
}
```

---

## 12. Hors Périmètre

Les fonctionnalités suivantes sont explicitement hors périmètre pour cette version initiale :

1. **Paiement des factures de vol** : Ce PRD concerne uniquement le provisionnement, pas le paiement direct de factures
2. **Prélèvement automatique récurrent** : Pas de souscription ou abonnement
3. **Paiement en plusieurs fois** : Un provisionnement = un paiement unique
4. **Remboursements automatiques** : Les remboursements doivent être gérés manuellement par le trésorier
5. **Gestion des litiges** : Les chargebacks et contestations sont gérés via HelloAsso
6. **Wallet/porte-monnaie virtuel** : Le compte pilote n'est pas un porte-monnaie rechargeable, c'est un compte comptable
7. **Paiement d'autres prestations** : Stages, remorqués, baptêmes (à traiter dans un PRD séparé)
8. **Multi-devises** : Support de l'euro uniquement
9. **Application mobile native** : Interface web responsive uniquement
10. **Paiement anonyme** : L'utilisateur doit être authentifié dans GVV

---

## 13. Plan de Déploiement

### Phase 1 : Développement et Tests (6-8 semaines)

**Semaine 1-2 : Setup et Configuration**
- Migration base de données
- Création des modèles et contrôleurs de base
- Configuration HelloAsso en mode sandbox
- Tests de connexion API

**Semaine 3-4 : Flow de Paiement**
- Implémentation création checkout HelloAsso
- Gestion de la redirection
- Traitement des webhooks
- Génération d'écritures comptables

**Semaine 5-6 : Interfaces Utilisateur**
- Page de demande de provisionnement (pilote)
- Page "Mon Compte" avec historique
- Liste des provisionnements (trésorier)
- Page de configuration (admin)

**Semaine 7-8 : Tests et Corrections**
- Tests unitaires (couverture >70%)
- Tests d'intégration avec HelloAsso sandbox
- Tests de sécurité (signatures, CSRF, etc.)
- Corrections de bugs

### Phase 2 : Pilote (2-4 semaines)

**Club Pilote :**
- Sélection d'un club volontaire (20-50 membres)
- Formation du trésorier
- Déploiement en production
- Monitoring intensif
- Collecte de feedback utilisateurs

**Critères de Succès Phase Pilote :**
- 0 perte de transaction
- >80% satisfaction utilisateurs
- <5% taux d'erreur
- Génération correcte de 100% des écritures comptables

### Phase 3 : Déploiement Général (4 semaines)

**Déploiement Progressif :**
- Semaine 1 : 2-3 clubs supplémentaires
- Semaine 2-3 : Tous les clubs volontaires
- Semaine 4 : Documentation et support

**Support :**
- Documentation utilisateur (pilotes et trésoriers)
- Vidéos tutoriels
- FAQ
- Support technique dédié

---

## 14. Documentation Requise

### 14.1 Documentation Technique

- Guide d'intégration HelloAsso API
- Spécifications des webhooks
- Schéma de base de données
- Diagrammes de séquence (flow de paiement)
- Documentation du code (PHPDoc)

### 14.2 Documentation Utilisateur

**Pour les Pilotes :**
- Comment provisionner mon compte en ligne ?
- Quels moyens de paiement sont acceptés ?
- Combien de temps pour voir le crédit ?
- Que faire en cas de problème ?

**Pour les Trésoriers :**
- Comment activer les paiements en ligne ?
- Comment consulter les provisionnements ?
- Comment faire le rapprochement bancaire ?
- Gestion des erreurs et des retours

**Pour les Administrateurs :**
- Configuration initiale HelloAsso
- Création des clés API
- Configuration des webhooks
- Paramétrage des comptes comptables
- Monitoring et logs

---

## 15. Questions Ouvertes

1. **Q :** Faut-il permettre aux membres de provisionner le compte d'un autre membre (ex: parent pour enfant) ?
   **R :** À décider - Suggestion : Phase 2, nécessite validation d'autorisation

2. **Q :** Faut-il envoyer un email de confirmation systématique après chaque provisionnement ?
   **R :** Oui recommandé - Option configurable par utilisateur

3. **Q :** Comment gérer les commissions HelloAsso si un club active la contribution volontaire ?
   **R :** Ne pas comptabiliser la contribution volontaire dans GVV (elle va à HelloAsso)

4. **Q :** Faut-il limiter le nombre de provisionnements par jour/semaine ?
   **R :** Oui recommandé - 5 transactions maximum par jour pour éviter abus

5. **Q :** Comment gérer un remboursement demandé par un pilote ?
   **R :** Processus manuel géré par le trésorier (hors scope automatisation)

6. **Q :** Faut-il créer une écriture comptable distincte pour les commissions ?
   **R :** Optionnel Phase 2 - Si HelloAsso prélève des frais, créer une 2ème écriture

7. **Q :** Doit-on garder un historique des tentatives de paiement échouées ?
   **R :** Oui - Utile pour debugging et statistiques

---

## 16. Documents Associés

- **Plan d'Implémentation** : `doc/plans/paiements_en_ligne_plan.md` (à créer)
- **Documentation API HelloAsso** : https://dev.helloasso.com/
- **Documentation Système Comptable GVV** : `doc/comptabilite.md`
- **Workflow de Développement** : `doc/development/workflow.md`
- **Contexte Projet** : `CLAUDE.md`, `.github/copilot-instructions.md`

---

## 17. Approbation et Validation

| Rôle | Nom | Signature | Date |
|------|------|-----------|------|
| Product Owner | [À déterminer] | | |
| Trésorier (Représentant Utilisateur) | [À déterminer] | | |
| Pilote (Représentant Utilisateur) | [À déterminer] | | |
| Développeur Lead | [À déterminer] | | |
| Administrateur Système | [À déterminer] | | |

---

**Fin du PRD**

**Version 1.0 - Janvier 2025**
