<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** An entry in the central service list, e.g. "Full blood count" in the Haematology group. */
class CatalogService extends Model
{
    protected $fillable = ['specialty_id', 'name', 'type', 'description', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** The group this service belongs to. */
    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    /** Facility listings offering this service. */
    public function listings(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
