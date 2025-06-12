<?php

namespace App\Http\Controllers;

use App\Models\CodLimit;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="COD Limit",
 *     description="API endpoints for Cash on Delivery limit management"
 * )
 */
class CodLimitController extends Controller
{
    /**
     * Display the current COD limit.
     * 
     * @OA\Get(
     *     path="/api/cod-limit",
     *     summary="Get current COD limit",
     *     description="Returns the current cash on delivery limit settings (public endpoint)",
     *     tags={"COD Limit"},
     *     @OA\Response(
     *         response=200,
     *         description="COD limit retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="limit_amount", type="string", example="5000.00"),
     *                 @OA\Property(property="is_active", type="boolean", example=true, description="COD limit status: true=active, false=inactive"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="COD limit not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="COD limit not found")
     *         )
     *     )
     * )
     */
    public function show(): JsonResponse
    {
        $codLimit = CodLimit::getCurrentLimit();
        
        if (!$codLimit) {
            return response()->json([
                'message' => 'COD limit not found'
            ], 404);
        }

        return response()->json([
            'data' => $codLimit
        ]);
    }

    /**
     * Update the COD limit.
     * 
     * @OA\Put(
     *     path="/api/admin/cod-limit",
     *     summary="Update COD limit",
     *     description="Updates the cash on delivery limit amount and status",
     *     tags={"COD Limit"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"limit_amount"},
     *             @OA\Property(property="limit_amount", type="number", format="decimal", example=7500.00, description="The COD limit amount"),
     *             @OA\Property(property="is_active", type="boolean", example=true, description="COD limit status: true=active, false=inactive")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="COD limit updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="COD limit updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="limit_amount", type="string", example="7500.00"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="limit_amount",
     *                     type="array",
     *                     @OA\Items(type="string", example="The limit amount field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'limit_amount' => 'required|numeric|min:0',
                'is_active' => 'sometimes|boolean'
            ]);

            $codLimit = CodLimit::getCurrentLimit();
            
            if (!$codLimit) {
                // Create if doesn't exist
                $codLimit = CodLimit::create([
                    'limit_amount' => $validated['limit_amount'],
                    'is_active' => $request->get('is_active', true)
                ]);
            } else {
                // Update existing
                $codLimit->update([
                    'limit_amount' => $validated['limit_amount'],
                    'is_active' => $request->get('is_active', $codLimit->is_active)
                ]);
            }

            return response()->json([
                'message' => 'COD limit updated successfully',
                'data' => $codLimit->fresh()
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating COD limit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle COD limit status (activate/deactivate).
     * 
     * @OA\Patch(
     *     path="/api/admin/cod-limit/toggle-status",
     *     summary="Toggle COD limit status",
     *     description="Activates or deactivates the COD limit",
     *     tags={"COD Limit"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="COD limit status toggled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="COD limit status toggled successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="limit_amount", type="string", example="5000.00"),
     *                 @OA\Property(property="is_active", type="boolean", example=false),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="COD limit not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="COD limit not found")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function toggleStatus(): JsonResponse
    {
        $codLimit = CodLimit::getCurrentLimit();
        
        if (!$codLimit) {
            return response()->json([
                'message' => 'COD limit not found'
            ], 404);
        }

        $codLimit->update(['is_active' => !$codLimit->is_active]);

        return response()->json([
            'message' => 'COD limit status toggled successfully',
            'data' => $codLimit->fresh()
        ]);
    }
}