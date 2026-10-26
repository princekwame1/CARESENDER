<?php

namespace Tests\Feature;

use App\Services\DirectoryImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class DirectoryTestCase extends TestCase
{
    use RefreshDatabase;

    /** East Legon, Accra */
    protected const LAT = 5.6350;

    protected const LNG = -0.1600;

    protected function setUp(): void
    {
        parent::setUp();
        app(DirectoryImporter::class)->import(base_path('tests/Fixtures/directory.json'));
    }
}
