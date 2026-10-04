# apps

| แอป | หน้าที่ | URL จาก `.env` |
|---|---|---|
| `web` | Webhook LINE, install, ดูสลิป | `APP_URL` |
| `admin` | หลังบ้านผู้ดูแล | `ADMIN_URL` |

ทั้งสองแอป `require` ไฟล์ `/bootstrap.php` แล้วใช้โค้ดจาก `packages/core`
