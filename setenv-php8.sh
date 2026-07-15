#!/bin/bash
# setenv-php8.sh - Configure l'environnement pour utiliser PHP 8.4
#
# Usage : source setenv-php8.sh
#
# Ajoute bin/php8-shim/ (qui contient un lien symbolique php -> ../php8,
# lui-même pointant vers /usr/bin/php8.4) en tête du PATH, afin que la
# commande `php` utilise la version 8.4 cible de la migration PHP 8,
# sans modifier bin/php (qui reste pointé sur PHP 7.4 pour setenv.sh).

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

export PATH="$SCRIPT_DIR/bin/php8-shim:$SCRIPT_DIR/bin:$PATH"

echo "Environnement configuré : php -> $(command -v php) ($(php -v | head -n 1))"
