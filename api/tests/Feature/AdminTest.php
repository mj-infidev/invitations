<?php

namespace Tests\Feature;

use App\Http\Middleware\UseAdminSession;
use App\Models\Admin;
use App\Models\AdminAction;
use App\Models\Invitation;
use App\Models\Order;
use App\Models\Template;
use App\Models\User;
use Database\Seeders\DemoInvitationSeeder;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The admin panel's API: its own session cookie, sign-in, the screens' data,
 * mark paid by hand, corrections, and the log every action leaves.
 */
class AdminTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TemplateSeeder::class);
        Storage::fake('public');
        Http::preventStrayRequests();
        $this->admin = Admin::create(['name' => 'Anu', 'email' => 'anu@example.com', 'password' => 'correct-horse-battery']);
        $this->customer = User::factory()->create(['name' => 'Priya R']);
        $this->withHeader('Origin', 'http://localhost:5174');
    }

    /** A complete draft owned by the customer. */
    private function draft(array $changes = []): Invitation
    {
        $data = array_replace_recursive(DemoInvitationSeeder::data(), $changes);
        $data['muhurtham']['date'] = now()->addMonths(2)->toDateString();

        return $this->customer->invitations()->create([
            'template_id' => Template::sole()->id,
            'slug' => 'draft-'.Str::lower(Str::random(10)),
            'status' => 'draft',
            'language' => 'both',
            'data' => $data,
            'title' => 'Priya weds Karthik',
            'preview_token' => Str::random(40),
        ]);
    }

    private function asAdmin(): static
    {
        return $this->actingAs($this->admin, 'admin');
    }

    // ── Signing in, and the separate cookie ──────────────────────

    public function test_admin_signs_in_on_its_own_cookie(): void
    {
        $customerCookie = config('session.cookie');

        $response = $this->postJson('/api/admin/login', ['email' => 'anu@example.com', 'password' => 'correct-horse-battery'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Anu');

        $cookies = collect($response->headers->getCookies())->keyBy(fn ($cookie) => $cookie->getName());
        $session = $cookies[UseAdminSession::COOKIE];
        $this->assertSame('/api/admin', $session->getPath());
        $this->assertSame('strict', $session->getSameSite());
        $this->assertSame(0, $session->getExpiresTime(), 'ends when the browser closes');
        $this->assertTrue($cookies->has(UseAdminSession::CSRF_COOKIE));
        $this->assertFalse($cookies->has('XSRF-TOKEN'));
        $this->assertFalse($cookies->has($customerCookie), "the customers' cookie is untouched");

        $this->assertAuthenticatedAs($this->admin, 'admin');
        $this->assertSame('admin.login', AdminAction::sole()->action);
    }

    public function test_a_customer_request_after_an_admin_one_keeps_the_customer_cookie(): void
    {
        $this->getJson('/api/admin/csrf-cookie')->assertNoContent();
        $customer = $this->withHeader('Origin', 'http://localhost:5173')->getJson('/api/me');

        $names = collect($customer->headers->getCookies())->map(fn ($cookie) => $cookie->getName());
        $this->assertContains('XSRF-TOKEN', $names);
        $this->assertNotContains(UseAdminSession::COOKIE, $names);
    }

    public function test_a_wrong_email_or_password_gets_the_same_answer_and_is_logged(): void
    {
        $wrongPassword = $this->postJson('/api/admin/login', ['email' => 'anu@example.com', 'password' => 'nope'])->assertUnprocessable();
        $wrongEmail = $this->postJson('/api/admin/login', ['email' => 'who@example.com', 'password' => 'nope'])->assertUnprocessable();

        $this->assertSame($wrongPassword->json('errors.email'), $wrongEmail->json('errors.email'));
        $this->assertGuest('admin');
        $this->assertSame(['who@example.com'], AdminAction::where('action', 'admin.login_failed')->latest('id')->limit(1)->get()->map(fn ($a) => $a->details['email'])->all());
    }

    public function test_five_failures_lock_sign_in_for_a_while(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/admin/login', ['email' => 'anu@example.com', 'password' => 'nope'])->assertUnprocessable();
        }

        $this->postJson('/api/admin/login', ['email' => 'anu@example.com', 'password' => 'correct-horse-battery'])->assertStatus(429);
        $this->assertGuest('admin');
    }

    public function test_customers_and_guests_get_401_on_admin_routes(): void
    {
        $invitation = $this->draft();
        $routes = [['get', '/api/admin/stats'], ['get', '/api/admin/invitations'], ['get', "/api/admin/invitations/{$invitation->id}"],
            ['put', "/api/admin/invitations/{$invitation->id}"], ['get', '/api/admin/orders'], ['get', '/api/admin/templates'],
            ['get', '/api/admin/users'], ['post', "/api/admin/invitations/{$invitation->id}/mark-paid"]];

        foreach ($routes as [$method, $uri]) {
            $this->{$method.'Json'}($uri)->assertUnauthorized();
        }

        $this->actingAs($this->customer, 'web');
        foreach ($routes as [$method, $uri]) {
            $this->{$method.'Json'}($uri)->assertUnauthorized();
        }
        $this->getJson('/api/admin/me')->assertExactJson(['data' => null]);
    }

    // ── Mark paid by hand ────────────────────────────────────────

    public function test_an_unpaid_order_is_marked_paid_with_a_reason_and_publishes(): void
    {
        $invitation = $this->draft();
        $order = Order::create(['user_id' => $this->customer->id, 'invitation_id' => $invitation->id, 'amount_paise' => 49900,
            'status' => 'failed', 'razorpay_order_id' => 'order_X']);

        $this->asAdmin()->postJson("/api/admin/orders/{$order->id}/mark-paid", ['reason' => 'short'])->assertJsonValidationErrors('reason');
        $this->asAdmin()->postJson("/api/admin/orders/{$order->id}/mark-paid", ['reason' => 'Paid by UPI to our account', 'reference' => 'UPI 4410'])
            ->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.paid_by', 'Anu');

        $this->assertSame($this->admin->id, $order->fresh()->paid_by_admin_id);
        $this->assertNull($order->fresh()->razorpay_payment_id);
        $this->assertSame('published', $invitation->fresh()->status);
        $this->assertMatchesRegularExpression('/^priya-karthik-[a-z0-9]{4}$/', $invitation->fresh()->slug);
        Storage::disk('public')->assertExists($invitation->fresh()->og_image_path);

        $log = AdminAction::where('action', 'order.mark_paid')->sole();
        $this->assertSame([$this->admin->id, 'order', $order->id, 'Paid by UPI to our account', 'UPI 4410'],
            [$log->admin_id, $log->subject_type, $log->subject_id, $log->reason, $log->details['reference']]);

        $this->asAdmin()->postJson("/api/admin/orders/{$order->id}/mark-paid", ['reason' => 'Paid by UPI to our account'])->assertStatus(409);
    }

    public function test_a_draft_with_no_order_is_marked_paid_by_creating_one(): void
    {
        $invitation = $this->draft();

        $this->asAdmin()->postJson("/api/admin/invitations/{$invitation->id}/mark-paid", ['reason' => 'Paid in cash at the shop'])
            ->assertOk()
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.orders.0.paid_by', 'Anu')
            ->assertJsonPath('data.history.0.action', 'order.mark_paid');

        $order = Order::sole();
        $this->assertSame([49900, null, 'paid'], [$order->amount_paise, $order->razorpay_order_id, $order->status]);
    }

    // ── Corrections ──────────────────────────────────────────────

    public function test_a_correction_is_logged_field_by_field_and_redraws_the_picture(): void
    {
        $invitation = $this->draft(['bride' => ['name' => ['en' => 'Prya']]]);
        $invitation->update(['status' => 'published', 'published_at' => now(), 'event_date' => now()->subDay()]);

        $data = $invitation->data;
        $data['bride']['name']['en'] = 'Priya';
        $this->asAdmin()->putJson("/api/admin/invitations/{$invitation->id}", ['language' => 'both', 'data' => $data, 'note' => 'Spelling, by phone'])
            ->assertOk()
            ->assertJsonPath('meta.changed', 1);

        $log = AdminAction::where('action', 'invitation.update')->sole();
        $this->assertSame(['bride.name.en' => ['Prya', 'Priya']], $log->details['changes']);
        $this->assertSame('Spelling, by phone', $log->reason);
        $this->assertSame('Priya', $invitation->fresh()->data['bride']['name']['en']);
        $this->assertNotNull($invitation->fresh()->data['avatar'] ?? null, 'the avatar survives');
        Storage::disk('public')->assertExists($invitation->fresh()->og_image_path);

        // The same data again: nothing changed, nothing logged.
        $this->asAdmin()->putJson("/api/admin/invitations/{$invitation->id}", ['language' => 'both', 'data' => $data])->assertJsonPath('meta.changed', 0);
        $this->assertSame(1, AdminAction::where('action', 'invitation.update')->count());
    }

    public function test_disabling_shows_the_ended_page_and_enabling_brings_it_back(): void
    {
        $invitation = $this->draft();
        $invitation->update(['status' => 'published', 'published_at' => now()]);

        $this->asAdmin()->postJson("/api/admin/invitations/{$invitation->id}/disable", [])->assertJsonValidationErrors('reason');
        $this->asAdmin()->postJson("/api/admin/invitations/{$invitation->id}/disable", ['reason' => 'Family asked to take it down'])->assertOk();
        $this->get("/i/{$invitation->slug}")->assertStatus(410);

        $this->asAdmin()->postJson("/api/admin/invitations/{$invitation->id}/enable", ['reason' => 'Family asked to put it back'])
            ->assertOk()->assertJsonPath('data.status', 'published');
        $this->get("/i/{$invitation->slug}")->assertOk();

        $this->assertSame(['invitation.enable', 'invitation.disable'], AdminAction::latest('id')->limit(2)->pluck('action')->all());
    }

    public function test_removing_a_photo_needs_a_reason_and_is_logged(): void
    {
        $invitation = $this->draft();
        Storage::disk('public')->put('invitations/x.webp', 'x');
        $media = $invitation->media()->create(['type' => 'photo', 'path' => 'invitations/x.webp', 'sort_order' => 1, 'size_bytes' => 1]);

        $this->asAdmin()->deleteJson("/api/admin/invitations/{$invitation->id}/media/{$media->id}", ['reason' => 'Asked by the family'])
            ->assertOk()->assertJsonCount(0, 'data.photos')->assertJsonPath('data.history.0.action', 'media.remove');

        Storage::disk('public')->assertMissing('invitations/x.webp');
    }

    // ── Templates, lists, stats ──────────────────────────────────

    public function test_a_new_price_is_logged_and_used_by_the_next_checkout(): void
    {
        $template = Template::sole();
        $this->asAdmin()->putJson("/api/admin/templates/{$template->id}", ['price_paise' => 59900, 'is_active' => true, 'max_photos' => 6])
            ->assertOk()->assertJsonPath('data.price_paise', 59900);
        $this->assertSame(['price_paise' => [49900, 59900]], AdminAction::where('action', 'template.update')->sole()->details['changes']);

        config(['services.razorpay.key_id' => 'rzp_test_x', 'services.razorpay.key_secret' => 's']);
        Http::fake(['api.razorpay.com/*' => Http::response(['id' => 'order_N', 'amount' => 59900, 'currency' => 'INR'])]);
        $invitation = $this->draft();
        $this->actingAs($this->customer, 'web')->withHeader('Origin', 'http://localhost:5173')
            ->postJson("/api/invitations/{$invitation->id}/checkout")->assertCreated()->assertJsonPath('data.amount', 59900);
        Http::assertSent(fn (HttpRequest $request) => $request['amount'] === 59900);
    }

    public function test_lists_search_and_filter(): void
    {
        $this->draft();
        $other = User::factory()->create(['name' => 'Meena S', 'email' => 'meena@example.com']);
        $other->invitations()->create(['template_id' => Template::sole()->id, 'slug' => 'meena-arun-ab12', 'status' => 'published',
            'language' => 'en', 'data' => [], 'title' => 'Meena weds Arun', 'preview_token' => Str::random(40), 'published_at' => now()]);

        $this->asAdmin();
        $this->getJson('/api/admin/invitations?q=meena')->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Meena weds Arun');
        $this->getJson('/api/admin/invitations?status=draft')->assertJsonCount(1, 'data')->assertJsonPath('data.0.customer.name', 'Priya R');
        $this->getJson('/api/admin/invitations?q=50%25')->assertJsonCount(0, 'data');
        $this->getJson('/api/admin/users?q=meena')->assertJsonCount(1, 'data')->assertJsonPath('data.0.paid_count', 1);
        $this->getJson("/api/admin/users/{$other->id}")->assertJsonPath('data.invitations.0.slug', 'meena-arun-ab12');
    }

    public function test_the_dashboard_adds_up(): void
    {
        $paid = $this->draft();
        $paid->update(['status' => 'published', 'published_at' => now(), 'view_count' => 7]);
        Order::create(['user_id' => $this->customer->id, 'invitation_id' => $paid->id, 'amount_paise' => 49900, 'status' => 'paid', 'paid_at' => now(), 'razorpay_order_id' => 'o1']);
        Order::create(['user_id' => $this->customer->id, 'invitation_id' => $paid->id, 'amount_paise' => 49900, 'status' => 'created', 'razorpay_order_id' => 'o2']);
        $this->draft();

        $this->asAdmin()->getJson('/api/admin/stats')->assertOk()
            ->assertJsonPath('data.sales_today_paise', 49900)
            ->assertJsonPath('data.sales_month_paise', 49900)
            ->assertJsonPath('data.paid_orders_month', 1)
            ->assertJsonPath('data.published', 1)
            ->assertJsonPath('data.drafts', 1)
            ->assertJsonPath('data.total_views', 7)
            ->assertJsonCount(1, 'data.recent_orders');
    }

    // ── admin:create ─────────────────────────────────────────────

    public function test_admin_create_adds_an_admin_who_can_sign_in(): void
    {
        $this->artisan('admin:create')
            ->expectsQuestion('Name', 'Kavya')
            ->expectsQuestion('Email', 'anu@example.com') // taken
            ->expectsOutputToContain('already been taken')
            ->expectsQuestion('Email', 'Kavya@Example.com')
            ->expectsQuestion('Password (at least 10 characters)', 'short')
            ->expectsOutputToContain('at least 10 characters')
            ->expectsQuestion('Password (at least 10 characters)', 'a-long-password')
            ->expectsQuestion('Password again', 'a-long-passwort') // a typo
            ->expectsOutputToContain("The two passwords don't match.")
            ->expectsQuestion('Password (at least 10 characters)', 'a-long-password')
            ->expectsQuestion('Password again', 'a-long-password')
            ->expectsOutputToContain('can now sign in')
            ->assertSuccessful();

        $this->postJson('/api/admin/login', ['email' => 'kavya@example.com', 'password' => 'a-long-password'])->assertOk();
    }
}
