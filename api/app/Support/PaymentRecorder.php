<?php

namespace App\Support;

use App\Models\Invitation;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The only place an order becomes paid, and safe to run twice: the browser's
 * verify call and Razorpay's webhook race, and whichever arrives first wins.
 * The second finds the order already paid and changes nothing.
 */
class PaymentRecorder
{
    /**
     * Marks the order paid and publishes its invitation, under a lock on the
     * order row. Returns false when the order was already paid.
     *
     * @param  string  $source  'verify' | 'webhook' | 'admin' (Step 6), for the log
     */
    public static function markPaid(Order $order, string $paymentId, string $source): bool
    {
        $published = null;

        $changed = DB::transaction(function () use ($order, $paymentId, &$published) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'paid') {
                return false;
            }

            $locked->forceFill([
                'status' => 'paid',
                'razorpay_payment_id' => $paymentId,
                'paid_at' => now(),
            ])->save();

            $invitation = Invitation::withTrashed()->whereKey($locked->invitation_id)->lockForUpdate()->first();
            if ($invitation && ! $invitation->trashed() && $invitation->status === 'draft') {
                InvitationPublisher::publish($invitation);
                $published = $invitation;
            } else {
                // Paid twice, or the draft was deleted while paying: the money is
                // recorded; it needs a person (a refund, from Step 6's admin panel).
                Log::warning("Order {$locked->id} paid but invitation {$locked->invitation_id} was not a draft to publish.");
            }

            return true;
        });

        if ($changed) {
            Log::info("Order {$order->id} paid ({$source}), payment {$paymentId}.");
        }

        // After the commit: a slow or failed picture never holds up a payment.
        if ($published) {
            self::refreshPicture($published);
        }

        return $changed;
    }

    /**
     * A failed attempt. Only an unpaid order is marked: a later attempt on the
     * same Razorpay order can still succeed, and `failed` can still become `paid`.
     */
    public static function markFailed(Order $order): void
    {
        Order::whereKey($order->id)->where('status', 'created')->update(['status' => 'failed', 'updated_at' => now()]);
    }

    public static function refreshPicture(Invitation $invitation): void
    {
        try {
            PreviewPicture::refresh($invitation);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
