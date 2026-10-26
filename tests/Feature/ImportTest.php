<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\Service;
use App\Services\DirectoryImporter;

class ImportTest extends DirectoryTestCase
{
    public function test_it_imports_facilities_and_services_but_skips_facility_rows(): void
    {
        $this->assertSame(3, Facility::count());
        $this->assertSame(4, Service::count());
        $this->assertNull(Service::find('s-facility-row'));
        $this->assertSame(2, Facility::find('f-legon')->services_count);
    }

    public function test_it_places_facilities_using_the_area_gazetteer(): void
    {
        $legon = Facility::find('f-legon');
        $this->assertSame('area', $legon->geo_precision);
        $this->assertEqualsWithDelta(5.635, $legon->lat, 0.001);
        $this->assertSame('Tema Community 25', Facility::find('f-tema')->locality->name);
    }

    public function test_facility_type_casing_is_merged(): void
    {
        $this->assertSame(['Clinic', 'Primary Hospital'], Facility::distinct()->orderBy('facility_type')->pluck('facility_type')->all());
    }

    public function test_reimport_preserves_admin_edits(): void
    {
        Service::find('s-gyn-legon')->update(['price' => 'GHS 300']);
        app(DirectoryImporter::class)->import(base_path('tests/Fixtures/directory.json'));
        $this->assertSame('GHS 300', Service::find('s-gyn-legon')->price);
    }

    public function test_reimport_keeps_dashboard_edits_and_links_specialties(): void
    {
        Facility::find('f-legon')->forceFill(['phone' => '+233209999999', 'verified_at' => now()])->save();
        app(DirectoryImporter::class)->import(base_path('tests/Fixtures/directory.json'));

        $this->assertSame('+233209999999', Facility::find('f-legon')->phone);
        $this->assertNotNull(Service::find('s-gyn-legon')->specialty_id);
        $this->assertSame('Gynaecologist', Service::find('s-gyn-legon')->specialty->name);
    }
}
