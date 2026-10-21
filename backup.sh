
#!/usr/bin/env bash
set -Eeuo pipefail

PROJECT_DIR="$HOME/inventaris-dashboard"
BACKUP_DIR="$PROJECT_DIR/backups"
RETENTION_DAYS=7

mkdir -p "$BACKUP_DIR"
cd "$PROJECT_DIR"

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/inventaris_${TIMESTAMP}.sql"
TEMP_FILE="${BACKUP_FILE}.tmp"

cleanup() {
    rm -f "$TEMP_FILE"
}
trap cleanup EXIT

docker compose exec -T mysql sh -c '
  set -eu
  MYSQL_CNF=$(mktemp)
  trap '\''rm -f "$MYSQL_CNF"'\'' EXIT
  chmod 600 "$MYSQL_CNF"

  printf "[client]\nuser=%s\npassword=%s\n" \
    "$MYSQL_USER" "$MYSQL_PASSWORD" > "$MYSQL_CNF"

  exec mysqldump --defaults-extra-file="$MYSQL_CNF" \
    --no-tablespaces "$MYSQL_DATABASE"
' > "$TEMP_FILE"

test -s "$TEMP_FILE"

if ! grep -q -- '-- Dump completed on' "$TEMP_FILE"; then
    echo "ERROR: Penanda akhir backup tidak ditemukan." >&2
    exit 1
fi

mv "$TEMP_FILE" "$BACKUP_FILE"
trap - EXIT

echo "Backup berhasil: $BACKUP_FILE"
ls -lh "$BACKUP_FILE"

# Hapus backup yang berusia lebih dari 7 hari.
find "$BACKUP_DIR" -maxdepth 1 -type f \
  -name 'inventaris_*.sql' \
  -mmin +$((RETENTION_DAYS * 24 * 60)) \
  -print -delete

echo "Pembersihan backup lama selesai."


