<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminViews;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Customers: name, email, and their invitations. Nothing more is shown. */
class UserController extends Controller
{
    /** GET /api/admin/users?q= */
    public function index(Request $request): JsonResponse
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);

        $query = User::withCount([
            'invitations',
            'invitations as paid_count' => fn ($q) => $q->whereIn('status', ['published', 'expired', 'disabled'])->whereNotNull('published_at'),
        ])->latest('id');

        if ($search = trim((string) $request->input('q'))) {
            $like = AdminViews::like($search);
            $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like));
        }

        return response()->json($query->paginate(25)->through(fn (User $user) => AdminViews::customerRef($user) + [
            'joined_at' => $user->created_at?->toIso8601String(),
            'invitations_count' => $user->invitations_count,
            'paid_count' => $user->paid_count,
        ]));
    }

    /** GET /api/admin/users/{user}: the customer and their invitations. */
    public function show(User $user): JsonResponse
    {
        $invitations = $user->invitations()->with(['user:id,name,email', 'template:id,name'])->latest('updated_at')->get();

        return response()->json(['data' => AdminViews::customerRef($user) + [
            'joined_at' => $user->created_at?->toIso8601String(),
            'invitations' => $invitations->map(fn (Invitation $invitation) => AdminViews::invitationRow($invitation)),
        ]]);
    }
}
