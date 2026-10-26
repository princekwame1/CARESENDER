<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogService;
use App\Models\Specialty;
use App\Services\CatalogSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** The central service list, grouped by specialty. */
class CatalogController extends Controller
{
    public function __construct(private CatalogSync $sync) {}

    public function index(Request $request): View
    {
        $groups = Specialty::query()
            ->with(['catalogServices' => fn ($s) => $s->withCount('listings')->orderBy('sort_order')->orderBy('name')])
            ->orderByRaw("kind = 'diagnostic'")->orderBy('name')
            ->get();

        // Searching and filtering happen in the page; ?q= only prefills the search box.
        return view('admin.catalog', [
            'groups' => $groups,
            'allGroups' => $groups,
            'q' => trim((string) $request->query('q')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $item = CatalogService::create($data);

        return back()->with('status', "Added {$item->name} to {$item->specialty->name}.");
    }

    public function update(Request $request, CatalogService $service): RedirectResponse
    {
        $data = $this->validated($request, $service);
        $service->update($data);
        $updated = $this->sync->syncItem($service);

        return back()->with('status', "Saved {$service->name}".($updated ? " and updated {$updated} facility ".str('listing')->plural($updated) : '').'.');
    }

    public function destroy(CatalogService $service): RedirectResponse
    {
        if ($service->listings()->exists()) {
            return back()->with('status', "{$service->name} is offered by facilities, so it can’t be deleted. Switch it off instead.");
        }
        $service->delete();

        return back()->with('status', "Deleted {$service->name}.");
    }

    private function validated(Request $request, ?CatalogService $service = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190', Rule::unique('catalog_services')->where('type', $request->input('type', $service?->type))->ignore($service)],
            'type' => [$service ? 'sometimes' : 'required', 'in:specialist,test'],
            'specialty_id' => ['required', 'exists:specialties,id'],
            'description' => ['nullable', 'string', 'max:500'],
        ], ['name.unique' => 'That service is already in the list.']);

        return [...$data, 'is_active' => $request->boolean('is_active', ! $service)];
    }
}
