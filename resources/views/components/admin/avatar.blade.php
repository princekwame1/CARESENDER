{{-- Profile photo, or the user's initial when there isn't one. --}}
@props(['user', 'size' => 'size-9'])
@if ($user->avatarUrl())
    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" {{ $attributes->class([$size, 'shrink-0 rounded-full object-cover ring-2 ring-surface']) }}>
@else
    <span {{ $attributes->class([$size, 'grid shrink-0 place-items-center rounded-full bg-[#07689f] font-semibold uppercase text-white']) }} aria-hidden="true">{{ mb_substr($user->name, 0, 1) }}</span>
@endif
