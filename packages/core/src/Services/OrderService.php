<?php

declare(strict_types=1);

namespace App\Services;

use App\Env;
use App\Helpers;
use App\Line\LineClient;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;

final class OrderService
{
    private OrderRepository $orders;
    private ProductRepository $products;
    private LineClient $line;

    public function __construct(
        ?OrderRepository $orders = null,
        ?ProductRepository $products = null,
        ?LineClient $line = null
    ) {
        $this->orders = $orders ?? new OrderRepository();
        $this->products = $products ?? new ProductRepository();
        $this->line = $line ?? new LineClient();
    }

    public function createOrder(
        int $customerId,
        int $productId,
        int $quantity,
        string $shippingName,
        string $shippingPhone,
        string $shippingAddress
    ): array {
        $product = $this->products->find($productId);
        if (!$product || !(int) $product['is_active']) {
            throw new \RuntimeException('ไม่พบสินค้านี้ในระบบ');
        }

        if ((int) $product['stock'] < $quantity) {
            throw new \RuntimeException('สินค้าคงเหลือไม่พอสำหรับการสั่งซื้อ');
        }

        $unitPrice = (float) $product['price'];
        $lineTotal = $unitPrice * $quantity;
        $orderCode = Helpers::orderCode();

        $orderId = $this->orders->create(
            [
                'order_code' => $orderCode,
                'customer_id' => $customerId,
                'status' => 'pending_payment',
                'total_amount' => $lineTotal,
                'shipping_name' => $shippingName,
                'shipping_phone' => $shippingPhone,
                'shipping_address' => $shippingAddress,
            ],
            [[
                'product_id' => $productId,
                'product_name' => $product['name'],
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'line_total' => $lineTotal,
            ]]
        );

        $order = $this->orders->find($orderId);
        $this->notifyAdmins(
            "มีคำสั่งซื้อใหม่ {$orderCode}\n" .
            "สินค้า: {$product['name']} x{$quantity}\n" .
            'ยอดรวม: ' . Helpers::money($lineTotal)
        );

        return $order;
    }

    public function paymentInstructions(array $order): string
    {
        $bank = Env::get('PAYMENT_BANK_NAME', '-') ?? '-';
        $name = Env::get('PAYMENT_ACCOUNT_NAME', '-') ?? '-';
        $number = Env::get('PAYMENT_ACCOUNT_NUMBER', '-') ?? '-';
        $note = Env::get('PAYMENT_NOTE', '') ?? '';

        return "สรุปคำสั่งซื้อ {$order['order_code']}\n" .
            'ยอดชำระ: ' . Helpers::money($order['total_amount']) . "\n\n" .
            "โอนเงินได้ที่\n" .
            "ธนาคาร: {$bank}\n" .
            "ชื่อบัญชี: {$name}\n" .
            "เลขบัญชี: {$number}\n\n" .
            ($note !== '' ? $note . "\n\n" : '') .
            'หลังจากโอนแล้ว ส่งรูปสลิปมาในแชทนี้ได้เลยค่ะ';
    }

    public function attachSlip(int $orderId, string $messageId, string $binaryContent): array
    {
        $order = $this->orders->find($orderId);
        if (!$order) {
            throw new \RuntimeException('ไม่พบคำสั่งซื้อ');
        }

        $ext = 'jpg';
        $filename = $order['order_code'] . '_' . time() . '.' . $ext;
        $relative = 'storage/slips/' . $filename;
        $absolute = Helpers::basePath($relative);
        if (file_put_contents($absolute, $binaryContent) === false) {
            throw new \RuntimeException('บันทึกสลิปไม่สำเร็จ');
        }

        $ocrText = null;
        $ocrAmount = null;
        if (Env::bool('OCR_ENABLED', false)) {
            $ocr = (new SlipOcrService())->analyze($absolute);
            $ocrText = $ocr['text'] ?? null;
            $ocrAmount = $ocr['amount'] ?? null;
        }

        $this->orders->addPayment($orderId, $relative, $messageId, $ocrText, $ocrAmount);
        $this->orders->updateStatus($orderId, 'awaiting_review');

        $this->notifyAdmins(
            "มีสลิปใหม่ของออเดอร์ {$order['order_code']}\n" .
            'ยอดออเดอร์: ' . Helpers::money($order['total_amount']) .
            ($ocrAmount !== null ? "\nOCR อ่านได้: " . Helpers::money($ocrAmount) : '') .
            "\nกรุณาตรวจในหน้าแอดมิน"
        );

        return $this->orders->find($orderId) ?? $order;
    }

    public function approvePayment(int $orderId, int $adminId): array
    {
        $order = $this->orders->find($orderId);
        if (!$order) {
            throw new \RuntimeException('ไม่พบคำสั่งซื้อ');
        }

        $payment = $this->orders->latestPayment($orderId);
        if (!$payment) {
            throw new \RuntimeException('ยังไม่มีสลิปในออเดอร์นี้');
        }

        $items = $this->orders->items($orderId);
        foreach ($items as $item) {
            if (!empty($item['product_id'])) {
                $product = $this->products->find((int) $item['product_id']);
                if ($product && (int) $product['stock'] < (int) $item['quantity']) {
                    throw new \RuntimeException('สต๊อกไม่พอ ไม่สามารถอนุมัติได้');
                }
            }
        }

        foreach ($items as $item) {
            if (!empty($item['product_id'])) {
                $this->products->adjustStock((int) $item['product_id'], -1 * (int) $item['quantity']);
            }
        }

        $this->orders->reviewPayment((int) $payment['id'], 'approved', $adminId);
        $this->orders->updateStatus($orderId, 'paid');

        $updated = $this->orders->find($orderId);
        if ($updated && $this->line->isConfigured()) {
            $this->line->push($updated['line_user_id'], [[
                'type' => 'text',
                'text' => "ชำระเงินออเดอร์ {$updated['order_code']} ผ่านการตรวจสอบแล้วค่ะ\nร้านกำลังเตรียมจัดส่งให้ต่อไป",
            ]]);
        }

        return $updated ?? $order;
    }

    public function rejectPayment(int $orderId, int $adminId, string $reason = ''): array
    {
        $order = $this->orders->find($orderId);
        if (!$order) {
            throw new \RuntimeException('ไม่พบคำสั่งซื้อ');
        }

        $payment = $this->orders->latestPayment($orderId);
        if ($payment) {
            $this->orders->reviewPayment((int) $payment['id'], 'rejected', $adminId);
        }

        $this->orders->updateStatus($orderId, 'rejected', $reason !== '' ? $reason : 'สลิปไม่ผ่านการตรวจสอบ');

        $updated = $this->orders->find($orderId);
        if ($updated && $this->line->isConfigured()) {
            $msg = "สลิปออเดอร์ {$updated['order_code']} ไม่ผ่านการตรวจสอบค่ะ";
            if ($reason !== '') {
                $msg .= "\nเหตุผล: {$reason}";
            }
            $msg .= "\nกรุณาส่งสลิปใหม่ หรือพิมพ์ \"แอดมิน\" เพื่อคุยกับพนักงาน";
            $this->line->push($updated['line_user_id'], [['type' => 'text', 'text' => $msg]]);
        }

        return $updated ?? $order;
    }

    public function setTracking(int $orderId, string $trackingNumber): array
    {
        $order = $this->orders->find($orderId);
        if (!$order) {
            throw new \RuntimeException('ไม่พบคำสั่งซื้อ');
        }

        $this->orders->setTracking($orderId, $trackingNumber);
        $updated = $this->orders->find($orderId);

        if ($updated && $this->line->isConfigured()) {
            $this->line->push($updated['line_user_id'], [[
                'type' => 'text',
                'text' => "ออเดอร์ {$updated['order_code']} จัดส่งแล้วค่ะ\nเลขพัสดุ: {$trackingNumber}\nขอบคุณที่ใช้บริการ iT Phone Planet ค่ะ",
            ]]);
        }

        return $updated ?? $order;
    }

    public function statusText(array $order): string
    {
        $map = [
            'pending_payment' => 'รอชำระเงิน',
            'awaiting_review' => 'รอแอดมินตรวจสอบสลิป',
            'paid' => 'ชำระเงินแล้ว กำลังเตรียมส่ง',
            'shipping' => 'กำลังจัดส่ง',
            'completed' => 'สำเร็จ',
            'cancelled' => 'ยกเลิก',
            'rejected' => 'สลิปไม่ผ่าน',
        ];

        $status = $map[$order['status']] ?? $order['status'];
        $text = "ออเดอร์ {$order['order_code']}\nสถานะ: {$status}\nยอดรวม: " . Helpers::money($order['total_amount']);
        if (!empty($order['tracking_number'])) {
            $text .= "\nเลขพัสดุ: {$order['tracking_number']}";
        }

        return $text;
    }

    private function notifyAdmins(string $message): void
    {
        $adminIds = Env::list('LINE_ADMIN_USER_IDS');
        if ($adminIds === [] || !$this->line->isConfigured()) {
            return;
        }

        try {
            $this->line->multicast($adminIds, [['type' => 'text', 'text' => $message]]);
        } catch (\Throwable $e) {
            Helpers::log('notifyAdmins failed: ' . $e->getMessage());
        }
    }
}
