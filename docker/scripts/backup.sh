#!/usr/bin/env bash
# Weekly backup of the database and the uploaded GPX files, stored only when something changed.
#
# - Changed since the last stored backup: writes BACKUP_DIR/summit-stats-<timestamp>/
#   (db.sql.gz, gpx.tar.gz, fingerprint), keeps the BACKUP_KEEP newest, mails the scp command
#   to fetch it.
# - Unchanged: stores nothing; mails a "still alive" notice once no mail went out for
#   BACKUP_ALIVE_DAYS days, so a silent week never hides a broken job.
# - Any failure: mails the error and exits non-zero.
# - --test-mail: sends a test mail with the current settings and exits, no backup.
#
# Run from the directory holding the compose file. Mail settings come from BACKUP_ENV_FILE
# (host-side only, never read by the containers), see docker/env/backup.env.example.
set -Eeuo pipefail

COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"
BACKUP_DIR="${BACKUP_DIR:-../shared/backups}"
BACKUP_ENV_FILE="${BACKUP_ENV_FILE:-../shared/env/backup.env}"
BACKUP_KEEP="${BACKUP_KEEP:-8}"
BACKUP_ALIVE_DAYS="${BACKUP_ALIVE_DAYS:-28}"
GPX_PARENT=/app/storage/app/private

# KEY=value lines like the compose env files (no quoting needed, nothing is evaluated);
# values already set in the environment win
if [ -f "$BACKUP_ENV_FILE" ]; then
    while IFS= read -r line || [ -n "$line" ]; do
        case "$line" in '' | '#'*) continue ;; esac
        key="${line%%=*}"
        value="${line#*=}"
        value="${value%\"}"
        value="${value#\"}"
        [ -n "${!key:-}" ] || export "$key=$value"
    done < "$BACKUP_ENV_FILE"
fi

SMTP_HOST="${SMTP_HOST:-smtp-relay.brevo.com}"
SMTP_PORT="${SMTP_PORT:-587}"
BACKUP_SSH_TARGET="${BACKUP_SSH_TARGET:-$(id -un)@$(hostname -f)}"

mkdir -p "$BACKUP_DIR"
BACKUP_DIR="$(cd "$BACKUP_DIR" && pwd)"
MAIL_MARKER="$BACKUP_DIR/.last-mail"
WORK_DIR="$(mktemp -d)"
trap 'rm -rf "$WORK_DIR"' EXIT

log() { echo "$(date '+%Y-%m-%d %H:%M:%S') $*"; }

# RFC 2047 encoded word, so names with accents survive any mail client
encode_header() { printf '=?UTF-8?B?%s?=' "$(printf '%s' "$1" | base64 | tr -d '\n')"; }

send_mail() {
    local subject="$1" body="$2" message="$WORK_DIR/mail.txt"
    for var in BACKUP_MAIL_FROM BACKUP_MAIL_TO SMTP_USERNAME SMTP_PASSWORD; do
        if [ -z "${!var:-}" ]; then
            log "mail not sent: $var is not set in $BACKUP_ENV_FILE"
            return 1
        fi
    done
    {
        printf 'From: %s <%s>\r\n' "$(encode_header "${BACKUP_MAIL_FROM_NAME:-Summit Stats backup}")" "$BACKUP_MAIL_FROM"
        printf 'To: %s <%s>\r\n' "$(encode_header "${BACKUP_MAIL_TO_NAME:-$BACKUP_MAIL_TO}")" "$BACKUP_MAIL_TO"
        printf 'Subject: %s\r\n' "$(encode_header "$subject")"
        printf 'Date: %s\r\n' "$(date -R)"
        printf 'MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n'
        printf '%s\n' "$body"
    } > "$message"
    # --ssl-reqd: STARTTLS is mandatory, credentials never travel in clear (BACKUP_SMTP_INSECURE=1 only for a local test relay)
    curl --silent --show-error --fail \
        $([ "${BACKUP_SMTP_INSECURE:-0}" = 1 ] || echo --ssl-reqd) \
        --url "smtp://$SMTP_HOST:$SMTP_PORT" \
        --user "$SMTP_USERNAME:$SMTP_PASSWORD" \
        --mail-from "$BACKUP_MAIL_FROM" --mail-rcpt "$BACKUP_MAIL_TO" \
        --upload-file "$message"
    touch "$MAIL_MARKER"
}

on_error() {
    local line="$1"
    trap - ERR
    log "backup failed at line $line"
    send_mail "Summit Stats backup FAILED" "The weekly backup failed on $(hostname -f) at line $line of backup.sh.

Check $BACKUP_DIR/backup.log." || true
    exit 1
}
trap 'on_error $LINENO' ERR

# --test-mail: checks the mail settings only (after a SMTP key change, for instance), no backup
if [ "${1:-}" = --test-mail ]; then
    send_mail "Summit Stats backup: test mail" "Mail settings work on $(hostname -f). No backup was made."
    log "test mail sent to $BACKUP_MAIL_TO"
    exit 0
fi

compose() { docker compose -f "$COMPOSE_FILE" "$@"; }

# 1. Database dump; pg_dump 16.10+ writes a random \restrict key, left out of the fingerprint
compose exec -T postgres sh -c 'pg_dump -U "$POSTGRES_USER" "$POSTGRES_DB"' > "$WORK_DIR/db.sql"
[ -s "$WORK_DIR/db.sql" ]

# 2. GPX files: content manifest for the fingerprint, archive for the backup
compose exec -T app sh -c "cd $GPX_PARENT && mkdir -p gpx && find gpx -type f -exec sha256sum {} + | sort" > "$WORK_DIR/gpx.manifest"
compose exec -T app tar -C "$GPX_PARENT" -czf - gpx > "$WORK_DIR/gpx.tar.gz"

FINGERPRINT="$({ grep -v '^\\\(un\)\{0,1\}restrict ' "$WORK_DIR/db.sql"; cat "$WORK_DIR/gpx.manifest"; } | sha256sum | cut -d' ' -f1)"
LATEST="$(find "$BACKUP_DIR" -maxdepth 1 -type d -name 'summit-stats-*' | sort | tail -n 1)"

if [ -n "$LATEST" ] && [ "$(cat "$LATEST/fingerprint" 2>/dev/null)" = "$FINGERPRINT" ]; then
    log "no change since $(basename "$LATEST"), nothing stored"
    if [ ! -f "$MAIL_MARKER" ] || [ -n "$(find "$MAIL_MARKER" -mtime +"$BACKUP_ALIVE_DAYS")" ]; then
        send_mail "Summit Stats backup: still alive, no change" "The weekly backup still runs on $(hostname -f).

No change since the last backup: $(basename "$LATEST")."
        log "still-alive mail sent"
    fi
    exit 0
fi

TARGET="$BACKUP_DIR/summit-stats-$(date +%Y%m%d-%H%M%S)"
mkdir "$TARGET.tmp"
gzip -c "$WORK_DIR/db.sql" > "$TARGET.tmp/db.sql.gz"
mv "$WORK_DIR/gpx.tar.gz" "$TARGET.tmp/gpx.tar.gz"
echo "$FINGERPRINT" > "$TARGET.tmp/fingerprint"
# the rename makes a half-written backup impossible to mistake for the latest one
mv "$TARGET.tmp" "$TARGET"
log "backup written to $TARGET"

# keep the BACKUP_KEEP newest backups (the one just written is always among them)
find "$BACKUP_DIR" -maxdepth 1 -type d -name 'summit-stats-*' | sort | head -n -"$BACKUP_KEEP" | xargs -r rm -rf

send_mail "Summit Stats backup: new backup to fetch" "A new backup was stored on $(hostname -f): $(basename "$TARGET")

Fetch it to your machine:

scp -r $BACKUP_SSH_TARGET:$TARGET ./

Contents: db.sql.gz (database), gpx.tar.gz (uploaded GPX files)."
log "new-backup mail sent"
