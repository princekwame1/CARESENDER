<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\DirectoryImporter;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(DirectoryImporter $importer): void
    {
        $admin = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@caresenda.test')],
            ['name' => 'CareSenda Admin', 'password' => env('ADMIN_PASSWORD', 'password')],
        );
        $admin->forceFill(['role' => 'admin', 'username' => env('ADMIN_USERNAME', 'admin')])->save();

        $this->call(SecretariatSeeder::class);

        $result = $importer->import(config('caresenda.dataset'));
        $this->command?->info("Imported {$result['facilities']} facilities and {$result['services']} services.");
    }
}
