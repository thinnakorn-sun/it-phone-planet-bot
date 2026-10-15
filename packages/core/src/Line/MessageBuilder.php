<?php

declare(strict_types=1);

namespace App\Line;

use App\Helpers;

final class MessageBuilder
{
    public static function text(string $text): array
    {
        return ['type' => 'text', 'text' => $text];
    }

    public static function welcome(): array
    {
        return self::text(
            "สวัสดีค่ะ 👏\n" .
            "ยินดีต้อนรับสู่ ITPHONEPLANET\n" .
            "กรุณาเข้าสู่ระบบหรือสมัครสมาชิก\n" .
            "เพื่อใช้งานระบบสั่งซื้อสินค้า"
        );
    }

    /**
     * ขั้นที่ 1: เข้าสู่ระบบ / สมัครสมาชิก (ตาม mockup)
     *
     * @return list<array<string, mixed>>
     */
    public static function authWelcome(string $loginUrl, string $registerUrl, bool $isMember = false): array
    {
        $footerButtons = [];
        if (!$isMember) {
            $footerButtons[] = [
                'type' => 'button',
                'style' => 'primary',
                'color' => '#FF4D94',
                'height' => 'md',
                'action' => [
                    'type' => 'uri',
                    'label' => 'เข้าสู่ระบบ',
                    'uri' => $loginUrl,
                ],
            ];
            $footerButtons[] = [
                'type' => 'button',
                'style' => 'secondary',
                'color' => '#FFFFFF',
                'height' => 'md',
                'action' => [
                    'type' => 'uri',
                    'label' => 'สมัครสมาชิก',
                    'uri' => $registerUrl,
                ],
            ];
        } else {
            $footerButtons[] = [
                'type' => 'button',
                'style' => 'primary',
                'color' => '#06C755',
                'height' => 'md',
                'action' => [
                    'type' => 'message',
                    'label' => 'เข้าสู่ระบบแล้ว ✓',
                    'text' => 'เลือกซื้อสินค้า',
                ],
            ];
        }

        $footerButtons[] = [
            'type' => 'button',
            'style' => 'primary',
            'color' => '#7EC8F5',
            'height' => 'md',
            'action' => [
                'type' => 'message',
                'label' => 'เลือกซื้อสินค้า',
                'text' => 'เลือกซื้อสินค้า',
            ],
        ];

        $intro = $isMember
            ? "สวัสดีค่ะ 👏\nยินดีต้อนรับกลับสู่ ITPHONEPLANET\nคุณเข้าสู่ระบบแล้ว กดเลือกซื้อสินค้าได้เลย"
            : "สวัสดีค่ะ 👏\nยินดีต้อนรับสู่ ITPHONEPLANET\nกรุณาเข้าสู่ระบบหรือสมัครสมาชิก\nเพื่อใช้งานระบบสั่งซื้อสินค้า";

        return [
            self::text($intro),
            [
                'type' => 'flex',
                'altText' => 'เข้าสู่ระบบ / สมัครสมาชิก ITPHONEPLANET',
                'contents' => [
                    'type' => 'bubble',
                    'size' => 'mega',
                    'body' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'spacing' => 'md',
                        'contents' => [
                            [
                                'type' => 'box',
                                'layout' => 'horizontal',
                                'spacing' => 'md',
                                'contents' => [
                                    [
                                        'type' => 'box',
                                        'layout' => 'vertical',
                                        'contents' => [[
                                            'type' => 'text',
                                            'text' => 'iT',
                                            'color' => '#FFFFFF',
                                            'weight' => 'bold',
                                            'size' => 'sm',
                                            'align' => 'center',
                                        ]],
                                        'backgroundColor' => '#111111',
                                        'cornerRadius' => '40px',
                                        'width' => '52px',
                                        'height' => '52px',
                                        'justifyContent' => 'center',
                                    ],
                                    [
                                        'type' => 'box',
                                        'layout' => 'vertical',
                                        'contents' => [
                                            [
                                                'type' => 'text',
                                                'text' => 'ITPHONEPLANET',
                                                'weight' => 'bold',
                                                'size' => 'md',
                                                'color' => '#111111',
                                            ],
                                            [
                                                'type' => 'text',
                                                'text' => 'ร้านจำหน่ายอุปกรณ์ไอทีและมือถือ',
                                                'size' => 'xs',
                                                'color' => '#888888',
                                                'wrap' => true,
                                            ],
                                        ],
                                        'flex' => 1,
                                        'justifyContent' => 'center',
                                    ],
                                ],
                            ],
                            ['type' => 'separator', 'margin' => 'md'],
                            [
                                'type' => 'text',
                                'text' => $isMember
                                    ? "สวัสดีค่ะ 👏\nคุณเข้าสู่ระบบแล้ว พร้อมสั่งซื้อสินค้าได้เลย"
                                    : "สวัสดีค่ะ 👏\nยินดีต้อนรับสู่ ITPHONEPLANET\nกรุณาเข้าสู่ระบบหรือสมัครสมาชิก\nเพื่อใช้งานระบบสั่งซื้อสินค้า",
                                'wrap' => true,
                                'size' => 'sm',
                                'color' => '#333333',
                            ],
                        ],
                    ],
                    'footer' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'spacing' => 'sm',
                        'contents' => $footerButtons,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param list<array<string, mixed>> $categories
     * @return list<array<string, mixed>>
     */
    public static function categories(array $categories): array
    {
        if ($categories === []) {
            return [self::text('ยังไม่มีหมวดสินค้าในระบบครับ')];
        }

        $rows = [];
        foreach ($categories as $category) {
            $name = (string) $category['name'];
            $rows[] = [
                'type' => 'box',
                'layout' => 'horizontal',
                'paddingAll' => '12px',
                'action' => [
                    'type' => 'message',
                    'label' => mb_substr($name, 0, 20),
                    'text' => $name,
                ],
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => self::categoryEmoji($name),
                        'size' => 'lg',
                        'flex' => 0,
                    ],
                    [
                        'type' => 'text',
                        'text' => $name,
                        'weight' => 'bold',
                        'size' => 'md',
                        'margin' => 'lg',
                        'gravity' => 'center',
                        'flex' => 1,
                    ],
                ],
            ];
            $rows[] = ['type' => 'separator'];
        }

        // remove last separator
        if ($rows !== []) {
            array_pop($rows);
        }

        $rows[] = [
            'type' => 'button',
            'margin' => 'md',
            'action' => [
                'type' => 'message',
                'label' => '← กลับเมนูหลัก',
                'text' => 'เมนู',
            ],
        ];

        return [
            self::text('นี่คือสินค้าทั้งหมดของเรา เลือกหมวดหมู่ที่คุณสนใจได้เลยครับ 😊'),
            [
                'type' => 'flex',
                'altText' => 'เลือกหมวดสินค้า',
                'contents' => [
                    'type' => 'bubble',
                    'body' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'paddingAll' => '0px',
                        'contents' => $rows,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param list<array<string, mixed>> $products
     * @return list<array<string, mixed>>
     */
    public static function products(array $products, string $categoryName): array
    {
        if ($products === []) {
            return [self::text("ยังไม่มีสินค้าในหมวด {$categoryName} ครับ")];
        }

        $bubbles = [];
        foreach (array_slice($products, 0, 10) as $product) {
            $inStock = (int) $product['stock'] > 0;
            $imageUrl = Helpers::productImageUrl($product['image_url'] ?? null);

            $bodyContents = [
                [
                    'type' => 'text',
                    'text' => (string) $product['name'],
                    'weight' => 'bold',
                    'size' => 'md',
                    'wrap' => true,
                ],
                [
                    'type' => 'text',
                    'text' => Helpers::baht($product['price']),
                    'size' => 'lg',
                    'weight' => 'bold',
                    'margin' => 'md',
                    'color' => '#111111',
                ],
                [
                    'type' => 'text',
                    'text' => $inStock ? 'มีสินค้า' : 'สินค้าหมด',
                    'size' => 'sm',
                    'color' => $inStock ? '#1DB446' : '#999999',
                    'margin' => 'sm',
                ],
            ];

            $bubble = [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => $bodyContents,
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'sm',
                    'contents' => [
                        [
                            'type' => 'button',
                            'style' => 'secondary',
                            'height' => 'sm',
                            'action' => [
                                'type' => 'message',
                                'label' => 'ดูรายละเอียด',
                                'text' => 'สินค้า ' . $product['id'],
                            ],
                        ],
                        [
                            'type' => 'button',
                            'style' => 'secondary',
                            'height' => 'sm',
                            'action' => [
                                'type' => 'message',
                                'label' => 'สั่งซื้อ',
                                'text' => $inStock ? ('สั่งซื้อ ' . $product['id']) : 'ดูสินค้า',
                            ],
                        ],
                    ],
                ],
            ];

            if ($imageUrl) {
                $bubble['hero'] = [
                    'type' => 'image',
                    'url' => $imageUrl,
                    'size' => 'full',
                    'aspectRatio' => '1:1',
                    'aspectMode' => 'cover',
                ];
            }

            $bubbles[] = $bubble;
        }

        return [
            self::text("นี่คือ{$categoryName}ที่เรามีให้บริการครับ"),
            [
                'type' => 'flex',
                'altText' => 'รายการสินค้า ' . $categoryName,
                'contents' => [
                    'type' => 'carousel',
                    'contents' => $bubbles,
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function productDetail(array $product): array
    {
        $desc = trim((string) ($product['description'] ?? ''));
        if ($desc === '') {
            $desc = 'ไม่มีรายละเอียดเพิ่มเติม';
        }

        $inStock = (int) $product['stock'] > 0;
        $imageUrl = Helpers::productImageUrl($product['image_url'] ?? null);

        $bubble = [
            'type' => 'bubble',
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    ['type' => 'text', 'text' => (string) $product['name'], 'weight' => 'bold', 'size' => 'lg', 'wrap' => true],
                    ['type' => 'text', 'text' => 'หมวด: ' . ($product['category_name'] ?? '-'), 'size' => 'sm', 'margin' => 'md'],
                    ['type' => 'text', 'text' => Helpers::baht($product['price']), 'size' => 'xl', 'margin' => 'md', 'weight' => 'bold'],
                    [
                        'type' => 'text',
                        'text' => $inStock ? ('มีสินค้า · คงเหลือ ' . (int) $product['stock'] . ' ชิ้น') : 'สินค้าหมด',
                        'size' => 'sm',
                        'color' => $inStock ? '#1DB446' : '#999999',
                        'margin' => 'sm',
                    ],
                    ['type' => 'text', 'text' => $desc, 'size' => 'sm', 'wrap' => true, 'margin' => 'lg'],
                ],
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [[
                    'type' => 'button',
                    'style' => $inStock ? 'primary' : 'secondary',
                    'action' => [
                        'type' => 'message',
                        'label' => $inStock ? 'สั่งซื้อ' : 'สินค้าหมด',
                        'text' => $inStock ? ('สั่งซื้อ ' . $product['id']) : 'ดูสินค้า',
                    ],
                ]],
            ],
        ];

        if ($imageUrl) {
            $bubble['hero'] = [
                'type' => 'image',
                'url' => $imageUrl,
                'size' => 'full',
                'aspectRatio' => '1:1',
                'aspectMode' => 'cover',
            ];
        }

        return [[
            'type' => 'flex',
            'altText' => (string) $product['name'],
            'contents' => $bubble,
        ]];
    }

    public static function confirmOrder(array $product): array
    {
        $price = Helpers::baht($product['price']);
        $message = self::text(
            "คุณต้องการสั่งซื้อ {$product['name']} ราคา {$price} ใช่ไหมครับ?"
        );
        $message['quickReply'] = [
            'items' => [
                self::quick('ใช่ครับ', 'ใช่ครับ'),
                self::quick('เปลี่ยนรายการ', 'เปลี่ยนรายการ'),
            ],
        ];

        return [$message];
    }

    public static function orderFormButton(string $url): array
    {
        return [[
            'type' => 'flex',
            'altText' => 'กรอกข้อมูลสั่งซื้อ',
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        ['type' => 'text', 'text' => 'ขั้นตอนถัดไป', 'weight' => 'bold', 'size' => 'md'],
                        [
                            'type' => 'text',
                            'text' => 'กรุณากรอกข้อมูลจัดส่งและยืนยันการสั่งซื้อในหน้าฟอร์มครับ',
                            'size' => 'sm',
                            'wrap' => true,
                            'margin' => 'md',
                            'color' => '#666666',
                        ],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [[
                        'type' => 'button',
                        'style' => 'primary',
                        'color' => '#E91E8C',
                        'action' => [
                            'type' => 'uri',
                            'label' => 'กรอกข้อมูลสั่งซื้อ',
                            'uri' => $url,
                        ],
                    ]],
                ],
            ],
        ]];
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    public static function orderSuccessCard(array $order, array $items = []): array
    {
        $lineUserId = (string) ($order['line_user_id'] ?? '');
        $customerId = (int) ($order['customer_id'] ?? 0);
        $displayName = (string) ($order['customer_name'] ?? $order['shipping_name'] ?? '');
        $detailUrl = \App\Security\CustomerAccess::detailUrl(
            (string) $order['order_code'],
            $lineUserId,
            $customerId,
            $displayName
        );
        $historyUrl = \App\Security\CustomerAccess::historyUrl($lineUserId, $customerId, $displayName);

        $created = !empty($order['created_at'])
            ? date('d/m/Y H:i', strtotime((string) $order['created_at']))
            : date('d/m/Y H:i');

        return [[
            'type' => 'flex',
            'altText' => 'สั่งซื้อสำเร็จ ' . $order['order_code'],
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'alignItems' => 'center',
                            'contents' => [
                                [
                                    'type' => 'box',
                                    'layout' => 'vertical',
                                    'contents' => [[
                                        'type' => 'text',
                                        'text' => '✓',
                                        'align' => 'center',
                                        'color' => '#FFFFFF',
                                        'size' => 'xl',
                                        'weight' => 'bold',
                                    ]],
                                    'backgroundColor' => '#06C755',
                                    'cornerRadius' => '30px',
                                    'width' => '56px',
                                    'height' => '56px',
                                    'justifyContent' => 'center',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => 'สั่งซื้อสำเร็จ!',
                                    'weight' => 'bold',
                                    'size' => 'lg',
                                    'margin' => 'md',
                                    'color' => '#06C755',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => 'ขอบคุณที่สั่งซื้อสินค้ากับเรา',
                                    'size' => 'sm',
                                    'color' => '#888888',
                                    'margin' => 'sm',
                                ],
                            ],
                        ],
                        ['type' => 'separator', 'margin' => 'lg'],
                        [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'margin' => 'lg',
                            'spacing' => 'sm',
                            'contents' => [
                                self::kv('หมายเลขคำสั่งซื้อ', (string) $order['order_code']),
                                self::kv('วันที่', $created),
                                self::kv('ยอดรวม', Helpers::money($order['total_amount'])),
                                self::kv('การจัดส่ง', 'Kerry Express'),
                                self::kv('การชำระเงิน', (string) ($order['payment_method'] ?: 'โอนเงินผ่านธนาคาร')),
                            ],
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'horizontal',
                            'margin' => 'lg',
                            'contents' => [
                                [
                                    'type' => 'text',
                                    'text' => 'สถานะการสั่งซื้อ',
                                    'size' => 'sm',
                                    'color' => '#555555',
                                    'flex' => 1,
                                ],
                                [
                                    'type' => 'text',
                                    'text' => 'รอการชำระเงิน',
                                    'size' => 'sm',
                                    'color' => '#06C755',
                                    'weight' => 'bold',
                                    'align' => 'end',
                                ],
                            ],
                        ],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'spacing' => 'sm',
                    'contents' => [
                        [
                            'type' => 'button',
                            'style' => 'primary',
                            'color' => '#06C755',
                            'action' => [
                                'type' => 'uri',
                                'label' => 'ดูรายละเอียดคำสั่งซื้อ',
                                'uri' => $detailUrl,
                            ],
                        ],
                        [
                            'type' => 'button',
                            'style' => 'secondary',
                            'action' => [
                                'type' => 'uri',
                                'label' => 'ประวัติการสั่งซื้อ',
                                'uri' => $historyUrl,
                            ],
                        ],
                    ],
                ],
            ],
        ]];
    }

    private static function kv(string $label, string $value): array
    {
        return [
            'type' => 'box',
            'layout' => 'baseline',
            'contents' => [
                ['type' => 'text', 'text' => $label, 'size' => 'sm', 'color' => '#888888', 'flex' => 2],
                ['type' => 'text', 'text' => $value, 'size' => 'sm', 'color' => '#111111', 'flex' => 3, 'wrap' => true],
            ],
        ];
    }

    public static function mainMenuQuickReply(): array
    {
        $message = self::welcome();
        $message['quickReply'] = [
            'items' => [
                self::quick('ดูสินค้า', 'ดูสินค้า'),
                self::quick('วิธีสั่ง', 'วิธีการสั่งซื้อ'),
                self::quick('สถานะ', 'สถานะ'),
                self::quick('โปรโมชั่น', 'โปรโมชั่น'),
                self::quick('แอดมิน', 'ติดต่อแอดมิน'),
            ],
        ];
        return $message;
    }

    private static function categoryEmoji(string $name): string
    {
        if (str_contains($name, 'นาฬิกา')) {
            return '⌚';
        }
        if (str_contains($name, 'เคส') || str_contains($name, 'ฟิล์ม')) {
            return '📱';
        }
        if (str_contains($name, 'ชาร์จ')) {
            return '🔌';
        }

        return '•••';
    }

    private static function quick(string $label, string $text): array
    {
        return [
            'type' => 'action',
            'action' => [
                'type' => 'message',
                'label' => $label,
                'text' => $text,
            ],
        ];
    }
}
