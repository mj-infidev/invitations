<?php

namespace App\Console\Commands;

use App\Support\Razorpay;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Razorpay can't reach localhost, so this plays its part: it fetches the real
 * payment made against a Razorpay order (test mode), builds the event Razorpay
 * would send, signs it with RAZORPAY_WEBHOOK_SECRET and posts it to our own
 * webhook. The whole real path runs — signature, event log, amount check,
 * publishing — with real payment data.
 *
 * To try the "tab closed before verify" case: pay in Checkout while blocking
 * /api/payments/verify in the browser's DevTools, then run this.
 */
class ReplayRazorpayWebhook extends Command
{
    protected $signature = 'payments:replay-webhook
        {order : The Razorpay order id, order_…}
        {--url= : Webhook address (default: this app\'s /api/webhooks/razorpay)}';

    protected $description = 'Send our webhook the event Razorpay would send for an order\'s payment (local testing)';

    public function handle(): int
    {
        if (! Razorpay::webhookSecret()) {
            $this->error('Set RAZORPAY_WEBHOOK_SECRET in .env first (any string you choose).');

            return self::FAILURE;
        }

        try {
            $payments = Razorpay::orderPayments($this->argument('order'));
        } catch (Throwable $e) {
            $this->error("Couldn't fetch the order's payments: {$e->getMessage()}");

            return self::FAILURE;
        }

        // The captured payment if there is one, else the newest attempt.
        $payment = collect($payments)->firstWhere('status', 'captured') ?? collect($payments)->first();
        if ($payment === null) {
            $this->error('That order has no payments yet.');

            return self::FAILURE;
        }

        $type = match ($payment['status']) {
            'captured' => 'payment.captured',
            'failed' => 'payment.failed',
            default => null,
        };
        if ($type === null) {
            $this->error("The payment is {$payment['status']}; Razorpay sends events only once it is captured or failed.");

            return self::FAILURE;
        }

        $body = json_encode([
            'entity' => 'event',
            'account_id' => 'acc_local_replay',
            'event' => $type,
            'contains' => ['payment'],
            'payload' => ['payment' => ['entity' => $payment]],
            'created_at' => time(),
        ]);

        $url = $this->option('url') ?: url('/api/webhooks/razorpay');
        $response = Http::withHeaders([
            'X-Razorpay-Signature' => Razorpay::signWebhook($body),
            'X-Razorpay-Event-Id' => 'evt_replay_'.Str::random(14),
        ])->withBody($body, 'application/json')->post($url);

        $this->line("{$type} for {$payment['id']} → {$url}");
        $this->line("{$response->status()} {$response->body()}");

        return $response->successful() ? self::SUCCESS : self::FAILURE;
    }
}
