<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminViews;
use App\Models\Invitation;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/** GET /api/admin/stats: the dashboard's numbers, in Indian time. */
class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $now = Carbon::now(config('invitations.timezone'));
        $today = $now->copy()->startOfDay()->utc();
        $month = $now->copy()->startOfMonth()->utc();
        $paid = fn () => Order::where('status', 'paid');

        return response()->json(['data' => [
            'sales_today_paise' => (int) $paid()->where('paid_at', '>=', $today)->sum('amount_paise'),
            'sales_month_paise' => (int) $paid()->where('paid_at', '>=', $month)->sum('amount_paise'),
            'paid_orders_month' => $paid()->where('paid_at', '>=', $month)->count(),
            'published' => Invitation::where('status', 'published')->count(),
            'drafts' => Invitation::where('status', 'draft')->count(),
            'total_views' => (int) Invitation::sum('view_count'),
            'recent_orders' => $paid()->with(['user', 'invitation', 'paidBy'])->latest('paid_at')->limit(5)->get()
                ->map(fn (Order $order) => AdminViews::orderRow($order)),
            'recent_actions' => AdminViews::history(fn ($query) => $query, 10),
        ]]);
    }
}
