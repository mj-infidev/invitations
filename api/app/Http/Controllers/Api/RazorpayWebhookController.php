<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentEvent;
use App\Support\PaymentRecorder;
use App\Support\Razorpay;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * POST /api/webhooks/razorpay: Razorpay telling us what happened, whatever the
 * customer's browser did. A closed tab still ends with the invitation published.
 *
 * Public, so the signature is the authentication: HMAC-SHA256 of the raw body
 * with the webhook secret. Unsigned or wrongly signed is a 400, and not read.
 *
 * Every event is logged in payment_events; its unique event id
 * (X-Razorpay-Event-Id) stops a redelivered event from being handled twice.
 * Anything signed but not ours to act on (other events, orders from another
 * project on the shared test account) answers 200: Razorpay retries any
 * non-2xx for days.
 *
 * Locally Razorpay can't reach this: `php artisan payments:replay-webhook`.
 */
class RazorpayWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! Razorpay::webhookSecret()) {
            Log::warning('Razorpay webhook arrived but RAZORPAY_WEBHOOK_SECRET is not set.');

            return response()->json(['message' => 'Webhooks are not configured.'], 400);
        }

        $raw = $request->getContent();
        if (! Razorpay::webhookSignatureValid($raw, (string) $request->header('X-Razorpay-Signature'))) {
            Log::warning('Razorpay webhook with a bad signature was rejected.');

            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        $type = (string) $request->input('event');
        $eventId = (string) ($request->header('X-Razorpay-Event-Id') ?: 'body-'.hash('sha256', $raw));

        try {
            $event = PaymentEvent::create(['event_id' => $eventId, 'type' => $type, 'payload' => $request->json()->all()]);
        } catch (UniqueConstraintViolationException) {
            return response()->json(['message' => 'Already received.']);
        }

        $payment = $request->input('payload.payment.entity', []);
        $order = isset($payment['order_id']) ? Order::where('razorpay_order_id', $payment['order_id'])->first() : null;

        if ($order === null) {
            Log::info("Razorpay {$type} for an order that isn't ours ignored.");
        } elseif ($type === 'payment.captured') {
            $this->captured($order, $payment);
        } elseif ($type === 'payment.failed') {
            PaymentRecorder::markFailed($order);
        }

        $event->forceFill(['processed_at' => now()])->save();

        return response()->json(['message' => "Handled {$type}."]);
    }

    private function captured(Order $order, array $payment): void
    {
        // The money must be what the order asked for; anything else is for a person to look at.
        if ((int) ($payment['amount'] ?? 0) !== $order->amount_paise || ($payment['currency'] ?? '') !== $order->currency) {
            Log::warning("Razorpay captured {$payment['amount']} {$payment['currency']} for order {$order->id}, which asked for {$order->amount_paise} {$order->currency}. Not published.");

            return;
        }

        PaymentRecorder::markPaid($order, (string) $payment['id'], 'webhook');
    }
}
