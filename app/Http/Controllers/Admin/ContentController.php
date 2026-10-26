<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Cms;
use App\Support\ImageUploader;
use App\Support\RichText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.content.edit', array_key_first(Cms::pages()));
    }

    public function edit(string $page): View
    {
        abort_unless(isset(Cms::pages()[$page]), 404);

        $fields = collect($this->fieldsFor($page))->map(fn ($f, $key) => [
            'key' => $key,
            'input' => str_replace('.', '__', $key), // PHP turns dots in field names into nested arrays
            'label' => $f[1], 'type' => $f[2], 'default' => $f[3],
            'value' => Cms::all()[$key] ?? null,
            'image' => $f[2] === 'image' ? Cms::image($key) : null,
            'alt' => $f[2] === 'image' ? (json_decode(Cms::all()[$key] ?? '', true)['alt'] ?? '') : null,
            'alt_default' => $f[4] ?? '',
        ]);

        return view('admin.content', ['page' => $page, 'pageInfo' => Cms::pages()[$page], 'fields' => $fields]);
    }

    public function update(Request $request, string $page): RedirectResponse
    {
        abort_unless(isset(Cms::pages()[$page]), 404);
        $fields = $this->fieldsFor($page);

        $rules = [];
        foreach ($fields as $key => $f) {
            $in = str_replace('.', '__', $key);
            $rules["content.$in"] = $f[2] === 'image'
                ? ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:min_width=400']
                : ['nullable', 'string', 'max:'.match ($f[2]) {
                    'rich' => 8000, 'textarea' => 3000, default => 300
                }];
            if ($f[2] === 'image') {
                $rules["alt.$in"] = ['nullable', 'string', 'max:200'];
            }
        }
        if (isset($fields['contact.email'])) {
            $rules['content.contact__email'] = ['nullable', 'email', 'max:190'];
        }
        $request->validate($rules, [
            'content.contact__email.email' => 'The contact email must be a valid email address.',
            'content.*.image' => 'Please upload a photo (JPG, PNG or WebP).',
            'content.*.max' => 'Photos must be 8 MB or smaller.',
            'content.*.dimensions' => 'Photos must be at least 400 pixels wide.',
        ]);

        $values = [];
        foreach ($fields as $key => $f) {
            $in = str_replace('.', '__', $key);
            // Each modal saves one section: leave fields that weren't sent alone.
            $sent = $request->has("content.$in") || $request->hasFile("content.$in") || $request->has("alt.$in") || $request->has("reset.$in");
            if (! $sent) {
                continue;
            }
            if ($f[2] !== 'image') {
                $v = $request->input("content.$in");
                $v = is_string($v) ? trim($v) : null;
                // Formatted text is cleaned down to safe tags; plain fields lose any HTML.
                $values[$key] = $f[2] === 'rich' ? RichText::sanitize($v) : ($v === null ? null : strip_tags($v));

                continue;
            }

            $current = json_decode(Cms::all()[$key] ?? '', true) ?: null;
            $alt = trim((string) $request->input("alt.$in"));
            if ($request->boolean("reset.$in")) {
                ImageUploader::delete($current['path'] ?? null);
                $values[$key] = null;
            } elseif ($file = $request->file("content.$in")) {
                ImageUploader::delete($current['path'] ?? null);
                $values[$key] = json_encode([...ImageUploader::store($file, $key), 'alt' => $alt]);
            } elseif ($current) {
                $values[$key] = json_encode([...$current, 'alt' => $alt]);
            }
        }
        Cms::save($values);

        return back()->with('status', 'Saved. The website shows your changes now.');
    }

    private function fieldsFor(string $page): array
    {
        return array_filter(Cms::fields(), fn ($f) => $f[0] === $page);
    }
}
