<?php

namespace App\Http\Resources;

use App\Models\Invitation;
use App\Support\TemplateForm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An invitation as its owner sees it in the builder. Never sent to anyone but
 * the owner: it carries the preview link, which opens the draft.
 *
 * @mixin \App\Models\Invitation
 */
class InvitationResource extends JsonResource
{
    /**
     * { data: invitation }, fresh from the database. Wrapped here because Laravel
     * leaves a resource unwrapped when it has a `data` key of its own (the form
     * answers), which would make this one response look unlike the others.
     */
    public static function respond(Invitation $invitation, int $status = 200): JsonResponse
    {
        return response()->json(
            ['data' => (new self($invitation->fresh(['template', 'media'])))->resolve()],
            $status,
        );
    }

    public function toArray(Request $request): array
    {
        $form = new TemplateForm($this->template->fields_schema);

        return [
            'id' => $this->id,
            'status' => $this->status,
            'language' => $this->language,
            'title' => $this->title,
            'event_date' => $this->event_date?->toDateString(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'data' => $this->data,
            'template' => [
                'slug' => $this->template->slug,
                'name' => $this->template->name,
                'max_photos' => $this->template->max_photos,
                'price_paise' => $this->template->price_paise,
            ],
            'photos' => $this->media->where('type', 'photo')->values()->map(fn ($media) => [
                'id' => $media->id,
                'url' => asset('storage/'.$media->path),
                'width' => $media->width,
                'height' => $media->height,
            ]),
            'preview_url' => route('invitation.show', $this->slug).'?preview='.$this->preview_token,
            // The link to share, once paid for. A draft has none.
            'public_url' => $this->status === 'published' ? route('invitation.show', $this->slug) : null,
            'missing' => $form->missing($this->data ?? [], $this->language),
            'can_delete' => $this->status === 'draft',
        ];
    }
}
