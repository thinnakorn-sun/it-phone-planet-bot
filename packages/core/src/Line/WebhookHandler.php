<?php

declare(strict_types=1);

namespace App\Line;

use App\Helpers;
use App\Repositories\CustomerRepository;
use App\Repositories\FaqRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\SessionRepository;
use App\Security\CustomerAccess;
use App\Security\OrderToken;
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
                $type = (string) ($event['type'] ?? '');
                $msgType = (string) (($event['message']['type'] ?? '') ?: '');
                Helpers::log("event type={$type} msgType={$msgType}");
                $this->handleEvent($event);
            } catch (\Throwable $e) {
                Helpers::log('Event error: ' . $e->getMessage());
                $replyToken = $event['replyToken'] ?? null;
                $userId = $event['source']['userId'] ?? null;
                if (is_string($replyToken) && $replyToken !== '') {
                    try {
                        $this->reply($replyToken, [MessageBuilder::text(
                            "ระบบขัดข้องชั่วคราว กรุณาลองใหม่อีกครั้งครับ\n(" . $e->getMessage() . ')'
                        )]);
                    } catch (\Throwable $replyError) {
                        Helpers::log('Event error reply failed: ' . $replyError->getMessage());
                        if (is_string($userId) && $userId !== '') {
                            try {
                                $this->line->push($userId, [MessageBuilder::text(
                                    "ระบบขัดข้องชั่วคราว กรุณาลองใหม่อีกครั้งครับ"
                                )]);
                            } catch (\Throwable $pushError) {
                                Helpers::log('Event error push failed: ' . $pushError->getMessage());
                            }
                        }
                    }
                }
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
        try {
            if ($messageId === '') {
                $this->reply($replyToken, [MessageBuilder::text('ไม่สามารถรับรูปได้ กรุณาส่งใหม่อีกครั้งครับ')]);
                return;
            }

            Helpers::log('handleImage start customer=' . (int) $customer['id'] . ' msg=' . $messageId);

            $order = $this->orders->pendingPaymentOrderForCustomer((int) $customer['id']);
            if (!$order) {
                $this->reply($replyToken, [MessageBuilder::text(
                    "ยังไม่มีออเดอร์ที่รอแนบสลิปครับ\nกดเมนู \"ดูสินค้า\" เพื่อสั่งซื้อ หรือกด \"ตรวจสอบคำสั่งซื้อ\""
                )]);
                return;
            }

            $binary = $this->line->getMessageContent($messageId);
            if ($binary === '') {
                throw new \RuntimeException('ไฟล์สลิปว่างเปล่า');
            }

            $updated = $this->orderService->attachSlip((int) $order['id'], $messageId, $binary);
            $this->sessions->clear($customer['line_user_id']);

            $code = (string) ($updated['order_code'] ?? $order['order_code']);
            $detailUrl = CustomerAccess::detailUrl(
                $code,
                (string) $customer['line_user_id'],
                (int) $customer['id'],
                (string) ($customer['display_name'] ?? '')
            );
            $historyUrl = CustomerAccess::historyUrl(
                (string) $customer['line_user_id'],
                (int) $customer['id'],
                (string) ($customer['display_name'] ?? '')
            );

            $messages = [
                MessageBuilder::text(
                    "รับสลิปออเดอร์ {$code} แล้วครับ ✓\n" .
                    "สถานะ: ชำระเงินแล้ว\n\n" .
                    "ขั้นตอนถัดไป\n" .
                    "1) ร้านกำลังเตรียมสินค้าและจัดส่ง\n" .
                    "2) เมื่อมีเลขพัสดุ ระบบจะแจ้งในแชทนี้ให้อัตโนมัติ\n" .
                    "3) พิมพ์ \"สถานะ\" เพื่อตรวจสอบได้ตลอดเวลา"
                ),
            ];

            // ปุ่มเปิดดูออเดอร์ (ถ้า URI ยาวเกิน LINE รับได้ จะข้ามและส่งแค่ข้อความ)
            if (strlen($detailUrl) <= 1000 && strlen($historyUrl) <= 1000) {
                $messages[] = [
                    'type' => 'template',
                    'altText' => 'ชำระเงินแล้ว - ดูออเดอร์',
                    'template' => [
                        'type' => 'buttons',
                        'text' => "ออเดอร์ {$code}\nชำระเงินแล้ว กำลังเตรียมจัดส่ง",
                        'actions' => [
                            ['type' => 'uri', 'label' => 'ดูรายละเอียดออเดอร์', 'uri' => $detailUrl],
                            ['type' => 'uri', 'label' => 'ประวัติการสั่งซื้อ', 'uri' => $historyUrl],
                        ],
                    ],
                ];
            }

            $this->reply($replyToken, $messages);
            Helpers::log('handleImage success order=' . $code);
        } catch (\Throwable $e) {
            Helpers::log('handleImage error: ' . $e->getMessage());
            $msg = "ขออภัย ระบบรับสลิปไม่สำเร็จชั่วคราว\nกรุณาส่งรูปสลิปใหม่อีกครั้งครับ\n\n(" . $e->getMessage() . ')';
            try {
                $this->reply($replyToken, [MessageBuilder::text($msg)]);
            } catch (\Throwable $replyError) {
                Helpers::log('handleImage reply failed: ' . $replyError->getMessage());
                try {
                    $this->line->push((string) $customer['line_user_id'], [MessageBuilder::text($msg)]);
                } catch (\Throwable $pushError) {
                    Helpers::log('handleImage push failed: ' . $pushError->getMessage());
                }
            }
        }
    }

    private function handleText(string $replyToken, array $customer, string $text): void
    {
        $lineUserId = $customer['line_user_id'];
        $session = $this->sessions->get($lineUserId);
        $normalized = mb_strtolower($text);

        if (in_array($normalized, ['เมนู', 'menu', 'สวัสดี', 'สวัสดีครับ', 'สวัสดีค่ะ', 'hello', 'hi', 'เริ่มต้น'], true)) {
            $this->sessions->clear($lineUserId);
            $this->reply($replyToken, [MessageBuilder::mainMenuQuickReply()]);
            return;
        }

        if ($session['state'] === 'awaiting_confirm') {
            $this->handleConfirm($replyToken, $customer, $session, $normalized);
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
            $this->replyCategoryProducts($replyToken, (int) $m[1]);
            return;
        }

        $categoryByName = $this->products->findCategoryByName($text);
        if ($categoryByName) {
            $products = $this->products->productsByCategory((int) $categoryByName['id']);
            $this->reply($replyToken, MessageBuilder::products($products, (string) $categoryByName['name']));
            return;
        }

        if (preg_match('/^สินค้า\s+(\d+)$/u', $text, $m)) {
            $product = $this->products->find((int) $m[1]);
            if (!$product || !(int) $product['is_active']) {
                $this->reply($replyToken, [MessageBuilder::text('ไม่พบสินค้านี้ครับ')]);
                return;
            }
            $this->reply($replyToken, MessageBuilder::productDetail($product));
            return;
        }

        if (preg_match('/^สั่งซื้อ\s+(\d+)$/u', $text, $m)) {
            $product = $this->products->find((int) $m[1]);
            if (!$product || !(int) $product['is_active']) {
                $this->reply($replyToken, [MessageBuilder::text('ไม่พบสินค้านี้ครับ')]);
                return;
            }
            if ((int) $product['stock'] <= 0) {
                $this->reply($replyToken, [MessageBuilder::text('สินค้าชิ้นนี้หมดชั่วคราวครับ')]);
                return;
            }

            $this->sessions->set($lineUserId, 'awaiting_confirm', [
                'product_id' => (int) $product['id'],
                'quantity' => 1,
            ]);
            $this->reply($replyToken, MessageBuilder::confirmOrder($product));
            return;
        }

        if (in_array($normalized, ['สินค้า', 'ดูสินค้า', 'เลือกสินค้า', 'ดูสินค้าทั้งหมด'], true)) {
            $this->reply($replyToken, MessageBuilder::categories($this->products->allActiveCategories()));
            return;
        }

        if (in_array($normalized, ['สถานะ', 'ติดตามพัสดุ', 'เลขพัสดุ', 'พัสดุ', 'ตรวจสอบคำสั่งซื้อ', 'ประวัติการสั่งซื้อ', 'ประวัติ'], true)) {
            $this->replyCustomerPortal($replyToken, $customer, 'history');
            return;
        }

        if (in_array($normalized, ['บัญชีของฉัน', 'บัญชี', 'โปรไฟล์'], true)) {
            $this->replyCustomerPortal($replyToken, $customer, 'account');
            return;
        }

        if (in_array($normalized, ['หน้าหลัก', 'หน้าแรก', 'โฮม'], true)) {
            $this->replyCustomerPortal($replyToken, $customer, 'home');
            return;
        }

        if (in_array($normalized, ['โปรโมชั่น', 'โปร'], true)) {
            $faq = $this->faqs->match('โปรโมชั่น');
            $this->reply($replyToken, [MessageBuilder::text($faq['answer'] ?? "โปรโมชั่นประจำเดือน\n• สั่งครบ 990 บาท ส่งฟรี")]);
            return;
        }

        if (in_array($normalized, ['แจ้งชำระเงิน', 'แนบสลิป', 'ส่งสลิป'], true)) {
            $order = $this->orders->pendingPaymentOrderForCustomer((int) $customer['id']);
            if (!$order) {
                $latest = $this->orders->latestForCustomer((int) $customer['id']);
                if ($latest) {
                    $this->reply($replyToken, [MessageBuilder::text(
                        $this->orderService->statusText($latest) . "\n\nหากต้องการแนบสลิปใหม่ กรุณาสร้างออเดอร์ที่รอชำระเงินก่อนครับ"
                    )]);
                    return;
                }
                $this->reply($replyToken, [MessageBuilder::text(
                    "ยังไม่มีออเดอร์ที่รอชำระเงินครับ\nกดเมนูดูสินค้าเพื่อสั่งซื้อก่อน แล้วค่อยแนบสลิปได้เลย"
                )]);
                return;
            }

            $this->reply($replyToken, [MessageBuilder::text(
                "ออเดอร์ {$order['order_code']}\n" .
                'ยอดที่ต้องชำระ: ' . Helpers::money($order['total_amount']) . "\n\n" .
                "กรุณาส่งรูปสลิปมาในแชทนี้ได้เลยครับ"
            )]);
            return;
        }

        if (in_array($normalized, ['แอดมิน', 'ติดต่อแอดมิน', 'คุยกับคน', 'พนักงาน'], true)) {
            $this->sessions->set($lineUserId, 'human_handoff', []);
            $this->reply($replyToken, [MessageBuilder::text(
                "ส่งต่อให้แอดมินแล้วครับ\nกรุณารอพนักงานตอบกลับโดยตรงในแชทนี้ได้เลย"
            )]);
            return;
        }

        if (in_array($normalized, ['วิธีสั่ง', 'วิธีการสั่งซื้อ', 'สั่งยังไง'], true)) {
            $faq = $this->faqs->match('วิธีสั่ง');
            $this->reply($replyToken, [MessageBuilder::text($faq['answer'] ?? 'กดเมนูดูสินค้า แล้วเลือกสินค้าเพื่อสั่งซื้อครับ')]);
            return;
        }

        $faq = $this->faqs->match($text);
        if ($faq) {
            $this->reply($replyToken, [MessageBuilder::text((string) $faq['answer'])]);
            return;
        }

        $found = $this->products->search($text);
        if ($found !== []) {
            $this->reply($replyToken, MessageBuilder::products($found, 'สินค้าที่ค้นหา'));
            return;
        }

        $this->reply($replyToken, [MessageBuilder::text(
            "ยังไม่เข้าใจคำถามนี้ครับ\nพิมพ์ \"เมนู\" หรือกด Rich Menu ด้านล่างได้เลย"
        )]);
    }

    private function handleConfirm(string $replyToken, array $customer, array $session, string $normalized): void
    {
        if (in_array($normalized, ['เปลี่ยนรายการ', 'ไม่', 'ไม่ครับ', 'ไม่ค่ะ'], true)) {
            $this->sessions->clear($customer['line_user_id']);
            $this->reply($replyToken, MessageBuilder::categories($this->products->allActiveCategories()));
            return;
        }

        if (!in_array($normalized, ['ใช่ครับ', 'ใช่ค่ะ', 'ใช่', 'ยืนยัน', 'ok'], true)) {
            $this->reply($replyToken, [MessageBuilder::text('กรุณากด "ใช่ครับ" เพื่อยืนยัน หรือ "เปลี่ยนรายการ" เพื่อเลือกใหม่')]);
            return;
        }

        $productId = (int) ($session['context']['product_id'] ?? 0);
        $qty = (int) ($session['context']['quantity'] ?? 1);
        $product = $this->products->find($productId);
        if (!$product) {
            $this->sessions->clear($customer['line_user_id']);
            $this->reply($replyToken, [MessageBuilder::text('ไม่พบสินค้า กรุณาเริ่มสั่งใหม่ครับ')]);
            return;
        }

        $token = OrderToken::make([
            'line_user_id' => $customer['line_user_id'],
            'customer_id' => (int) $customer['id'],
            'product_id' => $productId,
            'quantity' => $qty,
        ]);

        $this->sessions->set($customer['line_user_id'], 'awaiting_form', [
            'product_id' => $productId,
            'quantity' => $qty,
        ]);

        $formUrl = Helpers::publicBaseUrl() . '/order/form.php?token=' . rawurlencode($token);
        $this->reply($replyToken, MessageBuilder::orderFormButton($formUrl));
    }

    private function replyCategoryProducts(string $replyToken, int $categoryId): void
    {
        $category = $this->products->findCategory($categoryId);
        if (!$category) {
            $this->reply($replyToken, [MessageBuilder::text('ไม่พบหมวดสินค้านี้ครับ')]);
            return;
        }
        $products = $this->products->productsByCategory((int) $category['id']);
        $this->reply($replyToken, MessageBuilder::products($products, (string) $category['name']));
    }

    private function handleQuantity(string $replyToken, array $customer, array $session, string $text): void
    {
        if (!preg_match('/^\d+$/', $text)) {
            $this->reply($replyToken, [MessageBuilder::text('กรุณาพิมพ์จำนวนเป็นตัวเลข เช่น 1')]);
            return;
        }

        $qty = (int) $text;
        if ($qty < 1 || $qty > 99) {
            $this->reply($replyToken, [MessageBuilder::text('จำนวนต้องอยู่ระหว่าง 1-99 ครับ')]);
            return;
        }

        $productId = (int) ($session['context']['product_id'] ?? 0);
        $product = $this->products->find($productId);
        if (!$product) {
            $this->sessions->clear($customer['line_user_id']);
            $this->reply($replyToken, [MessageBuilder::text('ไม่พบสินค้า กรุณาเริ่มสั่งใหม่ครับ')]);
            return;
        }

        if ((int) $product['stock'] < $qty) {
            $this->reply($replyToken, [MessageBuilder::text('คงเหลือไม่พอ คงเหลือ ' . (int) $product['stock'] . ' ชิ้นครับ')]);
            return;
        }

        $this->sessions->set($customer['line_user_id'], 'awaiting_shipping', [
            'product_id' => $productId,
            'quantity' => $qty,
        ]);

        $this->reply($replyToken, [MessageBuilder::text(
            "กรอกข้อมูลจัดส่งในบรรทัดเดียวตามนี้ครับ\n" .
            "ชื่อ | เบอร์โทร | ที่อยู่\n\n" .
            "ตัวอย่าง\nสมชาย ใจดี | 0812345678 | 123 ถ.พหลโยธิน กรุงเทพฯ 10400"
        )]);
    }

    private function completeOrderFromShipping(string $replyToken, array $customer, array $session, string $text): void
    {
        $parts = array_map('trim', explode('|', $text));
        if (count($parts) < 3) {
            $this->reply($replyToken, [MessageBuilder::text(
                "รูปแบบไม่ครบครับ กรุณาพิมพ์แบบนี้\nชื่อ | เบอร์โทร | ที่อยู่"
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
            $this->reply($replyToken, [MessageBuilder::text(
                $this->orderService->paymentInstructions($order) .
                "\n\nเมื่อโอนแล้ว กดเมนู \"แจ้งชำระเงิน\" แล้วส่งรูปสลิปมาได้เลยครับ"
            )]);
        } catch (\Throwable $e) {
            $this->reply($replyToken, [MessageBuilder::text($e->getMessage())]);
        }
    }

    /**
     * เปิดหน้าลูกค้าด้วยลิงก์ที่เซ็นลายเซ็นแล้ว (ดูได้เฉพาะของตัวเอง)
     *
     * @param array<string, mixed> $customer
     */
    private function replyCustomerPortal(string $replyToken, array $customer, string $page): void
    {
        $lineUserId = (string) $customer['line_user_id'];
        $customerId = (int) $customer['id'];
        $displayName = (string) ($customer['display_name'] ?? '');

        $homeUrl = CustomerAccess::homeUrl($lineUserId, $customerId, $displayName);
        $historyUrl = CustomerAccess::historyUrl($lineUserId, $customerId, $displayName);
        $accountUrl = CustomerAccess::accountUrl($lineUserId, $customerId, $displayName);

        if ($page === 'account') {
            $this->reply($replyToken, [
                MessageBuilder::text('เปิดบัญชีของคุณได้ที่นี่ครับ (ลิงก์นี้ใช้ได้เฉพาะคุณ)'),
                [
                    'type' => 'template',
                    'altText' => 'บัญชีของฉัน',
                    'template' => [
                        'type' => 'buttons',
                        'text' => 'บัญชีของ ' . ($displayName !== '' ? $displayName : 'คุณ'),
                        'actions' => [
                            ['type' => 'uri', 'label' => 'เปิดบัญชีของฉัน', 'uri' => $accountUrl],
                            ['type' => 'uri', 'label' => 'ประวัติการสั่งซื้อ', 'uri' => $historyUrl],
                        ],
                    ],
                ],
            ]);
            return;
        }

        if ($page === 'home') {
            $this->reply($replyToken, [
                MessageBuilder::text('เปิดหน้าหลักของคุณได้ที่นี่ครับ'),
                [
                    'type' => 'template',
                    'altText' => 'หน้าหลักของฉัน',
                    'template' => [
                        'type' => 'buttons',
                        'text' => 'หน้าหลักสำหรับบัญชีคุณเท่านั้น',
                        'actions' => [
                            ['type' => 'uri', 'label' => 'เปิดหน้าหลัก', 'uri' => $homeUrl],
                            ['type' => 'uri', 'label' => 'ประวัติการสั่งซื้อ', 'uri' => $historyUrl],
                        ],
                    ],
                ],
            ]);
            return;
        }

        $order = $this->orders->latestForCustomer($customerId);
        $messages = [];
        if ($order) {
            $messages[] = MessageBuilder::text($this->orderService->statusText($order));
            $detailUrl = CustomerAccess::detailUrl(
                (string) $order['order_code'],
                $lineUserId,
                $customerId,
                $displayName
            );
            $messages[] = [
                'type' => 'template',
                'altText' => 'ดูรายละเอียดออเดอร์',
                'template' => [
                    'type' => 'buttons',
                    'text' => 'เปิดดูรายละเอียดหรือประวัติของคุณได้ที่นี่ครับ',
                    'actions' => [
                        ['type' => 'uri', 'label' => 'รายละเอียดออเดอร์', 'uri' => $detailUrl],
                        ['type' => 'uri', 'label' => 'ประวัติการสั่งซื้อ', 'uri' => $historyUrl],
                    ],
                ],
            ];
        } else {
            $messages[] = MessageBuilder::text('ยังไม่มีประวัติคำสั่งซื้อครับ กดปุ่มด้านล่างเพื่อเปิดหน้าประวัติของคุณ');
            $messages[] = [
                'type' => 'template',
                'altText' => 'ประวัติการสั่งซื้อ',
                'template' => [
                    'type' => 'buttons',
                    'text' => 'เปิดประวัติของบัญชีคุณเท่านั้น',
                    'actions' => [
                        ['type' => 'uri', 'label' => 'ประวัติการสั่งซื้อ', 'uri' => $historyUrl],
                        ['type' => 'uri', 'label' => 'บัญชีของฉัน', 'uri' => $accountUrl],
                    ],
                ],
            ];
        }

        $this->reply($replyToken, $messages);
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
