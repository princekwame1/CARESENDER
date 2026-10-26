{{-- A labelled form field. Problems are shown as toasts, so there is no text under the input. --}}
@props(['label', 'name', 'type' => 'text', 'value' => null, 'modal' => null])
@php
    // Inside a modal, only reuse old input when that modal is the one being reopened.
    $useOld = request()->hasSession() && (! $modal || old('_modal') === $modal);
    // Never refill passwords.
    $current = $type === 'password' ? null : ($useOld ? old(str_replace(['[', ']'], ['.', ''], $name), $value) : $value);
    $hasError = isset($errors) && $errors->has(str_replace(['[', ']'], ['.', ''], $name)) && $useOld;
@endphp
<label {{ $attributes->only('class')->class(['block']) }}>
    <span class="label">{{ $label }}</span>
    @if ($type === 'textarea')
        <textarea name="{{ $name }}" rows="3" {{ $attributes->except('class') }} @class(['field resize-y', '!border-danger' => $hasError])>{{ $current }}</textarea>
    @elseif ($type === 'select')
        <select name="{{ $name }}" {{ $attributes->except('class') }} @class(['field', '!border-danger' => $hasError])>{{ $slot }}</select>
    @else
        <input type="{{ $type }}" name="{{ $name }}" value="{{ $current }}" {{ $attributes->except('class') }} @class(['field', '!border-danger' => $hasError])>
    @endif
</label>
