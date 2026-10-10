<?php

namespace App\Support;

use App\Models\Invitation;
use Illuminate\Support\Str;

/**
 * Makes a paid draft public. Called inside PaymentRecorder's transaction (and,
 * from Step 6, the admin's "mark paid"). Once published:
 *  - the link is name-based (`priya-karthik-x7k2`) and never changes again;
 *  - the page opens for anyone with the link, without the watermark
 *    (InvitationPage shows the watermark only on drafts).
 */
class InvitationPublisher
{
    public static function publish(Invitation $invitation): void
    {
        $invitation->forceFill([
            'slug' => self::publicSlug($invitation),
            'status' => 'published',
            'published_at' => now(),
        ])->save();
    }

    /**
     * The English names plus four random characters. Tamil names make no
     * letters a link can use, so a Tamil-only invitation is `wedding-x7k2`.
     */
    public static function publicSlug(Invitation $invitation): string
    {
        $names = Str::slug(trim(data_get($invitation->data, 'bride.name.en').' '.data_get($invitation->data, 'groom.name.en')));
        $base = Str::limit($names, 40, '') ?: 'wedding';
        $base = rtrim($base, '-');

        do {
            $slug = $base.'-'.Str::lower(Str::random(4));
        } while (Invitation::withTrashed()->where('slug', $slug)->exists());

        return $slug;
    }
}
