<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\Template;
use App\Models\User;
use App\Support\Avatar;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The builder's API: drafts, autosave, the "still needed" list, ownership,
 * photos and deleting. Needs PHP's GD extension for the photo tests.
 */
class InvitationBuilderTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TemplateSeeder::class);
        Storage::fake('public');
        $this->customer = User::factory()->create();
        $this->withHeader('Origin', 'http://localhost:5173');
    }

    private function createDraft(?User $owner = null): Invitation
    {
        $id = $this->actingAs($owner ?? $this->customer, 'web')
            ->postJson('/api/invitations', ['template' => 'tamil-hindu-wedding'])
            ->assertCreated()
            ->json('data.id');

        return Invitation::findOrFail($id);
    }

    private function save(Invitation $invitation, array $data, string $language = 'both')
    {
        return $this->actingAs($this->customer, 'web')
            ->putJson("/api/invitations/{$invitation->id}", ['language' => $language, 'data' => $data]);
    }

    private static function names(?string $ta = 'பிரியா', ?string $en = 'Priya'): array
    {
        return ['bride' => ['name' => ['ta' => $ta, 'en' => $en]], 'groom' => ['name' => ['ta' => 'கார்த்திக்', 'en' => 'Karthik']]];
    }

    // ── Templates ────────────────────────────────────────────────

    public function test_templates_are_public_and_one_has_its_form(): void
    {
        $this->getJson('/api/templates')->assertOk()->assertJsonPath('data.0.slug', 'tamil-hindu-wedding');
        $this->getJson('/api/templates/tamil-hindu-wedding')->assertOk()->assertJsonPath('data.fields_schema.version', 1);
    }

    public function test_an_inactive_template_is_hidden_and_cannot_start_a_draft(): void
    {
        Template::query()->update(['is_active' => false]);

        $this->getJson('/api/templates')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/templates/tamil-hindu-wedding')->assertNotFound();
        $this->actingAs($this->customer, 'web')->postJson('/api/invitations', ['template' => 'tamil-hindu-wedding'])->assertUnprocessable();
    }

    // ── Drafts ───────────────────────────────────────────────────

    public function test_a_new_draft_starts_empty_with_the_avatar_defaults_and_a_placeholder_slug(): void
    {
        $draft = $this->createDraft();

        $this->assertSame('draft', $draft->status);
        $this->assertSame('both', $draft->language);
        $this->assertMatchesRegularExpression('/^draft-[a-z0-9]{10}$/', $draft->slug);
        $this->assertSame(Avatar::defaults(), $draft->data['avatar']);
        $this->assertSame(['ta' => null, 'en' => null], $draft->data['bride']['name']);
        $this->assertSame(40, strlen($draft->preview_token));
    }

    public function test_creating_a_draft_needs_a_signed_in_customer(): void
    {
        $this->postJson('/api/invitations', ['template' => 'tamil-hindu-wedding'])->assertUnauthorized();
    }

    public function test_saving_keeps_only_the_form_fields_and_the_avatar(): void
    {
        $draft = $this->createDraft();

        $this->save($draft, self::names() + ['injected' => 'x', 'avatar' => ['bride' => ['skin' => 'deep']]])->assertOk();

        $data = $draft->fresh()->data;
        $this->assertArrayNotHasKey('injected', $data);
        $this->assertSame(Avatar::defaults(), $data['avatar']);
        $this->assertSame('Priya', $data['bride']['name']['en']);
    }

    public function test_saving_fills_title_and_event_date(): void
    {
        $draft = $this->createDraft();

        $this->save($draft, self::names() + ['muhurtham' => ['date' => '2027-02-14']])->assertOk();
        $this->assertSame('Priya weds Karthik', $draft->fresh()->title);
        $this->assertSame('2027-02-14', $draft->fresh()->event_date->toDateString());

        $this->save($draft, self::names(), 'ta')->assertOk();
        $this->assertSame('பிரியா – கார்த்திக் திருமணம்', $draft->fresh()->title);
        $this->assertNull($draft->fresh()->event_date);
    }

    public function test_saving_rejects_bad_values_with_readable_messages(): void
    {
        $draft = $this->createDraft();

        $errors = $this->save($draft, [
            'bride' => ['name' => ['en' => str_repeat('a', 61)]],
            'muhurtham' => ['date' => '14/02/2027', 'time' => '25:00'],
            'venue' => ['map_url' => 'javascript:alert(1)'],
            'events' => array_fill(0, 5, []),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['data.bride.name.en', 'data.muhurtham.date', 'data.muhurtham.time', 'data.venue.map_url', 'data.events'])
            ->json('errors');

        $this->assertSame("The Bride's name (English) field must not be greater than 60 characters.", $errors['data.bride.name.en'][0]);
        $this->assertSame('Please paste a web link, starting with https://', $errors['data.venue.map_url'][0]);
    }

    public function test_still_needed_follows_the_languages_shown(): void
    {
        $draft = $this->createDraft();
        $keys = fn ($response) => collect($response->json('data.missing'))->map(fn ($m) => $m['key'].($m['lang'] ? ".{$m['lang']}" : ''))->all();

        $both = $keys($this->save($draft, self::names(ta: null)));
        $this->assertContains('bride.name.ta', $both);
        $this->assertNotContains('bride.name.en', $both);
        $this->assertContains('muhurtham.date', $both);

        $english = $keys($this->save($draft, self::names(ta: null), 'en'));
        $this->assertNotContains('bride.name.ta', $english);
        $this->assertNotContains('groom.name.en', $english);

        $tamil = $keys($this->save($draft, self::names(en: null), 'ta'));
        $this->assertNotContains('bride.name.en', $tamil);
    }

    public function test_an_added_event_needs_its_required_fields(): void
    {
        $draft = $this->createDraft();

        $missing = $this->save($draft, ['events' => [['time' => '18:30']]], 'en')->json('data.missing');

        $this->assertContains('events.0.name', array_column($missing, 'key'));
        $this->assertContains('events.0.date', array_column($missing, 'key'));
    }

    // ── Ownership ────────────────────────────────────────────────

    public function test_another_customers_invitation_is_not_found(): void
    {
        $theirs = $this->createDraft(User::factory()->create());

        $this->actingAs($this->customer, 'web');
        $this->getJson("/api/invitations/{$theirs->id}")->assertNotFound();
        $this->save($theirs, self::names())->assertNotFound();
        $this->deleteJson("/api/invitations/{$theirs->id}")->assertNotFound();
        $this->postJson("/api/invitations/{$theirs->id}/media", ['photo' => UploadedFile::fake()->image('a.jpg')])->assertNotFound();
        $this->getJson('/api/invitations')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_the_preview_link_opens_the_draft(): void
    {
        $draft = $this->createDraft();
        $url = $this->actingAs($this->customer, 'web')->getJson("/api/invitations/{$draft->id}")->json('data.preview_url');

        $this->get($url)->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Content-Security-Policy', "frame-ancestors 'self' ".config('invitations.client_url').' '.config('invitations.admin_url'));
    }

    // ── Photos ───────────────────────────────────────────────────

    public function test_a_photo_is_resaved_as_a_smaller_webp(): void
    {
        $draft = $this->createDraft();

        $photo = $this->postJson("/api/invitations/{$draft->id}/media", ['photo' => UploadedFile::fake()->image('wedding.jpg', 4000, 3000)])
            ->assertOk()
            ->json('data.photos.0');

        $media = $draft->media()->sole();
        $this->assertSame([1600, 1200], [$media->width, $media->height]);
        $this->assertStringEndsWith('.webp', $media->path);
        $this->assertStringStartsWith("invitations/{$draft->id}/", $media->path);
        Storage::disk('public')->assertExists($media->path);
        $this->assertSame('image/webp', finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), Storage::disk('public')->get($media->path)));
        $this->assertStringEndsWith($media->path, $photo['url']);
    }

    public function test_a_photo_must_be_a_real_image_of_an_allowed_type_and_size(): void
    {
        $draft = $this->createDraft();
        $url = "/api/invitations/{$draft->id}/media";

        $this->postJson($url, ['photo' => UploadedFile::fake()->create('x.gif', 10, 'image/gif')])->assertJsonValidationErrors('photo');
        $this->postJson($url, ['photo' => UploadedFile::fake()->createWithContent('fake.jpg', '<?php echo 1;')])->assertJsonValidationErrors('photo');
        $this->postJson($url, ['photo' => UploadedFile::fake()->image('big.jpg')->size(5121)])->assertJsonValidationErrors('photo');
        $this->postJson($url, ['photo' => UploadedFile::fake()->image('ok.png', 800, 600)])->assertOk();
        $this->postJson($url, ['photo' => UploadedFile::fake()->image('ok.webp', 800, 600)])->assertOk();
    }

    public function test_the_templates_photo_limit_is_enforced(): void
    {
        Template::query()->update(['max_photos' => 2]);
        $draft = $this->createDraft();
        $url = "/api/invitations/{$draft->id}/media";

        $this->postJson($url, ['photo' => UploadedFile::fake()->image('1.jpg')])->assertOk();
        $this->postJson($url, ['photo' => UploadedFile::fake()->image('2.jpg')])->assertOk();
        $this->postJson($url, ['photo' => UploadedFile::fake()->image('3.jpg')])
            ->assertUnprocessable()
            ->assertJsonPath('errors.photo.0', 'This invitation already has 2 photos, the most it can hold.');

        $this->assertSame(2, $draft->media()->count());
        $this->assertCount(2, Storage::disk('public')->allFiles("invitations/{$draft->id}"));
    }

    public function test_removing_a_photo_deletes_its_row_and_file(): void
    {
        $draft = $this->createDraft();
        $this->postJson("/api/invitations/{$draft->id}/media", ['photo' => UploadedFile::fake()->image('a.jpg')]);
        $media = $draft->media()->sole();

        $this->deleteJson("/api/invitations/{$draft->id}/media/{$media->id}")->assertOk()->assertJsonCount(0, 'data.photos');

        $this->assertSame(0, $draft->media()->count());
        Storage::disk('public')->assertMissing($media->path);
    }

    public function test_a_photo_from_another_invitation_cannot_be_removed_through_mine(): void
    {
        $mine = $this->createDraft();
        $theirs = $this->createDraft(User::factory()->create());
        $theirs->media()->create(['type' => 'photo', 'path' => 'x.webp', 'sort_order' => 1, 'size_bytes' => 1]);

        $this->actingAs($this->customer, 'web')
            ->deleteJson("/api/invitations/{$mine->id}/media/{$theirs->media()->sole()->id}")
            ->assertNotFound();
        $this->assertSame(1, $theirs->media()->count());
    }

    // ── Deleting ─────────────────────────────────────────────────

    public function test_deleting_a_draft_removes_its_media_rows_and_files(): void
    {
        $draft = $this->createDraft();
        $this->postJson("/api/invitations/{$draft->id}/media", ['photo' => UploadedFile::fake()->image('a.jpg')]);
        $path = $draft->media()->sole()->path;

        $this->deleteJson("/api/invitations/{$draft->id}")->assertOk();

        $this->assertSoftDeleted($draft);
        $this->assertSame(0, $draft->media()->count());
        Storage::disk('public')->assertMissing($path);
        $this->getJson("/api/invitations/{$draft->id}")->assertNotFound();
    }

    public function test_a_published_invitation_cannot_be_deleted_but_can_be_edited_until_the_event(): void
    {
        $invitation = $this->createDraft();
        $invitation->update(['status' => 'published', 'event_date' => now()->addMonth()->toDateString()]);

        $this->deleteJson("/api/invitations/{$invitation->id}")->assertForbidden();
        $this->save($invitation, self::names())->assertOk();

        $invitation->update(['event_date' => now()->subWeek()->toDateString()]);
        $this->save($invitation, self::names())->assertForbidden();
    }
}
