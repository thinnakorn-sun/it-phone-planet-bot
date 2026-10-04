<?php

declare(strict_types=1);

namespace App\Line;

use App\Helpers;
use App\Repositories\CustomerRepository;
use App\Repositories\FaqRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\SessionRepository;
use App\Services\OrderService;

final class WebhookHandler
{
    private LineClient $line;
    private CustomerRepository $customers;
    private ProductRepository $products;
    private OrderRepository $orders;
    private FaqRepository $faqs;
    private SessionRepository $sessions;
    private OrderService $orderService;

    public function __construct(
        ?LineClient $line = null,
        ?CustomerRepository $customers = null,
        ?ProductRepository $products = null,
        ?OrderRepository $orders = null,
        ?FaqRepository $faqs = null,
        ?SessionRepository $sessions = null,
        ?OrderService $orderService = null
    ) {
        $this->line = $line ?? new LineClient();
        $this->customers = $customers ?? new CustomerRepository();
        $this->products = $products ?? new ProductRepository();
        $this->orders = $orders ?? new OrderRepository();
        $this->faqs = $faqs ?? new FaqRepository();
        $this->sessions = $sessions ?? new SessionRepository();
        $this->orderService = $orderService ?? new OrderService();
    }

    public function handle(string $body, ?string $signature): void
    {
        if (!$this->line->isConfigured()) {
            Helpers::log('LINE keys are empty. Configure .env first.');
            http_response_code(503);
            echo 'LINE is not configured';
            return;
        }

        if (!$this->line->verifySignature($body, $signature)) {
            http_response_code(400);
            echo 'Invalid signature';
            return;
        }

        $payload = json_decode($body, true);
        if (!is_array($payload) || empty($payload['events']) || !is_array($payload['events'])) {
            echo 'OK';
            return;
        }

        foreach ($payload['events'] as $event) {
            try {
                $this->handleEvent($event);
            } catch (\Throwable $e) {
                Helpers::log('Event error: ' . $e->getMessage());
            }
        }

        echo 'OK';
    }

    /**
     * @param array<string, mixed> $event
     */
    private function handleEvent(array $event): void
    {
        $type = $event['type'] ?? '';
        $replyToken = $event['replyToken'] ?? null;
        $source = $event['source'] ?? [];
        $lineUserId = $source['userId'] ?? null;

        if (!$lineUserId || !$replyToken) {
            return;
        }

        $customer = $this->customers->findOrCreateByLineId($lineUserId);

        if ($type === 'follow' || $type === 'join') {
            $this->reply($replyToken, [MessageBuilder::mainMenuQuickReply()]);
            return;
        }

        if ($type === 'message') {
            $message = $event['message'] ?? [];
            $messageType = $message['type'] ?? '';

            if ($messageType === 'image') {
                $this->handleImage($replyToken, $customer, (string) ($message['id'] ?? ''));
                return;
            }

            if ($messageType === 'text') {
                $this->handleText($replyToken, $customer, trim((string) ($message['text'] ?? '')));
                return;
            }
        }

        $this->reply($replyToken, [MessageBuilder::mainMenuQuickReply()]);
    }

    private function handleImage(string $replyToken, array $customer, string $messageId): void
    {
        if ($messageId === '') {
            $this->reply($replyToken, [MessageBuilder::text('ไม่สามารถรับรูปได้ กรุณาส่งใหม่อีกครั้งค่ะ')]);
            return;
        }

        $order = $this->orders->pendingPaymentOrderForCustomer((int) $customer['id']);
        if (!$order) {
            $this->reply($replyToken, [MessageBuilder::text(
                "ยังไม่มีออเดอร์ที่รอแนบสลิปค่ะ\nพิมพ์ \"สินค้า\" เพื่อเริ่มสั่งซื้อ หรือพิมพ์ \"สถานะ\" เพื่อดูออเดอร์ล่าสุด"
            )]);
            return;
        }

        $binary = $this->line->getMessageContent($messageId);
        $this->orderService->attachSlip((int) $order['id'], $messageId, $binary);
        $this->sessions->clear($customer['line_user_id']);

        $this->reply($replyToken, [MessageBuilder::text(
            "รับสลิปออเดอร์ {$order['order_code']} แล้วค่ะ\nแอดมินกำลังตรวจสอบ ระบบจะแจ้งผลให้อีกครั้ง"
        )]);
    }

    private function handleText(string $replyToken, array $customer, string $text): void
    {
        $lineUserId = $customer['line_user_id'];
        $session = $this->sessions->get($lineUserId);
        $normalized = mb_strtolower($text);

        if (in_array($normalized, ['เมนู', 'menu', 'สวัสดี', 'hello', 'hi', 'เริ่มต้น'], true)) {
            $this->sessions->clear($lineUserId);
            $this->reply($replyToken, [MessageBuilder::mainMenuQuickReply()]);
            return;
        }

        if ($session['state'] === 'awaiting_shipping') {
            $this->completeOrderFromShipping($replyToken, $customer, $session, $text);
            return;
        }

        if ($session['state'] === 'awaiting_qty') {
            $this->handleQuantity($replyToken, $customer, $session, $text);
            return;
        }

        if (preg_match('/^หมวด\s+(\d+)$/u', $text, $m)) {
            $category = $this->products->findCategory((int) $m[1]);
            if (!$category) {
                $this->reply($replyToken, [MessageBuilder::text('ไม่พบหมวดสินค้านี้ค่ะ')]);
                return;
            }
            $products = $this->products->productsByCategory((int) $category['id']);
            $this->reply($replyToken, MessageBuilder::products($products, (string) $category['name']));
            return;
        }

        if (preg_match('/^สินค้า\s+(\d+)$/u', $text, $m)) {
            $product = $this->products->find((int) $m[1]);
            if (!$product || !(int) $product['is_active']) {
                $this->reply($replyToken, [MessageBuilder::text('ไม่พบสินค้านี้ค่ะ')]);
                return;
            }
            $this->reply($replyToken, MessageBuilder::productDetail($product));
            return;
        }

        if (preg_match('/^สั่งซื้อ\s+(\d+)$/u', $text, $m)) {
            $product = $this->products->find((int) $m[1]);
            if (!$product || !(int) $product['is_active']) {
                $this->reply($replyToken, [MessageBuilder::text('ไม่พบสินค้านี้ค่ะ')]);
                return;
            }
            if ((int) $product['stock'] <= 0) {
                $this->reply($replyToken, [MessageBuilder::text('สินค้าชิ้นนี้หมดชั่วคราวค่ะ')]);
                return;
            }

            $this->sessions->set($lineUserId, 'awaiting_qty', ['product_id' => (int) $product['id']]);
            $this->reply($replyToken, [MessageBuilder::text(
                "ต้องการสั่ง \"{$product['name']}\" จำนวนกี่ชิ้นคะ?\nพิมพ์ตัวเลข เช่น 1"
            )]);
            return;
        }

        if (in_array($normalized, ['สินค้า', 'ดูสินค้า', 'เลือกสินค้า'], true)) {
            $this->reply($replyToken, MessageBuilder::categories($this->products->allActiveCategories()));
            return;
        }

        if (in_array($normalized, ['สถานะ', 'ติดตามพัสดุ', 'เลขพัสดุ', 'พัสดุ'], true)) {
            $order = $this->orders->latestForCustomer((int) $customer['id']);
            if (!$order) {
                $this->reply($replyToken, [MessageBuilder::text('ยังไม่มีประวัติคำสั่งซื้อค่ะ')]);
                return;
            }
            $this->reply($replyToken, [MessageBuilder::text($this->orderService->statusText($order))]);
            return;
        }

        if (in_array($normalized, ['แอดมิน', 'ติดต่อแอดมิน', 'คุยกับคน', 'พนักงาน'], true)) {
            $this->sessions->set($lineUserId, 'human_handoff', []);
            $this->reply($replyToken, [MessageBuilder::text(
                "ส่งต่อให้แอดมินแล้วค่ะ\nกรุณารอพนักงานตอบกลับโดยตรงในแชทนี้ได้เลย"
            )]);
            return;
        }

        if (in_array($normalized, ['วิธีสั่ง', 'วิธีการสั่งซื้อ', 'สั่งยังไง'], true)) {
            $faq = $this->faqs->match('วิธีสั่ง');
            $this->reply($replyToken, [MessageBuilder::text($faq['answer'] ?? 'พิมพ์ "สินค้า" แล้วเลือกสินค้าเพื่อสั่งซื้อค่ะ')]);
            return;
        }

        $faq = $this->faqs->match($text);
        if ($faq) {
            $this->reply($replyToken, [MessageBuilder::text((string) $faq['answer'])]);
            return;
        }

        $found = $this->products->search($text);
        if ($found !== []) {
            $lines = ["พบสินค้าที่ใกล้เคียง:"];
            foreach ($found as $product) {
                $lines[] = "• {$product['name']} / " . Helpers::money($product['price']) . " (พิมพ์: สินค้า {$product['id']})";
            }
            $this->reply($replyToken, [MessageBuilder::text(implode("\n", $lines))]);
            return;
        }

        $this->reply($replyToken, [MessageBuilder::text(
            "ยังไม่เข้าใจคำถามนี้ค่ะ\nพิมพ์ \"เมนู\" เพื่อดูคำสั่งหลัก หรือพิมพ์ \"แอดมิน\" เพื่อคุยกับพนักงาน"
        )]);
    }

    private function handleQuantity(string $replyToken, array $customer, array $session, string $text): void
    {
        if (!preg_match('/^\d+$/', $text)) {
            $this->reply($replyToken, [MessageBuilder::text('กรุณาพิมพ์จำนวนเป็นตัวเลข เช่น 1')]);
            return;
        }

        $qty = (int) $text;
        if ($qty < 1 || $qty > 99) {
            $this->reply($replyToken, [MessageBuilder::text('จำนวนต้องอยู่ระหว่าง 1-99 ค่ะ')]);
            return;
        }

        $productId = (int) ($session['context']['product_id'] ?? 0);
        $product = $this->products->find($productId);
        if (!$product) {
            $this->sessions->clear($customer['line_user_id']);
            $this->reply($replyToken, [MessageBuilder::text('ไม่พบสินค้า กรุณาเริ่มสั่งใหม่ค่ะ')]);
            return;
        }

        if ((int) $product['stock'] < $qty) {
            $this->reply($replyToken, [MessageBuilder::text('คงเหลือไม่พอ คงเหลือ ' . (int) $product['stock'] . ' ชิ้นค่ะ')]);
            return;
        }

        $this->sessions->set($customer['line_user_id'], 'awaiting_shipping', [
            'product_id' => $productId,
            'quantity' => $qty,
        ]);

        $this->reply($replyToken, [MessageBuilder::text(
            "กรอกข้อมูลจัดส่งในบรรทัดเดียวตามนี้ค่ะ\n" .
            "ชื่อ | เบอร์โทร | ที่อยู่\n\n" .
            "ตัวอย่าง\nสมชาย ใจดี | 0812345678 | 123 ถ.พหลโยธิน กรุงเทพฯ 10400"
        )]);
    }

    private function completeOrderFromShipping(string $replyToken, array $customer, array $session, string $text): void
    {
        $parts = array_map('trim', explode('|', $text));
        if (count($parts) < 3) {
            $this->reply($replyToken, [MessageBuilder::text(
                "รูปแบบไม่ครบค่ะ กรุณาพิมพ์แบบนี้\nชื่อ | เบอร์โทร | ที่อยู่"
            )]);
            return;
        }

        [$name, $phone, $address] = [$parts[0], $parts[1], implode(' | ', array_slice($parts, 2))];
        $productId = (int) ($session['context']['product_id'] ?? 0);
        $qty = (int) ($session['context']['quantity'] ?? 1);

        try {
            $order = $this->orderService->createOrder(
                (int) $customer['id'],
                $productId,
                $qty,
                $name,
                $phone,
                $address
            );
            $this->customers->updateContact((int) $customer['id'], $phone, $address, $name);
            $this->sessions->clear($customer['line_user_id']);
            $this->reply($replyToken, [MessageBuilder::text($this->orderService->paymentInstructions($order))]);
        } catch (\Throwable $e) {
            $this->reply($replyToken, [MessageBuilder::text($e->getMessage())]);
        }
    }

    /**
     * @param list<array<string, mixed>>|array<string, mixed> $messages
     */
    private function reply(string $replyToken, array $messages): void
    {
        if (isset($messages['type'])) {
            $messages = [$messages];
        }

        $this->line->reply($replyToken, $messages);
    }
}
