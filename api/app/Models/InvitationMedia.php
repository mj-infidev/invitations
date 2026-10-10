<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationMedia extends Model
{
    public const TYPES = ['photo', 'music'];

    protected $table = 'invitation_media';

    protected $fillable = [
        'invitation_id', 'type', 'path', 'sort_order', 'width', 'height', 'size_bytes',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'width'      => 'integer',
            'height'     => 'integer',
            'size_bytes' => 'integer',
        ];
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }
}
