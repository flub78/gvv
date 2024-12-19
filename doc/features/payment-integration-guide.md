# Intégration de Paiement pour Application PHP Legacy

## Contexte
GVV nécessite une solution de paiement en ligne simple, sans utilisation de Composer.

## Approches Possibles

### 1. Stripe - Téléchargement Manuel

#### Méthode d'Intégration Sans Composer

1. **Téléchargement Direct**
   - Télécharger le fichier `stripe-php.zip` depuis le site officiel
   - Extraire et copier dans un dossier du projet
   - Inclure manuellement les fichiers nécessaires

2. **Exemple d'Intégration**
```php
<?php
// Inclusion manuelle
require_once('stripe-php/init.php');

\Stripe\Stripe::setApiKey('your_stripe_secret_key');

try {
    $charge = \Stripe\Charge::create([
        'amount' => 2000,  // Montant en centimes
        'currency' => 'eur',
        'source' => $token,
        'description' => 'Approvisionnement Compte Vol'
    ]);
} catch(\Stripe\Exception\CardException $e) {
    // Gestion des erreurs de paiement
}
```

### 2. PayPal REST API - Solution Manuelle

#### Avantages
- Téléchargement direct des fichiers SDK
- Pas de gestionnaire de dépendances requis
- Large documentation
- Intégration par fichiers PHP traditionnels

#### Étapes d'Intégration

1. **Téléchargement Manuel du SDK**
   - Récupérer le SDK PayPal PHP depuis GitHub
   - Télécharger manuellement les fichiers
   - Intégrer directement dans le projet

2. **Configuration Basique**
```php
<?php
// Inclusion manuelle des fichiers
require_once('paypal-sdk/PayPal.php');

$apiContext = new \PayPal\Rest\ApiContext(
    new \PayPal\Auth\OAuthTokenCredential(
        'CLIENT_ID',     // Votre identifiant client
        'CLIENT_SECRET'  // Votre secret client
    )
);

// Configuration de la transaction
$paiement = new \PayPal\Api\Payment();
$paiement->setIntent('sale');
// Reste de la configuration...
```

### 3. Mollie, solutions Open Source ou Légères

#### Avantages
* Supporte une large gamme de moyens de paiement, frais similaires à Stripe.

#### Étapes d'Intégration
1. Téléchargez la bibliothèque PHP sans utiliser Composer.
2. Configurez les paiements directement dans votre application.

### 4. Solution Bancaire Directe
#### Options Recommandées
- Systempay (Crédit Agricole)
- Paybox (Groupe BPCE)
- Solutions avec SDK téléchargeables manuellement

## Exemple d'Intégration Simplifiée avec Stripe

### Étape 1 : Ajouter le fichier PHP de Stripe
Téléchargez [stripe-php](https://github.com/stripe/stripe-php/releases) et incluez-le dans votre projet.

### Étape 2 : Créer un Contrôleur pour le Paiement
```php
class PaymentController extends BaseController {
    public function charge() {
        require_once APPPATH . 'ThirdParty/stripe/init.php';

        \Stripe\Stripe::setApiKey('your_secret_key');

        try {
            $charge = \Stripe\Charge::create([
                'amount' => 1000, // Montant en centimes
                'currency' => 'eur',
                'source' => $this->request->getPost('stripeToken'),
                'description' => 'Recharge compte de vol',
            ]);

            // Mettre à jour le compte de vol ici
            return redirect()->to('/success');
        } catch (\Stripe\Exception\CardException $e) {
            return redirect()->to('/error');
        }
    }
}
```

### Étape 3 : Ajouter un Formulaire de Paiement
```html
<form action="/payment/charge" method="post">
    <script
        src="https://checkout.stripe.com/checkout.js" class="stripe-button"
        data-key="your_publishable_key"
        data-amount="1000"
        data-name="Association Vols"
        data-description="Recharge compte de vol"
        data-currency="eur">
    </script>
</form>
```

## Recommandations Techniques

1. **Choix Prioritaire** : PayPal ou Stripe avec intégration manuelle
2. **Prérequis**
   - Téléchargement manuel des SDK
   - Gestion traditionnelle des fichiers
   - Pas de dépendances externes complexes
   
- Toujours utiliser le mode test
- Implémenter des logs détaillés
- Prévoir une sauvegarde des fichiers SDK
- Vérifier manuellement les mises à jour

### Points Critiques
- Sécurisation des transactions
- Gestion manuelle des mises à jour du SDK
- Vérification régulière des versions
- Compatibilité PHP 7.x

### Étapes Détaillées
1. Choisir un prestataire de paiement
2. Télécharger manuellement le SDK
3. Configurer les identifiants
4. Implémenter la logique de paiement
5. Tester en mode sandbox
6. Migrer en production

## Estimation
- Temps de développement : 3-5 jours
- Coût d'intégration : Minimal
- Frais de transaction : ~1.4% + 0.25€




