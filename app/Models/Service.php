<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['search_text'];

    public ?float $distanceKm = null;

    protected function casts(): array
    {
        return [
            'days' => 'array',
            'slots' => 'array',
            'diagnostic_categories' => 'array',
            'available' => 'boolean',
            'manager_confirmed' => 'boolean',
            'confirmation_reported_at' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function catalogService(): BelongsTo
    {
        return $this->belongsTo(CatalogService::class);
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /** Human-readable verification status shared by web and API. */
    public function verificationStatus(): array
    {
        if ($this->verified_at) {
            return ['code' => 'verified', 'label' => 'Checked by our team', 'date' => $this->verified_at->toIso8601String()];
        }
        if ($this->manager_confirmed) {
            return ['code' => 'confirmed', 'label' => 'Confirmed by CareSenda', 'date' => $this->confirmation_reported_at?->toDateString()];
        }
        if ($this->service_evidence === 'routine-checklist') {
            return ['code' => 'checklist', 'label' => 'Waiting for a phone check', 'date' => null];
        }

        return ['code' => 'pending', 'label' => 'Not checked yet', 'date' => null];
    }
}
