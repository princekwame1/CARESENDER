<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DirectoryStats;
use App\Support\SpecialtyPhoto;
use Illuminate\Http\JsonResponse;

class SpecialtyController extends Controller
{
    /** Every specialist listing name, most common first, with its card photo. */
    public function __invoke(DirectoryStats $stats): JsonResponse
    {
        return response()->json(['data' => collect($stats->topSpecialties(200))
            ->map(fn ($count, $name) => ['name' => $name, 'listings' => $count, 'image_url' => SpecialtyPhoto::cardUrl($name)])
            ->values()->all()]);
    }
}
