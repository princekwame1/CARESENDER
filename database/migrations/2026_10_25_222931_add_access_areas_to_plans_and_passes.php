<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Which parts of the directory a plan opens (facilities, specialists, tests). Null means all of them. */
    public function up(): void
    {
        Schema::table('access_plans', function (Blueprint $table) {
            $table->json('areas')->nullable()->after('kind');
        });

        Schema::table('access_passes', function (Blueprint $table) {
            $table->json('areas')->nullable()->after('kind');
        });

        Schema::table('search_unlocks', function (Blueprint $table) {
            $table->json('areas')->nullable()->after('facility_ids');
        });
    }

    public function down(): void
    {
        Schema::table('search_unlocks', fn (Blueprint $table) => $table->dropColumn('areas'));
        Schema::table('access_passes', fn (Blueprint $table) => $table->dropColumn('areas'));
        Schema::table('access_plans', fn (Blueprint $table) => $table->dropColumn('areas'));
    }
};
