<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Make sure the storage directory exists
        if (!Storage::disk('public')->exists('categories')) {
            Storage::disk('public')->makeDirectory('categories');
        }

        // The cake shop categories
        $categories = [
            [
                'name' => 'Birthday Cakes',
                'img' => 'categories/birthday-cakes.jpg',
                'source' => 'https://images.unsplash.com/photo-1562777717-dc6984f65a63?q=80&w=1000'
            ],
            [
                'name' => 'Wedding Cakes',
                'img' => 'categories/wedding-cakes.jpg',
                'source' => 'https://images.unsplash.com/photo-1623227866882-c005c26dfe41?q=80&w=1000'
            ],
            [
                'name' => 'Cupcakes',
                'img' => 'categories/cupcakes.jpg',
                'source' => 'https://images.unsplash.com/photo-1599785209707-a456fc1337bb?q=80&w=1000'
            ],
            [
                'name' => 'Pastries',
                'img' => 'categories/pastries.jpg',
                'source' => 'https://images.unsplash.com/photo-1517433367423-c7e5b0f35086?q=80&w=1000'
            ],
            [
                'name' => 'Seasonal Specials',
                'img' => 'categories/seasonal-specials.jpg',
                'source' => 'https://images.unsplash.com/photo-1576618148400-f54bed99fcfd?q=80&w=1000'
            ],
            [
                'name' => 'Custom Cakes',
                'img' => 'categories/custom-cakes.jpg',
                'source' => 'https://images.unsplash.com/photo-1535141192574-5d4897c12636?q=80&w=1000'
            ],
        ];

        foreach ($categories as $category) {
            // Download the image from the source URL
            try {
                $imageContent = file_get_contents($category['source']);
                $path = $category['img'];
                
                // Store the image in the public storage
                Storage::disk('public')->put($path, $imageContent);
                
                // Create the category
                Category::create([
                    'name' => $category['name'],
                    'img' => $path
                ]);
            } catch (\Exception $e) {
                // If image download fails, use a placeholder
                $this->command->warn("Could not download image for category '{$category['name']}': {$e->getMessage()}");
                
                Category::create([
                    'name' => $category['name'],
                    'img' => 'categories/placeholder.jpg'
                ]);
            }
        }
        
        $this->command->info('Categories seeded successfully!');
    }
}