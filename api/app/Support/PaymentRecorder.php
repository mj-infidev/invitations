<?php

namespace App\Support;

use App\Models\Admin;
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
     * By hand ($admin given): the order records who, and the admin log gets the
     * reason, in the same transaction. $paymentId is then the admin's reference
     * (a UPI number, say), kept in the log; the order has no Razorpay payment.
     *
     * @param  string  $source  'verify' | 'webhook' | 'admin', for the log
     */
    public static function markPaid(
        Order $order,
        ?string $paymentId,
        string $source,
        ?Admin $admin = null,
        ?string $reason = null,
    ): bool {
        $changed = DB::transaction(function () use ($order, $paymentId, $admin, $reason) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'paid') {
                return false;
            }

            $locked->forceFill([
                'status' => 'paid',
                'razorpay_payment_id' => $admin ? null : $paymentId,
                'paid_at' => now(),
                'paid_by_admin_id' => $admin?->id,
            ])->save();

            if ($admin) {
                AdminLog::record($admin, 'order.mark_paid', $locked, $reason, array_filter([
                    'reference' => $paymentId,
                    'amount_paise' => $locked->amount_paise,
                    'invitation_id' => $locked->invitation_id,
                ]));
            }

            $invitation = Invitation::withTrashed()->whereKey($locked->invitation_id)->lockForUpdate()->first();
            if ($invitation && ! $invitation->trashed() && $invitation->status === 'draft') {
                InvitationPublisher::publish($invitation);
                // After the (outermost) commit: a slow or failed picture never holds up a payment.
                DB::afterCommit(fn () => self::refreshPicture($invitation));
            } else {
                // Paid twice, or the draft was deleted while paying: the money is
                // recorded; a person needs to refund it (refunds from the panel come later).
                Log::warning("Order {$locked->id} paid but invitation {$locked->invitation_id} was not a draft to publish.");
            }

            return true;
        });

        if ($changed) {
            Log::info("Order {$order->id} paid ({$source}".($admin ? ", admin {$admin->id}" : '').").");
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
