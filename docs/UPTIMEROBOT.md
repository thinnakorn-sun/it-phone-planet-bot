# UptimeRobot สำหรับโปรเจกต์นี้

ใช้มอนิเตอร์หน้า `health.php` เพื่อเช็กว่าระบบยังตอบได้  
เหมาะตอนมี **URL สาธารณะ** (Cloudflare Tunnel / ngrok / hosting จริง)

## URL ที่ควรเฝ้า

```text
https://<public-host>/it-phone-planet-bot/apps/web/health.php
```

ไม่ต้องมอนิเตอร์ `webhook.php` แบบ GET ก็ได้ เพราะ health ครอบคลุม DB + สถานะระบบ

## ตั้งค่าแบบมือในเว็บ (แนะนำถ้ายังไม่มี API key)

1. สมัคร/เข้า [UptimeRobot](https://uptimerobot.com/)
2. กด **Add New Monitor**
3. ตั้งค่า:
   - Monitor Type: `HTTP(s)`
   - Friendly Name: `iT Phone Planet Health`
   - URL: ใส่ health.php ตามด้านบน
   - Monitoring Interval: `5 minutes`
4. Save

## ตั้งค่าแบบสคริปต์

1. ไปที่ Dashboard → My Settings → API Settings  
2. สร้าง **Main API Key**
3. รัน:

```bash
export UPTIMEROBOT_API_KEY='ใส่คีย์'
bash scripts/setup-uptimerobot.sh "$UPTIMEROBOT_API_KEY" \
  'https://<public-host>/it-phone-planet-bot/apps/web/health.php'
```

## หมายเหตุสำคัญ

- ถ้าใช้ Cloudflare Quick Tunnel / ngrok ฟรี URL **เปลี่ยนทุกครั้งที่เปิดใหม่**  
  ตอน URL เปลี่ยน ต้องอัปเดตทั้ง LINE Webhook และ UptimeRobot
- เครื่อง Mac ต้องเปิด Apache + tunnel ทิ้งไว้ ระบบถึงจะออนไลน์
- สำหรับส่งมอบจริง แนะนำย้ายไป hosting คงที่ แล้วค่อยผูก UptimeRobot ถาวร
