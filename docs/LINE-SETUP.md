# ต่อ LINE (ทำทีหลังได้)

ระบบฝั่งโค้ด/DB พร้อมแล้ว เหลือใส่คีย์และชี้ Webhook

## 1) สิ่งที่ต้องมีจาก LINE

1. สร้าง/เปิด **LINE Official Account**
2. เข้า [LINE Developers](https://developers.line.biz/) สร้าง **Messaging API** channel
3. คัดลอก:
   - `Channel secret`
   - `Channel access token` (Long-lived)

## 2) ใส่ในไฟล์ `.env`

แก้ที่โฟลเดอร์โปรเจกต์หลัก แล้วรัน sync:

```env
LINE_CHANNEL_SECRET=ใส่ตรงนี้
LINE_CHANNEL_ACCESS_TOKEN=ใส่ตรงนี้
LINE_ADMIN_USER_IDS=Uxxxxxxxx
```

`LINE_ADMIN_USER_IDS` = LINE User ID ของแอดมิน เพื่อรับแจ้งเตือนออเดอร์/สลิป  
หาได้จาก webhook log ตอนแอดมินทักบอทครั้งแรก หรือใช้เครื่องมือ get profile

หลังแก้ `.env` รัน:

```bash
bash scripts/sync-to-xampp.sh
```

## 3) เปิด Webhook ให้ LINE เข้าถึงเครื่องคุณได้

เครื่อง local ต้องมี public URL

### วิธีที่ใช้ตอนนี้: Cloudflare Tunnel

```bash
cloudflared tunnel --url http://127.0.0.1:80
```

คัดลอก URL ที่ได้ แล้วตั้ง Webhook ใน LINE Developers เป็น:

```text
https://<tunnel-id>.trycloudflare.com/it-phone-planet-bot/apps/web/webhook.php
```

### หรือใช้ ngrok (ต้อง login ก่อน)

```bash
ngrok config add-authtoken <TOKEN>
ngrok http 80
```

จากนั้นใน LINE Developers:
- เปิด **Use webhook**
- วาง Webhook URL
- กด **Verify**
- ปิด **Auto-reply messages** / **Greeting messages** ของ LINE OA ที่ชนกับบอท

## 4) เทสเร็วๆ ใน LINE

พิมพ์ตามนี้:
- `สวัสดี` หรือ `เมนู` → จะได้ปุ่มเข้าสู่ระบบ / สมัคร / เลือกซื้อสินค้า
- สมัครหรือเข้าสู่ระบบก่อน แล้วค่อยกด `เลือกซื้อสินค้า`
- `สถานะ` / `ประวัติการสั่งซื้อ` / `บัญชีของฉัน`
- ส่งรูปสลิปหลังสั่งซื้อแล้ว
- `แอดมิน`

คู่มือติดตั้งเต็ม: `docs/คู่มือติดตั้งและใช้งาน.md`

## 5) เช็กสถานะระบบ

- Health: `http://localhost/it-phone-planet-bot/apps/web/health.php`
- Webhook info: `http://localhost/it-phone-planet-bot/apps/web/webhook.php`
- แอดมิน: `http://localhost/it-phone-planet-bot/apps/admin/login.php`
