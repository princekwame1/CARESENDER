<?php

namespace Tests\Feature;

use App\Mail\ContactReply;
use App\Models\ContactMessage;
use App\Models\Facility;
use App\Models\Specialty;
use App\Models\User;
use Database\Seeders\SecretariatSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class TeamAccessTest extends DirectoryTestCase
{
    private function withRole(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();

        return $user;
    }

    private function message(string $contact): ContactMessage
    {
        return ContactMessage::create(['name' => 'Ama', 'contact' => $contact, 'topic' => 'wrong-info', 'message' => 'The lab now opens on Saturdays.']);
    }

    public function test_the_secretariat_sees_only_the_directory_and_messages(): void
    {
        $this->actingAs($this->withRole(User::SECRETARIAT));

        $this->get('/admin')->assertRedirect(route('admin.facilities.index'));
        $this->get('/admin/facilities')->assertOk()
            ->assertSee(route('admin.catalog.index'))->assertSee(route('admin.specialties.index'))->assertSee(route('admin.messages.index'))
            ->assertDontSee(route('admin.payments.index'))->assertDontSee(route('admin.settings.edit'))->assertDontSee(route('admin.team.index'));
        $this->get('/admin/facilities/f-legon/edit')->assertOk()->assertDontSee('and everything listed under it?');

        foreach (['/admin/payments', '/admin/plans', '/admin/settings', '/admin/content', '/admin/export', '/admin/api', '/admin/team'] as $page) {
            $this->get($page)->assertForbidden();
        }
        $this->delete('/admin/facilities/f-legon')->assertForbidden();
        $this->assertDatabaseHas('facilities', ['id' => 'f-legon']);
    }

    public function test_the_secretariat_updates_facilities_and_specialists(): void
    {
        $this->actingAs($this->withRole(User::SECRETARIAT));
        $specialty = Specialty::where('kind', 'clinical')->firstOrFail();

        $this->patch('/admin/facilities/f-legon/toggle')->assertRedirect();
        $this->put("/admin/specialties/{$specialty->id}", ['name' => 'Obstetrics and gynaecology', 'kind' => 'clinical'])->assertSessionHasNoErrors();

        $this->assertFalse(Facility::findOrFail('f-legon')->is_active);
        $this->assertSame('Obstetrics and gynaecology', $specialty->fresh()->name);
        $this->get('/facilities/f-legon')->assertOk(); // the team can still preview hidden facilities
    }

    public function test_members_cannot_use_the_team_dashboard(): void
    {
        $this->actingAs($this->withRole(User::MEMBER));

        $this->get('/admin/facilities')->assertForbidden();
        $this->post('/admin/messages/'.$this->message('ama@example.test')->id.'/reply', ['reply' => 'Hi'])->assertForbidden();
    }

    public function test_a_reply_to_an_email_address_is_emailed_and_recorded(): void
    {
        Mail::fake();
        $secretariat = $this->withRole(User::SECRETARIAT);
        $message = $this->message('ama@example.test');

        $this->actingAs($secretariat)->post("/admin/messages/{$message->id}/reply", ['reply' => ''])
            ->assertSessionHasErrors(['reply' => 'Write your reply first.']);
        Mail::assertNothingSent();

        $this->post("/admin/messages/{$message->id}/reply", ['reply' => 'Thanks, we have updated the opening hours.'])
            ->assertSessionHas('status', 'Reply sent to ama@example.test.');

        Mail::assertSent(ContactReply::class, fn (ContactReply $mail) => $mail->hasTo('ama@example.test') && $mail->contactMessage->is($message));
        $message->refresh();
        $this->assertSame('Thanks, we have updated the opening hours.', $message->reply);
        $this->assertSame($secretariat->id, $message->replied_by);
        $this->assertNotNull($message->replied_at);
        $this->assertNotNull($message->read_at);
        $this->get('/admin/messages?status=answered')->assertSee('Replied')->assertSee('Thanks, we have updated the opening hours.');
    }

    public function test_a_reply_to_a_phone_number_is_saved_to_send_on_whatsapp(): void
    {
        Mail::fake();
        $message = $this->message('024 000 0000');

        $this->actingAs($this->withRole(User::SECRETARIAT))->post("/admin/messages/{$message->id}/reply", ['reply' => 'Thanks Ama'])
            ->assertSessionHas('status', 'Reply saved. Open WhatsApp from the message to send it.');

        Mail::assertNothingSent();
        $this->assertNotNull($message->fresh()->replied_at);
        $this->get('/admin/messages')->assertSee('https://wa.me/233240000000?text=Thanks%20Ama', false);
    }

    public function test_the_reply_email_shows_the_reply_and_the_original_message(): void
    {
        $message = $this->message('ama@example.test');
        $message->reply = 'We have updated the hours.';

        (new ContactReply($message))
            ->assertSeeInText('Hello Ama')
            ->assertSeeInText('We have updated the hours.')
            ->assertSeeInText('The lab now opens on Saturdays.');
    }

    public function test_admins_add_change_and_remove_team_members(): void
    {
        $admin = $this->withRole(User::ADMIN);
        $member = $this->withRole(User::MEMBER);
        $this->actingAs($admin)->get('/admin/team')->assertOk()->assertSee($admin->name);

        $this->post('/admin/team', ['name' => 'Kofi', 'email' => 'kofi@example.test', 'role' => User::SECRETARIAT])
            ->assertSessionHasErrors(['password' => 'Choose a password for this new account (at least 8 characters).']);
        $this->post('/admin/team', ['name' => 'Kofi', 'email' => 'kofi@example.test', 'role' => User::SECRETARIAT, 'password' => 'secret123'])
            ->assertSessionHasNoErrors();
        $kofi = User::where('email', 'kofi@example.test')->firstOrFail();
        $this->assertTrue($kofi->isSecretariat());

        // An existing member account gets the role and keeps its password.
        $this->post('/admin/team', ['name' => $member->name, 'email' => $member->email, 'role' => User::SECRETARIAT])->assertSessionHasNoErrors();
        $this->assertTrue($member->fresh()->isSecretariat());
        $this->post('/admin/team', ['name' => 'Again', 'email' => $member->email, 'role' => User::ADMIN])
            ->assertSessionHasErrors(['email' => "{$member->name} is already on the team."]);

        $this->put("/admin/team/{$kofi->id}", ['role' => User::ADMIN])->assertSessionHasNoErrors();
        $this->assertTrue($kofi->fresh()->isAdmin());
        $this->put("/admin/team/{$admin->id}", ['role' => User::SECRETARIAT])->assertSessionHasErrors(['role' => 'You can’t change your own role.']);
        $this->delete("/admin/team/{$admin->id}")->assertSessionHasErrors('role');
        $this->assertTrue($admin->fresh()->isAdmin());

        $this->delete("/admin/team/{$kofi->id}")->assertSessionHasNoErrors();
        $this->assertSame(User::MEMBER, $kofi->fresh()->role);
        $this->put("/admin/team/{$kofi->id}", ['role' => User::ADMIN])->assertNotFound();
    }

    public function test_admins_create_member_accounts(): void
    {
        $this->actingAs($this->withRole(User::SECRETARIAT))->get('/admin/users')->assertForbidden();
        $this->actingAs($this->withRole(User::ADMIN));

        $this->post('/admin/users', ['name' => 'Ama', 'email' => 'ama@example.test', 'phone' => '024 123 4567'])
            ->assertSessionHasErrors('password');
        $this->post('/admin/users', ['name' => 'Ama', 'email' => 'ama@example.test', 'phone' => '024 123 4567', 'password' => 'secret123'])
            ->assertSessionHasNoErrors();

        $ama = User::where('email', 'ama@example.test')->firstOrFail();
        $this->assertSame(User::MEMBER, $ama->role);
        $this->assertSame('+233241234567', $ama->phone);
        $this->get('/admin/users?q=ama')->assertOk()->assertSee('ama@example.test');
        $this->post('/admin/users', ['name' => 'Ama again', 'email' => 'ama@example.test', 'password' => 'secret123'])
            ->assertSessionHasErrors('email');

        auth()->logout();
        $this->post('/login', ['login' => 'ama@example.test', 'password' => 'secret123'])->assertRedirect();
        $this->assertTrue(auth()->user()->is($ama));
    }

    public function test_the_secretariat_seeder_creates_the_account_once_configured(): void
    {
        config(['caresenda.secretariat.email' => null, 'caresenda.secretariat.password' => null]);
        $this->seed(SecretariatSeeder::class);
        $this->assertSame(0, User::staff()->count());

        config(['caresenda.secretariat.email' => 'desk@example.test', 'caresenda.secretariat.password' => 'first-secret']);
        $this->seed(SecretariatSeeder::class);
        $this->post('/login', ['login' => 'secretariat', 'password' => 'first-secret'])->assertRedirect();
        $this->assertTrue(auth()->user()->isSecretariat());

        // Re-seeding on a later deploy keeps a password the team has since changed.
        config(['caresenda.secretariat.password' => 'second-secret']);
        $this->seed(SecretariatSeeder::class);
        $this->assertSame(1, User::where('email', 'desk@example.test')->count());
        $this->assertTrue(Hash::check('first-secret', User::where('email', 'desk@example.test')->value('password')));
    }
}
