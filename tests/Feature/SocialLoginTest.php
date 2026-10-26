<?php

namespace Tests\Feature;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as ProviderUser;
use Tests\TestCase;

class SocialLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.client_id' => 'google-id', 'services.microsoft.client_id' => 'microsoft-id', 'services.apple.client_id' => 'apple-id',
            'services.apple.team_id' => 'TEAM', 'services.apple.key_id' => 'KEY', 'services.apple.private_key' => 'key']);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private function providerUser(string $id, string $email, array $raw = []): ProviderUser
    {
        return (new ProviderUser)->setRaw($raw)->map(['id' => $id, 'name' => 'Ama Mensah', 'email' => $email]);
    }

    public function test_only_configured_providers_are_offered(): void
    {
        $this->get('/login')->assertOk()->assertSee('Continue with Google')->assertSee('Continue with Apple')->assertDontSee('Continue with Facebook');
        $this->get('/auth/facebook/redirect')->assertNotFound();
        $this->get('/auth/twitter/redirect')->assertNotFound();
    }

    public function test_new_member_signs_up_with_google(): void
    {
        Socialite::fake('google', $this->providerUser('g-1', 'ama@example.com', ['email_verified' => true]));

        $this->get('/auth/google/callback')->assertRedirect(route('plans'));

        $user = User::firstWhere('email', 'ama@example.com');
        $this->assertAuthenticatedAs($user);
        $this->assertSame('Ama Mensah', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('social_accounts', ['user_id' => $user->id, 'provider' => 'google', 'provider_user_id' => 'g-1']);
    }

    public function test_linked_account_signs_back_in_even_if_email_changed(): void
    {
        $account = SocialAccount::factory()->create(['provider' => 'microsoft', 'provider_user_id' => 'm-1']);
        Socialite::fake('microsoft', $this->providerUser('m-1', 'new-address@example.com'));

        $this->get('/auth/microsoft/callback')->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($account->user);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_verified_email_links_to_existing_member(): void
    {
        $user = User::factory()->create(['email' => 'ama@example.com']);
        Socialite::fake('google', $this->providerUser('g-1', 'ama@example.com', ['email_verified' => true]));

        $this->get('/auth/google/callback')->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('social_accounts', ['user_id' => $user->id, 'provider' => 'google']);
    }

    public function test_unverified_email_does_not_take_over_existing_member(): void
    {
        User::factory()->create(['email' => 'ama@example.com']);
        Socialite::fake('microsoft', $this->providerUser('m-1', 'ama@example.com'));
        Socialite::fake('google', $this->providerUser('g-1', 'ama@example.com', ['email_verified' => false]));

        $this->get('/auth/microsoft/callback')->assertRedirect(route('login'))->assertSessionHas('error');
        $this->get('/auth/google/callback')->assertRedirect(route('login'))->assertSessionHas('error');

        $this->assertGuest();
        $this->assertDatabaseCount('social_accounts', 0);
    }

    public function test_team_accounts_cannot_be_linked(): void
    {
        $admin = User::factory()->create(['email' => 'boss@example.com']);
        $admin->forceFill(['role' => 'admin'])->save();
        Socialite::fake('google', $this->providerUser('g-1', 'boss@example.com', ['email_verified' => true]));

        $this->get('/auth/google/callback')->assertRedirect(route('login'))->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_cancelled_sign_in_returns_to_login(): void
    {
        $this->get('/auth/google/callback?error=access_denied')->assertRedirect(route('login'))->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_app_sign_in_hands_a_one_time_code_back_to_the_app(): void
    {
        $verifier = str_repeat('v', 64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        Socialite::fake('google', $this->providerUser('g-1', 'ama@example.com', ['email_verified' => true]));

        $this->get('/auth/google/redirect?app_challenge='.$challenge)->assertRedirect();
        $location = $this->get('/auth/google/callback')->assertRedirect()->headers->get('Location');

        $this->assertStringStartsWith('caresenda://auth?code=', $location);
        $this->assertGuest();
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->postJson('/api/v1/auth/social', ['code' => $query['code'], 'code_verifier' => str_repeat('x', 64), 'device_name' => 'Pixel'])
            ->assertJsonValidationErrors('code');
        $this->get('/auth/google/redirect?app_challenge='.$challenge);
        parse_str((string) parse_url($this->get('/auth/google/callback')->headers->get('Location'), PHP_URL_QUERY), $query);

        $this->postJson('/api/v1/auth/social', ['code' => $query['code'], 'code_verifier' => $verifier, 'device_name' => 'Pixel'])
            ->assertOk()
            ->assertJsonPath('data.email', 'ama@example.com')
            ->assertJsonStructure(['token']);
        // Each code works once.
        $this->postJson('/api/v1/auth/social', ['code' => $query['code'], 'code_verifier' => $verifier, 'device_name' => 'Pixel'])
            ->assertJsonValidationErrors('code');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_app_sign_in_problems_go_back_to_the_app(): void
    {
        $this->get('/auth/google/redirect?app_challenge='.str_repeat('c', 43));

        $this->get('/auth/google/callback?error=access_denied')
            ->assertRedirect('caresenda://auth?'.http_build_query(['error' => 'Google sign-in was cancelled.']));
    }

    public function test_meta_lists_the_providers_for_the_app(): void
    {
        $this->getJson('/api/v1/meta')->assertOk()->assertJsonPath('data.social_providers', [
            ['id' => 'google', 'label' => 'Google'],
            ['id' => 'apple', 'label' => 'Apple'],
            ['id' => 'microsoft', 'label' => 'Microsoft'],
        ]);
    }

    public function test_apple_form_post_is_turned_into_a_get(): void
    {
        $this->post('/auth/apple/callback', ['code' => 'abc', 'state' => 'xyz', 'user' => '{"name":{"firstName":"Ama"}}'])
            ->assertStatus(303)
            ->assertRedirect(route('social.callback', ['provider' => 'apple', 'code' => 'abc', 'state' => 'xyz', 'user' => '{"name":{"firstName":"Ama"}}']));
    }
}
