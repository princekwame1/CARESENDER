<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How long a plan lasts as a calendar period (hour, day, week, month, year) times a count,
     * so a month runs to the same date next month. duration_hours stays as an approximation.
     */
    public function up(): void
    {
        foreach (['access_plans', 'access_passes'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('period', 10)->nullable()->after('kind');
                $table->unsignedInteger('period_count')->nullable()->after('period');
            });

            DB::table($table)->whereNotNull('duration_hours')->orderBy('id')->each(function ($row) use ($table) {
                DB::table($table)->where('id', $row->id)->update(self::periodFor((int) $row->duration_hours, $row->kind));
            });
        }
    }

    public function down(): void
    {
        foreach (['access_plans', 'access_passes'] as $table) {
            Schema::table($table, fn (Blueprint $table) => $table->dropColumn(['period', 'period_count']));
        }
    }

    /** @return array{period: string, period_count: int} */
    private static function periodFor(int $hours, string $kind): array
    {
        // Search packs keep their validity in days; time plans use the largest whole unit.
        $units = $kind === 'searches' ? ['day' => 24] : ['year' => 8760, 'month' => 720, 'week' => 168, 'day' => 24];
        foreach ($units as $period => $size) {
            if ($hours % $size === 0) {
                return ['period' => $period, 'period_count' => intdiv($hours, $size)];
            }
        }

        return ['period' => 'hour', 'period_count' => $hours];
    }
};
