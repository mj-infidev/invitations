<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminViews;
use App\Models\Template;
use App\Support\AdminLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Templates: price, on or off, photo limit. A new price applies to the next checkout. */
class TemplateController extends Controller
{
    /** GET /api/admin/templates: every template, active or not. */
    public function index(): JsonResponse
    {
        return response()->json(['data' => Template::orderBy('sort_order')->orderBy('id')->get()->map(fn ($t) => AdminViews::template($t))]);
    }

    /** PUT /api/admin/templates/{template} { price_paise, is_active, max_photos } */
    public function update(Request $request, Template $template): JsonResponse
    {
        $input = $request->validate([
            'price_paise' => ['required', 'integer', 'min:100', 'max:10000000'], // ₹1 to ₹1,00,000
            'is_active' => ['required', 'boolean'],
            'max_photos' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $changes = AdminLog::changes($template->only(array_keys($input)), $input);

        if ($changes !== []) {
            DB::transaction(function () use ($request, $template, $input, $changes) {
                $template->update($input);
                AdminLog::record($request->user('admin'), 'template.update', $template, details: ['changes' => $changes]);
            });
        }

        return response()->json(['data' => AdminViews::template($template->fresh())]);
    }
}
