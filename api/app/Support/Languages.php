<?php

namespace App\Support;

/**
 * Which languages invitations are shown in, behind one setting:
 * TAMIL_ENABLED (config invitations.tamil_enabled), off for now.
 *
 * Off, every invitation behaves as English-only — the builder offers no
 * language choice, the page has no தமிழ் / English switch, only English is
 * required — whatever is stored. The Tamil code and the stored Tamil text and
 * `language` values are kept, so switching it on brings them back.
 */
final class Languages
{
    public static function tamilEnabled(): bool
    {
        return (bool) config('invitations.tamil_enabled');
    }

    /** The language an invitation is shown in: its own, or English while Tamil is off. */
    public static function effective(?string $stored): string
    {
        return self::tamilEnabled() ? ($stored ?: 'both') : 'en';
    }

    /** A new draft's language. */
    public static function forNewDraft(): string
    {
        return self::tamilEnabled() ? 'both' : 'en';
    }
}
