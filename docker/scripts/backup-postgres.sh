#!/usr/bin/env bash
# Dumps the PostgreSQL database from a running compose stack and rotates old backups.
# Run from the directory containing the target compose file (dev or prod).
set -euo pipefail

COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"
BACKUP_DIR="${BACKUP_DIR:-./backups}"
RETENTION_DAYS="${RETENTION_DAYS:-14}"

mkdir -p "$BACKUP_DIR"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
DUMP_FILE="$BACKUP_DIR/summit-stats-${TIMESTAMP}.sql.gz"

# Credentials are read inside the postgres container, which already has them (env_file in prod, .env in dev)
docker compose -f "$COMPOSE_FILE" exec -T postgres \
    sh -c 'pg_dump -U "$POSTGRES_USER" "$POSTGRES_DB"' | gzip > "$DUMP_FILE"

echo "Backup written to $DUMP_FILE"

find "$BACKUP_DIR" -name 'summit-stats-*.sql.gz' -mtime +"$RETENTION_DAYS" -delete
