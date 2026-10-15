-- สมาชิกร้าน: เข้าสู่ระบบ / สมัครก่อนสั่งซื้อ
USE `it_phone_planet`;

ALTER TABLE `customers`
  ADD COLUMN `email` VARCHAR(190) NULL AFTER `display_name`,
  ADD COLUMN `password_hash` VARCHAR(255) NULL AFTER `email`,
  ADD COLUMN `is_member` TINYINT(1) NOT NULL DEFAULT 0 AFTER `password_hash`,
  ADD COLUMN `member_name` VARCHAR(255) NULL AFTER `is_member`,
  ADD COLUMN `last_login_at` DATETIME NULL AFTER `member_name`,
  ADD UNIQUE KEY `uk_customers_email` (`email`);
