<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvitationResource;
use App\Models\Invitation;
use App\Models\InvitationMedia;
use App\Support\Photo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

/** The couple's photos on an invitation (routes under auth:web). */
class InvitationMediaController extends Controller
{
    /**
     * POST /api/invitations/{invitation}/media (multipart `photo`). JPEG, PNG
     * or WebP by content, up to 5 MB, while under the template's photo limit.
     * Answers with the whole invitation, as the autosave does.
     */
    public function store(Request $request, Invitation $invitation): JsonResponse
    {
        Gate::authorize('update', $invitation);

        $request->validate([
            // mimes checks the file's content, not its name.
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'photo.mimes' => 'Please choose a JPEG, PNG or WebP photo.',
            'photo.max' => 'That photo is over 5 MB. Please choose a smaller one.',
        ]);

        $max = $invitation->template->max_photos;
        $this->ensureRoom($invitation, $max);

        try {
            $stored = Photo::store($request->file('photo'), $invitation->id);
        } catch (Throwable $e) {
            report($e);
            throw ValidationException::withMessages(['photo' => "We couldn't read that photo. Please try another one."]);
        }

        // Checked again under a row lock: two uploads at once can't both take the last place.
        try {
            DB::transaction(function () use ($invitation, $max, $stored) {
                Invitation::whereKey($invitation->id)->lockForUpdate()->first();
                $this->ensureRoom($invitation, $max);

                $invitation->media()->create($stored + [
                    'type' => 'photo',
                    'sort_order' => (int) $invitation->media()->max('sort_order') + 1,
                ]);
                $invitation->touch();
            });
        } catch (Throwable $e) {
            Storage::disk('public')->delete($stored['path']);
            throw $e;
        }

        return InvitationResource::respond($invitation);
    }

    /** DELETE /api/invitations/{invitation}/media/{media}: the row and its file. */
    public function destroy(Invitation $invitation, InvitationMedia $media): JsonResponse
    {
        Gate::authorize('update', $invitation);
        abort_unless($media->invitation_id === $invitation->id, 404);

        $media->delete();
        Storage::disk('public')->delete($media->path);
        $invitation->touch();

        return InvitationResource::respond($invitation);
    }

    private function ensureRoom(Invitation $invitation, int $max): void
    {
        if ($invitation->media()->where('type', 'photo')->count() >= $max) {
            throw ValidationException::withMessages(['photo' => "This invitation already has {$max} photos, the most it can hold."]);
        }
    }
}
