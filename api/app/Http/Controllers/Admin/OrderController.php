<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminViews;
use App\Models\Order;
use App\Support\PaymentRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Orders, and marking one paid by hand. */
class OrderController extends Controller
{
    /** GET /api/admin/orders?status=&q=: by order number, Razorpay id or customer. */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(Order::STATUSES)],
        ]);

        $query = Order::with(['user:id,name,email', 'invitation:id,title,status', 'paidBy:id,name'])->latest('id');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = trim((string) $request->input('q'))) {
            $like = AdminViews::like($search);
            $query->where(fn ($q) => $q
                ->when(ctype_digit($search), fn ($q) => $q->orWhere('id', (int) $search))
                ->orWhere('razorpay_order_id', 'like', $like)
                ->orWhere('razorpay_payment_id', 'like', $like)
                ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $like)->orWhere('email', 'like', $like)));
        }

        return response()->json($query->paginate(25)->through(fn (Order $order) => AdminViews::orderRow($order)));
    }

    /**
     * POST /api/admin/orders/{order}/mark-paid { reason, reference? }: through
     * the same PaymentRecorder as Razorpay's payments, so it publishes the
     * invitation the same way. The order records the admin; the log the reason.
     */
    public function markPaid(Request $request, Order $order): JsonResponse
    {
        $input = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);
        abort_unless(in_array($order->status, ['created', 'failed'], true), 409, 'Only an unpaid order can be marked paid.');

        PaymentRecorder::markPaid($order, $input['reference'] ?? null, 'admin', $request->user('admin'), $input['reason']);

        return response()->json(['data' => AdminViews::orderRow($order->fresh(['user', 'invitation', 'paidBy']))]);
    }
}
