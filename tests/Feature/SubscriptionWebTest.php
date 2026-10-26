<?php

namespace Tests\Feature;

use App\Models\AccessPass;
use App\Models\AccessPlan;
use App\Models\User;
use App\Support\PaymentSettings;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Http;

class SubscriptionWebTest extends DirectoryTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        PaymentSettings::save(['enabled' => true, 'paystack_secret' => 'sk_test_abc123']);
    }

    private function memberWith(string $kind, ?array $areas, ?int $searches = null): User
    {
        $user = User::factory()->create();
        AccessPass::create([
            'user_id' => $user->id, 'plan_name' => 'Test', 'kind' => $kind, 'areas' => $areas,
            'searches_total' => $searches, 'searches_left' => $searches, 'duration_hours' => 24,
            'amount' => 1000, 'currency' => 'GHS', 'reference' => 'CS-'.uniqid(), 'status' => 'paid',
            'paid_at' => now(), 'expires_at' => now()->addDay(),
        ]);

        return $user;
    }

    public function test_search_shows_counts_but_no_details_without_a_plan(): void
    {
        $this->get('/search?q=gynaecologist')
            ->assertOk()
            ->assertSee('2 listings match this search')
            ->assertSee('Create an account')
            ->assertDontSee('Legon Test Hospital')
            ->assertDontSee('+233200000001');

        $this->get('/facilities/f-legon')
            ->assertOk()
            ->assertSee('1 specialist listing here')
            ->assertDontSee('+233200000001')
            ->assertDontSee('Dr. Ama Mensah')
            ->assertDontSee('Full Blood Count');
    }

    public function test_facility_page_opens_only_the_areas_in_the_plan(): void
    {
        $this->actingAs($this->memberWith(AccessPlan::UNLIMITED, ['facilities']));

        $this->get('/facilities/f-legon')
            ->assertOk()
            ->assertSee('+233200000001')
            ->assertDontSee('Dr. Ama Mensah')
            ->assertSee('1 specialist listing here');

        $this->get('/search?q=gynaecologist&type=specialist')->assertSee('match this search')->assertDontSee('Dansoman Family Clinic');
        $this->get('/search?q=legon&tab=facilities')->assertSee('Legon Test Hospital')->assertSee('tel:+233200000001', false);
    }

    public function test_a_search_credit_opens_results_on_the_website(): void
    {
        $user = $this->memberWith(AccessPlan::SEARCHES, null, searches: 2);
        $this->actingAs($user);

        $this->get('/search?q=gynaecologist')->assertSee('Use 1 of your 2 searches');
        $this->post('/search/unlock', ['q' => 'gynaecologist', 'tab' => 'services'])->assertRedirect()->assertSessionHasNoErrors();

        $this->get('/search?q=gynaecologist')->assertSee('Dansoman Family Clinic')->assertSee('Dr. Ama Mensah');
        $this->assertSame(1, AccessPass::where('user_id', $user->id)->value('searches_left'));
    }

    public function test_closest_to_you_and_the_map_hide_names_and_locations_without_a_plan(): void
    {
        $this->withUnencryptedCookie('cs_loc', '5.63500,-0.16000,East Legon')
            ->get('/nearby-panel')
            ->assertOk()
            ->assertSee('Names and locations need a plan')
            ->assertSee('Exact locations and names are for members')
            ->assertDontSee('Legon Test Hospital')
            ->assertDontSee(route('facilities.show', 'f-legon'));

        $markers = $this->getJson('/map/markers')->assertOk()->assertJsonPath('locked', true)->json('data');
        $this->assertNotEmpty($markers);
        $this->assertTrue(collect($markers)->every(fn ($m) => $m[0] === null && $m[3] === null));
        $this->getJson('/api/v1/facilities/f-legon')->assertJsonPath('data.location.lat', null);
    }

    public function test_members_with_facility_access_see_names_and_locations(): void
    {
        $this->actingAs($this->memberWith(AccessPlan::UNLIMITED, ['facilities']));

        $this->withUnencryptedCookie('cs_loc', '5.63500,-0.16000,East Legon')
            ->get('/nearby-panel')
            ->assertSee('Legon Test Hospital')
            ->assertDontSee('Names and locations need a plan');
        $this->getJson('/map/markers')->assertJsonPath('locked', false)->assertJsonFragment(['f-legon']);
    }

    public function test_admins_and_free_mode_see_everything(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get('/facilities/f-legon')->assertSee('+233200000001')->assertSee('Dr. Ama Mensah');

        auth()->logout();
        PaymentSettings::save(['enabled' => false]);
        $this->get('/search?q=gynaecologist')->assertSee('Dansoman Family Clinic');
    }

    public function test_visitors_sign_up_on_the_website_and_return_to_their_search(): void
    {
        $this->get('/register?back='.urlencode('/search?q=gynaecologist'))->assertOk()->assertSee('Create an account')->assertSee('Ghana +233');

        $form = ['name' => 'Ama', 'email' => 'ama@example.test', 'phone_country' => 'GH', 'password' => 'secret123', 'password_confirmation' => 'secret123'];
        $this->post('/register', ['password_confirmation' => 'nope'] + $form)->assertSessionHasErrors('password');
        $this->post('/register', ['phone' => '024'] + $form)->assertSessionHasErrors('phone');
        $this->post('/register', ['phone_country' => 'XX'] + $form)->assertSessionHasErrors('phone_country');
        $this->post('/register', ['phone' => '024 000 0000'] + $form)->assertRedirect('/search?q=gynaecologist');

        $user = User::where('email', 'ama@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->isAdmin());
        $this->assertSame('+233240000000', $user->phone);
        $this->get('/plans')->assertOk()->assertSee('You don’t have a plan yet', false);
    }

    public function test_phone_numbers_are_saved_with_the_country_code(): void
    {
        $this->assertSame('+447700900123', PhoneNumber::format('GB', '07700 900123'));
        $this->assertSame('+2348031234567', PhoneNumber::format('GH', '+234 803 123 4567')); // typed with its own code
        $this->assertSame('+390612345678', PhoneNumber::format('IT', '06 1234 5678')); // Italy keeps its 0
        $this->assertNull(PhoneNumber::format('GH', '12'));
    }

    public function test_sign_in_only_returns_to_pages_on_this_site(): void
    {
        $this->get('/login?back='.urlencode('//evil.test/x'));
        $this->post('/login', ['login' => User::factory()->create(['email' => 'm@example.test'])->email, 'password' => 'password'])
            ->assertRedirect(route('home'));
    }

    public function test_members_buy_a_plan_on_the_website(): void
    {
        $plan = AccessPlan::where('name', 'Full week')->firstOrFail();
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/web']]),
            'api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success', 'amount' => $plan->price, 'currency' => 'GHS']]),
        ]);

        $this->get('/plans')->assertOk()->assertSee('Full week')->assertSee('Create an account to buy');
        $this->post("/plans/{$plan->id}/checkout")->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->get('/plans')->assertSee('Pay GHS '.number_format($plan->price / 100, 2));
        $this->post("/plans/{$plan->id}/checkout")->assertRedirect('https://checkout.paystack.com/web');
        Http::assertSent(fn ($r) => str_contains($r->url(), 'initialize') && $r['callback_url'] === route('plans.confirm'));

        $pass = AccessPass::where('user_id', $user->id)->firstOrFail();
        $this->get('/plans/confirm?reference='.$pass->reference)->assertRedirect(route('plans'))->assertSessionHas('status');
        $this->assertSame('paid', $pass->fresh()->status);
        $this->get('/facilities/f-legon')->assertSee('+233200000001');

        // Someone else's reference is not found.
        $this->actingAs(User::factory()->create())->get('/plans/confirm?reference='.$pass->reference)->assertSessionHasErrors('plan');
    }
}
