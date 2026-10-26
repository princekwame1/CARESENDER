<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CatalogService;
use App\Models\Facility;
use App\Models\Service;
use App\Models\SiteContent;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class WebTest extends DirectoryTestCase
{
    public function test_public_pages_render(): void
    {
        $this->get('/')->assertOk()->assertSee('Looking for a particular specialist')->assertSee('Find care close to you');
        $this->get('/search?q=gynaecologist')->assertOk()->assertSee('Dansoman Family Clinic');
        $this->get('/facilities/f-legon')->assertOk()->assertSee('Dr. Ama Mensah');
        $this->get('/how-we-verify')->assertOk();
        $this->get('/developers')->assertRedirect('/admin/api');
        $this->get('/about')->assertOk()->assertSee('Making it easier to find care');
        $this->get('/contact')->assertOk()->assertSee('Send us a message');
        $this->get('/credits')->assertOk()->assertSee('Fquasie')->assertSee('CC BY-SA 4.0');
    }

    public function test_location_cookie_personalises_dashboard_and_sorts_results(): void
    {
        $this->withUnencryptedCookie('cs_loc', '5.63500,-0.16000,East Legon')
            ->get('/')->assertOk()->assertSee('Care around East Legon');

        $this->withUnencryptedCookie('cs_loc', '5.63500,-0.16000,East Legon')
            ->get('/search?q=gynaecologist')
            ->assertOk()
            ->assertSee('closest first')
            ->assertSeeInOrder(['Legon Test Hospital', 'Dansoman Family Clinic']);
    }

    public function test_nearby_panel_fragment(): void
    {
        $this->withUnencryptedCookie('cs_loc', '5.63500,-0.16000,')
            ->get('/nearby-panel')->assertOk()->assertSee('Closest to you')->assertDontSee('<html', false);
    }

    public function test_malformed_location_cookie_is_ignored(): void
    {
        $this->withUnencryptedCookie('cs_loc', '<script>')->get('/')->assertOk()->assertSee('Find care close to you');
    }

    public function test_admin_area_requires_admin(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_admin_can_verify_a_facility_from_the_web(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Searches in the last 14 days');
        $this->actingAs($admin)->get('/admin/facilities?status=unverified')->assertOk()->assertSee('Legon Test Hospital');

        $this->actingAs($admin)->put('/admin/facilities/f-legon', [
            'mark_checked' => '1',
            'services' => [
                // Tuesday unticked, Monday and Thursday ticked with times
                's-gyn-legon' => ['specialist_name' => 'Dr. Ama Mensah', 'price' => 'GHS 200', 'available' => '1', 'slots' => [
                    'Monday' => ['on' => '1', 'start' => '09:00', 'end' => '13:00'],
                    'Tuesday' => ['start' => '', 'end' => ''],
                    'Thursday' => ['on' => '1', 'start' => '09:00', 'end' => '13:00'],
                ]],
                's-fbc-legon' => ['price' => 'GHS 80'], // available unticked
            ],
        ])->assertRedirect('/admin/facilities/f-legon/edit')->assertSessionHas('status');

        $gyn = Service::find('s-gyn-legon');
        $this->assertSame(['Monday', 'Thursday'], $gyn->days);
        $this->assertSame('Mon, Thu, 9am to 1pm', $gyn->schedule);
        $this->assertNotNull($gyn->verified_at);
        $this->assertFalse(Service::find('s-fbc-legon')->available);
    }

    public function test_admin_export_streams_json(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        $json = json_decode($this->actingAs($admin)->get('/admin/export')->assertOk()->streamedContent(), true);
        $this->assertCount(3, $json['facilities']);
    }

    public function test_location_outside_coverage_does_not_sort_or_show_distances(): void
    {
        $this->withUnencryptedCookie('cs_loc', '51.50700,-0.12700,') // London
            ->get('/search?q=gynaecologist')
            ->assertOk()
            ->assertDontSee('closest first')
            ->assertDontSee('Approximate, based on neighbourhood location');
    }

    public function test_nearby_panel_explains_a_location_outside_ghana(): void
    {
        $this->withUnencryptedCookie('cs_loc', '59.74132,10.21272,') // Drammen, via a misplaced network
            ->get('/nearby-panel')
            ->assertOk()
            ->assertSee('Your device says you’re outside Ghana')
            ->assertSee('Try again');
    }

    public function test_api_docs_are_only_for_admins(): void
    {
        $this->get('/admin/api')->assertRedirect('/login');
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $this->actingAs($admin)->get('/admin/api')->assertOk()->assertSee('/api/v1/services');
    }

    public function test_contact_form_saves_message_for_admins(): void
    {
        $this->post('/contact', [
            'name' => 'Ama', 'contact' => '0244000000', 'topic' => 'wrong-info',
            'facility_name' => 'Legon Test Hospital', 'message' => 'The gynaecologist now comes on Fridays.',
        ])->assertRedirect('/contact')->assertSessionHas('sent');

        $this->assertDatabaseHas('contact_messages', ['name' => 'Ama', 'topic' => 'wrong-info', 'read_at' => null]);

        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $this->actingAs($admin)->get('/admin/messages')->assertOk()->assertSee('now comes on Fridays')->assertSee('Some information is wrong');
    }

    public function test_contact_form_validates_and_ignores_bots(): void
    {
        $this->post('/contact', ['name' => '', 'contact' => '', 'topic' => 'nope', 'message' => 'hi'])
            ->assertSessionHasErrors(['name', 'contact', 'topic', 'message']);

        $this->post('/contact', ['name' => 'Bot', 'contact' => 'x@y.z', 'topic' => 'question', 'message' => 'Buy cheap things now!!', 'website' => 'spam.example'])
            ->assertRedirect('/contact');
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_admin_can_sign_in_with_username_or_email(): void
    {
        $admin = User::factory()->create(['email' => 'boss@example.com', 'password' => 'Secret#123']);
        $admin->forceFill(['role' => 'admin', 'username' => 'admin'])->save();

        $this->post('/login', ['login' => 'admin', 'password' => 'Secret#123'])->assertRedirect('/admin');
        $this->post('/logout');
        $this->post('/login', ['login' => 'boss@example.com', 'password' => 'Secret#123'])->assertRedirect('/admin');
        $this->post('/logout');
        $this->post('/login', ['login' => 'admin', 'password' => 'wrong'])->assertSessionHasErrors('login');
    }

    public function test_dashboard_aliases_and_friendly_404(): void
    {
        $this->get('/dashboard')->assertRedirect('/admin');
        $this->get('/Admin')->assertRedirect('/admin');
        $this->get('/no-such-page')->assertNotFound()->assertSee('We couldn’t find that page', false);
        $this->get('/admin/no-such-page')->assertNotFound()->assertSee('We couldn’t find that page', false);
    }

    public function test_specialty_searches_show_a_related_photo(): void
    {
        $this->get('/search?q=gynaecologist')->assertOk()->assertSee('spec-gynaecology-1200.webp', false);
        $this->get('/search?q=pediatrician')->assertSee('spec-paediatrics-1200.webp', false);
        $this->get('/search?q=dentist&type=specialist')->assertSee('spec-dentist-1200.webp', false);
        $this->get('/search?q=eye')->assertSee('spec-eye-care-1200.webp', false);
        $this->get('/search?q=urologist&type=specialist')->assertSee('specialist-consultation-1200.webp', false);
        $this->get('/search?type=test')->assertSee('lab-malaria-test-1200.webp', false);
    }

    public function test_bot_searches_are_not_logged(): void
    {
        $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1)')->get('/search?q=dentist')->assertOk();
        $this->withHeader('User-Agent', 'Mozilla/5.0 HeadlessChrome/141.0')->get('/search?q=dentist')->assertOk();
        $this->assertDatabaseCount('search_logs', 0);

        $this->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 14) Chrome/141.0 Mobile')->get('/search?q=dentist')->assertOk();
        $this->assertDatabaseCount('search_logs', 1);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        return $admin;
    }

    public function test_admin_sets_opening_hours_and_rejects_bad_times(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->put('/admin/facilities/f-legon', ['opening_hours' => [
            'Monday' => ['on' => '1', 'open' => '08:00', 'close' => '17:00'],
            'Tuesday' => ['on' => '1', 'open' => '08:00', 'close' => '17:00'],
            'Saturday' => ['on' => '1', 'open' => '09:00', 'close' => '13:00'],
        ]])->assertSessionHasNoErrors();
        $this->assertSame('Mon, Tue, 8am to 5pm · Sat, 9am to 1pm', Facility::find('f-legon')->hours);
        $this->get('/facilities/f-legon')->assertSee('Mon, Tue, 8am to 5pm');

        $this->actingAs($admin)->put('/admin/facilities/f-legon', ['opening_hours' => [
            'Monday' => ['on' => '1', 'open' => '17:00', 'close' => '08:00'],
        ]])->assertSessionHasErrors('opening_hours.Monday');
    }

    public function test_admin_adds_a_facility_and_picks_services_from_the_list(): void
    {
        $admin = $this->admin();
        $area = Area::where('slug', 'osu')->firstOrFail();
        $cardio = Specialty::create(['name' => 'Cardiologist', 'slug' => 'cardiologist', 'kind' => 'clinical']);
        $consult = $cardio->catalogServices()->create(['name' => 'Cardiologist', 'type' => 'specialist']);
        $haem = Specialty::where('name', 'Haematology')->firstOrFail(); // test group built by the importer
        $fbc = CatalogService::where('name', 'Full Blood Count')->firstOrFail();

        $this->actingAs($admin)->post('/admin/facilities', [
            'name' => 'Osu Heart Clinic', 'area_id' => $area->id, 'phone' => '+233200000099', 'is_active' => '1',
            'diagnostic_categories' => ['laboratory'],
        ])->assertRedirect();
        $facility = Facility::where('name', 'Osu Heart Clinic')->firstOrFail();
        $this->assertSame('Osu', $facility->area);
        $this->assertTrue($facility->has_laboratory);

        // The picker lists the catalogue grouped by specialty
        $this->actingAs($admin)->get("/admin/facilities/{$facility->id}/edit")->assertOk()->assertSee('Add services')->assertSee('Specialists')->assertSee('Haematology')->assertSee('Full Blood Count');

        $this->actingAs($admin)->post("/admin/facilities/{$facility->id}/services", ['catalog_ids' => [$consult->id, $fbc->id]])
            ->assertSessionHas('status', 'Added 2 services. Set prices and times below.');
        // Adding the same ones again does nothing
        $this->actingAs($admin)->post("/admin/facilities/{$facility->id}/services", ['catalog_ids' => [$fbc->id]])
            ->assertSessionHas('status', 'Those services are already listed here.');

        $listing = $facility->services()->where('catalog_service_id', $fbc->id)->firstOrFail();
        $this->assertSame('test', $listing->type);
        $this->assertSame('Haematology', $listing->lab_category);
        $this->assertSame($haem->id, $listing->specialty_id);

        // Set the specialist's times on the facility page
        $doc = $facility->services()->where('catalog_service_id', $consult->id)->firstOrFail();
        $this->actingAs($admin)->put("/admin/facilities/{$facility->id}", ['services' => [
            $doc->id => ['available' => '1', 'specialist_name' => 'Dr. Kofi Owusu', 'slots' => ['Wednesday' => ['on' => '1', 'start' => '14:00', 'end' => '18:00']]],
            $listing->id => ['available' => '1', 'price' => 'GHS 120'],
        ]])->assertSessionHasNoErrors();

        $this->get('/search?q=cardiologist')->assertSee('Osu Heart Clinic')->assertSee('Wed, 2pm to 6pm');
        $this->getJson('/api/v1/services?q=fbc&city=Accra')->assertJsonFragment(['price' => 'GHS 120']);
        $this->assertSame(2, $facility->fresh()->services_count);

        $this->actingAs($admin)->delete("/admin/facilities/{$facility->id}/services/{$listing->id}")->assertRedirect();
        $this->assertSame(1, $facility->fresh()->services_count);
    }

    public function test_service_list_rename_and_move_update_facility_listings(): void
    {
        $admin = $this->admin();
        $fbc = CatalogService::where('name', 'Full Blood Count')->firstOrFail();
        $general = Specialty::create(['name' => 'Blood tests', 'slug' => 'blood-tests', 'kind' => 'diagnostic', 'diagnostic' => 'laboratory']);

        $this->actingAs($admin)->get('/admin/facilities/f-legon/edit')->assertOk()->assertSee('Add services');
        $this->actingAs($admin)->get('/admin/services')->assertOk()->assertSee('Full Blood Count')->assertSee('Haematology');
        $this->actingAs($admin)->put("/admin/services/{$fbc->id}", ['name' => 'Full blood count (FBC)', 'specialty_id' => $general->id, 'is_active' => '1'])
            ->assertSessionHas('status');

        $listing = Service::find('s-fbc-legon');
        $this->assertSame('Full blood count (FBC)', $listing->service);
        $this->assertSame('Blood tests', $listing->lab_category);
        $this->getJson('/api/v1/services?q=cbc')->assertJsonPath('data.0.service', 'Full blood count (FBC)');

        // In use, so it can't be deleted; a new unused one can
        $this->actingAs($admin)->delete("/admin/services/{$fbc->id}")->assertSessionHas('status');
        $this->assertModelExists($fbc);
        $this->actingAs($admin)->post('/admin/services', ['name' => 'Pap smear', 'type' => 'test', 'specialty_id' => $general->id])->assertSessionHas('status');
        $pap = CatalogService::where('name', 'Pap smear')->firstOrFail();
        $this->actingAs($admin)->delete("/admin/services/{$pap->id}")->assertSessionHas('status');
        $this->assertModelMissing($pap);
    }

    public function test_hidden_facilities_disappear_from_the_public_site(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->patch('/admin/facilities/f-dansoman/toggle')->assertRedirect();
        auth()->logout();

        $this->get('/facilities/f-dansoman')->assertNotFound();
        $this->get('/search?q=gynaecologist')->assertDontSee('Dansoman Family Clinic');
        $this->getJson('/api/v1/facilities/f-dansoman')->assertNotFound();
        $this->getJson('/api/v1/meta')->assertJsonPath('data.totals.facilities', 2);

        $this->actingAs($admin)->get('/facilities/f-dansoman')->assertOk(); // admins can still preview
        $this->actingAs($admin)->delete('/admin/facilities/f-dansoman')->assertRedirect('/admin/facilities');
        $this->assertDatabaseMissing('services', ['id' => 's-gyn-dansoman']);
    }

    public function test_admin_manages_specialties(): void
    {
        $admin = $this->admin();
        // The importer links specialist listings to the specialties list automatically.
        $gyn = Specialty::where('name', 'Gynaecologist')->firstOrFail();
        $this->assertSame($gyn->id, Service::find('s-gyn-dansoman')->specialty_id);

        $this->actingAs($admin)->get('/admin/specialties')->assertOk()->assertSee('Gynaecologist');
        $this->actingAs($admin)->put("/admin/specialties/{$gyn->id}", ['name' => 'Obstetrician & Gynaecologist', 'is_active' => '1', 'show_on_home' => '1'])->assertRedirect();
        $this->assertSame('Obstetrician & Gynaecologist', Service::find('s-gyn-legon')->service);
        $this->get('/')->assertSee('Obstetrician &amp; Gynaecologist', false);

        $this->actingAs($admin)->delete("/admin/specialties/{$gyn->id}")->assertSessionHas('status');
        $this->assertDatabaseHas('specialties', ['id' => $gyn->id]); // still used, so kept
    }

    public function test_admin_edits_website_content_page_by_page(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin/content')->assertRedirect('/admin/content/general');
        $this->actingAs($admin)->get('/admin/content/general')->assertOk()->assertSee('Announcement across the top of every page');
        $this->actingAs($admin)->get('/admin/content/home')->assertOk()->assertSee('Step 1')->assertSee('data-modal-open="edit-section-hero"', false);
        $this->actingAs($admin)->get('/admin/content/nope')->assertNotFound();

        $this->actingAs($admin)->put('/admin/content/general', ['content' => ['site__notice' => 'We are closed on 6 March for Independence Day.']])->assertSessionHas('status');
        $this->actingAs($admin)->put('/admin/content/home', ['content' => [
            'home__hero_title' => "Care you can count on.\nIn Accra and Tema.",
            'home__step1_title' => 'Search in plain words',
        ]])->assertSessionHas('status');
        $this->actingAs($admin)->put('/admin/content/contact', ['content' => ['contact__phone' => '+233302000000']])->assertSessionHas('status');
        $this->actingAs($admin)->put('/admin/content/verify', ['content' => ['verify__card3_title' => 'Closures']])->assertSessionHas('status');

        $this->get('/')->assertSee('Care you can count on.<br />', false)->assertSee('closed on 6 March')->assertSee('Search in plain words');
        $this->get('/contact')->assertSee('+233302000000');
        $this->get('/how-we-verify')->assertSee('Closures');
        $this->actingAs($admin)->put('/admin/content/contact', ['content' => ['contact__email' => 'not-an-email']])->assertSessionHasErrors('content.contact__email');
    }

    public function test_admin_uploads_and_resets_page_photos(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $file = UploadedFile::fake()->image('team.jpg', 1600, 1000);

        $this->actingAs($admin)->put('/admin/content/about', [
            'content' => ['about__image' => $file], 'alt' => ['about__image' => 'Our team at the office'],
        ])->assertSessionHasNoErrors();

        $saved = json_decode(SiteContent::find('about.image')->value, true);
        Storage::disk('public')->assertExists($saved['path'].'-640.webp');
        Storage::disk('public')->assertExists($saved['path'].'-1200.webp');
        $this->assertSame(1200, $saved['w']);
        $this->get('/about')->assertSee('/storage/'.$saved['path'].'-1200.webp', false)->assertSee('Our team at the office');

        // Too small, and not an image
        $this->actingAs($admin)->put('/admin/content/about', ['content' => ['about__image' => UploadedFile::fake()->image('tiny.png', 100, 100)]])->assertSessionHasErrors();
        $this->actingAs($admin)->put('/admin/content/about', ['content' => ['about__image' => UploadedFile::fake()->create('notes.pdf', 10)]])->assertSessionHasErrors();

        // Back to the default photo; files are cleaned up
        $this->actingAs($admin)->put('/admin/content/about', ['reset' => ['about__image' => '1']])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($saved['path'].'-1200.webp');
        $this->get('/about')->assertSee('/images/photos/health-fair-ghana-1200.webp', false);
    }

    public function test_specialty_photo_is_used_on_its_search_results(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $gyn = Specialty::where('name', 'Gynaecologist')->firstOrFail();

        $this->actingAs($admin)->put("/admin/specialties/{$gyn->id}", [
            'name' => 'Gynaecologist', 'is_active' => '1',
            'image' => UploadedFile::fake()->image('clinic.jpg', 1200, 800), 'image_alt' => 'Our partner maternity clinic',
        ])->assertSessionHasNoErrors();

        $path = $gyn->fresh()->image['path'];
        $this->get('/search?q=gynaecologists')->assertSee('/storage/'.$path.'-1200.webp', false)->assertSee('Our partner maternity clinic');
    }

    public function test_dashboard_pages_require_admin(): void
    {
        foreach (['/admin/facilities/create', '/admin/specialties', '/admin/services', '/admin/content/home'] as $url) {
            $this->get($url)->assertRedirect('/login');
            $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
            auth()->logout();
        }
    }

    public function test_rich_text_is_formatted_and_cleaned(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->put('/admin/content/home', ['content' => [
            'home__hero_text' => '<p>We call <strong>every week</strong>. <a href="https://ghs.gov.gh" onclick="steal()">GHS</a></p><script>alert(1)</script><ul><li>Labs</li></ul>',
            'home__steps_title' => '<b>Plain</b> heading',
        ]])->assertSessionHas('status');

        $saved = SiteContent::find('home.hero_text')->value;
        $this->assertStringContainsString('<strong>every week</strong>', $saved);
        $this->assertStringContainsString('href="https://ghs.gov.gh" target="_blank" rel="noopener"', $saved);
        $this->assertStringNotContainsString('script', $saved);
        $this->assertStringNotContainsString('onclick', $saved);
        $this->assertSame('Plain heading', SiteContent::find('home.steps_title')->value);

        $this->get('/')->assertSee('<strong>every week</strong>', false)->assertSee('<li>Labs</li>', false);
        $this->actingAs($admin)->get('/admin/content/home')->assertSee('data-rich', false);
    }

    public function test_admin_pins_a_facility_with_a_google_maps_link(): void
    {
        $admin = $this->admin();
        $link = 'https://www.google.com/maps/place/Legon+Test+Hospital/@5.65,-0.18,15z/data=!3m1!4b1!4m6!3m5!1s0x0:0x1!8m2!3d5.651234!4d-0.183456';

        $this->actingAs($admin)->put('/admin/facilities/f-legon', ['maps_url' => $link, 'coordinates' => ''])->assertSessionHasNoErrors();
        $f = Facility::find('f-legon');
        $this->assertSame('exact', $f->geo_precision);
        $this->assertEqualsWithDelta(5.651234, $f->lat, 0.000001);
        $this->assertSame($link, $f->directionsUrl());
        $this->get('/facilities/f-legon')->assertSee('Exact location')->assertSee($link, false);
        $this->getJson('/api/v1/facilities/f-legon')->assertJsonPath('data.location.precision', 'exact')->assertJsonPath('data.location.maps_url', $link);

        // Typed coordinates win; clearing them returns to the area centre
        $this->actingAs($admin)->put('/admin/facilities/f-legon', ['coordinates' => '5.6400, -0.1700'])->assertSessionHasNoErrors();
        $this->assertEqualsWithDelta(5.64, Facility::find('f-legon')->lat, 0.000001);
        $this->actingAs($admin)->put('/admin/facilities/f-legon', ['coordinates' => ''])->assertSessionHasNoErrors();
        $this->assertSame('area', Facility::find('f-legon')->geo_precision);

        // Bad input is rejected
        $this->actingAs($admin)->put('/admin/facilities/f-legon', ['maps_url' => 'https://example.com/where'])->assertSessionHasErrors('maps_url');
        $this->actingAs($admin)->put('/admin/facilities/f-legon', ['coordinates' => 'near the junction'])->assertSessionHasErrors('coordinates');
    }

    public function test_admin_account_menu_and_profile(): void
    {
        $admin = $this->admin();
        $admin->forceFill(['username' => 'admin', 'password' => 'Secret#123'])->save();

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Account menu')->assertSee('/admin/profile')->assertSee('Sign out');
        $this->actingAs($admin)->get('/admin/profile')->assertOk()->assertSee('Change password');

        $this->actingAs($admin)->put('/admin/profile', ['name' => 'Ama Owusu', 'username' => 'ama', 'email' => 'ama@caresenda.test'])->assertSessionHas('status');
        $this->assertSame('ama', $admin->fresh()->username);
        $this->actingAs($admin)->put('/admin/profile', ['name' => 'X', 'username' => 'has space', 'email' => 'bad'])->assertSessionHasErrors(['username', 'email']);

        $this->actingAs($admin)->put('/admin/profile/password', ['current_password' => 'wrong', 'password' => 'NewPass123', 'password_confirmation' => 'NewPass123'])
            ->assertSessionHasErrors('current_password');
        $this->actingAs($admin)->put('/admin/profile/password', ['current_password' => 'Secret#123', 'password' => 'NewPass123', 'password_confirmation' => 'NewPass123'])
            ->assertSessionHas('status');
        $this->assertTrue(Hash::check('NewPass123', $admin->fresh()->password));
    }

    public function test_admin_uploads_and_removes_a_profile_photo(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/profile/avatar', ['avatar' => UploadedFile::fake()->image('me.jpg', 800, 600)])->assertSessionHas('status');
        $path = $admin->fresh()->avatar_path;
        Storage::disk('public')->assertExists($path);
        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame([256, 256], [$w, $h]); // centre-cropped square
        $this->actingAs($admin)->get('/admin')->assertSee('/storage/'.$path, false);

        $this->actingAs($admin)->post('/admin/profile/avatar', ['avatar' => UploadedFile::fake()->create('cv.pdf', 20)])->assertSessionHasErrors('avatar');

        $this->actingAs($admin)->post('/admin/profile/avatar', ['remove' => '1'])->assertSessionHas('status');
        $this->assertNull($admin->fresh()->avatar_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_flash_messages_and_errors_reach_the_toast_area(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->withSession(['status' => 'Saved it.'])->get('/admin')
            ->assertSee('id="toasts"', false)->assertSee('"type":"success","message":"Saved it."', false);

        $this->actingAs($admin)->from('/admin/profile')->followingRedirects()
            ->put('/admin/profile', ['name' => '', 'username' => 'ok', 'email' => 'bad'])
            ->assertSee('"type":"error"', false)->assertSee('more problem to fix', false);

        $this->get('/')->assertSee('id="toasts"', false); // public site too
    }

    public function test_saving_one_content_section_leaves_the_others_alone(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->put('/admin/content/home', ['content' => ['home__hero_title' => 'First headline', 'home__steps_title' => 'Steps heading']]);
        $this->actingAs($admin)->put('/admin/content/home', ['_modal' => 'edit-section-hero', 'content' => ['home__hero_title' => 'Second headline']]);

        $this->assertSame('Second headline', SiteContent::find('home.hero_title')->value);
        $this->assertSame('Steps heading', SiteContent::find('home.steps_title')->value);
    }

    public function test_facility_sections_save_without_touching_each_other(): void
    {
        $admin = $this->admin();
        Facility::find('f-legon')->forceFill(['diagnostic_categories' => ['laboratory'], 'has_laboratory' => true])->save();

        // Saving opening hours alone must not clear the test categories from the details form.
        $this->actingAs($admin)->put('/admin/facilities/f-legon', ['_modal' => 'edit-hours', 'opening_hours' => ['Monday' => ['on' => '1', 'open' => '08:00', 'close' => '16:00']]])
            ->assertSessionHasNoErrors();
        $this->assertSame(['laboratory'], Facility::find('f-legon')->diagnostic_categories);

        // "I checked this today" only records the check.
        $this->actingAs($admin)->put('/admin/facilities/f-legon', ['mark_checked' => '1'])->assertSessionHas('status', 'Marked as checked today.');
        $this->assertNotNull(Facility::find('f-legon')->verified_at);

        $this->actingAs($admin)->get('/admin/facilities/f-legon/edit')->assertOk()
            ->assertSee('data-modal-open="edit-details"', false)->assertSee('data-modal-open="edit-hours"', false)->assertSee('id="add-services"', false);
        $this->actingAs($admin)->get('/admin/facilities/create')->assertRedirect('/admin/facilities?add=1');
    }
}
