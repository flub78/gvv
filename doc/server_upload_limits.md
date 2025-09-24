# Configuration des limites d'upload pour la restauration des médias

## Problème

Lors de la restauration de fichiers média volumineux, vous pouvez rencontrer l'erreur "Vous n'avez pas sélectionné de fichier à envoyer" même si vous avez sélectionné un fichier. Cela se produit généralement lorsque le fichier dépasse les limites configurées sur le serveur.

## Limites actuelles du serveur

Vous pouvez vérifier les limites actuelles via l'interface admin en allant sur `/admin/info` ou en consultant les logs de l'application.

Les principales limites à vérifier sont :
- `upload_max_filesize` : Taille maximum d'un fichier uploadé
- `post_max_size` : Taille maximum des données POST (doit être supérieure à upload_max_filesize)
- `memory_limit` : Limite mémoire PHP
- `max_execution_time` : Temps maximum d'exécution

## Solutions

### 1. Modification du php.ini

Modifiez les valeurs suivantes dans votre fichier `php.ini` :

```ini
; Augmenter la taille maximum des fichiers
upload_max_filesize = 100M

; Augmenter la taille maximum des données POST
post_max_size = 120M

; Augmenter la limite mémoire si nécessaire
memory_limit = 256M

; Augmenter le temps d'exécution pour les gros fichiers
max_execution_time = 300
```

### 2. Via .htaccess (si mod_php est utilisé)

Créez ou modifiez le fichier `.htaccess` à la racine de votre application :

```apache
php_value upload_max_filesize 100M
php_value post_max_size 120M
php_value memory_limit 256M
php_value max_execution_time 300
```

### 3. Via un fichier .user.ini (sur certains hébergements)

Créez un fichier `.user.ini` dans le répertoire de votre application :

```ini
upload_max_filesize = 100M
post_max_size = 120M
memory_limit = 256M
max_execution_time = 300
```

## Vérification

Après modification, redémarrez votre serveur web et vérifiez que les nouvelles valeurs sont prises en compte via `/admin/info`.

## Alternative pour les très gros fichiers

Si vous ne pouvez pas modifier les limites du serveur, vous pouvez :

1. **Diviser les fichiers** : Créer des archives plus petites
2. **Utiliser FTP** : Uploader le fichier via FTP dans le dossier `uploads/restore/` puis utiliser l'interface de restauration
3. **Ligne de commande** : Extraire directement sur le serveur via SSH

### Extraction manuelle via ligne de commande

Si vous avez accès SSH au serveur, vous pouvez extraire manuellement l'archive :

```bash
# Se placer dans le répertoire uploads de l'application
cd /chemin/vers/votre/application/uploads

# Extraire l'archive tar.gz (remplacez le nom du fichier)
tar -xzf /chemin/vers/votre/fichier_backup.tar.gz

# Ou pour une archive .tgz
tar -xzf /chemin/vers/votre/fichier_backup.tgz

# Ou pour une archive .tar simple
tar -xf /chemin/vers/votre/fichier_backup.tar

# Vérifier les permissions après extraction
chmod -R 755 .
chown -R www-data:www-data . # Ajustez selon votre configuration
```

## Notes importantes

- `post_max_size` doit toujours être supérieur à `upload_max_filesize`
- Ces limites s'appliquent également au serveur web (Nginx, Apache) qui peut avoir ses propres limites
- Sur un hébergement mutualisé, certaines limites peuvent ne pas être modifiables
