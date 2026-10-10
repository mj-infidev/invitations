<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invitation extends Model
{
    use SoftDeletes;

    public const STATUSES = ['draft', 'published', 'expired', 'disabled'];

    public const LANGUAGES = ['ta', 'en', 'both'];

    protected $fillable = [
        'user_id', 'template_id', 'slug', 'status', 'language', 'title', 'event_date',
        'data', 'og_image_path', 'preview_token', 'view_count', 'published_at', 'expires_at',
    ];

    // The draft key; anyone holding it can open the unpublished page.
    protected $hidden = [
        'preview_token',
    ];

    protected function casts(): array
    {
        return [
            'event_date'   => 'date',
            'data'         => 'array',
            'view_count'   => 'integer',
            'published_at' => 'datetime',
            'expires_at'   => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(InvitationMedia::class)->orderBy('sort_order');
    }

    /** A failed payment leaves an order behind, so there can be several. */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
