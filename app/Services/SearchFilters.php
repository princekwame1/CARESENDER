<?php

namespace App\Services;

use Illuminate\Http\Request;

/** Validated, immutable search parameters shared by web and API controllers. */
final readonly class SearchFilters
{
    public const TYPES = ['all', 'test', 'specialist', 'facility'];

    public const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    public function __construct(
        public ?string $q = null,
        public string $type = 'all',
        public ?string $city = null,
        public ?string $diagnostic = null,
        public ?string $day = null,
        public ?float $lat = null,
        public ?float $lng = null,
        public ?float $radius = null,
        public bool $confirmedOnly = false,
        public string $sort = 'relevance',
        public int $page = 1,
        public int $perPage = 20,
    ) {}

    public static function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', 'in:'.implode(',', self::TYPES)],
            'city' => ['nullable', 'in:'.implode(',', array_keys(config('caresenda.cities')))],
            'diagnostic' => ['nullable', 'in:'.implode(',', array_keys(config('caresenda.diagnostic_categories')))],
            'day' => ['nullable', 'in:'.implode(',', self::DAYS)],
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:lng'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
            'radius' => ['nullable', 'numeric', 'min:0.5', 'max:100'],
            'confirmed' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:relevance,distance,name'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /** Build from a request; falls back to the location cookie set by the web client. */
    public static function fromRequest(Request $request, bool $useLocationCookie = false): self
    {
        $data = $request->validate(self::rules());
        $lat = isset($data['lat']) ? (float) $data['lat'] : null;
        $lng = isset($data['lng']) ? (float) $data['lng'] : null;

        // The web location cookie only counts when it is inside Accra/Tema; distances from
        // far away (e.g. a visitor abroad) are meaningless and would sort everything oddly.
        if ($lat === null && $useLocationCookie && ($loc = LocationCookie::read($request))
            && app(GeoLocator::class)->resolve($loc['lat'], $loc['lng'])['in_coverage']) {
            [$lat, $lng] = [$loc['lat'], $loc['lng']];
        }

        $hasOrigin = $lat !== null;

        return new self(
            q: isset($data['q']) ? trim($data['q']) : null,
            type: $data['type'] ?? 'all',
            city: $data['city'] ?? null,
            diagnostic: $data['diagnostic'] ?? null,
            day: $data['day'] ?? null,
            lat: $lat,
            lng: $lng,
            radius: isset($data['radius']) && $hasOrigin ? (float) $data['radius'] : null,
            confirmedOnly: (bool) ($data['confirmed'] ?? false),
            sort: $data['sort'] ?? ($hasOrigin ? 'distance' : 'relevance'),
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 20),
        );
    }

    public function with(array $changes): self
    {
        return new self(...array_merge(get_object_vars($this), $changes));
    }

    public function hasOrigin(): bool
    {
        return $this->lat !== null && $this->lng !== null;
    }

    /** Identifies "the same search" regardless of page, sort or the exact spot it was run from. */
    public function signature(): string
    {
        return hash('sha256', json_encode([
            mb_strtolower((string) $this->q), $this->type, $this->city, $this->diagnostic, $this->day, $this->confirmedOnly,
            $this->radius, $this->radius ? [round($this->lat, 2), round($this->lng, 2)] : null,
        ]));
    }

    public function isEmpty(): bool
    {
        return ! $this->q && ! $this->diagnostic && ! $this->day && $this->type === 'all';
    }
}
