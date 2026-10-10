<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One admin action, as it happened (App\Support\AdminLog). Rows are only ever
 * added: there is no updated_at, and the app never edits or deletes one.
 */
class AdminAction extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['admin_id', 'action', 'subject_type', 'subject_id', 'reason', 'details', 'ip'];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
