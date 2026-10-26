<?php

namespace Tests\Unit;

use App\Support\MapLink;
use PHPUnit\Framework\TestCase;

class MapLinkTest extends TestCase
{
    public function test_reads_coordinates_from_google_maps_links_and_text(): void
    {
        $this->assertSame([5.6037, -0.187], MapLink::coordinates('https://www.google.com/maps/@5.6037,-0.1870,17z'));
        $this->assertSame([5.635123, -0.160456], MapLink::coordinates('https://www.google.com/maps/place/Clinic/@5.6,-0.1,15z/data=!3m1!4b1!4m6!3m5!1s0x0:0x1!8m2!3d5.635123!4d-0.160456'));
        $this->assertSame([5.55, -0.2], MapLink::coordinates('https://maps.google.com/?q=5.55,-0.2'));
        $this->assertSame([5.6037, -0.187], MapLink::coordinates(' 5.6037 , -0.1870 '));
        $this->assertNull(MapLink::coordinates('https://maps.app.goo.gl/AbCdEf123')); // short links carry no coordinates
        $this->assertNull(MapLink::coordinates('East Legon'));
        $this->assertNull(MapLink::coordinates('95, 200'));
    }

    public function test_recognises_google_maps_links(): void
    {
        $this->assertTrue(MapLink::isGoogleMaps('https://maps.app.goo.gl/AbCdEf123'));
        $this->assertTrue(MapLink::isGoogleMaps('https://www.google.com/maps/place/Ridge+Hospital'));
        $this->assertTrue(MapLink::isGoogleMaps('https://www.google.com.gh/maps/@5.6,-0.18,15z'));
        $this->assertFalse(MapLink::isGoogleMaps('https://example.com/maps'));
        $this->assertFalse(MapLink::isGoogleMaps('javascript:alert(1)'));
    }
}
