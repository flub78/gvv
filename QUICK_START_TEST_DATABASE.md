# Démarrage rapide - Base de test chiffrée

## Génération (développeur)

1. **Accéder au dashboard admin**
   ```
   http://gvv.net/admin
   ```

2. **Section "Outils de développement" → "Générer base de test"**

3. **Configurer et lancer**
   - ☐ Anonymisation numérotée (optionnel, plus rapide)
   - 🔑 Passphrase : `votre_passphrase_forte`
   - Cliquer "Générer la base de test"

4. **Vérifier et commiter**
   ```bash
   ls -lh install/base_de_test.sql.gpg  # Doit exister
   git add install/base_de_test.sql.gpg
   git commit -m "Update test database to migration 55"
   git push
   ```

## Utilisation dans Jenkins

### Configuration credentials
1. Jenkins → Credentials → Add Secret Text
   - ID: `gvv-test-db-passphrase`
   - Secret: [votre passphrase]

### Job configuration
```groovy
environment {
    GVV_TEST_DB_PASSPHRASE = credentials('gvv-test-db-passphrase')
}

stages {
    stage('Setup DB') {
        steps { sh './bin/init_test_database.sh' }
    }
    stage('Tests') {
        steps { sh 'source setenv.sh && ./run-all-tests.sh' }
    }
}
```

## Test local

```bash
export GVV_TEST_DB_PASSPHRASE="votre_passphrase"
export MYSQL_PASSWORD="lfoyfgbj"
./bin/init_test_database.sh
```

## Documentation complète

- **Guide complet** : `doc/test-database-encrypted.md`
- **Configuration Jenkins** : `doc/jenkins-phpunit-setup.md`
- **Résumé implémentation** : `TEST_DATABASE_ENCRYPTED_IMPLEMENTATION.md`

## Dépannage rapide

| Problème | Solution |
|----------|----------|
| Passphrase non fournie | `export GVV_TEST_DB_PASSPHRASE="..."` |
| Fichier .gpg manquant | Générer via dashboard admin |
| Échec déchiffrement | Vérifier passphrase, tester `gpg --decrypt install/base_de_test.sql.gpg` |
| Tests échouent | Vérifier migration version, régénérer si obsolète |

## Fichiers clés

```
install/base_de_test.sql.gpg    ← Base chiffrée (dans Git) ✅
bin/init_test_database.sh        ← Script restauration Jenkins
bin/create_test_users.sh         ← Création users de test
```

**Utilisateurs de test** : testuser, testadmin, testplanchiste, testca, testbureau, testtresorier  
**Mot de passe** : `password`
