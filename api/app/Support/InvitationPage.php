<?php

namespace App\Support;

use App\Models\Invitation;
use Illuminate\Support\Carbon;

/**
 * Everything the invitation page (/i/{slug}) shows, worked out once from an
 * invitation: which languages to print, dates and times in each language, the
 * WhatsApp preview tags, photos, and the JSON the viewer script reads.
 *
 * Text values come in the shape of a bilingual field ({"ta": …, "en": …}),
 * already narrowed to the languages guests see; <x-bi> prints them.
 */
class InvitationPage
{
    /** `data` with avatar defaults filled in and, for the demo, dates moved forward. */
    public readonly array $data;

    /** Languages printed, in display order: Tamil first when both. */
    public readonly array $languages;

    public readonly bool $isDemo;

    public function __construct(
        public readonly Invitation $invitation,
        public readonly bool $watermark,
    ) {
        $this->isDemo = $invitation->slug === config('invitations.demo_slug');
        $this->languages = match ($invitation->language) {
            'ta' => ['ta'],
            'en' => ['en'],
            default => ['ta', 'en'],
        };

        $data = $invitation->data ?? [];
        $data['avatar'] = Avatar::resolve($data['avatar'] ?? null);
        $this->data = $this->isDemo ? self::moveDemoDates($data) : $data;
    }

    /** The page's `<html lang>`: the first language shown. */
    public function lang(): string
    {
        return $this->languages[0];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->data, $key, $default);
    }

    /**
     * A bilingual field's text in the languages shown, blanks dropped. If none
     * of those languages was filled in, the other one is shown rather than
     * nothing: a name typed only in English still appears on a Tamil invitation.
     */
    public function text(string|array|null $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $text = [];
        foreach ($this->languages as $lang) {
            if (filled($value[$lang] ?? null)) {
                $text[$lang] = trim($value[$lang]);
            }
        }

        if ($text === []) {
            foreach (['ta', 'en'] as $lang) {
                if (filled($value[$lang] ?? null)) {
                    return [$lang => trim($value[$lang])];
                }
            }
        }

        return $text;
    }

    /** A line from lang/{ta,en}/invitation.php, in each language shown. */
    public function label(string $key, array $replace = []): array
    {
        $label = [];
        foreach ($this->languages as $lang) {
            $label[$lang] = __("invitation.{$key}", $replace, $lang);
        }

        return $label;
    }

    /** "வியாழக்கிழமை, 12 நவம்பர் 2026" / "Thursday, 12 November 2026". */
    public function date(?string $ymd): array
    {
        if (blank($ymd)) {
            return [];
        }

        $date = Carbon::parse($ymd);
        $text = [];
        foreach ($this->languages as $lang) {
            $text[$lang] = $date->locale($lang)->isoFormat('dddd, D MMMM YYYY');
        }

        return $text;
    }

    /** "காலை 6:30" / "6:30 AM". */
    public function time(?string $hm): array
    {
        if (blank($hm)) {
            return [];
        }

        $time = Carbon::createFromFormat('H:i', $hm);
        $text = [];
        foreach ($this->languages as $lang) {
            $text[$lang] = $lang === 'ta'
                ? self::tamilDayPart($time->hour).' '.$time->format('g:i')
                : $time->format('g:i A');
        }

        return $text;
    }

    /**
     * Carbon's Tamil AM/PM words are classical (எற்பாடு, வைகறை); these are the
     * ones printed on invitations today.
     */
    private static function tamilDayPart(int $hour): string
    {
        return match (true) {
            $hour < 6 => 'அதிகாலை',
            $hour < 12 => 'காலை',
            $hour < 16 => 'மதியம்',
            $hour < 19 => 'மாலை',
            default => 'இரவு',
        };
    }

    /** Language of the WhatsApp card: English unless the invitation is Tamil only. */
    private function previewLang(): string
    {
        return $this->invitation->language === 'ta' ? 'ta' : 'en';
    }

    /** "Priya weds Karthik": the stored title, or built from the names for a fresh draft. */
    public function title(): string
    {
        if (filled($this->invitation->title)) {
            return $this->invitation->title;
        }

        $bride = $this->get('bride.name.en') ?: $this->get('bride.name.ta');
        $groom = $this->get('groom.name.en') ?: $this->get('groom.name.ta');

        return $bride && $groom ? "{$bride} weds {$groom}" : __('invitation.wedding_invitation', [], $this->previewLang());
    }

    /** "12 November 2026 · Madurai": the date, then the town (last part of the venue address). */
    public function previewDescription(): string
    {
        $lang = $this->previewLang();
        $parts = [];

        if ($date = $this->get('muhurtham.date')) {
            $parts[] = Carbon::parse($date)->locale($lang)->isoFormat('D MMMM YYYY');
        }

        $address = $this->get("venue.address.{$lang}") ?: $this->get('venue.address.ta') ?: $this->get('venue.address.en');
        $venue = $this->get("venue.name.{$lang}") ?: $this->get('venue.name.ta') ?: $this->get('venue.name.en');
        if ($address) {
            $parts[] = trim(collect(preg_split('/[,\n]/', $address))->filter(fn ($part) => trim($part) !== '')->last());
        } elseif ($venue) {
            $parts[] = $venue;
        }

        return implode(' · ', $parts);
    }

    /**
     * The Google Maps link, only if it is an http(s) address. It is typed by the
     * customer, and a `javascript:` link would run on the guest's tap.
     */
    public function mapUrl(): ?string
    {
        $url = trim((string) $this->get('venue.map_url'));

        return preg_match('#^https?://#i', $url) ? $url : null;
    }

    /** Made when the invitation is published (Step 5); until then there is no preview picture. */
    public function previewImage(): ?string
    {
        return $this->invitation->og_image_path ? asset('storage/'.$this->invitation->og_image_path) : null;
    }

    /**
     * Uploaded photos. asset() rather than Storage::url(), so the links follow
     * the address the page was opened on (a phone on the local network too).
     */
    public function photos(): array
    {
        return $this->invitation->media
            ->where('type', 'photo')
            ->map(fn ($media) => [
                'url'    => asset('storage/'.$media->path),
                'width'  => $media->width,
                'height' => $media->height,
            ])
            ->values()
            ->all();
    }

    /**
     * Empty frames in the photo scene when there are no photos: on the demo and
     * on a draft preview, so the customer sees where theirs will go. A published
     * invitation without photos skips the scene.
     */
    public function placeholderPhotoCount(): int
    {
        if ($this->photos() !== [] || ! ($this->isDemo || $this->watermark)) {
            return 0;
        }

        return min(4, (int) $this->invitation->template->max_photos);
    }

    public function music(): ?string
    {
        $music = $this->invitation->media->firstWhere('type', 'music');

        return $music ? asset('storage/'.$music->path) : null;
    }

    /** The muhurtham as an ISO time in Indian time, for the countdown. */
    public function muhurthamAt(): ?string
    {
        $date = $this->get('muhurtham.date');
        if (blank($date)) {
            return null;
        }

        $time = $this->get('muhurtham.time') ?: '00:00';

        return Carbon::parse("{$date} {$time}", config('invitations.timezone'))->toIso8601String();
    }

    /** The Vite entry of this invitation's template. */
    public function viewerEntry(): string
    {
        return "resources/js/viewer/templates/{$this->invitation->template->slug}/index.js";
    }

    /** Read by the viewer from <script id="invitation-data">. */
    public function viewerJson(): string
    {
        return json_encode([
            'template'    => $this->invitation->template->slug,
            'languages'   => $this->languages,
            'muhurthamAt' => $this->muhurthamAt(),
            'music'       => $this->music(),
            'data'        => $this->data,
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Move the demo's muhurtham to `demo_days_ahead` days from today, and its
     * other events by the same number of days so they keep their order. The
     * typed Tamil calendar date would no longer match, so it is dropped.
     */
    private static function moveDemoDates(array $data): array
    {
        $muhurtham = data_get($data, 'muhurtham.date');
        if (blank($muhurtham)) {
            return $data;
        }

        $timezone = config('invitations.timezone');
        $target = Carbon::now($timezone)->startOfDay()->addDays(config('invitations.demo_days_ahead'));
        $shift = (int) round(Carbon::parse($muhurtham, $timezone)->startOfDay()->diffInDays($target, false));
        $move = fn (?string $date) => blank($date) ? $date : Carbon::parse($date)->addDays($shift)->toDateString();

        data_set($data, 'muhurtham.date', $move($muhurtham));
        unset($data['muhurtham']['tamil_date']);

        foreach ($data['events'] ?? [] as $i => $event) {
            $data['events'][$i]['date'] = $move($event['date'] ?? null);
        }

        return $data;
    }
}
