# packages/core

โค้ดกลางของระบบ (ใช้ร่วมกันโดย `apps/web` และ `apps/admin`)

```text
src/
├── Auth.php
├── Bootstrap.php
├── Database.php
├── Env.php
├── Helpers.php
├── Line/           # LINE client + webhook
├── Repositories/   # เข้าถึง MySQL
└── Services/       # logic ออเดอร์ / OCR
```

แอปทั้งสองโหลดผ่าน `bootstrap.php` ที่รากโปรเจกต์เท่านั้น
