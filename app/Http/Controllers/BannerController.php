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
     * Get the current active banners
     * 
     * @OA\Get(
     *     path="/api/banner",
     *     summary="Get active banners",
     *     description="Returns the currently active mobile and desktop banners",
     *     operationId="getBanners",
     *     tags={"Banners"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="mobile", ref="#/components/schemas/Banner"),
     *             @OA\Property(property="desktop", ref="#/components/schemas/Banner")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No active banners found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No active banners found")
     *         )
     *     )
     * )
     */
    public function show()
    {
        $mobileBanner = Banner::where('is_active', true)->where('type', 'mobile')->first();
        $desktopBanner = Banner::where('is_active', true)->where('type', 'desktop')->first();
        
        if (!$mobileBanner && !$desktopBanner) {
            return response()->json(['message' => 'No active banners found'], 404);
        }
        
        return response()->json([
            'mobile' => $mobileBanner,
            'desktop' => $desktopBanner
        ]);
    }

    /**
     * Store a new banner and replace any existing one of the same type
     * 
     * @OA\Post(
     *     path="/api/admin/banner",
     *     summary="Upload a new banner",
     *     description="Uploads a new banner and replaces any existing one of the same type",
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
     *                     property="type",
     *                     type="string",
     *                     enum={"mobile", "desktop"},
     *                     description="Banner type",
     *                     example="desktop"
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
            'type' => 'required|in:mobile,desktop',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'link' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Handle the file upload
        $imagePath = $request->file('image')->store('banners', 'public');
        
        // Deactivate existing banners of the same type
        Banner::where('is_active', true)
              ->where('type', $request->type)
              ->update(['is_active' => false]);
        
        // Delete old banner images of the same type to save space
        $oldBanners = Banner::where('is_active', false)
                           ->where('type', $request->type)
                           ->get();
        foreach ($oldBanners as $oldBanner) {
            // Remove the file
            if (Storage::disk('public')->exists($oldBanner->image_path)) {
                Storage::disk('public')->delete($oldBanner->image_path);
            }
        }
        
        // Delete old banner records of the same type
        Banner::where('is_active', false)
              ->where('type', $request->type)
              ->delete();

        // Create the new banner
        $banner = Banner::create([
            'image_path' => $imagePath,
            'type' => $request->type,
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'link' => $request->link,
            'is_active' => true
        ]);

        return response()->json(['message' => 'Banner created successfully', 'banner' => $banner], 201);
    }

    /**
     * Delete banner by type
     * 
     * @OA\Delete(
     *     path="/api/admin/banner/{type}",
     *     summary="Delete banner by type",
     *     description="Deletes the active banner of the specified type",
     *     operationId="deleteBanner",
     *     tags={"Banners"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="path",
     *         description="Banner type",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             enum={"mobile", "desktop"}
     *         )
     *     ),
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
    public function destroy($type)
    {
        if (!in_array($type, ['mobile', 'desktop'])) {
            return response()->json(['message' => 'Invalid banner type'], 400);
        }

        $banner = Banner::where('is_active', true)
                       ->where('type', $type)
                       ->first();
        
        if (!$banner) {
            return response()->json(['message' => 'No active banner found for this type'], 404);
        }
        
        // Delete the image file
        if (Storage::disk('public')->exists($banner->image_path)) {
            Storage::disk('public')->delete($banner->image_path);
        }
        
        $banner->delete();
        
        return response()->json(['message' => 'Banner deleted successfully']);
    }
}