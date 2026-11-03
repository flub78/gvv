#!/bin/bash
# Supprime les sauvegardes distantes de plus de 3 jours, UNIQUEMENT s'il
# existe déjà au moins une sauvegarde plus récente que 3 jours dans ce
# dossier — évite de vider le dossier distant si aucune nouvelle
# sauvegarde n'a été envoyée depuis plusieurs jours.
#
# Usage: rclone_safe_retention.sh <remote:chemin/> <fichier_log>
#
# Le compte rendu distingue les causes d'annulation :
#   - erreur rclone (jeton expiré, réseau…) : le contenu distant n'a pas pu être lu ;
#   - aucune sauvegarde distante récente : les envois ne se font plus.
# Code retour : 0 si la purge a eu lieu, 1 si elle a été annulée ou a échoué.
#
# Variable d'environnement facultative : RCLONE (défaut : /usr/bin/rclone)

set -u

REMOTE_DIR="${1:?dossier distant manquant}"
LOG="${2:?fichier de log manquant}"
RCLONE="${RCLONE:-/usr/bin/rclone}"

. "$(dirname "$0")/rclone_common.sh"

# --files-only : un sous-dossier récent ne doit pas compter comme une sauvegarde.
RECENT=$("$RCLONE" lsf --files-only --max-age 3d "$REMOTE_DIR" 2>>"$LOG")
RC=$?
if [ "$RC" -ne 0 ]; then
    log "ALERTE : purge ANNULEE sur $REMOTE_DIR - erreur rclone (code $RC) en lisant le dossier distant, voir les lignes précédentes du log (jeton expiré : rclone config reconnect)"
    exit 1
fi

RECENT_COUNT=$(printf '%s' "$RECENT" | grep -c .)
if [ "$RECENT_COUNT" -lt 1 ]; then
    log "ALERTE : purge ANNULEE sur $REMOTE_DIR - aucune sauvegarde distante de moins de 3 jours (les envois hors-site ne se font plus ?)"
    exit 1
fi

if ! "$RCLONE" --min-age 3d delete "$REMOTE_DIR" >> "$LOG" 2>&1; then
    log "ALERTE : purge en ECHEC sur $REMOTE_DIR - erreur rclone pendant la suppression, voir les lignes précédentes du log"
    exit 1
fi
log "purge OK sur $REMOTE_DIR ($RECENT_COUNT fichier(s) récent(s) présent(s))"
exit 0
