<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Specialty;
use App\Services\CatalogSync;
use App\Services\DirectoryCache;
use App\Support\ImageUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SpecialtyController extends Controller
{
    public function __construct(private DirectoryCache $cache, private CatalogSync $sync) {}

    public function index(): View
    {
        return view('admin.specialties', [
            'specialties' => Specialty::withCount(['services', 'catalogServices'])->orderByRaw("kind = 'diagnostic'")->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $group = Specialty::create([...$data, 'slug' => Str::slug($data['name'])]);
        // A new clinical specialty gets its consultation service in the list straight away.
        if ($group->kind === 'clinical') {
            $group->catalogServices()->create(['name' => $group->name, 'type' => 'specialist']);
        }
        $this->cache->flush();

        return back()->with('status', "Added {$data['name']}.");
    }

    public function update(Request $request, Specialty $specialty): RedirectResponse
    {
        $data = $this->validated($request, $specialty);
        $oldName = $specialty->name;
        $renamed = $data['name'] !== $oldName;

        if ($request->boolean('remove_image')) {
            ImageUploader::delete($specialty->image['path'] ?? null);
            $data['image'] = null;
        } elseif ($file = $request->file('image')) {
            ImageUploader::delete($specialty->image['path'] ?? null);
            $data['image'] = [...ImageUploader::store($file, 'specialty-'.$data['name']), 'alt' => (string) $request->input('image_alt')];
        }
        $specialty->update([...$data, 'slug' => Str::slug($data['name'])]);

        // Keep catalogue entries and facility listings in step with the new name.
        if ($renamed) {
            $specialty->catalogServices()->where('type', 'specialist')->where('name', $oldName)->update(['name' => $specialty->name]);
            $this->sync->syncGroup($specialty->load('catalogServices'));
        }
        $this->cache->flush();

        return back()->with('status', "Saved {$specialty->name}.");
    }

    public function destroy(Specialty $specialty): RedirectResponse
    {
        if ($specialty->services()->exists()) {
            return back()->with('status', "{$specialty->name} is still used by listings. Hide it instead, or remove those listings first.");
        }
        $specialty->catalogServices()->delete();
        ImageUploader::delete($specialty->image['path'] ?? null);
        $specialty->delete();
        $this->cache->flush();

        return back()->with('status', "Deleted {$specialty->name}.");
    }

    private function validated(Request $request, ?Specialty $specialty = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('specialties', 'name')->ignore($specialty)],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
            'show_on_home' => ['sometimes', 'boolean'],
            'kind' => ['sometimes', 'in:clinical,diagnostic'],
            'diagnostic' => ['nullable', 'in:laboratory,imaging,other'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:min_width=400'],
            'image_alt' => ['nullable', 'string', 'max:200'],
            'remove_image' => ['sometimes', 'boolean'],
        ], [
            'image.image' => 'Please upload a photo (JPG, PNG or WebP).',
            'image.max' => 'Photos must be 8 MB or smaller.',
            'image.dimensions' => 'Photos must be at least 400 pixels wide.',
        ]);
        unset($data['image'], $data['image_alt'], $data['remove_image']);
        if (($data['kind'] ?? $specialty?->kind ?? 'clinical') === 'clinical') {
            $data['diagnostic'] = null;
        } else {
            $data['diagnostic'] ??= $specialty?->diagnostic ?? 'laboratory';
        }

        return [...$data, 'is_active' => $request->boolean('is_active'), 'show_on_home' => $request->boolean('show_on_home')];
    }
}
