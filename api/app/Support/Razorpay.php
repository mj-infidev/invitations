<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Razorpay, the thin way, as in nancofills: create an order, check the
 * signature the browser brings back, check a webhook's signature — one REST
 * call and two HMACs, so no SDK, and Http::fake() makes it testable.
 *
 * Money is in paise throughout (₹499 = 49900), the only unit Razorpay takes.
 * Keys: config/services.php (RAZORPAY_KEY_ID / RAZORPAY_KEY_SECRET /
 * RAZORPAY_WEBHOOK_SECRET).
 */
final class Razorpay
{
    private const BASE = 'https://api.razorpay.com/v1';

    public static function keyId(): ?string
    {
        return config('services.razorpay.key_id') ?: null;
    }

    private static function secret(): ?string
    {
        return config('services.razorpay.key_secret') ?: null;
    }

    public static function webhookSecret(): ?string
    {
        return config('services.razorpay.webhook_secret') ?: null;
    }

    public static function configured(): bool
    {
        return self::keyId() !== null && self::secret() !== null;
    }

    /** Test keys are `rzp_test_…`; live ones `rzp_live_…`. */
    public static function isTestMode(): bool
    {
        return str_starts_with((string) self::keyId(), 'rzp_test');
    }

    /**
     * The gateway-side order the browser pays against. Capture is set on the
     * order itself (payment_capture), not left to the account's setting: the
     * test account is shared, and an uncaptured payment is refunded in days.
     *
     * @return array{id: string, amount: int, currency: string}
     *
     * @throws RuntimeException when Razorpay refuses or can't be reached
     */
    public static function createOrder(int $amountPaise, string $receipt, array $notes = []): array
    {
        $response = self::request('post', '/orders', [
            'amount' => $amountPaise,
            'currency' => 'INR',
            'receipt' => substr($receipt, 0, 40), // Razorpay's limit
            'payment_capture' => 1,
            'notes' => $notes,
        ]);

        if (! $response->json('id')) {
            throw new RuntimeException('The payment gateway refused this order.');
        }

        return [
            'id' => $response->json('id'),
            'amount' => (int) $response->json('amount'),
            'currency' => (string) ($response->json('currency') ?: 'INR'),
        ];
    }

    /** The payments made against an order, newest first (for the replay command). */
    public static function orderPayments(string $orderId): array
    {
        return self::request('get', "/orders/{$orderId}/payments")->json('items') ?? [];
    }

    /**
     * Checkout's answer in the browser: HMAC-SHA256 of "<order_id>|<payment_id>"
     * with the key secret. Without this, anyone could post a made-up payment id.
     */
    public static function paymentSignatureValid(string $orderId, string $paymentId, string $signature): bool
    {
        return self::hmacMatches("{$orderId}|{$paymentId}", $signature, (string) self::secret());
    }

    /** A webhook: HMAC-SHA256 of the raw body with the webhook secret (a different secret). */
    public static function webhookSignatureValid(string $rawBody, string $signature): bool
    {
        return self::hmacMatches($rawBody, $signature, (string) self::webhookSecret());
    }

    /** Signs a body as Razorpay would (the replay command, and tests). */
    public static function signWebhook(string $rawBody): string
    {
        return hash_hmac('sha256', $rawBody, (string) self::webhookSecret());
    }

    /** Constant-time compare, so a signature can't be found byte by byte. */
    private static function hmacMatches(string $payload, string $signature, string $secret): bool
    {
        return $secret !== '' && $signature !== '' && hash_equals(hash_hmac('sha256', $payload, $secret), $signature);
    }

    private static function request(string $method, string $path, array $body = []): \Illuminate\Http\Client\Response
    {
        if (! self::configured()) {
            throw new RuntimeException('Razorpay keys are not configured.');
        }

        try {
            $response = Http::withBasicAuth(self::keyId(), self::secret())
                ->timeout(15)
                ->acceptJson()
                ->{$method}(self::BASE.$path, $body);
        } catch (Throwable $e) {
            Log::warning("Razorpay {$method} {$path} failed: {$e->getMessage()}");
            throw new RuntimeException('We could not reach the payment gateway.');
        }

        if (! $response->successful()) {
            Log::warning("Razorpay refused {$method} {$path}: ".$response->body());
            throw new RuntimeException($response->json('error.description') ?: 'The payment gateway refused the request.');
        }

        return $response;
    }
}
