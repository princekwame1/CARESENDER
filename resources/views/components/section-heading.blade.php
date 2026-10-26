@props(['label' => null, 'title', 'center' => false])
<div data-reveal {{ $attributes->class(['max-w-3xl', 'mx-auto text-center' => $center]) }}>
    @if ($label)<p class="section-label">{{ $label }}</p>@endif
    <h2 class="mt-1 text-[24px] font-bold leading-[1.3] sm:text-3xl lg:text-[36px]">{!! $title !!}</h2>
    @if ($slot->isNotEmpty())<div class="cms-text mt-4 text-base sm:mt-5 sm:text-lg">{{ $slot }}</div>@endif
</div>
