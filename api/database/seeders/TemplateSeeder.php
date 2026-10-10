<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    /**
     * The one version-1 template. Price and photo limit are placeholders until
     * the open decisions in PLAN.md are settled; the admin panel can change them.
     */
    public function run(): void
    {
        Template::updateOrCreate(
            ['slug' => 'tamil-hindu-wedding'],
            [
                'name'           => 'Tamil Hindu wedding',
                'category'       => 'wedding',
                'tradition'      => 'tamil_hindu',
                'price_paise'    => 49900, // ₹499
                'fields_schema'  => self::tamilHinduWeddingFields(),
                'thumbnail_path' => 'templates/tamil-hindu-wedding/thumbnail.webp',
                'max_photos'     => 6,
                'is_active'      => true,
                'sort_order'     => 0,
            ],
        );
    }

    /**
     * The details form. Format: PLAN.md > Template form fields.
     */
    public static function tamilHinduWeddingFields(): array
    {
        return [
            'version' => 1,
            'groups'  => [
                [
                    'key'    => 'couple',
                    'label'  => ['en' => 'The couple', 'ta' => 'மணமக்கள்'],
                    'fields' => [
                        ['key' => 'bride.name', 'type' => 'text', 'bilingual' => true, 'required' => true, 'max' => 60,
                            'label' => ['en' => "Bride's name", 'ta' => 'மணமகள் பெயர்']],
                        ['key' => 'groom.name', 'type' => 'text', 'bilingual' => true, 'required' => true, 'max' => 60,
                            'label' => ['en' => "Groom's name", 'ta' => 'மணமகன் பெயர்']],
                    ],
                ],
                [
                    'key'    => 'families',
                    'label'  => ['en' => 'Families', 'ta' => 'குடும்பத்தினர்'],
                    'fields' => [
                        ['key' => 'bride.parents', 'type' => 'text', 'bilingual' => true, 'required' => true, 'max' => 120,
                            'label' => ['en' => "Bride's parents", 'ta' => 'மணமகளின் பெற்றோர்']],
                        ['key' => 'groom.parents', 'type' => 'text', 'bilingual' => true, 'required' => true, 'max' => 120,
                            'label' => ['en' => "Groom's parents", 'ta' => 'மணமகனின் பெற்றோர்']],
                        ['key' => 'message', 'type' => 'textarea', 'bilingual' => true, 'required' => false, 'max' => 300,
                            'label' => ['en' => 'Message from the family', 'ta' => 'குடும்பத்தினரின் செய்தி']],
                    ],
                ],
                [
                    'key'    => 'muhurtham',
                    'label'  => ['en' => 'Muhurtham', 'ta' => 'முகூர்த்தம்'],
                    'fields' => [
                        ['key' => 'muhurtham.date', 'type' => 'date', 'required' => true,
                            'label' => ['en' => 'Date', 'ta' => 'தேதி']],
                        ['key' => 'muhurtham.time', 'type' => 'time', 'required' => true,
                            'label' => ['en' => 'Time', 'ta' => 'நேரம்']],
                        // Typed by the family, never calculated (PLAN.md > Tamil language support).
                        // lang: shown only when Tamil is one of the languages (App\Support\Languages).
                        ['key' => 'muhurtham.tamil_date', 'type' => 'text', 'lang' => 'ta', 'required' => false, 'max' => 40,
                            'label' => ['en' => 'Tamil calendar date', 'ta' => 'தமிழ் தேதி'],
                            'help'  => ['en' => 'For example ஐப்பசி 26', 'ta' => 'உதாரணம்: ஐப்பசி 26']],
                    ],
                ],
                [
                    'key'    => 'venue',
                    'label'  => ['en' => 'Venue', 'ta' => 'மண்டபம்'],
                    'fields' => [
                        ['key' => 'venue.name', 'type' => 'text', 'bilingual' => true, 'required' => true, 'max' => 100,
                            'label' => ['en' => 'Venue name', 'ta' => 'மண்டபத்தின் பெயர்']],
                        ['key' => 'venue.address', 'type' => 'textarea', 'bilingual' => true, 'required' => true, 'max' => 300,
                            'label' => ['en' => 'Address', 'ta' => 'முகவரி']],
                        ['key' => 'venue.map_url', 'type' => 'url', 'required' => false, 'max' => 500,
                            'label' => ['en' => 'Google Maps link', 'ta' => 'கூகுள் மேப்ஸ் இணைப்பு']],
                    ],
                ],
                [
                    'key'    => 'other_events',
                    'label'  => ['en' => 'Other events', 'ta' => 'பிற நிகழ்வுகள்'],
                    'fields' => [
                        ['key' => 'events', 'type' => 'repeater', 'required' => false, 'max_items' => 4,
                            'label'  => ['en' => 'Event', 'ta' => 'நிகழ்வு'],
                            'fields' => [
                                ['key' => 'name', 'type' => 'text', 'bilingual' => true, 'required' => true, 'max' => 60,
                                    'label' => ['en' => 'Event name', 'ta' => 'நிகழ்வின் பெயர்'],
                                    'help'  => ['en' => 'For example Reception', 'ta' => 'உதாரணம்: வரவேற்பு']],
                                ['key' => 'date', 'type' => 'date', 'required' => true,
                                    'label' => ['en' => 'Date', 'ta' => 'தேதி']],
                                ['key' => 'time', 'type' => 'time', 'required' => false,
                                    'label' => ['en' => 'Time', 'ta' => 'நேரம்']],
                                ['key' => 'place', 'type' => 'text', 'bilingual' => true, 'required' => false, 'max' => 150,
                                    'label' => ['en' => 'Place', 'ta' => 'இடம்']],
                            ]],
                    ],
                ],
            ],
        ];
    }
}
