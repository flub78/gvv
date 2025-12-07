#!/bin/bash
#
# GVV - Script d'initialisation de la base de test pour Jenkins/CI
#
# Ce script déchiffre et restaure la base de données de test chiffrée
# Utilisé dans les jobs Jenkins pour initialiser l'environnement de test
#
# Variables d'environnement requises:
#   - GVV_TEST_DB_PASSPHRASE: Passphrase pour déchiffrer la base GPG
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

ENCRYPTED_FILE="install/base_de_test.sql.gpg"
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

# Créer un fichier temporaire pour la passphrase
PASSPHRASE_FILE=$(mktemp)
echo "$GVV_TEST_DB_PASSPHRASE" > "$PASSPHRASE_FILE"

# Déchiffrer et injecter directement dans MySQL
gpg --quiet --batch --yes --decrypt \
    --passphrase-file "$PASSPHRASE_FILE" \
    "$ENCRYPTED_FILE" | \
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME"

RESULT=$?

# Supprimer le fichier de passphrase
rm -f "$PASSPHRASE_FILE"

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
