<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="Categories",
 *     description="API endpoints for category management"
 * )
 */
class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     * 
     * @OA\Get(
     *     path="/api/categories",
     *     summary="Get all categories",
     *     description="Returns a list of all categories (public endpoint)",
     *     tags={"Categories"},
     *     @OA\Response(
     *         response=200,
     *         description="List of categories",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Electronics"),
     *                 @OA\Property(property="img", type="string", example="categories/electronics.jpg"),
     *                 @OA\Property(property="status", type="boolean", example=true, description="Category status: true=active, false=inactive"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $categories = Category::all();
        return response()->json($categories);
    }

    /**
     * Store a newly created category.
     * 
     * @OA\Post(
     *     path="/api/admin/categories",
     *     summary="Create a new category",
     *     description="Creates a new category with name, image and status",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","img"},
     *                 @OA\Property(property="name", type="string", example="Electronics"),
     *                 @OA\Property(property="img", type="file", format="binary", description="Category image"),
     *                 @OA\Property(property="status", type="boolean", example=true, description="Category status: true=active, false=inactive")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Category created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Electronics"),
     *             @OA\Property(property="img", type="string", example="categories/electronics.jpg"),
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories',
                'img' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                'status' => 'boolean'
            ]);

            $category = new Category();
            $category->name = $validated['name'];
            $category->status = $request->has('status') ? $validated['status'] : true; // Default to active if not provided
            
            if ($request->hasFile('img')) {
                // Store the file in the 'categories' directory within the public storage
                $path = $request->file('img')->store('categories', 'public');
                $category->img = $path;
            }
            
            $category->save();

            return response()->json($category, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified category.
     * 
     * @OA\Get(
     *     path="/api/categories/{category}",
     *     summary="Get category details",
     *     description="Returns the details of a specific category (public endpoint)",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         description="Category ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category details",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Electronics"),
     *             @OA\Property(property="img", type="string", example="categories/electronics.jpg"),
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Category not found")
     * )
     */
    public function show(Category $category)
    {
        return response()->json($category);
    }

    /**
     * Update the specified category.
     * 
     * @OA\Post(
     *     path="/api/admin/categories/{category}",
     *     summary="Update category",
     *     description="Updates an existing category",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         description="Category ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string", example="Updated Electronics"),
     *                 @OA\Property(property="img", type="file", format="binary", description="Category image"),
     *                 @OA\Property(property="status", type="boolean", example=true),
     *                 @OA\Property(property="_method", type="string", default="PUT", example="PUT")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Updated Electronics"),
     *             @OA\Property(property="img", type="string", example="categories/updated-electronics.jpg"),
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Category not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function update(Request $request, Category $category)
    {
        try {
            $rules = [];
            
            if ($request->has('name')) {
                $rules['name'] = 'required|string|max:255|unique:categories,name,'.$category->id;
            }
            
            if ($request->hasFile('img')) {
                $rules['img'] = 'required|image|mimes:jpeg,png,jpg,gif|max:2048';
            }

            if ($request->has('status')) {
                $rules['status'] = 'boolean';
            }
            
            $validated = $request->validate($rules);
            
            if ($request->has('name')) {
                $category->name = $validated['name'];
            }
            
            if ($request->hasFile('img')) {
                // Delete the old image if it exists
                if ($category->img && Storage::disk('public')->exists($category->img)) {
                    Storage::disk('public')->delete($category->img);
                }
                
                // Store the new image
                $path = $request->file('img')->store('categories', 'public');
                $category->img = $path;
            }

            if ($request->has('status')) {
                $category->status = $validated['status'];
            }
            
            $category->save();
            
            return response()->json($category);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle category status (activate/deactivate).
     * 
     * @OA\Patch(
     *     path="/api/admin/categories/{category}/toggle-status",
     *     summary="Toggle category status",
     *     description="Activates or deactivates a category",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         description="Category ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category status toggled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Electronics"),
     *             @OA\Property(property="img", type="string", example="categories/electronics.jpg"),
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Category not found"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function toggleStatus(Category $category)
    {
        $category->status = !$category->status;
        $category->save();
        
        return response()->json($category);
    }

    /**
     * Remove the specified category.
     * 
     * @OA\Delete(
     *     path="/api/admin/categories/{category}",
     *     summary="Delete category",
     *     description="Deletes a category",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="category",
     *         in="path",
     *         description="Category ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(response=204, description="Category deleted successfully"),
     *     @OA\Response(response=404, description="Category not found"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function destroy(Category $category)
    {
        $category->delete();
        return response()->json(null, 204);
    }
}