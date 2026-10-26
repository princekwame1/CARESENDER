<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Plans are set by the team in /admin/payments.
        Schema::create('access_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('description', 255)->nullable();
            $table->string('kind', 20); // searches | unlimited
            $table->unsignedSmallInteger('searches')->nullable();
            // unlimited: how long access lasts; searches: how long unused searches stay valid (null = never expire).
            $table->unsignedInteger('duration_hours')->nullable();
            $table->unsignedInteger('price'); // pesewas
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('access_passes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('access_plan_id')->nullable()->constrained()->nullOnDelete();
            // Copied from the plan at purchase, so later plan edits never change what someone paid for.
            $table->string('plan_name', 80);
            $table->string('kind', 20);
            $table->unsignedSmallInteger('searches_total')->nullable();
            $table->unsignedSmallInteger('searches_left')->nullable();
            $table->unsignedInteger('duration_hours')->nullable();
            $table->unsignedInteger('amount'); // pesewas
            $table->string('currency', 3);
            $table->string('reference', 64)->unique();
            $table->string('status', 20)->default('pending'); // pending | paid | failed
            $table->string('channel', 30)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // One search credit spent: the facilities in that search's results stay open for a while.
        Schema::create('search_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('access_pass_id')->nullable()->constrained()->nullOnDelete();
            $table->string('signature', 64);
            $table->string('query', 120)->nullable();
            $table->json('facility_ids');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
        });

        $now = now();
        $plans = [
            ['name' => 'Full day', 'description' => 'Unlimited searches for 24 hours.', 'kind' => 'unlimited', 'searches' => null, 'duration_hours' => 24, 'price' => 2000, 'sort_order' => 1],
            ['name' => 'Full week', 'description' => 'Unlimited searches for 7 days.', 'kind' => 'unlimited', 'searches' => null, 'duration_hours' => 24 * 7, 'price' => 6000, 'sort_order' => 2],
            ['name' => 'Full month', 'description' => 'Unlimited searches for 30 days.', 'kind' => 'unlimited', 'searches' => null, 'duration_hours' => 24 * 30, 'price' => 15000, 'sort_order' => 3],
            ['name' => '5 searches', 'description' => 'Open the results of 5 searches. Use them within 30 days.', 'kind' => 'searches', 'searches' => 5, 'duration_hours' => 24 * 30, 'price' => 1000, 'sort_order' => 0],
        ];
        DB::table('access_plans')->insert(array_map(fn ($p) => $p + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now], $plans));
    }

    public function down(): void
    {
        Schema::dropIfExists('search_unlocks');
        Schema::dropIfExists('access_passes');
        Schema::dropIfExists('access_plans');
    }
};
