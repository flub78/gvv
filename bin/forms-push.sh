#!/usr/bin/env bash
#
# forms-push.sh — synchronise un ou plusieurs formulaires GVV du poste de
#                 développement vers un serveur de production, via rsync/SSH.
#
# Chaque formulaire vit dans uploads/formulaires/<code>/ :
#   pageNN.html   fragment HTML de chaque page (emballé dans un doc HTML5)
#   style.css     CSS du formulaire
#   meta.json     titre, required_params, handler_class, target_*, liste des pages
#   images/       images référencées par les pages
#   template.pdf  gabarit PDF (optionnel)
#   .htaccess     "Require all denied" — jamais transféré (la prod garde le sien)
#
# CE QUE CE SCRIPT COUVRE
#   - modification du contenu d'une page existante (texte, champs, attributs
#     data-gvv-*, libellés), du style.css, des images : pris en compte au
#     rendu suivant grâce à la superposition fichier -> base de forms_public.
#
# CE QU'IL NE COUVRE PAS (nécessite le contrôleur CLI `forms_cli`, non livré)
#   - ajout / suppression / réordonnancement de page
#   - changement de titre de page
#   - métadonnées de la table `forms` : title, description, css_scope,
#     required_params, allow_upload_response, handler_class, target_url/label
#   Pour ces cas : pousser les fichiers PUIS resynchroniser la base, sinon la
#   prod sert une liste de pages / des métadonnées périmées.
#
# CE QU'IL NE FAIT JAMAIS
#   - créer un formulaire absent de la prod : public_slug, statut (publié /
#     brouillon) et section sont des décisions propres à l'installation. Créer
#     le formulaire une première fois via l'interface web, puis le mettre à
#     jour avec ce script.
#
# USAGE
#   bin/forms-push.sh [options] <code> [<code> ...]
#
# OPTIONS
#   -n, --dry-run     montre ce qui serait transféré, ne modifie rien
#       --host HOST    cible SSH           (défaut: $GVV_PROD_HOST  ou aeroclub@flub78.net)
#       --path PATH    racine GVV distante (défaut: $GVV_PROD_PATH  ou /home/aeroclub/gvv)
#       --key FILE     clé privée SSH      (défaut: $GVV_SSH_KEY    ou ~/.ssh/oracle)
#       --no-backup    ne pas archiver la version distante avant écrasement
#       --no-check     ne pas valider le HTML localement avant transfert
#       --allow-new    autoriser le transfert même si le formulaire n'existe pas
#                      encore côté prod (ne crée PAS la ligne en base)
#       --sync         après transfert, lancer `php index.php forms_cli sync <code>`
#                      sur la prod (requiert le contrôleur forms_cli)
#   -h, --help
#
# ROLLBACK
#   Les archives sont sur le serveur dans ~/gvv-form-backups/ :
#     ssh <host> 'tar xzf ~/gvv-form-backups/<code>_<horodatage>.tgz \
#                        -C <path>/uploads/formulaires/'
#
set -euo pipefail

# --------------------------------------------------------------------------
# Valeurs par défaut (surchargées par l'environnement puis par les options)
# --------------------------------------------------------------------------
PROD_HOST="${GVV_PROD_HOST:-aeroclub@flub78.net}"
PROD_PATH="${GVV_PROD_PATH:-/home/aeroclub/gvv}"
SSH_KEY="${GVV_SSH_KEY:-$HOME/.ssh/oracle}"

DRY_RUN=0
DO_BACKUP=1
DO_CHECK=1
ALLOW_NEW=0
DO_SYNC=0

# --------------------------------------------------------------------------
# Sorties
# --------------------------------------------------------------------------
if [ -t 1 ]; then
    C_RED=$'\033[31m'; C_GRN=$'\033[32m'; C_YEL=$'\033[33m'; C_BLU=$'\033[36m'; C_OFF=$'\033[0m'
else
    C_RED=; C_GRN=; C_YEL=; C_BLU=; C_OFF=
fi
info()  { printf '%s\n' "${C_BLU}$*${C_OFF}"; }
ok()    { printf '%s\n' "${C_GRN}$*${C_OFF}"; }
warn()  { printf '%s\n' "${C_YEL}!! $*${C_OFF}" >&2; }
die()   { printf '%s\n' "${C_RED}xx $*${C_OFF}" >&2; exit 1; }

usage() { sed -n '2,/^set -euo/p' "$0" | sed 's/^# \{0,1\}//; s/^#$//; /^set -euo/d'; exit "${1:-0}"; }

# --------------------------------------------------------------------------
# Analyse des arguments
# --------------------------------------------------------------------------
CODES=()
while [ $# -gt 0 ]; do
    case "$1" in
        -n|--dry-run) DRY_RUN=1 ;;
        --host)       shift; PROD_HOST="${1:-}" ;;
        --path)       shift; PROD_PATH="${1:-}" ;;
        --key)        shift; SSH_KEY="${1:-}" ;;
        --no-backup)  DO_BACKUP=0 ;;
        --no-check)   DO_CHECK=0 ;;
        --allow-new)  ALLOW_NEW=1 ;;
        --sync)       DO_SYNC=1 ;;
        -h|--help)    usage 0 ;;
        --)           shift; while [ $# -gt 0 ]; do CODES+=("$1"); shift; done; break ;;
        -*)           die "option inconnue : $1  (voir --help)" ;;
        *)            CODES+=("$1") ;;
    esac
    shift
done

[ "${#CODES[@]}" -ge 1 ] || usage 1
[ -n "$PROD_HOST" ] || die "hôte SSH vide (--host)"
[ -n "$PROD_PATH" ] || die "chemin distant vide (--path)"

# --------------------------------------------------------------------------
# Racine du dépôt
# --------------------------------------------------------------------------
if REPO_ROOT="$(git -C "$(dirname "${BASH_SOURCE[0]}")" rev-parse --show-toplevel 2>/dev/null)"; then
    :
else
    REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
fi
FORMS_DIR="$REPO_ROOT/uploads/formulaires"
[ -d "$FORMS_DIR" ] || die "répertoire des formulaires introuvable : $FORMS_DIR"

# --------------------------------------------------------------------------
# Outils requis
# --------------------------------------------------------------------------
command -v rsync >/dev/null || die "rsync absent du poste local"
command -v ssh   >/dev/null || die "ssh absent du poste local"

# --key '' (ou GVV_SSH_KEY='') : pas de -i, on s'en remet à ~/.ssh/config / l'agent
if [ -n "$SSH_KEY" ]; then
    [ -f "$SSH_KEY" ] || die "clé SSH introuvable : $SSH_KEY  (--key '' pour utiliser ~/.ssh/config)"
    SSH_CMD=(ssh -i "$SSH_KEY" -o ConnectTimeout=10)
    RSYNC_RSH="ssh -i $SSH_KEY -o ConnectTimeout=10"
else
    SSH_CMD=(ssh -o ConnectTimeout=10)
    RSYNC_RSH="ssh -o ConnectTimeout=10"
fi

PHP_BIN=""
if [ "$DO_CHECK" -eq 1 ]; then
    if command -v php >/dev/null; then
        PHP_BIN="$(command -v php)"
    else
        warn "php absent : validation HTML locale ignorée"
        DO_CHECK=0
    fi
fi

REMOTE_FORMS="$PROD_PATH/uploads/formulaires"

# --------------------------------------------------------------------------
# Validation des codes (avant tout accès réseau)
# --------------------------------------------------------------------------
for code in "${CODES[@]}"; do
    [[ "$code" =~ ^[A-Za-z0-9_-]+$ ]] \
        || die "code invalide : « $code » (attendu : lettres, chiffres, - et _)"
    [ -d "$FORMS_DIR/$code" ] \
        || die "formulaire local introuvable : $FORMS_DIR/$code"
    [ -f "$FORMS_DIR/$code/meta.json" ] && [ -f "$FORMS_DIR/$code/page01.html" ] \
        || die "$code : répertoire incomplet (meta.json + page01.html requis)"
done

# --------------------------------------------------------------------------
# Validation HTML locale (parser GVV)
# --------------------------------------------------------------------------
check_html() {
    local code="$1"
    CHECK_CODE="$code" "$PHP_BIN" -d error_reporting=E_ALL -r '
        define("BASEPATH", ".");
        require getcwd() . "/application/libraries/Forms_field_parser.php";
        $p = new Forms_field_parser();
        $code = getenv("CHECK_CODE");
        $fields = 0; $ident = 0; $pages = 0;
        foreach (glob("uploads/formulaires/$code/page*.html") as $f) {
            $pages++;
            foreach ($p->parse_fields(file_get_contents($f)) as $fld) {
                $fields++;
                if (!empty($fld["is_identifier"])) $ident++;
            }
        }
        fwrite(STDERR, "  $pages page(s), $fields champ(s), $ident champ(s) identifiant(s)\n");
        if ($pages === 0) { fwrite(STDERR, "  AUCUNE page\n"); exit(1); }
    '
}

# --------------------------------------------------------------------------
# Préflight distant (une seule connexion) : outils, droits, existence
# --------------------------------------------------------------------------
info "== préflight $PROD_HOST:$PROD_PATH =="
PREFLIGHT="$("${SSH_CMD[@]}" "$PROD_HOST" bash -s -- "$REMOTE_FORMS" "${CODES[@]}" <<'REMOTE'
set -eu
remote_forms="$1"; shift
command -v rsync >/dev/null || { echo "ERR rsync absent du serveur"; exit 1; }
[ -d "$remote_forms" ] || { echo "ERR répertoire distant introuvable : $remote_forms"; exit 1; }
[ -w "$remote_forms" ] && echo "PERM ok" || echo "PERM ro"
for c in "$@"; do
    if [ -d "$remote_forms/$c" ]; then echo "STATE EXISTE $c"; else echo "STATE NOUVEAU $c"; fi
done
REMOTE
)" || die "préflight distant en échec :"$'\n'"$PREFLIGHT"

declare -A REMOTE_STATE=()
while read -r tag rest; do
    case "$tag" in
        PERM)  if [ "$rest" = "ro" ]; then
                   warn "pas de droit d'écriture direct sur $REMOTE_FORMS (sudo requis ?)"
               fi ;;
        STATE) state="${rest%% *}"; code="${rest#* }"
               REMOTE_STATE["$code"]="$state"
               printf '  %-8s %s\n' "$state" "$code" ;;
        ERR)   die "$rest" ;;
    esac
done <<< "$PREFLIGHT"

for code in "${CODES[@]}"; do
    if [ "${REMOTE_STATE[$code]:-}" = "NOUVEAU" ] && [ "$ALLOW_NEW" -eq 0 ]; then
        die "$code : absent de la prod. C'est un nouveau formulaire — créez-le via
    l'interface web (choix du slug + publication), puis relancez. Pour ne
    pousser QUE les fichiers malgré tout : --allow-new."
    fi
done

# --------------------------------------------------------------------------
# Transfert
# --------------------------------------------------------------------------
RSYNC_OPTS=(-rzi --delete --exclude='.htaccess'
            --omit-dir-times --no-perms --no-owner --no-group
            -e "$RSYNC_RSH")
if [ "$DRY_RUN" -eq 1 ]; then RSYNC_OPTS+=(--dry-run); fi

TS="$(date +%Y%m%d-%H%M%S)"
FAILED=()

for code in "${CODES[@]}"; do
    printf '\n'
    info "== $code =="

    if [ "$DO_CHECK" -eq 1 ]; then
        ( cd "$REPO_ROOT" && check_html "$code" ) || { warn "$code : HTML non validé — ignoré"; FAILED+=("$code"); continue; }
    fi

    # Sauvegarde distante
    if [ "$DO_BACKUP" -eq 1 ] && [ "$DRY_RUN" -eq 0 ] && [ "${REMOTE_STATE[$code]:-}" = "EXISTE" ]; then
        if "${SSH_CMD[@]}" "$PROD_HOST" \
              "mkdir -p ~/gvv-form-backups && cd '$REMOTE_FORMS' && tar czf ~/gvv-form-backups/${code}_${TS}.tgz '$code'"; then
            ok "  sauvegarde : ~/gvv-form-backups/${code}_${TS}.tgz"
        else
            warn "  sauvegarde distante impossible"
        fi
    fi

    # rsync du répertoire du formulaire
    if rsync "${RSYNC_OPTS[@]}" "$FORMS_DIR/$code/" "$PROD_HOST:$REMOTE_FORMS/$code/"; then
        if [ "$DRY_RUN" -eq 1 ]; then ok "  (dry-run) OK"; else ok "  transféré"; fi
    else
        warn "$code : rsync en échec"
        FAILED+=("$code")
        continue
    fi

    # Resynchronisation base (optionnelle, requiert forms_cli)
    if [ "$DO_SYNC" -eq 1 ] && [ "$DRY_RUN" -eq 0 ]; then
        if "${SSH_CMD[@]}" "$PROD_HOST" "cd '$PROD_PATH' && php index.php forms_cli sync '$code'"; then
            ok "  base resynchronisée (forms_cli sync)"
        else
            warn "  forms_cli sync en échec (contrôleur absent ? métadonnées non propagées)"
            FAILED+=("$code")
        fi
    fi
done

# --------------------------------------------------------------------------
# Bilan
# --------------------------------------------------------------------------
printf '\n'
if [ "${#FAILED[@]}" -eq 0 ]; then
    ok "== terminé : ${#CODES[@]} formulaire(s) OK =="
else
    warn "== terminé avec erreurs : ${FAILED[*]} =="
fi

if [ "$DRY_RUN" -eq 0 ] && [ "$DO_SYNC" -eq 0 ] && [ "${#FAILED[@]}" -eq 0 ]; then
    cat <<EOF

Rappel : seuls le contenu des pages, le CSS et les images sont propagés.
Si tu as changé le nombre de pages, un titre de page ou une métadonnée du
formulaire, la base de la prod est encore périmée — relance avec --sync
(contrôleur forms_cli) ou fais un export/import via l'interface web.
EOF
fi

[ "${#FAILED[@]}" -eq 0 ]
