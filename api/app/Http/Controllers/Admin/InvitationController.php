<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminViews;
use App\Models\Invitation;
use App\Models\InvitationMedia;
use App\Models\Order;
use App\Support\AdminLog;
use App\Support\PaymentRecorder;
use App\Support\TemplateForm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Invitations, as the admin panel sees them. Admins can correct details in any
 * status (customers stop at the event date); every change is logged with what
 * it was before.
 */
class InvitationController extends Controller
{
    /** GET /api/admin/invitations?q=&status=: search by title, link, or customer. */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(Invitation::STATUSES)],
        ]);

        $query = Invitation::with(['user:id,name,email', 'template:id,name'])->latest('updated_at');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Titles carry the English names; Tamil names are stored escaped in `data`,
        // so a Tamil search finds nothing yet.
        if ($search = trim((string) $request->input('q'))) {
            $like = AdminViews::like($search);
            $query->where(fn ($q) => $q->where('title', 'like', $like)
                ->orWhere('slug', 'like', $like)
                ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $like)->orWhere('email', 'like', $like)));
        }

        return response()->json($query->paginate(25)->through(fn (Invitation $invitation) => AdminViews::invitationRow($invitation)));
    }

    /** GET /api/admin/invitations/{invitation}: everything the edit screen needs. */
    public function show(Invitation $invitation): JsonResponse
    {
        return response()->json(['data' => AdminViews::invitationDetail($invitation)]);
    }

    /**
     * PUT /api/admin/invitations/{invitation} { language, data, note? }: a
     * correction. Validated as the customer's builder is; the avatar is kept.
     * Logged with each changed field before and after; nothing changed, nothing logged.
     */
    public function update(Request $request, Invitation $invitation): JsonResponse
    {
        $form = new TemplateForm($invitation->template->fields_schema);
        [$rules, $names, $messages] = $form->rules();
        $request->validate($rules + [
            'language' => ['required', Rule::in(Invitation::LANGUAGES)],
            'note' => ['nullable', 'string', 'max:500'],
        ], $messages, $names);

        $language = $request->input('language');
        $data = $form->clean($request->input('data', []), $invitation->data ?? []);
        $changes = AdminLog::changes(
            ['language' => $invitation->language] + $form->clean($invitation->data ?? []),
            ['language' => $language] + $form->clean($data),
        );

        if ($changes !== []) {
            DB::transaction(function () use ($request, $invitation, $language, $data, $changes) {
                $invitation->update(['language' => $language, 'data' => $data] + TemplateForm::derived($data, $language));
                AdminLog::record($request->user('admin'), 'invitation.update', $invitation, $request->input('note'), ['changes' => $changes]);
            });

            // A published invitation's WhatsApp picture follows its names and date.
            if ($invitation->status === 'published') {
                PaymentRecorder::refreshPicture($invitation);
            }
        }

        return response()->json([
            'data' => AdminViews::invitationDetail($invitation->fresh()),
            'meta' => ['changed' => count($changes)],
        ]);
    }

    /** POST /api/admin/invitations/{invitation}/disable { reason }: the link shows "has ended". */
    public function disable(Request $request, Invitation $invitation): JsonResponse
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']])['reason'];
        abort_unless(in_array($invitation->status, ['draft', 'published'], true), 409, 'Only a draft or a published invitation can be disabled.');

        DB::transaction(function () use ($request, $invitation, $reason) {
            $from = $invitation->status;
            $invitation->update(['status' => 'disabled']);
            AdminLog::record($request->user('admin'), 'invitation.disable', $invitation, $reason, ['from' => $from]);
        });

        return response()->json(['data' => AdminViews::invitationDetail($invitation->fresh())]);
    }

    /** POST /api/admin/invitations/{invitation}/enable { reason }: back to published, or draft if never paid. */
    public function enable(Request $request, Invitation $invitation): JsonResponse
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']])['reason'];
        abort_unless($invitation->status === 'disabled', 409, 'Only a disabled invitation can be enabled.');

        DB::transaction(function () use ($request, $invitation, $reason) {
            $to = $invitation->published_at ? 'published' : 'draft';
            $invitation->update(['status' => $to]);
            AdminLog::record($request->user('admin'), 'invitation.enable', $invitation, $reason, ['to' => $to]);
        });

        return response()->json(['data' => AdminViews::invitationDetail($invitation->fresh())]);
    }

    /**
     * POST /api/admin/invitations/{invitation}/mark-paid { reason, reference? }:
     * a customer who paid outside Razorpay. Makes an order at the template's
     * price and marks it paid through PaymentRecorder, exactly as a Razorpay
     * payment would be: same publishing, link and picture.
     */
    public function markPaid(Request $request, Invitation $invitation): JsonResponse
    {
        $input = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);
        abort_unless($invitation->status === 'draft', 409, 'Only a draft can be marked paid.');

        DB::transaction(function () use ($request, $invitation, $input) {
            $order = Order::create([
                'user_id' => $invitation->user_id,
                'invitation_id' => $invitation->id,
                'amount_paise' => $invitation->template->price_paise,
                'currency' => 'INR',
                'status' => 'created',
            ]);
            PaymentRecorder::markPaid($order, $input['reference'] ?? null, 'admin', $request->user('admin'), $input['reason']);
        });

        return response()->json(['data' => AdminViews::invitationDetail($invitation->fresh())]);
    }

    /** DELETE /api/admin/invitations/{invitation}/media/{media} { reason }: a photo removed, file and all. */
    public function removeMedia(Request $request, Invitation $invitation, InvitationMedia $media): JsonResponse
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']])['reason'];
        abort_unless($media->invitation_id === $invitation->id, 404);

        DB::transaction(function () use ($request, $invitation, $media, $reason) {
            $media->delete();
            $invitation->touch();
            AdminLog::record($request->user('admin'), 'media.remove', $media, $reason, ['invitation_id' => $invitation->id, 'path' => $media->path]);
        });
        Storage::disk('public')->delete($media->path);

        return response()->json(['data' => AdminViews::invitationDetail($invitation->fresh())]);
    }
}
