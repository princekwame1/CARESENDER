<?php

namespace Tests\Feature\Api;

use App\Models\Service;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\DirectoryTestCase;

class AuthApiTest extends DirectoryTestCase
{
    public function test_register_login_me_logout(): void
    {
        $token = $this->postJson('/api/v1/auth/register', [
            'name' => 'Ama', 'email' => 'ama@example.com', 'password' => 'secret123', 'device_name' => 'pixel',
        ])->assertCreated()->assertJsonPath('data.role', 'member')->json('token');

        $this->withToken($token)->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.email', 'ama@example.com');

        $login = $this->postJson('/api/v1/auth/login', ['email' => 'ama@example.com', 'password' => 'secret123', 'device_name' => 'iphone'])
            ->assertOk()->json('token');
        $this->withToken($login)->postJson('/api/v1/auth/logout')->assertOk();
        $this->assertSame(1, User::first()->tokens()->count());
    }

    public function test_role_cannot_be_mass_assigned_on_register(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Eve', 'email' => 'eve@example.com', 'password' => 'secret123', 'device_name' => 'x', 'role' => 'admin',
        ])->assertCreated()->assertJsonPath('data.role', 'member');
    }

    public function test_register_saves_the_phone_with_its_country_code(): void
    {
        $base = ['name' => 'Kofi', 'password' => 'secret123', 'device_name' => 'pixel'];
        $this->postJson('/api/v1/auth/register', $base + ['email' => 'k1@example.com', 'phone_country' => 'NG', 'phone' => '0803 123 4567'])
            ->assertCreated()->assertJsonPath('data.phone', '+2348031234567');
        // Older app versions send no country: Ghana.
        $this->postJson('/api/v1/auth/register', $base + ['email' => 'k2@example.com', 'phone' => '024 000 0000'])
            ->assertCreated()->assertJsonPath('data.phone', '+233240000000');
        $this->postJson('/api/v1/auth/register', $base + ['email' => 'k3@example.com', 'phone' => '12'])->assertUnprocessable()->assertJsonValidationErrors('phone');

        $this->getJson('/api/v1/meta')->assertJsonPath('data.phone_countries.0', ['code' => 'GH', 'name' => 'Ghana', 'dial_code' => '+233']);
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->create(['email' => 'kofi@example.com']);
        $this->postJson('/api/v1/auth/login', ['email' => 'kofi@example.com', 'password' => 'nope', 'device_name' => 'x'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_searches_are_saved_to_history_when_signed_in(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/services?q=ultrasound&lat='.self::LAT.'&lng='.self::LNG)->assertOk();

        $this->getJson('/api/v1/me/searches')
            ->assertOk()
            ->assertJsonPath('data.0.query', 'ultrasound')
            ->assertJsonPath('data.0.area', 'East Legon');

        $this->deleteJson('/api/v1/me/searches')->assertOk();
        $this->getJson('/api/v1/me/searches')->assertJsonCount(0, 'data');
    }

    public function test_admin_endpoints_require_admin_role(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/v1/admin/stats')->assertForbidden();
        $this->patchJson('/api/v1/admin/facilities/f-legon', ['hours' => 'x'])->assertForbidden();
    }

    public function test_admin_records_a_verification(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        Sanctum::actingAs($admin);

        $this->patchJson('/api/v1/admin/facilities/f-legon', [
            'issue' => 'Lab closed for maintenance on Friday',
            'services' => [
                's-gyn-legon' => ['price' => 'GHS 250', 'days' => ['Monday', 'Thursday'], 'schedule' => 'Mon & Thu 09:00–13:00'],
                's-fbc-legon' => ['available' => false],
            ],
        ])->assertOk()->assertJsonPath('data.service_notice', 'Lab closed for maintenance on Friday');

        $gyn = Service::find('s-gyn-legon');
        $this->assertSame('GHS 250', $gyn->price);
        $this->assertSame('verified', $gyn->verificationStatus()['code']);

        // Unavailable services disappear from search; updated days are searchable.
        $this->getJson('/api/v1/services?q=fbc')->assertJsonPath('meta.total', 0);
        $this->getJson('/api/v1/services?day=Thursday')->assertJsonPath('data.0.id', 's-gyn-legon');
    }

    public function test_admin_update_validates_days(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        Sanctum::actingAs($admin);

        $this->patchJson('/api/v1/admin/facilities/f-legon', ['services' => ['s-gyn-legon' => ['days' => ['Funday']]]])
            ->assertUnprocessable();
    }
}
