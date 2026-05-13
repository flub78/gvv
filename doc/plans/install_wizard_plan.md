# Plan — Assistant d'installation GVV

## Objectif
Remplacer `install/index.php` et `install/reset.php` par un assistant d'installation Bootstrap multi-étapes, complet et autonome (aucune commande manuelle requise).

## Architecture

Un seul fichier `install/index.php` (PHP pur, sans CodeIgniter).  
État persisté en **session PHP** entre les étapes.  
`install/reset.php` refondu avec Bootstrap.

## Étapes de l'assistant

| # | Titre | Fichiers touchés |
|---|-------|-----------------|
| 1 | Prérequis | — |
| 2 | Base de données | `database.php` |
| 3 | URL de l'application | `config.php` |
| 4 | Informations du club | `club.php` |
| 5 | Authentification | `dx_auth.php` |
| 6 | Fonctionnalités | `program.php` |
| 7 | Google (optionnel) | `google.php` |
| 8 | Email Brevo (optionnel) | `email.php` |
| 9 | Initialisation de la base | `gvv_init.sql` |
| 10 | Répertoires & droits | — |
| 11 | Terminé | — |

Navigation : boutons **Précédent / Suivant**, indicateur de progression Bootstrap.

## Détail des étapes

### Étape 1 — Prérequis
- Version PHP ≥ 7.4
- Extensions : `mysqli`, `json`, `mbstring`, `gd`
- Droits en écriture sur `application/config/`
- Affichage badge vert/rouge par item
- Bloque si prérequis critiques manquants

### Étape 2 — Base de données
Champs : `hostname`, `username`, `password`, `database`  
Bouton **Tester la connexion** (AJAX ou rechargement)  
Pré-rempli depuis `database.php` si existant, sinon depuis `database.example.php`  
Validation → copie `database.example.php` → `database.php`, injecte les valeurs

### Étape 3 — URL de l'application
Champs : `base_url` (auto-détecté depuis `$_SERVER`), `language` (select fr/en/nl), `index_page`  
Pré-rempli depuis `config.php` si existant  
Validation → copie `config.example.php` → `config.php`, injecte `base_url` simple (sans le bloc auto-detect)

### Étape 4 — Informations du club
Champs : `sigle_club`, `email_club`, `url_club`, `copie_a`, `banner_color`, `ffvv_product`  
`mod` (message du jour) en textarea  
Pré-rempli depuis `club.php` si existant  
Validation → copie/met à jour `club.php`

### Étape 5 — Authentification
Champs : `DX_website_name`, `DX_webmaster_email`  
Pré-rempli depuis `dx_auth.php` si existant  
Validation → copie/met à jour `dx_auth.php`

### Étape 6 — Fonctionnalités
Champs : `program_title`, checkboxes pour les feature flags (`gestion_tickets`, `gestion_pompes`, `gestion_vd`, `gestion_of`, `gestion_formations`, `gestion_paiements`, `gestion_reservations`, `gestion_documentaire`)  
Pré-rempli depuis `program.php` si existant  
Validation → copie/met à jour `program.php`

### Étape 7 — Google (optionnel)
Champs : `client_id`, `client_secret`, `api_key`  
Bouton "Passer cette étape"  
Validation → copie/met à jour `google.php`

### Étape 8 — Email Brevo (optionnel)
Champs : `smtp_user`, `smtp_pass`  
Bouton "Passer cette étape" — à utiliser sur hébergement mutualisé (qui utilise `mail()` PHP)  
Validation → copie `email.example.php` → `email.php`, injecte les identifiants SMTP Brevo

### Étape 9 — Initialisation de la base
- Connexion avec les params de l'étape 2
- Compte les tables existantes
- Si < 22 tables : importe `gvv_init.sql`
- Si tables existantes : affiche avertissement + case à cocher pour forcer la réinitialisation
- Log des tables créées

### Étape 10 — Répertoires & droits
Pour chaque répertoire requis :
- Vérifie l'existence → crée si absent (avec `mkdir`)
- Vérifie les droits en écriture → `chmod` si nécessaire
- Affiche statut badge vert/orange/rouge

Répertoires vérifiés :
```
uploads/
uploads/restore/
uploads/attachments/
uploads/configuration/
uploads/documents/
uploads/formation/
uploads/email_lists/
assets/images/
application/logs/
captcha/
```

### Étape 11 — Terminé
- Résumé des fichiers créés/modifiés
- Lien vers l'application
- Rappel : changer les mots de passe des utilisateurs de test

## Fonctions utilitaires communes

```php
read_config_value($file, $key)   // lit une valeur depuis un .php de config
write_config_value($file, $key, $value)  // remplace une valeur (regex)
copy_example_if_missing($example, $dest) // copie si dest n'existe pas
ensure_dir($path, $mode=0775)    // crée le répertoire si absent
```

## Modification des fichiers de config
Regex : `$config\['KEY'\]\s*=\s*[^;]+;` → remplacé par `$config['KEY'] = 'VALUE';`  
Les blocs complexes (arrays, auto-detect base_url) sont remplacés par des valeurs simples pendant l'installation ; l'admin peut affiner ensuite.

## reset.php
- Refonte Bootstrap (même charte que l'assistant)
- Confirmation obligatoire (saisie du mot "RESET")
- Supprime toutes les tables, propose de relancer l'installation

## Statut des tâches

- [x] Étape 1 : Prérequis
- [x] Étape 2 : Base de données
- [x] Étape 3 : URL
- [x] Étape 4 : Club
- [x] Étape 5 : Auth
- [x] Étape 6 : Fonctionnalités
- [x] Étape 7 : Google
- [x] Étape 8 : Email Brevo
- [x] Étape 9 : Base de données init
- [x] Étape 10 : Répertoires
- [x] Étape 11 : Terminé
- [x] reset.php Bootstrap
