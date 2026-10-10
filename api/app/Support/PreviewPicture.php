<?php

namespace App\Support;

use App\Models\Invitation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Geometry\Factories\CircleFactory;
use Intervention\Image\Geometry\Factories\RectangleFactory;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\FontFactory;

/**
 * The picture on the WhatsApp card (og:image): 1200 × 630 JPEG, well under
 * 300 KB, with the names and date on the template's cover art (placeholder art
 * until the design pass).
 *
 * GD can't shape Tamil — vowel signs land in the wrong place — so the picture
 * carries the English names, and a Tamil-only invitation's picture has the art,
 * a large உ and the date in numbers, with no English words (the Tamil names are
 * in the card's title). See
 * PLAN.md > Design pass. The only Tamil drawn is the single letter உ.
 *
 * The file name carries a hash of what is drawn: WhatsApp caches a picture by
 * its address, so a changed picture needs a new one. Unchanged details make
 * the same name, and nothing is redrawn.
 */
class PreviewPicture
{
    /** Bump to redraw every picture after changing the drawing. */
    private const VERSION = 1;

    private const WIDTH = 1200;

    private const HEIGHT = 630;

    /** Draws the picture if the details on it changed, and stores its path. */
    public static function refresh(Invitation $invitation): void
    {
        $lines = self::lines($invitation);
        $hash = substr(md5(json_encode([$lines, self::VERSION])), 0, 10);
        $path = "og/{$invitation->slug}-{$hash}.jpg";

        if ($invitation->og_image_path === $path && Storage::disk('public')->exists($path)) {
            return;
        }

        Storage::disk('public')->put($path, (string) self::draw($lines)->toJpeg(85));

        $old = $invitation->og_image_path;
        $invitation->forceFill(['og_image_path' => $path])->saveQuietly();

        if ($old && $old !== $path) {
            Storage::disk('public')->delete($old);
        }
    }

    /**
     * What goes on the picture: names (English only, see above), the date and
     * the town.
     *
     * @return array{names: ?array{0: string, 1: string}, date: ?string, town: ?string}
     */
    public static function lines(Invitation $invitation): array
    {
        $page = new InvitationPage($invitation->loadMissing(['template', 'media']), watermark: false);
        $english = Languages::effective($invitation->language) !== 'ta';
        $bride = $page->get('bride.name.en');
        $groom = $page->get('groom.name.en');
        $date = $page->get('muhurtham.date');

        return [
            'names' => $english && $bride && $groom ? [$bride, $groom] : null,
            'date' => $date ? Carbon::parse($date)->locale('en')->isoFormat($english ? 'dddd, D MMMM YYYY' : 'D · M · YYYY') : null,
            'town' => $english ? $page->town('en') : null,
        ];
    }

    private static function draw(array $lines): ImageInterface
    {
        $fonts = resource_path('fonts');
        $gold = '#e0a93b';
        $cream = '#fff6e3';
        $centre = self::WIDTH / 2;

        $image = (new ImageManager(new Driver))->create(self::WIDTH, self::HEIGHT)->fill('#2e0711');

        // Dusk sky, sun glow, and a double gold frame.
        $image->drawRectangle(0, 300, fn (RectangleFactory $r) => $r->size(self::WIDTH, 330)->background('#4a0d1c'));
        $image->drawCircle($centre, 330, fn (CircleFactory $c) => $c->radius(230)->background('rgba(249, 200, 81, 0.10)'));
        $image->drawCircle($centre, 330, fn (CircleFactory $c) => $c->radius(150)->background('rgba(249, 200, 81, 0.10)'));
        $image->drawRectangle(26, 26, fn (RectangleFactory $r) => $r->size(self::WIDTH - 52, self::HEIGHT - 52)->border($gold, 3));
        $image->drawRectangle(38, 38, fn (RectangleFactory $r) => $r->size(self::WIDTH - 76, self::HEIGHT - 76)->border($gold, 1));

        $text = function (string $value, int $y, string $font, int $size, string $color) use ($image, $centre) {
            $image->text($value, $centre, $y, function (FontFactory $f) use ($font, $size, $color) {
                $f->filename($font);
                $f->size($size);
                $f->color($color);
                $f->align('center');
                $f->valign('middle');
            });
        };

        $tamil = "{$fonts}/TiroTamil-Regular.ttf";
        $script = "{$fonts}/GreatVibes-Regular.ttf";
        if ($lines['names']) {
            [$bride, $groom] = $lines['names'];
            $oneLine = "{$bride} & {$groom}";
            $size = self::fit($oneLine, $script, 108, 1000);
            if ($size >= 72) {
                $text($oneLine, 300, $script, $size, $cream);
            } else {
                // Long names: one per line, each as large as fits.
                $text($bride, 222, $script, self::fit($bride, $script, 92, 1000), $cream);
                $text('&', 294, $script, 52, $gold);
                $text($groom, 362, $script, self::fit($groom, $script, 92, 1000), $cream);
            }
        }

        // Without names (a Tamil-only invitation) the உ itself is the picture: no English
        // words on a card the family chose to send in Tamil.
        $text('உ', $lines['names'] ? 108 : 290, $tamil, $lines['names'] ? 64 : 190, $gold);

        $serif = "{$fonts}/CormorantGaramond.ttf";
        $image->drawRectangle($centre - 60, 452, fn (RectangleFactory $r) => $r->size(120, 2)->background($gold));
        if ($lines['date']) {
            $text($lines['date'], 498, $serif, 40, $cream);
        }
        if ($lines['town']) {
            $text($lines['town'], 552, $serif, 32, $gold);
        }

        return $image;
    }

    /** The largest size, up to $max, at which $text is no wider than $width. */
    private static function fit(string $text, string $font, int $max, int $width): int
    {
        for ($size = $max; $size > 24; $size -= 2) {
            $box = imagettfbbox($size * 0.75, 0, $font, $text); // GD sizes are in points
            if (abs($box[2] - $box[0]) <= $width) {
                return $size;
            }
        }

        return 24;
    }
}
