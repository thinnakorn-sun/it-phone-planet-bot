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

เครื่อง local ต้องมี public URL เช่น ngrok:

```bash
ngrok http 80
```

แล้วตั้ง Webhook URL ใน LINE Developers เป็น:

```text
https://<ngrok-id>.ngrok-free.app/it-phone-planet-bot/apps/web/webhook.php
```

จากนั้น:
- เปิด **Use webhook**
- กด **Verify**
- ปิด **Auto-reply messages** / **Greeting messages** ของ LINE OA ที่ชนกับบอท (ถ้ามี)

## 4) เทสเร็วๆ ใน LINE

พิมพ์ตามนี้:
- `เมนู`
- `สินค้า`
- `วิธีสั่ง`
- `สถานะ`
- `แอดมิน`

## 5) เช็กสถานะระบบ

- Health: `http://localhost/it-phone-planet-bot/apps/web/health.php`
- Webhook info: `http://localhost/it-phone-planet-bot/apps/web/webhook.php`
- แอดมิน: `http://localhost/it-phone-planet-bot/apps/admin/login.php`
