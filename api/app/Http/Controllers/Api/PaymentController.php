<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvitationResource;
use App\Models\Invitation;
use App\Models\Order;
use App\Support\PaymentRecorder;
use App\Support\Razorpay;
use App\Support\TemplateForm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

/**
 * Paying for an invitation (PLAN.md > Payments), customer side. An invitation
 * is published only after the server has confirmed the payment with Razorpay:
 * here by the signature Checkout hands back, or by the webhook.
 */
class PaymentController extends Controller
{
    /**
     * POST /api/invitations/{invitation}/checkout: a Razorpay order for this
     * draft, and what Checkout needs to open. The price is the template's,
     * copied onto the order — never anything the browser sends.
     */
    public function checkout(Request $request, Invitation $invitation): JsonResponse
    {
        Gate::authorize('update', $invitation);

        abort_if($invitation->status !== 'draft', 409, 'This invitation is already paid for.');

        $missing = (new TemplateForm($invitation->template->fields_schema))->missing($invitation->data ?? [], $invitation->language);
        abort_if($missing !== [], 422, 'Please fill in everything still needed before paying.');

        abort_unless(Razorpay::configured(), 503, 'Payments are not available right now.');

        $amount = $invitation->template->price_paise;
        try {
            $gateway = Razorpay::createOrder($amount, "inv_{$invitation->id}_".now()->format('ymdHis'), [
                'invitation_id' => (string) $invitation->id,
                'customer_id' => (string) $request->user('web')->id,
            ]);
        } catch (RuntimeException $e) {
            abort(502, $e->getMessage());
        }

        $order = Order::create([
            'user_id' => $request->user('web')->id,
            'invitation_id' => $invitation->id,
            'amount_paise' => $amount,
            'currency' => 'INR',
            'status' => 'created',
            'razorpay_order_id' => $gateway['id'],
        ]);

        $user = $request->user('web');

        return response()->json(['data' => [
            'key_id' => Razorpay::keyId(),
            'order_id' => $order->razorpay_order_id,
            'amount' => $order->amount_paise,
            'currency' => $order->currency,
            'name' => config('invitations.brand.name'),
            'description' => $invitation->template->name,
            'prefill' => ['name' => $user->name, 'email' => $user->email],
            'test_mode' => Razorpay::isTestMode(),
        ]], 201);
    }

    /**
     * POST /api/payments/verify: Checkout's answer, from the browser. The
     * signature proves Razorpay made it for this order; a bad one is refused.
     * Answers with the (now published) invitation.
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'razorpay_order_id' => ['required', 'string', 'max:64'],
            'razorpay_payment_id' => ['required', 'string', 'max:64'],
            'razorpay_signature' => ['required', 'string', 'max:128'],
        ]);

        $order = Order::where('razorpay_order_id', $request->input('razorpay_order_id'))
            ->where('user_id', $request->user('web')->id)
            ->firstOrFail();

        if (! Razorpay::paymentSignatureValid($order->razorpay_order_id, $request->input('razorpay_payment_id'), $request->input('razorpay_signature'))) {
            return response()->json([
                'message' => "We couldn't confirm this payment. If money left your account, your invitation will go live shortly — or contact us.",
            ], 422);
        }

        PaymentRecorder::markPaid($order, $request->input('razorpay_payment_id'), 'verify');

        return InvitationResource::respond($order->invitation);
    }
}
