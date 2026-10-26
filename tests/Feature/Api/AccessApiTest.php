<?php

namespace Tests\Feature\Api;

use App\Models\AccessPass;
use App\Models\AccessPlan;
use App\Models\User;
use App\Services\AccessGate;
use App\Support\PaymentSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\DirectoryTestCase;

class AccessApiTest extends DirectoryTestCase
{
    private const SECRET = 'sk_test_abc123';

    private function turnOnPayments(): void
    {
        PaymentSettings::save(['enabled' => true, 'paystack_secret' => self::SECRET]);
    }

    private function paidPass(User $user, string $kind, ?int $searches = null, ?int $hours = 24, ?array $areas = null): AccessPass
    {
        return AccessPass::create([
            'user_id' => $user->id, 'plan_name' => 'Test', 'kind' => $kind, 'areas' => $areas,
            'searches_total' => $searches, 'searches_left' => $searches, 'duration_hours' => $hours,
            'amount' => 1000, 'currency' => 'GHS', 'reference' => 'CS-'.uniqid(), 'status' => 'paid',
            'paid_at' => now(), 'expires_at' => $hours ? now()->addHours($hours) : null,
        ]);
    }

    public function test_everything_is_open_while_payments_are_off(): void
    {
        $this->getJson('/api/v1/facilities/f-legon')
            ->assertOk()
            ->assertJsonPath('data.locked', false)
            ->assertJsonPath('data.contact.phone', fn ($p) => $p !== null);
    }

    public function test_details_are_locked_without_a_plan(): void
    {
        $this->turnOnPayments();

        $this->getJson('/api/v1/facilities/f-legon')
            ->assertOk()
            ->assertJsonPath('data.locked', true)
            ->assertJsonPath('data.name', null)
            ->assertJsonPath('data.area', null)
            ->assertJsonPath('data.facility_type', fn ($t) => $t !== null)
            ->assertJsonPath('data.contact.phone', null)
            ->assertJsonPath('data.directions_url', null)
            ->assertJsonMissingPath('data.sources');

        $this->getJson('/api/v1/services?q=ultrasound')
            ->assertOk()
            ->assertJsonPath('data.0.locked', true)
            ->assertJsonPath('data.0.price', null)
            ->assertJsonPath('access.required', true)
            ->assertJsonPath('access.unlocked', false);
    }

    public function test_unlimited_pass_opens_everything(): void
    {
        $this->turnOnPayments();
        $user = User::factory()->create();
        $this->paidPass($user, AccessPlan::UNLIMITED);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/facilities/f-legon')->assertJsonPath('data.locked', false);
        $this->getJson('/api/v1/services?q=ultrasound')->assertJsonPath('access.unlocked', true);
    }

    public function test_a_search_credit_opens_that_search_only_and_is_spent_once(): void
    {
        $this->turnOnPayments();
        $user = User::factory()->create();
        $pass = $this->paidPass($user, AccessPlan::SEARCHES, searches: 2, hours: null);
        Sanctum::actingAs($user);

        $facilityId = $this->getJson('/api/v1/services?q=ultrasound')->assertJsonPath('data.0.locked', true)->json('data.0.facility_id');

        $this->postJson('/api/v1/me/unlocks', ['q' => 'ultrasound'])->assertOk()->assertJsonPath('data.unlocked', true)->assertJsonPath('data.searches_left', 1);
        // Opening the same search again (any page) is free.
        $this->postJson('/api/v1/me/unlocks', ['q' => 'Ultrasound', 'page' => 2])->assertOk()->assertJsonPath('data.searches_left', 1);
        $this->assertSame(1, $pass->fresh()->searches_left);

        $this->getJson('/api/v1/services?q=ultrasound')->assertJsonPath('data.0.locked', false)->assertJsonPath('access.unlocked', true);
        $this->getJson("/api/v1/facilities/{$facilityId}")->assertJsonPath('data.locked', false);
    }

    public function test_a_pass_only_opens_the_areas_its_plan_includes(): void
    {
        $this->turnOnPayments();
        $user = User::factory()->create();
        $this->paidPass($user, AccessPlan::UNLIMITED, areas: ['specialists']);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/facilities/f-legon')
            ->assertJsonPath('data.locked', true)
            ->assertJsonPath('data.contact.phone', null)
            ->assertJsonPath('data.services', fn ($services) => collect($services)->every(fn ($s) => $s['locked'] === ($s['type'] === 'test')));

        $this->getJson('/api/v1/services?q=gynaecologist&type=specialist')
            ->assertJsonPath('data.0.locked', false)
            ->assertJsonPath('data.0.facility.locked', false)
            ->assertJsonPath('access.unlocked', true)
            ->assertJsonPath('access.areas', ['specialists' => true]);
        $this->getJson('/api/v1/services?q=ultrasound&type=test')
            ->assertJsonPath('data.0.locked', true)
            ->assertJsonPath('access.unlocked', false);
        $this->getJson('/api/v1/me/access')
            ->assertJsonPath('data.full_access_until', null)
            ->assertJsonPath('data.access_until.facilities', null);
    }

    public function test_search_credits_only_open_searches_their_pack_covers(): void
    {
        $this->turnOnPayments();
        $user = User::factory()->create();
        $pass = $this->paidPass($user, AccessPlan::SEARCHES, searches: 3, hours: null, areas: ['specialists']);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/me/unlocks', ['q' => 'ultrasound', 'type' => 'test'])->assertStatus(402);
        $this->postJson('/api/v1/me/unlocks', ['q' => 'gynaecologist', 'type' => 'specialist'])->assertOk()->assertJsonPath('data.unlocked', true);
        $this->assertSame(2, $pass->fresh()->searches_left);
    }

    public function test_unlocking_needs_credits_and_a_query(): void
    {
        $this->turnOnPayments();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/me/unlocks', ['q' => 'ultrasound'])->assertStatus(402);
        $this->postJson('/api/v1/me/unlocks', ['city' => 'Accra'])->assertStatus(422);
    }

    public function test_checkout_and_payment_confirmation(): void
    {
        $this->turnOnPayments();
        $plan = AccessPlan::where('name', 'Full week')->firstOrFail();
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/x']]),
            'api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success', 'amount' => $plan->price, 'currency' => 'GHS', 'channel' => 'mobile_money']]),
        ]);
        Sanctum::actingAs($user = User::factory()->create());

        $ref = $this->postJson('/api/v1/me/passes', ['plan_id' => $plan->id])
            ->assertCreated()
            ->assertJsonPath('data.authorization_url', 'https://checkout.paystack.com/x')
            ->assertJsonPath('data.status', 'pending')
            ->json('data.reference');

        $this->getJson("/api/v1/me/passes/{$ref}")->assertOk()->assertJsonPath('data.status', 'paid');
        $pass = AccessPass::where('reference', $ref)->first();
        $this->assertEqualsWithDelta(now()->addDays(7)->timestamp, $pass->expires_at->timestamp, 5);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'initialize') && $r['amount'] === $plan->price && $r->hasHeader('Authorization', 'Bearer '.self::SECRET));

        $this->getJson('/api/v1/me/access')->assertJsonPath('data.full_access_until', fn ($v) => $v !== null);
        $this->assertSame($user->id, $pass->user_id);
    }

    public function test_underpaid_transactions_do_not_activate(): void
    {
        $this->turnOnPayments();
        $plan = AccessPlan::where('name', 'Full day')->firstOrFail();
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://x']]),
            'api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success', 'amount' => 100, 'currency' => 'GHS']]),
        ]);
        Sanctum::actingAs(User::factory()->create());

        $ref = $this->postJson('/api/v1/me/passes', ['plan_id' => $plan->id])->json('data.reference');
        $this->getJson("/api/v1/me/passes/{$ref}")->assertJsonPath('data.status', 'pending');
    }

    public function test_webhook_requires_a_valid_signature_and_activates_the_pass(): void
    {
        $this->turnOnPayments();
        $user = User::factory()->create();
        $pass = AccessPass::create([
            'user_id' => $user->id, 'plan_name' => '5 searches', 'kind' => AccessPlan::SEARCHES, 'searches_total' => 5,
            'duration_hours' => 720, 'amount' => 1000, 'currency' => 'GHS', 'reference' => 'CS-HOOK',
        ]);
        $body = json_encode(['event' => 'charge.success', 'data' => ['reference' => 'CS-HOOK', 'status' => 'success', 'amount' => 1000, 'currency' => 'GHS']]);

        $this->call('POST', '/api/v1/payments/paystack/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_PAYSTACK_SIGNATURE' => 'bad'], $body)->assertUnauthorized();
        $this->call('POST', '/api/v1/payments/paystack/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $body, self::SECRET)], $body)->assertOk();

        $pass->refresh();
        $this->assertSame('paid', $pass->status);
        $this->assertSame(5, $pass->searches_left);
    }

    public function test_plans_are_listed_from_the_database(): void
    {
        AccessPlan::create(['name' => 'Hidden', 'kind' => 'unlimited', 'duration_hours' => 1, 'price' => 100, 'is_active' => false]);

        $names = collect($this->getJson('/api/v1/plans')->assertOk()->json('data'))->pluck('name');
        $this->assertContains('Full month', $names);
        $this->assertNotContains('Hidden', $names);
        $this->getJson('/api/v1/meta')->assertJsonPath('data.access_plans.0.currency', 'GHS');
    }

    public function test_admin_manages_plans_and_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin/settings')->assertOk()->assertSee('Main colour')->assertDontSee('Paystack secret key');
        $this->get('/admin/settings/payments')->assertOk()->assertSee('Paystack secret key');
        $this->get('/admin/settings/nope')->assertNotFound();
        $this->get('/admin/payments')->assertOk()->assertSee('Recent payments');
        $this->get('/admin/plans')->assertOk()->assertSee('Full week');

        $this->put('/admin/settings/payments', ['enabled' => 1, 'unlock_hours' => 24, 'channels' => ['card']])
            ->assertSessionHasErrors('enabled'); // no key yet
        $this->put('/admin/settings/payments', ['enabled' => 1, 'paystack_secret' => self::SECRET, 'unlock_hours' => 12, 'channels' => ['mobile_money']])
            ->assertSessionHasNoErrors();
        $this->assertTrue(PaymentSettings::enabled());
        $this->assertSame(self::SECRET, PaymentSettings::secretKey());
        $this->assertSame(12, PaymentSettings::unlockHours());

        $newPlan = ['name' => '10 searches', 'billing' => 'search', 'searches' => 10, 'valid_days' => 60, 'price' => '18.50', 'is_active' => 1];
        $this->post('/admin/plans', $newPlan)->assertSessionHasErrors('areas');
        $this->post('/admin/plans', $newPlan + ['areas' => ['tests', 'facilities']])->assertSessionHasNoErrors();
        $plan = AccessPlan::where('name', '10 searches')->firstOrFail();
        $this->assertSame(1850, $plan->price);
        $this->assertSame(AccessPlan::SEARCHES, $plan->kind);
        $this->assertSame(60 * 24, $plan->duration_hours);
        $this->assertSame(['facilities', 'tests'], $plan->areas);
        $this->get('/admin/plans')->assertSee('Facility details, Diagnostic tests');

        foreach (['hour', 'day', 'week', 'month', 'year'] as $period) {
            $this->post('/admin/plans', ['name' => "Per {$period}", 'billing' => $period, 'length' => 1, 'price' => 5, 'areas' => ['facilities'], 'is_active' => 1])->assertSessionHasNoErrors();
        }
        $quarter = ['name' => 'Quarter', 'billing' => 'month', 'length' => 3, 'price' => 300, 'areas' => ['facilities'], 'is_active' => 1];
        $this->post('/admin/plans', $quarter)->assertSessionHasNoErrors();
        $plan = AccessPlan::where('name', 'Quarter')->firstOrFail();
        $this->assertSame([AccessPlan::UNLIMITED, 'month', 3, 'Every 3 months'], [$plan->kind, $plan->period, $plan->period_count, $plan->billingLabel()]);
        $this->assertSame('Per year', AccessPlan::where('name', 'Per year')->first()->billingLabel());
    }

    public function test_monthly_and_yearly_passes_end_on_the_calendar_date(): void
    {
        $this->travelTo('2027-01-31 10:00:00');
        $user = User::factory()->create();
        $gate = app(AccessGate::class);

        foreach (['month' => '2027-02-28 10:00:00', 'year' => '2028-01-31 10:00:00', 'week' => '2027-02-07 10:00:00'] as $period => $expected) {
            $pass = AccessPass::create([
                'user_id' => $user->id, 'plan_name' => $period, 'kind' => AccessPlan::UNLIMITED, 'period' => $period, 'period_count' => 1,
                'areas' => [$period === 'month' ? 'facilities' : ($period === 'year' ? 'specialists' : 'tests')],
                'duration_hours' => AccessPlan::PERIOD_HOURS[$period], 'amount' => 1000, 'currency' => 'GHS', 'reference' => "CS-{$period}",
            ]);
            $gate->apply("CS-{$period}", ['status' => 'success', 'amount' => 1000, 'currency' => 'GHS']);
            $this->assertSame($expected, $pass->fresh()->expires_at->toDateTimeString());
        }
    }

    public function test_admin_sets_branding_used_by_site_and_app(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->put('/admin/settings/branding', [
            'name' => 'CareLink', 'tagline' => 'Care near you', 'color' => '#AA3300',
            'logo' => UploadedFile::fake()->image('logo.png', 256, 256),
        ])->assertSessionHasNoErrors();

        $this->getJson('/api/v1/meta')
            ->assertJsonPath('data.branding.name', 'CareLink')
            ->assertJsonPath('data.branding.color', '#aa3300')
            ->assertJsonPath('data.branding.logo_url', fn ($u) => str_contains($u, '/storage/branding/'));
        $this->get('/')->assertSee('CareLink')->assertSee('content="#aa3300"', false);

        $this->put('/admin/settings/branding', ['name' => 'CareLink', 'color' => '#aa3300', 'favicon' => UploadedFile::fake()->image('icon.png', 64, 64)])
            ->assertSessionHasNoErrors();
        $this->getJson('/api/v1/meta')->assertJsonPath('data.branding.favicon_url', fn ($u) => str_contains($u, '/storage/branding/favicon-'));
        $this->get('/')->assertSee('rel="icon" href="/storage/branding/favicon-', false);

        $this->put('/admin/settings/branding', ['name' => 'CareLink', 'color' => 'red'])->assertSessionHasErrors('color');
        $this->put('/admin/settings/branding', ['name' => 'CareLink', 'color' => '#aa3300', 'favicon' => UploadedFile::fake()->create('icon.svg', 5, 'image/svg+xml')])
            ->assertSessionHasErrors('favicon');
    }
}
