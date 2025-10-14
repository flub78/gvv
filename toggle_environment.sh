#!/bin/bash

# Script pour basculer entre les modes développement et production
# Usage: ./toggle_environment.sh [development|production]

CURRENT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
INDEX_FILE="$CURRENT_DIR/index.php"

# Fonction pour afficher l'environnement actuel
show_current() {
    if grep -q "define('ENVIRONMENT', 'development');" "$INDEX_FILE"; then
        echo "Environnement actuel: DEVELOPMENT"
        echo "⚠️  Les fonctions d'anonymisation sont ACTIVES"
    elif grep -q "define('ENVIRONMENT', 'production');" "$INDEX_FILE"; then
        echo "Environnement actuel: PRODUCTION"  
        echo "🔒 Les fonctions d'anonymisation sont BLOQUÉES"
    else
        echo "❓ Configuration d'environnement non détectée"
    fi
}

# Fonction pour basculer vers le développement
set_development() {
    sed -i.bak 's/define('\''ENVIRONMENT'\'', '\''production'\'');/define('\''ENVIRONMENT'\'', '\''development'\'');/' "$INDEX_FILE"
    echo "✅ Basculé vers le mode DEVELOPMENT"
    echo "⚠️  Les fonctions d'anonymisation sont maintenant ACTIVES"
    echo "🔥 N'OUBLIEZ PAS de repasser en production après usage!"
}

# Fonction pour basculer vers la production
set_production() {
    sed -i.bak 's/define('\''ENVIRONMENT'\'', '\''development'\'');/define('\''ENVIRONMENT'\'', '\''production'\'');/' "$INDEX_FILE"
    echo "✅ Basculé vers le mode PRODUCTION"
    echo "🔒 Les fonctions d'anonymisation sont maintenant BLOQUÉES"
    echo "🛡️  Système sécurisé"
}

# Main
case "$1" in
    "development"|"dev")
        echo "Basculement vers le mode développement..."
        set_development
        ;;
    "production"|"prod")
        echo "Basculement vers le mode production..."
        set_production
        ;;
    "status"|"")
        show_current
        echo ""
        echo "Usage: $0 [development|production|status]"
        echo "  development/dev  - Active les fonctions d'anonymisation"
        echo "  production/prod  - Bloque les fonctions d'anonymisation"
        echo "  status          - Affiche l'environnement actuel"
        ;;
    *)
        echo "❌ Option invalide: $1"
        echo "Usage: $0 [development|production|status]"
        exit 1
        ;;
esac