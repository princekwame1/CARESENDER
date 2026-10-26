<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('member')->after('email')->index();
            $table->string('phone', 30)->nullable()->after('role');
        });

        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('city', 40)->index();
            $table->decimal('lat', 9, 6);
            $table->decimal('lng', 9, 6);
            $table->index(['lat', 'lng']);
        });

        Schema::create('facilities', function (Blueprint $table) {
            $table->string('id', 40)->primary();
            $table->string('name');
            $table->string('city', 40)->index();
            $table->string('area')->nullable();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->string('district')->nullable();
            $table->string('address')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('whatsapp', 40)->nullable();
            $table->string('facility_type', 80)->nullable()->index();
            $table->string('ownership', 40)->nullable();
            $table->json('diagnostic_categories')->nullable();
            $table->boolean('has_laboratory')->default(false)->index();
            $table->boolean('has_imaging')->default(false)->index();
            $table->decimal('lat', 9, 6)->nullable();
            $table->decimal('lng', 9, 6)->nullable();
            $table->string('geo_precision', 10)->default('city');
            $table->string('hours')->nullable();
            $table->text('issue')->nullable();
            $table->string('source', 500)->nullable();
            $table->string('contact_source', 500)->nullable();
            $table->json('directory_evidence')->nullable();
            $table->json('register_evidence')->nullable();
            $table->unsignedInteger('services_count')->default(0);
            $table->text('search_text');
            $table->timestamp('verified_at')->nullable();
            $table->date('collected_at')->nullable();
            $table->timestamps();
            $table->index(['lat', 'lng']);
        });

        Schema::create('services', function (Blueprint $table) {
            $table->string('id', 80)->primary();
            $table->string('facility_id', 40);
            $table->foreign('facility_id')->references('id')->on('facilities')->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('service');
            $table->string('specialist_name')->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('lab_category', 80)->nullable();
            $table->text('panel_details')->nullable();
            $table->string('schedule')->nullable();
            $table->json('days')->nullable();
            $table->string('price', 80)->nullable();
            $table->json('diagnostic_categories')->nullable();
            $table->boolean('available')->default(true);
            $table->boolean('manager_confirmed')->default(false)->index();
            $table->date('confirmation_reported_at')->nullable();
            $table->string('service_evidence', 40)->nullable();
            $table->text('service_note')->nullable();
            $table->string('source', 500)->nullable();
            $table->text('search_text');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['type', 'available']);
            $table->index('facility_id');
        });

        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('query')->nullable();
            $table->string('type', 20)->default('all');
            $table->string('city', 40)->nullable();
            $table->string('area')->nullable();
            $table->decimal('lat', 9, 6)->nullable();
            $table->decimal('lng', 9, 6)->nullable();
            $table->unsignedInteger('results_count')->default(0);
            $table->string('channel', 10)->default('web');
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_logs');
        Schema::dropIfExists('services');
        Schema::dropIfExists('facilities');
        Schema::dropIfExists('areas');
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'phone']);
        });
    }
};
