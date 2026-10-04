<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use App\Auth;
use App\Helpers;
use App\Repositories\ProductRepository;

Auth::requireLogin();
$repo = new ProductRepository();
$categories = $repo->allCategories();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    $data = [
        'category_id' => (int) ($_POST['category_id'] ?? 0),
        'sku' => trim((string) ($_POST['sku'] ?? '')),
        'name' => trim((string) ($_POST['name'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'price' => (float) ($_POST['price'] ?? 0),
        'stock' => (int) ($_POST['stock'] ?? 0),
        'image_url' => trim((string) ($_POST['image_url'] ?? '')),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];

    try {
        if ($data['name'] === '' || $data['category_id'] <= 0) {
            throw new RuntimeException('กรุณากรอกชื่อสินค้าและหมวดหมู่');
        }

        if ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            $repo->update($id, $data);
            $message = 'อัปเดตสินค้าแล้ว';
        } else {
            $repo->create($data);
            $message = 'เพิ่มสินค้าแล้ว';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$edit = $editId > 0 ? $repo->find($editId) : null;
$products = $repo->allForAdmin();

ob_start();
?>
<h1>จัดการสินค้า</h1>
<?php if ($message): ?><p class="ok"><?= Helpers::e($message) ?></p><?php endif; ?>
<?php if ($error): ?><p class="error"><?= Helpers::e($error) ?></p><?php endif; ?>

<div class="panel" style="margin-bottom:20px">
    <h2><?= $edit ? 'แก้ไขสินค้า' : 'เพิ่มสินค้าใหม่' ?></h2>
    <form method="post">
        <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>"><?php endif; ?>
        <div class="grid-2">
            <div>
                <label>หมวดหมู่</label>
                <select name="category_id" required>
                    <option value="">-- เลือก --</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= $edit && (int) $edit['category_id'] === (int) $category['id'] ? 'selected' : '' ?>>
                            <?= Helpers::e($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>SKU</label>
                <input type="text" name="sku" value="<?= Helpers::e($edit['sku'] ?? '') ?>">
            </div>
        </div>
        <label>ชื่อสินค้า</label>
        <input type="text" name="name" required value="<?= Helpers::e($edit['name'] ?? '') ?>">
        <label>รายละเอียด</label>
        <textarea name="description" rows="3"><?= Helpers::e($edit['description'] ?? '') ?></textarea>
        <div class="grid-2">
            <div>
                <label>ราคา</label>
                <input type="number" step="0.01" name="price" required value="<?= Helpers::e((string) ($edit['price'] ?? '0')) ?>">
            </div>
            <div>
                <label>คงเหลือ</label>
                <input type="number" name="stock" required value="<?= Helpers::e((string) ($edit['stock'] ?? '0')) ?>">
            </div>
        </div>
        <label>ลิงก์รูปภาพ (ถ้ามี)</label>
        <input type="url" name="image_url" value="<?= Helpers::e($edit['image_url'] ?? '') ?>">
        <label><input type="checkbox" name="is_active" <?= !$edit || (int) $edit['is_active'] === 1 ? 'checked' : '' ?>"> เปิดขาย</label>
        <p><button type="submit"><?= $edit ? 'บันทึกการแก้ไข' : 'เพิ่มสินค้า' ?></button>
        <?php if ($edit): ?> <a class="btn btn-secondary" href="products.php">ยกเลิก</a><?php endif; ?></p>
    </form>
</div>

<div class="panel">
    <h2>รายการสินค้า</h2>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>สินค้า</th>
            <th>หมวด</th>
            <th>ราคา</th>
            <th>สต๊อก</th>
            <th>สถานะ</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $product): ?>
            <tr>
                <td><?= (int) $product['id'] ?></td>
                <td><?= Helpers::e($product['name']) ?></td>
                <td><?= Helpers::e($product['category_name']) ?></td>
                <td><?= Helpers::e(Helpers::money($product['price'])) ?></td>
                <td><?= (int) $product['stock'] ?></td>
                <td><?= (int) $product['is_active'] ? 'เปิด' : 'ปิด' ?></td>
                <td><a href="products.php?edit=<?= (int) $product['id'] ?>">แก้ไข</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
$title = 'สินค้า';
$active = 'products';
require __DIR__ . '/_layout.php';
