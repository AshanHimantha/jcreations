<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Banners",
 *     description="API endpoints for managing banners"
 * )
 */
class BannerController extends Controller
{
    /**
     * Get the current active banner
     * 
     * @OA\Get(
     *     path="/api/banner",
     *     summary="Get active banner",
     *     description="Returns the currently active banner",
     *     operationId="getBanner",
     *     tags={"Banners"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Banner")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No active banner found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No active banner found")
     *         )
     *     )
     * )
     */
    public function show()
    {
        $banner = Banner::where('is_active', true)->first();
        
        if (!$banner) {
            return response()->json(['message' => 'No active banner found'], 404);
        }
        
        return response()->json($banner);
    }

    /**
     * Store a new banner and replace any existing one
     * 
     * @OA\Post(
     *     path="/api/admin/banner",
     *     summary="Upload a new banner",
     *     description="Uploads a new banner and replaces any existing one",
     *     operationId="storeBanner",
     *     tags={"Banners"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="image",
     *                     type="string",
     *                     format="binary",
     *                     description="Banner image file (max 2MB)"
     *                 ),
     *                 @OA\Property(
     *                     property="title",
     *                     type="string",
     *                     description="Banner title",
     *                     example="Summer Sale"
     *                 ),
     *                 @OA\Property(
     *                     property="subtitle",
     *                     type="string",
     *                     description="Banner subtitle",
     *                     example="Up to 50% off"
     *                 ),
     *                 @OA\Property(
     *                     property="link",
     *                     type="string",
     *                     description="Banner link URL",
     *                     example="https://example.com/sale"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Banner created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Banner created successfully"),
     *             @OA\Property(property="banner", ref="#/components/schemas/Banner")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|max:2048', // Max 2MB
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'link' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Handle the file upload
        $imagePath = $request->file('image')->store('banners', 'public');
        
        // Deactivate all existing banners
        Banner::where('is_active', true)->update(['is_active' => false]);
        
        // Delete old banner images to save space
        $oldBanners = Banner::where('is_active', false)->get();
        foreach ($oldBanners as $oldBanner) {
            // Remove the file
            if (Storage::disk('public')->exists($oldBanner->image_path)) {
                Storage::disk('public')->delete($oldBanner->image_path);
            }
        }
        
        // Delete old banner records
        Banner::where('is_active', false)->delete();

        // Create the new banner
        $banner = Banner::create([
            'image_path' => $imagePath,
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'link' => $request->link,
            'is_active' => true
        ]);

        return response()->json(['message' => 'Banner created successfully', 'banner' => $banner], 201);
    }

    /**
     * Delete the current banner
     * 
     * @OA\Delete(
     *     path="/api/admin/banner",
     *     summary="Delete active banner",
     *     description="Deletes the currently active banner",
     *     operationId="deleteBanner",
     *     tags={"Banners"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Banner deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Banner deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No active banner found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No active banner found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function destroy()
    {
        $banner = Banner::where('is_active', true)->first();
        
        if (!$banner) {
            return response()->json(['message' => 'No active banner found'], 404);
        }
        
        // Delete the image file
        if (Storage::disk('public')->exists($banner->image_path)) {
            Storage::disk('public')->delete($banner->image_path);
        }
        
        $banner->delete();
        
        return response()->json(['message' => 'Banner deleted successfully']);
    }
}