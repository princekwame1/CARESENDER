<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/*
| A central catalogue of services, grouped by specialty. Facility listings
| (the services table) now point at a catalogue entry instead of free text.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specialties', function (Blueprint $table) {
            // clinical = a medical specialty (Gynaecologist); diagnostic = a group of tests (Haematology)
            $table->string('kind', 12)->default('clinical')->after('slug')->index();
            $table->string('diagnostic', 12)->nullable()->after('kind'); // laboratory | imaging | other
        });

        Schema::create('catalog_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specialty_id')->constrained()->restrictOnDelete();
            $table->string('name', 190);
            $table->string('type', 20); // specialist | test
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['type', 'name']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('catalog_service_id')->nullable()->after('specialty_id')->constrained()->nullOnDelete();
        });

        $this->backfill();
    }

    private function backfill(): void
    {
        $now = now();
        $group = function (string $name, string $kind, ?string $diagnostic = null) use ($now) {
            $existing = DB::table('specialties')->where('name', $name)->first();
            if ($existing) {
                return $existing->id;
            }

            return DB::table('specialties')->insertGetId([
                'name' => $name, 'slug' => Str::slug($name), 'kind' => $kind, 'diagnostic' => $diagnostic,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        };

        // Specialist services: one catalogue entry per specialty, in that specialty's group.
        foreach (DB::table('specialties')->where('kind', 'clinical')->get() as $sp) {
            $id = DB::table('catalog_services')->insertGetId([
                'specialty_id' => $sp->id, 'name' => $sp->name, 'type' => 'specialist', 'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('services')->where('type', 'specialist')->where('specialty_id', $sp->id)->update(['catalog_service_id' => $id]);
        }

        // Tests: grouped by lab category, or by kind of test when uncategorised.
        $tests = DB::table('services')->where('type', 'test')
            ->select('service', 'lab_category', DB::raw('min(diagnostic_categories) as cats'))
            ->groupBy('service', 'lab_category')->get();
        foreach ($tests as $t) {
            $cat = json_decode($t->cats ?? '[]', true)[0] ?? 'laboratory';
            [$groupName, $diag] = match (true) {
                (bool) $t->lab_category => [$t->lab_category, 'laboratory'],
                $cat === 'imaging' => ['Radiology & imaging', 'imaging'],
                $cat === 'other' => ['Other diagnostics', 'other'],
                default => ['General laboratory tests', 'laboratory'],
            };
            $groupId = $group($groupName, 'diagnostic', $diag);
            $itemId = DB::table('catalog_services')->where('type', 'test')->where('name', $t->service)->value('id')
                ?? DB::table('catalog_services')->insertGetId([
                    'specialty_id' => $groupId, 'name' => $t->service, 'type' => 'test', 'created_at' => $now, 'updated_at' => $now,
                ]);
            DB::table('services')->where('type', 'test')->where('service', $t->service)
                ->update(['catalog_service_id' => $itemId, 'specialty_id' => $groupId, 'lab_category' => $groupName]);
        }
    }

    public function down(): void
    {
        Schema::table('services', fn (Blueprint $table) => $table->dropConstrainedForeignId('catalog_service_id'));
        Schema::dropIfExists('catalog_services');
        Schema::table('specialties', function (Blueprint $table) {
            $table->dropIndex(['kind']);
            $table->dropColumn(['kind', 'diagnostic']);
        });
    }
};
