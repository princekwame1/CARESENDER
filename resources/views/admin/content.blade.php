@php
    $pageLinks = ['general' => 'home', 'home' => 'home', 'about' => 'about', 'verify' => 'verify', 'contact' => 'contact', 'search' => 'search'];
    // Fields are labelled "Section · field"; group them by section.
    $sections = $fields->groupBy(fn ($f) => str_contains($f['label'], ' · ') ? trim(strtok($f['label'], '·')) : 'This page');
    $short = fn ($f) => str_contains($f['label'], ' · ') ? ucfirst(trim(substr($f['label'], strpos($f['label'], '·') + 2))) : $f['label'];
@endphp
<x-layouts.admin :title="$pageInfo[0]" :heading="'Page content · '.$pageInfo[0]">
    <x-slot:actions>
        <a href="{{ route($pageLinks[$page]) }}" target="_blank" class="btn-secondary !py-2">See the page</a>
    </x-slot:actions>

    <div class="space-y-5">
        <div class="grid gap-5 xl:grid-cols-2">
            @foreach ($sections as $section => $items)
                @php($m = 'edit-section-'.Str::slug($section))
                <section class="card p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold">{{ $section }}</h2>
                        <button type="button" class="btn-secondary !px-3 !py-1.5 text-xs" data-modal-open="{{ $m }}">Edit</button>
                    </div>
                    <dl class="mt-4 space-y-3 text-sm">
                        @foreach ($items as $f)
                            <div>
                                <dt class="text-xs text-muted">{{ $short($f) }}</dt>
                                <dd class="mt-0.5">
                                    @if ($f['type'] === 'image')
                                        <x-photo :image="$f['image']" :credit="false" class="mt-1 aspect-[16/9] max-w-xs rounded-md bg-surface-2" sizes="20rem" />
                                    @elseif (filled($f['value']))
                                        <span class="text-ink">{{ Str::limit(strip_tags($f['value']), 180) }}</span>
                                    @elseif (filled($f['default']))
                                        <span class="text-muted">{{ Str::limit(strip_tags($f['default']), 180) }}</span>
                                    @else
                                        <span class="text-muted">Nothing yet</span>
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endforeach
        </div>
    </div>

    {{-- One modal per section --}}
    @foreach ($sections as $section => $items)
        @php($m = 'edit-section-'.Str::slug($section))
        <x-admin.modal :id="$m" :title="$section" :size="$items->contains('type', 'rich') || $items->contains('type', 'image') ? 'lg' : 'md'">
            <form id="{{ $m }}-form" method="POST" action="{{ route('admin.content.update', $page) }}" enctype="multipart/form-data" class="grid gap-5">
                @csrf @method('PUT')
                <input type="hidden" name="_modal" value="{{ $m }}">
                @foreach ($items as $f)
                    @php($label = $short($f))
                    @if ($f['type'] === 'image')
                        <div class="grid gap-4 rounded-md border border-line p-4 sm:grid-cols-[11rem_1fr]">
                            <x-photo :image="$f['image']" :credit="false" class="aspect-[4/3] rounded-md bg-surface-2" sizes="11rem" />
                            <div class="space-y-3">
                                <p class="text-sm font-medium text-ink">{{ $label }}</p>
                                <div><span class="label">Choose a new photo</span>
                                    <x-file-upload :name="'content['.$f['input'].']'" accept="image/jpeg,image/png,image/webp" label="Browse a photo to upload" hint="JPG, PNG or WebP" />
                                </div>
                                <x-admin.field label="Describe the photo for people who can’t see it" :name="'alt['.$f['input'].']'" :value="$f['alt']" :modal="$m" :placeholder="$f['alt_default'] ?: 'What does the photo show?'" />
                                @if ($f['image']['custom'])
                                    <label class="flex items-center justify-between gap-4 text-sm">Go back to the original photo <input type="checkbox" name="reset[{{ $f['input'] }}]" value="1"></label>
                                @endif
                            </div>
                        </div>
                    @elseif ($f['type'] === 'rich')
                        <label class="block">
                            <span class="label">{{ $label }}</span>
                            <div data-rich class="overflow-hidden rounded-md border border-line bg-surface focus-within:border-brand focus-within:ring-2 focus-within:ring-brand/20">
                                <textarea name="content[{{ $f['input'] }}]" rows="4" class="field !rounded-none !border-0 !ring-0" data-placeholder="{{ strip_tags($f['default']) }}" placeholder="{{ strip_tags($f['default']) }}">{{ old('_modal') === $m ? old('content.'.$f['input']) : $f['value'] }}</textarea>
                            </div>
                        </label>
                    @else
                        <x-admin.field :label="$label" :name="'content['.$f['input'].']'" :type="$f['type'] === 'textarea' ? 'textarea' : 'text'" :value="$f['value']" :modal="$m" :placeholder="$f['default']" />
                    @endif
                @endforeach
            </form>
            <x-slot:footer>
                <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
                <button form="{{ $m }}-form" class="btn-primary">Save</button>
            </x-slot:footer>
        </x-admin.modal>
    @endforeach
</x-layouts.admin>
