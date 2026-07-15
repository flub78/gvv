#!/bin/bash
# setenv.sh - Configure l'environnement pour utiliser PHP 7.4
#
# Usage : source setenv.sh
#
# Ajoute bin/ (qui contient un lien symbolique php -> /usr/bin/php7.4)
# en tête du PATH, afin que la commande `php` utilise la version 7.4
# requise par le projet, avant toute autre version installée.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

export PATH="$SCRIPT_DIR/bin:$PATH"

echo "Environnement configuré : php -> $(command -v php) ($(php -v | head -n 1))"
