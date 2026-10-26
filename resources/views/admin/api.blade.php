@php
    $endpoints = [
        'Discovery' => [
            ['GET', '/meta', 'Cities, categories, days, common searches, access plans and totals.', null],
            ['GET', '/dashboard?lat=5.635&lng=-0.16', 'Home screen in one call: resolved location, nearby stats, nearest facilities.', null],
            ['GET|POST', '/geo/resolve?lat=5.635&lng=-0.16', 'Turn device GPS into the nearest CareSenda area + what is within the radius.', null],
            ['GET', '/areas', 'All neighbourhoods with approximate centroids.', null],
            ['GET', '/map/markers?city=Accra', 'Compact marker array for map views.', null],
        ],
        'Search' => [
            ['GET', '/suggest?q=gyn', 'Type-ahead suggestions (services first, then facilities). Minimum 2 characters.', null],
            ['GET', '/services?q=fbc&lat=5.635&lng=-0.16&radius=5', 'Tests & specialists. Filters: q, type, city, diagnostic, day, lat, lng, radius, confirmed, sort, page, per_page.', null],
            ['GET', '/services/{id}', 'One service with its facility.', null],
            ['GET', '/facilities?q=hospital&city=Tema', 'Facilities. Same filters as services.', null],
            ['GET', '/facilities/nearby?lat=5.635&lng=-0.16&radius=10&limit=10', 'Nearest facilities, sorted by distance.', null],
            ['GET', '/facilities/{id}', 'Facility detail with all services and sources.', null],
        ],
        'Account (Bearer token)' => [
            ['POST', '/auth/register', 'name, email, password, device_name, phone? → token', null],
            ['POST', '/auth/login', 'email, password, device_name → token', null],
            ['POST', '/auth/logout', 'Revoke the current token.', 'auth'],
            ['GET', '/me', 'Current user.', 'auth'],
            ['GET', '/me/searches', 'Last 20 searches made with this account.', 'auth'],
            ['DELETE', '/me/searches', 'Clear search history.', 'auth'],
        ],
        'Access & payments (Bearer token)' => [
            ['GET', '/plans', 'Plans on sale (set in Payments). Search results carry an "access" block and items carry "locked".', null],
            ['GET', '/me/access', 'Unlimited access end time, searches left, active passes, plans.', 'auth'],
            ['POST', '/me/passes', 'plan_id → Paystack authorization_url + reference. Open the URL in a browser.', 'auth'],
            ['GET', '/me/passes/{reference}', 'Payment status (checks Paystack while pending).', 'auth'],
            ['POST', '/me/unlocks', 'Same filters as the search → spends one search credit and opens all its results. 402 when none left.', 'auth'],
            ['POST', '/payments/paystack/webhook', 'Paystack webhook (signed). Set this URL in Paystack.', null],
        ],
        'Admin (admin token)' => [
            ['GET', '/admin/stats', 'Directory totals, facility types, search demand.', 'admin'],
            ['PATCH', '/admin/facilities/{id}', 'Record a phone verification: phone, whatsapp, hours, issue, services[{id}][available|price|schedule|days|specialist_name|gender].', 'admin'],
        ],
    ];
@endphp
<x-layouts.admin title="Mobile API">
    <div>
        <p class="max-w-3xl">A versioned JSON API for building the CareSenda Android and iOS apps. Public endpoints need no token; signed-in users get their search history synced.</p>

        <div class="mt-10 grid gap-5 sm:grid-cols-3">
            <div class="card p-6"><p class="label">Base URL</p><code class="text-sm font-semibold break-all">{{ url('/api/v1') }}</code></div>
            <div class="card p-6"><p class="label">Auth</p><p class="text-sm"><code>Authorization: Bearer &lt;token&gt;</code> (Laravel Sanctum)</p></div>
            <div class="card p-6"><p class="label">Limits</p><p class="text-sm">120 req/min per user or IP · 10/min on auth. Static endpoints send <code>ETag</code> + 5-min cache.</p></div>
        </div>

        @foreach ($endpoints as $group => $rows)
            <h2 class="mt-14 text-xl font-bold">{{ $group }}</h2>
            <div class="card mt-5 divide-y divide-line overflow-hidden">
                @foreach ($rows as [$method, $path, $desc, $auth])
                    <div class="grid gap-3 p-4 sm:grid-cols-[7rem_1fr]">
                        <span class="badge-confirmed h-fit w-fit font-mono">{{ $method }}</span>
                        <div class="min-w-0">
                            <code class="block break-all text-sm font-semibold">/api/v1{{ $path }}</code>
                            <p class="mt-1.5 text-sm text-muted">{{ $desc }} @if ($auth)<span class="badge-checklist ml-1">{{ $auth }}</span>@endif</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach

        <h2 class="mt-14 text-xl font-bold">Example</h2>
        <pre class="card mt-5 overflow-x-auto p-6 text-xs leading-relaxed"><code>curl "{{ url('/api/v1/services') }}?q=ultrasound&amp;lat=5.635&amp;lng=-0.16&amp;radius=5&amp;per_page=2"

{
  "data": [{
    "id": "…", "type": "test", "service": "Ultrasound scan",
    "distance_km": 0.8,
    "verification": { "code": "confirmed", "label": "Availability confirmed by CareSenda", "date": "2026-09-22" },
    "facility": { "id": "hefra-…", "name": "…", "contact": { "phone": "+233…" },
                  "location": { "lat": 5.635, "lng": -0.16, "precision": "area" } }
  }],
  "links": { … }, "meta": { "current_page": 1, "total": 42, … },
  "summary": { "services": 42, "confirmed_services": 30, "service_facilities": 18, "facilities": 3 }
}</code></pre>
        <p class="mt-5 text-sm text-muted">Distances are estimates: <code>location.precision</code> is <code>area</code> (neighbourhood centroid) or <code>city</code> (city centre fallback). Coordinates in search logs are rounded to ~1 km.</p>
    </div>
</x-layouts.admin>
