<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

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

    /** @return list<string> */
    public function coveredAreas(): array
    {
        return AccessPlan::normalizeAreas($this->areas);
    }
}
