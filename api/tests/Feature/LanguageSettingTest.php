<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\Template;
use App\Models\User;
use Database\Seeders\DemoInvitationSeeder;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * TAMIL_ENABLED off (the default for now): English only everywhere, whatever an
 * invitation has stored; the Tamil text and settings are kept.
 */
class LanguageSettingTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        config(['invitations.tamil_enabled' => false]);
        $this->seed(TemplateSeeder::class);
        $this->customer = User::factory()->create();
        $this->withHeader('Origin', 'http://localhost:5173');
    }

    /** A complete invitation stored as Tamil and English. */
    private function bothLanguages(string $status = 'published'): Invitation
    {
        return $this->customer->invitations()->create([
            'template_id' => Template::sole()->id,
            'slug' => 'priya-karthik-'.Str::lower(Str::random(4)),
            'status' => $status,
            'language' => 'both',
            'data' => DemoInvitationSeeder::data(),
            'title' => 'Priya weds Karthik',
            'preview_token' => Str::random(40),
            'published_at' => now(),
        ]);
    }

    public function test_a_new_draft_is_english_and_the_builder_is_told_tamil_is_off(): void
    {
        $this->actingAs($this->customer, 'web')
            ->postJson('/api/invitations', ['template' => 'tamil-hindu-wedding'])
            ->assertCreated()
            ->assertJsonPath('data.language', 'en')
            ->assertJsonPath('data.tamil_enabled', false);
    }

    public function test_only_english_is_still_needed_even_for_a_stored_both_invitation(): void
    {
        $invitation = $this->bothLanguages('draft');
        $data = $invitation->data;
        $data['bride']['name']['ta'] = null;

        $missing = $this->actingAs($this->customer, 'web')
            ->putJson("/api/invitations/{$invitation->id}", ['language' => 'both', 'data' => $data])
            ->assertOk()
            ->json('data.missing');

        $this->assertSame([], $missing);
        $this->assertSame('both', $invitation->fresh()->language, 'the stored setting is kept');
        $this->assertNull($invitation->fresh()->data['bride']['name']['ta']);
    }

    public function test_the_page_is_english_only_with_no_switch(): void
    {
        $invitation = $this->bothLanguages();

        $html = $this->get("/i/{$invitation->slug}")->assertOk()->getContent();
        // The inline JSON keeps the stored data, Tamil included (kept, not shown); check what is shown.
        $shown = preg_replace('#<script type="application/json" id="invitation-data">.*?</script>#s', '', $html);

        $this->assertStringContainsString('<html lang="en">', $shown);
        $this->assertStringNotContainsString('lang-switch', $shown);
        $this->assertStringNotContainsString('lang="ta"', $shown);
        $this->assertStringNotContainsString('பிரியா', $shown);
        $this->assertStringContainsString('Priya', $shown);
    }

    public function test_the_kept_tamil_comes_back_when_switched_on(): void
    {
        $invitation = $this->bothLanguages();
        config(['invitations.tamil_enabled' => true]);

        $this->get("/i/{$invitation->slug}")->assertOk()->assertSee('lang-switch', false)->assertSee('பிரியா');
    }
}
