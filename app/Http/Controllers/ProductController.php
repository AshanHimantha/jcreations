<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Products",
 *     description="API endpoints for product management"
 * )
 */
class ProductController extends Controller
{
    /**
     * Display a listing of products.
     * 
     * @OA\Get(
     *     path="/api/products/{limit?}",
     *     summary="Get all products with optional limit",
     *     description="Returns a list of all active products (public endpoint, default limit is 20)",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="limit",
     *         in="path",
     *         description="Maximum number of products to return (default: 20, max: 100)",
     *         required=false,
     *         @OA\Schema(type="integer", default=20, minimum=1, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of products",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Product")
     *         )
     *     )
     * )
     */
    public function index($limit = 20)
    {
        // Validate and constrain the limit
        $limit = is_numeric($limit) ? (int)$limit : 20;  
        $limit = min(max($limit, 1), 100);  // Between 1 and 100
        
        $products = Product::with('category')
                    ->where('status', '!=', 'deactive')
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
        
        return response()->json($products);
    }

    /**
     * Display a listing of all products including deactivated ones (admin only).
     * 
     * @OA\Get(
     *     path="/api/admin/products/limit/{limit}",
     *     summary="Get all products (including deactivated ones)",
     *     description="Returns a list of all products including deactivated ones (admin only)",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="path",
     *         description="Maximum number of products to return (no maximum for admin)",
     *         required=true,
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of all products",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Product")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function adminIndex($limit = null)
    {
        if ($limit !== null) {
            // Validate limit is a positive integer
            $limit = is_numeric($limit) ? (int)$limit : 20;
            $limit = max($limit, 1);  // Only enforce minimum value of 1
            
            $products = Product::with('category')
                       ->limit($limit)
                       ->orderBy('created_at', 'desc')
                       ->get();
        } else {
            // Original behavior - get all products with no limit
            $products = Product::with('category')->get();
        }
        
        return response()->json($products);
    }

    /**
     * Store a newly created product.
     * 
     * @OA\Post(
     *     path="/api/admin/products",
     *     summary="Create a new product",
     *     description="Creates a new product with details and images",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","description","category_id","price","status","image1"},
     *                 @OA\Property(property="name", type="string", maxLength=255, example="Smartphone XYZ"),
     *                 @OA\Property(property="description", type="string", example="Latest smartphone with advanced features"),
     *                 @OA\Property(property="character_count", type="integer", minimum=0, example=50, description="Character count of description (auto-calculated if not provided)"),
     *                 @OA\Property(property="image1", type="string", format="binary", description="First product image (required)"),
     *                 @OA\Property(property="image2", type="string", format="binary", description="Second product image (optional)"),
     *                 @OA\Property(property="image3", type="string", format="binary", description="Third product image (optional)"),
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="price", type="number", format="float", minimum=0, example=999.99),
     *                 @OA\Property(property="discount_percentage", type="number", format="float", minimum=0, maximum=100, example=10.5),
     *                 @OA\Property(property="discounted_price", type="number", format="float", minimum=0, example=899.99, description="Direct discounted price (alternative to discount_percentage)"),
     *                 @OA\Property(property="status", type="string", enum={"deactive", "in_stock", "out_of_stock"}, example="in_stock"),
     *                 @OA\Property(property="daily_deals", type="string", enum={"active", "deactive"}, example="deactive", description="Daily deals status")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(
     *         response=422, 
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error creating product"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'character_count' => 'nullable|integer|min:0',
                'category_id' => 'required|integer|exists:categories,id',
                'price' => 'required|numeric|min:0',
                'discount_percentage' => 'nullable|numeric|min:0|max:100',
                'discounted_price' => 'nullable|numeric|min:0',
                'status' => ['required', Rule::in(['deactive', 'in_stock', 'out_of_stock'])],
                'daily_deals' => ['nullable', Rule::in(['active', 'deactive'])],
                'image1' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                'image2' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'image3' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Calculate character count if not provided
            if (!isset($validated['character_count']) && isset($validated['description'])) {
                $validated['character_count'] = strlen($validated['description'] ?? '');
            }

            // Validate that discounted_price is less than regular price
            if (isset($validated['discounted_price']) && $validated['discounted_price'] >= $validated['price']) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => ['discounted_price' => ['Discounted price must be less than regular price']]
                ], 422);
            }

            // Calculate discount_percentage if discounted_price is provided and discount_percentage isn't
            if (isset($validated['discounted_price']) && !isset($validated['discount_percentage'])) {
                $validated['discount_percentage'] = round((1 - ($validated['discounted_price'] / $validated['price'])) * 100, 2);
            }

            $product = new Product();
            $product->name = $validated['name'];
            $product->description = $validated['description'] ?? "";
            $product->character_count = $validated['character_count'] ?? strlen($validated['description'] ?? '');
            $product->category_id = $validated['category_id'];
            $product->price = $validated['price'];
            $product->discount_percentage = $validated['discount_percentage'] ?? 0;
            $product->status = $validated['status'];
            $product->daily_deals = $validated['daily_deals'] ?? 'deactive';
            
            // Handle image uploads
            $images = [];
            
            if ($request->hasFile('image1')) {
                $path = $request->file('image1')->store('products', 'public');
                $images[] = $path;
            }
            
            if ($request->hasFile('image2')) {
                $path = $request->file('image2')->store('products', 'public');
                $images[] = $path;
            }
            
            if ($request->hasFile('image3')) {
                $path = $request->file('image3')->store('products', 'public');
                $images[] = $path;
            }
            
            $product->images = $images;
            $product->save();
            
            // Load the category relationship for the response
            $product->load('category');

            return response()->json($product, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified product.
     * 
     * @OA\Get(
     *     path="/api/product/single/{id}",
     *     summary="Get product details by ID",
     *     description="Returns the details of a specific product by ID (public endpoint)",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product details",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(
     *         response=404, 
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product not found")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        $product = Product::with('category')->find($id);
        
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        
        return response()->json($product);
    }

    /**
     * Update the specified product.
     * 
     * @OA\Put(
     *     path="/api/admin/products/{product}",
     *     summary="Update product",
     *     description="Updates an existing product (all fields are optional)",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string", maxLength=255, example="Updated Smartphone XYZ"),
     *                 @OA\Property(property="description", type="string", example="Updated description"),
     *                 @OA\Property(property="character_count", type="integer", minimum=0, example=50, description="Character count of description (auto-calculated if not provided)"),
     *                 @OA\Property(property="image1", type="string", format="binary", description="First product image"),
     *                 @OA\Property(property="image2", type="string", format="binary", description="Second product image"),
     *                 @OA\Property(property="image3", type="string", format="binary", description="Third product image"),
     *                 @OA\Property(property="category_id", type="integer", example=2),
     *                 @OA\Property(property="price", type="number", format="float", minimum=0, example=899.99),
     *                 @OA\Property(property="discount_percentage", type="number", format="float", minimum=0, maximum=100, example=15),
     *                 @OA\Property(property="discounted_price", type="number", format="float", minimum=0, example=799.99, description="Direct discounted price (alternative to discount_percentage)"),
     *                 @OA\Property(property="status", type="string", enum={"deactive", "in_stock", "out_of_stock"}, example="in_stock"),
     *                 @OA\Property(property="daily_deals", type="string", enum={"active", "deactive"}, example="active", description="Daily deals status"),
     *                 @OA\Property(property="_method", type="string", default="PUT", example="PUT")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(
     *         response=404, 
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422, 
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error updating product"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, Product $product)
    {
        try {
            $rules = [];
            
            if ($request->has('name')) {
                $rules['name'] = 'string|max:255';
            }
            
            if ($request->has('description')) {
                $rules['description'] = 'nullable|string';
            }
            
            if ($request->has('character_count')) {
                $rules['character_count'] = 'nullable|integer|min:0';
            }
            
            if ($request->has('category_id')) {
                $rules['category_id'] = 'integer|exists:categories,id';
            }
            
            if ($request->has('price')) {
                $rules['price'] = 'numeric|min:0';
            }
            
            if ($request->has('discount_percentage')) {
                $rules['discount_percentage'] = 'nullable|numeric|min:0|max:100';
            }
            
            if ($request->has('discounted_price')) {
                $rules['discounted_price'] = 'nullable|numeric|min:0';
            }
            
            if ($request->has('status')) {
                $rules['status'] = Rule::in(['deactive', 'in_stock', 'out_of_stock']);
            }
            
            if ($request->has('daily_deals')) {
                $rules['daily_deals'] = Rule::in(['active', 'deactive']);
            }
            
            if ($request->hasFile('image1')) {
                $rules['image1'] = 'image|mimes:jpeg,png,jpg,gif|max:2048';
            }
            
            if ($request->hasFile('image2')) {
                $rules['image2'] = 'image|mimes:jpeg,png,jpg,gif|max:2048';
            }
            
            if ($request->hasFile('image3')) {
                $rules['image3'] = 'image|mimes:jpeg,png,jpg,gif|max:2048';
            }
            
            $validated = $request->validate($rules);
            
            // Validate that discounted_price is less than regular price if both provided
            if (isset($validated['discounted_price']) && 
                isset($validated['price']) && 
                $validated['discounted_price'] >= $validated['price']) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => ['discounted_price' => ['Discounted price must be less than regular price']]
                ], 422);
            } else if (isset($validated['discounted_price']) && 
                     !isset($validated['price']) && 
                     $validated['discounted_price'] >= $product->price) {
                return response()->json([
                    'message' => 'Validation failed', 
                    'errors' => ['discounted_price' => ['Discounted price must be less than regular price']]
                ], 422);
            }
            
            // Calculate discount_percentage if discounted_price is provided
            if (isset($validated['discounted_price'])) {
                $price = $validated['price'] ?? $product->price;
                $validated['discount_percentage'] = round((1 - ($validated['discounted_price'] / $price)) * 100, 2);
            }
            
            // Update basic fields
            if ($request->has('name')) {
                $product->name = $validated['name'];
            }
            
            if ($request->has('description')) {
                $product->description = $validated['description'] ?? "";
                // Auto-calculate character count if not explicitly provided
                if (!$request->has('character_count')) {
                    $product->character_count = strlen($validated['description'] ?? '');
                }
            }
            
            if ($request->has('character_count')) {
                $product->character_count = $validated['character_count'];
            }
            
            if ($request->has('category_id')) {
                $product->category_id = $validated['category_id'];
            }
            
            if ($request->has('price')) {
                $product->price = $validated['price'];
            }
            
            if ($request->has('discount_percentage')) {
                $product->discount_percentage = $validated['discount_percentage'];
            }
            
            if ($request->has('status')) {
                $product->status = $validated['status'];
            }
            
            if ($request->has('daily_deals')) {
                $product->daily_deals = $validated['daily_deals'];
            }
            
            // Handle image updates
            $images = $product->images ?? [];
            
            if ($request->hasFile('image1')) {
                // Delete old image if it exists
                if (isset($images[0]) && Storage::disk('public')->exists($images[0])) {
                    Storage::disk('public')->delete($images[0]);
                }
                
                $path = $request->file('image1')->store('products', 'public');
                $images[0] = $path;
            }
            
            if ($request->hasFile('image2')) {
                if (isset($images[1]) && Storage::disk('public')->exists($images[1])) {
                    Storage::disk('public')->delete($images[1]);
                }
                
                $path = $request->file('image2')->store('products', 'public');
                $images[1] = $path;
            }
            
            if ($request->hasFile('image3')) {
                if (isset($images[2]) && Storage::disk('public')->exists($images[2])) {
                    Storage::disk('public')->delete($images[2]);
                }
                
                $path = $request->file('image3')->store('products', 'public');
                $images[2] = $path;
            }
            
            $product->images = $images;
            $product->save();
            
            // Load the category relationship for the response
            $product->load('category');
            
            return response()->json($product);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified product.
     * 
     * @OA\Delete(
     *     path="/api/admin/products/{product}",
     *     summary="Delete product",
     *     description="Deletes a product and all associated images",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=204, 
     *         description="Product deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404, 
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product not found")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function destroy(Product $product)
    {
        // Delete all product images
        if (!empty($product->images)) {
            foreach ($product->images as $image) {
                if (Storage::disk('public')->exists($image)) {
                    Storage::disk('public')->delete($image);
                }
            }
        }
        
        $product->delete();
        return response()->json(null, 204);
    }

    /**
     * Search for products by various criteria.
     * 
     * @OA\Get(
     *     path="/api/products/search/{limit?}",
     *     summary="Search products with optional limit",
     *     description="Search products by name, category, price range or status (public endpoint)",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="limit",
     *         in="path",
     *         description="Maximum number of products to return (default: 20, max: 100)",
     *         required=false,
     *         @OA\Schema(type="integer", default=20, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Search term for product name or description",
     *         required=false,
     *         @OA\Schema(type="string", example="smartphone")
     *     ),
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Filter by category ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="min_price",
     *         in="query",
     *         description="Minimum price",
     *         required=false,
     *         @OA\Schema(type="number", format="float", minimum=0, example=100.00)
     *     ),
     *     @OA\Parameter(
     *         name="max_price",
     *         in="query",
     *         description="Maximum price",
     *         required=false,
     *         @OA\Schema(type="number", format="float", minimum=0, example=1000.00)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Product status (only active products shown)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"in_stock", "out_of_stock"}, example="in_stock")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of products matching search criteria",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Product")
     *         )
     *     )
     * )
     */
    public function search(Request $request, $limit = 20)
    {
        // Validate and constrain the limit
        $limit = is_numeric($limit) ? (int)$limit : 20;
        $limit = min(max($limit, 1), 100);  // Between 1 and 100
        
        $query = Product::with('category')
                ->where('status', '!=', 'deactive');
        
        // Search in name or description
        if ($request->has('q')) {
            $searchTerm = $request->input('q');
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }
        
        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        
        // Filter by price range
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->input('min_price'));
        }
        
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->input('max_price'));
        }
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        
        $products = $query->limit($limit)->get();
        
        return response()->json($products);
    }

    /**
     * Display a listing of daily deals products.
     * 
     * @OA\Get(
     *     path="/api/products/daily-deals",
     *     summary="Get daily deals products",
     *     description="Returns a list of active products marked as daily deals",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Maximum number of products to return (default: 20, max: 100)",
     *         required=false,
     *         @OA\Schema(type="integer", default=20, minimum=1, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of daily deals products",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Product")
     *         )
     *     )
     * )
     */
 public function getDailyDeals(Request $request)
{
    // Get limit from query parameter, default to 20
    $limit = $request->query('limit', 20);
    
    // Validate and constrain the limit
    $limit = is_numeric($limit) ? (int)$limit : 20;
    $limit = min(max($limit, 1), 100);  // Between 1 and 100
    
    $products = Product::where('daily_deals', 'active')
        ->where('status', '!=', 'deactive') // Also exclude deactivated products
        ->with('category')
        ->orderBy('created_at', 'desc')
        ->limit($limit)
        ->get();
    
    return response()->json($products);
}
}