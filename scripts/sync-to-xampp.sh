#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DEST="/Applications/XAMPP/xamppfiles/htdocs/it-phone-planet-bot"

if [[ ! -d "/Applications/XAMPP/xamppfiles/htdocs" ]]; then
  echo "ไม่พบ XAMPP htdocs: /Applications/XAMPP/xamppfiles/htdocs"
  exit 1
fi

mkdir -p "$DEST"
rsync -a --delete \
  --exclude='.git/' \
  --exclude='storage/logs/*' \
  --exclude='storage/slips/*' \
  --include='storage/logs/.gitkeep' \
  --include='storage/slips/.gitkeep' \
  "$ROOT/" "$DEST/"

# Apache (user: daemon) ต้องเขียนสลิป/log ได้ — ไม่งั้นรับสลิปแล้วเงียบและสถานะไม่อัปเดต
mkdir -p "$DEST/storage/slips" "$DEST/storage/logs" "$ROOT/storage/slips" "$ROOT/storage/logs"
chmod -R a+rwX "$DEST/storage" "$ROOT/storage" || true
chmod 777 "$DEST/storage/slips" "$DEST/storage/logs" "$ROOT/storage/slips" "$ROOT/storage/logs" || true

echo "Synced:"
echo "  from: $ROOT"
echo "  to:   $DEST"
echo
echo "URLs:"
echo "  http://localhost/it-phone-planet-bot/apps/web/"
echo "  http://localhost/it-phone-planet-bot/apps/admin/login.php"
echo "  http://localhost/it-phone-planet-bot/apps/web/health.php"
