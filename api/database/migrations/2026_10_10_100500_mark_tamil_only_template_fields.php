<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Marks the Tamil calendar date field `lang: ta` in templates already stored,
 * as TemplateSeeder now does, so the form shows it only when Tamil is one of
 * the languages. Only that field changes; prices and other settings are kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->mark('ta');
    }

    public function down(): void
    {
        $this->mark(null);
    }

    private function mark(?string $lang): void
    {
        foreach (DB::table('templates')->get(['id', 'fields_schema']) as $template) {
            $schema = json_decode($template->fields_schema, true);

            foreach ($schema['groups'] ?? [] as $g => $group) {
                foreach ($group['fields'] ?? [] as $f => $field) {
                    if ($field['key'] === 'muhurtham.tamil_date') {
                        if ($lang) {
                            $schema['groups'][$g]['fields'][$f]['lang'] = $lang;
                        } else {
                            unset($schema['groups'][$g]['fields'][$f]['lang']);
                        }
                    }
                }
            }

            DB::table('templates')->where('id', $template->id)->update(['fields_schema' => json_encode($schema)]);
        }
    }
};
