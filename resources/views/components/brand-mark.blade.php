@props(['tile' => 'bg-brand text-brand-ink', 'text' => 'text-brand', 'inverse' => false])
{{-- Logo + name from Settings → Branding; otherwise the built-in CareSenda wordmark (white on dark backgrounds). --}}
@php($logo = \App\Support\Branding::logoUrl())
@if ($logo)
    <img src="{{ $logo }}" alt="" class="size-9 shrink-0 rounded-xl object-contain">
    <span class="font-display text-base font-semibold uppercase tracking-[2px] {{ $text }} sm:text-lg sm:tracking-[2.5px]">{{ \App\Support\Branding::name() }}</span>
@else
    <img src="/images/brand/logo.png" alt="{{ \App\Support\Branding::name() }}" width="832" height="200" @class(['brand-logo h-9 w-auto shrink-0 sm:h-10', 'brand-logo-inverse' => $inverse])>
@endif
