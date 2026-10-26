<?php

namespace App\Services;

use App\Models\CatalogService;
use App\Models\Service;
use App\Models\Specialty;
use App\Support\SearchText;

/** Keeps facility listings in step with their catalogue entry and group. */
class CatalogSync
{
    public function __construct(private DirectoryCache $cache) {}

    /** Attributes a listing takes from its catalogue entry. */
    public function attributesFor(CatalogService $item): array
    {
        $group = $item->specialty;

        return [
            'catalog_service_id' => $item->id,
            'type' => $item->type,
            'service' => $item->name,
            'specialty_id' => $group->id,
            'lab_category' => $item->type === 'test' ? $group->name : null,
            'diagnostic_categories' => $item->type === 'test' && $group->diagnostic ? [$group->diagnostic] : [],
        ];
    }

    /** Push a catalogue entry's name/group to every listing that uses it. */
    public function syncItem(CatalogService $item): int
    {
        $attrs = $this->attributesFor($item->load('specialty'));
        $count = 0;
        Service::with('facility')->where('catalog_service_id', $item->id)->chunkById(500, function ($listings) use ($attrs, &$count) {
            foreach ($listings as $s) {
                $s->forceFill($attrs);
                $s->search_text = SearchText::service($s, $s->facility);
                $s->save();
                $count++;
            }
        });
        $this->cache->flush();

        return $count;
    }

    public function syncGroup(Specialty $group): void
    {
        $group->catalogServices->each(fn (CatalogService $item) => $this->syncItem($item));
    }
}
