<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A search a user (or a guest, by guest_token) spent a credit on: its result facilities stay open (for the pass's areas) until expires_at. */
#[Fillable(['user_id', 'guest_token', 'access_pass_id', 'signature', 'query', 'facility_ids', 'areas', 'expires_at'])]
class SearchUnlock extends Model
{
    protected function casts(): array
    {
        return [
            'facility_ids' => 'array',
            'areas' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Not expired, and its plan hasn't ended: a search opened with a search pack closes once the pack's time is up or its
     * last search is used. A single search bought for one search stays open until it expires.
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('expires_at', '>', now())->where(fn ($query) => $query->whereNull('access_pass_id')
            ->orWhereHas('accessPass', fn ($pass) => $pass->active()->where(fn ($pass) => $pass->where('kind', '!=', AccessPlan::SEARCHES)
                ->orWhere('searches_total', '<=', 1)->orWhere('searches_left', '>', 0))));
    }

    public function accessPass(): BelongsTo
    {
        return $this->belongsTo(AccessPass::class);
    }

    /** @return list<string> */
    public function coveredAreas(): array
    {
        return AccessPlan::normalizeAreas($this->areas);
    }
}
