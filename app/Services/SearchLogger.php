<?php

namespace App\Services;

use App\Models\SearchLog;
use Illuminate\Http\Request;

class SearchLogger
{
    public function __construct(private GeoLocator $geo) {}

    public function log(Request $request, SearchFilters $f, int $results, string $channel): void
    {
        // Skip follow-up pages and automated traffic (crawlers, headless browsers) so stats reflect real people.
        if ($f->page > 1 || preg_match('/bot|crawl|spider|slurp|headless|lighthouse|preview/i', (string) $request->userAgent())) {
            return;
        }

        $user = $request->user() ?? $request->user('sanctum');
        $area = $f->hasOrigin() ? $this->geo->resolve($f->lat, $f->lng)['area'] : null;

        // Defer the write until after the response is sent.
        defer(fn () => SearchLog::create([
            'user_id' => $user?->id,
            'query' => $f->q ? mb_substr($f->q, 0, 120) : null,
            'type' => $f->type,
            'city' => $f->city,
            'area' => $area,
            // Store coordinates rounded to ~1 km for privacy.
            'lat' => $f->hasOrigin() ? round($f->lat, 2) : null,
            'lng' => $f->hasOrigin() ? round($f->lng, 2) : null,
            'results_count' => $results,
            'channel' => $channel,
        ]));
    }
}
