<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Make sure the storage directory exists
        if (!Storage::disk('public')->exists('products')) {
            Storage::disk('public')->makeDirectory('products');
        }

        // Get the category IDs
        $birthdayId = Category::where('name', 'Birthday Cakes')->first()->id;
        $weddingId = Category::where('name', 'Wedding Cakes')->first()->id;
        $cupcakesId = Category::where('name', 'Cupcakes')->first()->id;
        $pastriesId = Category::where('name', 'Pastries')->first()->id;
        $seasonalId = Category::where('name', 'Seasonal Specials')->first()->id;
        $customId = Category::where('name', 'Custom Cakes')->first()->id;

        // Products array
        $products = [
            // Birthday Cakes
            [
                'name' => 'Chocolate Birthday Cake',
                'description' => 'Decadent chocolate cake with rich ganache frosting, perfect for birthday celebrations. Multiple layers of moist chocolate sponge filled with chocolate buttercream.',
                'category_id' => $birthdayId,
                'price' => 45.99,
                'discount_percentage' => 0,
                'status' => 'in_stock',
                'images' => [
                    'products/chocolate-birthday-1.jpg' => 'https://images.unsplash.com/photo-1621303837174-89787a7d4729?q=80&w=1000',
                    'products/chocolate-birthday-2.jpg' => 'https://images.unsplash.com/photo-1562777717-dc6984f65a63?q=80&w=1000',
                    'products/chocolate-birthday-3.jpg' => 'https://images.unsplash.com/photo-1535141192574-5d4897c12636?q=80&w=1000',
                ]
            ],
            [
                'name' => 'Vanilla Rainbow Birthday Cake',
                'description' => 'Colorful rainbow cake with vanilla buttercream frosting. Six layers of different colored vanilla sponge create a stunning rainbow effect when cut.',
                'category_id' => $birthdayId,
                'price' => 52.99,
                'discount_percentage' => 5,
                'status' => 'in_stock',
                'images' => [
                    'products/rainbow-birthday-1.jpg' => 'https://images.unsplash.com/photo-1621303837174-89787a7d4729?q=80&w=1000',
                    'products/rainbow-birthday-2.jpg' => 'https://images.unsplash.com/photo-1557925923-cd4648e211a0?q=80&w=1000',
                ]
            ],
            
            // Wedding Cakes
            [
                'name' => 'Classic Tiered Wedding Cake',
                'description' => 'Elegant three-tiered wedding cake with smooth fondant finish and delicate floral decorations. Available in various flavors including vanilla, chocolate, and red velvet.',
                'category_id' => $weddingId,
                'price' => 349.99,
                'discount_percentage' => 0,
                'status' => 'in_stock',
                'images' => [
                    'products/wedding-classic-1.jpg' => 'https://images.unsplash.com/photo-1623227866882-c005c26dfe41?q=80&w=1000',
                    'products/wedding-classic-2.jpg' => 'https://images.unsplash.com/photo-1519654793190-2e8a4806f1f2?q=80&w=1000',
                ]
            ],
            [
                'name' => 'Rustic Naked Wedding Cake',
                'description' => 'Trendy "naked" wedding cake with visible layers, adorned with fresh berries and flowers. Perfect for rustic or boho wedding themes.',
                'category_id' => $weddingId,
                'price' => 299.99,
                'discount_percentage' => 10,
                'status' => 'in_stock',
                'images' => [
                    'products/wedding-rustic-1.jpg' => 'https://images.unsplash.com/photo-1549254018-f29e08be8bbe?q=80&w=1000',
                ]
            ],
            
            // Cupcakes
            [
                'name' => 'Assorted Cupcake Box',
                'description' => 'Box of 12 assorted cupcakes in our most popular flavors including chocolate, vanilla, red velvet, and lemon. Perfect for parties and gifts.',
                'category_id' => $cupcakesId,
                'price' => 28.99,
                'discount_percentage' => 0,
                'status' => 'in_stock',
                'images' => [
                    'products/cupcakes-assorted-1.jpg' => 'https://images.unsplash.com/photo-1599785209707-a456fc1337bb?q=80&w=1000',
                    'products/cupcakes-assorted-2.jpg' => 'https://images.unsplash.com/photo-1563729784474-d77dbb933a9e?q=80&w=1000',
                ]
            ],
            [
                'name' => 'Red Velvet Cupcakes',
                'description' => 'Box of 6 red velvet cupcakes with cream cheese frosting. Rich, moist, and topped with elegant decorations.',
                'category_id' => $cupcakesId,
                'price' => 18.99,
                'discount_percentage' => 0,
                'status' => 'in_stock',
                'images' => [
                    'products/cupcakes-redvelvet-1.jpg' => 'https://images.unsplash.com/photo-1614707267537-b85aaf00c4b7?q=80&w=1000',
                ]
            ],
            
            // Pastries
            [
                'name' => 'French Croissants',
                'description' => 'Set of 4 authentic French butter croissants. Flaky, buttery, and baked to golden perfection.',
                'category_id' => $pastriesId,
                'price' => 12.99,
                'discount_percentage' => 0,
                'status' => 'in_stock',
                'images' => [
                    'products/croissants-1.jpg' => 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?q=80&w=1000',
                    'products/croissants-2.jpg' => 'https://images.unsplash.com/photo-1549903072-7e6e0bedb7fb?q=80&w=1000',
                ]
            ],
            [
                'name' => 'Fruit Tart',
                'description' => 'Beautiful fruit tart with vanilla custard and fresh seasonal fruits. Available in individual size or 9" diameter.',
                'category_id' => $pastriesId,
                'price' => 24.99,
                'discount_percentage' => 0,
                'status' => 'out_of_stock',
                'images' => [
                    'products/fruit-tart-1.jpg' => 'https://images.unsplash.com/photo-1488477304112-4944851de03d?q=80&w=1000',
                ]
            ],
            
            // Seasonal Specials
            [
                'name' => 'Christmas Yule Log',
                'description' => 'Traditional chocolate Yule log cake decorated with festive elements. Rich chocolate sponge rolled with chocolate buttercream and decorated to look like a wooden log.',
                'category_id' => $seasonalId,
                'price' => 39.99,
                'discount_percentage' => 0,
                'status' => 'deactive',
                'images' => [
                    'products/yuletide-log-1.jpg' => 'https://images.unsplash.com/photo-1576618148400-f54bed99fcfd?q=80&w=1000',
                ]
            ],
            [
                'name' => 'Valentine\'s Heart Cake',
                'description' => 'Heart-shaped red velvet cake with cream cheese frosting, decorated with chocolate hearts and roses. Perfect for Valentine\'s Day.',
                'category_id' => $seasonalId,
                'price' => 42.99,
                'discount_percentage' => 0,
                'status' => 'deactive',
                'images' => [
                    'products/valentine-cake-1.jpg' => 'https://images.unsplash.com/photo-1582539305037-aa3668d3ba95?q=80&w=1000',
                ]
            ],
            
            // Custom Cakes
            [
                'name' => 'Custom Photo Cake',
                'description' => 'Personalized cake with your photo printed on edible paper. Available in vanilla or chocolate. Please allow 48 hours notice and provide a high-resolution image.',
                'category_id' => $customId,
                'price' => 65.99,
                'discount_percentage' => 0,
                'status' => 'in_stock',
                'images' => [
                    'products/custom-photo-1.jpg' => 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?q=80&w=1000',
                    'products/custom-photo-2.jpg' => 'https://images.unsplash.com/photo-1605807646983-377bc5a76493?q=80&w=1000',
                ]
            ],
            [
                'name' => 'Corporate Logo Cake',
                'description' => 'Custom cake featuring your company logo, perfect for corporate events and celebrations. Available in various sizes to serve 20-100 people.',
                'category_id' => $customId,
                'price' => 89.99,
                'discount_percentage' => 15,
                'status' => 'in_stock',
                'images' => [
                    'products/corporate-cake-1.jpg' => 'https://images.unsplash.com/photo-1551879400-111a9087cd86?q=80&w=1000',
                ]
            ],
        ];

        foreach ($products as $product) {
            // Download and store the images
            $imagesPaths = [];
            
            foreach ($product['images'] as $path => $url) {
                try {
                    $imageContent = file_get_contents($url);
                    Storage::disk('public')->put($path, $imageContent);
                    $imagesPaths[] = $path;
                } catch (\Exception $e) {
                    $this->command->warn("Could not download image for product '{$product['name']}': {$e->getMessage()}");
                    // Use placeholder if image download fails
                    $imagesPaths[] = 'products/placeholder.jpg';
                }
            }
            
            // Create the product
            Product::create([
                'name' => $product['name'],
                'description' => $product['description'],
                'category_id' => $product['category_id'],
                'price' => $product['price'],
                'discount_percentage' => $product['discount_percentage'],
                'status' => $product['status'],
                'images' => $imagesPaths
            ]);
        }
        
        $this->command->info('Products seeded successfully!');
    }
}