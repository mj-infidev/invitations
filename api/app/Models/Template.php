<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    protected $fillable = [
        'slug', 'name', 'category', 'tradition', 'price_paise', 'fields_schema',
        'thumbnail_path', 'max_photos', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_paise'   => 'integer',
            'fields_schema' => 'array',
            'max_photos'    => 'integer',
            'is_active'     => 'boolean',
            'sort_order'    => 'integer',
        ];
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }
}
