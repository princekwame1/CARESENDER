<?php

namespace App\Models;

use App\Support\PaymentSettings;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/** A plan the team sells: pay per search (a pack of searches), or unlimited access per hour, day, week, month or year. */
#[Fillable(['name', 'description', 'kind', 'period', 'period_count', 'areas', 'searches', 'duration_hours', 'price', 'is_active', 'sort_order'])]
class AccessPlan extends Model
{
    public const SEARCHES = 'searches';

    public const UNLIMITED = 'unlimited';

    /** How a plan is sold, as chosen in /admin/plans. */
    public const BILLING = [
        'search' => 'Per search',
        'hour' => 'Per hour',
        'day' => 'Per day',
        'week' => 'Per week',
        'month' => 'Per month',
        'year' => 'Per year',
    ];

    /** Approximate hours in each period, for duration_hours (kept for older app versions). */
    public const PERIOD_HOURS = ['hour' => 1, 'day' => 24, 'week' => 168, 'month' => 720, 'year' => 8760];

    /** Parts of the directory a plan can open. Search counts are always public. */
    public const AREAS = [
        'facilities' => 'Facility details',
        'specialists' => 'Specialists',
        'tests' => 'Diagnostic tests',
    ];

    protected $attributes = ['is_active' => true, 'sort_order' => 0, 'description' => null, 'areas' => null, 'searches' => null, 'duration_hours' => null, 'period' => null, 'period_count' => null];

    protected function casts(): array
    {
        return [
            'areas' => 'array',
            'searches' => 'integer',
            'period_count' => 'integer',
            'duration_hours' => 'integer',
            'price' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeAvailable(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('price');
    }

    public function isUnlimited(): bool
    {
        return $this->kind === self::UNLIMITED;
    }

    /** "search", or the period unlimited access is sold by ("hour" … "year"). */
    public function billing(): string
    {
        return $this->isUnlimited() ? ($this->period ?? 'hour') : 'search';
    }

    /** "Per search", "5 searches", "Per month", "Every 3 months". */
    public function billingLabel(): string
    {
        if (! $this->isUnlimited()) {
            return $this->searches === 1 ? 'Per search' : $this->searches.' searches';
        }
        $count = $this->period_count ?? 1;

        return $count === 1 ? self::BILLING[$this->billing()] : 'Every '.self::periodLabel($this->billing(), $count);
    }

    /** How long the plan lasts: "1 month", "3 years", "30 days"; null when it never runs out. */
    public function lengthLabel(): ?string
    {
        return self::periodLabel($this->period, $this->period_count) ?? self::durationLabel($this->duration_hours);
    }

    /** "1 hour", "3 months". */
    public static function periodLabel(?string $period, ?int $count): ?string
    {
        return $period && $count ? $count.' '.str($period)->plural($count) : null;
    }

    /** When access bought at $start ends. Months and years follow the calendar (15 March → 15 April). */
    public static function endsAt(Carbon $start, ?string $period, ?int $count, ?int $hours): ?Carbon
    {
        $start = $start->copy();

        return match (true) {
            $period === 'hour' && $count > 0 => $start->addHours($count),
            $period === 'day' && $count > 0 => $start->addDays($count),
            $period === 'week' && $count > 0 => $start->addWeeks($count),
            $period === 'month' && $count > 0 => $start->addMonthsNoOverflow($count),
            $period === 'year' && $count > 0 => $start->addYearsNoOverflow($count),
            (bool) $hours => $start->addHours($hours),
            default => null,
        };
    }

    /**
     * Areas a plan or pass opens, in display order. Null (saved before areas existed) opens everything.
     *
     * @param  ?list<string>  $areas
     * @return list<string>
     */
    public static function normalizeAreas(?array $areas): array
    {
        return $areas ? array_values(array_intersect(array_keys(self::AREAS), $areas)) : array_keys(self::AREAS);
    }

    /** @return list<string> */
    public function coveredAreas(): array
    {
        return self::normalizeAreas($this->areas);
    }

    /** "Facility details, Specialists", or "Everything" when all areas are open. */
    public static function areasLabel(?array $areas): string
    {
        $areas = self::normalizeAreas($areas);

        return count($areas) === count(self::AREAS) ? 'Everything' : implode(', ', array_map(fn ($area) => self::AREAS[$area], $areas));
    }

    /** "24 hours", "7 days", "30 days". */
    public static function durationLabel(?int $hours): ?string
    {
        if (! $hours) {
            return null;
        }

        return $hours % 24 === 0
            ? ($hours / 24).' '.str('day')->plural($hours / 24)
            : $hours.' '.str('hour')->plural($hours);
    }

    /** Paystack's fee the payer covers on top of the price, in pesewas. */
    public function fee(): int
    {
        return PaymentSettings::feeFor($this->price);
    }

    /** What the payer is charged at checkout, in pesewas. */
    public function total(): int
    {
        return $this->price + $this->fee();
    }

    public function toApi(string $currency): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'kind' => $this->kind,
            'billing' => $this->billing(),
            'billing_label' => $this->billingLabel(),
            'period' => $this->period,
            'period_count' => $this->period_count,
            'areas' => $this->coveredAreas(),
            'searches' => $this->isUnlimited() ? null : $this->searches,
            'duration_hours' => $this->duration_hours,
            'duration_label' => $this->lengthLabel(),
            'price' => $this->price / 100,
            'fee' => $this->fee() / 100,
            'total' => $this->total() / 100,
            'currency' => $currency,
        ];
    }
}
