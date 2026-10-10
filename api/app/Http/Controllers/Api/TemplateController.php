<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Template;
use Illuminate\Http\JsonResponse;

/** Public: the templates a customer can pick, and one with its form fields. */
class TemplateController extends Controller
{
    /** GET /api/templates: active templates, in the admin's order. */
    public function index(): JsonResponse
    {
        $templates = Template::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();

        // The landing page's live demo, for the template it belongs to.
        $demo = Invitation::where('slug', config('invitations.demo_slug'))->where('status', 'published')->first();

        return response()->json(['data' => $templates->map(fn (Template $template) => self::present($template) + [
            'demo_url' => $demo?->template_id === $template->id ? route('invitation.show', $demo->slug) : null,
        ])]);
    }

    /** GET /api/templates/{slug}: one active template with its form fields. */
    public function show(string $slug): JsonResponse
    {
        $template = Template::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return response()->json(['data' => self::present($template) + ['fields_schema' => $template->fields_schema]]);
    }

    private static function present(Template $template): array
    {
        return [
            'slug' => $template->slug,
            'name' => $template->name,
            'category' => $template->category,
            'tradition' => $template->tradition,
            'price_paise' => $template->price_paise,
            'max_photos' => $template->max_photos,
        ];
    }
}
