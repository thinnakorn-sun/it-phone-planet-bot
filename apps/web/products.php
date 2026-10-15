<?php

declare(strict_types=1);

/**
 * หน้ารายการสินค้าสาธารณะถูกปิดแล้ว
 * ลูกค้าเลือกสินค้าผ่าน LINE เท่านั้น — แอดมินจัดการที่ apps/admin/products.php
 */
http_response_code(404);
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ไม่พบหน้า</title>
</head>
<body style="font-family:sans-serif;padding:32px;text-align:center;color:#334155">
    <h1>ไม่พบหน้านี้</h1>
    <p>รายการสินค้าไม่เปิดให้ลูกค้าดูผ่านเว็บ กรุณาเลือกสินค้าผ่านแชท LINE</p>
</body>
</html>
