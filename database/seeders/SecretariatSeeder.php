<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/** Creates the secretariat team account from config('caresenda.secretariat'), keeping its password once it exists. */
class SecretariatSeeder extends Seeder
{
    public function run(): void
    {
        $account = config('caresenda.secretariat');

        if (blank($account['email']) || blank($account['password'])) {
            $this->command?->warn('Secretariat account skipped: set SECRETARIAT_EMAIL and SECRETARIAT_PASSWORD.');

            return;
        }

        $secretariat = User::firstOrCreate(
            ['email' => $account['email']],
            ['name' => $account['name'], 'password' => $account['password']],
        );
        $secretariat->forceFill(['role' => User::SECRETARIAT, 'username' => $account['username']])->save();

        $this->command?->info("Secretariat account ready: {$secretariat->email}");
    }
}
