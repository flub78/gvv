# Plan DevOps CI/CD pour GVV - Version Simplifiée

**Objectif** : Tests PHPUnit automatisés sur Jenkins avec base de données anonymisée et chiffrée.

**Date de création** : 2025-12-05
**Dernière mise à jour** : 2025-12-19
**Statut** : Implémentation

---

## Contexte

### Situation actuelle
- ✅ Serveur Jenkins déployé avec job à compléter
- ✅ PHP 7.4 installé sur Jenkins
- ✅ MySQL installé avec credentials de test
- ✅ Tables GVV déjà présentes dans la base
- ✅ Suite PHPUnit complète avec scripts `run-all-tests.sh`

### Objectif simplifié
1. Générer une sauvegarde anonymisée + chiffrée de la base de données
2. Commiter cette sauvegarde dans Git
3. Job Jenkins : déchiffrer → restaurer → exécuter tests PHPUnit
4. Notifications en cas d'échec

---

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│ Développeur                                                 │
│                                                             │
│  1. Modifie le code                                         │
│  2. git commit + push                                       │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ Git Repository                                              │
│                                                             │
│  • Code source GVV                                          │
│  • test_data/gvv_test.sql.gpg (base anonymisée chiffrée)    │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ Jenkins (polling ou webhook)                                │
│                                                             │
│  1. Détecte nouveau commit                                  │
│  2. Clone/Pull dépôt                                        │
│  3. Déchiffre test_data/gvv_test.sql.gpg                    │
│  4. Restaure base MySQL                                     │
│  5. source setenv.sh                                        │
│  6. ./run-all-tests.sh                                      │
│  7. Publie résultats + notification si échec                │
└─────────────────────────────────────────────────────────────┘
```

---

## Phase 1 : Génération base anonymisée chiffrée ⚡ PRIORITÉ 1

**Durée estimée** : 3-4h

### Étape 1.1 : Script d'export anonymisé

**Créer** : `bin/export_anonymized_db.sh`

```bash
#!/bin/bash
# Script d'export de la base de données anonymisée et chiffrée
# Usage: ./bin/export_anonymized_db.sh

set -e

# Configuration
DB_HOST="localhost"
DB_USER="gvv_user"
DB_PASS="lfoyfgbj"
DB_NAME="gvv2"
OUTPUT_DIR="test_data"
OUTPUT_FILE="gvv_test.sql"
ENCRYPTED_FILE="gvv_test.sql.gpg"
PASSPHRASE="${GVV_TEST_DB_PASSPHRASE:-changeme}"

echo "========================================="
echo "Export base de données anonymisée"
echo "========================================="

# Créer répertoire si nécessaire
mkdir -p "$OUTPUT_DIR"

# 1. Dump de la base
echo "1. Export de la base $DB_NAME..."
mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" \
  --single-transaction \
  --skip-triggers \
  "$DB_NAME" > "$OUTPUT_DIR/$OUTPUT_FILE"

# 2. Anonymisation des données sensibles
echo "2. Anonymisation des données..."
sed -i "s/\([0-9]\{10\}\)/XXXXXXXXXX/g" "$OUTPUT_DIR/$OUTPUT_FILE"  # Téléphones
sed -i "s/[a-zA-Z0-9._%+-]\+@[a-zA-Z0-9.-]\+\.[a-zA-Z]\{2,\}/anonyme@example.com/g" "$OUTPUT_DIR/$OUTPUT_FILE"  # Emails

# 3. Ajout des utilisateurs de test
echo "3. Ajout des utilisateurs de test..."
cat bin/create_test_users.sql >> "$OUTPUT_DIR/$OUTPUT_FILE"

# 4. Chiffrement avec GPG
echo "4. Chiffrement du fichier..."
gpg --batch --yes --passphrase "$PASSPHRASE" \
  --symmetric --cipher-algo AES256 \
  --output "$OUTPUT_DIR/$ENCRYPTED_FILE" \
  "$OUTPUT_DIR/$OUTPUT_FILE"

# 5. Nettoyage
rm "$OUTPUT_DIR/$OUTPUT_FILE"

echo ""
echo "✓ Export terminé : $OUTPUT_DIR/$ENCRYPTED_FILE"
echo "  Taille : $(du -h "$OUTPUT_DIR/$ENCRYPTED_FILE" | cut -f1)"
echo ""
echo "Déchiffrement test :"
echo "  gpg --batch --passphrase 'yourpass' -d $OUTPUT_DIR/$ENCRYPTED_FILE > test.sql"
```

**Actions** :
- [ ] Créer le fichier `bin/export_anonymized_db.sh`
- [ ] Rendre exécutable : `chmod +x bin/export_anonymized_db.sh`
- [ ] Adapter les patterns d'anonymisation selon vos besoins
- [ ] Ajouter `test_data/gvv_test.sql` à `.gitignore` (ne garder que le .gpg)

**Validation** :
```bash
# Définir la passphrase
export GVV_TEST_DB_PASSPHRASE="votre_passphrase_secrete"

# Exécuter le script
./bin/export_anonymized_db.sh

# Vérifier le fichier chiffré
ls -lh test_data/gvv_test.sql.gpg

# Test de déchiffrement
gpg --batch --passphrase "$GVV_TEST_DB_PASSPHRASE" \
  -d test_data/gvv_test.sql.gpg > /tmp/test_decrypt.sql

# Vérifier que c'est du SQL valide
head -20 /tmp/test_decrypt.sql
rm /tmp/test_decrypt.sql
```

---

### Étape 1.2 : Alternative - Contrôleur d'export

Si vous préférez un contrôleur CodeIgniter plutôt qu'un script bash :

**Créer** : `application/controllers/Admin_export.php`

```php
<?php
/**
 * Contrôleur pour export de base de données anonymisée
 * URL: /admin_export/database (accessible admin seulement)
 */
class Admin_export extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        // Vérifier droits admin
        if (!$this->dx_auth->is_admin()) {
            show_error('Accès refusé', 403);
        }
    }
    
    public function database() {
        $this->load->helper('download');
        
        // 1. Configuration
        $db_config = $this->db;
        $tables = $db_config->list_tables();
        
        // 2. Générer le dump SQL
        $sql_dump = $this->_generate_sql_dump($tables);
        
        // 3. Anonymiser
        $sql_dump = $this->_anonymize_data($sql_dump);
        
        // 4. Ajouter utilisateurs de test
        $sql_dump .= "\n" . file_get_contents('bin/create_test_users.sql');
        
        // 5. Chiffrer
        $encrypted = $this->_encrypt_gpg($sql_dump);
        
        // 6. Télécharger ou sauvegarder
        $filename = 'gvv_test_' . date('Ymd_His') . '.sql.gpg';
        file_put_contents('test_data/' . $filename, $encrypted);
        
        echo "Export terminé : test_data/$filename\n";
    }
    
    private function _generate_sql_dump($tables) {
        $dump = "-- GVV Database Export\n";
        $dump .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables as $table) {
            // Structure
            $create = $this->db->query("SHOW CREATE TABLE `$table`")->row_array();
            $dump .= "\n-- Table: $table\n";
            $dump .= "DROP TABLE IF EXISTS `$table`;\n";
            $dump .= $create['Create Table'] . ";\n\n";
            
            // Données
            $rows = $this->db->get($table)->result_array();
            foreach ($rows as $row) {
                $dump .= $this->_generate_insert($table, $row);
            }
        }
        
        return $dump;
    }
    
    private function _anonymize_data($sql) {
        // Anonymiser emails
        $sql = preg_replace(
            '/([a-zA-Z0-9._%+-]+)@([a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/',
            'anonyme@example.com',
            $sql
        );
        
        // Anonymiser téléphones (10 chiffres)
        $sql = preg_replace('/\b\d{10}\b/', 'XXXXXXXXXX', $sql);
        
        // Anonymiser noms (optionnel)
        // $sql = preg_replace(...);
        
        return $sql;
    }
    
    private function _encrypt_gpg($data) {
        $passphrase = getenv('GVV_TEST_DB_PASSPHRASE') ?: 'changeme';
        $temp_file = tempnam(sys_get_temp_dir(), 'gvv_export_');
        
        file_put_contents($temp_file, $data);
        
        exec(
            "gpg --batch --yes --passphrase " . escapeshellarg($passphrase) .
            " --symmetric --cipher-algo AES256 --output " . escapeshellarg($temp_file . '.gpg') .
            " " . escapeshellarg($temp_file),
            $output,
            $return_var
        );
        
        if ($return_var !== 0) {
            throw new Exception("Échec du chiffrement GPG");
        }
        
        $encrypted = file_get_contents($temp_file . '.gpg');
        unlink($temp_file);
        unlink($temp_file . '.gpg');
        
        return $encrypted;
    }
    
    private function _generate_insert($table, $row) {
        $columns = array_keys($row);
        $values = array_values($row);
        
        $values = array_map(function($v) {
            return $this->db->escape($v);
        }, $values);
        
        return sprintf(
            "INSERT INTO `%s` (`%s`) VALUES (%s);\n",
            $table,
            implode('`, `', $columns),
            implode(', ', $values)
        );
    }
}
```

**Avantage contrôleur** : Accès via URL, meilleure intégration CI, gestion d'erreurs
**Avantage script bash** : Plus simple, pas de dépendance au framework

**Choisir selon préférence** : Les deux approches sont valides.

---

### Étape 1.3 : Commiter le fichier chiffré

```bash
# Ajouter le fichier chiffré à Git
git add test_data/gvv_test.sql.gpg

# S'assurer que la version non chiffrée est ignorée
echo "test_data/*.sql" >> .gitignore
echo "test_data/*.sql.tmp" >> .gitignore

# Commit
git commit -m "feat: add encrypted test database"
git push
```

**Important** : Ne JAMAIS commiter le fichier SQL non chiffré !

---

## Phase 2 : Configuration Jenkins ⚡ PRIORITÉ 2

**Durée estimée** : 2-3h

### Étape 2.1 : Stocker la passphrase dans Jenkins

1. **Dans l'interface Jenkins** :
   - Aller dans "Manage Jenkins" → "Manage Credentials"
   - Ajouter "Secret text" :
     - **ID** : `gvv-test-db-passphrase`
     - **Secret** : votre passphrase
     - **Description** : "Passphrase pour déchiffrer gvv_test.sql.gpg"

2. **Alternative : fichier de configuration** (moins sécurisé)
   ```bash
   # Sur le serveur Jenkins
   echo "export GVV_TEST_DB_PASSPHRASE='votre_passphrase'" >> ~/.bashrc
   ```

---

### Étape 2.2 : Script de restauration de base

**Créer** : `bin/restore_test_db.sh`

```bash
#!/bin/bash
# Script de restauration de la base de données de test
# Usage: ./bin/restore_test_db.sh

set -e

# Configuration
DB_HOST="localhost"
DB_USER="gvv_user"
DB_PASS="lfoyfgbj"
DB_NAME="gvv2"
ENCRYPTED_FILE="test_data/gvv_test.sql.gpg"
PASSPHRASE="${GVV_TEST_DB_PASSPHRASE:-changeme}"

echo "========================================="
echo "Restauration base de données de test"
echo "========================================="

# Vérifier que le fichier chiffré existe
if [ ! -f "$ENCRYPTED_FILE" ]; then
    echo "❌ Fichier $ENCRYPTED_FILE introuvable"
    exit 1
fi

# 1. Déchiffrer
echo "1. Déchiffrement..."
gpg --batch --quiet --passphrase "$PASSPHRASE" \
  -d "$ENCRYPTED_FILE" > /tmp/gvv_test_restore.sql

# 2. Drop/Create database (optionnel - pour base propre)
echo "2. Réinitialisation de la base..."
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" <<EOF
DROP DATABASE IF EXISTS $DB_NAME;
CREATE DATABASE $DB_NAME CHARACTER SET utf8 COLLATE utf8_general_ci;
EOF

# 3. Restaurer
echo "3. Restauration des données..."
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < /tmp/gvv_test_restore.sql

# 4. Nettoyage
rm /tmp/gvv_test_restore.sql

# 5. Vérification
echo "4. Vérification..."
USER_COUNT=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
  -se "SELECT COUNT(*) FROM users")

echo ""
echo "✓ Restauration terminée"
echo "  Base: $DB_NAME"
echo "  Utilisateurs: $USER_COUNT"
echo ""
```

**Actions** :
- [ ] Créer le fichier `bin/restore_test_db.sh`
- [ ] Rendre exécutable : `chmod +x bin/restore_test_db.sh`
- [ ] Tester localement

**Validation** :
```bash
# Test local
export GVV_TEST_DB_PASSPHRASE="votre_passphrase"
./bin/restore_test_db.sh

# Vérifier la base
mysql -u gvv_user -plfoyfgbj gvv2 -e "SELECT username FROM users WHERE username LIKE 'test%'"
```

---

### Étape 2.3 : Configuration du Job Jenkins

**Configuration du job Jenkins** :

1. **Source Code Management** :
   - Type : Git
   - Repository URL : `https://github.com/votre-user/gvv.git`
   - Credentials : (si dépôt privé)
   - Branch : `*/main`

2. **Build Triggers** :
   - ☑ Poll SCM
   - Schedule : `H/15 * * * *` (toutes les 15 minutes)
   - Alternative : Webhook GitHub pour déclenchement immédiat

3. **Build Environment** :
   - ☑ Use secret text(s) or file(s)
     - Variable : `GVV_TEST_DB_PASSPHRASE`
     - Credentials : `gvv-test-db-passphrase`

4. **Build Steps** (Add build step → Execute shell) :

```bash
#!/bin/bash
set -e

echo "========================================="
echo "GVV PHPUnit Tests"
echo "========================================="
echo "Branch: $GIT_BRANCH"
echo "Commit: $GIT_COMMIT"
echo ""

# 1. Restaurer la base de données
echo "=== Étape 1: Restauration base de données ==="
chmod +x bin/restore_test_db.sh
./bin/restore_test_db.sh
echo ""

# 2. Configuration environnement PHP 7.4
echo "=== Étape 2: Configuration environnement ==="
source setenv.sh
php --version
echo ""

# 3. Vérifier composer dependencies (si nécessaire)
if [ ! -d "vendor" ]; then
    echo "=== Installation dépendances composer ==="
    composer install --no-interaction
    echo ""
fi

# 4. Exécuter les tests
echo "=== Étape 3: Exécution des tests PHPUnit ==="
./run-all-tests.sh

# 5. Archiver les résultats
echo ""
echo "=== Tests terminés ==="
```

5. **Post-build Actions** :
   - ☑ Publish JUnit test result report
     - Test report XMLs : `build/logs/*.xml`
   - ☑ Email Notification (ou Slack/Discord)
     - Recipients : votre email
     - Send e-mail for every unstable build : ☑
     - Send separate e-mails to individuals who broke the build : ☑

---

### Étape 2.4 : Test du job Jenkins

```bash
# 1. Faire un commit de test
echo "// test" >> application/controllers/Welcome.php
git add .
git commit -m "test: trigger jenkins"
git push

# 2. Attendre 15 minutes (ou déclencher manuellement)
# Dans Jenkins : cliquer "Build Now"

# 3. Vérifier les logs Jenkins
# Console Output doit montrer :
#  - Déchiffrement OK
#  - Restauration OK
#  - Tests exécutés
#  - Résultats publiés
```

**Validation** :
- [ ] Job se déclenche automatiquement (après 15min max)
- [ ] Base de données restaurée correctement
- [ ] Tests PHPUnit exécutés
- [ ] Résultats visibles dans Jenkins
- [ ] Email reçu en cas d'échec

---

## Phase 3 : Maintenance et évolution 🔄

### Quand regénérer la base anonymisée ?

**À chaque** :
- ✅ Modification du schéma (nouvelle migration)
- ✅ Ajout de données de test importantes
- ✅ Correction de bug dans l'anonymisation

**Workflow** :
```bash
# 1. Appliquer les changements en local
./run_migrations.php  # Si migration

# 2. Régénérer la base anonymisée
export GVV_TEST_DB_PASSPHRASE="votre_passphrase"
./bin/export_anonymized_db.sh

# 3. Commit
git add test_data/gvv_test.sql.gpg
git commit -m "chore: update test database (migration XX)"
git push

# 4. Jenkins va automatiquement utiliser la nouvelle version
```

---

### Améliorer l'anonymisation

**Patterns courants à ajouter** :

```bash
# Dans bin/export_anonymized_db.sh

# Anonymiser adresses
sed -i "s/\([0-9]\+ rue [^,]*\)/1 rue Anonyme/g" "$OUTPUT_DIR/$OUTPUT_FILE"

# Anonymiser noms de famille
sed -i "s/INSERT INTO `membres` VALUES ([^,]*, '[^']*', '\([^']*\)'/INSERT INTO `membres` VALUES (\1, 'Nom', 'Prenom'/g" "$OUTPUT_DIR/$OUTPUT_FILE"

# Anonymiser IBAN/RIB
sed -i "s/FR[0-9]\{2\}[0-9A-Z]\{23\}/FR00XXXXXXXXXXXXXXXXXXXX/g" "$OUTPUT_DIR/$OUTPUT_FILE"
```

**Test de l'anonymisation** :

```bash
# Après export, vérifier qu'il n'y a plus de données sensibles
gpg --batch --passphrase "$GVV_TEST_DB_PASSPHRASE" \
  -d test_data/gvv_test.sql.gpg | grep -i "email\|telephone\|iban"

# Ne doit rien trouver de sensible
```

---

## Checklist finale

### Développeur local
- [ ] Script `bin/export_anonymized_db.sh` créé et testé
- [ ] Script `bin/restore_test_db.sh` créé et testé
- [ ] Fichier `.gitignore` mis à jour (exclut .sql, garde .sql.gpg)
- [ ] Base anonymisée générée : `test_data/gvv_test.sql.gpg`
- [ ] Fichier chiffré commité et poussé sur Git
- [ ] Anonymisation vérifiée (pas de données sensibles)

### Jenkins
- [ ] Passphrase stockée dans Jenkins Credentials
- [ ] Job Jenkins configuré (SCM, triggers, build steps)
- [ ] Test manuel du job : Build Now
- [ ] Vérification logs : déchiffrement + restauration + tests OK
- [ ] Notifications configurées (email/Slack)
- [ ] Test avec commit : déclenchement automatique

### Workflow établi
- [ ] Procédure documentée pour régénérer la base
- [ ] Tests exécutés automatiquement à chaque commit
- [ ] Notifications reçues en cas d'échec
- [ ] Historique des tests visible dans Jenkins

---

## Dépannage

### Problème : "gpg: decryption failed: Bad session key"

**Cause** : Mauvaise passphrase

**Solution** :
```bash
# Vérifier la passphrase localement
echo $GVV_TEST_DB_PASSPHRASE

# Dans Jenkins : vérifier le credential ID
# Manage Jenkins → Credentials → vérifier "gvv-test-db-passphrase"

# Tester le déchiffrement manuellement sur Jenkins
ssh jenkins-server
export GVV_TEST_DB_PASSPHRASE="votre_passphrase"
gpg --batch --passphrase "$GVV_TEST_DB_PASSPHRASE" -d test_data/gvv_test.sql.gpg > /tmp/test.sql
```

---

### Problème : "Access denied for user 'gvv_user'@'localhost'"

**Solution** :
```bash
# Sur le serveur Jenkins, vérifier les credentials MySQL
mysql -u gvv_user -plfoyfgbj -e "SELECT 1"

# Si erreur : recréer l'utilisateur
sudo mysql -u root -p
CREATE USER IF NOT EXISTS 'gvv_user'@'localhost' IDENTIFIED BY 'lfoyfgbj';
GRANT ALL PRIVILEGES ON gvv2.* TO 'gvv_user'@'localhost';
FLUSH PRIVILEGES;
```

---

### Problème : Tests échouent avec "Unknown database 'gvv2'"

**Solution** :
```bash
# Vérifier la création de la base dans restore_test_db.sh
# Ajouter si manquant :
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e \
  "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8 COLLATE utf8_general_ci"
```

---

### Problème : "PHP 7.4 not found"

**Solution** :
```bash
# Sur le serveur Jenkins
which php7.4
/usr/bin/php7.4 --version

# Si absent : installer
sudo apt install php7.4-cli php7.4-mysql php7.4-xml php7.4-mbstring

# Modifier setenv.sh pour pointer vers le bon PHP
```

---

## Résumé - Vue d'ensemble

### Ce qui est automatisé
✅ Détection des commits (polling 15min)
✅ Restauration base de données anonymisée
✅ Exécution tests PHPUnit
✅ Publication résultats
✅ Notifications échecs

### Ce qui reste manuel
🔧 Régénération base anonymisée (quand schéma change)
🔧 Mise à jour passphrase (si changement)
🔧 Ajout nouveaux patterns d'anonymisation

### Investissement
- **Configuration initiale** : 5-7h
- **Maintenance** : 15-30min par modification schéma
- **ROI** : Détection immédiate régressions + pas de tests manuels

---

## Prochaines étapes optionnelles

Une fois ce système en place :

1. **Couverture de code** (Phase 1.1b du plan original)
   - Job séparé : `./run-all-tests.sh --coverage`
   - Déclenché uniquement si tests passent

2. **Tests Playwright** (Phase 4 du plan original)
   - Nécessite serveur de test accessible
   - Peut réutiliser la même base anonymisée

3. **Déploiement automatique** (Phase 3 du plan original)
   - Après validation tests
   - Vers serveur Oracle Free Tier

**Pour l'instant** : Concentrez-vous sur Phase 1-2 de ce plan simplifié.

---

**Document créé par** : Frédéric avec assistance Claude Code
**Dernière mise à jour** : 2025-12-19
