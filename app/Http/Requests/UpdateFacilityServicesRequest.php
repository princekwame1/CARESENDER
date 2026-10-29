<?php

namespace App\Http\Requests;

use App\Services\SearchFilters;
use App\Support\MapLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateFacilityServicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isStaff();
    }

    public function rules(): array
    {
        $days = implode(',', SearchFilters::DAYS);
        $time = ['nullable', 'date_format:H:i'];
        $creating = $this->routeIs('admin.facilities.store');

        return [
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:190'],
            'facility_type' => ['sometimes', 'nullable', 'string', 'max:80'],
            'ownership' => ['sometimes', 'nullable', 'in:Private,Public,Quasi-government,Faith-based,NGO'],
            'area_id' => [$creating ? 'required' : 'sometimes', 'nullable', 'exists:areas,id'],
            'address' => ['sometimes', 'nullable', 'string', 'max:190'],
            'maps_url' => ['sometimes', 'nullable', 'url:https', 'max:500', function ($attr, $value, $fail) {
                if ($value && ! MapLink::isGoogleMaps($value)) {
                    $fail('Paste a Google Maps link (it starts with https://maps.app.goo.gl or https://www.google.com/maps).');
                }
            }],
            'coordinates' => ['sometimes', 'nullable', 'string', 'max:60', function ($attr, $value, $fail) {
                if ($value && ! MapLink::coordinates($value)) {
                    $fail('Enter the location as latitude, longitude, for example 5.6037, -0.1870.');
                }
            }],
            'district' => ['sometimes', 'nullable', 'string', 'max:120'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'whatsapp' => ['sometimes', 'nullable', 'string', 'max:40'],
            'email' => ['sometimes', 'nullable', 'email', 'max:190'],
            'issue' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'diagnostic_categories' => ['sometimes', 'array'],
            'diagnostic_categories.*' => ['in:'.implode(',', array_keys(config('caresenda.diagnostic_categories')))],
            'opening_hours' => ['sometimes', 'array'],
            'opening_hours.*.on' => ['sometimes', 'boolean'],
            'opening_hours.*.open' => $time,
            'opening_hours.*.close' => $time,
            'services' => ['sometimes', 'array'],
            'services.*.available' => ['sometimes', 'boolean'],
            'services.*.specialist_name' => ['sometimes', 'nullable', 'string', 'max:190'],
            'services.*.gender' => ['sometimes', 'nullable', 'in:Male,Female'],
            'services.*.price' => ['sometimes', 'nullable', 'string', 'max:80'],
            'services.*.slots' => ['sometimes', 'array'],
            'services.*.slots.*.on' => ['sometimes', 'boolean'],
            'services.*.slots.*.start' => $time,
            'services.*.slots.*.end' => $time,
            // Mobile API (older clients): plain list of days
            'services.*.schedule' => ['sometimes', 'nullable', 'string', 'max:190'],
            'services.*.days' => ['sometimes', 'array'],
            'services.*.days.*' => ['in:'.$days],
            'mark_checked' => ['sometimes', 'boolean'],
        ];
    }

    /** Opening/sitting times must have the end after the start, and only real weekday keys. */
    public function after(): array
    {
        return [function (Validator $v) {
            $check = function (string $prefix, array $rows, string $from, string $to) use ($v) {
                foreach ($rows as $day => $row) {
                    if (! in_array($day, SearchFilters::DAYS, true)) {
                        $v->errors()->add($prefix, "Unknown day “{$day}”.");

                        continue;
                    }
                    if (! empty($row['on']) && (! empty($row[$from]) xor ! empty($row[$to]))) {
                        $v->errors()->add("$prefix.$day", "Give both times for {$day}.");
                    } elseif (! empty($row['on']) && ! empty($row[$from]) && $row[$to] <= $row[$from]) {
                        $v->errors()->add("$prefix.$day", "On {$day}, the closing time must be after the opening time.");
                    }
                }
            };
            $check('opening_hours', (array) $this->input('opening_hours', []), 'open', 'close');
            foreach ((array) $this->input('services', []) as $id => $svc) {
                $check("services.$id.slots", (array) ($svc['slots'] ?? []), 'start', 'end');
            }
        }];
    }
}
