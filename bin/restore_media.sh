#!/bin/bash
#
# restore_media.sh - Restaure les médias GVV depuis une archive tar.gz
#
# Usage: bin/restore_media.sh [OPTIONS] <archive.tar.gz>
#
# L'archive doit être au format généré par la sauvegarde des médias GVV
# (fichier tar.gz contenant les fichiers du répertoire uploads/).
#

set -euo pipefail

# Répertoire racine du projet (parent du répertoire bin/)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
UPLOADS_DIR="$PROJECT_DIR/uploads"

# Options par défaut
MERGE=false
BACKUP_FILE=""
ARCHIVE=""

usage() {
    cat <<EOF
Usage: $(basename "$0") [OPTIONS] <archive.tar.gz>

Restaure les médias GVV depuis une archive tar.gz dans le répertoire uploads/.

OPTIONS:
  --merge              Fusionne les médias de l'archive avec les médias existants
                       sans supprimer les fichiers locaux non présents dans l'archive.
                       Par défaut : remplacement complet (les fichiers existants sont supprimés).
  --backup <fichier>   Sauvegarde le répertoire uploads/ local au format tar.gz
                       avant la restauration. Le nom de fichier est obligatoire.
  --help               Affiche cette aide et quitte.

ARGUMENTS:
  <archive.tar.gz>     Fichier d'archive à restaurer (format tar.gz).
                       Peut être chiffré (.enc.tar.gz), auquel cas une passphrase
                       sera demandée.

EXEMPLES:
  $(basename "$0") gvv_monclub_media_2025_09_21.tar.gz
  $(basename "$0") --merge gvv_monclub_media_2025_09_21.tar.gz
  $(basename "$0") --backup /tmp/backup_avant_restore.tar.gz gvv_monclub_media_2025_09_21.tar.gz
  $(basename "$0") --merge --backup /tmp/backup.tar.gz gvv_monclub_media_2025_09_21.tar.gz
EOF
}

# Analyse des arguments
while [[ $# -gt 0 ]]; do
    case "$1" in
        --help|-h)
            usage
            exit 0
            ;;
        --merge)
            MERGE=true
            shift
            ;;
        --backup)
            if [[ -z "${2:-}" ]]; then
                echo "Erreur : --backup requiert un nom de fichier." >&2
                exit 1
            fi
            BACKUP_FILE="$2"
            shift 2
            ;;
        -*)
            echo "Erreur : option inconnue '$1'." >&2
            usage >&2
            exit 1
            ;;
        *)
            if [[ -n "$ARCHIVE" ]]; then
                echo "Erreur : plusieurs archives spécifiées. Un seul fichier est accepté." >&2
                exit 1
            fi
            ARCHIVE="$1"
            shift
            ;;
    esac
done

# Vérification de l'archive
if [[ -z "$ARCHIVE" ]]; then
    echo "Erreur : aucun fichier d'archive spécifié." >&2
    usage >&2
    exit 1
fi

if [[ ! -f "$ARCHIVE" ]]; then
    echo "Erreur : le fichier '$ARCHIVE' n'existe pas." >&2
    exit 1
fi

# Vérification du répertoire uploads
if [[ ! -d "$UPLOADS_DIR" ]]; then
    echo "Erreur : le répertoire uploads '$UPLOADS_DIR' n'existe pas." >&2
    exit 1
fi

# Sauvegarde locale avant restauration
if [[ -n "$BACKUP_FILE" ]]; then
    echo "Sauvegarde du répertoire uploads/ vers '$BACKUP_FILE'..."
    BACKUP_DIR="$(dirname "$BACKUP_FILE")"
    if [[ ! -d "$BACKUP_DIR" ]]; then
        mkdir -p "$BACKUP_DIR"
    fi
    tar --exclude='restore' \
        --exclude='attachments_backup' \
        --exclude='*.tmp' \
        --exclude='*.bak' \
        -czf "$BACKUP_FILE" \
        -C "$UPLOADS_DIR" \
        .
    if [[ $? -eq 0 ]]; then
        echo "Sauvegarde créée : $BACKUP_FILE ($(du -sh "$BACKUP_FILE" | cut -f1))"
    else
        echo "Erreur : la sauvegarde a échoué." >&2
        exit 1
    fi
fi

# Déchiffrement si nécessaire
ARCHIVE_TO_EXTRACT="$ARCHIVE"
TEMP_DECRYPTED=""

if [[ "$ARCHIVE" == *.enc.tar.gz ]] || [[ "$ARCHIVE" == *.enc ]]; then
    echo "L'archive est chiffrée. Saisir la passphrase de déchiffrement :"
    read -s -r PASSPHRASE
    echo ""
    TEMP_DECRYPTED="$(mktemp /tmp/gvv_media_restore_XXXXXX.tar.gz)"
    openssl enc -d -aes-256-cbc -pbkdf2 -pass "pass:$PASSPHRASE" \
        -in "$ARCHIVE" -out "$TEMP_DECRYPTED"
    if [[ $? -ne 0 ]]; then
        echo "Erreur : le déchiffrement a échoué. Passphrase incorrecte ?" >&2
        rm -f "$TEMP_DECRYPTED"
        exit 1
    fi
    ARCHIVE_TO_EXTRACT="$TEMP_DECRYPTED"
fi

# Vérification de l'intégrité de l'archive
echo "Vérification de l'archive..."
if ! tar -tzf "$ARCHIVE_TO_EXTRACT" > /dev/null 2>&1; then
    echo "Erreur : l'archive '$ARCHIVE' est invalide ou corrompue." >&2
    [[ -n "$TEMP_DECRYPTED" ]] && rm -f "$TEMP_DECRYPTED"
    exit 1
fi

# Restauration
if [[ "$MERGE" == false ]]; then
    echo "Mode remplacement : suppression des médias existants..."
    # Supprimer tout sauf le répertoire restore (utilisé pour les restaurations en cours)
    find "$UPLOADS_DIR" -mindepth 1 -maxdepth 1 ! -name 'restore' -exec rm -rf {} +
fi

echo "Extraction de l'archive vers '$UPLOADS_DIR'..."
if [[ "$MERGE" == true ]]; then
    # En mode merge, ne pas écraser les fichiers plus récents
    tar -xzf "$ARCHIVE_TO_EXTRACT" -C "$UPLOADS_DIR" --keep-newer-files 2>/dev/null || \
    tar -xzf "$ARCHIVE_TO_EXTRACT" -C "$UPLOADS_DIR"
else
    tar -xzf "$ARCHIVE_TO_EXTRACT" -C "$UPLOADS_DIR"
fi

EXTRACT_STATUS=$?

# Nettoyage du fichier temporaire de déchiffrement
[[ -n "$TEMP_DECRYPTED" ]] && rm -f "$TEMP_DECRYPTED"

if [[ $EXTRACT_STATUS -ne 0 ]]; then
    echo "Erreur : l'extraction a échoué." >&2
    exit 1
fi

# Correction des permissions
chmod -R a+rX "$UPLOADS_DIR" 2>/dev/null || true

echo ""
if [[ "$MERGE" == true ]]; then
    echo "Restauration (fusion) terminée avec succès."
else
    echo "Restauration complète terminée avec succès."
fi
echo "Médias restaurés dans : $UPLOADS_DIR"
echo "Contenu du répertoire uploads/ :"
ls -lh "$UPLOADS_DIR" | tail -n +2
