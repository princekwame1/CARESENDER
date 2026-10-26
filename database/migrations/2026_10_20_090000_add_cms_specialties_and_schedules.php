<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specialties', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('slug', 140)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_home')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('site_contents', function (Blueprint $table) {
            $table->string('key', 80)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::table('facilities', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('id')->index();
            $table->string('email', 190)->nullable()->after('whatsapp');
            // {"Monday": {"open": "08:00", "close": "17:00"}, ...}; missing days are closed.
            $table->json('opening_hours')->nullable()->after('hours');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('specialty_id')->nullable()->after('type')->constrained()->nullOnDelete();
            // [{"day": "Monday", "start": "09:00", "end": "13:00"}, ...]
            $table->json('slots')->nullable()->after('days');
        });

        // Seed the catalogue from the specialist services already in the directory.
        $names = DB::table('services')->where('type', 'specialist')->distinct()->orderBy('service')->pluck('service');
        $popular = DB::table('services')->where('type', 'specialist')->groupBy('service')->orderByRaw('count(*) desc')->limit(8)->pluck('service')->all();
        $now = now();
        foreach ($names as $i => $name) {
            $id = DB::table('specialties')->insertGetId([
                'name' => $name, 'slug' => Str::slug($name), 'show_on_home' => in_array($name, $popular, true),
                'sort_order' => $i, 'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('services')->where('type', 'specialist')->where('service', $name)->update(['specialty_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropConstrainedForeignId('specialty_id');
            $table->dropColumn('slots');
        });
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn(['is_active', 'email', 'opening_hours']);
        });
        Schema::dropIfExists('site_contents');
        Schema::dropIfExists('specialties');
    }
};
