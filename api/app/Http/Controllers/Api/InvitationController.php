<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvitationResource;
use App\Models\Invitation;
use App\Models\Template;
use App\Support\Avatar;
use App\Support\Languages;
use App\Support\PaymentRecorder;
use App\Support\TemplateForm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * The signed-in customer's invitations (routes under auth:web). Ownership is
 * checked by InvitationPolicy: anyone else's invitation is a 404.
 */
class InvitationController extends Controller
{
    /** GET /api/invitations: mine, latest first. Light: no form data. */
    public function index(Request $request): JsonResponse
    {
        $invitations = $request->user('web')->invitations()
            ->with(['template:id,slug,name', 'media' => fn ($query) => $query->where('type', 'photo')])
            ->latest('updated_at')
            ->get();

        return response()->json(['data' => $invitations->map(fn (Invitation $invitation) => [
            'id' => $invitation->id,
            'status' => $invitation->status,
            'title' => $invitation->title,
            'event_date' => $invitation->event_date?->toDateString(),
            'updated_at' => $invitation->updated_at?->toIso8601String(),
            'template' => ['slug' => $invitation->template->slug, 'name' => $invitation->template->name],
            'cover_url' => ($photo = $invitation->media->first()) ? asset('storage/'.$photo->path) : null,
            'preview_url' => route('invitation.show', $invitation->slug).'?preview='.$invitation->preview_token,
            'public_url' => $invitation->status === 'published' ? route('invitation.show', $invitation->slug) : null,
            'can_delete' => $invitation->status === 'draft',
        ])]);
    }

    /**
     * POST /api/invitations { template }: a new draft. Its slug is a random
     * placeholder (there are no names yet); the name-based link is set once,
     * at publish (Step 5). Drafts open only with the preview token.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'template' => ['required', 'string', Rule::exists('templates', 'slug')->where('is_active', true)],
        ]);

        $template = Template::where('slug', $request->input('template'))->firstOrFail();
        $form = new TemplateForm($template->fields_schema);

        $invitation = $request->user('web')->invitations()->create([
            'template_id' => $template->id,
            'slug' => self::draftSlug(),
            'status' => 'draft',
            'language' => Languages::forNewDraft(),
            'data' => $form->clean([], ['avatar' => Avatar::defaults()]),
            'preview_token' => Str::random(40),
        ]);

        return InvitationResource::respond($invitation, 201);
    }

    /** GET /api/invitations/{invitation} */
    public function show(Invitation $invitation): JsonResponse
    {
        Gate::authorize('view', $invitation);

        return InvitationResource::respond($invitation);
    }

    /**
     * PUT /api/invitations/{invitation} { language, data }: the builder's
     * autosave, the whole form each time. Only the template's fields are kept
     * (and the avatar, which the form never sends); gaps are fine in a draft.
     */
    public function update(Request $request, Invitation $invitation): JsonResponse
    {
        Gate::authorize('update', $invitation);

        $form = new TemplateForm($invitation->template->fields_schema);
        [$rules, $names, $messages] = $form->rules();
        $request->validate($rules + ['language' => ['required', Rule::in(['ta', 'en', 'both'])]], $messages, $names);

        $language = $request->input('language');
        $data = $form->clean($request->input('data', []), $invitation->data ?? []);

        $invitation->update(['language' => $language, 'data' => $data] + TemplateForm::derived($data, $language));

        // Published: the WhatsApp picture follows the names and date (redrawn only if they changed).
        if ($invitation->status === 'published') {
            PaymentRecorder::refreshPicture($invitation);
        }

        return InvitationResource::respond($invitation);
    }

    /**
     * DELETE /api/invitations/{invitation}: drafts only. Invitations are soft
     * deleted, so the database cascade on invitation_media never fires: the
     * media rows go here, and their files once the rows are gone.
     */
    public function destroy(Invitation $invitation): JsonResponse
    {
        Gate::authorize('delete', $invitation);

        $paths = $invitation->media()->pluck('path')->all();

        DB::transaction(function () use ($invitation) {
            $invitation->media()->delete();
            $invitation->delete();
        });

        Storage::disk('public')->delete($paths);

        return response()->json(['data' => null]);
    }

    /** "draft-k3x9q2m7p1": letters and digits only, as the /i/{slug} route allows. */
    private static function draftSlug(): string
    {
        do {
            $slug = 'draft-'.Str::lower(Str::random(10));
        } while (Invitation::withTrashed()->where('slug', $slug)->exists());

        return $slug;
    }
}
