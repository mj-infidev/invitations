<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use RuntimeException;
use Tests\TestCase;

/**
 * Customer sign-in with Google (Socialite faked), /api/me, logout, and the
 * separation between the customer (`web`) and admin (`admin`) guards.
 */
class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    private const CLIENT = 'http://localhost:5173';

    protected function setUp(): void
    {
        parent::setUp();
        config(['invitations.client_url' => self::CLIENT]);

        // As the client app does: Sanctum gives a session only to requests from it.
        $this->withHeader('Origin', self::CLIENT);
    }

    private function fakeGoogle(string $id = 'g-100', ?string $email = 'priya@example.com', bool $verified = true): void
    {
        $google = (new GoogleUser)
            ->setRaw(['sub' => $id, 'email' => $email, 'email_verified' => $verified])
            ->map(['id' => $id, 'name' => 'Priya R', 'email' => $email, 'avatar' => 'https://example.com/p.jpg']);

        Socialite::shouldReceive('driver->user')->andReturn($google);
    }

    private function backFromGoogle(string $returnTo = '/')
    {
        return $this->withSession(['signin.return_to' => $returnTo])
            ->get('/api/auth/google/callback?code=abc&state=xyz');
    }

    public function test_redirect_sends_the_browser_to_google_and_keeps_a_safe_return_path(): void
    {
        $this->get('/api/auth/google/redirect?redirect=/create?template=1')
            ->assertRedirectContains('accounts.google.com')
            ->assertSessionHas('signin.return_to', '/create?template=1');
    }

    public function test_return_paths_that_leave_the_client_site_are_refused(): void
    {
        foreach (['https://evil.example', '//evil.example', '/\\evil.example', 'create'] as $path) {
            $this->get('/api/auth/google/redirect?redirect='.urlencode($path))
                ->assertSessionHas('signin.return_to', '/');
        }
    }

    public function test_a_new_customer_is_created_signed_in_and_returned_to_the_client(): void
    {
        $this->fakeGoogle();

        $this->backFromGoogle('/create')->assertRedirect(self::CLIENT.'/create');

        $user = User::sole();
        $this->assertSame('g-100', $user->google_id);
        $this->assertSame('priya@example.com', $user->email);
        $this->assertSame('Priya R', $user->name);
        $this->assertNull($user->password);
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertGuest('admin');
    }

    public function test_a_returning_customer_is_found_by_google_id(): void
    {
        $existing = User::factory()->create(['google_id' => 'g-100', 'email' => 'old@example.com']);
        $this->fakeGoogle();

        $this->backFromGoogle();

        $this->assertSame(1, User::count());
        $this->assertAuthenticatedAs($existing, 'web');
        $this->assertSame('https://example.com/p.jpg', $existing->fresh()->avatar_url);
    }

    public function test_a_verified_email_links_an_account_that_has_no_google_id(): void
    {
        $existing = User::factory()->create(['google_id' => null, 'email' => 'priya@example.com']);
        $this->fakeGoogle();

        $this->backFromGoogle();

        $this->assertSame(1, User::count());
        $this->assertSame('g-100', $existing->fresh()->google_id);
        $this->assertAuthenticatedAs($existing, 'web');
    }

    public function test_an_unverified_email_never_links_or_is_stored(): void
    {
        $existing = User::factory()->create(['google_id' => null, 'email' => 'priya@example.com']);
        $this->fakeGoogle(verified: false);

        $this->backFromGoogle();

        $this->assertSame(2, User::count());
        $this->assertNull($existing->fresh()->google_id);
        $this->assertNull(User::where('google_id', 'g-100')->sole()->email);
    }

    public function test_cancelling_at_google_returns_with_signin_failed(): void
    {
        $this->withSession(['signin.return_to' => '/create'])
            ->get('/api/auth/google/callback?error=access_denied')
            ->assertRedirect(self::CLIENT.'/create?signin=failed');

        $this->assertGuest('web');
    }

    public function test_a_failed_exchange_with_google_returns_with_signin_failed(): void
    {
        Socialite::shouldReceive('driver->user')->andThrow(new RuntimeException('invalid state'));

        $this->backFromGoogle('/?tab=1')->assertRedirect(self::CLIENT.'/?tab=1&signin=failed');
        $this->assertGuest('web');
    }

    public function test_me_is_null_for_a_guest(): void
    {
        $this->getJson('/api/me')->assertOk()->assertExactJson(['data' => null]);
    }

    public function test_me_returns_the_signed_in_customer_without_private_fields(): void
    {
        $user = User::factory()->create(['google_id' => 'g-7']);

        $this->actingAs($user, 'web')->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonMissingPath('data.google_id')
            ->assertJsonMissingPath('data.password');
    }

    public function test_logout_signs_the_customer_out(): void
    {
        $this->actingAs(User::factory()->create(), 'web')
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->assertGuest('web');
    }

    public function test_logout_needs_a_signed_in_customer(): void
    {
        $this->postJson('/api/auth/logout')->assertUnauthorized();
    }

    public function test_an_admin_session_is_not_a_customer(): void
    {
        $admin = Admin::create(['name' => 'A', 'email' => 'a@example.com', 'password' => 'secret-pass']);

        $this->actingAs($admin, 'admin');

        $this->getJson('/api/me')->assertOk()->assertExactJson(['data' => null]);
        $this->postJson('/api/auth/logout')->assertUnauthorized();
    }

    public function test_a_customer_session_is_not_an_admin(): void
    {
        $this->actingAs(User::factory()->create(), 'web');

        $this->assertGuest('admin');
    }

    public function test_customer_logout_leaves_an_admin_on_the_same_cookie_signed_in(): void
    {
        $admin = Admin::create(['name' => 'A', 'email' => 'a@example.com', 'password' => 'secret-pass']);
        $this->actingAs($admin, 'admin')->actingAs(User::factory()->create(), 'web');

        $this->postJson('/api/auth/logout')->assertOk();

        $this->assertGuest('web');
        $this->assertAuthenticatedAs($admin, 'admin');
    }
}
