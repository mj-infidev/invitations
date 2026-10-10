<?php

namespace App\Support;

use App\Models\Admin;
use App\Models\AdminAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * The one place admin actions are written down. Call it inside the same
 * transaction as the change, so a change can't happen without its record.
 *
 * Actions: admin.login, admin.login_failed, admin.logout, invitation.update,
 * invitation.disable, invitation.enable, media.remove, order.mark_paid,
 * template.update.
 */
class AdminLog
{
    public static function record(
        ?Admin $admin,
        string $action,
        ?Model $subject = null,
        ?string $reason = null,
        array $details = [],
    ): AdminAction {
        return AdminAction::create([
            'admin_id' => $admin?->id,
            'action' => $action,
            'subject_type' => $subject ? self::type($subject) : null,
            'subject_id' => $subject?->getKey(),
            'reason' => $reason,
            'details' => $details ?: null,
            'ip' => request()?->ip(),
        ]);
    }

    /** "invitation", "order", "template", "media": short names for subject_type. */
    public static function type(Model $subject): string
    {
        return match (class_basename($subject)) {
            'InvitationMedia' => 'media',
            default => strtolower(class_basename($subject)),
        };
    }

    /**
     * What changed between two arrays, field by field: { "bride.name.en": ["Prya", "Priya"] }.
     * Nested values are compared by dot path; lists (other events) as a whole.
     */
    public static function changes(array $before, array $after): array
    {
        $flatten = fn (array $values) => collect(Arr::dot($values))
            // Arr::dot splits lists into events.0.name.ta; keep a list as one value.
            ->mapWithKeys(fn ($value, $key) => [preg_replace('/\.\d+(\..*)?$/', '', $key) => null])
            ->keys()
            ->mapWithKeys(fn ($key) => [$key => data_get($values, $key)])
            ->all();

        $old = $flatten($before);
        $new = $flatten($after);

        $changes = [];
        foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $key) {
            if (($old[$key] ?? null) !== ($new[$key] ?? null)) {
                $changes[$key] = [$old[$key] ?? null, $new[$key] ?? null];
            }
        }

        return $changes;
    }
}
