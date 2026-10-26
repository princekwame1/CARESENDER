{{-- Facility details fields, shared by the "Add a facility" and "Edit details" modals. --}}
@php($f = $facility ?? null)
<input type="hidden" name="_section" value="details">
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <x-admin.field class="sm:col-span-2" label="Facility name" name="name" :value="$f?->name" :modal="$modal" required />
    <x-admin.field label="What kind of place is it?" name="facility_type" :value="$f?->facility_type" :modal="$modal" list="facility-types" placeholder="For example, Clinic" />
    <datalist id="facility-types">@foreach ($facilityTypes as $t)<option value="{{ $t }}">@endforeach</datalist>
    <x-admin.field label="Who runs it?" name="ownership" type="select" :modal="$modal">
        <option value="">Not sure</option>
        @foreach (['Private', 'Public', 'Quasi-government', 'Faith-based', 'NGO'] as $o)<option @selected(($f?->ownership) === $o)>{{ $o }}</option>@endforeach
    </x-admin.field>
    <x-admin.field label="Neighbourhood" name="area_id" type="select" :modal="$modal" :required="! $f">
        <option value="">{{ $f ? 'Keep '.($f->area ?: $f->city) : 'Choose a neighbourhood' }}</option>
        @foreach ($areas as $city => $list)
            <optgroup label="{{ $city }}">@foreach ($list as $a)<option value="{{ $a->id }}" @selected(old('_modal') === $modal && (string) old('area_id') === (string) $a->id)>{{ $a->name }}</option>@endforeach</optgroup>
        @endforeach
    </x-admin.field>
    <x-admin.field label="District" name="district" :value="$f?->district" :modal="$modal" />
    <x-admin.field class="sm:col-span-2 lg:col-span-3" label="Street address" name="address" :value="$f?->address" :modal="$modal" />
    <x-admin.field label="Phone number" name="phone" :value="$f?->phone" :modal="$modal" placeholder="+233…" />
    <x-admin.field label="WhatsApp number" name="whatsapp" :value="$f?->whatsapp" :modal="$modal" placeholder="+233…" />
    <x-admin.field class="sm:col-span-2 lg:col-span-1" label="Email address" name="email" type="email" :value="$f?->email" :modal="$modal" />
    <x-admin.field class="lg:col-span-2" label="Google Maps link" name="maps_url" type="url" :value="$f?->maps_url" :modal="$modal" placeholder="Paste the link from Google Maps" />
    <x-admin.field label="Exact spot on the map" name="coordinates" :value="$f && $f->geo_precision === 'exact' ? $f->lat.', '.$f->lng : ''" :modal="$modal" placeholder="For example, 5.6037, -0.1870" />
    <div class="sm:col-span-2 lg:col-span-3">
        <span class="label">Which tests can people get here?</span>
        <div class="flex flex-wrap gap-2">
            @foreach (config('caresenda.diagnostic_categories') as $k => $label)
                <label class="chip cursor-pointer has-[:checked]:border-brand has-[:checked]:bg-brand-soft has-[:checked]:text-brand">
                    <input type="checkbox" name="diagnostic_categories[]" value="{{ $k }}" class="sr-only" @checked(in_array($k, old('_modal') === $modal ? old('diagnostic_categories', []) : ($f?->diagnostic_categories ?? [])))>{{ $label }}
                </label>
            @endforeach
        </div>
    </div>
    <x-admin.field class="sm:col-span-2 lg:col-span-3" label="Anything out of service right now?" name="issue" :value="$f?->issue" :modal="$modal" placeholder="For example, the MRI machine is being repaired" />
</div>
