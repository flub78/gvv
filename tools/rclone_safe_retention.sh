#!/bin/bash
# Supprime les sauvegardes distantes de plus de 3 jours, UNIQUEMENT s'il
# existe déjà au moins une sauvegarde plus récente que 3 jours dans ce
# dossier — évite de vider le dossier distant si aucune nouvelle
# sauvegarde locale n'a été produite depuis plusieurs jours.
# Usage: rclone_safe_retention.sh <remote:chemin/> <fichier_log>

set -uo pipefail

REMOTE_DIR="$1"
LOG="$2"
RCLONE=/usr/bin/rclone

RECENT_COUNT=$("$RCLONE" lsf --max-age 3d "$REMOTE_DIR" 2>>"$LOG" | wc -l)

if [ "$RECENT_COUNT" -ge 1 ]; then
    "$RCLONE" --min-age 3d delete "$REMOTE_DIR" >> "$LOG" 2>&1
    echo "$(date '+%F %T') - purge OK sur $REMOTE_DIR ($RECENT_COUNT fichier(s) récent(s) présent(s))" >> "$LOG"
else
    echo "$(date '+%F %T') - ALERTE : purge ANNULEE sur $REMOTE_DIR - aucune sauvegarde de moins de 3 jours trouvée (dernière sauvegarde locale en échec ?)" >> "$LOG"
fi