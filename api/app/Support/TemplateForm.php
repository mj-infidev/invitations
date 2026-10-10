<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A template's details form, from its `fields_schema` (PLAN.md > Template form
 * fields): what `invitations.data` may hold, how each value is validated, which
 * required answers are still missing, and the columns filled from the answers.
 *
 * Drafts save with gaps: validation checks shape and length only, never
 * `required`. What's required is reported by missing() and enforced at payment.
 */
class TemplateForm
{
    /** Keys in `data` that are not form fields but must survive every save. */
    private const RESERVED = ['avatar'];

    public function __construct(private readonly array $schema)
    {
    }

    /** Every top-level field, in form order, with its group's key. */
    public function fields(): array
    {
        $fields = [];
        foreach ($this->schema['groups'] ?? [] as $group) {
            foreach ($group['fields'] ?? [] as $field) {
                $fields[] = $field + ['group' => $group['key']];
            }
        }

        return $fields;
    }

    /**
     * Laravel validation rules for a save request, keyed `data.<field key>`,
     * plus readable names for its messages ("Bride's name (Tamil)") and the
     * messages that need more than a name.
     *
     * @return array{0: array<string, array>, 1: array<string, string>, 2: array<string, string>}
     */
    public function rules(): array
    {
        $rules = ['data' => ['present', 'array']];
        $names = [];

        foreach ($this->fields() as $field) {
            $this->addRules($field, 'data.'.$field['key'], $rules, $names);
        }

        // Laravel's `*` matches one path segment only, so each link field is named.
        $messages = collect($rules)
            ->filter(fn (array $list) => collect($list)->contains(fn ($rule) => str_starts_with($rule, 'regex:')))
            ->mapWithKeys(fn ($list, string $key) => ["{$key}.regex" => 'Please paste a web link, starting with https://'])
            ->all();

        return [$rules, $names, $messages];
    }

    private function addRules(array $field, string $path, array &$rules, array &$names): void
    {
        $label = $field['label']['en'] ?? $field['key'];

        if ($field['type'] === 'repeater') {
            $rules[$path] = ['nullable', 'array', 'max:'.($field['max_items'] ?? 10)];
            foreach ($field['fields'] as $sub) {
                $this->addRules($sub, "{$path}.*.{$sub['key']}", $rules, $names);
            }

            return;
        }

        $valueRules = match ($field['type']) {
            'date' => ['nullable', 'date_format:Y-m-d'],
            'time' => ['nullable', 'date_format:H:i'],
            // Only web links: a `javascript:` link would run on a guest's tap.
            'url' => ['nullable', 'string', 'max:'.($field['max'] ?? 500), 'regex:#^https?://\S+$#i'],
            default => ['nullable', 'string', 'max:'.($field['max'] ?? 255)],
        };

        if ($field['bilingual'] ?? false) {
            $rules[$path] = ['nullable', 'array'];
            foreach (['ta' => 'Tamil', 'en' => 'English'] as $lang => $language) {
                $rules["{$path}.{$lang}"] = $valueRules;
                $names["{$path}.{$lang}"] = "{$label} ({$language})";
            }
        } else {
            $rules[$path] = $valueRules;
            $names[$path] = $label;
        }
    }

    /**
     * `data` holding only this template's fields, blanks as null, plus the
     * reserved keys (the couple's avatar) carried over from what was stored:
     * the form never sends them, and a save must not drop them.
     */
    public function clean(array $input, array $stored = []): array
    {
        $data = [];
        foreach ($this->fields() as $field) {
            data_set($data, $field['key'], $this->cleanValue($field, data_get($input, $field['key'])));
        }

        foreach (self::RESERVED as $key) {
            if (array_key_exists($key, $stored)) {
                $data[$key] = $stored[$key];
            }
        }

        return $data;
    }

    private function cleanValue(array $field, mixed $value): mixed
    {
        if ($field['type'] === 'repeater') {
            return collect(is_array($value) ? array_values($value) : [])
                ->take($field['max_items'] ?? 10)
                ->map(function ($item) use ($field) {
                    $clean = [];
                    foreach ($field['fields'] as $sub) {
                        $clean[$sub['key']] = $this->cleanValue($sub, is_array($item) ? ($item[$sub['key']] ?? null) : null);
                    }

                    return $clean;
                })
                ->all();
        }

        $text = fn ($v) => is_string($v) && trim($v) !== '' ? trim($v) : null;

        if ($field['bilingual'] ?? false) {
            return ['ta' => $text($value['ta'] ?? null), 'en' => $text($value['en'] ?? null)];
        }

        return $text($value);
    }

    /**
     * Required answers still empty, for every language the invitation shows.
     * Each: { key, lang (null if not bilingual), group, label: {en, ta} }.
     * A repeater item's field has a key like `events.0.name`.
     */
    public function missing(array $data, string $language): array
    {
        $language = Languages::effective($language);
        $languages = $language === 'both' ? ['ta', 'en'] : [$language];
        $missing = [];

        foreach ($this->fields() as $field) {
            if ($field['type'] === 'repeater') {
                foreach (array_values(data_get($data, $field['key']) ?? []) as $i => $item) {
                    foreach ($field['fields'] as $sub) {
                        $this->checkRequired($sub, "{$field['key']}.{$i}.{$sub['key']}", $item[$sub['key']] ?? null, $field['group'], $languages, $missing);
                    }
                }
            } else {
                $this->checkRequired($field, $field['key'], data_get($data, $field['key']), $field['group'], $languages, $missing);
            }
        }

        return $missing;
    }

    private function checkRequired(array $field, string $key, mixed $value, string $group, array $languages, array &$missing): void
    {
        if (! ($field['required'] ?? false)) {
            return;
        }

        $entry = ['key' => $key, 'group' => $group, 'label' => $field['label']];

        if ($field['bilingual'] ?? false) {
            foreach ($languages as $lang) {
                if (blank($value[$lang] ?? null)) {
                    $missing[] = $entry + ['lang' => $lang];
                }
            }
        } elseif (blank($value)) {
            $missing[] = $entry + ['lang' => null];
        }
    }

    /**
     * The columns filled from the answers on every save: `title` for the
     * WhatsApp card ("Priya weds Karthik"; Tamil names on a Tamil-only
     * invitation) and `event_date` from the muhurtham.
     */
    public static function derived(array $data, string $language): array
    {
        $language = Languages::effective($language);
        $lang = $language === 'ta' ? 'ta' : 'en';
        $bride = data_get($data, "bride.name.{$lang}");
        $groom = data_get($data, "groom.name.{$lang}");

        $title = match (true) {
            ! $bride || ! $groom => null,
            $lang === 'ta' => Str::limit("{$bride} – {$groom} திருமணம்", 250),
            default => Str::limit("{$bride} weds {$groom}", 250),
        };

        $date = data_get($data, 'muhurtham.date');
        $eventDate = is_string($date) && Carbon::canBeCreatedFromFormat($date, 'Y-m-d') ? $date : null;

        return ['title' => $title, 'event_date' => $eventDate];
    }
}
