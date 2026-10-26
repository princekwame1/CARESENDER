<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\Service;
use App\Services\DirectoryCache;
use App\Support\SearchNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuggestController extends Controller
{
    /** Type-ahead suggestions: distinct service names first, then facility names. */
    public function __invoke(Request $request, DirectoryCache $cache): JsonResponse
    {
        $q = trim((string) $request->validate(['q' => ['required', 'string', 'min:2', 'max:60']])['q']);
        $words = SearchNormalizer::words($q) ?: [mb_strtolower($q)];

        $data = $cache->remember('suggest:'.md5(implode(' ', $words)), function () use ($words) {
            $like = fn ($query, $column) => collect($words)->each(
                fn ($w) => $query->where($column, 'like', '%'.addcslashes($w, '%_\\').'%')
            );

            $services = Service::query()->where('available', true)
                ->whereHas('facility', fn ($q) => $q->where('is_active', true))
                ->tap(fn ($q) => $like($q, 'search_text'))
                ->selectRaw('service as label, type, count(*) as places')
                ->groupBy('service', 'type')
                ->orderByDesc('places')
                ->limit(6)
                ->get()
                ->map(fn ($s) => ['label' => $s->label, 'kind' => $s->type, 'meta' => $s->places.' '.($s->places == 1 ? 'place' : 'places')]);

            $facilities = Facility::query()
                ->active()
                ->tap(fn ($q) => $like($q, 'search_text'))
                ->orderByDesc('services_count')
                ->limit(4)
                ->get(['id', 'name', 'area', 'city'])
                ->map(fn ($f) => ['label' => $f->name, 'kind' => 'facility', 'meta' => $f->area ?: $f->city, 'id' => $f->id]);

            return $services->concat($facilities)->values()->all();
        }, 3600);

        return response()->json(['data' => $data]);
    }
}
