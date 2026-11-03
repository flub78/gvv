#!/bin/bash
# Fonctions communes aux scripts de sauvegarde hors-site
# (rclone_offsite_upload.sh, rclone_safe_retention.sh), chargées par :
#   . "$(dirname "$0")/rclone_common.sh"
# Variable attendue : LOG (fichier journal).

# Ajoute une ligne horodatée au journal.
log() {
    echo "$(date '+%F %T') - $*" >> "$LOG"
}
