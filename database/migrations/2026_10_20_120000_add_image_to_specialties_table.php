<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specialties', function (Blueprint $table) {
            // {"path": "cms/…", "w": 1200, "h": 800, "alt": "…"} for an uploaded photo
            $table->json('image')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('specialties', fn (Blueprint $table) => $table->dropColumn('image'));
    }
};
