<?php

namespace Tests\Feature\Api;

use Tests\Feature\DirectoryTestCase;

class PublicApiTest extends DirectoryTestCase
{
    public function test_meta_describes_filters_and_totals(): void
    {
        $this->getJson('/api/v1/meta')
            ->assertOk()
            ->assertHeader('ETag')
            ->assertJsonPath('data.cities', ['Accra', 'Tema'])
            ->assertJsonPath('data.totals.facilities', 3)
            ->assertJsonPath('data.payments_enabled', false);
    }

    public function test_geo_resolve_finds_the_nearest_area(): void
    {
        $this->getJson('/api/v1/geo/resolve?lat='.self::LAT.'&lng='.self::LNG)
            ->assertOk()
            ->assertJsonPath('data.area', 'East Legon')
            ->assertJsonPath('data.city', 'Accra')
            ->assertJsonPath('data.in_coverage', true)
            ->assertJsonPath('data.nearby.facilities', 1);
    }

    public function test_geo_resolve_flags_locations_outside_coverage(): void
    {
        $this->getJson('/api/v1/geo/resolve?lat=6.6885&lng=-1.6244') // Kumasi
            ->assertOk()
            ->assertJsonPath('data.in_coverage', false)
            ->assertJsonPath('data.area', null)
            ->assertJsonPath('data.nearby', null);
    }

    public function test_geo_resolve_validates_coordinates(): void
    {
        $this->getJson('/api/v1/geo/resolve?lat=200&lng=0')->assertUnprocessable()->assertJsonValidationErrors('lat');
    }

    public function test_services_are_sorted_by_distance_when_location_is_given(): void
    {
        $this->getJson('/api/v1/services?q=gynecologists&lat='.self::LAT.'&lng='.self::LNG)
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.facility.id', 'f-legon')
            ->assertJsonPath('data.1.facility.id', 'f-dansoman')
            ->assertJsonPath('data.0.distance_km', 0)
            ->assertJsonPath('summary.services', 2);
    }

    public function test_search_aliases_match_lab_abbreviations(): void
    {
        $this->getJson('/api/v1/services?q=CBC')
            ->assertOk()
            ->assertJsonPath('data.0.id', 's-fbc-legon')
            ->assertJsonPath('data.0.verification.code', 'confirmed');
    }

    public function test_radius_city_day_and_diagnostic_filters(): void
    {
        $origin = 'lat='.self::LAT.'&lng='.self::LNG;
        $this->getJson("/api/v1/services?$origin&radius=5")->assertJsonPath('meta.total', 2);
        $this->getJson('/api/v1/services?city=Tema')->assertJsonPath('meta.total', 1);
        $this->getJson('/api/v1/services?day=Monday')->assertJsonPath('data.0.id', 's-gyn-dansoman');
        $this->getJson('/api/v1/services?diagnostic=imaging')->assertJsonPath('data.0.id', 's-us-tema');
        $this->getJson('/api/v1/services?confirmed=1')->assertJsonPath('meta.total', 1);
    }

    public function test_nearby_facilities(): void
    {
        $this->getJson('/api/v1/facilities/nearby?lat='.self::LAT.'&lng='.self::LNG.'&radius=30')
            ->assertOk()
            ->assertJsonPath('data.0.id', 'f-legon')
            ->assertJsonPath('data.1.id', 'f-dansoman')
            ->assertJsonCount(3, 'data');
    }

    public function test_facility_detail_includes_services_and_sources(): void
    {
        $this->getJson('/api/v1/facilities/f-legon?lat='.self::LAT.'&lng='.self::LNG)
            ->assertOk()
            ->assertJsonPath('data.name', 'Legon Test Hospital')
            ->assertJsonCount(2, 'data.services')
            ->assertJsonPath('data.sources.facility', 'https://example.test/a')
            ->assertJsonPath('data.distance_km', 0);

        $this->getJson('/api/v1/facilities/missing')->assertNotFound();
    }

    public function test_dashboard_returns_home_screen_in_one_call(): void
    {
        $this->getJson('/api/v1/dashboard?lat='.self::LAT.'&lng='.self::LNG)
            ->assertOk()
            ->assertJsonPath('data.location.area', 'East Legon')
            ->assertJsonPath('data.nearest_facilities.0.id', 'f-legon')
            ->assertJsonPath('data.totals.services', 4)
            ->assertJsonPath('data.specialty_images.Gynaecologist', url('/images/photos/spec-gynaecology-640.webp'));
    }

    public function test_specialties_list_names_counts_and_photos(): void
    {
        $this->getJson('/api/v1/specialties')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Gynaecologist')
            ->assertJsonPath('data.0.listings', 2)
            ->assertJsonPath('data.0.image_url', url('/images/photos/spec-gynaecology-640.webp'));
    }

    public function test_map_markers_are_compact(): void
    {
        $this->getJson('/api/v1/map/markers?city=Tema')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.0', 'f-tema');
    }

    public function test_suggestions_return_services_then_facilities(): void
    {
        $this->getJson('/api/v1/suggest?q=gyn')
            ->assertOk()
            ->assertJsonPath('data.0.label', 'Gynaecologist')
            ->assertJsonPath('data.0.kind', 'specialist')
            ->assertJsonPath('data.0.meta', '2 places');

        $this->getJson('/api/v1/suggest?q=legon')
            ->assertOk()
            ->assertJsonFragment(['kind' => 'facility', 'id' => 'f-legon']);

        $this->getJson('/api/v1/suggest?q=a')->assertUnprocessable();
    }
}
