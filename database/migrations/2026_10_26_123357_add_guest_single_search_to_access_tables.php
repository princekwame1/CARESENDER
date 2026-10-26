<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Single searches people can buy without an account. A guest is known by a browser cookie
     * (guest_token); the search they paid for is kept on the pass and opened once Paystack confirms.
     */
    public function up(): void
    {
        Schema::table('access_plans', function (Blueprint $table) {
            $table->boolean('guest_checkout')->default(false)->after('searches');
        });

        Schema::table('access_passes', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('guest_token', 64)->nullable()->after('user_id')->index();
            $table->string('email')->nullable()->after('guest_token');
            $table->json('search')->nullable()->after('searches_left');
        });

        Schema::table('search_unlocks', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('guest_token', 64)->nullable()->after('user_id');
            $table->index(['guest_token', 'expires_at']);
        });

        $now = now();
        DB::table('access_plans')->insert([
            'name' => 'Single search', 'description' => 'Open the results of one search. No account needed.',
            'kind' => 'searches', 'searches' => 1, 'guest_checkout' => true, 'price' => 500,
            'is_active' => true, 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('access_plans')->where('guest_checkout', true)->delete();
        DB::table('search_unlocks')->whereNull('user_id')->delete();
        DB::table('access_passes')->whereNull('user_id')->delete();

        Schema::table('search_unlocks', function (Blueprint $table) {
            $table->dropIndex(['guest_token', 'expires_at']);
            $table->dropColumn('guest_token');
            $table->foreignId('user_id')->nullable(false)->change();
        });

        Schema::table('access_passes', function (Blueprint $table) {
            $table->dropIndex(['guest_token']);
            $table->dropColumn(['guest_token', 'email', 'search']);
            $table->foreignId('user_id')->nullable(false)->change();
        });

        Schema::table('access_plans', fn (Blueprint $table) => $table->dropColumn('guest_checkout'));
    }
};
