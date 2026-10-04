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
            "ยินดีต้อนรับสู่ iT Phone Planet ค่ะ\n" .
            "พิมพ์เมนูหรือเลือกจากปุ่มด้านล่างได้เลย\n\n" .
            "• สินค้า\n• สั่งซื้อ\n• สถานะ\n• วิธีสั่ง\n• แอดมิน"
        );
    }

    /**
     * @param list<array<string, mixed>> $categories
     */
    public static function categories(array $categories): array
    {
        if ($categories === []) {
            return [self::text('ยังไม่มีหมวดสินค้าในระบบค่ะ')];
        }

        $actions = [];
        foreach (array_slice($categories, 0, 12) as $category) {
            $actions[] = [
                'type' => 'message',
                'label' => mb_substr((string) $category['name'], 0, 20),
                'text' => 'หมวด ' . $category['id'],
            ];
        }

        $template = [
            'type' => 'template',
            'altText' => 'เลือกหมวดสินค้า',
            'template' => [
                'type' => 'buttons',
                'title' => 'หมวดสินค้า',
                'text' => 'เลือกหมวดที่ต้องการดูได้เลยค่ะ',
                'actions' => array_slice($actions, 0, 4),
            ],
        ];

        // If more than 4 categories, also send as quick text list
        if (count($actions) > 4) {
            $lines = ["หมวดสินค้าทั้งหมด:"];
            foreach ($categories as $category) {
                $lines[] = "• {$category['name']} (พิมพ์: หมวด {$category['id']})";
            }
            return [$template, self::text(implode("\n", $lines))];
        }

        return [$template];
    }

    /**
     * @param list<array<string, mixed>> $products
     */
    public static function products(array $products, string $categoryName): array
    {
        if ($products === []) {
            return [self::text("ยังไม่มีสินค้าในหมวด {$categoryName} ค่ะ")];
        }

        $bubbles = [];
        foreach (array_slice($products, 0, 10) as $product) {
            $bubbles[] = [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        ['type' => 'text', 'text' => (string) $product['name'], 'weight' => 'bold', 'size' => 'md', 'wrap' => true],
                        ['type' => 'text', 'text' => Helpers::money($product['price']), 'size' => 'sm', 'margin' => 'md'],
                        ['type' => 'text', 'text' => 'คงเหลือ: ' . (int) $product['stock'] . ' ชิ้น', 'size' => 'sm', 'color' => '#666666'],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [[
                        'type' => 'button',
                        'style' => 'primary',
                        'action' => [
                            'type' => 'message',
                            'label' => 'ดูรายละเอียด',
                            'text' => 'สินค้า ' . $product['id'],
                        ],
                    ]],
                ],
            ];
        }

        return [[
            'type' => 'flex',
            'altText' => 'รายการสินค้า ' . $categoryName,
            'contents' => [
                'type' => 'carousel',
                'contents' => $bubbles,
            ],
        ]];
    }

    public static function productDetail(array $product): array
    {
        $desc = trim((string) ($product['description'] ?? ''));
        if ($desc === '') {
            $desc = 'ไม่มีรายละเอียดเพิ่มเติม';
        }

        $inStock = (int) $product['stock'] > 0;

        return [[
            'type' => 'flex',
            'altText' => (string) $product['name'],
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        ['type' => 'text', 'text' => (string) $product['name'], 'weight' => 'bold', 'size' => 'lg', 'wrap' => true],
                        ['type' => 'text', 'text' => 'หมวด: ' . ($product['category_name'] ?? '-'), 'size' => 'sm', 'margin' => 'md'],
                        ['type' => 'text', 'text' => Helpers::money($product['price']), 'size' => 'md', 'margin' => 'md', 'weight' => 'bold'],
                        ['type' => 'text', 'text' => 'คงเหลือ: ' . (int) $product['stock'] . ' ชิ้น', 'size' => 'sm'],
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
                            'label' => $inStock ? 'สั่งซื้อชิ้นนี้' : 'สินค้าหมด',
                            'text' => $inStock ? ('สั่งซื้อ ' . $product['id']) : 'สินค้า',
                        ],
                    ]],
                ],
            ],
        ]];
    }

    public static function mainMenuQuickReply(): array
    {
        $message = self::welcome();
        $message['quickReply'] = [
            'items' => [
                self::quick('สินค้า', 'สินค้า'),
                self::quick('วิธีสั่ง', 'วิธีสั่ง'),
                self::quick('สถานะ', 'สถานะ'),
                self::quick('แอดมิน', 'แอดมิน'),
            ],
        ];
        return $message;
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
