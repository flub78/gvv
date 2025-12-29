#!/bin/bash
#
# GVV - Script d'initialisation de la base de test pour Jenkins/CI
#
# Ce script déchiffre (OpenSSL) et restaure la base de données de test chiffrée
# Utilisé dans les jobs Jenkins pour initialiser l'environnement de test
#
# Variables d'environnement requises:
#   - GVV_TEST_DB_PASSPHRASE: Passphrase pour déchiffrer la base (OpenSSL AES-256-CBC)
#   - MYSQL_DATABASE: Nom de la base (défaut: gvv2)
#   - MYSQL_USER: Utilisateur MySQL (défaut: gvv_user)
#   - MYSQL_PASSWORD: Mot de passe MySQL
#   - MYSQL_HOST: Hôte MySQL (défaut: localhost)
#

set -e  # Exit on error

# Couleurs pour output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration par défaut
DB_NAME="${MYSQL_DATABASE:-gvv2}"
DB_USER="${MYSQL_USER:-gvv_user}"
DB_PASSWORD="${MYSQL_PASSWORD:-}"
DB_HOST="${MYSQL_HOST:-localhost}"

ENCRYPTED_FILE="install/base_de_test.enc.zip"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

cd "$PROJECT_ROOT"

echo "========================================="
echo "  GVV - Initialisation base de test"
echo "========================================="
echo ""

# Vérifier que le fichier chiffré existe
if [ ! -f "$ENCRYPTED_FILE" ]; then
    echo -e "${RED}ERROR:${NC} Fichier chiffré introuvable: $ENCRYPTED_FILE"
    echo "Générez-le d'abord avec: http://gvv.net/admin/generate_test_database"
    exit 1
fi

# Vérifier que la passphrase est définie
if [ -z "$GVV_TEST_DB_PASSPHRASE" ]; then
    echo -e "${RED}ERROR:${NC} Variable d'environnement GVV_TEST_DB_PASSPHRASE non définie"
    echo "Définissez-la dans Jenkins Credentials ou via:"
    echo "  export GVV_TEST_DB_PASSPHRASE='votre_passphrase'"
    exit 1
fi

# Vérifier que le mot de passe MySQL est défini
if [ -z "$DB_PASSWORD" ]; then
    echo -e "${RED}ERROR:${NC} Variable d'environnement MYSQL_PASSWORD non définie"
    exit 1
fi

echo "Configuration:"
echo "  - Database: $DB_NAME"
echo "  - Host: $DB_HOST"
echo "  - User: $DB_USER"
echo "  - Encrypted file: $ENCRYPTED_FILE"
echo ""

# Étape 1: Supprimer et recréer la base
echo -e "${YELLOW}[1/3]${NC} Suppression et recréation de la base $DB_NAME..."
mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" -e "DROP DATABASE IF EXISTS $DB_NAME;" 2>/dev/null || true
mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" -e "CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓${NC} Base créée"
else
    echo -e "${RED}✗${NC} Échec de la création de la base"
    exit 1
fi

# Étape 2: Déchiffrer et restaurer
echo -e "${YELLOW}[2/3]${NC} Déchiffrement et restauration de la base de test..."

# Créer des fichiers temporaires
TEMP_ZIP=$(mktemp --suffix=.zip)
TEMP_SQL=$(mktemp --suffix=.sql)

# Déchiffrer avec OpenSSL (compatible avec crypto_helper.php)
openssl enc -d -aes-256-cbc -pbkdf2 \
    -in "$ENCRYPTED_FILE" \
    -out "$TEMP_ZIP" \
    -pass pass:"$GVV_TEST_DB_PASSPHRASE" 2>/dev/null

if [ $? -ne 0 ]; then
    echo -e "${RED}✗${NC} Échec du déchiffrement OpenSSL"
    rm -f "$TEMP_ZIP" "$TEMP_SQL"
    exit 1
fi

# Créer un répertoire temporaire pour l'extraction
TEMP_DIR=$(mktemp -d)

# Décompresser le ZIP
unzip -q -o "$TEMP_ZIP" -d "$TEMP_DIR" 2>/dev/null
if [ $? -ne 0 ]; then
    echo -e "${RED}✗${NC} Échec de la décompression"
    rm -rf "$TEMP_ZIP" "$TEMP_SQL" "$TEMP_DIR"
    exit 1
fi

# Trouver le fichier SQL extrait (chercher n'importe quel .sql)
SQL_FILE=$(find "$TEMP_DIR" -name "*.sql" -type f | head -n 1)
if [ -z "$SQL_FILE" ] || [ ! -f "$SQL_FILE" ]; then
    echo -e "${RED}✗${NC} Fichier SQL introuvable après décompression"
    echo "Contenu du ZIP:"
    ls -la "$TEMP_DIR"
    rm -rf "$TEMP_ZIP" "$TEMP_SQL" "$TEMP_DIR"
    exit 1
fi

# Nettoyer le fichier SQL des lignes problématiques
# Supprimer les commentaires MariaDB/MySQL qui peuvent poser problème avec le client mysql
CLEANED_SQL="$TEMP_DIR/cleaned.sql"
# Supprimer: commentaires --, commentaires conditionnels /*!...*/ et /*M!...*/, et lignes vides en début
sed -e '/^-- /d' \
    -e '/^\/\*[!M]/d' \
    -e '1,/^DROP TABLE/{ /^$/d; /^--$/d; }' \
    "$SQL_FILE" > "$CLEANED_SQL"

# Vérifier que le fichier nettoyé n'est pas vide
if [ ! -s "$CLEANED_SQL" ]; then
    echo -e "${YELLOW}⚠${NC} Le nettoyage a produit un fichier vide, utilisation du fichier original"
    CLEANED_SQL="$SQL_FILE"
fi

# Importer dans MySQL avec binary-mode pour éviter l'interprétation des séquences \
mysql --binary-mode -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < "$CLEANED_SQL"
RESULT=$?

# Nettoyer les fichiers temporaires
rm -rf "$TEMP_ZIP" "$TEMP_SQL" "$TEMP_DIR"

if [ $RESULT -eq 0 ]; then
    echo -e "${GREEN}✓${NC} Base restaurée"
else
    echo -e "${RED}✗${NC} Échec de la restauration"
    exit 1
fi

# Étape 3: Vérifier la migration
echo -e "${YELLOW}[3/3]${NC} Vérification de la version de migration..."

MIGRATION_VERSION=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" \
    -sN -e "SELECT version FROM migrations ORDER BY version DESC LIMIT 1" 2>/dev/null || echo "0")

if [ "$MIGRATION_VERSION" -gt 0 ]; then
    echo -e "${GREEN}✓${NC} Migration version: $MIGRATION_VERSION"
else
    echo -e "${RED}✗${NC} Table migrations introuvable ou vide"
fi

# Vérifier quelques tables clés
echo ""
echo "Vérification des tables clés..."

TABLES=("users" "membres" "sections" "types_roles" "user_roles_per_section")
ALL_OK=true

for table in "${TABLES[@]}"; do
    COUNT=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" \
        -sN -e "SELECT COUNT(*) FROM $table" 2>/dev/null || echo "0")
    
    if [ "$COUNT" -gt 0 ]; then
        echo -e "  ${GREEN}✓${NC} $table: $COUNT enregistrements"
    else
        echo -e "  ${RED}✗${NC} $table: vide ou introuvable"
        ALL_OK=false
    fi
done

echo ""
echo "========================================="
if [ "$ALL_OK" = true ]; then
    echo -e "${GREEN}✓ Initialisation réussie !${NC}"
    echo "========================================="
    exit 0
else
    echo -e "${RED}✗ Initialisation terminée avec avertissements${NC}"
    echo "========================================="
    exit 1
fi
