#!/usr/bin/env bash
# Dumps the PostgreSQL database from a running compose stack and rotates old backups.
# Run from the directory containing the target compose file (dev or prod).
set -euo pipefail

COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"
ENV_FILE="${ENV_FILE:-.env.prod}"
BACKUP_DIR="${BACKUP_DIR:-./backups}"
RETENTION_DAYS="${RETENTION_DAYS:-14}"

set -a
# shellcheck disable=SC1090
source "$ENV_FILE"
set +a

mkdir -p "$BACKUP_DIR"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
DUMP_FILE="$BACKUP_DIR/summit-stats-${TIMESTAMP}.sql.gz"

docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" exec -T postgres \
    pg_dump -U "$DB_USERNAME" "$DB_DATABASE" | gzip > "$DUMP_FILE"

echo "Backup written to $DUMP_FILE"

find "$BACKUP_DIR" -name 'summit-stats-*.sql.gz' -mtime +"$RETENTION_DAYS" -delete
