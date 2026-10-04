<?php

declare(strict_types=1);

namespace App\Services;

use App\Env;
use App\Helpers;

/**
 * Optional OCR helper. Disabled by default (OCR_ENABLED=false).
 * When enabled, calls OCR_API_URL with Bearer OCR_API_KEY.
 * Expected JSON response example: {"text":"...","amount":199.00}
 */
final class SlipOcrService
{
    /**
     * @return array{text:?string, amount:?float}
     */
    public function analyze(string $absolutePath): array
    {
        $apiUrl = Env::get('OCR_API_URL', '') ?? '';
        $apiKey = Env::get('OCR_API_KEY', '') ?? '';

        if ($apiUrl === '') {
            return ['text' => null, 'amount' => null];
        }

        if (!is_file($absolutePath)) {
            return ['text' => null, 'amount' => null];
        }

        $cfile = new \CURLFile($absolutePath, mime_content_type($absolutePath) ?: 'image/jpeg', basename($absolutePath));
        $ch = curl_init($apiUrl);
        $headers = ['Accept: application/json'];
        if ($apiKey !== '') {
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => ['file' => $cfile],
        ]);

        $result = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($result === false || $status >= 400) {
            Helpers::log('OCR failed: ' . ($error ?: "HTTP {$status}"));
            return ['text' => null, 'amount' => null];
        }

        $json = json_decode((string) $result, true);
        if (!is_array($json)) {
            return ['text' => (string) $result, 'amount' => $this->extractAmount((string) $result)];
        }

        $text = isset($json['text']) ? (string) $json['text'] : null;
        $amount = null;
        if (isset($json['amount']) && is_numeric($json['amount'])) {
            $amount = (float) $json['amount'];
        } elseif ($text) {
            $amount = $this->extractAmount($text);
        }

        return ['text' => $text, 'amount' => $amount];
    }

    private function extractAmount(string $text): ?float
    {
        if (preg_match('/(\d{1,3}(?:,\d{3})*(?:\.\d{2})|\d+\.\d{2})/', $text, $m)) {
            return (float) str_replace(',', '', $m[1]);
        }

        return null;
    }
}
