<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\Order;
use App\Models\PaymentEvent;
use App\Models\User;
use Database\Seeders\DemoInvitationSeeder;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Paying for an invitation: checkout, verify, webhook, publishing and the
 * WhatsApp picture. Razorpay is faked; no request leaves the machine.
 */
class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private const KEY_SECRET = 'test_key_secret';

    private const WEBHOOK_SECRET = 'test_webhook_secret';

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TemplateSeeder::class);
        Storage::fake('public');
        config([
            'services.razorpay.key_id' => 'rzp_test_abc',
            'services.razorpay.key_secret' => self::KEY_SECRET,
            'services.razorpay.webhook_secret' => self::WEBHOOK_SECRET,
        ]);
        Http::preventStrayRequests();
        Http::fake(['api.razorpay.com/v1/orders' => fn (HttpRequest $request) => Http::response([
            'id' => 'order_'.substr(md5($request->body().microtime()), 0, 14),
            'amount' => $request['amount'],
            'currency' => 'INR',
        ])]);
        $this->customer = User::factory()->create(['name' => 'Priya R', 'email' => 'priya@example.com']);
        $this->withHeader('Origin', 'http://localhost:5173');
    }

    /** A draft with everything required filled in. */
    private function readyDraft(string $language = 'both', ?User $owner = null): Invitation
    {
        $this->actingAs($owner ?? $this->customer, 'web');
        $id = $this->postJson('/api/invitations', ['template' => 'tamil-hindu-wedding'])->json('data.id');

        $data = DemoInvitationSeeder::data();
        unset($data['avatar']);
        $data['muhurtham']['date'] = now()->addMonths(2)->toDateString();
        if ($language === 'ta') {
            $data['bride']['name']['en'] = null;
            $data['groom']['name']['en'] = null;
        }
        $this->putJson("/api/invitations/{$id}", ['language' => $language, 'data' => $data])->assertOk()->assertJsonCount(0, 'data.missing');

        return Invitation::findOrFail($id);
    }

    private function checkout(Invitation $invitation): Order
    {
        $this->postJson("/api/invitations/{$invitation->id}/checkout")->assertCreated();

        return Order::latest('id')->firstOrFail();
    }

    private function verify(Order $order, string $paymentId = 'pay_123', ?string $signature = null)
    {
        return $this->postJson('/api/payments/verify', [
            'razorpay_order_id' => $order->razorpay_order_id,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $signature ?? hash_hmac('sha256', "{$order->razorpay_order_id}|{$paymentId}", self::KEY_SECRET),
        ]);
    }

    private function webhook(string $event, Order $order, array $payment = [], ?string $eventId = null, ?string $signature = null)
    {
        $body = json_encode([
            'event' => $event,
            'payload' => ['payment' => ['entity' => $payment + [
                'id' => 'pay_hook',
                'order_id' => $order->razorpay_order_id,
                'amount' => $order->amount_paise,
                'currency' => 'INR',
                'status' => $event === 'payment.failed' ? 'failed' : 'captured',
            ]]],
        ]);

        return $this->call('POST', '/api/webhooks/razorpay', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => $signature ?? hash_hmac('sha256', $body, self::WEBHOOK_SECRET),
            'HTTP_X_RAZORPAY_EVENT_ID' => $eventId ?? 'evt_'.uniqid(),
        ], $body);
    }

    // ── Checkout ─────────────────────────────────────────────────

    public function test_checkout_creates_an_order_at_the_templates_price_with_capture_on(): void
    {
        $draft = $this->readyDraft();

        $response = $this->postJson("/api/invitations/{$draft->id}/checkout")
            ->assertCreated()
            ->assertJsonPath('data.key_id', 'rzp_test_abc')
            ->assertJsonPath('data.amount', 49900)
            ->assertJsonPath('data.prefill.email', 'priya@example.com')
            ->assertJsonPath('data.test_mode', true);

        $order = Order::sole();
        $this->assertSame(['created', 49900, $response->json('data.order_id')], [$order->status, $order->amount_paise, $order->razorpay_order_id]);
        Http::assertSent(fn (HttpRequest $request) => $request['payment_capture'] === 1 && $request['amount'] === 49900);
    }

    public function test_checkout_is_refused_until_everything_needed_is_filled_in(): void
    {
        $this->actingAs($this->customer, 'web');
        $id = $this->postJson('/api/invitations', ['template' => 'tamil-hindu-wedding'])->json('data.id');

        $this->postJson("/api/invitations/{$id}/checkout")->assertUnprocessable();
        $this->assertSame(0, Order::count());
    }

    public function test_checkout_is_refused_for_a_published_or_someone_elses_invitation(): void
    {
        $theirs = $this->readyDraft(owner: User::factory()->create());
        $mine = $this->readyDraft();
        $mine->update(['status' => 'published']);

        $this->actingAs($this->customer, 'web');
        $this->postJson("/api/invitations/{$theirs->id}/checkout")->assertNotFound();
        $this->postJson("/api/invitations/{$mine->id}/checkout")->assertStatus(409);
    }

    // ── Verify ───────────────────────────────────────────────────

    public function test_a_verified_payment_publishes_with_a_name_based_link_and_a_picture(): void
    {
        $draft = $this->readyDraft();
        $order = $this->checkout($draft);

        $this->verify($order)->assertOk()
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.public_url', fn ($url) => str_contains($url, '/i/priya-karthik-'));

        $invitation = $draft->fresh();
        $this->assertMatchesRegularExpression('/^priya-karthik-[a-z0-9]{4}$/', $invitation->slug);
        $this->assertNotNull($invitation->published_at);
        $this->assertSame(['paid', 'pay_123'], [$order->fresh()->status, $order->fresh()->razorpay_payment_id]);

        Storage::disk('public')->assertExists($invitation->og_image_path);
        [$width, $height, $type] = getimagesizefromstring(Storage::disk('public')->get($invitation->og_image_path));
        $this->assertSame([1200, 630, IMAGETYPE_JPEG], [$width, $height, $type]);
        $this->assertLessThan(300 * 1024, Storage::disk('public')->size($invitation->og_image_path));

        // Public now: no token, no watermark, and the WhatsApp card has its picture.
        $page = $this->get("/i/{$invitation->slug}")->assertOk();
        $page->assertDontSee('class="watermark"', false);
        $page->assertSee('<meta property="og:image" content="'.asset('storage/'.$invitation->og_image_path).'">', false);
    }

    public function test_a_bad_signature_is_refused_and_nothing_is_published(): void
    {
        $order = $this->checkout($this->readyDraft());

        $this->verify($order, signature: str_repeat('0', 64))->assertUnprocessable();

        $this->assertSame('created', $order->fresh()->status);
        $this->assertSame('draft', $order->invitation->fresh()->status);
    }

    public function test_someone_elses_order_cannot_be_verified(): void
    {
        $order = $this->checkout($this->readyDraft(owner: User::factory()->create()));

        $this->actingAs($this->customer, 'web');
        $this->verify($order)->assertNotFound();
    }

    public function test_verifying_twice_publishes_once(): void
    {
        $order = $this->checkout($this->readyDraft());

        $this->verify($order)->assertOk();
        $slug = $order->invitation->fresh()->slug;
        $this->verify($order)->assertOk();

        $this->assertSame($slug, $order->invitation->fresh()->slug);
    }

    public function test_a_tamil_only_invitation_gets_a_plain_link(): void
    {
        $order = $this->checkout($this->readyDraft('ta'));

        $this->verify($order)->assertOk();

        $this->assertMatchesRegularExpression('/^wedding-[a-z0-9]{4}$/', $order->invitation->fresh()->slug);
    }

    // ── Webhook ──────────────────────────────────────────────────

    public function test_a_captured_webhook_publishes_when_the_browser_never_came_back(): void
    {
        $order = $this->checkout($this->readyDraft());

        $this->webhook('payment.captured', $order)->assertOk();

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame('published', $order->invitation->fresh()->status);
        $this->assertNotNull(PaymentEvent::sole()->processed_at);
    }

    public function test_a_webhook_with_a_bad_signature_or_no_secret_is_refused(): void
    {
        $order = $this->checkout($this->readyDraft());

        $this->webhook('payment.captured', $order, signature: 'nope')->assertStatus(400);
        config(['services.razorpay.webhook_secret' => null]);
        $this->webhook('payment.captured', $order)->assertStatus(400);

        $this->assertSame('created', $order->fresh()->status);
        $this->assertSame(0, PaymentEvent::count());
    }

    public function test_a_redelivered_event_is_handled_once(): void
    {
        $order = $this->checkout($this->readyDraft());

        $this->webhook('payment.captured', $order, eventId: 'evt_same')->assertOk();
        $this->webhook('payment.captured', $order, eventId: 'evt_same')->assertOk()->assertJsonPath('message', 'Already received.');

        $this->assertSame(1, PaymentEvent::count());
    }

    public function test_a_captured_amount_that_does_not_match_is_not_published(): void
    {
        $order = $this->checkout($this->readyDraft());

        $this->webhook('payment.captured', $order, ['amount' => 100])->assertOk();

        $this->assertSame('created', $order->fresh()->status);
        $this->assertSame('draft', $order->invitation->fresh()->status);
    }

    public function test_verify_and_webhook_both_arriving_publish_once(): void
    {
        $order = $this->checkout($this->readyDraft());

        $this->webhook('payment.captured', $order, ['id' => 'pay_123'])->assertOk();
        $slug = $order->invitation->fresh()->slug;
        $this->verify($order, 'pay_123')->assertOk()->assertJsonPath('data.status', 'published');

        $this->assertSame($slug, $order->invitation->fresh()->slug);
        $this->assertSame('paid', $order->fresh()->status);
    }

    public function test_a_failed_attempt_can_still_be_followed_by_a_successful_one(): void
    {
        $order = $this->checkout($this->readyDraft());

        $this->webhook('payment.failed', $order)->assertOk();
        $this->assertSame('failed', $order->fresh()->status);
        $this->assertSame('draft', $order->invitation->fresh()->status);

        $this->webhook('payment.captured', $order)->assertOk();
        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame('published', $order->invitation->fresh()->status);
    }

    public function test_an_event_for_an_order_that_is_not_ours_is_accepted_and_ignored(): void
    {
        $order = $this->checkout($this->readyDraft());
        $order->razorpay_order_id = 'order_from_another_project';

        $this->webhook('payment.captured', $order)->assertOk();

        $this->assertSame('draft', Invitation::sole()->status);
    }

    // ── After publishing ─────────────────────────────────────────

    public function test_the_picture_is_redrawn_only_when_the_names_or_date_change(): void
    {
        $draft = $this->readyDraft();
        $this->verify($this->checkout($draft))->assertOk();
        $invitation = $draft->fresh();
        $first = $invitation->og_image_path;

        $data = $invitation->data;
        $data['message']['en'] = 'A new message';
        $this->putJson("/api/invitations/{$invitation->id}", ['language' => 'both', 'data' => $data])->assertOk();
        $this->assertSame($first, $invitation->fresh()->og_image_path);

        $data['groom']['name']['en'] = 'Karthikeyan';
        $this->putJson("/api/invitations/{$invitation->id}", ['language' => 'both', 'data' => $data])->assertOk();
        $second = $invitation->fresh()->og_image_path;

        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertExists($second);
        Storage::disk('public')->assertMissing($first);
        $this->assertSame($invitation->slug, $invitation->fresh()->slug); // the link never changes
    }
}
