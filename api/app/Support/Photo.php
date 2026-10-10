<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * A couple's photo, re-saved on our server before anyone sees it: turned
 * upright, scaled down to at most 1600 px on its longest side, and encoded as
 * WebP. Re-encoding keeps only the pixels, so hidden data in the original —
 * GPS location, camera details — never reaches the public disk.
 *
 * Needs PHP's GD extension (enabled in php.ini; see PLAN.md).
 */
class Photo
{
    public const LONGEST_SIDE = 1600;

    public const QUALITY = 80;

    /**
     * Saves the re-encoded photo under invitations/{id}/ on the public disk.
     *
     * @return array{path: string, width: int, height: int, size_bytes: int}
     */
    public static function store(UploadedFile $file, int $invitationId): array
    {
        // Upright from the EXIF orientation first, then scaled; never enlarged.
        // strip: drop any metadata explicitly, on top of what re-encoding drops.
        $image = (new ImageManager(new Driver, autoOrientation: true, strip: true))
            ->read($file->getRealPath())
            ->scaleDown(self::LONGEST_SIDE, self::LONGEST_SIDE);

        $bytes = (string) $image->toWebp(self::QUALITY);
        $path = "invitations/{$invitationId}/".Str::lower(Str::random(24)).'.webp';
        Storage::disk('public')->put($path, $bytes);

        return [
            'path' => $path,
            'width' => $image->width(),
            'height' => $image->height(),
            'size_bytes' => strlen($bytes),
        ];
    }
}
