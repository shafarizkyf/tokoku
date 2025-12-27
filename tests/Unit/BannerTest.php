<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use App\Models\Banner;

class BannerTest extends TestCase
{
    public function test_get_url_attribute_returns_full_url()
    {
        $path = 'banners/example.jpg';

        // Mock the Storage facade to return the expected storage path
        Storage::shouldReceive('url')
            ->once()
            ->with($path)
            ->andReturn('/storage/' . $path);

        $banner = new Banner(['path' => $path]);

        $this->assertEquals(url('/storage/' . $path), $banner->url);
    }
}
