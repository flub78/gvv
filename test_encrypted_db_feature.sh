#!/bin/bash
#
# Quick test script for encrypted test database feature
# This script validates the implementation without actually generating the database
#

set -e

echo "========================================="
echo "  Test: Encrypted Test Database Feature"
echo "========================================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

ERRORS=0

# Test 1: Check PHP files syntax
echo -e "${YELLOW}[1/6]${NC} Validating PHP syntax..."
source setenv.sh
if php -l application/controllers/admin.php > /dev/null 2>&1 && \
   php -l application/views/admin/bs_test_database_generation.php > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} PHP syntax OK"
else
    echo -e "${RED}✗${NC} PHP syntax errors found"
    ERRORS=$((ERRORS + 1))
fi

# Test 2: Check bash script
echo -e "${YELLOW}[2/6]${NC} Validating bash script..."
if bash -n bin/init_test_database.sh; then
    echo -e "${GREEN}✓${NC} Bash script syntax OK"
else
    echo -e "${RED}✗${NC} Bash script syntax errors"
    ERRORS=$((ERRORS + 1))
fi

# Test 3: Check required files exist
echo -e "${YELLOW}[3/6]${NC} Checking required files..."
REQUIRED_FILES=(
    "application/controllers/admin.php"
    "application/views/admin/bs_admin.php"
    "application/views/admin/bs_test_database_generation.php"
    "application/language/french/admin_lang.php"
    "bin/init_test_database.sh"
    "bin/create_test_users.sh"
    "doc/test-database-encrypted.md"
    "doc/jenkins-phpunit-setup.md"
)

ALL_FILES_OK=true
for file in "${REQUIRED_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo -e "  ${GREEN}✓${NC} $file"
    else
        echo -e "  ${RED}✗${NC} $file (missing)"
        ALL_FILES_OK=false
    fi
done

if [ "$ALL_FILES_OK" = true ]; then
    echo -e "${GREEN}✓${NC} All required files present"
else
    echo -e "${RED}✗${NC} Some files are missing"
    ERRORS=$((ERRORS + 1))
fi

# Test 4: Check new method exists in admin controller
echo -e "${YELLOW}[4/6]${NC} Checking admin controller method..."
if grep -q "function generate_test_database" application/controllers/admin.php; then
    echo -e "${GREEN}✓${NC} Method generate_test_database() found"
else
    echo -e "${RED}✗${NC} Method generate_test_database() not found"
    ERRORS=$((ERRORS + 1))
fi

# Test 5: Check dashboard card added
echo -e "${YELLOW}[5/6]${NC} Checking dashboard admin view..."
if grep -q "Générer base de test" application/views/admin/bs_admin.php; then
    echo -e "${GREEN}✓${NC} Dashboard card added"
else
    echo -e "${RED}✗${NC} Dashboard card not found"
    ERRORS=$((ERRORS + 1))
fi

# Test 6: Check .gitignore updated
echo -e "${YELLOW}[6/6]${NC} Checking .gitignore..."
if grep -q "base_de_test.sql" .gitignore && grep -q "base_de_test.zip" .gitignore; then
    echo -e "${GREEN}✓${NC} .gitignore updated correctly"
else
    echo -e "${RED}✗${NC} .gitignore not updated"
    ERRORS=$((ERRORS + 1))
fi

echo ""
echo "========================================="
if [ $ERRORS -eq 0 ]; then
    echo -e "${GREEN}✓ All tests passed!${NC}"
    echo "========================================="
    echo ""
    echo "Next steps:"
    echo "1. Access http://gvv.net/admin"
    echo "2. Click on 'Générer base de test' in 'Outils de développement'"
    echo "3. Enter a test passphrase (or set GVV_TEST_DB_PASSPHRASE)"
    echo "4. Click 'Générer la base de test'"
    echo "5. Check install/base_de_test.sql.gpg is created"
    echo "6. Test restoration: ./bin/init_test_database.sh"
    echo ""
    exit 0
else
    echo -e "${RED}✗ $ERRORS test(s) failed${NC}"
    echo "========================================="
    exit 1
fi
