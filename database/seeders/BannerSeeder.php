<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Banner;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // First deactivate any existing banners
        Banner::where('is_active', true)->update(['is_active' => false]);
        
        // Create a sample image or use a placeholder
        $imagePath = $this->createSampleImage();
        
        // Create the sample banner
        Banner::create([
            'image_path' => $imagePath,
            'title' => 'Welcome to JCreations',
            'subtitle' => 'Discover our amazing products',
            'link' => 'https://example.com/products',
            'is_active' => true
        ]);
        
        $this->command->info('Banner seeded successfully!');
    }
    
    /**
     * Create a sample banner image in storage
     *
     * @return string
     */
    private function createSampleImage()
    {
        // Check if the directory exists, if not create it
        if (!Storage::disk('public')->exists('banners')) {
            Storage::disk('public')->makeDirectory('banners');
        }
        
        // URL of a placeholder image - can be replaced with a local file
        $placeholderUrl = 'https://rolfesagri.co.za/wp-content/uploads/2021/02/Zinc-Nitrate-GHS-2024-01-01-980x895.png';
        
        // Generate a unique filename
        $filename = 'banners/sample-banner-' . time() . '.jpg';
        $tempFile = tempnam(sys_get_temp_dir(), 'banner');
        
        // Download the placeholder image
        file_put_contents($tempFile, file_get_contents($placeholderUrl));
        
        // Store the image
        Storage::disk('public')->putFileAs('', new File($tempFile), $filename);
        
        // Clean up temp file
        @unlink($tempFile);
        
        return $filename;
    }
}