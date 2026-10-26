<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Facility extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['search_text'];

    /** Distance in km from the requesting user, set by the search layer. */
    public ?float $distanceKm = null;

    protected function casts(): array
    {
        return [
            'diagnostic_categories' => 'array',
            'directory_evidence' => 'array',
            'register_evidence' => 'array',
            'opening_hours' => 'array',
            'is_active' => 'boolean',
            'has_laboratory' => 'boolean',
            'has_imaging' => 'boolean',
            'lat' => 'float',
            'lng' => 'float',
            'verified_at' => 'datetime',
            'collected_at' => 'date',
        ];
    }

    public function locality(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInCity(Builder $query, ?string $city): Builder
    {
        return $city ? $query->where('city', $city) : $query;
    }

    public function directionsUrl(): string
    {
        if ($this->maps_url) {
            return $this->maps_url;
        }

        $target = $this->lat && $this->geo_precision === 'exact'
            ? "{$this->lat},{$this->lng}"
            : "{$this->name} {$this->address} Ghana";

        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($target);
    }

    public function whatsappUrl(): ?string
    {
        return $this->whatsapp ? 'https://wa.me/'.ltrim($this->whatsapp, '+') : null;
    }
}
