#!/bin/bash
#
# Paramètres MySQL des scripts bin/, lus dans le groupe 'default' de
# application/config/database.php (fichier non suivi par git), pour ne
# jamais écrire d'identifiants dans un fichier versionné.
# Les variables d'environnement MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD et
# MYSQL_DATABASE, si elles sont définies, sont prioritaires.
#
# Usage : source "$(dirname "$0")/db_config.sh"

DB_CONFIG_FILE="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)/application/config/database.php"

db_config_value() {
    sed -n "s/^[[:space:]]*\$db\['default'\]\['$1'\][[:space:]]*=[[:space:]]*'\([^']*\)'[[:space:]]*;.*/\1/p" \
        "$DB_CONFIG_FILE" 2>/dev/null | head -1
}

if [ -z "$MYSQL_PASSWORD" ] && [ ! -f "$DB_CONFIG_FILE" ]; then
    echo "Erreur : $DB_CONFIG_FILE introuvable et MYSQL_PASSWORD non défini." >&2
    exit 1
fi

DB_HOST="${MYSQL_HOST:-$(db_config_value hostname)}"
DB_USER="${MYSQL_USER:-$(db_config_value username)}"
DB_PASS="${MYSQL_PASSWORD:-$(db_config_value password)}"
DB_NAME="${MYSQL_DATABASE:-$(db_config_value database)}"
