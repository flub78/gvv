# Debug Instructions - Email Lists Creation Issue

## État actuel

- **Code vérifié**: Controller, Model, Vue - Tout semble correct
- **Test base de données**: Insertion manuelle fonctionne (liste ID 2 et 3 créées)
- **Mode développement**: Activé dans `index.php`
- **Logs**: Activés (log_threshold = 4)

## Modifications apportées pour le debug

### 1. Controller (`application/controllers/email_lists.php`)

Ajout de logs de débogage dans:
- `create()` ligne 86 et 94
- `store()` ligne 116-117

### 2. Vue (`application/views/email_lists/form.php`)

Ajout de commentaires HTML de debug (lignes 59-66) qui affichent:
- L'URL d'action du formulaire
- Les variables controller, action, is_modification

## Instructions de test manuel

### 1. Accéder à la page de création

```
http://gvv.net/index.php/email_lists/create
```

### 2. Vérifier le code source HTML

Faire "Afficher le code source" et chercher les commentaires DEBUG:
```html
<!-- DEBUG: Form action URL: ... -->
<!-- DEBUG: controller=..., action=..., is_modification=... -->
```

**Attendu:**
- Form action URL: `http://gvv.net/index.php/email_lists/store` (ou similaire)
- controller: `email_lists`
- action: `store`
- is_modification: `NO`

### 3. Remplir le formulaire

- Nom: "Test Debug 2025"
- Description: "Test de débogage"
- Type de membre: Actifs
- Visible: coché

### 4. Cliquer sur "Enregistrer"

Observer:
- Y a-t-il un rechargement de page?
- L'URL change-t-elle?
- Y a-t-il un message d'erreur ou de succès?

### 5. Vérifier les logs CodeIgniter

```bash
tail -100 /home/frederic/git/gvv/application/logs/log-$(date +%Y-%m-%d).php
```

**Logs attendus si le formulaire se soumet:**
```
DEBUG - EMAIL_LISTS: create() method called
DEBUG - EMAIL_LISTS: Data for view - controller: email_lists, action: store
DEBUG - EMAIL_LISTS: store() method called
DEBUG - EMAIL_LISTS: POST data: Array...
```

**Si `store()` n'apparaît PAS dans les logs:**
→ Le formulaire ne se soumet pas (problème côté client/navigateur)

**Si `store()` apparaît dans les logs:**
→ Le formulaire se soumet mais il y a un problème dans le traitement

### 6. Vérifier la console du navigateur

Ouvrir les outils de développement (F12):
- Onglet "Console": Y a-t-il des erreurs JavaScript?
- Onglet "Réseau": Voir la requête POST quand on clique sur Enregistrer
  - Y a-t-il une requête POST vers `/email_lists/store`?
  - Quel est le code de statut HTTP (200, 302, 404, 500)?
  - Quelle est la réponse du serveur?

### 7. Vérifier la base de données

```bash
mysql -u gvv_user -plfoyfgbj gvv2 -e "SELECT * FROM email_lists ORDER BY created_at DESC LIMIT 3;"
```

Voir si une nouvelle ligne a été créée.

## Scénarios possibles

### Scénario A: Aucun log, aucune requête réseau
→ **Problème**: Le formulaire ne se soumet pas du tout
→ **Cause possible**: JavaScript bloque, attribut `action` incorrect, bouton submit hors formulaire

### Scénario B: Logs présents, mais erreur de validation
→ **Problème**: Validation échoue
→ **Cause possible**: Champs manquants, CSRF token, règles de validation trop strictes

### Scénario C: Logs présents, pas d'erreur, mais pas de création
→ **Problème**: `create_list()` échoue silencieusement
→ **Cause possible**: user_id null, foreign key, erreur SQL

### Scénario D: Logs présents, création réussie, mais pas de redirection
→ **Problème**: `redirect()` échoue
→ **Cause possible**: Headers déjà envoyés, erreur dans edit()

## Commandes utiles

```bash
# Voir les derniers logs
tail -50 /home/frederic/git/gvv/application/logs/log-$(date +%Y-%m-%d).php

# Voir les erreurs Apache
tail -50 /var/log/apache2/error.log

# Voir les listes créées
mysql -u gvv_user -plfoyfgbj gvv2 -e "SELECT * FROM email_lists;"

# Tester une requête POST avec curl
curl -v -X POST \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "name=Test+Curl&description=Test&active_member=active&visible=1" \
  http://gvv.net/index.php/email_lists/store
```

## Prochaines étapes après diagnostic

Une fois que vous avez identifié le scénario, nous pourrons corriger le problème spécifique.
