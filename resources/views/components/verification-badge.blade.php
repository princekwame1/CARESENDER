@props(['service'])
@php($status = $service->verificationStatus())
<span class="badge-{{ $status['code'] }} !px-2 !py-0.5 !text-[11px]" title="{{ $status['date'] ? 'Recorded '.\Illuminate\Support\Carbon::parse($status['date'])->toFormattedDateString() : '' }}">
    <x-icon :name="in_array($status['code'], ['verified', 'confirmed']) ? 'shield' : 'clock'" class="size-3" />
    {{ $status['label'] }}
</span>
