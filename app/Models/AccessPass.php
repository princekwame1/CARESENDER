<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One purchase of an access plan. Plan details are copied in so later plan edits don't change it.
 * Guests (no account) are known by guest_token; `search` holds the search a single-search purchase opens.
 */
#[Fillable(['user_id', 'guest_token', 'email', 'access_plan_id', 'plan_name', 'kind', 'period', 'period_count', 'areas', 'searches_total', 'searches_left', 'search', 'duration_hours', 'amount', 'fee', 'currency', 'reference', 'status', 'channel', 'paid_at', 'expires_at'])]
class AccessPass extends Model
{
    protected $attributes = ['status' => 'pending', 'channel' => null, 'paid_at' => null, 'expires_at' => null, 'fee' => 0, 'areas' => null, 'period' => null, 'period_count' => null];

    protected function casts(): array
    {
        return [
            'areas' => 'array',
            'period_count' => 'integer',
            'searches_total' => 'integer',
            'searches_left' => 'integer',
            'search' => 'array',
            'duration_hours' => 'integer',
            'amount' => 'integer',
            'fee' => 'integer',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Paid and not yet expired. */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'paid')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /**
     * A plan still in use: active, with searches left if it is a search pack. Single searches (bought for one search)
     * are not plans, so buying a plan never replaces them.
     */
    public function scopeRunning(Builder $query): void
    {
        $query->active()->where(fn ($q) => $q->whereNull('searches_total')->orWhere('searches_total', '>', 1))
            ->where(fn ($q) => $q->where('kind', '!=', AccessPlan::SEARCHES)->orWhere('searches_left', '>', 0));
    }

    /** Whether this is a single search bought for one search rather than a plan. */
    public function isSingleSearch(): bool
    {
        return ! $this->isUnlimited() && $this->searches_total === 1;
    }

    /** What the payer is charged: the plan price plus Paystack's fee, in pesewas. */
    public function total(): int
    {
        return $this->amount + $this->fee;
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isGuest(): bool
    {
        return $this->user_id === null;
    }

    public function isUnlimited(): bool
    {
        return $this->kind === AccessPlan::UNLIMITED;
    }

    /** @return list<string> */
    public function coveredAreas(): array
    {
        return AccessPlan::normalizeAreas($this->areas);
    }

    public function toApi(): array
    {
        return [
            'reference' => $this->reference,
            'plan_id' => $this->access_plan_id,
            'plan_name' => $this->plan_name,
            'kind' => $this->kind,
            'areas' => $this->coveredAreas(),
            'searches_total' => $this->searches_total,
            'searches_left' => $this->searches_left,
            'amount' => $this->amount / 100,
            'fee' => $this->fee / 100,
            'total' => $this->total() / 100,
            'currency' => $this->currency,
            'status' => $this->status,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
        ];
    }
}
