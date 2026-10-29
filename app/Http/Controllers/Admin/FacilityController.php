<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateFacilityServicesRequest;
use App\Models\Area;
use App\Models\Facility;
use App\Models\Service;
use App\Models\Specialty;
use App\Services\FacilityUpdater;
use App\Support\SearchNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FacilityController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'in:'.implode(',', array_keys(config('caresenda.cities')))],
            'status' => ['nullable', 'in:unverified,verified,no-phone,active,hidden'],
        ]);

        $facilities = Facility::query()
            ->when($filters['q'] ?? null, function ($q, $term) {
                foreach (SearchNormalizer::words($term) as $w) {
                    $q->where('search_text', 'like', '%'.addcslashes($w, '%_\\').'%');
                }
            })
            ->inCity($filters['city'] ?? null)
            ->when(($filters['status'] ?? null) === 'unverified', fn ($q) => $q->whereNull('verified_at')->whereNotNull('phone'))
            ->when(($filters['status'] ?? null) === 'verified', fn ($q) => $q->whereNotNull('verified_at'))
            ->when(($filters['status'] ?? null) === 'no-phone', fn ($q) => $q->whereNull('phone'))
            ->when(($filters['status'] ?? null) === 'active', fn ($q) => $q->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'hidden', fn ($q) => $q->where('is_active', false))
            ->orderByRaw('verified_at is not null')
            ->orderByDesc('services_count')
            ->paginate(25)
            ->withQueryString();

        return view('admin.facilities.index', [...compact('facilities', 'filters'), ...$this->formOptions()]);
    }

    /** Adding happens in a modal on the list page. */
    public function create(): RedirectResponse
    {
        return redirect()->route('admin.facilities.index', ['add' => 1]);
    }

    public function store(UpdateFacilityServicesRequest $request, FacilityUpdater $updater): RedirectResponse
    {
        $facility = $updater->create($request->validated());

        return redirect()->route('admin.facilities.edit', $facility)->with('status', 'Facility added. Next, add the services it offers.');
    }

    public function edit(Facility $facility): View
    {
        $facility->load(['services' => fn ($q) => $q->orderBy('type')->orderBy('service')]);

        return view('admin.facilities.edit', [
            'facility' => $facility,
            'specialists' => $facility->services->where('type', 'specialist'),
            'tests' => $facility->services->where('type', 'test'),
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateFacilityServicesRequest $request, Facility $facility, FacilityUpdater $updater): RedirectResponse
    {
        $data = $request->validated();
        // Unchecked checkboxes are missing from the post: treat as "off", but only for the form that has them.
        if ($request->input('_section') === 'details') {
            $data['diagnostic_categories'] ??= [];
        }
        foreach ($data['services'] ?? [] as $id => $svc) {
            $data['services'][$id]['available'] = (bool) ($svc['available'] ?? false);
        }

        $updater->apply($facility, $data, markChecked: $request->boolean('mark_checked'));

        $message = $request->boolean('mark_checked') && count($data) === 1
            ? 'Marked as checked today.'
            : 'Saved. Patients will see the change straight away.';

        return redirect()->route('admin.facilities.edit', $facility)->with('status', $message);
    }

    public function toggle(Facility $facility, FacilityUpdater $updater): RedirectResponse
    {
        $updater->setActive($facility, ! $facility->is_active);

        return back()->with('status', $facility->is_active ? 'Facility is now visible on the website.' : 'Facility is now hidden from the website.');
    }

    public function destroy(Facility $facility, FacilityUpdater $updater): RedirectResponse
    {
        $name = $facility->name;
        $updater->delete($facility);

        return redirect()->route('admin.facilities.index')->with('status', "Deleted {$name}.");
    }

    public function storeService(Request $request, Facility $facility, FacilityUpdater $updater): RedirectResponse
    {
        $ids = $request->validate([
            'catalog_ids' => ['required', 'array', 'min:1'],
            'catalog_ids.*' => ['integer', 'exists:catalog_services,id'],
        ], ['catalog_ids.required' => 'Tick at least one service to add.'])['catalog_ids'];

        $added = $updater->addFromCatalog($facility, array_map('intval', $ids));

        return redirect()->to(route('admin.facilities.edit', $facility).'#specialists')
            ->with('status', $added ? "Added {$added} ".str('service')->plural($added).'. Set prices and times below.' : 'Those services are already listed here.');
    }

    public function destroyService(Facility $facility, Service $service, FacilityUpdater $updater): RedirectResponse
    {
        abort_unless($service->facility_id === $facility->id, 404);
        $updater->removeService($service);

        return back()->with('status', "Removed {$service->service}.");
    }

    /** Streams the full directory without loading it into memory at once. */
    public function export(): StreamedResponse
    {
        return response()->streamDownload(function () {
            echo '{"exportedAt":"'.now()->toIso8601String().'","facilities":[';
            $first = true;
            Facility::with('services')->orderBy('id')->chunk(100, function ($chunk) use (&$first) {
                foreach ($chunk as $facility) {
                    echo ($first ? '' : ','), json_encode($facility->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    $first = false;
                }
            });
            echo ']}';
        }, 'caresenda-directory-'.now()->format('Y-m-d').'.json', ['Content-Type' => 'application/json']);
    }

    private function formOptions(): array
    {
        return [
            'areas' => Area::orderBy('city')->orderBy('name')->get(['id', 'name', 'city'])->groupBy('city'),
            // Catalogue grouped by specialty for the "Add services" picker.
            'catalogue' => Specialty::active()->whereHas('catalogServices', fn ($q) => $q->active())
                ->with(['catalogServices' => fn ($q) => $q->active()->orderBy('sort_order')->orderBy('name')])
                ->orderByRaw("kind = 'diagnostic'")->orderBy('name')->get(),
            'facilityTypes' => Facility::whereNotNull('facility_type')->distinct()->orderBy('facility_type')->pluck('facility_type'),
        ];
    }
}
