# iT Phone Planet — LINE Chatbot (Monorepo)

ระบบแชทบอตสนับสนุนการขายผ่าน LINE OA + หลังบ้านแอดมิน  
สแต็ก: **PHP + MySQL (XAMPP)** — ค่าคอนฟิกอ่านจาก `.env` เท่านั้น

## ส่งมอบลูกค้า / เริ่มใช้งาน

**ยังไม่ต้อง deploy ขึ้นเซิร์ฟเวอร์** — ติดตั้งบน XAMPP ของเครื่องที่สาธิตได้เลย

อ่านคู่มือฉบับเต็ม (ภาษาไทย):

- **[docs/คู่มือติดตั้งและใช้งาน.md](docs/คู่มือติดตั้งและใช้งาน.md)** ← เริ่มที่นี่
- [docs/LINE-SETUP.md](docs/LINE-SETUP.md) — ต่อ LINE Webhook / Tunnel

## ฟีเจอร์หลัก

- สมัครสมาชิก / เข้าสู่ระบบ ก่อนสั่งซื้อ
- เลือกสินค้า สั่งซื้อ แนบสลิป → สถานะชำระเงินแล้ว
- ประวัติออเดอร์ / บัญชี ดูได้เฉพาะของตัวเอง
- แอดมิน: สินค้า, ออเดอร์, สลิป, เลขพัสดุ

## โครงสร้าง

```text
it-phone-planet-bot/
├── apps/web/          # webhook, หน้าสั่งซื้อ, ล็อกอิน/สมัคร
├── apps/admin/        # หลังบ้าน
├── packages/core/     # โค้ดกลาง (LINE, ออเดอร์, สมาชิก)
├── database/          # schema + seed
├── docs/              # คู่มือ
└── .env.example
```

## ติดตั้งสรุป (รายละเอียดในคู่มือ)

```bash
cp .env.example .env
# แก้ค่า DB + LINE ใน .env

# Import: database/schema.sql แล้ว database/seed.sql
# เปิด: http://localhost/it-phone-planet-bot/apps/web/install.php

bash scripts/sync-to-xampp.sh   # ถ้าแก้โค้ดนอก htdocs
```

Webhook (หลังเปิด Cloudflare Tunnel):

```text
https://<tunnel>.trycloudflare.com/it-phone-planet-bot/apps/web/webhook.php
```

อย่า commit ไฟล์ `.env`
