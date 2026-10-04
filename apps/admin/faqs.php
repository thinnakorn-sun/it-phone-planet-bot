<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use App\Auth;
use App\Database;
use App\Helpers;

Auth::requireLogin();
$db = Database::connection();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $question = trim((string) ($_POST['question'] ?? ''));
    $keywords = trim((string) ($_POST['keywords'] ?? ''));
    $answer = trim((string) ($_POST['answer'] ?? ''));
    $sort = (int) ($_POST['sort_order'] ?? 0);
    $active = isset($_POST['is_active']) ? 1 : 0;

    if ($question !== '' && $keywords !== '' && $answer !== '') {
        if ($id > 0) {
            $stmt = $db->prepare(
                'UPDATE faqs SET question = ?, keywords = ?, answer = ?, sort_order = ?, is_active = ? WHERE id = ?'
            );
            $stmt->execute([$question, $keywords, $answer, $sort, $active, $id]);
            $message = 'อัปเดต FAQ แล้ว';
        } else {
            $stmt = $db->prepare(
                'INSERT INTO faqs (question, keywords, answer, sort_order, is_active) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$question, $keywords, $answer, $sort, $active]);
            $message = 'เพิ่ม FAQ แล้ว';
        }
    }
}

$faqs = $db->query('SELECT * FROM faqs ORDER BY sort_order ASC, id ASC')->fetchAll();
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$edit = null;
foreach ($faqs as $faq) {
    if ((int) $faq['id'] === $editId) {
        $edit = $faq;
        break;
    }
}

ob_start();
?>
<h1>FAQ / คำถามซ้ำ</h1>
<?php if ($message): ?><p class="ok"><?= Helpers::e($message) ?></p><?php endif; ?>

<div class="panel" style="margin-bottom:20px">
    <h2><?= $edit ? 'แก้ไข FAQ' : 'เพิ่ม FAQ' ?></h2>
    <form method="post">
        <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
        <label>หัวข้อ</label>
        <input type="text" name="question" required value="<?= Helpers::e($edit['question'] ?? '') ?>">
        <label>Keywords (คั่นด้วยคอมมา)</label>
        <input type="text" name="keywords" required value="<?= Helpers::e($edit['keywords'] ?? '') ?>">
        <label>คำตอบ</label>
        <textarea name="answer" rows="5" required><?= Helpers::e($edit['answer'] ?? '') ?></textarea>
        <label>ลำดับ</label>
        <input type="number" name="sort_order" value="<?= Helpers::e((string) ($edit['sort_order'] ?? '0')) ?>">
        <label><input type="checkbox" name="is_active" <?= !$edit || (int) $edit['is_active'] === 1 ? 'checked' : '' ?>"> ใช้งาน</label>
        <p><button type="submit">บันทึก</button></p>
    </form>
</div>

<div class="panel">
    <table>
        <thead>
        <tr><th>ID</th><th>หัวข้อ</th><th>Keywords</th><th>สถานะ</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($faqs as $faq): ?>
            <tr>
                <td><?= (int) $faq['id'] ?></td>
                <td><?= Helpers::e($faq['question']) ?></td>
                <td><?= Helpers::e($faq['keywords']) ?></td>
                <td><?= (int) $faq['is_active'] ? 'เปิด' : 'ปิด' ?></td>
                <td><a href="faqs.php?edit=<?= (int) $faq['id'] ?>">แก้ไข</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
$title = 'FAQ';
$active = 'faqs';
require __DIR__ . '/_layout.php';
