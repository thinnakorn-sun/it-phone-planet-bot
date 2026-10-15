#!/usr/bin/env bash
set -euo pipefail

# สร้าง monitor ใน UptimeRobot ผ่าน API
# ต้องมี UPTIMEROBOT_API_KEY ใน env หรือส่งเป็น argument แรก
# เอกสาร API: https://uptimerobot.com/api/

API_KEY="${1:-${UPTIMEROBOT_API_KEY:-}}"
MONITOR_URL="${2:-}"

if [[ -z "$API_KEY" ]]; then
  cat <<'EOF'
ใช้แบบนี้:

  export UPTIMEROBOT_API_KEY='your-api-key'
  bash scripts/setup-uptimerobot.sh

หรือ:

  bash scripts/setup-uptimerobot.sh 'your-api-key' 'https://xxxx/it-phone-planet-bot/apps/web/health.php'

หา API Key ได้ที่:
  https://uptimerobot.com/dashboard#mySettings
EOF
  exit 1
fi

if [[ -z "$MONITOR_URL" ]]; then
  echo "กรุณาใส่ URL ที่จะมอนิเตอร์ เช่น health.php บน public tunnel/hosting"
  echo "ตัวอย่าง: https://xxxx.trycloudflare.com/it-phone-planet-bot/apps/web/health.php"
  exit 1
fi

echo "Creating UptimeRobot HTTP monitor for: $MONITOR_URL"

resp=$(curl -s -X POST https://api.uptimerobot.com/v2/newMonitor \
  -d "api_key=${API_KEY}" \
  -d "format=json" \
  -d "type=1" \
  -d "url=${MONITOR_URL}" \
  -d "friendly_name=iT Phone Planet Health" \
  -d "interval=300")

echo "$resp" | python3 - <<'PY'
import sys, json
raw = sys.stdin.read()
try:
    data = json.loads(raw)
except Exception:
    print(raw)
    raise SystemExit(1)
print('stat:', data.get('stat'))
print('message:', data.get('error', data.get('monitor', data)))
if data.get('stat') != 'ok':
    raise SystemExit(1)
print('monitor_id:', data.get('monitor', {}).get('id'))
PY
