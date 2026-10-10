<?php

namespace Database\Seeders;

use App\Models\Invitation;
use App\Models\Template;
use App\Models\User;
use App\Support\Avatar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoInvitationSeeder extends Seeder
{
    /**
     * The live demo on the landing page (/i/priya-karthik-demo), published so it
     * opens without a preview token. It needs an owner, so a demo customer is
     * made too; that account has no Google id or password and can't sign in.
     */
    public function run(): void
    {
        $owner = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            ['name' => 'Demo customer', 'locale' => 'en'],
        );

        $template = Template::where('slug', 'tamil-hindu-wedding')->firstOrFail();

        $invitation = Invitation::withTrashed()->firstOrNew(['slug' => 'priya-karthik-demo']);
        $invitation->fill([
            'user_id'      => $owner->id,
            'template_id'  => $template->id,
            'status'       => 'published',
            'language'     => 'both',
            'title'        => 'Priya weds Karthik',
            'event_date'   => '2026-11-12',
            'data'         => self::data(),
            'published_at' => $invitation->published_at ?? now(),
            'expires_at'   => null, // the demo never expires
        ]);
        // Kept across re-seeds, so a preview link someone saved keeps working.
        $invitation->preview_token ??= Str::random(40);
        $invitation->deleted_at = null;
        $invitation->save();
    }

    /** Filled in the format of the template's fields_schema (PLAN.md > Template form fields). */
    public static function data(): array
    {
        return [
            'bride' => [
                'name'    => ['ta' => 'பிரியா', 'en' => 'Priya'],
                'parents' => ['ta' => 'திரு. ராஜேந்திரன் – திருமதி. லட்சுமி', 'en' => 'Mr. Rajendran & Mrs. Lakshmi'],
            ],
            'groom' => [
                'name'    => ['ta' => 'கார்த்திக்', 'en' => 'Karthik'],
                'parents' => ['ta' => 'திரு. சுப்பிரமணியன் – திருமதி. மீனாட்சி', 'en' => 'Mr. Subramanian & Mrs. Meenakshi'],
            ],
            'message' => [
                'ta' => 'உங்கள் வருகையையும் ஆசிகளையும் அன்புடன் எதிர்பார்க்கிறோம்.',
                'en' => 'We look forward to your presence and blessings.',
            ],
            'muhurtham' => [
                'date'       => '2026-11-12',
                'time'       => '06:30',
                'tamil_date' => 'ஐப்பசி 26',
            ],
            'venue' => [
                'name'    => ['ta' => 'ஸ்ரீ மீனாட்சி திருமண மண்டபம்', 'en' => 'Sri Meenakshi Kalyana Mandapam'],
                'address' => ['ta' => 'மேலமாசி வீதி, மதுரை', 'en' => 'West Masi Street, Madurai'],
                'map_url' => 'https://maps.google.com/?q=Madurai',
            ],
            'events' => [
                [
                    'name'  => ['ta' => 'வரவேற்பு', 'en' => 'Reception'],
                    'date'  => '2026-11-11',
                    'time'  => '18:30',
                    'place' => ['ta' => 'அதே மண்டபம்', 'en' => 'Same venue'],
                ],
            ],
            // Reserved for the couple customizer; every invitation starts with the defaults.
            'avatar' => Avatar::defaults(),
        ];
    }
}
