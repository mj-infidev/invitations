<?php

namespace App\Support;

/**
 * The cartoon couple of the closing scene, stored as `data.avatar` on an
 * invitation. Each person is drawn from separate parts (face, hair, outfit,
 * jewellery, garland), so a later customizer only has to change these values.
 * Until then every invitation gets the defaults.
 *
 * Values are names, never raw colours: the page maps them to colours here, so
 * stored data can't inject CSS and a palette can be retuned for everyone.
 */
final class Avatar
{
    public const SKIN = [
        'light'  => '#f2cfa8',
        'medium' => '#d8a06a',
        'tan'    => '#b67a4b',
        'deep'   => '#7c4a2c',
    ];

    public const HAIR_COLOR = [
        'black'      => '#1c1410',
        'dark_brown' => '#3d2617',
    ];

    public const OUTFIT_COLOR = [
        'maroon' => '#8c1d3a',
        'green'  => '#1e6b4b',
        'gold'   => '#c8952c',
        'blue'   => '#1f4e8c',
        'cream'  => '#f4e7c8',
    ];

    public const GARLAND = [
        'jasmine_rose' => ['flower' => '#fffaf0', 'accent' => '#c2185b'],
        'marigold'     => ['flower' => '#f59e0b', 'accent' => '#b91c1c'],
    ];

    /** Allowed values for each part. One hair and outfit shape each until real artwork arrives. */
    public const OPTIONS = [
        'bride' => [
            'skin'         => ['light', 'medium', 'tan', 'deep'],
            'hair'         => ['braid'],
            'hair_color'   => ['black', 'dark_brown'],
            'outfit'       => ['silk_saree'],
            'outfit_color' => ['maroon', 'green', 'gold', 'blue', 'cream'],
            'jewellery'    => ['temple_gold', 'simple'],
        ],
        'groom' => [
            'skin'         => ['light', 'medium', 'tan', 'deep'],
            'hair'         => ['short'],
            'hair_color'   => ['black', 'dark_brown'],
            'outfit'       => ['veshti_shirt'],
            'outfit_color' => ['cream', 'gold', 'maroon', 'green', 'blue'],
            'jewellery'    => ['none', 'chain'],
        ],
        'garland' => ['jasmine_rose', 'marigold'],
    ];

    public static function defaults(): array
    {
        return [
            'version' => 1,
            'bride'   => [
                'skin'         => 'medium',
                'hair'         => 'braid',
                'hair_color'   => 'black',
                'outfit'       => 'silk_saree',
                'outfit_color' => 'maroon',
                'jewellery'    => 'temple_gold',
            ],
            'groom' => [
                'skin'         => 'medium',
                'hair'         => 'short',
                'hair_color'   => 'black',
                'outfit'       => 'veshti_shirt',
                'outfit_color' => 'cream',
                'jewellery'    => 'chain',
            ],
            'garland' => 'jasmine_rose',
        ];
    }

    /** Stored values over the defaults; anything missing or unknown falls back to its default. */
    public static function resolve(?array $avatar): array
    {
        $resolved = self::defaults();

        foreach (['bride', 'groom'] as $person) {
            foreach (self::OPTIONS[$person] as $part => $allowed) {
                $value = $avatar[$person][$part] ?? null;
                if (in_array($value, $allowed, true)) {
                    $resolved[$person][$part] = $value;
                }
            }
        }

        if (in_array($avatar['garland'] ?? null, self::OPTIONS['garland'], true)) {
            $resolved['garland'] = $avatar['garland'];
        }

        return $resolved;
    }

    /** CSS custom properties for the couple's SVG, from a resolved avatar. */
    public static function cssVars(array $avatar): string
    {
        $vars = [];
        foreach (['bride', 'groom'] as $person) {
            $vars["--{$person}-skin"] = self::SKIN[$avatar[$person]['skin']];
            $vars["--{$person}-hair"] = self::HAIR_COLOR[$avatar[$person]['hair_color']];
            $vars["--{$person}-outfit"] = self::OUTFIT_COLOR[$avatar[$person]['outfit_color']];
        }
        $vars['--garland-flower'] = self::GARLAND[$avatar['garland']]['flower'];
        $vars['--garland-accent'] = self::GARLAND[$avatar['garland']]['accent'];

        return collect($vars)->map(fn ($value, $name) => "{$name}: {$value}")->implode('; ');
    }
}
