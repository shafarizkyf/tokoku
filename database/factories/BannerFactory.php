<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Banner>
 */
class BannerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $file = UploadedFile::fake()->create('banner.jpg', 100, 'image/jpeg');
        $path = $file->store('banners', 'public');
        return [
            'path' => $path,
            'link' => 'https://example.com',
            'description' => 'Test Banner',
        ];
    }
}
