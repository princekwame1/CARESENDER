<?php

namespace App\Http\Resources;

use App\Models\Service;
use App\Services\AccessGate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Service */
class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // The service name stays visible; who, when and how much need a pass that covers specialists or tests.
        $area = AccessGate::areaForService($this->type);
        $open = app(AccessGate::class)->allows($request, $this->facility_id, $area);

        return [
            'id' => $this->id,
            'type' => $this->type,
            'service' => $this->service,
            'locked' => ! $open,
            'specialist_name' => $open ? $this->specialist_name : null,
            'gender' => $open ? $this->gender : null,
            'lab_category' => $this->lab_category,
            'panel_details' => $this->panel_details,
            'schedule' => $open ? $this->schedule : null,
            'days' => $open ? ($this->days ?? []) : [],
            'slots' => $open ? ($this->slots ?? []) : [],
            'specialty_id' => $this->specialty_id,
            'price' => $open ? $this->price : null,
            'diagnostic_categories' => $this->diagnostic_categories ?? [],
            'available' => $this->available,
            'verification' => $this->verificationStatus(),
            'note' => $open ? $this->service_note : null,
            'distance_km' => $open ? $this->distanceKm : null,
            'facility' => (new FacilityResource($this->whenLoaded('facility')))->openedBy($area),
            'facility_id' => $this->facility_id,
        ];
    }
}
