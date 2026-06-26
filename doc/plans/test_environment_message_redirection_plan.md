# Plan : Redirection des messages en environnement de test

## Objectif

Empêcher l'environnement de test d'envoyer des emails et SMS aux vrais utilisateurs. Quand `test_email` et/ou `test_phone` sont définis dans `program.php`, tout message sortant est redirigé vers ces adresses de test, avec annotation du destinataire original dans le sujet.

## Périmètre

| Flux | Fichier | Méthode |
|------|---------|---------|
| Rappels de réservation — email | `libraries/Reservation_reminder.php` | `_send_email()` |
| Rappels de réservation — SMS | `libraries/Reservation_reminder.php` | `_dispatch()` |
| Bon de vol de découverte — email | `controllers/vols_decouverte.php` | `send_email_with_pdf()` |
| Lien de paiement HelloAsso — email | `controllers/paiements_en_ligne.php` | `send_payment_link_email()` |

**Hors périmètre** : `admin/test_email` — outil de test manuel avec destinataire explicite, ne doit pas être intercepté.

## Architecture

Deux fonctions helpers dans `application/helpers/email_helper.php` centralisent la logique de redirection :

- `test_intercept_email($to_email, &$subject)` — retourne l'adresse de destination effective et modifie le sujet pour indiquer le destinataire original
- `test_intercept_phone($phone)` — retourne le numéro effectif

Chaque point d'envoi appelle ces helpers avant de passer l'adresse à la librairie CI Email ou à Brevo.

### Comportement des helpers

- Si `test_email` / `test_phone` est vide ou absent : comportement inchangé, retourne la valeur originale
- Si défini : remplace le destinataire et préfixe le sujet avec `[TEST → original@address]`
- Écrit une entrée dans les logs GVV (`gvv_info`)

## Étapes

### [ ] Étape 1 — Configuration

Fichiers : `application/config/program.php` et `application/config/program.example.php`

Ajouter dans `program.php` (environnement de test) :
```php
$config['test_email'] = 'frederic.peignot@free.fr';
$config['test_phone'] = '+33600000000';
```

Ajouter dans `program.example.php` (production, vides par défaut) :
```php
// Laisser vide en production. Si défini, tous les emails/SMS sont redirigés vers ces adresses.
$config['test_email'] = '';
$config['test_phone'] = '';
```

### [ ] Étape 2 — Helpers de redirection

Fichier : `application/helpers/email_helper.php`

Ajouter en fin de fichier les deux fonctions :

```php
function test_intercept_email($to_email, &$subject) {
    $CI =& get_instance();
    $test_email = $CI->config->item('test_email');
    if (empty($test_email)) {
        return $to_email;
    }
    $subject = '[TEST → ' . $to_email . '] ' . $subject;
    gvv_info("TEST INTERCEPT email: $to_email → $test_email");
    return $test_email;
}

function test_intercept_phone($phone) {
    $CI =& get_instance();
    $test_phone = $CI->config->item('test_phone');
    if (empty($test_phone)) {
        return $phone;
    }
    gvv_info("TEST INTERCEPT SMS: $phone → $test_phone");
    return $test_phone;
}
```

### [ ] Étape 3 — Rappels de réservation (email)

Fichier : `application/libraries/Reservation_reminder.php`, méthode `_send_email()` (ligne ~556)

Avant `$this->CI->email->to($to_email)`, appeler :
```php
$to_email = test_intercept_email($to_email, $subject);
```

Le helper étant chargé via `$this->CI->load->helper('email')` dans `_load_dependencies()` (déjà présent).

### [ ] Étape 4 — Rappels de réservation (SMS)

Fichier : `application/libraries/Reservation_reminder.php`, méthode `_dispatch()` (ligne ~370)

Avant l'appel `$this->CI->brevo_sms_adapter->send($recipient['phone'], $sms_body)`, intercepter :
```php
$phone = test_intercept_phone($recipient['phone']);
$sms_res = $this->CI->brevo_sms_adapter->send($phone, $sms_body);
```

### [ ] Étape 5 — Bon de vol de découverte

Fichier : `application/controllers/vols_decouverte.php`, méthode `send_email_with_pdf()` (ligne ~729)

Après la construction du sujet, avant `$this->email->to(...)` :
```php
$subject = 'Votre bon de vol de découverte';
$to = test_intercept_email($vd['beneficiaire_email'], $subject);
$this->email->to($to);
$this->email->subject($subject);
```

Le helper `email` doit être chargé en tête de méthode si ce n'est pas déjà le cas dans le constructeur.

### [ ] Étape 6 — Lien de paiement HelloAsso

Fichier : `application/controllers/paiements_en_ligne.php`, méthode `send_payment_link_email()` (ligne ~336)

Avant `$this->email->to($to)`, intercepter :
```php
$to = test_intercept_email($to, $subject);
```

## Vérification

Après implémentation, vérifier manuellement sur l'environnement de test :

1. Déclencher un rappel de réservation via `/reservations/trigger_scheduler` ou le cron
2. Vérifier que l'email arrive sur `test_email` avec le sujet préfixé `[TEST → ...]`
3. Envoyer un bon VD depuis `vols_decouverte/email_vd/{id}`
4. Vérifier la redirection dans les logs GVV (`application/logs/`)
5. Vérifier que `admin/test_email` envoie toujours au destinataire saisi manuellement (non intercepté)
