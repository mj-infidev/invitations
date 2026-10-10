<?php

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Carbon;

/**
 * A customer reaches only their own invitations. Anyone else's answers 404,
 * not 403, so invitation ids can't be probed.
 */
class InvitationPolicy
{
    public function view(User $user, Invitation $invitation): Response
    {
        return $this->owns($user, $invitation);
    }

    /**
     * Details and photos can change while a draft, and after payment until the
     * event date (PLAN.md > Customer flow). Expired or disabled: no.
     */
    public function update(User $user, Invitation $invitation): Response
    {
        $owns = $this->owns($user, $invitation);
        if ($owns->denied()) {
            return $owns;
        }

        $editable = $invitation->status === 'draft'
            || ($invitation->status === 'published'
                && ($invitation->event_date === null
                    || $invitation->event_date->gte(Carbon::today(config('invitations.timezone')))));

        return $editable ? Response::allow() : Response::deny('This invitation can no longer be changed.');
    }

    /** Only a draft can be deleted: a paid invitation has an order behind it. */
    public function delete(User $user, Invitation $invitation): Response
    {
        $owns = $this->owns($user, $invitation);
        if ($owns->denied()) {
            return $owns;
        }

        return $invitation->status === 'draft' ? Response::allow() : Response::deny('Only a draft can be deleted.');
    }

    private function owns(User $user, Invitation $invitation): Response
    {
        return $invitation->user_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }
}
