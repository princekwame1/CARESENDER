<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('access_passes', function (Blueprint $table) {
            // Paystack's charge, added to the payer's bill on top of `amount` (pesewas).
            $table->unsignedInteger('fee')->default(0)->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('access_passes', fn (Blueprint $table) => $table->dropColumn('fee'));
    }
};
