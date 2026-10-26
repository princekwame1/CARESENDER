<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['lat' => 'float', 'lng' => 'float'];
    }

    public function facilities(): HasMany
    {
        return $this->hasMany(Facility::class);
    }
}
