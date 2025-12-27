<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

class BannerTest extends TestCase
{

    use RefreshDatabase;

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

    public function test_banner_create()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('banner.jpg');
        $path = $file->store('banners', 'public');
        $banner = Banner::create([
            'path' => $path,
            'link' => 'https://example.com',
            'description' => 'Test Banner',
        ]);
        $this->assertDatabaseHas('banners', ['id' => $banner->id, 'path' => $path]);
        $this->assertEquals('https://example.com', $banner->link);
        $this->assertEquals('Test Banner', $banner->description);
    }

    public function test_banner_read()
    {
        $banner = Banner::factory()->create([
            'link' => 'https://read.com',
            'description' => 'Read Banner',
        ]);
        $found = Banner::find($banner->id);
        $this->assertEquals('https://read.com', $found->link);
        $this->assertEquals('Read Banner', $found->description);
    }

    public function test_banner_update()
    {

        $banner = Banner::factory()->create();
        $banner->update([
            'link' => 'https://new.com',
            'description' => 'New Banner',
        ]);
        $this->assertEquals('https://new.com', $banner->fresh()->link);
        $this->assertEquals('New Banner', $banner->fresh()->description);
    }

    public function test_banner_delete()
    {
        $banner = Banner::factory()->create();
        $banner->delete();
        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
    }
}
