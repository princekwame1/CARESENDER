<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Specialty extends Model
{
    protected $fillable = ['name', 'slug', 'kind', 'diagnostic', 'description', 'image', 'is_active', 'show_on_home', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'show_on_home' => 'boolean', 'image' => 'array'];
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /** Catalogue services grouped under this specialty. */
    public function catalogServices(): HasMany
    {
        return $this->hasMany(CatalogService::class);
    }

    public function scopeClinical(Builder $query): Builder
    {
        return $query->where('kind', 'clinical');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
