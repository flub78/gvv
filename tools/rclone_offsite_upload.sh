#!/bin/bash
# Envoie la sauvegarde locale la plus récente vers le stockage distant et
# signale le résultat à un service de supervision (Healthchecks.io ou équivalent).
#
# Usage: rclone_offsite_upload.sh <motif_fichiers> <remote:chemin/> <fichier_log> [url_ping]
#
#   motif_fichiers  motif shell des sauvegardes locales, entre guillemets
#                   (ex. "/home/aeroclub/gvv/backups/media_backup_*.tar.gz")
#   remote:chemin/  dossier distant rclone (ex. gdrive:GVV_backups/media/)
#   fichier_log     journal où ajouter le compte rendu
#   url_ping        facultatif : URL de ping du check (ex. https://hc-ping.com/<uuid>).
#                   Succès : ping de l'URL ; échec : ping de <url>/fail avec la fin du
#                   journal, que le service joint à son alerte.
#
# Échecs détectés (code retour 1, ping /fail) :
#   - aucune sauvegarde locale ne correspond au motif ;
#   - la sauvegarde locale la plus récente est trop ancienne (la sauvegarde locale
#     ne tourne plus : sans ce contrôle, rclone renverrait sans erreur l'ancien fichier) ;
#   - l'envoi rclone échoue (jeton expiré, quota, réseau…).
# Si le serveur ou le cron ne tourne plus, aucun ping n'arrive : c'est le service
# de supervision qui alerte, à l'expiration du délai configuré.
#
# Variables d'environnement facultatives :
#   MAX_AGE_HOURS  âge maximal de la sauvegarde locale, en heures (défaut : 36)
#   RCLONE, CURL   chemins des exécutables (défaut : /usr/bin/rclone, /usr/bin/curl)

set -u

PATTERN="${1:?motif des fichiers manquant}"
REMOTE_DIR="${2:?dossier distant manquant}"
LOG="${3:?fichier de log manquant}"
PING_URL="${4:-}"
MAX_AGE_HOURS="${MAX_AGE_HOURS:-36}"
RCLONE="${RCLONE:-/usr/bin/rclone}"
CURL="${CURL:-/usr/bin/curl}"

log() {
    echo "$(date '+%F %T') - $*" >> "$LOG"
}

# Ping du service de supervision ; un échec du ping est journalisé sans masquer le résultat.
ping_monitor() {
    local suffix="$1" body="$2"
    [ -z "$PING_URL" ] && return 0
    if ! printf '%s\n' "$body" | "$CURL" -fsS -m 10 --retry 3 -o /dev/null --data-binary @- "${PING_URL}${suffix}"; then
        log "AVERTISSEMENT : ping de supervision impossible (${PING_URL}${suffix})"
    fi
}

fail() {
    log "ALERTE : envoi hors-site ECHOUE vers $REMOTE_DIR - $1"
    ping_monitor "/fail" "$(tail -n 30 "$LOG")"
    exit 1
}

# Sauvegarde locale la plus récente correspondant au motif (le motif est développé ici).
LATEST=""
for f in $PATTERN; do
    [ -f "$f" ] || continue
    if [ -z "$LATEST" ] || [ "$f" -nt "$LATEST" ]; then
        LATEST="$f"
    fi
done

[ -n "$LATEST" ] || fail "aucune sauvegarde locale ne correspond à $PATTERN"

AGE_HOURS=$(( ( $(date +%s) - $(stat -c %Y "$LATEST") ) / 3600 ))
if [ "$AGE_HOURS" -gt "$MAX_AGE_HOURS" ]; then
    fail "la sauvegarde locale la plus récente ($(basename "$LATEST")) date de ${AGE_HOURS} h (maximum ${MAX_AGE_HOURS} h) : la sauvegarde locale ne tourne plus ?"
fi

if ! "$RCLONE" copy "$LATEST" "$REMOTE_DIR" >> "$LOG" 2>&1; then
    fail "erreur rclone en envoyant $(basename "$LATEST") (voir les lignes précédentes du log ; jeton expiré : rclone config reconnect)"
fi

log "envoi OK : $(basename "$LATEST") -> $REMOTE_DIR"
ping_monitor "" "$(basename "$LATEST")"
exit 0
