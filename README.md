# iT Phone Planet — LINE Chatbot (Monorepo)

ระบบแชทบอตสนับสนุนการขายผ่าน LINE OA + หลังบ้านแอดมิน  
สแต็กตามเอกสารโครงงาน: **PHP + MySQL (XAMPP)**

ค่าคอนฟิกทั้งหมดอ่านจาก `.env` เท่านั้น ไม่ hardcode คีย์/รหัสผ่าน/URL ในโค้ด

## โครงสร้างแบบ Monorepo

```text
it-phone-planet-bot/
├── apps/
│   ├── web/                 # แอปสาธารณะ: webhook, install, ดูสลิป
│   └── admin/               # แอปหลังบ้านผู้ดูแล
├── packages/
│   └── core/src/            # โค้ดกลางใช้ร่วมกัน (DB, LINE, Services)
├── database/                # schema.sql + seed.sql
├── storage/                 # slips / logs (ไม่ให้เข้าตรงจากเว็บ)
├── bootstrap.php            # จุดโหลดระบบกลางเพียงจุดเดียว
├── .env.example
└── README.md
```

อ่านยังไงให้เร็ว:

| อยากแก้ | ไปที่ |
|---|---|
| บอท LINE / webhook | `apps/web/` + `packages/core/src/Line/` |
| หน้าแอดมิน | `apps/admin/` |
| ธุรกิจออเดอร์/สลิป/สต๊อก | `packages/core/src/Services/` |
| ตาราง DB | `database/` |
| คีย์และค่าคอนฟิก | `.env` |

## ความต้องการ

- XAMPP (Apache + MySQL + PHP 8.0+)
- PHP extensions: `pdo_mysql`, `curl`, `mbstring`, `json`
- ngrok สำหรับทดสอบ webhook

## ติดตั้งเร็วๆ

### 1) วางโปรเจกต์ใน htdocs

ตัวอย่าง path:

`/Applications/XAMPP/xamppfiles/htdocs/it-phone-planet-bot`

### 2) ตั้งค่า `.env`

```bash
cp .env.example .env
```

ค่าสำคัญ:

```env
APP_URL=http://localhost/it-phone-planet-bot/apps/web
ADMIN_URL=http://localhost/it-phone-planet-bot/apps/admin
ASSET_URL=http://localhost/it-phone-planet-bot/apps/web/assets

DB_HOST=127.0.0.1
DB_NAME=it_phone_planet
DB_USER=root
DB_PASS=

LINE_CHANNEL_SECRET=
LINE_CHANNEL_ACCESS_TOKEN=

ADMIN_USERNAME=admin
ADMIN_PASSWORD=ChangeMe123!
```

### 3) Import ฐานข้อมูล

ใน phpMyAdmin import ตามลำดับ:

1. `database/schema.sql`
2. `database/seed.sql`

### 4) สร้างแอดมินจาก `.env`

เปิด:

`http://localhost/it-phone-planet-bot/apps/web/install.php`

แล้วเข้าแอดมินที่:

`http://localhost/it-phone-planet-bot/apps/admin/login.php`

### 5) ต่อ LINE Webhook

Webhook URL:

`https://<ngrok>/it-phone-planet-bot/apps/web/webhook.php`

ใส่ `LINE_CHANNEL_SECRET` และ `LINE_CHANNEL_ACCESS_TOKEN` ใน `.env`

## ฟีเจอร์หลัก

- ลูกค้า: FAQ, ดูสินค้า, สั่งซื้อ, แนบสลิป, เช็คสถานะ/เลขพัสดุ, ส่งต่อแอดมิน
- แอดมิน: จัดการสินค้า/FAQ, ตรวจสลิป, กรอกเลขพัสดุ, ตัดสต๊อกเมื่ออนุมัติ
- OCR สลิป: เตรียมไว้ ปิดเป็นค่าเริ่มต้น (`OCR_ENABLED=false`)

## คำสั่งบอท

| พิมพ์ | ความหมาย |
|---|---|
| เมนู | เมนูหลัก |
| สินค้า | เลือกหมวด |
| สินค้า {id} | รายละเอียด |
| สั่งซื้อ {id} | เริ่มสั่งซื้อ |
| สถานะ | ออเดอร์ล่าสุด |
| วิธีสั่ง | FAQ สั่งซื้อ |
| แอดมิน | ส่งต่อพนักงาน |

## หมายเหตุ

- อย่า commit ไฟล์ `.env`
- หลังติดตั้งจริงควรปิด/ลบ `apps/web/install.php`
- Rich Menu รูปสวยตั้งใน LINE OA Manager ทีหลังได้
