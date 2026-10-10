<?php

namespace App\Http\Resources;

use App\Models\AdminAction;
use App\Models\Invitation;
use App\Models\Order;
use App\Models\Template;
use App\Models\User;
use App\Support\TemplateForm;
use Illuminate\Database\Eloquent\Builder;

/**
 * How the admin panel sees things. Only what its screens show: customer data
 * stays to name and email (PLAN.md > Security: admin view).
 */
class AdminViews
{
    public static function invitationRow(Invitation $invitation): array
    {
        return [
            'id' => $invitation->id,
            'title' => $invitation->title,
            'status' => $invitation->status,
            'slug' => $invitation->slug,
            'event_date' => $invitation->event_date?->toDateString(),
            'view_count' => $invitation->view_count,
            'updated_at' => $invitation->updated_at?->toIso8601String(),
            'customer' => $invitation->user ? self::customerRef($invitation->user) : null,
            'template' => $invitation->template?->name,
        ];
    }

    public static function invitationDetail(Invitation $invitation): array
    {
        $invitation->loadMissing(['user', 'template', 'media', 'orders.paidBy']);

        return self::invitationRow($invitation) + [
            'language' => $invitation->language,
            'data' => $invitation->data,
            'published_at' => $invitation->published_at?->toIso8601String(),
            'template_slug' => $invitation->template->slug,
            'fields_schema' => $invitation->template->fields_schema,
            'price_paise' => $invitation->template->price_paise,
            'photos' => $invitation->media->where('type', 'photo')->values()->map(fn ($media) => [
                'id' => $media->id,
                'url' => asset('storage/'.$media->path),
            ]),
            'preview_url' => route('invitation.show', $invitation->slug).'?preview='.$invitation->preview_token,
            'public_url' => $invitation->status === 'published' ? route('invitation.show', $invitation->slug) : null,
            'missing' => (new TemplateForm($invitation->template->fields_schema))->missing($invitation->data ?? [], $invitation->language),
            'orders' => $invitation->orders->sortByDesc('id')->values()->map(fn (Order $order) => self::orderRow($order, withInvitation: false)),
            'history' => self::history(fn (Builder $query) => $query
                ->where(fn ($q) => $q->where('subject_type', 'invitation')->where('subject_id', $invitation->id))
                ->orWhere(fn ($q) => $q->where('subject_type', 'order')->whereIn('subject_id', $invitation->orders->pluck('id')))
                ->orWhere(fn ($q) => $q->where('subject_type', 'media')->where('details->invitation_id', $invitation->id))),
        ];
    }

    public static function orderRow(Order $order, bool $withInvitation = true): array
    {
        return [
            'id' => $order->id,
            'status' => $order->status,
            'amount_paise' => $order->amount_paise,
            'currency' => $order->currency,
            'razorpay_order_id' => $order->razorpay_order_id,
            'razorpay_payment_id' => $order->razorpay_payment_id,
            'paid_at' => $order->paid_at?->toIso8601String(),
            'created_at' => $order->created_at?->toIso8601String(),
            // Razorpay, or the admin who marked it paid by hand.
            'paid_by' => $order->paidBy?->name,
            'can_mark_paid' => in_array($order->status, ['created', 'failed'], true),
        ] + ($withInvitation ? [
            'customer' => $order->user ? self::customerRef($order->user) : null,
            'invitation' => $order->invitation ? [
                'id' => $order->invitation->id,
                'title' => $order->invitation->title,
                'status' => $order->invitation->status,
            ] : null,
        ] : []);
    }

    public static function template(Template $template): array
    {
        return $template->only(['id', 'slug', 'name', 'category', 'tradition', 'price_paise', 'max_photos', 'is_active', 'sort_order']);
    }

    public static function customerRef(User $user): array
    {
        return ['id' => $user->id, 'name' => $user->name, 'email' => $user->email];
    }

    /** The newest admin actions matching a query, newest first. */
    public static function history(callable $scope, int $limit = 50): array
    {
        return AdminAction::with('admin:id,name')
            ->where($scope)
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (AdminAction $action) => self::action($action))
            ->all();
    }

    public static function action(AdminAction $action): array
    {
        return [
            'id' => $action->id,
            'action' => $action->action,
            'admin' => $action->admin?->name,
            'subject_type' => $action->subject_type,
            'subject_id' => $action->subject_id,
            'reason' => $action->reason,
            'details' => $action->details,
            'created_at' => $action->created_at?->toIso8601String(),
        ];
    }

    /** A LIKE pattern for a search box: % and _ typed by the admin are literal. */
    public static function like(string $search): string
    {
        return '%'.addcslashes($search, '%_\\').'%';
    }
}
