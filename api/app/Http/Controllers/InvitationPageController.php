<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Support\InvitationPage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * GET /i/{slug}: the page guests open. Blade with the details already in the
 * HTML, so WhatsApp can build its preview card without running JavaScript.
 */
class InvitationPageController extends Controller
{
    /** Apps that fetch a link to draw its preview card. Their visits are not views. */
    private const LINK_PREVIEW_AGENTS = '/WhatsApp|facebookexternalhit|Facebot|TelegramBot|Twitterbot|Slackbot|LinkedInBot|Discordbot|SkypeUriPreview/i';

    public function show(Request $request, string $slug): Response
    {
        $invitation = Invitation::with(['template', 'media'])->where('slug', $slug)->first();
        abort_if($invitation === null, 404);

        $token = $request->query('preview');
        $hasPreviewToken = is_string($token) && $token !== '' && hash_equals($invitation->preview_token, $token);

        // A draft is only for its owner, through the preview link. Without it the
        // invitation doesn't exist, as far as anyone else can tell.
        abort_if($invitation->status === 'draft' && ! $hasPreviewToken, 404);

        if (in_array($invitation->status, ['expired', 'disabled'], true) || $invitation->expires_at?->isPast()) {
            return $this->noindex(response()->view('invitations.ended', [
                'lang' => $invitation->language === 'en' ? 'en' : 'ta',
                'languages' => $invitation->language === 'both' ? ['ta', 'en'] : [$invitation->language],
            ], 410));
        }

        $view = "invitations.templates.{$invitation->template->slug}";
        abort_unless(view()->exists($view), 404);

        if ($invitation->status === 'published' && ! $hasPreviewToken
            && ! preg_match(self::LINK_PREVIEW_AGENTS, (string) $request->userAgent())) {
            // toBase(): counting a view must not touch updated_at.
            Invitation::whereKey($invitation->id)->toBase()->increment('view_count');
        }

        $page = new InvitationPage($invitation, watermark: $invitation->status === 'draft');

        $response = response()->view($view, ['page' => $page]);

        // Drafts change on every save and the demo's dates move daily: never cache them.
        if ($page->watermark || $page->isDemo) {
            $response->header('Cache-Control', 'no-store');
        }

        return $this->noindex($response);
    }

    private function noindex(Response $response): Response
    {
        return $response->header('X-Robots-Tag', 'noindex');
    }
}
