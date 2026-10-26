<?php

namespace App\Http\Resources;

use App\Models\Facility;
use App\Services\AccessGate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Facility */
class FacilityResource extends JsonResource
{
    /** The access area that opens these details; a service's facility opens with that service. */
    public string $accessArea = 'facilities';

    public function openedBy(string $area): static
    {
        $this->accessArea = $area;

        return $this;
    }

    public function toArray(Request $request): array
    {
        // Without an access pass for this area (when payments are on) only the type and city are shown:
        // the name, neighbourhood and everything else need a plan.
        $open = app(AccessGate::class)->allows($request, $this->id, $this->accessArea);

        return [
            'id' => $this->id,
            'name' => $open ? $this->name : null,
            'facility_type' => $this->facility_type,
            'ownership' => $this->ownership,
            'city' => $this->city,
            'area' => $open ? $this->area : null,
            'district' => $open ? $this->district : null,
            'locked' => ! $open,
            'address' => $open ? $this->address : null,
            'contact' => [
                'phone' => $open ? $this->phone : null,
                'whatsapp' => $open ? $this->whatsapp : null,
                'whatsapp_url' => $open ? $this->whatsappUrl() : null,
            ],
            'diagnostic_categories' => $this->diagnostic_categories ?? [],
            'location' => [
                // Where it is (even the neighbourhood centre) and how far away it is need a pass.
                'lat' => $open ? $this->lat : null,
                'lng' => $open ? $this->lng : null,
                // "exact" = set by the team; "area" = neighbourhood centre; "city" = city centre fallback.
                'precision' => $this->geo_precision,
                'maps_url' => $open ? $this->maps_url : null,
            ],
            'distance_km' => $open ? $this->distanceKm : null,
            'services_count' => $this->services_count,
            'hours' => $open ? $this->hours : null,
            'opening_hours' => $open ? ($this->opening_hours ?? (object) []) : (object) [],
            'email' => $open ? $this->email : null,
            'service_notice' => $this->issue,
            'verified_at' => $this->verified_at?->toIso8601String(),
            'directions_url' => $open ? $this->directionsUrl() : null,
            $this->mergeWhen($open && $request->routeIs('api.v1.facilities.show'), fn () => [
                'sources' => [
                    'facility' => $this->source,
                    'contact' => $this->contact_source,
                    'directory' => $this->directory_evidence,
                    'register' => $this->register_evidence ?? [],
                ],
            ]),
            'services' => ServiceResource::collection($this->whenLoaded('services')),
        ];
    }
}
